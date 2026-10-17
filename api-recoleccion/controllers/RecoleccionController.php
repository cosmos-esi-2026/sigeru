<?php

require_once __DIR__ . "/../models/Recoleccion.php";
require_once __DIR__ . "/../models/FlujoRecoleccion.php";
require_once __DIR__ . "/../core/ErrorDeNegocio.php";
require_once __DIR__ . "/../core/Response.php";

class RecoleccionController
{
    public function listar(array $usuario): void
    {
        $consulta = FlujoRecoleccion::filtrosDe($_GET);
        if ($consulta["error"] !== null) {
            Response::error($consulta["error"], 400);
        }
        Response::json(["ok" => true, "datos" => Recoleccion::listar($consulta["filtros"], $usuario)]);
    }

    public function obtener(int $id, array $usuario): void
    {
        $recoleccion = Recoleccion::obtener($id);
        if ($recoleccion === null || !FlujoRecoleccion::alcanza($usuario, $recoleccion)) {
            Response::error("Recolección no encontrada", 404);
        }
        Response::json(["ok" => true, "datos" => Recoleccion::detalle($id)]);
    }

    public function planificar(): void
    {
        $datos = Response::leerJson();

        $error = FlujoRecoleccion::errorDeNueva($datos);
        if ($error !== null) {
            Response::error($error, 400);
        }

        $this->ejecutar(function () use ($datos) {
            $nueva = Recoleccion::planificar($datos);
            Response::json([
                "ok"      => true,
                "mensaje" => "Recolección " . $nueva["numero"] . " planificada",
                "datos"   => $nueva,
            ], 201);
        });
    }

    public function iniciar(int $id, array $usuario): void
    {
        $this->ejecutar(function () use ($id, $usuario) {
            Response::json(["ok" => true, "mensaje" => "Recolección iniciada", "datos" => Recoleccion::iniciar($id, $usuario)]);
        });
    }

    public function registrarVaciado(int $id, array $usuario): void
    {
        $datos = Response::leerJson();

        $error = FlujoRecoleccion::errorDeVaciado($datos);
        if ($error !== null) {
            Response::error($error, 400);
        }

        $this->ejecutar(function () use ($id, $datos, $usuario) {
            $recoleccion = Recoleccion::registrarVaciado($id, $datos, $usuario);
            Response::json(["ok" => true, "mensaje" => "Vaciado registrado", "datos" => $recoleccion], 201);
        });
    }

    public function finalizar(int $id, array $usuario): void
    {
        $observacion = $this->observacion(false);

        $this->ejecutar(function () use ($id, $observacion, $usuario) {
            $recoleccion = Recoleccion::cerrar($id, "finalizar", $observacion, $usuario);
            Response::json(["ok" => true, "mensaje" => "Recolección finalizada", "datos" => $recoleccion]);
        });
    }

    public function cancelar(int $id, array $usuario): void
    {
        $observacion = $this->observacion(true);

        $this->ejecutar(function () use ($id, $observacion, $usuario) {
            $recoleccion = Recoleccion::cerrar($id, "cancelar", $observacion, $usuario);
            Response::json(["ok" => true, "mensaje" => "Recolección cancelada", "datos" => $recoleccion]);
        });
    }

    private function observacion(bool $obligatoria): string
    {
        $datos = Response::leerJson();

        $error = FlujoRecoleccion::errorDeTexto($datos["observacion"] ?? null, $obligatoria);
        if ($error !== null) {
            Response::error($error, 400);
        }
        return FlujoRecoleccion::limpiar($datos["observacion"] ?? null);
    }

    private function ejecutar(callable $accion): void
    {
        try {
            $accion();
        } catch (ErrorDeNegocio $e) {
            $cuerpo = ["ok" => false, "mensaje" => $e->getMessage()];
            if ($e->datos() !== []) {
                $cuerpo["datos"] = $e->datos();
            }
            Response::json($cuerpo, $e->estadoHttp());
        }
    }
}
