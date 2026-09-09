<?php
// ============================================================================
//  SiGeRU 2026 - API de gestion
//  controllers/CamionController.php
// ============================================================================

require_once __DIR__ . "/../models/Camion.php";
require_once __DIR__ . "/../core/Response.php";

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

        if (empty($datos["matricula"])) {
            Response::error("Falta la matrícula", 400);
        }
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
        if (isset($datos["estado"]) && !in_array($datos["estado"], Camion::ESTADOS, true)) {
            Response::error(
                "Estado inválido. Valores admitidos: " . implode(", ", Camion::ESTADOS),
                400
            );
        }
        if (isset($datos["matricula"]) && $datos["matricula"] !== $actual["matricula"]
            && Camion::existeMatricula($datos["matricula"])) {
            Response::error("Ya existe un camión con esa matrícula", 409);
        }

        $actualizado = Camion::actualizar($id, $datos);
        Response::json(["ok" => true, "mensaje" => "Camión actualizado", "datos" => $actualizado]);
    }

    /**
     * Baja LOGICA: pasa a "fuera_servicio". Las recolecciones ya hechas lo
     * referencian, asi que borrarlo dejaria huerfano el historial.
     */
    public function darDeBaja(int $id): void
    {
        $camion = Camion::obtener($id);
        if ($camion === null) {
            Response::error("Camión no encontrado", 404);
        }
        if ($camion["estado"] === "fuera_servicio") {
            Response::error("El camión ya estaba fuera de servicio", 409);
        }

        $baja = Camion::darDeBaja($id);
        Response::json(["ok" => true, "mensaje" => "Camión dado de baja", "datos" => $baja]);
    }
}
