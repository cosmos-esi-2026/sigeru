<?php
// ============================================================================
//  SiGeRU 2026 - Modelo de maquinaria de los centros de acopio
//
//  QUE ES: el inventario del equipamiento fijo de los vertederos y centros de
//  acopio -compactadoras, palas cargadoras, cintas de clasificacion-, que es lo
//  que la letra llama "maquinaria basica" en el CRUD de la 2da entrega.
//
//  ALCANCE: registro de inventario (que hay, de que tipo, donde y en que
//  estado). La planificacion de mantenimiento preventivo es nivel avanzado de
//  la letra y quedo fuera de alcance a proposito; el motivo esta en
//  Adenda_Alcance_Maquinaria_SiGeRU.pdf.
//
//  CONTRATO CON EL FRONTEND: igual que Contenedor, la clave foranea viaja como
//  id y ademas como NOMBRE del centro, para que la pantalla no tenga que hacer
//  un segundo pedido solo para mostrar donde esta cada maquina.
//
//  Todas las consultas son preparadas. Nunca se concatena un valor en el SQL.
// ============================================================================

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

    /**
     * Resuelve el centro de acopio a partir del id o del nombre. Devuelve null
     * si no existe: a diferencia de la zona de un contenedor, aca NO hay valor
     * por omision. Una maquina sin centro no tiene sentido -es lo que la
     * distingue de un camion, que se mueve- y la columna es NOT NULL.
     */
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

    /** Modifica solo los campos que vengan; lo que no se manda queda igual. */
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

    /**
     * Baja LOGICA: la maquina pasa a "fuera_servicio", igual criterio y hasta
     * el mismo valor que usa Camion. No se borra porque el inventario de un
     * organismo publico es patrimonio: una maquina de baja sigue teniendo que
     * poder listarse.
     */
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

    /** Cuantas maquinas tiene un centro. Lo usa la baja de centros de acopio. */
    public static function cuantasEnCentro(int $idCentro): int
    {
        $sql = "SELECT COUNT(*) FROM maquinaria WHERE id_centro = :id";
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute([":id" => $idCentro]);

        return (int) $consulta->fetchColumn();
    }
}
