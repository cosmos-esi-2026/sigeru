<?php

// Reporte en PDF (extras, parte 20): el generador de PDF propio y el armado del reporte. Se abre el PDF generado y se lee su texto
// (los flujos van comprimidos con zlib) para comprobar tildes, cifras y paginacion. Clases puras: sin base ni servidor.

require_once __DIR__ . "/../api-gestion/models/ReportePdf.php";
require_once __DIR__ . "/ayuda-pdf.php";

titulo("41. PDF propio: estructura, tildes y paginacion");

$p = new PdfSimple("Pie izquierdo", "Pie central", "Prueba");
$p->paginaNueva();
$p->titulo("Ñandú, ¿qué hay? Café (con) \ barra");
$p->parrafo("Señor Núñez: la ñ, las tildes áéíóú ÁÉÍÓÚ, la ü de pingüino y los signos ¿? ¡! llegan bien.");
$pdf = $p->salida();
afirmar("El PDF empieza con la cabecera y termina con %%EOF", [true, true], [str_starts_with($pdf, "%PDF-1.4"), str_ends_with($pdf, "%%EOF\n")]);
$texto = pdfTodoElTexto($pdf);
afirmar("La enie, las tildes, la dieresis y los signos de apertura salen bien", [true, true, true, true], [
    str_contains($texto, "Ñandú, ¿qué hay?"), str_contains($texto, "áéíóú ÁÉÍÓÚ"), str_contains($texto, "pingüino"), str_contains($texto, "¿? ¡!"),
]);
afirmar("Los parentesis y la barra invertida se escapan y vuelven iguales", true, str_contains($texto, "Café (con) \ barra"));
afirmar("El pie dice «Página 1 de 1»", true, str_contains($texto, "Página 1 de 1"));

// Tabla de xref: cada desplazamiento apunta al "N 0 obj" que corresponde.
preg_match('/startxref\n(\d+)\n/', $pdf, $sx);
$xref = substr($pdf, (int) $sx[1]);
preg_match_all('/(\d{10}) 00000 n /', $xref, $offs);
$bien = true;
foreach ($offs[1] as $n => $off) {
    $bien = $bien && str_starts_with(substr($pdf, (int) $off), ($n + 1) . " 0 obj");
}
afirmar("La tabla de referencias apunta a cada objeto (" . count($offs[1]) . " objetos)", true, $bien && count($offs[1]) >= 6);

titulo("41 bis. PDF propio: medidas, saltos de linea y paginas");

$q = new PdfSimple("a", "b");
afirmar("Medidas de Helvetica: «AAA» a 10 pt = 3 x 667 x 10 / 1000", 20.01, round($q->anchoDeTexto("AAA", 10), 2));
afirmar("«iii» a 10 pt = 6,66 y en negrita «A» = 7,22 (a 10 pt)", [6.66, 7.22], [round($q->anchoDeTexto("iii", 10), 2), round($q->anchoDeTexto("A", 10, true), 2)]);
afirmar("Una letra acentuada mide lo mismo que su letra", true, $q->anchoDeTexto("é", 10) === $q->anchoDeTexto("e", 10) && $q->anchoDeTexto("Ñ", 10) === $q->anchoDeTexto("N", 10));
$lineas = $q->lineas("Esta es una frase bastante larga que tiene que partirse en varias lineas para entrar en el ancho", 120, 10);
afirmar("El salto de linea parte la frase y ninguna linea pasa del ancho", [true, true], [count($lineas) >= 4, count(array_filter($lineas, fn($l) => $q->anchoDeTexto($l, 10) > 120.01)) === 0]);
$largas = $q->lineas("Anticonstitucionalisimamente", 60, 10);
afirmar("Una palabra que no entra se corta, sin perder letras", [true, true], [count($largas) >= 2, implode("", $largas) === "Anticonstitucionalisimamente"]);
afirmar("Un texto vacio da una linea vacia y los saltos de linea se respetan", [[""], ["uno", "dos"]], [$q->lineas("", 100, 10), $q->lineas("uno\ndos", 100, 10)]);

$g = new PdfSimple("Pie izq", "Pie centro", "Prueba larga");
$g->paginaNueva();
$g->titulo("Tabla larga");
$filas = [];
for ($i = 1; $i <= 150; $i++) {
    $filas[] = ["Fila $i", "Una descripcion un poco larga para la fila numero $i, con acentos: canción, año", (string) ($i * 3)];
}
$g->tabla(["Nombre", "Descripción", "Valor"], $filas, [1.5, 5, 1], ["l", "l", "r"]);
$grande = $g->salida();
$paginas = pdfTextos($grande);
$n = count($paginas);
afirmar("150 filas ocupan varias paginas y /Count coincide con las paginas reales", [true, true], [$n >= 4, str_contains($grande, "/Count $n ")]);
$ultimo = pdfTodoElTexto($grande);
afirmar("Cada pagina lleva «Página X de N» (la primera y la ultima)", [true, true], [str_contains($ultimo, "Página 1 de $n"), str_contains($ultimo, "Página $n de $n")]);
afirmar("El encabezado de la tabla se repite en cada pagina", $n, count(array_filter($paginas, fn($f) => in_array("Descripción", array_column($f, "texto"), true))));
afirmar("Estan las 150 filas, ninguna cortada entre paginas ni repetida", 150, preg_match_all('/Fila \d+/', $ultimo));
$fuera = 0;
foreach ($paginas as $f) {
    foreach ($f as $t) {
        if ($t["y"] < 40 || $t["y"] > 842 || $t["x"] < 40 || $t["x"] > 560) $fuera++;
    }
}
afirmar("Ningun texto queda fuera de la hoja ni de los margenes", 0, $fuera);

titulo("42. Reporte en PDF: cifras, formato y topes");

afirmar("Numeros como en la pantalla (punto de miles, coma decimal, sin ceros de mas)", ["1.308,85", "37,7", "4", "0", "-", "12.000"], [
    ReportePdf::numero(1308.85, 2), ReportePdf::numero(37.7, 1), ReportePdf::numero(4.0, 1), ReportePdf::numero(0, 2), ReportePdf::numero(null), ReportePdf::numero(12000, 0)]);
afirmar("Duraciones como en la pantalla: menos de 48 h en horas, despues en dias", ["47,5 h", "2,0 días", "3,5 días", "-"], [
    ReportePdf::duracion(47.5), ReportePdf::duracion(48), ReportePdf::duracion(84), ReportePdf::duracion(null)]);
afirmar("Periodo en texto", ["Del 01/10/2026 al 19/10/2026", "Desde el 01/10/2026", "Hasta el 19/10/2026", "Todo el historial"], [
    ReportePdf::periodoTexto(["desde" => "2026-10-01", "hasta" => "2026-10-19"]), ReportePdf::periodoTexto(["desde" => "2026-10-01", "hasta" => null]),
    ReportePdf::periodoTexto(["desde" => null, "hasta" => "2026-10-19"]), ReportePdf::periodoTexto([])]);
afirmar("Singular y plural", ["1 calificación", "2 calificaciones", "0 calificaciones"], [
    ReportePdf::plural(1, "calificación", "calificaciones"), ReportePdf::plural(2, "calificación", "calificaciones"), ReportePdf::plural(0, "calificación", "calificaciones")]);

$detalle = [];
for ($i = 1; $i <= 150; $i++) {
    $detalle[] = ["numero" => sprintf("INC-%04d", $i), "id_incidencia" => $i, "fecha_reporte" => "2026-10-0" . (($i % 9) + 1) . " 10:00:00", "contenedor" => "CNT-001", "direccion" => "Av. Italia",
                  "zona" => "Cordón", "tipo_problema" => "desbordado", "prioridad" => "alta", "estado" => $i % 2 ? "abierta" : "resuelta", "fecha_resolucion" => null, "horas_resolucion" => $i % 2 ? null : 30.0];
}
$llenos = [];
for ($i = 1; $i <= 30; $i++) {
    $llenos[] = ["id_contenedor" => $i, "codigo" => sprintf("LLENO-%02d", $i), "direccion" => "Calle $i", "capacidad" => 1100, "zona" => "Centro"];
}
$datos = [
    "periodo" => ["desde" => "2026-10-01", "hasta" => "2026-10-19", "zona" => null, "generado_en" => "2026-10-19 12:30:00"],
    "incidencias" => [
        "totales" => ["total" => 150, "abiertas" => 75, "en_curso" => 0, "resueltas" => 75, "descartadas" => 0, "pendientes" => 75, "cerradas" => 75],
        "por_prioridad" => ["baja" => 0, "media" => 0, "alta" => 150],
        "por_tipo" => [["tipo_problema" => "desbordado", "total" => 150]],
        "por_zona" => [["id_zona" => 1, "zona" => "Cordón", "abiertas" => 75, "en_curso" => 0, "resueltas" => 75, "descartadas" => 0, "total" => 150, "tiempo_medio_horas" => 30.0]],
        "tiempo_resolucion" => ["cantidad" => 75, "medio_horas" => 30.0, "mediano_horas" => 30.0, "minimo_horas" => 30.0, "maximo_horas" => 30.0],
        "top_contenedores" => [["id_contenedor" => 1, "codigo" => "CNT-001", "direccion" => "Av. Italia", "zona" => "Cordón", "total" => 150, "pendientes" => 75, "problema_frecuente" => "desbordado"]],
        "detalle" => $detalle, "detalle_truncado" => false,
    ],
    "satisfaccion" => ["general" => ["incidencias" => 150, "calificaciones" => 1, "promedio" => 4.0, "distribucion" => [1 => 0, 2 => 0, 3 => 0, 4 => 1, 5 => 0]],
        "por_zona" => [["zona" => "Cordón", "incidencias" => 150, "resueltas" => 75, "calificaciones" => 1, "promedio" => 4.0, "tiempo_medio_horas" => 30.0]], "por_cuadrilla" => []],
    "urgencias" => ["indicadores" => ["abiertas" => 75, "alta" => 75, "sin_asignar" => 10, "vencidas" => 5, "criticas" => 3, "contenedores_llenos" => 30, "contenedores_mantenimiento" => 0],
        "incidencias" => [], "contenedores_llenos" => $llenos, "umbral_dias" => 3],
    "impacto" => ["kg_total" => 1308.85, "kg_reciclable" => 492.9, "pct_reciclable" => 37.7, "co2_evitado_kg" => 806.8, "kg_sin_factor" => 0.0, "kg_recibidos_en_centros" => 1026.45,
        "por_tipo" => [["tipo" => "Papel", "reciclable" => true, "kg" => 150.0, "factor" => 3.99, "co2_evitado_kg" => 598.5], ["tipo" => "Mixto", "reciclable" => false, "kg" => 815.95, "factor" => null, "co2_evitado_kg" => null]],
        "nota" => "Estimación: factores de referencia de otro país.", "fuentes" => [["fuente" => "EPA WARM v16", "url" => "https://www.epa.gov/", "tipos" => ["Papel"]]]],
    "logo_svg" => '<svg viewBox="0 0 10 10"><path fill="#115440" d="M0 0L10 0L10 10L0 10Z"/></svg>',
];
$reporte = ReportePdf::armar($datos);
$todo = pdfTodoElTexto($reporte);
$cuantas = count(pdfTextos($reporte));
afirmar("El reporte tiene portada con el titulo y el periodo, y cada seccion", [true, true, true], [
    str_contains($todo, "Reporte de gestión") && str_contains($todo, "Del 01/10/2026 al 19/10/2026") && str_contains($todo, "Generado el 19/10/2026 12:30"),
    str_contains($todo, "1. Resumen") && str_contains($todo, "2. Urgencias") && str_contains($todo, "3. Incidencias del período") && str_contains($todo, "4. Satisfacción del vecino"),
    str_contains($todo, "5. Impacto ambiental (estimación)") && str_contains($todo, "6. Detalle de incidencias"),
]);
$faltan = array_values(array_filter(["Incidencias del período", "75 / 0 / 75 / 0", "30 h (75 resueltas)", "4 (1 calificación)", "1.308,85 kg", "37,7 %", "806,8 kg", "Cordón"], fn($e) => !str_contains($todo, $e)));
afirmar("Las cifras del PDF son las de los datos (150 incidencias, 75 resueltas, 30 h, 4 con 1 calificacion, 1.308,85 kg, 37,7 %, 806,8 kg)", [], $faltan);
afirmar("Tope de filas: el detalle muestra 100 de 150 y avisa; los llenos muestran 25 de 30 y avisan", [true, true, false, true, false], [
    str_contains($todo, "Se muestran las 100 más recientes de 150"), str_contains($todo, "(se muestran los primeros 25)"),
    str_contains($todo, "INC-0101"), str_contains($todo, "INC-0100"), str_contains($todo, "LLENO-26"),
]);
afirmar("Paginacion: varias paginas y el pie «Página X de N» en la primera y la ultima", [true, true, true], [$cuantas >= 3, str_contains($todo, "Página 1 de $cuantas"), str_contains($todo, "Página $cuantas de $cuantas")]);
preg_match_all('/stream\n(.*?)\nendstream/s', $reporte, $fl);
$dibujo = implode("", array_map(fn($f) => (string) @gzuncompress($f), $fl[1]));
afirmar("Con logo el PDF trae el dibujo vectorial; sin logo el reporte sigue y dice SiGeRU", [true, true], [
    str_contains($dibujo, "f*"), str_contains(pdfTodoElTexto(ReportePdf::armar(array_merge($datos, ["logo_svg" => null]))), "SiGeRU"),
]);
$vacio = $datos;
$vacio["incidencias"] = ["totales" => ["total" => 0, "abiertas" => 0, "en_curso" => 0, "resueltas" => 0, "descartadas" => 0, "pendientes" => 0, "cerradas" => 0], "por_prioridad" => ["baja" => 0, "media" => 0, "alta" => 0],
    "por_tipo" => [], "por_zona" => [], "tiempo_resolucion" => ["cantidad" => 0, "medio_horas" => null, "mediano_horas" => null, "minimo_horas" => null, "maximo_horas" => null], "top_contenedores" => [], "detalle" => [], "detalle_truncado" => false];
$vacio["satisfaccion"] = ["general" => ["incidencias" => 0, "calificaciones" => 0, "promedio" => null, "distribucion" => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0]], "por_zona" => [], "por_cuadrilla" => []];
$vacio["urgencias"]["contenedores_llenos"] = [];
$textoVacio = pdfTodoElTexto(ReportePdf::armar($vacio));
afirmar("Un periodo sin incidencias ni calificaciones no rompe el reporte", [true, true, true], [
    str_contains($textoVacio, "No hubo incidencias en el período"), str_contains($textoVacio, "Sin calificaciones en el período"), str_contains($textoVacio, "Página 1 de"),
]);
