<?php

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../core/ErrorDeNegocio.php";
require_once __DIR__ . "/FlujoIncidencia.php";
require_once __DIR__ . "/Contenedor.php";
require_once __DIR__ . "/Cuadrilla.php";
require_once __DIR__ . "/FotoIncidencia.php";
require_once __DIR__ . "/Calificaciones.php";

class Incidencia
{
    private const CAMPOS = "i.id_incidencia, i.descripcion, i.tipo_problema, i.estado, i.prioridad,
                            i.fecha_reporte, i.fecha_resolucion, i.id_usuario, i.id_contenedor,
                            i.id_cuadrilla, c.codigo AS contenedor, c.direccion,
                            c.estado AS estado_contenedor, c.id_zona, z.nombre AS zona,
                            q.nombre AS cuadrilla, CONCAT(u.nombre, ' ', u.apellido) AS reportada_por";

    private const DESDE = "FROM incidencia i
                           JOIN contenedor c ON c.id_contenedor = i.id_contenedor
                           JOIN zona z ON z.id_zona = c.id_zona
                           JOIN usuario u ON u.id_usuario = i.id_usuario
                           LEFT JOIN cuadrilla q ON q.id_cuadrilla = i.id_cuadrilla";

    private const FILTROS = [
        "estado"     => "i.estado = :estado",
        "tipo"       => "i.tipo_problema = :tipo",
        "zona"       => "c.id_zona = :zona",
        "contenedor" => "i.id_contenedor = :contenedor",
        "cuadrilla"  => "i.id_cuadrilla = :cuadrilla",
        "desde"      => "i.fecha_reporte >= :desde",
        "hasta"      => "i.fecha_reporte <= :hasta",
    ];

    public static function listar(array $filtros, array $usuario): array
    {
        $donde = [];
        $valores = [];

        if ($usuario["rol"] === "Cuadrilla") {
            $donde[] = "i.id_cuadrilla = :alcance";
            $valores[":alcance"] = $usuario["id_cuadrilla"] ?? 0;
        } elseif ($usuario["rol"] === "Vecino") {
            $donde[] = "i.id_usuario = :alcance";
            $valores[":alcance"] = $usuario["id_usuario"];
        } elseif ($usuario["rol"] !== "Administrador") {
            return [];
        }

        foreach (self::FILTROS as $clave => $condicion) {
            if (!isset($filtros[$clave])) {
                continue;
            }
            $donde[] = $condicion;
            $valores[":$clave"] = match ($clave) {
                "desde" => $filtros[$clave] . " 00:00:00",
                "hasta" => $filtros[$clave] . " 23:59:59",
                default => $filtros[$clave],
            };
        }

        $sql = "SELECT " . self::CAMPOS . " " . self::DESDE .
               ($donde === [] ? "" : " WHERE " . implode(" AND ", $donde)) .
               " ORDER BY i.fecha_reporte DESC, i.id_incidencia DESC";

        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute($valores);

        return array_map(fn (array $fila) => self::formatear($fila, $usuario), $consulta->fetchAll());
    }

    public static function obtener(int $id): ?array
    {
        $consulta = Database::conexion()->prepare(
            "SELECT " . self::CAMPOS . " " . self::DESDE . " WHERE i.id_incidencia = :id"
        );
        $consulta->execute([":id" => $id]);

        $fila = $consulta->fetch();
        return $fila === false ? null : $fila;
    }

    public static function detalle(int $id, array $usuario): ?array
    {
        $fila = self::obtener($id);
        if ($fila === null) {
            return null;
        }

        $detalle = self::formatear($fila, $usuario);
        $detalle["historial"] = FlujoIncidencia::historialVisible(self::historial($id), $usuario);
        $detalle["tiene_foto"] = FotoIncidencia::existe($id);
        // La calificacion la ve quien ve la incidencia (el autor, el administrador y la cuadrilla asignada: nadie mas llega hasta aca).
        $detalle["calificacion"] = Calificaciones::de($id);
        $detalle["puede_calificar"] = false;
        if ($detalle["calificacion"] === null && ($usuario["rol"] ?? "") === "Vecino" && $fila["estado"] === "resuelta"
            && (int) $usuario["id_usuario"] === (int) $fila["id_usuario"]
            && Participacion::dentroDelPlazo($fila["fecha_resolucion"], (string) Database::conexion()->query("SELECT NOW()")->fetchColumn())) {
            $detalle["puede_calificar"] = true;
            $detalle["calificar_hasta"] = Participacion::plazoHasta($fila["fecha_resolucion"]);
        }
        return $detalle;
    }

    public static function historial(int $id): array
    {
        $consulta = Database::conexion()->prepare(
            "SELECT h.id_registro, h.fecha_hora, h.estado, h.observacion, h.id_usuario,
                    CONCAT(u.nombre, ' ', u.apellido) AS usuario, r.nombre AS rol
             FROM historial_incidencia h
             JOIN usuario u ON u.id_usuario = h.id_usuario
             JOIN rol r ON r.id_rol = u.id_rol
             WHERE h.id_incidencia = :id
             ORDER BY h.fecha_hora, h.id_registro"
        );
        $consulta->execute([":id" => $id]);

        return array_map(function (array $fila) {
            $fila["id_registro"] = (int) $fila["id_registro"];
            $fila["id_usuario"] = (int) $fila["id_usuario"];
            return $fila;
        }, $consulta->fetchAll());
    }

    private static function formatear(array $fila, array $usuario): array
    {
        foreach (["id_incidencia", "id_usuario", "id_contenedor", "id_zona"] as $campo) {
            $fila[$campo] = (int) $fila[$campo];
        }
        $fila["id_cuadrilla"] = $fila["id_cuadrilla"] === null ? null : (int) $fila["id_cuadrilla"];
        $fila["numero"] = FlujoIncidencia::numero($fila["id_incidencia"]);

        if ($usuario["rol"] !== "Administrador") {
            unset($fila["reportada_por"], $fila["id_usuario"]);
        }
        return $fila;
    }

    private static function asentar(PDO $conexion, int $id, string $estado, string $observacion, array $usuario): void
    {
        $consulta = $conexion->prepare(
            "INSERT INTO historial_incidencia (id_incidencia, estado, observacion, id_usuario)
             VALUES (:id, :estado, :observacion, :usuario)"
        );
        $consulta->execute([
            ":id"          => $id,
            ":estado"      => $estado,
            ":observacion" => $observacion,
            ":usuario"     => $usuario["id_usuario"],
        ]);
    }

    private static function preparar(PDO $conexion, int $id, array $usuario, string $accion): array
    {
        $bloqueo = $conexion->prepare("SELECT id_incidencia FROM incidencia WHERE id_incidencia = :id FOR UPDATE");
        $bloqueo->execute([":id" => $id]);

        $incidencia = $bloqueo->fetchColumn() === false ? null : self::obtener($id);
        if ($incidencia === null) {
            throw new ErrorDeNegocio("Incidencia no encontrada", 404);
        }
        if (!FlujoIncidencia::alcanza($usuario, $incidencia)) {
            throw new ErrorDeNegocio("Esta incidencia no está asignada a tu cuadrilla", 403);
        }
        if (!FlujoIncidencia::puede($accion, $incidencia["estado"])) {
            throw new ErrorDeNegocio(FlujoIncidencia::motivoDeRechazo($accion, $incidencia["estado"]), 409);
        }
        return $incidencia;
    }

    public static function crear(array $datos, array $usuario): array
    {
        $id = Database::transaccion(function (PDO $conexion) use ($datos, $usuario) {
            $idContenedor = FlujoIncidencia::entero($datos["id_contenedor"] ?? null);
            $consulta = $conexion->prepare(
                "SELECT id_contenedor, codigo, estado FROM contenedor WHERE " .
                ($idContenedor !== null ? "id_contenedor = :valor" : "codigo = :valor") . " FOR UPDATE"
            );
            $consulta->execute([":valor" => $idContenedor ?? FlujoIncidencia::limpiar($datos["codigo_contenedor"] ?? null)]);

            $contenedor = $consulta->fetch();
            if ($contenedor === false) {
                throw new ErrorDeNegocio("El contenedor indicado no existe", 404);
            }
            if ($contenedor["estado"] === "retirado") {
                throw new ErrorDeNegocio("El contenedor " . $contenedor["codigo"] . " está retirado y ya no admite reportes", 409);
            }

            $repetida = $conexion->prepare(
                "SELECT id_incidencia FROM incidencia
                 WHERE id_contenedor = :contenedor AND tipo_problema = :tipo AND estado IN ('abierta', 'en_curso')
                 ORDER BY id_incidencia LIMIT 1"
            );
            $repetida->execute([":contenedor" => $contenedor["id_contenedor"], ":tipo" => $datos["tipo_problema"]]);

            $existente = $repetida->fetchColumn();
            if ($existente !== false) {
                $numero = FlujoIncidencia::numero((int) $existente);
                throw new ErrorDeNegocio(
                    "Ya hay un reporte abierto de este problema en ese contenedor ($numero)",
                    409,
                    ["id_incidencia" => (int) $existente, "numero" => $numero]
                );
            }

            $alta = $conexion->prepare(
                "INSERT INTO incidencia (descripcion, tipo_problema, prioridad, id_usuario, id_contenedor)
                 VALUES (:descripcion, :tipo, :prioridad, :usuario, :contenedor)"
            );
            $alta->execute([
                ":descripcion" => FlujoIncidencia::limpiar($datos["descripcion"]),
                ":tipo"        => $datos["tipo_problema"],
                ":prioridad"   => $datos["prioridad"] ?? "media",
                ":usuario"     => $usuario["id_usuario"],
                ":contenedor"  => $contenedor["id_contenedor"],
            ]);

            $id = (int) $conexion->lastInsertId();
            self::asentar($conexion, $id, "abierta", "Incidencia registrada", $usuario);

            $nuevoEstado = FlujoIncidencia::estadoContenedorTrasReporte($datos["tipo_problema"], $contenedor["estado"]);
            if ($nuevoEstado !== null) {
                Contenedor::cambiarEstado(
                    (int) $contenedor["id_contenedor"],
                    $nuevoEstado,
                    "Desborde informado en " . FlujoIncidencia::numero($id)
                );
            }
            return $id;
        });

        return self::detalle($id, $usuario);
    }

    public static function asignar(int $id, int $idCuadrilla, ?string $prioridad, array $usuario): array
    {
        Database::transaccion(function (PDO $conexion) use ($id, $idCuadrilla, $prioridad, $usuario) {
            $incidencia = self::preparar($conexion, $id, $usuario, "asignar");

            $cuadrilla = Cuadrilla::obtener($idCuadrilla);
            if ($cuadrilla === null) {
                throw new ErrorDeNegocio("La cuadrilla indicada no existe", 404);
            }
            if ($cuadrilla["estado"] !== "activa") {
                throw new ErrorDeNegocio("La " . $cuadrilla["nombre"] . " está inactiva", 409);
            }
            if ($cuadrilla["ocupada"]) {
                throw new ErrorDeNegocio("La " . $cuadrilla["nombre"] . " está en una recolección en curso", 409);
            }
            if ($incidencia["estado"] === "en_curso" && (int) $incidencia["id_cuadrilla"] === $idCuadrilla) {
                throw new ErrorDeNegocio("La incidencia ya está asignada a la " . $cuadrilla["nombre"], 409);
            }

            $observacion = ($incidencia["cuadrilla"] === null
                    ? "Asignada a la "
                    : "Reasignada de la " . $incidencia["cuadrilla"] . " a la ") . $cuadrilla["nombre"];
            $nuevaPrioridad = $prioridad ?? $incidencia["prioridad"];
            if ($nuevaPrioridad !== $incidencia["prioridad"]) {
                $observacion .= ". Prioridad de " . $incidencia["prioridad"] . " a " . $nuevaPrioridad;
            }

            $cambio = $conexion->prepare(
                "UPDATE incidencia SET id_cuadrilla = :cuadrilla, estado = 'en_curso', prioridad = :prioridad
                 WHERE id_incidencia = :id"
            );
            $cambio->execute([":cuadrilla" => $idCuadrilla, ":prioridad" => $nuevaPrioridad, ":id" => $id]);

            self::asentar($conexion, $id, "en_curso", $observacion, $usuario);
        });

        return self::detalle($id, $usuario);
    }

    public static function cambiarPrioridad(int $id, string $prioridad, array $usuario): array
    {
        Database::transaccion(function (PDO $conexion) use ($id, $prioridad, $usuario) {
            $incidencia = self::preparar($conexion, $id, $usuario, "prioridad");
            if ($incidencia["prioridad"] === $prioridad) {
                return;
            }

            $cambio = $conexion->prepare("UPDATE incidencia SET prioridad = :prioridad WHERE id_incidencia = :id");
            $cambio->execute([":prioridad" => $prioridad, ":id" => $id]);

            self::asentar(
                $conexion,
                $id,
                $incidencia["estado"],
                "Prioridad de " . $incidencia["prioridad"] . " a " . $prioridad,
                $usuario
            );
        });

        return self::detalle($id, $usuario);
    }

    public static function registrarAvance(int $id, string $observacion, array $usuario): array
    {
        Database::transaccion(function (PDO $conexion) use ($id, $observacion, $usuario) {
            self::preparar($conexion, $id, $usuario, "avance");
            self::asentar($conexion, $id, "en_curso", $observacion, $usuario);
        });

        return self::detalle($id, $usuario);
    }

    public static function cerrar(int $id, string $accion, string $observacion, array $usuario): array
    {
        Database::transaccion(function (PDO $conexion) use ($id, $accion, $observacion, $usuario) {
            $incidencia = self::preparar($conexion, $id, $usuario, $accion);
            $estado = FlujoIncidencia::estadoTras($accion, $incidencia["estado"]);

            $cierre = $conexion->prepare(
                "UPDATE incidencia SET estado = :estado, fecha_resolucion = NOW() WHERE id_incidencia = :id"
            );
            $cierre->execute([":estado" => $estado, ":id" => $id]);

            self::asentar($conexion, $id, $estado, $observacion !== "" ? $observacion : "Incidencia resuelta", $usuario);
            if ($estado === "resuelta") {
                Calificaciones::sumarPorResolucion($conexion, (int) $incidencia["id_usuario"], $id);
            }

            $otras = $conexion->prepare(
                "SELECT COUNT(*) FROM incidencia
                 WHERE id_contenedor = :contenedor AND id_incidencia <> :id AND estado IN ('abierta', 'en_curso')"
            );
            $otras->execute([":contenedor" => $incidencia["id_contenedor"], ":id" => $id]);

            $nuevoEstado = FlujoIncidencia::estadoContenedorTrasCierre(
                $incidencia["estado_contenedor"],
                (int) $otras->fetchColumn()
            );
            if ($nuevoEstado !== null) {
                Contenedor::cambiarEstado(
                    (int) $incidencia["id_contenedor"],
                    $nuevoEstado,
                    FlujoIncidencia::numero($id) . ($estado === "resuelta" ? " resuelta" : " descartada")
                );
            }
        });

        return self::detalle($id, $usuario);
    }
}
