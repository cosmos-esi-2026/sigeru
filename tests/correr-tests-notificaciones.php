<?php

// Parte de correr-tests.php (se incluye desde alli y usa sus funciones y sus sesiones).
// Avisos (extras, parte 13): la campanita. Todo lo que se crea lleva el prefijo TNOT y se borra al final;
// los avisos que genera la corrida se borran por fecha (como la auditoria).

titulo("29. Avisos: preparar los datos de prueba");

$pdo->exec("DELETE FROM incidencia WHERE id_contenedor IN (SELECT id_contenedor FROM contenedor WHERE codigo LIKE 'TNOT-%')");
$pdo->exec("DELETE FROM recoleccion WHERE id_camion IN (SELECT id_camion FROM camion WHERE matricula LIKE 'TNOT-%')");
$pdo->exec("DELETE FROM camion WHERE matricula LIKE 'TNOT-%'");
$pdo->exec("DELETE FROM ruta WHERE nombre LIKE 'TNOT %'");
$pdo->exec("DELETE FROM contenedor WHERE codigo LIKE 'TNOT-%'");
$pdo->exec("UPDATE usuario SET id_centro = NULL WHERE email LIKE 'tnot-%@test.uy'");
$pdo->exec("DELETE FROM centro_acopio WHERE nombre LIKE 'TNOT %'");
$pdo->exec("DELETE FROM usuario WHERE email LIKE 'tnot-%@test.uy' OR email = 'sin-correo-valido-$sufijo'");

$avisosDe = function (string $cookies, string $consulta = "limite=50"): array {
    [, $c] = peticion("GET", "/api-usuarios/notificaciones?$consulta", null, $cookies);
    return $c ?? [];
};
$deTipo = fn(array $respuesta, string $tipo, ?string $enlace = null) => array_values(array_filter($respuesta["datos"] ?? [], fn($n) => $n["tipo"] === $tipo && ($enlace === null || $n["enlace"] === $enlace)));

foreach (["GET /notificaciones", "POST /notificaciones/1/leida", "POST /notificaciones/leer-todas"] as $pedido) {
    [$metodo, $ruta] = explode(" ", $pedido);
    [$codigo] = peticion($metodo, "/api-usuarios$ruta", [], null);
    afirmar("$pedido sin sesion: 401", 401, $codigo);
}

titulo("29 bis. Avisos: limites de la lista");

foreach (["limite=0", "limite=abc", "limite=51", "limite=-1"] as $malo) {
    [$codigo] = peticion("GET", "/api-usuarios/notificaciones?$malo", null, $vecino);
    afirmar("Un limite invalido ($malo): 400", 400, $codigo);
}

titulo("29 ter. Avisos: una incidencia mueve los avisos de cada persona");

$baseAdmin = (int) ($avisosDe($admin)["no_leidas"] ?? 0);
[, $cuerpo] = peticion("POST", "/api-gestion/contenedores", ["codigo" => "TNOT-A$sufijo", "direccion" => "Calle de prueba de avisos 7",
    "capacidad" => 800, "tipo_residuo" => "Mixto", "id_zona" => 4], $admin);
[$codigo, $cuerpo] = peticion("POST", "/api-gestion/incidencias", ["codigo_contenedor" => "TNOT-A$sufijo", "tipo_problema" => "desbordado",
    "descripcion" => "Hay bolsas en la vereda", "prioridad" => "alta"], $vecino);
$idIncN = (int) ($cuerpo["datos"]["id_incidencia"] ?? 0);
$numInc = $cuerpo["datos"]["numero"] ?? "";
afirmar("El vecino informa una incidencia de prioridad alta", [201, true], [$codigo, $idIncN > 0]);
$criticas = $deTipo($avisosDe($admin), "incidencia_critica", "incidencia.html?id=$idIncN");
afirmar("Los administradores reciben un aviso de incidencia critica con el enlace a la incidencia", [true, "incidencia.html?id=$idIncN", false], [
    count($criticas) >= 1 && str_contains($criticas[0]["titulo"], $numInc), $criticas[0]["enlace"] ?? null, $criticas[0]["leida"] ?? null]);
afirmar("El contador de no leidas del administrador subio", true, (int) ($avisosDe($admin)["no_leidas"] ?? 0) > $baseAdmin);
afirmar("El vecino no recibe el aviso de critica", [], $deTipo($avisosDe($vecino), "incidencia_critica", "incidencia.html?id=$idIncN"));
afirmar("Ni la cuadrilla", [], $deTipo($avisosDe($cuadrilla), "incidencia_critica", "incidencia.html?id=$idIncN"));

peticion("POST", "/api-gestion/incidencias/$idIncN/asignacion", ["id_cuadrilla" => 1], $admin);
$aLuis = $deTipo($avisosDe($cuadrilla), "incidencia_asignada", "incidencia.html?id=$idIncN");
afirmar("Al asignarla, la cuadrilla recibe el aviso con el numero y la direccion", [true, "incidencia.html?id=$idIncN"], [
    count($aLuis) >= 1 && str_contains($aLuis[0]["mensaje"], $numInc) && str_contains($aLuis[0]["mensaje"], "Calle de prueba de avisos 7"), $aLuis[0]["enlace"] ?? null]);
afirmar("Otra cuadrilla no recibe nada de eso", [], $deTipo($avisosDe($otraCuadrilla), "incidencia_asignada", "incidencia.html?id=$idIncN"));
$enCurso = $deTipo($avisosDe($vecino), "incidencia_en_curso", "incidencia.html?id=$idIncN");
afirmar("El vecino que informo recibe: en curso", [true, "incidencia.html?id=$idIncN"], [count($enCurso) >= 1 && str_contains($enCurso[0]["titulo"], "en curso"), $enCurso[0]["enlace"] ?? null]);
afirmar("Y el mensaje no trae el nombre de la cuadrilla ni del personal", false, (bool) preg_match('/Cuadrilla (Norte|Sur)|Luis|Carla|Ana/', ($enCurso[0]["titulo"] ?? "") . " " . ($enCurso[0]["mensaje"] ?? "")));
[$codigo] = peticion("POST", "/api-gestion/incidencias/$idIncN/asignacion", ["id_cuadrilla" => 1], $admin);
afirmar("Pedir la misma asignacion otra vez (409) no genera avisos de mas", [409, 1, 1], [$codigo, count($deTipo($avisosDe($vecino), "incidencia_en_curso", "incidencia.html?id=$idIncN")), count($deTipo($avisosDe($cuadrilla), "incidencia_asignada", "incidencia.html?id=$idIncN"))]);


titulo("29 quater. Avisos: leer, marcar y que cada uno vea solo los suyos");

$delVecino = $avisosDe($vecino);
$idDeLuis = (int) ($aLuis[0]["id_notificacion"] ?? 0);
afirmar("El vecino tiene avisos sin leer", true, ($delVecino["no_leidas"] ?? 0) >= 1);
[$codigo] = peticion("POST", "/api-usuarios/notificaciones/$idDeLuis/leida", [], $vecino);
afirmar("Un vecino no puede marcar el aviso de otra persona: 404", 404, $codigo);
afirmar("Y ese aviso sigue sin leer", false, $deTipo($avisosDe($cuadrilla), "incidencia_asignada", "incidencia.html?id=$idIncN")[0]["leida"] ?? null);
[$codigo] = peticion("POST", "/api-usuarios/notificaciones/99999999/leida", [], $vecino);
afirmar("Un aviso que no existe: 404", 404, $codigo);
foreach ($avisosDe($vecino)["datos"] as $n) {
    afirmar("El vecino solo ve avisos propios (enlaces a lo suyo, sin datos ajenos)", true, !str_contains($n["mensaje"], "Calle de prueba de avisos"));
    break;
}
$antes = (int) ($avisosDe($cuadrilla)["no_leidas"] ?? 0);
[$codigo, $cuerpo] = peticion("POST", "/api-usuarios/notificaciones/$idDeLuis/leida", [], $cuadrilla);
afirmar("La cuadrilla marca el suyo: 200 y el contador baja", [200, $antes - 1], [$codigo, $cuerpo["no_leidas"] ?? null]);
[$codigo] = peticion("POST", "/api-usuarios/notificaciones/$idDeLuis/leida", [], $cuadrilla);
afirmar("Marcarlo otra vez no falla", 200, $codigo);
[, $cuerpo] = peticion("GET", "/api-usuarios/notificaciones?limite=1", null, $vecino);
afirmar("El limite se respeta", 1, count($cuerpo["datos"] ?? []));
[, $cuerpo] = peticion("GET", "/api-usuarios/notificaciones?solo_no_leidas=1", null, $cuadrilla);
afirmar("Solo no leidas: ninguna leida", [], array_values(array_filter($cuerpo["datos"] ?? [], fn($n) => $n["leida"])));
$adminAntes = (int) ($avisosDe($admin)["no_leidas"] ?? 0);
[$codigo, $cuerpo] = peticion("POST", "/api-usuarios/notificaciones/leer-todas", [], $vecino);
afirmar("El vecino marca todas como leidas: 200 y su contador en 0", [200, 0, 0], [$codigo, $cuerpo["no_leidas"] ?? null, $avisosDe($vecino)["no_leidas"] ?? null]);
afirmar("Y no toca los avisos de los demas", $adminAntes, (int) ($avisosDe($admin)["no_leidas"] ?? 0));

titulo("29 quinquies. Avisos: resolver, descartar y textos largos");

[$codigo] = peticion("POST", "/api-gestion/incidencias/$idIncN/resolucion", ["observacion" => "Se levanto todo"], $cuadrilla);
$resuelta = $deTipo($avisosDe($vecino), "incidencia_resuelta", "incidencia.html?id=$idIncN");
afirmar("La cuadrilla resuelve: el vecino recibe el aviso de resuelta", [200, true, false], [$codigo, count($resuelta) === 1 && str_contains($resuelta[0]["titulo"], "resuelto"), $resuelta[0]["leida"] ?? null]);

[, $cuerpo] = peticion("POST", "/api-gestion/incidencias", ["codigo_contenedor" => "TNOT-A$sufijo", "tipo_problema" => "roto", "descripcion" => "La tapa esta rota"], $vecino);
$idIncN2 = (int) ($cuerpo["datos"]["id_incidencia"] ?? 0);
[$codigo] = peticion("POST", "/api-gestion/incidencias/$idIncN2/descarte", ["observacion" => "Duplicado de otro reclamo"], $admin);
afirmar("El administrador descarta: 200 y el vecino recibe el aviso en la campanita", [200, 1], [$codigo, count($deTipo($avisosDe($vecino), "incidencia_descartada", "incidencia.html?id=$idIncN2"))]);

$hash = password_hash(CLAVE_DE_PRUEBA, PASSWORD_DEFAULT);
$pdo->prepare("INSERT INTO usuario (nombre, apellido, email, password_hash, id_rol, estado) VALUES ('Sin', 'Correo', ?, ?, (SELECT id_rol FROM rol WHERE nombre = 'Vecino'), 'activo')")
    ->execute(["sin-correo-valido-$sufijo", $hash]);
$idSinCorreo = (int) $pdo->lastInsertId();
require_once __DIR__ . "/../api-gestion/core/Notificador.php";
$fallo = false;
try {
    Notificador::avisar(99999999, "incidencia_resuelta", "Titulo", "Mensaje", null);
    Notificador::avisar($idSinCorreo, "x", str_repeat("a", 500), str_repeat("b", 900), "incidencia.html?id=1");
} catch (Throwable $e) {
    $fallo = true;
}
afirmar("Avisar a una persona que no existe, o con textos enormes, no lanza ningun error", false, $fallo);
afirmar("Los textos largos se cortan a los limites de la tabla", [Notificador::LARGO_TITULO, Notificador::LARGO_MENSAJE], [
    mb_strlen((string) $pdo->query("SELECT titulo FROM notificacion WHERE id_usuario = $idSinCorreo AND tipo = 'x'")->fetchColumn()),
    mb_strlen((string) $pdo->query("SELECT mensaje FROM notificacion WHERE id_usuario = $idSinCorreo AND tipo = 'x'")->fetchColumn()),
]);


titulo("29 sexies. Avisos: recolecciones y centros de acopio");

[, $cuerpo] = peticion("POST", "/api-recoleccion/camiones", ["matricula" => "TNOT-$sufijo", "capacidad" => 4000], $admin);
$idCamionN = (int) ($cuerpo["datos"]["id_camion"] ?? 0);
[, $cuerpo] = peticion("POST", "/api-recoleccion/rutas", ["nombre" => "TNOT Ruta $sufijo", "dia_semana" => "martes", "hora_inicio" => "06:15", "id_zona" => 2], $admin);
$idRutaN = (int) ($cuerpo["datos"]["id_ruta"] ?? 0);
[, $cuerpo] = peticion("POST", "/api-gestion/centros", ["nombre" => "TNOT Centro $sufijo", "capacidad" => 1000], $admin);
$idCentroN = (int) ($cuerpo["datos"]["id_centro"] ?? 0);
$pdo->prepare("INSERT INTO usuario (nombre, apellido, email, password_hash, id_rol, id_centro, estado) VALUES ('Tnot', 'Operario', ?, ?, (SELECT id_rol FROM rol WHERE nombre = 'Operario'), ?, 'activo')")
    ->execute(["tnot-op-$sufijo@test.uy", $hash, $idCentroN]);
$idOpN = (int) $pdo->lastInsertId();
$opN = sys_get_temp_dir() . "/sigeru_test_opn.txt";
iniciarSesion("tnot-op-$sufijo@test.uy", $opN);

$planN = ["id_ruta" => $idRutaN, "id_camion" => $idCamionN, "id_cuadrilla" => 1, "fecha_hora_inicio" => "2026-10-21T06:30", "id_centro" => $idCentroN];
[$codigo, $cuerpo] = peticion("POST", "/api-recoleccion/recolecciones", $planN, $admin);
$idRecN = (int) ($cuerpo["datos"]["id_recoleccion"] ?? 0);
$recAvisos = $deTipo($avisosDe($cuadrilla), "recoleccion_asignada", "recoleccion.html?id=$idRecN");
afirmar("Al planificar una recoleccion, la cuadrilla recibe el aviso con numero, ruta y fecha", [201, true, true], [
    $codigo, count($recAvisos) === 1 && str_contains($recAvisos[0]["mensaje"], "TNOT Ruta"), str_contains($recAvisos[0]["mensaje"] ?? "", "21/10/2026 06:30")]);
afirmar("Otra cuadrilla no recibe nada", [], $deTipo($avisosDe($otraCuadrilla), "recoleccion_asignada", "recoleccion.html?id=$idRecN"));
[$codigo] = peticion("POST", "/api-recoleccion/recolecciones", array_merge($planN, ["id_ruta" => 99999]), $admin);
afirmar("Una planificacion que falla (404) no deja avisos", [404, 1], [$codigo, count($deTipo($avisosDe($cuadrilla), "recoleccion_asignada", "recoleccion.html?id=$idRecN"))]);

peticion("POST", "/api-gestion/contenedores", ["codigo" => "TNOT-B$sufijo", "direccion" => "Contenedor de prueba de avisos", "capacidad" => 1100, "tipo_residuo" => "Mixto", "id_zona" => 2], $admin);
peticion("POST", "/api-recoleccion/recolecciones/$idRecN/inicio", [], $cuadrilla);
peticion("POST", "/api-recoleccion/recolecciones/$idRecN/vaciados", ["codigo_contenedor" => "TNOT-B$sufijo", "peso_kg" => 700], $cuadrilla);
$adminAntes = count($deTipo($avisosDe($admin), "centro_alerta", "centro.html?id=$idCentroN"));
[$codigo, $cuerpo] = peticion("POST", "/api-gestion/recepciones/$idRecN", ["lineas" => [["id_tipo_residuo" => 6, "kg" => 500]]], $opN);
afirmar("Recibir 500 kg en un centro de 1000 kg (50 %): no hay aviso", [201, 0], [$codigo, count($deTipo($avisosDe($opN), "centro_alerta", "centro.html?id=$idCentroN"))]);
peticion("POST", "/api-recoleccion/recolecciones/$idRecN/fin", [], $cuadrilla);
[, $cuerpo] = peticion("POST", "/api-recoleccion/recolecciones", array_merge($planN, ["fecha_hora_inicio" => "2026-10-22T06:30"]), $admin);
$idRecN2 = (int) ($cuerpo["datos"]["id_recoleccion"] ?? 0);
peticion("POST", "/api-recoleccion/recolecciones/$idRecN2/inicio", [], $cuadrilla);
peticion("POST", "/api-recoleccion/recolecciones/$idRecN2/vaciados", ["codigo_contenedor" => "TNOT-B$sufijo", "peso_kg" => 400], $cuadrilla);
[$codigo] = peticion("POST", "/api-gestion/recepciones/$idRecN2", ["lineas" => [["id_tipo_residuo" => 6, "kg" => 350]]], $opN);
$alertaOp = $deTipo($avisosDe($opN), "centro_alerta", "centro.html?id=$idCentroN");
$alertaAdmin = $deTipo($avisosDe($admin), "centro_alerta", "centro.html?id=$idCentroN");
afirmar("Al pasar del 80 % (85 %) avisan al operario del centro y a los administradores, con el porcentaje", [201, true, true, $adminAntes + 1], [
    $codigo, count($alertaOp) === 1 && str_contains($alertaOp[0]["mensaje"], "85"), str_contains($alertaOp[0]["titulo"] ?? "", "TNOT Centro"), count($alertaAdmin)]);
afirmar("El operario de otro centro (el del seed) no recibe ese aviso", [], $deTipo($avisosDe($operario), "centro_alerta", "centro.html?id=$idCentroN"));
peticion("POST", "/api-recoleccion/recolecciones/$idRecN2/fin", [], $cuadrilla);
[, $cuerpo] = peticion("POST", "/api-recoleccion/recolecciones", array_merge($planN, ["fecha_hora_inicio" => "2026-10-23T06:30"]), $admin);
$idRecN3 = (int) ($cuerpo["datos"]["id_recoleccion"] ?? 0);
peticion("POST", "/api-recoleccion/recolecciones/$idRecN3/inicio", [], $cuadrilla);
peticion("POST", "/api-recoleccion/recolecciones/$idRecN3/vaciados", ["codigo_contenedor" => "TNOT-B$sufijo", "peso_kg" => 20], $cuadrilla);
peticion("POST", "/api-gestion/recepciones/$idRecN3", ["lineas" => [["id_tipo_residuo" => 6, "kg" => 10]]], $opN);
afirmar("Una recepcion mas, ya por encima del 80 %, no repite el aviso", 1, count($deTipo($avisosDe($opN), "centro_alerta", "centro.html?id=$idCentroN")));
peticion("POST", "/api-recoleccion/recolecciones/$idRecN3/fin", [], $cuadrilla);


titulo("29 septies. Avisos: limpieza y datos del seed intactos");

$pdo->exec("DELETE FROM incidencia WHERE id_contenedor IN (SELECT id_contenedor FROM contenedor WHERE codigo LIKE 'TNOT-%')");
$pdo->exec("DELETE FROM recoleccion WHERE id_camion IN (SELECT id_camion FROM camion WHERE matricula LIKE 'TNOT-%')");
$pdo->exec("DELETE FROM camion WHERE matricula LIKE 'TNOT-%'");
$pdo->exec("DELETE FROM ruta WHERE nombre LIKE 'TNOT %'");
$pdo->exec("DELETE FROM contenedor WHERE codigo LIKE 'TNOT-%'");
$pdo->exec("UPDATE usuario SET id_centro = NULL WHERE email LIKE 'tnot-%@test.uy'");
$pdo->exec("DELETE FROM centro_acopio WHERE nombre LIKE 'TNOT %'");
$pdo->exec("DELETE FROM usuario WHERE email LIKE 'tnot-%@test.uy' OR email = 'sin-correo-valido-$sufijo'");
$pdo->prepare("DELETE FROM notificacion WHERE fecha_hora >= ?")->execute([$inicioDeLaCorrida]);
foreach ([$opN] as $archivo) {
    if (file_exists($archivo)) {
        unlink($archivo);
    }
}
afirmar("No quedan avisos de la corrida ni datos de prueba", [0, 0, 0, 0], [
    (int) $pdo->query("SELECT COUNT(*) FROM notificacion")->fetchColumn(),
    (int) $pdo->query("SELECT COUNT(*) FROM usuario WHERE email LIKE 'tnot-%@test.uy' OR email LIKE 'sin-correo-valido-%'")->fetchColumn(),
    (int) $pdo->query("SELECT COUNT(*) FROM contenedor WHERE codigo LIKE 'TNOT-%'")->fetchColumn(),
    (int) $pdo->query("SELECT COUNT(*) FROM centro_acopio WHERE nombre LIKE 'TNOT %'")->fetchColumn(),
]);
