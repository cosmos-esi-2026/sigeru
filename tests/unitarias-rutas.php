<?php

// Plan de rutas para la defensa (22/10/2026): topes, esquema de la migracion 017 y conversion de coordenadas de la Intendencia.

titulo("47. Rutas: topes y esquema");

$limitesGestion = __DIR__ . "/../api-gestion/models/Limites.php";
afirmar("Limites.php es identico en api-gestion y api-recoleccion", true, file_get_contents($limitesGestion) === file_get_contents(__DIR__ . "/../api-recoleccion/models/Limites.php"));
require_once $limitesGestion;
afirmar("Topes: 100 contenedores en servicio por zona (municipio) y 25 paradas por ruta", [100, 25], [Limites::MAX_CONTENEDORES_POR_ZONA, Limites::MAX_CONTENEDORES_POR_RUTA]);
afirmar("Los mensajes dicen el tope y la zona, y que hacer", [true, true, true, true], [
    str_contains(Limites::mensajeZonaLlena("Municipio A"), "100"),
    str_contains(Limites::mensajeZonaLlena("Municipio A"), "Municipio A"),
    str_contains(Limites::mensajeZonaLlena("Municipio A"), "retirá"),
    str_contains(Limites::mensajeRutaLlena(), "25"),
]);

$schema = (string) file_get_contents(__DIR__ . "/../database/schema.sql");
$sinRegistrar = [];
foreach (glob(__DIR__ . "/../database/migraciones/*.sql") as $archivo) {
    if (!str_contains($schema, "('" . basename($archivo) . "'")) {
        $sinRegistrar[] = basename($archivo);
    }
}
afirmar("Todas las migraciones estan registradas al final de schema.sql", [], $sinRegistrar);
afirmar("La migracion 017 existe y schema.sql crea ruta_contenedor y las columnas del trazado de la ruta", [true, true, true, true], [
    is_file(__DIR__ . "/../database/migraciones/017-rutas-con-contenedores.sql"),
    str_contains($schema, "CREATE TABLE ruta_contenedor"),
    str_contains($schema, "trazado_tipo        ENUM('calles','recta')"),
    str_contains($schema, "DROP TABLE IF EXISTS ruta_contenedor;"),
]);
$seedPrueba = (string) file_get_contents(__DIR__ . "/../database/datos_prueba.sql");
afirmar("Los dos seeds vacian ruta_contenedor antes de cargar", [true, true], [
    str_contains($seedPrueba, "TRUNCATE TABLE ruta_contenedor;"),
    !is_file(__DIR__ . "/../database/datos_defensa.sql") || str_contains((string) file_get_contents(__DIR__ . "/../database/datos_defensa.sql"), "TRUNCATE TABLE ruta_contenedor;"),
]);

// El conversor de las coordenadas de la Intendencia (UTM 21S -> latitud/longitud) vive en generadores/utm.py, fuera del repo: si no esta, no se prueba.
$python = __DIR__ . "/../../../herramientas/python/python.exe";
$conversor = __DIR__ . "/../../generadores/utm.py";
if (is_file($python) && is_file($conversor)) {
    $salida = [];
    $codigoSalida = 1;
    exec('"' . $python . '" -I "' . $conversor . '" 2>&1', $salida, $codigoSalida);
    afirmar("La autoprueba del conversor UTM a latitud/longitud pasa (ida y vuelta, arco de meridiano, escala)", [0, true], [$codigoSalida, str_contains(implode("\n", $salida), "Todo bien.")]);
}
