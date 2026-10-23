<?php

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/FlujoRecoleccion.php";
require_once __DIR__ . "/Limites.php";
require_once __DIR__ . "/Trazado.php";

class Ruta
{
    private const CONSULTA = "SELECT r.id_ruta, r.nombre, r.dia_semana, TIME_FORMAT(r.hora_inicio, '%H:%i') AS hora_inicio,
                                     r.estado, r.id_zona, z.nombre AS zona,
                                     (SELECT COUNT(*) FROM ruta_contenedor rc WHERE rc.id_ruta = r.id_ruta) AS cantidad,
                                     r.trazado_tipo, r.trazado_servicio, r.trazado_en
                              FROM ruta r
                              JOIN zona z ON z.id_zona = r.id_zona";

    public static function listar(): array
    {
        $filas = Database::conexion()->query(self::CONSULTA . " ORDER BY r.estado, FIELD(r.dia_semana, 'lunes', 'martes',
            'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'), r.hora_inicio, r.nombre")->fetchAll();
        return array_map([self::class, "formatear"], $filas);
    }

    public static function obtener(int $id): ?array
    {
        $consulta = Database::conexion()->prepare(self::CONSULTA . " WHERE r.id_ruta = :id");
        $consulta->execute([":id" => $id]);

        $fila = $consulta->fetch();
        return $fila === false ? null : self::formatear($fila);
    }

    public static function existeZona(int $idZona): bool
    {
        $consulta = Database::conexion()->prepare("SELECT 1 FROM zona WHERE id_zona = :id");
        $consulta->execute([":id" => $idZona]);
        return $consulta->fetchColumn() !== false;
    }

    public static function crear(array $datos): array
    {
        $conexion = Database::conexion();
        $consulta = $conexion->prepare(
            "INSERT INTO ruta (nombre, dia_semana, hora_inicio, estado, id_zona)
             VALUES (:nombre, :dia, :hora, :estado, :zona)"
        );
        $consulta->execute([
            ":nombre" => FlujoRecoleccion::limpiar($datos["nombre"]),
            ":dia"    => $datos["dia_semana"],
            ":hora"   => substr($datos["hora_inicio"], 0, 5) . ":00",
            ":estado" => $datos["estado"] ?? "activa",
            ":zona"   => FlujoRecoleccion::entero($datos["id_zona"]),
        ]);

        return self::obtener((int) $conexion->lastInsertId());
    }

    public static function actualizar(int $id, array $datos): array
    {
        $partes = [];
        $valores = [":id" => $id];

        if (array_key_exists("nombre", $datos)) {
            $partes[] = "nombre = :nombre";
            $valores[":nombre"] = FlujoRecoleccion::limpiar($datos["nombre"]);
        }
        if (array_key_exists("dia_semana", $datos)) {
            $partes[] = "dia_semana = :dia";
            $valores[":dia"] = $datos["dia_semana"];
        }
        if (array_key_exists("hora_inicio", $datos)) {
            $partes[] = "hora_inicio = :hora";
            $valores[":hora"] = substr($datos["hora_inicio"], 0, 5) . ":00";
        }
        if (array_key_exists("id_zona", $datos)) {
            $partes[] = "id_zona = :zona";
            $valores[":zona"] = FlujoRecoleccion::entero($datos["id_zona"]);
        }
        if (array_key_exists("estado", $datos)) {
            $partes[] = "estado = :estado";
            $valores[":estado"] = $datos["estado"];
        }

        if ($partes !== []) {
            $consulta = Database::conexion()->prepare("UPDATE ruta SET " . implode(", ", $partes) . " WHERE id_ruta = :id");
            $consulta->execute($valores);
        }
        return self::obtener($id);
    }

    // Los contenedores de la ruta en orden de visita. "recolectable" es falso para los que estan en mantenimiento: la ruta los muestra como "no se recolecta".
    public static function paradas(int $id): array
    {
        $consulta = Database::conexion()->prepare("SELECT rc.orden, c.id_contenedor, c.codigo, c.direccion, c.latitud, c.longitud, c.estado
                                                   FROM ruta_contenedor rc JOIN contenedor c ON c.id_contenedor = rc.id_contenedor
                                                   WHERE rc.id_ruta = :id ORDER BY rc.orden");
        $consulta->execute([":id" => $id]);
        return array_map(static function (array $p): array {
            return ["orden" => (int) $p["orden"], "id_contenedor" => (int) $p["id_contenedor"], "codigo" => $p["codigo"], "direccion" => $p["direccion"],
                    "latitud" => $p["latitud"] === null ? null : (float) $p["latitud"], "longitud" => $p["longitud"] === null ? null : (float) $p["longitud"],
                    "estado" => $p["estado"], "recolectable" => !in_array($p["estado"], ["mantenimiento", "retirado"], true)];
        }, $consulta->fetchAll());
    }

    // La ruta con sus paradas y su trazado. "vigente" dice si el trazado guardado es de la lista de paradas de hoy (si no, hay que recalcularlo).
    public static function detalle(int $id): ?array
    {
        $ruta = self::obtener($id);
        if ($ruta === null) {
            return null;
        }
        $paradas = self::paradas($id);
        $consulta = Database::conexion()->prepare("SELECT trazado, trazado_firma, trazado_distancia_m, trazado_duracion_s FROM ruta WHERE id_ruta = :id");
        $consulta->execute([":id" => $id]);
        $t = $consulta->fetch();

        $trazado = null;
        if ($ruta["trazado_tipo"] !== null) {
            $trazado = [
                "tipo"        => $ruta["trazado_tipo"],
                "servicio"    => $ruta["trazado_servicio"],
                "distancia_m" => $t["trazado_distancia_m"] === null ? null : (int) $t["trazado_distancia_m"],
                "duracion_s"  => $t["trazado_duracion_s"] === null ? null : (int) $t["trazado_duracion_s"],
                "calculado_en" => $ruta["trazado_en"],
                "vigente"     => $t["trazado_firma"] !== null && $t["trazado_firma"] === Trazado::firma($paradas),
                "geojson"     => $t["trazado"] === null ? null : json_decode($t["trazado"], true),
            ];
        }
        unset($ruta["trazado_tipo"], $ruta["trazado_servicio"], $ruta["trazado_en"]);
        return $ruta + ["tope" => Limites::MAX_CONTENEDORES_POR_RUTA, "paradas" => $paradas, "trazado" => $trazado];
    }

    // Los contenedores de la zona de la ruta que se pueden poner en ella (no retirados, con ubicacion), con la ruta ACTIVA en la que ya estan, si estan en una.
    public static function candidatos(int $idRuta, int $idZona): array
    {
        $consulta = Database::conexion()->prepare(
            "SELECT c.id_contenedor, c.codigo, c.direccion, c.latitud, c.longitud, c.estado,
                    EXISTS(SELECT 1 FROM ruta_contenedor a WHERE a.id_ruta = :ruta AND a.id_contenedor = c.id_contenedor) AS en_esta_ruta,
                    (SELECT r2.id_ruta FROM ruta_contenedor rc2 JOIN ruta r2 ON r2.id_ruta = rc2.id_ruta
                      WHERE rc2.id_contenedor = c.id_contenedor AND r2.estado = 'activa' AND r2.id_ruta <> :ruta2 LIMIT 1) AS id_otra_ruta,
                    (SELECT r3.nombre FROM ruta_contenedor rc3 JOIN ruta r3 ON r3.id_ruta = rc3.id_ruta
                      WHERE rc3.id_contenedor = c.id_contenedor AND r3.estado = 'activa' AND r3.id_ruta <> :ruta3 LIMIT 1) AS otra_ruta
             FROM contenedor c
             WHERE c.id_zona = :zona AND c.estado <> 'retirado' AND c.latitud IS NOT NULL AND c.longitud IS NOT NULL
             ORDER BY c.codigo"
        );
        $consulta->execute([":ruta" => $idRuta, ":ruta2" => $idRuta, ":ruta3" => $idRuta, ":zona" => $idZona]);
        return array_map(static function (array $c): array {
            return ["id_contenedor" => (int) $c["id_contenedor"], "codigo" => $c["codigo"], "direccion" => $c["direccion"],
                    "latitud" => (float) $c["latitud"], "longitud" => (float) $c["longitud"], "estado" => $c["estado"],
                    "en_esta_ruta" => (int) $c["en_esta_ruta"] === 1,
                    "id_otra_ruta" => $c["id_otra_ruta"] === null ? null : (int) $c["id_otra_ruta"], "otra_ruta" => $c["otra_ruta"]];
        }, $consulta->fetchAll());
    }

    // Guarda el resultado de Trazado::calcular(). Una ruta en linea recta guarda solo el tipo y la firma (no hay geometria que guardar).
    public static function guardarTrazado(int $id, string $firma, array $resultado): void
    {
        Database::conexion()->prepare(
            "UPDATE ruta SET trazado = :trazado, trazado_tipo = :tipo, trazado_firma = :firma, trazado_servicio = :servicio,
                             trazado_distancia_m = :distancia, trazado_duracion_s = :duracion, trazado_en = NOW() WHERE id_ruta = :id"
        )->execute([
            ":trazado"   => $resultado["tipo"] === Trazado::TIPO_CALLES ? json_encode(Trazado::geojson($resultado["coordenadas"]), JSON_UNESCAPED_SLASHES) : null,
            ":tipo"      => $resultado["tipo"],
            ":firma"     => $firma,
            ":servicio"  => $resultado["servicio"],
            ":distancia" => $resultado["distancia_m"],
            ":duracion"  => $resultado["duracion_s"],
            ":id"        => $id,
        ]);
    }

    // Las rutas ACTIVAS con lo que hace falta para dibujarlas en el mapa: el trazado por calles (GeoJSON) o, si no lo tienen, las coordenadas de sus paradas
    // para unirlas en linea recta. Se filtra por zona porque todas juntas son mucho mas de lo que un mapa necesita.
    public static function paraMapa(?int $idZona): array
    {
        $sql = "SELECT r.id_ruta, r.nombre, r.dia_semana, TIME_FORMAT(r.hora_inicio, '%H:%i') AS hora_inicio, r.id_zona, z.nombre AS zona,
                       r.trazado_tipo, r.trazado_distancia_m,
                       (SELECT COUNT(*) FROM ruta_contenedor rc WHERE rc.id_ruta = r.id_ruta) AS cantidad
                FROM ruta r JOIN zona z ON z.id_zona = r.id_zona
                WHERE r.estado = 'activa'" . ($idZona === null ? "" : " AND r.id_zona = :zona") . " ORDER BY z.nombre, r.nombre";
        $consulta = Database::conexion()->prepare($sql);
        $consulta->execute($idZona === null ? [] : [":zona" => $idZona]);
        $filas = $consulta->fetchAll();
        // El trazado (LONGTEXT) se pide aparte: ordenar filas con ese texto adentro era 50 veces mas lento (medido con 40 rutas: 0,4 s contra 0,007 s).
        $trazados = [];
        if ($filas !== []) {
            $ids = implode(",", array_map(static fn(array $f) => (int) $f["id_ruta"], $filas));
            $trazados = Database::conexion()->query("SELECT id_ruta, trazado FROM ruta WHERE trazado IS NOT NULL AND id_ruta IN ($ids)")->fetchAll(PDO::FETCH_KEY_PAIR);
        }
        $rutas = [];
        foreach ($filas as $r) {
            $r["trazado"] = $trazados[$r["id_ruta"]] ?? null;
            $conLinea = $r["trazado_tipo"] === Trazado::TIPO_CALLES && $r["trazado"] !== null;
            $rutas[] = [
                "id_ruta" => (int) $r["id_ruta"], "nombre" => $r["nombre"], "dia_semana" => $r["dia_semana"], "hora_inicio" => $r["hora_inicio"],
                "id_zona" => (int) $r["id_zona"], "zona" => $r["zona"], "cantidad" => (int) $r["cantidad"],
                "trazado_tipo" => $r["trazado_tipo"], "distancia_m" => $r["trazado_distancia_m"] === null ? null : (int) $r["trazado_distancia_m"],
                "geojson" => $conLinea ? json_decode($r["trazado"], true) : null,
                "paradas" => $conLinea ? null : array_map(static fn(array $p) => [$p["latitud"], $p["longitud"]], self::paradas((int) $r["id_ruta"])),
            ];
        }
        return $rutas;
    }

    public static function recoleccionesAbiertas(int $id): int
    {
        $consulta = Database::conexion()->prepare("SELECT COUNT(*) FROM recoleccion WHERE id_ruta = :id AND estado IN ('planificada', 'en_curso')");
        $consulta->execute([":id" => $id]);
        return (int) $consulta->fetchColumn();
    }

    public static function tuvoRecolecciones(int $id): bool
    {
        $consulta = Database::conexion()->prepare("SELECT COUNT(*) FROM recoleccion WHERE id_ruta = :id");
        $consulta->execute([":id" => $id]);
        return (int) $consulta->fetchColumn() > 0;
    }

    // Borrado de verdad: solo si la ruta nunca tuvo una recoleccion. Sus contenedores se sueltan solos (ON DELETE CASCADE).
    public static function borrar(int $id): void
    {
        Database::conexion()->prepare("DELETE FROM ruta WHERE id_ruta = :id")->execute([":id" => $id]);
    }

    public static function cantidadDeParadas(int $id): int
    {
        $consulta = Database::conexion()->prepare("SELECT COUNT(*) FROM ruta_contenedor WHERE id_ruta = :id");
        $consulta->execute([":id" => $id]);
        return (int) $consulta->fetchColumn();
    }

    private static function formatear(array $fila): array
    {
        $fila["id_ruta"] = (int) $fila["id_ruta"];
        $fila["id_zona"] = (int) $fila["id_zona"];
        $fila["cantidad"] = (int) $fila["cantidad"];
        return $fila;
    }
}
