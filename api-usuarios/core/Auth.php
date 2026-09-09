<?php
// ============================================================================
//  SiGeRU 2026 - Sesion y control de acceso por rol
//
//  SESION REAL (2da entrega). Reemplaza al control anterior, que confiaba en
//  las cabeceras X-Usuario-Email / X-Usuario-Rol que mandaba el navegador.
//  Aquello se podia falsificar escribiendo dos cabeceras a mano: cualquiera
//  podia declararse Administrador.
//
//  Como funciona ahora:
//  - El login guarda el id del usuario en la sesion del SERVIDOR. El navegador
//    solo recibe una cookie con el identificador de sesion, que no dice nada
//    de quien es ni que rol tiene.
//  - En cada peticion, el rol se vuelve a leer DE LA BASE. Si un administrador
//    da de baja a alguien que esta con la sesion abierta, la siguiente peticion
//    de esa persona ya no pasa.
//  - La cookie va con path "/" para que las dos APIs (usuarios y gestion)
//    compartan la misma sesion: se sirven desde el mismo origen bajo Apache.
//
//  Este archivo es identico en api-usuarios y api-gestion.
// ============================================================================

require_once __DIR__ . "/Response.php";
require_once __DIR__ . "/../config/database.php";

class Auth
{
    private const NOMBRE_SESION = "SIGERUSESSID";

    private static function arrancarSesion(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }

        session_name(self::NOMBRE_SESION);
        session_set_cookie_params([
            "path"     => "/",        // compartida por las dos APIs y el frontend
            "httponly" => true,       // el JavaScript no puede leer la cookie
            "samesite" => "Lax",      // no viaja en peticiones de otros sitios
        ]);
        session_start();
    }

    /** Guarda la sesion despues de un login correcto. */
    public static function iniciarSesion(array $usuario): void
    {
        self::arrancarSesion();

        // Evita el robo de sesion por fijacion: se cambia el identificador al
        // momento de autenticarse.
        session_regenerate_id(true);

        $_SESSION["id_usuario"] = (int) $usuario["id_usuario"];
    }

    public static function cerrarSesion(): void
    {
        self::arrancarSesion();

        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $p = session_get_cookie_params();
            setcookie(session_name(), "", time() - 42000, $p["path"], $p["domain"] ?? "",
                      $p["secure"] ?? false, $p["httponly"] ?? true);
        }
        session_destroy();
    }

    /**
     * Devuelve el usuario que hace la peticion, o null si no hay sesion.
     * Relee siempre de la base: el rol y el estado que valen son los de ahora,
     * no los que tenia cuando inicio sesion.
     */
    public static function identificar(): ?array
    {
        self::arrancarSesion();

        if (empty($_SESSION["id_usuario"])) {
            return null;
        }

        $sql = "SELECT u.id_usuario, u.nombre, u.apellido, u.email,
                       r.nombre AS rol, u.estado
                FROM usuario u
                JOIN rol r ON r.id_rol = u.id_rol
                WHERE u.id_usuario = :id";

        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute([":id" => $_SESSION["id_usuario"]]);

        $usuario = $consulta->fetch();
        return $usuario === false ? null : $usuario;
    }

    /** Corta la peticion con 401/403 si quien la hace no tiene el rol pedido. */
    public static function exigirRol(string $rol): array
    {
        $usuario = self::identificar();

        if ($usuario === null) {
            Response::error("Hay que iniciar sesión para hacer esta operación", 401);
        }
        if ($usuario["estado"] !== "activo") {
            Response::error("La cuenta no está activa", 403);
        }
        if ($usuario["rol"] !== $rol) {
            Response::error("Se necesita el rol de $rol para hacer esta operación", 403);
        }

        return $usuario;
    }

    /** Quien esta logueado, sin exigir un rol concreto. */
    public static function exigirSesion(): array
    {
        $usuario = self::identificar();

        if ($usuario === null) {
            Response::error("Hay que iniciar sesión para hacer esta operación", 401);
        }
        if ($usuario["estado"] !== "activo") {
            Response::error("La cuenta no está activa", 403);
        }

        return $usuario;
    }
}
