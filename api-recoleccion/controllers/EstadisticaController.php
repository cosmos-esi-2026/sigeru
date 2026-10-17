<?php

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../models/ResumenRecoleccion.php";
require_once __DIR__ . "/../models/FlujoRecoleccion.php";
require_once __DIR__ . "/../core/Response.php";

class EstadisticaController
{
    // Lo recolectado en un periodo: los vaciados se cuentan por su fecha y las recolecciones por su inicio.
    public function recoleccion(): void
    {
        $consulta = FlujoRecoleccion::filtrosDe(array_intersect_key($_GET, array_flip(["desde", "hasta"])));
        if ($consulta["error"] !== null) {
            Response::error($consulta["error"], 400);
        }
        $filtros = $consulta["filtros"];

        $zona = $_GET["zona"] ?? "";
        $idZona = null;
        if ($zona !== "") {
            $idZona = FlujoRecoleccion::entero($zona);
            if ($idZona === null) {
                Response::error("Filtro 'zona' inválido: tiene que ser el número de identificación", 400);
            }
        }

        $db = Database::conexion();
        $nombreZona = null;
        if ($idZona !== null) {
            $buscar = $db->prepare("SELECT nombre FROM zona WHERE id_zona = :id");
            $buscar->execute([":id" => $idZona]);
            $nombreZona = $buscar->fetchColumn();
            if ($nombreZona === false) {
                Response::error("La zona indicada no existe", 404);
            }
        }

        $dondeVaciado = [];
        $dondeRecoleccion = [];
        $valores = [];
        if (isset($filtros["desde"])) {
            $dondeVaciado[] = "v.fecha_hora >= :desde";
            $dondeRecoleccion[] = "r.fecha_hora_inicio >= :desde";
            $valores[":desde"] = $filtros["desde"] . " 00:00:00";
        }
        if (isset($filtros["hasta"])) {
            $dondeVaciado[] = "v.fecha_hora <= :hasta";
            $dondeRecoleccion[] = "r.fecha_hora_inicio <= :hasta";
            $valores[":hasta"] = $filtros["hasta"] . " 23:59:59";
        }
        if ($idZona !== null) {
            $dondeVaciado[] = "c.id_zona = :zona";
            $dondeRecoleccion[] = "ru.id_zona = :zona";
            $valores[":zona"] = $idZona;
        }

        $vaciados = $db->prepare(
            "SELECT t.nombre AS tipo_residuo, c.id_zona, z.nombre AS zona, v.peso_kg, v.volumen_l
             FROM vaciado v
             JOIN contenedor c ON c.id_contenedor = v.id_contenedor
             JOIN zona z ON z.id_zona = c.id_zona
             JOIN tipo_residuo t ON t.id_tipo_residuo = c.id_tipo_residuo" .
            ($dondeVaciado === [] ? "" : " WHERE " . implode(" AND ", $dondeVaciado))
        );
        $vaciados->execute($valores);

        $recolecciones = $db->prepare(
            "SELECT r.estado FROM recoleccion r JOIN ruta ru ON ru.id_ruta = r.id_ruta" .
            ($dondeRecoleccion === [] ? "" : " WHERE " . implode(" AND ", $dondeRecoleccion))
        );
        $recolecciones->execute($valores);

        $datos = ResumenRecoleccion::resumen($vaciados->fetchAll(), $recolecciones->fetchAll());
        $datos["periodo"] = [
            "desde"       => $filtros["desde"] ?? null,
            "hasta"       => $filtros["hasta"] ?? null,
            "id_zona"     => $idZona,
            "zona"        => $nombreZona ?: null,
            "generado_en" => (string) $db->query("SELECT NOW()")->fetchColumn(),
        ];
        Response::json(["ok" => true, "datos" => $datos]);
    }
}
