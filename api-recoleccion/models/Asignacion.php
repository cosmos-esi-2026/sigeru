<?php

// Asignacion de rutas a cuadrillas (plan de rutas para la defensa, bloque 6; 22/10/2026). Parte PURA: no toca la base de datos.
//
// Decision SIN Fornos (punto 6 de la nota del profe: «si sobran cuadrillas, recogemos los otros contenedores»): el sistema SUGIERE y el administrador CONFIRMA;
// nada se crea solo. La sugerencia empareja, en orden, las rutas sin asignar (ya ordenadas por prioridad: primero las que tienen contenedores desbordados,
// despues las llenas, despues el resto) con las cuadrillas y los camiones libres. Si sobran cuadrillas, siguen con las rutas de menor prioridad (los «otros
// contenedores»); si sobran rutas, quedan sin asignar y se avisa cuantas.
final class Asignacion
{
    // $rutas: rutas sin asignar, en orden de prioridad (cada una con "id_ruta" y "hora_inicio"). $cuadrillas y $camiones: los libres, en el orden en que se ofrecen.
    // Devuelve ["propuestas" => [["id_ruta", "id_cuadrilla", "id_camion"]], "rutas_sin_asignar" => [ids], "cuadrillas_que_sobran" => [ids], "camiones_que_sobran" => [ids]].
    public static function sugerir(array $rutas, array $cuadrillas, array $camiones): array
    {
        $propuestas = [];
        $n = min(count($rutas), count($cuadrillas), count($camiones));
        for ($i = 0; $i < $n; $i++) {
            $propuestas[] = ["id_ruta" => (int) $rutas[$i]["id_ruta"], "id_cuadrilla" => (int) $cuadrillas[$i]["id_cuadrilla"], "id_camion" => (int) $camiones[$i]["id_camion"]];
        }
        return [
            "propuestas"            => $propuestas,
            "rutas_sin_asignar"     => array_map(static fn(array $r) => (int) $r["id_ruta"], array_slice($rutas, $n)),
            "cuadrillas_que_sobran" => array_map(static fn(array $c) => (int) $c["id_cuadrilla"], array_slice($cuadrillas, $n)),
            "camiones_que_sobran"   => array_map(static fn(array $c) => (int) $c["id_camion"], array_slice($camiones, $n)),
        ];
    }

    // "2026-11-16" + "06:30" -> "2026-11-16 06:30:00". Devuelve null si la fecha o la hora no son validas.
    public static function fechaHora(string $fecha, string $hora): ?string
    {
        $f = DateTime::createFromFormat("!Y-m-d", $fecha);
        $h = DateTime::createFromFormat("!H:i", substr($hora, 0, 5));
        if ($f === false || $f->format("Y-m-d") !== $fecha || $h === false || $h->format("H:i") !== substr($hora, 0, 5)) {
            return null;
        }
        return $fecha . " " . $h->format("H:i") . ":00";
    }

    // Pura: forma del pedido POST /recolecciones/lote  {"asignaciones": [{"id_ruta", "id_cuadrilla", "id_camion", "fecha_hora_inicio", "id_centro"?, "observaciones"?}, ...]}
    public static function errorDeLote(array $datos, int $maximo = 60): ?string
    {
        if (!isset($datos["asignaciones"]) || !is_array($datos["asignaciones"]) || !array_is_list($datos["asignaciones"]) || $datos["asignaciones"] === []) {
            return "Mandá «asignaciones»: una lista con al menos una asignación (ruta, cuadrilla, camión y fecha)";
        }
        if (count($datos["asignaciones"]) > $maximo) {
            return "Se pueden asignar hasta $maximo rutas de una vez";
        }
        $rutas = [];
        $cuadrillas = [];
        $camiones = [];
        foreach ($datos["asignaciones"] as $i => $a) {
            $n = $i + 1;
            if (!is_array($a)) {
                return "La asignación $n no es válida";
            }
            $error = FlujoRecoleccion::errorDeNueva($a);
            if ($error !== null) {
                return "Asignación $n: $error";
            }
            $r = FlujoRecoleccion::entero($a["id_ruta"]);
            $cu = FlujoRecoleccion::entero($a["id_cuadrilla"]);
            $ca = FlujoRecoleccion::entero($a["id_camion"]);
            $dia = substr((string) FlujoRecoleccion::fechaHora($a["fecha_hora_inicio"]), 0, 10);
            if (isset($rutas[$r . "|" . $dia])) {
                return "La ruta de la asignación $n está repetida el mismo día";
            }
            if (isset($cuadrillas[$cu . "|" . $dia])) {
                return "La cuadrilla de la asignación $n ya tiene otra ruta ese día en este pedido";
            }
            if (isset($camiones[$ca . "|" . $dia])) {
                return "El camión de la asignación $n ya tiene otra ruta ese día en este pedido";
            }
            $rutas[$r . "|" . $dia] = $cuadrillas[$cu . "|" . $dia] = $camiones[$ca . "|" . $dia] = true;
        }
        return null;
    }
}
