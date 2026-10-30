<?php

require_once __DIR__ . "/core/Response.php";
require_once __DIR__ . "/core/Auditoria.php";
require_once __DIR__ . "/controllers/UsuarioController.php";
require_once __DIR__ . "/controllers/NotificacionController.php";

Response::manejarErrores();
Response::cors();

$base = str_replace("\\", "/", dirname($_SERVER["SCRIPT_NAME"]));
$ruta = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
if ($base !== "/" && str_starts_with($ruta, $base)) {
    $ruta = substr($ruta, strlen($base));
}
$ruta = "/" . trim($ruta, "/");

$metodo = $_SERVER["REQUEST_METHOD"];

$segmentos = array_values(array_filter(explode("/", $ruta), fn($s) => $s !== ""));
$recurso = $segmentos[0] ?? "";

$controlador = new UsuarioController();

if ($ruta === "/" && $metodo === "GET") {
    Response::json([
        "ok"        => true,
        "api"       => "SiGeRU - API de usuarios",
        "endpoints" => [
            "GET /usuarios",
            "GET /usuarios/{id}",
            "POST /usuarios",
            "POST /login",
            "POST /registro",
            "GET /auditoria",
            "POST /solicitudes",
            "GET /solicitudes",
            "PUT /solicitudes/{id}",
            "GET /notificaciones",
            "POST /notificaciones/{id}/leida",
            "POST /notificaciones/leer-todas",
            "PUT /mi-zona",
        ],
    ]);
}

if ($recurso === "usuarios") {
    if ($metodo === "GET" && count($segmentos) === 1) {
        $controlador->listar();
    }
    if ($metodo === "GET" && count($segmentos) === 2) {
        $controlador->obtener((int) $segmentos[1]);
    }
    if ($metodo === "POST" && count($segmentos) === 1) {
        $controlador->crear();
    }
    if ($metodo === "PUT" && count($segmentos) === 2) {
        $controlador->actualizar((int) $segmentos[1]);
    }

    if ($metodo === "DELETE" && count($segmentos) === 2) {
        $controlador->darDeBaja((int) $segmentos[1]);
    }
}

if ($recurso === "auditoria" && $metodo === "GET" && count($segmentos) === 1) {
    $controlador->auditoria();
}

if ($recurso === "registro" && $metodo === "POST") {
    $controlador->registrarVecino();
}

if ($recurso === "login" && $metodo === "POST") {
    $controlador->login();
}
if ($recurso === "logout" && $metodo === "POST") {
    $controlador->logout();
}

if ($recurso === "sesion" && $metodo === "GET") {
    $controlador->sesion();
}

if ($recurso === "solicitudes") {
    if ($metodo === "POST" && count($segmentos) === 1) {
        $controlador->crearSolicitud();
    }
    if ($metodo === "GET" && count($segmentos) === 1) {
        $controlador->listarSolicitudes();
    }
    if ($metodo === "PUT" && count($segmentos) === 2) {
        $controlador->resolverSolicitud((int) $segmentos[1]);
    }
}

if ($recurso === "notificaciones") {
    $avisos = new NotificacionController();
    if ($metodo === "GET" && count($segmentos) === 1) {
        $avisos->listar();
    }
    if ($metodo === "POST" && count($segmentos) === 2 && $segmentos[1] === "leer-todas") {
        $avisos->leerTodas();
    }
    if ($metodo === "POST" && count($segmentos) === 3 && $segmentos[2] === "leida" && ctype_digit($segmentos[1])) {
        $avisos->marcarLeida((int) $segmentos[1]);
    }
}

if ($recurso === "mi-zona" && $metodo === "PUT" && count($segmentos) === 1) {
    $controlador->guardarMiZona();
}

Response::json(["ok" => false, "mensaje" => "Ruta no encontrada: $metodo $ruta"], 404);
