<?php
// ============================================================================
//  SiGeRU 2026 - Respuestas de la API
//
//  Todo lo que sale de la API sale por aca: siempre JSON, siempre con el codigo
//  HTTP correcto. Incluye la red de seguridad que evita que un error de PHP se
//  escape como HTML (que ademas de romper al cliente, mostraba la ruta completa
//  de los archivos del servidor).
// ============================================================================

class Response
{
    public static function cors(): void
    {
        // El frontend se sirve desde el mismo origen que las APIs (Apache, bajo
        // /sigeru/), asi que no hace falta abrir CORS a cualquiera. Se responde
        // el preflight por si alguna herramienta lo manda.
        header("Vary: Origin");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type");

        if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
            http_response_code(204);
            exit;
        }
    }

    public static function json($datos, int $codigo = 200): void
    {
        http_response_code($codigo);
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** Atajo para los errores, que siempre tienen la misma forma. */
    public static function error(string $mensaje, int $codigo): void
    {
        self::json(["ok" => false, "mensaje" => $mensaje], $codigo);
    }

    public static function leerJson(): array
    {
        $crudo = file_get_contents("php://input");
        $datos = json_decode($crudo, true);
        return is_array($datos) ? $datos : [];
    }

    /**
     * Red de seguridad: cualquier excepcion que no se haya atajado antes sale
     * como JSON 500 y con un mensaje generico. El detalle real va al log de
     * PHP, no a la respuesta: un mensaje de PDO puede revelar nombres de tabla
     * y rutas del servidor.
     */
    public static function manejarErrores(): void
    {
        set_exception_handler(function (Throwable $e) {
            error_log("SiGeRU API: " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine());

            if (!headers_sent()) {
                http_response_code(500);
                header("Content-Type: application/json; charset=utf-8");
            }
            echo json_encode([
                "ok"      => false,
                "mensaje" => "Ocurrió un error inesperado en el servidor.",
            ], JSON_UNESCAPED_UNICODE);
        });
    }
}
