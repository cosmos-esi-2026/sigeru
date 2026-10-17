<?php

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/Usuario.php";

class EnlaceClave
{
    public static function solicitadoRecientemente(int $idUsuario): bool
    {
        $consulta = Database::conexion()->prepare("SELECT 1 FROM enlace_clave
            WHERE id_usuario = :id AND vence_en > DATE_ADD(NOW(), INTERVAL 55 MINUTE)
            LIMIT 1");
        $consulta->execute([":id" => $idUsuario]);
        return $consulta->fetchColumn() !== false;
    }

    public static function crear(int $idUsuario): string
    {
        $token = bin2hex(random_bytes(32));
        $db = Database::conexion();
        $db->prepare("DELETE FROM enlace_clave WHERE id_usuario = :id")
            ->execute([":id" => $idUsuario]);
        $db->prepare("INSERT INTO enlace_clave (id_usuario, token_hash, vence_en)
                      VALUES (:id, :hash, DATE_ADD(NOW(), INTERVAL 1 HOUR))")
            ->execute([":id" => $idUsuario, ":hash" => hash("sha256", $token)]);
        return $token;
    }

    public static function consumir(string $token, string $clave): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/D', $token)) {
            return null;
        }
        $db = Database::conexion();
        $db->beginTransaction();
        try {
            $consulta = $db->prepare("SELECT e.id_usuario FROM enlace_clave e
                JOIN usuario u ON u.id_usuario = e.id_usuario
                WHERE e.token_hash = :hash AND e.vence_en > NOW()
                  AND u.estado IN ('activo', 'pendiente') FOR UPDATE");
            $consulta->execute([":hash" => hash("sha256", $token)]);
            $id = $consulta->fetchColumn();
            if ($id === false) {
                $db->rollBack();
                return null;
            }
            $db->prepare("UPDATE usuario SET password_hash = :hash, estado = 'activo'
                          WHERE id_usuario = :id")
                ->execute([":hash" => password_hash($clave, PASSWORD_DEFAULT), ":id" => $id]);
            $db->prepare("DELETE FROM enlace_clave WHERE id_usuario = :id")
                ->execute([":id" => $id]);
            $db->commit();
            return Usuario::obtener((int) $id);
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }
}
