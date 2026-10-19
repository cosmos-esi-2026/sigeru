<?php

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../core/ErrorDeNegocio.php";
require_once __DIR__ . "/Stock.php";

// Consultas y escrituras del stock de un centro de acopio (extras, parte 12).
class CentroStock
{
    private const SUMA = "SUM(CASE m.sentido WHEN 'entrada' THEN m.kg ELSE -m.kg END)";

    // [id_centro => kg] de todos los centros (para la lista de centros).
    public static function totales(): array
    {
        $sql = "SELECT m.id_centro, " . self::SUMA . " FROM movimiento_stock m GROUP BY m.id_centro";
        $filas = Database::conexion()->query($sql)->fetchAll(PDO::FETCH_KEY_PAIR);
        return array_map(fn($kg) => round((float) $kg, 2), $filas);
    }

    public static function saldos(int $idCentro): array
    {
        $sql = "SELECT t.id_tipo_residuo, t.nombre AS tipo, t.reciclable, COALESCE(" . self::SUMA . ", 0) AS kg
                FROM tipo_residuo t
                LEFT JOIN movimiento_stock m ON m.id_tipo_residuo = t.id_tipo_residuo AND m.id_centro = :centro
                GROUP BY t.id_tipo_residuo, t.nombre, t.reciclable
                ORDER BY t.nombre";
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute([":centro" => $idCentro]);
        return array_map(function (array $f) {
            return ["id_tipo_residuo" => (int) $f["id_tipo_residuo"], "tipo" => $f["tipo"],
                    "reciclable" => (bool) $f["reciclable"], "kg" => round((float) $f["kg"], 2)];
        }, $consulta->fetchAll());
    }

    public static function saldoDelTipo(PDO $conexion, int $idCentro, int $idTipo): float
    {
        $consulta = $conexion->prepare("SELECT COALESCE(" . self::SUMA . ", 0) FROM movimiento_stock m
                                        WHERE m.id_centro = :centro AND m.id_tipo_residuo = :tipo");
        $consulta->execute([":centro" => $idCentro, ":tipo" => $idTipo]);
        return round((float) $consulta->fetchColumn(), 2);
    }

    // Resumen del centro: lo que muestran la ficha y la lista.
    public static function resumen(array $centro): array
    {
        $saldos = self::saldos((int) $centro["id_centro"]);
        $total = Stock::total(array_column($saldos, "kg"));
        $capacidad = $centro["capacidad"] === null ? null : (int) $centro["capacidad"];
        $ocupacion = Stock::ocupacion($total, $capacidad);
        return ["saldos" => $saldos, "stock_kg" => $total, "capacidad" => $capacidad,
                "ocupacion_pct" => $ocupacion, "nivel" => Stock::nivel($ocupacion)];
    }

    public static function contarMovimientos(int $idCentro, ?string $sentido): int
    {
        $sql = "SELECT COUNT(*) FROM movimiento_stock WHERE id_centro = :centro" . ($sentido ? " AND sentido = :sentido" : "");
        $consulta = Database::conexion()->prepare($sql);
        $consulta->bindValue(":centro", $idCentro, PDO::PARAM_INT);
        if ($sentido) {
            $consulta->bindValue(":sentido", $sentido);
        }
        $consulta->execute();
        return (int) $consulta->fetchColumn();
    }

    public static function movimientos(int $idCentro, ?string $sentido, ?array $pagina): array
    {
        $sql = "SELECT m.id_movimiento, m.fecha_hora, m.sentido, m.motivo, m.kg, m.observacion,
                       t.nombre AS tipo, m.id_recoleccion, m.id_corregido,
                       CONCAT(u.nombre, ' ', u.apellido) AS usuario,
                       (SELECT COUNT(*) FROM movimiento_stock c WHERE c.id_corregido = m.id_movimiento) AS corregido
                FROM movimiento_stock m
                JOIN tipo_residuo t ON t.id_tipo_residuo = m.id_tipo_residuo
                JOIN usuario u ON u.id_usuario = m.id_usuario
                WHERE m.id_centro = :centro" . ($sentido ? " AND m.sentido = :sentido" : "") . "
                ORDER BY m.fecha_hora DESC, m.id_movimiento DESC" . ($pagina === null ? " LIMIT 200" : " LIMIT :limite OFFSET :desde");
        $consulta = Database::conexion()->prepare($sql);
        $consulta->bindValue(":centro", $idCentro, PDO::PARAM_INT);
        if ($sentido) {
            $consulta->bindValue(":sentido", $sentido);
        }
        if ($pagina !== null) {
            $consulta->bindValue(":limite", $pagina["por_pagina"], PDO::PARAM_INT);
            $consulta->bindValue(":desde", ($pagina["pagina"] - 1) * $pagina["por_pagina"], PDO::PARAM_INT);
        }
        $consulta->execute();
        return array_map(function (array $f) {
            $f["id_movimiento"] = (int) $f["id_movimiento"];
            $f["kg"] = (float) $f["kg"];
            $f["corregido"] = (int) $f["corregido"] > 0;
            $f["recoleccion"] = $f["id_recoleccion"] === null ? null : Stock::numeroDeRecoleccion((int) $f["id_recoleccion"]);
            $f["id_corregido"] = $f["id_corregido"] === null ? null : (int) $f["id_corregido"];
            unset($f["id_recoleccion"]);
            return $f;
        }, $consulta->fetchAll());
    }

    // Registra una salida (retiro). Bloquea el centro mientras calcula el saldo para que dos retiros
    // a la vez no saquen mas de lo que hay.
    public static function retirar(int $idCentro, int $idTipo, array $datos, int $idUsuario): array
    {
        $conexion = Database::conexion();
        $conexion->beginTransaction();
        try {
            $conexion->prepare("SELECT id_centro FROM centro_acopio WHERE id_centro = :id FOR UPDATE")->execute([":id" => $idCentro]);
            $error = Stock::errorDeRetiro($datos, self::saldoDelTipo($conexion, $idCentro, $idTipo));
            if ($error !== null) {
                throw new ErrorDeNegocio($error, 409);
            }
            $id = self::insertar($conexion, $idCentro, $idTipo, "salida", $datos["motivo"], Stock::kgValidos($datos["kg"]),
                                 null, null, $idUsuario, $datos["observacion"] ?? null);
            $conexion->commit();
            return self::unMovimiento($id);
        } catch (Throwable $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            throw $e;
        }
    }

    public static function corregir(int $idCentro, int $idMovimiento, int $idUsuario, string $observacion): array
    {
        $conexion = Database::conexion();
        $conexion->beginTransaction();
        try {
            $conexion->prepare("SELECT id_centro FROM centro_acopio WHERE id_centro = :id FOR UPDATE")->execute([":id" => $idCentro]);
            $consulta = $conexion->prepare("SELECT * FROM movimiento_stock WHERE id_movimiento = :id AND id_centro = :centro");
            $consulta->execute([":id" => $idMovimiento, ":centro" => $idCentro]);
            $original = $consulta->fetch();
            if ($original === false) {
                throw new ErrorDeNegocio("Movimiento no encontrado", 404);
            }
            $ya = $conexion->prepare("SELECT COUNT(*) FROM movimiento_stock WHERE id_corregido = :id");
            $ya->execute([":id" => $idMovimiento]);
            $error = Stock::errorDeCorreccion($original, (int) $ya->fetchColumn() > 0,
                                              self::saldoDelTipo($conexion, $idCentro, (int) $original["id_tipo_residuo"]));
            if ($error !== null) {
                throw new ErrorDeNegocio($error, 409);
            }
            $id = self::insertar($conexion, $idCentro, (int) $original["id_tipo_residuo"], Stock::sentidoInverso($original["sentido"]),
                                 "correccion", (float) $original["kg"],
                                 $original["id_recoleccion"] === null ? null : (int) $original["id_recoleccion"],
                                 $idMovimiento, $idUsuario, $observacion);
            $conexion->commit();
            return self::unMovimiento($id);
        } catch (Throwable $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            throw $e;
        }
    }

    // Usada tambien por la recepcion (misma transaccion): inserta un movimiento y devuelve su id.
    public static function insertar(PDO $conexion, int $idCentro, int $idTipo, string $sentido, string $motivo, float $kg,
                                    ?int $idRecoleccion, ?int $idCorregido, int $idUsuario, ?string $observacion): int
    {
        $consulta = $conexion->prepare("INSERT INTO movimiento_stock
            (id_centro, id_tipo_residuo, sentido, motivo, kg, id_recoleccion, id_corregido, id_usuario, observacion)
            VALUES (:centro, :tipo, :sentido, :motivo, :kg, :recoleccion, :corregido, :usuario, :observacion)");
        $consulta->execute([":centro" => $idCentro, ":tipo" => $idTipo, ":sentido" => $sentido, ":motivo" => $motivo, ":kg" => $kg,
                            ":recoleccion" => $idRecoleccion, ":corregido" => $idCorregido, ":usuario" => $idUsuario,
                            ":observacion" => $observacion === null || $observacion === "" ? null : $observacion]);
        return (int) $conexion->lastInsertId();
    }

    private static function unMovimiento(int $id): array
    {
        $consulta = Database::conexion()->prepare("SELECT id_centro FROM movimiento_stock WHERE id_movimiento = :id");
        $consulta->execute([":id" => $id]);
        $idCentro = (int) $consulta->fetchColumn();
        foreach (self::movimientos($idCentro, null, null) as $m) {
            if ($m["id_movimiento"] === $id) {
                return $m;
            }
        }
        return ["id_movimiento" => $id];
    }
}
