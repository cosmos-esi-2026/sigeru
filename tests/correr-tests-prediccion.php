<?php

// Parte de correr-tests.php (se incluye desde alli y usa sus funciones y sus sesiones).
// Prediccion de llenado (extras, parte 16). Un contenedor propio TPRED con 6 vaciados de fechas controladas (relativas a ahora) en recolecciones
// propias marcadas TPRED; al final se borra todo (los vaciados se van en cascada).

titulo("35. Prediccion de llenado: permisos y formato");

$pdo->exec("DELETE FROM recoleccion WHERE observaciones = 'TPRED'");
$pdo->exec("DELETE FROM contenedor WHERE codigo LIKE 'TPRED-%'");

$obtenidos = [];
foreach (["sin sesion" => null, "vecino" => $vecino, "operario" => $operario] as $quien => $cookies) {
    [$obtenidos[$quien]] = peticion("GET", "/api-gestion/contenedores/prediccion", null, $cookies);
}
afirmar("La prediccion es de Administrador y Cuadrilla: sin sesion 401, vecino y operario 403", ["sin sesion" => 401, "vecino" => 403, "operario" => 403], $obtenidos);
[$codigo] = peticion("POST", "/api-gestion/contenedores/prediccion", ["x" => 1], $admin);
afirmar("Solo se lee (POST): 403", 403, $codigo);
foreach (["0", "abc", "721", "-1", "1.5"] as $malo) {
    [$codigo] = peticion("GET", "/api-gestion/contenedores/prediccion?dentro_de=$malo", null, $admin);
    afirmar("dentro_de invalido ($malo): 400", 400, $codigo);
}
[$codigo, $cuerpo] = peticion("GET", "/api-gestion/contenedores/prediccion", null, $cuadrilla);
$filas = $cuerpo["datos"] ?? [];
$porCodigo = array_column($filas, null, "codigo");
afirmar("La cuadrilla tambien la ve: 200, con el metodo explicado y sin los retirados", [200, true, false], [$codigo, str_contains($cuerpo["metodo"] ?? "", "no es inteligencia artificial"), isset($porCodigo["CNT-010"])]);
afirmar("Cada fila trae su prediccion con estado, horas, nivel, intervalo, vaciados, confianza y texto", true, count(array_filter($filas, fn($f) => isset($f["prediccion"]["estado"]) && array_key_exists("horas_hasta_90", $f["prediccion"])
    && array_key_exists("confianza", $f["prediccion"]) && isset($f["prediccion"]["texto"]))) === count($filas));
afirmar("Un contenedor lleno del seed figura como lleno", ["lleno", 0], [$porCodigo["CNT-002"]["prediccion"]["estado"] ?? null, (int) ($porCodigo["CNT-002"]["prediccion"]["horas_hasta_90"] ?? -1)]);
afirmar("Uno con un solo vaciado (seed) figura sin datos suficientes, no con un numero inventado", ["sin_datos", null, "Sin datos suficientes" === "Sin datos suficientes"], [
    $porCodigo["CNT-001"]["prediccion"]["estado"] ?? null, array_key_exists("horas_hasta_90", $porCodigo["CNT-001"]["prediccion"] ?? []) ? $porCodigo["CNT-001"]["prediccion"]["horas_hasta_90"] : "falta", true]);
$orden = array_values(array_filter(array_map(fn($f) => $f["prediccion"]["horas_hasta_90"], $filas), fn($h) => $h !== null));
$ordenado = $orden;
sort($ordenado);
afirmar("La lista viene ordenada: primero los que se llenan antes", $ordenado, $orden);


titulo("35 bis. Prediccion de llenado: un caso con la cuenta hecha a mano");

[, $cuerpo] = peticion("POST", "/api-gestion/contenedores", ["codigo" => "TPRED-A$sufijo", "direccion" => "Calle de prueba de prediccion 1", "capacidad" => 800, "tipo_residuo" => "Mixto", "id_zona" => 2], $admin);
$idContP = (int) ($cuerpo["datos"]["id_contenedor"] ?? 0);
foreach ([246, 198, 150, 102, 54, 6] as $horasAtras) {
    $pdo->prepare("INSERT INTO recoleccion (fecha_hora_inicio, fecha_hora_fin, estado, observaciones, id_ruta, id_camion, id_cuadrilla, id_centro)
                   VALUES (DATE_SUB(NOW(), INTERVAL ? HOUR), DATE_SUB(NOW(), INTERVAL ? HOUR), 'finalizada', 'TPRED', 1, 1, 1, 1)")->execute([$horasAtras + 1, $horasAtras]);
    $idRecP = (int) $pdo->lastInsertId();
    $pdo->prepare("INSERT INTO vaciado (id_recoleccion, id_contenedor, fecha_hora, peso_kg, volumen_l) VALUES (?, ?, DATE_SUB(NOW(), INTERVAL ? HOUR), 100, 600)")->execute([$idRecP, $idContP, $horasAtras]);
}
[, $cuerpo] = peticion("GET", "/api-gestion/contenedores/prediccion", null, $admin);
$p = array_column($cuerpo["datos"] ?? [], null, "codigo")["TPRED-A$sufijo"]["prediccion"] ?? [];
afirmar("6 vaciados cada 48 h, el ultimo hace 6 h: estimada, 48 h de intervalo, 6 vaciados y confianza media", ["estimada", 48.0, 6, "media"], [
    $p["estado"] ?? null, (float) ($p["intervalo_medio_horas"] ?? 0), $p["vaciados"] ?? null, $p["confianza"] ?? null]);
afirmar("Llega al 90 % en 0,9 x 48 - 6 = 37,2 horas (entre 12 y 13 % hoy: 6 h de 48 h son 12,5 %, y el redondeo depende de los minutos que pasaron desde que se armo el caso)", [true, true], [abs((float) ($p["horas_hasta_90"] ?? 0) - 37.2) < 0.2, in_array($p["nivel_estimado_pct"] ?? null, [12, 13], true)]);
afirmar("El texto para la pantalla dice dia y horas", true, str_contains($p["texto"] ?? "", "en 1 día"));
[, $cuerpo] = peticion("GET", "/api-gestion/contenedores/prediccion?dentro_de=48", null, $admin);
afirmar("Con dentro_de=48 figura (se llena en 37 h)", true, isset(array_column($cuerpo["datos"] ?? [], null, "codigo")["TPRED-A$sufijo"]));
afirmar("Y en esa lista todos se llenan dentro de las 48 h", true, count(array_filter($cuerpo["datos"] ?? [], fn($f) => ($f["prediccion"]["horas_hasta_90"] ?? 999) > 48)) === 0);
[, $cuerpo] = peticion("GET", "/api-gestion/contenedores/prediccion?dentro_de=24", null, $admin);
afirmar("Con dentro_de=24 ya no figura", false, isset(array_column($cuerpo["datos"] ?? [], null, "codigo")["TPRED-A$sufijo"]));
peticion("PUT", "/api-gestion/contenedores/$idContP", ["estado" => "lleno"], $admin);
[, $cuerpo] = peticion("GET", "/api-gestion/contenedores/prediccion", null, $admin);
afirmar("Si el contenedor pasa a lleno, la prediccion dice lleno (0 horas)", ["lleno", 0], [array_column($cuerpo["datos"] ?? [], null, "codigo")["TPRED-A$sufijo"]["prediccion"]["estado"] ?? null, (int) (array_column($cuerpo["datos"] ?? [], null, "codigo")["TPRED-A$sufijo"]["prediccion"]["horas_hasta_90"] ?? -1)]);
peticion("PUT", "/api-gestion/contenedores/$idContP", ["estado" => "retirado"], $admin);
[, $cuerpo] = peticion("GET", "/api-gestion/contenedores/prediccion", null, $admin);
afirmar("Si se retira, deja de aparecer", false, isset(array_column($cuerpo["datos"] ?? [], null, "codigo")["TPRED-A$sufijo"]));

$pdo->exec("DELETE FROM recoleccion WHERE observaciones = 'TPRED'");
$pdo->exec("DELETE FROM contenedor WHERE codigo LIKE 'TPRED-%'");
afirmar("Los datos de prueba de la prediccion se borraron (los vaciados se fueron en cascada)", [0, 0], [
    (int) $pdo->query("SELECT COUNT(*) FROM contenedor WHERE codigo LIKE 'TPRED-%'")->fetchColumn(),
    (int) $pdo->query("SELECT COUNT(*) FROM recoleccion WHERE observaciones = 'TPRED'")->fetchColumn(),
]);
