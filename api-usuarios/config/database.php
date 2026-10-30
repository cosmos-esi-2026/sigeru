<?php

class Database
{
    private static ?PDO $conexion = null;

    private static function config(string $clave, string $porDefecto): string
    {
        $valor = getenv($clave);
        return $valor === false || $valor === "" ? $porDefecto : $valor;
    }

    public static function conexion(): PDO
    {
        if (self::$conexion !== null) {
            return self::$conexion;
        }

        $dsn = "mysql:host=" . self::config("SIGERU_DB_HOST", "127.0.0.1") .
               ";port="      . self::config("SIGERU_DB_PORT", "3306") .
               ";dbname="    . self::config("SIGERU_DB_NAME", "sigeru") .
               ";charset=utf8mb4";

        try {
            self::$conexion = new PDO(
                $dsn,
                self::config("SIGERU_DB_USER", "root"),
                self::config("SIGERU_DB_PASS", ""),
                [

                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,

                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
            // NOW() y los DEFAULT CURRENT_TIMESTAMP usan la hora de Uruguay (UTC-3 todo el anio) aunque el servidor de la base este en UTC.
            self::$conexion->exec("SET time_zone = '" . self::config("SIGERU_DB_TZ", "-03:00") . "'");
        } catch (PDOException $e) {

            http_response_code(503);
            header("Content-Type: application/json; charset=utf-8");
            echo json_encode([
                "ok"      => false,
                "mensaje" => "No hay conexión con la base de datos. Verificá que MySQL esté corriendo.",
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        return self::$conexion;
    }
}
