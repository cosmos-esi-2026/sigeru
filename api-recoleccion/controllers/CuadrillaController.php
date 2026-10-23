<?php

require_once __DIR__ . "/../models/Cuadrilla.php";
require_once __DIR__ . "/../core/Response.php";
require_once __DIR__ . "/../models/ValidacionCuadrilla.php";

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
        $cuadrilla["personas"] = Cuadrilla::integrantes($id);
        Response::json(["ok" => true, "datos" => $cuadrilla]);
    }

    public function crear(): void
    {
        $datos = Response::leerJson();
        $error = ValidacionCuadrilla::error($datos, true);
        if ($error !== null) {
            Response::error($error, 400);
        }
        if (Cuadrilla::existeNombre(trim($datos["nombre"]))) {
            Response::error("Ya existe una cuadrilla con ese nombre", 409);
        }
        Response::json(["ok" => true, "mensaje" => "Cuadrilla creada", "datos" => Cuadrilla::crear($datos)], 201);
    }

    public function actualizar(int $id): void
    {
        $datos = Response::leerJson();
        $actual = Cuadrilla::obtener($id);
        if ($actual === null) {
            Response::error("Cuadrilla no encontrada", 404);
        }
        $error = ValidacionCuadrilla::error($datos, false);
        if ($error !== null) {
            Response::error($error, 400);
        }
        if (isset($datos["nombre"]) && trim($datos["nombre"]) !== $actual["nombre"] && Cuadrilla::existeNombre(trim($datos["nombre"]), $id)) {
            Response::error("Ya existe una cuadrilla con ese nombre", 409);
        }
        if (($datos["estado"] ?? null) === "inactiva" && $actual["estado"] !== "inactiva") {
            $this->exigirSinTrabajoAbierto($actual);
        }
        Response::json(["ok" => true, "mensaje" => "Cuadrilla actualizada", "datos" => Cuadrilla::actualizar($id, $datos)]);
    }

    public function darDeBaja(int $id): void
    {
        $cuadrilla = Cuadrilla::obtener($id);
        if ($cuadrilla === null) {
            Response::error("Cuadrilla no encontrada", 404);
        }
        if ($cuadrilla["estado"] === "inactiva") {
            Response::error("La cuadrilla ya estaba dada de baja", 409);
        }
        $this->exigirSinTrabajoAbierto($cuadrilla);
        Response::json(["ok" => true, "mensaje" => "Cuadrilla dada de baja", "datos" => Cuadrilla::darDeBaja($id)]);
    }

    // Una cuadrilla con una recoleccion planificada o en curso, o una incidencia en curso, no se da de baja: primero se reasigna o se cierra.
    private function exigirSinTrabajoAbierto(array $cuadrilla): void
    {
        if ($cuadrilla["recolecciones_abiertas"] > 0) {
            Response::error("La cuadrilla tiene " . $cuadrilla["recolecciones_abiertas"] . " recolección(es) planificada(s) o en curso: cancelalas o reasignalas antes de darla de baja", 409);
        }
        if ($cuadrilla["incidencias_en_curso"] > 0) {
            Response::error("La cuadrilla tiene " . $cuadrilla["incidencias_en_curso"] . " incidencia(s) en curso: reasignalas o cerralas antes de darla de baja", 409);
        }
    }
}
