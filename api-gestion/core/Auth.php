<?php
// ============================================================================
//  SiGeRU 2026 - Control de acceso por rol (API de gestion)
//
//  IMPORTANTE - alcance de este archivo:
//  Esto NO es un sistema de sesiones. Es el control minimo que impide que
//  cualquiera cree o modifique contenedores y camiones sin haber iniciado
//  sesion, mientras la persistencia en base de datos todavia no esta hecha.
//
//  Como funciona: el frontend manda en cada peticion las cabeceras
//  "X-Usuario-Email" y "X-Usuario-Rol" con los datos de la sesion iniciada.
//
//  Limitacion conocida y asumida: a diferencia de la API de usuarios, esta API
//  no tiene la lista de usuarios, asi que no puede comprobar que ese usuario
//  exista de verdad -- solo que la peticion venga identificada y con el rol
//  adecuado. Las cabeceras se pueden falsificar.
//
//  Cuando la persistencia este conectada, las dos APIs van a compartir la tabla
//  usuario y esta clase pasa a verificar el rol contra la base, o contra el
//  token firmado que emita el login. El punto de cambio es identificar():
//  todo el resto del codigo ya llama a exigirRol() y no hay que tocarlo.
// ============================================================================

require_once __DIR__ . "/Response.php";

class Auth
{
    /** Devuelve los datos de sesion que trae la peticion, o null si no vienen. */
    public static function identificar(): ?array
    {
        $email = $_SERVER["HTTP_X_USUARIO_EMAIL"] ?? "";
        $rol   = $_SERVER["HTTP_X_USUARIO_ROL"] ?? "";

        if ($email === "" || $rol === "") {
            return null;
        }
        return ["email" => $email, "rol" => $rol];
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

        if ($usuario["rol"] !== $rol) {
            Response::json([
                "ok"      => false,
                "mensaje" => "Se necesita el rol de $rol para hacer esta operacion",
            ], 403);
        }

        return $usuario;
    }
}
