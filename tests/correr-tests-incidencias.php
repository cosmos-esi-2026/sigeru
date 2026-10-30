<?php

// Parte de correr-tests.php (se incluye desde alli y usa sus funciones y sus sesiones).
// Todo lo que muta usa un contenedor propio: el historial es de solo agregado y los
// asientos de un contenedor del seed no se podrian limpiar despues.

titulo("11. Incidencias: matriz de permisos");

$pdo->exec("DELETE FROM incidencia WHERE id_contenedor IN (SELECT id_contenedor FROM contenedor WHERE codigo LIKE 'TINC-%')");
$pdo->exec("DELETE FROM contenedor WHERE codigo LIKE 'TINC-%'");

$otraCuadrilla = sys_get_temp_dir() . "/sigeru_test_otra_cuadrilla.txt";
iniciarSesion("carla.nunez@sigeru.uy", $otraCuadrilla);

function idsDe(array $cuerpo): array
{
    return array_map("intval", array_column($cuerpo["datos"] ?? [], "id_incidencia"));
}

function ultimoAsiento(array $cuerpo): array
{
    $historial = $cuerpo["datos"]["historial"] ?? [];
    return $historial === [] ? [] : $historial[count($historial) - 1];
}

function asientos(string $ruta, ?string $cookies): array
{
    [, $cuerpo] = peticion("GET", $ruta, null, $cookies);
    return $cuerpo["datos"]["historial"] ?? [];
}

$subrecursos = [
    "/api-gestion/incidencias/99999/asignacion" => [["id_cuadrilla" => 1], ["Administrador"]],
    "/api-gestion/incidencias/99999/avance"     => [["observacion" => "x"], ["Cuadrilla"]],
    "/api-gestion/incidencias/99999/resolucion" => [["observacion" => "x"], ["Administrador", "Cuadrilla"]],
    "/api-gestion/incidencias/99999/descarte"   => [["observacion" => "x"], ["Administrador"]],
    "/api-gestion/incidencias/99999/derivacion" => [["nota" => "x"], ["Administrador"]],
];
foreach ($subrecursos as $ruta => [$cuerpoPeticion, $rolesConAcceso]) {
    $esperados = [];
    $obtenidos = [];
    foreach ($sesiones as $rol => $cookies) {
        $esperados[$rol] = $cookies === null ? 401 : (in_array($rol, $rolesConAcceso, true) ? 404 : 403);
        [$obtenidos[$rol]] = peticion("POST", $ruta, $cuerpoPeticion, $cookies);
    }
    afirmar("POST $ruta: quien no corresponde recibe 403, y a quien si le toca le falta la incidencia: 404", $esperados, $obtenidos);
}

$esperados = [];
$obtenidos = [];
foreach ($sesiones as $rol => $cookies) {
    $esperados[$rol] = $cookies === null ? 401 : (in_array($rol, ["Vecino", "Cuadrilla"], true) ? 400 : 403);
    [$obtenidos[$rol]] = peticion("POST", "/api-gestion/incidencias", ["nada" => 1], $cookies);
}
afirmar("POST /incidencias lo pueden hacer un vecino y una cuadrilla en terreno (con un cuerpo vacio llega a la validacion: 400)", $esperados, $obtenidos);

$esperados = [];
$obtenidos = [];
foreach ($sesiones as $rol => $cookies) {
    $esperados[$rol] = $cookies === null ? 401 : ($rol === "Administrador" ? 404 : 403);
    [$obtenidos[$rol]] = peticion("PUT", "/api-gestion/incidencias/99999", ["prioridad" => "alta"], $cookies);
}
afirmar("PUT /incidencias/{id} (prioridad) solo lo puede hacer un administrador", $esperados, $obtenidos);

$esperados = [];
$obtenidos = [];
foreach ($sesiones as $rol => $cookies) {
    $esperados[$rol] = $cookies === null ? 401 : 403;
    [$obtenidos[$rol]] = peticion("DELETE", "/api-gestion/incidencias/1", null, $cookies);
}
afirmar("Nadie puede borrar una incidencia, ni el administrador", $esperados, $obtenidos);

[$codigo] = peticion("POST", "/api-gestion/incidencias/1/inventado", ["x" => 1], $admin);
afirmar("Un subrecurso que no existe no lo usa nadie: 403", 403, $codigo);
[$codigo] = peticion("POST", "/api-recoleccion/cuadrillas", ["nombre" => "x"], $cuadrilla);
afirmar("Las cuadrillas no las crea una cuadrilla (solo el administrador): 403", 403, $codigo);


titulo("12. Incidencias: registrar y validar");

[$codigo, $cuerpo] = peticion("POST", "/api-gestion/contenedores", [
    "codigo" => "TINC-$sufijo", "direccion" => "Calle de prueba de incidencias 1",
    "capacidad" => 800, "tipo_residuo" => "Mixto", "id_zona" => 4,
], $admin);
$idContInc = $cuerpo["datos"]["id_contenedor"] ?? null;
afirmar("El contenedor de las pruebas de incidencias se crea", 201, $codigo);

$reporte = ["codigo_contenedor" => "TINC-$sufijo", "tipo_problema" => "desbordado", "descripcion" => "Hay bolsas en la vereda"];

[$codigo] = peticion("POST", "/api-gestion/incidencias", array_diff_key($reporte, ["tipo_problema" => 1]), $vecino);
afirmar("Sin tipo de problema: 400", 400, $codigo);
[$codigo] = peticion("POST", "/api-gestion/incidencias", ["tipo_problema" => "inventado"] + $reporte, $vecino);
afirmar("Un tipo de problema que no existe: 400", 400, $codigo);
[$codigo] = peticion("POST", "/api-gestion/incidencias", ["descripcion" => "Roto"] + $reporte, $vecino);
afirmar("Una descripcion de menos de 5 caracteres: 400", 400, $codigo);
[$codigo] = peticion("POST", "/api-gestion/incidencias", ["prioridad" => "urgente"] + $reporte, $vecino);
afirmar("Una prioridad que no existe: 400", 400, $codigo);
[$codigo] = peticion("POST", "/api-gestion/incidencias", array_diff_key($reporte, ["codigo_contenedor" => 1]), $vecino);
afirmar("Sin indicar el contenedor: 400", 400, $codigo);
[$codigo] = peticion("POST", "/api-gestion/incidencias", ["tipo_problema" => "otro", "descripcion" => "   "] + $reporte, $vecino);
afirmar("Un problema de tipo «otro» con la descripcion en blanco: 400 (es el unico tipo que la exige)", 400, $codigo);
[$codigo] = peticion("POST", "/api-gestion/incidencias", ["tipo_problema" => "otro"] + array_diff_key($reporte, ["descripcion" => 1]), $vecino);
afirmar("Un problema de tipo «otro» sin descripcion: 400", 400, $codigo);
[$codigo, $cuerpo] = peticion("GET", "/api-gestion/incidencias?contenedor=$idContInc", null, $admin);
afirmar("Ninguna de las anteriores dejo una incidencia a medias", 0, count($cuerpo["datos"] ?? []));

[$codigo, $cuerpo] = peticion("POST", "/api-gestion/incidencias", ["codigo_contenedor" => "NO-EXISTE-$sufijo"] + $reporte, $vecino);
afirmar("Un codigo de contenedor que no existe: 404 (RF-221)", 404, $codigo);
[$codigo] = peticion("POST", "/api-gestion/incidencias", ["id_contenedor" => 99999] + $reporte, $vecino);
afirmar("Un id de contenedor que no existe: 404", 404, $codigo);
[$codigo, $cuerpo] = peticion("POST", "/api-gestion/incidencias", ["codigo_contenedor" => "TEST-$sufijo"] + $reporte, $vecino);
afirmar("Un contenedor retirado no admite reportes: 409", 409, $codigo);
afirmar("Y el mensaje dice por que", true, str_contains($cuerpo["mensaje"] ?? "", "está retirado"));

// H-06 (parte 26): la descripcion es opcional, salvo en «otro». Un aviso rapido desde el QR no se frena por no escribir nada.
[, $cuerpo] = peticion("POST", "/api-gestion/contenedores", ["codigo" => "TINC-$sufijo-D", "direccion" => "Calle de prueba de la descripcion opcional 1", "capacidad" => 800, "tipo_residuo" => "Mixto", "id_zona" => 4], $admin);
[$codigo, $cuerpo] = peticion("POST", "/api-gestion/incidencias", ["codigo_contenedor" => "TINC-$sufijo-D", "tipo_problema" => "lleno"], $vecino);
afirmar("Sin descripcion en un tipo que no es «otro»: 201, con prioridad media y el rotulo del tipo como descripcion", [201, "media", "Contenedor lleno"],
    [$codigo, $cuerpo["datos"]["prioridad"] ?? null, $cuerpo["datos"]["descripcion"] ?? null]);
$pdo->exec("DELETE FROM incidencia WHERE id_contenedor IN (SELECT id_contenedor FROM contenedor WHERE codigo = 'TINC-$sufijo-D')");
$pdo->exec("DELETE FROM contenedor WHERE codigo = 'TINC-$sufijo-D'");

[$codigo, $cuerpo] = peticion("POST", "/api-gestion/incidencias", $reporte, $vecino);
afirmar("Un vecino registra un desborde: 201", 201, $codigo);
$inc = $cuerpo["datos"] ?? [];
$idInc = $inc["id_incidencia"] ?? 0;
$numero = sprintf("INC-%04d", $idInc);
afirmar("Se le informa el numero de incidencia (RF-220)", $numero, $inc["numero"] ?? null);
afirmar("El mensaje tambien lo dice", "Incidencia $numero registrada", $cuerpo["mensaje"] ?? null);
afirmar("Nace abierta, con prioridad alta (el desborde es lo mas urgente) y el tipo informado (RF-219)", ["abierta", "alta", "desbordado"],
    [$inc["estado"] ?? null, $inc["prioridad"] ?? null, $inc["tipo_problema"] ?? null]);
afirmar("Nace con su primer asiento, hecho por el vecino (RNF-017)", [["abierta", "Vecino"]],
    array_map(fn ($a) => [$a["estado"], $a["rol"]], $inc["historial"] ?? []));
afirmar("El vecino no ve quien reporto (es el mismo) ni su id", [false, false], [isset($inc["reportada_por"]), isset($inc["id_usuario"])]);

[$codigo, $cuerpo] = peticion("GET", "/api-gestion/contenedores/$idContInc", null, $admin);
afirmar("Un desborde sobre un contenedor disponible lo pasa a lleno", "lleno", $cuerpo["datos"]["estado"] ?? null);
[$codigo, $cuerpo] = peticion("GET", "/api-gestion/contenedores/$idContInc/historial", null, $admin);
afirmar("Y deja su asiento en el historial del contenedor, con el numero",
    true, in_array("Desborde informado en $numero", array_column($cuerpo["datos"] ?? [], "observacion"), true));

[$codigo, $cuerpo] = peticion("POST", "/api-gestion/incidencias", $reporte, $vecino);
afirmar("El mismo problema en el mismo contenedor, abierto todavia: 409", 409, $codigo);
afirmar("Y le dice cual es el reporte existente", $numero, $cuerpo["datos"]["numero"] ?? null);


titulo("13. Incidencias: quien ve cada una y los filtros");

$delVecino = array_map("intval", $pdo->query("SELECT id_incidencia FROM incidencia WHERE id_usuario = 3")->fetchAll(PDO::FETCH_COLUMN));
[$codigo, $cuerpo] = peticion("GET", "/api-gestion/incidencias", null, $vecino);
$ids = idsDe($cuerpo);
sort($ids);
sort($delVecino);
afirmar("Un vecino ve exactamente las que informo el (RF-241)", $delVecino, $ids);
afirmar("Y la nueva esta entre ellas", true, in_array($idInc, $ids, true));

[$codigo, $cuerpo] = peticion("GET", "/api-gestion/incidencias", null, $cuadrilla);
afirmar("Una cuadrilla ve solo las suyas: ninguna de las que no tienen cuadrilla (RF-229)", [1], array_values(array_unique(array_map("intval", array_column($cuerpo["datos"] ?? [], "id_cuadrilla")))));
afirmar("Y todavia no ve la nueva, que no esta asignada", false, in_array($idInc, idsDe($cuerpo), true));

[$codigo, $cuerpo] = peticion("GET", "/api-gestion/incidencias", null, $admin);
afirmar("El administrador las ve todas (RF-238): las 5 del seed mas la nueva", true, count($cuerpo["datos"] ?? []) >= 6);
$deLaNueva = array_values(array_filter($cuerpo["datos"] ?? [], fn ($f) => (int) $f["id_incidencia"] === $idInc))[0] ?? [];
afirmar("Y ve quien la reporto", "Marta Rodriguez", $deLaNueva["reportada_por"] ?? null);
afirmar("La lista trae contenedor, zona, tipo, estado, prioridad y fecha (RF-237)", [true, true, true, true, true, true],
    array_map(fn ($c) => isset($deLaNueva[$c]), ["contenedor", "zona", "tipo_problema", "estado", "prioridad", "fecha_reporte"]));

[$codigo, $cuerpo] = peticion("GET", "/api-gestion/incidencias/$idInc", null, $cuadrilla);
afirmar("Una cuadrilla no puede abrir una que no tiene asignada: 404", 404, $codigo);
[$codigo, $cuerpo] = peticion("GET", "/api-gestion/incidencias/$idInc", null, $vecino);
afirmar("El vecino abre el detalle de la suya: 200", 200, $codigo);
$deOtro = (int) $pdo->query("SELECT MIN(id_incidencia) FROM incidencia WHERE id_usuario <> 3")->fetchColumn();
[$codigo] = peticion("GET", "/api-gestion/incidencias/$deOtro", null, $vecino);
afirmar("Pero no el de una que informo otra persona: 404", 404, $codigo);
[$codigo] = peticion("GET", "/api-gestion/incidencias/99999", null, $admin);
afirmar("Una incidencia que no existe: 404", 404, $codigo);

[$codigo, $cuerpo] = peticion("GET", "/api-gestion/incidencias?estado=abierta&zona=4&contenedor=$idContInc", null, $admin);
afirmar("Filtrar por estado, zona y contenedor la encuentra (RF-239)", [$idInc], idsDe($cuerpo));
[$codigo, $cuerpo] = peticion("GET", "/api-gestion/incidencias?estado=resuelta&contenedor=$idContInc", null, $admin);
afirmar("Con otro estado no aparece", [], idsDe($cuerpo));
[$codigo, $cuerpo] = peticion("GET", "/api-gestion/incidencias?zona=5&contenedor=$idContInc", null, $admin);
afirmar("Ni con otra zona", [], idsDe($cuerpo));
[$codigo, $cuerpo] = peticion("GET", "/api-gestion/incidencias?desde=" . date("Y-m-d") . "&hasta=" . date("Y-m-d") . "&contenedor=$idContInc", null, $admin);
afirmar("Un periodo que incluye hoy, el dia completo, la encuentra", [$idInc], idsDe($cuerpo));
[$codigo, $cuerpo] = peticion("GET", "/api-gestion/incidencias?desde=2999-01-01", null, $admin);
afirmar("Un periodo en el futuro no trae nada", [], idsDe($cuerpo));
[$codigo, $cuerpo] = peticion("GET", "/api-gestion/incidencias?tipo=roto&contenedor=$idContInc", null, $admin);
afirmar("Por tipo de problema tambien se filtra", [], idsDe($cuerpo));
foreach (["estado=reportada", "zona=abc", "desde=ayer", "desde=2026-10-02&hasta=2026-10-01", "tipo=inventado"] as $malo) {
    [$codigo] = peticion("GET", "/api-gestion/incidencias?$malo", null, $admin);
    afirmar("El filtro invalido '$malo' se rechaza: 400", 400, $codigo);
}


titulo("14. Incidencias: prioridad y asignacion");

[$codigo] = peticion("POST", "/api-gestion/incidencias/$idInc/asignacion", ["id_cuadrilla" => 1], $vecino);
afirmar("Un vecino no puede asignar: 403", 403, $codigo);
[$codigo] = peticion("POST", "/api-gestion/incidencias/$idInc/asignacion", ["id_cuadrilla" => 1], $cuadrilla);
afirmar("Una cuadrilla tampoco: 403", 403, $codigo);
[$codigo, $cuerpo] = peticion("POST", "/api-gestion/incidencias/$idInc/avance", ["observacion" => "Llegamos"], $cuadrilla);
afirmar("Una cuadrilla no puede dar avance de una que no es suya: 403", 403, $codigo);
[$codigo] = peticion("POST", "/api-gestion/incidencias/$idInc/avance", ["observacion" => "Llegamos"], $admin);
afirmar("El administrador no registra avances, es trabajo de la cuadrilla: 403", 403, $codigo);

[$codigo] = peticion("PUT", "/api-gestion/incidencias/$idInc", ["prioridad" => "urgente"], $admin);
afirmar("Una prioridad inventada: 400", 400, $codigo);
[$codigo, $cuerpo] = peticion("PUT", "/api-gestion/incidencias/$idInc", ["prioridad" => "media"], $admin);
afirmar("El administrador cambia la prioridad (RF-225)", ["media", "abierta"], [$cuerpo["datos"]["prioridad"] ?? null, $cuerpo["datos"]["estado"] ?? null]);
$antes = count(asientos("/api-gestion/incidencias/$idInc", $admin));
afirmar("Y el cambio queda en el historial", "Prioridad de alta a media", ultimoAsiento($cuerpo)["observacion"] ?? null);
peticion("PUT", "/api-gestion/incidencias/$idInc", ["prioridad" => "media"], $admin);
afirmar("Pedir la misma prioridad no agrega otro asiento", $antes, count(asientos("/api-gestion/incidencias/$idInc", $admin)));

[$codigo] = peticion("POST", "/api-gestion/incidencias/$idInc/asignacion", [], $admin);
afirmar("Asignar sin decir la cuadrilla: 400", 400, $codigo);
[$codigo] = peticion("POST", "/api-gestion/incidencias/$idInc/asignacion", ["id_cuadrilla" => 1, "prioridad" => "urgente"], $admin);
afirmar("Asignar con una prioridad inventada: 400", 400, $codigo);
[$codigo] = peticion("POST", "/api-gestion/incidencias/$idInc/asignacion", ["id_cuadrilla" => 99999], $admin);
afirmar("Una cuadrilla que no existe: 404", 404, $codigo);
[$codigo, $cuerpo] = peticion("POST", "/api-gestion/incidencias/$idInc/asignacion", ["id_cuadrilla" => 4], $admin);
afirmar("Una cuadrilla inactiva no se puede asignar: 409 (RF-226)", 409, $codigo);
[$codigo, $cuerpo] = peticion("POST", "/api-gestion/incidencias/$idInc/asignacion", ["id_cuadrilla" => 2], $admin);
afirmar("Una cuadrilla en una recoleccion en curso tampoco: 409 (RF-228)", 409, $codigo);
[$codigo, $cuerpo] = peticion("GET", "/api-gestion/incidencias/$idInc", null, $admin);
afirmar("Y las dos negativas no tocaron la incidencia", ["abierta", null], [$cuerpo["datos"]["estado"] ?? null, $cuerpo["datos"]["id_cuadrilla"] ?? null]);

[$codigo, $cuerpo] = peticion("GET", "/api-recoleccion/cuadrillas?disponibles=1", null, $admin);
$libres = array_map("intval", array_column($cuerpo["datos"] ?? [], "id_cuadrilla"));
sort($libres);
afirmar("Solo se ofrecen las cuadrillas disponibles: activas y fuera de una recoleccion (RF-228)", [1, 3], $libres);
[$codigo, $cuerpo] = peticion("GET", "/api-recoleccion/cuadrillas", null, $admin);
$disponibilidad = array_column($cuerpo["datos"] ?? [], "disponible", "id_cuadrilla");
ksort($disponibilidad);
afirmar("Sin filtro se listan las 4, cada una con su disponibilidad", [1 => true, 2 => false, 3 => true, 4 => false], $disponibilidad);
$ocupadas = array_column($cuerpo["datos"] ?? [], "ocupada", "id_cuadrilla");
ksort($ocupadas);
afirmar("Y se distingue la ocupada (en una recoleccion) de la inactiva", [1 => false, 2 => true, 3 => false, 4 => false], $ocupadas);

[$codigo, $cuerpo] = peticion("POST", "/api-gestion/incidencias/$idInc/asignacion", ["id_cuadrilla" => 1], $admin);
afirmar("Asignar a una cuadrilla disponible: 200", 200, $codigo);
afirmar("La incidencia pasa a en curso, con esa cuadrilla (RF-226, RF-227)", ["en_curso", 1, "Cuadrilla Norte"],
    [$cuerpo["datos"]["estado"] ?? null, $cuerpo["datos"]["id_cuadrilla"] ?? null, $cuerpo["datos"]["cuadrilla"] ?? null]);
afirmar("Y la prioridad que ya tenia se conserva", "media", $cuerpo["datos"]["prioridad"] ?? null);
afirmar("El asiento dice a quien se asigno y quien lo hizo", ["Asignada a la Cuadrilla Norte", "Administrador"],
    [ultimoAsiento($cuerpo)["observacion"] ?? null, ultimoAsiento($cuerpo)["rol"] ?? null]);
[$codigo] = peticion("POST", "/api-gestion/incidencias/$idInc/asignacion", ["id_cuadrilla" => 1], $admin);
afirmar("Asignarla otra vez a la misma cuadrilla: 409", 409, $codigo);

[$codigo, $cuerpo] = peticion("POST", "/api-gestion/incidencias/$idInc/asignacion", ["id_cuadrilla" => 3, "prioridad" => "baja"], $admin);
afirmar("Reasignar a otra cuadrilla disponible, cambiando la prioridad: 200", 200, $codigo);
afirmar("El asiento cuenta de donde a donde y el cambio de prioridad",
    "Reasignada de la Cuadrilla Norte a la Cuadrilla Centro. Prioridad de media a baja", ultimoAsiento($cuerpo)["observacion"] ?? null);
[$codigo, $cuerpo] = peticion("POST", "/api-gestion/incidencias/$idInc/asignacion", ["id_cuadrilla" => 1, "prioridad" => "alta"], $admin);
afirmar("Y vuelve a la Norte para seguir con el recorrido", [200, "alta", 1], [$codigo, $cuerpo["datos"]["prioridad"] ?? null, $cuerpo["datos"]["id_cuadrilla"] ?? null]);

[$codigo, $cuerpo] = peticion("GET", "/api-recoleccion/cuadrillas", null, $admin);
$norte = array_values(array_filter($cuerpo["datos"] ?? [], fn ($c) => $c["id_cuadrilla"] === 1))[0] ?? [];
afirmar("La lista de cuadrillas cuenta sus incidencias en curso", (int) $pdo->query("SELECT COUNT(*) FROM incidencia WHERE id_cuadrilla = 1 AND estado = 'en_curso'")->fetchColumn(), $norte["incidencias_en_curso"] ?? null);


titulo("15. Incidencias: el trabajo de la cuadrilla");

[$codigo, $cuerpo] = peticion("GET", "/api-gestion/incidencias", null, $cuadrilla);
afirmar("La cuadrilla asignada ahora la ve en su lista", true, in_array($idInc, idsDe($cuerpo), true));
[$codigo, $cuerpo] = peticion("GET", "/api-gestion/incidencias", null, $otraCuadrilla);
afirmar("Otra cuadrilla no", false, in_array($idInc, idsDe($cuerpo), true));
[$codigo] = peticion("GET", "/api-gestion/incidencias/$idInc", null, $otraCuadrilla);
afirmar("Ni puede abrir su detalle: 404", 404, $codigo);
[$codigo] = peticion("POST", "/api-gestion/incidencias/$idInc/avance", ["observacion" => "Entro sin permiso"], $otraCuadrilla);
afirmar("Ni registrarle un avance: 403", 403, $codigo);
[$codigo] = peticion("POST", "/api-gestion/incidencias/$idInc/resolucion", ["observacion" => "Entro sin permiso"], $otraCuadrilla);
afirmar("Ni resolverla: 403", 403, $codigo);
[$codigo, $cuerpo] = peticion("GET", "/api-gestion/incidencias/$idInc", null, $cuadrilla);
afirmar("La cuadrilla asignada abre el detalle con su historial (RF-240)", [200, "Cuadrilla Norte"], [$codigo, $cuerpo["datos"]["cuadrilla"] ?? null]);
afirmar("La cuadrilla no ve el nombre de quien reporto", false, isset($cuerpo["datos"]["reportada_por"]));
$primerAsiento = $cuerpo["datos"]["historial"][0] ?? [];
afirmar("Ni en el historial: el primer asiento dice Vecino, sin nombre ni id",
    [null, "Vecino", false], [$primerAsiento["usuario"] ?? null, $primerAsiento["rol"] ?? null, array_key_exists("id_usuario", $primerAsiento)]);
afirmar("El administrador si ve en el historial quien lo informo",
    "Marta Rodriguez", asientos("/api-gestion/incidencias/$idInc", $admin)[0]["usuario"] ?? null);
afirmar("Y el vecino que lo informo se ve a si mismo",
    "Marta Rodriguez", asientos("/api-gestion/incidencias/$idInc", $vecino)[0]["usuario"] ?? null);
afirmar("Pero al personal que lo atendio lo ve por su rol, no por su nombre",
    [], array_values(array_filter(asientos("/api-gestion/incidencias/$idInc", $vecino), fn ($a) => $a["rol"] !== "Vecino" && ($a["usuario"] ?? null) !== null)));

$antes = count($cuerpo["datos"]["historial"] ?? []);
[$codigo] = peticion("POST", "/api-gestion/incidencias/$idInc/avance", [], $cuadrilla);
afirmar("Un avance sin observacion: 400", 400, $codigo);
[$codigo] = peticion("POST", "/api-gestion/incidencias/$idInc/avance", ["observacion" => str_repeat("a", 301)], $cuadrilla);
afirmar("Una observacion de mas de 300 caracteres: 400", 400, $codigo);
[$codigo, $cuerpo] = peticion("POST", "/api-gestion/incidencias/$idInc/avance", ["observacion" => "Estamos en camino"], $cuadrilla);
afirmar("Registrar un avance con su observacion: 200 (RF-230)", 200, $codigo);
afirmar("Queda un asiento mas, en curso, firmado por la cuadrilla", [$antes + 1, "en_curso", "Estamos en camino", "Luis Gomez"], [
    count($cuerpo["datos"]["historial"] ?? []), ultimoAsiento($cuerpo)["estado"] ?? null,
    ultimoAsiento($cuerpo)["observacion"] ?? null, ultimoAsiento($cuerpo)["usuario"] ?? null,
]);

[$codigo, $cuerpo] = peticion("POST", "/api-gestion/incidencias/$idInc/resolucion", ["observacion" => "Contenedor vaciado"], $cuadrilla);
afirmar("La cuadrilla la resuelve: 200 (RF-231)", 200, $codigo);
afirmar("Queda resuelta con su fecha de resolucion", [ "resuelta", true], [$cuerpo["datos"]["estado"] ?? null, !empty($cuerpo["datos"]["fecha_resolucion"])]);
$historial = $cuerpo["datos"]["historial"] ?? [];
afirmar("El historial cuenta la historia completa, en orden (RF-232)",
    ["abierta", "abierta", "en_curso", "en_curso", "en_curso", "en_curso", "resuelta"], array_column($historial, "estado"));
$fechas = array_column($historial, "fecha_hora");
$ordenadas = $fechas;
sort($ordenadas);
$historialCompleto = asientos("/api-gestion/incidencias/$idInc", $admin);
afirmar("Cada asiento tiene fecha, orden cronologico y autor (RNF-017, visto por el administrador)", [true, true], [
    $fechas === $ordenadas,
    count(array_filter($historialCompleto, fn ($a) => ($a["usuario"] ?? "") !== "" && ($a["id_usuario"] ?? 0) > 0)) === count($historial),
]);
[$codigo, $cuerpo] = peticion("GET", "/api-gestion/contenedores/$idContInc", null, $admin);
afirmar("Al cerrar la ultima incidencia el contenedor vuelve a disponible", "disponible", $cuerpo["datos"]["estado"] ?? null);
[$codigo, $cuerpo] = peticion("GET", "/api-gestion/contenedores/$idContInc/historial", null, $admin);
afirmar("Con su asiento, que cita el numero", true, in_array("$numero resuelta", array_column($cuerpo["datos"] ?? [], "observacion"), true));

foreach ([
    "avance" => ["observacion" => "tarde"], "resolucion" => ["observacion" => "tarde"],
    "asignacion" => ["id_cuadrilla" => 1], "descarte" => ["observacion" => "tarde"],
] as $accion => $cuerpoPeticion) {
    [$codigo, $cuerpo] = peticion("POST", "/api-gestion/incidencias/$idInc/$accion", $cuerpoPeticion, $accion === "avance" ? $cuadrilla : $admin);
    afirmar("Una incidencia ya resuelta no admite '$accion': 409", [409, "La incidencia ya está resuelta y no admite más cambios"], [$codigo, $cuerpo["mensaje"] ?? null]);
}
[$codigo] = peticion("PUT", "/api-gestion/incidencias/$idInc", ["prioridad" => "baja"], $admin);
afirmar("Ni un cambio de prioridad: 409", 409, $codigo);


titulo("16. Incidencias: el contenedor se libera solo cuando no queda nada abierto");

[$codigo, $cuerpo] = peticion("POST", "/api-gestion/incidencias", $reporte, $vecino);
afirmar("Con la anterior ya resuelta, el mismo problema se puede volver a informar: 201", 201, $codigo);
$idDesborde = $cuerpo["datos"]["id_incidencia"] ?? 0;
[$codigo, $cuerpo] = peticion("POST", "/api-gestion/incidencias",
    ["tipo_problema" => "roto", "descripcion" => "Ademas tiene la tapa rota"] + $reporte, $vecino);
afirmar("Y otro problema distinto sobre el mismo contenedor tambien: 201", 201, $codigo);
$idRoto = $cuerpo["datos"]["id_incidencia"] ?? 0;
[$codigo, $cuerpo] = peticion("GET", "/api-gestion/contenedores/$idContInc", null, $admin);
afirmar("El contenedor esta lleno por el desborde", "lleno", $cuerpo["datos"]["estado"] ?? null);

[$codigo, $cuerpo] = peticion("POST", "/api-gestion/incidencias/$idDesborde/resolucion", [], $admin);
afirmar("El administrador resuelve el desborde sin asignarlo (la observacion es opcional): 200", 200, $codigo);
afirmar("Se guarda una observacion por omision", "Incidencia resuelta", ultimoAsiento($cuerpo)["observacion"] ?? null);
[$codigo, $cuerpo] = peticion("GET", "/api-gestion/contenedores/$idContInc", null, $admin);
afirmar("El contenedor sigue lleno: queda el reporte de la tapa abierto", "lleno", $cuerpo["datos"]["estado"] ?? null);

[$codigo] = peticion("POST", "/api-gestion/incidencias/$idRoto/descarte", [], $admin);
afirmar("Descartar sin decir el motivo: 400", 400, $codigo);
[$codigo, $cuerpo] = peticion("POST", "/api-gestion/incidencias/$idRoto/descarte", ["observacion" => "La tapa estaba bien"], $admin);
afirmar("Descartar con su motivo: 200", [200, "descartada"], [$codigo, $cuerpo["datos"]["estado"] ?? null]);
afirmar("Una descartada tambien lleva fecha de cierre", true, !empty($cuerpo["datos"]["fecha_resolucion"]));
[$codigo, $cuerpo] = peticion("GET", "/api-gestion/contenedores/$idContInc", null, $admin);
afirmar("Cerrada la ultima, el contenedor vuelve a disponible", "disponible", $cuerpo["datos"]["estado"] ?? null);
[$codigo, $cuerpo] = peticion("GET", "/api-gestion/contenedores/$idContInc/historial", null, $admin);
afirmar("El historial del contenedor cuenta todo: alta, lleno, disponible, lleno, disponible",
    ["disponible", "lleno", "disponible", "lleno", "disponible"], array_column(array_reverse($cuerpo["datos"] ?? []), "estado"));


titulo("17. Estado del contenedor: un solo camino, con asiento y sin duplicados");

[$codigo] = peticion("PUT", "/api-gestion/contenedores/$idContInc", ["estado" => "mantenimiento"], $admin);
afirmar("Cambiar el estado a mano: 200", 200, $codigo);
[$codigo, $cuerpo] = peticion("GET", "/api-gestion/contenedores/$idContInc/historial", null, $admin);
$asientosContenedor = count($cuerpo["datos"] ?? []);
afirmar("Deja su asiento (RF-233)", ["mantenimiento", "Cambio de estado desde el panel"], [$cuerpo["datos"][0]["estado"] ?? null, $cuerpo["datos"][0]["observacion"] ?? null]);
peticion("PUT", "/api-gestion/contenedores/$idContInc", ["estado" => "mantenimiento", "direccion" => "Calle de prueba de incidencias 2"], $admin);
[$codigo, $cuerpo] = peticion("GET", "/api-gestion/contenedores/$idContInc/historial", null, $admin);
afirmar("Reenviar el mismo estado no duplica el asiento (CU-027 A1.0)", $asientosContenedor, count($cuerpo["datos"] ?? []));
[$codigo, $cuerpo] = peticion("GET", "/api-gestion/contenedores/$idContInc", null, $admin);
afirmar("Pero lo demas que vino en el pedido si se guarda", ["mantenimiento", "Calle de prueba de incidencias 2"], [$cuerpo["datos"]["estado"] ?? null, $cuerpo["datos"]["direccion"] ?? null]);

$intentar = function (string $sql) use ($pdo): string {
    try {
        $pdo->exec($sql);
        return "se pudo";
    } catch (PDOException $e) {
        return str_contains($e->getMessage(), "solo agregado") ? "bloqueado" : $e->getMessage();
    }
};
afirmar("La base impide modificar o borrar un asiento del historial del contenedor (RF-235, RNF-016)", ["bloqueado", "bloqueado"], [
    $intentar("UPDATE estado_contenedor SET observacion = 'x' WHERE id_contenedor = $idContInc"),
    $intentar("DELETE FROM estado_contenedor WHERE id_contenedor = $idContInc"),
]);
afirmar("Y lo mismo con el historial de las incidencias", ["bloqueado", "bloqueado"], [
    $intentar("UPDATE historial_incidencia SET observacion = 'x' WHERE id_incidencia = $idInc"),
    $intentar("DELETE FROM historial_incidencia WHERE id_incidencia = $idInc"),
]);
afirmar("Siguen todos los asientos de la incidencia (el intento no borro nada)", 7,
    (int) $pdo->query("SELECT COUNT(*) FROM historial_incidencia WHERE id_incidencia = $idInc")->fetchColumn());

$zona = "ZTX-$sufijo";
try {
    Database::transaccion(function (PDO $c) use ($zona) {
        $c->exec("INSERT INTO zona (nombre) VALUES ('$zona')");
        Database::transaccion(function (PDO $dentro) {
            $dentro->exec("INSERT INTO zona (nombre) VALUES ('ZTX-anidada')");
            throw new RuntimeException("falla a mitad del trabajo");
        });
    });
} catch (RuntimeException $e) {
}
afirmar("Si algo falla a mitad de una transaccion, ni lo de afuera ni lo de adentro queda guardado", 0,
    (int) $pdo->query("SELECT COUNT(*) FROM zona WHERE nombre IN ('$zona', 'ZTX-anidada')")->fetchColumn());
Database::transaccion(fn (PDO $c) => $c->exec("INSERT INTO zona (nombre) VALUES ('$zona')"));
afirmar("Y si todo sale bien, se guarda", 1, (int) $pdo->query("SELECT COUNT(*) FROM zona WHERE nombre = '$zona'")->fetchColumn());
$pdo->exec("DELETE FROM zona WHERE nombre = '$zona'");
