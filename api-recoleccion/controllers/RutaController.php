<?php

require_once __DIR__ . "/../core/ErrorDeNegocio.php";
require_once __DIR__ . "/../core/Notificador.php";
require_once __DIR__ . "/../models/Ruta.php";
require_once __DIR__ . "/../models/RutaParadas.php";
require_once __DIR__ . "/../models/Trazado.php";
require_once __DIR__ . "/../models/FlujoRecoleccion.php";
require_once __DIR__ . "/../core/Response.php";

// Rutas (solo Administrador: la matriz de permisos lo dice). Una ruta lleva contenedores en orden y un trazado por calles; ver RutaParadas y Trazado.
class RutaController
{
    public function listar(): void
    {
        Response::json(["ok" => true, "datos" => Ruta::listar()]);
    }

    // GET /rutas/mapa?id_zona=N: las rutas activas de una zona (o de todas) con su trazado, para dibujarlas en el mapa.
    public function mapa(): void
    {
        $idZona = null;
        if (isset($_GET["id_zona"]) && $_GET["id_zona"] !== "") {
            $idZona = FlujoRecoleccion::entero($_GET["id_zona"]);
            if ($idZona === null) {
                Response::error("Parámetro 'id_zona' inválido: un número entero positivo", 400);
            }
        }
        Response::json(["ok" => true, "datos" => Ruta::paraMapa($idZona)]);
    }

    public function obtener(int $id): void
    {
        $ruta = Ruta::detalle($id);
        if ($ruta === null) {
            Response::error("Ruta no encontrada", 404);
        }
        Response::json(["ok" => true, "datos" => $ruta]);
    }

    public function crear(): void
    {
        $datos = Response::leerJson();

        $error = FlujoRecoleccion::errorDeRuta($datos, true);
        if ($error !== null) {
            Response::error($error, 400);
        }
        if (!Ruta::existeZona(FlujoRecoleccion::entero($datos["id_zona"]))) {
            Response::error("La zona indicada no existe", 404);
        }

        Response::json(["ok" => true, "mensaje" => "Ruta creada", "datos" => Ruta::crear($datos)], 201);
    }

    public function actualizar(int $id): void
    {
        $datos = Response::leerJson();

        $ruta = Ruta::obtener($id);
        if ($ruta === null) {
            Response::error("Ruta no encontrada", 404);
        }
        $error = FlujoRecoleccion::errorDeRuta($datos, false);
        if ($error !== null) {
            Response::error($error, 400);
        }
        if (array_key_exists("id_zona", $datos) && !Ruta::existeZona(FlujoRecoleccion::entero($datos["id_zona"]))) {
            Response::error("La zona indicada no existe", 404);
        }
        // Una ruta solo lleva contenedores de su zona: si ya tiene contenedores, no se le puede cambiar la zona.
        if (array_key_exists("id_zona", $datos) && FlujoRecoleccion::entero($datos["id_zona"]) !== $ruta["id_zona"] && $ruta["cantidad"] > 0) {
            Response::error("La ruta ya tiene contenedores y una ruta solo lleva los de su zona: vaciala antes de cambiarle la zona", 409);
        }
        // Reactivar una ruta no puede dejar un contenedor en dos rutas activas.
        if (($datos["estado"] ?? null) === "activa" && $ruta["estado"] === "inactiva") {
            $choques = RutaParadas::conflictosAlActivar($id);
            if ($choques !== []) {
                $nombres = implode(", ", array_map(fn(array $c) => $c["codigo"] . " (" . $c["ruta"] . ")", array_slice($choques, 0, 5)));
                Response::error("No se puede reactivar: " . count($choques) . " contenedor(es) ya están en otras rutas activas: $nombres", 409);
            }
        }

        Response::json(["ok" => true, "mensaje" => "Ruta actualizada", "datos" => Ruta::actualizar($id, $datos)]);
    }

    // PUT /rutas/{id}/contenedores  {"contenedores": [ids en orden], "mover": false}: reemplaza la lista de paradas (ver RutaParadas).
    public function reemplazarContenedores(int $id): void
    {
        if (Ruta::obtener($id) === null) {
            Response::error("Ruta no encontrada", 404);
        }
        $datos = Response::leerJson();
        $error = RutaParadas::errorDeLista($datos);
        if ($error !== null) {
            Response::error($error, 400);
        }
        $this->ejecutar(function () use ($id, $datos): void {
            $resultado = RutaParadas::reemplazar($id, RutaParadas::idsDe($datos), ($datos["mover"] ?? false) === true);
            foreach (array_merge([$id], $resultado["afectadas"]) as $idRuta) {
                $this->avisarCambioDeRuta((int) $idRuta);
            }
            Response::json(["ok" => true, "mensaje" => "Ruta guardada", "datos" => Ruta::detalle($id), "movidos" => $resultado["movidos"]]);
        });
    }

    // GET /rutas/{id}/candidatos: los contenedores de la zona de la ruta que se pueden poner en ella, y en que otra ruta activa esta cada uno.
    public function candidatos(int $id): void
    {
        $ruta = Ruta::obtener($id);
        if ($ruta === null) {
            Response::error("Ruta no encontrada", 404);
        }
        Response::json(["ok" => true, "datos" => Ruta::candidatos($id, $ruta["id_zona"]), "tope" => Limites::MAX_CONTENEDORES_POR_RUTA]);
    }

    // POST /rutas/{id}/trazado: pide el camino por las calles (o cae a linea recta) y lo guarda. Tarda unos segundos: la pantalla lo pide aparte de guardar la lista.
    public function trazar(int $id): void
    {
        if (Ruta::obtener($id) === null) {
            Response::error("Ruta no encontrada", 404);
        }
        $paradas = Ruta::paradas($id);
        if (count($paradas) < 2) {
            Response::error("La ruta necesita al menos 2 contenedores para poder trazarla", 409);
        }
        set_time_limit(90);
        $resultado = Trazado::calcular($paradas);
        Ruta::guardarTrazado($id, Trazado::firma($paradas), $resultado);
        Response::json([
            "ok"      => true,
            "mensaje" => $resultado["tipo"] === Trazado::TIPO_CALLES ? "Ruta trazada por las calles" : "Ruta guardada: se dibuja en línea recta entre sus paradas",
            "aviso"   => $resultado["aviso"] ?? null,
            "intentos" => $resultado["intentos"],
            "datos"   => Ruta::detalle($id),
        ]);
    }

    // DELETE /rutas/{id}            -> baja logica (queda "inactiva": sus recolecciones pasadas la siguen referenciando).
    // DELETE /rutas/{id}?definitivo=1 -> borrado de verdad, solo si la ruta nunca tuvo una recoleccion.
    public function darDeBaja(int $id): void
    {
        $ruta = Ruta::obtener($id);
        if ($ruta === null) {
            Response::error("Ruta no encontrada", 404);
        }
        if (($_GET["definitivo"] ?? "") === "1") {
            if (Ruta::tuvoRecolecciones($id)) {
                Response::error("La ruta ya tuvo recolecciones y no se puede borrar de verdad: dala de baja y queda inactiva", 409);
            }
            Ruta::borrar($id);
            Response::json(["ok" => true, "mensaje" => "Ruta borrada"]);
        }
        if ($ruta["estado"] === "inactiva") {
            Response::error("La ruta ya estaba inactiva", 409);
        }
        if (Ruta::recoleccionesAbiertas($id) > 0) {
            Response::error("La ruta tiene recolecciones planificadas o en curso: cancelalas antes de darla de baja", 409);
        }

        Response::json([
            "ok"      => true,
            "mensaje" => "Ruta dada de baja",
            "datos"   => Ruta::actualizar($id, ["estado" => "inactiva"]),
        ]);
    }

    // Aviso a las cuadrillas que tienen planificada una recoleccion en esa ruta: un aviso nunca rompe la operacion (Notificador lo garantiza).
    private function avisarCambioDeRuta(int $idRuta): void
    {
        $ruta = Ruta::obtener($idRuta);
        if ($ruta === null) {
            return;
        }
        foreach (RutaParadas::cuadrillasAfectadas($idRuta) as $fila) {
            Notificador::avisarA(
                Notificador::deCuadrilla((int) $fila["id_cuadrilla"]),
                "ruta_cambiada",
                "Tu ruta cambió: " . $ruta["nombre"],
                "El administrador modificó los contenedores de la ruta «" . $ruta["nombre"] . "». Revisá tu recolección planificada.",
                "recoleccion.html?id=" . (int) $fila["id_recoleccion"]
            );
        }
    }

    private function ejecutar(callable $accion): void
    {
        try {
            $accion();
        } catch (ErrorDeNegocio $e) {
            $cuerpo = ["ok" => false, "mensaje" => $e->getMessage()];
            if ($e->datos() !== []) {
                $cuerpo["datos"] = $e->datos();
            }
            Response::json($cuerpo, $e->estadoHttp());
        }
    }
}
