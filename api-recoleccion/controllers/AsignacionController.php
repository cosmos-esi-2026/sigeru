<?php

require_once __DIR__ . "/../models/AsignacionDia.php";
require_once __DIR__ . "/../models/RecorridoRuta.php";
require_once __DIR__ . "/../models/Recoleccion.php";
require_once __DIR__ . "/../core/Response.php";

// «Asignacion de rutas» (solo Administrador) y «Mis rutas» (solo Cuadrilla). Plan de rutas para la defensa, bloque 6.
class AsignacionController
{
    // GET /asignacion?fecha=AAAA-MM-DD[&otros_dias=1]: las rutas de ese dia por prioridad, las cuadrillas y camiones libres y la sugerencia. No crea nada.
    public function dia(): void
    {
        $fecha = $_GET["fecha"] ?? "";
        $f = is_string($fecha) ? DateTime::createFromFormat("!Y-m-d", $fecha) : false;
        if ($f === false || $f->format("Y-m-d") !== $fecha) {
            Response::error("Parámetro 'fecha' inválido: una fecha con el formato AAAA-MM-DD", 400);
        }
        Response::json(["ok" => true, "datos" => AsignacionDia::armar($fecha, ($_GET["otros_dias"] ?? "") === "1")]);
    }

    // GET /mis-rutas: las recolecciones planificadas o en curso de MI cuadrilla, cada una con su ruta (paradas en orden, trazado y prioridad).
    public function misRutas(array $usuario): void
    {
        if (($usuario["id_cuadrilla"] ?? null) === null) {
            Response::json(["ok" => true, "sin_cuadrilla" => true, "datos" => []]);
        }
        $filas = array_map(static function (array $r): array {
            return [
                "id_recoleccion" => $r["id_recoleccion"], "numero" => $r["numero"], "estado" => $r["estado"], "fecha_hora_inicio" => $r["fecha_hora_inicio"],
                "cuadrilla" => $r["cuadrilla"], "matricula" => $r["matricula"], "centro" => $r["centro"],
                "recorrido" => RecorridoRuta::de($r["id_ruta"], $r["id_recoleccion"]),
            ];
        }, Recoleccion::listar(["activas" => true], $usuario));
        Response::json(["ok" => true, "sin_cuadrilla" => false, "datos" => $filas]);
    }
}
