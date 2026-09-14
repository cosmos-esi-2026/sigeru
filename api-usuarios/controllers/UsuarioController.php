<?php

require_once __DIR__ . "/../models/Usuario.php";
require_once __DIR__ . "/../core/Response.php";
require_once __DIR__ . "/../core/Auth.php";
require_once __DIR__ . "/../core/Validacion.php";

class UsuarioController
{
    public function listar(): void
    {
        Auth::exigirRol("Administrador");
        Response::json(["ok" => true, "datos" => Usuario::listar()]);
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
        if (!empty($datos["password"]) && !Validacion::contrasena($datos["password"])) {
            Response::error(
                "La contraseña tiene que tener al menos " . Validacion::CLAVE_MINIMA . " caracteres",
                400
            );
        }
        if (Usuario::existeEmail($datos["email"])) {
            Response::error("Ya existe un usuario con ese email", 409);
        }

        $rol = $datos["rol"] ?? "Vecino";
        if (!in_array($rol, Usuario::ROLES, true)) {
            Response::error("Rol inválido. Valores admitidos: " . implode(", ", Usuario::ROLES), 400);
        }

        $nuevo = Usuario::crear($datos, $rol);
        Response::json(["ok" => true, "mensaje" => "Usuario creado", "datos" => $nuevo], 201);
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
        $usuario = Usuario::login($datos["email"] ?? "", $datos["password"] ?? "");

        if ($usuario === null) {
            Response::error("El email o la contraseña no coinciden", 401);
        }

        Auth::iniciarSesion($usuario);
        Response::json(["ok" => true, "mensaje" => "Login correcto", "datos" => $usuario]);
    }

    public function logout(): void
    {
        Auth::cerrarSesion();
        Response::json(["ok" => true, "mensaje" => "Sesión cerrada"]);
    }

    public function sesion(): void
    {
        $usuario = Auth::identificar();
        if ($usuario === null) {
            Response::error("No hay una sesión iniciada", 401);
        }
        Response::json(["ok" => true, "datos" => $usuario]);
    }

    public function crearSolicitud(): void
    {
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

        [$actualizada, $usuario] = Usuario::resolverSolicitud($id, $decision, $rol);

        Response::json([
            "ok"      => true,
            "mensaje" => "Solicitud $decision",
            "datos"   => ["solicitud" => $actualizada, "usuario" => $usuario],
        ]);
    }
}
