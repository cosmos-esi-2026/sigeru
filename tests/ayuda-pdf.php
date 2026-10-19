<?php

// Para las pruebas del PDF: lee el texto de un PDF generado por PdfSimple (sus flujos van comprimidos con zlib).

// Devuelve, por pagina, la lista de ["texto" => utf8, "x" =>, "y" =>] de cada Tj del PDF.
function pdfTextos(string $pdf): array
{
    preg_match_all('/stream\n(.*?)\nendstream/s', $pdf, $flujos);
    $paginas = [];
    foreach ($flujos[1] as $flujo) {
        $contenido = @gzuncompress($flujo);
        if ($contenido === false) {
            continue;
        }
        $filas = [];
        preg_match_all('/([\d.\-]+) ([\d.\-]+) Td \((.*?)\) Tj ET/s', $contenido, $m, PREG_SET_ORDER);
        foreach ($m as $t) {
            $crudo = strtr($t[3], ["\\\\" => "\\", "\(" => "(", "\)" => ")"]);
            $filas[] = ["texto" => mb_convert_encoding($crudo, "UTF-8", "Windows-1252"), "x" => (float) $t[1], "y" => (float) $t[2]];
        }
        $paginas[] = $filas;
    }
    return $paginas;
}
function pdfTodoElTexto(string $pdf): string
{
    return implode("\n", array_map(fn($f) => implode("\n", array_column($f, "texto")), pdfTextos($pdf)));
}
