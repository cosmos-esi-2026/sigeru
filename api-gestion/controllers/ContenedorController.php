<?php

require_once __DIR__ . "/../models/Contenedor.php";
require_once __DIR__ . "/../core/Response.php";

class ContenedorController
{
    public function listar(): void
    {
        Response::json(["ok" => true, "datos" => Contenedor::listar()]);
    }

    public function obtener(int $id): void
    {
        $contenedor = Contenedor::obtener($id);
        if ($contenedor === null) {
            Response::json(["ok" => false, "mensaje" => "Contenedor no encontrado"], 404);
        }
        Response::json(["ok" => true, "datos" => $contenedor]);
    }

    public function crear(): void
    {
        $datos = Response::leerJson();

        if (empty($datos["codigo"]) || empty($datos["direccion"])) {
            Response::json(["ok" => false, "mensaje" => "Faltan datos: codigo y direccion"], 400);
        }

        $nuevo = Contenedor::crear($datos);
        Response::json(["ok" => true, "mensaje" => "Contenedor creado", "datos" => $nuevo], 201);
    }
}