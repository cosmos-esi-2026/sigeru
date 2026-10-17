<?php

// Valida y reconvierte la foto de una incidencia (RF-222, RF-223). No toca la base ni el disco publico:
// recibe la ruta de un archivo subido y devuelve los bytes de un JPEG nuevo, o el motivo del rechazo.
//
// Nunca se confia en el nombre ni en el tipo que declara el navegador: se mira el CONTENIDO. Y la imagen
// se vuelve a dibujar con GD, de modo que lo que se guarda es un JPEG limpio: sin los datos de ubicacion
// de la camara (EXIF/GPS), sin comentarios y sin nada escondido al final del archivo.
class ProcesadorFoto
{
    public const TAMANO_MAXIMO = 2097152;      // 2 MB
    public const LADO_MAXIMO_ENTRADA = 6000;   // pixeles; mas que eso es una bomba de descompresion
    public const PIXELES_MAXIMOS = 24000000;
    public const LADO_MAXIMO_SALIDA = 1600;
    public const CALIDAD_JPEG = 82;
    public const TIPOS = ["image/jpeg", "image/png", "image/webp"];

    public static function disponible(): bool
    {
        return extension_loaded("gd") && function_exists("imagejpeg") && function_exists("imagecreatefromjpeg");
    }

    private static function rechazo(string $motivo, int $estado = 400): array
    {
        return ["ok" => false, "error" => $motivo, "estado" => $estado, "jpeg" => null, "ancho" => 0, "alto" => 0];
    }

    // $tamano es el que informa PHP para el archivo subido (se vuelve a comprobar contra el disco).
    public static function procesar(string $ruta, int $tamano): array
    {
        if (!self::disponible()) {
            return self::rechazo("Este servidor no puede procesar fotos todavía.", 503);
        }
        if (!is_file($ruta)) {
            return self::rechazo("No llegó ninguna foto.");
        }
        $real = filesize($ruta);
        if ($tamano <= 0 || $real === false || $real <= 0) {
            return self::rechazo("La foto está vacía.");
        }
        if ($tamano > self::TAMANO_MAXIMO || $real > self::TAMANO_MAXIMO) {
            return self::rechazo("La foto pesa más de 2 MB.", 413);
        }

        // Se lee el tipo del contenido (los primeros bytes), no del nombre ni de lo que declare el navegador.
        $cabecera = file_get_contents($ruta, false, null, 0, 8192);
        $tipo = $cabecera === false ? false : (new finfo(FILEINFO_MIME_TYPE))->buffer($cabecera);
        if (!in_array($tipo, self::TIPOS, true)) {
            return self::rechazo("La foto tiene que ser JPG, PNG o WebP.");
        }
        $medidas = @getimagesize($ruta);
        if ($medidas === false || $medidas[0] < 1 || $medidas[1] < 1) {
            return self::rechazo("No se pudo leer la imagen: el archivo está dañado.");
        }
        [$ancho, $alto] = $medidas;
        if ($ancho > self::LADO_MAXIMO_ENTRADA || $alto > self::LADO_MAXIMO_ENTRADA || $ancho * $alto > self::PIXELES_MAXIMOS) {
            return self::rechazo("La foto es demasiado grande en píxeles (hasta " . self::LADO_MAXIMO_ENTRADA . " por " . self::LADO_MAXIMO_ENTRADA . ").");
        }

        $imagen = match ($tipo) {
            "image/jpeg" => @imagecreatefromjpeg($ruta),
            "image/png"  => @imagecreatefrompng($ruta),
            default      => function_exists("imagecreatefromwebp") ? @imagecreatefromwebp($ruta) : false,
        };
        if ($imagen === false) {
            return self::rechazo("No se pudo leer la imagen: el archivo está dañado o su formato no se admite.");
        }

        if ($tipo === "image/jpeg") {
            $imagen = self::enderezar($imagen, $ruta);
        }
        $ancho = imagesx($imagen);
        $alto = imagesy($imagen);

        $factor = min(1.0, self::LADO_MAXIMO_SALIDA / max($ancho, $alto));
        $nuevoAncho = max(1, (int) round($ancho * $factor));
        $nuevoAlto = max(1, (int) round($alto * $factor));

        $lienzo = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
        imagefill($lienzo, 0, 0, imagecolorallocate($lienzo, 255, 255, 255));   // la transparencia queda sobre blanco
        imagecopyresampled($lienzo, $imagen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);
        imagedestroy($imagen);

        ob_start();
        $escrito = imagejpeg($lienzo, null, self::CALIDAD_JPEG);
        $jpeg = (string) ob_get_clean();
        imagedestroy($lienzo);
        if (!$escrito || $jpeg === "") {
            return self::rechazo("No se pudo procesar la foto.", 500);
        }

        return ["ok" => true, "error" => null, "estado" => 200, "jpeg" => $jpeg, "ancho" => $nuevoAncho, "alto" => $nuevoAlto];
    }

    // Las fotos de celular traen la orientacion en el EXIF; al descartar el EXIF hay que aplicarla antes.
    private static function enderezar($imagen, string $ruta)
    {
        if (!function_exists("exif_read_data")) {
            return $imagen;
        }
        $exif = @exif_read_data($ruta);
        $giro = [3 => 180, 6 => -90, 8 => 90][$exif["Orientation"] ?? 1] ?? 0;
        if ($giro === 0) {
            return $imagen;
        }
        $girada = imagerotate($imagen, $giro, 0);
        if ($girada === false) {
            return $imagen;
        }
        imagedestroy($imagen);
        return $girada;
    }
}
