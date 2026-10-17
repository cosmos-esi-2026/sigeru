<?php

require_once __DIR__ . "/Campos.php";

// Datos de un camion (parte 9): antes solo se pedia que la matricula no estuviera vacia.
class ValidacionCamion
{
    public const ESTADOS = ["operativo", "en_ruta", "mantenimiento", "fuera_servicio"];
    public const CAPACIDAD_MAXIMA = 100000;

    public static function error(array $datos, bool $esAlta): ?string
    {
        if ($esAlta && Campos::texto($datos["matricula"] ?? null) === "") {
            return "Falta la matrícula";
        }
        if (array_key_exists("matricula", $datos)
            && ($e = Campos::errorDeTexto($datos["matricula"], "La matrícula", 1, 20, '/^[A-Za-z0-9][A-Za-z0-9 -]*$/'))) {
            return $e;
        }
        return Campos::errorDeTextoOpcional($datos["marca"] ?? null, "La marca", 60)
            ?? Campos::errorDeTextoOpcional($datos["modelo"] ?? null, "El modelo", 60)
            ?? Campos::errorDeEntero($datos["capacidad"] ?? null, "La capacidad (kg)", 1, self::CAPACIDAD_MAXIMA)
            ?? Campos::errorDeOpcion($datos["estado"] ?? null, "Estado", self::ESTADOS);
    }
}
