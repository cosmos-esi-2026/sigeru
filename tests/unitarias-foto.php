<?php

// Foto de una incidencia (parte P4b): validacion por contenido y reconversion a un JPEG limpio.

require_once __DIR__ . "/../api-gestion/models/ProcesadorFoto.php";

titulo("21. Foto de la incidencia: validacion y reconversion");

function archivoTemporalDe(string $bytes): string
{
    $ruta = tempnam(sys_get_temp_dir(), "foto");
    file_put_contents($ruta, $bytes);
    return $ruta;
}

function imagenDePrueba(string $formato, int $ancho = 120, int $alto = 90, bool $transparente = false): string
{
    $im = imagecreatetruecolor($ancho, $alto);
    if ($transparente) {
        imagesavealpha($im, true);
        imagefill($im, 0, 0, imagecolorallocatealpha($im, 0, 0, 0, 127));
    } else {
        imagefill($im, 0, 0, imagecolorallocate($im, 30, 140, 90));
    }
    imagefilledellipse($im, intdiv($ancho, 2), intdiv($alto, 2), intdiv($ancho, 2), intdiv($alto, 2), imagecolorallocate($im, 200, 40, 40));
    ob_start();
    match ($formato) { "png" => imagepng($im), "webp" => imagewebp($im), "gif" => imagegif($im), default => imagejpeg($im, null, 90) };
    $bytes = (string) ob_get_clean();
    imagedestroy($im);
    return $bytes;
}

function procesarBytes(string $bytes, ?int $tamanoInformado = null): array
{
    $ruta = archivoTemporalDe($bytes);
    $resultado = ProcesadorFoto::procesar($ruta, $tamanoInformado ?? strlen($bytes));
    unlink($ruta);
    return $resultado;
}

function tipoDeBytes(string $bytes): string
{
    return (string) (new finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
}

afirmar("El servidor de pruebas tiene GD", true, ProcesadorFoto::disponible());

foreach (["jpg" => "jpeg", "png" => "png", "webp" => "webp"] as $nombre => $formato) {
    $r = procesarBytes(imagenDePrueba($formato));
    afirmar("Una foto $nombre valida se acepta y sale como JPEG", [true, "image/jpeg", 120, 90], [$r["ok"], tipoDeBytes((string) $r["jpeg"]), $r["ancho"], $r["alto"]]);
}
$r = procesarBytes(imagenDePrueba("png", 120, 90, true));
afirmar("Un PNG transparente se aplana sobre blanco (la esquina sale clara)", true, (function () use ($r) {
    $im = imagecreatefromstring((string) $r["jpeg"]);
    $color = imagecolorat($im, 1, 1);
    return (($color >> 16) & 255) > 230 && (($color >> 8) & 255) > 230 && ($color & 255) > 230;
})());

$grande = procesarBytes(imagenDePrueba("jpeg", 3000, 2000));
afirmar("Una foto grande se reduce a 1600 de lado mayor, sin deformarla", [true, 1600, 1067], [$grande["ok"], $grande["ancho"], $grande["alto"]]);
afirmar("Y pesa mucho menos que el limite", true, strlen((string) $grande["jpeg"]) < ProcesadorFoto::TAMANO_MAXIMO);

foreach ([
    "un archivo de texto"               => ["esto no es una imagen", "JPG, PNG o WebP"],
    "un GIF"                            => [imagenDePrueba("gif"), "JPG, PNG o WebP"],
    "un SVG"                            => ['<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>', "JPG, PNG o WebP"],
    "un PHP disfrazado de JPEG"         => [chr(0xFF) . chr(0xD8) . chr(0xFF) . chr(0xE0) . '<?php echo "hola"; ?>', "dañado"],
    "un JPEG cortado a la mitad"        => [substr(imagenDePrueba("jpeg", 400, 300), 0, 300), "dañado"],
] as $descripcion => [$bytes, $parteDelMensaje]) {
    $r = procesarBytes($bytes);
    afirmar("Se rechaza $descripcion", [false, true], [$r["ok"], str_contains((string) $r["error"], $parteDelMensaje)]);
}
afirmar("Un archivo vacio", [false, 400], (function () { $r = procesarBytes("x", 0); return [$r["ok"], $r["estado"]]; })());
$r = procesarBytes(str_repeat("a", ProcesadorFoto::TAMANO_MAXIMO + 1));
afirmar("Mas de 2 MB se rechaza con 413, aunque no sea una imagen", [false, 413], [$r["ok"], $r["estado"]]);
$r = procesarBytes(imagenDePrueba("jpeg"), ProcesadorFoto::TAMANO_MAXIMO + 1);
afirmar("Si el tamano informado no cuadra, tambien", [false, 413], [$r["ok"], $r["estado"]]);

$ancha = imagecreatetruecolor(7000, 8);
ob_start();
imagepng($ancha);
$bytesAncha = (string) ob_get_clean();
imagedestroy($ancha);
$r = procesarBytes($bytesAncha);
afirmar("Una imagen de mas de 6000 px de lado se rechaza antes de decodificarla", [false, true], [$r["ok"], str_contains((string) $r["error"], "demasiado grande")]);
$bomba = imagecreate(6000, 5000);
imagecolorallocate($bomba, 0, 0, 0);
ob_start();
imagepng($bomba, null, 9);
$bytesBomba = (string) ob_get_clean();
imagedestroy($bomba);
$r = procesarBytes($bytesBomba);
afirmar("Una 'bomba' de 30 millones de pixeles que pesa casi nada se rechaza", [true, false], [strlen($bytesBomba) < 200000, $r["ok"]]);

$limpio = imagenDePrueba("jpeg", 200, 150);
$exif = "Exif" . chr(0) . chr(0) . "GPSLatitude=-34.9011;GPSLongitude=-56.1645;Make=SecretPhone";
$segmento = chr(0xFF) . chr(0xE1) . pack("n", strlen($exif) + 2) . $exif;
$conGps = substr($limpio, 0, 2) . $segmento . substr($limpio, 2);
afirmar("El JPEG de prueba trae los datos de ubicacion de la camara", true, str_contains($conGps, "GPSLatitude"));
$r = procesarBytes($conGps);
afirmar("La foto guardada no los conserva (ni EXIF, ni GPS, ni la marca del telefono)", [true, false, false, false], [
    $r["ok"], str_contains((string) $r["jpeg"], "GPSLatitude"), str_contains((string) $r["jpeg"], "Exif"), str_contains((string) $r["jpeg"], "SecretPhone"),
]);
$conCola = $limpio . '<?php echo "pwned"; ?>' . str_repeat("A", 100);
$r = procesarBytes($conCola);
afirmar("Lo que viene pegado despues del final del JPEG desaparece", [true, false], [$r["ok"], str_contains((string) $r["jpeg"], "<?php")]);
afirmar("El resultado siempre es un JPEG real y legible", true, imagecreatefromstring((string) $r["jpeg"]) !== false);
afirmar("El almacen usa nombres de 32 hex y rechaza cualquier otro", [true, false, false, false], (function () {
    require_once __DIR__ . "/../api-gestion/models/FotoIncidencia.php";
    return [FotoIncidencia::nombreValido(str_repeat("a1", 16)), FotoIncidencia::nombreValido("../../etc/passwd"),
            FotoIncidencia::nombreValido(str_repeat("A1", 16)), FotoIncidencia::nombreValido(str_repeat("a", 31))];
})());
