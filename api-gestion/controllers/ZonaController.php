<?php
// ============================================================================
//  SiGeRU 2026 - API de gestion
//  controllers/ZonaController.php
// ============================================================================

require_once __DIR__ . "/../models/Zona.php";
require_once __DIR__ . "/../core/Response.php";

class ZonaController
{
    public function listar(): void
    {
        Response::json(["ok" => true, "datos" => Zona::listar()]);
    }

    public function obtener(int $id): void
    {
        $zona = Zona::obtener($id);
        if ($zona === null) {
            Response::error("Zona no encontrada", 404);
        }
        Response::json(["ok" => true, "datos" => $zona]);
    }

    public function crear(): void
    {
        $datos = Response::leerJson();

        if (empty($datos["nombre"])) {
            Response::error("Falta el nombre de la zona", 400);
        }
        if (Zona::existeNombre($datos["nombre"])) {
            Response::error("Ya existe una zona con ese nombre", 409);
        }

        $nueva = Zona::crear($datos);
        Response::json(["ok" => true, "mensaje" => "Zona creada", "datos" => $nueva], 201);
    }
}
