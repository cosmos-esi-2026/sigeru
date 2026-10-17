<?php

require_once __DIR__ . "/Auth.php";
require_once __DIR__ . "/../config/database.php";

// Registro de auditoria (OWASP A09). Es una COPIA IDENTICA en las tres APIs: tests/unitarias.php falla si difieren.
// Response::json() llama a registrar() justo antes de responder: asi la persona ya esta identificada y la respuesta
// (su codigo) ya esta decidida. Si registrar falla, la peticion sigue: la auditoria no puede tirar el sistema.
class Auditoria
{
    private const METODOS = ["POST", "PUT", "DELETE"];

    private static ?array $recordado = null;

    // Para acciones que destruyen la sesion antes de responder (cerrar sesion): se anota quien era antes.
    public static function recordarUsuario(?array $usuario): void
    {
        self::$recordado = $usuario;
    }

    // Solo cambios que salieron bien (2xx) y accesos negados por permisos (403). Los GET no se registran.
    public static function debeRegistrarse(string $metodo, int $codigo): bool
    {
        return in_array($metodo, self::METODOS, true) && (($codigo >= 200 && $codigo < 300) || $codigo === 403);
    }

    // Solo el camino, sin parametros ni caracteres de control, y acotado al tamano de la columna.
    public static function rutaLimpia(string $uri): string
    {
        $camino = (string) parse_url($uri, PHP_URL_PATH);
        $camino = preg_replace('/[^\x20-\x7E]/', "?", $camino) ?? "";
        $camino = preg_replace('#^/sigeru#', "", $camino) ?? $camino;
        return substr($camino === "" ? "/" : $camino, 0, 200);
    }

    public static function registrar(int $codigo): void
    {
        $metodo = $_SERVER["REQUEST_METHOD"] ?? "";
        if (!self::debeRegistrarse($metodo, $codigo)) {
            return;
        }
        try {
            $usuario = self::$recordado;
            if ($usuario === null && (session_status() === PHP_SESSION_ACTIVE || isset($_COOKIE["SIGERUSESSID"]))) {
                $usuario = Auth::identificar();
            }
            $consulta = Database::conexion()->prepare(
                "INSERT INTO auditoria (id_usuario, rol, metodo, ruta, codigo, ip)
                 VALUES (:usuario, :rol, :metodo, :ruta, :codigo, :ip)"
            );
            $consulta->execute([
                ":usuario" => $usuario["id_usuario"] ?? null,
                ":rol"     => $usuario["rol"] ?? null,
                ":metodo"  => $metodo,
                ":ruta"    => self::rutaLimpia($_SERVER["REQUEST_URI"] ?? "/"),
                ":codigo"  => $codigo,
                ":ip"      => substr((string) ($_SERVER["REMOTE_ADDR"] ?? "?"), 0, 45),
            ]);
        } catch (Throwable $e) {
            error_log("SiGeRU auditoria: no se pudo registrar (" . $e->getMessage() . ")");
        }
    }

    // Filtros de la lista: usuario (id), metodo, resultado (hecho/negado), desde y hasta (AAAA-MM-DD) y q (en la ruta).
    public static function filtrosDe(array $consulta): array
    {
        $filtros = [];
        $usuario = $consulta["usuario"] ?? "";
        if ($usuario !== "") {
            if (!is_string($usuario) || !ctype_digit($usuario) || (int) $usuario < 1) {
                return ["filtros" => [], "error" => "Filtro 'usuario' inválido: tiene que ser el número de identificación"];
            }
            $filtros["usuario"] = (int) $usuario;
        }
        $metodo = $consulta["metodo"] ?? "";
        if ($metodo !== "") {
            if (!is_string($metodo) || !in_array($metodo, self::METODOS, true)) {
                return ["filtros" => [], "error" => "Filtro 'metodo' inválido. Valores admitidos: " . implode(", ", self::METODOS)];
            }
            $filtros["metodo"] = $metodo;
        }
        $resultado = $consulta["resultado"] ?? "";
        if ($resultado !== "") {
            if (!in_array($resultado, ["hecho", "negado"], true)) {
                return ["filtros" => [], "error" => "Filtro 'resultado' inválido. Valores admitidos: hecho, negado"];
            }
            $filtros["resultado"] = $resultado;
        }
        foreach (["desde", "hasta"] as $clave) {
            $valor = $consulta[$clave] ?? "";
            if ($valor === "") {
                continue;
            }
            $fecha = is_string($valor) ? DateTime::createFromFormat("!Y-m-d", $valor) : false;
            if ($fecha === false || $fecha->format("Y-m-d") !== $valor) {
                return ["filtros" => [], "error" => "Filtro '$clave' inválido: usá el formato AAAA-MM-DD"];
            }
            $filtros[$clave] = $valor;
        }
        if (isset($filtros["desde"], $filtros["hasta"]) && $filtros["desde"] > $filtros["hasta"]) {
            return ["filtros" => [], "error" => "La fecha 'desde' no puede ser posterior a 'hasta'"];
        }
        $q = $consulta["q"] ?? "";
        if ($q !== "") {
            if (!is_string($q) || mb_strlen(trim($q)) > 60) {
                return ["filtros" => [], "error" => "La búsqueda no puede pasar de 60 caracteres"];
            }
            if (trim($q) !== "") {
                $filtros["q"] = trim($q);
            }
        }
        return ["filtros" => $filtros, "error" => null];
    }

    private static function condiciones(array $filtros): array
    {
        $donde = [];
        $valores = [];
        if (isset($filtros["usuario"])) {
            $donde[] = "a.id_usuario = :usuario";
            $valores[":usuario"] = $filtros["usuario"];
        }
        if (isset($filtros["metodo"])) {
            $donde[] = "a.metodo = :metodo";
            $valores[":metodo"] = $filtros["metodo"];
        }
        if (isset($filtros["resultado"])) {
            $donde[] = $filtros["resultado"] === "negado" ? "a.codigo = 403" : "a.codigo < 400";
        }
        if (isset($filtros["desde"])) {
            $donde[] = "a.fecha_hora >= :desde";
            $valores[":desde"] = $filtros["desde"] . " 00:00:00";
        }
        if (isset($filtros["hasta"])) {
            $donde[] = "a.fecha_hora <= :hasta";
            $valores[":hasta"] = $filtros["hasta"] . " 23:59:59";
        }
        if (isset($filtros["q"])) {
            $donde[] = "a.ruta LIKE :q";
            $valores[":q"] = "%" . addcslashes($filtros["q"], "%_" . chr(92)) . "%";
        }
        return [$donde === [] ? "" : " WHERE " . implode(" AND ", $donde), $valores];
    }

    public static function contar(array $filtros = []): int
    {
        [$donde, $valores] = self::condiciones($filtros);
        $consulta = Database::conexion()->prepare("SELECT COUNT(*) FROM auditoria a" . $donde);
        $consulta->execute($valores);
        return (int) $consulta->fetchColumn();
    }

    public static function listar(array $filtros, int $pagina, int $porPagina): array
    {
        [$donde, $valores] = self::condiciones($filtros);
        $consulta = Database::conexion()->prepare(
            "SELECT a.id_auditoria, a.fecha_hora, a.id_usuario, CONCAT(u.nombre, ' ', u.apellido) AS usuario, u.email,
                    a.rol, a.metodo, a.ruta, a.codigo, a.ip
             FROM auditoria a LEFT JOIN usuario u ON u.id_usuario = a.id_usuario" . $donde .
            " ORDER BY a.id_auditoria DESC LIMIT :limite OFFSET :desplazamiento"
        );
        foreach ($valores as $nombre => $valor) {
            $consulta->bindValue($nombre, $valor);
        }
        $consulta->bindValue(":limite", $porPagina, PDO::PARAM_INT);
        $consulta->bindValue(":desplazamiento", ($pagina - 1) * $porPagina, PDO::PARAM_INT);
        $consulta->execute();
        return array_map(function (array $fila) {
            $fila["id_auditoria"] = (int) $fila["id_auditoria"];
            $fila["id_usuario"] = $fila["id_usuario"] === null ? null : (int) $fila["id_usuario"];
            $fila["codigo"] = (int) $fila["codigo"];
            return $fila;
        }, $consulta->fetchAll());
    }
}
