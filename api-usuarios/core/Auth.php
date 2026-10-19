<?php

require_once __DIR__ . "/Response.php";
require_once __DIR__ . "/../config/database.php";

class Auth
{
    private const NOMBRE_SESION = "SIGERUSESSID";

    // Una sesion sin ninguna actividad por 2 horas se cierra sola (cada pedido renueva el plazo).
    public const INACTIVIDAD_MAXIMA = 7200;

    public static function vencida(?int $ultimaActividad, int $ahora, int $maximo = self::INACTIVIDAD_MAXIMA): bool
    {
        return $ultimaActividad !== null && $ahora - $ultimaActividad > $maximo;
    }

    private static ?array $identificado = null;
    private static bool $consultado = false;

    private static function esHttps(): bool
    {
        return (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off")
            || strtolower($_SERVER["HTTP_X_FORWARDED_PROTO"] ?? "") === "https";
    }

    private static function arrancarSesion(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }

        // Una cookie de sesion que el servidor no emitio no se acepta (evita fijar la sesion de otro),
        // y viaja solo por cookie. "secure" se prende solo cuando la conexion es HTTPS.
        ini_set("session.use_strict_mode", "1");
        ini_set("session.use_only_cookies", "1");
        session_name(self::NOMBRE_SESION);
        session_set_cookie_params([
            "path"     => "/",
            "httponly" => true,
            "samesite" => "Lax",
            "secure"   => self::esHttps(),
        ]);
        session_start();
    }

    public static function iniciarSesion(array $usuario): void
    {
        self::arrancarSesion();

        session_regenerate_id(true);

        $_SESSION["id_usuario"] = (int) $usuario["id_usuario"];
        $_SESSION["ultima_actividad"] = time();
        self::$consultado = false;
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

        self::$identificado = null;
        self::$consultado = false;
    }

    public static function identificar(): ?array
    {
        if (self::$consultado) {
            return self::$identificado;
        }

        self::arrancarSesion();
        self::$consultado = true;
        self::$identificado = null;

        if (empty($_SESSION["id_usuario"])) {
            return null;
        }
        if (self::vencida(isset($_SESSION["ultima_actividad"]) ? (int) $_SESSION["ultima_actividad"] : null, time())) {
            $_SESSION = [];
            session_regenerate_id(true);
            return null;
        }
        $_SESSION["ultima_actividad"] = time();

        $sql = "SELECT u.id_usuario, u.nombre, u.apellido, u.email,
                       r.nombre AS rol, u.estado, u.id_cuadrilla, u.id_centro
                FROM usuario u
                JOIN rol r ON r.id_rol = u.id_rol
                WHERE u.id_usuario = :id";

        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute([":id" => $_SESSION["id_usuario"]]);

        $usuario = $consulta->fetch();
        self::$identificado = $usuario === false ? null : $usuario;
        return self::$identificado;
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

    public static function exigirRol(string ...$roles): array
    {
        $usuario = self::exigirSesion();

        if (!in_array($usuario["rol"], $roles, true)) {
            Response::error(
                count($roles) === 1
                    ? "Se necesita el rol de " . $roles[0] . " para hacer esta operación"
                    : "Tu rol no tiene permiso para hacer esta operación",
                403
            );
        }

        return $usuario;
    }
}
