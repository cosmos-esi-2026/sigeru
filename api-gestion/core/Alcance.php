<?php

require_once __DIR__ . "/Response.php";
require_once __DIR__ . "/../models/AlcanceCentro.php";

// Limita al Operario a su centro de acopio (extras, parte 12). El Administrador no tiene limite.
class Alcance
{
    // El centro al que esta limitado el usuario, o null si ve todos. Un Operario sin centro asignado no puede operar.
    public static function centro(array $usuario): ?int
    {
        if (($usuario["rol"] ?? "") === "Administrador") {
            return null;
        }
        $centro = AlcanceCentro::centroDelUsuario($usuario);
        if ($centro === null) {
            Response::error("Todavía no tenés un centro de acopio asignado. Pedile al administrador que te lo asigne.", 403);
        }
        return $centro;
    }

    // Un centro ajeno no se distingue de uno que no existe.
    public static function exigir(array $usuario, int $idCentro): void
    {
        if (!AlcanceCentro::puede($usuario, $idCentro)) {
            Response::error("Centro de acopio no encontrado", 404);
        }
    }
}
