<?php

require_once __DIR__ . "/Auth.php";

class Permisos
{
    private const TODOS = ["Administrador", "Operario", "Cuadrilla", "Vecino"];
    private const ADMINISTRADOR = ["Administrador"];
    private const PERSONAL_DE_CENTRO = ["Administrador", "Operario"];
    private const QUIEN_ATIENDE = ["Administrador", "Cuadrilla"];
    private const ANONIMO = ["*"];

    private const REGLAS = [
        "contenedores"           => ["GET" => self::TODOS, "*" => self::ADMINISTRADOR],
        "contenedores/historial" => ["GET" => ["Administrador", "Cuadrilla"]],
        "centros"                => ["GET" => self::PERSONAL_DE_CENTRO, "PUT" => self::PERSONAL_DE_CENTRO,
                                     "*" => self::ADMINISTRADOR],
        "centros/stock"          => ["GET" => self::PERSONAL_DE_CENTRO],
        "centros/movimientos"    => ["GET" => self::PERSONAL_DE_CENTRO, "POST" => self::PERSONAL_DE_CENTRO],
        "centros/correccion"     => ["POST" => self::PERSONAL_DE_CENTRO],
        "maquinaria"             => ["GET" => self::PERSONAL_DE_CENTRO, "*" => self::ADMINISTRADOR],
        "maquinaria/estado"      => ["POST" => self::PERSONAL_DE_CENTRO],
        "maquinaria/historial"   => ["GET" => self::PERSONAL_DE_CENTRO],
        "recepciones"            => ["GET" => self::PERSONAL_DE_CENTRO, "POST" => self::PERSONAL_DE_CENTRO],
        "tipos-residuo"          => ["GET" => self::TODOS, "*" => self::ADMINISTRADOR],
        "zonas"                  => ["GET" => self::TODOS, "*" => self::ADMINISTRADOR],
        "incidencias"            => ["GET" => ["Administrador", "Cuadrilla", "Vecino"],
                                     "POST" => ["Vecino"], "PUT" => self::ADMINISTRADOR],
        "incidencias/asignacion" => ["POST" => self::ADMINISTRADOR],
        "incidencias/avance"     => ["POST" => ["Cuadrilla"]],
        "incidencias/resolucion" => ["POST" => self::QUIEN_ATIENDE],
        "incidencias/descarte"   => ["POST" => self::ADMINISTRADOR],
        "incidencias/calificacion" => ["POST" => ["Vecino"]],
        "participacion"          => ["GET" => self::TODOS],
        "participacion/progreso" => ["GET" => ["Vecino"]],
        "participacion/ranking"  => ["GET" => self::TODOS],
        "incidencias/foto"       => ["GET" => ["Administrador", "Cuadrilla", "Vecino"], "POST" => ["Vecino"]],
        "estadisticas"           => ["GET" => self::ADMINISTRADOR],
        "estadisticas/centro"    => ["GET" => self::PERSONAL_DE_CENTRO],
        "publico"                => ["GET" => self::ANONIMO],
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
        $roles = self::rolesPermitidos($recurso, $metodo);
        if ($roles === self::ANONIMO) {
            return [];
        }
        return Auth::exigirRol(...$roles);
    }
}
