<?php

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/FlujoRecoleccion.php";

class Ruta
{
    private const CONSULTA = "SELECT r.id_ruta, r.nombre, r.dia_semana, TIME_FORMAT(r.hora_inicio, '%H:%i') AS hora_inicio,
                                     r.estado, r.id_zona, z.nombre AS zona
                              FROM ruta r
                              JOIN zona z ON z.id_zona = r.id_zona";

    public static function listar(): array
    {
        $filas = Database::conexion()->query(self::CONSULTA . " ORDER BY r.estado, FIELD(r.dia_semana, 'lunes', 'martes',
            'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'), r.hora_inicio, r.nombre")->fetchAll();
        return array_map([self::class, "formatear"], $filas);
    }

    public static function obtener(int $id): ?array
    {
        $consulta = Database::conexion()->prepare(self::CONSULTA . " WHERE r.id_ruta = :id");
        $consulta->execute([":id" => $id]);

        $fila = $consulta->fetch();
        return $fila === false ? null : self::formatear($fila);
    }

    public static function existeZona(int $idZona): bool
    {
        $consulta = Database::conexion()->prepare("SELECT 1 FROM zona WHERE id_zona = :id");
        $consulta->execute([":id" => $idZona]);
        return $consulta->fetchColumn() !== false;
    }

    public static function crear(array $datos): array
    {
        $conexion = Database::conexion();
        $consulta = $conexion->prepare(
            "INSERT INTO ruta (nombre, dia_semana, hora_inicio, estado, id_zona)
             VALUES (:nombre, :dia, :hora, :estado, :zona)"
        );
        $consulta->execute([
            ":nombre" => FlujoRecoleccion::limpiar($datos["nombre"]),
            ":dia"    => $datos["dia_semana"],
            ":hora"   => substr($datos["hora_inicio"], 0, 5) . ":00",
            ":estado" => $datos["estado"] ?? "activa",
            ":zona"   => FlujoRecoleccion::entero($datos["id_zona"]),
        ]);

        return self::obtener((int) $conexion->lastInsertId());
    }

    public static function actualizar(int $id, array $datos): array
    {
        $partes = [];
        $valores = [":id" => $id];

        if (array_key_exists("nombre", $datos)) {
            $partes[] = "nombre = :nombre";
            $valores[":nombre"] = FlujoRecoleccion::limpiar($datos["nombre"]);
        }
        if (array_key_exists("dia_semana", $datos)) {
            $partes[] = "dia_semana = :dia";
            $valores[":dia"] = $datos["dia_semana"];
        }
        if (array_key_exists("hora_inicio", $datos)) {
            $partes[] = "hora_inicio = :hora";
            $valores[":hora"] = substr($datos["hora_inicio"], 0, 5) . ":00";
        }
        if (array_key_exists("id_zona", $datos)) {
            $partes[] = "id_zona = :zona";
            $valores[":zona"] = FlujoRecoleccion::entero($datos["id_zona"]);
        }
        if (array_key_exists("estado", $datos)) {
            $partes[] = "estado = :estado";
            $valores[":estado"] = $datos["estado"];
        }

        if ($partes !== []) {
            $consulta = Database::conexion()->prepare("UPDATE ruta SET " . implode(", ", $partes) . " WHERE id_ruta = :id");
            $consulta->execute($valores);
        }
        return self::obtener($id);
    }

    private static function formatear(array $fila): array
    {
        $fila["id_ruta"] = (int) $fila["id_ruta"];
        $fila["id_zona"] = (int) $fila["id_zona"];
        return $fila;
    }
}
