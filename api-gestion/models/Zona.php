<?php
// ============================================================================
//  SiGeRU 2026 - Modelo de zonas
//  La zona agrupa contenedores y rutas, y se abastece de un centro de acopio.
//  El formulario de contenedores la necesita para el desplegable.
// ============================================================================

require_once __DIR__ . "/../config/database.php";

class Zona
{
    public static function listar(): array
    {
        $sql = "SELECT z.id_zona, z.nombre, z.descripcion,
                       z.id_centro, c.nombre AS centro,
                       (SELECT COUNT(*) FROM contenedor ct WHERE ct.id_zona = z.id_zona)
                           AS contenedores
                FROM zona z
                LEFT JOIN centro_acopio c ON c.id_centro = z.id_centro
                ORDER BY z.nombre";

        return Database::conexion()->query($sql)->fetchAll();
    }

    public static function obtener(int $id): ?array
    {
        $sql = "SELECT id_zona, nombre, descripcion, id_centro FROM zona WHERE id_zona = :id";
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute([":id" => $id]);

        $zona = $consulta->fetch();
        return $zona === false ? null : $zona;
    }

    public static function existeNombre(string $nombre): bool
    {
        $sql = "SELECT 1 FROM zona WHERE nombre = :nombre";
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute([":nombre" => $nombre]);

        return $consulta->fetchColumn() !== false;
    }

    public static function crear(array $datos): array
    {
        $sql = "INSERT INTO zona (nombre, descripcion, id_centro)
                VALUES (:nombre, :descripcion, :id_centro)";

        $conexion = Database::conexion();
        $consulta = $conexion->prepare($sql);
        $consulta->execute([
            ":nombre"      => $datos["nombre"] ?? "",
            ":descripcion" => ($datos["descripcion"] ?? "") === "" ? null : $datos["descripcion"],
            ":id_centro"   => empty($datos["id_centro"]) ? null : (int) $datos["id_centro"],
        ]);

        return self::obtener((int) $conexion->lastInsertId());
    }
}
