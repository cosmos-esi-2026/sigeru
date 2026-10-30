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
        if (class_exists("Auditoria", false)) {
            Auditoria::registrar($codigo);
        }
        http_response_code($codigo);
        header("Content-Type: application/json; charset=utf-8");
        header("Cache-Control: no-store");
        header("X-Content-Type-Options: nosniff");
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function error(string $mensaje, int $codigo): void
    {
        self::json(["ok" => false, "mensaje" => $mensaje], $codigo);
    }

    // Solo se acepta JSON. Un formulario de otro sitio no puede mandar "application/json" sin pedir permiso
    // antes (y esta API no lo da): es una defensa en profundidad contra CSRF, ademas de la cookie SameSite.
    public static function leerJson(): array
    {
        $crudo = file_get_contents("php://input");
        if ($crudo !== "" && stripos($_SERVER["CONTENT_TYPE"] ?? "", "application/json") !== 0) {
            self::error("El cuerpo de la petición tiene que ser JSON (Content-Type: application/json)", 415);
        }
        $datos = json_decode($crudo, true);
        return is_array($datos) ? $datos : [];
    }

    public static function manejarErrores(): void
    {
        // Uruguay no tiene horario de verano: la hora de PHP se fija aca para que no dependa del php.ini del servidor (XAMPP trae Europe/Berlin).
        date_default_timezone_set("America/Montevideo");

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
