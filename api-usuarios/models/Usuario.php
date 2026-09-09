<?php
// ============================================================================
//  SiGeRU 2026 - Modelo de usuarios y de solicitudes de registro
//
//  PERSISTENCIA REAL (2da entrega): los datos viven en MySQL, no en memoria.
//  Las firmas de los metodos y el contrato con los controladores son los
//  mismos que tenia la version en memoria: lo unico que cambio es el cuerpo.
//
//  Reglas que se sostienen:
//  - Todas las consultas son PREPARADAS. Nunca se concatena un valor dentro
//    del SQL, ni siquiera un id que "seguro es un numero".
//  - Las contrasenas se guardan como hash (password_hash) y se verifican con
//    password_verify. Nunca en texto plano.
//  - El hash NUNCA sale de esta clase hacia afuera (ver sinClave()).
//  - El rol viaja como nombre ("Administrador") en el JSON, aunque en la base
//    sea una clave foranea a la tabla rol. Eso mantiene el contrato de la API.
// ============================================================================

require_once __DIR__ . "/../config/database.php";

class Usuario
{
    /** Roles que el sistema reconoce. Cualquier otro valor se rechaza. */
    public const ROLES = ["Administrador", "Cuadrilla", "Operario", "Vecino"];

    /** Columnas que se devuelven hacia afuera, con el rol ya resuelto a nombre. */
    private const CAMPOS = "u.id_usuario, u.nombre, u.apellido, u.email,
                            u.telefono, u.direccion, r.nombre AS rol, u.estado,
                            u.fecha_registro";

    /** Quita el hash antes de devolver un usuario hacia afuera. */
    private static function sinClave(array $u): array
    {
        unset($u["password_hash"]);
        return $u;
    }

    /** Traduce el nombre del rol a su id. Devuelve null si el rol no existe. */
    private static function idDeRol(string $rol): ?int
    {
        $sql = "SELECT id_rol FROM rol WHERE nombre = :nombre";
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute([":nombre" => $rol]);

        $id = $consulta->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    // ------------------------------------------------------------ usuarios --

    public static function listar(): array
    {
        $sql = "SELECT " . self::CAMPOS . "
                FROM usuario u
                JOIN rol r ON r.id_rol = u.id_rol
                ORDER BY u.id_usuario";

        return Database::conexion()->query($sql)->fetchAll();
    }

    public static function obtener(int $id): ?array
    {
        $sql = "SELECT " . self::CAMPOS . "
                FROM usuario u
                JOIN rol r ON r.id_rol = u.id_rol
                WHERE u.id_usuario = :id";

        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute([":id" => $id]);

        $usuario = $consulta->fetch();
        return $usuario === false ? null : $usuario;
    }

    /** Uso interno del control de acceso: necesita el registro completo. */
    public static function porEmail(string $email): ?array
    {
        $sql = "SELECT " . self::CAMPOS . ", u.password_hash
                FROM usuario u
                JOIN rol r ON r.id_rol = u.id_rol
                WHERE u.email = :email";

        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute([":email" => $email]);

        $usuario = $consulta->fetch();
        return $usuario === false ? null : $usuario;
    }

    public static function existeEmail(string $email): bool
    {
        return self::porEmail($email) !== null;
    }

    /**
     * Crea un usuario. El rol NUNCA sale del cuerpo de la peticion: lo decide
     * quien llama a este metodo, que es el controlador, despues de haber
     * verificado que quien pide la operacion tiene permiso para asignarlo.
     *
     * Si no viene contrasena (el alta desde el panel no la pide), se genera una
     * provisoria al azar: es preferible a guardar el hash de una cadena vacia,
     * que dejaria la cuenta abierta con solo mandar "" como contrasena.
     */
    public static function crear(array $datos, string $rol, string $estado = "activo"): array
    {
        if (!in_array($rol, self::ROLES, true)) {
            $rol = "Vecino";
        }
        $idRol = self::idDeRol($rol);

        $clave = $datos["password"] ?? "";
        if ($clave === "") {
            $clave = bin2hex(random_bytes(8));
        }

        $sql = "INSERT INTO usuario
                   (nombre, apellido, email, password_hash, telefono, direccion,
                    id_rol, id_solicitud, estado)
                VALUES
                   (:nombre, :apellido, :email, :hash, :telefono, :direccion,
                    :id_rol, :id_solicitud, :estado)";

        $conexion = Database::conexion();
        $consulta = $conexion->prepare($sql);
        $consulta->execute([
            ":nombre"       => $datos["nombre"]    ?? "",
            ":apellido"     => $datos["apellido"]  ?? "",
            ":email"        => $datos["email"]     ?? "",
            ":hash"         => password_hash($clave, PASSWORD_DEFAULT),
            ":telefono"     => $datos["telefono"]  ?? null,
            ":direccion"    => $datos["direccion"] ?? null,
            ":id_rol"       => $idRol,
            ":id_solicitud" => $datos["id_solicitud"] ?? null,
            ":estado"       => $estado,
        ]);

        return self::obtener((int) $conexion->lastInsertId());
    }

    /**
     * Modifica un usuario. Solo toca los campos que vengan en $datos: lo que no
     * se manda, queda como estaba.
     */
    public static function actualizar(int $id, array $datos, ?string $rol = null): ?array
    {
        $partes = [];
        $valores = [":id" => $id];

        foreach (["nombre", "apellido", "email", "telefono", "direccion"] as $campo) {
            if (array_key_exists($campo, $datos)) {
                $partes[] = "$campo = :$campo";
                $valores[":$campo"] = $datos[$campo];
            }
        }

        if ($rol !== null && in_array($rol, self::ROLES, true)) {
            $partes[] = "id_rol = :id_rol";
            $valores[":id_rol"] = self::idDeRol($rol);
        }

        if ($partes === []) {
            return self::obtener($id);
        }

        $sql = "UPDATE usuario SET " . implode(", ", $partes) . " WHERE id_usuario = :id";
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute($valores);

        return self::obtener($id);
    }

    /**
     * Baja LOGICA: el usuario pasa a inactivo y deja de poder entrar, pero su
     * historial (incidencias reportadas, cuadrilla que integraba) se conserva.
     * Borrarlo de verdad rompería esas referencias.
     */
    public static function darDeBaja(int $id): ?array
    {
        $sql = "UPDATE usuario SET estado = 'inactivo' WHERE id_usuario = :id";
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute([":id" => $id]);

        return self::obtener($id);
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
            $sql = "SELECT * FROM solicitud_registro ORDER BY fecha_solicitud DESC";
            return Database::conexion()->query($sql)->fetchAll();
        }

        $sql = "SELECT * FROM solicitud_registro
                WHERE estado = :estado
                ORDER BY fecha_solicitud DESC";
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute([":estado" => $estado]);

        return $consulta->fetchAll();
    }

    public static function obtenerSolicitud(int $id): ?array
    {
        $sql = "SELECT * FROM solicitud_registro WHERE id_solicitud = :id";
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute([":id" => $id]);

        $solicitud = $consulta->fetch();
        return $solicitud === false ? null : $solicitud;
    }

    public static function crearSolicitud(array $datos): array
    {
        $sql = "INSERT INTO solicitud_registro
                   (nombre, apellido, email, telefono, direccion, estado)
                VALUES
                   (:nombre, :apellido, :email, :telefono, :direccion, 'pendiente')";

        $conexion = Database::conexion();
        $consulta = $conexion->prepare($sql);
        $consulta->execute([
            ":nombre"    => $datos["nombre"]    ?? "",
            ":apellido"  => $datos["apellido"]  ?? "",
            ":email"     => $datos["email"]     ?? "",
            ":telefono"  => $datos["telefono"]  ?? null,
            ":direccion" => $datos["direccion"] ?? null,
        ]);

        return self::obtenerSolicitud((int) $conexion->lastInsertId());
    }

    /**
     * Aprueba o rechaza una solicitud. Al aprobarla se crea el usuario con el
     * rol que indique el administrador, o Vecino si no indica ninguno.
     * Devuelve [solicitud, usuario|null] o null si la solicitud no existe.
     *
     * Las dos operaciones (cambiar el estado y crear el usuario) van dentro de
     * una TRANSACCION: si el alta del usuario falla, la solicitud no puede
     * quedar marcada como aprobada sin que exista la cuenta.
     */
    public static function resolverSolicitud(int $id, string $decision, string $rol = "Vecino"): ?array
    {
        $solicitud = self::obtenerSolicitud($id);
        if ($solicitud === null) {
            return null;
        }

        $conexion = Database::conexion();
        $conexion->beginTransaction();

        try {
            $sql = "UPDATE solicitud_registro SET estado = :estado WHERE id_solicitud = :id";
            $consulta = $conexion->prepare($sql);
            $consulta->execute([":estado" => $decision, ":id" => $id]);

            $usuario = null;
            if ($decision === "aprobada") {
                // La contrasena provisoria la genera crear() sola. El vinculo con
                // la solicitud queda guardado: es la relacion "origina" del modelo.
                $usuario = self::crear([
                    "nombre"       => $solicitud["nombre"],
                    "apellido"     => $solicitud["apellido"],
                    "email"        => $solicitud["email"],
                    "telefono"     => $solicitud["telefono"],
                    "direccion"    => $solicitud["direccion"],
                    "id_solicitud" => $id,
                ], $rol, "activo");
            }

            $conexion->commit();
            return [self::obtenerSolicitud($id), $usuario];
        } catch (PDOException $e) {
            $conexion->rollBack();
            throw $e;
        }
    }
}
