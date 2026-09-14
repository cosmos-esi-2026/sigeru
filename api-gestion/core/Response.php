<?php

class Response
{
    public static function cors(): void
    {

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
