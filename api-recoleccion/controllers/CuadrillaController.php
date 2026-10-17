<?php

require_once __DIR__ . "/../models/Cuadrilla.php";
require_once __DIR__ . "/../core/Response.php";

class CuadrillaController
{
    public function listar(): void
    {
        $soloDisponibles = ($_GET["disponibles"] ?? "") === "1";
        Response::json(["ok" => true, "datos" => Cuadrilla::listar($soloDisponibles)]);
    }

    public function obtener(int $id): void
    {
        $cuadrilla = Cuadrilla::obtener($id);
        if ($cuadrilla === null) {
            Response::error("Cuadrilla no encontrada", 404);
        }
        Response::json(["ok" => true, "datos" => $cuadrilla]);
    }
}
