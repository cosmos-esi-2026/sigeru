<?php

require_once __DIR__ . "/../config/database.php";

// Los avisos de una persona (extras, parte 13). Cada uno solo ve, lee y marca los suyos.
class Notificacion
{
    public const LIMITE_MAXIMO = 50;
    public const LIMITE_OMITIDO = 20;

    public static function listar(int $idUsuario, int $limite, bool $soloNoLeidas): array
    {
        $consulta = Database::conexion()->prepare(
            "SELECT id_notificacion, tipo, titulo, mensaje, enlace, leida, fecha_hora FROM notificacion
             WHERE id_usuario = :u" . ($soloNoLeidas ? " AND leida = 0" : "") . "
             ORDER BY fecha_hora DESC, id_notificacion DESC LIMIT :limite"
        );
        $consulta->bindValue(":u", $idUsuario, PDO::PARAM_INT);
        $consulta->bindValue(":limite", $limite, PDO::PARAM_INT);
        $consulta->execute();
        return array_map(function (array $f) {
            $f["id_notificacion"] = (int) $f["id_notificacion"];
            $f["leida"] = (int) $f["leida"] === 1;
            return $f;
        }, $consulta->fetchAll());
    }

    public static function contarNoLeidas(int $idUsuario): int
    {
        $consulta = Database::conexion()->prepare("SELECT COUNT(*) FROM notificacion WHERE id_usuario = :u AND leida = 0");
        $consulta->execute([":u" => $idUsuario]);
        return (int) $consulta->fetchColumn();
    }

    // false si el aviso no existe o es de otra persona (para quien pregunta es lo mismo).
    public static function marcarLeida(int $id, int $idUsuario): bool
    {
        $consulta = Database::conexion()->prepare("SELECT COUNT(*) FROM notificacion WHERE id_notificacion = :id AND id_usuario = :u");
        $consulta->execute([":id" => $id, ":u" => $idUsuario]);
        if ((int) $consulta->fetchColumn() === 0) {
            return false;
        }
        Database::conexion()->prepare("UPDATE notificacion SET leida = 1 WHERE id_notificacion = :id AND id_usuario = :u")
            ->execute([":id" => $id, ":u" => $idUsuario]);
        return true;
    }

    public static function marcarTodas(int $idUsuario): int
    {
        $consulta = Database::conexion()->prepare("UPDATE notificacion SET leida = 1 WHERE id_usuario = :u AND leida = 0");
        $consulta->execute([":u" => $idUsuario]);
        return $consulta->rowCount();
    }

    public static function avisosPorCorreo(int $idUsuario): bool
    {
        $consulta = Database::conexion()->prepare("SELECT avisos_por_correo FROM usuario WHERE id_usuario = :u");
        $consulta->execute([":u" => $idUsuario]);
        return (int) $consulta->fetchColumn() === 1;
    }

    public static function guardarAvisosPorCorreo(int $idUsuario, bool $activo): void
    {
        Database::conexion()->prepare("UPDATE usuario SET avisos_por_correo = :a WHERE id_usuario = :u")
            ->execute([":a" => $activo ? 1 : 0, ":u" => $idUsuario]);
    }
}
