<?php

require_once __DIR__ . "/../config/database.php";

class Maquinaria
{
    public const ESTADOS = ["operativa", "mantenimiento", "fuera_servicio"];

    public const TIPOS = ["compactadora", "pala_cargadora", "cinta_clasificadora",
                          "retroexcavadora", "montacargas", "otra"];

    private const CAMPOS = "m.id_maquinaria, m.codigo, m.tipo, m.marca, m.modelo,
                            m.estado, m.id_centro, c.nombre AS centro";

    private const DESDE = "FROM maquinaria m
                           JOIN centro_acopio c ON c.id_centro = m.id_centro";

    public static function listar(): array
    {
        $sql = "SELECT " . self::CAMPOS . " " . self::DESDE . " ORDER BY m.id_maquinaria";
        return Database::conexion()->query($sql)->fetchAll();
    }

    public static function obtener(int $id): ?array
    {
        $sql = "SELECT " . self::CAMPOS . " " . self::DESDE . " WHERE m.id_maquinaria = :id";
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute([":id" => $id]);

        $maquina = $consulta->fetch();
        return $maquina === false ? null : $maquina;
    }

    public static function existeCodigo(string $codigo): bool
    {
        $sql = "SELECT 1 FROM maquinaria WHERE codigo = :codigo";
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute([":codigo" => $codigo]);

        return $consulta->fetchColumn() !== false;
    }

    public static function idDeCentro(array $datos): ?int
    {
        if (!empty($datos["id_centro"])) {
            $sql = "SELECT id_centro FROM centro_acopio WHERE id_centro = :id";
            $consulta = Database::conexion()->prepare($sql);
            $consulta->execute([":id" => (int) $datos["id_centro"]]);
            $id = $consulta->fetchColumn();
            return $id === false ? null : (int) $id;
        }
        if (!empty($datos["centro"])) {
            $sql = "SELECT id_centro FROM centro_acopio WHERE nombre = :nombre";
            $consulta = Database::conexion()->prepare($sql);
            $consulta->execute([":nombre" => $datos["centro"]]);
            $id = $consulta->fetchColumn();
            return $id === false ? null : (int) $id;
        }
        return null;
    }

    public static function crear(array $datos): array
    {
        $sql = "INSERT INTO maquinaria (codigo, tipo, marca, modelo, estado, id_centro)
                VALUES (:codigo, :tipo, :marca, :modelo, :estado, :id_centro)";

        $conexion = Database::conexion();
        $consulta = $conexion->prepare($sql);
        $consulta->execute([
            ":codigo"    => $datos["codigo"] ?? "",
            ":tipo"      => in_array($datos["tipo"] ?? "", self::TIPOS, true)
                                ? $datos["tipo"] : "otra",
            ":marca"     => ($datos["marca"]  ?? "") === "" ? null : $datos["marca"],
            ":modelo"    => ($datos["modelo"] ?? "") === "" ? null : $datos["modelo"],
            ":estado"    => in_array($datos["estado"] ?? "", self::ESTADOS, true)
                                ? $datos["estado"] : "operativa",
            ":id_centro" => self::idDeCentro($datos),
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

        foreach (["codigo", "marca", "modelo"] as $campo) {
            if (array_key_exists($campo, $datos)) {
                $partes[] = "$campo = :$campo";
                $valores[":$campo"] = $datos[$campo] === "" ? null : $datos[$campo];
            }
        }
        if (isset($datos["tipo"]) && in_array($datos["tipo"], self::TIPOS, true)) {
            $partes[] = "tipo = :tipo";
            $valores[":tipo"] = $datos["tipo"];
        }
        if (isset($datos["estado"]) && in_array($datos["estado"], self::ESTADOS, true)) {
            $partes[] = "estado = :estado";
            $valores[":estado"] = $datos["estado"];
        }
        if (!empty($datos["id_centro"]) || !empty($datos["centro"])) {
            $idCentro = self::idDeCentro($datos);
            if ($idCentro !== null) {
                $partes[] = "id_centro = :id_centro";
                $valores[":id_centro"] = $idCentro;
            }
        }

        if ($partes === []) {
            return self::obtener($id);
        }

        $sql = "UPDATE maquinaria SET " . implode(", ", $partes) . " WHERE id_maquinaria = :id";
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute($valores);

        return self::obtener($id);
    }

    public static function darDeBaja(int $id): ?array
    {
        if (self::obtener($id) === null) {
            return null;
        }

        $sql = "UPDATE maquinaria SET estado = 'fuera_servicio' WHERE id_maquinaria = :id";
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute([":id" => $id]);

        return self::obtener($id);
    }

    public static function cuantasEnCentro(int $idCentro): int
    {
        $sql = "SELECT COUNT(*) FROM maquinaria WHERE id_centro = :id";
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute([":id" => $idCentro]);

        return (int) $consulta->fetchColumn();
    }
}
