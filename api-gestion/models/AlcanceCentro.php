<?php

// A que centro de acopio llega cada rol (extras, parte 12). Clase pura.
// El Administrador ve todos; el Operario, solo el que tiene asignado; los demas, ninguno.
class AlcanceCentro
{
    public static function centroDelUsuario(array $usuario): ?int
    {
        if (($usuario["rol"] ?? "") !== "Operario") {
            return null;
        }
        $centro = $usuario["id_centro"] ?? null;
        return $centro === null ? null : (int) $centro;
    }

    public static function puede(array $usuario, int $idCentro): bool
    {
        return match ($usuario["rol"] ?? "") {
            "Administrador" => true,
            "Operario"      => self::centroDelUsuario($usuario) === $idCentro,
            default         => false,
        };
    }
}
