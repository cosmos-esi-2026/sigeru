<?php

require_once __DIR__ . "/Paginacion.php";

// Reglas de los datos de un contenedor y de los filtros de su lista (parte 9). Clase pura:
// lo que necesita la base (que la zona y el tipo existan) lo comprueba el controlador.
class ValidacionContenedor
{
    public const CAPACIDAD_MAXIMA = 100000;
    public const ESTADOS = ["disponible", "lleno", "mantenimiento", "retirado"];

    private static function vacio($valor): bool
    {
        return $valor === null || $valor === "";
    }

    private static function texto($valor): string
    {
        return is_string($valor) ? trim($valor) : "";
    }

    // Devuelve el primer problema encontrado, o null si los datos sirven.
    public static function error(array $datos, bool $esAlta): ?string
    {
        if ($esAlta && (self::texto($datos["codigo"] ?? null) === "" || self::texto($datos["direccion"] ?? null) === "")) {
            return "Faltan datos: código y dirección";
        }
        if (array_key_exists("codigo", $datos)) {
            $codigo = self::texto($datos["codigo"]);
            if (strlen($codigo) < 1 || strlen($codigo) > 30 || !preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/', $codigo)) {
                return "El código tiene que tener de 1 a 30 caracteres: letras, números, punto, guion o guion bajo";
            }
        }
        if (array_key_exists("direccion", $datos)) {
            $largo = mb_strlen(self::texto($datos["direccion"]));
            if ($largo < 1 || $largo > 200) {
                return "La dirección tiene que tener de 1 a 200 caracteres";
            }
        }
        if (isset($datos["estado"]) && !in_array($datos["estado"], self::ESTADOS, true)) {
            return "Estado inválido. Valores admitidos: " . implode(", ", self::ESTADOS);
        }
        if (array_key_exists("capacidad", $datos) && !self::vacio($datos["capacidad"])) {
            $capacidad = $datos["capacidad"];
            $esEntero = is_int($capacidad) || (is_string($capacidad) && ctype_digit($capacidad));
            if (!$esEntero || (int) $capacidad < 1 || (int) $capacidad > self::CAPACIDAD_MAXIMA) {
                return "La capacidad tiene que ser un número entero de litros, de 1 a " . self::CAPACIDAD_MAXIMA;
            }
        }
        $error = self::errorDeUbicacion($datos);
        if ($error !== null) {
            return $error;
        }
        if (!self::vacio($datos["id_zona"] ?? null) && !self::esId($datos["id_zona"])) {
            return "La zona tiene que ser el número de identificación de una zona";
        }
        if (array_key_exists("tipo_residuo", $datos) && !self::vacio($datos["tipo_residuo"]) && !is_string($datos["tipo_residuo"])) {
            return "El tipo de residuo tiene que ser un texto";
        }
        if ($esAlta) {
            if (self::vacio($datos["id_zona"] ?? null) && self::texto($datos["zona"] ?? null) === "") {
                return "Indicá la zona del contenedor";
            }
            if (self::texto($datos["tipo_residuo"] ?? null) === "") {
                return "Indicá el tipo de residuo del contenedor";
            }
        }
        return null;
    }

    private static function esId($valor): bool
    {
        return (is_int($valor) && $valor > 0) || (is_string($valor) && ctype_digit($valor) && (int) $valor > 0);
    }

    private static function errorDeUbicacion(array $datos): ?string
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

    // Filtros de la lista: q (texto), estado, zona (id) y tipo (nombre).
    public static function filtrosDe(array $consulta): array
    {
        $filtros = [];

        $busqueda = Paginacion::busqueda($consulta["q"] ?? null);
        if ($busqueda["error"] !== null) {
            return ["filtros" => [], "error" => $busqueda["error"]];
        }
        if ($busqueda["texto"] !== null) {
            $filtros["q"] = $busqueda["texto"];
        }

        $estado = $consulta["estado"] ?? "";
        if ($estado !== "") {
            if (!is_string($estado) || !in_array($estado, self::ESTADOS, true)) {
                return ["filtros" => [], "error" => "Filtro 'estado' inválido. Valores admitidos: " . implode(", ", self::ESTADOS)];
            }
            $filtros["estado"] = $estado;
        }

        $zona = $consulta["zona"] ?? "";
        if ($zona !== "") {
            if (!self::esId($zona)) {
                return ["filtros" => [], "error" => "Filtro 'zona' inválido: tiene que ser el número de identificación"];
            }
            $filtros["zona"] = (int) $zona;
        }

        $tipo = $consulta["tipo"] ?? "";
        if ($tipo !== "") {
            if (!is_string($tipo) || mb_strlen($tipo) > 60) {
                return ["filtros" => [], "error" => "Filtro 'tipo' inválido: tiene que ser el nombre de un tipo de residuo"];
            }
            $filtros["tipo"] = $tipo;
        }

        return ["filtros" => $filtros, "error" => null];
    }
}
