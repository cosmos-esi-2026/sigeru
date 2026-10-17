<?php

// Paginacion y busqueda de las listas (parte 9). Clase pura. Es una COPIA IDENTICA en
// api-usuarios/core/Paginacion.php: tests/unitarias.php falla si las dos difieren.
class Paginacion
{
    public const POR_PAGINA_OMITIDO = 25;
    public const POR_PAGINA_MAXIMO = 100;
    public const LARGO_BUSQUEDA = 60;

    // Sin "pagina" en la consulta no se pagina: se devuelve todo, como siempre (lo usan el mapa y los formularios).
    public static function de(array $consulta): array
    {
        if (!isset($consulta["pagina"]) && !isset($consulta["por_pagina"])) {
            return ["paginar" => false, "pagina" => 1, "por_pagina" => 0, "error" => null];
        }
        $pagina = self::entero($consulta["pagina"] ?? "1");
        $porPagina = self::entero($consulta["por_pagina"] ?? (string) self::POR_PAGINA_OMITIDO);
        if ($pagina === null) {
            return self::mal("Parámetro 'pagina' inválido: tiene que ser un número desde 1");
        }
        if ($porPagina === null || $porPagina > self::POR_PAGINA_MAXIMO) {
            return self::mal("Parámetro 'por_pagina' inválido: un número de 1 a " . self::POR_PAGINA_MAXIMO);
        }
        return ["paginar" => true, "pagina" => $pagina, "por_pagina" => $porPagina, "error" => null];
    }

    public static function meta(int $total, int $pagina, int $porPagina): array
    {
        $paginas = max(1, (int) ceil($total / $porPagina));
        $desde = $total === 0 || $pagina > $paginas ? 0 : ($pagina - 1) * $porPagina + 1;
        $hasta = $desde === 0 ? 0 : min($total, $pagina * $porPagina);
        return ["total" => $total, "pagina" => $pagina, "por_pagina" => $porPagina, "paginas" => $paginas,
                "desde" => $desde, "hasta" => $hasta];
    }

    // Texto de busqueda: una cadena de hasta 60 caracteres, sin espacios de mas. Vacio = sin busqueda.
    public static function busqueda($valor): array
    {
        if ($valor === null || $valor === "") {
            return ["texto" => null, "error" => null];
        }
        if (!is_string($valor)) {
            return ["texto" => null, "error" => "Parámetro 'q' inválido: tiene que ser un texto"];
        }
        $texto = trim($valor);
        if (mb_strlen($texto) > self::LARGO_BUSQUEDA) {
            return ["texto" => null, "error" => "La búsqueda no puede pasar de " . self::LARGO_BUSQUEDA . " caracteres"];
        }
        return ["texto" => $texto === "" ? null : $texto, "error" => null];
    }

    // Para usar el texto dentro de un LIKE sin que % o _ funcionen como comodines.
    public static function paraLike(string $texto): string
    {
        return "%" . addcslashes($texto, "%_" . chr(92)) . "%";
    }

    private static function entero($valor): ?int
    {
        return is_string($valor) && ctype_digit($valor) && (int) $valor >= 1 && strlen($valor) <= 9 ? (int) $valor : null;
    }

    private static function mal(string $mensaje): array
    {
        return ["paginar" => false, "pagina" => 1, "por_pagina" => 0, "error" => $mensaje];
    }
}
