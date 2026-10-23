<?php

// Parte de correr-tests.php (se incluye desde alli y usa sus funciones y sus sesiones).
// Todo lo que se planifica usa un camion, una ruta y contenedores propios (TREC-), que
// se borran al final. Las recolecciones del seed no se tocan.

titulo("19. Recolecciones: matriz de permisos");

$pdo->exec("DELETE FROM recoleccion WHERE id_camion IN (SELECT id_camion FROM camion WHERE matricula LIKE 'TREC-%')
            OR id_ruta IN (SELECT id_ruta FROM ruta WHERE nombre LIKE 'TREC %')");
$pdo->exec("DELETE FROM camion WHERE matricula LIKE 'TREC-%'");
$pdo->exec("DELETE FROM ruta WHERE nombre LIKE 'TREC %'");
$pdo->exec("DELETE FROM contenedor WHERE codigo LIKE 'TREC-%'");

$camionesDelSeed = $pdo->query("SELECT id_camion, estado FROM camion ORDER BY id_camion")->fetchAll(PDO::FETCH_KEY_PAIR);
$recoleccionesDelSeed = $pdo->query("SELECT id_recoleccion, CONCAT(estado, '|', fecha_hora_inicio) FROM recoleccion ORDER BY id_recoleccion")->fetchAll(PDO::FETCH_KEY_PAIR);

foreach (["/api-gestion/camiones", "/api-gestion/cuadrillas"] as $viejo) {
    [$codigo] = peticion("GET", $viejo, null, $admin);
    afirmar("$viejo ya no existe en api-gestion: 404 (se mudo a api-recoleccion)", 404, $codigo);
}

$subrecursos = [
    "POST /recolecciones"                => ["Administrador"],
    "POST /recolecciones/99999/inicio"      => ["Administrador", "Cuadrilla"],
    "POST /recolecciones/99999/vaciados"    => ["Administrador", "Cuadrilla"],
    "POST /recolecciones/99999/fin"         => ["Administrador", "Cuadrilla"],
    "POST /recolecciones/99999/cancelacion" => ["Administrador"],
];
foreach ($subrecursos as $pedido => $quienes) {
    [$metodo, $ruta] = explode(" ", $pedido);
    $esperados = [];
    $obtenidos = [];
    foreach ($sesiones as $rol => $cookies) {
        [$obtenidos[$rol]] = peticion($metodo, "/api-recoleccion$ruta", ["x" => 1], $cookies);
        $esperados[$rol] = $cookies === null ? 401 : (in_array($rol, $quienes, true) ? "pasa" : 403);
        if ($esperados[$rol] === "pasa") {
            $obtenidos[$rol] = in_array($obtenidos[$rol], [401, 403], true) ? $obtenidos[$rol] : "pasa";
        }
    }
    afirmar("$pedido: lo pueden hacer " . implode(" y ", $quienes), $esperados, $obtenidos);
}
[$codigo] = peticion("DELETE", "/api-recoleccion/recolecciones/1", null, $admin);
afirmar("Nadie borra una recoleccion, ni el administrador: 403", 403, $codigo);
[$codigo] = peticion("POST", "/api-recoleccion/recolecciones/1/inventado", ["x" => 1], $admin);
afirmar("Un subrecurso que no existe no lo usa nadie: 403", 403, $codigo);


titulo("20. Rutas");

[$codigo] = peticion("POST", "/api-recoleccion/rutas", ["nombre" => "TREC sin dia", "hora_inicio" => "06:00", "id_zona" => 2], $admin);
afirmar("Una ruta sin dia: 400", 400, $codigo);
[$codigo] = peticion("POST", "/api-recoleccion/rutas", ["nombre" => "TREC hora mala", "dia_semana" => "lunes", "hora_inicio" => "25:00", "id_zona" => 2], $admin);
afirmar("Una hora imposible: 400", 400, $codigo);
[$codigo] = peticion("POST", "/api-recoleccion/rutas", ["nombre" => "TREC zona", "dia_semana" => "lunes", "hora_inicio" => "06:00", "id_zona" => 99999], $admin);
afirmar("Una zona que no existe: 404", 404, $codigo);
[$codigo, $cuerpo] = peticion("POST", "/api-recoleccion/rutas", ["nombre" => "TREC Ruta $sufijo", "dia_semana" => "martes", "hora_inicio" => "06:15", "id_zona" => 2], $admin);
afirmar("Crear una ruta: 201, activa y con su zona", [201, "activa", "Centro", "06:15"], [
    $codigo, $cuerpo["datos"]["estado"] ?? null, $cuerpo["datos"]["zona"] ?? null, $cuerpo["datos"]["hora_inicio"] ?? null,
]);
$idRuta = (int) ($cuerpo["datos"]["id_ruta"] ?? 0);
[$codigo, $cuerpo] = peticion("PUT", "/api-recoleccion/rutas/$idRuta", ["hora_inicio" => "07:00", "dia_semana" => "jueves"], $admin);
afirmar("Editarla cambia solo lo que llega", [200, "07:00", "jueves", "TREC Ruta $sufijo"], [
    $codigo, $cuerpo["datos"]["hora_inicio"] ?? null, $cuerpo["datos"]["dia_semana"] ?? null, $cuerpo["datos"]["nombre"] ?? null,
]);
[$codigo] = peticion("PUT", "/api-recoleccion/rutas/$idRuta", ["dia_semana" => "feriado"], $admin);
afirmar("Un dia inventado al editar: 400", 400, $codigo);
[$codigo] = peticion("PUT", "/api-recoleccion/rutas/99999", ["hora_inicio" => "07:00"], $admin);
afirmar("Editar una ruta que no existe: 404", 404, $codigo);

[$codigo, $cuerpo] = peticion("POST", "/api-recoleccion/rutas", ["nombre" => "TREC Baja $sufijo", "dia_semana" => "lunes", "hora_inicio" => "06:00", "id_zona" => 2], $admin);
$idRutaBaja = (int) ($cuerpo["datos"]["id_ruta"] ?? 0);
[$codigo, $cuerpo] = peticion("DELETE", "/api-recoleccion/rutas/$idRutaBaja", null, $admin);
afirmar("Dar de baja una ruta la deja inactiva (no la borra)", [200, "inactiva"], [$codigo, $cuerpo["datos"]["estado"] ?? null]);
[$codigo] = peticion("DELETE", "/api-recoleccion/rutas/$idRutaBaja", null, $admin);
afirmar("Darla de baja otra vez: 409", 409, $codigo);


titulo("21. Recolecciones: planificar, iniciar, vaciar y finalizar");

[$codigo, $cuerpo] = peticion("POST", "/api-recoleccion/camiones", ["matricula" => "TREC-$sufijo", "capacidad" => 4000], $admin);
$idCamion = (int) ($cuerpo["datos"]["id_camion"] ?? 0);
afirmar("Se crea un camion de prueba en la nueva API", [201, "operativo"], [$codigo, $cuerpo["datos"]["estado"] ?? null]);
$contenedoresDePrueba = [];
foreach (["A", "B"] as $letra) {
    [, $cuerpo] = peticion("POST", "/api-gestion/contenedores", [
        "codigo" => "TREC-$letra$sufijo", "direccion" => "Contenedor de prueba de recoleccion",
        "capacidad" => 1100, "tipo_residuo" => "Mixto", "id_zona" => 2, "latitud" => -34.9, "longitud" => -56.2,
    ], $admin);
    $contenedoresDePrueba[$letra] = (int) ($cuerpo["datos"]["id_contenedor"] ?? 0);
}
// Desde el plan de rutas para la defensa solo se vacian contenedores de la ruta de la recoleccion: los dos de prueba van en la ruta.
[$codigo] = peticion("PUT", "/api-recoleccion/rutas/$idRuta/contenedores", ["contenedores" => array_values($contenedoresDePrueba)], $admin);
afirmar("Los contenedores de prueba se ponen en la ruta de prueba", 200, $codigo);

$plan = ["id_ruta" => $idRuta, "id_camion" => $idCamion, "id_cuadrilla" => 1, "fecha_hora_inicio" => "2026-10-20T06:30", "id_centro" => 1];
[$codigo] = peticion("POST", "/api-recoleccion/recolecciones", array_merge($plan, ["fecha_hora_inicio" => "mañana"]), $admin);
afirmar("Planificar sin una fecha valida: 400", 400, $codigo);
[$codigo] = peticion("POST", "/api-recoleccion/recolecciones", array_merge($plan, ["id_ruta" => 99999]), $admin);
afirmar("Con una ruta que no existe: 404", 404, $codigo);
[$codigo] = peticion("POST", "/api-recoleccion/recolecciones", array_merge($plan, ["id_ruta" => $idRutaBaja]), $admin);
afirmar("Con una ruta inactiva: 409", 409, $codigo);
[$codigo] = peticion("POST", "/api-recoleccion/recolecciones", array_merge($plan, ["id_camion" => 3]), $admin);
afirmar("Con un camion en mantenimiento: 409", 409, $codigo);
[$codigo] = peticion("POST", "/api-recoleccion/recolecciones", array_merge($plan, ["id_cuadrilla" => 4]), $admin);
afirmar("Con una cuadrilla inactiva: 409", 409, $codigo);
[$codigo] = peticion("POST", "/api-recoleccion/recolecciones", array_merge($plan, ["id_centro" => 99999]), $admin);
afirmar("Con un centro que no existe: 404", 404, $codigo);

[$codigo, $cuerpo] = peticion("POST", "/api-recoleccion/recolecciones", $plan, $admin);
$idRec = (int) ($cuerpo["datos"]["id_recoleccion"] ?? 0);
afirmar("Planificar: 201, con numero REC-, planificada, para la Norte y sin tocar el camion", [201, true, "planificada", "Cuadrilla Norte", "2026-10-20 06:30:00", "operativo"], [
    $codigo, str_starts_with($cuerpo["datos"]["numero"] ?? "", "REC-"), $cuerpo["datos"]["estado"] ?? null,
    $cuerpo["datos"]["cuadrilla"] ?? null, $cuerpo["datos"]["fecha_hora_inicio"] ?? null, $cuerpo["datos"]["estado_camion"] ?? null,
]);

[, $cuerpo] = peticion("GET", "/api-recoleccion/recolecciones?activas=1", null, $cuadrilla);
afirmar("La cuadrilla Norte la ve en sus tareas", true, in_array($idRec, array_column($cuerpo["datos"] ?? [], "id_recoleccion"), true));
afirmar("Y en su lista no hay recolecciones de otras cuadrillas", [1], array_values(array_unique(array_column($cuerpo["datos"] ?? [], "id_cuadrilla"))));
[, $cuerpo] = peticion("GET", "/api-recoleccion/recolecciones", null, $otraCuadrilla);
afirmar("Otra cuadrilla no la ve", false, in_array($idRec, array_column($cuerpo["datos"] ?? [], "id_recoleccion"), true));
[$codigo] = peticion("GET", "/api-recoleccion/recolecciones/$idRec", null, $otraCuadrilla);
afirmar("Ni abre su detalle: 404", 404, $codigo);
[$codigo] = peticion("POST", "/api-recoleccion/recolecciones/$idRec/inicio", [], $otraCuadrilla);
afirmar("Ni la puede iniciar: 403", 403, $codigo);
[$codigo] = peticion("GET", "/api-recoleccion/recolecciones?estado=borrada", null, $admin);
afirmar("Un filtro de estado inventado: 400", 400, $codigo);

[$codigo] = peticion("POST", "/api-recoleccion/recolecciones/$idRec/vaciados", ["codigo_contenedor" => "TREC-A$sufijo", "peso_kg" => 100], $cuadrilla);
afirmar("Vaciar antes de iniciar: 409", 409, $codigo);
[$codigo] = peticion("POST", "/api-recoleccion/recolecciones/$idRec/fin", [], $cuadrilla);
afirmar("Finalizar antes de iniciar: 409", 409, $codigo);

[$codigo, $cuerpo] = peticion("POST", "/api-recoleccion/recolecciones/$idRec/inicio", [], $cuadrilla);
afirmar("La cuadrilla la inicia: en curso y el camion pasa a en ruta", [200, "en_curso", "en_ruta"], [
    $codigo, $cuerpo["datos"]["estado"] ?? null, $cuerpo["datos"]["estado_camion"] ?? null,
]);
afirmar("La hora de inicio pasa a ser la real", true, ($cuerpo["datos"]["fecha_hora_inicio"] ?? "") !== "2026-10-20 06:30:00");
[$codigo] = peticion("POST", "/api-recoleccion/recolecciones/$idRec/inicio", [], $cuadrilla);
afirmar("Iniciarla otra vez: 409", 409, $codigo);
[, $cuerpo] = peticion("GET", "/api-recoleccion/cuadrillas?disponibles=1", null, $admin);
afirmar("La Norte deja de figurar como disponible para incidencias", false, in_array(1, array_column($cuerpo["datos"] ?? [], "id_cuadrilla"), true));

[$codigo] = peticion("PUT", "/api-recoleccion/camiones/$idCamion", ["estado" => "mantenimiento"], $admin);
afirmar("No se puede cambiar a mano el estado de un camion en una recoleccion en curso: 409", 409, $codigo);
[$codigo] = peticion("DELETE", "/api-recoleccion/camiones/$idCamion", null, $admin);
afirmar("Ni darlo de baja: 409", 409, $codigo);
[$codigo] = peticion("PUT", "/api-recoleccion/camiones/$idCamion", ["marca" => "Prueba"], $admin);
afirmar("Pero si editar sus otros datos", 200, $codigo);

$base = "/api-recoleccion/recolecciones/$idRec/vaciados";
[$codigo] = peticion("POST", $base, ["codigo_contenedor" => "TREC-A$sufijo", "peso_kg" => 0], $cuadrilla);
afirmar("Un vaciado con peso 0: 400", 400, $codigo);
[$codigo] = peticion("POST", $base, ["codigo_contenedor" => "NO-EXISTE", "peso_kg" => 50], $cuadrilla);
afirmar("Un contenedor que no existe: 404", 404, $codigo);
[$codigo] = peticion("POST", $base, ["codigo_contenedor" => "TREC-A$sufijo", "peso_kg" => 50, "volumen_l" => 1500], $cuadrilla);
afirmar("Mas volumen que la capacidad del contenedor: 400", 400, $codigo);
[$codigo, $cuerpo] = peticion("POST", $base, ["codigo_contenedor" => "TREC-A$sufijo", "peso_kg" => "210.5", "volumen_l" => 640], $cuadrilla);
afirmar("Registrar un vaciado: 201 y suma al total", [201, 1, 210.5], [
    $codigo, $cuerpo["datos"]["vaciados"] ?? null, $cuerpo["datos"]["peso_total_kg"] ?? null,
]);
afirmar("El detalle trae el vaciado con su contenedor", ["TREC-A$sufijo", 210.5, 640], [
    $cuerpo["datos"]["detalle_vaciados"][0]["contenedor"] ?? null, $cuerpo["datos"]["detalle_vaciados"][0]["peso_kg"] ?? null,
    (int) ($cuerpo["datos"]["detalle_vaciados"][0]["volumen_l"] ?? 0),
]);
[$codigo] = peticion("POST", $base, ["codigo_contenedor" => "TREC-A$sufijo", "peso_kg" => 20], $cuadrilla);
afirmar("El mismo contenedor dos veces en la misma recoleccion: 409", 409, $codigo);
peticion("DELETE", "/api-gestion/contenedores/" . $contenedoresDePrueba["B"], null, $admin);
[$codigo] = peticion("POST", $base, ["codigo_contenedor" => "TREC-B$sufijo", "peso_kg" => 20], $cuadrilla);
afirmar("Un contenedor retirado no se vacia: 409", 409, $codigo);

[$codigo, $cuerpo] = peticion("POST", "/api-recoleccion/recolecciones", array_merge($plan, ["id_cuadrilla" => 3]), $admin);
$idOtra = (int) ($cuerpo["datos"]["id_recoleccion"] ?? 0);
afirmar("Se puede planificar otra con el mismo camion aunque este en ruta", 201, $codigo);
[$codigo, $cuerpo] = peticion("POST", "/api-recoleccion/recolecciones/$idOtra/inicio", [], $admin);
afirmar("Pero no iniciarla mientras el camion sigue en ruta: 409", [409, true], [$codigo, str_contains($cuerpo["mensaje"] ?? "", "en ruta")]);
[$codigo] = peticion("POST", "/api-recoleccion/recolecciones/$idOtra/cancelacion", [], $admin);
afirmar("Cancelar sin motivo: 400", 400, $codigo);
[$codigo] = peticion("POST", "/api-recoleccion/recolecciones/$idOtra/cancelacion", ["observacion" => "Se reprograma"], $cuadrilla);
afirmar("La cuadrilla no cancela: 403", 403, $codigo);
[$codigo, $cuerpo] = peticion("POST", "/api-recoleccion/recolecciones/$idOtra/cancelacion", ["observacion" => "Se reprograma"], $admin);
afirmar("Cancelar una planificada: queda cancelada, sin hora de fin y el camion sigue en ruta", [200, "cancelada", null, "Se reprograma", "en_ruta"], [
    $codigo, $cuerpo["datos"]["estado"] ?? null, array_key_exists("fecha_hora_fin", $cuerpo["datos"] ?? []) ? $cuerpo["datos"]["fecha_hora_fin"] : "falta",
    $cuerpo["datos"]["observaciones"] ?? null, $cuerpo["datos"]["estado_camion"] ?? null,
]);

[$codigo, $cuerpo] = peticion("POST", "/api-recoleccion/recolecciones/$idRec/fin", ["observacion" => "Sin novedades"], $cuadrilla);
afirmar("La cuadrilla la finaliza: con hora de fin y el camion vuelve a operativo", [200, "finalizada", true, "Sin novedades", "operativo"], [
    $codigo, $cuerpo["datos"]["estado"] ?? null, !empty($cuerpo["datos"]["fecha_hora_fin"]),
    $cuerpo["datos"]["observaciones"] ?? null, $cuerpo["datos"]["estado_camion"] ?? null,
]);
[$codigo] = peticion("POST", $base, ["codigo_contenedor" => "TREC-A$sufijo", "peso_kg" => 20], $cuadrilla);
afirmar("Finalizada no admite vaciados: 409", 409, $codigo);
[$codigo] = peticion("POST", "/api-recoleccion/recolecciones/$idRec/cancelacion", ["observacion" => "x"], $admin);
afirmar("Ni cancelarla: 409", 409, $codigo);
[, $cuerpo] = peticion("GET", "/api-recoleccion/cuadrillas?disponibles=1", null, $admin);
afirmar("La Norte vuelve a estar disponible", true, in_array(1, array_column($cuerpo["datos"] ?? [], "id_cuadrilla"), true));

[, $cuerpo] = peticion("POST", "/api-recoleccion/recolecciones", $plan, $admin);
$idTercera = (int) ($cuerpo["datos"]["id_recoleccion"] ?? 0);
peticion("POST", "/api-recoleccion/recolecciones/$idTercera/inicio", [], $admin);
[$codigo, $cuerpo] = peticion("POST", "/api-recoleccion/recolecciones/$idTercera/cancelacion", ["observacion" => "Se rompio el camion"], $admin);
afirmar("Cancelar una en curso: con hora de fin y el camion vuelve a operativo", [200, "cancelada", true, "operativo"], [
    $codigo, $cuerpo["datos"]["estado"] ?? null, !empty($cuerpo["datos"]["fecha_hora_fin"]), $cuerpo["datos"]["estado_camion"] ?? null,
]);


titulo("22. Recolecciones: limpieza y datos del seed intactos");

$pdo->prepare("DELETE FROM recoleccion WHERE id_camion = ? OR id_ruta IN (?, ?)")->execute([$idCamion, $idRuta, $idRutaBaja]);
$pdo->prepare("DELETE FROM camion WHERE id_camion = ?")->execute([$idCamion]);
$pdo->prepare("DELETE FROM ruta WHERE id_ruta IN (?, ?)")->execute([$idRuta, $idRutaBaja]);
$pdo->prepare("DELETE FROM contenedor WHERE id_contenedor IN (?, ?)")->execute(array_values($contenedoresDePrueba));
afirmar("No quedan camiones, rutas ni contenedores de prueba", [0, 0, 0], [
    (int) $pdo->query("SELECT COUNT(*) FROM camion WHERE matricula LIKE 'TREC-%'")->fetchColumn(),
    (int) $pdo->query("SELECT COUNT(*) FROM ruta WHERE nombre LIKE 'TREC %'")->fetchColumn(),
    (int) $pdo->query("SELECT COUNT(*) FROM contenedor WHERE codigo LIKE 'TREC-%'")->fetchColumn(),
]);
afirmar("Los camiones del seed siguen con su estado", $camionesDelSeed,
    $pdo->query("SELECT id_camion, estado FROM camion ORDER BY id_camion")->fetchAll(PDO::FETCH_KEY_PAIR));
afirmar("Y las recolecciones del seed, intactas", $recoleccionesDelSeed,
    $pdo->query("SELECT id_recoleccion, CONCAT(estado, '|', fecha_hora_inicio) FROM recoleccion ORDER BY id_recoleccion")->fetchAll(PDO::FETCH_KEY_PAIR));
