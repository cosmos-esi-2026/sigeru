<?php

require_once __DIR__ . "/../models/TipoResiduo.php";
require_once __DIR__ . "/../core/Response.php";

class TipoResiduoController
{
    public function listar(): void
    {
        Response::json(["ok" => true, "datos" => TipoResiduo::listar()]);
    }

    public function obtener(int $id): void
    {
        $tipo = TipoResiduo::obtener($id);
        if ($tipo === null) {
            Response::error("Tipo de residuo no encontrado", 404);
        }
        Response::json(["ok" => true, "datos" => $tipo]);
    }

    public function crear(): void
    {
        $datos = Response::leerJson();

        if (empty($datos["nombre"])) {
            Response::error("Falta el nombre del tipo de residuo", 400);
        }
        if (TipoResiduo::existeNombre($datos["nombre"])) {
            Response::error("Ya existe un tipo de residuo con ese nombre", 409);
        }

        $nuevo = TipoResiduo::crear($datos);
        Response::json(["ok" => true, "mensaje" => "Tipo de residuo creado", "datos" => $nuevo], 201);
    }

    public function actualizar(int $id): void
    {
        $datos = Response::leerJson();

        $actual = TipoResiduo::obtener($id);
        if ($actual === null) {
            Response::error("Tipo de residuo no encontrado", 404);
        }
        if (isset($datos["nombre"]) && $datos["nombre"] !== $actual["nombre"]
            && TipoResiduo::existeNombre($datos["nombre"])) {
            Response::error("Ya existe un tipo de residuo con ese nombre", 409);
        }

        $actualizado = TipoResiduo::actualizar($id, $datos);
        Response::json(["ok" => true, "mensaje" => "Tipo de residuo actualizado", "datos" => $actualizado]);
    }

    public function eliminar(int $id): void
    {
        if (TipoResiduo::obtener($id) === null) {
            Response::error("Tipo de residuo no encontrado", 404);
        }

        $enUso = TipoResiduo::contenedoresQueLoUsan($id);
        if ($enUso > 0) {
            Response::error(
                "No se puede eliminar: hay $enUso contenedor(es) de este tipo. " .
                "Cambiales el tipo antes de borrarlo.",
                409
            );
        }

        TipoResiduo::eliminar($id);
        Response::json(["ok" => true, "mensaje" => "Tipo de residuo eliminado"]);
    }
}
