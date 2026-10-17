<?php

require_once __DIR__ . "/Campos.php";

// Datos de una maquinaria (parte 9): el tipo y el estado ya se validaban; faltaban el largo y el formato.
class ValidacionMaquinaria
{
    public static function error(array $datos, bool $esAlta): ?string
    {
        if ($esAlta && Campos::texto($datos["codigo"] ?? null) === "") {
            return "Falta el código de la maquinaria";
        }
        if (array_key_exists("codigo", $datos)
            && ($e = Campos::errorDeTexto($datos["codigo"], "El código", 1, 30, '/^[A-Za-z0-9][A-Za-z0-9._-]*$/'))) {
            return $e;
        }
        return Campos::errorDeTextoOpcional($datos["marca"] ?? null, "La marca", 60)
            ?? Campos::errorDeTextoOpcional($datos["modelo"] ?? null, "El modelo", 60);
    }
}
