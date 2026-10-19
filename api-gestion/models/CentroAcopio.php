<?php

require_once __DIR__ . "/../config/database.php";

class CentroAcopio
{
    public const ESTADOS = ["activo", "inactivo"];

    private const CAMPOS = "id_centro, nombre, direccion, latitud, longitud, capacidad, estado";

    // $idCentro: solo ese centro (el Operario); null devuelve todos.
    public static function listar(?int $idCentro = null): array
    {
        $sql = "SELECT " . self::CAMPOS . " FROM centro_acopio" . ($idCentro === null ? "" : " WHERE id_centro = :centro")
             . " ORDER BY id_centro";
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute($idCentro === null ? [] : [":centro" => $idCentro]);
        return $consulta->fetchAll();
    }

    public static function obtener(int $id): ?array
    {
        $sql = "SELECT " . self::CAMPOS . " FROM centro_acopio WHERE id_centro = :id";
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute([":id" => $id]);

        $centro = $consulta->fetch();
        return $centro === false ? null : $centro;
    }

    public static function existeNombre(string $nombre): bool
    {
        $sql = "SELECT 1 FROM centro_acopio WHERE nombre = :nombre";
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute([":nombre" => $nombre]);

        return $consulta->fetchColumn() !== false;
    }

    public static function crear(array $datos): array
    {
        $sql = "INSERT INTO centro_acopio (nombre, direccion, latitud, longitud, capacidad, estado)
                VALUES (:nombre, :direccion, :latitud, :longitud, :capacidad, :estado)";

        $conexion = Database::conexion();
        $consulta = $conexion->prepare($sql);
        $consulta->execute([
            ":nombre"    => $datos["nombre"]    ?? "",
            ":direccion" => ($datos["direccion"] ?? "") === "" ? null : $datos["direccion"],
            ":latitud"   => ($datos["latitud"]   ?? "") === "" ? null : $datos["latitud"],
            ":longitud"  => ($datos["longitud"]  ?? "") === "" ? null : $datos["longitud"],
            ":capacidad" => ($datos["capacidad"] ?? "") === "" ? null : (int) $datos["capacidad"],
            ":estado"    => in_array($datos["estado"] ?? "", self::ESTADOS, true)
                                ? $datos["estado"] : "activo",
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

        foreach (["nombre", "direccion", "latitud", "longitud", "capacidad"] as $campo) {
            if (array_key_exists($campo, $datos)) {
                $partes[] = "$campo = :$campo";
                $valores[":$campo"] = $datos[$campo] === "" ? null : $datos[$campo];
            }
        }
        if (isset($datos["estado"]) && in_array($datos["estado"], self::ESTADOS, true)) {
            $partes[] = "estado = :estado";
            $valores[":estado"] = $datos["estado"];
        }

        if ($partes === []) {
            return self::obtener($id);
        }

        $sql = "UPDATE centro_acopio SET " . implode(", ", $partes) . " WHERE id_centro = :id";
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute($valores);

        return self::obtener($id);
    }

    public static function darDeBaja(int $id): ?array
    {
        if (self::obtener($id) === null) {
            return null;
        }

        $sql = "UPDATE centro_acopio SET estado = 'inactivo' WHERE id_centro = :id";
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute([":id" => $id]);

        return self::obtener($id);
    }
}
