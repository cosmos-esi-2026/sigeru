<?php

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../core/Paginacion.php";
require_once __DIR__ . "/../core/Validacion.php";

class Usuario
{
    public const ROLES = ["Administrador", "Cuadrilla", "Operario", "Vecino"];

    private const HASH_FALSO = '$2y$10$726ooijwYffp.y0Zo54Ulu7IbL6EQlG/sI2FtIRy/GDNMwjpSFJla';

    private const CAMPOS = "u.id_usuario, u.nombre, u.apellido, u.email, u.cedula,
                            u.telefono, u.direccion, r.nombre AS rol, u.estado,
                            u.id_cuadrilla,
                            (SELECT q.nombre FROM cuadrilla q WHERE q.id_cuadrilla = u.id_cuadrilla) AS cuadrilla,
                            u.id_centro,
                            (SELECT ce.nombre FROM centro_acopio ce WHERE ce.id_centro = u.id_centro) AS centro,
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

    private static function condiciones(array $filtros): array
    {
        $donde = [];
        $valores = [];
        if (isset($filtros["q"])) {
            $like = Paginacion::paraLike($filtros["q"]);
            $donde[] = "(u.nombre LIKE :q1 OR u.apellido LIKE :q2 OR u.email LIKE :q3 OR u.cedula LIKE :q4)";
            foreach ([":q1", ":q2", ":q3", ":q4"] as $nombre) {
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
        if (isset($filtros["cuadrilla"])) {
            $donde[] = "u.id_cuadrilla = :cuadrilla";
            $valores[":cuadrilla"] = $filtros["cuadrilla"];
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

    // La cedula va guardada solo con digitos; quien llama la normaliza antes (Validacion::normalizarCedula).
    public static function porCedula(string $cedula): ?array
    {
        $sql = "SELECT " . self::CAMPOS . ", u.password_hash
                FROM usuario u
                JOIN rol r ON r.id_rol = u.id_rol
                WHERE u.cedula = :cedula";

        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute([":cedula" => $cedula]);

        $usuario = $consulta->fetch();
        return $usuario === false ? null : $usuario;
    }

    // $exceptoId: al editar un usuario, su propia cedula no cuenta como repetida.
    public static function existeCedula(string $cedula, ?int $exceptoId = null): bool
    {
        $encontrado = self::porCedula($cedula);
        return $encontrado !== null && (int) $encontrado["id_usuario"] !== $exceptoId;
    }

    // Un pedido de cuadrilla pendiente con esa cedula: evita que la misma persona quede dos veces en la cola del administrador.
    public static function haySolicitudPendienteConCedula(string $cedula): bool
    {
        $consulta = Database::conexion()->prepare("SELECT 1 FROM solicitud_registro WHERE cedula = :cedula AND estado = 'pendiente' LIMIT 1");
        $consulta->execute([":cedula" => $cedula]);
        return $consulta->fetchColumn() !== false;
    }

    public static function existeZona(int $id): bool
    {
        $consulta = Database::conexion()->prepare("SELECT 1 FROM zona WHERE id_zona = :id");
        $consulta->execute([":id" => $id]);
        return $consulta->fetchColumn() !== false;
    }

    public static function guardarZona(int $idUsuario, ?int $idZona): void
    {
        Database::conexion()->prepare("UPDATE usuario SET id_zona = :z WHERE id_usuario = :u")->execute([":z" => $idZona, ":u" => $idUsuario]);
    }

    public static function existeCentro(int $id): bool
    {
        $consulta = Database::conexion()->prepare("SELECT 1 FROM centro_acopio WHERE id_centro = :id");
        $consulta->execute([":id" => $id]);
        return $consulta->fetchColumn() !== false;
    }

    // Una cuadrilla a la que se puede asociar a alguien: existe y esta activa (una dada de baja no recibe gente nueva).
    public static function cuadrillaActiva(int $id): bool
    {
        $consulta = Database::conexion()->prepare("SELECT 1 FROM cuadrilla WHERE id_cuadrilla = :id AND estado = 'activa'");
        $consulta->execute([":id" => $id]);
        return $consulta->fetchColumn() !== false;
    }

    public static function existeCuadrilla(int $id): bool
    {
        $consulta = Database::conexion()->prepare("SELECT 1 FROM cuadrilla WHERE id_cuadrilla = :id");
        $consulta->execute([":id" => $id]);
        return $consulta->fetchColumn() !== false;
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
                   (nombre, apellido, email, cedula, password_hash, telefono, direccion,
                    id_rol, id_centro, id_cuadrilla, id_solicitud, estado)
                VALUES
                   (:nombre, :apellido, :email, :cedula, :hash, :telefono, :direccion,
                    :id_rol, :id_centro, :id_cuadrilla, :id_solicitud, :estado)";

        $conexion = Database::conexion();
        $consulta = $conexion->prepare($sql);
        $consulta->execute([
            ":nombre"       => $datos["nombre"]    ?? "",
            ":apellido"     => $datos["apellido"]  ?? "",
            ":email"        => $datos["email"]     ?? "",
            ":cedula"       => !empty($datos["cedula"]) ? Validacion::normalizarCedula((string) $datos["cedula"]) : null,
            ":hash"         => password_hash($clave, PASSWORD_DEFAULT),
            ":telefono"     => $datos["telefono"]  ?? null,
            ":direccion"    => $datos["direccion"] ?? null,
            ":id_rol"       => $idRol,
            ":id_centro"    => $rol === "Operario" && !empty($datos["id_centro"]) ? (int) $datos["id_centro"] : null,
            ":id_cuadrilla" => $rol === "Cuadrilla" && !empty($datos["id_cuadrilla"]) ? (int) $datos["id_cuadrilla"] : null,
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

        // Vacia o ausente la deja como estaba solo si no se manda; mandada vacia la borra.
        if (array_key_exists("cedula", $datos)) {
            $partes[] = "cedula = :cedula";
            $valores[":cedula"] = empty($datos["cedula"]) ? null : Validacion::normalizarCedula((string) $datos["cedula"]);
        }

        if (array_key_exists("id_centro", $datos) || ($rol !== null && $rol !== "Operario")) {
            $partes[] = "id_centro = :id_centro";
            $valores[":id_centro"] = ($rol !== null && $rol !== "Operario") || empty($datos["id_centro"]) ? null : (int) $datos["id_centro"];
        }

        // Igual que el centro: la cuadrilla es solo del rol Cuadrilla y se limpia si el rol pasa a ser otro.
        if (array_key_exists("id_cuadrilla", $datos) || ($rol !== null && $rol !== "Cuadrilla")) {
            $partes[] = "id_cuadrilla = :id_cuadrilla";
            $valores[":id_cuadrilla"] = ($rol !== null && $rol !== "Cuadrilla") || empty($datos["id_cuadrilla"]) ? null : (int) $datos["id_cuadrilla"];
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
        Database::conexion()->prepare("UPDATE usuario SET estado = 'inactivo' WHERE id_usuario = :id")
            ->execute([":id" => $id]);

        return self::obtener($id);
    }

    // $identificador es el correo o, con $porCedula, la cedula ya normalizada (solo digitos).
    public static function login(string $identificador, string $clave, bool $porCedula = false): ?array
    {
        $u = $porCedula ? self::porCedula($identificador) : self::porEmail($identificador);

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
                   (nombre, apellido, email, cedula, telefono, direccion, tipo_cuenta, cuadrilla_indicada, estado)
                VALUES
                   (:nombre, :apellido, :email, :cedula, :telefono, :direccion, :tipo, :indicada, 'pendiente')";

        $conexion = Database::conexion();
        $consulta = $conexion->prepare($sql);
        $consulta->execute([
            ":nombre"    => $datos["nombre"]    ?? "",
            ":apellido"  => $datos["apellido"]  ?? "",
            ":email"     => $datos["email"]     ?? "",
            ":cedula"    => $datos["cedula"]    ?? null,
            ":telefono"  => $datos["telefono"]  ?? null,
            ":direccion" => $datos["direccion"] ?? null,
            ":tipo"      => ($datos["tipo_cuenta"] ?? "vecino") === "cuadrilla" ? "cuadrilla" : "vecino",
            ":indicada"  => $datos["cuadrilla_indicada"] ?? null,
        ]);

        return self::obtenerSolicitud((int) $conexion->lastInsertId());
    }

    // $cedula: la que el administrador confirma al aprobar; si no viene, se usa la del pedido.
    public static function resolverSolicitud(int $id, string $decision, string $rol = "Vecino", ?int $idCuadrilla = null, ?string $clave = null, ?string $cedula = null): ?array
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
                    "cedula"       => $cedula ?? $solicitud["cedula"],
                    "telefono"     => $solicitud["telefono"],
                    "direccion"    => $solicitud["direccion"],
                    "id_solicitud" => $id,
                    "id_cuadrilla" => $idCuadrilla,
                    "password"     => $clave,
                ], $rol);
            }

            $conexion->commit();
            return [self::obtenerSolicitud($id), $usuario];
        } catch (Throwable $e) {
            if ($conexion->inTransaction()) $conexion->rollBack();
            throw $e;
        }
    }
}
