<?php
// ============================================================================
//  SiGeRU 2026 - Control de acceso por rol
//
//  IMPORTANTE - alcance de este archivo:
//  Esto NO es un sistema de sesiones. Es el control minimo que permite que los
//  endpoints que modifican datos dejen de estar abiertos a cualquiera mientras
//  la persistencia en base de datos todavia no esta hecha.
//
//  Como funciona: el frontend manda en cada peticion la cabecera
//  "X-Usuario-Email" con el email del usuario que inicio sesion. La API busca
//  ese usuario y verifica su rol antes de dejar pasar la operacion.
//
//  Limitacion conocida y asumida: la cabecera se puede falsificar, porque
//  todavia no hay token ni sesion firmada. Aun asi cierra el agujero grave que
//  habia -- que el propio cuerpo del POST decidiera el rol del usuario que se
//  estaba creando --, y deja el punto exacto donde hay que enchufar la
//  autenticacion real cuando la base de datos este conectada: alcanza con
//  cambiar el cuerpo de identificar() por la validacion del token.
// ============================================================================

require_once __DIR__ . "/Response.php";
require_once __DIR__ . "/../models/Usuario.php";

class Auth
{
    /** Devuelve el usuario que hace la peticion, o null si no se identifico. */
    public static function identificar(): ?array
    {
        $email = $_SERVER["HTTP_X_USUARIO_EMAIL"] ?? "";
        if ($email === "") {
            return null;
        }
        return Usuario::porEmail($email);
    }

    /** Corta la peticion con 401/403 si quien la hace no tiene el rol pedido. */
    public static function exigirRol(string $rol): array
    {
        $usuario = self::identificar();

        if ($usuario === null) {
            Response::json([
                "ok"      => false,
                "mensaje" => "Hay que iniciar sesion para hacer esta operacion",
            ], 401);
        }

        if (($usuario["estado"] ?? "") !== "activo") {
            Response::json([
                "ok"      => false,
                "mensaje" => "La cuenta no esta activa",
            ], 403);
        }

        if (($usuario["rol"] ?? "") !== $rol) {
            Response::json([
                "ok"      => false,
                "mensaje" => "Se necesita el rol de $rol para hacer esta operacion",
            ], 403);
        }

        return $usuario;
    }
}
