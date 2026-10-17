<?php

// Seguridad por HTTP (parte 11, OWASP): cabeceras, cookie de sesion, solo JSON, CORS, fijacion de sesion y
// archivos internos. Se ejecuta dentro de correr-tests.php.

titulo("22j. Seguridad: cabeceras, sesion y exposicion");

function pedirConCabeceras(string $metodo, string $ruta, ?string $cuerpo = null, array $extra = [], ?string $cookies = null): array
{
    $ch = curl_init(BASE . $ruta);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_CUSTOMREQUEST => $metodo, CURLOPT_HTTPHEADER => $extra]);
    if ($cuerpo !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $cuerpo);
    }
    if ($cookies !== null) {
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookies);
    }
    $respuesta = (string) curl_exec($ch);
    $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $tamano = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    $cabeceras = [];
    $cookiesRecibidas = [];
    foreach (explode("\n", substr($respuesta, 0, $tamano)) as $linea) {
        if (str_contains($linea, ":")) {
            [$n, $v] = explode(":", $linea, 2);
            $n = strtolower(trim($n));
            $cabeceras[$n] = trim($v);
            if ($n === "set-cookie") {
                $cookiesRecibidas[] = trim($v);
            }
        }
    }
    return [$codigo, $cabeceras, substr($respuesta, $tamano), $cookiesRecibidas];
}

[, $landing] = pedirConCabeceras("GET", "/landing/index.html");
if (!isset($landing["x-frame-options"])) {
    omitir("Las cabeceras de seguridad: el servidor no tiene mod_headers (en Docker: a2enmod headers)");
} else {
    foreach (["La landing" => "/landing/index.html", "Una pantalla del panel" => "/frontend-usuarios/dashboard.html", "Una API" => "/api-gestion/"] as $que => $ruta) {
        [, $c] = pedirConCabeceras("GET", $ruta);
        afirmar("$que lleva las cabeceras de seguridad", [true, "SAMEORIGIN", "same-origin", true], [
            ($c["x-content-type-options"] ?? "") === "nosniff", $c["x-frame-options"] ?? null, $c["referrer-policy"] ?? null,
            str_contains($c["permissions-policy"] ?? "", "camera=()"),
        ]);
        $csp = $c["content-security-policy"] ?? "";
        afirmar("$que: la politica de contenido solo deja ejecutar codigo propio", [true, true, true, true], [
            str_contains($csp, "script-src 'self'"), str_contains($csp, "object-src 'none'"),
            str_contains($csp, "frame-ancestors 'self'"), !str_contains($csp, "unsafe-eval"),
        ]);
    }
}
[, $c] = pedirConCabeceras("GET", "/api-gestion/");
afirmar("No se anuncia la version de PHP", false, isset($c["x-powered-by"]));
afirmar("Ni la version de Apache", 1, preg_match('/^Apache(?!\/\d)/', $c["server"] ?? "x") ? 1 : 0);

$credenciales = json_encode(["email" => "admin@sigeru.uy", "password" => CLAVE_DE_PRUEBA]);
[$codigo, , , $galletas] = pedirConCabeceras("POST", "/api-usuarios/login", $credenciales, ["Content-Type: application/json"]);
$galleta = $galletas[0] ?? "";
afirmar("La cookie de sesion es HttpOnly y SameSite=Lax", [200, true, true], [$codigo, str_contains($galleta, "HttpOnly"), str_contains($galleta, "SameSite=Lax")]);

foreach (["text/plain", "application/x-www-form-urlencoded"] as $tipo) {
    [$codigo] = pedirConCabeceras("POST", "/api-usuarios/login", $credenciales, ["Content-Type: $tipo"]);
    afirmar("Un cuerpo '$tipo' se rechaza (415): un formulario de otro sitio no puede mandarlo como JSON", 415, $codigo);
}
[$codigo] = pedirConCabeceras("POST", "/api-usuarios/login", $credenciales, ["Content-Type: multipart/form-data; boundary=x"]);
afirmar("Un formulario multipart no llega como JSON (PHP no entrega su cuerpo): no inicia sesion", true, $codigo !== 200);
[$codigo] = pedirConCabeceras("POST", "/api-gestion/contenedores", "{}", ["Content-Type: text/plain"], $admin);
afirmar("Tampoco para una accion del administrador", 415, $codigo);
[$codigo] = pedirConCabeceras("POST", "/api-usuarios/login", $credenciales, ["Content-Type: application/json; charset=utf-8"]);
afirmar("JSON con charset si se acepta", 200, $codigo);

// Fijacion de sesion: la cookie que elige el atacante no se acepta ni se conserva despues de entrar.
[, , , $galletas] = pedirConCabeceras("POST", "/api-usuarios/login", $credenciales, ["Content-Type: application/json", "Cookie: SIGERUSESSID=fijadaporelatacante0123456789"]);
afirmar("Un id de sesion impuesto desde afuera no se reutiliza al iniciar sesion", true,
    $galletas !== [] && !str_contains(implode(";", $galletas), "fijadaporelatacante"));

// Cerrar sesion la invalida en el servidor, no solo en el navegador.
$archivoCierre = sys_get_temp_dir() . "/sigeru_test_cierre.txt";
iniciarSesion("vecino@sigeru.uy", $archivoCierre);
[$codigo] = peticion("GET", "/api-usuarios/sesion", null, $archivoCierre);
afirmar("Con sesion, /sesion responde 200", 200, $codigo);
$cookieAntes = (string) file_get_contents($archivoCierre);
peticion("POST", "/api-usuarios/logout", [], $archivoCierre);
file_put_contents($archivoCierre, $cookieAntes);
[$codigo] = peticion("GET", "/api-usuarios/sesion", null, $archivoCierre);
afirmar("Despues de cerrar sesion, la cookie vieja ya no sirve (401)", 401, $codigo);
unlink($archivoCierre);

[, $c] = pedirConCabeceras("GET", "/api-gestion/contenedores", null, ["Origin: http://sitio-malicioso.example"], $vecino);
afirmar("CORS: otro origen no recibe permiso (ni cookies compartidas)", [false, false], [isset($c["access-control-allow-origin"]), isset($c["access-control-allow-credentials"])]);

foreach (["/.htaccess", "/.github/workflows/docker.yml", "/api-usuarios/core/Auth.php", "/api-gestion/config/database.php",
          "/database/datos_prueba.sql", "/tests/unitarias.php", "/tmp/correo/", "/almacen/", "/docker/Dockerfile", "/ciberseguridad/LEEME.md"] as $interno) {
    [$codigo, , $cuerpo] = pedirConCabeceras("GET", $interno);
    afirmar("No se expone $interno", true, in_array($codigo, [403, 404], true) && !str_contains($cuerpo, "<?php") && !str_contains($cuerpo, "password"));
}

titulo("22k. Auditoria: quien hizo que");

function filaDeAuditoria(PDO $pdo, string $metodo, string $ruta, ?int $usuario): ?array
{
    $consulta = $pdo->prepare("SELECT * FROM auditoria WHERE metodo = ? AND ruta = ? AND " . ($usuario === null ? "id_usuario IS NULL" : "id_usuario = ?") . " ORDER BY id_auditoria DESC LIMIT 1");
    $consulta->execute($usuario === null ? [$metodo, $ruta] : [$metodo, $ruta, $usuario]);
    $fila = $consulta->fetch(PDO::FETCH_ASSOC);
    return $fila === false ? null : $fila;
}
function totalDeAuditoria(PDO $pdo): int
{
    return (int) $pdo->query("SELECT COUNT(*) FROM auditoria")->fetchColumn();
}

[, $camion] = peticion("GET", "/api-recoleccion/camiones/1", null, $admin);
[$codigo] = peticion("PUT", "/api-recoleccion/camiones/1", ["marca" => $camion["datos"]["marca"]], $admin);
$fila = filaDeAuditoria($pdo, "PUT", "/api-recoleccion/camiones/1", 1);
afirmar("Un cambio del administrador queda registrado con su persona, rol, ruta y resultado", [200, "Administrador", 200, true],
    [$codigo, $fila["rol"] ?? null, (int) ($fila["codigo"] ?? 0), $fila !== null && $fila["ip"] !== ""]);

[$codigo] = peticion("POST", "/api-gestion/contenedores", ["codigo" => "NOPUEDE-$sufijo"], $vecino);
$fila = filaDeAuditoria($pdo, "POST", "/api-gestion/contenedores", 3);
afirmar("Un acceso negado por permisos tambien (403), con quien lo intento", [403, "Vecino", 403], [$codigo, $fila["rol"] ?? null, (int) ($fila["codigo"] ?? 0)]);

$antes = totalDeAuditoria($pdo);
peticion("GET", "/api-gestion/contenedores", null, $admin);
peticion("GET", "/api-usuarios/usuarios", null, $admin);
peticion("POST", "/api-gestion/contenedores", ["codigo" => "A B"], $admin);
peticion("PUT", "/api-recoleccion/camiones/99999", ["marca" => "x"], $admin);
peticion("GET", "/api-usuarios/usuarios");
afirmar("Las lecturas, los errores de validacion, los 404 y los 401 no se registran", $antes, totalDeAuditoria($pdo));

$archivoAuditoria = sys_get_temp_dir() . "/sigeru_test_auditoria.txt";
iniciarSesion("vecino@sigeru.uy", $archivoAuditoria);
afirmar("Iniciar sesion queda registrado", 3, (int) (filaDeAuditoria($pdo, "POST", "/api-usuarios/login", 3)["id_usuario"] ?? 0));
peticion("POST", "/api-usuarios/logout", [], $archivoAuditoria);
afirmar("Cerrar sesion tambien, y sabe quien era aunque la sesion ya no exista", 3, (int) (filaDeAuditoria($pdo, "POST", "/api-usuarios/logout", 3)["id_usuario"] ?? 0));
unlink($archivoAuditoria);

$secretos = (int) $pdo->query("SELECT COUNT(*) FROM auditoria WHERE ruta LIKE '%" . CLAVE_DE_PRUEBA . "%' OR ruta LIKE '%token%' OR ruta LIKE '%password%'")->fetchColumn();
afirmar("Nada de lo que viaja en el cuerpo (contrasenas, enlaces) llega a la auditoria", 0, $secretos);

[$codigo, $cuerpo] = peticion("GET", "/api-usuarios/auditoria", null, $admin);
afirmar("El administrador lee la auditoria (200), de lo mas nuevo a lo mas viejo", [200, true], [$codigo,
    ($cuerpo["datos"][0]["id_auditoria"] ?? 0) > ($cuerpo["datos"][1]["id_auditoria"] ?? PHP_INT_MAX)]);
afirmar("Cada fila trae fecha, persona, rol, operacion, ruta, resultado e IP", true, array_reduce($cuerpo["datos"] ?? [], fn ($ok, $f) =>
    $ok && isset($f["fecha_hora"], $f["metodo"], $f["ruta"], $f["codigo"], $f["ip"]) && array_key_exists("usuario", $f), true));
afirmar("La respuesta no trae el cuerpo de ninguna peticion ni cabeceras", [], array_values(array_filter(array_keys($cuerpo["datos"][0] ?? []), fn ($k) => in_array($k, ["cuerpo", "body", "password", "cabeceras"], true))));
[, $negados] = peticion("GET", "/api-usuarios/auditoria?resultado=negado", null, $admin);
afirmar("Filtrar por 'negado' deja solo los 403", [true, true], [count($negados["datos"] ?? []) >= 1,
    array_reduce($negados["datos"] ?? [], fn ($ok, $f) => $ok && $f["codigo"] === 403, true)]);
[, $puts] = peticion("GET", "/api-usuarios/auditoria?metodo=PUT&q=camiones", null, $admin);
afirmar("Filtrar por operacion y por ruta", true, count($puts["datos"] ?? []) >= 1 && array_reduce($puts["datos"], fn ($ok, $f) => $ok && $f["metodo"] === "PUT" && str_contains($f["ruta"], "camiones"), true));
[, $deVecino] = peticion("GET", "/api-usuarios/auditoria?usuario=3", null, $admin);
afirmar("Filtrar por persona", true, count($deVecino["datos"] ?? []) >= 1 && array_reduce($deVecino["datos"], fn ($ok, $f) => $ok && $f["id_usuario"] === 3, true));
[, $pagina] = peticion("GET", "/api-usuarios/auditoria?pagina=1&por_pagina=2", null, $admin);
afirmar("Paginada: dos filas y el total del registro", [2, totalDeAuditoria($pdo)], [count($pagina["datos"] ?? []), $pagina["meta"]["total"] ?? null]);
[, $hoy] = peticion("GET", "/api-usuarios/auditoria?desde=" . date("Y-m-d") . "&hasta=" . date("Y-m-d"), null, $admin);
afirmar("Filtrar por fecha", true, ($hoy["meta"]["total"] ?? 0) >= 1);
[, $futuro] = peticion("GET", "/api-usuarios/auditoria?desde=2999-01-01", null, $admin);
afirmar("Un periodo sin movimientos esta vacio", [[], 0], [$futuro["datos"] ?? null, $futuro["meta"]["total"] ?? null]);
foreach (["usuario=abc", "metodo=GET", "resultado=quizas", "desde=2026-13-45", "pagina=0"] as $mala) {
    [$codigo] = peticion("GET", "/api-usuarios/auditoria?$mala", null, $admin);
    afirmar("Auditoria con '$mala': 400", 400, $codigo);
}
foreach (["Vecino" => $vecino, "Operario" => $operario, "Cuadrilla" => $cuadrilla] as $rol => $sesion) {
    [$codigo] = peticion("GET", "/api-usuarios/auditoria", null, $sesion);
    afirmar("Un $rol no lee la auditoria (403)", 403, $codigo);
}
[$codigo] = peticion("GET", "/api-usuarios/auditoria");
afirmar("Sin sesion (401)", 401, $codigo);
[$codigo] = peticion("DELETE", "/api-usuarios/auditoria/1", null, $admin);
afirmar("No existe ninguna operacion para borrar o cambiar la auditoria (404)", 404, $codigo);
[$codigo] = peticion("PUT", "/api-usuarios/auditoria/1", ["ruta" => "x"], $admin);
afirmar("Ni para modificarla (404)", 404, $codigo);

titulo("22l. Seguridad: inyeccion SQL");

foreach (["admin@sigeru.uy' OR '1'='1", "' OR 1=1 --", "admin@sigeru.uy'; DROP TABLE usuario; --"] as $correo) {
    [$codigo] = peticion("POST", "/api-usuarios/login", ["email" => $correo, "password" => "x"]);
    afirmar("Login con el correo " . json_encode($correo) . " no entra", true, in_array($codigo, [400, 401], true));
}
[$codigo] = peticion("POST", "/api-usuarios/login", ["email" => "admin@sigeru.uy", "password" => "' OR '1'='1"]);
afirmar("Login con una contrasena de inyeccion no entra (401)", 401, $codigo);
[$codigo] = peticion("GET", "/api-gestion/contenedores?zona=1%20OR%201%3D1", null, $admin);
afirmar("Un filtro numerico con SQL se rechaza (400)", 400, $codigo);
[$codigo, $cuerpo] = peticion("GET", "/api-gestion/contenedores?q=" . rawurlencode("'; DROP TABLE contenedor; --"), null, $admin);
afirmar("Una busqueda con SQL es solo texto: sin resultados y sin efectos", [200, 0], [$codigo, count($cuerpo["datos"] ?? [-1])]);
[$codigo, $cuerpo] = peticion("GET", "/api-gestion/contenedores", null, $admin);
afirmar("La tabla sigue ahi", true, $codigo === 200 && count($cuerpo["datos"] ?? []) >= 9);
[$codigo, $cuerpo] = peticion("GET", "/api-gestion/contenedores/1%20OR%201=1", null, $admin);
afirmar("Un id con SQL no devuelve la lista entera", true, !isset($cuerpo["datos"][1]));
[$codigo] = peticion("GET", "/api-gestion/estadisticas/incidencias?desde=" . rawurlencode("2026-01-01' OR '1'='1"), null, $admin);
afirmar("Una fecha con SQL (400)", 400, $codigo);
[$codigo] = peticion("GET", "/api-usuarios/auditoria?q=" . rawurlencode("%' OR 1=1 --"), null, $admin);
afirmar("La busqueda de la auditoria tambien es solo texto (200)", 200, $codigo);

titulo("22m. Seguridad: freno a los pedidos publicos");

foreach (["/api-usuarios/solicitudes" => "solicitudes de registro", "/api-usuarios/registro" => "registros", "/api-usuarios/recuperar-clave" => "pedidos de recuperacion"] as $ruta => $que) {
    $aceptados = 0;
    $detenido = false;
    $reintentar = null;
    for ($i = 1; $i <= 14; $i++) {
        [$codigo, $cabeceras] = pedirConCabeceras("POST", $ruta, "{}", ["Content-Type: application/json"]);
        if ($codigo === 429) {
            $detenido = true;
            $reintentar = $cabeceras["retry-after"] ?? null;
            break;
        }
        $aceptados++;
    }
    afirmar("Una misma IP no puede mandar mas de 10 $que por hora: el pedido numero " . ($aceptados + 1) . " recibe 429 con Retry-After",
        [true, true, "3600"], [$detenido, $aceptados >= 1 && $aceptados <= 10, $reintentar]);
}
$pdo->exec("DELETE FROM limite_peticion");
[$codigo] = pedirConCabeceras("POST", "/api-usuarios/solicitudes", "{}", ["Content-Type: application/json"]);
afirmar("Pasada la ventana (aca, borrando el contador) se puede de nuevo", 400, $codigo);
$pdo->exec("DELETE FROM limite_peticion");
foreach (["/frontend-usuarios/js/", "/landing/js/", "/frontend-usuarios/css/", "/landing/vendor/"] as $directorio) {
    [$codigo, , $cuerpo] = pedirConCabeceras("GET", $directorio);
    afirmar("No se lista el contenido de $directorio", true, in_array($codigo, [403, 404], true) && !str_contains($cuerpo, "Index of"));
}
