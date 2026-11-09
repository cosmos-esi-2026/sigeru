<?php

// Cedula de identidad de la cuadrilla (30/10/2026): validacion, formato, esquema y pantallas de acceso. Se incluye desde unitarias.php.
require_once __DIR__ . "/../api-usuarios/core/Validacion.php";

titulo("9. Cedula: validacion y formato");

foreach (["1.234.567-2", "1234567-2", "12345672", " 1234567 2 ", "41000019", "4.100.001-9"] as $buena) {
    afirmar("Cedula valida: «{$buena}»", true, Validacion::cedula($buena));
}
// Las de 7 digitos en total (6 + verificador) se completan con un cero adelante: para cada cuerpo de 6 digitos hay un solo verificador.
$validas7 = [];
for ($dv = 0; $dv < 10; $dv++) {
    if (Validacion::cedula("123456" . $dv)) {
        $validas7[] = "123456" . $dv;
    }
}
afirmar("Una cedula de 7 digitos tiene un solo verificador valido", 1, count($validas7));
foreach (["1234567-3" => "verificador mal", "1234567" => "faltaria un digito", "123456789" => "demasiado larga", "" => "vacia", "abc" => "letras", "12345" => "muy corta", "1.234.56-72a" => "con letras"] as $mala => $motivo) {
    afirmar("Cedula invalida ($motivo): «{$mala}»", false, Validacion::cedula($mala));
}
afirmar("Un valor que no es texto no es cedula", false, Validacion::cedula(["41000019"]));
afirmar("Null tampoco", false, Validacion::cedula(null));
afirmar("Un entero con una cedula valida sirve", true, Validacion::cedula(41000019));
afirmar("Normalizar deja solo los digitos", "41000019", Validacion::normalizarCedula("4.100.001-9"));
afirmar("Normalizar no inventa digitos", "", Validacion::normalizarCedula("abc"));
afirmar("Sin digitos, soloDigitosCedula devuelve null", null, Validacion::soloDigitosCedula("--"));

// Para cada cuerpo de 7 digitos hay exactamente un verificador correcto (el control del esquema de pesos 2987634).
$contadas = 0;
foreach ([4100001, 4100002, 3000000, 9999999, 1000000] as $cuerpo) {
    $buenas = 0;
    for ($dv = 0; $dv < 10; $dv++) {
        $buenas += Validacion::cedula($cuerpo . $dv) ? 1 : 0;
    }
    $contadas += $buenas === 1 ? 1 : 0;
}
afirmar("Cada cuerpo tiene un solo verificador valido", 5, $contadas);

titulo("9b. Cedula: esquema, migracion, semillas y pantallas");

$raiz = __DIR__ . "/..";
$schema = (string) file_get_contents("$raiz/database/schema.sql");
afirmar("schema.sql: usuario.cedula es unica", true, str_contains($schema, "UNIQUE KEY uq_usuario_cedula (cedula)"));
afirmar("schema.sql: solicitud_registro.cedula tiene indice", true, str_contains($schema, "idx_solicitud_cedula (cedula)"));
afirmar("schema.sql registra la migracion 022", true, str_contains($schema, "'022-cedula-cuadrilla.sql'"));
$migracion = (string) file_get_contents("$raiz/database/migraciones/022-cedula-cuadrilla.sql");
afirmar("La migracion 022 se puede repetir (guardas con information_schema)", 4, substr_count($migracion, "information_schema"));
afirmar("La migracion 022 no usa IF NOT EXISTS de MariaDB", false, (bool) preg_match('/ADD (COLUMN|INDEX) IF NOT EXISTS/i', $migracion));

foreach (["datos_prueba.sql", "datos_defensa.sql"] as $semilla) {
    $texto = (string) file_get_contents("$raiz/database/$semilla");
    preg_match_all("/WHEN \d+ THEN '(\d{7,8})'/", $texto, $encontradas);
    afirmar("$semilla: las cuadrillas del seed traen cedula", true, count($encontradas[1]) >= 2);
    $invalidas = array_filter($encontradas[1], fn($c) => !Validacion::cedula($c));
    afirmar("$semilla: todas las cedulas del seed son validas", [], array_values($invalidas));
}

$front = "$raiz/frontend-usuarios";
$login = (string) file_get_contents("$front/login.html");
afirmar("El login tiene la pestania de cuadrilla (cedula)", true, str_contains($login, 'data-modo-acceso="cedula"') && str_contains($login, 'data-modo-acceso="correo"'));
afirmar("El login ya no usa una imagen fija de fondo", false, str_contains($login, "acceso-poster") || str_contains($login, "acceso-video"));
afirmar("El login anima la calle y tiene el boton de pausa", true, str_contains($login, 'class="acceso-calle"') && str_contains($login, 'class="acceso-pausa"'));
$auth = (string) file_get_contents("$front/js/auth.js");
afirmar("El login manda cedula (no email) en la pestania de cuadrilla", true, str_contains($auth, 'nombre: "cedula"') && str_contains($auth, "cuerpo[MODOS[modo].nombre]"));
$pedido = (string) file_get_contents("$front/acceso-cuadrilla.html");
afirmar("El pedido de cuadrilla pide la cedula, obligatoria", true, (bool) preg_match('/name="cedula"[^>]*required/', $pedido));
afirmar("El pedido de cuadrilla manda la cedula solo con digitos", true, str_contains((string) file_get_contents("$front/js/acceso-cuadrilla.js"), "cedula: soloDigitos("));
$usuarios = (string) file_get_contents("$front/usuarios.html");
afirmar("Usuarios del administrador tiene el campo de cedula", true, str_contains($usuarios, 'name="cedula"') && str_contains($usuarios, "js/cedula.js"));
$solicitudes = (string) file_get_contents("$front/solicitudes.html");
afirmar("Solicitudes del administrador muestra y confirma la cedula", true, str_contains($solicitudes, 'name="cedula"') && str_contains($solicitudes, "js/cedula.js"));

foreach (["login.html", "registro.html", "acceso-cuadrilla.html"] as $pagina) {
    $html = (string) file_get_contents("$front/$pagina");
    afirmar("$pagina: el panel animado tiene boton de pausa y el script que lo maneja", true, str_contains($html, 'class="acceso-pausa"') && str_contains($html, "js/acceso-escena.js"));
}
$css = (string) file_get_contents("$front/css/acceso.css");
afirmar("acceso.css respeta «reducir movimiento» en todo lo que se anima", true, str_contains($css, "prefers-reduced-motion: reduce") && str_contains($css, ".ruta-trazo") && str_contains($css, ".reclamo-pasos"));
afirmar("acceso.css: el fondo del panel no depende de --verde-oscuro (se aclara en modo oscuro)", false, (bool) preg_match('/\.acceso-presentacion \{[^}]*var\(--verde-oscuro\)/s', $css));
