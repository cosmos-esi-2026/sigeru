<?php

// 03/11/2026: dos defectos que aparecieron al ejecutar a mano los casos del plan de testing (Plan_de_Testing_SiGeRU, DEF-16 y DEF-17).
// No hay Node en el pendrive, asi que esto mira el codigo fuente; el comportamiento real del boton y del campo se probo en el navegador.

titulo("54. Capacidad del contenedor (DEF-16) y descarga del informe en CSV (DEF-17)");

$raiz = realpath(__DIR__ . "/..");
$front = "$raiz/frontend-usuarios";

// --- DEF-16: un campo numerico del navegador descarta las letras en silencio y la capacidad se guardaba vacia sin avisar.
$contenedores = file_get_contents("$front/contenedores.html");
preg_match('/<input[^>]*name="capacidad"[^>]*>/', $contenedores, $campo);
$campo = $campo[0] ?? "";
afirmar("El campo capacidad existe en el formulario de contenedores", true, $campo !== "");
afirmar("Ya no es de tipo number (el navegador descartaba las letras sin avisar)", false, str_contains($campo, 'type="number"'));
afirmar("Es de texto con teclado numerico y acepta solo digitos", true,
    str_contains($campo, 'type="text"') && str_contains($campo, 'inputmode="numeric"') && str_contains($campo, 'pattern="[0-9]+"'));

$formularios = file_get_contents("$front/js/formularios.js");
afirmar("Los digitos que no cumplen el patron tienen un mensaje propio", true,
    str_contains($formularios, "patternMismatch") && str_contains($formularios, "Escribí solo números enteros"));

// --- DEF-17: el informe tenia solo «Imprimir».
$reportes = file_get_contents("$front/reportes.html");
afirmar("La pantalla Reportes tiene el boton para descargar el CSV", true, str_contains($reportes, 'id="btn-csv"'));
afirmar("Y carga csv.js antes de reportes.js", true,
    strpos($reportes, "js/csv.js") !== false && strpos($reportes, "js/csv.js") < strpos($reportes, "js/reportes.js"));

$csv = file_get_contents("$front/js/csv.js");
afirmar("El CSV usa punto y coma y fin de linea de Windows (como espera una planilla en espanol)", true,
    str_contains($csv, 'join(";")') && str_contains($csv, '"\r\n"'));
afirmar("El archivo lleva la marca UTF-8 para que se lean las tildes", true, str_contains($csv, '"﻿"'));
afirmar("Las comillas se duplican y los textos con ; o saltos de linea van entre comillas", true,
    str_contains($csv, 'replace(/"/g, \'""\')') && str_contains($csv, '/[";\r\n]/'));
afirmar("Un texto que empieza con = + - @ no se toma como formula en la planilla", true,
    str_contains($csv, '/^[=+\-@\t\r]/'));

$js = file_get_contents("$front/js/reportes.js");
afirmar("El boton descarga el detalle del informe que esta en pantalla", true,
    str_contains($js, 'getElementById("btn-csv")') && str_contains($js, "informeIncidencias.detalle") && str_contains($js, "descargarCsv("));
afirmar("Avisa si el detalle esta cortado o si no hay incidencias", true,
    str_contains($js, "detalle_truncado") && str_contains($js, "No hay incidencias en este período para descargar"));
afirmar("El boton queda oculto al imprimir (la barra superior no se imprime)", true,
    preg_match('/@media print.*\.topbar[^{]*\{[^}]*display:\s*none/s', file_get_contents("$front/css/reportes.css")) === 1);
