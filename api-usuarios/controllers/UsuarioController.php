<?php

require_once __DIR__ . "/../models/Usuario.php";
require_once __DIR__ . "/../core/Response.php";
require_once __DIR__ . "/../core/Auth.php";

class UsuarioController
{
    // ------------------------------------------------------------ usuarios --

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
            Response::json(["ok" => false, "mensaje" => "Usuario no encontrado"], 404);
        }
        Response::json(["ok" => true, "datos" => $usuario]);
    }

    /**
     * Alta de usuario desde el panel de administracion. Solo un administrador
     * puede crear usuarios, y solo aca se puede elegir el rol: el registro
     * publico va por crearSolicitud() y nunca crea un usuario directamente.
     */
    public function crear(): void
    {
        Auth::exigirRol("Administrador");
        $datos = Response::leerJson();

        if (empty($datos["nombre"]) || empty($datos["email"])) {
            Response::json(["ok" => false, "mensaje" => "Faltan datos: nombre y email"], 400);
        }
        if (Usuario::existeEmail($datos["email"])) {
            Response::json(["ok" => false, "mensaje" => "Ya existe un usuario con ese email"], 409);
        }

        $rol = $datos["rol"] ?? "Vecino";
        if (!in_array($rol, Usuario::ROLES, true)) {
            Response::json([
                "ok"      => false,
                "mensaje" => "Rol invalido. Valores admitidos: " . implode(", ", Usuario::ROLES),
            ], 400);
        }

        $nuevo = Usuario::crear($datos, $rol);
        Response::json(["ok" => true, "mensaje" => "Usuario creado", "datos" => $nuevo], 201);
    }

    public function login(): void
    {
        $datos = Response::leerJson();
        $usuario = Usuario::login($datos["email"] ?? "", $datos["password"] ?? "");

        if ($usuario === null) {
            Response::json(["ok" => false, "mensaje" => "Email o contrasena incorrectos"], 401);
        }
        Response::json(["ok" => true, "mensaje" => "Login correcto", "datos" => $usuario]);
    }

    // --------------------------------------------------------- solicitudes --

    /** Registro publico: crea una solicitud pendiente, nunca un usuario. */
    public function crearSolicitud(): void
    {
        $datos = Response::leerJson();

        if (empty($datos["nombre"]) || empty($datos["email"])) {
            Response::json(["ok" => false, "mensaje" => "Faltan datos: nombre y email"], 400);
        }
        if (Usuario::existeEmail($datos["email"])) {
            Response::json(["ok" => false, "mensaje" => "Ya existe un usuario con ese email"], 409);
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

    /**
     * Aprueba o rechaza una solicitud (CU-011 del analisis de casos de uso).
     * Cuerpo esperado: {"decision": "aprobada"|"rechazada", "rol": "..."}
     */
    public function resolverSolicitud(int $id): void
    {
        Auth::exigirRol("Administrador");
        $datos = Response::leerJson();
        $decision = $datos["decision"] ?? "";

        if (!in_array($decision, ["aprobada", "rechazada"], true)) {
            Response::json([
                "ok"      => false,
                "mensaje" => "La decision tiene que ser 'aprobada' o 'rechazada'",
            ], 400);
        }

        $solicitud = Usuario::obtenerSolicitud($id);
        if ($solicitud === null) {
            Response::json(["ok" => false, "mensaje" => "Solicitud no encontrada"], 404);
        }
        if ($solicitud["estado"] !== "pendiente") {
            Response::json([
                "ok"      => false,
                "mensaje" => "La solicitud ya estaba " . $solicitud["estado"],
            ], 409);
        }

        $rol = $datos["rol"] ?? "Vecino";
        if (!in_array($rol, Usuario::ROLES, true)) {
            Response::json([
                "ok"      => false,
                "mensaje" => "Rol invalido. Valores admitidos: " . implode(", ", Usuario::ROLES),
            ], 400);
        }

        [$actualizada, $usuario] = Usuario::resolverSolicitud($id, $decision, $rol);

        Response::json([
            "ok"      => true,
            "mensaje" => "Solicitud $decision",
            "datos"   => ["solicitud" => $actualizada, "usuario" => $usuario],
        ]);
    }
}
