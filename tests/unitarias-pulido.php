<?php

// Pulido (extras, parte 22): paginas de error, imagen para compartir, estados vacios, impresion y revision de textos.

titulo("46. Pulido: errores, imagen para compartir, estados vacios e impresion");

$raiz = __DIR__ . "/..";
$ht = (string) file_get_contents("$raiz/.htaccess");
afirmar("El .htaccess apunta 403, 404 y 500 a paginas propias dentro de la landing (que la lista blanca deja pasar)", [true, true, true], [
    str_contains($ht, "ErrorDocument 403 /sigeru/landing/errores/403.html"), str_contains($ht, "ErrorDocument 404 /sigeru/landing/errores/404.html"), str_contains($ht, "ErrorDocument 500 /sigeru/landing/errores/500.html"),
]);
$problemas = [];
foreach (["403" => "No tenés acceso", "404" => "No encontramos", "500" => "Algo salió mal"] as $codigo => $titulo) {
    $ruta = "$raiz/landing/errores/$codigo.html";
    $html = file_exists($ruta) ? (string) file_get_contents($ruta) : "";
    if (!str_contains($html, "<h1>$titulo") ) $problemas[] = "$codigo: titulo";
    if (!str_contains($html, "Error $codigo")) $problemas[] = "$codigo: codigo";
    if (preg_match('/Apache|PHP|Server at|stack|Exception|\/(?:var|home)\/|[A-Z]:\\|mysql|SELECT /i', $html)) $problemas[] = "$codigo: filtra datos internos";
    if (!str_contains($html, 'href="/sigeru/landing/css/base.css"') || !str_contains($html, "/sigeru/landing/js/tema.js")) $problemas[] = "$codigo: recursos con ruta absoluta";
    if (!str_contains($html, 'lang="es"') || !str_contains($html, "<main") || !str_contains($html, 'role="img"') || !str_contains($html, "<h1>")) $problemas[] = "$codigo: estructura";
    if (!str_contains($html, 'name="robots" content="noindex"')) $problemas[] = "$codigo: se indexaria";
}
afirmar("Las tres paginas de error: titulo y codigo, sin datos internos, con rutas absolutas (se sirven bajo cualquier URL), accesibles y sin indexar", [], $problemas);

$img = @getimagesize("$raiz/landing/img/compartir.png");
afirmar("compartir.png es un PNG de 1200 x 630 (el tamano de las vistas previas)", [1200, 630, IMAGETYPE_PNG], $img ? [$img[0], $img[1], $img[2]] : null);
$og = [];
foreach (["landing/index.html", "frontend-usuarios/login.html"] as $p) {
    $html = (string) file_get_contents("$raiz/$p");
    foreach (["og:title", "og:description", "og:image", "og:image:alt", "og:type", "twitter:card"] as $meta) {
        if (!preg_match('/(?:property|name)="' . preg_quote($meta, "/") . '"\s+content="[^"]+"/', $html)) $og[] = "$p: $meta";
    }
}
afirmar("Las paginas publicas (inicio y login) llevan las metaetiquetas de Open Graph y Twitter con su imagen", [], $og);

$tabla = (string) file_get_contents("$raiz/frontend-usuarios/js/tabla.js");
afirmar("Los estados vacios usan ilustraciones propias (contenedor y lupa), no un icono generico", [true, true, true], [
    str_contains($tabla, "function ilustracionVacia(tipo)"), str_contains($tabla, '"lupa"'), str_contains($tabla, "ilustracionVacia(sinCoincidencias"),
]);
$repQ = (string) file_get_contents("$raiz/frontend-usuarios/css/reportes.css");
afirmar("La impresion de Reportes: hoja apaisada, tabla de ancho fijo, botones de ordenar sin dibujo y siempre sobre fondo blanco (H11-1)", [true, true, true, true], [
    str_contains($repQ, "size: A4 landscape"), str_contains($repQ, "table-layout: fixed"), str_contains($repQ, ".th-orden::after { display: none !important; }"), str_contains($repQ, "background: #fff !important"),
]);
$largos = [];
foreach (glob("$raiz/frontend-usuarios/*.html") as $pagina) {
    preg_match_all('/class="buscador"[^>]*placeholder="([^"]+)"/', (string) file_get_contents($pagina), $m);
    foreach ($m[1] as $ph) if (mb_strlen($ph) > 34) $largos[] = basename($pagina) . ": " . $ph;
}
afirmar("Los placeholders de las busquedas entran en el campo (34 caracteres o menos; H11-3)", [], $largos);
$estilos = (string) file_get_contents("$raiz/frontend-usuarios/css/styles.css");
afirmar("El contenedor de las tablas es relative (los textos solo-lectores no ensanchan la pagina) y las acciones de fila pasan de renglon", [true, true], [
    (bool) preg_match('/\.tabla-scroll \{\s*position: relative;/', $estilos), str_contains($estilos, ".acciones-fila { display: flex; flex-wrap: wrap;"),
]);
