<?php

// Listas con busqueda y paginacion en el servidor, y validacion de contenedores (parte 9).

require_once __DIR__ . "/../api-gestion/models/Paginacion.php";
require_once __DIR__ . "/../api-gestion/models/ValidacionContenedor.php";

function enOtroProceso(string $requerido, string $codigo)
{
    $guion = tempnam(sys_get_temp_dir(), "lst");
    file_put_contents($guion, '<?php require ' . var_export($requerido, true) . '; echo json_encode(' . $codigo . ');');
    $salida = shell_exec(escapeshellarg(PHP_BINARY) . " " . escapeshellarg($guion));
    unlink($guion);
    return json_decode((string) $salida, true);
}

titulo("14. Listas: paginacion y busqueda");

afirmar("Sin 'pagina' no se pagina (el mapa y los formularios siguen recibiendo todo)", false, Paginacion::de(["estado" => "lleno"])["paginar"]);
afirmar("Con 'pagina' se pagina y el tamano por omision es 25", [true, 3, 25], (function () {
    $p = Paginacion::de(["pagina" => "3"]);
    return [$p["paginar"], $p["pagina"], $p["por_pagina"]];
})());
afirmar("Tamano de pagina elegido", 50, Paginacion::de(["pagina" => "1", "por_pagina" => "50"])["por_pagina"]);
foreach (["0", "-1", "abc", "1.5", "", "1000000000"] as $malo) {
    afirmar("Una pagina '$malo' se rechaza", true, Paginacion::de(["pagina" => $malo])["error"] !== null);
}
foreach (["0", "101", "x"] as $malo) {
    afirmar("Un por_pagina '$malo' se rechaza", true, Paginacion::de(["pagina" => "1", "por_pagina" => $malo])["error"] !== null);
}
afirmar("Un por_pagina suelto tambien pagina", true, Paginacion::de(["por_pagina" => "10"])["paginar"]);

afirmar("Meta de la primera pagina", ["total" => 37, "pagina" => 1, "por_pagina" => 10, "paginas" => 4, "desde" => 1, "hasta" => 10], Paginacion::meta(37, 1, 10));
afirmar("Meta de la ultima pagina", ["total" => 37, "pagina" => 4, "por_pagina" => 10, "paginas" => 4, "desde" => 31, "hasta" => 37], Paginacion::meta(37, 4, 10));
afirmar("Meta de una pagina que no existe", ["total" => 37, "pagina" => 9, "por_pagina" => 10, "paginas" => 4, "desde" => 0, "hasta" => 0], Paginacion::meta(37, 9, 10));
afirmar("Meta sin resultados", ["total" => 0, "pagina" => 1, "por_pagina" => 25, "paginas" => 1, "desde" => 0, "hasta" => 0], Paginacion::meta(0, 1, 25));
afirmar("Meta con el total justo", 3, Paginacion::meta(30, 1, 10)["paginas"]);

afirmar("Busqueda vacia = sin busqueda", [null, null], array_values(Paginacion::busqueda("   ")));
afirmar("Busqueda con espacios de mas", "pocitos", Paginacion::busqueda("  pocitos ")["texto"]);
afirmar("Busqueda demasiado larga", true, Paginacion::busqueda(str_repeat("a", 61))["error"] !== null);
afirmar("Busqueda que no es texto", true, Paginacion::busqueda(["a"])["error"] !== null);
afirmar("% y _ del usuario no son comodines", "%100" . chr(92) . "%%", Paginacion::paraLike("100%"));
afirmar("La barra tambien se escapa", "%a" . chr(92) . chr(92) . "b%", Paginacion::paraLike("a" . chr(92) . "b"));
afirmar(
    "Paginacion es identica en api-gestion y api-usuarios",
    true,
    file_get_contents(__DIR__ . "/../api-gestion/models/Paginacion.php") === file_get_contents(__DIR__ . "/../api-usuarios/core/Paginacion.php")
);

titulo("15. Listas: filtros de usuarios y de contenedores");

$ejecutar = fn (array $c) => enOtroProceso(__DIR__ . "/../api-usuarios/core/FiltrosUsuario.php", "FiltrosUsuario::de(" . var_export($c, true) . ")");
afirmar("Filtros de usuarios validos", ["filtros" => ["q" => "ana", "rol" => "Vecino", "estado" => "pendiente"], "error" => null],
    $ejecutar(["q" => " ana ", "rol" => "Vecino", "estado" => "pendiente"]));
afirmar("Sin filtros", ["filtros" => [], "error" => null], $ejecutar([]));
afirmar("Un rol inexistente", true, $ejecutar(["rol" => "Rey"])["error"] !== null);
afirmar("Un estado inexistente", true, $ejecutar(["estado" => "volando"])["error"] !== null);
afirmar("Un rol que es una lista", true, $ejecutar(["rol" => ["Vecino"]])["error"] !== null);

afirmar("Filtros de contenedores validos", ["filtros" => ["q" => "italia", "estado" => "lleno", "zona" => 2, "tipo" => "Vidrio"], "error" => null],
    ValidacionContenedor::filtrosDe(["q" => "italia", "estado" => "lleno", "zona" => "2", "tipo" => "Vidrio"]));
foreach ([["estado" => "volando"], ["zona" => "abc"], ["zona" => "0"], ["tipo" => ["x"]], ["q" => str_repeat("a", 70)]] as $malo) {
    afirmar("Filtro de contenedores invalido: " . json_encode($malo), true, ValidacionContenedor::filtrosDe($malo)["error"] !== null);
}

titulo("16. Contenedores: validacion de los datos");

$alta = ["codigo" => "CNT-100", "direccion" => "Av. Italia 1234", "id_zona" => 1, "tipo_residuo" => "Vidrio"];
afirmar("Un alta completa sirve", null, ValidacionContenedor::error($alta, true));
afirmar("Un alta con capacidad y ubicacion sirve", null, ValidacionContenedor::error($alta + ["capacidad" => "1100", "latitud" => "-34.9", "longitud" => -56.1], true));
afirmar("Una capacidad vacia es 'sin dato'", null, ValidacionContenedor::error($alta + ["capacidad" => ""], true));
foreach (["codigo", "direccion"] as $falta) {
    $datos = $alta;
    unset($datos[$falta]);
    afirmar("Falta $falta", "Faltan datos: código y dirección", ValidacionContenedor::error($datos, true));
}
afirmar("Codigo con espacios y simbolos", true, ValidacionContenedor::error(["codigo" => "CNT 1;--"] + $alta, true) !== null);
afirmar("Codigo de mas de 30 caracteres", true, ValidacionContenedor::error(["codigo" => str_repeat("A", 31)] + $alta, true) !== null);
afirmar("Direccion de mas de 200 caracteres", true, ValidacionContenedor::error(["direccion" => str_repeat("a", 201)] + $alta, true) !== null);
foreach (["0", "-5", "1.5", "mucha", "100001", 0, -1] as $capacidad) {
    afirmar("Capacidad invalida: " . var_export($capacidad, true), true, ValidacionContenedor::error(["capacidad" => $capacidad] + $alta, true) !== null);
}
afirmar("La capacidad maxima sirve", null, ValidacionContenedor::error(["capacidad" => 100000] + $alta, true));
afirmar("La capacidad minima sirve", null, ValidacionContenedor::error(["capacidad" => 1] + $alta, true));
afirmar("Latitud sin longitud", "La ubicación necesita latitud y longitud juntas", ValidacionContenedor::error($alta + ["latitud" => "-34.9"], true));
afirmar("Latitud fuera de rango", true, ValidacionContenedor::error($alta + ["latitud" => "95", "longitud" => "-56"], true) !== null);
afirmar("Longitud fuera de rango", true, ValidacionContenedor::error($alta + ["latitud" => "-34", "longitud" => "-181"], true) !== null);
afirmar("Coordenadas que no son numeros", true, ValidacionContenedor::error($alta + ["latitud" => "norte", "longitud" => "oeste"], true) !== null);
afirmar("Un estado inexistente", true, ValidacionContenedor::error($alta + ["estado" => "volando"], true) !== null);
afirmar("Un alta sin zona ya no usa 'la primera'", "Indicá la zona del contenedor", ValidacionContenedor::error(["id_zona" => null] + $alta, true));
afirmar("Una zona por nombre sirve", null, ValidacionContenedor::error(["id_zona" => null, "zona" => "Centro"] + $alta, true));
afirmar("Un alta sin tipo ya no usa 'Mixto'", "Indicá el tipo de residuo del contenedor", ValidacionContenedor::error(["tipo_residuo" => ""] + $alta, true));
afirmar("Una zona que no es un numero", true, ValidacionContenedor::error(["id_zona" => "uno"] + $alta, true) !== null);
afirmar("En una edicion no hace falta mandar todo", null, ValidacionContenedor::error(["capacidad" => 900], false));
afirmar("Pero lo que se manda se valida", true, ValidacionContenedor::error(["capacidad" => -3], false) !== null);
afirmar("Una edicion puede vaciar la ubicacion", null, ValidacionContenedor::error(["latitud" => "", "longitud" => ""], false));
afirmar("Una edicion no puede dejar el codigo vacio", true, ValidacionContenedor::error(["codigo" => ""], false) !== null);

titulo("17. Listas: pantallas que filtran y paginan en el servidor");

$frontListas = __DIR__ . "/../frontend-usuarios";
foreach (["contenedores" => "js/contenedores.js", "usuarios" => "js/usuarios.js", "reportes" => "js/reportes.js"] as $pantalla => $script) {
    $html = (string) file_get_contents("$frontListas/$pantalla.html");
    afirmar("$pantalla.html carga lista.js antes de su propio script", true,
        strpos($html, 'src="js/lista.js"') !== false && strpos($html, 'src="js/lista.js"') < strpos($html, 'src="' . $script . '"'));
}
$cuenta = fn (string $archivo, string $patron) => preg_match_all($patron, (string) file_get_contents("$frontListas/$archivo"));
afirmar("Las listas con filtros en el servidor ya no usan el buscador del navegador (ese filtraria solo la pagina)", [false, false],
    [str_contains((string) file_get_contents("$frontListas/contenedores.html"), 'id="buscador"'), str_contains((string) file_get_contents("$frontListas/usuarios.html"), 'id="buscador"')]);

titulo("18. Validacion de centros, maquinaria y camiones");

require_once __DIR__ . "/../api-gestion/models/ValidacionCentro.php";
require_once __DIR__ . "/../api-gestion/models/ValidacionMaquinaria.php";

afirmar("Campos es identico en api-gestion y api-recoleccion", true,
    file_get_contents(__DIR__ . "/../api-gestion/models/Campos.php") === file_get_contents(__DIR__ . "/../api-recoleccion/models/Campos.php"));

$centro = ["nombre" => "Centro Norte", "direccion" => "Calle 1", "capacidad" => 5000, "latitud" => "-34.8", "longitud" => "-56.1", "estado" => "activo"];
afirmar("Un centro completo sirve", null, ValidacionCentro::error($centro, true));
afirmar("Un centro solo con nombre sirve", null, ValidacionCentro::error(["nombre" => "Centro"], true));
afirmar("Un centro sin nombre", "Falta el nombre del centro de acopio", ValidacionCentro::error(["direccion" => "x"], true));
afirmar("Un nombre de mas de 120 caracteres", true, ValidacionCentro::error(["nombre" => str_repeat("n", 121)], true) !== null);
afirmar("Un estado inventado ya no se cambia en silencio por 'activo'", true, ValidacionCentro::error(["estado" => "volando"] + $centro, true) !== null);
foreach (["-1", "0", "mucha", 10000001, "1.5"] as $capacidad) {
    afirmar("Capacidad de centro invalida: " . var_export($capacidad, true), true, ValidacionCentro::error(["capacidad" => $capacidad] + $centro, true) !== null);
}
afirmar("Latitud sin longitud en un centro", true, ValidacionCentro::error(["longitud" => ""] + $centro, true) !== null);
afirmar("Una edicion parcial de un centro sirve", null, ValidacionCentro::error(["capacidad" => 6000], false));
afirmar("Una edicion no puede dejar el nombre vacio", true, ValidacionCentro::error(["nombre" => ""], false) !== null);

afirmar("Una maquinaria con codigo sirve", null, ValidacionMaquinaria::error(["codigo" => "MAQ-1", "marca" => "Bomag"], true));
afirmar("Una maquinaria sin codigo", "Falta el código de la maquinaria", ValidacionMaquinaria::error(["marca" => "Bomag"], true));
afirmar("Codigo de maquinaria con caracteres raros", true, ValidacionMaquinaria::error(["codigo" => "MAQ <1>"], true) !== null);
afirmar("Marca demasiado larga", true, ValidacionMaquinaria::error(["codigo" => "M1", "marca" => str_repeat("m", 61)], true) !== null);
afirmar("Marca que no es texto", true, ValidacionMaquinaria::error(["codigo" => "M1", "marca" => ["x"]], true) !== null);

$camion = fn (array $d, bool $alta) => enOtroProceso(__DIR__ . "/../api-recoleccion/models/ValidacionCamion.php", "ValidacionCamion::error(" . var_export($d, true) . ", " . var_export($alta, true) . ")");
afirmar("Un camion con matricula sirve", null, $camion(["matricula" => "SBA 1234", "capacidad" => "12000", "estado" => "operativo"], true));
afirmar("Un camion sin matricula", "Falta la matrícula", $camion(["marca" => "Volvo"], true));
afirmar("Matricula con simbolos", true, $camion(["matricula" => "SBA;1234"], true) !== null);
afirmar("Matricula de mas de 20 caracteres", true, $camion(["matricula" => str_repeat("A", 21)], true) !== null);
afirmar("Capacidad de camion invalida", [true, true, true], [$camion(["matricula" => "A1", "capacidad" => "-4"], true) !== null, $camion(["matricula" => "A1", "capacidad" => "100001"], true) !== null, $camion(["matricula" => "A1", "capacidad" => "pesada"], true) !== null]);
afirmar("Estado de camion invalido", true, $camion(["matricula" => "A1", "estado" => "volando"], true) !== null);
afirmar("Una edicion de camion puede mandar solo el estado", null, $camion(["estado" => "mantenimiento"], false));

titulo("19. Clave de prueba del seed");

require_once __DIR__ . "/../api-usuarios/core/Validacion.php";

$sembrado = (string) file_get_contents(__DIR__ . "/../database/datos_prueba.sql");
preg_match_all("/'([A-Za-z0-9.@_-]+@[A-Za-z0-9.-]+)',\s*'(\\$2y\\$10\\$[^']+)'/", $sembrado, $filasSembradas, PREG_SET_ORDER);
afirmar("El seed tiene los 6 usuarios con hash bcrypt", 6, count($filasSembradas));
afirmar("La clave de prueba cumple la politica de contrasenas", true, Validacion::contrasena("sigeru2026"));
afirmar("Y la vieja de 6 caracteres no la cumple", false, Validacion::contrasena("123456"));
afirmar("Todos los usuarios del seed entran con la clave de prueba", [], array_values(array_filter(
    array_map(fn ($f) => $f[1], $filasSembradas),
    fn ($email) => !password_verify("sigeru2026", array_column($filasSembradas, 2, 1)[$email])
)));
afirmar("Ninguno conserva la clave de 6 caracteres", [], array_values(array_filter(
    array_map(fn ($f) => $f[1], $filasSembradas),
    fn ($email) => password_verify("123456", array_column($filasSembradas, 2, 1)[$email])
)));
afirmar("Cada usuario tiene su propio hash (cada uno con su sal)", 6, count(array_unique(array_column($filasSembradas, 2))));
