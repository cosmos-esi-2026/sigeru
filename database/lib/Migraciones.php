<?php

class Migraciones
{
    public const PATRON_ARCHIVO = '/^(\d{3})-[a-z0-9]+(-[a-z0-9]+)*\.sql$/';

    public static function archivos(string $carpeta): array
    {
        $nombres = [];
        foreach (scandir($carpeta) as $archivo) {
            if (str_ends_with($archivo, ".sql")) {
                $nombres[] = $archivo;
            }
        }
        sort($nombres, SORT_STRING);
        return $nombres;
    }

    public static function nombreValido(string $archivo): bool
    {
        return preg_match(self::PATRON_ARCHIVO, $archivo) === 1;
    }

    public static function numerosRepetidos(array $archivos): array
    {
        $vistos = [];
        $repetidos = [];
        foreach ($archivos as $archivo) {
            $numero = substr($archivo, 0, 3);
            if (isset($vistos[$numero])) {
                $repetidos[$numero] = true;
            }
            $vistos[$numero] = true;
        }
        return array_keys($repetidos);
    }

    public static function dividirSentencias(string $sql): array
    {
        $sentencias = [];
        $actual = "";
        $largo = strlen($sql);
        $comilla = null;

        for ($i = 0; $i < $largo; $i++) {
            $c = $sql[$i];
            $siguiente = $sql[$i + 1] ?? "";

            if ($comilla !== null) {
                $actual .= $c;
                if ($c === "\\" && $comilla !== "`") {
                    $actual .= $siguiente;
                    $i++;
                } elseif ($c === $comilla) {
                    $comilla = null;
                }
                continue;
            }

            if ($c === "'" || $c === '"' || $c === "`") {
                $comilla = $c;
                $actual .= $c;
                continue;
            }

            $comentarioDeLinea = ($c === "#")
                || ($c === "-" && $siguiente === "-" && in_array($sql[$i + 2] ?? "\n", [" ", "\t", "\r", "\n"], true));
            if ($comentarioDeLinea) {
                $fin = strpos($sql, "\n", $i);
                $i = $fin === false ? $largo : $fin;
                $actual .= "\n";
                continue;
            }

            if ($c === "/" && $siguiente === "*" && ($sql[$i + 2] ?? "") !== "!") {
                $fin = strpos($sql, "*/", $i + 2);
                $i = $fin === false ? $largo : $fin + 1;
                $actual .= " ";
                continue;
            }

            if ($c === ";") {
                $limpia = trim($actual);
                if ($limpia !== "") {
                    $sentencias[] = $limpia;
                }
                $actual = "";
                continue;
            }

            $actual .= $c;
        }

        $resto = trim($actual);
        if ($resto !== "") {
            $sentencias[] = $resto;
        }
        return $sentencias;
    }

    public static function registradasEnSchema(string $sql): array
    {
        $sinComentarios = preg_replace('/^\s*--.*$/m', "", $sql);
        $nombres = [];

        if (preg_match_all('/INSERT\s+INTO\s+migracion\s*\(\s*nombre\s*\)\s*VALUES(.*?);/is', $sinComentarios, $bloques)) {
            foreach ($bloques[1] as $bloque) {
                preg_match_all("/'([^']+)'/", $bloque, $encontrados);
                foreach ($encontrados[1] as $nombre) {
                    $nombres[] = $nombre;
                }
            }
        }

        sort($nombres, SORT_STRING);
        return $nombres;
    }
}
