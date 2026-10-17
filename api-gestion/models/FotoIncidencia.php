<?php

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../core/ErrorDeNegocio.php";

// La ficha en la base y el archivo en disco de la foto de una incidencia (RF-222). El archivo vive en
// almacen/incidencias/ (fuera de lo que sirve Apache) con un nombre al azar; nunca se usa un nombre
// que venga del navegador. SIGERU_ALMACEN permite ponerlo en otra carpeta (en Docker, una que Apache pueda escribir).
class FotoIncidencia
{
    public static function raiz(): string
    {
        $configurada = getenv("SIGERU_ALMACEN");
        return rtrim($configurada === false || $configurada === "" ? __DIR__ . "/../../almacen" : $configurada, "/\\");
    }

    public static function nombreValido(string $archivo): bool
    {
        return (bool) preg_match('/^[a-f0-9]{32}$/', $archivo);
    }

    public static function ruta(string $archivo): string
    {
        if (!self::nombreValido($archivo)) {
            throw new InvalidArgumentException("Nombre de archivo inválido");
        }
        return self::raiz() . "/incidencias/" . substr($archivo, 0, 2) . "/" . $archivo . ".jpg";
    }

    public static function de(int $idIncidencia): ?array
    {
        $consulta = Database::conexion()->prepare(
            "SELECT id_foto, id_incidencia, archivo, bytes, ancho, alto, fecha_hora
             FROM foto_incidencia WHERE id_incidencia = :id"
        );
        $consulta->execute([":id" => $idIncidencia]);
        $fila = $consulta->fetch();
        return $fila === false ? null : $fila;
    }

    public static function existe(int $idIncidencia): bool
    {
        $consulta = Database::conexion()->prepare("SELECT 1 FROM foto_incidencia WHERE id_incidencia = :id");
        $consulta->execute([":id" => $idIncidencia]);
        return $consulta->fetchColumn() !== false;
    }

    // Guarda el JPEG y su ficha. Si la ficha no se puede insertar, el archivo no queda huerfano.
    public static function guardar(int $idIncidencia, string $jpeg, int $ancho, int $alto): array
    {
        $archivo = bin2hex(random_bytes(16));
        $ruta = self::ruta($archivo);
        $carpeta = dirname($ruta);
        if (!is_dir($carpeta) && !mkdir($carpeta, 0775, true) && !is_dir($carpeta)) {
            throw new ErrorDeNegocio("No se pudo guardar la foto en el servidor.", 500);
        }
        $temporal = $ruta . ".tmp";
        if (file_put_contents($temporal, $jpeg, LOCK_EX) === false || !rename($temporal, $ruta)) {
            @unlink($temporal);
            throw new ErrorDeNegocio("No se pudo guardar la foto en el servidor.", 500);
        }

        try {
            $consulta = Database::conexion()->prepare(
                "INSERT INTO foto_incidencia (id_incidencia, archivo, bytes, ancho, alto)
                 VALUES (:incidencia, :archivo, :bytes, :ancho, :alto)"
            );
            $consulta->execute([
                ":incidencia" => $idIncidencia, ":archivo" => $archivo,
                ":bytes" => strlen($jpeg), ":ancho" => $ancho, ":alto" => $alto,
            ]);
        } catch (PDOException $e) {
            @unlink($ruta);
            if ((int) $e->getCode() === 23000) {
                throw new ErrorDeNegocio("Esta incidencia ya tiene una foto.", 409);
            }
            throw $e;
        }
        return self::de($idIncidencia);
    }

    public static function contenido(array $ficha): ?string
    {
        $ruta = self::ruta($ficha["archivo"]);
        $bytes = is_file($ruta) ? file_get_contents($ruta) : false;
        return $bytes === false ? null : $bytes;
    }
}
