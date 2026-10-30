<?php

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../models/Estadisticas.php";
require_once __DIR__ . "/../models/FlujoIncidencia.php";
require_once __DIR__ . "/../core/Response.php";
require_once __DIR__ . "/../core/Alcance.php";
require_once __DIR__ . "/../models/CentroAcopio.php";
require_once __DIR__ . "/../models/CentroStock.php";
require_once __DIR__ . "/../models/Maquinaria.php";
require_once __DIR__ . "/../models/Recepcion.php";

class EstadisticaController
{
    // Resumen del centro para el inicio del Operario: recepciones pendientes, ocupacion y maquinas en mantenimiento.
    // El Operario ve el suyo; el Administrador elige uno con ?centro=ID.
    public function centro(array $usuario): void
    {
        $idCentro = Alcance::centro($usuario);
        if ($idCentro === null) {
            $pedido = $_GET["centro"] ?? null;
            if (!is_string($pedido) || !ctype_digit($pedido) || (int) $pedido < 1) {
                Response::error("Indicá el centro: ?centro=ID", 400);
            }
            $idCentro = (int) $pedido;
        }
        $centro = CentroAcopio::obtener($idCentro);
        if ($centro === null) {
            Response::error("Centro de acopio no encontrado", 404);
        }
        Response::json(["ok" => true, "datos" => [
            "id_centro"                 => $idCentro,
            "centro"                    => $centro["nombre"],
            "recepciones_pendientes"    => Recepcion::pendientesDelCentro($idCentro),
            "maquinas_en_mantenimiento" => Maquinaria::enMantenimientoEnCentro($idCentro),
        ] + array_diff_key(CentroStock::resumen($centro), ["saldos" => 1])]);
    }

    // Informe de incidencias de un periodo (RF-242 a RF-244): se filtra por fecha de reporte.
    public function incidencias(): void
    {
        $consulta = FlujoIncidencia::filtrosDe(array_intersect_key($_GET, array_flip(["desde", "hasta", "zona"])));
        if ($consulta["error"] !== null) {
            Response::error($consulta["error"], 400);
        }
        Response::json(["ok" => true, "datos" => $this->datosIncidencias($consulta["filtros"])]);
    }

    // Los datos del informe de incidencias: $filtros viene de FlujoIncidencia::filtrosDe.
    public function datosIncidencias(array $filtros): array
    {
        $donde = [];
        $valores = [];
        if (isset($filtros["desde"])) {
            $donde[] = "i.fecha_reporte >= :desde";
            $valores[":desde"] = $filtros["desde"] . " 00:00:00";
        }
        if (isset($filtros["hasta"])) {
            $donde[] = "i.fecha_reporte <= :hasta";
            $valores[":hasta"] = $filtros["hasta"] . " 23:59:59";
        }
        if (isset($filtros["zona"])) {
            $donde[] = "c.id_zona = :zona";
            $valores[":zona"] = $filtros["zona"];
        }

        $db = Database::conexion();
        $sql = "SELECT i.id_incidencia, i.estado, i.prioridad, i.tipo_problema, i.fecha_reporte, i.fecha_resolucion,
                       i.id_contenedor, c.codigo AS contenedor, c.direccion, c.id_zona, z.nombre AS zona
                FROM incidencia i
                JOIN contenedor c ON c.id_contenedor = i.id_contenedor
                JOIN zona z ON z.id_zona = c.id_zona" .
               ($donde === [] ? "" : " WHERE " . implode(" AND ", $donde)) .
               " ORDER BY i.fecha_reporte DESC, i.id_incidencia DESC";
        $consultaSql = $db->prepare($sql);
        $consultaSql->execute($valores);

        $nombreZona = null;
        if (isset($filtros["zona"])) {
            $buscar = $db->prepare("SELECT nombre FROM zona WHERE id_zona = :id");
            $buscar->execute([":id" => $filtros["zona"]]);
            $nombreZona = $buscar->fetchColumn();
            if ($nombreZona === false) {
                Response::error("La zona indicada no existe", 404);
            }
        }

        $datos = Estadisticas::informe($consultaSql->fetchAll());
        $datos["periodo"] = [
            "desde"       => $filtros["desde"] ?? null,
            "hasta"       => $filtros["hasta"] ?? null,
            "id_zona"     => $filtros["zona"] ?? null,
            "zona"        => $nombreZona ?: null,
            "generado_en" => (string) $db->query("SELECT NOW()")->fetchColumn(),
        ];
        return $datos;
    }

    // Panel por urgencias del administrador: lo abierto ordenado por gravedad, y los contenedores llenos.
    public function urgencias(): void
    {
        $umbral = Estadisticas::umbralDe($_GET["dias"] ?? null);
        if ($umbral === null) {
            Response::error("Filtro 'dias' inválido: usá un número entre 1 y 60", 400);
        }
        Response::json(["ok" => true, "datos" => $this->datosUrgencias($umbral)]);
    }

    public function datosUrgencias(int $umbral): array
    {
        $db = Database::conexion();
        $abiertas = $db->query(
            "SELECT i.id_incidencia, i.estado, i.prioridad, i.tipo_problema, i.id_cuadrilla,
                    c.codigo AS contenedor, c.direccion, z.nombre AS zona, q.nombre AS cuadrilla,
                    TIMESTAMPDIFF(HOUR, i.fecha_reporte, NOW()) AS horas_abierta
             FROM incidencia i
             JOIN contenedor c ON c.id_contenedor = i.id_contenedor
             JOIN zona z ON z.id_zona = c.id_zona
             LEFT JOIN cuadrilla q ON q.id_cuadrilla = i.id_cuadrilla
             WHERE i.estado IN ('abierta', 'en_curso')"
        )->fetchAll();

        $datos = Estadisticas::urgencias($abiertas, $umbral);

        $llenos = $db->query(
            "SELECT c.id_contenedor, c.codigo, c.direccion, c.capacidad, z.nombre AS zona
             FROM contenedor c JOIN zona z ON z.id_zona = c.id_zona
             WHERE c.estado = 'lleno' ORDER BY z.nombre, c.codigo"
        )->fetchAll();
        $mantenimiento = (int) $db->query("SELECT COUNT(*) FROM contenedor WHERE estado = 'mantenimiento'")->fetchColumn();

        $datos["indicadores"]["contenedores_llenos"] = count($llenos);
        $datos["indicadores"]["contenedores_mantenimiento"] = $mantenimiento;
        $datos["contenedores_llenos"] = array_map(function (array $c) {
            $c["id_contenedor"] = (int) $c["id_contenedor"];
            $c["capacidad"] = $c["capacidad"] === null ? null : (int) $c["capacidad"];
            return $c;
        }, $llenos);
        $datos["umbral_dias"] = $umbral;

        return $datos;
    }
}
