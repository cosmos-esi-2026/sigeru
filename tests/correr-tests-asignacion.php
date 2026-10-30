<?php

// Parte de correr-tests.php (se incluye desde alli y usa sus funciones y sus sesiones, entre ellas $cuadrilla = Cuadrilla Norte y $otraCuadrilla).
// Plan de rutas para la defensa, bloque 6: la asignacion de rutas a cuadrillas, la sugerencia, «Mis rutas» y lo que ve la cuadrilla. Todo con una zona propia TASG; se borra al final.

titulo("52. Asignacion de rutas: permisos y validaciones");

$limpiarTasg = function () use ($pdo): void {
    $pdo->exec("DELETE FROM recoleccion WHERE id_ruta IN (SELECT id_ruta FROM ruta WHERE nombre LIKE 'TASG Ruta%') OR id_camion IN (SELECT id_camion FROM camion WHERE matricula LIKE 'TASG-%')");
    $pdo->exec("DELETE FROM incidencia WHERE id_contenedor IN (SELECT id_contenedor FROM contenedor WHERE codigo LIKE 'TASG-%')");
    $pdo->exec("DELETE FROM ruta WHERE nombre LIKE 'TASG Ruta%'");
    $pdo->exec("DELETE FROM contenedor WHERE codigo LIKE 'TASG-%'");
    $pdo->exec("DELETE FROM camion WHERE matricula LIKE 'TASG-%'");
    $pdo->exec("DELETE FROM cuadrilla WHERE nombre LIKE 'TASG Cuadrilla%'");
    $pdo->exec("DELETE FROM zona WHERE nombre LIKE 'TASG Zona%'");
};
$limpiarTasg();

$dia = "2030-01-07";   // un lunes, lejos de los datos del seed
$pdo->prepare("INSERT INTO zona (nombre, descripcion) VALUES (?, 'zona de prueba de la asignacion')")->execute(["TASG Zona $sufijo"]);
$idZonaA = (int) $pdo->lastInsertId();
$nuevoCont = $pdo->prepare("INSERT INTO contenedor (codigo, direccion, latitud, longitud, capacidad, estado, id_zona, id_tipo_residuo) VALUES (?, ?, ?, -56.2, 1000, ?, ?, 1)");
$ca = [];
foreach ([1 => "lleno", 2 => "disponible", 3 => "disponible", 4 => "disponible", 5 => "disponible", 6 => "disponible"] as $i => $estado) {
    $nuevoCont->execute(["TASG-$sufijo-$i", "Calle de asignacion $i", -34.9 - $i * 0.001, $estado, $idZonaA]);
    $ca[$i] = (int) $pdo->lastInsertId();
}
$crearRuta = function (string $nombre, string $diaSemana, string $hora) use ($admin, $idZonaA, $sufijo): int {
    [, $cuerpo] = peticion("POST", "/api-recoleccion/rutas", ["nombre" => "TASG Ruta $nombre $sufijo", "dia_semana" => $diaSemana, "hora_inicio" => $hora, "id_zona" => $idZonaA], $admin);
    return (int) ($cuerpo["datos"]["id_ruta"] ?? 0);
};
$idR1 = $crearRuta("1", "lunes", "06:00");
$idR2 = $crearRuta("2", "lunes", "07:30");
$idR3 = $crearRuta("3", "jueves", "06:00");
peticion("PUT", "/api-recoleccion/rutas/$idR1/contenedores", ["contenedores" => [$ca[1], $ca[2], $ca[3]]], $admin);
peticion("PUT", "/api-recoleccion/rutas/$idR2/contenedores", ["contenedores" => [$ca[4], $ca[5]]], $admin);
peticion("PUT", "/api-recoleccion/rutas/$idR3/contenedores", ["contenedores" => [$ca[6]]], $admin);
$idCam1 = (int) (peticion("POST", "/api-recoleccion/camiones", ["matricula" => "TASG-$sufijo-1", "capacidad" => 4000], $admin)[1]["datos"]["id_camion"] ?? 0);
$idCam2 = (int) (peticion("POST", "/api-recoleccion/camiones", ["matricula" => "TASG-$sufijo-2", "capacidad" => 4000], $admin)[1]["datos"]["id_camion"] ?? 0);
afirmar("Se armaron tres rutas (lunes, lunes y jueves) y dos camiones de prueba", true, $idR1 > 0 && $idR2 > 0 && $idR3 > 0 && $idCam1 > 0 && $idCam2 > 0);

$obtenidos = [];
foreach (["sin sesion" => null, "vecino" => $vecino, "operario" => $operario, "cuadrilla" => $cuadrilla] as $quien => $cookies) {
    $fila = [];
    [$fila[]] = peticion("GET", "/api-recoleccion/asignacion?fecha=$dia", null, $cookies);
    [$fila[]] = peticion("POST", "/api-recoleccion/recolecciones/lote", ["asignaciones" => []], $cookies);
    $obtenidos[$quien] = $fila;
}
afirmar("La asignacion y el lote son solo del Administrador: sin sesion 401; vecino, operario y cuadrilla 403",
    ["sin sesion" => [401, 401], "vecino" => [403, 403], "operario" => [403, 403], "cuadrilla" => [403, 403]], $obtenidos);
$mias = [];
foreach (["sin sesion" => null, "vecino" => $vecino, "operario" => $operario, "administrador" => $admin, "cuadrilla" => $cuadrilla] as $quien => $cookies) {
    [$mias[$quien]] = peticion("GET", "/api-recoleccion/mis-rutas", null, $cookies);
}
afirmar("«Mis rutas» es solo de la Cuadrilla: sin sesion 401; vecino, operario y administrador 403; la cuadrilla 200", ["sin sesion" => 401, "vecino" => 403, "operario" => 403, "administrador" => 403, "cuadrilla" => 200], $mias);

foreach (["", "abc", "2030-13-01", "2030-02-30", "07/01/2030"] as $malo) {
    [$codigo] = peticion("GET", "/api-recoleccion/asignacion?fecha=" . urlencode($malo), null, $admin);
    afirmar("Fecha invalida («{$malo}»): 400", 400, $codigo);
}
[$codigo] = peticion("GET", "/api-recoleccion/asignacion", null, $admin);
afirmar("Sin fecha: 400", 400, $codigo);
[$codigo] = peticion("POST", "/api-recoleccion/asignacion", [], $admin);
afirmar("Solo se lee: ni el administrador puede hacer POST a /asignacion (403, no esta en la matriz de permisos)", 403, $codigo);
foreach ([["x" => 1], ["asignaciones" => []], ["asignaciones" => "r1"], ["asignaciones" => [["id_ruta" => $idR1]]]] as $malo) {
    [$codigo] = peticion("POST", "/api-recoleccion/recolecciones/lote", $malo, $admin);
    afirmar("Un lote mal armado: 400", 400, $codigo);
}
[$codigo] = peticion("GET", "/api-recoleccion/recolecciones/lote", null, $admin);
afirmar("El lote solo se crea (GET a /recolecciones/lote): 405", 405, $codigo);

titulo("52 bis. Asignacion de rutas: el dia, la prioridad y la sugerencia");

[$codigo, $cuerpo] = peticion("GET", "/api-recoleccion/asignacion?fecha=$dia", null, $admin);
$d = $cuerpo["datos"] ?? [];
$rutasTasg = array_values(array_filter($d["rutas"] ?? [], fn($r) => str_starts_with($r["nombre"], "TASG Ruta")));
afirmar("El lunes aparecen las dos rutas del lunes (y no la del jueves), cada una con su prioridad", [200, "lunes", [$idR1, $idR2], ["media", "normal"]], [
    $codigo, $d["dia"] ?? null, array_column($rutasTasg, "id_ruta"), array_column(array_column($rutasTasg, "prioridad"), "nivel"),
]);
$r1 = $rutasTasg[0] ?? [];
afirmar("La ruta con un contenedor lleno cuenta 50 puntos y 1 lleno, y no tiene cuadrilla", [50, 1, 0, null, 3, true], [
    $r1["prioridad"]["puntaje"] ?? null, $r1["prioridad"]["llenos"] ?? null, $r1["prioridad"]["urgentes"] ?? null, array_key_exists("asignada", $r1) ? $r1["asignada"] : "falta", $r1["cantidad"] ?? null, $r1["es_del_dia"] ?? null,
]);
[, $cuerpo] = peticion("GET", "/api-recoleccion/asignacion?fecha=$dia&otros_dias=1", null, $admin);
afirmar("Con «otros dias» aparece tambien la del jueves, despues de las del dia", true, in_array($idR3, array_column($cuerpo["datos"]["rutas"] ?? [], "id_ruta"), true) && !in_array($idR3, array_column($rutasTasg, "id_ruta"), true));

$pdo->prepare("INSERT INTO incidencia (descripcion, tipo_problema, estado, prioridad, id_usuario, id_contenedor) VALUES ('Desborde de prueba', 'desbordado', 'abierta', 'alta', 1, ?)")->execute([$ca[4]]);
$pdo->prepare("INSERT INTO incidencia (descripcion, tipo_problema, estado, prioridad, id_usuario, id_contenedor) VALUES ('Contenedor roto de prueba', 'roto', 'abierta', 'media', 1, ?)")->execute([$ca[2]]);
[, $cuerpo] = peticion("GET", "/api-recoleccion/asignacion?fecha=$dia", null, $admin);
$rutasTasg = array_values(array_filter($cuerpo["datos"]["rutas"] ?? [], fn($r) => str_starts_with($r["nombre"], "TASG Ruta")));
afirmar("Una incidencia «desbordado» sube la ruta a prioridad alta y la pone primera; un contenedor «roto» no se recolecta y no suma",
    [[$idR2, $idR1], ["alta", "media"], 1, 1, 50], [
    array_column($rutasTasg, "id_ruta"), array_column(array_column($rutasTasg, "prioridad"), "nivel"), $rutasTasg[0]["prioridad"]["urgentes"] ?? null,
    $rutasTasg[1]["prioridad"]["no_recolectables"] ?? null, $rutasTasg[1]["prioridad"]["puntaje"] ?? null,
]);
$sug = $cuerpo["datos"]["sugerencia"] ?? [];
$propuestaR2 = current(array_filter($sug["propuestas"] ?? [], fn($p) => $p["id_ruta"] === $idR2)) ?: [];
$ordenSug = array_column($sug["propuestas"] ?? [], "id_ruta");
afirmar("La sugerencia pone la ruta urgente antes que la de prioridad media, con la hora de cada ruta", [true, true, "$dia 07:30:00"], [
    in_array($idR2, $ordenSug, true) && in_array($idR1, $ordenSug, true), array_search($idR2, $ordenSug, true) < array_search($idR1, $ordenSug, true), $propuestaR2["fecha_hora_inicio"] ?? null,
]);
$cuentaRec = (int) $pdo->query("SELECT COUNT(*) FROM recoleccion")->fetchColumn();
afirmar("Pedir la asignacion del dia no crea ninguna recoleccion", $cuentaRec, (int) $pdo->query("SELECT COUNT(*) FROM recoleccion")->fetchColumn());

titulo("52 bis 2. La sugerencia empareja cada ruta con la cuadrilla de su municipio (parte 26)");

// Dos cuadrillas de prueba: una del municipio de las rutas TASG y otra de un municipio ajeno (la zona 2 del seed). Se borran apenas termina esta seccion.
[, $cuerpo] = peticion("POST", "/api-recoleccion/cuadrillas", ["nombre" => "TASG Cuadrilla Propia $sufijo", "id_zona" => $idZonaA], $admin);
$idCuaPropia = (int) ($cuerpo["datos"]["id_cuadrilla"] ?? 0);
[, $cuerpo] = peticion("POST", "/api-recoleccion/cuadrillas", ["nombre" => "TASG Cuadrilla Ajena $sufijo", "id_zona" => 2], $admin);
$idCuaAjena = (int) ($cuerpo["datos"]["id_cuadrilla"] ?? 0);
[, $cuerpo] = peticion("GET", "/api-recoleccion/asignacion?fecha=$dia", null, $admin);
$d = $cuerpo["datos"] ?? [];
$cuadPorId = array_column($d["cuadrillas"] ?? [], null, "id_cuadrilla");
afirmar("La asignacion del dia trae el municipio de cada cuadrilla", [$idZonaA, "TASG Zona $sufijo", 2], [
    $cuadPorId[$idCuaPropia]["id_zona"] ?? null, $cuadPorId[$idCuaPropia]["zona"] ?? null, $cuadPorId[$idCuaAjena]["id_zona"] ?? null,
]);
$propuestas = $d["sugerencia"]["propuestas"] ?? [];
$propR2 = current(array_filter($propuestas, fn($p) => $p["id_ruta"] === $idR2)) ?: [];
afirmar("La ruta del municipio TASG recibe la cuadrilla de su municipio, sin aviso de otro municipio", [$idCuaPropia, false], [$propR2["id_cuadrilla"] ?? null, $propR2["fuera_de_zona"] ?? "falta"]);
$rutasPorId = array_column($d["rutas"] ?? [], null, "id_ruta");
$incoherentes = [];
foreach ($propuestas as $p) {
    $zr = $rutasPorId[$p["id_ruta"]]["id_zona"] ?? null;
    $zc = $cuadPorId[$p["id_cuadrilla"]]["id_zona"] ?? null;
    if ($p["fuera_de_zona"] !== ($zr !== null && $zc !== null && $zr !== $zc)) {
        $incoherentes[] = $p["id_ruta"];
    }
}
afirmar("En todas las propuestas del dia, el aviso «fuera de su municipio» aparece exactamente cuando el municipio de la ruta y el de la cuadrilla son distintos", [], $incoherentes);
afirmar("Y ninguna propuesta lleva dos veces la misma cuadrilla ni el mismo camion", [count($propuestas), count($propuestas)], [count(array_unique(array_column($propuestas, "id_cuadrilla"))), count(array_unique(array_column($propuestas, "id_camion")))]);
$pdo->exec("DELETE FROM cuadrilla WHERE nombre LIKE 'TASG Cuadrilla%'");

titulo("52 ter. Asignacion de rutas: el lote, todas o ninguna");

$asig = fn(int $ruta, int $cuadr, int $cam, string $hora = "06:00") => ["id_ruta" => $ruta, "id_cuadrilla" => $cuadr, "id_camion" => $cam, "fecha_hora_inicio" => "$dia $hora"];
$antes = (int) $pdo->query("SELECT COUNT(*) FROM recoleccion")->fetchColumn();
[$codigo, $cuerpo] = peticion("POST", "/api-recoleccion/recolecciones/lote", ["asignaciones" => [$asig($idR1, 1, $idCam1), $asig(99999999, 3, $idCam2)]], $admin);
afirmar("Si una asignacion falla (ruta que no existe), no se crea ninguna y el mensaje dice cual es", [404, true, $antes], [
    $codigo, str_starts_with($cuerpo["mensaje"] ?? "", "Asignación 2: "), (int) $pdo->query("SELECT COUNT(*) FROM recoleccion")->fetchColumn(),
]);
[$codigo] = peticion("POST", "/api-recoleccion/recolecciones/lote", ["asignaciones" => [$asig($idR1, 1, $idCam1), $asig($idR2, 1, $idCam2)]], $admin);
afirmar("La misma cuadrilla dos veces el mismo dia en un lote: 400", 400, $codigo);
[$codigo] = peticion("POST", "/api-recoleccion/recolecciones/lote", ["asignaciones" => [$asig($idR1, 4, $idCam1)]], $admin);
afirmar("Una cuadrilla inactiva: 409 y no se crea nada", [409, $antes], [$codigo, (int) $pdo->query("SELECT COUNT(*) FROM recoleccion")->fetchColumn()]);

[$codigo, $cuerpo] = peticion("POST", "/api-recoleccion/recolecciones/lote", ["asignaciones" => [$asig($idR1, 1, $idCam1, "06:00"), $asig($idR2, 3, $idCam2, "07:30")]], $admin);
afirmar("Un lote valido: 201 con las dos recolecciones planificadas", [201, 2, ["planificada", "planificada"], ["Cuadrilla Norte", "Cuadrilla Centro"]], [
    $codigo, count($cuerpo["datos"] ?? []), array_column($cuerpo["datos"] ?? [], "estado"), array_column($cuerpo["datos"] ?? [], "cuadrilla"),
]);
$idRec1 = (int) ($cuerpo["datos"][0]["id_recoleccion"] ?? 0);
$idRec2 = (int) ($cuerpo["datos"][1]["id_recoleccion"] ?? 0);
afirmar("A la cuadrilla se le avisa de cada recoleccion asignada", true, (int) $pdo->query("SELECT COUNT(*) FROM notificacion WHERE tipo = 'recoleccion_asignada' AND enlace = 'recoleccion.html?id=$idRec1'")->fetchColumn() >= 1);
[, $cuerpo] = peticion("GET", "/api-recoleccion/asignacion?fecha=$dia", null, $admin);
$rutasTasg = array_column(array_filter($cuerpo["datos"]["rutas"] ?? [], fn($r) => str_starts_with($r["nombre"], "TASG Ruta")), null, "id_ruta");
$libres = array_column(array_filter($cuerpo["datos"]["cuadrillas"] ?? [], fn($c) => $c["libre"]), "id_cuadrilla");
afirmar("Despues, las dos rutas figuran asignadas (con su cuadrilla) y esas cuadrillas ya no estan libres ese dia", [["Cuadrilla Norte", "Cuadrilla Centro"], false, false, true], [
    [$rutasTasg[$idR1]["asignada"]["cuadrilla"] ?? null, $rutasTasg[$idR2]["asignada"]["cuadrilla"] ?? null], in_array(1, $libres, true), in_array(3, $libres, true), in_array(2, $libres, true),
]);
[$codigo, $cuerpo] = peticion("POST", "/api-recoleccion/recolecciones/lote", ["asignaciones" => [$asig($idR3, 1, $idCam1, "08:00")]], $admin);
afirmar("Esa cuadrilla y ese camion no se pueden asignar otra vez el mismo dia: 409 que dice cual", [409, true], [$codigo, str_contains($cuerpo["mensaje"] ?? "", "ya tiene la recolección REC-")]);

titulo("52 quater. Mis rutas y lo que ve la cuadrilla");

[$codigo, $cuerpo] = peticion("GET", "/api-recoleccion/mis-rutas", null, $cuadrilla);
$propia = current(array_filter($cuerpo["datos"] ?? [], fn($r) => $r["id_recoleccion"] === $idRec1)) ?: [];
afirmar("La cuadrilla Norte ve su recoleccion con la ruta, las paradas en orden y su prioridad", [200, false, $idR1, [$ca[1], $ca[2], $ca[3]], [1, 2, 3], ["media", "normal", "normal"]], [
    $codigo, $cuerpo["sin_cuadrilla"] ?? null, $propia["recorrido"]["id_ruta"] ?? null, array_column($propia["recorrido"]["paradas"] ?? [], "id_contenedor"),
    array_column($propia["recorrido"]["paradas"] ?? [], "orden"), array_column($propia["recorrido"]["paradas"] ?? [], "prioridad"),
]);
afirmar("La parada «rota» se muestra como que no se recolecta, y nadie ha vaciado todavia", [false, 0], [$propia["recorrido"]["paradas"][1]["recolectable"] ?? null, $propia["recorrido"]["visitadas"] ?? null]);
[, $cuerpo] = peticion("GET", "/api-recoleccion/mis-rutas", null, $otraCuadrilla);
afirmar("Otra cuadrilla no ve esa recoleccion ni su ruta", [false, false], [
    in_array($idRec1, array_column($cuerpo["datos"] ?? [], "id_recoleccion"), true), in_array($idRec2, array_column($cuerpo["datos"] ?? [], "id_recoleccion"), true),
]);
[$codigo, $cuerpo] = peticion("GET", "/api-recoleccion/recolecciones/$idRec1", null, $cuadrilla);
afirmar("El detalle de su recoleccion trae el recorrido (paradas y trazado) para dibujar el mapa", [200, 3, true], [$codigo, count($cuerpo["datos"]["recorrido"]["paradas"] ?? []), array_key_exists("trazado", $cuerpo["datos"]["recorrido"] ?? [])]);
[$codigo] = peticion("GET", "/api-recoleccion/recolecciones/$idRec1", null, $otraCuadrilla);
afirmar("Y la cuadrilla ajena no lo abre: 404", 404, $codigo);
[$codigo] = peticion("GET", "/api-recoleccion/rutas/$idR1", null, $cuadrilla);
afirmar("La cuadrilla sigue sin poder leer ni armar rutas: 403", 403, $codigo);

[, $cuerpo] = peticion("GET", "/api-gestion/contenedores", null, $cuadrilla);
$codigosVistos = array_column($cuerpo["datos"] ?? [], "codigo");
afirmar("La cuadrilla Norte ve solo los contenedores de su ruta: los de la ruta 1 si; los de la ruta 2 (de otra cuadrilla) no", [true, true, true, false, false], [
    in_array("TASG-$sufijo-1", $codigosVistos, true), in_array("TASG-$sufijo-2", $codigosVistos, true), in_array("TASG-$sufijo-3", $codigosVistos, true),
    in_array("TASG-$sufijo-4", $codigosVistos, true), in_array("TASG-$sufijo-6", $codigosVistos, true),
]);
[, $cuerpoAdmin] = peticion("GET", "/api-gestion/contenedores", null, $admin);
afirmar("Pero el administrador los ve todos, y el vecino tambien (no se le limita)", [true, true], [
    in_array("TASG-$sufijo-4", array_column($cuerpoAdmin["datos"] ?? [], "codigo"), true), in_array("TASG-$sufijo-4", array_column(peticion("GET", "/api-gestion/contenedores", null, $vecino)[1]["datos"] ?? [], "codigo"), true),
]);
[$codigo] = peticion("GET", "/api-gestion/contenedores/" . $ca[4], null, $cuadrilla);
afirmar("Un contenedor de otra ruta: la cuadrilla recibe 404 en la ficha y en el historial", [404, 404, 200], [
    $codigo, peticion("GET", "/api-gestion/contenedores/" . $ca[4] . "/historial", null, $cuadrilla)[0], peticion("GET", "/api-gestion/contenedores/" . $ca[1], null, $cuadrilla)[0],
]);
$totalCuadrilla = peticion("GET", "/api-gestion/contenedores?por_pagina=10&pagina=1", null, $cuadrilla)[1]["meta"]["total"] ?? null;
$totalAdmin = peticion("GET", "/api-gestion/contenedores?por_pagina=10&pagina=1", null, $admin)[1]["meta"]["total"] ?? null;
afirmar("La lista paginada tambien respeta el alcance: el total de la cuadrilla es menor que el del administrador", true, $totalCuadrilla !== null && $totalAdmin !== null && $totalCuadrilla > 0 && $totalCuadrilla < $totalAdmin);

[$codigo, $cuerpo] = peticion("POST", "/api-recoleccion/recolecciones/$idRec1/inicio", [], $cuadrilla);
afirmar("La cuadrilla inicia su recoleccion", [200, "en_curso"], [$codigo, $cuerpo["datos"]["estado"] ?? null]);
$vaciar = fn(string $codigoCont) => peticion("POST", "/api-recoleccion/recolecciones/$idRec1/vaciados", ["codigo_contenedor" => $codigoCont, "peso_kg" => 40], $cuadrilla);
[$codigo, $cuerpo] = $vaciar("TASG-$sufijo-1");
afirmar("Vaciar un contenedor de su ruta: 201, y en el recorrido queda como visitado", [201, true, 1], [
    $codigo, $cuerpo["datos"]["recorrido"]["paradas"][0]["visitada"] ?? null, $cuerpo["datos"]["recorrido"]["visitadas"] ?? null,
]);
[$codigo, $cuerpo] = $vaciar("TASG-$sufijo-5");
afirmar("Vaciar un contenedor que NO es de su ruta (es de la ruta 2): 409 que lo dice", [409, true], [$codigo, str_contains($cuerpo["mensaje"] ?? "", "no está en la ruta de esta recolección")]);
$pdo->prepare("UPDATE contenedor SET estado = 'mantenimiento' WHERE id_contenedor = ?")->execute([$ca[3]]);
[$codigo, $cuerpo] = $vaciar("TASG-$sufijo-3");
afirmar("Vaciar uno de su ruta que esta en mantenimiento: 409 (lo atiende mantenimiento, no el camion)", [409, true], [$codigo, str_contains($cuerpo["mensaje"] ?? "", "mantenimiento")]);
[$codigo] = $vaciar("TASG-$sufijo-1");
afirmar("Y el mismo contenedor dos veces sigue siendo 409", 409, $codigo);

$limpiarTasg();
afirmar("Las rutas, contenedores, camiones y la zona de prueba se borraron", [0, 0, 0, 0, 0], [
    (int) $pdo->query("SELECT COUNT(*) FROM ruta WHERE nombre LIKE 'TASG Ruta%'")->fetchColumn(), (int) $pdo->query("SELECT COUNT(*) FROM contenedor WHERE codigo LIKE 'TASG-%'")->fetchColumn(),
    (int) $pdo->query("SELECT COUNT(*) FROM camion WHERE matricula LIKE 'TASG-%'")->fetchColumn(), (int) $pdo->query("SELECT COUNT(*) FROM zona WHERE nombre LIKE 'TASG Zona%'")->fetchColumn(),
    (int) $pdo->query("SELECT COUNT(*) FROM recoleccion WHERE id_recoleccion IN ($idRec1, $idRec2)")->fetchColumn(),
]);
