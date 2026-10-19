<?php

// Parte de correr-tests.php (se incluye desde alli y usa sus funciones y sus sesiones).
// Reenvios sin duplicar (extras, parte 21): registrar un vaciado y registrar un avance con la misma clave no se cargan dos veces.
// Datos propios TIDEM (camion, ruta, contenedores, recoleccion e incidencia); se borra todo al final.

titulo("43. Idempotencia: el mismo pedido dos veces no duplica el vaciado");

$conClave = function (string $ruta, array $datos, ?string $clave, string $cookies) {
    $ch = curl_init(BASE . $ruta);
    $cabeceras = ["Content-Type: application/json"];
    if ($clave !== null) {
        $cabeceras[] = "Idempotency-Key: $clave";
    }
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CUSTOMREQUEST => "POST", CURLOPT_HTTPHEADER => $cabeceras,
        CURLOPT_POSTFIELDS => json_encode($datos), CURLOPT_COOKIEFILE => $cookies]);
    $crudo = curl_exec($ch);
    $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$codigo, json_decode((string) $crudo, true)];
};
$uuid = fn() => sprintf("%08x-%04x-4%03x-%04x-%012x", random_int(0, 0xffffffff), random_int(0, 0xffff), random_int(0, 0xfff), random_int(0x8000, 0xbfff), random_int(0, 0xffffffffffff));

[, $cuerpo] = peticion("POST", "/api-recoleccion/camiones", ["matricula" => "TIDEM-$sufijo", "capacidad" => 4000], $admin);
$idCamionD = (int) ($cuerpo["datos"]["id_camion"] ?? 0);
[, $cuerpo] = peticion("POST", "/api-recoleccion/rutas", ["nombre" => "TIDEM Ruta $sufijo", "dia_semana" => "martes", "hora_inicio" => "06:15", "id_zona" => 2], $admin);
$idRutaD = (int) ($cuerpo["datos"]["id_ruta"] ?? 0);
foreach (["A", "B", "C", "D", "E", "F"] as $letra) {
    peticion("POST", "/api-gestion/contenedores", ["codigo" => "TIDEM-$letra$sufijo", "direccion" => "Contenedor de prueba de idempotencia", "capacidad" => 1100, "tipo_residuo" => "Mixto", "id_zona" => 2], $admin);
}
[, $cuerpo] = peticion("POST", "/api-recoleccion/recolecciones", ["id_ruta" => $idRutaD, "id_camion" => $idCamionD, "id_cuadrilla" => 1, "fecha_hora_inicio" => "2026-10-22T06:30", "id_centro" => 1], $admin);
$idRecD = (int) ($cuerpo["datos"]["id_recoleccion"] ?? 0);
// La incidencia se asigna antes: una cuadrilla en una recoleccion en curso no se puede asignar (RF-228).
[, $cuerpo] = peticion("POST", "/api-gestion/incidencias", ["codigo_contenedor" => "TIDEM-F$sufijo", "tipo_problema" => "desbordado", "descripcion" => "Prueba de idempotencia"], $vecino);
$idIncD = (int) ($cuerpo["datos"]["id_incidencia"] ?? 0);
peticion("POST", "/api-gestion/incidencias/$idIncD/asignacion", ["id_cuadrilla" => 1], $admin);
peticion("POST", "/api-recoleccion/recolecciones/$idRecD/inicio", [], $cuadrilla);
$vaciadosDe = fn() => (int) $pdo->query("SELECT COUNT(*) FROM vaciado WHERE id_recoleccion = $idRecD")->fetchColumn();
$ruta = "/api-recoleccion/recolecciones/$idRecD/vaciados";

[$codigo] = $conClave($ruta, ["codigo_contenedor" => "TIDEM-A$sufijo", "peso_kg" => 100], "no-es-un-uuid", $cuadrilla);
afirmar("Una clave que no es un UUID se rechaza: 400 (y no se cargo nada)", [400, 0], [$codigo, $vaciadosDe()]);

$k1 = $uuid();
[$c1, $r1] = $conClave($ruta, ["codigo_contenedor" => "TIDEM-A$sufijo", "peso_kg" => 100], $k1, $cuadrilla);
afirmar("Con una clave nueva el vaciado se registra: 201, sin marca de repetido", [201, true, false], [$c1, ($r1["datos"]["vaciados"] ?? 0) === 1, isset($r1["repetida"])]);
[$c2, $r2] = $conClave($ruta, ["codigo_contenedor" => "TIDEM-A$sufijo", "peso_kg" => 100], $k1, $cuadrilla);
afirmar("El mismo pedido con la misma clave devuelve la misma respuesta (201) marcada como repetida", [201, true, $r1["datos"] ?? null], [$c2, $r2["repetida"] ?? false, $r2["datos"] ?? null]);
afirmar("... y el vaciado esta una sola vez en la base", 1, $vaciadosDe());
[$c3, $r3] = $conClave($ruta, ["codigo_contenedor" => "TIDEM-B$sufijo", "peso_kg" => 40], $k1, $cuadrilla);
afirmar("Con la misma clave y otro contenido sigue devolviendo lo de la primera vez: no se carga nada nuevo", [201, true, 1], [$c3, $r3["repetida"] ?? false, $vaciadosDe()]);
[$c4] = $conClave("/api-gestion/incidencias/99999/avance", ["observacion" => "x"], $k1, $cuadrilla);
afirmar("La misma clave para otra operacion se rechaza: 409", 409, $c4);

$k2 = $uuid();
[$c5] = $conClave($ruta, ["codigo_contenedor" => "TIDEM-B$sufijo", "peso_kg" => 40], $k2, $cuadrilla);
afirmar("Otra clave es otra operacion: se registra (2 vaciados)", [201, 2], [$c5, $vaciadosDe()]);
[$c6] = $conClave($ruta, ["codigo_contenedor" => "TIDEM-C$sufijo", "peso_kg" => 10], null, $cuadrilla);
[$c7] = $conClave($ruta, ["codigo_contenedor" => "TIDEM-C$sufijo", "peso_kg" => 10], null, $cuadrilla);
afirmar("Sin clave todo sigue como antes: el reenvio se rechaza con 409 («ya se vacio»); con clave, en cambio, el reenvio devuelve el exito original", [201, 409, 3], [$c6, $c7, $vaciadosDe()]);

$k3 = $uuid();
[$c8] = $conClave($ruta, ["codigo_contenedor" => "TIDEM-D$sufijo", "peso_kg" => -5], $k3, $cuadrilla);
$recuerda = (int) $pdo->query("SELECT COUNT(*) FROM clave_idempotencia WHERE clave = '$k3'")->fetchColumn();
afirmar("Un error no se recuerda: el pedido invalido da 400 y no deja la clave guardada", [400, 0], [$c8, $recuerda]);
[$c9] = $conClave($ruta, ["codigo_contenedor" => "TIDEM-D$sufijo", "peso_kg" => 5], $k3, $cuadrilla);
afirmar("... y con la misma clave y el dato corregido, se registra (201)", [201, 4], [$c9, $vaciadosDe()]);

$k4 = $uuid();
$pdo->prepare("INSERT INTO clave_idempotencia (clave, id_usuario, operacion) VALUES (?, 1, 'vaciado')")->execute([$k4]);
$pdo->prepare("UPDATE clave_idempotencia SET id_usuario = (SELECT id_usuario FROM usuario WHERE email = 'cuadrilla@sigeru.uy') WHERE clave = ?")->execute([$k4]);
[$c10] = $conClave($ruta, ["codigo_contenedor" => "TIDEM-E$sufijo", "peso_kg" => 7], $k4, $cuadrilla);
afirmar("Si la misma operacion todavia se esta procesando (reserva reciente): 409, no se ejecuta dos veces", [409, 4], [$c10, $vaciadosDe()]);
$pdo->prepare("UPDATE clave_idempotencia SET creada = DATE_SUB(NOW(), INTERVAL 5 MINUTE) WHERE clave = ?")->execute([$k4]);
[$c11] = $conClave($ruta, ["codigo_contenedor" => "TIDEM-E$sufijo", "peso_kg" => 7], $k4, $cuadrilla);
afirmar("Una reserva abandonada (mas de 2 minutos) se libera y el pedido se hace", [201, 5], [$c11, $vaciadosDe()]);

$pdo->prepare("UPDATE clave_idempotencia SET id_usuario = 1 WHERE clave = ?")->execute([$k1]);
[$c12] = $conClave($ruta, ["codigo_contenedor" => "TIDEM-A$sufijo", "peso_kg" => 100], $k1, $cuadrilla);
afirmar("La clave de otra persona no sirve: 409", 409, $c12);

titulo("43 bis. Idempotencia: el avance de una incidencia");

$avancesDe = fn() => (int) $pdo->query("SELECT COUNT(*) FROM historial_incidencia WHERE id_incidencia = $idIncD AND observacion LIKE 'TIDEM%'")->fetchColumn();
$rutaAv = "/api-gestion/incidencias/$idIncD/avance";
$ka = $uuid();
[$a1, $ra1] = $conClave($rutaAv, ["observacion" => "TIDEM llegamos al lugar"], $ka, $cuadrilla);
[$a2, $ra2] = $conClave($rutaAv, ["observacion" => "TIDEM llegamos al lugar"], $ka, $cuadrilla);
afirmar("Un avance con clave se registra una sola vez aunque llegue dos veces: 200 y 200 (el segundo, repetido)", [200, 200, false, true, 1], [$a1, $a2, isset($ra1["repetida"]), $ra2["repetida"] ?? false, $avancesDe()]);
[$a3] = $conClave($rutaAv, ["observacion" => "TIDEM otro avance"], $uuid(), $cuadrilla);
afirmar("Otra clave es otro avance: 2 en el historial", [200, 2], [$a3, $avancesDe()]);
[$a4] = $conClave($rutaAv, ["observacion" => ""], $uuid(), $cuadrilla);
afirmar("Un avance sin texto se rechaza (400) y no queda la clave", [400], [$a4]);
$quedan = (int) $pdo->query("SELECT COUNT(*) FROM clave_idempotencia WHERE operacion = 'avance'")->fetchColumn();
afirmar("Las claves de los errores no se guardan: solo quedan las 2 de los avances hechos", 2, $quedan);

$pdo->exec("DELETE FROM clave_idempotencia WHERE operacion IN ('vaciado', 'avance')");
$pdo->exec("DELETE FROM incidencia WHERE id_contenedor IN (SELECT id_contenedor FROM contenedor WHERE codigo LIKE 'TIDEM-%')");
$pdo->exec("DELETE FROM recoleccion WHERE id_camion IN (SELECT id_camion FROM camion WHERE matricula LIKE 'TIDEM-%')");
$pdo->exec("DELETE FROM camion WHERE matricula LIKE 'TIDEM-%'");
$pdo->exec("DELETE FROM ruta WHERE nombre LIKE 'TIDEM %'");
$pdo->exec("DELETE FROM contenedor WHERE codigo LIKE 'TIDEM-%'");
afirmar("Los datos de prueba de la idempotencia se borraron", [0, 0, 0, 0, 0], [
    (int) $pdo->query("SELECT COUNT(*) FROM contenedor WHERE codigo LIKE 'TIDEM-%'")->fetchColumn(), (int) $pdo->query("SELECT COUNT(*) FROM camion WHERE matricula LIKE 'TIDEM-%'")->fetchColumn(),
    (int) $pdo->query("SELECT COUNT(*) FROM ruta WHERE nombre LIKE 'TIDEM %'")->fetchColumn(), (int) $pdo->query("SELECT COUNT(*) FROM incidencia WHERE descripcion = 'Prueba de idempotencia'")->fetchColumn(),
    (int) $pdo->query("SELECT COUNT(*) FROM clave_idempotencia")->fetchColumn(),
]);
