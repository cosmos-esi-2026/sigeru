<?php
// ============================================================================
//  SiGeRU 2026 - Modelo de camiones
//
//  PERSISTENCIA REAL (2da entrega): los datos viven en MySQL.
//  Mismo contrato que la version en memoria; consultas siempre preparadas.
// ============================================================================

require_once __DIR__ . "/../config/database.php";

class Camion
{
    public const ESTADOS = ["operativo", "en_ruta", "mantenimiento", "fuera_servicio"];

    private const CAMPOS = "id_camion, matricula, marca, modelo, capacidad, estado";

    public static function listar(): array
    {
        $sql = "SELECT " . self::CAMPOS . " FROM camion ORDER BY id_camion";
        return Database::conexion()->query($sql)->fetchAll();
    }

    public static function obtener(int $id): ?array
    {
        $sql = "SELECT " . self::CAMPOS . " FROM camion WHERE id_camion = :id";
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute([":id" => $id]);

        $camion = $consulta->fetch();
        return $camion === false ? null : $camion;
    }

    public static function existeMatricula(string $matricula): bool
    {
        $sql = "SELECT 1 FROM camion WHERE matricula = :matricula";
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute([":matricula" => $matricula]);

        return $consulta->fetchColumn() !== false;
    }

    public static function crear(array $datos): array
    {
        $estado = in_array($datos["estado"] ?? "", self::ESTADOS, true)
            ? $datos["estado"]
            : "operativo";

        $sql = "INSERT INTO camion (matricula, marca, modelo, capacidad, estado)
                VALUES (:matricula, :marca, :modelo, :capacidad, :estado)";

        $conexion = Database::conexion();
        $consulta = $conexion->prepare($sql);
        $consulta->execute([
            ":matricula" => $datos["matricula"] ?? "",
            ":marca"     => ($datos["marca"]  ?? "") === "" ? null : $datos["marca"],
            ":modelo"    => ($datos["modelo"] ?? "") === "" ? null : $datos["modelo"],
            ":capacidad" => ($datos["capacidad"] ?? "") === "" ? null : (int) $datos["capacidad"],
            ":estado"    => $estado,
        ]);

        return self::obtener((int) $conexion->lastInsertId());
    }

    /** Modifica solo los campos que vengan; lo que no se manda queda igual. */
    public static function actualizar(int $id, array $datos): ?array
    {
        if (self::obtener($id) === null) {
            return null;
        }

        $partes = [];
        $valores = [":id" => $id];

        foreach (["matricula", "marca", "modelo", "capacidad"] as $campo) {
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

        $sql = "UPDATE camion SET " . implode(", ", $partes) . " WHERE id_camion = :id";
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute($valores);

        return self::obtener($id);
    }

    /**
     * Baja LOGICA: el camion pasa a "fuera_servicio". No se borra porque las
     * recolecciones ya hechas lo referencian: borrarlo dejaria huerfano el
     * historial del servicio.
     */
    public static function darDeBaja(int $id): ?array
    {
        if (self::obtener($id) === null) {
            return null;
        }

        $sql = "UPDATE camion SET estado = 'fuera_servicio' WHERE id_camion = :id";
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute([":id" => $id]);

        return self::obtener($id);
    }
}
