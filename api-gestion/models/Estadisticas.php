<?php

require_once __DIR__ . "/FlujoIncidencia.php";

// Calculos del informe de incidencias y del panel por urgencias. Todo lo de esta clase
// es puro (recibe filas ya leidas, no toca la base): por eso tiene pruebas unitarias.
class Estadisticas
{
    public const UMBRAL_DIAS = 3;
    public const MAX_DETALLE = 1000;
    public const TOP_CONTENEDORES = 5;
    public const MAX_URGENTES = 10;

    private const ABIERTAS = ["abierta", "en_curso"];
    private const NIVELES = ["critica" => 0, "urgente" => 1, "atencion" => 2, "normal" => 3];

    public static function redondear(?float $valor): ?float
    {
        return $valor === null ? null : round($valor, 1);
    }

    public static function promedio(array $valores): ?float
    {
        return $valores === [] ? null : array_sum($valores) / count($valores);
    }

    public static function mediana(array $valores): ?float
    {
        if ($valores === []) {
            return null;
        }
        sort($valores);
        $n = count($valores);
        $medio = intdiv($n, 2);
        return $n % 2 === 1 ? (float) $valores[$medio] : ($valores[$medio - 1] + $valores[$medio]) / 2;
    }

    // Horas entre dos fechas "AAAA-MM-DD HH:MM:SS". Null si falta una o si van al reves.
    public static function horasEntre(?string $desde, ?string $hasta): ?float
    {
        if ($desde === null || $hasta === null) {
            return null;
        }
        $utc = new DateTimeZone("UTC");
        try {
            $inicio = new DateTimeImmutable($desde, $utc);
            $fin = new DateTimeImmutable($hasta, $utc);
        } catch (Exception $e) {
            return null;
        }
        $segundos = $fin->getTimestamp() - $inicio->getTimestamp();
        return $segundos < 0 ? null : $segundos / 3600;
    }

    public static function resumenTiempos(array $horas): array
    {
        return [
            "cantidad"      => count($horas),
            "medio_horas"   => self::redondear(self::promedio($horas)),
            "mediano_horas" => self::redondear(self::mediana($horas)),
            "minimo_horas"  => $horas === [] ? null : self::redondear((float) min($horas)),
            "maximo_horas"  => $horas === [] ? null : self::redondear((float) max($horas)),
        ];
    }

    // El tipo con mas incidencias; en un empate gana el que figura primero en la lista de tipos.
    public static function problemaFrecuente(array $conteoPorTipo): ?string
    {
        $mejor = null;
        $maximo = 0;
        foreach (FlujoIncidencia::TIPOS as $tipo) {
            $cantidad = $conteoPorTipo[$tipo] ?? 0;
            if ($cantidad > $maximo) {
                $maximo = $cantidad;
                $mejor = $tipo;
            }
        }
        return $mejor;
    }

    public static function informe(array $filas): array
    {
        $totales = ["total" => 0, "abiertas" => 0, "en_curso" => 0, "resueltas" => 0, "descartadas" => 0,
                    "pendientes" => 0, "cerradas" => 0];
        $clavesDeEstado = ["abierta" => "abiertas", "en_curso" => "en_curso", "resuelta" => "resueltas",
                           "descartada" => "descartadas"];
        $porPrioridad = array_fill_keys(FlujoIncidencia::PRIORIDADES, 0);
        $porTipo = array_fill_keys(FlujoIncidencia::TIPOS, 0);
        $porZona = [];
        $porContenedor = [];
        $horasResolucion = [];
        $detalle = [];

        foreach ($filas as $fila) {
            $estado = $fila["estado"];
            $clave = $clavesDeEstado[$estado];
            $abierta = in_array($estado, self::ABIERTAS, true);
            $horas = $estado === "resuelta"
                ? self::horasEntre($fila["fecha_reporte"], $fila["fecha_resolucion"]) : null;

            $totales["total"]++;
            $totales[$clave]++;
            $totales[$abierta ? "pendientes" : "cerradas"]++;
            $porPrioridad[$fila["prioridad"]]++;
            $porTipo[$fila["tipo_problema"]]++;
            if ($horas !== null) {
                $horasResolucion[] = $horas;
            }

            $idZona = (int) $fila["id_zona"];
            $porZona[$idZona] ??= ["id_zona" => $idZona, "zona" => $fila["zona"], "abiertas" => 0, "en_curso" => 0,
                                   "resueltas" => 0, "descartadas" => 0, "total" => 0, "_horas" => []];
            $porZona[$idZona][$clave]++;
            $porZona[$idZona]["total"]++;
            if ($horas !== null) {
                $porZona[$idZona]["_horas"][] = $horas;
            }

            $idContenedor = (int) $fila["id_contenedor"];
            $porContenedor[$idContenedor] ??= ["id_contenedor" => $idContenedor, "codigo" => $fila["contenedor"],
                                              "direccion" => $fila["direccion"], "zona" => $fila["zona"],
                                              "total" => 0, "pendientes" => 0, "_tipos" => []];
            $porContenedor[$idContenedor]["total"]++;
            $porContenedor[$idContenedor]["pendientes"] += $abierta ? 1 : 0;
            $tipo = $fila["tipo_problema"];
            $porContenedor[$idContenedor]["_tipos"][$tipo] = ($porContenedor[$idContenedor]["_tipos"][$tipo] ?? 0) + 1;

            if (count($detalle) < self::MAX_DETALLE) {
                $detalle[] = [
                    "numero"           => FlujoIncidencia::numero((int) $fila["id_incidencia"]),
                    "id_incidencia"    => (int) $fila["id_incidencia"],
                    "fecha_reporte"    => $fila["fecha_reporte"],
                    "contenedor"       => $fila["contenedor"],
                    "direccion"        => $fila["direccion"],
                    "zona"             => $fila["zona"],
                    "tipo_problema"    => $tipo,
                    "prioridad"        => $fila["prioridad"],
                    "estado"           => $estado,
                    "fecha_resolucion" => $fila["fecha_resolucion"],
                    "horas_resolucion" => self::redondear($horas),
                ];
            }
        }

        $zonas = array_map(function (array $zona) {
            $zona["tiempo_medio_horas"] = self::redondear(self::promedio($zona["_horas"]));
            unset($zona["_horas"]);
            return $zona;
        }, array_values($porZona));
        usort($zonas, fn ($a, $b) => [$b["total"], $a["zona"]] <=> [$a["total"], $b["zona"]]);

        $contenedores = array_map(function (array $c) {
            $c["problema_frecuente"] = self::problemaFrecuente($c["_tipos"]);
            unset($c["_tipos"]);
            return $c;
        }, array_values($porContenedor));
        usort($contenedores, fn ($a, $b) => [$b["total"], $a["codigo"]] <=> [$a["total"], $b["codigo"]]);

        $tipos = [];
        foreach ($porTipo as $tipo => $cantidad) {
            $tipos[] = ["tipo_problema" => $tipo, "total" => $cantidad];
        }
        usort($tipos, fn ($a, $b) => $b["total"] <=> $a["total"]);

        return [
            "totales"           => $totales,
            "por_prioridad"     => $porPrioridad,
            "por_tipo"          => $tipos,
            "por_zona"          => $zonas,
            "tiempo_resolucion" => self::resumenTiempos($horasResolucion),
            "top_contenedores"  => array_slice($contenedores, 0, self::TOP_CONTENEDORES),
            "detalle"           => $detalle,
            "detalle_truncado"  => $totales["total"] > count($detalle),
        ];
    }

    // Cantidad de dias para considerar "vencida" una incidencia abierta (1 a 60).
    public static function umbralDe($valor): ?int
    {
        if ($valor === null || $valor === "") {
            return self::UMBRAL_DIAS;
        }
        $numero = FlujoIncidencia::entero($valor);
        return $numero !== null && $numero <= 60 ? $numero : null;
    }

    // critica: prioridad alta y vencida · urgente: alta o vencida · atencion: sin cuadrilla · normal: el resto
    public static function nivelDeUrgencia(string $prioridad, float $horasAbierta, ?int $idCuadrilla, int $umbralDias): string
    {
        $vencida = $horasAbierta >= $umbralDias * 24;
        if ($prioridad === "alta" && $vencida) {
            return "critica";
        }
        if ($prioridad === "alta" || $vencida) {
            return "urgente";
        }
        return $idCuadrilla === null ? "atencion" : "normal";
    }

    // Recibe las incidencias abiertas con "horas_abierta" y devuelve los indicadores y las mas urgentes.
    public static function urgencias(array $abiertas, int $umbralDias): array
    {
        $indicadores = ["abiertas" => 0, "alta" => 0, "sin_asignar" => 0, "vencidas" => 0, "criticas" => 0];
        $lista = [];

        foreach ($abiertas as $fila) {
            $horas = (int) $fila["horas_abierta"];
            $idCuadrilla = $fila["id_cuadrilla"] === null ? null : (int) $fila["id_cuadrilla"];
            $nivel = self::nivelDeUrgencia($fila["prioridad"], $horas, $idCuadrilla, $umbralDias);

            $indicadores["abiertas"]++;
            $indicadores["alta"] += $fila["prioridad"] === "alta" ? 1 : 0;
            $indicadores["sin_asignar"] += $idCuadrilla === null ? 1 : 0;
            $indicadores["vencidas"] += $horas >= $umbralDias * 24 ? 1 : 0;
            $indicadores["criticas"] += $nivel === "critica" ? 1 : 0;

            $lista[] = [
                "id_incidencia" => (int) $fila["id_incidencia"],
                "numero"        => FlujoIncidencia::numero((int) $fila["id_incidencia"]),
                "contenedor"    => $fila["contenedor"],
                "direccion"     => $fila["direccion"],
                "zona"          => $fila["zona"],
                "tipo_problema" => $fila["tipo_problema"],
                "prioridad"     => $fila["prioridad"],
                "estado"        => $fila["estado"],
                "cuadrilla"     => $fila["cuadrilla"],
                "horas_abierta" => $horas,
                "dias_abierta"  => intdiv($horas, 24),
                "nivel"         => $nivel,
            ];
        }

        usort($lista, fn ($a, $b) => [self::NIVELES[$a["nivel"]], -$a["horas_abierta"], $a["id_incidencia"]]
                                   <=> [self::NIVELES[$b["nivel"]], -$b["horas_abierta"], $b["id_incidencia"]]);

        return ["indicadores" => $indicadores, "incidencias" => array_slice($lista, 0, self::MAX_URGENTES)];
    }
}
