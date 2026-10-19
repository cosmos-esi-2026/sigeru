<?php

// Parte de correr-tests.php (se incluye desde alli y usa sus funciones y sus sesiones).
// Operario con trabajo real (extras, parte 12). Todo lo que se crea lleva el prefijo TOPE: dos centros,
// dos usuarios, un camion, una ruta, dos contenedores y una maquina. Al final se borra el centro (la
// cascada lleva sus movimientos y recepciones, que son de solo agregado) y el resto.

titulo("24. Operario: preparar los datos de prueba");

$pdo->exec("DELETE FROM maquinaria WHERE codigo LIKE 'TOPE-%'");
$pdo->exec("DELETE FROM recoleccion WHERE id_camion IN (SELECT id_camion FROM camion WHERE matricula LIKE 'TOPE-%')");
$pdo->exec("DELETE FROM camion WHERE matricula LIKE 'TOPE-%'");
$pdo->exec("DELETE FROM ruta WHERE nombre LIKE 'TOPE %'");
$pdo->exec("DELETE FROM contenedor WHERE codigo LIKE 'TOPE-%'");
$pdo->exec("UPDATE usuario SET id_centro = NULL WHERE email LIKE 'tope-%@test.uy'");
$pdo->exec("DELETE FROM centro_acopio WHERE nombre LIKE 'TOPE %'");
$pdo->exec("DELETE FROM usuario WHERE email LIKE 'tope-%@test.uy'");

$conteoSeed = [
    "movimiento_stock" => (int) $pdo->query("SELECT COUNT(*) FROM movimiento_stock")->fetchColumn(),
    "recepcion" => (int) $pdo->query("SELECT COUNT(*) FROM recepcion")->fetchColumn(),
    "historial_maquinaria" => (int) $pdo->query("SELECT COUNT(*) FROM historial_maquinaria")->fetchColumn(),
];

[$codigo, $cuerpo] = peticion("POST", "/api-gestion/centros", ["nombre" => "TOPE Centro $sufijo", "capacidad" => 1000], $admin);
$idCentro = (int) ($cuerpo["datos"]["id_centro"] ?? 0);
afirmar("El administrador crea un centro de prueba", [201, "activo"], [$codigo, $cuerpo["datos"]["estado"] ?? null]);
[, $cuerpo] = peticion("POST", "/api-gestion/centros", ["nombre" => "TOPE Otro $sufijo", "capacidad" => 500], $admin);
$idCentroB = (int) ($cuerpo["datos"]["id_centro"] ?? 0);

$hash = password_hash(CLAVE_DE_PRUEBA, PASSWORD_DEFAULT);
$crearUsuario = function (string $email, ?int $centro) use ($pdo, $hash): int {
    $pdo->prepare("INSERT INTO usuario (nombre, apellido, email, password_hash, id_rol, id_centro, estado)
                   VALUES ('Tope', 'Operario', ?, ?, (SELECT id_rol FROM rol WHERE nombre = 'Operario'), ?, 'activo')")
        ->execute([$email, $hash, $centro]);
    return (int) $pdo->lastInsertId();
};
$idOpTope = $crearUsuario("tope-op-$sufijo@test.uy", $idCentro);
$idOpSinCentro = $crearUsuario("tope-sin-$sufijo@test.uy", null);
$opTope = sys_get_temp_dir() . "/sigeru_test_optope.txt";
$opSin = sys_get_temp_dir() . "/sigeru_test_opsin.txt";
iniciarSesion("tope-op-$sufijo@test.uy", $opTope);
iniciarSesion("tope-sin-$sufijo@test.uy", $opSin);
[, $cuerpo] = peticion("GET", "/api-usuarios/sesion", null, $opTope);
afirmar("La sesion del Operario trae su centro", [$idCentro, "Operario"], [(int) ($cuerpo["datos"]["id_centro"] ?? 0), $cuerpo["datos"]["rol"] ?? null]);


titulo("24 bis. Operario: quien puede y a que centro llega");

foreach (["GET /recepciones", "GET /centros/$idCentro/stock", "GET /centros/$idCentro/movimientos", "GET /estadisticas/centro?centro=$idCentro",
          "GET /maquinaria/1/historial"] as $pedido) {
    [$metodo, $ruta] = explode(" ", $pedido);
    $obtenidos = [];
    foreach (["sin sesion" => null, "vecino" => $vecino, "cuadrilla" => $cuadrilla] as $quien => $cookies) {
        [$obtenidos[$quien]] = peticion($metodo, "/api-gestion$ruta", null, $cookies);
    }
    afirmar("$pedido: sin sesion 401, vecino y cuadrilla 403", ["sin sesion" => 401, "vecino" => 403, "cuadrilla" => 403], $obtenidos);
}
foreach (["POST /recepciones/1", "POST /centros/$idCentro/movimientos", "POST /centros/$idCentro/movimientos/1/correccion", "POST /maquinaria/1/estado"] as $pedido) {
    [$metodo, $ruta] = explode(" ", $pedido);
    $obtenidos = [];
    foreach (["sin sesion" => null, "vecino" => $vecino, "cuadrilla" => $cuadrilla] as $quien => $cookies) {
        [$obtenidos[$quien]] = peticion($metodo, "/api-gestion$ruta", ["x" => 1], $cookies);
    }
    afirmar("$pedido: sin sesion 401, vecino y cuadrilla 403", ["sin sesion" => 401, "vecino" => 403, "cuadrilla" => 403], $obtenidos);
}

[, $cuerpo] = peticion("GET", "/api-gestion/centros", null, $operario);
afirmar("El Operario del seed ve solo su centro (el 1) en la lista", [1], array_map("intval", array_column($cuerpo["datos"] ?? [], "id_centro")));
[, $cuerpo] = peticion("GET", "/api-gestion/centros", null, $opTope);
afirmar("El Operario de prueba ve solo el suyo", [$idCentro], array_map("intval", array_column($cuerpo["datos"] ?? [], "id_centro")));
[, $cuerpo] = peticion("GET", "/api-gestion/centros", null, $admin);
$idsAdmin = array_map("intval", array_column($cuerpo["datos"] ?? [], "id_centro"));
afirmar("El administrador ve todos", true, in_array($idCentro, $idsAdmin, true) && in_array($idCentroB, $idsAdmin, true) && in_array(1, $idsAdmin, true));
afirmar("Cada centro trae su stock, su ocupacion y su nivel", true, count(array_filter($cuerpo["datos"] ?? [], fn($c) => array_key_exists("stock_kg", $c) && array_key_exists("ocupacion_pct", $c) && isset($c["nivel"]))) === count($cuerpo["datos"] ?? []));
foreach (["GET /centros/$idCentro", "GET /centros/$idCentro/stock", "GET /centros/$idCentro/movimientos", "GET /recepciones/1"] as $pedido) {
    [$metodo, $ruta] = explode(" ", $pedido);
    [$codigo] = peticion($metodo, "/api-gestion$ruta", null, $operario);
    afirmar("El Operario del seed no ve lo de otro centro ($pedido): 404", 404, $codigo);
}
[$codigo] = peticion("PUT", "/api-gestion/centros/$idCentro", ["capacidad" => 5], $operario);
afirmar("Ni edita otro centro: 404", 404, $codigo);
[$codigo] = peticion("POST", "/api-gestion/centros/$idCentro/movimientos", ["id_tipo_residuo" => 1, "kg" => 1, "motivo" => "venta"], $operario);
afirmar("Ni retira de otro centro: 404", 404, $codigo);
[$codigo] = peticion("PUT", "/api-gestion/centros/$idCentro", ["capacidad" => 1200], $opTope);
afirmar("El Operario edita su propio centro", 200, $codigo);
peticion("PUT", "/api-gestion/centros/$idCentro", ["capacidad" => 1000], $admin);
[$codigo, $cuerpo] = peticion("GET", "/api-gestion/centros", null, $opSin);
afirmar("Un Operario sin centro asignado: 403 con un mensaje claro", [403, true], [$codigo, str_contains($cuerpo["mensaje"] ?? "", "centro de acopio asignado")]);
[$codigo] = peticion("GET", "/api-gestion/recepciones", null, $opSin);
afirmar("Sin centro tampoco ve recepciones: 403", 403, $codigo);
[$codigo] = peticion("GET", "/api-gestion/estadisticas/centro", null, $admin);
afirmar("El resumen del administrador pide ?centro=: 400", 400, $codigo);


titulo("25. Operario: recibir lo que trae una recoleccion");

[, $cuerpo] = peticion("POST", "/api-recoleccion/camiones", ["matricula" => "TOPE-$sufijo", "capacidad" => 4000], $admin);
$idCamion = (int) ($cuerpo["datos"]["id_camion"] ?? 0);
[, $cuerpo] = peticion("POST", "/api-recoleccion/rutas", ["nombre" => "TOPE Ruta $sufijo", "dia_semana" => "martes", "hora_inicio" => "06:15", "id_zona" => 2], $admin);
$idRuta = (int) ($cuerpo["datos"]["id_ruta"] ?? 0);
foreach (["A" => "Mixto", "B" => "Vidrio"] as $letra => $tipo) {
    peticion("POST", "/api-gestion/contenedores", ["codigo" => "TOPE-$letra$sufijo", "direccion" => "Contenedor de prueba de recepcion",
        "capacidad" => 1100, "tipo_residuo" => $tipo, "id_zona" => 2], $admin);
}
$plan = ["id_ruta" => $idRuta, "id_camion" => $idCamion, "id_cuadrilla" => 1, "fecha_hora_inicio" => "2026-10-21T06:30", "id_centro" => $idCentro];
[, $cuerpo] = peticion("POST", "/api-recoleccion/recolecciones", $plan, $admin);
$idRec = (int) ($cuerpo["datos"]["id_recoleccion"] ?? 0);

[, $cuerpo] = peticion("GET", "/api-gestion/recepciones?estado=pendiente", null, $opTope);
afirmar("Una recoleccion solo planificada todavia no se recibe: no figura", false, in_array($idRec, array_column($cuerpo["datos"] ?? [], "id_recoleccion"), true));
[$codigo] = peticion("GET", "/api-gestion/recepciones/$idRec", null, $opTope);
afirmar("Ni abre su detalle: 404", 404, $codigo);
[$codigo, $cuerpo] = peticion("POST", "/api-gestion/recepciones/$idRec", ["lineas" => [["id_tipo_residuo" => 6, "kg" => 10]]], $opTope);
afirmar("Ni se puede recibir: 409 (todavia no salio)", [409, true], [$codigo, str_contains($cuerpo["mensaje"] ?? "", "todavía no salió")]);

peticion("POST", "/api-recoleccion/recolecciones/$idRec/inicio", [], $cuadrilla);
peticion("POST", "/api-recoleccion/recolecciones/$idRec/vaciados", ["codigo_contenedor" => "TOPE-A$sufijo", "peso_kg" => 100], $cuadrilla);
peticion("POST", "/api-recoleccion/recolecciones/$idRec/vaciados", ["codigo_contenedor" => "TOPE-B$sufijo", "peso_kg" => 50], $cuadrilla);

[$codigo, $cuerpo] = peticion("GET", "/api-gestion/recepciones?estado=pendiente", null, $opTope);
$fila = null;
foreach ($cuerpo["datos"] ?? [] as $f) {
    if ((int) $f["id_recoleccion"] === $idRec) { $fila = $f; }
}
afirmar("En curso y con vaciados: figura como pendiente, con lo esperado", [200, true, 150.0, false, "Centro"], [
    $codigo, $fila !== null, isset($fila["peso_esperado_kg"]) ? (float) $fila["peso_esperado_kg"] : null, $fila["recibida"] ?? null, null === $fila ? null : ($fila["centro"] !== "" ? "Centro" : "")]);
[, $cuerpo] = peticion("GET", "/api-gestion/recepciones", null, $operario);
afirmar("El Operario de otro centro no la ve", false, in_array($idRec, array_column($cuerpo["datos"] ?? [], "id_recoleccion"), true));
[, $cuerpo] = peticion("GET", "/api-gestion/recepciones?centro=$idCentro", null, $admin);
afirmar("El administrador la ve filtrando por centro", true, in_array($idRec, array_column($cuerpo["datos"] ?? [], "id_recoleccion"), true));
[$codigo] = peticion("GET", "/api-gestion/recepciones?estado=cualquiera", null, $admin);
afirmar("Un estado inventado: 400", 400, $codigo);
[$codigo] = peticion("GET", "/api-gestion/recepciones?centro=abc", null, $admin);
afirmar("Un centro inventado: 400", 400, $codigo);

[$codigo, $cuerpo] = peticion("GET", "/api-gestion/recepciones/$idRec", null, $opTope);
$esperado = array_map("floatval", array_column($cuerpo["datos"]["esperado_por_tipo"] ?? [], "kg", "tipo"));
afirmar("El detalle muestra lo esperado por tipo de residuo", [200, ["Mixto" => 100.0, "Vidrio" => 50.0]], [$codigo, $esperado]);
[$codigo] = peticion("GET", "/api-gestion/recepciones/$idRec", null, $operario);
afirmar("El Operario de otro centro no abre el detalle: 404", 404, $codigo);

$base = "/api-gestion/recepciones/$idRec";
foreach ([
    "Sin lineas"             => ["lineas" => []],
    "Sin el campo lineas"    => ["observacion" => "x"],
    "Un tipo que no existe"  => ["lineas" => [["id_tipo_residuo" => 999, "kg" => 5]]],
    "Un peso de cero"        => ["lineas" => [["id_tipo_residuo" => 6, "kg" => 0]]],
    "Un peso negativo"       => ["lineas" => [["id_tipo_residuo" => 6, "kg" => -3]]],
    "Un peso con texto"      => ["lineas" => [["id_tipo_residuo" => 6, "kg" => "mucho"]]],
    "Un tipo repetido"       => ["lineas" => [["id_tipo_residuo" => 6, "kg" => 1], ["id_tipo_residuo" => 6, "kg" => 2]]],
    "Una nota enorme"        => ["lineas" => [["id_tipo_residuo" => 6, "kg" => 1]], "observacion" => str_repeat("a", 301)],
] as $nombre => $cuerpoMalo) {
    [$codigo] = peticion("POST", $base, $cuerpoMalo, $opTope);
    afirmar("Recibir con datos malos ($nombre): 400", 400, $codigo);
}
[$codigo] = peticion("POST", $base, ["lineas" => [["id_tipo_residuo" => 6, "kg" => 100]]], $operario);
afirmar("El Operario de otro centro no la recibe: 404", 404, $codigo);
[$codigo] = peticion("POST", "/api-gestion/recepciones/99999", ["lineas" => [["id_tipo_residuo" => 6, "kg" => 1]]], $opTope);
afirmar("Una recoleccion que no existe: 404", 404, $codigo);

[$codigo, $cuerpo] = peticion("POST", $base, ["lineas" => [["id_tipo_residuo" => 6, "kg" => 100], ["id_tipo_residuo" => 3, "kg" => "50.00"]], "observacion" => "Todo en orden"], $opTope);
afirmar("Recibir: 201, queda recibida con lo recibido por tipo y sin aviso", [201, true, 150.0, "Todo en orden", null], [
    $codigo, $cuerpo["datos"]["recibida"] ?? null, (float) ($cuerpo["datos"]["peso_recibido_kg"] ?? -1), $cuerpo["datos"]["observacion_recepcion"] ?? null, array_key_exists("aviso", $cuerpo ?? []) ? $cuerpo["aviso"] : "falta"]);
[$codigo, $cuerpo] = peticion("POST", $base, ["lineas" => [["id_tipo_residuo" => 6, "kg" => 1]]], $opTope);
afirmar("Recibirla otra vez: 409", [409, true], [$codigo, str_contains($cuerpo["mensaje"] ?? "", "ya fue recibida")]);
[, $cuerpo] = peticion("GET", "/api-gestion/recepciones?estado=pendiente", null, $opTope);
afirmar("Ya no esta entre las pendientes", false, in_array($idRec, array_column($cuerpo["datos"] ?? [], "id_recoleccion"), true));
[, $cuerpo] = peticion("GET", "/api-gestion/recepciones?estado=recibida", null, $opTope);
afirmar("Esta entre las recibidas", true, in_array($idRec, array_column($cuerpo["datos"] ?? [], "id_recoleccion"), true));

[$codigo, $cuerpo] = peticion("GET", "/api-gestion/centros/$idCentro/stock", null, $opTope);
$saldos = array_map("floatval", array_column($cuerpo["datos"]["saldos"] ?? [], "kg", "tipo"));
afirmar("El stock subio: 100 de Mixto, 50 de Vidrio, 150 en total, 15 % y nivel normal", [200, 100.0, 50.0, 0.0, 150.0, 15.0, "normal"], [
    $codigo, $saldos["Mixto"] ?? null, $saldos["Vidrio"] ?? null, $saldos["Papel"] ?? null,
    (float) ($cuerpo["datos"]["stock_kg"] ?? -1), (float) ($cuerpo["datos"]["ocupacion_pct"] ?? -1), $cuerpo["datos"]["nivel"] ?? null]);
peticion("POST", "/api-recoleccion/recolecciones/$idRec/fin", [], $cuadrilla);


titulo("26 quinquies. Operario: retiros y correcciones");

$mov = "/api-gestion/centros/$idCentro/movimientos";
$saldoDe = function (string $tipo) use ($idCentro, $opTope): float {
    [, $c] = peticion("GET", "/api-gestion/centros/$idCentro/stock", null, $opTope);
    return (float) (array_column($c["datos"]["saldos"] ?? [], "kg", "tipo")[$tipo] ?? -1);
};
foreach ([
    "Sin motivo"            => ["id_tipo_residuo" => 6, "kg" => 5],
    "Un motivo inventado"   => ["id_tipo_residuo" => 6, "kg" => 5, "motivo" => "regalo"],
    "Un peso de cero"       => ["id_tipo_residuo" => 6, "kg" => 0, "motivo" => "venta"],
    "Un peso con texto"     => ["id_tipo_residuo" => 6, "kg" => "x", "motivo" => "venta"],
    "Un tipo que no existe" => ["id_tipo_residuo" => 999, "kg" => 5, "motivo" => "venta"],
    "Sin tipo"              => ["kg" => 5, "motivo" => "venta"],
    "Una nota enorme"       => ["id_tipo_residuo" => 6, "kg" => 5, "motivo" => "venta", "observacion" => str_repeat("a", 201)],
] as $nombre => $malo) {
    [$codigo] = peticion("POST", $mov, $malo, $opTope);
    afirmar("Retirar con datos malos ($nombre): 400", 400, $codigo);
}
[$codigo, $cuerpo] = peticion("POST", $mov, ["id_tipo_residuo" => 6, "kg" => 30, "motivo" => "venta", "observacion" => "Venta de prueba"], $opTope);
$idRetiro = (int) ($cuerpo["datos"]["id_movimiento"] ?? 0);
afirmar("Retirar 30 kg de Mixto: 201, es una salida por venta", [201, "salida", "venta", 30.0], [
    $codigo, $cuerpo["datos"]["sentido"] ?? null, $cuerpo["datos"]["motivo"] ?? null, (float) ($cuerpo["datos"]["kg"] ?? -1)]);
afirmar("El stock de Mixto bajo a 70", 70.0, $saldoDe("Mixto"));
[$codigo, $cuerpo] = peticion("POST", $mov, ["id_tipo_residuo" => 6, "kg" => 71, "motivo" => "traslado"], $opTope);
afirmar("Retirar mas de lo que hay: 409 y no cambia nada", [409, 70.0], [$codigo, $saldoDe("Mixto")]);
[$codigo] = peticion("POST", $mov, ["id_tipo_residuo" => 2, "kg" => 1, "motivo" => "disposicion"], $opTope);
afirmar("Retirar de un tipo sin stock (Plastico): 409", 409, $codigo);

[$codigo] = peticion("POST", "$mov/$idRetiro/correccion", [], $opTope);
afirmar("Corregir sin decir por que: 400", 400, $codigo);
[$codigo] = peticion("POST", "$mov/$idRetiro/correccion", ["observacion" => "Se anoto de mas"], $operario);
afirmar("El Operario de otro centro no corrige: 404", 404, $codigo);
[$codigo] = peticion("POST", "$mov/99999999/correccion", ["observacion" => "x"], $opTope);
afirmar("Corregir un movimiento que no existe: 404", 404, $codigo);
[$codigo, $cuerpo] = peticion("POST", "$mov/$idRetiro/correccion", ["observacion" => "Se anoto de mas"], $opTope);
$idCorreccion = (int) ($cuerpo["datos"]["id_movimiento"] ?? 0);
afirmar("Corregir el retiro: 201, es una entrada inversa por correccion", [201, "entrada", "correccion", 30.0, $idRetiro], [
    $codigo, $cuerpo["datos"]["sentido"] ?? null, $cuerpo["datos"]["motivo"] ?? null, (float) ($cuerpo["datos"]["kg"] ?? -1), $cuerpo["datos"]["id_corregido"] ?? null]);
afirmar("El stock de Mixto volvio a 100", 100.0, $saldoDe("Mixto"));
[$codigo] = peticion("POST", "$mov/$idRetiro/correccion", ["observacion" => "Otra vez"], $opTope);
afirmar("Corregirlo dos veces: 409", 409, $codigo);
[$codigo] = peticion("POST", "$mov/$idCorreccion/correccion", ["observacion" => "Corregir la correccion"], $opTope);
afirmar("Una correccion no se corrige: 409", 409, $codigo);

peticion("POST", $mov, ["id_tipo_residuo" => 6, "kg" => 80, "motivo" => "disposicion"], $opTope);
[, $cuerpo] = peticion("GET", "$mov?sentido=entrada", null, $opTope);
$idEntradaMixto = 0;
$idEntradaVidrio = 0;
$recDeEntrada = "";
foreach ($cuerpo["datos"] ?? [] as $m) {
    if ($m["motivo"] === "recepcion" && $m["tipo"] === "Mixto") { $idEntradaMixto = $m["id_movimiento"]; $recDeEntrada = (string) $m["recoleccion"]; }
    if ($m["motivo"] === "recepcion" && $m["tipo"] === "Vidrio") { $idEntradaVidrio = $m["id_movimiento"]; }
}
afirmar("La recepcion dejo un movimiento por tipo y dice de que recoleccion salio", true, $idEntradaMixto > 0 && $idEntradaVidrio > 0 && str_starts_with($recDeEntrada, "REC-"));
[$codigo] = peticion("POST", "$mov/$idEntradaMixto/correccion", ["observacion" => "Error de pesaje"], $opTope);
afirmar("Corregir una entrada cuando ya salio parte de ese stock: 409", 409, $codigo);
[$codigo] = peticion("POST", "$mov/$idEntradaVidrio/correccion", ["observacion" => "Error de pesaje"], $opTope);
afirmar("Corregir la entrada de Vidrio (nada salio): 201 y el Vidrio queda en 0", [201, 0.0], [$codigo, $saldoDe("Vidrio")]);


titulo("26 sexies. Operario: aviso del 80 %, resumen e historial");

[, $cuerpo] = peticion("POST", "/api-recoleccion/recolecciones", array_merge($plan, ["fecha_hora_inicio" => "2026-10-22T06:30"]), $admin);
$idRec2 = (int) ($cuerpo["datos"]["id_recoleccion"] ?? 0);
peticion("POST", "/api-recoleccion/recolecciones/$idRec2/inicio", [], $cuadrilla);
peticion("POST", "/api-recoleccion/recolecciones/$idRec2/vaciados", ["codigo_contenedor" => "TOPE-A$sufijo", "peso_kg" => 40], $cuadrilla);
[$codigo, $cuerpo] = peticion("POST", "/api-gestion/recepciones/$idRec2", ["lineas" => [["id_tipo_residuo" => 6, "kg" => 820]]], $opTope);
afirmar("Recibir 820 kg deja el centro sobre el 80 %: 201 con aviso", [201, true], [$codigo, str_contains($cuerpo["aviso"] ?? "", "84")]);
[, $cuerpo] = peticion("GET", "/api-gestion/centros", null, $admin);
$filaCentro = array_values(array_filter($cuerpo["datos"] ?? [], fn($c) => (int) $c["id_centro"] === $idCentro))[0] ?? [];
afirmar("La lista de centros marca el nivel de alerta: 840 kg, 84 %", [840.0, 84.0, "alerta"], [(float) ($filaCentro["stock_kg"] ?? -1), (float) ($filaCentro["ocupacion_pct"] ?? -1), $filaCentro["nivel"] ?? null]);
peticion("POST", "/api-recoleccion/recolecciones/$idRec2/fin", [], $cuadrilla);

[, $cuerpo] = peticion("POST", "/api-recoleccion/recolecciones", array_merge($plan, ["fecha_hora_inicio" => "2026-10-23T06:30"]), $admin);
$idRec3 = (int) ($cuerpo["datos"]["id_recoleccion"] ?? 0);
peticion("POST", "/api-recoleccion/recolecciones/$idRec3/inicio", [], $cuadrilla);
[$codigo, $cuerpo] = peticion("GET", "/api-gestion/estadisticas/centro", null, $opTope);
afirmar("El resumen del Operario: 1 recepcion pendiente, 84 % y nivel alerta, sin maquinas en mantenimiento", [200, 1, 84.0, "alerta", 0], [
    $codigo, $cuerpo["datos"]["recepciones_pendientes"] ?? null, (float) ($cuerpo["datos"]["ocupacion_pct"] ?? -1), $cuerpo["datos"]["nivel"] ?? null,
    $cuerpo["datos"]["maquinas_en_mantenimiento"] ?? null]);
[, $cuerpo] = peticion("GET", "/api-gestion/estadisticas/centro", null, $operario);
afirmar("El resumen del Operario del seed es el de su centro, no el de otro", 1, $cuerpo["datos"]["id_centro"] ?? null);
[, $cuerpo] = peticion("GET", "/api-gestion/estadisticas/centro?centro=$idCentro", null, $admin);
afirmar("El administrador pide el centro que quiere", $idCentro, $cuerpo["datos"]["id_centro"] ?? null);
[$codigo] = peticion("GET", "/api-gestion/estadisticas/centro?centro=99999", null, $admin);
afirmar("Un centro que no existe: 404", 404, $codigo);

[$codigo, $cuerpo] = peticion("GET", "$mov?pagina=1&por_pagina=3", null, $opTope);
afirmar("El historial pagina: 3 filas y el total en meta", [200, 3, true], [$codigo, count($cuerpo["datos"] ?? []), ($cuerpo["meta"]["total"] ?? 0) >= 7]);
[$codigo] = peticion("GET", "$mov?sentido=lateral", null, $opTope);
afirmar("Un sentido inventado: 400", 400, $codigo);
[, $cuerpo] = peticion("GET", "$mov?sentido=salida", null, $opTope);
afirmar("Filtrar por salidas", ["salida"], array_values(array_unique(array_column($cuerpo["datos"] ?? [], "sentido"))));
[, $cuerpo] = peticion("GET", $mov, null, $opTope);
$conCorregido = array_values(array_filter($cuerpo["datos"] ?? [], fn($m) => $m["id_movimiento"] === $idRetiro))[0] ?? [];
afirmar("El retiro corregido figura como corregido", true, $conCorregido["corregido"] ?? null);

foreach (["UPDATE movimiento_stock SET kg = 1 WHERE id_movimiento = $idRetiro", "DELETE FROM movimiento_stock WHERE id_movimiento = $idRetiro"] as $sql) {
    try {
        $pdo->exec($sql);
        $resultado = "se pudo";
    } catch (PDOException $e) {
        $resultado = str_contains($e->getMessage(), "solo agregado") ? "bloqueado" : $e->getMessage();
    }
    afirmar("Un movimiento de stock no se edita ni se borra, ni por fuera de la API (" . substr($sql, 0, 6) . ")", "bloqueado", $resultado);
}


titulo("27. Operario: estado de la maquinaria con historial");

[$codigo, $cuerpo] = peticion("POST", "/api-gestion/maquinaria", ["codigo" => "TOPE-M$sufijo", "tipo" => "compactadora", "id_centro" => $idCentro], $admin);
$idMaq = (int) ($cuerpo["datos"]["id_maquinaria"] ?? 0);
afirmar("El administrador crea una maquina en el centro de prueba", [201, "operativa"], [$codigo, $cuerpo["datos"]["estado"] ?? null]);
[, $cuerpo] = peticion("GET", "/api-gestion/maquinaria/$idMaq/historial", null, $opTope);
afirmar("Al crearla queda anotada en su historial: 'Alta en el sistema'", [1, "operativa", "Alta en el sistema"], [count($cuerpo["datos"] ?? []), $cuerpo["datos"][0]["estado"] ?? null, $cuerpo["datos"][0]["observacion"] ?? null]);
[, $cuerpo] = peticion("GET", "/api-gestion/maquinaria", null, $opTope);
afirmar("El Operario de prueba ve solo las maquinas de su centro", [$idMaq], array_map("intval", array_column($cuerpo["datos"] ?? [], "id_maquinaria")));
[, $cuerpo] = peticion("GET", "/api-gestion/maquinaria", null, $operario);
afirmar("El Operario del seed no ve la de otro centro", false, in_array($idMaq, array_map("intval", array_column($cuerpo["datos"] ?? [], "id_maquinaria")), true));
foreach (["GET /maquinaria/$idMaq", "GET /maquinaria/$idMaq/historial"] as $pedido) {
    [$metodo, $ruta] = explode(" ", $pedido);
    [$codigo] = peticion($metodo, "/api-gestion$ruta", null, $operario);
    afirmar("El Operario de otro centro no la abre ($pedido): 404", 404, $codigo);
}
[$codigo] = peticion("POST", "/api-gestion/maquinaria/$idMaq/estado", ["estado" => "mantenimiento", "observacion" => "Revision"], $operario);
afirmar("Ni le cambia el estado: 404", 404, $codigo);

$estado = "/api-gestion/maquinaria/$idMaq/estado";
[$codigo] = peticion("POST", $estado, ["estado" => "mantenimiento"], $opTope);
afirmar("Cambiar el estado sin nota: 400", 400, $codigo);
[$codigo] = peticion("POST", $estado, ["estado" => "mantenimiento", "observacion" => "   "], $opTope);
afirmar("Una nota de puros espacios: 400", 400, $codigo);
[$codigo] = peticion("POST", $estado, ["estado" => "roto", "observacion" => "x"], $opTope);
afirmar("Un estado inventado: 400", 400, $codigo);
[$codigo] = peticion("POST", $estado, ["estado" => "mantenimiento", "observacion" => str_repeat("a", 301)], $opTope);
afirmar("Una nota enorme: 400", 400, $codigo);
[$codigo] = peticion("POST", $estado, ["estado" => "operativa", "observacion" => "Ya estaba bien"], $opTope);
afirmar("Pasarla al estado en que ya esta: 409", 409, $codigo);
[$codigo, $cuerpo] = peticion("POST", $estado, ["estado" => "mantenimiento", "observacion" => "Cambio de correa"], $opTope);
afirmar("El Operario la pasa a mantenimiento con una nota: 200", [200, "mantenimiento"], [$codigo, $cuerpo["datos"]["estado"] ?? null]);
[, $cuerpo] = peticion("GET", "/api-gestion/estadisticas/centro", null, $opTope);
afirmar("El resumen cuenta 1 maquina en mantenimiento", 1, $cuerpo["datos"]["maquinas_en_mantenimiento"] ?? null);
[, $cuerpo] = peticion("GET", "/api-gestion/maquinaria/$idMaq/historial", null, $opTope);
afirmar("El historial suma el cambio, con la nota y quien lo hizo (lo mas nuevo primero)", [2, "mantenimiento", "Cambio de correa", "Tope Operario", "Operario"], [
    count($cuerpo["datos"] ?? []), $cuerpo["datos"][0]["estado"] ?? null, $cuerpo["datos"][0]["observacion"] ?? null,
    $cuerpo["datos"][0]["usuario"] ?? null, $cuerpo["datos"][0]["rol"] ?? null]);
[$codigo] = peticion("PUT", "/api-gestion/maquinaria/$idMaq", ["marca" => "Bomag"], $opTope);
afirmar("El Operario sigue sin poder editar los datos de la maquina: 403", 403, $codigo);
[$codigo] = peticion("DELETE", "/api-gestion/maquinaria/$idMaq", null, $opTope);
afirmar("Ni darla de baja: 403", 403, $codigo);
peticion("PUT", "/api-gestion/maquinaria/$idMaq", ["estado" => "operativa"], $admin);
peticion("DELETE", "/api-gestion/maquinaria/$idMaq", null, $admin);
[, $cuerpo] = peticion("GET", "/api-gestion/maquinaria/$idMaq/historial", null, $admin);
afirmar("Lo que hace el administrador tambien queda anotado (cuatro asientos)", [4, "fuera_servicio", "operativa"], [
    count($cuerpo["datos"] ?? []), $cuerpo["datos"][0]["estado"] ?? null, $cuerpo["datos"][1]["estado"] ?? null]);
foreach (["UPDATE historial_maquinaria SET estado = 'operativa' WHERE id_maquinaria = $idMaq", "DELETE FROM historial_maquinaria WHERE id_maquinaria = $idMaq"] as $sql) {
    try {
        $pdo->exec($sql);
        $resultado = "se pudo";
    } catch (PDOException $e) {
        $resultado = str_contains($e->getMessage(), "solo agregado") ? "bloqueado" : $e->getMessage();
    }
    afirmar("El historial de una maquina no se edita ni se borra (" . substr($sql, 0, 6) . ")", "bloqueado", $resultado);
}


titulo("27 bis. Operario: asignar el centro desde Usuarios");

[$codigo, $cuerpo] = peticion("PUT", "/api-usuarios/usuarios/$idOpTope", ["id_centro" => $idCentroB], $admin);
afirmar("El administrador cambia el centro de un Operario", [200, $idCentroB, "TOPE Otro $sufijo"], [$codigo, (int) ($cuerpo["datos"]["id_centro"] ?? 0), $cuerpo["datos"]["centro"] ?? null]);
[$codigo] = peticion("PUT", "/api-usuarios/usuarios/$idOpTope", ["id_centro" => 99999], $admin);
afirmar("Un centro que no existe: 400", 400, $codigo);
[$codigo] = peticion("PUT", "/api-usuarios/usuarios/$idOpTope", ["id_centro" => "abc"], $admin);
afirmar("Un centro con texto: 400", 400, $codigo);
[$codigo] = peticion("PUT", "/api-usuarios/usuarios/3", ["id_centro" => $idCentro], $admin);
afirmar("Un Vecino no se asocia a un centro: 400", 400, $codigo);
[$codigo] = peticion("PUT", "/api-usuarios/usuarios/$idOpTope", ["id_centro" => $idCentro], $operario);
afirmar("Un Operario no cambia centros: 403", 403, $codigo);
[$codigo, $cuerpo] = peticion("PUT", "/api-usuarios/usuarios/$idOpTope", ["id_centro" => null], $admin);
afirmar("Se le saca el centro con null", [200, null], [$codigo, array_key_exists("id_centro", $cuerpo["datos"] ?? []) ? $cuerpo["datos"]["id_centro"] : "falta"]);
[$codigo] = peticion("GET", "/api-gestion/centros", null, $opTope);
afirmar("Sin centro, el Operario queda sin acceso (el cambio rige de inmediato): 403", 403, $codigo);
peticion("PUT", "/api-usuarios/usuarios/$idOpTope", ["id_centro" => $idCentro], $admin);
[$codigo, $cuerpo] = peticion("PUT", "/api-usuarios/usuarios/$idOpTope", ["rol" => "Vecino"], $admin);
afirmar("Al pasar a otro rol se le suelta el centro", [200, null], [$codigo, array_key_exists("id_centro", $cuerpo["datos"] ?? []) ? $cuerpo["datos"]["id_centro"] : "falta"]);
peticion("PUT", "/api-usuarios/usuarios/$idOpTope", ["rol" => "Operario", "id_centro" => $idCentro], $admin);
[, $cuerpo] = peticion("GET", "/api-usuarios/usuarios?q=Tope", null, $admin);
afirmar("La lista de usuarios muestra el centro del Operario", true, in_array("TOPE Centro $sufijo", array_column($cuerpo["datos"] ?? [], "centro"), true));


titulo("27 ter. Operario: limpieza y datos del seed intactos");

$pdo->exec("DELETE FROM maquinaria WHERE codigo LIKE 'TOPE-%'");
$pdo->exec("DELETE FROM recoleccion WHERE id_camion IN (SELECT id_camion FROM camion WHERE matricula LIKE 'TOPE-%')");
$pdo->exec("DELETE FROM camion WHERE matricula LIKE 'TOPE-%'");
$pdo->exec("DELETE FROM ruta WHERE nombre LIKE 'TOPE %'");
$pdo->exec("DELETE FROM contenedor WHERE codigo LIKE 'TOPE-%'");
$pdo->exec("UPDATE usuario SET id_centro = NULL WHERE email LIKE 'tope-%@test.uy'");
$pdo->exec("DELETE FROM centro_acopio WHERE nombre LIKE 'TOPE %'");
$pdo->exec("DELETE FROM usuario WHERE email LIKE 'tope-%@test.uy'");
foreach ([$opTope, $opSin] as $archivo) {
    if (file_exists($archivo)) {
        unlink($archivo);
    }
}
afirmar("No quedan centros, usuarios, camiones, rutas, contenedores ni maquinas de prueba", [0, 0, 0, 0, 0, 0], [
    (int) $pdo->query("SELECT COUNT(*) FROM centro_acopio WHERE nombre LIKE 'TOPE %'")->fetchColumn(),
    (int) $pdo->query("SELECT COUNT(*) FROM usuario WHERE email LIKE 'tope-%@test.uy'")->fetchColumn(),
    (int) $pdo->query("SELECT COUNT(*) FROM camion WHERE matricula LIKE 'TOPE-%'")->fetchColumn(),
    (int) $pdo->query("SELECT COUNT(*) FROM ruta WHERE nombre LIKE 'TOPE %'")->fetchColumn(),
    (int) $pdo->query("SELECT COUNT(*) FROM contenedor WHERE codigo LIKE 'TOPE-%'")->fetchColumn(),
    (int) $pdo->query("SELECT COUNT(*) FROM maquinaria WHERE codigo LIKE 'TOPE-%'")->fetchColumn(),
]);
afirmar("Los movimientos, recepciones e historiales de maquinas volvieron a los del seed (se fueron en cascada)", $conteoSeed, [
    "movimiento_stock" => (int) $pdo->query("SELECT COUNT(*) FROM movimiento_stock")->fetchColumn(),
    "recepcion" => (int) $pdo->query("SELECT COUNT(*) FROM recepcion")->fetchColumn(),
    "historial_maquinaria" => (int) $pdo->query("SELECT COUNT(*) FROM historial_maquinaria")->fetchColumn(),
]);
afirmar("El stock de los centros del seed no cambio", [79200.0, 40700.0, 32526.45], array_map(
    fn($id) => round((float) $pdo->query("SELECT COALESCE(SUM(CASE sentido WHEN 'entrada' THEN kg ELSE -kg END), 0) FROM movimiento_stock WHERE id_centro = $id")->fetchColumn(), 2), [1, 2, 3]));
