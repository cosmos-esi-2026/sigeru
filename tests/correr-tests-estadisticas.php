<?php

// Estadisticas por HTTP (parte 7): permisos, validaciones, y que las cifras del informe y del
// panel coincidan con lo que devuelven las listas de siempre. Se ejecuta dentro de correr-tests.php,
// despues de crear las incidencias de prueba y antes de la limpieza.

titulo("22b. Estadisticas: permisos");

$rutasEstadisticas = [
    "/api-gestion/estadisticas/incidencias",
    "/api-gestion/estadisticas/urgencias",
    "/api-recoleccion/estadisticas/recoleccion",
];
foreach ($rutasEstadisticas as $ruta) {
    $obtenidos = [];
    foreach (["Administrador" => $admin, "Operario" => $operario, "Cuadrilla" => $cuadrilla, "Vecino" => $vecino, "Sin sesion" => null] as $rol => $sesion) {
        [$obtenidos[$rol]] = peticion("GET", $ruta, null, $sesion);
    }
    afirmar(
        "GET $ruta solo lo puede leer un administrador",
        ["Administrador" => 200, "Operario" => 403, "Cuadrilla" => 403, "Vecino" => 403, "Sin sesion" => 401],
        $obtenidos
    );
}
[$codigo] = peticion("POST", "/api-gestion/estadisticas/incidencias", [], $admin);
afirmar("Las estadisticas no se escriben: ni el administrador puede hacer POST (403)", 403, $codigo);
[$codigo] = peticion("DELETE", "/api-recoleccion/estadisticas/recoleccion", null, $admin);
afirmar("Ni DELETE en recoleccion (403)", 403, $codigo);
[$codigo] = peticion("GET", "/api-gestion/estadisticas/inventada", null, $admin);
afirmar("Una estadistica que no existe: 404", 404, $codigo);
[$codigo] = peticion("GET", "/api-recoleccion/estadisticas/inventada", null, $admin);
afirmar("Tampoco en recoleccion: 404", 404, $codigo);

titulo("22c. Estadisticas: validaciones del periodo");

foreach ([
    "desde=2026-13-45"                   => "una fecha imposible",
    "hasta=ayer"                         => "una fecha con texto",
    "desde=2026-10-10&hasta=2026-10-01"  => "un periodo al reves",
    "zona=abc"                           => "una zona con texto",
    "zona=0"                             => "una zona en cero",
] as $consulta => $descripcion) {
    foreach (["/api-gestion/estadisticas/incidencias", "/api-recoleccion/estadisticas/recoleccion"] as $ruta) {
        [$codigo] = peticion("GET", "$ruta?$consulta", null, $admin);
        afirmar("$ruta con $descripcion: 400", 400, $codigo);
    }
}
[$codigo] = peticion("GET", "/api-gestion/estadisticas/incidencias?zona=99999", null, $admin);
afirmar("Una zona que no existe: 404", 404, $codigo);
[$codigo] = peticion("GET", "/api-recoleccion/estadisticas/recoleccion?zona=99999", null, $admin);
afirmar("Tambien en recoleccion: 404", 404, $codigo);
foreach (["0", "61", "-1", "abc"] as $dias) {
    [$codigo] = peticion("GET", "/api-gestion/estadisticas/urgencias?dias=$dias", null, $admin);
    afirmar("Un umbral de dias '$dias': 400", 400, $codigo);
}

titulo("22d. Estadisticas: las cifras coinciden con las listas");

[$codigo, $cuerpo] = peticion("GET", "/api-gestion/incidencias", null, $admin);
$listaIncidencias = $cuerpo["datos"] ?? [];
$conteoPorEstado = array_count_values(array_column($listaIncidencias, "estado"));

[$codigo, $cuerpo] = peticion("GET", "/api-gestion/estadisticas/incidencias", null, $admin);
afirmar("El informe sin filtros responde 200", 200, $codigo);
$informe = $cuerpo["datos"] ?? [];
afirmar("El total es el de la lista de incidencias", count($listaIncidencias), $informe["totales"]["total"] ?? null);
afirmar(
    "Y el desglose por estado tambien",
    [$conteoPorEstado["abierta"] ?? 0, $conteoPorEstado["en_curso"] ?? 0, $conteoPorEstado["resuelta"] ?? 0, $conteoPorEstado["descartada"] ?? 0],
    [$informe["totales"]["abiertas"] ?? null, $informe["totales"]["en_curso"] ?? null, $informe["totales"]["resueltas"] ?? null, $informe["totales"]["descartadas"] ?? null]
);
afirmar("Pendientes y cerradas suman el total", $informe["totales"]["total"],
    ($informe["totales"]["pendientes"] ?? 0) + ($informe["totales"]["cerradas"] ?? 0));
afirmar("Las zonas suman el total", $informe["totales"]["total"], array_sum(array_column($informe["por_zona"] ?? [], "total")));
afirmar("Los tipos suman el total", $informe["totales"]["total"], array_sum(array_column($informe["por_tipo"] ?? [], "total")));
afirmar("Las prioridades suman el total", $informe["totales"]["total"], array_sum($informe["por_prioridad"] ?? []));
afirmar("El detalle trae una fila por incidencia", count($listaIncidencias), count($informe["detalle"] ?? []));
afirmar("El detalle incluye la incidencia que creo esta corrida", true,
    in_array(sprintf("INC-%04d", $idInc), array_column($informe["detalle"] ?? [], "numero"), true));
afirmar("El periodo vuelve en la respuesta", ["desde" => null, "hasta" => null, "id_zona" => null],
    array_intersect_key($informe["periodo"] ?? [], ["desde" => 1, "hasta" => 1, "id_zona" => 1]));

$hoy = date("Y-m-d");
[$codigo, $cuerpo] = peticion("GET", "/api-gestion/estadisticas/incidencias?desde=$hoy&hasta=$hoy", null, $admin);
afirmar("Un periodo que es solo hoy incluye lo creado en esta corrida", true,
    in_array(sprintf("INC-%04d", $idInc), array_column($cuerpo["datos"]["detalle"] ?? [], "numero"), true));
[$codigo, $cuerpo] = peticion("GET", "/api-gestion/estadisticas/incidencias?desde=2999-01-01", null, $admin);
afirmar("Un periodo en el futuro esta vacio", [0, [], false], [
    $cuerpo["datos"]["totales"]["total"] ?? null, $cuerpo["datos"]["por_zona"] ?? null, $cuerpo["datos"]["detalle_truncado"] ?? null,
]);

[$codigo, $cuerpo] = peticion("GET", "/api-gestion/incidencias?zona=1", null, $admin);
$cantidadZona1 = count($cuerpo["datos"] ?? []);
[$codigo, $cuerpo] = peticion("GET", "/api-gestion/estadisticas/incidencias?zona=1", null, $admin);
afirmar("Filtrar por zona da lo mismo que el listado filtrado", $cantidadZona1, $cuerpo["datos"]["totales"]["total"] ?? null);
afirmar("Y el periodo informa el nombre de la zona", true, is_string($cuerpo["datos"]["periodo"]["zona"] ?? null));

[$codigo, $cuerpo] = peticion("GET", "/api-gestion/estadisticas/urgencias", null, $admin);
afirmar("El panel por urgencias responde 200", 200, $codigo);
$panel = $cuerpo["datos"] ?? [];
afirmar("Las abiertas del panel son las abiertas y en curso del listado",
    ($conteoPorEstado["abierta"] ?? 0) + ($conteoPorEstado["en_curso"] ?? 0), $panel["indicadores"]["abiertas"] ?? null);
[$codigo, $cuerpo] = peticion("GET", "/api-gestion/contenedores", null, $admin);
$llenos = array_filter($cuerpo["datos"] ?? [], fn ($c) => ($c["estado"] ?? "") === "lleno");
afirmar("Los llenos del panel son los del listado de contenedores", count($llenos), $panel["indicadores"]["contenedores_llenos"] ?? null);
afirmar("Y la lista de llenos trae los mismos", count($llenos), count($panel["contenedores_llenos"] ?? []));
afirmar("El umbral por omision es de 3 dias", 3, $panel["umbral_dias"] ?? null);
afirmar("Cada urgencia trae nivel, dias y numero", true, array_reduce($panel["incidencias"] ?? [], fn ($ok, $i) =>
    $ok && isset($i["nivel"], $i["dias_abierta"], $i["numero"]) && in_array($i["nivel"], ["critica", "urgente", "atencion", "normal"], true), true));
[$codigo, $cuerpo] = peticion("GET", "/api-gestion/estadisticas/urgencias?dias=60", null, $admin);
afirmar("Con otro umbral cambia lo vencido pero no lo abierto", true,
    ($cuerpo["datos"]["umbral_dias"] ?? null) === 60 && ($cuerpo["datos"]["indicadores"]["abiertas"] ?? -1) === ($panel["indicadores"]["abiertas"] ?? -2)
    && ($cuerpo["datos"]["indicadores"]["vencidas"] ?? 99) <= ($panel["indicadores"]["vencidas"] ?? 0));

[$codigo, $cuerpo] = peticion("GET", "/api-recoleccion/estadisticas/recoleccion", null, $admin);
afirmar("El resumen de recoleccion responde 200", 200, $codigo);
$resumenRec = $cuerpo["datos"] ?? [];
[$codigo, $cuerpo] = peticion("GET", "/api-recoleccion/recolecciones", null, $admin);
afirmar("Las recolecciones del resumen son las del listado", count($cuerpo["datos"] ?? []), $resumenRec["totales"]["recolecciones"] ?? null);
afirmar("Los kilos por tipo suman el total", round($resumenRec["totales"]["peso_kg"] ?? -1, 2), round(array_sum(array_column($resumenRec["por_tipo_residuo"] ?? [], "peso_kg")), 2));
afirmar("Los kilos por zona tambien", round($resumenRec["totales"]["peso_kg"] ?? -1, 2), round(array_sum(array_column($resumenRec["por_zona"] ?? [], "peso_kg")), 2));
[$codigo, $cuerpo] = peticion("GET", "/api-recoleccion/estadisticas/recoleccion?desde=2999-01-01", null, $admin);
afirmar("Un periodo en el futuro no tiene recolecciones ni kilos", [0, 0, 0.0], [
    $cuerpo["datos"]["totales"]["recolecciones"] ?? null, $cuerpo["datos"]["totales"]["vaciados"] ?? null, (float) ($cuerpo["datos"]["totales"]["peso_kg"] ?? -1),
]);

[$codigo, $cuerpo] = peticion("GET", "/api-recoleccion/estadisticas/recoleccion?zona=1", null, $admin);
$zona1 = $cuerpo["datos"] ?? [];
$delInforme = array_values(array_filter($resumenRec["por_zona"] ?? [], fn ($z) => $z["id_zona"] === 1));
afirmar("Filtrar recoleccion por zona deja solo esa zona", [1], array_values(array_unique(array_column($zona1["por_zona"] ?? [], "id_zona"))) ?: [1]);
afirmar("Y sus kilos son los de esa zona en el informe completo", round($delInforme[0]["peso_kg"] ?? 0, 2), round($zona1["totales"]["peso_kg"] ?? -1, 2));
afirmar("Y el periodo informa la zona", true, is_string($zona1["periodo"]["zona"] ?? null));
