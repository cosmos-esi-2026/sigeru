<?php

require_once __DIR__ . "/Response.php";
require_once __DIR__ . "/../config/database.php";

// Freno por direccion IP a los pedidos publicos (OWASP A04). Cuenta todos los intentos, buenos o malos: asi tambien
// frena a quien prueba datos para ver cuales existen. Si la base falla, el pedido sigue (el freno no tira el sistema).
class LimitePeticion
{
    // true cuando ya hubo $maximo pedidos o mas en la ventana.
    public static function superado(int $cuenta, int $maximo): bool
    {
        return $cuenta >= $maximo;
    }

    public static function clave(string $accion, string $ip): string
    {
        return substr($accion . ":" . $ip, 0, 80);
    }

    public static function exigir(string $accion, int $maximo, int $segundos): void
    {
        $clave = self::clave($accion, (string) ($_SERVER["REMOTE_ADDR"] ?? "?"));
        try {
            $db = Database::conexion();
            $consulta = $db->prepare("SELECT COUNT(*) FROM limite_peticion WHERE clave = :clave
                                      AND fecha_hora > DATE_SUB(NOW(), INTERVAL :segundos SECOND)");
            $consulta->bindValue(":clave", $clave);
            $consulta->bindValue(":segundos", $segundos, PDO::PARAM_INT);
            $consulta->execute();
            if (self::superado((int) $consulta->fetchColumn(), $maximo)) {
                header("Retry-After: " . $segundos);
                Response::error("Hiciste demasiados pedidos seguidos. Esperá un rato y probá de nuevo.", 429);
            }
            $db->prepare("INSERT INTO limite_peticion (clave) VALUES (:clave)")->execute([":clave" => $clave]);
            if (random_int(1, 20) === 1) {
                $db->exec("DELETE FROM limite_peticion WHERE fecha_hora < DATE_SUB(NOW(), INTERVAL 1 DAY)");
            }
        } catch (PDOException $e) {
            error_log("SiGeRU limite de pedidos: " . $e->getMessage());
        }
    }
}
