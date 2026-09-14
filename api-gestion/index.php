<?php

require_once __DIR__ . "/core/Response.php";
require_once __DIR__ . "/core/Auth.php";
require_once __DIR__ . "/controllers/ContenedorController.php";
require_once __DIR__ . "/controllers/CamionController.php";
require_once __DIR__ . "/controllers/CentroAcopioController.php";
require_once __DIR__ . "/controllers/MaquinariaController.php";
require_once __DIR__ . "/controllers/TipoResiduoController.php";
require_once __DIR__ . "/controllers/ZonaController.php";

Response::manejarErrores();
Response::cors();

$metodo = $_SERVER["REQUEST_METHOD"];

$base = str_replace("\\", "/", dirname($_SERVER["SCRIPT_NAME"]));
$ruta = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
if ($base !== "/" && str_starts_with($ruta, $base)) {
    $ruta = substr($ruta, strlen($base));
}
$partes = explode("/", trim(rtrim($ruta, "/"), "/"));

$recurso    = $partes[0] ?? "";
$id         = isset($partes[1]) && $partes[1] !== "" ? (int) $partes[1] : null;
$subrecurso = $partes[2] ?? null;

function exigirAdministrador(string $metodo): void
{
    if (in_array($metodo, ["POST", "PUT", "DELETE"], true)) {
        Auth::exigirRol("Administrador");
    }
}

switch ($recurso) {

    case "contenedores":
        $controlador = new ContenedorController();
        exigirAdministrador($metodo);

        if ($subrecurso === "historial" && $metodo === "GET" && $id !== null) {
            $controlador->historial($id);
        } elseif ($metodo === "GET" && $id !== null) {
            $controlador->obtener($id);
        } elseif ($metodo === "GET") {
            $controlador->listar();
        } elseif ($metodo === "POST") {
            $controlador->crear();
        } elseif ($metodo === "PUT" && $id !== null) {
            $controlador->actualizar($id);
        } elseif ($metodo === "DELETE" && $id !== null) {
            $controlador->darDeBaja($id);
        } else {
            Response::error("Método no permitido", 405);
        }
        break;

    case "camiones":
        $controlador = new CamionController();
        exigirAdministrador($metodo);

        if ($metodo === "GET" && $id !== null) {
            $controlador->obtener($id);
        } elseif ($metodo === "GET") {
            $controlador->listar();
        } elseif ($metodo === "POST") {
            $controlador->crear();
        } elseif ($metodo === "PUT" && $id !== null) {
            $controlador->actualizar($id);
        } elseif ($metodo === "DELETE" && $id !== null) {
            $controlador->darDeBaja($id);
        } else {
            Response::error("Método no permitido", 405);
        }
        break;

    case "centros":
        $controlador = new CentroAcopioController();
        exigirAdministrador($metodo);

        if ($metodo === "GET" && $id !== null) {
            $controlador->obtener($id);
        } elseif ($metodo === "GET") {
            $controlador->listar();
        } elseif ($metodo === "POST") {
            $controlador->crear();
        } elseif ($metodo === "PUT" && $id !== null) {
            $controlador->actualizar($id);
        } elseif ($metodo === "DELETE" && $id !== null) {
            $controlador->darDeBaja($id);
        } else {
            Response::error("Método no permitido", 405);
        }
        break;

    case "maquinaria":
        $controlador = new MaquinariaController();
        exigirAdministrador($metodo);

        if ($metodo === "GET" && $id !== null) {
            $controlador->obtener($id);
        } elseif ($metodo === "GET") {
            $controlador->listar();
        } elseif ($metodo === "POST") {
            $controlador->crear();
        } elseif ($metodo === "PUT" && $id !== null) {
            $controlador->actualizar($id);
        } elseif ($metodo === "DELETE" && $id !== null) {
            $controlador->darDeBaja($id);
        } else {
            Response::error("Método no permitido", 405);
        }
        break;

    case "tipos-residuo":
        $controlador = new TipoResiduoController();
        exigirAdministrador($metodo);

        if ($metodo === "GET" && $id !== null) {
            $controlador->obtener($id);
        } elseif ($metodo === "GET") {
            $controlador->listar();
        } elseif ($metodo === "POST") {
            $controlador->crear();
        } elseif ($metodo === "PUT" && $id !== null) {
            $controlador->actualizar($id);
        } elseif ($metodo === "DELETE" && $id !== null) {
            $controlador->eliminar($id);
        } else {
            Response::error("Método no permitido", 405);
        }
        break;

    case "zonas":
        $controlador = new ZonaController();
        exigirAdministrador($metodo);

        if ($metodo === "GET" && $id !== null) {
            $controlador->obtener($id);
        } elseif ($metodo === "GET") {
            $controlador->listar();
        } elseif ($metodo === "POST") {
            $controlador->crear();
        } else {
            Response::error("Método no permitido", 405);
        }
        break;

    case "":
        Response::json([
            "ok"      => true,
            "mensaje" => "API de gestión SiGeRU funcionando",
            "endpoints" => [
                "/contenedores", "/contenedores/{id}", "/contenedores/{id}/historial",
                "/camiones", "/camiones/{id}",
                "/centros", "/centros/{id}",
                "/maquinaria", "/maquinaria/{id}",
                "/tipos-residuo", "/tipos-residuo/{id}",
                "/zonas", "/zonas/{id}",
            ],
        ]);
        break;

    default:
        Response::error("Recurso no encontrado", 404);
}
