<?php
// ============================================================================
//  SiGeRU 2026 - Modelo de usuarios y de solicitudes de registro
//
//  Los datos viven en memoria: se pierden al reiniciar el servidor. La
//  persistencia en base de datos es el entregable central de la 2da entrega.
//  Cuando se conecte, lo unico que cambia es el cuerpo de estos metodos; las
//  firmas y el contrato con los controladores se mantienen.
//
//  Las contrasenas se guardan como hash (password_hash) y se verifican con
//  password_verify. Nunca se guarda ni se compara la contrasena en texto plano,
//  ni siquiera para los usuarios de prueba.
// ============================================================================

class Usuario
{
    /** Roles que el sistema reconoce. Cualquier otro valor se rechaza. */
    public const ROLES = ["Administrador", "Cuadrilla", "Operario", "Vecino"];

    // El hash corresponde a la contrasena "123456" en los tres usuarios de
    // prueba. Sirve para probar el login sin base de datos.
    private const HASH_PRUEBA = '$2y$10$LVGBhE9hgLajmFslfYzY3uiS21FUi6Rc5p0sU1Y2ZgA3/RyQlPWte';

    private static array $usuarios = [
        [
            "id_usuario" => 1, "nombre" => "Ana", "apellido" => "Perez",
            "email" => "admin@sigeru.uy", "rol" => "Administrador", "estado" => "activo",
            "password_hash" => self::HASH_PRUEBA,
        ],
        [
            "id_usuario" => 2, "nombre" => "Luis", "apellido" => "Gomez",
            "email" => "cuadrilla@sigeru.uy", "rol" => "Cuadrilla", "estado" => "activo",
            "password_hash" => self::HASH_PRUEBA,
        ],
        [
            "id_usuario" => 3, "nombre" => "Marta", "apellido" => "Rodriguez",
            "email" => "vecino@sigeru.uy", "rol" => "Vecino", "estado" => "activo",
            "password_hash" => self::HASH_PRUEBA,
        ],
    ];

    private static array $solicitudes = [
        [
            "id_solicitud" => 1, "nombre" => "Jorge", "apellido" => "Silva",
            "email" => "jorge.silva@ejemplo.uy", "telefono" => "099123456",
            "direccion" => "Ejido 1275", "estado" => "pendiente",
            "fecha_solicitud" => "2026-09-01",
        ],
    ];

    // ------------------------------------------------------------ usuarios --

    /** Quita el hash antes de devolver un usuario hacia afuera. */
    private static function sinClave(array $u): array
    {
        unset($u["password_hash"]);
        return $u;
    }

    public static function listar(): array
    {
        return array_map([self::class, 'sinClave'], self::$usuarios);
    }

    public static function obtener(int $id): ?array
    {
        foreach (self::$usuarios as $u) {
            if ($u["id_usuario"] === $id) {
                return self::sinClave($u);
            }
        }
        return null;
    }

    /** Uso interno del control de acceso: necesita el registro completo. */
    public static function porEmail(string $email): ?array
    {
        foreach (self::$usuarios as $u) {
            if (strcasecmp($u["email"], $email) === 0) {
                return $u;
            }
        }
        return null;
    }

    public static function existeEmail(string $email): bool
    {
        return self::porEmail($email) !== null;
    }

    /**
     * Crea un usuario. El rol NUNCA sale del cuerpo de la peticion: lo decide
     * quien llama a este metodo, que es el controlador, despues de haber
     * verificado que quien pide la operacion tiene permiso para asignarlo.
     */
    public static function crear(array $datos, string $rol, string $estado = "activo"): array
    {
        if (!in_array($rol, self::ROLES, true)) {
            $rol = "Vecino";
        }

        $ids = array_column(self::$usuarios, "id_usuario");
        $nuevo = [
            "id_usuario"    => ($ids ? max($ids) : 0) + 1,
            "nombre"        => $datos["nombre"]   ?? "",
            "apellido"      => $datos["apellido"] ?? "",
            "email"         => $datos["email"]    ?? "",
            "rol"           => $rol,
            "estado"        => $estado,
            "password_hash" => password_hash($datos["password"] ?? "", PASSWORD_DEFAULT),
        ];
        self::$usuarios[] = $nuevo;
        return self::sinClave($nuevo);
    }

    public static function login(string $email, string $clave): ?array
    {
        $u = self::porEmail($email);
        if ($u === null || ($u["estado"] ?? "") !== "activo") {
            return null;
        }
        if (!password_verify($clave, $u["password_hash"] ?? "")) {
            return null;
        }
        return self::sinClave($u);
    }

    // --------------------------------------------------------- solicitudes --

    public static function listarSolicitudes(?string $estado = null): array
    {
        if ($estado === null) {
            return self::$solicitudes;
        }
        return array_values(array_filter(
            self::$solicitudes,
            fn($s) => $s["estado"] === $estado
        ));
    }

    public static function obtenerSolicitud(int $id): ?array
    {
        foreach (self::$solicitudes as $s) {
            if ($s["id_solicitud"] === $id) {
                return $s;
            }
        }
        return null;
    }

    public static function crearSolicitud(array $datos): array
    {
        $ids = array_column(self::$solicitudes, "id_solicitud");
        $nueva = [
            "id_solicitud"    => ($ids ? max($ids) : 0) + 1,
            "nombre"          => $datos["nombre"]    ?? "",
            "apellido"        => $datos["apellido"]  ?? "",
            "email"           => $datos["email"]     ?? "",
            "telefono"        => $datos["telefono"]  ?? "",
            "direccion"       => $datos["direccion"] ?? "",
            "estado"          => "pendiente",
            "fecha_solicitud" => date("Y-m-d"),
        ];
        self::$solicitudes[] = $nueva;
        return $nueva;
    }

    /**
     * Aprueba o rechaza una solicitud. Al aprobarla se crea el usuario con el
     * rol que indique el administrador, o Vecino si no indica ninguno.
     * Devuelve [solicitud, usuario|null] o null si la solicitud no existe.
     */
    public static function resolverSolicitud(int $id, string $decision, string $rol = "Vecino"): ?array
    {
        foreach (self::$solicitudes as $i => $s) {
            if ($s["id_solicitud"] !== $id) {
                continue;
            }

            self::$solicitudes[$i]["estado"] = $decision;
            $usuario = null;

            if ($decision === "aprobada") {
                $usuario = self::crear([
                    "nombre"   => $s["nombre"],
                    "apellido" => $s["apellido"],
                    "email"    => $s["email"],
                    "password" => bin2hex(random_bytes(8)),  // clave provisoria
                ], $rol, "activo");
            }

            return [self::$solicitudes[$i], $usuario];
        }
        return null;
    }
}
