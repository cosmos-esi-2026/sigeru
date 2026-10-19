<?php

require_once __DIR__ . "/../models/CentroAcopio.php";
require_once __DIR__ . "/../models/Maquinaria.php";
require_once __DIR__ . "/../models/Recepcion.php";
require_once __DIR__ . "/../core/Auth.php";
require_once __DIR__ . "/../core/Response.php";
require_once __DIR__ . "/../models/ValidacionCentro.php";
require_once __DIR__ . "/../models/CentroStock.php";
require_once __DIR__ . "/../models/Paginacion.php";
require_once __DIR__ . "/../models/Campos.php";
require_once __DIR__ . "/../core/Alcance.php";

class CentroAcopioController
{
    // Cada centro trae su stock total y su ocupacion; el Operario solo ve el suyo.
    public function listar(array $usuario): void
    {
        $totales = CentroStock::totales();
        $centros = array_map(function (array $c) use ($totales) {
            $kg = $totales[$c["id_centro"]] ?? 0.0;
            $capacidad = $c["capacidad"] === null ? null : (int) $c["capacidad"];
            $ocupacion = Stock::ocupacion($kg, $capacidad);
            return $c + ["stock_kg" => $kg, "ocupacion_pct" => $ocupacion, "nivel" => Stock::nivel($ocupacion)];
        }, CentroAcopio::listar(Alcance::centro($usuario)));
        Response::json(["ok" => true, "datos" => $centros]);
    }

    private function propio(int $id, array $usuario): array
    {
        Alcance::exigir($usuario, $id);
        $centro = CentroAcopio::obtener($id);
        if ($centro === null) {
            Response::error("Centro de acopio no encontrado", 404);
        }
        return $centro;
    }

    public function obtener(int $id, array $usuario): void
    {
        $centro = $this->propio($id, $usuario);
        Response::json(["ok" => true, "datos" => $centro + CentroStock::resumen($centro)]);
    }

    public function stock(int $id, array $usuario): void
    {
        $centro = $this->propio($id, $usuario);
        Response::json(["ok" => true, "datos" => ["centro" => $centro["nombre"]] + CentroStock::resumen($centro)]);
    }

    public function movimientos(int $id, array $usuario): void
    {
        $this->propio($id, $usuario);
        $sentido = $_GET["sentido"] ?? null;
        if ($sentido !== null && (!is_string($sentido) || !in_array($sentido, ["entrada", "salida"], true))) {
            Response::error("Parámetro 'sentido' inválido: entrada o salida", 400);
        }
        $pagina = Paginacion::de($_GET);
        if ($pagina["error"] !== null) {
            Response::error($pagina["error"], 400);
        }
        $filas = CentroStock::movimientos($id, $sentido, $pagina["paginar"] ? $pagina : null);
        $cuerpo = ["ok" => true, "datos" => $filas];
        if ($pagina["paginar"]) {
            $cuerpo["meta"] = Paginacion::meta(CentroStock::contarMovimientos($id, $sentido), $pagina["pagina"], $pagina["por_pagina"]);
        }
        Response::json($cuerpo);
    }

    // POST /centros/{id}/movimientos: un retiro (venta, traslado o disposicion final).
    public function retirar(int $id, array $usuario): void
    {
        $centro = $this->propio($id, $usuario);
        if ($centro["estado"] !== "activo") {
            Response::error("El centro de acopio está inactivo", 409);
        }
        $datos = Response::leerJson();
        $tipo = $datos["id_tipo_residuo"] ?? null;
        $esEntero = is_int($tipo) || (is_string($tipo) && ctype_digit($tipo));
        if (!$esEntero || !in_array((int) $tipo, Recepcion::tiposValidos(), true)) {
            Response::error("Elegí un tipo de residuo que exista", 400);
        }
        if (($e = Campos::errorDeTextoOpcional($datos["observacion"] ?? null, "La observación", 200)) !== null) {
            Response::error($e, 400);
        }
        if (!in_array($datos["motivo"] ?? null, Stock::MOTIVOS_RETIRO, true)) {
            Response::error("Motivo inválido. Valores admitidos: " . implode(", ", Stock::MOTIVOS_RETIRO), 400);
        }
        if (Stock::kgValidos($datos["kg"] ?? null) === null) {
            Response::error("Los kg tienen que ser un número mayor que 0, con hasta 2 decimales", 400);
        }
        $datos["observacion"] = Campos::texto($datos["observacion"] ?? null);
        try {
            $mov = CentroStock::retirar($id, (int) $tipo, $datos, (int) $usuario["id_usuario"]);
        } catch (ErrorDeNegocio $e) {
            Response::error($e->getMessage(), $e->estadoHttp());
        }
        Response::json(["ok" => true, "mensaje" => "Retiro registrado", "datos" => $mov], 201);
    }

    // POST /centros/{id}/movimientos/{mid}/correccion: un movimiento inverso, con el motivo.
    public function corregir(int $id, int $idMovimiento, array $usuario): void
    {
        $this->propio($id, $usuario);
        $datos = Response::leerJson();
        $nota = Campos::texto($datos["observacion"] ?? null);
        if ($nota === "" || mb_strlen($nota) > 200) {
            Response::error("Contá el motivo de la corrección (hasta 200 caracteres)", 400);
        }
        try {
            $mov = CentroStock::corregir($id, $idMovimiento, (int) $usuario["id_usuario"], $nota);
        } catch (ErrorDeNegocio $e) {
            Response::error($e->getMessage(), $e->estadoHttp());
        }
        Response::json(["ok" => true, "mensaje" => "Corrección registrada", "datos" => $mov], 201);
    }

    public function crear(): void
    {
        $datos = Response::leerJson();

        $error = ValidacionCentro::error($datos, true);
        if ($error !== null) {
            Response::error($error, 400);
        }
        $datos["nombre"] = trim($datos["nombre"]);
        if (CentroAcopio::existeNombre($datos["nombre"])) {
            Response::error("Ya existe un centro de acopio con ese nombre", 409);
        }

        $nuevo = CentroAcopio::crear($datos);
        Response::json(["ok" => true, "mensaje" => "Centro de acopio creado", "datos" => $nuevo], 201);
    }

    public function actualizar(int $id, array $usuario): void
    {
        $datos = Response::leerJson();

        Alcance::exigir($usuario, $id);
        $actual = CentroAcopio::obtener($id);
        if ($actual === null) {
            Response::error("Centro de acopio no encontrado", 404);
        }
        $error = ValidacionCentro::error($datos, false);
        if ($error !== null) {
            Response::error($error, 400);
        }
        if (isset($datos["nombre"])) {
            $datos["nombre"] = trim($datos["nombre"]);
        }
        if (isset($datos["nombre"]) && $datos["nombre"] !== $actual["nombre"]
            && CentroAcopio::existeNombre($datos["nombre"])) {
            Response::error("Ya existe un centro de acopio con ese nombre", 409);
        }
        if (isset($datos["estado"]) && $datos["estado"] !== $actual["estado"]
            && $usuario["rol"] !== "Administrador") {
            Response::error("Solo un administrador puede cambiar el estado de un centro de acopio", 403);
        }

        $actualizado = CentroAcopio::actualizar($id, $datos);
        Response::json([
            "ok"      => true,
            "mensaje" => "Centro de acopio actualizado",
            "datos"   => $actualizado,
        ]);
    }

    public function darDeBaja(int $id): void
    {
        $centro = CentroAcopio::obtener($id);
        if ($centro === null) {
            Response::error("Centro de acopio no encontrado", 404);
        }
        if ($centro["estado"] === "inactivo") {
            Response::error("El centro de acopio ya estaba inactivo", 409);
        }

        $maquinas = Maquinaria::cuantasEnCentro($id);
        if ($maquinas === 1) {
            $aviso = " Atención: tiene 1 máquina registrada; conviene reasignarla a otro centro.";
        } elseif ($maquinas > 1) {
            $aviso = sprintf(" Atención: tiene %d máquinas registradas; conviene reasignarlas"
                             . " a otro centro.", $maquinas);
        } else {
            $aviso = "";
        }

        $baja = CentroAcopio::darDeBaja($id);
        Response::json([
            "ok"      => true,
            "mensaje" => "Centro de acopio dado de baja." . $aviso,
            "datos"   => $baja,
        ]);
    }
}
