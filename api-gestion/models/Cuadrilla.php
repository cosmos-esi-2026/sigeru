<?php

require_once __DIR__ . "/../config/database.php";

class Cuadrilla
{
    private const CONSULTA = "SELECT q.id_cuadrilla, q.nombre, q.turno, q.estado,
                                     EXISTS (SELECT 1 FROM recoleccion r
                                             WHERE r.id_cuadrilla = q.id_cuadrilla
                                               AND r.estado = 'en_curso') AS ocupada,
                                     (SELECT COUNT(*) FROM incidencia i
                                      WHERE i.id_cuadrilla = q.id_cuadrilla
                                        AND i.estado = 'en_curso') AS incidencias_en_curso
                              FROM cuadrilla q";

    public static function listar(bool $soloDisponibles = false): array
    {
        $filas = Database::conexion()->query(self::CONSULTA . " ORDER BY q.nombre")->fetchAll();
        $cuadrillas = array_map([self::class, "formatear"], $filas);

        if ($soloDisponibles) {
            $cuadrillas = array_values(array_filter($cuadrillas, fn (array $c) => $c["disponible"]));
        }
        return $cuadrillas;
    }

    public static function obtener(int $id): ?array
    {
        $consulta = Database::conexion()->prepare(self::CONSULTA . " WHERE q.id_cuadrilla = :id");
        $consulta->execute([":id" => $id]);

        $fila = $consulta->fetch();
        return $fila === false ? null : self::formatear($fila);
    }

    private static function formatear(array $fila): array
    {
        $ocupada = (bool) $fila["ocupada"];

        return [
            "id_cuadrilla"         => (int) $fila["id_cuadrilla"],
            "nombre"               => $fila["nombre"],
            "turno"                => $fila["turno"],
            "estado"               => $fila["estado"],
            "ocupada"              => $ocupada,
            "incidencias_en_curso" => (int) $fila["incidencias_en_curso"],
            "disponible"           => $fila["estado"] === "activa" && !$ocupada,
        ];
    }
}
