<?php

// Parte de correr-tests.php (se incluye desde alli y usa sus funciones y sus sesiones).
// Impacto ambiental (extras, parte 18). Un camion, una ruta, dos contenedores (Vidrio y Mixto) y una recoleccion propios TIMP,
// con vaciados de peso conocido, para ver que las cifras se mueven como la cuenta a mano. Se borra todo al final.

titulo("37. Impacto ambiental: endpoint publico y de Reportes");

$pdo->exec("DELETE FROM recoleccion WHERE id_camion IN (SELECT id_camion FROM camion WHERE matricula LIKE 'TIMP-%')");
$pdo->exec("DELETE FROM camion WHERE matricula LIKE 'TIMP-%'");
$pdo->exec("DELETE FROM ruta WHERE nombre LIKE 'TIMP %'");
$pdo->exec("DELETE FROM contenedor WHERE codigo LIKE 'TIMP-%'");
$pdo->exec("DELETE FROM limite_peticion WHERE clave LIKE 'impacto-publico:%'");

[$codigo, $cuerpo] = peticion("GET", "/api-gestion/publico/impacto");
$antes = $cuerpo["datos"] ?? [];
afirmar("El impacto publico se lee sin sesion (200)", 200, $codigo);
foreach (["Administrador" => $admin, "Vecino" => $vecino] as $rol => $sesion) {
    [$c] = peticion("GET", "/api-gestion/publico/impacto", null, $sesion);
    afirmar("Tambien lo lee un $rol (200)", 200, $c);
}
$clavesEsperadas = ["kg_total", "kg_reciclable", "pct_reciclable", "co2_evitado_kg", "kg_sin_factor", "por_tipo", "kg_recibidos_en_centros", "fuentes", "tipos_sin_factor", "nota", "generado_en"];
$claves = array_keys($antes);
sort($claves);
sort($clavesEsperadas);
afirmar("Trae solo agregados (kg, porcentaje, CO2, por tipo, fuentes y la nota)", $clavesEsperadas, $claves);
$crudo = (string) json_encode($antes);
afirmar("No hay ningun correo, telefono ni id de usuario en la respuesta", false, str_contains($crudo, "@") || str_contains($crudo, "id_usuario") || str_contains($crudo, "telefono"));
afirmar("La nota dice que es una estimacion y los tipos sin factor son Mixto y Organico (solo kg)", [true, ["Mixto", "Organico"]], [str_contains($antes["nota"] ?? "", "Estimación"), $antes["tipos_sin_factor"] ?? null]);
afirmar("Cada una de las cuatro fuentes trae su texto y su enlace a la EPA", true, count($antes["fuentes"] ?? []) === 4
    && count(array_filter($antes["fuentes"], fn($f) => str_starts_with($f["url"] ?? "", "https://www.epa.gov/") && str_contains($f["fuente"], "EPA WARM v16"))) === 4);
$porTipoAntes = array_column($antes["por_tipo"] ?? [], null, "tipo");
afirmar("Un tipo sin factor no tiene CO2 en el detalle (ni factor)", [[null, null], [null, null]], array_map(fn($t) => [array_key_exists("co2_evitado_kg", $porTipoAntes[$t] ?? []) ? $porTipoAntes[$t]["co2_evitado_kg"] : "falta", array_key_exists("factor", $porTipoAntes[$t] ?? []) ? $porTipoAntes[$t]["factor"] : "falta"], ["Mixto", "Organico"]));
[, $rec] = peticion("GET", "/api-recoleccion/estadisticas/recoleccion", null, $admin);
afirmar("Los kg recolectados son los mismos que muestran los Reportes (misma fuente: vaciado)", round((float) ($rec["datos"]["totales"]["peso_kg"] ?? -1), 2), (float) ($antes["kg_total"] ?? -2));
afirmar("Los kg recibidos en centros son los de las recepciones (stock de la parte 12)", (float) $pdo->query("SELECT COALESCE(SUM(kg), 0) FROM movimiento_stock WHERE motivo = 'recepcion'")->fetchColumn(), (float) ($antes["kg_recibidos_en_centros"] ?? -1));
[$c] = peticion("POST", "/api-gestion/publico/impacto", []);
afirmar("No se escribe sin sesion: 401", 401, $c);
$obtenidos = [];
foreach (["sin sesion" => null, "vecino" => $vecino, "operario" => $operario, "cuadrilla" => $cuadrilla] as $quien => $cookies) {
    [$obtenidos[$quien]] = peticion("GET", "/api-gestion/estadisticas/impacto", null, $cookies);
}
afirmar("El impacto por mes de Reportes es del Administrador: los demas reciben 401 o 403", ["sin sesion" => 401, "vecino" => 403, "operario" => 403, "cuadrilla" => 403], $obtenidos);
[$c] = peticion("GET", "/api-gestion/estadisticas/impacto?desde=ayer", null, $admin);
afirmar("Una fecha invalida: 400", 400, $c);
[$c, $cuerpo] = peticion("GET", "/api-gestion/estadisticas/impacto?desde=2999-01-01", null, $admin);
afirmar("Un periodo futuro esta vacio: sin meses, 0 kg y sin porcentaje inventado", [200, [], 0.0, true], [$c, $cuerpo["datos"]["por_mes"] ?? "falta", (float) ($cuerpo["datos"]["kg_total"] ?? -1), array_key_exists("pct_reciclable", $cuerpo["datos"] ?? []) && $cuerpo["datos"]["pct_reciclable"] === null]);


titulo("37 bis. Impacto ambiental: las cifras se mueven como la cuenta a mano");

[, $cuerpo] = peticion("POST", "/api-recoleccion/camiones", ["matricula" => "TIMP-$sufijo", "capacidad" => 4000], $admin);
$idCamionI = (int) ($cuerpo["datos"]["id_camion"] ?? 0);
[, $cuerpo] = peticion("POST", "/api-recoleccion/rutas", ["nombre" => "TIMP Ruta $sufijo", "dia_semana" => "martes", "hora_inicio" => "06:15", "id_zona" => 2], $admin);
$idRutaI = (int) ($cuerpo["datos"]["id_ruta"] ?? 0);
foreach (["V" => "Vidrio", "M" => "Mixto"] as $letra => $tipo) {
    peticion("POST", "/api-gestion/contenedores", ["codigo" => "TIMP-$letra$sufijo", "direccion" => "Contenedor de prueba de impacto", "capacidad" => 1100, "tipo_residuo" => $tipo, "id_zona" => 2], $admin);
}
ponerEnRuta($pdo, $idRutaI, ["TIMP-V$sufijo", "TIMP-M$sufijo"]);
[, $cuerpo] = peticion("POST", "/api-recoleccion/recolecciones", ["id_ruta" => $idRutaI, "id_camion" => $idCamionI, "id_cuadrilla" => 1, "fecha_hora_inicio" => "2026-10-21T06:30", "id_centro" => 1], $admin);
$idRecI = (int) ($cuerpo["datos"]["id_recoleccion"] ?? 0);
peticion("POST", "/api-recoleccion/recolecciones/$idRecI/inicio", [], $cuadrilla);
peticion("POST", "/api-recoleccion/recolecciones/$idRecI/vaciados", ["codigo_contenedor" => "TIMP-V$sufijo", "peso_kg" => 100], $cuadrilla);
peticion("POST", "/api-recoleccion/recolecciones/$idRecI/vaciados", ["codigo_contenedor" => "TIMP-M$sufijo", "peso_kg" => 50], $cuadrilla);

[, $cuerpo] = peticion("GET", "/api-gestion/publico/impacto");
$despues = $cuerpo["datos"] ?? [];
afirmar("Suma 150 kg en total, 100 de ellos reciclables (el vidrio)", [150.0, 100.0], [round($despues["kg_total"] - $antes["kg_total"], 2), round($despues["kg_reciclable"] - $antes["kg_reciclable"], 2)]);
afirmar("Y 100 kg de vidrio evitan 100 x 0,331 = 33,1 kg de CO2 (el mixto no suma)", true, abs(($despues["co2_evitado_kg"] - $antes["co2_evitado_kg"]) - 33.1) < 0.15);
$vidrio = array_column($despues["por_tipo"], null, "tipo")["Vidrio"];
afirmar("El vidrio figura con su factor 0,331", [0.331, true], [$vidrio["factor"], $vidrio["co2_evitado_kg"] > 0]);
[, $cuerpo] = peticion("GET", "/api-gestion/estadisticas/impacto", null, $admin);
$d = $cuerpo["datos"] ?? [];
$mesActual = date("Y-m");
$mes = array_column($d["por_mes"] ?? [], null, "mes")[$mesActual] ?? [];
afirmar("Reportes: el total coincide con el publico y hay una fila para el mes actual", [true, true], [abs($d["kg_total"] - $despues["kg_total"]) < 0.01 && abs($d["co2_evitado_kg"] - $despues["co2_evitado_kg"]) < 0.01, ($mes["kg_total"] ?? 0) >= 150]);
afirmar("Reportes: los meses salen ordenados y su suma da el total", [true, true], [
    array_column($d["por_mes"], "mes") === (function () use ($d) { $m = array_column($d["por_mes"], "mes"); sort($m); return $m; })(),
    abs(array_sum(array_column($d["por_mes"], "kg_total")) - $d["kg_total"]) < 0.05]);
[, $cuerpo] = peticion("GET", "/api-gestion/estadisticas/impacto?desde=" . date("Y-m-d") . "&hasta=" . date("Y-m-d"), null, $admin);
afirmar("Un periodo de solo hoy trae lo de esta prueba (150 kg o mas)", true, ($cuerpo["datos"]["kg_total"] ?? 0) >= 150);

titulo("37 ter. Impacto ambiental: freno por IP y limpieza");

$insertarPedido = $pdo->prepare("INSERT INTO limite_peticion (clave) VALUES (?)");
foreach (["127.0.0.1", "::1"] as $ip) {
    for ($i = 0; $i < 125; $i++) {
        $insertarPedido->execute(["impacto-publico:$ip"]);
    }
}
[$c] = peticion("GET", "/api-gestion/publico/impacto");
afirmar("Pasado el limite de la IP (120 por hora) el impacto publico responde 429", 429, $c);
$pdo->exec("DELETE FROM limite_peticion WHERE clave LIKE 'impacto-publico:%'");
[$c] = peticion("GET", "/api-gestion/publico/impacto");
afirmar("Sin los pedidos acumulados vuelve a responder 200", 200, $c);

$pdo->exec("DELETE FROM recoleccion WHERE id_camion IN (SELECT id_camion FROM camion WHERE matricula LIKE 'TIMP-%')");
$pdo->exec("DELETE FROM camion WHERE matricula LIKE 'TIMP-%'");
$pdo->exec("DELETE FROM ruta WHERE nombre LIKE 'TIMP %'");
$pdo->exec("DELETE FROM contenedor WHERE codigo LIKE 'TIMP-%'");
$pdo->exec("DELETE FROM limite_peticion WHERE clave LIKE 'impacto-publico:%'");
afirmar("No quedan camiones, rutas ni contenedores de prueba del impacto", [0, 0, 0], [
    (int) $pdo->query("SELECT COUNT(*) FROM camion WHERE matricula LIKE 'TIMP-%'")->fetchColumn(),
    (int) $pdo->query("SELECT COUNT(*) FROM ruta WHERE nombre LIKE 'TIMP %'")->fetchColumn(),
    (int) $pdo->query("SELECT COUNT(*) FROM contenedor WHERE codigo LIKE 'TIMP-%'")->fetchColumn(),
]);
