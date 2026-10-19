<?php

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../models/ProximoPasaje.php";
require_once __DIR__ . "/../core/Response.php";

// GET /proximo-pasaje: solo para el Vecino. Devuelve la zona que eligio, el estado y el dia y la hora; nada interno.
class PasajeController
{
    public function proximo(array $usuario): void
    {
        $zona = $this->zonaDelUsuario((int) $usuario["id_usuario"]);
        if ($zona === null) {
            Response::json(["ok" => true, "datos" => ["estado" => "sin_zona", "zona" => null, "id_zona" => null, "proximo" => null, "cancelada" => null]]);
        }
        $ahora = (string) Database::conexion()->query("SELECT NOW()")->fetchColumn();
        $calculo = ProximoPasaje::calcular($this->recoleccionesDeLaZona($zona["id_zona"]), $ahora);
        Response::json(["ok" => true, "datos" => ["zona" => $zona["nombre"], "id_zona" => $zona["id_zona"]] + $calculo]);
    }

    private function zonaDelUsuario(int $idUsuario): ?array
    {
        $consulta = Database::conexion()->prepare("SELECT z.id_zona, z.nombre FROM usuario u JOIN zona z ON z.id_zona = u.id_zona WHERE u.id_usuario = :u");
        $consulta->execute([":u" => $idUsuario]);
        $fila = $consulta->fetch();
        return $fila === false ? null : ["id_zona" => (int) $fila["id_zona"], "nombre" => $fila["nombre"]];
    }

    // Las recolecciones de las rutas de la zona (activas e inactivas: lo que importa es lo planificado), solo estado y fecha.
    private function recoleccionesDeLaZona(int $idZona): array
    {
        $consulta = Database::conexion()->prepare(
            "SELECT r.estado, r.fecha_hora_inicio FROM recoleccion r JOIN ruta ru ON ru.id_ruta = r.id_ruta
             WHERE ru.id_zona = :z AND r.estado IN ('planificada', 'en_curso', 'cancelada')"
        );
        $consulta->execute([":z" => $idZona]);
        return $consulta->fetchAll();
    }
}
