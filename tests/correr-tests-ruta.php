<?php

// Parte de correr-tests.php (se incluye desde alli y usa sus funciones y sus sesiones).
// Ruta sugerida (extras, parte 17). Una zona propia TRUT con cuatro contenedores llenos sobre un meridiano; se borra todo al final.

titulo("40. Ruta sugerida: permisos y validaciones");

$pdo->exec("DELETE FROM contenedor WHERE codigo LIKE 'TRUT-%'");
$pdo->exec("DELETE FROM zona WHERE nombre LIKE 'TRUT Zona%'");

$obtenidos = [];
foreach (["sin sesion" => null, "vecino" => $vecino, "operario" => $operario, "cuadrilla" => $cuadrilla] as $quien => $cookies) {
    [$obtenidos[$quien]] = peticion("GET", "/api-gestion/contenedores/ruta-sugerida", null, $cookies);
}
afirmar("La ruta sugerida es solo del Administrador: sin sesion 401; vecino, operario y cuadrilla 403", ["sin sesion" => 401, "vecino" => 403, "operario" => 403, "cuadrilla" => 403], $obtenidos);
[$codigo] = peticion("POST", "/api-gestion/contenedores/ruta-sugerida", ["x" => 1], $admin);
afirmar("Solo se lee (POST): 403", 403, $codigo);
foreach (["0", "12", "72", "abc", "-24", "24.5"] as $malo) {
    [$codigo] = peticion("GET", "/api-gestion/contenedores/ruta-sugerida?horizonte=$malo", null, $admin);
    afirmar("horizonte invalido ($malo): 400", 400, $codigo);
}
[$codigo] = peticion("GET", "/api-gestion/contenedores/ruta-sugerida?id_zona=99999", null, $admin);
afirmar("Zona inexistente: 400", 400, $codigo);
[$codigo] = peticion("GET", "/api-gestion/contenedores/ruta-sugerida?id_zona=abc", null, $admin);
afirmar("Zona que no es un numero: 400", 400, $codigo);
[$codigo] = peticion("GET", "/api-gestion/contenedores/ruta-sugerida?id_centro=99999", null, $admin);
afirmar("Centro de destino inexistente: 400", 400, $codigo);

titulo("40 bis. Ruta sugerida: un caso con la cuenta hecha a mano");

$pdo->prepare("INSERT INTO zona (nombre, descripcion) VALUES (?, 'zona de prueba de la ruta sugerida')")->execute(["TRUT Zona $sufijo"]);
$idZonaR = (int) $pdo->lastInsertId();
// Cuatro contenedores llenos a 0,01 grado (1,112 km) uno del otro, insertados en desorden.
foreach ([["C", -34.82], ["A", -34.80], ["D", -34.83], ["B", -34.81]] as [$letra, $lat]) {
    $pdo->prepare("INSERT INTO contenedor (codigo, direccion, latitud, longitud, capacidad, estado, id_zona, id_tipo_residuo)
                   VALUES (?, ?, ?, -56.2000000, 800, 'lleno', ?, 1)")->execute(["TRUT-$letra$sufijo", "Calle de prueba $letra", $lat, $idZonaR]);
}
$cuentaRec = (int) $pdo->query("SELECT COUNT(*) FROM recoleccion")->fetchColumn();

[$codigo, $cuerpo] = peticion("GET", "/api-gestion/contenedores/ruta-sugerida?id_zona=$idZonaR&horizonte=24", null, $admin);
$d = $cuerpo["datos"] ?? [];
afirmar("El administrador la pide: 200 y cuatro paradas, solo de esa zona", [200, 4], [$codigo, $d["cantidad"] ?? null]);
afirmar("Sin centro se empieza por el mas al norte y se baja: A, B, C, D", ["TRUT-A$sufijo", "TRUT-B$sufijo", "TRUT-C$sufijo", "TRUT-D$sufijo"], array_column($d["paradas"] ?? [], "codigo"));
afirmar("Largo a mano: 3 tramos de 1,1119508 km = 3,34 km; a 20 km/h son 10 min mas 4 paradas x 5 min = 30 min", [3.34, 30, null, 0], [$d["distancia_km"] ?? null, $d["tiempo_min"] ?? null, array_key_exists("destino", $d) ? $d["destino"] : "falta", $d["descartados_por_tope"] ?? null]);
afirmar("Cada parada trae orden, km desde la anterior y cuando se llena (lleno)", [[1, 0.0, "Ya está lleno"], [2, 1.11, "Ya está lleno"]], array_map(fn($x) => [$x["orden"], (float) $x["km_desde_anterior"], $x["se_llena"]], array_slice($d["paradas"] ?? [], 0, 2)));
afirmar("Dice sus supuestos: linea recta, velocidad fija, tope y que es una propuesta", [true, true, true, true], [
    str_contains($d["supuestos"] ?? "", "línea recta"), str_contains($d["supuestos"] ?? "", "20 km/h"), str_contains($d["supuestos"] ?? "", "25"), str_contains($d["supuestos"] ?? "", "propuesta"),
]);
afirmar("La respuesta no trae datos de personas", false, str_contains((string) json_encode($d), "@") || str_contains((string) json_encode($d), "id_usuario"));

[, $cuerpo] = peticion("GET", "/api-gestion/centros/1", null, $admin);
$centro = $cuerpo["datos"] ?? [];
[$codigo, $cuerpo] = peticion("GET", "/api-gestion/contenedores/ruta-sugerida?id_zona=$idZonaR&horizonte=48&id_centro=1", null, $admin);
$d = $cuerpo["datos"] ?? [];
$suma = array_sum(array_map(fn($x) => (float) $x["km_desde_anterior"], $d["paradas"] ?? [])) + (float) ($d["km_al_destino"] ?? 0);
afirmar("Con centro de destino: 200, el destino viene en la respuesta y el recorrido termina ahi", [200, (int) ($centro["id_centro"] ?? 0), true], [$codigo, $d["destino"]["id_centro"] ?? null, ($d["km_al_destino"] ?? -1) > 0]);
afirmar("La distancia total es la suma de los tramos (con un margen de redondeo de 0,03 km)", true, abs($suma - (float) ($d["distancia_km"] ?? 0)) < 0.03);

$pdo->prepare("UPDATE contenedor SET estado = 'retirado' WHERE codigo = ?")->execute(["TRUT-D$sufijo"]);
$pdo->prepare("UPDATE contenedor SET estado = 'mantenimiento' WHERE codigo = ?")->execute(["TRUT-C$sufijo"]);
$pdo->prepare("UPDATE contenedor SET estado = 'disponible' WHERE codigo = ?")->execute(["TRUT-B$sufijo"]);
[, $cuerpo] = peticion("GET", "/api-gestion/contenedores/ruta-sugerida?id_zona=$idZonaR&horizonte=24", null, $admin);
afirmar("Un retirado y uno en mantenimiento no entran; uno disponible sin historial de vaciados tampoco (no se inventa una prediccion): queda solo el lleno", ["TRUT-A$sufijo"], array_column($cuerpo["datos"]["paradas"] ?? [], "codigo"));

$pdo->exec("UPDATE contenedor SET estado = 'disponible' WHERE codigo LIKE 'TRUT-%'");
[, $cuerpo] = peticion("GET", "/api-gestion/contenedores/ruta-sugerida?id_zona=$idZonaR&horizonte=24", null, $admin);
afirmar("Sin ningun contenedor que lo necesite, la propuesta viene vacia (0 paradas, 0 km)", [0, 0.0, []], [$cuerpo["datos"]["cantidad"] ?? null, (float) ($cuerpo["datos"]["distancia_km"] ?? -1), $cuerpo["datos"]["paradas"] ?? null]);

afirmar("Pedir la ruta no crea ninguna recoleccion", $cuentaRec, (int) $pdo->query("SELECT COUNT(*) FROM recoleccion")->fetchColumn());

$pdo->exec("DELETE FROM contenedor WHERE codigo LIKE 'TRUT-%'");
$pdo->exec("DELETE FROM zona WHERE nombre LIKE 'TRUT Zona%'");
afirmar("Los contenedores y la zona de prueba se borraron", [0, 0], [
    (int) $pdo->query("SELECT COUNT(*) FROM contenedor WHERE codigo LIKE 'TRUT-%'")->fetchColumn(),
    (int) $pdo->query("SELECT COUNT(*) FROM zona WHERE nombre LIKE 'TRUT Zona%'")->fetchColumn(),
]);
