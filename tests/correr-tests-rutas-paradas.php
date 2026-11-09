<?php

// Parte de correr-tests.php (se incluye desde alli y usa sus funciones y sus sesiones).
// Plan de rutas para la defensa (22/10/2026), bloque 2: los contenedores de una ruta, su orden, los conflictos, el trazado y el borrado.
// Una zona propia TRUP con 30 contenedores y otra con 2; se borra todo al final.

require_once __DIR__ . "/../api-gestion/models/Limites.php";

titulo("48. Rutas con contenedores: permisos y validaciones");

$limpiarTrup = function () use ($pdo): void {
    $pdo->exec("DELETE FROM recoleccion WHERE observaciones = 'TRUP prueba'");
    $pdo->exec("DELETE FROM ruta WHERE nombre LIKE 'TRUP Ruta%'");
    $pdo->exec("DELETE FROM contenedor WHERE codigo LIKE 'TRUP-%'");
    $pdo->exec("DELETE FROM zona WHERE nombre LIKE 'TRUP Zona%'");
};
$limpiarTrup();

$pdo->prepare("INSERT INTO zona (nombre, descripcion) VALUES (?, 'zona de prueba de las rutas')")->execute(["TRUP Zona $sufijo"]);
$idZonaT = (int) $pdo->lastInsertId();
$pdo->prepare("INSERT INTO zona (nombre, descripcion) VALUES (?, 'otra zona de prueba de las rutas')")->execute(["TRUP Zona B $sufijo"]);
$idZonaT2 = (int) $pdo->lastInsertId();
$nuevoCont = $pdo->prepare("INSERT INTO contenedor (codigo, direccion, latitud, longitud, capacidad, estado, id_zona, id_tipo_residuo) VALUES (?, ?, ?, ?, 800, 'disponible', ?, 1)");
$idc = [];   // idc[n] = id del contenedor TRUP-$sufijo-n
for ($i = 1; $i <= 30; $i++) {
    $nuevoCont->execute(["TRUP-$sufijo-$i", "Calle de prueba $i", -34.9 - $i * 0.001, -56.2, $idZonaT]);
    $idc[$i] = (int) $pdo->lastInsertId();
}
foreach ([31, 32] as $i) {
    $nuevoCont->execute(["TRUP-$sufijo-$i", "Calle de otra zona $i", -34.8, -56.1, $idZonaT2]);
    $idc[$i] = (int) $pdo->lastInsertId();
}
$crearRuta = function (string $nombre, int $zona, string $estado = "activa") use ($admin): int {
    [, $cuerpo] = peticion("POST", "/api-recoleccion/rutas", ["nombre" => "TRUP Ruta $nombre $GLOBALS[sufijo]", "dia_semana" => "martes", "hora_inicio" => "07:00", "id_zona" => $zona, "estado" => $estado], $admin);
    return (int) ($cuerpo["datos"]["id_ruta"] ?? 0);
};
$idA = $crearRuta("A", $idZonaT);
$idB = $crearRuta("B", $idZonaT);
afirmar("Se crearon dos rutas de prueba en la zona TRUP", true, $idA > 0 && $idB > 0 && $idA !== $idB);
$paradasDe = fn(int $idRuta) => array_map("intval", $pdo->query("SELECT id_contenedor FROM ruta_contenedor WHERE id_ruta = $idRuta ORDER BY orden")->fetchAll(PDO::FETCH_COLUMN));
$ordenDe = fn(int $idRuta) => array_map("intval", $pdo->query("SELECT orden FROM ruta_contenedor WHERE id_ruta = $idRuta ORDER BY orden")->fetchAll(PDO::FETCH_COLUMN));
$poner = fn(int $idRuta, array $ids, bool $mover = false, ?string $sesion = null) => peticion("PUT", "/api-recoleccion/rutas/$idRuta/contenedores", ["contenedores" => $ids, "mover" => $mover], $sesion ?? $admin);

$obtenidos = [];
foreach (["sin sesion" => null, "vecino" => $vecino, "operario" => $operario, "cuadrilla" => $cuadrilla] as $quien => $cookies) {
    $fila = [];
    [$fila[]] = peticion("PUT", "/api-recoleccion/rutas/$idA/contenedores", ["contenedores" => [$idc[1]]], $cookies);
    [$fila[]] = peticion("GET", "/api-recoleccion/rutas/$idA/candidatos", null, $cookies);
    [$fila[]] = peticion("POST", "/api-recoleccion/rutas/$idA/trazado", [], $cookies);
    [$fila[]] = peticion("GET", "/api-recoleccion/rutas/$idA", null, $cookies);
    $obtenidos[$quien] = $fila;
}
$esperado401 = [401, 401, 401, 401];
$esperado403 = [403, 403, 403, 403];
afirmar("Armar rutas es solo del Administrador: sin sesion 401; vecino, operario y cuadrilla 403 (poner contenedores, candidatos, trazar y ver)",
    ["sin sesion" => $esperado401, "vecino" => $esperado403, "operario" => $esperado403, "cuadrilla" => $esperado403], $obtenidos);
afirmar("Y no se cambio nada: la ruta sigue sin contenedores", [], $paradasDe($idA));

[$codigo] = $poner(999999, [$idc[1]]);
afirmar("Una ruta que no existe: 404 (poner contenedores)", 404, $codigo);
[$codigo] = peticion("GET", "/api-recoleccion/rutas/999999/candidatos", null, $admin);
afirmar("Una ruta que no existe: 404 (candidatos)", 404, $codigo);
[$codigo] = peticion("POST", "/api-recoleccion/rutas/999999/trazado", [], $admin);
afirmar("Una ruta que no existe: 404 (trazar)", 404, $codigo);

$mal = [
    "sin la clave contenedores"      => [peticion("PUT", "/api-recoleccion/rutas/$idA/contenedores", ["otra" => 1], $admin)[0], null],
    "contenedores no es una lista"   => [peticion("PUT", "/api-recoleccion/rutas/$idA/contenedores", ["contenedores" => "1,2"], $admin)[0], null],
    "un id que no es un numero"      => [$poner($idA, ["abc"])[0], null],
    "un id cero"                     => [$poner($idA, [0])[0], null],
    "un id negativo"                 => [$poner($idA, [-3])[0], null],
    "ids repetidos"                  => [$poner($idA, [$idc[1], $idc[2], $idc[1]])[0], null],
    "mover que no es booleano"       => [peticion("PUT", "/api-recoleccion/rutas/$idA/contenedores", ["contenedores" => [$idc[1]], "mover" => "si"], $admin)[0], null],
    "un contenedor que no existe"    => [$poner($idA, [$idc[1], 99999999])[0], null],
    "un contenedor de otra zona"     => [$poner($idA, [$idc[1], $idc[31]])[0], null],
];
afirmar("Pedidos mal armados: todos 400", array_fill_keys(array_keys($mal), [400, null]), $mal);
$tope = Limites::MAX_CONTENEDORES_POR_RUTA;
$veintiseis = array_values(array_slice($idc, 0, $tope + 1));
[$codigo, $cuerpo] = $poner($idA, $veintiseis);
afirmar("Mas del tope de paradas ($tope): 400 y el mensaje dice el maximo", [400, true], [$codigo, str_contains($cuerpo["mensaje"] ?? "", (string) $tope)]);
afirmar("Despues de tantos rechazos la ruta sigue vacia (nada quedo a medias)", [], $paradasDe($idA));

$pdo->prepare("UPDATE contenedor SET estado = 'retirado' WHERE id_contenedor = ?")->execute([$idc[5]]);
$pdo->prepare("UPDATE contenedor SET estado = 'mantenimiento' WHERE id_contenedor = ?")->execute([$idc[6]]);
$pdo->prepare("UPDATE contenedor SET latitud = NULL, longitud = NULL WHERE id_contenedor = ?")->execute([$idc[7]]);
[$codigoR, $cuerpoR] = $poner($idA, [$idc[1], $idc[5]]);
[$codigoM, $cuerpoM] = $poner($idA, [$idc[1], $idc[6]]);
[$codigoU, $cuerpoU] = $poner($idA, [$idc[1], $idc[7]]);
afirmar("Un contenedor retirado, uno en mantenimiento (nuevo en la ruta) o uno sin ubicacion no entran: 400 con el motivo", [[400, true], [400, true], [400, true]], [
    [$codigoR, str_contains($cuerpoR["mensaje"] ?? "", "retirado")], [$codigoM, str_contains($cuerpoM["mensaje"] ?? "", "mantenimiento")], [$codigoU, str_contains($cuerpoU["mensaje"] ?? "", "ubicación")],
]);
$pdo->prepare("UPDATE contenedor SET estado = 'disponible', latitud = ?, longitud = -56.2 WHERE id_contenedor IN (?, ?)")->execute([-34.906, $idc[5], $idc[6]]);
$pdo->prepare("UPDATE contenedor SET latitud = -34.907, longitud = -56.2 WHERE id_contenedor = ?")->execute([$idc[7]]);

titulo("48 bis. Rutas con contenedores: guardar, ordenar y mover");

[$codigo, $cuerpo] = $poner($idA, [$idc[3], $idc[1], $idc[2]]);
afirmar("Guardar la lista en el orden pedido: 200 y la respuesta trae las paradas numeradas en ese orden", [200, [$idc[3], $idc[1], $idc[2]], [1, 2, 3]], [
    $codigo, array_column($cuerpo["datos"]["paradas"] ?? [], "id_contenedor"), array_column($cuerpo["datos"]["paradas"] ?? [], "orden"),
]);
afirmar("En la base queda el mismo orden", [[$idc[3], $idc[1], $idc[2]], [1, 2, 3]], [$paradasDe($idA), $ordenDe($idA)]);
[, $cuerpo] = peticion("GET", "/api-recoleccion/rutas/$idA", null, $admin);
afirmar("El detalle de la ruta trae las paradas con su ubicacion, la cantidad y el tope", [3, $tope, true, true], [
    $cuerpo["datos"]["cantidad"] ?? null, $cuerpo["datos"]["tope"] ?? null, is_float($cuerpo["datos"]["paradas"][0]["latitud"] ?? null), ($cuerpo["datos"]["paradas"][0]["recolectable"] ?? null) === true,
]);
[, $lista] = peticion("GET", "/api-recoleccion/rutas", null, $admin);
$filaA = current(array_filter($lista["datos"] ?? [], fn($r) => ($r["id_ruta"] ?? 0) === $idA)) ?: [];
afirmar("La lista de rutas muestra cuantos contenedores tiene cada una y si tiene trazado", [3, true], [$filaA["cantidad"] ?? null, array_key_exists("trazado_tipo", $filaA)]);

[$codigo] = $poner($idA, [$idc[2], $idc[3], $idc[1], $idc[4]]);
afirmar("Reordenar y agregar uno: 200 y queda en el nuevo orden", [200, [$idc[2], $idc[3], $idc[1], $idc[4]], [1, 2, 3, 4]], [$codigo, $paradasDe($idA), $ordenDe($idA)]);
[$codigo, $cuerpo] = $poner($idA, [$idc[2], 99999999]);
afirmar("Un pedido con un contenedor inexistente no cambia nada (atomico): 400 y la lista sigue igual", [400, [$idc[2], $idc[3], $idc[1], $idc[4]]], [$codigo, $paradasDe($idA)]);

[$codigo, $cuerpo] = $poner($idB, [$idc[1], $idc[8]]);
afirmar("Un contenedor que ya esta en otra ruta activa: 409 que dice en cual y trae los conflictos", [409, true, true, $idc[1]], [
    $codigo, str_contains($cuerpo["mensaje"] ?? "", "TRUP Ruta A"), str_contains($cuerpo["mensaje"] ?? "", "mover"), $cuerpo["datos"]["conflictos"][0]["id_contenedor"] ?? null,
]);
afirmar("Y la ruta B no cambio", [], $paradasDe($idB));
[$codigo, $cuerpo] = $poner($idB, [$idc[1], $idc[8]], true);
afirmar("Con mover: 200, el contenedor pasa a la ruta B y la respuesta dice cual se movio", [200, [$idc[1], $idc[8]], $idc[1]], [$codigo, $paradasDe($idB), $cuerpo["movidos"][0]["id_contenedor"] ?? null]);
afirmar("La ruta A perdio ese contenedor y se renumero sin huecos", [[$idc[2], $idc[3], $idc[4]], [1, 2, 3]], [$paradasDe($idA), $ordenDe($idA)]);

[$codigo] = $poner($idA, []);
afirmar("Vaciar una ruta: 200 y queda sin contenedores", [200, []], [$codigo, $paradasDe($idA)]);
[$codigo] = $poner($idA, [$idc[2], $idc[3]]);
afirmar("Se vuelven a poner dos", [200, [$idc[2], $idc[3]]], [$codigo, $paradasDe($idA)]);
$pdo->prepare("UPDATE contenedor SET estado = 'mantenimiento' WHERE id_contenedor = ?")->execute([$idc[3]]);
[$codigo, $cuerpo] = $poner($idA, [$idc[3], $idc[2]]);
afirmar("Un contenedor que ya estaba en la ruta y paso a mantenimiento se conserva (se muestra como «no se recolecta»)", [200, false], [$codigo, $cuerpo["datos"]["paradas"][0]["recolectable"] ?? null]);
$pdo->prepare("UPDATE contenedor SET estado = 'disponible' WHERE id_contenedor = ?")->execute([$idc[3]]);

[$codigo, $cuerpo] = peticion("GET", "/api-recoleccion/rutas/$idA/candidatos", null, $admin);
$cand = array_column($cuerpo["datos"] ?? [], null, "id_contenedor");
afirmar("Candidatos: los de la zona de la ruta (30 menos el retirado... que ya no esta retirado, y sin los de otra zona), con su estado de ruta",
    [200, 30, false, true, "TRUP Ruta B $sufijo", $idB, false], [
        $codigo, count($cand), isset($cand[$idc[31]]), $cand[$idc[2]]["en_esta_ruta"] ?? null, $cand[$idc[1]]["otra_ruta"] ?? null, $cand[$idc[1]]["id_otra_ruta"] ?? null, $cand[$idc[1]]["en_esta_ruta"] ?? null,
    ]);

titulo("48 ter. Rutas con contenedores: inactivas, recolecciones en curso, cambio de zona y avisos");

$idI = $crearRuta("I", $idZonaT, "inactiva");
[$codigo] = $poner($idI, [$idc[1], $idc[2]]);
afirmar("Una ruta inactiva puede llevar contenedores que estan en una activa (no hay conflicto)", [200, [$idc[1], $idc[2]]], [$codigo, $paradasDe($idI)]);
[$codigo, $cuerpo] = peticion("PUT", "/api-recoleccion/rutas/$idI", ["estado" => "activa"], $admin);
afirmar("Pero no se puede reactivar si deja un contenedor en dos rutas activas: 409 con cuales", [409, true], [$codigo, str_contains($cuerpo["mensaje"] ?? "", "TRUP-$sufijo-1")]);
$mismaZona = peticion("PUT", "/api-recoleccion/rutas/$idA", ["id_zona" => $idZonaT2], $admin)[0];
afirmar("A una ruta con contenedores no se le puede cambiar la zona: 409", 409, $mismaZona);
[$codigo] = peticion("PUT", "/api-recoleccion/rutas/$idA", ["nombre" => "TRUP Ruta A renombrada $sufijo"], $admin);
afirmar("Renombrarla si se puede: 200", 200, $codigo);

$pdo->prepare("INSERT INTO recoleccion (fecha_hora_inicio, estado, observaciones, id_ruta, id_camion, id_cuadrilla) VALUES (NOW(), 'en_curso', 'TRUP prueba', ?, 1, 1)")->execute([$idB]);
[$codigo, $cuerpo] = $poner($idB, [$idc[8]]);
afirmar("Una ruta que esta en una recoleccion en curso no se toca: 409", [409, [$idc[1], $idc[8]]], [$codigo, $paradasDe($idB)]);
[$codigo] = $poner($idA, [$idc[2], $idc[1]], true);
afirmar("Y tampoco se le puede sacar un contenedor moviendolo a otra ruta: 409 y nada cambia", [409, [$idc[3], $idc[2]]], [$codigo, $paradasDe($idA)]);
[$codigo] = peticion("DELETE", "/api-recoleccion/rutas/$idB", null, $admin);
afirmar("Darla de baja con una recoleccion en curso tampoco: 409", 409, $codigo);
$pdo->exec("DELETE FROM recoleccion WHERE observaciones = 'TRUP prueba'");

$pdo->prepare("INSERT INTO recoleccion (fecha_hora_inicio, estado, observaciones, id_ruta, id_camion, id_cuadrilla) VALUES (DATE_ADD(NOW(), INTERVAL 5 DAY), 'planificada', 'TRUP prueba', ?, 1, 1)")->execute([$idB]);
$avisosAntes = (int) $pdo->query("SELECT COUNT(*) FROM notificacion WHERE tipo = 'ruta_cambiada'")->fetchColumn();
[$codigo] = $poner($idB, [$idc[8], $idc[9]]);
$avisosDespues = (int) $pdo->query("SELECT COUNT(*) FROM notificacion WHERE tipo = 'ruta_cambiada' AND titulo LIKE '%TRUP Ruta B%'")->fetchColumn();
afirmar("Cambiar una ruta con una recoleccion planificada avisa a los de la cuadrilla (1 aviso por integrante activo)", [200, true], [$codigo, $avisosDespues >= 1 && $avisosDespues === (int) $pdo->query("SELECT COUNT(*) FROM usuario WHERE id_cuadrilla = 1 AND estado = 'activo'")->fetchColumn()]);
[$codigo] = peticion("DELETE", "/api-recoleccion/rutas/$idB", null, $admin);
afirmar("Dar de baja una ruta con una recoleccion planificada: 409 (hay que cancelarla antes)", 409, $codigo);
[$codigo] = peticion("DELETE", "/api-recoleccion/rutas/$idB?definitivo=1", null, $admin);
afirmar("Borrarla de verdad tampoco, porque ya tiene una recoleccion: 409", 409, $codigo);
$pdo->exec("DELETE FROM recoleccion WHERE observaciones = 'TRUP prueba'");

titulo("48 quater. Rutas con contenedores: trazado y borrado");

[, $raiz] = peticion("GET", "/api-recoleccion/");
if (($raiz["trazado"] ?? "") !== "recta") {
    omitir("Con Apache arrancado con SIGERU_TRAZADO_MODO=calles el trazado pediria servicios externos: se prueba con el arranque comun (modo recta)");
} else {
    [$codigo, $cuerpo] = peticion("POST", "/api-recoleccion/rutas/$idI/trazado", [], $admin);
    $paradasI = count($paradasDe($idI));
    afirmar("Trazar una ruta de 2 paradas en modo recta: 200, tipo recta, con aviso y sin servicio", [200, "recta", true, null], [
        $codigo, $cuerpo["datos"]["trazado"]["tipo"] ?? null, str_contains($cuerpo["aviso"] ?? "", "línea recta"), array_key_exists("servicio", $cuerpo["datos"]["trazado"] ?? []) ? $cuerpo["datos"]["trazado"]["servicio"] : "falta",
    ]);
    afirmar("El trazado guardado esta vigente y trae la distancia en linea recta", [true, true], [$cuerpo["datos"]["trazado"]["vigente"] ?? null, ($cuerpo["datos"]["trazado"]["distancia_m"] ?? 0) > 0]);
    $pdo->prepare("UPDATE contenedor SET latitud = -34.95 WHERE id_contenedor = ?")->execute([$idc[2]]);
    [, $cuerpo] = peticion("GET", "/api-recoleccion/rutas/$idI", null, $admin);
    afirmar("Si cambia la ubicacion de un contenedor, el trazado guardado deja de estar vigente", false, $cuerpo["datos"]["trazado"]["vigente"] ?? null);
    $pdo->prepare("UPDATE contenedor SET latitud = -34.902 WHERE id_contenedor = ?")->execute([$idc[2]]);
    [, $cuerpo] = $poner($idI, [$idc[1]]);
    afirmar("Si cambia la lista de paradas, el trazado se borra", [true, null], [array_key_exists("trazado", $cuerpo["datos"] ?? []), array_key_exists("trazado", $cuerpo["datos"] ?? []) ? $cuerpo["datos"]["trazado"] : "falta"]);
    [$codigo] = peticion("POST", "/api-recoleccion/rutas/$idI/trazado", [], $admin);
    afirmar("Con menos de 2 paradas no se puede trazar: 409", 409, $codigo);
}

[$codigo] = peticion("DELETE", "/api-recoleccion/rutas/$idA", null, $admin);
afirmar("Dar de baja una ruta sin recolecciones: 200 y queda inactiva", [200, "inactiva"], [$codigo, $pdo->query("SELECT estado FROM ruta WHERE id_ruta = $idA")->fetchColumn()]);
[$codigo] = peticion("DELETE", "/api-recoleccion/rutas/$idA", null, $admin);
afirmar("Darla de baja otra vez: 409", 409, $codigo);
$idN = $crearRuta("N", $idZonaT);
$poner($idN, [$idc[20], $idc[21]]);
[$codigo] = peticion("DELETE", "/api-recoleccion/rutas/$idN?definitivo=1", null, $admin);
afirmar("Borrar de verdad una ruta que nunca tuvo recolecciones: 200; sus vinculos se van y los contenedores siguen", [200, 0, 2], [
    $codigo, (int) $pdo->query("SELECT COUNT(*) FROM ruta_contenedor WHERE id_ruta = $idN")->fetchColumn(),
    (int) $pdo->query("SELECT COUNT(*) FROM contenedor WHERE id_contenedor IN ({$idc[20]}, {$idc[21]})")->fetchColumn(),
]);
[$codigo] = peticion("GET", "/api-recoleccion/rutas/$idN", null, $admin);
afirmar("Y la ruta ya no existe: 404", 404, $codigo);

$pdo->exec("DELETE FROM recoleccion WHERE observaciones = 'TRUP prueba'");
$limpiarTrup();
afirmar("Las rutas, los contenedores y las zonas de prueba se borraron (y la cascada se llevo los vinculos)", [0, 0, 0, 0], [
    (int) $pdo->query("SELECT COUNT(*) FROM ruta WHERE nombre LIKE 'TRUP Ruta%'")->fetchColumn(),
    (int) $pdo->query("SELECT COUNT(*) FROM contenedor WHERE codigo LIKE 'TRUP-%'")->fetchColumn(),
    (int) $pdo->query("SELECT COUNT(*) FROM zona WHERE nombre LIKE 'TRUP Zona%'")->fetchColumn(),
    (int) $pdo->query("SELECT COUNT(*) FROM ruta_contenedor WHERE id_ruta NOT IN (SELECT id_ruta FROM ruta)")->fetchColumn(),
]);
