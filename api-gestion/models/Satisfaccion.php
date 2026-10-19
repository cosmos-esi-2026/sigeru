<?php

require_once __DIR__ . "/Estadisticas.php";

// Calidad del servicio para el administrador (extras, parte 14): incidencias, satisfaccion promedio y tiempo medio de
// resolucion por zona y por cuadrilla. Clase pura: recibe filas ya leidas (una por incidencia del periodo).
class Satisfaccion
{
    // Cada fila: zona, cuadrilla (o null), estado, fecha_reporte, fecha_resolucion (o null), puntaje (o null).
    public static function informe(array $filas): array
    {
        $zonas = [];
        $cuadrillas = [];
        $distribucion = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        $puntajes = [];
        foreach ($filas as $f) {
            self::sumar($zonas, $f["zona"], $f);
            if ($f["cuadrilla"] !== null) {
                self::sumar($cuadrillas, $f["cuadrilla"], $f);
            }
            if ($f["puntaje"] !== null) {
                $distribucion[(int) $f["puntaje"]]++;
                $puntajes[] = (int) $f["puntaje"];
            }
        }
        $porZona = [];
        foreach ($zonas as $nombre => $g) {
            $porZona[] = self::cerrar("zona", (string) $nombre, $g);
        }
        usort($porZona, fn($a, $b) => [$b["incidencias"], $a["zona"]] <=> [$a["incidencias"], $b["zona"]]);
        $porCuadrilla = [];
        foreach ($cuadrillas as $nombre => $g) {
            $porCuadrilla[] = self::cerrar("cuadrilla", (string) $nombre, $g);
        }
        usort($porCuadrilla, fn($a, $b) => [$b["incidencias"], $a["cuadrilla"]] <=> [$a["incidencias"], $b["cuadrilla"]]);
        return [
            "general" => [
                "incidencias"    => count($filas),
                "calificaciones" => count($puntajes),
                "promedio"       => Estadisticas::redondear(Estadisticas::promedio($puntajes)),
                "distribucion"   => $distribucion,
            ],
            "por_zona"      => $porZona,
            "por_cuadrilla" => $porCuadrilla,
        ];
    }

    private static function sumar(array &$grupos, string $nombre, array $f): void
    {
        $grupos[$nombre] ??= ["incidencias" => 0, "resueltas" => 0, "puntajes" => [], "horas" => []];
        $grupos[$nombre]["incidencias"]++;
        if ($f["estado"] === "resuelta") {
            $grupos[$nombre]["resueltas"]++;
            $horas = Estadisticas::horasEntre($f["fecha_reporte"], $f["fecha_resolucion"]);
            if ($horas !== null) {
                $grupos[$nombre]["horas"][] = $horas;
            }
        }
        if ($f["puntaje"] !== null) {
            $grupos[$nombre]["puntajes"][] = (int) $f["puntaje"];
        }
    }

    private static function cerrar(string $clave, string $nombre, array $g): array
    {
        return [$clave => $nombre, "incidencias" => $g["incidencias"], "resueltas" => $g["resueltas"],
                "calificaciones" => count($g["puntajes"]), "promedio" => Estadisticas::redondear(Estadisticas::promedio($g["puntajes"])),
                "tiempo_medio_horas" => Estadisticas::redondear(Estadisticas::promedio($g["horas"]))];
    }
}
