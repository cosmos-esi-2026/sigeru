<?php

// Cuando se llena un contenedor (extras, parte 16). NO ES IA NI APRENDIZAJE AUTOMATICO: es una estimacion estadistica
// simple que el equipo puede explicar. Clase pura: recibe las fechas de los vaciados ya leidas.
//
// Idea: cada contenedor se vacia con cierto ritmo. Se mide el intervalo medio entre vaciados (mu). Si el contenedor se llena
// de forma pareja, el nivel actual se estima como el tiempo que paso desde el ultimo vaciado dividido ese intervalo medio,
// y llega al 90 % cuando pasaron 0,9 x mu. Horas hasta el 90 % = 0,9 x mu - horas desde el ultimo vaciado (no menos de 0).
// Hacen falta al menos 3 vaciados. La confianza sale de cuantos datos hay y de cuanto varian los intervalos.
class Prediccion
{
    public const VACIADOS_MINIMOS = 3;
    public const UMBRAL = 0.9;

    private static function horas(string $desde, string $hasta): ?float
    {
        $utc = new DateTimeZone("UTC");
        try {
            $segundos = (new DateTimeImmutable($hasta, $utc))->getTimestamp() - (new DateTimeImmutable($desde, $utc))->getTimestamp();
        } catch (Exception $e) {
            return null;
        }
        return $segundos / 3600;
    }

    // $vaciados: fechas "AAAA-MM-DD HH:MM:SS" (en cualquier orden). $estado: el estado del contenedor. $ahora: "AAAA-MM-DD HH:MM:SS".
    public static function calcular(array $vaciados, string $estado, string $ahora): array
    {
        $fechas = array_values(array_filter(array_map("strval", $vaciados), fn($f) => $f !== "" && $f <= $ahora));
        sort($fechas);
        $cantidad = count($fechas);
        $base = ["estado" => "sin_datos", "horas_hasta_90" => null, "nivel_estimado_pct" => null, "intervalo_medio_horas" => null,
                 "vaciados" => $cantidad, "confianza" => null, "momento_90" => null];

        if ($estado === "lleno") {
            return ["estado" => "lleno", "horas_hasta_90" => 0.0, "nivel_estimado_pct" => 100, "momento_90" => $ahora, "confianza" => null] + $base;
        }
        if (in_array($estado, ["retirado", "mantenimiento"], true) || $cantidad < self::VACIADOS_MINIMOS) {
            return $base;
        }

        $intervalos = [];
        for ($i = 1; $i < $cantidad; $i++) {
            $h = self::horas($fechas[$i - 1], $fechas[$i]);
            if ($h !== null && $h > 0) {
                $intervalos[] = $h;
            }
        }
        if (count($intervalos) < self::VACIADOS_MINIMOS - 1) {
            return $base;
        }
        $media = array_sum($intervalos) / count($intervalos);
        $varianza = array_sum(array_map(fn($h) => ($h - $media) ** 2, $intervalos)) / count($intervalos);
        $variacion = sqrt($varianza) / $media;
        $desdeElUltimo = max(0.0, (float) self::horas($fechas[$cantidad - 1], $ahora));
        $nivel = min(100, (int) round($desdeElUltimo / $media * 100));
        $faltan = max(0.0, self::UMBRAL * $media - $desdeElUltimo);

        return [
            "estado"                => "estimada",
            "horas_hasta_90"        => round($faltan, 1),
            "nivel_estimado_pct"    => $nivel,
            "intervalo_medio_horas" => round($media, 1),
            "vaciados"              => $cantidad,
            "confianza"             => self::confianza($cantidad, $variacion),
            "momento_90"            => (new DateTimeImmutable($ahora, new DateTimeZone("UTC")))->modify("+" . (int) round($faltan * 3600) . " seconds")->format("Y-m-d H:i:s"),
        ];
    }

    // alta: 8 vaciados o mas y ritmo parejo (variacion hasta 25 %); media: 5 o mas y hasta 50 %; baja: el resto.
    public static function confianza(int $vaciados, float $variacion): string
    {
        if ($vaciados >= 8 && $variacion <= 0.25) {
            return "alta";
        }
        if ($vaciados >= 5 && $variacion <= 0.5) {
            return "media";
        }
        return "baja";
    }

    // "37,2 h" -> "en 1 dia y 13 h": un texto para pantalla.
    public static function textoDeHoras(?float $horas): string
    {
        if ($horas === null) {
            return "sin datos suficientes";
        }
        if ($horas < 1) {
            return "ahora";
        }
        $total = (int) round($horas);
        if ($total < 24) {
            return "en $total h";
        }
        $dias = intdiv($total, 24);
        $resto = $total % 24;
        return "en $dias " . ($dias === 1 ? "día" : "días") . ($resto > 0 ? " y $resto h" : "");
    }
}
