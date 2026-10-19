<?php

require_once __DIR__ . "/Response.php";
require_once __DIR__ . "/../config/database.php";

// Reenvios sin duplicar (extras, parte 21). Es una COPIA IDENTICA en api-gestion y api-recoleccion: tests/unitarias.php falla si difieren.
//
// La Cuadrilla puede guardar sin conexion dos acciones (registrar un vaciado, registrar un avance de incidencia) y reenviarlas despues.
// Cada una lleva una clave unica en el encabezado "Idempotency-Key" (un UUID). Si el mismo pedido llega otra vez (el celular no supo
// si el primero salio bien), el servidor NO lo repite: devuelve la misma respuesta, con "repetida": true.
//   - comenzar() reserva la clave ("en_proceso") antes de hacer nada; dos pedidos iguales simultaneos no se ejecutan a la vez (409).
//   - Response::json() llama a terminar() justo antes de responder: una respuesta exitosa (2xx) se guarda; un error NO se recuerda
//     (se borra la reserva) para que la persona pueda corregir y reintentar.
//   - Sin el encabezado no pasa nada: el pedido se procesa como siempre.
class Idempotencia
{
    private const FORMATO = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/iD';
    // Una reserva que quedo "en_proceso" mas de este tiempo se considera abandonada (el servidor se cayo a mitad de camino).
    private const SEGUNDOS_DE_RESERVA = 120;
    private const DIAS_DE_MEMORIA = 30;

    private static ?string $activa = null;

    public static function formatoValido(string $clave): bool
    {
        return preg_match(self::FORMATO, $clave) === 1;
    }

    public static function comenzar(array $usuario, string $operacion): void
    {
        $crudo = $_SERVER["HTTP_IDEMPOTENCY_KEY"] ?? null;
        if ($crudo === null || $crudo === "") {
            return;
        }
        if (!is_string($crudo) || !self::formatoValido($crudo)) {
            Response::error("Clave de operación inválida: tiene que ser un UUID", 400);
        }
        $clave = strtolower($crudo);
        $db = Database::conexion();

        $db->prepare("DELETE FROM clave_idempotencia WHERE clave = :c AND estado = 'en_proceso' AND creada < DATE_SUB(NOW(), INTERVAL " . self::SEGUNDOS_DE_RESERVA . " SECOND)")
           ->execute([":c" => $clave]);
        try {
            $db->prepare("INSERT INTO clave_idempotencia (clave, id_usuario, operacion) VALUES (:c, :u, :o)")
               ->execute([":c" => $clave, ":u" => (int) $usuario["id_usuario"], ":o" => substr($operacion, 0, 40)]);
            self::$activa = $clave;
            if (random_int(1, 50) === 1) {
                $db->exec("DELETE FROM clave_idempotencia WHERE creada < DATE_SUB(NOW(), INTERVAL " . self::DIAS_DE_MEMORIA . " DAY)");
            }
            return;
        } catch (PDOException $e) {
            if ((string) $e->getCode() !== "23000") {
                throw $e;
            }
        }

        $consulta = $db->prepare("SELECT id_usuario, operacion, estado, codigo, respuesta FROM clave_idempotencia WHERE clave = :c");
        $consulta->execute([":c" => $clave]);
        $fila = $consulta->fetch();
        if ($fila === false) {
            Response::error("No se pudo comprobar la clave de operación: reintentá", 409);
        }
        if ((int) $fila["id_usuario"] !== (int) $usuario["id_usuario"] || $fila["operacion"] !== $operacion) {
            Response::error("Esa clave de operación ya se usó para otra cosa", 409);
        }
        if ($fila["estado"] !== "terminada") {
            Response::error("Esa operación ya se está procesando: esperá un momento y reintentá", 409);
        }
        self::repetir((int) $fila["codigo"], (string) $fila["respuesta"]);
    }

    // Lo llama Response::json() antes de responder.
    public static function terminar(int $codigo, $datos): void
    {
        if (self::$activa === null) {
            return;
        }
        $clave = self::$activa;
        self::$activa = null;
        try {
            if ($codigo >= 200 && $codigo < 300) {
                Database::conexion()->prepare("UPDATE clave_idempotencia SET estado = 'terminada', codigo = :k, respuesta = :r WHERE clave = :c")
                    ->execute([":k" => $codigo, ":r" => json_encode($datos, JSON_UNESCAPED_UNICODE), ":c" => $clave]);
            } else {
                Database::conexion()->prepare("DELETE FROM clave_idempotencia WHERE clave = :c")->execute([":c" => $clave]);
            }
        } catch (Throwable $e) {
            error_log("SiGeRU idempotencia: no se pudo cerrar la clave (" . $e->getMessage() . ")");
        }
    }

    // La misma respuesta de la primera vez, marcada como repetida. No pasa por Response::json (no se audita: no cambia nada).
    private static function repetir(int $codigo, string $respuesta): void
    {
        $datos = json_decode($respuesta, true);
        $datos = is_array($datos) ? $datos : ["ok" => true];
        $datos["repetida"] = true;
        http_response_code($codigo);
        header("Content-Type: application/json; charset=utf-8");
        header("Cache-Control: no-store");
        header("X-Content-Type-Options: nosniff");
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
