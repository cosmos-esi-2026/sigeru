<?php

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../core/ErrorDeNegocio.php";
require_once __DIR__ . "/FlujoRecoleccion.php";
require_once __DIR__ . "/RecorridoRuta.php";

class Recoleccion
{
    private const CAMPOS = "r.id_recoleccion, r.fecha_hora_inicio, r.fecha_hora_fin, r.estado, r.observaciones,
                            r.id_ruta, ru.nombre AS ruta, ru.id_zona, z.nombre AS zona,
                            r.id_camion, c.matricula, c.estado AS estado_camion,
                            r.id_cuadrilla, q.nombre AS cuadrilla, r.id_centro, ce.nombre AS centro,
                            (SELECT COUNT(*) FROM vaciado v WHERE v.id_recoleccion = r.id_recoleccion) AS vaciados,
                            (SELECT COALESCE(SUM(v.peso_kg), 0) FROM vaciado v WHERE v.id_recoleccion = r.id_recoleccion) AS peso_total_kg";

    private const DESDE = "FROM recoleccion r
                           JOIN ruta ru ON ru.id_ruta = r.id_ruta
                           JOIN zona z ON z.id_zona = ru.id_zona
                           JOIN camion c ON c.id_camion = r.id_camion
                           JOIN cuadrilla q ON q.id_cuadrilla = r.id_cuadrilla
                           LEFT JOIN centro_acopio ce ON ce.id_centro = r.id_centro";

    private const FILTROS = [
        "estado"    => "r.estado = :estado",
        "ruta"      => "r.id_ruta = :ruta",
        "camion"    => "r.id_camion = :camion",
        "cuadrilla" => "r.id_cuadrilla = :cuadrilla",
        "desde"     => "r.fecha_hora_inicio >= :desde",
        "hasta"     => "r.fecha_hora_inicio <= :hasta",
    ];

    private const ORDEN = " ORDER BY CASE r.estado WHEN 'en_curso' THEN 0 WHEN 'planificada' THEN 1 ELSE 2 END,
                            CASE WHEN r.estado = 'planificada' THEN r.fecha_hora_inicio END ASC,
                            r.fecha_hora_inicio DESC, r.id_recoleccion DESC";

    public static function listar(array $filtros, array $usuario): array
    {
        $donde = [];
        $valores = [];

        if ($usuario["rol"] === "Cuadrilla") {
            $donde[] = "r.id_cuadrilla = :alcance";
            $valores[":alcance"] = $usuario["id_cuadrilla"] ?? 0;
        } elseif ($usuario["rol"] !== "Administrador") {
            return [];
        }

        if (!empty($filtros["activas"])) {
            $donde[] = "r.estado IN ('planificada', 'en_curso')";
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
               ($donde === [] ? "" : " WHERE " . implode(" AND ", $donde)) . self::ORDEN;

        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute($valores);

        return array_map([self::class, "formatear"], $consulta->fetchAll());
    }

    public static function obtener(int $id): ?array
    {
        $consulta = Database::conexion()->prepare("SELECT " . self::CAMPOS . " " . self::DESDE . " WHERE r.id_recoleccion = :id");
        $consulta->execute([":id" => $id]);

        $fila = $consulta->fetch();
        return $fila === false ? null : self::formatear($fila);
    }

    public static function detalle(int $id): ?array
    {
        $recoleccion = self::obtener($id);
        if ($recoleccion === null) {
            return null;
        }

        $consulta = Database::conexion()->prepare(
            "SELECT v.id_vaciado, v.fecha_hora, v.peso_kg, v.volumen_l, v.id_contenedor, c.codigo AS contenedor, c.direccion
             FROM vaciado v
             JOIN contenedor c ON c.id_contenedor = v.id_contenedor
             WHERE v.id_recoleccion = :id
             ORDER BY v.fecha_hora, v.id_vaciado"
        );
        $consulta->execute([":id" => $id]);

        $recoleccion["recorrido"] = RecorridoRuta::de($recoleccion["id_ruta"], $recoleccion["id_recoleccion"]);
        $recoleccion["detalle_vaciados"] = array_map(function (array $fila) {
            foreach (["id_vaciado", "id_contenedor"] as $campo) {
                $fila[$campo] = (int) $fila[$campo];
            }
            $fila["peso_kg"] = $fila["peso_kg"] === null ? null : (float) $fila["peso_kg"];
            $fila["volumen_l"] = $fila["volumen_l"] === null ? null : (float) $fila["volumen_l"];
            return $fila;
        }, $consulta->fetchAll());

        return $recoleccion;
    }

    private static function formatear(array $fila): array
    {
        foreach (["id_recoleccion", "id_ruta", "id_zona", "id_camion", "id_cuadrilla", "vaciados"] as $campo) {
            $fila[$campo] = (int) $fila[$campo];
        }
        $fila["id_centro"] = $fila["id_centro"] === null ? null : (int) $fila["id_centro"];
        $fila["peso_total_kg"] = (float) $fila["peso_total_kg"];
        $fila["numero"] = FlujoRecoleccion::numero($fila["id_recoleccion"]);
        return $fila;
    }

    private static function bloquear(PDO $conexion, string $tabla, string $clave, int $id): ?array
    {
        $consulta = $conexion->prepare("SELECT * FROM $tabla WHERE $clave = :id FOR UPDATE");
        $consulta->execute([":id" => $id]);

        $fila = $consulta->fetch();
        return $fila === false ? null : $fila;
    }

    private static function cambiarCamion(PDO $conexion, int $idCamion, string $estado): void
    {
        $consulta = $conexion->prepare("UPDATE camion SET estado = :estado WHERE id_camion = :id");
        $consulta->execute([":estado" => $estado, ":id" => $idCamion]);
    }

    private static function preparar(PDO $conexion, int $id, array $usuario, string $accion): array
    {
        $recoleccion = self::bloquear($conexion, "recoleccion", "id_recoleccion", $id);
        if ($recoleccion === null) {
            throw new ErrorDeNegocio("Recolección no encontrada", 404);
        }
        if (!FlujoRecoleccion::alcanza($usuario, $recoleccion)) {
            throw new ErrorDeNegocio("Esta recolección no es de tu cuadrilla", 403);
        }
        if (!FlujoRecoleccion::puede($accion, $recoleccion["estado"])) {
            throw new ErrorDeNegocio(FlujoRecoleccion::motivoDeRechazo($accion, $recoleccion["estado"]), 409);
        }
        return $recoleccion;
    }

    public static function planificar(array $datos): array
    {
        $id = Database::transaccion(function (PDO $conexion) use ($datos) {
            $ruta = self::bloquear($conexion, "ruta", "id_ruta", FlujoRecoleccion::entero($datos["id_ruta"]));
            if ($ruta === null) {
                throw new ErrorDeNegocio("La ruta indicada no existe", 404);
            }
            if ($ruta["estado"] !== "activa") {
                throw new ErrorDeNegocio("La ruta " . $ruta["nombre"] . " está inactiva", 409);
            }

            $camion = self::bloquear($conexion, "camion", "id_camion", FlujoRecoleccion::entero($datos["id_camion"]));
            if ($camion === null) {
                throw new ErrorDeNegocio("El camión indicado no existe", 404);
            }
            if (in_array($camion["estado"], ["mantenimiento", "fuera_servicio"], true)) {
                throw new ErrorDeNegocio(FlujoRecoleccion::motivoCamionNoDisponible($camion["estado"], $camion["matricula"]), 409);
            }

            $cuadrilla = self::bloquear($conexion, "cuadrilla", "id_cuadrilla", FlujoRecoleccion::entero($datos["id_cuadrilla"]));
            if ($cuadrilla === null) {
                throw new ErrorDeNegocio("La cuadrilla indicada no existe", 404);
            }
            if ($cuadrilla["estado"] !== "activa") {
                throw new ErrorDeNegocio("La " . $cuadrilla["nombre"] . " está inactiva", 409);
            }

            $idCentro = FlujoRecoleccion::entero($datos["id_centro"] ?? null);
            if ($idCentro !== null) {
                $centro = self::bloquear($conexion, "centro_acopio", "id_centro", $idCentro);
                if ($centro === null) {
                    throw new ErrorDeNegocio("El centro de acopio indicado no existe", 404);
                }
                if ($centro["estado"] !== "activo") {
                    throw new ErrorDeNegocio("El centro " . $centro["nombre"] . " está inactivo", 409);
                }
            }

            $observaciones = FlujoRecoleccion::limpiar($datos["observaciones"] ?? null);
            $alta = $conexion->prepare(
                "INSERT INTO recoleccion (fecha_hora_inicio, estado, observaciones, id_ruta, id_camion, id_cuadrilla, id_centro)
                 VALUES (:inicio, 'planificada', :observaciones, :ruta, :camion, :cuadrilla, :centro)"
            );
            $alta->execute([
                ":inicio"        => FlujoRecoleccion::fechaHora($datos["fecha_hora_inicio"]),
                ":observaciones" => $observaciones === "" ? null : $observaciones,
                ":ruta"          => (int) $ruta["id_ruta"],
                ":camion"        => (int) $camion["id_camion"],
                ":cuadrilla"     => (int) $cuadrilla["id_cuadrilla"],
                ":centro"        => $idCentro,
            ]);
            return (int) $conexion->lastInsertId();
        });

        return self::detalle($id);
    }

    // Planifica varias recolecciones de una vez (la «Asignacion del dia»): todas o ninguna. Una cuadrilla o un camion solo puede tener UNA recoleccion
    // planificada o en curso por dia; si una asignacion falla, el mensaje dice cual (la numero N) y no se crea ninguna.
    public static function planificarLote(array $asignaciones): array
    {
        return Database::transaccion(function (PDO $conexion) use ($asignaciones): array {
            $creadas = [];
            foreach ($asignaciones as $i => $datos) {
                try {
                    $dia = substr((string) FlujoRecoleccion::fechaHora($datos["fecha_hora_inicio"]), 0, 10);
                    self::exigirLibreEseDia($conexion, "id_cuadrilla", (int) FlujoRecoleccion::entero($datos["id_cuadrilla"]), $dia, "La cuadrilla");
                    self::exigirLibreEseDia($conexion, "id_camion", (int) FlujoRecoleccion::entero($datos["id_camion"]), $dia, "El camión");
                    $creadas[] = self::planificar($datos);
                } catch (ErrorDeNegocio $e) {
                    throw new ErrorDeNegocio("Asignación " . ($i + 1) . ": " . $e->getMessage(), $e->estadoHttp(), $e->datos());
                }
            }
            return $creadas;
        });
    }

    private static function exigirLibreEseDia(PDO $conexion, string $campo, int $id, string $dia, string $quien): void
    {
        $consulta = $conexion->prepare("SELECT id_recoleccion FROM recoleccion WHERE $campo = :id AND DATE(fecha_hora_inicio) = :dia AND estado IN ('planificada', 'en_curso') LIMIT 1");
        $consulta->execute([":id" => $id, ":dia" => $dia]);
        $otra = $consulta->fetchColumn();
        if ($otra !== false) {
            throw new ErrorDeNegocio("$quien ya tiene la recolección " . FlujoRecoleccion::numero((int) $otra) . " ese día", 409);
        }
    }

    public static function iniciar(int $id, array $usuario): array
    {
        Database::transaccion(function (PDO $conexion) use ($id, $usuario) {
            $recoleccion = self::preparar($conexion, $id, $usuario, "iniciar");

            $camion = self::bloquear($conexion, "camion", "id_camion", (int) $recoleccion["id_camion"]);
            $motivo = FlujoRecoleccion::motivoCamionNoDisponible($camion["estado"], $camion["matricula"]);
            if ($motivo !== null) {
                throw new ErrorDeNegocio($motivo, 409);
            }

            $cuadrilla = self::bloquear($conexion, "cuadrilla", "id_cuadrilla", (int) $recoleccion["id_cuadrilla"]);
            if ($cuadrilla["estado"] !== "activa") {
                throw new ErrorDeNegocio("La " . $cuadrilla["nombre"] . " está inactiva", 409);
            }
            $otra = $conexion->prepare(
                "SELECT id_recoleccion FROM recoleccion
                 WHERE id_cuadrilla = :cuadrilla AND estado = 'en_curso' AND id_recoleccion <> :id LIMIT 1"
            );
            $otra->execute([":cuadrilla" => (int) $cuadrilla["id_cuadrilla"], ":id" => $id]);
            $enCurso = $otra->fetchColumn();
            if ($enCurso !== false) {
                throw new ErrorDeNegocio(
                    "La " . $cuadrilla["nombre"] . " ya está en la recolección " . FlujoRecoleccion::numero((int) $enCurso),
                    409
                );
            }

            $cambio = $conexion->prepare(
                "UPDATE recoleccion SET estado = 'en_curso', fecha_hora_inicio = NOW() WHERE id_recoleccion = :id"
            );
            $cambio->execute([":id" => $id]);
            self::cambiarCamion($conexion, (int) $camion["id_camion"], "en_ruta");
        });

        return self::detalle($id);
    }

    public static function registrarVaciado(int $id, array $datos, array $usuario): array
    {
        Database::transaccion(function (PDO $conexion) use ($id, $datos, $usuario) {
            $recoleccion = self::preparar($conexion, $id, $usuario, "vaciado");

            $idContenedor = FlujoRecoleccion::entero($datos["id_contenedor"] ?? null);
            $consulta = $conexion->prepare(
                "SELECT id_contenedor, codigo, capacidad, estado FROM contenedor WHERE " .
                ($idContenedor !== null ? "id_contenedor = :valor" : "codigo = :valor") . " FOR UPDATE"
            );
            $consulta->execute([":valor" => $idContenedor ?? FlujoRecoleccion::limpiar($datos["codigo_contenedor"] ?? null)]);
            $contenedor = $consulta->fetch();

            if ($contenedor === false) {
                throw new ErrorDeNegocio("El contenedor indicado no existe", 404);
            }
            if ($contenedor["estado"] === "retirado") {
                throw new ErrorDeNegocio("El contenedor " . $contenedor["codigo"] . " está retirado", 409);
            }
            // Solo se vacian contenedores de la ruta de ESTA recoleccion, y los que se recolectan: uno en mantenimiento lo atiende mantenimiento, no el camion.
            $enRuta = $conexion->prepare("SELECT 1 FROM ruta_contenedor WHERE id_ruta = :ruta AND id_contenedor = :contenedor");
            $enRuta->execute([":ruta" => (int) $recoleccion["id_ruta"], ":contenedor" => (int) $contenedor["id_contenedor"]]);
            if ($enRuta->fetchColumn() === false) {
                throw new ErrorDeNegocio("El contenedor " . $contenedor["codigo"] . " no está en la ruta de esta recolección", 409);
            }
            if ($contenedor["estado"] === "mantenimiento") {
                throw new ErrorDeNegocio("El contenedor " . $contenedor["codigo"] . " está en mantenimiento: no se recolecta", 409);
            }
            $error = FlujoRecoleccion::errorDeVaciado($datos, $contenedor["capacidad"] === null ? null : (int) $contenedor["capacidad"]);
            if ($error !== null) {
                throw new ErrorDeNegocio($error, 400);
            }

            $repetido = $conexion->prepare("SELECT 1 FROM vaciado WHERE id_recoleccion = :id AND id_contenedor = :contenedor");
            $repetido->execute([":id" => $id, ":contenedor" => (int) $contenedor["id_contenedor"]]);
            if ($repetido->fetchColumn() !== false) {
                throw new ErrorDeNegocio("El contenedor " . $contenedor["codigo"] . " ya se vació en esta recolección", 409);
            }

            $volumen = $datos["volumen_l"] ?? null;
            $alta = $conexion->prepare(
                "INSERT INTO vaciado (id_recoleccion, id_contenedor, peso_kg, volumen_l)
                 VALUES (:id, :contenedor, :peso, :volumen)"
            );
            $alta->execute([
                ":id"         => $id,
                ":contenedor" => (int) $contenedor["id_contenedor"],
                ":peso"       => round((float) $datos["peso_kg"], 2),
                ":volumen"    => $volumen === null || $volumen === "" ? null : round((float) $volumen, 2),
            ]);
        });

        return self::detalle($id);
    }

    public static function cerrar(int $id, string $accion, string $observacion, array $usuario): array
    {
        Database::transaccion(function (PDO $conexion) use ($id, $accion, $observacion, $usuario) {
            $recoleccion = self::preparar($conexion, $id, $usuario, $accion);
            $estado = FlujoRecoleccion::estadoTras($accion, $recoleccion["estado"]);
            $empezo = $recoleccion["estado"] === "en_curso";

            $cierre = $conexion->prepare(
                "UPDATE recoleccion SET estado = :estado, fecha_hora_fin = " . ($empezo ? "NOW()" : "NULL") .
                ($observacion !== "" ? ", observaciones = :observaciones" : "") . " WHERE id_recoleccion = :id"
            );
            $valores = [":estado" => $estado, ":id" => $id];
            if ($observacion !== "") {
                $valores[":observaciones"] = $observacion;
            }
            $cierre->execute($valores);

            $camion = self::bloquear($conexion, "camion", "id_camion", (int) $recoleccion["id_camion"]);
            $nuevoEstado = FlujoRecoleccion::estadoCamionTrasCierre($recoleccion["estado"], $camion["estado"]);
            if ($nuevoEstado !== null) {
                self::cambiarCamion($conexion, (int) $camion["id_camion"], $nuevoEstado);
            }
        });

        return self::detalle($id);
    }

    public static function camionEnCurso(int $idCamion): ?int
    {
        $consulta = Database::conexion()->prepare(
            "SELECT id_recoleccion FROM recoleccion WHERE id_camion = :id AND estado = 'en_curso' LIMIT 1"
        );
        $consulta->execute([":id" => $idCamion]);
        $id = $consulta->fetchColumn();
        return $id === false ? null : (int) $id;
    }
}
