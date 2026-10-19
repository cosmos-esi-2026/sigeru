<?php

// "Cuando pasa el camion por mi zona" (extras, parte 15). El calculo es puro (recibe filas ya leidas) y devuelve
// solo lo que el vecino puede saber: el dia y la hora. Nunca el camion, la cuadrilla ni la ruta interna.
class ProximoPasaje
{
    // $filas: [["estado" => "planificada|en_curso|cancelada", "fecha_hora_inicio" => "AAAA-MM-DD HH:MM:SS"], ...]
    // $ahora: "AAAA-MM-DD HH:MM:SS". Devuelve ["estado" => "programado|en_curso|sin_planificadas", "proximo" => ?, "cancelada" => ?].
    // - Una planificada cuenta solo si es de ahora en adelante (una vieja que nadie cerro no es un proximo pasaje).
    // - Si hay una recoleccion en curso, el camion esta pasando hoy.
    // - Si se cancelo una recoleccion futura anterior al proximo pasaje, se devuelve su fecha para avisarlo.
    public static function calcular(array $filas, string $ahora): array
    {
        $planificadas = [];
        $canceladas = [];
        $enCurso = false;
        foreach ($filas as $f) {
            $fecha = (string) $f["fecha_hora_inicio"];
            if ($f["estado"] === "en_curso") {
                $enCurso = true;
            } elseif ($f["estado"] === "planificada" && $fecha >= $ahora) {
                $planificadas[] = $fecha;
            } elseif ($f["estado"] === "cancelada" && $fecha >= $ahora) {
                $canceladas[] = $fecha;
            }
        }
        sort($planificadas);
        sort($canceladas);
        $proximo = $planificadas[0] ?? null;
        $cancelada = $canceladas[0] ?? null;
        if ($cancelada !== null && $proximo !== null && $cancelada > $proximo) {
            $cancelada = null;
        }
        if ($enCurso) {
            return ["estado" => "en_curso", "proximo" => $proximo, "cancelada" => $cancelada];
        }
        return ["estado" => $proximo === null ? "sin_planificadas" : "programado", "proximo" => $proximo, "cancelada" => $cancelada];
    }
}
