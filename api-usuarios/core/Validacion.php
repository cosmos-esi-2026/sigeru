<?php

class Validacion
{

    public const CLAVE_MINIMA = 8;

    public static function email(string $valor): bool
    {
        return filter_var($valor, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function contrasena(string $valor): bool
    {
        return strlen($valor) >= self::CLAVE_MINIMA;
    }

    // Deja solo los digitos: "1.234.567-2" y " 12345672 " son la misma cedula. Devuelve null si no es texto o queda vacio.
    public static function soloDigitosCedula($valor): ?string
    {
        if (!is_string($valor) && !is_int($valor)) {
            return null;
        }
        $digitos = preg_replace('/\D+/', '', (string) $valor);
        return $digitos === "" ? null : $digitos;
    }

    // Cedula uruguaya: 7 u 8 digitos (con el verificador al final) y verificador correcto (pesos 2987634).
    public static function cedula($valor): bool
    {
        // Solo digitos, puntos, guion y espacios: una letra suelta no se descarta en silencio.
        if (is_string($valor) && preg_match('/[^\d.\-\s]/', $valor)) {
            return false;
        }
        $digitos = self::soloDigitosCedula($valor);
        if ($digitos === null || strlen($digitos) < 7 || strlen($digitos) > 8) {
            return false;
        }
        $cuerpo = str_pad(substr($digitos, 0, -1), 7, "0", STR_PAD_LEFT);
        $suma = 0;
        foreach (str_split("2987634") as $posicion => $peso) {
            $suma += (int) $cuerpo[$posicion] * (int) $peso;
        }
        return (int) substr($digitos, -1) === (10 - $suma % 10) % 10;
    }

    // La forma en que se guarda y se busca: solo digitos. Llamar solo con una cedula ya validada.
    public static function normalizarCedula(string $valor): string
    {
        return (string) self::soloDigitosCedula($valor);
    }
}
