<?php

class PoliticaBloqueo
{
    public const MAXIMO_FALLOS = 5;
    public const VENTANA_SEGUNDOS = 900;

    public static function normalizar($email): string
    {
        if (!is_string($email)) {
            return "";
        }
        return substr(mb_strtolower(trim($email)), 0, 120);
    }

    public static function segundosDeEspera(array $instantesFallidos, int $ahora): int
    {
        $enVentana = array_values(array_filter(
            $instantesFallidos,
            fn($instante) => $instante > $ahora - self::VENTANA_SEGUNDOS
        ));

        if (count($enVentana) < self::MAXIMO_FALLOS) {
            return 0;
        }

        rsort($enVentana);
        $quintoMasReciente = $enVentana[self::MAXIMO_FALLOS - 1];

        return max(0, $quintoMasReciente + self::VENTANA_SEGUNDOS - $ahora);
    }
}
