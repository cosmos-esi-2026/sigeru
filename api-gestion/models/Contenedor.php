<?php

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../core/ErrorDeNegocio.php";
require_once __DIR__ . "/Paginacion.php";
require_once __DIR__ . "/Limites.php";

class Contenedor
{
    public const ESTADOS = ["disponible", "lleno", "mantenimiento", "retirado"];

    private const CAMPOS = "c.id_contenedor, c.codigo, c.direccion, c.latitud,
                            c.longitud, c.capacidad, c.estado,
                            c.id_zona, z.nombre AS zona,
                            c.id_tipo_residuo, t.nombre AS tipo_residuo";

    private const DESDE = "FROM contenedor c
                           JOIN zona z ON z.id_zona = c.id_zona
                           JOIN tipo_residuo t ON t.id_tipo_residuo = c.id_tipo_residuo";

    // Lo que ve una Cuadrilla (plan de rutas para la defensa, punto 8 de la nota del profe): los contenedores de las rutas de sus recolecciones planificadas o en curso,
    // y los de las incidencias que se le asignaron. Se aplica en el SERVIDOR: la pantalla no puede saltearlo.
    private const DE_LA_CUADRILLA = "c.id_contenedor IN (
            SELECT rc.id_contenedor FROM ruta_contenedor rc JOIN recoleccion r ON r.id_ruta = rc.id_ruta
            WHERE r.id_cuadrilla = :cua1 AND r.estado IN ('planificada', 'en_curso')
            UNION
            SELECT i.id_contenedor FROM incidencia i WHERE i.id_cuadrilla = :cua2 AND i.estado = 'en_curso')";

    private static function condiciones(array $filtros): array
    {
        $donde = [];
        $valores = [];
        if (isset($filtros["alcance_cuadrilla"])) {
            $donde[] = self::DE_LA_CUADRILLA;
            $valores[":cua1"] = $valores[":cua2"] = (int) $filtros["alcance_cuadrilla"];
        }
        if (isset($filtros["q"])) {
            $like = Paginacion::paraLike($filtros["q"]);
            $donde[] = "(c.codigo LIKE :q1 OR c.direccion LIKE :q2 OR z.nombre LIKE :q3 OR t.nombre LIKE :q4)";
            foreach ([":q1", ":q2", ":q3", ":q4"] as $nombre) {
                $valores[$nombre] = $like;
            }
        }
        if (isset($filtros["estado"])) {
            $donde[] = "c.estado = :estado";
            $valores[":estado"] = $filtros["estado"];
        }
        if (isset($filtros["zona"])) {
            $donde[] = "c.id_zona = :zona";
            $valores[":zona"] = $filtros["zona"];
        }
        if (isset($filtros["tipo"])) {
            $donde[] = "t.nombre = :tipo";
            $valores[":tipo"] = $filtros["tipo"];
        }
        return [$donde === [] ? "" : " WHERE " . implode(" AND ", $donde), $valores];
    }

    public static function contar(array $filtros = []): int
    {
        [$donde, $valores] = self::condiciones($filtros);
        $consulta = Database::conexion()->prepare("SELECT COUNT(*) " . self::DESDE . $donde);
        $consulta->execute($valores);
        return (int) $consulta->fetchColumn();
    }

    // $pagina = ["pagina" => n, "por_pagina" => n] para devolver solo un tramo; null devuelve todo.
    public static function listar(array $filtros = [], ?array $pagina = null): array
    {
        [$donde, $valores] = self::condiciones($filtros);
        $sql = "SELECT " . self::CAMPOS . " " . self::DESDE . $donde . " ORDER BY c.id_contenedor";
        if ($pagina !== null) {
            $sql .= " LIMIT :limite OFFSET :desplazamiento";
        }
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

    public static function existeZona(int $id): bool
    {
        $consulta = Database::conexion()->prepare("SELECT 1 FROM zona WHERE id_zona = :id");
        $consulta->execute([":id" => $id]);
        return $consulta->fetchColumn() !== false;
    }

    public static function idDeZonaPorNombre(string $nombre): ?int
    {
        $consulta = Database::conexion()->prepare("SELECT id_zona FROM zona WHERE nombre = :nombre");
        $consulta->execute([":nombre" => $nombre]);
        $id = $consulta->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    public static function existeTipo(string $nombre): bool
    {
        return self::idDeTipo($nombre) !== null;
    }

    // $alcanceCuadrilla: si viene, solo se devuelve el contenedor si esta dentro de lo que ve esa cuadrilla.
    public static function obtener(int $id, ?int $alcanceCuadrilla = null): ?array
    {
        $sql = "SELECT " . self::CAMPOS . " " . self::DESDE . " WHERE c.id_contenedor = :id" . ($alcanceCuadrilla === null ? "" : " AND " . self::DE_LA_CUADRILLA);
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute($alcanceCuadrilla === null ? [":id" => $id] : [":id" => $id, ":cua1" => $alcanceCuadrilla, ":cua2" => $alcanceCuadrilla]);

        $contenedor = $consulta->fetch();
        return $contenedor === false ? null : $contenedor;
    }

    public static function existeCodigo(string $codigo): bool
    {
        $sql = "SELECT 1 FROM contenedor WHERE codigo = :codigo";
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute([":codigo" => $codigo]);

        return $consulta->fetchColumn() !== false;
    }

    public static function idDeTipo(?string $nombre): ?int
    {
        if ($nombre === null || $nombre === "") {
            return null;
        }
        $sql = "SELECT id_tipo_residuo FROM tipo_residuo WHERE nombre = :nombre";
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute([":nombre" => $nombre]);

        $id = $consulta->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    // La zona la valida el controlador antes de llegar acá: no hay zona "por omision".
    private static function resolverZona(array $datos): ?int
    {
        if (!empty($datos["id_zona"])) {
            return (int) $datos["id_zona"];
        }
        return empty($datos["zona"]) ? null : self::idDeZonaPorNombre($datos["zona"]);
    }

    // Contenedores en servicio (no retirados) de una zona: son los que cuentan para el tope de Limites::MAX_CONTENEDORES_POR_ZONA.
    public static function enServicioEnZona(int $idZona): int
    {
        $consulta = Database::conexion()->prepare("SELECT COUNT(*) FROM contenedor WHERE id_zona = :zona AND estado <> 'retirado'");
        $consulta->execute([":zona" => $idZona]);
        return (int) $consulta->fetchColumn();
    }

    // Hay que llamarla dentro de una transaccion y ANTES de agregar el contenedor a la zona. Bloquea la fila de la zona para que dos altas
    // simultaneas no se pasen del tope entre las dos.
    private static function exigirCupoEnZona(PDO $conexion, int $idZona): void
    {
        $zona = $conexion->prepare("SELECT nombre FROM zona WHERE id_zona = :zona FOR UPDATE");
        $zona->execute([":zona" => $idZona]);
        $nombre = $zona->fetchColumn();
        if ($nombre === false) {
            return; // la existencia de la zona ya la valido el controlador
        }
        if (self::enServicioEnZona($idZona) >= Limites::MAX_CONTENEDORES_POR_ZONA) {
            throw new ErrorDeNegocio(Limites::mensajeZonaLlena((string) $nombre), 409);
        }
    }

    public static function crear(array $datos): array
    {
        $estado = in_array($datos["estado"] ?? "", self::ESTADOS, true)
            ? $datos["estado"]
            : "disponible";

        $conexion = Database::conexion();
        $conexion->beginTransaction();

        try {
            $idZona = self::resolverZona($datos);
            if ($idZona !== null && $estado !== "retirado") {
                self::exigirCupoEnZona($conexion, $idZona);
            }

            $sql = "INSERT INTO contenedor
                       (codigo, direccion, latitud, longitud, capacidad, estado,
                        id_zona, id_tipo_residuo)
                    VALUES
                       (:codigo, :direccion, :latitud, :longitud, :capacidad, :estado,
                        :id_zona, :id_tipo)";

            $consulta = $conexion->prepare($sql);
            $consulta->execute([
                ":codigo"    => $datos["codigo"]    ?? "",
                ":direccion" => $datos["direccion"] ?? "",
                ":latitud"   => $datos["latitud"]   ?? null,
                ":longitud"  => $datos["longitud"]  ?? null,
                ":capacidad" => ($datos["capacidad"] ?? "") === "" ? null : (int) $datos["capacidad"],
                ":estado"    => $estado,
                ":id_zona"   => $idZona,
                ":id_tipo"   => self::idDeTipo($datos["tipo_residuo"] ?? null),
            ]);

            $id = (int) $conexion->lastInsertId();
            self::registrarEstado($id, $estado, "Alta del contenedor");

            $conexion->commit();
            return self::obtener($id);
        } catch (Throwable $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            throw $e;
        }
    }

    public static function actualizar(int $id, array $datos): ?array
    {
        return Database::transaccion(fn () => self::aplicarCambios($id, $datos));
    }

    private static function aplicarCambios(int $id, array $datos): ?array
    {
        $antes = self::obtener($id);
        if ($antes === null) {
            return null;
        }

        // Mover un contenedor a otra zona, o sacarlo de "retirado", lo suma al cupo de la zona de destino: no puede pasarse del tope.
        $zonaDestino = !empty($datos["id_zona"]) ? (int) $datos["id_zona"] : (int) $antes["id_zona"];
        $estadoDestino = isset($datos["estado"]) && in_array($datos["estado"], self::ESTADOS, true) ? $datos["estado"] : $antes["estado"];
        if ($estadoDestino !== "retirado" && ($zonaDestino !== (int) $antes["id_zona"] || $antes["estado"] === "retirado")) {
            self::exigirCupoEnZona(Database::conexion(), $zonaDestino);
        }

        $partes = [];
        $valores = [":id" => $id];

        foreach (["codigo", "direccion", "latitud", "longitud", "capacidad"] as $campo) {
            if (array_key_exists($campo, $datos)) {
                $partes[] = "$campo = :$campo";
                $valores[":$campo"] = $datos[$campo] === "" ? null : $datos[$campo];
            }
        }
        if (isset($datos["tipo_residuo"])) {
            $idTipo = self::idDeTipo($datos["tipo_residuo"]);
            if ($idTipo !== null) {
                $partes[] = "id_tipo_residuo = :id_tipo";
                $valores[":id_tipo"] = $idTipo;
            }
        }
        if (!empty($datos["id_zona"])) {
            $partes[] = "id_zona = :id_zona";
            $valores[":id_zona"] = (int) $datos["id_zona"];
        }

        if ($partes !== []) {
            $sql = "UPDATE contenedor SET " . implode(", ", $partes) . " WHERE id_contenedor = :id";
            $consulta = Database::conexion()->prepare($sql);
            $consulta->execute($valores);
        }

        if (isset($datos["estado"]) && in_array($datos["estado"], self::ESTADOS, true)) {
            self::cambiarEstado($id, $datos["estado"], "Cambio de estado desde el panel");
        }

        return self::obtener($id);
    }

    public static function darDeBaja(int $id, string $motivo = "Retirado desde el panel"): ?array
    {
        return Database::transaccion(function () use ($id, $motivo) {
            if (self::obtener($id) === null) {
                return null;
            }

            self::cambiarEstado($id, "retirado", $motivo);
            return self::obtener($id);
        });
    }

    public static function cambiarEstado(int $id, string $estado, string $observacion): bool
    {
        return Database::transaccion(function (PDO $conexion) use ($id, $estado, $observacion) {
            $consulta = $conexion->prepare("SELECT estado FROM contenedor WHERE id_contenedor = :id FOR UPDATE");
            $consulta->execute([":id" => $id]);

            $actual = $consulta->fetchColumn();
            if ($actual === false || $actual === $estado) {
                return false;
            }

            $consulta = $conexion->prepare("UPDATE contenedor SET estado = :estado WHERE id_contenedor = :id");
            $consulta->execute([":estado" => $estado, ":id" => $id]);

            self::registrarEstado($id, $estado, $observacion);
            return true;
        });
    }

    public static function registrarEstado(int $id, string $estado, ?string $observacion = null): void
    {
        $sql = "INSERT INTO estado_contenedor (id_contenedor, estado, observacion)
                VALUES (:id, :estado, :observacion)";

        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute([
            ":id"          => $id,
            ":estado"      => $estado,
            ":observacion" => $observacion,
        ]);
    }

    public static function historial(int $id): array
    {
        $sql = "SELECT id_registro, fecha_hora, estado, observacion
                FROM estado_contenedor
                WHERE id_contenedor = :id
                ORDER BY fecha_hora DESC, id_registro DESC";

        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute([":id" => $id]);

        return $consulta->fetchAll();
    }
}
