<?php

// Parte 26 (24/10/2026), bloque A: el codigo tiene que correr en el PHP del servidor de la instalacion. Rocky Linux 9 trae PHP 8.0 por defecto,
// y el codigo se desarrollo con 8.2: una sola funcion de 8.1 (array_is_list) habria roto «Armar ruta» y «Asignacion» en la VM.
// Este archivo no prueba el servidor: impide que vuelva a entrar una funcion o una sintaxis posterior a PHP 8.0 y fija la hora de Uruguay.

titulo("53. Compatibilidad con PHP 8.0 y hora de Uruguay");

$raiz = realpath(__DIR__ . "/..");
$archivos = [];
foreach (["api-usuarios", "api-gestion", "api-recoleccion", "database/lib"] as $carpeta) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$raiz/$carpeta", FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if ($f->isFile() && $f->getExtension() === "php") {
            $archivos[] = $f->getPathname();
        }
    }
}
afirmar("Se revisan los archivos PHP de las tres APIs y de la libreria de migraciones (mas de 50)", true, count($archivos) > 50);

// Lo que NO existe en PHP 8.0. Se busca en el codigo sin comentarios ni textos entre comillas, para no marcar una palabra suelta de un mensaje.
$prohibido = [
    "array_is_list()"           => '/\barray_is_list\s*\(/',
    "fsync()/fdatasync()"       => '/\bfd?sync\s*\(/',
    "enum"                      => '/^\s*enum\s+\w+/m',
    "readonly"                  => '/\breadonly\s+(?:public|protected|private|class|\$|\w+\s+\$)/',
    "tipo never"                => '/\)\s*:\s*never\b/',
    "llamada como valor f(...)" => '/\b[A-Za-z_][A-Za-z0-9_:>\-]*\(\s*\.\.\.\s*\)/',
    "funciones de PHP 8.3 y 8.4" => '/\b(?:json_validate|mb_str_pad|str_increment|str_decrement|array_find|array_find_key|array_any|array_all|mb_trim)\s*\(/',
];
$sinTexto = static function (string $codigo): string {
    $salida = "";
    foreach (token_get_all($codigo) as $t) {
        if (is_array($t)) {
            if (in_array($t[0], [T_COMMENT, T_DOC_COMMENT, T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE, T_INLINE_HTML], true)) {
                $salida .= str_repeat(" ", strlen($t[1]));
                continue;
            }
            $salida .= $t[1];
        } else {
            $salida .= $t;
        }
    }
    return $salida;
};
$hallazgos = [];
foreach ($archivos as $archivo) {
    $codigo = $sinTexto((string) file_get_contents($archivo));
    foreach ($prohibido as $nombre => $patron) {
        if (preg_match($patron, $codigo)) {
            $hallazgos[] = str_replace($raiz . DIRECTORY_SEPARATOR, "", $archivo) . ": " . $nombre;
        }
    }
}
afirmar("Ningun archivo usa funciones ni sintaxis posteriores a PHP 8.0", [], $hallazgos);

// El detector tiene que detectar: se le da un codigo con cada cosa prohibida y tiene que marcarlas todas.
$muestra = '<?php $a = array_is_list($x); fsync($f);' . "\n" . 'enum Estado { case A; }' . "\n" . 'final class P { public readonly int $n; } function f(): never { exit; } $g = strlen(...); $h = json_validate("{}");';
$marcadas = [];
foreach ($prohibido as $nombre => $patron) {
    if (preg_match($patron, $sinTexto($muestra))) {
        $marcadas[] = $nombre;
    }
}
afirmar("El detector marca cada una de las siete clases de cosas prohibidas", array_keys($prohibido), $marcadas);
afirmar("Y no marca una palabra suelta dentro de un mensaje o un comentario", [], array_filter(
    array_keys($prohibido),
    static fn(string $n) => preg_match($prohibido[$n], $sinTexto('<?php // array_is_list( y enum Estado ' . "\n" . '$m = "usa array_is_list() y fsync()"; ?>')) === 1
));

// La lista de un pedido (rutas y asignaciones) se sigue exigiendo sin array_is_list: un objeto con claves sueltas se rechaza.
require_once $raiz . "/api-recoleccion/models/FlujoRecoleccion.php";
require_once $raiz . "/api-recoleccion/models/RutaParadas.php";
require_once $raiz . "/api-recoleccion/models/Asignacion.php";
afirmar("Una lista con claves sueltas o saltadas no es una lista (rutas)", [true, true, false], [
    RutaParadas::errorDeLista(["contenedores" => ["a" => 1, "b" => 2]]) !== null,
    RutaParadas::errorDeLista(["contenedores" => [1 => 5, 2 => 6]]) !== null,
    RutaParadas::errorDeLista(["contenedores" => [5, 6]]) !== null,
]);
afirmar("Una lista con claves sueltas o saltadas no es una lista (asignaciones)", [true, true], [
    Asignacion::errorDeLote(["asignaciones" => [1 => ["id_ruta" => 1]]]) !== null,
    Asignacion::errorDeLote(["asignaciones" => ["x" => ["id_ruta" => 1]]]) !== null,
]);

// Hora de Uruguay: la fija el codigo, no el php.ini ni el servidor de la base.
$respuestas = array_map(static fn(string $a) => (string) file_get_contents("$raiz/$a/core/Response.php"), ["api-usuarios", "api-gestion", "api-recoleccion"]);
afirmar("Las tres APIs fijan America/Montevideo al arrancar", [true, true, true], array_map(static fn(string $r) => str_contains($r, 'date_default_timezone_set("America/Montevideo")'), $respuestas));
$bases = array_map(static fn(string $a) => (string) file_get_contents("$raiz/$a/config/database.php"), ["api-usuarios", "api-gestion", "api-recoleccion"]);
afirmar("Las tres conexiones a la base fijan time_zone de la sesion (UTC-3 por defecto, SIGERU_DB_TZ para cambiarlo)", [true, true, true], array_map(
    static fn(string $b) => str_contains($b, "SET time_zone = '") && str_contains($b, '"SIGERU_DB_TZ", "-03:00"'),
    $bases
));
