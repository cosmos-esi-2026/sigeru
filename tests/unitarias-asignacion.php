<?php

// Prioridad de una ruta y asignacion a cuadrillas (plan de rutas para la defensa, bloques 6 y 7). Casos hechos a mano, sin base de datos.

require_once __DIR__ . "/../api-recoleccion/models/FlujoRecoleccion.php";
require_once __DIR__ . "/../api-recoleccion/models/PrioridadRuta.php";
require_once __DIR__ . "/../api-recoleccion/models/Asignacion.php";

titulo("51. Prioridad de contenedores y de rutas");

$c = fn(string $estado, array $incidencias = []) => PrioridadRuta::deContenedor(["estado" => $estado, "incidencias" => $incidencias]);
afirmar("Un contenedor disponible y sin incidencias: normal, 0 puntos, se recolecta", ["normal", 0, true], [$c("disponible")["nivel"], $c("disponible")["puntaje"], $c("disponible")["recolectable"]]);
afirmar("Un contenedor lleno: prioridad media, 50 puntos", ["media", 50, true], [$c("lleno")["nivel"], $c("lleno")["puntaje"], $c("lleno")["recolectable"]]);
afirmar("Una incidencia «desbordado» abierta: prioridad alta, 100 puntos", ["alta", 100], [$c("disponible", ["desbordado"])["nivel"], $c("disponible", ["desbordado"])["puntaje"]]);
afirmar("Lleno y ademas desbordado puntua por lo mas grave (100), no se suman", ["alta", 100], [$c("lleno", ["desbordado"])["nivel"], $c("lleno", ["desbordado"])["puntaje"]]);
afirmar("Una incidencia «lleno» sobre un contenedor que figura disponible lo vuelve de prioridad media", ["media", 50], [$c("disponible", ["lleno"])["nivel"], $c("disponible", ["lleno"])["puntaje"]]);
afirmar("Otras incidencias (mal ubicado, residuo no permitido, otro) no cambian la recoleccion", ["normal", 0], [$c("disponible", ["otro", "ausente_o_mal_ubicado", "residuo_no_permitido"])["nivel"], $c("disponible", ["otro"])["puntaje"]]);
afirmar("Un contenedor en mantenimiento o retirado no se recolecta y no suma", [false, false, 0, 0], [$c("mantenimiento")["recolectable"], $c("retirado")["recolectable"], $c("mantenimiento")["puntaje"], $c("retirado", ["desbordado"])["puntaje"]]);
afirmar("Un contenedor ROTO (incidencia abierta) no se recolecta aunque este lleno o desbordado: lo atiende mantenimiento", [false, false, 0], [$c("lleno", ["roto"])["recolectable"], $c("disponible", ["desbordado", "roto"])["recolectable"], $c("lleno", ["roto"])["puntaje"]]);
afirmar("Una parada sin datos se toma como disponible sin incidencias", ["normal", true], [PrioridadRuta::deContenedor([])["nivel"], PrioridadRuta::deContenedor([])["recolectable"]]);

$ruta = PrioridadRuta::deRuta([
    ["estado" => "lleno"], ["estado" => "disponible"], ["estado" => "disponible", "incidencias" => ["desbordado"]],
    ["estado" => "mantenimiento"], ["estado" => "lleno", "incidencias" => ["roto"]],
]);
afirmar("Una ruta toma el nivel mas alto de sus contenedores y suma sus puntos (lleno 50 + desbordado 100), contando aparte los que no se recolectan",
    ["alta", 150, 1, 1, 2, 5], [$ruta["nivel"], $ruta["puntaje"], $ruta["urgentes"], $ruta["llenos"], $ruta["no_recolectables"], $ruta["paradas"]]);
afirmar("Una ruta con solo llenos es de prioridad media", ["media", 100], [PrioridadRuta::deRuta([["estado" => "lleno"], ["estado" => "lleno"], ["estado" => "disponible"]])["nivel"], PrioridadRuta::deRuta([["estado" => "lleno"], ["estado" => "lleno"]])["puntaje"]]);
afirmar("Una ruta sin nada urgente es normal; una ruta vacia tambien (sin dividir por cero)", ["normal", 0, "normal", 0], [
    PrioridadRuta::deRuta([["estado" => "disponible"]])["nivel"], PrioridadRuta::deRuta([["estado" => "disponible"]])["puntaje"], PrioridadRuta::deRuta([])["nivel"], PrioridadRuta::deRuta([])["paradas"],
]);
afirmar("Si lo unico urgente esta roto, la ruta NO sube de nivel (el camion no se lo lleva)", "normal", PrioridadRuta::deRuta([["estado" => "disponible", "incidencias" => ["desbordado", "roto"]], ["estado" => "disponible"]])["nivel"]);
afirmar("Los textos de prioridad", ["Alta", "Media", "Normal"], [PrioridadRuta::texto("alta"), PrioridadRuta::texto("media"), PrioridadRuta::texto("normal")]);

$r = fn(int $id, array $paradas, ?int $dias = null) => ["id_ruta" => $id, "prioridad" => PrioridadRuta::deRuta($paradas), "dias_sin_recoleccion" => $dias];
$normal = [["estado" => "disponible"]];
$lleno = [["estado" => "lleno"]];
$desbordado = [["estado" => "disponible", "incidencias" => ["desbordado"]]];
afirmar("Orden: primero alta, despues media, despues normal", [3, 2, 1], array_column(PrioridadRuta::ordenar([$r(1, $normal), $r(2, $lleno), $r(3, $desbordado)]), "id_ruta"));
afirmar("A igual nivel, gana el mayor puntaje (dos llenos antes que uno)", [2, 1], array_column(PrioridadRuta::ordenar([$r(1, $lleno), $r(2, [["estado" => "lleno"], ["estado" => "lleno"]])]), "id_ruta"));
afirmar("A igual puntaje, va antes la que hace mas dias que no se recolecta; una que nunca se recolecto va antes que todas", [4, 3, 1, 2], array_column(PrioridadRuta::ordenar([$r(1, $normal, 3), $r(2, $normal, 1), $r(3, $normal, 10), $r(4, $normal, null)]), "id_ruta"));
afirmar("Si todo empata, el orden es por id (siempre el mismo)", [1, 2, 3], array_column(PrioridadRuta::ordenar([$r(3, $normal, 5), $r(1, $normal, 5), $r(2, $normal, 5)]), "id_ruta"));

titulo("51 bis. Asignacion sugerida de cuadrillas y camiones");

$rutas = array_map(fn($i) => ["id_ruta" => $i], [10, 20, 30]);
$cuads = array_map(fn($i) => ["id_cuadrilla" => $i], [1, 2, 3, 4, 5]);
$cams = array_map(fn($i) => ["id_camion" => $i], [7, 8, 9]);
$s = Asignacion::sugerir($rutas, $cuads, $cams);
afirmar("Se empareja en orden: la primera ruta (la de mayor prioridad) con la primera cuadrilla y el primer camion", [[10, 1, 7], [20, 2, 8], [30, 3, 9]], array_map(fn($p) => [$p["id_ruta"], $p["id_cuadrilla"], $p["id_camion"]], $s["propuestas"]));
afirmar("Si sobran cuadrillas, quedan libres para otras rutas y se avisa cuales", [[4, 5], [], []], [$s["cuadrillas_que_sobran"], $s["camiones_que_sobran"], $s["rutas_sin_asignar"]]);
$s = Asignacion::sugerir(array_map(fn($i) => ["id_ruta" => $i], [1, 2, 3, 4, 5]), [["id_cuadrilla" => 9], ["id_cuadrilla" => 8]], array_map(fn($i) => ["id_camion" => $i], [1, 2, 3, 4]));
afirmar("Si sobran rutas, las de menor prioridad quedan sin asignar y se avisa cuales; los camiones que sobran tambien", [[[1, 9, 1], [2, 8, 2]], [3, 4, 5], [], [3, 4]], [
    array_map(fn($p) => [$p["id_ruta"], $p["id_cuadrilla"], $p["id_camion"]], $s["propuestas"]), $s["rutas_sin_asignar"], $s["cuadrillas_que_sobran"], $s["camiones_que_sobran"],
]);
$s = Asignacion::sugerir([], $cuads, $cams);
afirmar("Sin rutas no hay propuestas y todo sobra", [0, 5, 3], [count($s["propuestas"]), count($s["cuadrillas_que_sobran"]), count($s["camiones_que_sobran"])]);
$s = Asignacion::sugerir($rutas, [], $cams);
afirmar("Sin cuadrillas libres no hay propuestas y las tres rutas quedan sin asignar", [0, [10, 20, 30]], [count($s["propuestas"]), $s["rutas_sin_asignar"]]);

afirmar("Fecha y hora: se arma «AAAA-MM-DD HH:MM:00» (la hora de la ruta puede traer segundos)", ["2026-11-16 06:30:00", "2026-11-16 06:30:00"], [Asignacion::fechaHora("2026-11-16", "06:30"), Asignacion::fechaHora("2026-11-16", "06:30:00")]);
afirmar("Una fecha que no existe o una hora imposible se rechazan", [null, null, null, null], [Asignacion::fechaHora("2026-02-30", "06:00"), Asignacion::fechaHora("2026-11-16", "25:00"), Asignacion::fechaHora("mañana", "06:00"), Asignacion::fechaHora("2026-11-16", "")]);

$a = fn(int $ruta, int $cuadrilla, int $camion, string $dia = "2026-11-16") => ["id_ruta" => $ruta, "id_cuadrilla" => $cuadrilla, "id_camion" => $camion, "fecha_hora_inicio" => "$dia 06:00"];
afirmar("Un pedido de lote valido: sin error", null, Asignacion::errorDeLote(["asignaciones" => [$a(1, 1, 1), $a(2, 2, 2)]]));
afirmar("Sin la clave, vacio o con claves sueltas: rechazado", [true, true, true], [Asignacion::errorDeLote([]) !== null, Asignacion::errorDeLote(["asignaciones" => []]) !== null, Asignacion::errorDeLote(["asignaciones" => ["x" => $a(1, 1, 1)]]) !== null]);
afirmar("Una asignacion incompleta dice cual es (la numero 2)", true, str_starts_with((string) Asignacion::errorDeLote(["asignaciones" => [$a(1, 1, 1), ["id_ruta" => 2]]]), "Asignación 2: "));
afirmar("La misma ruta, cuadrilla o camion dos veces el mismo dia: rechazado, y dice cual", [true, true, true], [
    str_contains((string) Asignacion::errorDeLote(["asignaciones" => [$a(1, 1, 1), $a(1, 2, 2)]]), "ruta de la asignación 2"),
    str_contains((string) Asignacion::errorDeLote(["asignaciones" => [$a(1, 1, 1), $a(2, 1, 2)]]), "cuadrilla de la asignación 2"),
    str_contains((string) Asignacion::errorDeLote(["asignaciones" => [$a(1, 1, 1), $a(2, 2, 1)]]), "camión de la asignación 2"),
]);
afirmar("Pero la misma cuadrilla en dias distintos si se puede", null, Asignacion::errorDeLote(["asignaciones" => [$a(1, 1, 1, "2026-11-16"), $a(2, 1, 1, "2026-11-17")]]));
afirmar("Mas del maximo de asignaciones de una vez: rechazado", true, Asignacion::errorDeLote(["asignaciones" => array_map(fn($i) => $a($i, $i, $i), range(1, 61))]) !== null);
