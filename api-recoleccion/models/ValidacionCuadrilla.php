<?php

require_once __DIR__ . "/Campos.php";

// Datos de una cuadrilla (plan de rutas para la defensa, bloque 8). Clase pura.
class ValidacionCuadrilla
{
    public const TURNOS = ["matutino", "vespertino", "nocturno"];
    public const ESTADOS = ["activa", "inactiva"];

    public static function error(array $datos, bool $esAlta): ?string
    {
        if ($esAlta && Campos::texto($datos["nombre"] ?? null) === "") {
            return "Falta el nombre de la cuadrilla";
        }
        if (array_key_exists("nombre", $datos)
            && ($e = Campos::errorDeTexto($datos["nombre"], "El nombre", 2, 80))) {
            return $e;
        }
        return Campos::errorDeOpcion($datos["turno"] ?? null, "Turno", self::TURNOS)
            ?? Campos::errorDeOpcion($datos["estado"] ?? null, "Estado", self::ESTADOS);
    }
}
