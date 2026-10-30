<?php

// Asignacion de rutas a cuadrillas (plan de rutas para la defensa, bloque 6; 22/10/2026). Parte PURA: no toca la base de datos.
//
// Decision SIN Fornos (punto 6 de la nota del profe: «si sobran cuadrillas, recogemos los otros contenedores»): el sistema SUGIERE y el administrador CONFIRMA;
// nada se crea solo. La sugerencia recorre las rutas sin asignar en orden de prioridad (primero las que tienen contenedores desbordados, despues las llenas,
// despues el resto) y, desde la parte 26 (24/10/2026, hallazgo H-04), le busca a cada una la cuadrilla de SU MUNICIPIO:
//   1) una cuadrilla libre del mismo municipio de la ruta;  2) si no hay, una libre sin municipio (puede cubrir cualquiera);
//   3) las rutas que quedaron sin cuadrilla reciben, por orden, las que sobraron de otros municipios, marcadas «fuera_de_zona» (el administrador decide).
// El turno solo avisa («turno_distinto»): no filtra. Si ni las rutas ni las cuadrillas traen municipio, empareja por posicion como antes.
final class Asignacion
{
    // A que hora empieza cada turno (hasta la hora siguiente de la lista; el nocturno cruza la medianoche). frontend-usuarios/js/asignacion.js tiene la misma tabla:
    // tests/unitarias-asignacion.php comprueba que coinciden.
    public const TURNO_DESDE = ["matutino" => 5, "vespertino" => 13, "nocturno" => 21];

    // «06:30» o «06:30:00» -> «matutino». Una hora que no se entiende devuelve null.
    public static function turnoDeLaHora(string $hora): ?string
    {
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $hora, $m)) {
            return null;
        }
        $h = (int) $m[1];
        return $h >= self::TURNO_DESDE["nocturno"] || $h < self::TURNO_DESDE["matutino"] ? "nocturno" : ($h >= self::TURNO_DESDE["vespertino"] ? "vespertino" : "matutino");
    }

    private static function zona(array $x): ?int
    {
        return isset($x["id_zona"]) && $x["id_zona"] !== "" ? (int) $x["id_zona"] : null;
    }

    // $rutas: rutas sin asignar, en orden de prioridad (cada una con "id_ruta", "hora_inicio" y, si se sabe, "id_zona"). $cuadrillas y $camiones: los libres, en el orden en que se
    // ofrecen (la cuadrilla con "id_zona" y "turno" si se saben). Devuelve ["propuestas" => [["id_ruta", "id_cuadrilla", "id_camion", "fuera_de_zona", "turno_distinto"]],
    // "rutas_sin_asignar" => [ids], "cuadrillas_que_sobran" => [ids], "camiones_que_sobran" => [ids]].
    public static function sugerir(array $rutas, array $cuadrillas, array $camiones): array
    {
        $rutas = array_values($rutas);
        $cuadrillas = array_values($cuadrillas);
        $camiones = array_values($camiones);

        // Solo entran a la sugerencia tantas rutas (las de mayor prioridad) como camiones libres hay.
        $entran = min(count($rutas), count($camiones));
        $usada = [];          // indice de cuadrilla => true
        $elegida = [];        // indice de ruta => indice de cuadrilla
        $primera = static function (callable $sirve) use ($cuadrillas, &$usada): ?int {
            foreach ($cuadrillas as $k => $c) {
                if (!isset($usada[$k]) && $sirve($c)) {
                    return $k;
                }
            }
            return null;
        };

        for ($i = 0; $i < $entran; $i++) {
            $zr = self::zona($rutas[$i]);
            $k = $zr !== null ? $primera(static fn(array $c) => self::zona($c) === $zr) : null;
            $k ??= $primera(static fn(array $c) => self::zona($c) === null);
            if ($k !== null) {
                $usada[$k] = true;
                $elegida[$i] = $k;
            }
        }
        for ($i = 0; $i < $entran; $i++) {
            if (!isset($elegida[$i]) && ($k = $primera(static fn(array $c) => true)) !== null) {
                $usada[$k] = true;
                $elegida[$i] = $k;
            }
        }

        // En el orden de las rutas (el de prioridad), no en el que se fueron emparejando: asi los camiones tambien se reparten por prioridad.
        ksort($elegida);
        $propuestas = [];
        $camion = 0;
        foreach ($elegida as $i => $k) {
            $zr = self::zona($rutas[$i]);
            $zc = self::zona($cuadrillas[$k]);
            $turnoRuta = self::turnoDeLaHora((string) ($rutas[$i]["hora_inicio"] ?? ""));
            $turnoCuadrilla = $cuadrillas[$k]["turno"] ?? null;
            $propuestas[] = [
                "id_ruta"        => (int) $rutas[$i]["id_ruta"],
                "id_cuadrilla"   => (int) $cuadrillas[$k]["id_cuadrilla"],
                "id_camion"      => (int) $camiones[$camion++]["id_camion"],
                "fuera_de_zona"  => $zr !== null && $zc !== null && $zr !== $zc,
                "turno_distinto" => $turnoRuta !== null && $turnoCuadrilla !== null && $turnoRuta !== $turnoCuadrilla,
            ];
        }

        $sinCuadrilla = array_filter($rutas, static fn($r, $i) => !isset($elegida[$i]), ARRAY_FILTER_USE_BOTH);
        $sobranCuadrillas = array_filter($cuadrillas, static fn($c, $k) => !isset($usada[$k]), ARRAY_FILTER_USE_BOTH);
        return [
            "propuestas"            => $propuestas,
            "rutas_sin_asignar"     => array_values(array_map(static fn(array $r) => (int) $r["id_ruta"], $sinCuadrilla)),
            "cuadrillas_que_sobran" => array_values(array_map(static fn(array $c) => (int) $c["id_cuadrilla"], $sobranCuadrillas)),
            "camiones_que_sobran"   => array_map(static fn(array $c) => (int) $c["id_camion"], array_slice($camiones, count($propuestas))),
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
        if (!isset($datos["asignaciones"]) || !is_array($datos["asignaciones"]) || array_values($datos["asignaciones"]) !== $datos["asignaciones"] || $datos["asignaciones"] === []) {
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
