<?php

class FlujoIncidencia
{
    public const ESTADOS = ["abierta", "en_curso", "resuelta", "descartada"];
    public const PRIORIDADES = ["baja", "media", "alta"];
    public const TIPOS = ["desbordado", "lleno", "roto", "residuo_no_permitido", "ausente_o_mal_ubicado", "otro"];
    // Lo que se guarda como descripcion cuando la persona no escribio ninguna (el tipo ya dice que pasa; los mismos rotulos que incidencias-comun.js).
    public const TEXTO_TIPO = [
        "desbordado" => "Contenedor desbordado", "lleno" => "Contenedor lleno", "roto" => "Contenedor roto o dañado",
        "residuo_no_permitido" => "Residuo no permitido", "ausente_o_mal_ubicado" => "Contenedor ausente o mal ubicado", "otro" => "Otro",
    ];

    // Prioridad con la que entra cada tipo si quien reporta no la elige (decision SIN Fornos, a partir de lo que dijo el profe: «lleno» es media y «desbordado» es mas
    // urgente; un contenedor roto lo atiende mantenimiento). El administrador la puede cambiar despues.
    public const PRIORIDAD_POR_TIPO = [
        "desbordado" => "alta", "lleno" => "media", "roto" => "media",
        "ausente_o_mal_ubicado" => "media", "residuo_no_permitido" => "baja", "otro" => "baja",
    ];

    public static function prioridadDeTipo(string $tipo): string
    {
        return self::PRIORIDAD_POR_TIPO[$tipo] ?? "media";
    }

    // Un contenedor roto o danado no lo recolecta el camion: no se asigna a una cuadrilla, lo deriva el administrador a mantenimiento.
    public static function esDeMantenimiento(string $tipo): bool
    {
        return $tipo === "roto";
    }

    private const ESTADOS_ABIERTOS = ["abierta", "en_curso"];

    private const ACCIONES = [
        "asignar"   => ["abierta", "en_curso"],
        "prioridad" => ["abierta", "en_curso"],
        "avance"    => ["en_curso"],
        "resolver"  => ["abierta", "en_curso"],
        "descartar" => ["abierta", "en_curso"],
        "derivar"   => ["abierta", "en_curso"],
    ];

    private const ESTADO_RESULTANTE = [
        "asignar"   => "en_curso",
        "avance"    => "en_curso",
        "resolver"  => "resuelta",
        "descartar" => "descartada",
    ];

    public static function numero(int $id): string
    {
        return sprintf("INC-%04d", $id);
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
            return "La incidencia ya está " . ($estado === "resuelta" ? "resuelta" : "descartada") .
                   " y no admite más cambios";
        }
        return "Para registrar un avance la incidencia tiene que estar asignada a una cuadrilla";
    }

    public static function alcanza(array $usuario, array $incidencia): bool
    {
        return match ($usuario["rol"] ?? "") {
            "Administrador" => true,
            // La cuadrilla ve las que se le asignaron y las que ella misma informo (todos pueden reportar: vecinos y cuadrillas).
            "Cuadrilla"     => (($usuario["id_cuadrilla"] ?? null) !== null
                               && (int) $usuario["id_cuadrilla"] === (int) ($incidencia["id_cuadrilla"] ?? 0))
                               || (int) ($usuario["id_usuario"] ?? 0) === (int) ($incidencia["id_usuario"] ?? -1),
            "Vecino"        => (int) ($usuario["id_usuario"] ?? 0) === (int) ($incidencia["id_usuario"] ?? -1),
            default         => false,
        };
    }

    public static function historialVisible(array $asientos, array $usuario): array
    {
        if (($usuario["rol"] ?? "") === "Administrador") {
            return $asientos;
        }
        $esVecino = ($usuario["rol"] ?? "") === "Vecino";
        return array_map(function (array $asiento) use ($usuario, $esVecino) {
            $esPropio = (int) $asiento["id_usuario"] === (int) ($usuario["id_usuario"] ?? 0);
            // Un vecino ajeno nunca se ve con nombre; y al vecino el personal se le muestra por su rol, no por su nombre.
            if (($asiento["rol"] === "Vecino" || $esVecino) && !$esPropio) {
                $asiento["usuario"] = null;
            }
            unset($asiento["id_usuario"]);
            return $asiento;
        }, $asientos);
    }

    public static function estadoContenedorTrasReporte(string $tipo, string $estadoContenedor): ?string
    {
        return in_array($tipo, ["desbordado", "lleno"], true) && $estadoContenedor === "disponible" ? "lleno" : null;
    }

    public static function estadoContenedorTrasCierre(string $estadoContenedor, int $otrasAbiertas): ?string
    {
        if ($otrasAbiertas > 0) {
            return null;
        }
        return in_array($estadoContenedor, ["lleno", "mantenimiento"], true) ? "disponible" : null;
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

    public static function errorDePrioridad($valor): ?string
    {
        if (!in_array($valor, self::PRIORIDADES, true)) {
            return "Prioridad inválida. Valores admitidos: " . implode(", ", self::PRIORIDADES);
        }
        return null;
    }

    public static function errorDeObservacion(string $texto, bool $obligatoria): ?string
    {
        if ($texto === "" && $obligatoria) {
            return "Escribí una observación";
        }
        if (mb_strlen($texto) > 300) {
            return "La observación no puede pasar de 300 caracteres";
        }
        return null;
    }

    // La descripcion que se guarda: la escrita o, si no hay, el rotulo del tipo.
    public static function descripcionDe(array $datos): string
    {
        $texto = self::limpiar($datos["descripcion"] ?? null);
        return $texto !== "" ? $texto : (self::TEXTO_TIPO[$datos["tipo_problema"] ?? ""] ?? "");
    }

    public static function errorDeNueva(array $datos): ?string
    {
        if (self::entero($datos["id_contenedor"] ?? null) === null
            && self::limpiar($datos["codigo_contenedor"] ?? null) === "") {
            return "Indicá el contenedor por su código";
        }
        if (!in_array($datos["tipo_problema"] ?? null, self::TIPOS, true)) {
            return "Tipo de problema inválido. Valores admitidos: " . implode(", ", self::TIPOS);
        }

        // La descripcion es opcional (el tipo ya dice que pasa y un aviso rapido desde el QR no tiene que frenarse): solo «otro» la exige.
        // Si se escribe algo, tiene que ser un texto de verdad (5 caracteres o mas).
        $largo = mb_strlen(self::limpiar($datos["descripcion"] ?? null));
        if (($largo === 0 && $datos["tipo_problema"] === "otro") || ($largo > 0 && $largo < 5)) {
            return "Contá el problema en al menos 5 caracteres";
        }
        if ($largo > 500) {
            return "La descripción no puede pasar de 500 caracteres";
        }
        if (isset($datos["prioridad"])) {
            return self::errorDePrioridad($datos["prioridad"]);
        }
        return null;
    }

    public static function filtrosDe(array $consulta): array
    {
        $filtros = [];

        foreach (["estado" => self::ESTADOS, "tipo" => self::TIPOS] as $clave => $validos) {
            $valor = $consulta[$clave] ?? "";
            if ($valor === "") {
                continue;
            }
            if (!is_string($valor) || !in_array($valor, $validos, true)) {
                return ["filtros" => [], "error" => "Filtro '$clave' inválido. Valores admitidos: " . implode(", ", $validos)];
            }
            $filtros[$clave] = $valor;
        }

        foreach (["zona", "contenedor", "cuadrilla"] as $clave) {
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
