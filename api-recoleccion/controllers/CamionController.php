<?php

require_once __DIR__ . "/../models/Camion.php";
require_once __DIR__ . "/../models/Recoleccion.php";
require_once __DIR__ . "/../models/FlujoRecoleccion.php";
require_once __DIR__ . "/../core/Response.php";
require_once __DIR__ . "/../models/ValidacionCamion.php";

class CamionController
{
    public function listar(): void
    {
        Response::json(["ok" => true, "datos" => Camion::listar()]);
    }

    public function obtener(int $id): void
    {
        $camion = Camion::obtener($id);
        if ($camion === null) {
            Response::error("Camión no encontrado", 404);
        }
        Response::json(["ok" => true, "datos" => $camion]);
    }

    public function crear(): void
    {
        $datos = Response::leerJson();

        $error = ValidacionCamion::error($datos, true);
        if ($error !== null) {
            Response::error($error, 400);
        }
        $datos["matricula"] = trim($datos["matricula"]);
        if (Camion::existeMatricula($datos["matricula"])) {
            Response::error("Ya existe un camión con esa matrícula", 409);
        }
        if (isset($datos["estado"]) && !in_array($datos["estado"], Camion::ESTADOS, true)) {
            Response::error(
                "Estado inválido. Valores admitidos: " . implode(", ", Camion::ESTADOS),
                400
            );
        }

        $nuevo = Camion::crear($datos);
        Response::json(["ok" => true, "mensaje" => "Camión creado", "datos" => $nuevo], 201);
    }

    public function actualizar(int $id): void
    {
        $datos = Response::leerJson();

        $actual = Camion::obtener($id);
        if ($actual === null) {
            Response::error("Camión no encontrado", 404);
        }
        $error = ValidacionCamion::error($datos, false);
        if ($error !== null) {
            Response::error($error, 400);
        }
        if (isset($datos["matricula"])) {
            $datos["matricula"] = trim($datos["matricula"]);
        }
        if (isset($datos["estado"]) && !in_array($datos["estado"], Camion::ESTADOS, true)) {
            Response::error(
                "Estado inválido. Valores admitidos: " . implode(", ", Camion::ESTADOS),
                400
            );
        }
        $this->exigirFueraDeRecoleccion($id, $datos["estado"] ?? null, $actual["estado"]);
        if (isset($datos["matricula"]) && $datos["matricula"] !== $actual["matricula"]
            && Camion::existeMatricula($datos["matricula"])) {
            Response::error("Ya existe un camión con esa matrícula", 409);
        }

        $actualizado = Camion::actualizar($id, $datos);
        Response::json(["ok" => true, "mensaje" => "Camión actualizado", "datos" => $actualizado]);
    }

    public function darDeBaja(int $id): void
    {
        $camion = Camion::obtener($id);
        if ($camion === null) {
            Response::error("Camión no encontrado", 404);
        }
        if ($camion["estado"] === "fuera_servicio") {
            Response::error("El camión ya estaba fuera de servicio", 409);
        }
        $this->exigirFueraDeRecoleccion($id, "fuera_servicio", $camion["estado"]);

        $baja = Camion::darDeBaja($id);
        Response::json(["ok" => true, "mensaje" => "Camión dado de baja", "datos" => $baja]);
    }

    private function exigirFueraDeRecoleccion(int $id, ?string $estadoNuevo, string $estadoActual): void
    {
        if ($estadoNuevo === null || $estadoNuevo === $estadoActual) {
            return;
        }
        $enCurso = Recoleccion::camionEnCurso($id);
        if ($enCurso !== null) {
            Response::error(
                "El camión está en la recolección " . FlujoRecoleccion::numero($enCurso) .
                ", que está en curso: su estado cambia solo al finalizarla o cancelarla",
                409
            );
        }
    }
}
