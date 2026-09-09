<?php
// ============================================================================
//  SiGeRU 2026 - API de gestion
//  controllers/ContenedorController.php
//
//  Recibe la peticion, valida, usa el modelo y responde JSON. Las reglas de
//  quien puede hacer que estan en el router (index.php), que es el unico lugar
//  donde se decide el control de acceso.
// ============================================================================

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

    /** Historial de estados: es lo que permite ver la vida util del contenedor. */
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
        // El codigo se puede cambiar, pero no para pisar el de otro contenedor.
        if (isset($datos["codigo"]) && $datos["codigo"] !== $actual["codigo"]
            && Contenedor::existeCodigo($datos["codigo"])) {
            Response::error("Ya existe un contenedor con ese código", 409);
        }

        $actualizado = Contenedor::actualizar($id, $datos);
        Response::json(["ok" => true, "mensaje" => "Contenedor actualizado", "datos" => $actualizado]);
    }

    /**
     * Baja LOGICA: pasa a "retirado" y queda asentado en el historial.
     * No se borra la fila porque sus vaciados y su historial son parte del
     * registro del servicio.
     */
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
