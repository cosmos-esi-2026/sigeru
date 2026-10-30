<?php

require_once __DIR__ . "/../core/ErrorDeNegocio.php";
require_once __DIR__ . "/../models/Contenedor.php";
require_once __DIR__ . "/../models/ValidacionContenedor.php";
require_once __DIR__ . "/../models/Paginacion.php";
require_once __DIR__ . "/../core/Response.php";

class ContenedorController
{
    // Sin "pagina" devuelve todo (lo usan el mapa y los formularios). Con "pagina" devuelve un tramo y "meta".
    // Una Cuadrilla solo ve los contenedores de sus rutas asignadas (y los de sus incidencias): se le agrega ese alcance a los filtros.
    private static function alcanceDe(array $usuario): ?int
    {
        return ($usuario["rol"] ?? "") === "Cuadrilla" ? (int) ($usuario["id_cuadrilla"] ?? 0) : null;
    }

    public function listar(array $usuario = []): void
    {
        $consulta = ValidacionContenedor::filtrosDe($_GET);
        if ($consulta["error"] !== null) {
            Response::error($consulta["error"], 400);
        }
        $pagina = Paginacion::de($_GET);
        if ($pagina["error"] !== null) {
            Response::error($pagina["error"], 400);
        }
        $alcance = self::alcanceDe($usuario);
        if ($alcance !== null) {
            $consulta["filtros"]["alcance_cuadrilla"] = $alcance;
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

    public function obtener(int $id, array $usuario = []): void
    {
        $contenedor = Contenedor::obtener($id, self::alcanceDe($usuario));
        if ($contenedor === null) {
            Response::error("Contenedor no encontrado", 404);
        }
        Response::json(["ok" => true, "datos" => $contenedor]);
    }

    public function historial(int $id, array $usuario = []): void
    {
        if (Contenedor::obtener($id, self::alcanceDe($usuario)) === null) {
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

        try {
            $nuevo = Contenedor::crear($datos);
        } catch (ErrorDeNegocio $e) {
            Response::error($e->getMessage(), $e->estadoHttp());
        }
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

        try {
            $actualizado = Contenedor::actualizar($id, $datos);
        } catch (ErrorDeNegocio $e) {
            Response::error($e->getMessage(), $e->estadoHttp());
        }
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
