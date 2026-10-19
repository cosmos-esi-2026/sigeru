<?php

require_once __DIR__ . "/../models/Recepcion.php";
require_once __DIR__ . "/../models/CentroAcopio.php";
require_once __DIR__ . "/../models/CentroStock.php";
require_once __DIR__ . "/../models/Campos.php";
require_once __DIR__ . "/../core/Alcance.php";
require_once __DIR__ . "/../core/Response.php";
require_once __DIR__ . "/../core/Notificador.php";

// Recepcion en el centro de acopio (extras, parte 12). El Operario solo ve y recibe lo de su centro.
class RecepcionController
{
    public function listar(array $usuario): void
    {
        $estado = $_GET["estado"] ?? "pendiente";
        if (!is_string($estado) || !in_array($estado, Recepcion::ESTADOS, true)) {
            Response::error("Parámetro 'estado' inválido. Valores admitidos: " . implode(", ", Recepcion::ESTADOS), 400);
        }
        $centro = Alcance::centro($usuario);
        if ($centro === null && isset($_GET["centro"])) {
            $pedido = $_GET["centro"];
            if (!is_string($pedido) || !ctype_digit($pedido) || (int) $pedido < 1) {
                Response::error("Parámetro 'centro' inválido", 400);
            }
            $centro = (int) $pedido;
        }
        Response::json(["ok" => true, "datos" => Recepcion::listar($centro, $estado)]);
    }

    private function propia(int $id, array $usuario): array
    {
        $detalle = Recepcion::obtener($id);
        if ($detalle === null || !AlcanceCentro::puede($usuario, $detalle["id_centro"])) {
            Response::error("Recolección no encontrada", 404);
        }
        return $detalle;
    }

    public function obtener(int $id, array $usuario): void
    {
        Alcance::centro($usuario);
        Response::json(["ok" => true, "datos" => $this->propia($id, $usuario)]);
    }

    // POST /recepciones/{id_recoleccion}: {lineas: [{id_tipo_residuo, kg}], observacion?}
    public function confirmar(int $id, array $usuario): void
    {
        $centro = Alcance::centro($usuario);
        $datos = Response::leerJson();
        [$lineas, $error] = Stock::lineasDeRecepcion($datos["lineas"] ?? null, Recepcion::tiposValidos());
        if ($error === null) {
            $error = Campos::errorDeTextoOpcional($datos["observacion"] ?? null, "La observación", 300);
        }
        if ($error !== null) {
            Response::error($error, 400);
        }
        $previa = Recepcion::obtener($id);
        $nivelAntes = $previa === null ? "normal" : CentroStock::resumen(CentroAcopio::obtener($previa["id_centro"]))["nivel"];
        try {
            $detalle = Recepcion::confirmar($id, $lineas, Campos::texto($datos["observacion"] ?? null), (int) $usuario["id_usuario"], $centro);
        } catch (ErrorDeNegocio $e) {
            Response::error($e->getMessage(), $e->estadoHttp());
        }
        $centroFila = CentroAcopio::obtener($detalle["id_centro"]);
        $resumen = CentroStock::resumen($centroFila);
        // El aviso salta cuando el centro PASA el 80 % (o llega al tope), no en cada recepcion que sigue arriba.
        $rango = ["sin_capacidad" => 0, "normal" => 0, "alerta" => 1, "lleno" => 2];
        if ($rango[$resumen["nivel"]] > $rango[$nivelAntes]) {
            [$titulo, $mensaje] = Notificador::centroLleno($centroFila["nombre"], (float) $resumen["ocupacion_pct"], $resumen["nivel"]);
            Notificador::avisarA(array_merge(Notificador::administradores(), Notificador::operariosDeCentro((int) $centroFila["id_centro"])),
                                 "centro_" . $resumen["nivel"], $titulo, $mensaje, "centro.html?id=" . (int) $centroFila["id_centro"]);
        }
        $aviso = in_array($resumen["nivel"], ["alerta", "lleno"], true)
            ? "Atención: el centro está al " . str_replace(".", ",", (string) $resumen["ocupacion_pct"]) . " % de su capacidad." : null;
        Response::json(["ok" => true, "mensaje" => "Recepción registrada: el stock del centro subió", "datos" => $detalle,
                        "aviso" => $aviso], 201);
    }
}
