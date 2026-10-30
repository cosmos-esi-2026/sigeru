<?php

require_once __DIR__ . "/../config/database.php";

// Avisos (extras, parte 13): UNA sola funcion para escribir un aviso, usada por api-gestion y api-recoleccion.
// Es una COPIA IDENTICA en api-recoleccion/core/Notificador.php: tests/unitarias.php falla si difieren.
//
// Reglas: un aviso NUNCA rompe la operacion que lo causa (todo error se anota en el log y se sigue) y nunca lleva
// datos de otra persona: solo el numero, la direccion del contenedor o el nombre del centro. El correo solo
// se manda si quien lo recibe lo tiene activado y la cuenta esta activa; sin correo configurado queda la campanita.
class Notificador
{
    public const LARGO_TITULO = 120;
    public const LARGO_MENSAJE = 300;

    // --- Textos (puros): cada evento arma su titulo y su mensaje ---------------------------------------------

    public static function incidenciaEstado(string $numero, string $estado): array
    {
        return match ($estado) {
            "en_curso"   => ["Tu reclamo $numero está en curso", "Una cuadrilla ya está atendiendo tu reclamo $numero."],
            "resuelta"   => ["Tu reclamo $numero fue resuelto", "Tu reclamo $numero se resolvió. Gracias por avisar: si el problema sigue, informalo de nuevo."],
            "descartada" => ["Tu reclamo $numero fue descartado", "Revisamos tu reclamo $numero y se descartó (por ejemplo, porque estaba repetido). El detalle está en «Mis reclamos»."],
            "derivada"   => ["Tu reclamo $numero pasó a mantenimiento", "El contenedor de tu reclamo $numero está roto o dañado: no lo recolecta el camión, lo atiende mantenimiento. Te avisamos cuando se resuelva."],
            default      => ["Tu reclamo $numero cambió", "Tu reclamo $numero cambió de estado."],
        };
    }

    public static function incidenciaAsignada(string $numero, string $direccion): array
    {
        return ["Incidencia asignada: $numero", "Se asignó la incidencia $numero (" . $direccion . ") a tu cuadrilla."];
    }

    public static function recoleccionAsignada(string $numero, string $ruta, string $fecha): array
    {
        return ["Nueva recolección: $numero", "Se planificó la recolección $numero de la ruta $ruta para el $fecha."];
    }

    public static function incidenciaCritica(string $numero, string $direccion): array
    {
        return ["Incidencia crítica: $numero", "Entró una incidencia de prioridad alta ($numero) en " . $direccion . "."];
    }

    public static function contenedorRoto(string $numero, string $direccion): array
    {
        return ["Contenedor dañado: $numero", "Se informó un contenedor roto o dañado ($numero) en " . $direccion . ". No lo recolecta el camión: derivalo a mantenimiento."];
    }

    public static function centroLleno(string $nombre, float $ocupacion, string $nivel): array
    {
        $pct = str_replace(".", ",", (string) round($ocupacion, 1));
        return $nivel === "lleno"
            ? [$nombre . " llegó a su capacidad", "El centro «" . $nombre . "» está al " . $pct . " % de su capacidad: hay que retirar material."]
            : [$nombre . " pasó del 80 %", "El centro «" . $nombre . "» está al " . $pct . " % de su capacidad: conviene planificar un retiro."];
    }

    // "2026-10-21 06:30:00" -> "21/10/2026 06:30"
    public static function fechaLegible(string $fecha): string
    {
        return preg_match('/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/', $fecha, $m) ? "$m[3]/$m[2]/$m[1] $m[4]:$m[5]" : $fecha;
    }

    // --- Quienes reciben --------------------------------------------------------------------------------------

    private static function ids(string $sql, array $valores = []): array
    {
        try {
            $consulta = Database::conexion()->prepare($sql);
            $consulta->execute($valores);
            return array_map("intval", $consulta->fetchAll(PDO::FETCH_COLUMN));
        } catch (Throwable $e) {
            error_log("SiGeRU: no se pudo buscar a quien avisar: " . $e->getMessage());
            return [];
        }
    }

    public static function administradores(): array
    {
        return self::ids("SELECT u.id_usuario FROM usuario u JOIN rol r ON r.id_rol = u.id_rol
                          WHERE r.nombre = 'Administrador' AND u.estado = 'activo'");
    }

    public static function deCuadrilla(int $idCuadrilla): array
    {
        return self::ids("SELECT id_usuario FROM usuario WHERE id_cuadrilla = :c AND estado = 'activo'", [":c" => $idCuadrilla]);
    }

    public static function operariosDeCentro(int $idCentro): array
    {
        return self::ids("SELECT id_usuario FROM usuario WHERE id_centro = :c AND estado = 'activo'", [":c" => $idCentro]);
    }

    // --- Escribir el aviso ------------------------------------------------------------------------------------

    // $enlace: pagina de frontend-usuarios, por ejemplo "incidencia.html?id=3".
    public static function avisar(int $idUsuario, string $tipo, string $titulo, string $mensaje, ?string $enlace = null): void
    {
        try {
            $titulo = mb_substr($titulo, 0, self::LARGO_TITULO);
            $mensaje = mb_substr($mensaje, 0, self::LARGO_MENSAJE);
            Database::conexion()->prepare("INSERT INTO notificacion (id_usuario, tipo, titulo, mensaje, enlace)
                                           VALUES (:u, :t, :ti, :m, :e)")
                ->execute([":u" => $idUsuario, ":t" => $tipo, ":ti" => $titulo, ":m" => $mensaje, ":e" => $enlace]);
        } catch (Throwable $e) {
            error_log("SiGeRU: no se pudo guardar un aviso: " . $e->getMessage());
            return;
        }
    }

    public static function avisarA(array $idsUsuario, string $tipo, string $titulo, string $mensaje, ?string $enlace = null): void
    {
        foreach (array_unique($idsUsuario) as $id) {
            self::avisar((int) $id, $tipo, $titulo, $mensaje, $enlace);
        }
    }
}
