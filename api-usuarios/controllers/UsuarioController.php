<?php

require_once __DIR__ . "/../models/Usuario.php";
require_once __DIR__ . "/../core/Response.php";

class UsuarioController
{
    public function listar(): void
    {
        $usuarios = Usuario::listar();
        Response::json(["ok" => true, "datos" => $usuarios]);
    }

    public function obtener(int $id): void
    {
        $usuario = Usuario::obtener($id);
        if ($usuario === null) {
            Response::json(["ok" => false, "mensaje" => "Usuario no encontrado"], 404);
        }
        Response::json(["ok" => true, "datos" => $usuario]);
    }

    public function crear(): void
    {
        $datos = Response::leerJson();

        if (empty($datos["nombre"]) || empty($datos["email"])) {
            Response::json(["ok" => false, "mensaje" => "Faltan datos: nombre y email"], 400);
        }

        $nuevo = Usuario::crear($datos);
        Response::json(["ok" => true, "mensaje" => "Usuario creado", "datos" => $nuevo], 201);
    }

    public function login(): void
    {
        $datos = Response::leerJson();
        $email = $datos["email"] ?? "";
        $clave = $datos["password"] ?? "";

        $usuario = Usuario::login($email, $clave);
        if ($usuario === null) {
            Response::json(["ok" => false, "mensaje" => "Email o contrasena incorrectos"], 401);
        }


        Response::json(["ok" => true, "mensaje" => "Login correcto", "datos" => $usuario]);
    }

    public function crearSolicitud(): void
    {
        $datos = Response::leerJson();

        if (empty($datos["nombre"]) || empty($datos["email"])) {
            Response::json(["ok" => false, "mensaje" => "Faltan datos: nombre y email"], 400);
        }

        Response::json([
            "ok" => true,
            "mensaje" => "Solicitud recibida",
            "datos" => [
                "nombre"   => $datos["nombre"],
                "email"    => $datos["email"],
                "estado"   => "pendiente",
            ]
        ], 201);
    }
}