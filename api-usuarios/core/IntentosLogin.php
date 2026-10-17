<?php

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/PoliticaBloqueo.php";

class IntentosLogin
{
    public const RETENCION_DIAS = 30;

    public static function esperaPara(string $email): int
    {
        $sql = "SELECT UNIX_TIMESTAMP(fecha_hora) AS instante, UNIX_TIMESTAMP() AS ahora
                FROM intento_login
                WHERE email = :email
                  AND exitoso = 0
                  AND fecha_hora > NOW() - INTERVAL " . PoliticaBloqueo::VENTANA_SEGUNDOS . " SECOND
                  AND fecha_hora > COALESCE(
                        (SELECT MAX(fecha_hora) FROM intento_login
                         WHERE email = :email_exitoso AND exitoso = 1),
                        '1970-01-01 00:00:00')
                ORDER BY fecha_hora DESC
                LIMIT " . PoliticaBloqueo::MAXIMO_FALLOS;

        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute([":email" => $email, ":email_exitoso" => $email]);
        $filas = $consulta->fetchAll();

        if ($filas === []) {
            return 0;
        }

        return PoliticaBloqueo::segundosDeEspera(
            array_map(fn($fila) => (int) $fila["instante"], $filas),
            (int) $filas[0]["ahora"]
        );
    }

    public static function registrar(string $email, bool $exitoso): void
    {
        $conexion = Database::conexion();

        $alta = $conexion->prepare(
            "INSERT INTO intento_login (email, ip, exitoso) VALUES (:email, :ip, :exitoso)"
        );
        $alta->execute([
            ":email"   => $email,
            ":ip"      => substr($_SERVER["REMOTE_ADDR"] ?? "desconocida", 0, 45),
            ":exitoso" => $exitoso ? 1 : 0,
        ]);

        $conexion->exec(
            "DELETE FROM intento_login WHERE fecha_hora < NOW() - INTERVAL " . self::RETENCION_DIAS . " DAY"
        );
    }
}
