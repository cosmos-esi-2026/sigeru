<?php

require_once __DIR__ . "/../models/Usuario.php";
require_once __DIR__ . "/../core/Response.php";
require_once __DIR__ . "/../core/Auth.php";
require_once __DIR__ . "/../core/Validacion.php";
require_once __DIR__ . "/../core/IntentosLogin.php";
require_once __DIR__ . "/../core/PoliticaBloqueo.php";
require_once __DIR__ . "/../core/Correo.php";
require_once __DIR__ . "/../core/LimitePeticion.php";
require_once __DIR__ . "/../core/FiltrosUsuario.php";
require_once __DIR__ . "/../core/Paginacion.php";
require_once __DIR__ . "/../models/EnlaceClave.php";

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

        $rol = $datos["rol"] ?? "Vecino";
        if (!in_array($rol, Usuario::ROLES, true)) {
            Response::error("Rol inválido. Valores admitidos: " . implode(", ", Usuario::ROLES), 400);
        }

        $errorCentro = $this->errorDeCentro($datos, $rol);
        if ($errorCentro !== null) {
            Response::error($errorCentro, 400);
        }

        try {
            Correo::verificarConfiguracion();
        } catch (CorreoException $e) {
            Response::error("El correo no está configurado para enviar accesos.", 503);
        }
        $db = Database::conexion();
        $db->beginTransaction();
        try {
            $datos["password"] = bin2hex(random_bytes(32));
            $nuevo = Usuario::crear($datos, $rol, "pendiente");
            $token = EnlaceClave::crear((int) $nuevo["id_usuario"]);
            Correo::enviarEnlace($nuevo["email"], $nuevo["nombre"], $token);
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            if ($e instanceof CorreoException) {
                Response::error("No se pudo enviar el correo. No se creó la cuenta.", 503);
            }
            throw $e;
        }
        Response::json(["ok" => true, "mensaje" => "Usuario creado. Se envió un enlace para definir la contraseña.", "datos" => $nuevo], 201);
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

    public function enviarEnlace(int $id): void
    {
        Auth::exigirRol("Administrador");
        $usuario = Usuario::obtener($id);
        if ($usuario === null) {
            Response::error("Usuario no encontrado", 404);
        }
        if ($usuario["estado"] === "inactivo") {
            Response::error("Una cuenta dada de baja no puede recibir enlaces de acceso.", 409);
        }
        try {
            Correo::verificarConfiguracion();
        } catch (CorreoException $e) {
            Response::error("El correo no está configurado para enviar accesos.", 503);
        }
        $db = Database::conexion();
        $db->beginTransaction();
        try {
            $token = EnlaceClave::crear($id);
            Correo::enviarEnlace($usuario["email"], $usuario["nombre"], $token);
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            if ($e instanceof CorreoException) {
                Response::error("No se pudo enviar el correo. El enlace anterior sigue vigente.", 503);
            }
            throw $e;
        }
        Response::json(["ok" => true, "mensaje" => "Se envió el enlace para crear una contraseña."]);
    }

    public function recuperarClave(): void
    {
        LimitePeticion::exigir("recuperar-clave", 10, 3600);
        $datos = Response::leerJson();
        $email = $datos["email"] ?? null;
        if (!is_string($email) || !Validacion::email(trim($email))) {
            Response::error("Escribí un correo electrónico válido.", 400);
        }

        try {
            Correo::verificarConfiguracion();
        } catch (CorreoException $e) {
            Response::error("El correo no está disponible en este momento. Probá más tarde.", 503);
        }

        $usuario = Usuario::porEmail(trim($email));
        if ($usuario !== null && $usuario["rol"] === "Vecino" &&
            $usuario["estado"] === "activo" &&
            !EnlaceClave::solicitadoRecientemente((int) $usuario["id_usuario"])) {
            $db = Database::conexion();
            $db->beginTransaction();
            try {
                $token = EnlaceClave::crear((int) $usuario["id_usuario"]);
                Correo::enviarEnlace($usuario["email"], $usuario["nombre"], $token, true);
                $db->commit();
            } catch (Throwable $e) {
                if ($db->inTransaction()) $db->rollBack();
                error_log("SiGeRU: no se pudo enviar la recuperación de contraseña.");
            }
        }

        Response::json([
            "ok" => true,
            "mensaje" => "Si existe una cuenta de vecino con ese correo, recibirás un enlace para crear una contraseña nueva. Revisá también la carpeta de spam. Abrí el correo en esta misma computadora.",
        ]);
    }

    public function crearClave(): void
    {
        LimitePeticion::exigir("crear-clave", 30, 3600);
        $datos = Response::leerJson();
        $token = $datos["token"] ?? null;
        $clave = $datos["password"] ?? null;
        if (!is_string($token) || !is_string($clave) || !Validacion::contrasena($clave)) {
            Response::error("El enlace o la contraseña no son válidos. La contraseña necesita al menos 8 caracteres.", 400);
        }
        $usuario = EnlaceClave::consumir($token, $clave);
        if ($usuario === null) {
            Response::error("El enlace venció o ya fue usado. Pedí uno nuevo.", 400);
        }
        Response::json(["ok" => true, "mensaje" => "Contraseña creada. Ya podés iniciar sesión."]);
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
        $email = PoliticaBloqueo::normalizar($datos["email"] ?? "");
        $clave = is_string($datos["password"] ?? null) ? $datos["password"] : "";

        $espera = IntentosLogin::esperaPara($email);
        if ($espera > 0) {
            $minutos = (int) ceil($espera / 60);
            header("Retry-After: " . $espera);
            Response::error(
                "Demasiados intentos fallidos. Probá de nuevo en " . $minutos . ($minutos === 1 ? " minuto." : " minutos."),
                429
            );
        }

        $usuario = Usuario::login($email, $clave);
        IntentosLogin::registrar($email, $usuario !== null);

        if ($usuario === null) {
            Response::error("El email o la contraseña no coinciden", 401);
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

        $nuevo = Usuario::crear($datos, "Vecino");
        try {
            Correo::enviarBienvenida($nuevo["email"], $nuevo["nombre"]);
        } catch (CorreoException $e) {
            // La cuenta ya existe: sin correo configurado o con SMTP caido el registro no se cae.
            error_log("SiGeRU: no se pudo enviar la bienvenida.");
        }
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

        $rol = $datos["rol"] ?? "Vecino";
        if (!in_array($rol, Usuario::ROLES, true)) {
            Response::error("Rol inválido. Valores admitidos: " . implode(", ", Usuario::ROLES), 400);
        }
        if ($decision === "aprobada") {
            try {
                Correo::verificarConfiguracion();
            } catch (CorreoException $e) {
                Response::error("El correo no está configurado para enviar accesos.", 503);
            }
        }

        try {
            [$actualizada, $usuario] = Usuario::resolverSolicitud(
                $id, $decision, $rol,
                function (array $nuevo): void {
                    $token = EnlaceClave::crear((int) $nuevo["id_usuario"]);
                    Correo::enviarEnlace($nuevo["email"], $nuevo["nombre"], $token);
                }
            );
        } catch (CorreoException $e) {
            Response::error("No se pudo enviar el correo. La solicitud sigue pendiente.", 503);
        }

        Response::json([
            "ok"      => true,
            "mensaje" => $decision === "aprobada"
                ? "Solicitud aprobada. Se envió un enlace para crear la contraseña."
                : "Solicitud rechazada",
            "datos"   => ["solicitud" => $actualizada, "usuario" => $usuario],
        ]);
    }
}
