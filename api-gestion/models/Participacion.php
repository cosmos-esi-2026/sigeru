<?php

// Participacion del vecino (extras, parte 14): calificar la atencion, puntos, insignias y ranking por zona.
// Clase pura: no toca la base. Los puntos solo se suman cuando el reclamo SE RESUELVE (no por crearlo, para evitar
// abusos) y al calificar; un reclamo descartado no suma. Las insignias se calculan, no se guardan.
class Participacion
{
    public const PLAZO_DIAS = 30;
    public const PUNTOS_RESUELTO = 10;
    public const PUNTOS_CALIFICAR = 5;
    public const COMENTARIO_MAXIMO = 300;

    // "tipo": que se cuenta (reclamos resueltos o calificaciones hechas); "meta": cuantos hacen falta.
    public const INSIGNIAS = [
        ["clave" => "primer_aviso",    "nombre" => "Primer aviso resuelto", "descripcion" => "Un reclamo tuyo se resolvió.",           "tipo" => "resueltos",   "meta" => 1],
        ["clave" => "cinco_resueltos", "nombre" => "Vecino activo",         "descripcion" => "Cinco reclamos tuyos resueltos.",        "tipo" => "resueltos",   "meta" => 5],
        ["clave" => "diez_resueltos",  "nombre" => "Vecino ejemplar",       "descripcion" => "Diez reclamos tuyos resueltos.",         "tipo" => "resueltos",   "meta" => 10],
        ["clave" => "opina",           "nombre" => "Opina para mejorar",    "descripcion" => "Calificaste la atención de un reclamo.", "tipo" => "calificados", "meta" => 1],
    ];

    // Un puntaje de 1 a 5 (entero o texto con solo digitos). Null si no sirve.
    public static function puntajeValido($valor): ?int
    {
        $esEntero = is_int($valor) || (is_string($valor) && preg_match('/^[1-5]$/', $valor) === 1);
        return $esEntero && (int) $valor >= 1 && (int) $valor <= 5 ? (int) $valor : null;
    }

    public static function errorDeComentario($valor): ?string
    {
        if ($valor === null || $valor === "") {
            return null;
        }
        return is_string($valor) && mb_strlen(trim($valor)) <= self::COMENTARIO_MAXIMO
            ? null : "El comentario puede tener hasta " . self::COMENTARIO_MAXIMO . " caracteres";
    }

    // "2026-09-01 11:00:00" -> "2026-10-01 11:00:00"
    public static function plazoHasta(string $fechaResolucion): ?string
    {
        try {
            return (new DateTimeImmutable($fechaResolucion, new DateTimeZone("UTC")))->modify("+" . self::PLAZO_DIAS . " days")->format("Y-m-d H:i:s");
        } catch (Exception $e) {
            return null;
        }
    }

    public static function dentroDelPlazo(?string $fechaResolucion, string $ahora): bool
    {
        if ($fechaResolucion === null) {
            return false;
        }
        $hasta = self::plazoHasta($fechaResolucion);
        return $hasta !== null && $ahora <= $hasta;
    }

    // [codigo HTTP, mensaje] o null si el vecino puede calificar. Solo el autor, un reclamo resuelto, una vez y a tiempo.
    public static function errorDeCalificacion(array $incidencia, array $usuario, bool $yaCalificada, string $ahora): ?array
    {
        if (($usuario["rol"] ?? "") !== "Vecino" || (int) ($usuario["id_usuario"] ?? 0) !== (int) ($incidencia["id_usuario"] ?? -1)) {
            return [404, "Incidencia no encontrada"];
        }
        if ($incidencia["estado"] === "descartada") {
            return [409, "Un reclamo descartado no se califica"];
        }
        if ($incidencia["estado"] !== "resuelta") {
            return [409, "Solo se puede calificar un reclamo resuelto"];
        }
        if ($yaCalificada) {
            return [409, "Ese reclamo ya fue calificado"];
        }
        if (!self::dentroDelPlazo($incidencia["fecha_resolucion"] ?? null, $ahora)) {
            return [409, "El plazo para calificar (" . self::PLAZO_DIAS . " días desde la resolución) venció"];
        }
        return null;
    }

    // Todas las insignias, con si estan logradas y cuanto falta. Se calculan a partir de dos conteos.
    public static function insignias(int $resueltos, int $calificados): array
    {
        return array_map(function (array $i) use ($resueltos, $calificados) {
            $actual = $i["tipo"] === "resueltos" ? $resueltos : $calificados;
            return ["clave" => $i["clave"], "nombre" => $i["nombre"], "descripcion" => $i["descripcion"],
                    "lograda" => $actual >= $i["meta"], "progreso" => min($actual, $i["meta"]), "meta" => $i["meta"]];
        }, self::INSIGNIAS);
    }

    // Ranking de zonas: [["zona", "resueltos", "puntos"], ...] -> con posicion (los empates comparten posicion). Nunca por persona.
    public static function ranking(array $filas): array
    {
        usort($filas, fn($a, $b) => [$b["puntos"], $b["resueltos"], $a["zona"]] <=> [$a["puntos"], $a["resueltos"], $b["zona"]]);
        return array_map(function (array $fila) use ($filas) {
            $mejores = count(array_filter($filas, fn($otra) => (int) $otra["puntos"] > (int) $fila["puntos"]));
            return ["posicion" => $mejores + 1, "zona" => $fila["zona"], "resueltos" => (int) $fila["resueltos"], "puntos" => (int) $fila["puntos"]];
        }, $filas);
    }

    public static function textoMotivo(string $motivo): string
    {
        return $motivo === "reclamo_resuelto" ? "Reclamo resuelto" : "Calificación enviada";
    }
}
