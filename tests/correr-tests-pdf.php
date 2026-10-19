<?php

// Parte de correr-tests.php (se incluye desde alli y usa sus funciones y sus sesiones).
// Reporte en PDF (extras, parte 20): permisos, formato del archivo, cifras iguales a las de la pantalla y descarga en la auditoria.

require_once __DIR__ . "/../api-gestion/models/ReportePdf.php";
require_once __DIR__ . "/ayuda-pdf.php";

titulo("41h. Reporte en PDF: permisos y formato");

$bajarPdf = function (string $consulta, ?string $cookies) {
    $ch = curl_init(BASE . "/api-gestion/reportes/pdf" . $consulta);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true]);
    if ($cookies !== null) {
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookies);
    }
    $crudo = (string) curl_exec($ch);
    $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $cabeza = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    return [$codigo, substr($crudo, 0, $cabeza), substr($crudo, $cabeza)];
};

$obtenidos = [];
foreach (["sin sesion" => null, "vecino" => $vecino, "operario" => $operario, "cuadrilla" => $cuadrilla] as $quien => $cookies) {
    [$obtenidos[$quien]] = $bajarPdf("", $cookies);
}
afirmar("El reporte en PDF es solo del Administrador: sin sesion 401; vecino, operario y cuadrilla 403", ["sin sesion" => 401, "vecino" => 403, "operario" => 403, "cuadrilla" => 403], $obtenidos);
[$codigo] = peticion("POST", "/api-gestion/reportes/pdf", ["x" => 1], $admin);
afirmar("Solo se descarga con GET (POST): 403", 403, $codigo);
foreach (["?desde=ayer", "?hasta=2026-13-45", "?desde=2026-10-19&hasta=2026-10-01", "?zona=abc"] as $malo) {
    [$codigo] = $bajarPdf($malo, $admin);
    afirmar("Filtro invalido ($malo): 400", 400, $codigo);
}
[$codigo] = $bajarPdf("?zona=99999", $admin);
afirmar("Zona que no existe: 404", 404, $codigo);

$antes = (int) $pdo->query("SELECT COUNT(*) FROM auditoria WHERE metodo = 'GET' AND ruta LIKE '/api-gestion/reportes/pdf%'")->fetchColumn();
[$codigo, $cabeceras, $pdfCrudo] = $bajarPdf("", $admin);
afirmar("El administrador lo descarga: 200, application/pdf, adjunto con nombre .pdf, sin cache", [200, true, true, true], [
    $codigo, str_contains(strtolower($cabeceras), "content-type: application/pdf"), (bool) preg_match('/content-disposition: attachment; filename="sigeru-reporte-\d{4}-\d{2}-\d{2}\.pdf"/i', $cabeceras), str_contains(strtolower($cabeceras), "cache-control: no-store"),
]);
afirmar("Es un PDF de verdad: cabecera, %%EOF y varias paginas", [true, true, true], [str_starts_with($pdfCrudo, "%PDF-1.4"), str_ends_with($pdfCrudo, "%%EOF\n"), count(pdfTextos($pdfCrudo)) >= 2]);
$despues = (int) $pdo->query("SELECT COUNT(*) FROM auditoria WHERE metodo = 'GET' AND ruta LIKE '/api-gestion/reportes/pdf%'")->fetchColumn();
$fila = $pdo->query("SELECT rol, codigo FROM auditoria WHERE metodo = 'GET' AND ruta LIKE '/api-gestion/reportes/pdf%' ORDER BY id_auditoria DESC LIMIT 1")->fetch();
afirmar("La descarga queda en la auditoria, con el rol y el codigo 200 (y los intentos negados no suman)", [$antes + 1, "Administrador", 200], [$despues, $fila["rol"] ?? null, (int) ($fila["codigo"] ?? 0)]);

titulo("41i. Reporte en PDF: las cifras son las de la pantalla");

$comparar = function (string $consulta, string $rotulo) use ($bajarPdf, $admin) {
    [, $inc] = peticion("GET", "/api-gestion/estadisticas/incidencias" . $consulta, null, $admin);
    [, $sat] = peticion("GET", "/api-gestion/estadisticas/satisfaccion" . $consulta, null, $admin);
    $impConsulta = preg_replace('/&?zona=\d+/', "", $consulta);
    [, $imp] = peticion("GET", "/api-gestion/estadisticas/impacto" . ($impConsulta === "?" ? "" : $impConsulta), null, $admin);
    [$codigo, , $crudo] = $bajarPdf($consulta, $admin);
    $texto = pdfTodoElTexto($crudo);
    $i = $inc["datos"];
    $esperadas = [
        "Incidencias del período" => ReportePdf::numero($i["totales"]["total"], 0),
        "Abiertas / en curso" => ReportePdf::numero($i["totales"]["abiertas"], 0) . " / " . ReportePdf::numero($i["totales"]["en_curso"], 0) . " / " . ReportePdf::numero($i["totales"]["resueltas"], 0) . " / " . ReportePdf::numero($i["totales"]["descartadas"], 0),
        "Kilos recolectados" => ReportePdf::numero($imp["datos"]["kg_total"], 2) . " kg",
        "CO2 evitado" => ReportePdf::numero($imp["datos"]["co2_evitado_kg"], 1) . " kg",
        "Satisfaccion" => $sat["datos"]["general"]["calificaciones"] > 0 ? ReportePdf::numero($sat["datos"]["general"]["promedio"], 1) . " (" . ReportePdf::plural($sat["datos"]["general"]["calificaciones"], "calificación", "calificaciones") . ")" : "Sin calificaciones en el período",
    ];
    $faltan = [];
    foreach ($esperadas as $etiqueta => $valor) {
        if (!str_contains($texto, $valor)) {
            $faltan[] = "$etiqueta: $valor";
        }
    }
    foreach ($i["por_zona"] as $z) {
        if (!str_contains($texto, $z["zona"])) {
            $faltan[] = "zona " . $z["zona"];
        }
    }
    foreach (array_slice($i["detalle"], 0, 100) as $d) {
        if (!str_contains($texto, $d["numero"])) {
            $faltan[] = "incidencia " . $d["numero"];
        }
    }
    afirmar("$rotulo: el PDF trae las mismas cifras, zonas e incidencias que la pantalla (200)", [200, []], [$codigo, $faltan]);
    return $texto;
};

$todoElHistorial = $comparar("", "Todo el historial");
afirmar("Las tildes salen bien en el archivo real (gestión, período, satisfacción, estimación)", [true, true, true, true], [
    str_contains($todoElHistorial, "Reporte de gestión"), str_contains($todoElHistorial, "Incidencias del período"), str_contains($todoElHistorial, "4. Satisfacción del vecino"), str_contains($todoElHistorial, "(estimación)"),
]);
preg_match_all('/Página (\d+) de (\d+)/', $todoElHistorial, $pie, PREG_SET_ORDER);
afirmar("Paginacion: el pie dice «Página X de N» en todas las paginas y la ultima es la N", true, $pie !== [] && (int) end($pie)[1] === (int) end($pie)[2] && count($pie) === (int) end($pie)[2]);

$periodo = $comparar("?desde=2026-09-01&hasta=2026-09-30", "Septiembre de 2026");
afirmar("El periodo elegido figura en la portada", true, str_contains($periodo, "Del 01/09/2026 al 30/09/2026"));
$zona = $comparar("?zona=2", "Una sola zona");
afirmar("Con una zona elegida, la portada dice cual", true, str_contains($zona, "Zona "));
$vacio = $comparar("?desde=2030-01-01&hasta=2030-01-31", "Un periodo sin datos");
afirmar("Sin incidencias en el periodo el PDF lo dice y no se rompe", true, str_contains($vacio, "No hubo incidencias en el período"));
