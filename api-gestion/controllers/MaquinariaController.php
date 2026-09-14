<?php

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
