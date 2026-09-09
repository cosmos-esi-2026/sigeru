<?php
// ============================================================================
//  SiGeRU 2026 - API de gestion
//  controllers/CentroAcopioController.php
// ============================================================================

require_once __DIR__ . "/../models/CentroAcopio.php";
require_once __DIR__ . "/../core/Response.php";

class CentroAcopioController
{
    public function listar(): void
    {
        Response::json(["ok" => true, "datos" => CentroAcopio::listar()]);
    }

    public function obtener(int $id): void
    {
        $centro = CentroAcopio::obtener($id);
        if ($centro === null) {
            Response::error("Centro de acopio no encontrado", 404);
        }
        Response::json(["ok" => true, "datos" => $centro]);
    }

    public function crear(): void
    {
        $datos = Response::leerJson();

        if (empty($datos["nombre"])) {
            Response::error("Falta el nombre del centro de acopio", 400);
        }
        if (CentroAcopio::existeNombre($datos["nombre"])) {
            Response::error("Ya existe un centro de acopio con ese nombre", 409);
        }

        $nuevo = CentroAcopio::crear($datos);
        Response::json(["ok" => true, "mensaje" => "Centro de acopio creado", "datos" => $nuevo], 201);
    }

    public function actualizar(int $id): void
    {
        $datos = Response::leerJson();

        $actual = CentroAcopio::obtener($id);
        if ($actual === null) {
            Response::error("Centro de acopio no encontrado", 404);
        }
        if (isset($datos["nombre"]) && $datos["nombre"] !== $actual["nombre"]
            && CentroAcopio::existeNombre($datos["nombre"])) {
            Response::error("Ya existe un centro de acopio con ese nombre", 409);
        }

        $actualizado = CentroAcopio::actualizar($id, $datos);
        Response::json([
            "ok"      => true,
            "mensaje" => "Centro de acopio actualizado",
            "datos"   => $actualizado,
        ]);
    }

    public function darDeBaja(int $id): void
    {
        $centro = CentroAcopio::obtener($id);
        if ($centro === null) {
            Response::error("Centro de acopio no encontrado", 404);
        }
        if ($centro["estado"] === "inactivo") {
            Response::error("El centro de acopio ya estaba inactivo", 409);
        }

        $baja = CentroAcopio::darDeBaja($id);
        Response::json(["ok" => true, "mensaje" => "Centro de acopio dado de baja", "datos" => $baja]);
    }
}
