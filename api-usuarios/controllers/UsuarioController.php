<?php

require_once __DIR__ . "/../models/Usuario.php";
require_once __DIR__ . "/../core/Response.php";
require_once __DIR__ . "/../core/Auth.php";
require_once __DIR__ . "/../core/Validacion.php";
require_once __DIR__ . "/../core/IntentosLogin.php";
require_once __DIR__ . "/../core/PoliticaBloqueo.php";
require_once __DIR__ . "/../core/LimitePeticion.php";
require_once __DIR__ . "/../core/FiltrosUsuario.php";
require_once __DIR__ . "/../core/Paginacion.php";

class UsuarioController
{
    public function listar(): void
    {
        Auth::exigirRol("Administrador");

        $consulta = FiltrosUsuario::de($_GET);
        if ($consulta["error"] !== null) {
            Response::error($consulta["error"], 400);
        }
        $pagina = Paginacion::de($_GET);
        if ($pagina["error"] !== null) {
            Response::error($pagina["error"], 400);
        }
        if (!$pagina["paginar"]) {
            Response::json(["ok" => true, "datos" => Usuario::listar($consulta["filtros"])]);
        }
        Response::json([
            "ok"    => true,
            "datos" => Usuario::listar($consulta["filtros"], $pagina),
            "meta"  => Paginacion::meta(Usuario::contar($consulta["filtros"]), $pagina["pagina"], $pagina["por_pagina"]),
        ]);
    }

    public function obtener(int $id): void
    {
        Auth::exigirRol("Administrador");

        $usuario = Usuario::obtener($id);
        if ($usuario === null) {
            Response::error("Usuario no encontrado", 404);
        }
        Response::json(["ok" => true, "datos" => $usuario]);
    }

    // El centro de acopio es solo para el Operario: tiene que existir y el rol final tiene que ser Operario.
    private function errorDeCentro(array $datos, string $rolFinal): ?string
    {
        $centro = $datos["id_centro"] ?? null;
        if ($centro === null || $centro === "") {
            return null;
        }
        $esEntero = is_int($centro) || (is_string($centro) && ctype_digit($centro));
        if (!$esEntero || (int) $centro < 1 || !Usuario::existeCentro((int) $centro)) {
            return "El centro de acopio indicado no existe";
        }
        return $rolFinal === "Operario" ? null : "Solo un Operario se asocia a un centro de acopio";
    }

    // La cuadrilla es solo para el rol Cuadrilla, y ese rol la necesita: sin cuadrilla el usuario no veria ninguna ruta.
    // $actual es el usuario que se edita (null al crear): si no se manda id_cuadrilla se conserva la que ya tenia.
    private function errorDeCuadrilla(array $datos, string $rolFinal, ?array $actual): ?string
    {
        $manda = array_key_exists("id_cuadrilla", $datos);
        $cuadrilla = $datos["id_cuadrilla"] ?? null;
        $vacia = $cuadrilla === null || $cuadrilla === "";

        if ($rolFinal !== "Cuadrilla") {
            return $manda && !$vacia ? "Solo un usuario con rol Cuadrilla se asocia a una cuadrilla" : null;
        }
        if (!$manda) {
            return ($actual !== null && $actual["rol"] === "Cuadrilla" && !empty($actual["id_cuadrilla"]))
                ? null : "Elegí la cuadrilla a la que pertenece: sin ella no ve ninguna ruta";
        }
        if ($vacia) {
            return "Elegí la cuadrilla a la que pertenece: sin ella no ve ninguna ruta";
        }
        $esEntero = is_int($cuadrilla) || (is_string($cuadrilla) && ctype_digit($cuadrilla));
        if (!$esEntero || (int) $cuadrilla < 1 || !Usuario::existeCuadrilla((int) $cuadrilla)) {
            return "La cuadrilla indicada no existe";
        }
        $igualALaActual = $actual !== null && (int) ($actual["id_cuadrilla"] ?? 0) === (int) $cuadrilla;
        if (!$igualALaActual && !Usuario::cuadrillaActiva((int) $cuadrilla)) {
            return "La cuadrilla indicada está dada de baja";
        }
        return null;
    }

    // La cedula es obligatoria para el rol Cuadrilla (con ella inicia sesion) y opcional para los demas.
    // $actual es el usuario que se edita (null al crear): si no se manda cedula se conserva la que ya tenia.
    private function exigirCedula(array $datos, string $rolFinal, ?array $actual): void
    {
        $manda = array_key_exists("cedula", $datos);
        $valor = $datos["cedula"] ?? null;
        $vacia = $valor === null || (is_string($valor) && trim($valor) === "");

        if ($vacia) {
            $conserva = !$manda && $actual !== null && !empty($actual["cedula"]);
            if ($rolFinal === "Cuadrilla" && !$conserva) {
                Response::error("Ingresá la cédula: la cuadrilla inicia sesión con ella", 400);
            }
            return;
        }
        if (!Validacion::cedula($valor)) {
            Response::error("La cédula no es válida: revisá los dígitos (con el verificador, sin puntos ni guion)", 400);
        }
        if (Usuario::existeCedula(Validacion::normalizarCedula((string) $valor), $actual === null ? null : (int) $actual["id_usuario"])) {
            Response::error("Ya existe un usuario con esa cédula", 409);
        }
    }

    // El administrador define la contrasena inicial (o la nueva, al restablecerla): se la da a la persona en mano.
    private function exigirContrasena(array $datos): void
    {
        if (!is_string($datos["password"] ?? null) || !Validacion::contrasena($datos["password"])) {
            Response::error(
                "Definí una contraseña de al menos " . Validacion::CLAVE_MINIMA . " caracteres",
                400
            );
        }
    }

    public function crear(): void
    {
        Auth::exigirRol("Administrador");
        $datos = Response::leerJson();

        if (empty($datos["nombre"]) || empty($datos["email"])) {
            Response::error("Faltan datos: nombre y email", 400);
        }
        if (!Validacion::email($datos["email"])) {
            Response::error("El email no tiene un formato válido", 400);
        }
        if (Usuario::existeEmail($datos["email"])) {
            Response::error("Ya existe un usuario con ese email", 409);
        }
        $this->exigirContrasena($datos);

        $rol = $datos["rol"] ?? "Vecino";
        if (!in_array($rol, Usuario::ROLES, true)) {
            Response::error("Rol inválido. Valores admitidos: " . implode(", ", Usuario::ROLES), 400);
        }

        $errorCentro = $this->errorDeCentro($datos, $rol);
        if ($errorCentro !== null) {
            Response::error($errorCentro, 400);
        }
        $errorCuadrilla = $this->errorDeCuadrilla($datos, $rol, null);
        if ($errorCuadrilla !== null) {
            Response::error($errorCuadrilla, 400);
        }
        $this->exigirCedula($datos, $rol, null);

        $nuevo = Usuario::crear($datos, $rol);
        Response::json(["ok" => true, "mensaje" => "Usuario creado. Ya puede iniciar sesión con la contraseña que le definiste.", "datos" => $nuevo], 201);
    }

    public function actualizar(int $id): void
    {
        Auth::exigirRol("Administrador");
        $datos = Response::leerJson();

        $actual = Usuario::obtener($id);
        if ($actual === null) {
            Response::error("Usuario no encontrado", 404);
        }

        if (isset($datos["email"])) {
            if (!Validacion::email($datos["email"])) {
                Response::error("El email no tiene un formato válido", 400);
            }
            if ($datos["email"] !== $actual["email"] && Usuario::existeEmail($datos["email"])) {
                Response::error("Ya existe un usuario con ese email", 409);
            }
        }

        $rol = $datos["rol"] ?? null;
        if ($rol !== null && !in_array($rol, Usuario::ROLES, true)) {
            Response::error(
                "Rol inválido. Valores admitidos: " . implode(", ", Usuario::ROLES),
                400
            );
        }

        $errorCentro = $this->errorDeCentro($datos, $rol ?? $actual["rol"]);
        if ($errorCentro !== null) {
            Response::error($errorCentro, 400);
        }
        $errorCuadrilla = $this->errorDeCuadrilla($datos, $rol ?? $actual["rol"], $actual);
        if ($errorCuadrilla !== null) {
            Response::error($errorCuadrilla, 400);
        }
        $this->exigirCedula($datos, $rol ?? $actual["rol"], $actual);

        if (array_key_exists("password", $datos)) {
            if (!is_string($datos["password"]) || !Validacion::contrasena($datos["password"])) {
                Response::error(
                    "La contraseña tiene que tener al menos " . Validacion::CLAVE_MINIMA . " caracteres",
                    400
                );
            }
        }

        $actualizado = Usuario::actualizar($id, $datos, $rol);
        Response::json(["ok" => true, "mensaje" => "Usuario actualizado", "datos" => $actualizado]);
    }

    public function darDeBaja(int $id): void
    {
        $administrador = Auth::exigirRol("Administrador");

        $usuario = Usuario::obtener($id);
        if ($usuario === null) {
            Response::error("Usuario no encontrado", 404);
        }
        if ($usuario["estado"] === "inactivo") {
            Response::error("El usuario ya estaba inactivo", 409);
        }

        if ((int) $administrador["id_usuario"] === $id) {
            Response::error("No podés darte de baja tu propia cuenta", 409);
        }

        $baja = Usuario::darDeBaja($id);
        Response::json(["ok" => true, "mensaje" => "Usuario dado de baja", "datos" => $baja]);
    }

    public function login(): void
    {
        $datos = Response::leerJson();
        // La cuadrilla entra solo con cedula; el resto, con correo. El limite de intentos cuenta por lo que se uso para entrar
        // (la cedula va solo con digitos, asi que nunca se confunde con un correo).
        $porCedula = array_key_exists("cedula", $datos);
        $email = $porCedula
            ? substr((string) Validacion::soloDigitosCedula($datos["cedula"]), 0, 20)
            : PoliticaBloqueo::normalizar($datos["email"] ?? "");
        $clave = is_string($datos["password"] ?? null) ? $datos["password"] : "";

        // Sin ningun digito no hay cedula que buscar ni cuenta que bloquear: se responde igual que si no coincidiera.
        if ($porCedula && $email === "") {
            Response::error("La cédula o la contraseña no coinciden", 401);
        }

        $espera = IntentosLogin::esperaPara($email);
        if ($espera > 0) {
            $minutos = (int) ceil($espera / 60);
            header("Retry-After: " . $espera);
            Response::error(
                "Demasiados intentos fallidos. Probá de nuevo en " . $minutos . ($minutos === 1 ? " minuto." : " minutos."),
                429
            );
        }

        $usuario = Usuario::login($email, $clave, $porCedula);
        // Una cuenta de cuadrilla entra solo con cedula: con el correo no se abre sesion, aunque la contrasena sea la correcta.
        $cuadrillaPorCorreo = !$porCedula && $usuario !== null && $usuario["rol"] === "Cuadrilla";
        IntentosLogin::registrar($email, $usuario !== null && !$cuadrillaPorCorreo);
        if ($cuadrillaPorCorreo) {
            Response::error("Las cuadrillas ingresan con su cédula: usá la pestaña «Cuadrilla».", 403);
        }

        if ($usuario === null) {
            Response::error($porCedula ? "La cédula o la contraseña no coinciden" : "El email o la contraseña no coinciden", 401);
        }

        Auth::iniciarSesion($usuario);
        Response::json(["ok" => true, "mensaje" => "Login correcto", "datos" => $usuario]);
    }

    public function logout(): void
    {
        Auditoria::recordarUsuario(Auth::identificar());
        Auth::cerrarSesion();
        Response::json(["ok" => true, "mensaje" => "Sesión cerrada"]);
    }

    // Registro de auditoria (solo Administrador): siempre paginado, de lo mas nuevo a lo mas viejo.
    public function auditoria(): void
    {
        Auth::exigirRol("Administrador");

        $consulta = Auditoria::filtrosDe($_GET);
        if ($consulta["error"] !== null) {
            Response::error($consulta["error"], 400);
        }
        $pagina = Paginacion::de($_GET);
        if ($pagina["error"] !== null) {
            Response::error($pagina["error"], 400);
        }
        $numero = $pagina["paginar"] ? $pagina["pagina"] : 1;
        $porPagina = $pagina["paginar"] ? $pagina["por_pagina"] : Paginacion::POR_PAGINA_OMITIDO;

        Response::json([
            "ok"    => true,
            "datos" => Auditoria::listar($consulta["filtros"], $numero, $porPagina),
            "meta"  => Paginacion::meta(Auditoria::contar($consulta["filtros"]), $numero, $porPagina),
        ]);
    }

    public function sesion(): void
    {
        $usuario = Auth::identificar();
        if ($usuario === null) {
            Response::error("No hay una sesión iniciada", 401);
        }
        if ($usuario["estado"] !== "activo") {
            Response::error("La cuenta no está activa", 403);
        }
        Response::json(["ok" => true, "datos" => $usuario]);
    }

    public function registrarVecino(): void
    {
        LimitePeticion::exigir("registro", 10, 3600);
        $datos = Response::leerJson();

        if (empty($datos["nombre"]) || empty($datos["apellido"]) || empty($datos["email"])) {
            Response::error("Faltan datos: nombre, apellido y email", 400);
        }
        if (!is_string($datos["email"]) || !Validacion::email($datos["email"])) {
            Response::error("El email no tiene un formato válido", 400);
        }
        if (!is_string($datos["password"] ?? null) || !Validacion::contrasena($datos["password"])) {
            Response::error(
                "La contraseña tiene que tener al menos " . Validacion::CLAVE_MINIMA . " caracteres",
                400
            );
        }
        if (Usuario::existeEmail($datos["email"])) {
            Response::error("Ya existe un usuario con ese email", 409);
        }

        unset($datos["cedula"]); // el vecino se registra solo con correo: la cedula es de la cuadrilla
        $nuevo = Usuario::crear($datos, "Vecino");
        Response::json([
            "ok" => true,
            "mensaje" => "Cuenta creada. Ya podés iniciar sesión.",
            "datos" => $nuevo,
        ], 201);
    }

    public function crearSolicitud(): void
    {
        LimitePeticion::exigir("solicitud", 10, 3600);
        $datos = Response::leerJson();

        if (empty($datos["nombre"]) || empty($datos["email"])) {
            Response::error("Faltan datos: nombre y email", 400);
        }
        if (!Validacion::email($datos["email"])) {
            Response::error("El email no tiene un formato válido", 400);
        }
        if (Usuario::existeEmail($datos["email"])) {
            Response::error("Ya existe un usuario con ese email", 409);
        }

        $tipo = $datos["tipo_cuenta"] ?? "vecino";
        if (!in_array($tipo, ["vecino", "cuadrilla"], true)) {
            Response::error("Tipo de cuenta inválido. Valores admitidos: vecino, cuadrilla", 400);
        }
        $indicada = $datos["cuadrilla_indicada"] ?? null;
        if ($indicada !== null && (!is_string($indicada) || mb_strlen(trim($indicada)) > 80)) {
            Response::error("La cuadrilla en la que trabajás no puede pasar de 80 caracteres", 400);
        }
        if ($tipo === "cuadrilla" && (!is_string($indicada) || trim($indicada) === "")) {
            Response::error("Contanos en qué cuadrilla trabajás", 400);
        }
        // La cuadrilla inicia sesion con cedula: el pedido la trae. Un vecino no la pide (si la manda, se ignora).
        $cedula = null;
        if ($tipo === "cuadrilla") {
            $crudo = $datos["cedula"] ?? null;
            if ($crudo === null || (is_string($crudo) && trim($crudo) === "")) {
                Response::error("Ingresá tu cédula: es con la que vas a iniciar sesión", 400);
            }
            if (!Validacion::cedula($crudo)) {
                Response::error("La cédula no es válida: revisá los dígitos (con el verificador, sin puntos ni guion)", 400);
            }
            $cedula = Validacion::normalizarCedula((string) $crudo);
            if (Usuario::existeCedula($cedula)) {
                Response::error("Ya existe un usuario con esa cédula", 409);
            }
            if (Usuario::haySolicitudPendienteConCedula($cedula)) {
                Response::error("Ya hay un pedido pendiente con esa cédula", 409);
            }
        }
        $datos["cedula"] = $cedula;
        $datos["tipo_cuenta"] = $tipo;
        $datos["cuadrilla_indicada"] = is_string($indicada) && trim($indicada) !== "" ? trim($indicada) : null;
        $nueva = Usuario::crearSolicitud($datos);
        Response::json([
            "ok"      => true,
            "mensaje" => "Solicitud recibida. Queda pendiente de aprobacion.",
            "datos"   => $nueva,
        ], 201);
    }

    public function listarSolicitudes(): void
    {
        Auth::exigirRol("Administrador");
        $estado = $_GET["estado"] ?? null;
        Response::json(["ok" => true, "datos" => Usuario::listarSolicitudes($estado)]);
    }

    public function resolverSolicitud(int $id): void
    {
        Auth::exigirRol("Administrador");
        $datos = Response::leerJson();
        $decision = $datos["decision"] ?? "";

        if (!in_array($decision, ["aprobada", "rechazada"], true)) {
            Response::error("La decisión tiene que ser 'aprobada' o 'rechazada'", 400);
        }

        $solicitud = Usuario::obtenerSolicitud($id);
        if ($solicitud === null) {
            Response::error("Solicitud no encontrada", 404);
        }
        if ($solicitud["estado"] !== "pendiente") {
            Response::error("La solicitud ya estaba " . $solicitud["estado"], 409);
        }

        // Quien pidio acceso como cuadrilla solo puede quedar como Cuadrilla, y el administrador elige la cuadrilla real.
        $rol = $solicitud["tipo_cuenta"] === "cuadrilla" ? "Cuadrilla" : ($datos["rol"] ?? "Vecino");
        if (!in_array($rol, Usuario::ROLES, true)) {
            Response::error("Rol inválido. Valores admitidos: " . implode(", ", Usuario::ROLES), 400);
        }
        if ($solicitud["tipo_cuenta"] === "cuadrilla" && isset($datos["rol"]) && $datos["rol"] !== "Cuadrilla") {
            Response::error("Esta solicitud es de una cuadrilla: solo se puede aprobar con el rol Cuadrilla", 400);
        }
        $idCuadrilla = null;
        if ($decision === "aprobada") {
            $errorCuadrilla = $this->errorDeCuadrilla($datos, $rol, null);
            if ($errorCuadrilla !== null) {
                Response::error($errorCuadrilla, 400);
            }
            $idCuadrilla = $rol === "Cuadrilla" ? (int) $datos["id_cuadrilla"] : null;
            $this->exigirContrasena($datos);
            // La cedula del pedido, o la que el administrador confirma o corrige al aprobar (los pedidos viejos no la traian).
            if (!array_key_exists("cedula", $datos) && !empty($solicitud["cedula"])) {
                $datos["cedula"] = $solicitud["cedula"];
            }
            $this->exigirCedula($datos, $rol, null);
            $cedula = !empty($datos["cedula"]) ? Validacion::normalizarCedula((string) $datos["cedula"]) : null;
        }

        [$actualizada, $usuario] = Usuario::resolverSolicitud($id, $decision, $rol, $idCuadrilla, $datos["password"] ?? null, $cedula ?? null);

        Response::json([
            "ok"      => true,
            "mensaje" => $decision === "aprobada"
                ? "Solicitud aprobada. La cuenta ya está activa con la contraseña que definiste."
                : "Solicitud rechazada",
            "datos"   => ["solicitud" => $actualizada, "usuario" => $usuario],
        ]);
    }
}
