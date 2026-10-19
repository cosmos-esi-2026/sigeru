<?php

// Guia "donde tiro esto" (parte 19): la pagina y su JSON se sirven sin sesion y el JSON es el mismo del repo.
// Se ejecuta dentro de correr-tests.php.

titulo("22g. Guia de clasificacion servida por Apache");

$pedir = function (string $ruta) {
    $ch = curl_init(BASE . $ruta);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true]);
    $cuerpo = curl_exec($ch);
    $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$codigo, (string) $cuerpo];
};

[$codigoPagina, $pagina] = $pedir("/landing/donde-tiro.html");
afirmar("La pagina de la guia se lee sin sesion (200)", [200, true], [$codigoPagina, str_contains($pagina, "¿Dónde tiro esto?")]);

[$codigoJson, $json] = $pedir("/landing/data/residuos.json");
$remoto = json_decode($json, true);
$local = json_decode((string) file_get_contents(__DIR__ . "/../landing/data/residuos.json"), true);
afirmar("El JSON de residuos se sirve (200) y es el del repo", [200, true], [$codigoJson, is_array($remoto) && $remoto === $local]);
afirmar("La guia no depende de ninguna API (no hay llamadas a /api-)", false, str_contains((string) file_get_contents(__DIR__ . "/../landing/js/donde-tiro.js"), "api-"));
