<?php

// Impacto ambiental (extras, parte 18): el calculo con numeros hechos a mano y los factores contra su fuente (EPA WARM v16).

require_once __DIR__ . "/../api-gestion/models/Impacto.php";

titulo("36. Impacto ambiental: calculo a mano");

$fila = fn($tipo, $reciclable, $kg, $factor, $mes = null) => ["tipo" => $tipo, "reciclable" => $reciclable, "kg" => $kg, "factor" => $factor] + ($mes === null ? [] : ["mes" => $mes]);
$r = Impacto::calcular([
    $fila("Vidrio", true, 200, 0.331), $fila("Papel", true, 100, 3.99), $fila("Plastico", true, 50, null), $fila("Mixto", false, 650, null),
]);
afirmar("Total 1000 kg, 350 reciclables (35 %), CO2 = 200 x 0,331 + 100 x 3,99 = 465,2 kg y 50 kg reciclables sin factor", [1000.0, 350.0, 35.0, 465.2, 50.0], [
    $r["kg_total"], $r["kg_reciclable"], $r["pct_reciclable"], $r["co2_evitado_kg"], $r["kg_sin_factor"]]);
afirmar("Por tipo: el vidrio suma 66,2 kg de CO2, el papel 399 y el plastico (sin factor) y el mixto no suman nada", [66.2, 399.0, null, null], array_column($r["por_tipo"], "co2_evitado_kg"));
$vacio = Impacto::calcular([]);
afirmar("Sin datos: 0 kg, sin porcentaje (no se divide por cero) y 0 de CO2", [0.0, null, 0.0], [$vacio["kg_total"], $vacio["pct_reciclable"], $vacio["co2_evitado_kg"]]);
afirmar("Un tipo con 0 kg no rompe el calculo", [0.0, 0.0], (function () use ($fila) { $c = Impacto::calcular([$fila("Vidrio", true, 0, 0.331)]); return [$c["kg_total"], $c["co2_evitado_kg"]]; })());
afirmar("Un tipo que no es reciclable no suma CO2 aunque tuviera factor", [null, 0.0], (function () use ($fila) { $c = Impacto::calcular([$fila("Organico", false, 100, 1.0)]); return [$c["por_tipo"][0]["co2_evitado_kg"], $c["co2_evitado_kg"]]; })());
afirmar("Todo reciclable da 100 %", 100.0, Impacto::calcular([$fila("Vidrio", true, 10, 0.331)])["pct_reciclable"]);

$meses = Impacto::porMes([
    $fila("Papel", true, 10, 3.99, "2026-10"), $fila("Vidrio", true, 100, 0.331, "2026-09"), $fila("Mixto", false, 100, null, "2026-09"), $fila("Vidrio", true, 20, 0.331, "2026-10"),
]);
afirmar("Por mes: de septiembre a octubre, cada uno con su propio calculo", [["2026-09", 200.0, 50.0, 33.1], ["2026-10", 30.0, 100.0, 46.5]], array_map(fn($m) => [$m["mes"], $m["kg_total"], $m["pct_reciclable"], $m["co2_evitado_kg"]], $meses));
afirmar("Sin filas no hay meses", [], Impacto::porMes([]));

$fuentes = Impacto::fuentes([
    ["tipo" => "Vidrio", "factor" => 0.331, "fuente" => "Fuente A", "url" => "https://a.example/x"], ["tipo" => "Papel", "factor" => 3.99, "fuente" => "Fuente B", "url" => null],
    ["tipo" => "Mixto", "factor" => null, "fuente" => null, "url" => null], ["tipo" => "Organico", "factor" => null, "fuente" => "Fuente C", "url" => "https://c.example"],
]);
afirmar("Las fuentes citadas son solo las de los tipos con factor", [["Fuente A", ["Vidrio"]], ["Fuente B", ["Papel"]]], array_map(fn($f) => [$f["fuente"], $f["tipos"]], $fuentes));

titulo("36 bis. Impacto ambiental: los factores salen de la fuente");

// EPA WARM v16 (dic. 2023): neto de reciclar y neto de enterrar en MTCO2E por tonelada corta; 1 tonelada corta = 907,18474 kg.
$aKg = fn(float $reciclar, float $enterrar) => round(($enterrar - $reciclar) * 1000 / 907.18474, 3);
foreach ([["Plastico (Mixed Plastics, Exhibit 5-3)", -0.93, 0.02, 1.047], ["Vidrio (Glass, Exhibit 1-4)", -0.28, 0.02, 0.331],
          ["Papel (Mixed Paper general, Exhibit 3-7)", -3.55, 0.07, 3.990], ["Metal (Mixed Metals, Exhibit 2-7)", -4.39, 0.02, 4.861]] as [$nombre, $reciclar, $enterrar, $guardado]) {
    afirmar("Factor de $nombre: ($enterrar - ($reciclar)) t/tc = $guardado kg de CO2e por kg", $guardado, $aKg($reciclar, $enterrar));
}
$migracion = (string) file_get_contents(__DIR__ . "/../database/migraciones/015-factor-ambiental.sql");
$seed = (string) file_get_contents(__DIR__ . "/../database/datos_prueba.sql");
afirmar("La migracion y el seed llevan los mismos cuatro factores, con su fuente y su enlace", [4, 4, 4, 4], [
    substr_count($migracion, "EPA WARM v16"), substr_count($seed, "EPA WARM v16 (dic. 2023), Exhibit"), substr_count($migracion, "warm_containers_packaging_and_non-durable_goods_materials_v16_dec.pdf"),
    substr_count($seed, "warm_containers_packaging_and_non-durable_goods_materials_v16_dec.pdf")]);
afirmar("Organico y Mixto no tienen factor en el seed (sin fuente verificada, solo kg)", [false, false], [str_contains($seed, "WHERE nombre = 'Organico'"), str_contains($seed, "WHERE nombre = 'Mixto'")]);
afirmar("core/LimitePeticion.php es identico en api-usuarios y api-gestion", true,
    file_get_contents(__DIR__ . "/../api-usuarios/core/LimitePeticion.php") === file_get_contents(__DIR__ . "/../api-gestion/core/LimitePeticion.php"));
