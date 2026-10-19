<?php

// Parte de correr-tests.php (se incluye desde alli y usa sus funciones y sus sesiones).
// Participacion del vecino (extras, parte 14). Todo lo que se crea lleva el prefijo TPAR (un contenedor, dos vecinos, sus reclamos);
// al final se borra y la cascada se lleva sus calificaciones y puntos, que son de solo agregado.

titulo("31. Participacion: preparar los datos de prueba");

$pdo->exec("DELETE FROM incidencia WHERE id_contenedor IN (SELECT id_contenedor FROM contenedor WHERE codigo LIKE 'TPAR-%')");
$pdo->exec("DELETE FROM contenedor WHERE codigo LIKE 'TPAR-%'");
$pdo->exec("DELETE FROM usuario WHERE email LIKE 'tpar-%@test.uy'");

$conteoParticipacion = [
    "calificacion" => (int) $pdo->query("SELECT COUNT(*) FROM calificacion")->fetchColumn(),
    "movimiento_punto" => (int) $pdo->query("SELECT COUNT(*) FROM movimiento_punto")->fetchColumn(),
];
$hashP = password_hash(CLAVE_DE_PRUEBA, PASSWORD_DEFAULT);
$vecinosP = [];
foreach (["a" => "tpar-a-$sufijo@test.uy", "b" => "tpar-b-$sufijo@test.uy"] as $clave => $correoP) {
    $pdo->prepare("INSERT INTO usuario (nombre, apellido, email, password_hash, id_rol, estado) VALUES ('Tpar', ?, ?, ?, (SELECT id_rol FROM rol WHERE nombre = 'Vecino'), 'activo')")
        ->execute([strtoupper($clave), $correoP, $hashP]);
    $vecinosP[$clave] = sys_get_temp_dir() . "/sigeru_test_tpar_$clave.txt";
    iniciarSesion($correoP, $vecinosP[$clave]);
}
[$vA, $vB] = [$vecinosP["a"], $vecinosP["b"]];
peticion("POST", "/api-gestion/contenedores", ["codigo" => "TPAR-A$sufijo", "direccion" => "Calle de prueba de participacion 1", "capacidad" => 800, "tipo_residuo" => "Mixto", "id_zona" => 2], $admin);
$progresoDe = fn(string $cookies) => (peticion("GET", "/api-gestion/participacion/progreso", null, $cookies)[1]["datos"] ?? []);
$reportar = function (string $tipo, string $cookies) use ($sufijo): int {
    [, $c] = peticion("POST", "/api-gestion/incidencias", ["codigo_contenedor" => "TPAR-A$sufijo", "tipo_problema" => $tipo, "descripcion" => "Reclamo de prueba de participacion"], $cookies);
    return (int) ($c["datos"]["id_incidencia"] ?? 0);
};

foreach (["POST /incidencias/1/calificacion", "GET /participacion/progreso", "GET /participacion/ranking", "GET /estadisticas/satisfaccion"] as $pedido) {
    [$metodo, $ruta] = explode(" ", $pedido);
    [$codigo] = peticion($metodo, "/api-gestion$ruta", ["puntaje" => 5], null);
    afirmar("$pedido sin sesion: 401", 401, $codigo);
}
foreach (["POST /incidencias/1/calificacion" => ["admin" => $admin, "operario" => $operario, "cuadrilla" => $cuadrilla],
          "GET /participacion/progreso" => ["admin" => $admin, "operario" => $operario, "cuadrilla" => $cuadrilla],
          "GET /estadisticas/satisfaccion" => ["vecino" => $vecino, "operario" => $operario, "cuadrilla" => $cuadrilla]] as $pedido => $roles) {
    [$metodo, $ruta] = explode(" ", $pedido);
    $obtenidos = [];
    foreach ($roles as $quien => $cookies) {
        [$obtenidos[$quien]] = peticion($metodo, "/api-gestion$ruta", ["puntaje" => 5], $cookies);
    }
    afirmar("$pedido: los demas roles reciben 403", array_fill_keys(array_keys($roles), 403), $obtenidos);
}
$rankingPorRol = [];
foreach (["admin" => $admin, "vecino" => $vecino, "operario" => $operario, "cuadrilla" => $cuadrilla] as $quien => $cookies) {
    [$rankingPorRol[$quien]] = peticion("GET", "/api-gestion/participacion/ranking", null, $cookies);
}
afirmar("El ranking por zona lo ve cualquier persona con sesion (no hay ranking publico)", array_fill_keys(["admin", "vecino", "operario", "cuadrilla"], 200), $rankingPorRol);
[$codigo] = peticion("GET", "/api-gestion/participacion/inventado", null, $admin);
afirmar("Un recurso de participacion que no existe: 403", 403, $codigo);


titulo("31 bis. Participacion: los puntos llegan al resolverse, no al informar");

$baseA = $progresoDe($vA);
afirmar("Un vecino nuevo empieza sin puntos ni insignias", [0, 0, 0, [false, false, false, false]], [$baseA["puntos"] ?? null, $baseA["resueltos"] ?? null, $baseA["calificados"] ?? null, array_column($baseA["insignias"] ?? [], "lograda")]);
$idP1 = $reportar("desbordado", $vA);
afirmar("Informar un problema no suma puntos (para evitar abusos)", 0, $progresoDe($vA)["puntos"] ?? null);
[$codigo] = peticion("POST", "/api-gestion/incidencias/$idP1/calificacion", ["puntaje" => 5], $vA);
afirmar("Calificar un reclamo abierto: 409", 409, $codigo);
peticion("POST", "/api-gestion/incidencias/$idP1/asignacion", ["id_cuadrilla" => 1], $admin);
[$codigo] = peticion("POST", "/api-gestion/incidencias/$idP1/calificacion", ["puntaje" => 5], $vA);
afirmar("Ni uno en curso: 409", 409, $codigo);
[$codigo] = peticion("POST", "/api-gestion/incidencias/$idP1/resolucion", ["observacion" => "Listo"], $cuadrilla);
$p = $progresoDe($vA);
afirmar("Al resolverse suma 10 puntos, 1 resuelto y la primera insignia", [200, 10, 1, [true, false, false, false]], [$codigo, $p["puntos"] ?? null, $p["resueltos"] ?? null, array_column($p["insignias"] ?? [], "lograda")]);
afirmar("El historial de puntos dice por que", ["Reclamo resuelto", 10], [$p["movimientos"][0]["motivo"] ?? null, $p["movimientos"][0]["puntos"] ?? null]);
afirmar("Y el reclamo aparece para calificar, con su plazo", [[$idP1], true], [array_column($p["por_calificar"] ?? [], "id_incidencia"), isset($p["por_calificar"][0]["hasta"])]);
afirmar("El otro vecino no ve esos puntos", 0, $progresoDe($vB)["puntos"] ?? null);

[$codigo, $cuerpo] = peticion("GET", "/api-gestion/incidencias/$idP1", null, $vA);
afirmar("El autor ve que puede calificar (hasta una fecha) y que todavia no hay calificacion", [200, true, null, true], [$codigo, $cuerpo["datos"]["puede_calificar"] ?? null, array_key_exists("calificacion", $cuerpo["datos"] ?? []) ? $cuerpo["datos"]["calificacion"] : "falta", isset($cuerpo["datos"]["calificar_hasta"])]);


titulo("31 ter. Participacion: calificar una sola vez, solo el autor");

$cal = "/api-gestion/incidencias/$idP1/calificacion";
foreach ([
    "Sin puntaje"        => [],
    "Puntaje 0"          => ["puntaje" => 0],
    "Puntaje 6"          => ["puntaje" => 6],
    "Puntaje con texto"  => ["puntaje" => "bueno"],
    "Puntaje decimal"    => ["puntaje" => 3.5],
    "Comentario enorme"  => ["puntaje" => 3, "comentario" => str_repeat("a", 301)],
] as $nombre => $malo) {
    [$codigo] = peticion("POST", $cal, $malo, $vA);
    afirmar("Calificar con datos malos ($nombre): 400", 400, $codigo);
}
[$codigo] = peticion("POST", $cal, ["puntaje" => 5], $vB);
afirmar("Otro vecino no puede calificar un reclamo ajeno: 404", 404, $codigo);
[$codigo] = peticion("POST", $cal, ["puntaje" => 5], $vecino);
afirmar("Ni el vecino del seed: 404", 404, $codigo);
[$codigo] = peticion("POST", "/api-gestion/incidencias/99999999/calificacion", ["puntaje" => 5], $vA);
afirmar("Un reclamo que no existe: 404", 404, $codigo);
afirmar("Nada de eso dejo calificacion ni puntos de mas", 10, $progresoDe($vA)["puntos"] ?? null);

[$codigo, $cuerpo] = peticion("POST", $cal, ["puntaje" => "4", "comentario" => "Muy bien, gracias"], $vA);
afirmar("El autor califica con 4 y un comentario: 201", [201, 4, "Muy bien, gracias"], [$codigo, $cuerpo["datos"]["puntaje"] ?? null, $cuerpo["datos"]["comentario"] ?? null]);
$p = $progresoDe($vA);
afirmar("Suma 5 puntos mas (15), la insignia de opinar y ya no esta para calificar", [15, 1, [true, false, false, true], []], [
    $p["puntos"] ?? null, $p["calificados"] ?? null, array_column($p["insignias"] ?? [], "lograda"), $p["por_calificar"] ?? null]);
[$codigo] = peticion("POST", $cal, ["puntaje" => 1], $vA);
afirmar("No se califica dos veces: 409", 409, $codigo);
afirmar("Y el segundo intento no cambio nada", [15, 4], [$progresoDe($vA)["puntos"] ?? null, (int) $pdo->query("SELECT puntaje FROM calificacion WHERE id_incidencia = $idP1")->fetchColumn()]);

foreach (["autor" => [$vA, 200, true], "administrador" => [$admin, 200, true], "cuadrilla asignada" => [$cuadrilla, 200, true], "otra cuadrilla" => [$otraCuadrilla, 404, false], "otro vecino" => [$vB, 404, false]] as $quien => [$cookies, $esperadoCodigo, $ve]) {
    [$codigo, $cuerpo] = peticion("GET", "/api-gestion/incidencias/$idP1", null, $cookies);
    afirmar("El detalle para $quien: " . ($ve ? "ve la calificacion (4)" : "no lo abre"), [$esperadoCodigo, $ve ? 4 : null], [$codigo, $cuerpo["datos"]["calificacion"]["puntaje"] ?? null]);
}
[, $cuerpo] = peticion("GET", "/api-gestion/incidencias/$idP1", null, $vA);
afirmar("Una vez calificado ya no ofrece calificar", false, $cuerpo["datos"]["puede_calificar"] ?? null);

$idP2 = $reportar("roto", $vA);
peticion("POST", "/api-gestion/incidencias/$idP2/descarte", ["observacion" => "Duplicado"], $admin);
[$codigo] = peticion("POST", "/api-gestion/incidencias/$idP2/calificacion", ["puntaje" => 5], $vA);
afirmar("Un reclamo descartado no se califica (409) y no suma puntos", [409, 15], [$codigo, $progresoDe($vA)["puntos"] ?? null]);
afirmar("El descartado tampoco llega a resueltos", 1, $progresoDe($vA)["resueltos"] ?? null);


titulo("31 quater. Participacion: plazo, solo agregado y duplicados");

$idP3 = $reportar("ausente_o_mal_ubicado", $vA);
peticion("POST", "/api-gestion/incidencias/$idP3/asignacion", ["id_cuadrilla" => 1], $admin);
peticion("POST", "/api-gestion/incidencias/$idP3/resolucion", [], $cuadrilla);
$pdo->exec("UPDATE incidencia SET fecha_resolucion = DATE_SUB(NOW(), INTERVAL 31 DAY) WHERE id_incidencia = $idP3");
[$codigo, $cuerpo] = peticion("POST", "/api-gestion/incidencias/$idP3/calificacion", ["puntaje" => 5], $vA);
afirmar("Pasados 30 dias de la resolucion ya no se califica: 409 con el motivo", [409, true], [$codigo, str_contains($cuerpo["mensaje"] ?? "", "plazo")]);
[, $cuerpo] = peticion("GET", "/api-gestion/incidencias/$idP3", null, $vA);
afirmar("Y el detalle no ofrece calificar", false, $cuerpo["datos"]["puede_calificar"] ?? null);
afirmar("Ni la lista para calificar lo incluye", false, in_array($idP3, array_column($progresoDe($vA)["por_calificar"] ?? [], "id_incidencia"), true));
$pdo->exec("UPDATE incidencia SET fecha_resolucion = DATE_SUB(NOW(), INTERVAL 29 DAY) WHERE id_incidencia = $idP3");
[$codigo] = peticion("POST", "/api-gestion/incidencias/$idP3/calificacion", ["puntaje" => 2], $vA);
afirmar("A los 29 dias todavia se puede: 201", 201, $codigo);
afirmar("Los puntos acumulados: 10 + 5 del primero y 10 + 5 de este, 30 en total", [30, 2, 2], [$progresoDe($vA)["puntos"] ?? null, $progresoDe($vA)["resueltos"] ?? null, $progresoDe($vA)["calificados"] ?? null]);

foreach (["UPDATE calificacion SET puntaje = 1 WHERE id_incidencia = $idP1" => "calificacion", "DELETE FROM calificacion WHERE id_incidencia = $idP1" => "calificacion",
          "UPDATE movimiento_punto SET puntos = 99 WHERE id_incidencia = $idP1" => "puntos", "DELETE FROM movimiento_punto WHERE id_incidencia = $idP1" => "puntos"] as $sql => $que) {
    try {
        $pdo->exec($sql);
        $resultado = "se pudo";
    } catch (PDOException $e) {
        $resultado = preg_match("/no se edita|no se borra|solo agregado/", $e->getMessage()) ? "bloqueado" : $e->getMessage();
    }
    afirmar("Ni por fuera de la API se edita o se borra (" . substr($sql, 0, 6) . " de $que)", "bloqueado", $resultado);
}
try {
    $pdo->exec("INSERT INTO movimiento_punto (id_usuario, id_incidencia, motivo, puntos) SELECT id_usuario, id_incidencia, 'reclamo_resuelto', 10 FROM incidencia WHERE id_incidencia = $idP1");
    $duplicado = "se pudo";
} catch (PDOException $e) {
    $duplicado = "rechazado";
}
afirmar("Los puntos de un mismo reclamo y motivo no se pueden duplicar", "rechazado", $duplicado);
try {
    $pdo->exec("INSERT INTO calificacion (id_incidencia, id_usuario, puntaje) SELECT id_incidencia + 0, id_usuario, 9 FROM incidencia WHERE id_incidencia = $idP2");
    $fueraDeEscala = "se pudo";
} catch (PDOException $e) {
    $fueraDeEscala = "rechazado";
}
afirmar("Un puntaje fuera de 1 a 5 lo rechaza la propia base", "rechazado", $fueraDeEscala);


titulo("31 quinquies. Participacion: ranking por zona y calidad del servicio");

[, $cuerpo] = peticion("GET", "/api-gestion/participacion/ranking", null, $vB);
$filasRanking = $cuerpo["datos"] ?? [];
$porZonaRanking = array_column($filasRanking, null, "zona");
afirmar("El ranking es por zona (sin nombres ni correos) y trae las cuatro columnas", [["posicion", "zona", "resueltos", "puntos"], true], [array_keys($filasRanking[0] ?? []), count($filasRanking) >= 5]);
afirmar("Ciudad Vieja tiene los 15 puntos del seed y Centro los 30 de la prueba (mas lo que ya hubiera)", [15, true], [$porZonaRanking["Ciudad Vieja"]["puntos"] ?? null, ($porZonaRanking["Centro"]["puntos"] ?? 0) >= 30]);
afirmar("Esta ordenado por puntos, de mayor a menor", true, array_column($filasRanking, "puntos") === (function () { $p = array_column($GLOBALS["filasRanking"], "puntos"); rsort($p); return $p; })());
afirmar("La primera fila es la posicion 1", 1, $filasRanking[0]["posicion"] ?? null);

[$codigo, $cuerpo] = peticion("GET", "/api-gestion/estadisticas/satisfaccion", null, $admin);
$d = $cuerpo["datos"] ?? [];
$zonasS = array_column($d["por_zona"] ?? [], null, "zona");
$cuadrillasS = array_column($d["por_cuadrilla"] ?? [], null, "cuadrilla");
afirmar("El informe de calidad: 200 con general, por zona, por cuadrilla y el periodo", [200, true, true, true, true], [
    $codigo, isset($d["general"]), isset($d["por_zona"]), isset($d["por_cuadrilla"]), isset($d["periodo"]["generado_en"])]);
afirmar("Ciudad Vieja: 1 calificacion de 4 (la del seed) en un periodo sin tocar", [1, 4.0], [$zonasS["Ciudad Vieja"]["calificaciones"] ?? null, (float) ($zonasS["Ciudad Vieja"]["promedio"] ?? 0)]);
afirmar("Centro: las 2 de la prueba (4 y 2) dan 3", [2, 3.0], [$zonasS["Centro"]["calificaciones"] ?? null, (float) ($zonasS["Centro"]["promedio"] ?? 0)]);
afirmar("La distribucion suma las calificaciones del general", $d["general"]["calificaciones"] ?? -1, array_sum($d["general"]["distribucion"] ?? []));
afirmar("La Norte suma las 3 (la del seed y las 2 de la prueba): (4+4+2)/3 = 3,3", [3, 3.3], [$cuadrillasS["Cuadrilla Norte"]["calificaciones"] ?? null, (float) ($cuadrillasS["Cuadrilla Norte"]["promedio"] ?? 0)]);
afirmar("Cada zona trae su tiempo medio de resolucion (o nulo si no resolvio nada)", true, count(array_filter($d["por_zona"] ?? [], fn($z) => array_key_exists("tiempo_medio_horas", $z))) === count($d["por_zona"] ?? []));
[, $cuerpo] = peticion("GET", "/api-gestion/estadisticas/satisfaccion?zona=1", null, $admin);
afirmar("Filtrar por zona deja solo esa zona", ["Ciudad Vieja"], array_column($cuerpo["datos"]["por_zona"] ?? [], "zona"));
[, $cuerpo] = peticion("GET", "/api-gestion/estadisticas/satisfaccion?desde=2999-01-01", null, $admin);
afirmar("Un periodo futuro esta vacio, sin inventar promedios", [0, null, []], [$cuerpo["datos"]["general"]["incidencias"] ?? null, array_key_exists("promedio", $cuerpo["datos"]["general"] ?? []) ? $cuerpo["datos"]["general"]["promedio"] : "falta", $cuerpo["datos"]["por_zona"] ?? null]);
[$codigo] = peticion("GET", "/api-gestion/estadisticas/satisfaccion?desde=ayer", null, $admin);
afirmar("Una fecha invalida: 400", 400, $codigo);


titulo("31 sexies. Participacion: limpieza y datos del seed intactos");

$pdo->exec("DELETE FROM incidencia WHERE id_contenedor IN (SELECT id_contenedor FROM contenedor WHERE codigo LIKE 'TPAR-%')");
$pdo->exec("DELETE FROM contenedor WHERE codigo LIKE 'TPAR-%'");
$pdo->exec("DELETE FROM usuario WHERE email LIKE 'tpar-%@test.uy'");
foreach ($vecinosP as $archivo) {
    if (file_exists($archivo)) {
        unlink($archivo);
    }
}
afirmar("Las calificaciones y los puntos volvieron a los del seed (se fueron en cascada)", $conteoParticipacion, [
    "calificacion" => (int) $pdo->query("SELECT COUNT(*) FROM calificacion")->fetchColumn(),
    "movimiento_punto" => (int) $pdo->query("SELECT COUNT(*) FROM movimiento_punto")->fetchColumn(),
]);
afirmar("Marta sigue con sus 15 puntos de demostracion (los del reclamo INC-0002 del seed)", 15, (int) $pdo->query("SELECT SUM(puntos) FROM movimiento_punto WHERE id_usuario = 3 AND id_incidencia = 2")->fetchColumn());
