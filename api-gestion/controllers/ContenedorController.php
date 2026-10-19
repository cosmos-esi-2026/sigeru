<?php

require_once __DIR__ . "/../models/Contenedor.php";
require_once __DIR__ . "/../models/ValidacionContenedor.php";
require_once __DIR__ . "/../models/Paginacion.php";
require_once __DIR__ . "/../models/Prediccion.php";
require_once __DIR__ . "/../core/Response.php";

class ContenedorController
{
    // Sin "pagina" devuelve todo (lo usan el mapa y los formularios). Con "pagina" devuelve un tramo y "meta".
    public function listar(): void
    {
        $consulta = ValidacionContenedor::filtrosDe($_GET);
        if ($consulta["error"] !== null) {
            Response::error($consulta["error"], 400);
        }
        $pagina = Paginacion::de($_GET);
        if ($pagina["error"] !== null) {
            Response::error($pagina["error"], 400);
        }

        if (!$pagina["paginar"]) {
            Response::json(["ok" => true, "datos" => Contenedor::listar($consulta["filtros"])]);
        }
        $total = Contenedor::contar($consulta["filtros"]);
        Response::json([
            "ok"    => true,
            "datos" => Contenedor::listar($consulta["filtros"], $pagina),
            "meta"  => Paginacion::meta($total, $pagina["pagina"], $pagina["por_pagina"]),
        ]);
    }

    // GET /contenedores/prediccion?dentro_de=48: cuando se llena cada contenedor (estimacion estadistica, no IA). Administrador y Cuadrilla.
    public function prediccion(): void
    {
        $dentro = null;
        if (isset($_GET["dentro_de"])) {
            $pedido = $_GET["dentro_de"];
            if (!is_string($pedido) || !ctype_digit($pedido) || (int) $pedido < 1 || (int) $pedido > 720) {
                Response::error("Parámetro 'dentro_de' inválido: un número de horas de 1 a 720", 400);
            }
            $dentro = (int) $pedido;
        }
        $ahora = (string) Database::conexion()->query("SELECT NOW()")->fetchColumn();
        $vaciados = Contenedor::fechasDeVaciados();
        $filas = [];
        foreach (Contenedor::listar([]) as $c) {
            if ($c["estado"] === "retirado") {
                continue;
            }
            $prediccion = Prediccion::calcular($vaciados[(int) $c["id_contenedor"]] ?? [], $c["estado"], $ahora);
            if ($dentro !== null && ($prediccion["horas_hasta_90"] === null || $prediccion["horas_hasta_90"] > $dentro)) {
                continue;
            }
            $filas[] = ["id_contenedor" => (int) $c["id_contenedor"], "codigo" => $c["codigo"], "direccion" => $c["direccion"], "zona" => $c["zona"],
                        "tipo_residuo" => $c["tipo_residuo"], "capacidad" => $c["capacidad"], "estado" => $c["estado"],
                        "prediccion" => $prediccion + ["texto" => Prediccion::textoDeHoras($prediccion["horas_hasta_90"])]];
        }
        usort($filas, fn($a, $b) => [$a["prediccion"]["horas_hasta_90"] ?? PHP_FLOAT_MAX, $a["codigo"]] <=> [$b["prediccion"]["horas_hasta_90"] ?? PHP_FLOAT_MAX, $b["codigo"]]);
        Response::json(["ok" => true, "datos" => $filas, "generado_en" => $ahora,
                        "metodo" => "Estimación estadística (no es inteligencia artificial): intervalo medio entre vaciados y tiempo desde el último; hacen falta " . Prediccion::VACIADOS_MINIMOS . " vaciados."]);
    }

    // Que la zona y el tipo existan lo sabe solo la base; el resto de las reglas son de ValidacionContenedor.
    private function errorDeReferencias(array $datos, bool $esAlta): ?string
    {
        if (!empty($datos["id_zona"]) && !Contenedor::existeZona((int) $datos["id_zona"])) {
            return "La zona indicada no existe";
        }
        if ($esAlta && empty($datos["id_zona"]) && !empty($datos["zona"])
            && Contenedor::idDeZonaPorNombre((string) $datos["zona"]) === null) {
            return "La zona indicada no existe";
        }
        if (!empty($datos["tipo_residuo"]) && !Contenedor::existeTipo((string) $datos["tipo_residuo"])) {
            return "El tipo de residuo indicado no existe";
        }
        return null;
    }

    public function obtener(int $id): void
    {
        $contenedor = Contenedor::obtener($id);
        if ($contenedor === null) {
            Response::error("Contenedor no encontrado", 404);
        }
        Response::json(["ok" => true, "datos" => $contenedor]);
    }

    public function historial(int $id): void
    {
        if (Contenedor::obtener($id) === null) {
            Response::error("Contenedor no encontrado", 404);
        }
        Response::json(["ok" => true, "datos" => Contenedor::historial($id)]);
    }

    public function crear(): void
    {
        $datos = Response::leerJson();

        $error = ValidacionContenedor::error($datos, true) ?? $this->errorDeReferencias($datos, true);
        if ($error !== null) {
            Response::error($error, 400);
        }
        $datos["codigo"] = trim($datos["codigo"]);
        $datos["direccion"] = trim($datos["direccion"]);
        if (Contenedor::existeCodigo($datos["codigo"])) {
            Response::error("Ya existe un contenedor con ese código", 409);
        }

        $nuevo = Contenedor::crear($datos);
        Response::json(["ok" => true, "mensaje" => "Contenedor creado", "datos" => $nuevo], 201);
    }

    public function actualizar(int $id): void
    {
        $datos = Response::leerJson();

        $actual = Contenedor::obtener($id);
        if ($actual === null) {
            Response::error("Contenedor no encontrado", 404);
        }
        $error = ValidacionContenedor::error($datos, false) ?? $this->errorDeReferencias($datos, false);
        if ($error !== null) {
            Response::error($error, 400);
        }
        foreach (["codigo", "direccion"] as $campo) {
            if (isset($datos[$campo])) {
                $datos[$campo] = trim($datos[$campo]);
            }
        }

        if (isset($datos["codigo"]) && $datos["codigo"] !== $actual["codigo"]
            && Contenedor::existeCodigo($datos["codigo"])) {
            Response::error("Ya existe un contenedor con ese código", 409);
        }

        $actualizado = Contenedor::actualizar($id, $datos);
        Response::json(["ok" => true, "mensaje" => "Contenedor actualizado", "datos" => $actualizado]);
    }

    public function darDeBaja(int $id): void
    {
        $contenedor = Contenedor::obtener($id);
        if ($contenedor === null) {
            Response::error("Contenedor no encontrado", 404);
        }
        if ($contenedor["estado"] === "retirado") {
            Response::error("El contenedor ya estaba retirado", 409);
        }

        $datos = Response::leerJson();
        $motivo = $datos["motivo"] ?? "Retirado desde el panel";

        $retirado = Contenedor::darDeBaja($id, $motivo);
        Response::json(["ok" => true, "mensaje" => "Contenedor retirado", "datos" => $retirado]);
    }
}
