<?php

// Impacto ambiental (extras, parte 18). Clase pura: recibe filas ya leidas. TODA cifra de CO2 es una ESTIMACION: kg recolectados de
// cada tipo de residuo reciclable por el factor de emision evitada de ese tipo (kg de CO2e evitados por kg reciclado en lugar de
// enterrado, de la fuente que se cita). Un tipo sin factor verificado no suma CO2: se informan solo sus kg. No se inventa ninguna cifra.
class Impacto
{
    // $filas: cada una con tipo, reciclable (bool), kg (numero) y factor (numero o null).
    public static function calcular(array $filas): array
    {
        $total = 0.0;
        $reciclable = 0.0;
        $sinFactor = 0.0;
        $co2 = 0.0;
        $porTipo = [];
        foreach ($filas as $f) {
            $kg = (float) $f["kg"];
            $factor = $f["factor"] === null ? null : (float) $f["factor"];
            $esReciclable = (bool) $f["reciclable"];
            $co2Tipo = $esReciclable && $factor !== null ? round($kg * $factor, 1) : null;
            $total += $kg;
            if ($esReciclable) {
                $reciclable += $kg;
                if ($factor === null) {
                    $sinFactor += $kg;
                }
            }
            $co2 += $co2Tipo ?? 0.0;
            $porTipo[] = ["tipo" => $f["tipo"], "reciclable" => $esReciclable, "kg" => round($kg, 2), "factor" => $factor, "co2_evitado_kg" => $co2Tipo];
        }
        return [
            "kg_total"        => round($total, 2),
            "kg_reciclable"   => round($reciclable, 2),
            "pct_reciclable"  => $total > 0 ? round($reciclable / $total * 100, 1) : null,
            "co2_evitado_kg"  => round($co2, 1),
            "kg_sin_factor"   => round($sinFactor, 2),
            "por_tipo"        => $porTipo,
        ];
    }

    // $filas: cada una con mes ("AAAA-MM") ademas de lo de arriba. Devuelve una entrada por mes, de la mas vieja a la mas nueva.
    public static function porMes(array $filas): array
    {
        $meses = [];
        foreach ($filas as $f) {
            $meses[$f["mes"]][] = $f;
        }
        ksort($meses);
        $resultado = [];
        foreach ($meses as $mes => $delMes) {
            $resultado[] = ["mes" => (string) $mes] + self::calcular($delMes);
        }
        return $resultado;
    }

    // Las fuentes distintas de los factores que se usaron: [["fuente" => ..., "url" => ..., "tipos" => ["Vidrio", ...]], ...]
    public static function fuentes(array $tipos): array
    {
        $porFuente = [];
        foreach ($tipos as $t) {
            if ($t["factor"] === null || ($t["fuente"] ?? null) === null) {
                continue;
            }
            $porFuente[$t["fuente"]]["fuente"] = $t["fuente"];
            $porFuente[$t["fuente"]]["url"] = $t["url"] ?? null;
            $porFuente[$t["fuente"]]["tipos"][] = $t["tipo"];
        }
        return array_values($porFuente);
    }
}
