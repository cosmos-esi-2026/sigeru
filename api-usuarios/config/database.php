<?php
// ============================================================================
//  SiGeRU 2026 - Conexion a la base de datos
//  Archivo: config/database.php
//
//  Una sola conexion PDO para toda la peticion (patron singleton): abrir una
//  conexion por consulta es el error de rendimiento mas comun en PHP plano.
//
//  Por que PDO y no mysqli: PDO permite consultas preparadas con parametros
//  con nombre, que es lo que evita la inyeccion SQL, y deja cambiar de motor
//  sin reescribir los modelos.
//
//  Las credenciales son las del entorno local del proyecto (XAMPP portable,
//  root sin contrasena). La base nunca sale del pendrive; si algun dia se
//  publica en un servidor real, esto es lo primero que hay que cambiar.
// ============================================================================

class Database
{
    private static ?PDO $conexion = null;

    /**
     * Los valores por defecto son los del entorno local (XAMPP portable, root
     * sin contrasena). Se pueden pisar con variables de entorno, que es lo que
     * necesita Docker: alli la base no es 127.0.0.1 sino el contenedor "db".
     * Asi el mismo archivo sirve en los dos entornos sin editarlo.
     */
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
                    // Que un error de SQL lance excepcion en vez de devolver
                    // false en silencio: si algo falla, queremos enterarnos.
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    // Solo arrays asociativos: es lo que esperan los modelos y
                    // lo que se serializa directo a JSON.
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    // Consultas preparadas de verdad, del lado del motor.
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            // La API contesta JSON, asi que el fallo de conexion tambien tiene
            // que ser JSON: si no, el frontend recibe HTML de error de PHP y
            // no puede mostrar nada util.
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
