<?php

// "Cuando pasa el camion por mi zona" (extras, parte 15): el calculo del proximo pasaje.

require_once __DIR__ . "/../api-recoleccion/models/ProximoPasaje.php";

titulo("32. Proximo pasaje: calculo para el vecino");

$ahora = "2026-10-19 12:00:00";
$r = fn(string $estado, string $fecha) => ["estado" => $estado, "fecha_hora_inicio" => $fecha];

afirmar("Varias planificadas: el proximo es el mas cercano, aunque lleguen desordenadas", ["programado", "2026-10-20 06:30:00", null], (function () use ($r, $ahora) {
    $c = ProximoPasaje::calcular([$r("planificada", "2026-10-25 06:30:00"), $r("planificada", "2026-10-20 06:30:00"), $r("planificada", "2026-10-22 14:00:00")], $ahora);
    return [$c["estado"], $c["proximo"], $c["cancelada"]];
})());
afirmar("Sin recolecciones (una zona sin rutas): no hay planificadas y no se inventa nada", ["sin_planificadas", null, null], (function () use ($ahora) {
    $c = ProximoPasaje::calcular([], $ahora);
    return [$c["estado"], $c["proximo"], $c["cancelada"]];
})());
afirmar("Una planificada que ya paso (nadie la cerro) no es un proximo pasaje", ["sin_planificadas", null], (function () use ($r, $ahora) {
    $c = ProximoPasaje::calcular([$r("planificada", "2026-09-11 06:15:00")], $ahora);
    return [$c["estado"], $c["proximo"]];
})());
afirmar("Justo a la hora de ahora todavia cuenta", ["programado", "2026-10-19 12:00:00"], (function () use ($r, $ahora) {
    $c = ProximoPasaje::calcular([$r("planificada", "2026-10-19 12:00:00")], $ahora);
    return [$c["estado"], $c["proximo"]];
})());
afirmar("Si cancelaron la proxima, se muestra la siguiente y se avisa de la cancelada", ["programado", "2026-10-26 06:30:00", "2026-10-20 06:30:00"], (function () use ($r, $ahora) {
    $c = ProximoPasaje::calcular([$r("cancelada", "2026-10-20 06:30:00"), $r("planificada", "2026-10-26 06:30:00")], $ahora);
    return [$c["estado"], $c["proximo"], $c["cancelada"]];
})());
afirmar("Una cancelada posterior al proximo pasaje no se menciona", ["2026-10-20 06:30:00", null], (function () use ($r, $ahora) {
    $c = ProximoPasaje::calcular([$r("planificada", "2026-10-20 06:30:00"), $r("cancelada", "2026-10-27 06:30:00")], $ahora);
    return [$c["proximo"], $c["cancelada"]];
})());
afirmar("Una cancelada vieja (de antes de ahora) no se menciona", [null], (function () use ($r, $ahora) {
    return [ProximoPasaje::calcular([$r("cancelada", "2026-09-04 22:00:00"), $r("planificada", "2026-10-20 06:30:00")], $ahora)["cancelada"]];
})());
afirmar("Si cancelaron todas: sin planificadas, y se avisa la primera cancelada", ["sin_planificadas", null, "2026-10-20 06:30:00"], (function () use ($r, $ahora) {
    $c = ProximoPasaje::calcular([$r("cancelada", "2026-10-27 06:30:00"), $r("cancelada", "2026-10-20 06:30:00")], $ahora);
    return [$c["estado"], $c["proximo"], $c["cancelada"]];
})());
afirmar("Con una recoleccion en curso, el camion esta pasando hoy (y se informa la siguiente)", ["en_curso", "2026-10-26 06:30:00"], (function () use ($r, $ahora) {
    $c = ProximoPasaje::calcular([$r("en_curso", "2026-10-19 06:00:00"), $r("planificada", "2026-10-26 06:30:00")], $ahora);
    return [$c["estado"], $c["proximo"]];
})());
afirmar("El resultado trae solo estado, proximo y cancelada (nada interno)", [["estado", "proximo", "cancelada"]], [array_keys(ProximoPasaje::calcular([$r("planificada", "2026-10-26 06:30:00")], $ahora))]);
afirmar("Las finalizadas no se tienen en cuenta aunque lleguen por error", ["sin_planificadas", null], (function () use ($r, $ahora) {
    $c = ProximoPasaje::calcular([$r("finalizada", "2026-10-30 06:30:00")], $ahora);
    return [$c["estado"], $c["proximo"]];
})());
