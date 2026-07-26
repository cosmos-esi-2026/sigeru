<?php

require_once __DIR__ . "/core/Response.php";
require_once __DIR__ . "/controllers/ContenedorController.php";
require_once __DIR__ . "/controllers/CamionController.php";

Response::cors();

$metodo = $_SERVER["REQUEST_METHOD"];
$ruta   = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$ruta   = rtrim($ruta, "/");
$partes = explode("/", trim($ruta, "/"));

$recurso = $partes[0] ?? "";
$id      = $partes[1] ?? null;

switch ($recurso) {

    case "contenedores":
        $controlador = new ContenedorController();
        if ($metodo === "GET" && $id !== null) {
            $controlador->obtener((int) $id);
        } elseif ($metodo === "GET") {
            $controlador->listar();
        } elseif ($metodo === "POST") {
            $controlador->crear();
        } else {
            Response::json(["ok" => false, "mensaje" => "Metodo no permitido"], 405);
        }
        break;

    case "camiones":
        $controlador = new CamionController();
        if ($metodo === "GET" && $id !== null) {
            $controlador->obtener((int) $id);
        } elseif ($metodo === "GET") {
            $controlador->listar();
        } elseif ($metodo === "POST") {
            $controlador->crear();
        } else {
            Response::json(["ok" => false, "mensaje" => "Metodo no permitido"], 405);
        }
        break;

    case "":
        Response::json([
            "ok" => true,
            "mensaje" => "API de gestion SiGeRU funcionando",
            "endpoints" => [
                "/contenedores", "/contenedores/{id}",
                "/camiones", "/camiones/{id}"
            ]
        ]);
        break;

    default:
        Response::json(["ok" => false, "mensaje" => "Recurso no encontrado"], 404);
}