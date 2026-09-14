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
}
