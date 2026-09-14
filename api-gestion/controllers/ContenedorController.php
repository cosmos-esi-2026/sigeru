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
            Response::error("Contenedor no encontrado", 404);
        }
        Response::json(["ok" => true, "datos" => $contenedor]);
    }

    public function historial(int $id): void
    {
        if (Contenedor::obtener($id) === null) {
            Response::error("Contenedor no encontrado", 404);
        }
        Response::json(["ok" => true, "datos" => Contenedor::historial($id)]);
    }

    public function crear(): void
    {
        $datos = Response::leerJson();

        if (empty($datos["codigo"]) || empty($datos["direccion"])) {
            Response::error("Faltan datos: código y dirección", 400);
        }
        if (Contenedor::existeCodigo($datos["codigo"])) {
            Response::error("Ya existe un contenedor con ese código", 409);
        }
        if (isset($datos["estado"]) && !in_array($datos["estado"], Contenedor::ESTADOS, true)) {
            Response::error(
                "Estado inválido. Valores admitidos: " . implode(", ", Contenedor::ESTADOS),
                400
            );
        }

        $nuevo = Contenedor::crear($datos);
        Response::json(["ok" => true, "mensaje" => "Contenedor creado", "datos" => $nuevo], 201);
    }

    public function actualizar(int $id): void
    {
        $datos = Response::leerJson();

        $actual = Contenedor::obtener($id);
        if ($actual === null) {
            Response::error("Contenedor no encontrado", 404);
        }
        if (isset($datos["estado"]) && !in_array($datos["estado"], Contenedor::ESTADOS, true)) {
            Response::error(
                "Estado inválido. Valores admitidos: " . implode(", ", Contenedor::ESTADOS),
                400
            );
        }

        if (isset($datos["codigo"]) && $datos["codigo"] !== $actual["codigo"]
            && Contenedor::existeCodigo($datos["codigo"])) {
            Response::error("Ya existe un contenedor con ese código", 409);
        }

        $actualizado = Contenedor::actualizar($id, $datos);
        Response::json(["ok" => true, "mensaje" => "Contenedor actualizado", "datos" => $actualizado]);
    }

    public function darDeBaja(int $id): void
    {
        $contenedor = Contenedor::obtener($id);
        if ($contenedor === null) {
            Response::error("Contenedor no encontrado", 404);
        }
        if ($contenedor["estado"] === "retirado") {
            Response::error("El contenedor ya estaba retirado", 409);
        }

        $datos = Response::leerJson();
        $motivo = $datos["motivo"] ?? "Retirado desde el panel";

        $retirado = Contenedor::darDeBaja($id, $motivo);
        Response::json(["ok" => true, "mensaje" => "Contenedor retirado", "datos" => $retirado]);
    }
}
