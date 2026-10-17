<?php

class FlujoRecoleccion
{
    public const ESTADOS = ["planificada", "en_curso", "finalizada", "cancelada"];
    public const DIAS = ["lunes", "martes", "miercoles", "jueves", "viernes", "sabado", "domingo"];
    public const PESO_MAXIMO_KG = 5000;

    private const ESTADOS_ABIERTOS = ["planificada", "en_curso"];

    private const ACCIONES = [
        "iniciar"   => ["planificada"],
        "vaciado"   => ["en_curso"],
        "finalizar" => ["en_curso"],
        "cancelar"  => ["planificada", "en_curso"],
    ];

    private const ESTADO_RESULTANTE = [
        "iniciar"   => "en_curso",
        "vaciado"   => "en_curso",
        "finalizar" => "finalizada",
        "cancelar"  => "cancelada",
    ];

    public static function numero(int $id): string
    {
        return sprintf("REC-%04d", $id);
    }

    public static function estaAbierta(string $estado): bool
    {
        return in_array($estado, self::ESTADOS_ABIERTOS, true);
    }

    public static function puede(string $accion, string $estado): bool
    {
        return in_array($estado, self::ACCIONES[$accion] ?? [], true);
    }

    public static function estadoTras(string $accion, string $estadoActual): string
    {
        return self::ESTADO_RESULTANTE[$accion] ?? $estadoActual;
    }

    public static function motivoDeRechazo(string $accion, string $estado): string
    {
        if (!self::estaAbierta($estado)) {
            return "La recolección ya está " . ($estado === "finalizada" ? "finalizada" : "cancelada") .
                   " y no admite más cambios";
        }
        if ($accion === "iniciar") {
            return "La recolección ya está en curso";
        }
        return "La recolección todavía no empezó: primero hay que iniciarla";
    }

    public static function alcanza(array $usuario, array $recoleccion): bool
    {
        return match ($usuario["rol"] ?? "") {
            "Administrador" => true,
            "Cuadrilla"     => ($usuario["id_cuadrilla"] ?? null) !== null
                               && (int) $usuario["id_cuadrilla"] === (int) ($recoleccion["id_cuadrilla"] ?? 0),
            default         => false,
        };
    }

    public static function motivoCamionNoDisponible(string $estadoCamion, string $matricula): ?string
    {
        return match ($estadoCamion) {
            "operativo"      => null,
            "en_ruta"        => "El camión $matricula ya está en ruta",
            "mantenimiento"  => "El camión $matricula está en mantenimiento",
            default          => "El camión $matricula está fuera de servicio",
        };
    }

    public static function estadoCamionTrasCierre(string $estadoRecoleccion, string $estadoCamion): ?string
    {
        return $estadoRecoleccion === "en_curso" && $estadoCamion === "en_ruta" ? "operativo" : null;
    }

    public static function entero($valor): ?int
    {
        if (is_int($valor)) {
            return $valor > 0 ? $valor : null;
        }
        if (is_string($valor) && ctype_digit($valor) && (int) $valor > 0) {
            return (int) $valor;
        }
        return null;
    }

    public static function limpiar($valor): string
    {
        return is_string($valor) ? trim($valor) : "";
    }

    public static function fechaHora($valor): ?string
    {
        if (!is_string($valor)) {
            return null;
        }
        $texto = str_replace("T", " ", trim($valor));
        $fecha = DateTime::createFromFormat("!Y-m-d H:i", substr($texto, 0, 16));
        if ($fecha === false || $fecha->format("Y-m-d H:i") !== substr($texto, 0, 16)) {
            return null;
        }
        return $fecha->format("Y-m-d H:i:00");
    }

    public static function errorDeNueva(array $datos): ?string
    {
        foreach (["id_ruta" => "la ruta", "id_camion" => "el camión", "id_cuadrilla" => "la cuadrilla"] as $campo => $nombre) {
            if (self::entero($datos[$campo] ?? null) === null) {
                return "Indicá $nombre";
            }
        }
        if (self::fechaHora($datos["fecha_hora_inicio"] ?? null) === null) {
            return "Indicá la fecha y la hora de inicio (AAAA-MM-DD HH:MM)";
        }
        if (isset($datos["id_centro"]) && $datos["id_centro"] !== "" && self::entero($datos["id_centro"]) === null) {
            return "El centro de destino tiene que ser el número de identificación";
        }
        return self::errorDeTexto($datos["observaciones"] ?? null, false, "Las observaciones no pueden");
    }

    public static function errorDeTexto($valor, bool $obligatorio, string $sujeto = "La observación no puede"): ?string
    {
        $texto = self::limpiar($valor);
        if ($texto === "" && $obligatorio) {
            return "Escribí el motivo";
        }
        if (mb_strlen($texto) > 300) {
            return "$sujeto pasar de 300 caracteres";
        }
        return null;
    }

    public static function errorDeVaciado(array $datos, ?int $capacidadContenedor = null): ?string
    {
        if (self::entero($datos["id_contenedor"] ?? null) === null
            && self::limpiar($datos["codigo_contenedor"] ?? null) === "") {
            return "Indicá el contenedor por su código";
        }
        $peso = $datos["peso_kg"] ?? null;
        if (!is_numeric($peso) || (float) $peso <= 0 || (float) $peso > self::PESO_MAXIMO_KG) {
            return "El peso tiene que ser un número mayor que 0 y de hasta " . self::PESO_MAXIMO_KG . " kg";
        }
        $volumen = $datos["volumen_l"] ?? null;
        if ($volumen === null || $volumen === "") {
            return null;
        }
        if (!is_numeric($volumen) || (float) $volumen < 0) {
            return "El volumen tiene que ser un número mayor o igual a 0";
        }
        if ($capacidadContenedor !== null && (float) $volumen > $capacidadContenedor) {
            return "El volumen no puede superar la capacidad del contenedor ($capacidadContenedor L)";
        }
        return null;
    }

    public static function errorDeRuta(array $datos, bool $esAlta): ?string
    {
        if ($esAlta || array_key_exists("nombre", $datos)) {
            $largo = mb_strlen(self::limpiar($datos["nombre"] ?? null));
            if ($largo < 3 || $largo > 80) {
                return "El nombre de la ruta tiene que tener entre 3 y 80 caracteres";
            }
        }
        if (($esAlta || array_key_exists("dia_semana", $datos)) && !in_array($datos["dia_semana"] ?? null, self::DIAS, true)) {
            return "Día inválido. Valores admitidos: " . implode(", ", self::DIAS);
        }
        if ($esAlta || array_key_exists("hora_inicio", $datos)) {
            $hora = is_string($datos["hora_inicio"] ?? null) ? DateTime::createFromFormat("!H:i", substr($datos["hora_inicio"], 0, 5)) : false;
            if ($hora === false || $hora->format("H:i") !== substr($datos["hora_inicio"], 0, 5)) {
                return "La hora de inicio tiene que tener el formato HH:MM";
            }
        }
        if (($esAlta || array_key_exists("id_zona", $datos)) && self::entero($datos["id_zona"] ?? null) === null) {
            return "Indicá la zona de la ruta";
        }
        if (array_key_exists("estado", $datos) && !in_array($datos["estado"], ["activa", "inactiva"], true)) {
            return "Estado inválido. Valores admitidos: activa, inactiva";
        }
        return null;
    }

    public static function filtrosDe(array $consulta): array
    {
        $filtros = [];

        $estado = $consulta["estado"] ?? "";
        if ($estado !== "") {
            if (!is_string($estado) || !in_array($estado, self::ESTADOS, true)) {
                return ["filtros" => [], "error" => "Filtro 'estado' inválido. Valores admitidos: " . implode(", ", self::ESTADOS)];
            }
            $filtros["estado"] = $estado;
        }
        if (($consulta["activas"] ?? "") === "1") {
            $filtros["activas"] = true;
        }

        foreach (["ruta", "camion", "cuadrilla"] as $clave) {
            $valor = $consulta[$clave] ?? "";
            if ($valor === "") {
                continue;
            }
            $numero = self::entero($valor);
            if ($numero === null) {
                return ["filtros" => [], "error" => "Filtro '$clave' inválido: tiene que ser el número de identificación"];
            }
            $filtros[$clave] = $numero;
        }

        foreach (["desde", "hasta"] as $clave) {
            $valor = $consulta[$clave] ?? "";
            if ($valor === "") {
                continue;
            }
            $fecha = is_string($valor) ? DateTime::createFromFormat("!Y-m-d", $valor) : false;
            if ($fecha === false || $fecha->format("Y-m-d") !== $valor) {
                return ["filtros" => [], "error" => "Filtro '$clave' inválido: usá el formato AAAA-MM-DD"];
            }
            $filtros[$clave] = $valor;
        }

        if (isset($filtros["desde"], $filtros["hasta"]) && $filtros["desde"] > $filtros["hasta"]) {
            return ["filtros" => [], "error" => "La fecha 'desde' no puede ser posterior a 'hasta'"];
        }

        return ["filtros" => $filtros, "error" => null];
    }
}
