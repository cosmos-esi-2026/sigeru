<?php

require_once __DIR__ . "/../database/lib/Migraciones.php";
require_once __DIR__ . "/../api-gestion/core/Permisos.php";
require_once __DIR__ . "/../api-gestion/models/FlujoIncidencia.php";
require_once __DIR__ . "/../api-usuarios/core/PoliticaBloqueo.php";
require_once __DIR__ . "/../api-recoleccion/models/FlujoRecoleccion.php";

$pasaron = 0;
$fallaron = 0;

function afirmar(string $descripcion, $esperado, $obtenido): void
{
    global $pasaron, $fallaron;

    if ($esperado === $obtenido) {
        $pasaron++;
        echo "  \xE2\x9C\x93 $descripcion\n";
        return;
    }

    $fallaron++;
    echo "  \xC3\x97 $descripcion\n      esperado: " . var_export($esperado, true) .
         "\n      obtenido: " . var_export($obtenido, true) . "\n";
}

function titulo(string $texto): void
{
    echo "\n" . $texto . "\n" . str_repeat("-", strlen($texto)) . "\n";
}

function permisosDe(string $aplicacion): array
{
    $archivo = __DIR__ . "/../$aplicacion/core/Permisos.php";
    $guion = tempnam(sys_get_temp_dir(), "perm");
    file_put_contents($guion, '<?php require ' . var_export($archivo, true) . '; $tabla = [];
        foreach (Permisos::recursos() as $r) { foreach (["GET", "POST", "PUT", "DELETE"] as $m) { $tabla[$r][$m] = Permisos::rolesPermitidos($r, $m); } }
        echo json_encode($tabla);');
    $salida = shell_exec(escapeshellarg(PHP_BINARY) . " " . escapeshellarg($guion));
    unlink($guion);
    return json_decode((string) $salida, true) ?? [];
}

echo "SiGeRU - Pruebas unitarias (sin base de datos ni servidor)\n";
echo "==============================================\n";

titulo("1. Migraciones: dividir un archivo en sentencias");

afirmar(
    "Dos sentencias separadas por punto y coma",
    ["CREATE TABLE a (id INT)", "INSERT INTO a VALUES (1)"],
    Migraciones::dividirSentencias("CREATE TABLE a (id INT);\nINSERT INTO a VALUES (1);\n")
);
afirmar(
    "Un punto y coma dentro de un texto no corta la sentencia",
    ["SET @sql = 'ALTER TABLE a ADD COLUMN b INT; SELECT 1'"],
    Migraciones::dividirSentencias("SET @sql = 'ALTER TABLE a ADD COLUMN b INT; SELECT 1';")
);
afirmar(
    "Una comilla escapada dentro del texto no confunde el corte",
    ["INSERT INTO a VALUES ('it\'s; fine')", "SELECT 2"],
    Migraciones::dividirSentencias("INSERT INTO a VALUES ('it\'s; fine'); SELECT 2;")
);
afirmar(
    "Los comentarios de linea se descartan, aunque lleven punto y coma",
    ["SELECT 1"],
    Migraciones::dividirSentencias("-- esto; no es una sentencia\nSELECT 1;\n-- ni esto;\n")
);
afirmar(
    "Los comentarios de bloque se descartan",
    ["SELECT 1"],
    Migraciones::dividirSentencias("/* titulo; del archivo */ SELECT 1;")
);
afirmar(
    "Una sentencia final sin punto y coma tambien cuenta",
    ["SELECT 1", "SELECT 2"],
    Migraciones::dividirSentencias("SELECT 1; SELECT 2")
);
afirmar(
    "Un archivo vacio no tiene sentencias",
    [],
    Migraciones::dividirSentencias("-- nada por hacer\n\n")
);

titulo("2. Migraciones: nombres de archivo");

afirmar("001-incidencias.sql es valido", true, Migraciones::nombreValido("001-incidencias.sql"));
afirmar("012-historial-de-incidencias.sql es valido", true, Migraciones::nombreValido("012-historial-de-incidencias.sql"));
afirmar("Sin el cero inicial no es valido", false, Migraciones::nombreValido("1-incidencias.sql"));
afirmar("Con mayusculas no es valido", false, Migraciones::nombreValido("001-Incidencias.sql"));
afirmar("Con guion bajo no es valido", false, Migraciones::nombreValido("001-nueva_tabla.sql"));
afirmar("Con otra extension no es valido", false, Migraciones::nombreValido("001-incidencias.txt"));
afirmar(
    "Dos archivos con el mismo numero se detectan",
    ["002"],
    Migraciones::numerosRepetidos(["001-a.sql", "002-b.sql", "002-c.sql"])
);

titulo("3. Migraciones: registro en schema.sql");

$schema = file_get_contents(__DIR__ . "/../database/schema.sql");

afirmar(
    "Se leen los nombres del INSERT de migracion",
    ["001-a.sql", "002-b.sql"],
    Migraciones::registradasEnSchema("INSERT INTO migracion (nombre) VALUES ('002-b.sql'), ('001-a.sql');")
);
afirmar(
    "Un INSERT dentro de un comentario no cuenta",
    [],
    Migraciones::registradasEnSchema("-- INSERT INTO migracion (nombre) VALUES ('001-a.sql');")
);
afirmar(
    "Los archivos de migraciones/ son validos y sin numeros repetidos",
    [[], []],
    (function () {
        $archivos = Migraciones::archivos(__DIR__ . "/../database/migraciones");
        return [
            array_values(array_filter($archivos, fn($a) => !Migraciones::nombreValido($a))),
            Migraciones::numerosRepetidos($archivos),
        ];
    })()
);
afirmar(
    "schema.sql registra exactamente las migraciones que hay en migraciones/",
    Migraciones::archivos(__DIR__ . "/../database/migraciones"),
    Migraciones::registradasEnSchema($schema)
);
afirmar(
    "schema.sql crea la tabla tecnica migracion",
    1,
    preg_match('/CREATE TABLE migracion\s*\(/i', $schema)
);

function archivosDelFront(string $raiz): array
{
    $rutas = [];
    foreach (["landing", "frontend-usuarios"] as $aplicacion) {
        $recorrido = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($raiz . "/" . $aplicacion, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($recorrido as $archivo) {
            $propio = !str_contains($archivo->getFilename(), ".min.");
            if ($propio && in_array(strtolower($archivo->getExtension()), ["html", "css", "js"], true)) {
                $rutas[] = $archivo->getPathname();
            }
        }
    }
    sort($rutas);
    return $rutas;
}

$raiz = __DIR__ . "/..";
$front = $raiz . "/frontend-usuarios";
$landing = $raiz . "/landing";

titulo("4. Front: copias compartidas entre landing y frontend-usuarios");

foreach (["css/base.css", "js/formularios.js", "js/tema.js", "img/logo.svg"] as $relativa) {
    afirmar(
        "$relativa es identico en las dos aplicaciones",
        true,
        file_get_contents("$front/$relativa") === file_get_contents("$landing/$relativa")
    );
}

$fuentesFront = array_map("basename", glob("$front/fonts/*"));
$fuentesLanding = array_map("basename", glob("$landing/fonts/*"));
afirmar("Las dos aplicaciones traen las mismas fuentes y licencias", $fuentesFront, $fuentesLanding);
afirmar(
    "Cada fuente es identica en las dos",
    [],
    array_values(array_filter($fuentesFront, fn($f) => md5_file("$front/fonts/$f") !== md5_file("$landing/fonts/$f")))
);

preg_match_all('/url\("\.\.\/fonts\/([^"]+)"\)/', file_get_contents("$front/css/base.css"), $pedidas);
afirmar(
    "Todas las fuentes que pide base.css existen",
    [],
    array_values(array_filter($pedidas[1], fn($f) => !file_exists("$front/fonts/$f")))
);

titulo("5. Front: sin dependencias de internet ni entre aplicaciones");

$externos = [];
$acoplados = [];
foreach (archivosDelFront($raiz) as $ruta) {
    $contenido = file_get_contents($ruta);
    $corta = substr($ruta, strlen($raiz) + 1);

    if (preg_match('/<(?:link|script|img)\b[^>]*\b(?:href|src)\s*=\s*["\']https?:\/\//i', $contenido)
        || preg_match('/url\(\s*["\']?https?:|@import|fonts\.googleapis|fonts\.gstatic|cdn\.|unpkg|jsdelivr/i', $contenido)) {
        $externos[] = $corta;
    }
    if (str_starts_with($corta, "landing") && preg_match('/<(?:link|script|img)\b[^>]*frontend-usuarios|url\([^)]*frontend-usuarios|["\']\.\.\/frontend-usuarios\/(?:js|css|img)/i', $contenido)) {
        $acoplados[] = $corta;
    }
}
afirmar("Ninguna pagina, hoja de estilos ni script carga algo de internet", [], $externos);

$conMosaicos = [];
foreach (archivosDelFront($raiz) as $ruta) {
    if (preg_match('/tile\.openstreetmap\.org|\{z\}\/\{x\}\/\{y\}/', file_get_contents($ruta))) {
        $conMosaicos[] = substr($ruta, strlen($raiz) + 1);
    }
}
afirmar("Los unicos pedidos a internet (los mosaicos del fondo del mapa) estan en el mapa interno, en el recorrido de la recoleccion, en armar ruta, en la ruta sugerida y en el publico", ["frontend-usuarios/js/mapa.js", "frontend-usuarios/js/recoleccion-detalle.js", "frontend-usuarios/js/ruta-armar.js", "frontend-usuarios/js/ruta-sugerida.js", "landing/js/publico.js"],
    array_map(fn ($r) => str_replace("\\", "/", $r), $conMosaicos));
$leaflet = $front . "/vendor/leaflet/";
afirmar("Leaflet esta dentro del repo, con su licencia, y es la version verificada", [true, true, true], [
    is_file($leaflet . "LICENSE"),
    str_contains((string) @file_get_contents($leaflet . "leaflet.js"), "Leaflet 1.9.4"),
    base64_encode(hash_file("sha256", $leaflet . "leaflet.js", true)) === "20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=",
]);
$mapaHtml = (string) @file_get_contents($front . "/mapa.html");
afirmar("mapa.html carga Leaflet local, no de un CDN", [true, true], [
    str_contains($mapaHtml, 'src="vendor/leaflet/leaflet.js"'), str_contains($mapaHtml, 'href="vendor/leaflet/leaflet.css"'),
]);
afirmar("La landing no carga nada de frontend-usuarios", [], $acoplados);

titulo("5b. Landing: datos reales, mapa publico y promesas honestas");

$landingHtml = (string) file_get_contents($landing . "/index.html");
$copiasLeaflet = [];
foreach (["leaflet.js", "leaflet.css", "LICENSE", "images/marker-icon.png", "images/layers.png"] as $archivoLeaflet) {
    $copiasLeaflet[$archivoLeaflet] = is_file("$landing/vendor/leaflet/$archivoLeaflet")
        && md5_file("$landing/vendor/leaflet/$archivoLeaflet") === md5_file("$front/vendor/leaflet/$archivoLeaflet");
}
afirmar("La landing tiene su propia copia identica de Leaflet", array_fill_keys(array_keys($copiasLeaflet), true), $copiasLeaflet);
afirmar("La landing carga Leaflet y su script de datos desde su propia carpeta", [true, true, true], [
    str_contains($landingHtml, 'src="vendor/leaflet/leaflet.js"'),
    str_contains($landingHtml, 'href="vendor/leaflet/leaflet.css"'),
    str_contains($landingHtml, 'src="js/publico.js"') && is_file("$landing/js/publico.js"),
]);
$inventos = [];
foreach (["CNT-018", "62 %", "06:40", "Nivel de llenado", "Última recolección", "por turno", "en tiempo real"] as $frase) {
    if (stripos($landingHtml, $frase) !== false) {
        $inventos[] = $frase;
    }
}
afirmar("La landing no tiene la ficha inventada ni promesas que el sistema no cumple", [], $inventos);
afirmar("Las cifras de la landing salen de la API publica, que no pide sesion", true,
    str_contains((string) file_get_contents("$landing/js/publico.js"), "/sigeru/api-gestion/publico/mapa"));
afirmar("La landing ya no trae el script duplicado de registro", false, is_file("$landing/js/main.js"));

titulo("6. Front: un solo menu y base compartida");

$menu = file_get_contents("$front/js/shell.js");
preg_match_all('/href:\s*"([a-z-]+\.html)"/', $menu, $enlaces);
afirmar(
    "Cada pagina del menu de shell.js existe",
    [],
    array_values(array_filter($enlaces[1], fn($p) => !file_exists("$front/$p")))
);

$conMenu = [];
$sinShell = [];
$sinBase = [];
$conMenuCopiado = [];
foreach (glob("$front/*.html") as $pagina) {
    $html = file_get_contents($pagina);
    $nombre = basename($pagina);
    if (str_contains($html, 'id="sidebar"')) {
        $conMenu[] = $nombre;
        if (!str_contains($html, 'src="js/shell.js"')) {
            $sinShell[] = $nombre;
        }
    }
    if (str_contains($html, "sidebar-nav") || str_contains($html, 'id="btn-menu"')) {
        $conMenuCopiado[] = $nombre;
    }
    if (str_contains($html, 'href="css/styles.css"')
        && !preg_match('/href="css\/base\.css">\s*<link rel="stylesheet" href="css\/styles\.css"/', $html)) {
        $sinBase[] = $nombre;
    }
}
afirmar("Las paginas con menu lateral son las 26 de gestion (24 mas armar ruta y asignacion)", 26, count($conMenu));
afirmar("Todas cargan shell.js", [], $sinShell);
afirmar("Ninguna tiene copiado el menu ni la hamburguesa", [], $conMenuCopiado);
afirmar(
    "Las paginas con menu estan en el menu de shell.js",
    [],
    array_values(array_diff($conMenu, $enlaces[1]))
);
afirmar("Todas cargan base.css justo antes de styles.css", [], $sinBase);
afirmar(
    "El login no trae credenciales precargadas",
    0,
    preg_match('/<input\b[^>]*\bvalue=/i', file_get_contents("$front/login.html"))
);

titulo("7. Login: politica de bloqueo por intentos");

$ahora = 10000;
afirmar("Con 4 fallos no hay espera", 0, PoliticaBloqueo::segundosDeEspera([9900, 9910, 9920, 9930], $ahora));
afirmar(
    "Con 5 fallos hay que esperar hasta que el quinto salga de la ventana",
    800,
    PoliticaBloqueo::segundosDeEspera([9900, 9910, 9920, 9930, 9940], $ahora)
);
afirmar(
    "Los fallos viejos (fuera de los 15 minutos) no cuentan",
    0,
    PoliticaBloqueo::segundosDeEspera([8000, 8010, 8020, 8030, 8040], $ahora)
);
afirmar(
    "Con mas de 5 fallos manda el quinto mas reciente",
    400,
    PoliticaBloqueo::segundosDeEspera([9000, 9500, 9600, 9700, 9800, 9900, 9990], $ahora + 100)
);
afirmar(
    "Cuando el quinto fallo cumple los 15 minutos, se levanta",
    0,
    PoliticaBloqueo::segundosDeEspera([9100, 9200, 9300, 9400, 9500], 9500 + 900)
);
afirmar("El email se normaliza: minusculas y sin espacios", "admin@sigeru.uy", PoliticaBloqueo::normalizar("  ADMIN@Sigeru.UY "));
afirmar("Un valor que no es texto queda vacio", "", PoliticaBloqueo::normalizar(["x"]));
afirmar("Un email enorme se recorta a 120 caracteres", 120, strlen(PoliticaBloqueo::normalizar(str_repeat("a", 500))));

titulo("8. Permisos: la tabla, el router, el menu y las copias");

$todos = ["Administrador", "Operario", "Cuadrilla", "Vecino"];
$gestion = file_get_contents($raiz . "/api-gestion/index.php");
preg_match_all('/case "([a-z-]+)":/', $gestion, $casos);
$recursosDelRouter = $casos[1];
sort($recursosDelRouter);
$recursosConRegla = array_values(array_filter(Permisos::recursos(), fn (string $r) => !str_contains($r, "/")));
sort($recursosConRegla);
afirmar("Cada recurso del router de gestion tiene su regla de permisos", $recursosDelRouter, $recursosConRegla);

$permisosRecoleccion = permisosDe("api-recoleccion");
$recoleccionRouter = file_get_contents($raiz . "/api-recoleccion/index.php");
preg_match_all('/case "([a-z-]+)":/', $recoleccionRouter, $casos);
$recursosDelRouter = $casos[1];
sort($recursosDelRouter);
$recursosConRegla = array_values(array_filter(array_keys($permisosRecoleccion), fn (string $r) => !str_contains($r, "/")));
sort($recursosConRegla);
afirmar("Cada recurso del router de recoleccion tiene su regla de permisos", $recursosDelRouter, $recursosConRegla);
afirmar("Camiones y cuadrillas ya no estan en api-gestion", [[], []], [
    Permisos::rolesPermitidos("camiones", "GET"), Permisos::rolesPermitidos("cuadrillas", "GET"),
]);
afirmar("Los camiones solo los lee un administrador", ["Administrador"], $permisosRecoleccion["camiones"]["GET"] ?? null);
afirmar("Las recolecciones las leen el administrador y la cuadrilla", ["Administrador", "Cuadrilla"], $permisosRecoleccion["recolecciones"]["GET"] ?? null);
afirmar("Planificar y cancelar es solo del administrador", [["Administrador"], ["Administrador"]], [
    $permisosRecoleccion["recolecciones"]["POST"] ?? null, $permisosRecoleccion["recolecciones/cancelacion"]["POST"] ?? null,
]);
afirmar("Los cuatro roles leen contenedores", $todos, Permisos::rolesPermitidos("contenedores", "GET"));
afirmar("Escribir contenedores es solo del administrador", ["Administrador"], Permisos::rolesPermitidos("contenedores", "POST"));
afirmar("El operario puede actualizar centros", true, in_array("Operario", Permisos::rolesPermitidos("centros", "PUT"), true));
afirmar("Pero no darlos de baja", false, in_array("Operario", Permisos::rolesPermitidos("centros", "DELETE"), true));
afirmar("Un recurso que no existe no lo puede usar nadie", [], Permisos::rolesPermitidos("inventado", "GET"));

$menu = file_get_contents($raiz . "/frontend-usuarios/js/shell.js");
preg_match_all('/href:\s*"([a-z-]+)\.html",\s*texto:\s*"[^"]*",\s*roles:\s*(TODOS|\[[^\]]*\])/', $menu, $secciones, PREG_SET_ORDER);
$recursoDePantalla = [
    "contenedores"       => ["contenedores", "GET"],
    "mapa"               => ["contenedores", "GET"],
    "ruta-sugerida"      => ["contenedores/ruta-sugerida", "GET"],
    "reportes"           => ["estadisticas", "GET"],
    "contenedor"         => ["contenedores/historial", "GET"],
    "camiones"           => ["camiones", "GET", "recoleccion"],
    "recolecciones"      => ["recolecciones", "GET", "recoleccion"],
    "rutas"              => ["rutas", "GET", "recoleccion"],
    "ruta-armar"         => ["rutas", "GET", "recoleccion"],
    "asignacion"         => ["asignacion", "GET", "recoleccion"],
    "mis-tareas"         => ["recolecciones", "GET", "recoleccion"],
    "recoleccion"        => ["recolecciones", "GET", "recoleccion"],
    "centros"            => ["centros", "GET"],
    "maquinaria"         => ["maquinaria", "GET"],
    "incidencias"        => ["incidencias", "GET"],
    "mis-incidencias"    => ["incidencias", "GET"],
    "mis-reclamos"       => ["incidencias", "GET"],
    "incidencia"         => ["incidencias", "GET"],
    "informar-problema"  => ["incidencias", "POST"],
];
$excedidos = [];
foreach ($secciones as $seccion) {
    if (!isset($recursoDePantalla[$seccion[1]])) {
        continue;
    }
    [$recursoApi, $metodoApi] = $recursoDePantalla[$seccion[1]];
    $rolesDeLaApi = ($recursoDePantalla[$seccion[1]][2] ?? "gestion") === "recoleccion"
        ? ($permisosRecoleccion[$recursoApi][$metodoApi] ?? [])
        : Permisos::rolesPermitidos($recursoApi, $metodoApi);
    if ($seccion[2] === "TODOS") {
        $rolesDelMenu = $todos;
    } else {
        preg_match_all('/"([A-Za-z]+)"/', $seccion[2], $nombres);
        $rolesDelMenu = $nombres[1];
    }
    $sobran = array_diff($rolesDelMenu, $rolesDeLaApi);
    if ($sobran !== []) {
        $excedidos[$seccion[1]] = array_values($sobran);
    }
}
afirmar("El menu no muestra pantallas cuya lectura la API le niega al rol", [], $excedidos);
afirmar("shell.js define las 26 pantallas de gestion", 26, count($secciones));
afirmar("Cada pantalla de incidencias esta atada a su permiso de la API", [], array_values(array_diff(
    ["incidencias", "mis-incidencias", "mis-reclamos", "incidencia", "informar-problema", "contenedor",
     "recolecciones", "mis-tareas", "recoleccion", "camiones", "mapa", "reportes", "rutas"],
    array_column($secciones, 1)
)));

foreach (["core/Auth.php", "core/Response.php"] as $compartido) {
    afirmar(
        "$compartido es identico en las tres APIs",
        [true, true],
        [
            file_get_contents($raiz . "/api-usuarios/" . $compartido) === file_get_contents($raiz . "/api-gestion/" . $compartido),
            file_get_contents($raiz . "/api-gestion/" . $compartido) === file_get_contents($raiz . "/api-recoleccion/" . $compartido),
        ]
    );
}
foreach (["config/database.php", "core/ErrorDeNegocio.php", "models/Cuadrilla.php", ".htaccess"] as $compartido) {
    afirmar(
        "$compartido es identico en api-gestion y api-recoleccion",
        true,
        file_get_contents($raiz . "/api-gestion/" . $compartido) === file_get_contents($raiz . "/api-recoleccion/" . $compartido)
    );
}

preg_match('/RewriteRule \^\(([^)]+)\)/', file_get_contents($raiz . "/.htaccess"), $listaBlanca);
$publicadas = explode("|", $listaBlanca[1] ?? "");
sort($publicadas);
$aplicaciones = array_merge(["landing", "frontend-usuarios"], array_map("basename", glob($raiz . "/api-*", GLOB_ONLYDIR)));
sort($aplicaciones);
afirmar("La lista blanca del .htaccess son exactamente las aplicaciones", $aplicaciones, $publicadas);

require __DIR__ . "/unitarias-incidencias.php";
require __DIR__ . "/unitarias-recoleccion.php";
require __DIR__ . "/unitarias-correo.php";
require __DIR__ . "/unitarias-estadisticas.php";
require __DIR__ . "/unitarias-listas.php";
require __DIR__ . "/unitarias-accesibilidad.php";
require __DIR__ . "/unitarias-foto.php";
require __DIR__ . "/unitarias-seguridad.php";
require __DIR__ . "/unitarias-operario.php";
require __DIR__ . "/unitarias-notificaciones.php";
require __DIR__ . "/unitarias-guia.php";
require __DIR__ . "/unitarias-ruta.php";
require __DIR__ . "/unitarias-rutas.php";
require __DIR__ . "/unitarias-trazado.php";
require __DIR__ . "/unitarias-paradas.php";
require __DIR__ . "/unitarias-asignacion.php";
require __DIR__ . "/unitarias-pdf.php";
require __DIR__ . "/unitarias-offline.php";
require __DIR__ . "/unitarias-tema.php";
require __DIR__ . "/unitarias-pulido.php";
require __DIR__ . "/unitarias-participacion.php";
require __DIR__ . "/unitarias-pasaje.php";
require __DIR__ . "/unitarias-prediccion.php";
require __DIR__ . "/unitarias-impacto.php";

echo "\n" . str_repeat("=", 50) . "\n";
echo "Pasaron: $pasaron   Fallaron: $fallaron\n";
echo $fallaron === 0 ? "Todo verde.\n" : "Hay fallas.\n";
exit($fallaron === 0 ? 0 : 1);
