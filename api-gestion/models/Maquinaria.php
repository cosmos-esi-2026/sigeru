<?php

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../core/ErrorDeNegocio.php";

class Maquinaria
{
    public const ESTADOS = ["operativa", "mantenimiento", "fuera_servicio"];

    public const TIPOS = ["compactadora", "pala_cargadora", "cinta_clasificadora",
                          "retroexcavadora", "montacargas", "otra"];

    private const CAMPOS = "m.id_maquinaria, m.codigo, m.tipo, m.marca, m.modelo,
                            m.estado, m.id_centro, c.nombre AS centro";

    private const DESDE = "FROM maquinaria m
                           JOIN centro_acopio c ON c.id_centro = m.id_centro";

    // $idCentro: solo las de ese centro (el Operario); null devuelve todas.
    public static function listar(?int $idCentro = null): array
    {
        $sql = "SELECT " . self::CAMPOS . " " . self::DESDE . ($idCentro === null ? "" : " WHERE m.id_centro = :centro")
             . " ORDER BY m.id_maquinaria";
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute($idCentro === null ? [] : [":centro" => $idCentro]);
        return $consulta->fetchAll();
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

    public static function crear(array $datos, ?int $idUsuario = null): array
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

        $nueva = self::obtener((int) $conexion->lastInsertId());
        if ($idUsuario !== null) {
            self::registrarEstado($conexion, (int) $nueva["id_maquinaria"], $nueva["estado"], "Alta en el sistema", $idUsuario);
        }
        return $nueva;
    }

    public static function actualizar(int $id, array $datos, ?int $idUsuario = null): ?array
    {
        $antes = self::obtener($id);
        if ($antes === null) {
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
        $conexion = Database::conexion();
        $consulta = $conexion->prepare($sql);
        $consulta->execute($valores);

        $despues = self::obtener($id);
        if ($idUsuario !== null && $despues["estado"] !== $antes["estado"]) {
            self::registrarEstado($conexion, $id, $despues["estado"], "Cambio desde la edicion de la maquina", $idUsuario);
        }
        return $despues;
    }

    public static function darDeBaja(int $id, ?int $idUsuario = null): ?array
    {
        if (self::obtener($id) === null) {
            return null;
        }

        $sql = "UPDATE maquinaria SET estado = 'fuera_servicio' WHERE id_maquinaria = :id";
        $conexion = Database::conexion();
        $consulta = $conexion->prepare($sql);
        $consulta->execute([":id" => $id]);
        if ($idUsuario !== null) {
            self::registrarEstado($conexion, $id, "fuera_servicio", "Dada de baja", $idUsuario);
        }

        return self::obtener($id);
    }

    public static function registrarEstado(PDO $conexion, int $id, string $estado, ?string $observacion, int $idUsuario): void
    {
        $conexion->prepare("INSERT INTO historial_maquinaria (id_maquinaria, estado, observacion, id_usuario)
                            VALUES (:m, :e, :o, :u)")
                 ->execute([":m" => $id, ":e" => $estado, ":o" => $observacion, ":u" => $idUsuario]);
    }

    // Cambia el estado y lo anota en el historial, todo junto. Una nota es obligatoria.
    public static function cambiarEstado(int $id, string $estado, string $observacion, int $idUsuario): array
    {
        $conexion = Database::conexion();
        $conexion->beginTransaction();
        try {
            $consulta = $conexion->prepare("SELECT estado FROM maquinaria WHERE id_maquinaria = :id FOR UPDATE");
            $consulta->execute([":id" => $id]);
            $actual = $consulta->fetchColumn();
            if ($actual === false) {
                throw new ErrorDeNegocio("Maquinaria no encontrada", 404);
            }
            if ($actual === $estado) {
                throw new ErrorDeNegocio("La máquina ya está en ese estado", 409);
            }
            $conexion->prepare("UPDATE maquinaria SET estado = :e WHERE id_maquinaria = :id")->execute([":e" => $estado, ":id" => $id]);
            self::registrarEstado($conexion, $id, $estado, $observacion, $idUsuario);
            $conexion->commit();
        } catch (Throwable $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            throw $e;
        }
        return self::obtener($id);
    }

    public static function historial(int $id): array
    {
        $consulta = Database::conexion()->prepare("SELECT h.id_registro, h.fecha_hora, h.estado, h.observacion,
                CONCAT(u.nombre, ' ', u.apellido) AS usuario, r.nombre AS rol
            FROM historial_maquinaria h JOIN usuario u ON u.id_usuario = h.id_usuario JOIN rol r ON r.id_rol = u.id_rol
            WHERE h.id_maquinaria = :id ORDER BY h.fecha_hora DESC, h.id_registro DESC");
        $consulta->execute([":id" => $id]);
        return $consulta->fetchAll();
    }

    public static function enMantenimientoEnCentro(int $idCentro): int
    {
        $consulta = Database::conexion()->prepare("SELECT COUNT(*) FROM maquinaria WHERE id_centro = :c AND estado = 'mantenimiento'");
        $consulta->execute([":c" => $idCentro]);
        return (int) $consulta->fetchColumn();
    }

    public static function cuantasEnCentro(int $idCentro): int
    {
        $sql = "SELECT COUNT(*) FROM maquinaria WHERE id_centro = :id";
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute([":id" => $idCentro]);

        return (int) $consulta->fetchColumn();
    }
}
