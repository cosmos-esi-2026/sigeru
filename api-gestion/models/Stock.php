<?php

// Reglas del stock de un centro de acopio (extras, parte 12). Clase pura: no toca la base.
// El stock de un centro nunca se guarda: es la suma de sus movimientos (entradas menos salidas),
// que son de solo agregado. Un error se corrige con un movimiento inverso.
class Stock
{
    public const UMBRAL_ALERTA = 80.0;
    public const MOTIVOS_RETIRO = ["venta", "traslado", "disposicion"];
    public const KG_MAXIMO = 1000000.0;

    // Suma los movimientos por tipo de residuo: [id_tipo_residuo => kg]. Nunca queda un menos cero.
    public static function saldos(array $movimientos): array
    {
        $saldos = [];
        foreach ($movimientos as $m) {
            $tipo = (int) $m["id_tipo_residuo"];
            $signo = $m["sentido"] === "entrada" ? 1 : -1;
            $saldos[$tipo] = round(($saldos[$tipo] ?? 0.0) + $signo * (float) $m["kg"], 2);
        }
        return array_map(fn($kg) => abs($kg) < 0.005 ? 0.0 : $kg, $saldos);
    }

    public static function total(array $saldos): float
    {
        return round(array_sum($saldos), 2);
    }

    // Porcentaje de la capacidad ocupado, con un decimal. Sin capacidad cargada no hay porcentaje.
    public static function ocupacion(float $kg, ?int $capacidad): ?float
    {
        if ($capacidad === null || $capacidad <= 0) {
            return null;
        }
        return round($kg / $capacidad * 100, 1);
    }

    // "alerta" cuando pasa del 80 %; "lleno" desde el 100 %.
    public static function nivel(?float $ocupacion): string
    {
        if ($ocupacion === null) {
            return "sin_capacidad";
        }
        if ($ocupacion >= 100.0) {
            return "lleno";
        }
        return $ocupacion > self::UMBRAL_ALERTA ? "alerta" : "normal";
    }

    // Un peso valido: numero positivo, hasta 2 decimales y no mas de KG_MAXIMO. Devuelve null si no sirve.
    public static function kgValidos($valor): ?float
    {
        if (is_string($valor)) {
            $valor = trim($valor);
            if (!preg_match('/^\d{1,7}(\.\d{1,2})?$/', $valor)) {
                return null;
            }
            $valor = (float) $valor;
        }
        if (!(is_int($valor) || is_float($valor))) {
            return null;
        }
        $valor = (float) $valor;
        if ($valor <= 0 || $valor > self::KG_MAXIMO || abs($valor * 100 - round($valor * 100)) > 1e-6) {
            return null;
        }
        return round($valor, 2);
    }

    public static function errorDeRetiro(array $datos, float $saldoDelTipo): ?string
    {
        if (!in_array($datos["motivo"] ?? null, self::MOTIVOS_RETIRO, true)) {
            return "Motivo inválido. Valores admitidos: " . implode(", ", self::MOTIVOS_RETIRO);
        }
        $kg = self::kgValidos($datos["kg"] ?? null);
        if ($kg === null) {
            return "Los kg tienen que ser un número mayor que 0, con hasta 2 decimales";
        }
        if ($kg > $saldoDelTipo + 0.001) {
            return "No hay tanto stock de ese tipo: hay " . self::kgTexto($saldoDelTipo) . " y se piden " . self::kgTexto($kg);
        }
        return null;
    }

    // Se puede corregir un movimiento propio de la operatoria, una sola vez, y nunca una correccion.
    // Si el inverso es una salida, tiene que haber stock para sacarla.
    public static function errorDeCorreccion(array $movimiento, bool $yaCorregido, float $saldoDelTipo): ?string
    {
        if ($movimiento["motivo"] === "correccion") {
            return "Una corrección no se corrige: registrá un movimiento nuevo";
        }
        if ($yaCorregido) {
            return "Ese movimiento ya fue corregido";
        }
        if ($movimiento["sentido"] === "entrada" && (float) $movimiento["kg"] > $saldoDelTipo + 0.001) {
            return "No se puede corregir: ya salió parte de ese stock (hay " . self::kgTexto($saldoDelTipo) . " de ese tipo)";
        }
        return null;
    }

    public static function sentidoInverso(string $sentido): string
    {
        return $sentido === "entrada" ? "salida" : "entrada";
    }

    // Lineas de una recepcion: [{id_tipo_residuo, kg}]. Devuelve [lineas normalizadas, error].
    // Un mismo tipo no puede repetirse y tiene que haber al menos un tipo con peso.
    public static function lineasDeRecepcion($lineas, array $tiposValidos): array
    {
        if (!is_array($lineas) || $lineas === [] || array_keys($lineas) !== range(0, count($lineas) - 1)) {
            return [[], "Cargá al menos un tipo de residuo con su peso"];
        }
        if (count($lineas) > 20) {
            return [[], "Demasiadas líneas: hasta 20"];
        }
        $resultado = [];
        foreach ($lineas as $linea) {
            $tipo = is_array($linea) ? ($linea["id_tipo_residuo"] ?? null) : null;
            $esEntero = is_int($tipo) || (is_string($tipo) && ctype_digit($tipo));
            if (!$esEntero || !in_array((int) $tipo, $tiposValidos, true)) {
                return [[], "Hay un tipo de residuo que no existe"];
            }
            if (isset($resultado[(int) $tipo])) {
                return [[], "Un mismo tipo de residuo no puede repetirse"];
            }
            $kg = self::kgValidos($linea["kg"] ?? null);
            if ($kg === null) {
                return [[], "Los kg de cada tipo tienen que ser un número mayor que 0, con hasta 2 decimales"];
            }
            $resultado[(int) $tipo] = $kg;
        }
        $normalizadas = [];
        foreach ($resultado as $tipo => $kg) {
            $normalizadas[] = ["id_tipo_residuo" => $tipo, "kg" => $kg];
        }
        return [$normalizadas, null];
    }

    public static function kgTexto(float $kg): string
    {
        return rtrim(rtrim(number_format($kg, 2, ",", "."), "0"), ",") . " kg";
    }

    public static function numeroDeRecoleccion(int $id): string
    {
        return sprintf("REC-%04d", $id);
    }
}
