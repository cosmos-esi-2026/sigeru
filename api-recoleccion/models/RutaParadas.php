<?php

require_once __DIR__ . "/FlujoRecoleccion.php";
require_once __DIR__ . "/Trazado.php";

// (Usa Database, ErrorDeNegocio y Limites, que carga quien la llama (RutaController, vía Ruta.php): asi la parte pura se prueba sin la base ni choca con la clase Database de la otra API.)
// Los contenedores de una ruta y su orden de visita (plan de rutas para la defensa, bloque 2; migracion 017).
//
// Reglas (todas en el servidor, la pantalla solo ayuda):
//  - La lista se reemplaza ENTERA en una transaccion: o queda como se pidio o no cambia nada.
//  - Hasta Limites::MAX_CONTENEDORES_POR_RUTA paradas, sin repetir, con ubicacion, ni retirados ni (si son nuevos en la ruta) en mantenimiento.
//  - Una ruta solo lleva contenedores de SU zona (el municipio).
//  - Un contenedor pertenece a UNA sola ruta activa: si ya esta en otra, se rechaza con 409 y se dice cual, salvo que se pida "mover".
//  - Una ruta que esta en una recoleccion en curso no se toca (ni la de origen al mover).
//  - Si la lista cambia, el trazado guardado deja de valer y se borra (la pantalla lo vuelve a pedir).
final class RutaParadas
{
    // Pura. Pedido: {"contenedores": [ids en el orden de visita], "mover": true|false}. Devuelve el motivo del rechazo, o null.
    public static function errorDeLista(array $datos): ?string
    {
        if (!isset($datos["contenedores"]) || !is_array($datos["contenedores"]) || array_values($datos["contenedores"]) !== $datos["contenedores"]) {
            return "Mandá «contenedores»: la lista de ids de los contenedores en el orden de visita";
        }
        $ids = [];
        foreach ($datos["contenedores"] as $valor) {
            $id = FlujoRecoleccion::entero($valor);
            if ($id === null) {
                return "Cada contenedor tiene que ser un número entero positivo (su id)";
            }
            $ids[] = $id;
        }
        if (count($ids) !== count(array_unique($ids))) {
            return "Hay contenedores repetidos en la lista";
        }
        if (count($ids) > Limites::MAX_CONTENEDORES_POR_RUTA) {
            return Limites::mensajeRutaLlena();
        }
        if (array_key_exists("mover", $datos) && !is_bool($datos["mover"])) {
            return "«mover» tiene que ser verdadero o falso";
        }
        return null;
    }

    public static function idsDe(array $datos): array
    {
        return array_map("intval", $datos["contenedores"]);
    }

    private static function marcadores(array $ids): string
    {
        return implode(",", array_fill(0, count($ids), "?"));
    }

    // Reemplaza la lista de paradas de la ruta. Devuelve ["movidos" => [[id_contenedor, codigo, id_ruta, ruta]], "afectadas" => [ids de rutas cambiadas]].
    public static function reemplazar(int $idRuta, array $ids, bool $mover): array
    {
        return Database::transaccion(function (PDO $c) use ($idRuta, $ids, $mover): array {
            $consulta = $c->prepare("SELECT id_ruta, nombre, id_zona, estado, trazado_firma FROM ruta WHERE id_ruta = ? FOR UPDATE");
            $consulta->execute([$idRuta]);
            $ruta = $consulta->fetch();
            if ($ruta === false) {
                throw new ErrorDeNegocio("Ruta no encontrada", 404);
            }
            self::exigirSinRecoleccionEnCurso($c, $idRuta);

            $actuales = [];
            $consulta = $c->prepare("SELECT id_contenedor FROM ruta_contenedor WHERE id_ruta = ?");
            $consulta->execute([$idRuta]);
            foreach ($consulta->fetchAll(PDO::FETCH_COLUMN) as $id) {
                $actuales[(int) $id] = true;
            }

            $contenedores = [];
            if ($ids !== []) {
                $consulta = $c->prepare("SELECT id_contenedor, codigo, estado, id_zona, latitud, longitud FROM contenedor WHERE id_contenedor IN (" . self::marcadores($ids) . ")");
                $consulta->execute($ids);
                foreach ($consulta->fetchAll() as $fila) {
                    $contenedores[(int) $fila["id_contenedor"]] = $fila;
                }
            }
            foreach ($ids as $id) {
                $fila = $contenedores[$id] ?? null;
                if ($fila === null) {
                    throw new ErrorDeNegocio("El contenedor $id no existe", 400);
                }
                $codigo = $fila["codigo"];
                if ($fila["latitud"] === null || $fila["longitud"] === null) {
                    throw new ErrorDeNegocio("El contenedor $codigo no tiene ubicación cargada: no se puede ubicar en el mapa", 400);
                }
                if ($fila["estado"] === "retirado") {
                    throw new ErrorDeNegocio("El contenedor $codigo está retirado", 400);
                }
                if ($fila["estado"] === "mantenimiento" && !isset($actuales[$id])) {
                    throw new ErrorDeNegocio("El contenedor $codigo está en mantenimiento: no se puede agregar a una ruta", 400);
                }
                if ((int) $fila["id_zona"] !== (int) $ruta["id_zona"]) {
                    throw new ErrorDeNegocio("El contenedor $codigo es de otra zona: una ruta solo lleva contenedores de su zona (municipio)", 400);
                }
            }

            // Un contenedor, una sola ruta activa.
            $movidos = [];
            $afectadas = [];
            if ($ids !== [] && $ruta["estado"] === "activa") {
                $consulta = $c->prepare("SELECT rc.id_contenedor, c.codigo, r.id_ruta, r.nombre
                                         FROM ruta_contenedor rc
                                         JOIN ruta r ON r.id_ruta = rc.id_ruta
                                         JOIN contenedor c ON c.id_contenedor = rc.id_contenedor
                                         WHERE r.estado = 'activa' AND rc.id_ruta <> ? AND rc.id_contenedor IN (" . self::marcadores($ids) . ")
                                         ORDER BY c.codigo");
                $consulta->execute(array_merge([$idRuta], $ids));
                foreach ($consulta->fetchAll() as $fila) {
                    $movidos[] = ["id_contenedor" => (int) $fila["id_contenedor"], "codigo" => $fila["codigo"], "id_ruta" => (int) $fila["id_ruta"], "ruta" => $fila["nombre"]];
                }
                if ($movidos !== [] && !$mover) {
                    $nombres = implode(", ", array_map(fn(array $m) => $m["codigo"] . " (" . $m["ruta"] . ")", array_slice($movidos, 0, 5)));
                    throw new ErrorDeNegocio(count($movidos) . " contenedor(es) ya están en otras rutas activas: $nombres" . (count($movidos) > 5 ? " y otros" : "") .
                        ". Un contenedor va en una sola ruta: podés moverlo a esta.", 409, ["conflictos" => $movidos]);
                }
                $afectadas = array_values(array_unique(array_column($movidos, "id_ruta")));
                foreach ($afectadas as $otra) {
                    self::exigirSinRecoleccionEnCurso($c, $otra);
                }
                if ($afectadas !== []) {
                    $c->prepare("SELECT id_ruta FROM ruta WHERE id_ruta IN (" . self::marcadores($afectadas) . ") FOR UPDATE")->execute($afectadas);
                }
                if ($movidos !== []) {
                    $c->prepare("DELETE rc FROM ruta_contenedor rc JOIN ruta r ON r.id_ruta = rc.id_ruta
                                 WHERE r.estado = 'activa' AND rc.id_ruta <> ? AND rc.id_contenedor IN (" . self::marcadores($ids) . ")")->execute(array_merge([$idRuta], $ids));
                    foreach ($afectadas as $otra) {
                        self::renumerar($c, $otra);
                        self::borrarTrazado($c, $otra);
                    }
                }
            }

            // Reemplazo de la lista.
            $c->prepare("DELETE FROM ruta_contenedor WHERE id_ruta = ?")->execute([$idRuta]);
            $insertar = $c->prepare("INSERT INTO ruta_contenedor (id_ruta, id_contenedor, orden) VALUES (?, ?, ?)");
            foreach ($ids as $posicion => $id) {
                $insertar->execute([$idRuta, $id, $posicion + 1]);
            }

            $paradas = array_map(fn(int $id) => ["latitud" => $contenedores[$id]["latitud"], "longitud" => $contenedores[$id]["longitud"]], $ids);
            if ($ruta["trazado_firma"] === null || $ruta["trazado_firma"] !== Trazado::firma($paradas)) {
                self::borrarTrazado($c, $idRuta);
            }
            return ["movidos" => $movidos, "afectadas" => $afectadas];
        });
    }

    private static function exigirSinRecoleccionEnCurso(PDO $c, int $idRuta): void
    {
        $consulta = $c->prepare("SELECT COUNT(*) FROM recoleccion WHERE id_ruta = ? AND estado = 'en_curso'");
        $consulta->execute([$idRuta]);
        if ((int) $consulta->fetchColumn() > 0) {
            throw new ErrorDeNegocio("La ruta está en una recolección en curso: esperá a que termine para cambiarla", 409);
        }
    }

    // Deja el orden de la ruta como 1..N sin huecos (tras sacarle contenedores). Va en orden ascendente para no chocar con el unico (id_ruta, orden).
    private static function renumerar(PDO $c, int $idRuta): void
    {
        $c->exec("SET @n := 0");
        $c->prepare("UPDATE ruta_contenedor SET orden = (@n := @n + 1) WHERE id_ruta = ? ORDER BY orden")->execute([$idRuta]);
    }

    private static function borrarTrazado(PDO $c, int $idRuta): void
    {
        $c->prepare("UPDATE ruta SET trazado = NULL, trazado_tipo = NULL, trazado_firma = NULL, trazado_servicio = NULL,
                                     trazado_distancia_m = NULL, trazado_duracion_s = NULL, trazado_en = NULL WHERE id_ruta = ?")->execute([$idRuta]);
    }

    // Cuadrillas con una recoleccion PLANIFICADA en esa ruta: son las que tienen que enterarse de que la ruta cambio.
    public static function cuadrillasAfectadas(int $idRuta): array
    {
        $consulta = Database::conexion()->prepare("SELECT DISTINCT id_cuadrilla, id_recoleccion FROM recoleccion WHERE id_ruta = ? AND estado = 'planificada' ORDER BY id_recoleccion");
        $consulta->execute([$idRuta]);
        return $consulta->fetchAll();
    }

    // Al reactivar una ruta, ninguno de sus contenedores puede estar en otra ruta activa.
    public static function conflictosAlActivar(int $idRuta): array
    {
        $consulta = Database::conexion()->prepare("SELECT c.codigo, r.nombre AS ruta
                                                   FROM ruta_contenedor rc
                                                   JOIN contenedor c ON c.id_contenedor = rc.id_contenedor
                                                   JOIN ruta_contenedor otro ON otro.id_contenedor = rc.id_contenedor AND otro.id_ruta <> rc.id_ruta
                                                   JOIN ruta r ON r.id_ruta = otro.id_ruta AND r.estado = 'activa'
                                                   WHERE rc.id_ruta = ? ORDER BY c.codigo");
        $consulta->execute([$idRuta]);
        return $consulta->fetchAll();
    }
}
