<?php

// Accesibilidad de la estructura de cada pagina (parte 10): lo que se puede comprobar leyendo el HTML.
// Lo dinamico (contraste, tamanos, titulos de lo que arma el JavaScript) se audito en el navegador; ver
// programacion-full-stack/ampliacion-cierre-3ra.md.

titulo("20. Accesibilidad: estructura de cada pagina");

$paginas = array_merge(glob(__DIR__ . "/../frontend-usuarios/*.html"), [__DIR__ . "/../landing/index.html"]);
$problemas = [];
foreach ($paginas as $ruta) {
    $nombre = basename(dirname($ruta)) . "/" . basename($ruta);
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTMLFile($ruta, LIBXML_NOWARNING | LIBXML_NOERROR);
    libxml_clear_errors();
    $xp = new DOMXPath($doc);

    if (!$xp->query("//html[@lang]")->length) $problemas[] = "$nombre: sin lang";
    if (trim((string) $xp->evaluate("string(//title)")) === "") $problemas[] = "$nombre: sin title";
    if ($xp->query("//h1")->length !== 1) $problemas[] = "$nombre: h1 = " . $xp->query("//h1")->length;
    if ($xp->query("//main")->length !== 1) $problemas[] = "$nombre: main = " . $xp->query("//main")->length;
    if (!$xp->query("//meta[@name='viewport']")->length) $problemas[] = "$nombre: sin viewport";
    foreach ($xp->query("//img[not(@alt)]") as $img) $problemas[] = "$nombre: img sin alt";

    $conMenu = $xp->query("//*[@id='sidebar']")->length > 0;
    $esAcceso = $xp->query("//*[contains(@class,'acceso-')]")->length > 0;
    if (($conMenu || $nombre === "landing/index.html") && !$xp->query("//a[contains(@class,'saltar-contenido')]")->length) {
        $problemas[] = "$nombre: sin enlace para saltar al contenido";
    }

    foreach ($xp->query("//input[not(@type='hidden' or @type='submit' or @type='button' or @type='reset')] | //select | //textarea") as $control) {
        $id = $control->getAttribute("id");
        $tieneEtiqueta = $control->hasAttribute("aria-label") || $control->hasAttribute("aria-labelledby")
            || ($id !== "" && $xp->query("//label[@for='" . $id . "']")->length)
            || $xp->query("ancestor::label", $control)->length;
        if (!$tieneEtiqueta) {
            $problemas[] = "$nombre: control sin etiqueta (" . ($control->getAttribute("name") ?: $id ?: $control->nodeName) . ")";
        }
    }
    foreach ($xp->query("//button[not(normalize-space()) and not(@aria-label) and not(@aria-labelledby) and not(@title)]") as $boton) {
        $problemas[] = "$nombre: boton sin nombre";
    }
    $ids = [];
    foreach ($xp->query("//*[@id]") as $conId) {
        $ids[$conId->getAttribute("id")] = ($ids[$conId->getAttribute("id")] ?? 0) + 1;
    }
    foreach ($ids as $id => $veces) {
        if ($veces > 1) $problemas[] = "$nombre: id repetido '$id'";
    }
}
afirmar("Las " . count($paginas) . " paginas cumplen lang, title, un h1, un main, viewport, alt, etiquetas, nombres y ids unicos", [], $problemas);

afirmar("El foco visible esta en la base compartida y el movimiento reducido en los estilos del panel", [true, true], [
    str_contains((string) file_get_contents(__DIR__ . "/../frontend-usuarios/css/base.css"), ":focus-visible"),
    str_contains((string) file_get_contents(__DIR__ . "/../frontend-usuarios/css/styles.css"), "prefers-reduced-motion"),
]);
afirmar("La landing respeta el movimiento reducido", true, str_contains((string) file_get_contents(__DIR__ . "/../landing/css/styles.css"), "prefers-reduced-motion"));
