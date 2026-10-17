<?php

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../core/Paginacion.php";

class Usuario
{
    public const ROLES = ["Administrador", "Cuadrilla", "Operario", "Vecino"];

    private const HASH_FALSO = '$2y$10$726ooijwYffp.y0Zo54Ulu7IbL6EQlG/sI2FtIRy/GDNMwjpSFJla';

    private const CAMPOS = "u.id_usuario, u.nombre, u.apellido, u.email,
                            u.telefono, u.direccion, r.nombre AS rol, u.estado,
                            u.id_cuadrilla, u.fecha_registro";

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

    private static function condiciones(array $filtros): array
    {
        $donde = [];
        $valores = [];
        if (isset($filtros["q"])) {
            $like = Paginacion::paraLike($filtros["q"]);
            $donde[] = "(u.nombre LIKE :q1 OR u.apellido LIKE :q2 OR u.email LIKE :q3)";
            foreach ([":q1", ":q2", ":q3"] as $nombre) {
                $valores[$nombre] = $like;
            }
        }
        if (isset($filtros["rol"])) {
            $donde[] = "r.nombre = :rol";
            $valores[":rol"] = $filtros["rol"];
        }
        if (isset($filtros["estado"])) {
            $donde[] = "u.estado = :estado";
            $valores[":estado"] = $filtros["estado"];
        }
        return [$donde === [] ? "" : " WHERE " . implode(" AND ", $donde), $valores];
    }

    public static function contar(array $filtros = []): int
    {
        [$donde, $valores] = self::condiciones($filtros);
        $consulta = Database::conexion()->prepare(
            "SELECT COUNT(*) FROM usuario u JOIN rol r ON r.id_rol = u.id_rol" . $donde
        );
        $consulta->execute($valores);
        return (int) $consulta->fetchColumn();
    }

    // $pagina = ["pagina" => n, "por_pagina" => n] devuelve un tramo; null devuelve todo.
    public static function listar(array $filtros = [], ?array $pagina = null): array
    {
        [$donde, $valores] = self::condiciones($filtros);
        $sql = "SELECT " . self::CAMPOS . "
                FROM usuario u
                JOIN rol r ON r.id_rol = u.id_rol" . $donde . "
                ORDER BY u.id_usuario" . ($pagina === null ? "" : " LIMIT :limite OFFSET :desplazamiento");

        $consulta = Database::conexion()->prepare($sql);
        foreach ($valores as $nombre => $valor) {
            $consulta->bindValue($nombre, $valor);
        }
        if ($pagina !== null) {
            $consulta->bindValue(":limite", $pagina["por_pagina"], PDO::PARAM_INT);
            $consulta->bindValue(":desplazamiento", ($pagina["pagina"] - 1) * $pagina["por_pagina"], PDO::PARAM_INT);
        }
        $consulta->execute();
        return $consulta->fetchAll();
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

        $clave = $datos["password"];

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

        if (array_key_exists("password", $datos)) {
            $partes[] = "password_hash = :password_hash";
            $valores[":password_hash"] = password_hash($datos["password"], PASSWORD_DEFAULT);
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
        $db = Database::conexion();
        $db->beginTransaction();
        try {
            $db->prepare("UPDATE usuario SET estado = 'inactivo' WHERE id_usuario = :id")
                ->execute([":id" => $id]);
            $db->prepare("DELETE FROM enlace_clave WHERE id_usuario = :id")
                ->execute([":id" => $id]);
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }

        return self::obtener($id);
    }

    public static function login(string $email, string $clave): ?array
    {
        $u = self::porEmail($email);

        if ($u === null || ($u["estado"] ?? "") !== "activo") {
            password_verify($clave, self::HASH_FALSO);
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

    public static function resolverSolicitud(int $id, string $decision, string $rol = "Vecino", ?callable $notificar = null): ?array
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
                    "password"     => bin2hex(random_bytes(32)),
                ], $rol, "pendiente");
                if ($notificar !== null) {
                    $notificar($usuario);
                }
            }

            $conexion->commit();
            return [self::obtenerSolicitud($id), $usuario];
        } catch (Throwable $e) {
            if ($conexion->inTransaction()) $conexion->rollBack();
            throw $e;
        }
    }
}
