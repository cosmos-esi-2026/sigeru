<?php

require_once __DIR__ . "/FlujoRecoleccion.php";

// Resumen de lo recolectado en un periodo (kg y litros por tipo de residuo y por zona).
// Es puro: recibe las filas ya leidas, no toca la base.
class ResumenRecoleccion
{
    public const ESTADOS = ["planificada", "en_curso", "finalizada", "cancelada"];

    private static function numero($valor): float
    {
        return $valor === null ? 0.0 : (float) $valor;
    }

    private static function redondear(array $fila): array
    {
        $fila["peso_kg"] = round($fila["peso_kg"], 2);
        $fila["volumen_l"] = round($fila["volumen_l"], 2);
        return $fila;
    }

    // $vaciados: una fila por vaciado (tipo_residuo, id_zona, zona, peso_kg, volumen_l).
    // $recolecciones: una fila por recoleccion (estado).
    public static function resumen(array $vaciados, array $recolecciones): array
    {
        $porEstado = array_fill_keys(self::ESTADOS, 0);
        foreach ($recolecciones as $recoleccion) {
            $porEstado[$recoleccion["estado"]]++;
        }

        $totales = ["recolecciones" => count($recolecciones), "vaciados" => 0, "peso_kg" => 0.0,
                    "volumen_l" => 0.0, "vaciados_sin_peso" => 0];
        $porTipo = [];
        $porZona = [];

        foreach ($vaciados as $v) {
            $peso = self::numero($v["peso_kg"]);
            $volumen = self::numero($v["volumen_l"]);

            $totales["vaciados"]++;
            $totales["peso_kg"] += $peso;
            $totales["volumen_l"] += $volumen;
            $totales["vaciados_sin_peso"] += $v["peso_kg"] === null ? 1 : 0;

            $tipo = $v["tipo_residuo"];
            $porTipo[$tipo] ??= ["tipo_residuo" => $tipo, "vaciados" => 0, "peso_kg" => 0.0, "volumen_l" => 0.0];
            $porTipo[$tipo]["vaciados"]++;
            $porTipo[$tipo]["peso_kg"] += $peso;
            $porTipo[$tipo]["volumen_l"] += $volumen;

            $idZona = (int) $v["id_zona"];
            $porZona[$idZona] ??= ["id_zona" => $idZona, "zona" => $v["zona"], "vaciados" => 0,
                                   "peso_kg" => 0.0, "volumen_l" => 0.0];
            $porZona[$idZona]["vaciados"]++;
            $porZona[$idZona]["peso_kg"] += $peso;
            $porZona[$idZona]["volumen_l"] += $volumen;
        }

        $porTipo = array_map([self::class, "redondear"], array_values($porTipo));
        $porZona = array_map([self::class, "redondear"], array_values($porZona));
        usort($porTipo, fn ($a, $b) => [$b["peso_kg"], $a["tipo_residuo"]] <=> [$a["peso_kg"], $b["tipo_residuo"]]);
        usort($porZona, fn ($a, $b) => [$b["peso_kg"], $a["zona"]] <=> [$a["peso_kg"], $b["zona"]]);

        return [
            "totales"          => self::redondear($totales) + ["por_estado" => $porEstado],
            "por_tipo_residuo" => $porTipo,
            "por_zona"         => $porZona,
        ];
    }
}
