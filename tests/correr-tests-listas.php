<?php

// Listas con filtros y paginacion en el servidor, y contenedores con datos validados (parte 9).
// No crea nada: las altas invalidas tienen que rechazarse y las listas solo se leen.

titulo("22f. Contenedores: ya no hay valores por omision silenciosos");

$altaValida = ["codigo" => "TVAL-$sufijo", "direccion" => "Calle de validacion 1", "id_zona" => 1, "tipo_residuo" => "Vidrio"];
$rechazos = [
    "sin zona"                  => array_diff_key($altaValida, ["id_zona" => 1]),
    "sin tipo de residuo"       => array_diff_key($altaValida, ["tipo_residuo" => 1]),
    "con una zona inexistente"  => ["id_zona" => 99999] + $altaValida,
    "con una zona por nombre inexistente" => ["id_zona" => null, "zona" => "Atlantida"] + $altaValida,
    "con un tipo inexistente"   => ["tipo_residuo" => "Antimateria"] + $altaValida,
    "con capacidad negativa"    => ["capacidad" => -5] + $altaValida,
    "con capacidad en texto"    => ["capacidad" => "mucha"] + $altaValida,
    "con capacidad enorme"      => ["capacidad" => 100001] + $altaValida,
    "con latitud sola"          => ["latitud" => "-34.9"] + $altaValida,
    "con latitud imposible"     => ["latitud" => "95", "longitud" => "-56"] + $altaValida,
    "con un codigo con espacios" => ["codigo" => "TVAL 1"] + $altaValida,
    "con un codigo larguisimo"  => ["codigo" => str_repeat("A", 31)] + $altaValida,
];
foreach ($rechazos as $descripcion => $datos) {
    [$codigo] = peticion("POST", "/api-gestion/contenedores", $datos, $admin);
    afirmar("Un alta $descripcion: 400", 400, $codigo);
}
[, $cuerpo] = peticion("GET", "/api-gestion/contenedores?q=TVAL", null, $admin);
afirmar("Y ninguna de esas altas dejo un contenedor", 0, count($cuerpo["datos"] ?? [-1]));

foreach ([["capacidad" => -3], ["tipo_residuo" => "Antimateria"], ["id_zona" => 99999], ["latitud" => "10"], ["codigo" => ""]] as $edicion) {
    [$codigo] = peticion("PUT", "/api-gestion/contenedores/1", $edicion, $admin);
    afirmar("Editar con " . json_encode($edicion) . ": 400", 400, $codigo);
}
[$codigo, $cuerpo] = peticion("GET", "/api-gestion/contenedores/1", null, $admin);
afirmar("Y el contenedor 1 quedo como estaba", ["CNT-001", true], [$cuerpo["datos"]["codigo"] ?? null, ($cuerpo["datos"]["capacidad"] ?? 0) > 0]);

titulo("22g. Listas: filtros y paginacion en el servidor");

[, $todos] = peticion("GET", "/api-gestion/contenedores", null, $admin);
$cantidad = count($todos["datos"] ?? []);
afirmar("Sin 'pagina' la lista viene entera y sin meta (como siempre)", [true, false], [$cantidad >= 9, isset($todos["meta"])]);

[$codigo, $p1] = peticion("GET", "/api-gestion/contenedores?pagina=1&por_pagina=4", null, $admin);
afirmar("La primera pagina trae 4 y el total de todos", [200, 4, $cantidad], [$codigo, count($p1["datos"] ?? []), $p1["meta"]["total"] ?? null]);
afirmar("El meta de la primera pagina", [1, 4, 1, 4], [$p1["meta"]["pagina"] ?? null, $p1["meta"]["por_pagina"] ?? null, $p1["meta"]["desde"] ?? null, $p1["meta"]["hasta"] ?? null]);
$vistos = array_column($p1["datos"] ?? [], "id_contenedor");
$paginas = $p1["meta"]["paginas"] ?? 0;
for ($n = 2; $n <= $paginas; $n++) {
    [, $pn] = peticion("GET", "/api-gestion/contenedores?pagina=$n&por_pagina=4", null, $admin);
    $vistos = array_merge($vistos, array_column($pn["datos"] ?? [], "id_contenedor"));
}
afirmar("Recorriendo todas las paginas salen todos los contenedores, una vez cada uno",
    array_column($todos["datos"] ?? [], "id_contenedor"), $vistos);
[, $fuera] = peticion("GET", "/api-gestion/contenedores?pagina=999&por_pagina=4", null, $admin);
afirmar("Una pagina que no existe viene vacia, con el meta", [[], 0], [$fuera["datos"] ?? null, $fuera["meta"]["desde"] ?? null]);
[$codigo] = peticion("GET", "/api-gestion/contenedores?pagina=1", null, $vecino);
afirmar("Un vecino tambien puede paginar contenedores", 200, $codigo);

foreach (["pagina=0", "pagina=x", "pagina=1&por_pagina=0", "pagina=1&por_pagina=101", "estado=volando", "zona=abc", "q=" . str_repeat("a", 61)] as $mala) {
    [$codigo] = peticion("GET", "/api-gestion/contenedores?$mala", null, $admin);
    afirmar("Consulta invalida '" . substr($mala, 0, 30) . "': 400", 400, $codigo);
}

[, $llenos] = peticion("GET", "/api-gestion/contenedores?estado=lleno", null, $admin);
$esperadosLlenos = array_values(array_filter($todos["datos"] ?? [], fn ($c) => $c["estado"] === "lleno"));
afirmar("El filtro por estado coincide con filtrar la lista entera", array_column($esperadosLlenos, "id_contenedor"), array_column($llenos["datos"] ?? [], "id_contenedor"));
[, $zona] = peticion("GET", "/api-gestion/contenedores?zona=3", null, $admin);
afirmar("El filtro por zona", array_values(array_column(array_filter($todos["datos"] ?? [], fn ($c) => (int) $c["id_zona"] === 3), "id_contenedor")),
    array_column($zona["datos"] ?? [], "id_contenedor"));
[, $vidrio] = peticion("GET", "/api-gestion/contenedores?tipo=Vidrio", null, $admin);
afirmar("El filtro por tipo", array_values(array_column(array_filter($todos["datos"] ?? [], fn ($c) => $c["tipo_residuo"] === "Vidrio"), "id_contenedor")),
    array_column($vidrio["datos"] ?? [], "id_contenedor"));
[, $busca] = peticion("GET", "/api-gestion/contenedores?q=cnt-00", null, $admin);
afirmar("La busqueda ignora mayusculas y encuentra por codigo", true, count($busca["datos"] ?? []) >= 9);
[, $porZona] = peticion("GET", "/api-gestion/contenedores?q=pocitos", null, $admin);
afirmar("La busqueda tambien mira la zona", true, count($porZona["datos"] ?? []) >= 1 && count($porZona["datos"]) < $cantidad);
[, $comodin] = peticion("GET", "/api-gestion/contenedores?q=" . rawurlencode("%"), null, $admin);
afirmar("Un % en la busqueda no es comodin", 0, count($comodin["datos"] ?? [-1]));
[, $inyeccion] = peticion("GET", "/api-gestion/contenedores?q=" . rawurlencode("' OR 1=1 --"), null, $admin);
afirmar("Ni una inyeccion SQL en la busqueda", 0, count($inyeccion["datos"] ?? [-1]));
[, $combinado] = peticion("GET", "/api-gestion/contenedores?estado=disponible&q=cnt&pagina=1&por_pagina=2", null, $admin);
afirmar("Filtros y pagina juntos: el total es el del filtro", true, ($combinado["meta"]["total"] ?? 0) === count(array_filter($todos["datos"] ?? [], fn ($c) => $c["estado"] === "disponible")));

[, $usuarios] = peticion("GET", "/api-usuarios/usuarios", null, $admin);
$cantidadUsuarios = count($usuarios["datos"] ?? []);
afirmar("La lista de usuarios sin pagina viene entera y sin meta", [true, false], [$cantidadUsuarios >= 6, isset($usuarios["meta"])]);
[, $u1] = peticion("GET", "/api-usuarios/usuarios?pagina=1&por_pagina=3", null, $admin);
afirmar("Usuarios paginados", [3, $cantidadUsuarios], [count($u1["datos"] ?? []), $u1["meta"]["total"] ?? null]);
[, $vecinos] = peticion("GET", "/api-usuarios/usuarios?rol=Vecino", null, $admin);
afirmar("Usuarios por rol", array_values(array_column(array_filter($usuarios["datos"] ?? [], fn ($u) => $u["rol"] === "Vecino"), "id_usuario")), array_column($vecinos["datos"] ?? [], "id_usuario"));
[, $inactivos] = peticion("GET", "/api-usuarios/usuarios?estado=inactivo", null, $admin);
afirmar("Usuarios por estado", array_values(array_column(array_filter($usuarios["datos"] ?? [], fn ($u) => $u["estado"] === "inactivo"), "id_usuario")), array_column($inactivos["datos"] ?? [], "id_usuario"));
[, $porCorreo] = peticion("GET", "/api-usuarios/usuarios?q=admin@sigeru", null, $admin);
afirmar("Usuarios: la busqueda encuentra por correo", ["admin@sigeru.uy"], array_column($porCorreo["datos"] ?? [], "email"));
foreach (["rol=Rey", "estado=volando", "pagina=0"] as $mala) {
    [$codigo] = peticion("GET", "/api-usuarios/usuarios?$mala", null, $admin);
    afirmar("Usuarios con '$mala': 400", 400, $codigo);
}
[$codigo] = peticion("GET", "/api-usuarios/usuarios?pagina=1", null, $vecino);
afirmar("Un vecino no lista usuarios, tampoco paginados (403)", 403, $codigo);
[$codigo] = peticion("GET", "/api-usuarios/usuarios?pagina=1");
afirmar("Sin sesion (401)", 401, $codigo);

titulo("22h. Centros, maquinaria y camiones: datos validados");

$invalidos = [
    ["POST", "/api-gestion/centros", ["nombre" => "TCEN-$sufijo", "estado" => "volando"], "un centro con estado inventado"],
    ["POST", "/api-gestion/centros", ["nombre" => "TCEN-$sufijo", "capacidad" => -1], "un centro con capacidad negativa"],
    ["POST", "/api-gestion/centros", ["nombre" => "TCEN-$sufijo", "latitud" => "95", "longitud" => "10"], "un centro con latitud imposible"],
    ["POST", "/api-gestion/centros", ["nombre" => "TCEN-$sufijo", "latitud" => "-34"], "un centro con latitud sola"],
    ["POST", "/api-gestion/centros", ["nombre" => str_repeat("n", 121)], "un centro con nombre larguisimo"],
    ["POST", "/api-gestion/maquinaria", ["codigo" => "MAQ <$sufijo>", "id_centro" => 1], "una maquinaria con codigo con simbolos"],
    ["POST", "/api-gestion/maquinaria", ["codigo" => "TMAQ-$sufijo", "id_centro" => 1, "marca" => str_repeat("m", 61)], "una maquinaria con marca larguisima"],
    ["POST", "/api-recoleccion/camiones", ["matricula" => "TCAM;$sufijo"], "un camion con matricula con simbolos"],
    ["POST", "/api-recoleccion/camiones", ["matricula" => "TCAM-$sufijo", "capacidad" => -4], "un camion con capacidad negativa"],
    ["POST", "/api-recoleccion/camiones", ["matricula" => "TCAM-$sufijo", "capacidad" => "pesada"], "un camion con capacidad en texto"],
    ["POST", "/api-recoleccion/camiones", ["matricula" => "TCAM-$sufijo", "estado" => "volando"], "un camion con estado inventado"],
];
foreach ($invalidos as [$metodo, $ruta, $datos, $descripcion]) {
    [$codigo] = peticion($metodo, $ruta, $datos, $admin);
    afirmar("Un alta de $descripcion: 400", 400, $codigo);
}
[, $cuerpo] = peticion("GET", "/api-gestion/centros", null, $admin);
afirmar("Ninguna de esas altas dejo un centro", 0, count(array_filter($cuerpo["datos"] ?? [], fn ($c) => str_starts_with($c["nombre"], "TCEN-"))));
[, $cuerpo] = peticion("GET", "/api-recoleccion/camiones", null, $admin);
afirmar("Ni un camion", 0, count(array_filter($cuerpo["datos"] ?? [], fn ($c) => str_starts_with($c["matricula"], "TCAM"))));

foreach ([["/api-gestion/centros/1", ["capacidad" => "mucha"]], ["/api-gestion/centros/1", ["estado" => "volando"]], ["/api-gestion/centros/1", ["nombre" => ""]]] as [$ruta, $edicion]) {
    [$codigo] = peticion("PUT", $ruta, $edicion, $admin);
    afirmar("Editar el centro 1 con " . json_encode($edicion) . ": 400", 400, $codigo);
}
[$codigo] = peticion("PUT", "/api-recoleccion/camiones/1", ["capacidad" => -1], $admin);
afirmar("Editar un camion con capacidad negativa: 400", 400, $codigo);
[$codigo] = peticion("PUT", "/api-gestion/maquinaria/1", ["codigo" => "MAQ <x>"], $admin);
afirmar("Editar una maquinaria con codigo con simbolos: 400", 400, $codigo);
[$codigo, $cuerpo] = peticion("GET", "/api-gestion/centros/1", null, $admin);
afirmar("Y el centro 1 sigue activo y con nombre", [200, "activo", true], [$codigo, $cuerpo["datos"]["estado"] ?? null, ($cuerpo["datos"]["nombre"] ?? "") !== ""]);
