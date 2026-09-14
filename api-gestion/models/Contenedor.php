<?php

require_once __DIR__ . "/../config/database.php";

class Contenedor
{
    public const ESTADOS = ["disponible", "lleno", "mantenimiento", "retirado"];

    private const CAMPOS = "c.id_contenedor, c.codigo, c.direccion, c.latitud,
                            c.longitud, c.capacidad, c.estado,
                            c.id_zona, z.nombre AS zona,
                            c.id_tipo_residuo, t.nombre AS tipo_residuo";

    private const DESDE = "FROM contenedor c
                           JOIN zona z ON z.id_zona = c.id_zona
                           JOIN tipo_residuo t ON t.id_tipo_residuo = c.id_tipo_residuo";

    public static function listar(): array
    {
        $sql = "SELECT " . self::CAMPOS . " " . self::DESDE . " ORDER BY c.id_contenedor";
        return Database::conexion()->query($sql)->fetchAll();
    }

    public static function obtener(int $id): ?array
    {
        $sql = "SELECT " . self::CAMPOS . " " . self::DESDE . " WHERE c.id_contenedor = :id";
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute([":id" => $id]);

        $contenedor = $consulta->fetch();
        return $contenedor === false ? null : $contenedor;
    }

    public static function existeCodigo(string $codigo): bool
    {
        $sql = "SELECT 1 FROM contenedor WHERE codigo = :codigo";
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute([":codigo" => $codigo]);

        return $consulta->fetchColumn() !== false;
    }

    private static function idDeTipo(?string $nombre): ?int
    {
        if ($nombre === null || $nombre === "") {
            return null;
        }
        $sql = "SELECT id_tipo_residuo FROM tipo_residuo WHERE nombre = :nombre";
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute([":nombre" => $nombre]);

        $id = $consulta->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    private static function resolverZona(array $datos): ?int
    {
        if (!empty($datos["id_zona"])) {
            return (int) $datos["id_zona"];
        }
        if (!empty($datos["zona"])) {
            $sql = "SELECT id_zona FROM zona WHERE nombre = :nombre";
            $consulta = Database::conexion()->prepare($sql);
            $consulta->execute([":nombre" => $datos["zona"]]);
            $id = $consulta->fetchColumn();
            if ($id !== false) {
                return (int) $id;
            }
        }
        $primera = Database::conexion()->query("SELECT MIN(id_zona) FROM zona")->fetchColumn();
        return $primera === false ? null : (int) $primera;
    }

    public static function crear(array $datos): array
    {
        $estado = in_array($datos["estado"] ?? "", self::ESTADOS, true)
            ? $datos["estado"]
            : "disponible";

        $conexion = Database::conexion();
        $conexion->beginTransaction();

        try {
            $sql = "INSERT INTO contenedor
                       (codigo, direccion, latitud, longitud, capacidad, estado,
                        id_zona, id_tipo_residuo)
                    VALUES
                       (:codigo, :direccion, :latitud, :longitud, :capacidad, :estado,
                        :id_zona, :id_tipo)";

            $consulta = $conexion->prepare($sql);
            $consulta->execute([
                ":codigo"    => $datos["codigo"]    ?? "",
                ":direccion" => $datos["direccion"] ?? "",
                ":latitud"   => $datos["latitud"]   ?? null,
                ":longitud"  => $datos["longitud"]  ?? null,
                ":capacidad" => ($datos["capacidad"] ?? "") === "" ? null : (int) $datos["capacidad"],
                ":estado"    => $estado,
                ":id_zona"   => self::resolverZona($datos),
                ":id_tipo"   => self::idDeTipo($datos["tipo_residuo"] ?? null)
                                ?? self::idDeTipo("Mixto"),
            ]);

            $id = (int) $conexion->lastInsertId();
            self::registrarEstado($id, $estado, "Alta del contenedor");

            $conexion->commit();
            return self::obtener($id);
        } catch (PDOException $e) {
            $conexion->rollBack();
            throw $e;
        }
    }

    public static function actualizar(int $id, array $datos): ?array
    {
        $anterior = self::obtener($id);
        if ($anterior === null) {
            return null;
        }

        $partes = [];
        $valores = [":id" => $id];

        foreach (["codigo", "direccion", "latitud", "longitud", "capacidad"] as $campo) {
            if (array_key_exists($campo, $datos)) {
                $partes[] = "$campo = :$campo";
                $valores[":$campo"] = $datos[$campo] === "" ? null : $datos[$campo];
            }
        }
        if (isset($datos["estado"]) && in_array($datos["estado"], self::ESTADOS, true)) {
            $partes[] = "estado = :estado";
            $valores[":estado"] = $datos["estado"];
        }
        if (isset($datos["tipo_residuo"])) {
            $idTipo = self::idDeTipo($datos["tipo_residuo"]);
            if ($idTipo !== null) {
                $partes[] = "id_tipo_residuo = :id_tipo";
                $valores[":id_tipo"] = $idTipo;
            }
        }
        if (!empty($datos["id_zona"])) {
            $partes[] = "id_zona = :id_zona";
            $valores[":id_zona"] = (int) $datos["id_zona"];
        }

        if ($partes !== []) {
            $sql = "UPDATE contenedor SET " . implode(", ", $partes) . " WHERE id_contenedor = :id";
            $consulta = Database::conexion()->prepare($sql);
            $consulta->execute($valores);
        }

        if (isset($valores[":estado"]) && $valores[":estado"] !== $anterior["estado"]) {
            self::registrarEstado($id, $valores[":estado"], "Cambio de estado desde el panel");
        }

        return self::obtener($id);
    }

    public static function darDeBaja(int $id, string $motivo = "Retirado desde el panel"): ?array
    {
        if (self::obtener($id) === null) {
            return null;
        }

        $sql = "UPDATE contenedor SET estado = 'retirado' WHERE id_contenedor = :id";
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute([":id" => $id]);

        self::registrarEstado($id, "retirado", $motivo);
        return self::obtener($id);
    }

    public static function registrarEstado(int $id, string $estado, ?string $observacion = null): void
    {
        $sql = "INSERT INTO estado_contenedor (id_contenedor, estado, observacion)
                VALUES (:id, :estado, :observacion)";

        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute([
            ":id"          => $id,
            ":estado"      => $estado,
            ":observacion" => $observacion,
        ]);
    }

    public static function historial(int $id): array
    {
        $sql = "SELECT id_registro, fecha_hora, estado, observacion
                FROM estado_contenedor
                WHERE id_contenedor = :id
                ORDER BY fecha_hora DESC, id_registro DESC";

        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute([":id" => $id]);

        return $consulta->fetchAll();
    }
}
