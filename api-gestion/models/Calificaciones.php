<?php

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../core/ErrorDeNegocio.php";
require_once __DIR__ . "/Participacion.php";
require_once __DIR__ . "/FlujoIncidencia.php";

// Consultas y escrituras de la participacion del vecino (extras, parte 14).
class Calificaciones
{
    public static function de(int $idIncidencia): ?array
    {
        $consulta = Database::conexion()->prepare("SELECT puntaje, comentario, fecha_hora FROM calificacion WHERE id_incidencia = :i");
        $consulta->execute([":i" => $idIncidencia]);
        $fila = $consulta->fetch();
        return $fila === false ? null : ["puntaje" => (int) $fila["puntaje"], "comentario" => $fila["comentario"], "fecha_hora" => $fila["fecha_hora"]];
    }

    // Suma los puntos de un reclamo resuelto, dentro de la transaccion que lo resuelve. Una sola vez por reclamo.
    public static function sumarPorResolucion(PDO $conexion, int $idUsuario, int $idIncidencia): void
    {
        $conexion->prepare("INSERT IGNORE INTO movimiento_punto (id_usuario, id_incidencia, motivo, puntos)
                            VALUES (:u, :i, 'reclamo_resuelto', :p)")
                 ->execute([":u" => $idUsuario, ":i" => $idIncidencia, ":p" => Participacion::PUNTOS_RESUELTO]);
    }

    public static function calificar(int $idIncidencia, array $usuario, int $puntaje, ?string $comentario): array
    {
        Database::transaccion(function (PDO $conexion) use ($idIncidencia, $usuario, $puntaje, $comentario) {
            $consulta = $conexion->prepare("SELECT id_incidencia, id_usuario, estado, fecha_resolucion FROM incidencia WHERE id_incidencia = :i FOR UPDATE");
            $consulta->execute([":i" => $idIncidencia]);
            $incidencia = $consulta->fetch();
            if ($incidencia === false) {
                throw new ErrorDeNegocio("Incidencia no encontrada", 404);
            }
            $ya = $conexion->prepare("SELECT COUNT(*) FROM calificacion WHERE id_incidencia = :i");
            $ya->execute([":i" => $idIncidencia]);
            $error = Participacion::errorDeCalificacion($incidencia, $usuario, (int) $ya->fetchColumn() > 0, (string) $conexion->query("SELECT NOW()")->fetchColumn());
            if ($error !== null) {
                throw new ErrorDeNegocio($error[1], $error[0]);
            }
            $conexion->prepare("INSERT INTO calificacion (id_incidencia, id_usuario, puntaje, comentario) VALUES (:i, :u, :p, :c)")
                     ->execute([":i" => $idIncidencia, ":u" => $usuario["id_usuario"], ":p" => $puntaje, ":c" => $comentario === "" ? null : $comentario]);
            $conexion->prepare("INSERT INTO movimiento_punto (id_usuario, id_incidencia, motivo, puntos) VALUES (:u, :i, 'calificacion', :p)")
                     ->execute([":u" => $usuario["id_usuario"], ":i" => $idIncidencia, ":p" => Participacion::PUNTOS_CALIFICAR]);
        });
        return self::de($idIncidencia);
    }

    private static function conteo(string $sql, int $idUsuario): int
    {
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute([":u" => $idUsuario]);
        return (int) $consulta->fetchColumn();
    }

    // "Mi progreso" del vecino: puntos, insignias, ultimos movimientos y los reclamos que puede calificar todavia.
    public static function progreso(int $idUsuario): array
    {
        $conexion = Database::conexion();
        $resueltos = self::conteo("SELECT COUNT(*) FROM incidencia WHERE id_usuario = :u AND estado = 'resuelta'", $idUsuario);
        $calificados = self::conteo("SELECT COUNT(*) FROM calificacion WHERE id_usuario = :u", $idUsuario);

        $movimientos = $conexion->prepare("SELECT p.id_incidencia, p.motivo, p.puntos, p.fecha_hora FROM movimiento_punto p
                                           WHERE p.id_usuario = :u ORDER BY p.fecha_hora DESC, p.id_movimiento DESC LIMIT 10");
        $movimientos->execute([":u" => $idUsuario]);

        $pendientes = $conexion->prepare("SELECT i.id_incidencia, i.fecha_resolucion, c.direccion FROM incidencia i
            JOIN contenedor c ON c.id_contenedor = i.id_contenedor
            WHERE i.id_usuario = :u AND i.estado = 'resuelta' AND i.fecha_resolucion IS NOT NULL
              AND NOT EXISTS (SELECT 1 FROM calificacion k WHERE k.id_incidencia = i.id_incidencia)
              AND i.fecha_resolucion >= DATE_SUB(NOW(), INTERVAL " . Participacion::PLAZO_DIAS . " DAY)
            ORDER BY i.fecha_resolucion DESC");
        $pendientes->execute([":u" => $idUsuario]);

        return [
            "puntos"       => self::conteo("SELECT COALESCE(SUM(puntos), 0) FROM movimiento_punto WHERE id_usuario = :u", $idUsuario),
            "resueltos"    => $resueltos,
            "calificados"  => $calificados,
            "insignias"    => Participacion::insignias($resueltos, $calificados),
            "movimientos"  => array_map(fn($m) => [
                "numero" => FlujoIncidencia::numero((int) $m["id_incidencia"]), "id_incidencia" => (int) $m["id_incidencia"],
                "motivo" => Participacion::textoMotivo($m["motivo"]), "puntos" => (int) $m["puntos"], "fecha_hora" => $m["fecha_hora"],
            ], $movimientos->fetchAll()),
            "por_calificar" => array_map(fn($p) => [
                "numero" => FlujoIncidencia::numero((int) $p["id_incidencia"]), "id_incidencia" => (int) $p["id_incidencia"],
                "direccion" => $p["direccion"], "hasta" => Participacion::plazoHasta($p["fecha_resolucion"]),
            ], $pendientes->fetchAll()),
            "puntos_por_resuelto" => Participacion::PUNTOS_RESUELTO, "puntos_por_calificar" => Participacion::PUNTOS_CALIFICAR,
        ];
    }

    // Por zona, nunca por persona: cuantos reclamos se resolvieron y cuantos puntos sumaron los vecinos de esa zona.
    public static function rankingZonas(): array
    {
        $filas = Database::conexion()->query(
            "SELECT z.nombre AS zona,
                    (SELECT COUNT(*) FROM incidencia i JOIN contenedor c ON c.id_contenedor = i.id_contenedor
                      WHERE c.id_zona = z.id_zona AND i.estado = 'resuelta') AS resueltos,
                    (SELECT COALESCE(SUM(p.puntos), 0) FROM movimiento_punto p JOIN incidencia i ON i.id_incidencia = p.id_incidencia
                      JOIN contenedor c ON c.id_contenedor = i.id_contenedor WHERE c.id_zona = z.id_zona) AS puntos
             FROM zona z"
        )->fetchAll();
        return Participacion::ranking($filas);
    }
}
