<?php

require_once __DIR__ . "/../models/Camion.php";
require_once __DIR__ . "/../core/Response.php";

class CamionController
{
    public function listar(): void
    {
        Response::json(["ok" => true, "datos" => Camion::listar()]);
    }

    public function obtener(int $id): void
    {
        $camion = Camion::obtener($id);
        if ($camion === null) {
            Response::json(["ok" => false, "mensaje" => "Camion no encontrado"], 404);
        }
        Response::json(["ok" => true, "datos" => $camion]);
    }

    public function crear(): void
    {
        $datos = Response::leerJson();

        if (empty($datos["matricula"])) {
            Response::json(["ok" => false, "mensaje" => "Falta dato: matricula"], 400);
        }

        $nuevo = Camion::crear($datos);
        Response::json(["ok" => true, "mensaje" => "Camion creado", "datos" => $nuevo], 201);
    }
}
