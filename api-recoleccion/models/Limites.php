<?php

// Topes de la demostracion (nota del profe de Programacion Full Stack, 22/10/2026; plan-rutas-defensa.md). UN solo lugar: copia IDENTICA en
// api-gestion y api-recoleccion (tests/unitarias-rutas.php falla si difieren) y las pruebas leen estas constantes en vez de repetir los numeros.
final class Limites
{
    // Contenedores EN SERVICIO (todos menos los retirados) que puede tener una zona. La zona es el municipio.
    public const MAX_CONTENEDORES_POR_ZONA = 100;

    // Paradas de una ruta: un camion no levanta 100 contenedores en un turno, asi que cada municipio se reparte en rutas de unos 20.
    public const MAX_CONTENEDORES_POR_RUTA = 25;

    public static function mensajeZonaLlena(string $zona): string
    {
        return "La zona «" . $zona . "» ya tiene " . self::MAX_CONTENEDORES_POR_ZONA . " contenedores en servicio, que es el máximo: retirá uno o elegí otra zona.";
    }

    public static function mensajeRutaLlena(): string
    {
        return "Una ruta admite hasta " . self::MAX_CONTENEDORES_POR_RUTA . " contenedores: armá otra ruta para el resto.";
    }
}
