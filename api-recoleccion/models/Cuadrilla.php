<?php

require_once __DIR__ . "/../config/database.php";

class Cuadrilla
{
    private const CONSULTA = "SELECT q.id_cuadrilla, q.nombre, q.turno, q.estado, q.id_zona, z.nombre AS zona,
                                     EXISTS (SELECT 1 FROM recoleccion r
                                             WHERE r.id_cuadrilla = q.id_cuadrilla
                                               AND r.estado = 'en_curso') AS ocupada,
                                     (SELECT COUNT(*) FROM incidencia i
                                      WHERE i.id_cuadrilla = q.id_cuadrilla
                                        AND i.estado = 'en_curso') AS incidencias_en_curso,
                                     (SELECT COUNT(*) FROM usuario u
                                      WHERE u.id_cuadrilla = q.id_cuadrilla
                                        AND u.estado <> 'inactivo') AS integrantes,
                                     (SELECT COUNT(*) FROM recoleccion r
                                      WHERE r.id_cuadrilla = q.id_cuadrilla
                                        AND r.estado IN ('planificada', 'en_curso')) AS recolecciones_abiertas
                              FROM cuadrilla q LEFT JOIN zona z ON z.id_zona = q.id_zona";

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

    public static function existeNombre(string $nombre, ?int $salvo = null): bool
    {
        $consulta = Database::conexion()->prepare("SELECT 1 FROM cuadrilla WHERE nombre = :nombre AND id_cuadrilla <> :salvo");
        $consulta->execute([":nombre" => $nombre, ":salvo" => $salvo ?? 0]);
        return $consulta->fetchColumn() !== false;
    }

    public static function existeZona(int $idZona): bool
    {
        $consulta = Database::conexion()->prepare("SELECT 1 FROM zona WHERE id_zona = :id");
        $consulta->execute([":id" => $idZona]);
        return $consulta->fetchColumn() !== false;
    }

    // El municipio es opcional: vacio o ausente es NULL (la cuadrilla puede cubrir cualquiera).
    private static function zonaOCero($valor): ?int
    {
        return $valor === null || $valor === "" ? null : (int) $valor;
    }

    public static function crear(array $datos): array
    {
        $conexion = Database::conexion();
        $conexion->prepare("INSERT INTO cuadrilla (nombre, turno, estado, id_zona) VALUES (:nombre, :turno, 'activa', :zona)")
            ->execute([":nombre" => trim($datos["nombre"]), ":turno" => ($datos["turno"] ?? "") === "" ? "matutino" : $datos["turno"],
                       ":zona" => self::zonaOCero($datos["id_zona"] ?? null)]);
        return self::obtener((int) $conexion->lastInsertId());
    }

    public static function actualizar(int $id, array $datos): ?array
    {
        $partes = [];
        $valores = [":id" => $id];
        foreach (["nombre", "turno", "estado"] as $campo) {
            if (isset($datos[$campo]) && $datos[$campo] !== "") {
                $partes[] = "$campo = :$campo";
                $valores[":$campo"] = $campo === "nombre" ? trim($datos[$campo]) : $datos[$campo];
            }
        }
        // Si el pedido trae «id_zona», se cambia (vacio la deja sin municipio); si no la trae, se conserva la que tenia.
        if (array_key_exists("id_zona", $datos)) {
            $partes[] = "id_zona = :id_zona";
            $valores[":id_zona"] = self::zonaOCero($datos["id_zona"]);
        }
        if ($partes !== []) {
            Database::conexion()->prepare("UPDATE cuadrilla SET " . implode(", ", $partes) . " WHERE id_cuadrilla = :id")->execute($valores);
        }
        return self::obtener($id);
    }

    // La cuadrilla no se borra (sus recolecciones e incidencias quedan en el historial): se da de baja.
    public static function darDeBaja(int $id): ?array
    {
        Database::conexion()->prepare("UPDATE cuadrilla SET estado = 'inactiva' WHERE id_cuadrilla = :id")->execute([":id" => $id]);
        return self::obtener($id);
    }

    // Las personas de la cuadrilla (sin correo ni telefono: la pantalla del administrador solo necesita saber quienes son).
    public static function integrantes(int $id): array
    {
        $consulta = Database::conexion()->prepare("SELECT u.id_usuario, u.nombre, u.apellido, u.estado FROM usuario u WHERE u.id_cuadrilla = :id ORDER BY u.apellido, u.nombre");
        $consulta->execute([":id" => $id]);
        return $consulta->fetchAll();
    }

    private static function formatear(array $fila): array
    {
        $ocupada = (bool) $fila["ocupada"];

        return [
            "id_cuadrilla"         => (int) $fila["id_cuadrilla"],
            "nombre"               => $fila["nombre"],
            "turno"                => $fila["turno"],
            "estado"               => $fila["estado"],
            "id_zona"              => $fila["id_zona"] === null ? null : (int) $fila["id_zona"],
            "zona"                 => $fila["zona"],
            "ocupada"              => $ocupada,
            "incidencias_en_curso" => (int) $fila["incidencias_en_curso"],
            "integrantes"          => (int) $fila["integrantes"],
            "recolecciones_abiertas" => (int) $fila["recolecciones_abiertas"],
            "disponible"           => $fila["estado"] === "activa" && !$ocupada,
        ];
    }
}
