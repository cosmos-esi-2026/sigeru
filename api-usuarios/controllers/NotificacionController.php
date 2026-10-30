<?php

require_once __DIR__ . "/../models/Notificacion.php";
require_once __DIR__ . "/../core/Auth.php";
require_once __DIR__ . "/../core/Response.php";

// La campanita (extras, parte 13). Cualquier persona con sesion.
class NotificacionController
{
    public function listar(): void
    {
        $usuario = Auth::exigirSesion();
        $limite = Notificacion::LIMITE_OMITIDO;
        if (isset($_GET["limite"])) {
            $pedido = $_GET["limite"];
            if (!is_string($pedido) || !ctype_digit($pedido) || (int) $pedido < 1 || (int) $pedido > Notificacion::LIMITE_MAXIMO) {
                Response::error("Parámetro 'limite' inválido: un número de 1 a " . Notificacion::LIMITE_MAXIMO, 400);
            }
            $limite = (int) $pedido;
        }
        $soloNoLeidas = ($_GET["solo_no_leidas"] ?? "") === "1";
        $id = (int) $usuario["id_usuario"];
        Response::json(["ok" => true, "datos" => Notificacion::listar($id, $limite, $soloNoLeidas), "no_leidas" => Notificacion::contarNoLeidas($id)]);
    }

    public function marcarLeida(int $id): void
    {
        $usuario = Auth::exigirSesion();
        if (!Notificacion::marcarLeida($id, (int) $usuario["id_usuario"])) {
            Response::error("Aviso no encontrado", 404);
        }
        Response::json(["ok" => true, "mensaje" => "Aviso marcado como leído", "no_leidas" => Notificacion::contarNoLeidas((int) $usuario["id_usuario"])]);
    }

    public function leerTodas(): void
    {
        $usuario = Auth::exigirSesion();
        $cuantas = Notificacion::marcarTodas((int) $usuario["id_usuario"]);
        Response::json(["ok" => true, "mensaje" => "Avisos marcados como leídos", "marcadas" => $cuantas, "no_leidas" => 0]);
    }
}
