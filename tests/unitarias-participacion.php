<?php

// Participacion del vecino (extras, parte 14): calificar, puntos, insignias, ranking por zona y calidad del servicio.

require_once __DIR__ . "/../api-gestion/models/Participacion.php";
require_once __DIR__ . "/../api-gestion/models/Satisfaccion.php";

titulo("30. Participacion: calificar con reglas claras");

foreach ([[1, 1], [5, 5], ["3", 3], ["5", 5]] as [$entrada, $esperado]) {
    afirmar("Un puntaje valido: " . var_export($entrada, true), $esperado, Participacion::puntajeValido($entrada));
}
foreach ([0, 6, -1, "0", "6", "10", "abc", "", null, 3.5, "3.5", true, [], " 3", "03"] as $malo) {
    afirmar("Un puntaje invalido: " . json_encode($malo), null, Participacion::puntajeValido($malo));
}
afirmar("El comentario es opcional", [null, null], [Participacion::errorDeComentario(null), Participacion::errorDeComentario("")]);
afirmar("Un comentario de 300 caracteres pasa y de 301 no", [null, true], [Participacion::errorDeComentario(str_repeat("a", 300)), Participacion::errorDeComentario(str_repeat("a", 301)) !== null]);
afirmar("Un comentario que no es texto no pasa", true, Participacion::errorDeComentario(["x"]) !== null);

afirmar("El plazo es de 30 dias desde la resolucion", "2026-10-01 11:00:00", Participacion::plazoHasta("2026-09-01 11:00:00"));
afirmar("Una fecha rara no tiene plazo", null, Participacion::plazoHasta("no es una fecha"));
afirmar("Justo en el limite del plazo se puede; un segundo despues, no", [true, false], [
    Participacion::dentroDelPlazo("2026-09-01 11:00:00", "2026-10-01 11:00:00"), Participacion::dentroDelPlazo("2026-09-01 11:00:00", "2026-10-01 11:00:01")]);
afirmar("Sin fecha de resolucion no hay plazo", false, Participacion::dentroDelPlazo(null, "2026-10-01 00:00:00"));

$resuelta = ["id_usuario" => 3, "estado" => "resuelta", "fecha_resolucion" => "2026-09-01 11:00:00"];
$vecinoAutor = ["rol" => "Vecino", "id_usuario" => 3];
$ahora = "2026-09-10 00:00:00";
afirmar("El autor califica un reclamo resuelto a tiempo: sin error", null, Participacion::errorDeCalificacion($resuelta, $vecinoAutor, false, $ahora));
afirmar("Otro vecino no puede calificar: 404 (para el no existe)", [404, "Incidencia no encontrada"], Participacion::errorDeCalificacion($resuelta, ["rol" => "Vecino", "id_usuario" => 6], false, $ahora));
foreach (["Administrador", "Cuadrilla", "Operario"] as $rol) {
    afirmar("El rol $rol no califica: 404", 404, Participacion::errorDeCalificacion($resuelta, ["rol" => $rol, "id_usuario" => 3], false, $ahora)[0]);
}
foreach (["abierta", "en_curso"] as $estado) {
    afirmar("Un reclamo $estado no se califica: 409", [409, "Solo se puede calificar un reclamo resuelto"], Participacion::errorDeCalificacion(["estado" => $estado] + $resuelta, $vecinoAutor, false, $ahora));
}
afirmar("Un reclamo descartado no se califica: 409", [409, "Un reclamo descartado no se califica"], Participacion::errorDeCalificacion(["estado" => "descartada"] + $resuelta, $vecinoAutor, false, $ahora));
afirmar("No se califica dos veces: 409", [409, "Ese reclamo ya fue calificado"], Participacion::errorDeCalificacion($resuelta, $vecinoAutor, true, $ahora));
afirmar("Pasado el plazo: 409 con el motivo", [409, "El plazo para calificar (30 días desde la resolución) venció"], Participacion::errorDeCalificacion($resuelta, $vecinoAutor, false, "2026-10-01 11:00:01"));

titulo("30 bis. Participacion: insignias calculadas y ranking por zona");

afirmar("Sin nada hecho no hay insignias logradas", [false, false, false, false], array_column(Participacion::insignias(0, 0), "lograda"));
afirmar("Un reclamo resuelto da la primera", [true, false, false, false], array_column(Participacion::insignias(1, 0), "lograda"));
afirmar("Cinco resueltos dan la primera y la de vecino activo", [true, true, false, false], array_column(Participacion::insignias(5, 0), "lograda"));
afirmar("Diez resueltos y una calificacion dan todas", [true, true, true, true], array_column(Participacion::insignias(10, 1), "lograda"));
afirmar("Cuatro resueltos: la de cinco va 4 de 5", [4, 5, false], (function () { $i = Participacion::insignias(4, 0)[1]; return [$i["progreso"], $i["meta"], $i["lograda"]]; })());
afirmar("El progreso no pasa de la meta", 5, Participacion::insignias(99, 0)[1]["progreso"]);
afirmar("Calificar da la insignia de opinar", [false, true], [Participacion::insignias(0, 0)[3]["lograda"], Participacion::insignias(0, 2)[3]["lograda"]]);

$zonas = [["zona" => "Cordon", "resueltos" => 0, "puntos" => 0], ["zona" => "Centro", "resueltos" => 2, "puntos" => 30],
          ["zona" => "Pocitos", "resueltos" => 1, "puntos" => 15], ["zona" => "Ciudad Vieja", "resueltos" => 1, "puntos" => 15]];
afirmar("El ranking ordena por puntos y desempata por reclamos y nombre; los empates comparten posicion", [
    [1, "Centro", 30], [2, "Ciudad Vieja", 15], [2, "Pocitos", 15], [4, "Cordon", 0]], array_map(fn($f) => [$f["posicion"], $f["zona"], $f["puntos"]], Participacion::ranking($zonas)));
afirmar("El ranking es por zona: ninguna fila trae el nombre de una persona", [["posicion", "zona", "resueltos", "puntos"]], [array_keys(Participacion::ranking($zonas)[0])]);
afirmar("Sin zonas no hay ranking", [], Participacion::ranking([]));
afirmar("Los textos de los puntos", ["Reclamo resuelto", "Calificación enviada"], [Participacion::textoMotivo("reclamo_resuelto"), Participacion::textoMotivo("calificacion")]);
afirmar("La escala de puntos es 10 por resuelto y 5 por calificar", [10, 5], [Participacion::PUNTOS_RESUELTO, Participacion::PUNTOS_CALIFICAR]);

titulo("30 ter. Participacion: calidad del servicio por zona y por cuadrilla (cuenta a mano)");

$f = fn($zona, $cuadrilla, $estado, $reporte, $resolucion, $puntaje) =>
    ["zona" => $zona, "cuadrilla" => $cuadrilla, "estado" => $estado, "fecha_reporte" => $reporte, "fecha_resolucion" => $resolucion, "puntaje" => $puntaje];
$filas = [
    $f("A", "N", "resuelta", "2026-09-01 10:00:00", "2026-09-02 10:00:00", 4),
    $f("A", "N", "resuelta", "2026-09-01 10:00:00", "2026-09-01 22:00:00", 5),
    $f("A", "S", "abierta",  "2026-09-05 10:00:00", null, null),
    $f("B", null, "abierta", "2026-09-06 10:00:00", null, null),
    $f("B", "S", "resuelta", "2026-09-01 00:00:00", "2026-09-01 06:00:00", 2),
];
$informe = Satisfaccion::informe($filas);
afirmar("General: 5 incidencias, 3 calificaciones, promedio (4+5+2)/3 = 3,7 y la distribucion", [5, 3, 3.7, [1 => 0, 2 => 1, 3 => 0, 4 => 1, 5 => 1]], [
    $informe["general"]["incidencias"], $informe["general"]["calificaciones"], $informe["general"]["promedio"], $informe["general"]["distribucion"]]);
afirmar("Por zona: A (3 incidencias, 2 resueltas, 4,5 y 18 h) y B (2, 1, 2,0 y 6 h)", [
    ["A", 3, 2, 2, 4.5, 18.0], ["B", 2, 1, 1, 2.0, 6.0]], array_map(fn($z) => [$z["zona"], $z["incidencias"], $z["resueltas"], $z["calificaciones"], $z["promedio"], $z["tiempo_medio_horas"]], $informe["por_zona"]));
afirmar("Por cuadrilla: solo cuentan las incidencias asignadas; empatan en 2 y se ordenan por nombre", [
    ["N", 2, 2, 2, 4.5, 18.0], ["S", 2, 1, 1, 2.0, 6.0]], array_map(fn($c) => [$c["cuadrilla"], $c["incidencias"], $c["resueltas"], $c["calificaciones"], $c["promedio"], $c["tiempo_medio_horas"]], $informe["por_cuadrilla"]));
$vacio = Satisfaccion::informe([]);
afirmar("Sin incidencias no hay promedio ni inventa datos", [0, 0, null, [], []], [$vacio["general"]["incidencias"], $vacio["general"]["calificaciones"], $vacio["general"]["promedio"], $vacio["por_zona"], $vacio["por_cuadrilla"]]);
$sinNota = Satisfaccion::informe([$f("A", "N", "abierta", "2026-09-01 10:00:00", null, null)]);
afirmar("Una zona sin calificaciones tiene promedio nulo (no cero)", [null, null], [$sinNota["por_zona"][0]["promedio"], $sinNota["por_zona"][0]["tiempo_medio_horas"]]);
