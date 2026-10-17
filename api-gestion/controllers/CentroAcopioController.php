<?php

require_once __DIR__ . "/../models/CentroAcopio.php";
require_once __DIR__ . "/../models/Maquinaria.php";
require_once __DIR__ . "/../core/Auth.php";
require_once __DIR__ . "/../core/Response.php";
require_once __DIR__ . "/../models/ValidacionCentro.php";

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

        $error = ValidacionCentro::error($datos, true);
        if ($error !== null) {
            Response::error($error, 400);
        }
        $datos["nombre"] = trim($datos["nombre"]);
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
        $error = ValidacionCentro::error($datos, false);
        if ($error !== null) {
            Response::error($error, 400);
        }
        if (isset($datos["nombre"])) {
            $datos["nombre"] = trim($datos["nombre"]);
        }
        if (isset($datos["nombre"]) && $datos["nombre"] !== $actual["nombre"]
            && CentroAcopio::existeNombre($datos["nombre"])) {
            Response::error("Ya existe un centro de acopio con ese nombre", 409);
        }
        if (isset($datos["estado"]) && $datos["estado"] !== $actual["estado"]
            && Auth::identificar()["rol"] !== "Administrador") {
            Response::error("Solo un administrador puede cambiar el estado de un centro de acopio", 403);
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

        $maquinas = Maquinaria::cuantasEnCentro($id);
        if ($maquinas === 1) {
            $aviso = " Atención: tiene 1 máquina registrada; conviene reasignarla a otro centro.";
        } elseif ($maquinas > 1) {
            $aviso = sprintf(" Atención: tiene %d máquinas registradas; conviene reasignarlas"
                             . " a otro centro.", $maquinas);
        } else {
            $aviso = "";
        }

        $baja = CentroAcopio::darDeBaja($id);
        Response::json([
            "ok"      => true,
            "mensaje" => "Centro de acopio dado de baja." . $aviso,
            "datos"   => $baja,
        ]);
    }
}
