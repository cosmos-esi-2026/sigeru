<?php

require_once __DIR__ . "/Paginacion.php";

// Filtros de la lista de usuarios (parte 9): q (nombre, apellido o correo), rol y estado. Clase pura.
class FiltrosUsuario
{
    public const ROLES = ["Administrador", "Cuadrilla", "Operario", "Vecino"];
    public const ESTADOS = ["activo", "inactivo", "pendiente"];

    public static function de(array $consulta): array
    {
        $filtros = [];

        $busqueda = Paginacion::busqueda($consulta["q"] ?? null);
        if ($busqueda["error"] !== null) {
            return ["filtros" => [], "error" => $busqueda["error"]];
        }
        if ($busqueda["texto"] !== null) {
            $filtros["q"] = $busqueda["texto"];
        }

        foreach (["rol" => self::ROLES, "estado" => self::ESTADOS] as $clave => $validos) {
            $valor = $consulta[$clave] ?? "";
            if ($valor === "") {
                continue;
            }
            if (!is_string($valor) || !in_array($valor, $validos, true)) {
                return ["filtros" => [], "error" => "Filtro '$clave' inválido. Valores admitidos: " . implode(", ", $validos)];
            }
            $filtros[$clave] = $valor;
        }

        $cuadrilla = $consulta["cuadrilla"] ?? "";
        if ($cuadrilla !== "") {
            if (!is_string($cuadrilla) || !ctype_digit($cuadrilla) || (int) $cuadrilla < 1) {
                return ["filtros" => [], "error" => "Filtro 'cuadrilla' inválido: tiene que ser el número de la cuadrilla"];
            }
            $filtros["cuadrilla"] = (int) $cuadrilla;
        }

        return ["filtros" => $filtros, "error" => null];
    }
}
