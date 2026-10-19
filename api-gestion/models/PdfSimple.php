<?php

// Generador de PDF propio, sin dependencias (extras, parte 20). Hace solo lo que el reporte necesita: paginas A4, texto en Helvetica
// (fuente estandar de PDF, no se incrusta), titulos, parrafos con salto de linea, tablas que siguen en la hoja siguiente repitiendo
// el encabezado, un dibujo vectorial (el logo en SVG) y el pie "Pagina X de N". Recibe texto en UTF-8 y lo pasa a Windows-1252
// (la codificacion WinAnsi de PDF), asi las tildes, la enie y los signos de apertura salen bien. Clase pura: no toca la base.
class PdfSimple
{
    public const ANCHO = 595.28;
    public const ALTO = 841.89;
    public const MARGEN = 48.0;
    private const PIE = 52.0;

    // Anchos de Helvetica y Helvetica-Bold (milesimas de em) para los codigos 32 a 126 de WinAnsi.
    private const NORMAL = [278, 278, 355, 556, 556, 889, 667, 191, 333, 333, 389, 584, 278, 333, 278, 278, 556, 556, 556, 556, 556, 556, 556, 556, 556, 556,
        278, 278, 584, 584, 584, 556, 1015, 667, 667, 722, 722, 667, 611, 778, 722, 278, 500, 667, 556, 833, 722, 778, 667, 778, 722, 667, 611, 722, 667, 944,
        667, 667, 611, 278, 278, 278, 469, 556, 333, 556, 556, 500, 556, 556, 278, 556, 556, 222, 222, 500, 222, 833, 556, 556, 556, 556, 333, 500, 278, 556,
        500, 722, 500, 500, 500, 334, 260, 334, 584];
    private const NEGRITA = [278, 333, 474, 556, 556, 889, 722, 238, 333, 333, 389, 584, 278, 333, 278, 278, 556, 556, 556, 556, 556, 556, 556, 556, 556, 556,
        333, 333, 584, 584, 584, 611, 975, 722, 722, 722, 722, 667, 611, 778, 722, 278, 556, 722, 611, 833, 722, 778, 667, 778, 722, 667, 611, 722, 667, 944,
        667, 667, 611, 333, 278, 333, 584, 556, 333, 556, 611, 556, 611, 556, 333, 611, 611, 278, 278, 556, 278, 889, 611, 611, 611, 611, 389, 556, 333, 611,
        556, 778, 556, 556, 500, 389, 280, 389, 584];
    // Letras base de los codigos 192 a 255 (Latin-1): una letra acentuada mide lo mismo que su letra.
    private const BASE_LATIN1 = "AAAAAAACEEEEIIIIDNOOOOOxOUUUUYTsaaaaaaaceeeeiiiidnooooo/ouuuuyty";

    private array $paginas = [];
    private string $hoja = "";
    private float $y = 0.0;
    private bool $hayHoja = false;

    public function __construct(private string $pieIzquierdo, private string $pieCentro, private string $titulo = "Documento")
    {
    }

    public static function aWinAnsi(string $texto): string
    {
        $convertido = @mb_convert_encoding($texto, "Windows-1252", "UTF-8");
        return $convertido === false ? "?" : $convertido;
    }

    private static function ancho(string $winAnsi, float $tam, bool $negrita): float
    {
        $tabla = $negrita ? self::NEGRITA : self::NORMAL;
        $suma = 0;
        for ($i = 0, $n = strlen($winAnsi); $i < $n; $i++) {
            $c = ord($winAnsi[$i]);
            if ($c >= 192) {
                $c = ord(self::BASE_LATIN1[$c - 192]);
            }
            $suma += ($c >= 32 && $c <= 126) ? $tabla[$c - 32] : ($c === 0x95 ? 350 : ($c === 0x97 ? 1000 : 556));
        }
        return $suma * $tam / 1000;
    }

    public function anchoDeTexto(string $utf8, float $tam, bool $negrita = false): float
    {
        return self::ancho(self::aWinAnsi($utf8), $tam, $negrita);
    }

    // Parte un texto en lineas que entren en $ancho puntos (corta una palabra solo si por si sola no entra).
    public function lineas(string $utf8, float $ancho, float $tam, bool $negrita = false): array
    {
        $salida = [];
        foreach (preg_split('/\R/u', trim($utf8)) ?: [""] as $parrafo) {
            $actual = "";
            foreach (preg_split('/\s+/u', trim($parrafo), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $palabra) {
                while (mb_strlen($palabra) > 1 && $this->anchoDeTexto($palabra, $tam, $negrita) > $ancho) {
                    $corte = mb_strlen($palabra) - 1;
                    while ($corte > 1 && $this->anchoDeTexto(mb_substr($palabra, 0, $corte), $tam, $negrita) > $ancho) {
                        $corte--;
                    }
                    if ($actual !== "") {
                        $salida[] = $actual;
                        $actual = "";
                    }
                    $salida[] = mb_substr($palabra, 0, $corte);
                    $palabra = mb_substr($palabra, $corte);
                }
                $prueba = $actual === "" ? $palabra : $actual . " " . $palabra;
                if ($actual !== "" && $this->anchoDeTexto($prueba, $tam, $negrita) > $ancho) {
                    $salida[] = $actual;
                    $actual = $palabra;
                } else {
                    $actual = $prueba;
                }
            }
            $salida[] = $actual;
        }
        return $salida;
    }

    // ---- paginas y primitivas ----

    public function paginaNueva(): void
    {
        if ($this->hayHoja) {
            $this->paginas[] = $this->hoja;
        }
        $this->hoja = "";
        $this->hayHoja = true;
        $this->y = self::ALTO - self::MARGEN;
    }

    public function y(): float
    {
        return $this->y;
    }

    public function bajar(float $puntos): void
    {
        $this->y -= $puntos;
    }

    // Si no entran $alto puntos en la hoja, pasa a la siguiente. Devuelve true si cambio de hoja.
    public function asegurar(float $alto): bool
    {
        if (!$this->hayHoja || $this->y - $alto < self::MARGEN + self::PIE) {
            $this->paginaNueva();
            return true;
        }
        return false;
    }

    private static function num(float $n): string
    {
        $t = rtrim(rtrim(number_format($n, 2, ".", ""), "0"), ".");
        return $t === "" || $t === "-0" ? "0" : $t;
    }

    private static function color(array $rgb): string
    {
        return self::num($rgb[0] / 255) . " " . self::num($rgb[1] / 255) . " " . self::num($rgb[2] / 255);
    }

    private static function escapar(string $winAnsi): string
    {
        return strtr($winAnsi, ["\\" => "\\\\", "(" => "\(", ")" => "\)", "\r" => "", "\n" => " "]);
    }

    public function texto(string $utf8, float $x, float $y, float $tam = 10.0, bool $negrita = false, array $rgb = [20, 36, 28]): void
    {
        if (!$this->hayHoja) {
            $this->paginaNueva();
        }
        $this->hoja .= "BT /" . ($negrita ? "F2" : "F1") . " " . self::num($tam) . " Tf " . self::color($rgb) . " rg " . self::num($x) . " " . self::num($y) .
                       " Td (" . self::escapar(self::aWinAnsi($utf8)) . ") Tj ET\n";
    }

    public function rectangulo(float $x, float $y, float $ancho, float $alto, array $rgb): void
    {
        $this->hoja .= self::color($rgb) . " rg " . self::num($x) . " " . self::num($y) . " " . self::num($ancho) . " " . self::num($alto) . " re f\n";
    }

    public function linea(float $x1, float $y1, float $x2, float $y2, array $rgb = [200, 205, 198], float $grosor = 0.6): void
    {
        $this->hoja .= self::color($rgb) . " RG " . self::num($grosor) . " w " . self::num($x1) . " " . self::num($y1) . " m " . self::num($x2) . " " . self::num($y2) . " l S\n";
    }

    // Dibuja un SVG hecho solo de <path fill="#rrggbb" d="..."> con M, L, C y Z absolutos (como el logo). Cabe en $lado puntos, con su esquina inferior izquierda en ($x, $y).
    public function dibujarSvg(string $svg, float $x, float $y, float $lado): bool
    {
        if (!preg_match('/viewBox="\s*(-?[\d.]+)[ ,]+(-?[\d.]+)[ ,]+([\d.]+)[ ,]+([\d.]+)\s*"/', $svg, $vb) || (float) $vb[3] <= 0 || (float) $vb[4] <= 0) {
            return false;
        }
        $escala = $lado / max((float) $vb[3], (float) $vb[4]);
        $punto = fn(float $px, float $py) => self::num($x + ($px - (float) $vb[1]) * $escala) . " " . self::num($y + $lado - ($py - (float) $vb[2]) * $escala);
        $dibujo = "";
        preg_match_all('/<path\b[^>]*>/i', $svg, $trazados);
        foreach ($trazados[0] as $etiqueta) {
            if (!preg_match('/\bd="([^"]+)"/', $etiqueta, $d)) {
                continue;
            }
            $relleno = preg_match('/\bfill="#([0-9a-fA-F]{6})"/', $etiqueta, $f) ? [hexdec(substr($f[1], 0, 2)), hexdec(substr($f[1], 2, 2)), hexdec(substr($f[1], 4, 2))] : [0, 0, 0];
            preg_match_all('/([MLCZ])([^MLCZ]*)/', $d[1], $ordenes, PREG_SET_ORDER);
            $camino = "";
            foreach ($ordenes as $o) {
                preg_match_all('/-?\d+(?:\.\d+)?/', $o[2], $n);
                $v = array_map("floatval", $n[0]);
                if ($o[1] === "Z") {
                    $camino .= "h ";
                } elseif ($o[1] === "C") {
                    for ($i = 0; $i + 5 < count($v); $i += 6) {
                        $camino .= $punto($v[$i], $v[$i + 1]) . " " . $punto($v[$i + 2], $v[$i + 3]) . " " . $punto($v[$i + 4], $v[$i + 5]) . " c ";
                    }
                } else {
                    for ($i = 0; $i + 1 < count($v); $i += 2) {
                        $camino .= $punto($v[$i], $v[$i + 1]) . ($o[1] === "M" && $i === 0 ? " m " : " l ");
                    }
                }
            }
            $dibujo .= self::color($relleno) . " rg " . $camino . "f*\n";
        }
        if ($dibujo === "") {
            return false;
        }
        if (!$this->hayHoja) {
            $this->paginaNueva();
        }
        $this->hoja .= "q\n" . $dibujo . "Q\n";
        return true;
    }

    // ---- bloques ----

    public function titulo(string $utf8, float $tam = 15.0): void
    {
        $this->asegurar($tam * 3.2);
        $this->bajar($tam * 1.5);
        $this->texto($utf8, self::MARGEN, $this->y, $tam, true, [17, 84, 64]);
        $this->bajar(5);
        $this->linea(self::MARGEN, $this->y, self::ANCHO - self::MARGEN, $this->y, [17, 84, 64], 1.0);
        $this->bajar(8);
    }

    public function parrafo(string $utf8, float $tam = 9.5, bool $negrita = false, array $rgb = [20, 36, 28]): void
    {
        foreach ($this->lineas($utf8, self::ANCHO - 2 * self::MARGEN, $tam, $negrita) as $linea) {
            $this->asegurar($tam * 1.5);
            $this->bajar($tam * 1.3);
            $this->texto($linea, self::MARGEN, $this->y, $tam, $negrita, $rgb);
        }
        $this->bajar(4);
    }

    // $pesos: ancho relativo de cada columna. $alinear: "l" o "r" por columna. Una fila no se parte entre hojas; el encabezado se repite.
    public function tabla(array $encabezados, array $filas, array $pesos, array $alinear = [], float $tam = 8.5): void
    {
        $total = array_sum($pesos);
        $disponible = self::ANCHO - 2 * self::MARGEN;
        $anchos = array_map(fn($p) => $disponible * $p / $total, $pesos);
        $interlinea = $tam * 1.25;
        $relleno = 4.0;

        $dibujarFila = function (array $celdas, bool $encabezado, bool $cebra) use ($anchos, $alinear, $tam, $interlinea, $relleno): float {
            $lineas = [];
            $maximo = 1;
            foreach ($celdas as $i => $c) {
                $lineas[$i] = $this->lineas((string) $c, $anchos[$i] - 2 * $relleno, $tam, $encabezado);
                $maximo = max($maximo, count($lineas[$i]));
            }
            $alto = $maximo * $interlinea + 2 * $relleno - 1;
            $this->asegurar($alto);
            $arriba = $this->y;
            if ($encabezado) {
                $this->rectangulo(self::MARGEN, $arriba - $alto, array_sum($anchos), $alto, [17, 84, 64]);
            } elseif ($cebra) {
                $this->rectangulo(self::MARGEN, $arriba - $alto, array_sum($anchos), $alto, [243, 246, 241]);
            }
            $x = self::MARGEN;
            foreach ($lineas as $i => $ls) {
                foreach ($ls as $k => $linea) {
                    $ancho = $this->anchoDeTexto($linea, $tam, $encabezado);
                    $px = ($alinear[$i] ?? "l") === "r" ? $x + $anchos[$i] - $relleno - $ancho : $x + $relleno;
                    $this->texto($linea, $px, $arriba - $relleno - $tam * 0.95 - $k * $interlinea, $tam, $encabezado, $encabezado ? [255, 255, 255] : [20, 36, 28]);
                }
                $x += $anchos[$i];
            }
            $this->y = $arriba - $alto;
            return $alto;
        };

        $this->asegurar(3 * $interlinea + 2 * $relleno);
        $dibujarFila($encabezados, true, false);
        foreach ($filas as $n => $fila) {
            $altoPrevisto = 0;
            foreach ($fila as $i => $c) {
                $altoPrevisto = max($altoPrevisto, count($this->lineas((string) $c, $anchos[$i] - 2 * $relleno, $tam)) * $interlinea + 2 * $relleno - 1);
            }
            if ($this->y - $altoPrevisto < self::MARGEN + self::PIE) {
                $this->paginaNueva();
                $dibujarFila($encabezados, true, false);
            }
            $dibujarFila($fila, false, $n % 2 === 1);
        }
        $this->bajar(6);
    }

    // ---- salida ----

    public function cantidadDePaginas(): int
    {
        return count($this->paginas) + ($this->hayHoja ? 1 : 0);
    }

    public function salida(): string
    {
        if ($this->hayHoja) {
            $this->paginas[] = $this->hoja;
            $this->hoja = "";
            $this->hayHoja = false;
        }
        if ($this->paginas === []) {
            $this->paginas[] = "";
        }
        $total = count($this->paginas);
        foreach ($this->paginas as $i => $contenido) {
            $this->hayHoja = true;
            $this->hoja = "";
            $yPie = self::MARGEN - 6;
            $this->linea(self::MARGEN, $yPie + 14, self::ANCHO - self::MARGEN, $yPie + 14, [200, 205, 198], 0.5);
            $this->texto($this->pieIzquierdo, self::MARGEN, $yPie, 8, false, [100, 117, 108]);
            $centro = $this->pieCentro;
            $this->texto($centro, (self::ANCHO - $this->anchoDeTexto($centro, 8)) / 2, $yPie, 8, false, [100, 117, 108]);
            $der = "Página " . ($i + 1) . " de " . $total;
            $this->texto($der, self::ANCHO - self::MARGEN - $this->anchoDeTexto($der, 8), $yPie, 8, false, [100, 117, 108]);
            $this->paginas[$i] = $contenido . $this->hoja;
        }
        $this->hayHoja = false;
        $this->hoja = "";

        $objetos = [];
        $objetos[1] = "<< /Type /Catalog /Pages 2 0 R >>";
        $hijos = [];
        for ($i = 0; $i < $total; $i++) {
            $hijos[] = (5 + 2 * $i) . " 0 R";
        }
        $objetos[2] = "<< /Type /Pages /Kids [" . implode(" ", $hijos) . "] /Count " . $total . " >>";
        $objetos[3] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>";
        $objetos[4] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>";
        foreach ($this->paginas as $i => $contenido) {
            $datos = function_exists("gzcompress") ? gzcompress($contenido, 6) : $contenido;
            $filtro = function_exists("gzcompress") ? " /Filter /FlateDecode" : "";
            $objetos[5 + 2 * $i] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 " . self::num(self::ANCHO) . " " . self::num(self::ALTO) . "] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents " . (6 + 2 * $i) . " 0 R >>";
            $objetos[6 + 2 * $i] = "<< /Length " . strlen($datos) . $filtro . " >>\nstream\n" . $datos . "\nendstream";
        }
        $info = 5 + 2 * $total;
        $objetos[$info] = "<< /Title (" . self::escapar(self::aWinAnsi($this->titulo)) . ") /Producer (SiGeRU) >>";

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $posiciones = [];
        foreach ($objetos as $n => $cuerpo) {
            $posiciones[$n] = strlen($pdf);
            $pdf .= $n . " 0 obj\n" . $cuerpo . "\nendobj\n";
        }
        $inicioXref = strlen($pdf);
        $pdf .= "xref\n0 " . ($info + 1) . "\n0000000000 65535 f \n";
        for ($n = 1; $n <= $info; $n++) {
            $pdf .= sprintf("%010d 00000 n \n", $posiciones[$n]);
        }
        $pdf .= "trailer\n<< /Size " . ($info + 1) . " /Root 1 0 R /Info " . $info . " 0 R >>\nstartxref\n" . $inicioXref . "\n%%EOF\n";
        return $pdf;
    }
}
