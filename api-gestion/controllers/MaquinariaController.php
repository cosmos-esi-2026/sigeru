<?php

require_once __DIR__ . "/../models/Maquinaria.php";
require_once __DIR__ . "/../core/Response.php";
require_once __DIR__ . "/../core/Alcance.php";
require_once __DIR__ . "/../models/Campos.php";
require_once __DIR__ . "/../models/ValidacionMaquinaria.php";

class MaquinariaController
{
    public function listar(array $usuario): void
    {
        Response::json(["ok" => true, "datos" => Maquinaria::listar(Alcance::centro($usuario))]);
    }

    // Una maquina de otro centro no se le muestra al Operario: para el, no existe.
    private function propia(int $id, array $usuario): array
    {
        $maquina = Maquinaria::obtener($id);
        if ($maquina === null || !AlcanceCentro::puede($usuario, (int) $maquina["id_centro"])) {
            Response::error("Maquinaria no encontrada", 404);
        }
        return $maquina;
    }

    public function obtener(int $id, array $usuario): void
    {
        Response::json(["ok" => true, "datos" => $this->propia($id, $usuario)]);
    }

    // POST /maquinaria/{id}/estado: el Operario (en su centro) o el Administrador pasan la maquina de estado, con una nota.
    public function cambiarEstado(int $id, array $usuario): void
    {
        $this->propia($id, $usuario);
        $datos = Response::leerJson();
        $estado = $datos["estado"] ?? null;
        if (!is_string($estado) || !in_array($estado, Maquinaria::ESTADOS, true)) {
            Response::error("Estado inválido. Valores admitidos: " . implode(", ", Maquinaria::ESTADOS), 400);
        }
        $nota = Campos::texto($datos["observacion"] ?? null);
        if ($nota === "" || mb_strlen($nota) > 300) {
            Response::error("La nota es obligatoria (hasta 300 caracteres): contá qué se hizo o por qué cambia", 400);
        }
        try {
            $maquina = Maquinaria::cambiarEstado($id, $estado, $nota, (int) $usuario["id_usuario"]);
        } catch (ErrorDeNegocio $e) {
            Response::error($e->getMessage(), $e->estadoHttp());
        }
        Response::json(["ok" => true, "mensaje" => "Estado de la máquina actualizado", "datos" => $maquina]);
    }

    public function historial(int $id, array $usuario): void
    {
        $this->propia($id, $usuario);
        Response::json(["ok" => true, "datos" => Maquinaria::historial($id)]);
    }

    public function crear(array $usuario): void
    {
        $datos = Response::leerJson();

        $error = ValidacionMaquinaria::error($datos, true);
        if ($error !== null) {
            Response::error($error, 400);
        }
        $datos["codigo"] = trim($datos["codigo"]);
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

        $nueva = Maquinaria::crear($datos, (int) $usuario["id_usuario"]);
        Response::json(["ok" => true, "mensaje" => "Maquinaria creada", "datos" => $nueva], 201);
    }

    public function actualizar(int $id, array $usuario): void
    {
        $datos = Response::leerJson();

        $actual = Maquinaria::obtener($id);
        if ($actual === null) {
            Response::error("Maquinaria no encontrada", 404);
        }
        $error = ValidacionMaquinaria::error($datos, false);
        if ($error !== null) {
            Response::error($error, 400);
        }
        if (isset($datos["codigo"])) {
            $datos["codigo"] = trim($datos["codigo"]);
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

        $actualizada = Maquinaria::actualizar($id, $datos, (int) $usuario["id_usuario"]);
        Response::json([
            "ok"      => true,
            "mensaje" => "Maquinaria actualizada",
            "datos"   => $actualizada,
        ]);
    }

    public function darDeBaja(int $id, array $usuario): void
    {
        $maquina = Maquinaria::obtener($id);
        if ($maquina === null) {
            Response::error("Maquinaria no encontrada", 404);
        }
        if ($maquina["estado"] === "fuera_servicio") {
            Response::error("La maquinaria ya estaba fuera de servicio", 409);
        }

        $baja = Maquinaria::darDeBaja($id, (int) $usuario["id_usuario"]);
        Response::json(["ok" => true, "mensaje" => "Maquinaria dada de baja", "datos" => $baja]);
    }
}
