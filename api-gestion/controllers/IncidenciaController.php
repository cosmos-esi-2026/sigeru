<?php

require_once __DIR__ . "/../models/Incidencia.php";
require_once __DIR__ . "/../models/FlujoIncidencia.php";
require_once __DIR__ . "/../core/ErrorDeNegocio.php";
require_once __DIR__ . "/../core/Response.php";
require_once __DIR__ . "/../core/Notificador.php";

class IncidenciaController
{
    public function listar(array $usuario): void
    {
        $consulta = FlujoIncidencia::filtrosDe($_GET);
        if ($consulta["error"] !== null) {
            Response::error($consulta["error"], 400);
        }
        Response::json(["ok" => true, "datos" => Incidencia::listar($consulta["filtros"], $usuario)]);
    }

    public function obtener(int $id, array $usuario): void
    {
        $incidencia = Incidencia::obtener($id);
        if ($incidencia === null || !FlujoIncidencia::alcanza($usuario, $incidencia)) {
            Response::error("Incidencia no encontrada", 404);
        }
        Response::json(["ok" => true, "datos" => Incidencia::detalle($id, $usuario)]);
    }

    public function crear(array $usuario): void
    {
        $datos = Response::leerJson();

        $error = FlujoIncidencia::errorDeNueva($datos);
        if ($error !== null) {
            Response::error($error, 400);
        }

        $this->ejecutar(function () use ($datos, $usuario) {
            $nueva = Incidencia::crear($datos, $usuario);
            if (FlujoIncidencia::esDeMantenimiento($nueva["tipo_problema"])) {
                [$titulo, $mensaje] = Notificador::contenedorRoto($nueva["numero"], (string) ($nueva["direccion"] ?? ""));
                Notificador::avisarA(Notificador::administradores(), "contenedor_roto", $titulo, $mensaje, "incidencia.html?id=" . (int) $nueva["id_incidencia"]);
            }
            if (($nueva["prioridad"] ?? "") === "alta") {
                [$titulo, $mensaje] = Notificador::incidenciaCritica($nueva["numero"], (string) ($nueva["direccion"] ?? ""));
                Notificador::avisarA(Notificador::administradores(), "incidencia_critica", $titulo, $mensaje, "incidencia.html?id=" . (int) $nueva["id_incidencia"]);
            }
            Response::json([
                "ok"      => true,
                "mensaje" => "Incidencia " . $nueva["numero"] . " registrada",
                "datos"   => $nueva,
            ], 201);
        });
    }

    public function asignar(int $id, array $usuario): void
    {
        $datos = Response::leerJson();

        $idCuadrilla = FlujoIncidencia::entero($datos["id_cuadrilla"] ?? null);
        if ($idCuadrilla === null) {
            Response::error("Indicá la cuadrilla a la que se asigna", 400);
        }
        $prioridad = $datos["prioridad"] ?? null;
        $error = $prioridad === null ? null : FlujoIncidencia::errorDePrioridad($prioridad);
        if ($error !== null) {
            Response::error($error, 400);
        }

        $this->ejecutar(function () use ($id, $idCuadrilla, $prioridad, $usuario) {
            $antes = Incidencia::obtener($id);
            $incidencia = Incidencia::asignar($id, $idCuadrilla, $prioridad, $usuario);
            [$titulo, $mensaje] = Notificador::incidenciaAsignada($incidencia["numero"], (string) ($incidencia["direccion"] ?? ""));
            Notificador::avisarA(Notificador::deCuadrilla($idCuadrilla), "incidencia_asignada", $titulo, $mensaje, "incidencia.html?id=" . $id);
            if ($antes !== null && $antes["estado"] === "abierta") {
                $this->avisarAlVecino($id, (int) $antes["id_usuario"], "en_curso");
            }
            Response::json(["ok" => true, "mensaje" => "Incidencia asignada", "datos" => $incidencia]);
        });
    }

    // POST /incidencias/{id}/derivacion {"nota": "..."}: un contenedor roto o dañado pasa a mantenimiento. Solo el administrador.
    public function derivar(int $id, array $usuario): void
    {
        $datos = Response::leerJson();
        $nota = FlujoIncidencia::limpiar($datos["nota"] ?? null);
        if (mb_strlen($nota) > 200) {
            Response::error("La nota no puede pasar de 200 caracteres", 400);
        }
        $this->ejecutar(function () use ($id, $nota, $usuario) {
            $antes = Incidencia::obtener($id);
            $incidencia = Incidencia::derivar($id, $nota, $usuario);
            if ($antes !== null) {
                $this->avisarAlVecino($id, (int) $antes["id_usuario"], "derivada");
            }
            Response::json(["ok" => true, "mensaje" => "Derivada a mantenimiento", "datos" => $incidencia]);
        });
    }

    public function cambiarPrioridad(int $id, array $usuario): void
    {
        $datos = Response::leerJson();

        $error = FlujoIncidencia::errorDePrioridad($datos["prioridad"] ?? null);
        if ($error !== null) {
            Response::error($error, 400);
        }

        $this->ejecutar(function () use ($id, $datos, $usuario) {
            $incidencia = Incidencia::cambiarPrioridad($id, $datos["prioridad"], $usuario);
            Response::json(["ok" => true, "mensaje" => "Prioridad actualizada", "datos" => $incidencia]);
        });
    }

    public function registrarAvance(int $id, array $usuario): void
    {
        $observacion = $this->observacion(true);

        $this->ejecutar(function () use ($id, $observacion, $usuario) {
            $incidencia = Incidencia::registrarAvance($id, $observacion, $usuario);
            Response::json(["ok" => true, "mensaje" => "Avance registrado", "datos" => $incidencia]);
        });
    }

    public function resolver(int $id, array $usuario): void
    {
        $observacion = $this->observacion(false);

        $this->ejecutar(function () use ($id, $observacion, $usuario) {
            $antes = Incidencia::obtener($id);
            $incidencia = Incidencia::cerrar($id, "resolver", $observacion, $usuario);
            if ($antes !== null) {
                $this->avisarAlVecino($id, (int) $antes["id_usuario"], "resuelta");
            }
            Response::json(["ok" => true, "mensaje" => "Incidencia resuelta", "datos" => $incidencia]);
        });
    }

    public function descartar(int $id, array $usuario): void
    {
        $observacion = $this->observacion(true);

        $this->ejecutar(function () use ($id, $observacion, $usuario) {
            $antes = Incidencia::obtener($id);
            $incidencia = Incidencia::cerrar($id, "descartar", $observacion, $usuario);
            if ($antes !== null) {
                $this->avisarAlVecino($id, (int) $antes["id_usuario"], "descartada");
            }
            Response::json(["ok" => true, "mensaje" => "Incidencia descartada", "datos" => $incidencia]);
        });
    }

    // Al vecino que informo: campanita y correo (si lo tiene activado). Nunca rompe la operacion.
    private function avisarAlVecino(int $idIncidencia, int $idVecino, string $estado): void
    {
        [$titulo, $mensaje] = Notificador::incidenciaEstado(FlujoIncidencia::numero($idIncidencia), $estado);
        Notificador::avisar($idVecino, "incidencia_" . $estado, $titulo, $mensaje, "incidencia.html?id=" . $idIncidencia);
    }

    private function observacion(bool $obligatoria): string
    {
        $datos = Response::leerJson();
        $texto = FlujoIncidencia::limpiar($datos["observacion"] ?? null);

        $error = FlujoIncidencia::errorDeObservacion($texto, $obligatoria);
        if ($error !== null) {
            Response::error($error, 400);
        }
        return $texto;
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
