<?php

// Aplicacion instalable y trabajo sin conexion (extras, parte 21): lo que se puede comprobar leyendo los archivos y la clase de claves.
// La cola de IndexedDB y el service worker corren en el navegador: se probaron ahi (ver tests/js/cola-offline.html y programacion-full-stack/ampliacion-offline-3ra.md).

require_once __DIR__ . "/../api-gestion/core/Idempotencia.php";

titulo("44. Claves de operacion y aplicacion instalable");

afirmar("Un UUID valido se acepta (minusculas o mayusculas)", [true, true], [Idempotencia::formatoValido("3f2b8c1e-5a4d-4e6f-9a1b-0c2d3e4f5a6b"), Idempotencia::formatoValido("3F2B8C1E-5A4D-4E6F-9A1B-0C2D3E4F5A6B")]);
$malos = ["", "abc", "3f2b8c1e5a4d4e6f9a1b0c2d3e4f5a6b", "3f2b8c1e-5a4d-4e6f-9a1b-0c2d3e4f5a6", "3f2b8c1e-5a4d-4e6f-9a1b-0c2d3e4f5a6bb", "3f2b8c1e-5a4d-4e6f-9a1b-0c2d3e4f5a6g", "'; DROP TABLE usuario;--", "3f2b8c1e-5a4d-4e6f-9a1b-0c2d3e4f5a6b\n"];
afirmar("Lo que no es un UUID se rechaza (vacio, sin guiones, largo de mas o de menos, letras fuera de hex, inyeccion, salto de linea)", [], array_values(array_filter($malos, fn($m) => Idempotencia::formatoValido($m))));
afirmar("Idempotencia.php es identico en api-gestion y api-recoleccion", true, file_get_contents(__DIR__ . "/../api-gestion/core/Idempotencia.php") === file_get_contents(__DIR__ . "/../api-recoleccion/core/Idempotencia.php"));
$tiposAplican = [];
foreach (["api-recoleccion/controllers/RecoleccionController.php" => "vaciado", "api-gestion/controllers/IncidenciaController.php" => "avance"] as $archivo => $operacion) {
    $tiposAplican[$operacion] = str_contains((string) file_get_contents(__DIR__ . "/../$archivo"), 'Idempotencia::comenzar($usuario, "' . $operacion . '")');
}
afirmar("Solo se usa en las dos acciones que se pueden guardar sin conexion: vaciado y avance", ["vaciado" => true, "avance" => true], $tiposAplican);
afirmar("Response avisa a Idempotencia antes de responder en las tres APIs (para guardar el exito o soltar la reserva)", [true, true, true], array_map(
    fn($api) => str_contains((string) file_get_contents(__DIR__ . "/../$api/core/Response.php"), "Idempotencia::terminar(\$codigo, \$datos)"), ["api-usuarios", "api-gestion", "api-recoleccion"]));

$front = __DIR__ . "/../frontend-usuarios";
$manifiesto = json_decode((string) file_get_contents("$front/manifest.webmanifest"), true);
afirmar("El manifiesto es JSON con nombre, nombre corto, inicio, alcance, modo y colores", [true, true, true, true], [
    is_array($manifiesto) && ($manifiesto["name"] ?? "") !== "" && ($manifiesto["short_name"] ?? "") === "SiGeRU",
    ($manifiesto["start_url"] ?? "") === "login.html" && ($manifiesto["scope"] ?? "") === "./" && ($manifiesto["display"] ?? "") === "standalone",
    preg_match('/^#[0-9a-f]{6}$/i', $manifiesto["theme_color"] ?? "") === 1 && preg_match('/^#[0-9a-f]{6}$/i', $manifiesto["background_color"] ?? "") === 1,
    ($manifiesto["lang"] ?? "") === "es",
]);
$problemasIconos = [];
foreach ($manifiesto["icons"] ?? [] as $icono) {
    $ruta = "$front/" . $icono["src"];
    $info = file_exists($ruta) ? getimagesize($ruta) : false;
    if ($info === false || $info[2] !== IMAGETYPE_PNG || $info[0] . "x" . $info[1] !== $icono["sizes"]) $problemasIconos[] = $icono["src"];
}
$tamanos = array_column($manifiesto["icons"] ?? [], "sizes");
afirmar("Los iconos existen, son PNG y miden lo que dice el manifiesto; hay de 192 y de 512, y uno «maskable»", [[], true, true, true], [
    $problemasIconos, in_array("192x192", $tamanos, true), in_array("512x512", $tamanos, true), in_array("maskable", array_column($manifiesto["icons"] ?? [], "purpose"), true),
]);

$sw = (string) file_get_contents("$front/sw.js");
preg_match('/const PRECARGA = \[(.*?)\];/s', $sw, $lista);
preg_match_all('/"([^"]+)"/', $lista[1] ?? "", $archivos);
afirmar("El service worker precarga solo archivos que existen (" . count($archivos[1]) . ")", [], array_values(array_filter($archivos[1], fn($a) => !file_exists("$front/$a"))));
afirmar("El service worker nunca toca las APIs, ni lo que no es GET, ni otros origenes", [true, true, true], [
    str_contains($sw, '/\/api-[a-z]+(\/|$)/.test(url.pathname)'), str_contains($sw, 'pedido.method !== "GET"'), str_contains($sw, "url.origin !== self.location.origin"),
]);
afirmar("Tiene version de cache, borra las viejas, y muestra sin-conexion.html si no hay red ni copia", [true, true, true], [
    preg_match('/const VERSION = "sigeru-estaticos-v\d+"/', $sw) === 1, str_contains($sw, "caches.delete"), str_contains($sw, 'caches.match("sin-conexion.html")'),
]);
afirmar("El service worker no guarda ninguna respuesta de las APIs ni de otro origen (solo respuestas basicas, del mismo origen)", true, substr_count($sw, 'respuesta.type === "basic"') === 2);

$sinManifiesto = [];
$sinPwa = [];
$conPendientes = [];
foreach (glob("$front/*.html") as $pagina) {
    $nombre = basename($pagina);
    $html = (string) file_get_contents($pagina);
    if ($nombre === "sin-conexion.html") continue;
    if (!str_contains($html, '<link rel="manifest" href="manifest.webmanifest">') || !str_contains($html, 'name="theme-color"')) $sinManifiesto[] = $nombre;
    if (!str_contains($html, 'src="js/pwa.js"')) $sinPwa[] = $nombre;
    if (str_contains($html, 'src="js/pendientes.js"')) $conPendientes[] = $nombre;
}
afirmar("Todas las pantallas enlazan el manifiesto y registran el service worker", [[], []], [$sinManifiesto, $sinPwa]);
afirmar("La barra de pendientes esta solo en las pantallas de la Cuadrilla (tareas, recoleccion, incidencias, incidencia, inicio)", ["dashboard.html", "incidencia.html", "mis-incidencias.html", "mis-tareas.html", "recoleccion.html"], $conPendientes);
$cola = (string) file_get_contents("$front/js/cola-offline.js");
afirmar("La cola solo admite vaciado y avance, de la Cuadrilla, y manda la clave en Idempotency-Key", [true, true, true, true], [
    str_contains($cola, "vaciado:") && str_contains($cola, "avance:") && substr_count($cola, 'rol: "Cuadrilla"') === 2,
    str_contains($cola, '"Idempotency-Key": op.clave'), str_contains($cola, "recolecciones\/\d+\/vaciados"), str_contains($cola, "incidencias\/\d+\/avance"),
]);
afirmar("La cola no guarda contrasenas ni la sesion (nada de password ni de localStorage de la sesion)", false, (bool) preg_match('/password|contrasena|localStorage/i', $cola));
afirmar("Existe la pagina de pruebas de la cola para el navegador", true, file_exists(__DIR__ . "/js/cola-offline.html"));
