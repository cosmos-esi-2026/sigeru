<?php

require_once __DIR__ . "/Auth.php";

class Permisos
{
    private const ADMINISTRADOR = ["Administrador"];
    private const QUIEN_RECOLECTA = ["Administrador", "Cuadrilla"];

    private const REGLAS = [
        "camiones"                   => ["*" => self::ADMINISTRADOR],
        "cuadrillas"                 => ["GET" => self::ADMINISTRADOR],
        "rutas"                      => ["*" => self::ADMINISTRADOR],
        "recolecciones"              => ["GET" => self::QUIEN_RECOLECTA, "POST" => self::ADMINISTRADOR],
        "recolecciones/inicio"       => ["POST" => self::QUIEN_RECOLECTA],
        "recolecciones/vaciados"     => ["POST" => self::QUIEN_RECOLECTA],
        "recolecciones/fin"          => ["POST" => self::QUIEN_RECOLECTA],
        "recolecciones/cancelacion"  => ["POST" => self::ADMINISTRADOR],
        "estadisticas"               => ["GET" => self::ADMINISTRADOR],
        "proximo-pasaje"             => ["GET" => ["Vecino"]],
        "asignacion"                 => ["GET" => self::ADMINISTRADOR],
        "mis-rutas"                  => ["GET" => ["Cuadrilla"]],
    ];

    public static function recursos(): array
    {
        return array_keys(self::REGLAS);
    }

    public static function rolesPermitidos(string $recurso, string $metodo): array
    {
        $reglas = self::REGLAS[$recurso] ?? [];
        return $reglas[$metodo] ?? $reglas["*"] ?? [];
    }

    public static function exigir(string $recurso, string $metodo): array
    {
        return Auth::exigirRol(...self::rolesPermitidos($recurso, $metodo));
    }
}
