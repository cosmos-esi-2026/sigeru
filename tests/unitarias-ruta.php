<?php

// Ruta sugerida (extras, parte 17): heuristica (vecino mas cercano + 2-opt), no IA. Casos con la cuenta hecha a mano.

require_once __DIR__ . "/../api-gestion/models/RutaSugerida.php";

titulo("39. Ruta sugerida: distancia, seleccion y orden");

$p = fn(float $lat, float $lon = 0.0, string $codigo = "X") => ["latitud" => $lat, "longitud" => $lon, "codigo" => $codigo, "estado" => "disponible", "horas_hasta_90" => 5.0];

afirmar("Haversine: un grado de latitud son 111,195 km", 111.195, round(RutaSugerida::distanciaKm($p(0), $p(1)), 3));
afirmar("Haversine: de un punto a si mismo son 0 km", 0.0, RutaSugerida::distanciaKm($p(-34.9, -56.2), $p(-34.9, -56.2)));
afirmar("Haversine: media vuelta al mundo por el ecuador es pi x radio", round(M_PI * RutaSugerida::RADIO_TIERRA_KM, 2), round(RutaSugerida::distanciaKm($p(0, 0), $p(0, 180)), 2));
afirmar("Haversine es simetrica", RutaSugerida::distanciaKm($p(-34.9, -56.2), $p(-34.8, -56.1)), RutaSugerida::distanciaKm($p(-34.8, -56.1), $p(-34.9, -56.2)));

// Tres contenedores sobre un meridiano, a 0,01 grado (1,112 km) uno del otro, mezclados en la entrada.
$linea = [$p(0.02, 0, "B"), $p(0.00, 0, "A"), $p(0.01, 0, "C")];
afirmar("Sin destino se empieza por el mas al norte y se baja: B (0,02) -> C (0,01) -> A (0)", [0, 2, 1], RutaSugerida::ordenar($linea));
$s = RutaSugerida::sugerir($linea, 24);
afirmar("Largo a mano: 2 tramos de 0,01 grado = 2 x 1,1119508 = 2,22 km; a 20 km/h son 6,7 min y 3 paradas x 5 min = 15: 22 min",
    [2.22, 22, 3, null], [$s["distancia_km"], $s["tiempo_min"], $s["cantidad"], $s["km_al_destino"]]);
afirmar("Cada parada trae su orden y los km desde la anterior (0 la primera)", [[1, 0.0], [2, 1.11], [3, 1.11]],
    array_map(fn($x) => [$x["orden"], $x["km_desde_anterior"]], $s["paradas"]));

$destino = ["latitud" => -0.01, "longitud" => 0.0];
$conDestino = RutaSugerida::sugerir($linea, 24, $destino);
afirmar("Con centro de destino el recorrido termina ahi: 3 tramos de 1,1119508 = 3,34 km (el ultimo, 1,11, al centro); 10 min + 15 = 25 min",
    [3.34, 1.11, 25], [$conDestino["distancia_km"], $conDestino["km_al_destino"], $conDestino["tiempo_min"]]);
afirmar("Con destino abajo se empieza por el contenedor mas lejos del destino y se va acercando", ["B", "C", "A"], array_column($conDestino["paradas"], "codigo"));

$vacio = RutaSugerida::sugerir([], 24);
afirmar("Sin contenedores: sin paradas, 0 km y 0 minutos", [[], 0.0, 0, 0], [$vacio["paradas"], $vacio["distancia_km"], $vacio["tiempo_min"], $vacio["cantidad"]]);
$uno = RutaSugerida::sugerir([$p(0.01, 0, "A")], 24);
afirmar("Con un contenedor: una parada, 0 km y 5 minutos de parada", [1, 0.0, 5], [$uno["cantidad"], $uno["distancia_km"], $uno["tiempo_min"]]);
$unoDestino = RutaSugerida::sugerir([$p(0.01, 0, "A")], 24, ["latitud" => 0.0, "longitud" => 0.0]);
afirmar("Con un contenedor y un centro a 0,01 grado: 1,11 km (el trayecto al centro)", [1.11, 1.11], [$unoDestino["distancia_km"], $unoDestino["km_al_destino"]]);

titulo("39 bis. Ruta sugerida: que contenedores entran");

$filas = [
    ["codigo" => "C1", "estado" => "lleno", "horas_hasta_90" => 0.0, "latitud" => "-34.90", "longitud" => "-56.10"],
    ["codigo" => "C2", "estado" => "disponible", "horas_hasta_90" => 10.0, "latitud" => "-34.91", "longitud" => "-56.11"],
    ["codigo" => "C3", "estado" => "disponible", "horas_hasta_90" => 30.0, "latitud" => "-34.92", "longitud" => "-56.12"],
    ["codigo" => "C4", "estado" => "disponible", "horas_hasta_90" => 60.0, "latitud" => "-34.93", "longitud" => "-56.13"],
    ["codigo" => "C5", "estado" => "disponible", "horas_hasta_90" => null, "latitud" => "-34.94", "longitud" => "-56.14"],
    ["codigo" => "C6", "estado" => "retirado", "horas_hasta_90" => 1.0, "latitud" => "-34.95", "longitud" => "-56.15"],
    ["codigo" => "C7", "estado" => "mantenimiento", "horas_hasta_90" => 1.0, "latitud" => "-34.96", "longitud" => "-56.16"],
    ["codigo" => "C8", "estado" => "disponible", "horas_hasta_90" => 2.0, "latitud" => null, "longitud" => null],
];
afirmar("A 24 h entran el lleno y el que se llena en 10 h; no entran los mas lentos, sin prediccion, retirados, en mantenimiento ni sin ubicacion", ["C1", "C2"],
    array_column(RutaSugerida::seleccionar($filas, 24)["elegidos"], "codigo"));
afirmar("A 48 h entra tambien el de 30 h (y sigue sin entrar el de 60 h)", ["C1", "C2", "C3"], array_column(RutaSugerida::seleccionar($filas, 48)["elegidos"], "codigo"));
afirmar("Los contenedores llenos van primero aunque esten despues en la entrada", "L", RutaSugerida::seleccionar([
    ["codigo" => "A", "estado" => "disponible", "horas_hasta_90" => 3.0, "latitud" => 1, "longitud" => 1],
    ["codigo" => "L", "estado" => "lleno", "horas_hasta_90" => 0.0, "latitud" => 1, "longitud" => 1],
], 24)["elegidos"][0]["codigo"]);

$muchos = [];
for ($i = 1; $i <= 30; $i++) {
    $muchos[] = ["codigo" => sprintf("M%02d", 31 - $i), "estado" => "lleno", "horas_hasta_90" => 0.0, "latitud" => -34.9 + $i / 1000, "longitud" => -56.1];
}
$sel = RutaSugerida::seleccionar($muchos, 24);
afirmar("Con 30 candidatos se respeta el tope de 25 y se avisa que quedaron 5 afuera", [25, 5], [count($sel["elegidos"]), $sel["descartados_por_tope"]]);
afirmar("Con empate de urgencia se desempata por codigo (los 25 primeros: M01 a M25)", ["M01", "M25"], [$sel["elegidos"][0]["codigo"], $sel["elegidos"][24]["codigo"]]);
afirmar("La propuesta completa no pasa de 25 paradas", 25, RutaSugerida::sugerir($muchos, 24)["cantidad"]);

titulo("39 ter. Ruta sugerida: el 2-opt y el determinismo");

// Puntos pseudoaleatorios fijos (generador congruencial): siempre los mismos.
$semilla = 12345;
$siguiente = function () use (&$semilla) {
    $semilla = ($semilla * 1103515245 + 12345) % 2147483648;
    return $semilla / 2147483648;
};
$permutaciones = function (array $v) use (&$permutaciones): array {
    if (count($v) <= 1) return [$v];
    $salida = [];
    foreach ($v as $i => $x) {
        $resto = $v;
        unset($resto[$i]);
        foreach ($permutaciones(array_values($resto)) as $q) $salida[] = array_merge([$x], $q);
    }
    return $salida;
};
$problemas = [];
$peorRazon = 1.0;
for ($caso = 0; $caso < 12; $caso++) {
    $pts = [];
    for ($i = 0; $i < 7; $i++) $pts[] = $p(-34.9 + $siguiente() * 0.1, -56.2 + $siguiente() * 0.1, "P$i");
    $dest = $caso % 2 === 0 ? ["latitud" => -34.88, "longitud" => -56.15] : null;
    $orden = RutaSugerida::ordenar($pts, $dest);
    $copia = $orden;
    sort($copia);
    if ($copia !== range(0, 6)) $problemas[] = "caso $caso: no es una permutacion";
    if (RutaSugerida::ordenar($pts, $dest) !== $orden) $problemas[] = "caso $caso: no es determinista";
    $optimo = PHP_FLOAT_MAX;
    foreach ($permutaciones(range(0, 6)) as $perm) $optimo = min($optimo, RutaSugerida::largoKm($pts, $perm, $dest));
    $largo = RutaSugerida::largoKm($pts, $orden, $dest);
    if ($largo < $optimo - 1e-9) $problemas[] = "caso $caso: mejor que el optimo (imposible)";
    $peorRazon = max($peorRazon, $largo / $optimo);
}
afirmar("En 12 casos de 7 puntos (con y sin destino): siempre una permutacion valida, determinista y nunca mejor que el optimo", [], $problemas);
afirmar("El 2-opt deja el recorrido a menos de 25 % del optimo por fuerza bruta (peor razon: " . round($peorRazon, 3) . ")", true, $peorRazon <= 1.25);

$cuadrado = [$p(0.00, 0.00, "A"), $p(0.02, 0.00, "B"), $p(0.00, 0.02, "C"), $p(0.02, 0.02, "D")];
afirmar("Un cuadrado de 2,22 km de lado: el recorrido abierto optimo son 3 lados (6,67 km), sin cruzarse", 6.67,
    round(RutaSugerida::largoKm($cuadrado, RutaSugerida::ordenar($cuadrado)), 2));

afirmar("Las constantes son las declaradas en pantalla: tope 25, 20 km/h, 5 min por parada, horizontes de 24 y 48 h", [25, 20.0, 5, [24, 48]],
    [RutaSugerida::TOPE, RutaSugerida::VELOCIDAD_KMH, RutaSugerida::MINUTOS_POR_PARADA, RutaSugerida::HORIZONTES]);
