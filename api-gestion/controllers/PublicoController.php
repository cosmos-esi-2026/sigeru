<?php

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../core/Response.php";
require_once __DIR__ . "/../core/LimitePeticion.php";
require_once __DIR__ . "/../models/ImpactoDatos.php";

// Datos que ve cualquiera, sin sesion, en la landing: cifras del sistema y puntos del mapa.
// Solo agregados y datos de lugares publicos (contenedores en la calle, centros de acopio).
// Nunca trae personas, incidencias individuales ni nada que identifique a un vecino.
class PublicoController
{
    private static function coordenada($valor): ?float
    {
        return $valor === null ? null : (float) $valor;
    }

    // GET /publico/impacto: cifras ambientales agregadas (sin personas). Con freno por IP. Siempre rotulado como estimacion y con su fuente.
    public function impacto(): void
    {
        LimitePeticion::exigir("impacto-publico", 120, 3600);
        $datos = ImpactoDatos::resumen();
        Response::json(["ok" => true, "datos" => $datos + ["generado_en" => (string) Database::conexion()->query("SELECT NOW()")->fetchColumn()]]);
    }

    public function mapa(): void
    {
        $db = Database::conexion();

        $contenedores = $db->query(
            "SELECT c.codigo, c.direccion, c.latitud, c.longitud, c.estado,
                    t.nombre AS tipo_residuo, z.nombre AS zona
             FROM contenedor c
             JOIN tipo_residuo t ON t.id_tipo_residuo = c.id_tipo_residuo
             JOIN zona z ON z.id_zona = c.id_zona
             WHERE c.estado <> 'retirado'
             ORDER BY c.codigo"
        )->fetchAll();
        $contenedores = array_map(function (array $c) {
            $c["latitud"] = self::coordenada($c["latitud"]);
            $c["longitud"] = self::coordenada($c["longitud"]);
            return $c;
        }, $contenedores);

        $centros = $db->query(
            "SELECT nombre, direccion, latitud, longitud
             FROM centro_acopio WHERE estado = 'activo' ORDER BY nombre"
        )->fetchAll();
        $centros = array_map(function (array $c) {
            $c["latitud"] = self::coordenada($c["latitud"]);
            $c["longitud"] = self::coordenada($c["longitud"]);
            return $c;
        }, $centros);

        $porEstado = array_count_values(array_column($contenedores, "estado"));
        $tipos = $db->query("SELECT nombre, reciclable FROM tipo_residuo ORDER BY id_tipo_residuo")->fetchAll();
        $resueltas = (int) $db->query("SELECT COUNT(*) FROM incidencia WHERE estado = 'resuelta'")->fetchColumn();

        Response::json(["ok" => true, "datos" => [
            "resumen" => [
                "contenedores"          => count($contenedores),
                "disponibles"           => $porEstado["disponible"] ?? 0,
                "llenos"                => $porEstado["lleno"] ?? 0,
                "en_mantenimiento"      => $porEstado["mantenimiento"] ?? 0,
                "zonas"                 => count(array_unique(array_column($contenedores, "zona"))),
                "centros_de_acopio"     => count($centros),
                "incidencias_resueltas" => $resueltas,
                "tipos_de_residuo"      => array_map(fn (array $t) => [
                    "nombre" => $t["nombre"], "reciclable" => (bool) $t["reciclable"],
                ], $tipos),
            ],
            "contenedores" => $contenedores,
            "centros"      => $centros,
            "generado_en"  => (string) $db->query("SELECT NOW()")->fetchColumn(),
        ]]);
    }
}
