<?php

// Calculos de estadisticas: informe de incidencias, panel por urgencias y resumen de recolecciones.
// Son clases puras (reciben filas ya leidas), asi que se prueban sin base ni servidor.

require_once __DIR__ . "/../api-gestion/models/Estadisticas.php";
require_once __DIR__ . "/../api-recoleccion/models/ResumenRecoleccion.php";

function incidenciaDePrueba(int $id, string $estado, string $prioridad, string $tipo, int $zona, int $contenedor,
                            string $reporte, ?string $resolucion = null): array
{
    return [
        "id_incidencia" => $id, "estado" => $estado, "prioridad" => $prioridad, "tipo_problema" => $tipo,
        "id_zona" => $zona, "zona" => $zona === 1 ? "Centro" : "Norte",
        "id_contenedor" => $contenedor, "contenedor" => "CNT-$contenedor", "direccion" => "Calle $contenedor",
        "fecha_reporte" => $reporte, "fecha_resolucion" => $resolucion,
    ];
}

titulo("10. Estadisticas: matematica basica");

afirmar("Mediana de una cantidad impar", 3.0, Estadisticas::mediana([5, 1, 3]));
afirmar("Mediana de una cantidad par", 2.5, Estadisticas::mediana([4, 1, 2, 3]));
afirmar("Mediana de nada", null, Estadisticas::mediana([]));
afirmar("Promedio de nada", null, Estadisticas::promedio([]));
afirmar("Redondea a un decimal", 12.3, Estadisticas::redondear(12.34));
afirmar("Doce horas entre dos fechas", 12.0, Estadisticas::horasEntre("2026-10-01 08:00:00", "2026-10-01 20:00:00"));
afirmar("Cruza la medianoche y cuenta dias", 30.5, Estadisticas::horasEntre("2026-10-01 20:00:00", "2026-10-03 02:30:00"));
afirmar("Sin fecha de resolucion no hay horas", null, Estadisticas::horasEntre("2026-10-01 08:00:00", null));
afirmar("Una resolucion anterior al reporte no cuenta", null, Estadisticas::horasEntre("2026-10-02 08:00:00", "2026-10-01 08:00:00"));
afirmar("Una fecha rota no cuenta", null, Estadisticas::horasEntre("no-es-fecha", "2026-10-01 08:00:00"));
afirmar(
    "Resumen de tiempos",
    ["cantidad" => 3, "medio_horas" => 28.0, "mediano_horas" => 24.0, "minimo_horas" => 12.0, "maximo_horas" => 48.0],
    Estadisticas::resumenTiempos([12.0, 24.0, 48.0])
);
afirmar(
    "Resumen de tiempos sin datos",
    ["cantidad" => 0, "medio_horas" => null, "mediano_horas" => null, "minimo_horas" => null, "maximo_horas" => null],
    Estadisticas::resumenTiempos([])
);
afirmar("El problema frecuente es el de mayor cantidad", "roto", Estadisticas::problemaFrecuente(["roto" => 3, "otro" => 1]));
afirmar("En un empate gana el primero de la lista de tipos", "desbordado", Estadisticas::problemaFrecuente(["roto" => 2, "desbordado" => 2]));
afirmar("Sin incidencias no hay problema frecuente", null, Estadisticas::problemaFrecuente([]));

titulo("11. Estadisticas: informe de incidencias de un periodo");

$filas = [
    incidenciaDePrueba(1, "resuelta", "alta", "desbordado", 1, 1, "2026-10-01 08:00:00", "2026-10-01 20:00:00"),
    incidenciaDePrueba(2, "resuelta", "media", "roto", 1, 2, "2026-10-01 10:00:00", "2026-10-02 10:00:00"),
    incidenciaDePrueba(3, "resuelta", "baja", "otro", 2, 3, "2026-10-02 00:00:00", "2026-10-04 00:00:00"),
    incidenciaDePrueba(4, "abierta", "alta", "desbordado", 1, 1, "2026-10-05 09:00:00"),
    incidenciaDePrueba(5, "en_curso", "media", "desbordado", 1, 1, "2026-10-06 09:00:00"),
    incidenciaDePrueba(6, "descartada", "baja", "otro", 2, 3, "2026-10-07 09:00:00"),
];
$informe = Estadisticas::informe($filas);

afirmar(
    "Totales por estado, abiertas (pendientes) y cerradas",
    ["total" => 6, "abiertas" => 1, "en_curso" => 1, "resueltas" => 3, "descartadas" => 1, "pendientes" => 2, "cerradas" => 4],
    $informe["totales"]
);
afirmar("Por prioridad", ["baja" => 2, "media" => 2, "alta" => 2], $informe["por_prioridad"]);
afirmar(
    "Por tipo, de mayor a menor",
    [["desbordado", 3], ["otro", 2], ["roto", 1], ["lleno", 0], ["residuo_no_permitido", 0], ["ausente_o_mal_ubicado", 0]],
    array_map(fn ($t) => [$t["tipo_problema"], $t["total"]], $informe["por_tipo"])
);
afirmar(
    "Tiempo de resolucion: solo cuentan las resueltas",
    ["cantidad" => 3, "medio_horas" => 28.0, "mediano_horas" => 24.0, "minimo_horas" => 12.0, "maximo_horas" => 48.0],
    $informe["tiempo_resolucion"]
);
afirmar(
    "Por zona, con su tiempo medio",
    [["Centro", 4, 1, 1, 2, 0, 18.0], ["Norte", 2, 0, 0, 1, 1, 48.0]],
    array_map(fn ($z) => [$z["zona"], $z["total"], $z["abiertas"], $z["en_curso"], $z["resueltas"], $z["descartadas"],
                          $z["tiempo_medio_horas"]], $informe["por_zona"])
);
afirmar(
    "Contenedores con mas incidencias y su problema mas frecuente",
    [["CNT-1", 3, 2, "desbordado"], ["CNT-3", 2, 0, "otro"], ["CNT-2", 1, 0, "roto"]],
    array_map(fn ($c) => [$c["codigo"], $c["total"], $c["pendientes"], $c["problema_frecuente"]], $informe["top_contenedores"])
);
afirmar("El detalle trae una fila por incidencia, con su numero", ["INC-0001", 6], [$informe["detalle"][0]["numero"], count($informe["detalle"])]);
afirmar("Y las horas de resolucion de las resueltas", [12.0, 24.0, 48.0, null], array_column(array_slice($informe["detalle"], 0, 4), "horas_resolucion"));
afirmar("La suma de las zonas es el total", $informe["totales"]["total"], array_sum(array_column($informe["por_zona"], "total")));
afirmar("La suma de los tipos es el total", $informe["totales"]["total"], array_sum(array_column($informe["por_tipo"], "total")));
afirmar("Sin recortar el detalle", false, $informe["detalle_truncado"]);

$vacio = Estadisticas::informe([]);
afirmar("Un periodo sin incidencias da todo en cero", [0, 0, [], [], false], [
    $vacio["totales"]["total"], $vacio["tiempo_resolucion"]["cantidad"], $vacio["por_zona"], $vacio["top_contenedores"], $vacio["detalle_truncado"],
]);

$muchas = [];
for ($i = 1; $i <= Estadisticas::MAX_DETALLE + 1; $i++) {
    $muchas[] = incidenciaDePrueba($i, "abierta", "baja", "otro", 1, 1, "2026-10-01 08:00:00");
}
$grande = Estadisticas::informe($muchas);
afirmar("El detalle se corta en el maximo pero los totales cuentan todo", [Estadisticas::MAX_DETALLE, Estadisticas::MAX_DETALLE + 1, true],
    [count($grande["detalle"]), $grande["totales"]["total"], $grande["detalle_truncado"]]);

$sinTiempo = Estadisticas::informe([incidenciaDePrueba(9, "resuelta", "baja", "otro", 1, 1, "2026-10-05 08:00:00", "2026-10-01 08:00:00")]);
afirmar("Una resuelta con fechas incoherentes cuenta como resuelta pero no entra en los tiempos", [1, 0],
    [$sinTiempo["totales"]["resueltas"], $sinTiempo["tiempo_resolucion"]["cantidad"]]);

titulo("12. Estadisticas: panel por urgencias");

afirmar("Umbral por omision: 3 dias", 3, Estadisticas::umbralDe(null));
afirmar("Un umbral vacio tambien", 3, Estadisticas::umbralDe(""));
afirmar("Un umbral valido", 5, Estadisticas::umbralDe("5"));
afirmar("El maximo de 60 dias", 60, Estadisticas::umbralDe("60"));
afirmar("Cero, negativo, pasado de 60 o texto no sirven", [null, null, null, null],
    [Estadisticas::umbralDe("0"), Estadisticas::umbralDe("-2"), Estadisticas::umbralDe("61"), Estadisticas::umbralDe("abc")]);

afirmar("Alta y vencida: critica", "critica", Estadisticas::nivelDeUrgencia("alta", 100.0, 1, 3));
afirmar("Alta sin vencer: urgente", "urgente", Estadisticas::nivelDeUrgencia("alta", 10.0, 1, 3));
afirmar("Media vencida: urgente", "urgente", Estadisticas::nivelDeUrgencia("media", 80.0, 1, 3));
afirmar("Sin cuadrilla y sin vencer: atencion", "atencion", Estadisticas::nivelDeUrgencia("media", 5.0, null, 3));
afirmar("Con cuadrilla y sin vencer: normal", "normal", Estadisticas::nivelDeUrgencia("baja", 5.0, 2, 3));
afirmar("A las 72 horas justas ya esta vencida (umbral de 3 dias)", ["normal", "urgente"],
    [Estadisticas::nivelDeUrgencia("baja", 71.0, 1, 3), Estadisticas::nivelDeUrgencia("baja", 72.0, 1, 3)]);

function abiertaDePrueba(int $id, string $prioridad, int $horas, ?int $cuadrilla): array
{
    return [
        "id_incidencia" => $id, "estado" => $cuadrilla === null ? "abierta" : "en_curso", "prioridad" => $prioridad,
        "tipo_problema" => "roto", "id_cuadrilla" => $cuadrilla, "contenedor" => "CNT-$id", "direccion" => "Calle $id",
        "zona" => "Centro", "cuadrilla" => $cuadrilla === null ? null : "Cuadrilla $cuadrilla", "horas_abierta" => $horas,
    ];
}

$urgencias = Estadisticas::urgencias([
    abiertaDePrueba(10, "alta", 100, null),
    abiertaDePrueba(11, "alta", 10, 1),
    abiertaDePrueba(12, "media", 80, 1),
    abiertaDePrueba(13, "media", 5, null),
    abiertaDePrueba(14, "baja", 5, 2),
    abiertaDePrueba(15, "alta", 200, 1),
], 3);
afirmar("Indicadores del panel", ["abiertas" => 6, "alta" => 3, "sin_asignar" => 2, "vencidas" => 3, "criticas" => 2], $urgencias["indicadores"]);
afirmar(
    "Orden: criticas (la mas vieja primero), urgentes, atencion y normales",
    [15, 10, 12, 11, 13, 14],
    array_column($urgencias["incidencias"], "id_incidencia")
);
afirmar("Cada fila trae su nivel y sus dias", ["critica", 8, "INC-0015"],
    [$urgencias["incidencias"][0]["nivel"], $urgencias["incidencias"][0]["dias_abierta"], $urgencias["incidencias"][0]["numero"]]);

$muchasAbiertas = [];
for ($i = 1; $i <= 25; $i++) {
    $muchasAbiertas[] = abiertaDePrueba($i, "media", $i, 1);
}
$recortada = Estadisticas::urgencias($muchasAbiertas, 3);
afirmar("La lista se corta en las mas urgentes pero los indicadores cuentan todo", [Estadisticas::MAX_URGENTES, 25],
    [count($recortada["incidencias"]), $recortada["indicadores"]["abiertas"]]);
afirmar("Sin incidencias abiertas el panel esta limpio", [0, []], [Estadisticas::urgencias([], 3)["indicadores"]["abiertas"], Estadisticas::urgencias([], 3)["incidencias"]]);

titulo("13. Estadisticas: resumen de lo recolectado");

$resumen = ResumenRecoleccion::resumen([
    ["tipo_residuo" => "Vidrio", "id_zona" => 1, "zona" => "Centro", "peso_kg" => "100.50", "volumen_l" => "300.00"],
    ["tipo_residuo" => "Vidrio", "id_zona" => 1, "zona" => "Centro", "peso_kg" => "50.00", "volumen_l" => "100.00"],
    ["tipo_residuo" => "Papel", "id_zona" => 2, "zona" => "Norte", "peso_kg" => null, "volumen_l" => "200.00"],
    ["tipo_residuo" => "Mixto", "id_zona" => 2, "zona" => "Norte", "peso_kg" => "20.25", "volumen_l" => null],
], [["estado" => "finalizada"], ["estado" => "finalizada"], ["estado" => "cancelada"]]);

afirmar(
    "Totales (un vaciado sin peso se cuenta aparte)",
    ["recolecciones" => 3, "vaciados" => 4, "peso_kg" => 170.75, "volumen_l" => 600.0, "vaciados_sin_peso" => 1,
     "por_estado" => ["planificada" => 0, "en_curso" => 0, "finalizada" => 2, "cancelada" => 1]],
    $resumen["totales"]
);
afirmar("Por tipo de residuo, de mayor a menor peso", [["Vidrio", 2, 150.5], ["Mixto", 1, 20.25], ["Papel", 1, 0.0]],
    array_map(fn ($t) => [$t["tipo_residuo"], $t["vaciados"], $t["peso_kg"]], $resumen["por_tipo_residuo"]));
afirmar("Por zona", [["Centro", 2, 150.5, 400.0], ["Norte", 2, 20.25, 200.0]],
    array_map(fn ($z) => [$z["zona"], $z["vaciados"], $z["peso_kg"], $z["volumen_l"]], $resumen["por_zona"]));
afirmar("La suma por tipo es el peso total", $resumen["totales"]["peso_kg"], array_sum(array_column($resumen["por_tipo_residuo"], "peso_kg")));

$sinDatos = ResumenRecoleccion::resumen([], []);
afirmar("Sin recolecciones todo da cero", [0, 0, 0.0, [], []], [
    $sinDatos["totales"]["recolecciones"], $sinDatos["totales"]["vaciados"], $sinDatos["totales"]["peso_kg"],
    $sinDatos["por_tipo_residuo"], $sinDatos["por_zona"],
]);
afirmar("Los estados de recoleccion del resumen son los del flujo", FlujoRecoleccion::ESTADOS, ResumenRecoleccion::ESTADOS);
