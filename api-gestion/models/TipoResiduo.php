<?php

require_once __DIR__ . "/../config/database.php";

class TipoResiduo
{
    private const CAMPOS = "id_tipo_residuo, nombre, descripcion, reciclable";

    public static function listar(): array
    {
        $sql = "SELECT " . self::CAMPOS . " FROM tipo_residuo ORDER BY nombre";
        $tipos = Database::conexion()->query($sql)->fetchAll();

        foreach ($tipos as &$t) {
            $t["reciclable"] = (bool) $t["reciclable"];
        }
        return $tipos;
    }

    public static function obtener(int $id): ?array
    {
        $sql = "SELECT " . self::CAMPOS . " FROM tipo_residuo WHERE id_tipo_residuo = :id";
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute([":id" => $id]);

        $tipo = $consulta->fetch();
        if ($tipo === false) {
            return null;
        }
        $tipo["reciclable"] = (bool) $tipo["reciclable"];
        return $tipo;
    }

    public static function existeNombre(string $nombre): bool
    {
        $sql = "SELECT 1 FROM tipo_residuo WHERE nombre = :nombre";
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute([":nombre" => $nombre]);

        return $consulta->fetchColumn() !== false;
    }

    public static function crear(array $datos): array
    {
        $sql = "INSERT INTO tipo_residuo (nombre, descripcion, reciclable)
                VALUES (:nombre, :descripcion, :reciclable)";

        $conexion = Database::conexion();
        $consulta = $conexion->prepare($sql);
        $consulta->execute([
            ":nombre"      => $datos["nombre"] ?? "",
            ":descripcion" => ($datos["descripcion"] ?? "") === "" ? null : $datos["descripcion"],
            ":reciclable"  => !empty($datos["reciclable"]) ? 1 : 0,
        ]);

        return self::obtener((int) $conexion->lastInsertId());
    }

    public static function actualizar(int $id, array $datos): ?array
    {
        if (self::obtener($id) === null) {
            return null;
        }

        $partes = [];
        $valores = [":id" => $id];

        foreach (["nombre", "descripcion"] as $campo) {
            if (array_key_exists($campo, $datos)) {
                $partes[] = "$campo = :$campo";
                $valores[":$campo"] = $datos[$campo] === "" ? null : $datos[$campo];
            }
        }
        if (array_key_exists("reciclable", $datos)) {
            $partes[] = "reciclable = :reciclable";
            $valores[":reciclable"] = !empty($datos["reciclable"]) ? 1 : 0;
        }

        if ($partes === []) {
            return self::obtener($id);
        }

        $sql = "UPDATE tipo_residuo SET " . implode(", ", $partes) . " WHERE id_tipo_residuo = :id";
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute($valores);

        return self::obtener($id);
    }

    public static function contenedoresQueLoUsan(int $id): int
    {
        $sql = "SELECT COUNT(*) FROM contenedor WHERE id_tipo_residuo = :id";
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute([":id" => $id]);

        return (int) $consulta->fetchColumn();
    }

    public static function eliminar(int $id): bool
    {
        $sql = "DELETE FROM tipo_residuo WHERE id_tipo_residuo = :id";
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute([":id" => $id]);

        return $consulta->rowCount() > 0;
    }
}
