<?php

// Prediccion de llenado (extras, parte 16): estimacion estadistica, no IA. Casos conocidos y la cuenta hecha a mano.

require_once __DIR__ . "/../api-gestion/models/Prediccion.php";

titulo("34. Prediccion de llenado: casos conocidos");

$ahora = "2026-10-19 12:00:00";
$hace = fn(float $horas) => gmdate("Y-m-d H:i:s", strtotime($ahora . " UTC") - (int) round($horas * 3600));
$serie = fn(int $cuantos, float $intervalo, float $ultimo) => $cuantos <= 0 ? [] : array_map(fn($i) => $hace($ultimo + $i * $intervalo), range(0, $cuantos - 1));

$c = Prediccion::calcular($serie(6, 48, 6), "disponible", $ahora);
afirmar("Ritmo constante de 48 h y el ultimo vaciado hace 6 h: llega al 90 % en 0,9 x 48 - 6 = 37,2 h", ["estimada", 37.2, 13, 48.0, 6, "media"], [
    $c["estado"], $c["horas_hasta_90"], $c["nivel_estimado_pct"], $c["intervalo_medio_horas"], $c["vaciados"], $c["confianza"]]);
afirmar("El momento del 90 % es ahora mas esas horas", "2026-10-21 01:12:00", $c["momento_90"]);
afirmar("Con 8 vaciados y ritmo parejo la confianza es alta", "alta", Prediccion::calcular($serie(8, 48, 6), "disponible", $ahora)["confianza"]);
afirmar("Las fechas pueden llegar desordenadas", 37.2, Prediccion::calcular(array_reverse($serie(6, 48, 6)), "disponible", $ahora)["horas_hasta_90"]);

foreach ([0, 1, 2] as $cuantos) {
    $c = Prediccion::calcular($serie($cuantos, 48, 6), "disponible", $ahora);
    afirmar("Con $cuantos vaciados: sin datos suficientes (hacen falta 3)", ["sin_datos", null, null, null, $cuantos], [$c["estado"], $c["horas_hasta_90"], $c["nivel_estimado_pct"], $c["confianza"], $c["vaciados"]]);
}
$c = Prediccion::calcular($serie(3, 48, 6), "disponible", $ahora);
afirmar("Con justo 3 vaciados ya estima, pero con confianza baja", ["estimada", 37.2, "baja"], [$c["estado"], $c["horas_hasta_90"], $c["confianza"]]);

$c = Prediccion::calcular($serie(6, 48, 6), "lleno", $ahora);
afirmar("Un contenedor ya lleno: estado lleno, 0 horas y 100 %", ["lleno", 0.0, 100], [$c["estado"], $c["horas_hasta_90"], $c["nivel_estimado_pct"]]);
afirmar("Un contenedor lleno sin historial tambien es lleno (no hace falta predecir)", "lleno", Prediccion::calcular([], "lleno", $ahora)["estado"]);
foreach (["retirado", "mantenimiento"] as $estado) {
    afirmar("Un contenedor $estado no se predice", "sin_datos", Prediccion::calcular($serie(8, 48, 6), $estado, $ahora)["estado"]);
}
$c = Prediccion::calcular($serie(6, 48, 60), "disponible", $ahora);
afirmar("Si ya paso mas tiempo que el intervalo: 0 horas y 100 % (ya deberia estar al 90 % o mas)", ["estimada", 0.0, 100], [$c["estado"], $c["horas_hasta_90"], $c["nivel_estimado_pct"]]);

$irregular = array_map($hace, [6, 30, 126, 138, 210, 234]);
$c = Prediccion::calcular($irregular, "disponible", $ahora);
afirmar("Datos irregulares (intervalos de 24, 96, 12, 72 y 24 h): confianza baja aunque haya 6 vaciados", ["estimada", "baja"], [$c["estado"], $c["confianza"]]);
afirmar("El intervalo medio de esos datos es 45,6 h", 45.6, $c["intervalo_medio_horas"]);
afirmar("Un vaciado en el futuro (reloj raro) se ignora", 3, Prediccion::calcular(array_merge($serie(3, 48, 6), [$hace(-5)]), "disponible", $ahora)["vaciados"]);
$c = Prediccion::calcular(array_merge($serie(4, 48, 6), [$hace(6)]), "disponible", $ahora);
afirmar("Con una fecha repetida el intervalo cero se descarta", [5, 48.0], [$c["vaciados"], $c["intervalo_medio_horas"]]);
afirmar("Una fecha vacia se ignora", 3, Prediccion::calcular(array_merge($serie(3, 48, 6), [""]), "disponible", $ahora)["vaciados"]);

titulo("34 bis. Prediccion de llenado: confianza y textos");

foreach ([[8, 0.25, "alta"], [8, 0.26, "media"], [7, 0.1, "media"], [5, 0.5, "media"], [5, 0.51, "baja"], [4, 0.0, "baja"], [20, 0.9, "baja"]] as [$n, $v, $esperada]) {
    afirmar("Confianza con $n vaciados y variacion $v: $esperada", $esperada, Prediccion::confianza($n, $v));
}
foreach ([[null, "sin datos suficientes"], [0.0, "ahora"], [0.4, "ahora"], [5.2, "en 5 h"], [23.4, "en 23 h"], [24.0, "en 1 día"], [37.2, "en 1 día y 13 h"], [50.0, "en 2 días y 2 h"], [168.0, "en 7 días"]] as [$horas, $texto]) {
    afirmar("El texto de " . var_export($horas, true) . " horas: $texto", $texto, Prediccion::textoDeHoras($horas));
}
afirmar("La prediccion exige 3 vaciados y usa el 90 % como umbral", [3, 0.9], [Prediccion::VACIADOS_MINIMOS, Prediccion::UMBRAL]);
