<?php

// Parte de correr-tests.php (se incluye desde alli y usa sus funciones y sus sesiones).
// Plan de rutas para la defensa, bloque 2: las rutas para dibujarlas en el mapa (GET /rutas/mapa). Usa las rutas del seed de pruebas.

titulo("50. Rutas en el mapa: permisos y respuesta");

$obtenidos = [];
foreach (["sin sesion" => null, "vecino" => $vecino, "operario" => $operario, "cuadrilla" => $cuadrilla] as $quien => $cookies) {
    [$obtenidos[$quien]] = peticion("GET", "/api-recoleccion/rutas/mapa", null, $cookies);
}
afirmar("Las rutas del mapa son solo del Administrador: sin sesion 401; vecino, operario y cuadrilla 403", ["sin sesion" => 401, "vecino" => 403, "operario" => 403, "cuadrilla" => 403], $obtenidos);

[$codigo, $cuerpo] = peticion("GET", "/api-recoleccion/rutas/mapa", null, $admin);
$todas = $cuerpo["datos"] ?? [];
$activasEnBase = (int) $pdo->query("SELECT COUNT(*) FROM ruta WHERE estado = 'activa'")->fetchColumn();
afirmar("El administrador recibe todas las rutas activas (y solo ellas)", [200, $activasEnBase, 0], [$codigo, count($todas), count(array_filter($todas, fn($r) => !isset($r["id_ruta"])))]);

$idZona = (int) $pdo->query("SELECT id_zona FROM ruta WHERE estado = 'activa' ORDER BY id_ruta LIMIT 1")->fetchColumn();
[$codigo, $cuerpo] = peticion("GET", "/api-recoleccion/rutas/mapa?id_zona=$idZona", null, $admin);
$deZona = $cuerpo["datos"] ?? [];
$esperadas = (int) $pdo->query("SELECT COUNT(*) FROM ruta WHERE estado = 'activa' AND id_zona = $idZona")->fetchColumn();
afirmar("Con id_zona trae solo las de esa zona", [200, $esperadas, true], [$codigo, count($deZona), count(array_filter($deZona, fn($r) => $r["id_zona"] !== $idZona)) === 0]);

$primera = $deZona[0] ?? [];
afirmar("Cada ruta trae lo que hace falta para dibujarla: nombre, dia, hora, cantidad, y el trazado o las paradas para la linea recta", [true, true, true], [
    isset($primera["nombre"], $primera["dia_semana"], $primera["hora_inicio"], $primera["cantidad"], $primera["zona"]),
    array_key_exists("geojson", $primera) && array_key_exists("paradas", $primera),
    ($primera["geojson"] ?? null) !== null || is_array($primera["paradas"] ?? null),
]);
$sinTrazar = current(array_filter($todas, fn($r) => $r["geojson"] === null && ($r["cantidad"] ?? 0) > 1)) ?: [];
afirmar("Una ruta sin trazado por calles trae las coordenadas de sus paradas para unirlas en linea recta", [true, true], [
    $sinTrazar === [] || is_array($sinTrazar["paradas"]), $sinTrazar === [] || count($sinTrazar["paradas"]) === $sinTrazar["cantidad"],
]);

foreach (["abc", "0", "-3", "1.5"] as $malo) {
    [$codigo] = peticion("GET", "/api-recoleccion/rutas/mapa?id_zona=$malo", null, $admin);
    afirmar("id_zona invalido ($malo): 400", 400, $codigo);
}
[$codigo] = peticion("POST", "/api-recoleccion/rutas/mapa", [], $admin);
afirmar("Solo se lee: POST a /rutas/mapa no esta permitido (405)", 405, $codigo);
