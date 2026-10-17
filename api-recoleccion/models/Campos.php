<?php

// Reglas basicas de campos de formulario (parte 9). Clase pura. Es una COPIA IDENTICA en
// api-recoleccion/models/Campos.php: tests/unitarias.php falla si las dos difieren.
class Campos
{
    public static function vacio($valor): bool
    {
        return $valor === null || $valor === "";
    }

    public static function texto($valor): string
    {
        return is_string($valor) ? trim($valor) : "";
    }

    // Un texto de $min a $max caracteres. Si $patron viene, ademas tiene que cumplirlo.
    public static function errorDeTexto($valor, string $nombre, int $min, int $max, ?string $patron = null): ?string
    {
        $texto = self::texto($valor);
        $largo = mb_strlen($texto);
        if (!is_string($valor) || $largo < $min || $largo > $max) {
            return $min === 1
                ? "$nombre: obligatorio, hasta $max caracteres"
                : "$nombre: de $min a $max caracteres";
        }
        if ($patron !== null && !preg_match($patron, $texto)) {
            return "$nombre tiene caracteres que no se admiten";
        }
        return null;
    }

    // Un texto opcional: vacio esta bien; si viene, hasta $max caracteres.
    public static function errorDeTextoOpcional($valor, string $nombre, int $max): ?string
    {
        if (self::vacio($valor)) {
            return null;
        }
        return is_string($valor) && mb_strlen(trim($valor)) <= $max ? null : "$nombre: hasta $max caracteres";
    }

    // Un entero de $min a $max. Vacio esta bien (quien llama decide si es obligatorio).
    public static function errorDeEntero($valor, string $nombre, int $min, int $max): ?string
    {
        if (self::vacio($valor)) {
            return null;
        }
        $esEntero = is_int($valor) || (is_string($valor) && ctype_digit($valor) && strlen($valor) <= 9);
        if (!$esEntero || (int) $valor < $min || (int) $valor > $max) {
            return "$nombre tiene que ser un número entero de $min a $max";
        }
        return null;
    }

    public static function errorDeUbicacion(array $datos): ?string
    {
        $tieneLat = !self::vacio($datos["latitud"] ?? null);
        $tieneLon = !self::vacio($datos["longitud"] ?? null);
        if (!$tieneLat && !$tieneLon) {
            return null;
        }
        if ($tieneLat !== $tieneLon) {
            return "La ubicación necesita latitud y longitud juntas";
        }
        foreach (["latitud" => 90, "longitud" => 180] as $campo => $limite) {
            $valor = $datos[$campo];
            if (!(is_int($valor) || is_float($valor) || (is_string($valor) && is_numeric($valor))) || abs((float) $valor) > $limite) {
                return "La $campo tiene que ser un número entre -$limite y $limite";
            }
        }
        return null;
    }

    public static function errorDeOpcion($valor, string $nombre, array $validas): ?string
    {
        if ($valor === null || $valor === "") {
            return null;
        }
        return is_string($valor) && in_array($valor, $validas, true)
            ? null : "$nombre inválido. Valores admitidos: " . implode(", ", $validas);
    }
}
