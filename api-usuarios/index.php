<?php
// ============================================================================
//  SiGeRU 2026 - API de usuarios - index.php (Router / punto de entrada)
//
//  Todas las peticiones entran por acá (ver .htaccess). Este archivo decide
//  qué acción del controlador ejecutar segun el verbo HTTP (GET, POST, PUT) y
//  la ruta pedida. NO define modelos ni logica de negocio: solo dirige el
//  trafico.
//
//  Rutas disponibles:
//    GET  /                     -> informacion de la API (para probar que responde)
//    GET  /usuarios             -> lista todos los usuarios      (Administrador)
//    GET  /usuarios/{id}        -> devuelve un usuario por su id (Administrador)
//    POST /usuarios             -> crea un usuario               (Administrador)
//    POST /login                -> valida email + contrasena     (publico)
//    POST /solicitudes          -> registra una solicitud de alta (publico)
//    GET  /solicitudes          -> lista las solicitudes         (Administrador)
//    PUT  /solicitudes/{id}     -> aprueba o rechaza una solicitud (Administrador)
//
//  El registro publico crea una SOLICITUD, nunca un usuario: el alta efectiva
//  la hace un administrador al aprobarla. Ese es el circuito que modela la
//  tabla solicitud_registro del DER.
// ============================================================================

require_once __DIR__ . "/core/Response.php";
require_once __DIR__ . "/controllers/UsuarioController.php";

// Red de seguridad: cualquier excepcion sale como JSON, nunca como HTML de PHP
Response::manejarErrores();
Response::cors();

// --- 1. Obtener la ruta pedida, sin el prefijo de la carpeta ----------------
// Ejemplo: si la API vive en http://localhost/api-usuarios/ y se pide
// ".../api-usuarios/usuarios/5", aca queremos quedarnos solo con "/usuarios/5".
$base = str_replace("\\", "/", dirname($_SERVER["SCRIPT_NAME"])); // /api-usuarios
$ruta = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);         // /api-usuarios/usuarios/5
if ($base !== "/" && str_starts_with($ruta, $base)) {
    $ruta = substr($ruta, strlen($base));                        // /usuarios/5
}
$ruta = "/" . trim($ruta, "/");                                  // normaliza barras

$metodo = $_SERVER["REQUEST_METHOD"];

// Parte la ruta en segmentos: "/usuarios/5" -> ["usuarios", "5"]
$segmentos = array_values(array_filter(explode("/", $ruta), fn($s) => $s !== ""));
$recurso = $segmentos[0] ?? "";

// --- 2. Dirigir cada ruta a su accion del controlador -----------------------
$controlador = new UsuarioController();

// GET /  -> mensaje de bienvenida, util para verificar que la API anda
if ($ruta === "/" && $metodo === "GET") {
    Response::json([
        "ok"        => true,
        "api"       => "SiGeRU - API de usuarios",
        "endpoints" => [
            "GET /usuarios",
            "GET /usuarios/{id}",
            "POST /usuarios",
            "POST /login",
            "POST /solicitudes",
            "GET /solicitudes",
            "PUT /solicitudes/{id}",
        ],
    ]);
}

// Rutas del recurso /usuarios
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
    // La baja es logica: el usuario pasa a inactivo, la fila no se borra.
    if ($metodo === "DELETE" && count($segmentos) === 2) {
        $controlador->darDeBaja((int) $segmentos[1]);
    }
}

// POST /login  y  POST /logout
if ($recurso === "login" && $metodo === "POST") {
    $controlador->login();
}
if ($recurso === "logout" && $metodo === "POST") {
    $controlador->logout();
}

// GET /sesion -> quien esta logueado. El frontend lo usa para saber si la
// sesion del servidor sigue viva sin tener que pedir un recurso protegido.
if ($recurso === "sesion" && $metodo === "GET") {
    $controlador->sesion();
}

// Rutas del recurso /solicitudes
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

// --- 3. Si ninguna ruta coincidio -------------------------------------------
Response::json(["ok" => false, "mensaje" => "Ruta no encontrada: $metodo $ruta"], 404);
