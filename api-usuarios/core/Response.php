<?php
class Response
{
    public static function cors(): void
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
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

    public static function leerJson(): array
    {
        $crudo = file_get_contents("php://input");
        $datos = json_decode($crudo, true);
        return is_array($datos) ? $datos : [];
    }
}