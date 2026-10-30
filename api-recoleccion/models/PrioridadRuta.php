<?php

// Prioridad de recoleccion de un contenedor y de una ruta (plan de rutas para la defensa, bloques 6 y 7; 22/10/2026).
//
// Es una regla simple y EXPLICABLE, no inteligencia artificial. Decision SIN Fornos (su idea: «lleno» es prioridad media, «desbordado» mas alta, y un contenedor
// roto o danado no lo recolecta el camion: lo atiende mantenimiento):
//   - desbordado  (una incidencia «desbordado» abierta o en curso)                  -> ALTA   (100 puntos)
//   - lleno       (el contenedor esta lleno, o hay una incidencia «lleno» abierta)   -> MEDIA  ( 50 puntos)
//   - el resto                                                                       -> NORMAL (  0 puntos)
//   - roto o en mantenimiento (o una incidencia «roto» abierta)                      -> NO SE RECOLECTA: no suma y se muestra aparte
// Un contenedor puntua por lo mas grave que tenga (no se suman). La ruta toma el nivel MAS ALTO de sus contenedores recolectables y suma sus puntos.
// Esta clase es PURA (sin base de datos): recibe las paradas ya armadas y se prueba con casos hechos a mano.
final class PrioridadRuta
{
    public const ALTA = "alta";
    public const MEDIA = "media";
    public const NORMAL = "normal";

    public const PUNTOS_DESBORDADO = 100;
    public const PUNTOS_LLENO = 50;

    private const RANGO = [self::NORMAL => 0, self::MEDIA => 1, self::ALTA => 2];

    // $parada: ["estado" => estado del contenedor, "incidencias" => [tipos de incidencia abierta o en curso en ese contenedor]]
    public static function deContenedor(array $parada): array
    {
        $estado = (string) ($parada["estado"] ?? "disponible");
        $incidencias = $parada["incidencias"] ?? [];
        $recolectable = !in_array($estado, ["mantenimiento", "retirado"], true) && !in_array("roto", $incidencias, true);
        if (!$recolectable) {
            return ["recolectable" => false, "nivel" => self::NORMAL, "puntaje" => 0];
        }
        if (in_array("desbordado", $incidencias, true)) {
            return ["recolectable" => true, "nivel" => self::ALTA, "puntaje" => self::PUNTOS_DESBORDADO];
        }
        if ($estado === "lleno" || in_array("lleno", $incidencias, true)) {
            return ["recolectable" => true, "nivel" => self::MEDIA, "puntaje" => self::PUNTOS_LLENO];
        }
        return ["recolectable" => true, "nivel" => self::NORMAL, "puntaje" => 0];
    }

    // Devuelve ["nivel", "puntaje", "urgentes" (desbordados), "llenos", "no_recolectables", "paradas"].
    public static function deRuta(array $paradas): array
    {
        $nivel = self::NORMAL;
        $puntaje = 0;
        $urgentes = 0;
        $llenos = 0;
        $noRecolectables = 0;
        foreach ($paradas as $parada) {
            $c = self::deContenedor($parada);
            if (!$c["recolectable"]) {
                $noRecolectables++;
                continue;
            }
            $puntaje += $c["puntaje"];
            $urgentes += $c["nivel"] === self::ALTA ? 1 : 0;
            $llenos += $c["nivel"] === self::MEDIA ? 1 : 0;
            if (self::RANGO[$c["nivel"]] > self::RANGO[$nivel]) {
                $nivel = $c["nivel"];
            }
        }
        return ["nivel" => $nivel, "puntaje" => $puntaje, "urgentes" => $urgentes, "llenos" => $llenos, "no_recolectables" => $noRecolectables, "paradas" => count($paradas)];
    }

    public static function texto(string $nivel): string
    {
        return match ($nivel) {
            self::ALTA  => "Alta",
            self::MEDIA => "Media",
            default     => "Normal",
        };
    }

    // Ordena rutas (cada una con "prioridad" => deRuta(), "dias_sin_recoleccion" => int|null, "id_ruta"): primero el nivel mas alto, despues el mayor puntaje,
    // despues la que hace mas tiempo que no se recolecta (una que nunca se recolecto va antes), y por ultimo el id para que el orden sea siempre el mismo.
    public static function ordenar(array $rutas): array
    {
        usort($rutas, static function (array $a, array $b): int {
            return [self::RANGO[$b["prioridad"]["nivel"]], $b["prioridad"]["puntaje"], $b["dias_sin_recoleccion"] ?? PHP_INT_MAX, $a["id_ruta"]]
               <=> [self::RANGO[$a["prioridad"]["nivel"]], $a["prioridad"]["puntaje"], $a["dias_sin_recoleccion"] ?? PHP_INT_MAX, $b["id_ruta"]];
        });
        return $rutas;
    }
}
