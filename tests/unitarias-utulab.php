<?php

// QR de los contenedores (Proyecto UTU LAB, 3ra entrega, 23/10/2026): sin sesion, el login tiene que devolver al vecino a
// «Informar un problema» con el contenedor del QR, y nunca a un destino fuera de frontend-usuarios (redireccion abierta).
// La logica de Sesion.destinoSeguro corre en el navegador: tests/js/volver.html (24 casos). Aca se fija que el cableado este.

titulo("52. QR de los contenedores: el login vuelve a donde estaba");

$fu = __DIR__ . "/../frontend-usuarios";
$api = (string) file_get_contents("$fu/js/api.js");
$shell = (string) file_get_contents("$fu/js/shell.js");
$auth = (string) file_get_contents("$fu/js/auth.js");

afirmar("api.js tiene destinoSeguro e irAlLogin, y exigir() pasa por irAlLogin", [true, true, true], [
    str_contains($api, "destinoSeguro(texto)"),
    str_contains($api, "irAlLogin()"),
    (bool) preg_match('/exigir\(\)\s*\{\s*if \(!this\.obtener\(\)\)\s*\{\s*this\.irAlLogin\(\);/', $api),
]);
afirmar("destinoSeguro exige una pagina .html de la misma carpeta (sin barras, esquemas ni '..')", true,
    str_contains($api, '/^[a-z0-9-]+\.html(\?[A-Za-z0-9_=&%.\-]*)?$/'));
afirmar("shell.js ya no manda al login a mano: las dos salidas usan Sesion.irAlLogin()", [0, 2], [
    substr_count($shell, 'replace("login.html")'),
    substr_count($shell, "Sesion.irAlLogin();"),
]);
afirmar("auth.js vuelve al destino seguro de ?volver= o, si no hay, al panel", true,
    str_contains($auth, 'Sesion.destinoSeguro(new URLSearchParams(window.location.search).get("volver")) || "dashboard.html"'));
afirmar("informar-problema.js sigue leyendo ?contenedor= (es lo que abre el QR)", true,
    str_contains((string) file_get_contents("$fu/js/informar-problema.js"), 'new URLSearchParams(window.location.search).get("contenedor")'));
afirmar("Existe la pagina de pruebas de la vuelta para el navegador", true, file_exists(__DIR__ . "/js/volver.html"));
$gen = __DIR__ . "/../../generadores/utulab3_qr.py";
afirmar("El generador de etiquetas arma el mismo enlace que lee la pantalla", true,
    !file_exists($gen) || str_contains((string) file_get_contents($gen), "/frontend-usuarios/informar-problema.html?contenedor="));

titulo("52 bis. Hallazgos de la evaluación heurística arreglados (23/10/2026)");

$form = (string) file_get_contents("$fu/informar-problema.html");
$informar = (string) file_get_contents("$fu/js/informar-problema.js");
afirmar("La prioridad del formulario arranca en «Según el problema» (valor vacío) y no en «Media»", [true, false], [
    (bool) preg_match('/<option value="" selected>Según el problema/', $form),
    (bool) preg_match('/<option value="media" selected>/', $form),
]);
afirmar("Si la prioridad queda vacía no se manda, y el servidor aplica la del tipo (desbordado = alta)", [true, "alta", "baja"], [
    str_contains($informar, "prioridad:         formIncidencia.prioridad.value || undefined"),
    FlujoIncidencia::prioridadDeTipo("desbordado"),
    FlujoIncidencia::prioridadDeTipo("residuo_no_permitido"),
]);
afirmar("El ejemplo del código del contenedor tiene la forma de los reales (MVD-...)", [false, true], [
    str_contains($form, 'placeholder="CNT-004"'), str_contains($form, 'placeholder="MVD-172712"'),
]);
afirmar("La confirmación le habla distinto a la cuadrilla (no «se va a asignar a una cuadrilla»)", true,
    str_contains($informar, 'if (Sesion.rol() === "Cuadrilla")') && str_contains($form, 'id="ayuda-confirmacion"'));
