<?php

// Ruta sugerida para el camion (extras, parte 17). NO ES IA: es una heuristica clasica que el equipo puede explicar.
// Clase pura: recibe los contenedores ya leidos (con su prediccion) y devuelve el orden, los kilometros y el tiempo.
//
// 1) Se eligen los contenedores que ya estan llenos o que segun la prediccion (parte 16) llegan al 90 % dentro del horizonte.
//    Si son mas de TOPE, se quedan los mas urgentes.
// 2) Se ordenan por "vecino mas cercano": se empieza por el contenedor mas lejos del centro de destino (o el mas al norte, si no
//    hay destino) y siempre se va al mas cercano que falta. Distancia: haversine, en linea recta, NO por calles.
// 3) Se mejora con 2-opt: se invierte un tramo del recorrido mientras eso lo acorte. Si hay centro de destino, el recorrido termina ahi.
// Todo es determinista: ante un empate manda el orden de entrada (primero los mas urgentes, despues por codigo).
class RutaSugerida
{
    public const TOPE = 25;
    public const RADIO_TIERRA_KM = 6371.0088;
    public const VELOCIDAD_KMH = 20.0;
    public const MINUTOS_POR_PARADA = 5;
    public const HORIZONTES = [24, 48];

    public static function distanciaKm(array $a, array $b): float
    {
        $f1 = deg2rad((float) $a["latitud"]);
        $f2 = deg2rad((float) $b["latitud"]);
        $df = $f2 - $f1;
        $dl = deg2rad((float) $b["longitud"] - (float) $a["longitud"]);
        $h = sin($df / 2) ** 2 + cos($f1) * cos($f2) * sin($dl / 2) ** 2;
        return 2 * self::RADIO_TIERRA_KM * asin(min(1.0, sqrt($h)));
    }

    // $filas: contenedores con estado, latitud, longitud, codigo y horas_hasta_90 (null si no hay prediccion).
    // Devuelve ["elegidos" => [...], "descartados_por_tope" => n]. Elegidos va ordenado de mas a menos urgente.
    public static function seleccionar(array $filas, int $horizonte): array
    {
        $candidatos = [];
        foreach ($filas as $f) {
            $sinUbicacion = !is_numeric($f["latitud"] ?? null) || !is_numeric($f["longitud"] ?? null);
            if ($sinUbicacion || !in_array($f["estado"] ?? "", ["disponible", "lleno"], true)) {
                continue;
            }
            $horas = ($f["estado"] ?? "") === "lleno" ? 0.0 : ($f["horas_hasta_90"] ?? null);
            if ($horas !== null && $horas <= $horizonte) {
                $candidatos[] = $f + ["urgencia_horas" => (float) $horas];
            }
        }
        usort($candidatos, fn($a, $b) => [$a["urgencia_horas"], (string) ($a["codigo"] ?? "")] <=> [$b["urgencia_horas"], (string) ($b["codigo"] ?? "")]);
        return ["elegidos" => array_slice($candidatos, 0, self::TOPE), "descartados_por_tope" => max(0, count($candidatos) - self::TOPE)];
    }

    // Devuelve los indices de $puntos en el orden del recorrido. $destino (opcional) es el ultimo punto fijo.
    public static function ordenar(array $puntos, ?array $destino = null): array
    {
        $n = count($puntos);
        if ($n <= 1) {
            return $n === 0 ? [] : [0];
        }
        $puntos = array_values($puntos);

        // Punto de partida: el mas lejos del destino, o el mas al norte (empata el primero de la lista).
        $inicio = 0;
        for ($i = 1; $i < $n; $i++) {
            $mejor = $destino !== null
                ? self::distanciaKm($puntos[$i], $destino) > self::distanciaKm($puntos[$inicio], $destino) + 1e-12
                : (float) $puntos[$i]["latitud"] > (float) $puntos[$inicio]["latitud"] + 1e-12;
            if ($mejor) {
                $inicio = $i;
            }
        }

        // Vecino mas cercano.
        $orden = [$inicio];
        $faltan = array_values(array_diff(range(0, $n - 1), [$inicio]));
        while ($faltan !== []) {
            $ultimo = $puntos[end($orden)];
            $elegido = 0;
            foreach ($faltan as $k => $idx) {
                if (self::distanciaKm($ultimo, $puntos[$idx]) < self::distanciaKm($ultimo, $puntos[$faltan[$elegido]]) - 1e-12) {
                    $elegido = $k;
                }
            }
            $orden[] = $faltan[$elegido];
            array_splice($faltan, $elegido, 1);
        }

        // 2-opt sobre un recorrido abierto (el final es el destino, si hay).
        for ($pasada = 0; $pasada < 200; $pasada++) {
            $mejoro = false;
            for ($i = 0; $i < $n - 1; $i++) {
                for ($j = $i + 1; $j < $n; $j++) {
                    $antes = $i > 0 ? $puntos[$orden[$i - 1]] : null;
                    $despues = $j < $n - 1 ? $puntos[$orden[$j + 1]] : $destino;
                    $b = $puntos[$orden[$i]];
                    $c = $puntos[$orden[$j]];
                    $actual = ($antes ? self::distanciaKm($antes, $b) : 0.0) + ($despues ? self::distanciaKm($c, $despues) : 0.0);
                    $nuevo = ($antes ? self::distanciaKm($antes, $c) : 0.0) + ($despues ? self::distanciaKm($b, $despues) : 0.0);
                    if ($nuevo < $actual - 1e-9) {
                        array_splice($orden, $i, $j - $i + 1, array_reverse(array_slice($orden, $i, $j - $i + 1)));
                        $mejoro = true;
                    }
                }
            }
            if (!$mejoro) {
                break;
            }
        }
        return $orden;
    }

    // Largo del recorrido (km) para un orden dado, con el tramo final al destino si hay.
    public static function largoKm(array $puntos, array $orden, ?array $destino = null): float
    {
        $total = 0.0;
        for ($i = 1; $i < count($orden); $i++) {
            $total += self::distanciaKm($puntos[$orden[$i - 1]], $puntos[$orden[$i]]);
        }
        if ($destino !== null && $orden !== []) {
            $total += self::distanciaKm($puntos[$orden[count($orden) - 1]], $destino);
        }
        return $total;
    }

    // Arma la propuesta completa. $filas: ver seleccionar(). $destino: ["latitud", "longitud", ...] o null.
    public static function sugerir(array $filas, int $horizonte, ?array $destino = null): array
    {
        $seleccion = self::seleccionar($filas, $horizonte);
        $elegidos = $seleccion["elegidos"];
        $orden = self::ordenar($elegidos, $destino);

        $paradas = [];
        $anterior = null;
        foreach ($orden as $posicion => $idx) {
            $fila = $elegidos[$idx];
            $paradas[] = $fila + ["orden" => $posicion + 1, "km_desde_anterior" => $anterior === null ? 0.0 : round(self::distanciaKm($anterior, $fila), 2)];
            $anterior = $fila;
        }
        $kmAlDestino = ($destino !== null && $anterior !== null) ? round(self::distanciaKm($anterior, $destino), 2) : null;
        $km = self::largoKm($elegidos, $orden, $destino);

        return [
            "paradas" => $paradas,
            "cantidad" => count($paradas),
            "distancia_km" => round($km, 2),
            "km_al_destino" => $kmAlDestino,
            "tiempo_min" => $paradas === [] ? 0 : (int) round($km / self::VELOCIDAD_KMH * 60 + count($paradas) * self::MINUTOS_POR_PARADA),
            "descartados_por_tope" => $seleccion["descartados_por_tope"],
        ];
    }
}
