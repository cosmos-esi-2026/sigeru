<?php

require_once __DIR__ . "/../config/database.php";

class Usuario
{
    public const ROLES = ["Administrador", "Cuadrilla", "Operario", "Vecino"];

    private const CAMPOS = "u.id_usuario, u.nombre, u.apellido, u.email,
                            u.telefono, u.direccion, r.nombre AS rol, u.estado,
                            u.fecha_registro";

    private static function sinClave(array $u): array
    {
        unset($u["password_hash"]);
        return $u;
    }

    private static function idDeRol(string $rol): ?int
    {
        $sql = "SELECT id_rol FROM rol WHERE nombre = :nombre";
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute([":nombre" => $rol]);

        $id = $consulta->fetchColumn();
        return $id === false ? null : (int) $id;
    }

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
