<?php

// Modo oscuro y pulido visual (extras, parte 22): contraste AA (WCAG) calculado en las dos paletas con los colores reales de css/base.css,
// tokens definidos, el interruptor y que cada pantalla cargue tema.js en el <head>.

titulo("45. Tema claro y oscuro: contraste AA");

$base = (string) file_get_contents(__DIR__ . "/../frontend-usuarios/css/base.css");

function tokensDe(string $bloque): array
{
    preg_match_all('/--([a-z0-9-]+)\s*:\s*(#[0-9a-fA-F]{6})\b/', $bloque, $m, PREG_SET_ORDER);
    $t = [];
    foreach ($m as $x) $t[$x[1]] = strtolower($x[2]);
    return $t;
}
function bloqueDe(string $css, string $inicio): string
{
    $i = strpos($css, $inicio);
    if ($i === false) return "";
    $abre = strpos($css, "{", $i);
    $nivel = 0;
    for ($k = $abre; $k < strlen($css); $k++) {
        if ($css[$k] === "{") $nivel++;
        if ($css[$k] === "}" && --$nivel === 0) return substr($css, $abre + 1, $k - $abre - 1);
    }
    return "";
}
function luminancia(string $hex): float
{
    $c = array_map(function ($p) {
        $v = hexdec($p) / 255;
        return $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
    }, str_split(ltrim($hex, "#"), 2));
    return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
}
function contraste(string $a, string $b): float
{
    [$x, $y] = [luminancia($a), luminancia($b)];
    return (max($x, $y) + 0.05) / (min($x, $y) + 0.05);
}

$claro = tokensDe(bloqueDe($base, ":root {"));
$oscuroSistema = tokensDe(bloqueDe(bloqueDe($base, "@media (prefers-color-scheme: dark)"), ':root:not([data-tema="claro"])'));
$oscuroForzado = tokensDe(bloqueDe($base, ':root[data-tema="oscuro"]'));
$oscuro = array_merge($claro, $oscuroSistema);

afirmar("Hay paleta clara, oscura por sistema y oscura forzada, y las dos oscuras son iguales", [true, true, true], [count($claro) > 25, count($oscuroSistema) > 25, $oscuroSistema === $oscuroForzado]);

$pares = [
    ["tinta", "hueso"], ["tinta", "superficie"], ["tinta-suave", "superficie"], ["tinta-suave", "hueso"], ["tinta-tenue", "superficie"], ["tinta-tenue", "hueso"],
    ["verde", "superficie"], ["verde-oscuro", "superficie"], ["verde-oscuro", "verde-agua"], ["verde", "verde-agua"],
    ["blanco", "accion"], ["blanco", "accion-hover"], ["blanco", "barra"], ["blanco", "peligro"], ["blanco", "peligro-hover"],
    ["ok-texto", "ok-fondo"], ["alerta-texto", "alerta-fondo"], ["error-texto", "error-fondo"], ["neutro-texto", "neutro-fondo"], ["azul-texto", "azul-fondo"],
    ["error-texto", "superficie"], ["ok-texto", "superficie"], ["alerta-texto", "superficie"], ["tinta", "mapa-fondo"], ["blanco", "pie"],
];
foreach (["claro" => $claro, "oscuro" => $oscuro] as $nombre => $paleta) {
    $paleta += ["blanco" => "#ffffff"];
    $paleta["blanco"] = "#ffffff";
    $falla = [];
    foreach ($pares as [$texto, $fondo]) {
        if (!isset($paleta[$texto], $paleta[$fondo])) { $falla[] = "$texto/$fondo: sin definir"; continue; }
        $r = contraste($paleta[$texto], $paleta[$fondo]);
        if ($r < 4.5) $falla[] = "$texto sobre $fondo = " . round($r, 2);
    }
    afirmar("Paleta $nombre: " . count($pares) . " combinaciones de texto y fondo con contraste de al menos 4,5:1 (AA)", [], $falla);
}
afirmar("Cuenta a mano del contraste: blanco sobre negro = 21:1 y negro sobre negro = 1:1", [21.0, 1.0], [round(contraste("#ffffff", "#000000"), 2), round(contraste("#000000", "#000000"), 2)]);
afirmar("El texto del menu lateral (#bcd6c8) sobre la barra tambien pasa en las dos paletas", [true, true], [contraste("#bcd6c8", $claro["barra"]) >= 4.5, contraste("#bcd6c8", $oscuro["barra"]) >= 4.5]);

titulo("45 bis. Tema: tokens definidos, interruptor y paginas");

$css = [];
foreach (["frontend-usuarios/css/base.css", "frontend-usuarios/css/styles.css", "frontend-usuarios/css/acceso.css", "frontend-usuarios/css/reportes.css", "landing/css/base.css", "landing/css/styles.css"] as $a) {
    $css[$a] = (string) file_get_contents(__DIR__ . "/../$a");
}
preg_match_all('/--([a-z0-9-]+)\s*:/', $css["frontend-usuarios/css/base.css"], $definidos);
$definidos = array_unique($definidos[1]);
$locales = [];
foreach ($css as $archivo => $texto) {
    if (str_contains($archivo, "base.css")) continue;
    preg_match_all('/--([a-z0-9-]+)\s*:/', $texto, $d);
    foreach ($d[1] as $n) $locales[$n] = true;
}
$sinDefinir = [];
foreach ($css as $archivo => $texto) {
    preg_match_all('/var\(--([a-z0-9-]+)/', $texto, $usos);
    foreach (array_unique($usos[1]) as $uso) {
        if (!in_array($uso, $definidos, true) && !isset($locales[$uso])) $sinDefinir[] = "$archivo: --$uso";
    }
}
afirmar("Todos los colores y medidas que usan los estilos (var(--x)) estan definidos", [], $sinDefinir);
$fondosBlancos = [];
foreach ($css as $archivo => $texto) {
    if (preg_match('/background(-color)?:\s*var\(--blanco\)/', $texto)) $fondosBlancos[] = $archivo;
}
afirmar("Ningun fondo usa el blanco fijo: las superficies usan --superficie, que cambia con el tema", [], $fondosBlancos);
afirmar("Los dos base.css siguen siendo identicos", true, $css["frontend-usuarios/css/base.css"] === $css["landing/css/base.css"]);

$tema = (string) file_get_contents(__DIR__ . "/../frontend-usuarios/js/tema.js");
afirmar("El interruptor tiene tres estados (automatico, oscuro, claro) y no depende de localStorage para andar", [true, true, true, true], [
    str_contains($tema, '"auto", "oscuro", "claro"'), str_contains($tema, "data-tema"),
    preg_match_all('/try \{/', $tema) === 2 && substr_count($tema, 'window.localStorage.') === 3, str_contains($tema, "catch (e)"),
]);
afirmar("El boton del tema tiene nombre accesible y no se imprime", [true, true], [str_contains($tema, "aria-label"), str_contains($css["frontend-usuarios/css/base.css"], "@media print { .boton-tema { display: none !important; } }")]);

$sinTema = [];
foreach (array_merge(glob(__DIR__ . "/../frontend-usuarios/*.html"), glob(__DIR__ . "/../landing/*.html"), glob(__DIR__ . "/../landing/errores/*.html")) as $pagina) {
    $html = (string) file_get_contents($pagina);
    $cabeza = substr($html, 0, (int) strpos($html, "</head>"));
    if (!str_contains($cabeza, "js/tema.js")) $sinTema[] = basename(dirname($pagina)) . "/" . basename($pagina);
}
afirmar("Todas las pantallas cargan tema.js en el <head> (el tema se aplica antes de pintar)", [], $sinTema);
