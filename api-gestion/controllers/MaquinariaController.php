<?php
// ============================================================================
//  SiGeRU 2026 - API de gestion
//  controllers/MaquinariaController.php
//
//  Modulo que la letra pide en el CRUD de la 2da entrega ("CRUD de centros de
//  acopio y maquinaria basica") y que el modelo de 14 tablas no cubria. Entro
//  con la Adenda de Alcance del 09/09/2026.
// ============================================================================

require_once __DIR__ . "/../models/Maquinaria.php";
require_once __DIR__ . "/../core/Response.php";

class MaquinariaController
{
    public function listar(): void
    {
        Response::json(["ok" => true, "datos" => Maquinaria::listar()]);
    }

    public function obtener(int $id): void
    {
        $maquina = Maquinaria::obtener($id);
        if ($maquina === null) {
            Response::error("Maquinaria no encontrada", 404);
        }
        Response::json(["ok" => true, "datos" => $maquina]);
    }

    public function crear(): void
    {
        $datos = Response::leerJson();

        if (empty($datos["codigo"])) {
            Response::error("Falta el código de la maquinaria", 400);
        }
        if (Maquinaria::existeCodigo($datos["codigo"])) {
            Response::error("Ya existe una maquinaria con ese código", 409);
        }
        if (isset($datos["tipo"]) && $datos["tipo"] !== ""
            && !in_array($datos["tipo"], Maquinaria::TIPOS, true)) {
            Response::error("Tipo de maquinaria inválido", 400);
        }
        if (isset($datos["estado"]) && $datos["estado"] !== ""
            && !in_array($datos["estado"], Maquinaria::ESTADOS, true)) {
            Response::error("Estado de maquinaria inválido", 400);
        }

        // Una maquina siempre esta EN un centro: la columna es NOT NULL y no
        // hay centro por omision. Sin esto la base devolveria un error de
        // integridad, que no le dice nada a quien usa la pantalla.
        if (Maquinaria::idDeCentro($datos) === null) {
            Response::error("Hay que indicar en qué centro de acopio está la maquinaria", 400);
        }

        $nueva = Maquinaria::crear($datos);
        Response::json(["ok" => true, "mensaje" => "Maquinaria creada", "datos" => $nueva], 201);
    }

    public function actualizar(int $id): void
    {
        $datos = Response::leerJson();

        $actual = Maquinaria::obtener($id);
        if ($actual === null) {
            Response::error("Maquinaria no encontrada", 404);
        }
        if (isset($datos["codigo"]) && $datos["codigo"] !== $actual["codigo"]
            && Maquinaria::existeCodigo($datos["codigo"])) {
            Response::error("Ya existe una maquinaria con ese código", 409);
        }
        if (isset($datos["tipo"]) && $datos["tipo"] !== ""
            && !in_array($datos["tipo"], Maquinaria::TIPOS, true)) {
            Response::error("Tipo de maquinaria inválido", 400);
        }
        if (isset($datos["estado"]) && $datos["estado"] !== ""
            && !in_array($datos["estado"], Maquinaria::ESTADOS, true)) {
            Response::error("Estado de maquinaria inválido", 400);
        }
        if ((!empty($datos["id_centro"]) || !empty($datos["centro"]))
            && Maquinaria::idDeCentro($datos) === null) {
            Response::error("El centro de acopio indicado no existe", 400);
        }

        $actualizada = Maquinaria::actualizar($id, $datos);
        Response::json([
            "ok"      => true,
            "mensaje" => "Maquinaria actualizada",
            "datos"   => $actualizada,
        ]);
    }

    public function darDeBaja(int $id): void
    {
        $maquina = Maquinaria::obtener($id);
        if ($maquina === null) {
            Response::error("Maquinaria no encontrada", 404);
        }
        if ($maquina["estado"] === "fuera_servicio") {
            Response::error("La maquinaria ya estaba fuera de servicio", 409);
        }

        $baja = Maquinaria::darDeBaja($id);
        Response::json(["ok" => true, "mensaje" => "Maquinaria dada de baja", "datos" => $baja]);
    }
}
