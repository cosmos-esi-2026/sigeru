<?php

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
            "path"     => "/",
            "httponly" => true,
            "samesite" => "Lax",
        ]);
        session_start();
    }

    public static function iniciarSesion(array $usuario): void
    {
        self::arrancarSesion();

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
