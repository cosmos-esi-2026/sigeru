<?php

require_once __DIR__ . "/../models/Ruta.php";
require_once __DIR__ . "/../models/FlujoRecoleccion.php";
require_once __DIR__ . "/../core/Response.php";

class RutaController
{
    public function listar(): void
    {
        Response::json(["ok" => true, "datos" => Ruta::listar()]);
    }

    public function obtener(int $id): void
    {
        $ruta = Ruta::obtener($id);
        if ($ruta === null) {
            Response::error("Ruta no encontrada", 404);
        }
        Response::json(["ok" => true, "datos" => $ruta]);
    }

    public function crear(): void
    {
        $datos = Response::leerJson();

        $error = FlujoRecoleccion::errorDeRuta($datos, true);
        if ($error !== null) {
            Response::error($error, 400);
        }
        if (!Ruta::existeZona(FlujoRecoleccion::entero($datos["id_zona"]))) {
            Response::error("La zona indicada no existe", 404);
        }

        Response::json(["ok" => true, "mensaje" => "Ruta creada", "datos" => Ruta::crear($datos)], 201);
    }

    public function actualizar(int $id): void
    {
        $datos = Response::leerJson();

        if (Ruta::obtener($id) === null) {
            Response::error("Ruta no encontrada", 404);
        }
        $error = FlujoRecoleccion::errorDeRuta($datos, false);
        if ($error !== null) {
            Response::error($error, 400);
        }
        if (array_key_exists("id_zona", $datos) && !Ruta::existeZona(FlujoRecoleccion::entero($datos["id_zona"]))) {
            Response::error("La zona indicada no existe", 404);
        }

        Response::json(["ok" => true, "mensaje" => "Ruta actualizada", "datos" => Ruta::actualizar($id, $datos)]);
    }

    public function darDeBaja(int $id): void
    {
        $ruta = Ruta::obtener($id);
        if ($ruta === null) {
            Response::error("Ruta no encontrada", 404);
        }
        if ($ruta["estado"] === "inactiva") {
            Response::error("La ruta ya estaba inactiva", 409);
        }

        Response::json([
            "ok"      => true,
            "mensaje" => "Ruta dada de baja",
            "datos"   => Ruta::actualizar($id, ["estado" => "inactiva"]),
        ]);
    }
}
