<?php

require_once __DIR__ . "/core/Response.php";
require_once __DIR__ . "/core/Auditoria.php";
require_once __DIR__ . "/core/Auth.php";
require_once __DIR__ . "/core/Permisos.php";
require_once __DIR__ . "/controllers/CamionController.php";
require_once __DIR__ . "/controllers/CuadrillaController.php";
require_once __DIR__ . "/controllers/RutaController.php";
require_once __DIR__ . "/controllers/RecoleccionController.php";
require_once __DIR__ . "/controllers/EstadisticaController.php";
require_once __DIR__ . "/controllers/PasajeController.php";

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

switch ($recurso) {

    case "camiones":
        $controlador = new CamionController();
        Permisos::exigir("camiones", $metodo);

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

    case "cuadrillas":
        $controlador = new CuadrillaController();
        Permisos::exigir("cuadrillas", $metodo);

        if ($metodo === "GET" && $id !== null) {
            $controlador->obtener($id);
        } elseif ($metodo === "GET") {
            $controlador->listar();
        } else {
            Response::error("Método no permitido", 405);
        }
        break;

    case "rutas":
        $controlador = new RutaController();
        Permisos::exigir("rutas", $metodo);

        if (($partes[1] ?? "") === "mapa") {
            if ($metodo === "GET" && $subrecurso === null) {
                $controlador->mapa();
            } else {
                Response::error("Método no permitido", 405);
            }
        } elseif ($subrecurso === "contenedores" && $metodo === "PUT" && $id !== null) {
            $controlador->reemplazarContenedores($id);
        } elseif ($subrecurso === "candidatos" && $metodo === "GET" && $id !== null) {
            $controlador->candidatos($id);
        } elseif ($subrecurso === "trazado" && $metodo === "POST" && $id !== null) {
            $controlador->trazar($id);
        } elseif ($subrecurso !== null) {
            Response::error("Método no permitido", 405);
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

    case "recolecciones":
        $controlador = new RecoleccionController();
        $usuario = Permisos::exigir($subrecurso === null ? "recolecciones" : "recolecciones/$subrecurso", $metodo);

        if ($subrecurso === null && $metodo === "GET" && $id !== null) {
            $controlador->obtener($id, $usuario);
        } elseif ($subrecurso === null && $metodo === "GET") {
            $controlador->listar($usuario);
        } elseif ($subrecurso === null && $metodo === "POST" && $id === null) {
            $controlador->planificar();
        } elseif ($subrecurso === "inicio" && $metodo === "POST" && $id !== null) {
            $controlador->iniciar($id, $usuario);
        } elseif ($subrecurso === "vaciados" && $metodo === "POST" && $id !== null) {
            $controlador->registrarVaciado($id, $usuario);
        } elseif ($subrecurso === "fin" && $metodo === "POST" && $id !== null) {
            $controlador->finalizar($id, $usuario);
        } elseif ($subrecurso === "cancelacion" && $metodo === "POST" && $id !== null) {
            $controlador->cancelar($id, $usuario);
        } else {
            Response::error("Método no permitido", 405);
        }
        break;

    case "proximo-pasaje":
        $usuario = Permisos::exigir("proximo-pasaje", $metodo);

        if ($metodo === "GET") {
            (new PasajeController())->proximo($usuario);
        } else {
            Response::error("Método no permitido", 405);
        }
        break;

    case "estadisticas":
        $controlador = new EstadisticaController();
        Permisos::exigir("estadisticas", $metodo);

        if ($metodo === "GET" && ($partes[1] ?? "") === "recoleccion") {
            $controlador->recoleccion();
        } elseif ($metodo === "GET") {
            Response::error("Estadística no encontrada", 404);
        } else {
            Response::error("Método no permitido", 405);
        }
        break;

    case "":
        Response::json([
            "ok"      => true,
            "mensaje" => "API de recolección SiGeRU funcionando",
            "trazado" => Trazado::modo(),
            "endpoints" => [
                "/camiones", "/camiones/{id}",
                "/cuadrillas", "/cuadrillas/{id}",
                "/rutas", "/rutas/{id}", "/rutas/mapa", "/rutas/{id}/contenedores", "/rutas/{id}/candidatos", "/rutas/{id}/trazado",
                "/recolecciones", "/recolecciones/{id}", "/recolecciones/{id}/inicio",
                "/recolecciones/{id}/vaciados", "/recolecciones/{id}/fin", "/recolecciones/{id}/cancelacion",
                "/estadisticas/recoleccion", "/proximo-pasaje",
            ],
        ]);
        break;

    default:
        Response::error("Recurso no encontrado", 404);
}
