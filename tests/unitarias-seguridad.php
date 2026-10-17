<?php

// Seguridad (parte 11, OWASP): auditoria, copias idénticas del codigo de seguridad compartido y acceso al codigo interno.

require_once __DIR__ . "/../api-gestion/core/Auditoria.php";

titulo("22. Seguridad: auditoria y codigo compartido");

foreach ([["POST", 200, true], ["POST", 201, true], ["PUT", 204, true], ["DELETE", 200, true], ["POST", 403, true],
          ["PUT", 403, true], ["GET", 200, false], ["GET", 403, false], ["POST", 400, false], ["POST", 401, false],
          ["POST", 404, false], ["DELETE", 409, false], ["POST", 500, false], ["OPTIONS", 204, false]] as [$metodo, $codigo, $esperado]) {
    afirmar("$metodo con codigo $codigo " . ($esperado ? "se registra" : "no se registra"), $esperado, Auditoria::debeRegistrarse($metodo, $codigo));
}

afirmar("La ruta se guarda sin parametros", "/api-gestion/contenedores", Auditoria::rutaLimpia("/sigeru/api-gestion/contenedores?token=secreto&x=1"));
afirmar("Y sin el prefijo del alias", "/api-usuarios/login", Auditoria::rutaLimpia("/sigeru/api-usuarios/login"));
afirmar("Una ruta con un id se conserva", "/api-gestion/incidencias/12/asignacion", Auditoria::rutaLimpia("/sigeru/api-gestion/incidencias/12/asignacion"));
afirmar("Los caracteres de control no pasan (no se puede falsear una linea)", [false, true], (function () {
    $ruta = Auditoria::rutaLimpia("/a" . chr(10) . "b" . chr(0) . "c" . chr(13) . "d");
    return [(bool) preg_match('/[\x00-\x1F]/', $ruta), str_starts_with($ruta, "/a")];
})());
afirmar("Una ruta enorme se corta en 200 caracteres", 200, strlen(Auditoria::rutaLimpia("/" . str_repeat("a", 500))));
afirmar("Una URI vacia queda en la raiz", "/", Auditoria::rutaLimpia(""));

afirmar("Sin filtros", ["filtros" => [], "error" => null], Auditoria::filtrosDe([]));
afirmar("Filtros validos de auditoria", ["filtros" => ["usuario" => 3, "metodo" => "PUT", "resultado" => "negado", "desde" => "2026-10-01", "hasta" => "2026-10-31", "q" => "camiones"], "error" => null],
    Auditoria::filtrosDe(["usuario" => "3", "metodo" => "PUT", "resultado" => "negado", "desde" => "2026-10-01", "hasta" => "2026-10-31", "q" => " camiones "]));
foreach ([["usuario" => "abc"], ["usuario" => "0"], ["metodo" => "GET"], ["metodo" => ["PUT"]], ["resultado" => "quizas"],
          ["desde" => "2026-13-01"], ["hasta" => "ayer"], ["desde" => "2026-10-10", "hasta" => "2026-10-01"], ["q" => str_repeat("a", 61)]] as $malo) {
    afirmar("Filtro de auditoria invalido: " . json_encode($malo), true, Auditoria::filtrosDe($malo)["error"] !== null);
}

$raiz = __DIR__ . "/..";
foreach (["core/Auditoria.php", "core/Auth.php", "core/Response.php"] as $compartido) {
    afirmar("$compartido es identico en las tres APIs", [true, true], [
        file_get_contents("$raiz/api-usuarios/$compartido") === file_get_contents("$raiz/api-gestion/$compartido"),
        file_get_contents("$raiz/api-gestion/$compartido") === file_get_contents("$raiz/api-recoleccion/$compartido"),
    ]);
}
afirmar("El .htaccess de las tres APIs es identico y manda el codigo interno al router", [true, true, true], [
    file_get_contents("$raiz/api-usuarios/.htaccess") === file_get_contents("$raiz/api-gestion/.htaccess"),
    file_get_contents("$raiz/api-gestion/.htaccess") === file_get_contents("$raiz/api-recoleccion/.htaccess"),
    str_contains((string) file_get_contents("$raiz/api-gestion/.htaccess"), "(config|controllers|core|models)"),
]);
$htaccess = (string) file_get_contents("$raiz/.htaccess");
afirmar("El .htaccess de la raiz pone las cabeceras de seguridad", [true, true, true, true], [
    str_contains($htaccess, "Content-Security-Policy"), str_contains($htaccess, "X-Frame-Options"),
    str_contains($htaccess, "Referrer-Policy"), str_contains($htaccess, "X-Content-Type-Options"),
]);
afirmar("Y la politica no admite codigo inline ni eval", [true, false, false], [
    str_contains($htaccess, "script-src 'self'"), str_contains($htaccess, "unsafe-eval"), (bool) preg_match("/script-src[^;]*unsafe-inline/", $htaccess),
]);
afirmar("Auth prende la cookie 'secure' con HTTPS y no acepta ids de sesion impuestos", [true, true], [
    str_contains((string) file_get_contents("$raiz/api-usuarios/core/Auth.php"), '"secure"'),
    str_contains((string) file_get_contents("$raiz/api-usuarios/core/Auth.php"), "use_strict_mode"),
]);
afirmar("Response solo lee JSON", true, str_contains((string) file_get_contents("$raiz/api-usuarios/core/Response.php"), "415"));

$limite = fn (string $codigo) => enOtroProceso(__DIR__ . "/../api-usuarios/core/LimitePeticion.php", $codigo);
afirmar("El freno salta al llegar al maximo, no antes", [false, false, true, true], [
    $limite("LimitePeticion::superado(0, 10)"), $limite("LimitePeticion::superado(9, 10)"),
    $limite("LimitePeticion::superado(10, 10)"), $limite("LimitePeticion::superado(500, 10)"),
]);
afirmar("La clave junta la accion y la IP y entra en la columna", ["registro:203.0.113.5", 80], [
    $limite('LimitePeticion::clave("registro", "203.0.113.5")'), $limite('strlen(LimitePeticion::clave(str_repeat("a", 100), "1.2.3.4"))'),
]);
afirmar("Cada accion publica pide el freno", [true, true, true, true], array_map(
    fn ($metodo) => (bool) preg_match('/function ' . $metodo . '\(\): void\s*\{\s*LimitePeticion::exigir\(/', (string) file_get_contents(__DIR__ . "/../api-usuarios/controllers/UsuarioController.php")),
    ["registrarVecino", "crearSolicitud", "recuperarClave", "crearClave"]
));

afirmar("Una sesion sin actividad por mas de 2 horas esta vencida; con actividad reciente no; sin dato, no se juzga", [false, false, true, false], [
    Auth::vencida(1000, 1000 + 7200), Auth::vencida(1000, 1000 + 60), Auth::vencida(1000, 1000 + 7201), Auth::vencida(null, 999999),
]);
