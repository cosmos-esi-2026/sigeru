<?php

// Parte de correr-tests.php. Paginas de error propias (extras, parte 22): una direccion inexistente muestra la 404 propia,
// la lista blanca muestra la 403 propia, y las APIs siguen contestando sus errores en JSON.

titulo("46h. Paginas de error amables");

$pedirCrudo = function (string $ruta) {
    $ch = curl_init(BASE . $ruta);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true]);
    $cuerpo = (string) curl_exec($ch);
    $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $tipo = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    return [$codigo, $tipo, $cuerpo];
};
[$c, $t, $b] = $pedirCrudo("/landing/esta-direccion-no-existe");
afirmar("Una direccion inexistente: 404 con la pagina propia (HTML, en espanol, sin datos del servidor)", [404, true, true, false], [$c, str_contains($t, "text/html"), str_contains($b, "No encontramos esa página"), (bool) preg_match('/Apache|Server at|PHP\//i', $b)]);
[$c, , $b] = $pedirCrudo("/frontend-usuarios/pantalla-que-no-existe.html");
afirmar("Tambien en el panel: 404 propia", [404, true], [$c, str_contains($b, "No encontramos esa página")]);
[$c, , $b] = $pedirCrudo("/tests/correr-tests.php");
afirmar("Una carpeta fuera de la lista blanca (tests): 403 con la pagina propia y sin mostrar su contenido", [403, true, false], [$c, str_contains($b, "No tenés acceso"), str_contains($b, "<?php")]);
[$c, , $b] = $pedirCrudo("/database/schema.sql");
afirmar("Los .sql de la base no se sirven: 403 propia", [403, true, false], [$c, str_contains($b, "No tenés acceso"), str_contains($b, "CREATE TABLE")]);
[$c, $t, $b] = $pedirCrudo("/api-gestion/recurso-inexistente");
afirmar("Las APIs siguen contestando sus errores en JSON (no se reemplazan por la pagina propia)", [404, true, true], [$c, str_contains($t, "application/json"), str_contains($b, '"ok":false')]);
foreach (["403", "404", "500"] as $cod) {
    [$c, , $b] = $pedirCrudo("/landing/errores/$cod.html");
    afirmar("La pagina de error $cod se sirve (200) y trae su codigo", [200, true], [$c, str_contains($b, "Error $cod")]);
}
[$c, , $b] = $pedirCrudo("/landing/img/compartir.png");
afirmar("La imagen para compartir se sirve (200, PNG)", [200, "\x89PNG"], [$c, substr($b, 1, 3) === "PNG" ? "\x89PNG" : substr($b, 0, 4)]);
