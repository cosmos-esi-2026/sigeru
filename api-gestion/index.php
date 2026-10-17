<?php

require_once __DIR__ . "/core/Response.php";
require_once __DIR__ . "/core/Auditoria.php";
require_once __DIR__ . "/core/Auth.php";
require_once __DIR__ . "/core/Permisos.php";
require_once __DIR__ . "/controllers/ContenedorController.php";
require_once __DIR__ . "/controllers/CentroAcopioController.php";
require_once __DIR__ . "/controllers/MaquinariaController.php";
require_once __DIR__ . "/controllers/TipoResiduoController.php";
require_once __DIR__ . "/controllers/ZonaController.php";
require_once __DIR__ . "/controllers/IncidenciaController.php";
require_once __DIR__ . "/controllers/EstadisticaController.php";
require_once __DIR__ . "/controllers/PublicoController.php";

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

    case "contenedores":
        $controlador = new ContenedorController();
        Permisos::exigir($subrecurso === "historial" ? "contenedores/historial" : "contenedores", $metodo);

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

    case "centros":
        $controlador = new CentroAcopioController();
        Permisos::exigir("centros", $metodo);

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
        Permisos::exigir("maquinaria", $metodo);

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
        Permisos::exigir("tipos-residuo", $metodo);

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
        Permisos::exigir("zonas", $metodo);

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

    case "incidencias":
        $controlador = new IncidenciaController();
        $usuario = Permisos::exigir($subrecurso === null ? "incidencias" : "incidencias/$subrecurso", $metodo);

        if ($subrecurso === null && $metodo === "GET" && $id !== null) {
            $controlador->obtener($id, $usuario);
        } elseif ($subrecurso === null && $metodo === "GET") {
            $controlador->listar($usuario);
        } elseif ($subrecurso === null && $metodo === "POST" && $id === null) {
            $controlador->crear($usuario);
        } elseif ($subrecurso === null && $metodo === "PUT" && $id !== null) {
            $controlador->cambiarPrioridad($id, $usuario);
        } elseif ($subrecurso === "asignacion" && $metodo === "POST" && $id !== null) {
            $controlador->asignar($id, $usuario);
        } elseif ($subrecurso === "avance" && $metodo === "POST" && $id !== null) {
            $controlador->registrarAvance($id, $usuario);
        } elseif ($subrecurso === "resolucion" && $metodo === "POST" && $id !== null) {
            $controlador->resolver($id, $usuario);
        } elseif ($subrecurso === "descarte" && $metodo === "POST" && $id !== null) {
            $controlador->descartar($id, $usuario);
        } elseif ($subrecurso === "foto" && $metodo === "POST" && $id !== null) {
            $controlador->subirFoto($id, $usuario);
        } elseif ($subrecurso === "foto" && $metodo === "GET" && $id !== null) {
            $controlador->verFoto($id, $usuario);
        } else {
            Response::error("Método no permitido", 405);
        }
        break;

    case "publico":
        Permisos::exigir("publico", $metodo);

        if ($metodo === "GET" && ($partes[1] ?? "") === "mapa") {
            (new PublicoController())->mapa();
        } elseif ($metodo === "GET") {
            Response::error("Recurso no encontrado", 404);
        } else {
            Response::error("Método no permitido", 405);
        }
        break;

    case "estadisticas":
        $controlador = new EstadisticaController();
        Permisos::exigir("estadisticas", $metodo);
        $tema = $partes[1] ?? "";

        if ($metodo === "GET" && $tema === "incidencias") {
            $controlador->incidencias();
        } elseif ($metodo === "GET" && $tema === "urgencias") {
            $controlador->urgencias();
        } elseif ($metodo === "GET") {
            Response::error("Estadística no encontrada", 404);
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
                "/centros", "/centros/{id}",
                "/maquinaria", "/maquinaria/{id}",
                "/tipos-residuo", "/tipos-residuo/{id}",
                "/zonas", "/zonas/{id}",
                "/incidencias", "/incidencias/{id}", "/incidencias/{id}/asignacion",
                "/incidencias/{id}/avance", "/incidencias/{id}/resolucion", "/incidencias/{id}/descarte",
                "/incidencias/{id}/foto",
                "/estadisticas/incidencias", "/estadisticas/urgencias",
                "/publico/mapa",
            ],
        ]);
        break;

    default:
        Response::error("Recurso no encontrado", 404);
}
