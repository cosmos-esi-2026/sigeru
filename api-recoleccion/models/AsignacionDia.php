<?php

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/PrioridadRuta.php";
require_once __DIR__ . "/Asignacion.php";
require_once __DIR__ . "/FlujoRecoleccion.php";

// «Asignacion de rutas» de un dia (plan de rutas para la defensa, bloque 6): lo que el administrador necesita para repartir las rutas de un dia entre las cuadrillas.
// Solo LEE (la sugerencia no crea nada); la logica de prioridad y de emparejamiento esta en PrioridadRuta y Asignacion (puras).
final class AsignacionDia
{
    private const DIAS = ["domingo", "lunes", "martes", "miercoles", "jueves", "viernes", "sabado"];

    public static function diaDeLaSemana(string $fecha): string
    {
        return self::DIAS[(int) date("w", strtotime($fecha))];
    }

    public static function armar(string $fecha, bool $otrosDias): array
    {
        $db = Database::conexion();
        $dia = self::diaDeLaSemana($fecha);

        $rutas = $db->query(
            "SELECT r.id_ruta, r.nombre, r.dia_semana, TIME_FORMAT(r.hora_inicio, '%H:%i') AS hora_inicio, r.id_zona, z.nombre AS zona,
                    (SELECT COUNT(*) FROM ruta_contenedor rc WHERE rc.id_ruta = r.id_ruta) AS cantidad
             FROM ruta r JOIN zona z ON z.id_zona = r.id_zona
             WHERE r.estado = 'activa'
             HAVING cantidad > 0
             ORDER BY r.id_ruta"
        )->fetchAll();

        // Los contenedores de cada ruta y las incidencias abiertas o en curso de cada contenedor: con eso se calcula la prioridad.
        $paradas = [];
        foreach ($db->query("SELECT rc.id_ruta, c.id_contenedor, c.estado FROM ruta_contenedor rc JOIN contenedor c ON c.id_contenedor = rc.id_contenedor")->fetchAll() as $p) {
            $paradas[(int) $p["id_ruta"]][] = ["id_contenedor" => (int) $p["id_contenedor"], "estado" => $p["estado"], "incidencias" => []];
        }
        $incidencias = [];
        foreach ($db->query("SELECT id_contenedor, tipo_problema FROM incidencia WHERE estado IN ('abierta', 'en_curso')")->fetchAll() as $i) {
            $incidencias[(int) $i["id_contenedor"]][] = $i["tipo_problema"];
        }

        $diasSin = $db->prepare("SELECT id_ruta, DATEDIFF(:fecha, MAX(DATE(fecha_hora_inicio))) AS dias FROM recoleccion WHERE estado = 'finalizada' GROUP BY id_ruta");
        $diasSin->execute([":fecha" => $fecha]);
        $diasSin = array_column($diasSin->fetchAll(), "dias", "id_ruta");

        $delDia = $db->prepare(
            "SELECT r.id_recoleccion, r.id_ruta, r.id_cuadrilla, r.id_camion, r.estado, r.fecha_hora_inicio, q.nombre AS cuadrilla, c.matricula
             FROM recoleccion r JOIN cuadrilla q ON q.id_cuadrilla = r.id_cuadrilla JOIN camion c ON c.id_camion = r.id_camion
             WHERE DATE(r.fecha_hora_inicio) = :fecha AND r.estado IN ('planificada', 'en_curso')
             ORDER BY r.id_recoleccion"
        );
        $delDia->execute([":fecha" => $fecha]);
        $asignadas = [];
        $cuadrillasOcupadas = [];
        $camionesOcupados = [];
        foreach ($delDia->fetchAll() as $a) {
            $asignadas[(int) $a["id_ruta"]] ??= [
                "id_recoleccion" => (int) $a["id_recoleccion"], "numero" => FlujoRecoleccion::numero((int) $a["id_recoleccion"]), "estado" => $a["estado"],
                "cuadrilla" => $a["cuadrilla"], "matricula" => $a["matricula"], "fecha_hora_inicio" => $a["fecha_hora_inicio"],
            ];
            $cuadrillasOcupadas[(int) $a["id_cuadrilla"]] = true;
            $camionesOcupados[(int) $a["id_camion"]] = true;
        }

        $filas = [];
        foreach ($rutas as $r) {
            $id = (int) $r["id_ruta"];
            $esDelDia = $r["dia_semana"] === $dia;
            if (!$esDelDia && !$otrosDias && !isset($asignadas[$id])) {
                continue;
            }
            $lista = array_map(static function (array $p) use ($incidencias): array {
                $p["incidencias"] = $incidencias[$p["id_contenedor"]] ?? [];
                return $p;
            }, $paradas[$id] ?? []);
            $filas[] = [
                "id_ruta" => $id, "nombre" => $r["nombre"], "id_zona" => (int) $r["id_zona"], "zona" => $r["zona"], "dia_semana" => $r["dia_semana"], "hora_inicio" => $r["hora_inicio"],
                "cantidad" => (int) $r["cantidad"], "es_del_dia" => $esDelDia, "prioridad" => PrioridadRuta::deRuta($lista),
                "dias_sin_recoleccion" => isset($diasSin[$id]) ? (int) $diasSin[$id] : null, "asignada" => $asignadas[$id] ?? null,
            ];
        }
        $filas = PrioridadRuta::ordenar($filas);
        // Las rutas de ese dia de la semana primero, y dentro de cada grupo, por prioridad.
        usort($filas, static fn(array $a, array $b) => [$b["es_del_dia"], 0] <=> [$a["es_del_dia"], 0]);

        $cuadrillas = [];
        foreach ($db->query("SELECT id_cuadrilla, nombre, turno FROM cuadrilla WHERE estado = 'activa' ORDER BY id_cuadrilla")->fetchAll() as $c) {
            $cuadrillas[] = ["id_cuadrilla" => (int) $c["id_cuadrilla"], "nombre" => $c["nombre"], "turno" => $c["turno"], "libre" => !isset($cuadrillasOcupadas[(int) $c["id_cuadrilla"]])];
        }
        $camiones = [];
        foreach ($db->query("SELECT id_camion, matricula, estado FROM camion WHERE estado = 'operativo' ORDER BY id_camion")->fetchAll() as $c) {
            $camiones[] = ["id_camion" => (int) $c["id_camion"], "matricula" => $c["matricula"], "estado" => $c["estado"], "libre" => !isset($camionesOcupados[(int) $c["id_camion"]])];
        }

        $sinAsignar = array_values(array_filter($filas, static fn(array $r) => $r["asignada"] === null && ($otrosDias || $r["es_del_dia"])));
        $s = Asignacion::sugerir(
            $sinAsignar,
            array_values(array_filter($cuadrillas, static fn(array $c) => $c["libre"])),
            array_values(array_filter($camiones, static fn(array $c) => $c["libre"]))
        );
        $porRuta = array_column($filas, null, "id_ruta");
        $s["propuestas"] = array_map(static function (array $p) use ($porRuta, $fecha): array {
            return $p + ["fecha_hora_inicio" => Asignacion::fechaHora($fecha, $porRuta[$p["id_ruta"]]["hora_inicio"])];
        }, $s["propuestas"]);

        return ["fecha" => $fecha, "dia" => $dia, "rutas" => $filas, "cuadrillas" => $cuadrillas, "camiones" => $camiones, "sugerencia" => $s];
    }
}
