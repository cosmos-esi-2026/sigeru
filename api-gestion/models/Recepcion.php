<?php

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../core/ErrorDeNegocio.php";
require_once __DIR__ . "/Stock.php";
require_once __DIR__ . "/CentroStock.php";

// Recepcion en el centro de acopio de lo que trae una recoleccion (extras, parte 12).
// Una recoleccion se recibe una sola vez; cada tipo de residuo recibido es un movimiento de entrada.
class Recepcion
{
    public const ESTADOS = ["pendiente", "recibida", "todas"];

    private const CAMPOS = "r.id_recoleccion, r.estado, r.fecha_hora_inicio, r.fecha_hora_fin, r.observaciones,
                            ru.nombre AS ruta, z.nombre AS zona, c.matricula, q.nombre AS cuadrilla,
                            r.id_centro, ce.nombre AS centro,
                            (SELECT COUNT(*) FROM vaciado v WHERE v.id_recoleccion = r.id_recoleccion) AS vaciados,
                            (SELECT COALESCE(SUM(v.peso_kg), 0) FROM vaciado v WHERE v.id_recoleccion = r.id_recoleccion) AS peso_esperado_kg,
                            rc.id_recepcion, rc.fecha_hora AS fecha_recepcion, rc.observacion AS observacion_recepcion,
                            CONCAT(ur.nombre, ' ', ur.apellido) AS recibida_por,
                            (SELECT COALESCE(SUM(m.kg), 0) FROM movimiento_stock m
                              WHERE m.id_recoleccion = r.id_recoleccion AND m.motivo = 'recepcion') AS peso_recibido_kg";

    private const DESDE = "FROM recoleccion r
                           JOIN ruta ru ON ru.id_ruta = r.id_ruta
                           JOIN zona z ON z.id_zona = ru.id_zona
                           JOIN camion c ON c.id_camion = r.id_camion
                           JOIN cuadrilla q ON q.id_cuadrilla = r.id_cuadrilla
                           JOIN centro_acopio ce ON ce.id_centro = r.id_centro
                           LEFT JOIN recepcion rc ON rc.id_recoleccion = r.id_recoleccion
                           LEFT JOIN usuario ur ON ur.id_usuario = rc.id_usuario";

    private static function dar(array $f): array
    {
        $f["id_recoleccion"] = (int) $f["id_recoleccion"];
        $f["numero"] = Stock::numeroDeRecoleccion($f["id_recoleccion"]);
        $f["id_centro"] = (int) $f["id_centro"];
        $f["vaciados"] = (int) $f["vaciados"];
        $f["peso_esperado_kg"] = round((float) $f["peso_esperado_kg"], 2);
        $f["peso_recibido_kg"] = round((float) $f["peso_recibido_kg"], 2);
        $f["recibida"] = $f["id_recepcion"] !== null;
        unset($f["id_recepcion"]);
        return $f;
    }

    // $idCentro: null = todos los centros (solo el Administrador).
    public static function listar(?int $idCentro, string $estado): array
    {
        $donde = ["r.estado IN ('en_curso', 'finalizada')"];
        $valores = [];
        if ($idCentro !== null) {
            $donde[] = "r.id_centro = :centro";
            $valores[":centro"] = $idCentro;
        }
        if ($estado === "pendiente") {
            $donde[] = "rc.id_recepcion IS NULL";
        } elseif ($estado === "recibida") {
            $donde[] = "rc.id_recepcion IS NOT NULL";
        }
        $sql = "SELECT " . self::CAMPOS . " " . self::DESDE . " WHERE " . implode(" AND ", $donde) . "
                ORDER BY (rc.id_recepcion IS NOT NULL), r.fecha_hora_inicio DESC, r.id_recoleccion DESC";
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute($valores);
        return array_map([self::class, "dar"], $consulta->fetchAll());
    }

    public static function pendientesDelCentro(int $idCentro): int
    {
        $consulta = Database::conexion()->prepare("SELECT COUNT(*) FROM recoleccion r
            LEFT JOIN recepcion rc ON rc.id_recoleccion = r.id_recoleccion
            WHERE r.id_centro = :centro AND r.estado IN ('en_curso', 'finalizada') AND rc.id_recepcion IS NULL");
        $consulta->execute([":centro" => $idCentro]);
        return (int) $consulta->fetchColumn();
    }

    public static function obtener(int $idRecoleccion): ?array
    {
        $conexion = Database::conexion();
        $consulta = $conexion->prepare("SELECT " . self::CAMPOS . " " . self::DESDE . "
                                        WHERE r.id_recoleccion = :id AND r.estado IN ('en_curso', 'finalizada')");
        $consulta->execute([":id" => $idRecoleccion]);
        $fila = $consulta->fetch();
        if ($fila === false) {
            return null;
        }
        $detalle = self::dar($fila);

        $esperado = $conexion->prepare("SELECT t.id_tipo_residuo, t.nombre AS tipo, SUM(v.peso_kg) AS kg
            FROM vaciado v JOIN contenedor ct ON ct.id_contenedor = v.id_contenedor
            JOIN tipo_residuo t ON t.id_tipo_residuo = ct.id_tipo_residuo
            WHERE v.id_recoleccion = :id GROUP BY t.id_tipo_residuo, t.nombre ORDER BY t.nombre");
        $esperado->execute([":id" => $idRecoleccion]);
        $detalle["esperado_por_tipo"] = array_map(fn($f) => [
            "id_tipo_residuo" => (int) $f["id_tipo_residuo"], "tipo" => $f["tipo"], "kg" => round((float) $f["kg"], 2),
        ], $esperado->fetchAll());

        $recibido = $conexion->prepare("SELECT t.id_tipo_residuo, t.nombre AS tipo, m.kg, m.id_movimiento,
                (SELECT COUNT(*) FROM movimiento_stock c WHERE c.id_corregido = m.id_movimiento) AS corregido
            FROM movimiento_stock m JOIN tipo_residuo t ON t.id_tipo_residuo = m.id_tipo_residuo
            WHERE m.id_recoleccion = :id AND m.motivo = 'recepcion' ORDER BY t.nombre");
        $recibido->execute([":id" => $idRecoleccion]);
        $detalle["recibido_por_tipo"] = array_map(fn($f) => [
            "id_movimiento" => (int) $f["id_movimiento"], "id_tipo_residuo" => (int) $f["id_tipo_residuo"],
            "tipo" => $f["tipo"], "kg" => round((float) $f["kg"], 2), "corregido" => (int) $f["corregido"] > 0,
        ], $recibido->fetchAll());
        return $detalle;
    }

    public static function tiposValidos(): array
    {
        return array_map("intval", Database::conexion()->query("SELECT id_tipo_residuo FROM tipo_residuo")->fetchAll(PDO::FETCH_COLUMN));
    }

    // $centroPermitido: el centro del Operario (null para el Administrador, que puede recibir en cualquiera).
    public static function confirmar(int $idRecoleccion, array $lineas, ?string $observacion, int $idUsuario, ?int $centroPermitido): array
    {
        $conexion = Database::conexion();
        $conexion->beginTransaction();
        try {
            $consulta = $conexion->prepare("SELECT r.id_centro, r.estado, ce.estado AS estado_centro
                FROM recoleccion r LEFT JOIN centro_acopio ce ON ce.id_centro = r.id_centro
                WHERE r.id_recoleccion = :id FOR UPDATE");
            $consulta->execute([":id" => $idRecoleccion]);
            $r = $consulta->fetch();
            if ($r === false || ($centroPermitido !== null && (int) $r["id_centro"] !== $centroPermitido)) {
                throw new ErrorDeNegocio("Recolección no encontrada", 404);
            }
            if ($r["id_centro"] === null) {
                throw new ErrorDeNegocio("Esta recolección no tiene un centro de destino", 409);
            }
            if (!in_array($r["estado"], ["en_curso", "finalizada"], true)) {
                throw new ErrorDeNegocio("La recolección todavía no salió o fue cancelada: no hay nada para recibir", 409);
            }
            if ($r["estado_centro"] !== "activo") {
                throw new ErrorDeNegocio("El centro de destino está inactivo", 409);
            }
            $ya = $conexion->prepare("SELECT COUNT(*) FROM recepcion WHERE id_recoleccion = :id");
            $ya->execute([":id" => $idRecoleccion]);
            if ((int) $ya->fetchColumn() > 0) {
                throw new ErrorDeNegocio("Esta recolección ya fue recibida", 409);
            }

            $idCentro = (int) $r["id_centro"];
            $conexion->prepare("INSERT INTO recepcion (id_recoleccion, id_centro, id_usuario, observacion)
                                VALUES (:r, :c, :u, :o)")->execute([
                ":r" => $idRecoleccion, ":c" => $idCentro, ":u" => $idUsuario,
                ":o" => $observacion === null || $observacion === "" ? null : $observacion,
            ]);
            foreach ($lineas as $linea) {
                CentroStock::insertar($conexion, $idCentro, $linea["id_tipo_residuo"], "entrada", "recepcion", $linea["kg"],
                                      $idRecoleccion, null, $idUsuario, $observacion);
            }
            $conexion->commit();
        } catch (Throwable $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            throw $e;
        }
        return self::obtener($idRecoleccion);
    }
}
