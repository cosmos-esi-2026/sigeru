<?php

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/Impacto.php";

// Lecturas de la base para el impacto ambiental (extras, parte 18): kg recolectados (de vaciado) por tipo de residuo y por mes,
// y kg recibidos en los centros de acopio (de los movimientos de stock de la parte 12).
class ImpactoDatos
{
    private static function rango(?string $desde, ?string $hasta): array
    {
        $donde = "";
        $valores = [];
        if ($desde !== null) {
            $donde .= " AND v.fecha_hora >= :desde";
            $valores[":desde"] = $desde . " 00:00:00";
        }
        if ($hasta !== null) {
            $donde .= " AND v.fecha_hora <= :hasta";
            $valores[":hasta"] = $hasta . " 23:59:59";
        }
        return [$donde, $valores];
    }

    private static function fila(array $f): array
    {
        return ["tipo" => $f["tipo"], "reciclable" => (bool) $f["reciclable"], "kg" => (float) $f["kg"],
                "factor" => $f["factor"] === null ? null : (float) $f["factor"], "fuente" => $f["fuente"], "url" => $f["url"]]
               + (isset($f["mes"]) ? ["mes" => $f["mes"]] : []);
    }

    // Todos los tipos de residuo (con 0 kg los que no se recolectaron), con su factor y su fuente.
    public static function kgPorTipo(?string $desde = null, ?string $hasta = null): array
    {
        [$donde, $valores] = self::rango($desde, $hasta);
        $consulta = Database::conexion()->prepare(
            "SELECT t.nombre AS tipo, t.reciclable, t.factor_co2_kg_por_kg AS factor, t.fuente_factor AS fuente, t.url_fuente AS url,
                    COALESCE(SUM(v.peso_kg), 0) AS kg
             FROM tipo_residuo t
             LEFT JOIN contenedor c ON c.id_tipo_residuo = t.id_tipo_residuo
             LEFT JOIN vaciado v ON v.id_contenedor = c.id_contenedor" . ($donde === "" ? "" : " AND 1 = 1" . $donde) . "
             GROUP BY t.id_tipo_residuo, t.nombre, t.reciclable, t.factor_co2_kg_por_kg, t.fuente_factor, t.url_fuente
             ORDER BY t.nombre"
        );
        $consulta->execute($valores);
        return array_map([self::class, "fila"], $consulta->fetchAll());
    }

    public static function kgPorMesYTipo(?string $desde = null, ?string $hasta = null): array
    {
        [$donde, $valores] = self::rango($desde, $hasta);
        $consulta = Database::conexion()->prepare(
            "SELECT DATE_FORMAT(v.fecha_hora, '%Y-%m') AS mes, t.nombre AS tipo, t.reciclable, t.factor_co2_kg_por_kg AS factor,
                    t.fuente_factor AS fuente, t.url_fuente AS url, SUM(v.peso_kg) AS kg
             FROM vaciado v
             JOIN contenedor c ON c.id_contenedor = v.id_contenedor
             JOIN tipo_residuo t ON t.id_tipo_residuo = c.id_tipo_residuo
             WHERE v.peso_kg IS NOT NULL" . $donde . "
             GROUP BY mes, t.id_tipo_residuo, t.nombre, t.reciclable, t.factor_co2_kg_por_kg, t.fuente_factor, t.url_fuente"
        );
        $consulta->execute($valores);
        return array_map([self::class, "fila"], $consulta->fetchAll());
    }

    // Lo recibido en los centros por recepcion, ya descontadas las correcciones de recepciones.
    public static function kgRecibidos(): float
    {
        $kg = Database::conexion()->query(
            "SELECT COALESCE(SUM(CASE WHEN m.motivo = 'recepcion' THEN m.kg ELSE -m.kg END), 0)
             FROM movimiento_stock m LEFT JOIN movimiento_stock o ON o.id_movimiento = m.id_corregido
             WHERE m.motivo = 'recepcion' OR (m.motivo = 'correccion' AND o.motivo = 'recepcion')"
        )->fetchColumn();
        return round((float) $kg, 2);
    }

    // Lo que se arma igual para el panel publico y para Reportes.
    public static function resumen(?string $desde = null, ?string $hasta = null): array
    {
        $porTipo = self::kgPorTipo($desde, $hasta);
        $impacto = Impacto::calcular($porTipo);
        $impacto["kg_recibidos_en_centros"] = self::kgRecibidos();
        $impacto["fuentes"] = Impacto::fuentes($porTipo);
        $impacto["tipos_sin_factor"] = array_values(array_map(fn($t) => $t["tipo"], array_filter($porTipo, fn($t) => $t["factor"] === null)));
        $impacto["nota"] = "Estimación: kilos recolectados de cada tipo reciclable por el factor de emisión evitada de esa fuente (factores de referencia de otro país, no medidos acá). "
                         . "Los tipos sin fuente verificada se informan solo en kilos.";
        return $impacto;
    }
}
