<?php

require_once __DIR__ . "/Campos.php";

// Datos de un centro de acopio (parte 9). Clase pura. Antes un estado invalido se cambiaba
// por "activo" sin avisar, y la capacidad y la ubicacion no se miraban.
class ValidacionCentro
{
    public const ESTADOS = ["activo", "inactivo"];
    public const CAPACIDAD_MAXIMA = 10000000;

    public static function error(array $datos, bool $esAlta): ?string
    {
        if ($esAlta && Campos::texto($datos["nombre"] ?? null) === "") {
            return "Falta el nombre del centro de acopio";
        }
        if (array_key_exists("nombre", $datos) && ($e = Campos::errorDeTexto($datos["nombre"], "El nombre", 1, 120))) {
            return $e;
        }
        return Campos::errorDeTextoOpcional($datos["direccion"] ?? null, "La dirección", 200)
            ?? Campos::errorDeEntero($datos["capacidad"] ?? null, "La capacidad (kg)", 1, self::CAPACIDAD_MAXIMA)
            ?? Campos::errorDeUbicacion($datos)
            ?? Campos::errorDeOpcion($datos["estado"] ?? null, "Estado", self::ESTADOS);
    }
}
