<?php

require_once __DIR__ . "/PdfSimple.php";

// Reporte de gestion en PDF (extras, parte 20). Clase pura: recibe los MISMOS datos que muestra la pantalla de Reportes
// (los arma EstadisticaController) y los acomoda en el documento. Todo con tope de filas.
class ReportePdf
{
    public const TOPE_DETALLE = 100;
    public const TOPE_LLENOS = 25;

    private const ESTADO = ["abierta" => "Abierta", "en_curso" => "En curso", "resuelta" => "Resuelta", "descartada" => "Descartada"];
    private const PRIORIDAD = ["alta" => "Alta", "media" => "Media", "baja" => "Baja"];
    private const TIPO = ["desbordado" => "Contenedor desbordado", "lleno" => "Contenedor lleno", "roto" => "Contenedor roto o dañado", "residuo_no_permitido" => "Residuo no permitido",
                          "ausente_o_mal_ubicado" => "Contenedor ausente o mal ubicado", "otro" => "Otro"];

    // Como Intl.NumberFormat("es-UY"): punto de miles y coma decimal, sin ceros de mas.
    public static function numero($valor, int $decimales = 1): string
    {
        if ($valor === null || $valor === "") {
            return "-";
        }
        $t = number_format((float) $valor, $decimales, ",", ".");
        return $decimales > 0 ? rtrim(rtrim($t, "0"), ",") : $t;
    }

    // Igual que la pantalla: menos de 48 h en horas, despues en dias con un decimal.
    public static function duracion($horas): string
    {
        if ($horas === null) {
            return "-";
        }
        return (float) $horas < 48 ? self::numero($horas, 1) . " h" : number_format((float) $horas / 24, 1, ",", ".") . " días";
    }

    public static function plural(int $n, string $singular, string $plural): string
    {
        return $n . " " . ($n === 1 ? $singular : $plural);
    }

    public static function fecha(?string $iso): string
    {
        return $iso ? date("d/m/Y", strtotime(substr($iso, 0, 10))) : "";
    }

    private static function etiqueta(array $mapa, ?string $clave): string
    {
        return $mapa[$clave ?? ""] ?? (string) $clave;
    }

    public static function periodoTexto(array $p): string
    {
        $desde = self::fecha($p["desde"] ?? null);
        $hasta = self::fecha($p["hasta"] ?? null);
        $rango = "Todo el historial";
        if ($desde !== "" && $hasta !== "") {
            $rango = "Del $desde al $hasta";
        } elseif ($desde !== "") {
            $rango = "Desde el $desde";
        } elseif ($hasta !== "") {
            $rango = "Hasta el $hasta";
        }
        return $rango;
    }

    // $d: periodo, incidencias, satisfaccion, urgencias, impacto y logo_svg (texto del SVG o null).
    public static function armar(array $d): string
    {
        $p = $d["periodo"];
        $generado = (string) ($p["generado_en"] ?? "");
        $pdf = new PdfSimple("SiGeRU - Reporte de gestión", "Generado el " . self::fecha($generado) . substr($generado, 10, 6), "SiGeRU - Reporte de gestión");
        $pdf->paginaNueva();

        // Portada
        $pdf->bajar(70);
        $logo = isset($d["logo_svg"]) && $d["logo_svg"] !== null && $pdf->dibujarSvg($d["logo_svg"], PdfSimple::MARGEN, $pdf->y() - 80, 80);
        $pdf->texto("SiGeRU", PdfSimple::MARGEN + ($logo ? 96 : 0), $pdf->y() - 46, 34, true, [17, 84, 64]);
        $pdf->texto("Sistema de Gestión de Residuos Urbanos", PdfSimple::MARGEN + ($logo ? 98 : 0), $pdf->y() - 68, 11, false, [85, 104, 95]);
        $pdf->bajar(110);
        $pdf->texto("Reporte de gestión", PdfSimple::MARGEN, $pdf->y(), 20, true);
        $pdf->bajar(22);
        $pdf->parrafo(self::periodoTexto($p) . "  |  " . (($p["zona"] ?? null) ? "Zona " . $p["zona"] : "Todas las zonas") . "  |  Generado el " . self::fecha($generado) . substr($generado, 10, 6), 10, false, [85, 104, 95]);
        $pdf->bajar(6);

        self::resumen($pdf, $d);
        self::urgencias($pdf, $d["urgencias"]);
        self::incidencias($pdf, $d["incidencias"]);
        self::satisfaccion($pdf, $d["satisfaccion"]);
        self::impacto($pdf, $d["impacto"]);
        self::detalle($pdf, $d["incidencias"]);
        return $pdf->salida();
    }

    private static function resumen(PdfSimple $pdf, array $d): void
    {
        $t = $d["incidencias"]["totales"];
        $tiempo = $d["incidencias"]["tiempo_resolucion"];
        $g = $d["satisfaccion"]["general"];
        $u = $d["urgencias"]["indicadores"];
        $i = $d["impacto"];
        $pdf->titulo("1. Resumen");
        $pdf->tabla(["Indicador", "Valor"], [
            ["Incidencias del período", self::numero($t["total"], 0)],
            ["Abiertas / en curso / resueltas / descartadas", self::numero($t["abiertas"], 0) . " / " . self::numero($t["en_curso"], 0) . " / " . self::numero($t["resueltas"], 0) . " / " . self::numero($t["descartadas"], 0)],
            ["Tiempo medio de resolución", self::duracion($tiempo["medio_horas"] ?? null) . ($tiempo["cantidad"] > 0 ? " (" . self::plural($tiempo["cantidad"], "resuelta", "resueltas") . ")" : "")],
            ["Satisfacción promedio (puntaje de 1 a 5)", $g["calificaciones"] > 0 ? self::numero($g["promedio"], 1) . " (" . self::plural($g["calificaciones"], "calificación", "calificaciones") . ")" : "Sin calificaciones en el período"],
            ["Contenedores llenos en este momento", self::numero($u["contenedores_llenos"] ?? 0, 0)],
            ["Incidencias abiertas críticas en este momento", self::numero($u["criticas"], 0)],
            ["Kilos recolectados", self::numero($i["kg_total"], 2) . " kg"],
            ["Material reciclable", $i["pct_reciclable"] === null ? "-" : self::numero($i["pct_reciclable"], 1) . " %"],
            ["CO2 evitado (estimación)", self::numero($i["co2_evitado_kg"], 1) . " kg"],
        ], [3, 2], ["l", "r"], 9);
        $pdf->parrafo("El período filtra las incidencias por su fecha de reporte y los kilos por la fecha del vaciado. Los contenedores llenos y las incidencias críticas son la situación del momento en que se generó el reporte.", 8.5, false, [85, 104, 95]);
    }

    private static function urgencias(PdfSimple $pdf, array $u): void
    {
        $in = $u["indicadores"];
        $pdf->titulo("2. Urgencias");
        $pdf->parrafo("Incidencias abiertas ahora: " . self::numero($in["abiertas"], 0) . ". De prioridad alta: " . self::numero($in["alta"], 0) . ". Sin asignar a una cuadrilla: " . self::numero($in["sin_asignar"], 0) .
                      ". Vencidas (más de " . $u["umbral_dias"] . " días abiertas): " . self::numero($in["vencidas"], 0) . ". Críticas (prioridad alta y vencidas): " . self::numero($in["criticas"], 0) . ".");
        if ($u["incidencias"] !== []) {
            $pdf->parrafo("Las " . count($u["incidencias"]) . " más urgentes, de la más grave a la menos grave:", 9.5, true);
            $pdf->tabla(["N.º", "Contenedor", "Zona", "Problema", "Prioridad", "Estado", "Días"], array_map(fn($x) => [
                $x["numero"], $x["contenedor"], $x["zona"], self::etiqueta(self::TIPO, $x["tipo_problema"]), self::etiqueta(self::PRIORIDAD, $x["prioridad"]),
                self::etiqueta(self::ESTADO, $x["estado"]), (string) $x["dias_abierta"],
            ], $u["incidencias"]), [1.2, 1.5, 1.5, 3, 1.2, 1.3, 0.7], ["l", "l", "l", "l", "l", "l", "r"], 8);
        }
        $llenos = $u["contenedores_llenos"];
        $pdf->parrafo("Contenedores llenos ahora: " . count($llenos) . ($llenos === [] ? "." : ($llenos[0] !== null && count($llenos) > self::TOPE_LLENOS ? " (se muestran los primeros " . self::TOPE_LLENOS . ")." : ".")), 9.5, true);
        if ($llenos !== []) {
            $pdf->tabla(["Contenedor", "Dirección", "Zona", "Capacidad"], array_map(fn($c) => [
                $c["codigo"], $c["direccion"], $c["zona"], $c["capacidad"] === null ? "-" : self::numero($c["capacidad"], 0) . " L",
            ], array_slice($llenos, 0, self::TOPE_LLENOS)), [1.3, 3.5, 1.7, 1.2], ["l", "l", "l", "r"], 8);
        }
    }

    private static function incidencias(PdfSimple $pdf, array $inc): void
    {
        $pdf->titulo("3. Incidencias del período");
        $pdf->parrafo("Por zona (de la que más incidencias tuvo a la que menos):", 9.5, true);
        if ($inc["por_zona"] === []) {
            $pdf->parrafo("No hubo incidencias en el período.");
        } else {
            $pdf->tabla(["Zona", "Total", "Abiertas", "En curso", "Resueltas", "Descartadas", "Tiempo medio"], array_map(fn($z) => [
                $z["zona"], (string) $z["total"], (string) $z["abiertas"], (string) $z["en_curso"], (string) $z["resueltas"], (string) $z["descartadas"], self::duracion($z["tiempo_medio_horas"]),
            ], $inc["por_zona"]), [2.6, 1, 1.1, 1.1, 1.2, 1.4, 1.5], ["l", "r", "r", "r", "r", "r", "r"], 8.5);
            $pdf->parrafo("Por tipo de problema:", 9.5, true);
            $pdf->tabla(["Problema", "Incidencias"], array_map(fn($t) => [self::etiqueta(self::TIPO, $t["tipo_problema"]), (string) $t["total"]], $inc["por_tipo"]), [4, 1.2], ["l", "r"], 8.5);
            $pdf->parrafo("Por prioridad: alta " . $inc["por_prioridad"]["alta"] . ", media " . $inc["por_prioridad"]["media"] . ", baja " . $inc["por_prioridad"]["baja"] . ".");
            if ($inc["top_contenedores"] !== []) {
                $pdf->parrafo("Los " . count($inc["top_contenedores"]) . " contenedores con más incidencias:", 9.5, true);
                $pdf->tabla(["Contenedor", "Dirección", "Zona", "Total", "Pendientes", "Problema más frecuente"], array_map(fn($c) => [
                    $c["codigo"], $c["direccion"], $c["zona"], (string) $c["total"], (string) $c["pendientes"], self::etiqueta(self::TIPO, $c["problema_frecuente"]),
                ], $inc["top_contenedores"]), [1.2, 2.6, 1.4, 0.8, 1.1, 2.4], ["l", "l", "l", "r", "r", "l"], 8);
            }
        }
    }

    private static function satisfaccion(PdfSimple $pdf, array $s): void
    {
        $g = $s["general"];
        $pdf->titulo("4. Satisfacción del vecino");
        if ($g["calificaciones"] === 0) {
            $pdf->parrafo("Ninguna incidencia del período fue calificada todavía (el vecino califica una vez resuelta). Incidencias del período: " . $g["incidencias"] . ".");
            return;
        }
        $reparto = [];
        foreach ($g["distribucion"] as $puntaje => $cantidad) {
            $reparto[] = "$puntaje: $cantidad";
        }
        $pdf->parrafo("Promedio general: " . self::numero($g["promedio"], 1) . " sobre 5, con " . self::plural($g["calificaciones"], "calificación", "calificaciones") . " de " . self::plural($g["incidencias"], "incidencia", "incidencias") . ". Reparto por puntaje (puntaje: cantidad): " . implode(" · ", $reparto) . ".");
        $fila = fn($nombre, $x) => [$nombre, (string) $x["incidencias"], (string) $x["resueltas"], (string) $x["calificaciones"], self::numero($x["promedio"], 1), self::duracion($x["tiempo_medio_horas"])];
        $pdf->parrafo("Por zona:", 9.5, true);
        $pdf->tabla(["Zona", "Incidencias", "Resueltas", "Calificadas", "Promedio", "Tiempo medio"], array_map(fn($x) => $fila($x["zona"], $x), $s["por_zona"]), [2.6, 1.2, 1.1, 1.2, 1.1, 1.5], ["l", "r", "r", "r", "r", "r"], 8.5);
        if ($s["por_cuadrilla"] !== []) {
            $pdf->parrafo("Por cuadrilla:", 9.5, true);
            $pdf->tabla(["Cuadrilla", "Incidencias", "Resueltas", "Calificadas", "Promedio", "Tiempo medio"], array_map(fn($x) => $fila($x["cuadrilla"], $x), $s["por_cuadrilla"]), [2.6, 1.2, 1.1, 1.2, 1.1, 1.5], ["l", "r", "r", "r", "r", "r"], 8.5);
        }
    }

    private static function impacto(PdfSimple $pdf, array $i): void
    {
        $pdf->titulo("5. Impacto ambiental (estimación)");
        $pdf->parrafo("Kilos recolectados: " . self::numero($i["kg_total"], 2) . " kg, de los cuales " . self::numero($i["kg_reciclable"], 2) . " kg son de tipos reciclables" .
                      ($i["pct_reciclable"] === null ? "." : " (" . self::numero($i["pct_reciclable"], 1) . " %).") . " CO2 evitado estimado: " . self::numero($i["co2_evitado_kg"], 1) . " kg. Kilos recibidos en centros de acopio: " . self::numero($i["kg_recibidos_en_centros"] ?? 0, 2) . " kg.");
        if ($i["por_tipo"] !== []) {
            $pdf->tabla(["Tipo de residuo", "Kilos", "Factor (kg CO2e por kg)", "CO2 evitado (kg)"], array_map(fn($t) => [
                $t["tipo"], self::numero($t["kg"], 2), $t["factor"] === null ? "sin fuente: solo kilos" : self::numero($t["factor"], 3), $t["co2_evitado_kg"] === null ? "-" : self::numero($t["co2_evitado_kg"], 1),
            ], $i["por_tipo"]), [2, 1.4, 2.4, 1.7], ["l", "r", "r", "r"], 8.5);
        }
        $pdf->parrafo((string) ($i["nota"] ?? ""), 8.5, false, [85, 104, 95]);
        foreach ($i["fuentes"] ?? [] as $f) {
            $pdf->parrafo("Fuente (" . implode(", ", $f["tipos"]) . "): " . $f["fuente"] . ($f["url"] ? " - " . $f["url"] : ""), 8, false, [85, 104, 95]);
        }
    }

    private static function detalle(PdfSimple $pdf, array $inc): void
    {
        $filas = array_slice($inc["detalle"], 0, self::TOPE_DETALLE);
        $pdf->titulo("6. Detalle de incidencias");
        if ($filas === []) {
            $pdf->parrafo("No hubo incidencias en el período.");
            return;
        }
        $total = $inc["totales"]["total"];
        $pdf->parrafo($total > count($filas) ? "Se muestran las " . count($filas) . " más recientes de " . self::numero($total, 0) . " (tope del reporte). Para ver todas, usá «Exportar» en la pantalla de Reportes." : "Todas las incidencias del período (" . count($filas) . "), de la más reciente a la más antigua.");
        $pdf->tabla(["N.º", "Fecha", "Contenedor", "Zona", "Problema", "Prior.", "Estado", "Resolución"], array_map(fn($x) => [
            $x["numero"], self::fecha($x["fecha_reporte"]), $x["contenedor"], $x["zona"], self::etiqueta(self::TIPO, $x["tipo_problema"]), self::etiqueta(self::PRIORIDAD, $x["prioridad"]),
            self::etiqueta(self::ESTADO, $x["estado"]), $x["horas_resolucion"] === null ? "-" : self::duracion($x["horas_resolucion"]),
        ], $filas), [1.1, 1.2, 1.3, 1.3, 2.4, 0.9, 1.1, 1.2], ["l", "l", "l", "l", "l", "l", "l", "r"], 7.5);
    }
}
