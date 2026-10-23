<?php

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/Ruta.php";
require_once __DIR__ . "/PrioridadRuta.php";

// La ruta de una recoleccion lista para mostrarla en el mapa de la cuadrilla (plan de rutas para la defensa, bloque 6): las paradas en orden con su ubicacion,
// cuales ya se vaciaron EN ESA recoleccion, la prioridad de cada una y el trazado por calles. Solo se lo entregan a quien ve esa recoleccion (la cuadrilla
// asignada y el administrador): la API de rutas es solo del administrador, asi que la cuadrilla nunca ve rutas ajenas.
final class RecorridoRuta
{
    public static function de(int $idRuta, ?int $idRecoleccion): ?array
    {
        $ruta = Ruta::detalle($idRuta);
        if ($ruta === null) {
            return null;
        }
        $db = Database::conexion();

        $vaciados = [];
        if ($idRecoleccion !== null) {
            $consulta = $db->prepare("SELECT id_contenedor FROM vaciado WHERE id_recoleccion = :id");
            $consulta->execute([":id" => $idRecoleccion]);
            foreach ($consulta->fetchAll(PDO::FETCH_COLUMN) as $id) {
                $vaciados[(int) $id] = true;
            }
        }
        $incidencias = [];
        $ids = array_column($ruta["paradas"], "id_contenedor");
        if ($ids !== []) {
            $consulta = $db->prepare("SELECT id_contenedor, tipo_problema FROM incidencia WHERE estado IN ('abierta', 'en_curso') AND id_contenedor IN (" . implode(",", array_fill(0, count($ids), "?")) . ")");
            $consulta->execute($ids);
            foreach ($consulta->fetchAll() as $i) {
                $incidencias[(int) $i["id_contenedor"]][] = $i["tipo_problema"];
            }
        }

        $visitadas = 0;
        $paradas = array_map(static function (array $p) use ($vaciados, $incidencias, &$visitadas): array {
            $prioridad = PrioridadRuta::deContenedor(["estado" => $p["estado"], "incidencias" => $incidencias[$p["id_contenedor"]] ?? []]);
            $p["visitada"] = isset($vaciados[$p["id_contenedor"]]);
            $p["recolectable"] = $prioridad["recolectable"];
            $p["prioridad"] = $prioridad["nivel"];
            $visitadas += $p["visitada"] ? 1 : 0;
            return $p;
        }, $ruta["paradas"]);

        return [
            "id_ruta" => $ruta["id_ruta"], "nombre" => $ruta["nombre"], "zona" => $ruta["zona"], "dia_semana" => $ruta["dia_semana"], "hora_inicio" => $ruta["hora_inicio"],
            "paradas" => $paradas, "visitadas" => $visitadas, "trazado" => $ruta["trazado"],
        ];
    }
}
