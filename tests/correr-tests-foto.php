<?php

// Foto de la incidencia por HTTP (P4b): quien puede subirla y verla, que se rechace lo que no es una foto, y que
// lo guardado sea un JPEG limpio fuera de la carpeta publica. Se ejecuta dentro de correr-tests.php.

titulo("22i. Foto de la incidencia");

require_once __DIR__ . "/../api-gestion/models/FotoIncidencia.php";
require_once __DIR__ . "/../api-gestion/models/ProcesadorFoto.php";

function subirFotoPorHttp(?int $id, ?string $bytes, ?string $cookies, string $campo = "foto", string $nombre = "foto.jpg"): array
{
    $temporal = tempnam(sys_get_temp_dir(), "sub");
    file_put_contents($temporal, $bytes ?? "");
    $ch = curl_init(BASE . "/api-gestion/incidencias/$id/foto");
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $bytes === null ? [] : [$campo => new CURLFile($temporal, "image/jpeg", $nombre)]]);
    if ($cookies !== null) {
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookies);
    }
    $cuerpo = curl_exec($ch);
    $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    unlink($temporal);
    return [$codigo, json_decode((string) $cuerpo, true)];
}

function verFotoPorHttp(int $id, ?string $cookies): array
{
    $ch = curl_init(BASE . "/api-gestion/incidencias/$id/foto");
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true]);
    if ($cookies !== null) {
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookies);
    }
    $respuesta = (string) curl_exec($ch);
    $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $tamano = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    $cabeceras = [];
    foreach (explode("\n", substr($respuesta, 0, $tamano)) as $linea) {
        if (str_contains($linea, ":")) {
            [$n, $v] = explode(":", $linea, 2);
            $cabeceras[strtolower(trim($n))] = trim($v);
        }
    }
    return [$codigo, $cabeceras, substr($respuesta, $tamano)];
}

function jpegDePrueba(int $ancho = 320, int $alto = 240, bool $conGps = false): string
{
    $im = imagecreatetruecolor($ancho, $alto);
    imagefill($im, 0, 0, imagecolorallocate($im, 40, 120, 80));
    imagefilledrectangle($im, 20, 20, $ancho - 20, $alto - 20, imagecolorallocate($im, 220, 220, 60));
    ob_start();
    imagejpeg($im, null, 90);
    $bytes = (string) ob_get_clean();
    imagedestroy($im);
    if ($conGps) {
        $exif = "Exif" . chr(0) . chr(0) . "GPSLatitude=-34.9011;Make=SecretPhone";
        $bytes = substr($bytes, 0, 2) . chr(0xFF) . chr(0xE1) . pack("n", strlen($exif) + 2) . $exif . substr($bytes, 2);
    }
    return $bytes;
}

if (!function_exists("imagejpeg")) {
    omitir("Toda la seccion 22i: el PHP de las pruebas no tiene GD para armar las imagenes");
    return;
}

$base = ["codigo_contenedor" => "TINC-$sufijo", "descripcion" => "Prueba de la foto de una incidencia"];
[$codigo, $cuerpo] = peticion("POST", "/api-gestion/incidencias", $base + ["tipo_problema" => "otro"], $vecino);
$idFoto = $cuerpo["datos"]["id_incidencia"] ?? 0;
if ($codigo === 409) {
    [$codigo, $cuerpo] = peticion("POST", "/api-gestion/incidencias", $base + ["tipo_problema" => "ausente_o_mal_ubicado"], $vecino);
    $idFoto = $cuerpo["datos"]["id_incidencia"] ?? 0;
}
afirmar("Se crea la incidencia de prueba de la foto", 201, $codigo);

[$codigo] = subirFotoPorHttp($idFoto, jpegDePrueba(), null);
afirmar("Sin sesion no se sube una foto (401)", 401, $codigo);
foreach (["Administrador" => $admin, "Operario" => $operario, "Cuadrilla" => $cuadrilla] as $rol => $sesion) {
    [$codigo] = subirFotoPorHttp($idFoto, jpegDePrueba(), $sesion);
    afirmar("Un $rol no sube fotos (403)", 403, $codigo);
}
[$codigo] = subirFotoPorHttp(99999, jpegDePrueba(), $vecino);
afirmar("Una incidencia que no existe (404)", 404, $codigo);

[$codigo] = subirFotoPorHttp($idFoto, null, $vecino);
afirmar("Sin ningun archivo (400)", 400, $codigo);
[$codigo] = subirFotoPorHttp($idFoto, jpegDePrueba(), $vecino, "otro_campo");
afirmar("En un campo que no es 'foto' (400)", 400, $codigo);
[$codigo, $cuerpo] = subirFotoPorHttp($idFoto, "esto es un texto, no una foto", $vecino, "foto", "foto.jpg");
afirmar("Un texto con nombre .jpg se rechaza por su contenido (400)", [400, true], [$codigo, str_contains((string) ($cuerpo["mensaje"] ?? ""), "JPG, PNG o WebP")]);
[$codigo] = subirFotoPorHttp($idFoto, str_repeat("a", ProcesadorFoto::TAMANO_MAXIMO + 10), $vecino);
afirmar("Mas de 2 MB (413)", 413, $codigo);
[$codigo] = subirFotoPorHttp($idFoto, "", $vecino);
afirmar("Un archivo vacio (400)", 400, $codigo);
[, $cuerpo] = peticion("GET", "/api-gestion/incidencias/$idFoto", null, $vecino);
afirmar("Nada de eso dejo una foto", false, $cuerpo["datos"]["tiene_foto"] ?? null);
[$codigo] = verFotoPorHttp($idFoto, $vecino);
afirmar("Sin foto, pedirla da 404", 404, $codigo);

[$codigo, $cuerpo] = subirFotoPorHttp($idFoto, jpegDePrueba(3000, 2000, true), $vecino);
if ($codigo === 503) {
    omitir("El resto de la seccion 22i: el Apache de esta instalacion no tiene GD (ver README)");
} else {
    afirmar("El vecino sube su foto (201), reducida a 1600 de lado", [201, 1600, 1067], [$codigo, $cuerpo["datos"]["ancho"] ?? null, $cuerpo["datos"]["alto"] ?? null]);
    [$codigo] = subirFotoPorHttp($idFoto, jpegDePrueba(), $vecino);
    afirmar("Una segunda foto en la misma incidencia (409)", 409, $codigo);

    [$codigo, $cabeceras, $imagen] = verFotoPorHttp($idFoto, $vecino);
    afirmar("El vecino ve su foto", [200, "image/jpeg", "nosniff"], [$codigo, $cabeceras["content-type"] ?? null, $cabeceras["x-content-type-options"] ?? null]);
    afirmar("Con una politica que no deja ejecutar nada", true, str_contains($cabeceras["content-security-policy"] ?? "", "sandbox"));
    afirmar("Y es un JPEG sin la ubicacion de la camara ni la marca del telefono", [true, false, false],
        [str_starts_with($imagen, chr(0xFF) . chr(0xD8)), str_contains($imagen, "GPSLatitude"), str_contains($imagen, "SecretPhone")]);
    [$codigo] = verFotoPorHttp($idFoto, $admin);
    afirmar("El administrador la ve", 200, $codigo);
    [$codigo] = verFotoPorHttp($idFoto, $cuadrilla);
    afirmar("Una cuadrilla a la que no esta asignada la incidencia no (404)", 404, $codigo);
    [$codigo] = verFotoPorHttp($idFoto, $operario);
    afirmar("Un operario tampoco (403)", 403, $codigo);
    [$codigo] = verFotoPorHttp($idFoto, null);
    afirmar("Sin sesion (401)", 401, $codigo);
    [, $cuerpo] = peticion("GET", "/api-gestion/incidencias/$idFoto", null, $admin);
    afirmar("El detalle avisa que tiene foto", true, $cuerpo["datos"]["tiene_foto"] ?? null);
    [, $cuerpo] = peticion("GET", "/api-gestion/incidencias/1", null, $admin);
    afirmar("Y el de una incidencia sin foto, que no", false, $cuerpo["datos"]["tiene_foto"] ?? null);

    $ficha = $pdo->prepare("SELECT archivo, bytes FROM foto_incidencia WHERE id_incidencia = ?");
    $ficha->execute([$idFoto]);
    $fila = $ficha->fetch(PDO::FETCH_ASSOC) ?: ["archivo" => "", "bytes" => 0];
    $rutaFoto = FotoIncidencia::nombreValido($fila["archivo"]) ? FotoIncidencia::ruta($fila["archivo"]) : "";
    afirmar("El archivo tiene nombre al azar de 32 hex y esta en el almacen, fuera de lo publico", [true, true, false],
        [FotoIncidencia::nombreValido($fila["archivo"]), is_file($rutaFoto), str_contains(str_replace("\\", "/", $rutaFoto), "/frontend-usuarios/")]);
    afirmar("Y la ficha tiene el peso del JPEG guardado", strlen($imagen), (int) $fila["bytes"]);
    [$codigo] = peticion("GET", "/almacen/incidencias/" . substr($fila["archivo"], 0, 2) . "/" . $fila["archivo"] . ".jpg");
    afirmar("El almacen no se sirve por la web (403)", 403, $codigo);
    if ($rutaFoto !== "" && is_file($rutaFoto)) {
        unlink($rutaFoto);
    }
}

[, $cuerpo] = peticion("POST", "/api-gestion/incidencias", $base + ["tipo_problema" => "roto"], $vecino);
$idCerrada = $cuerpo["datos"]["id_incidencia"] ?? 0;
[$codigo] = peticion("POST", "/api-gestion/incidencias/$idCerrada/descarte", ["observacion" => "Se cierra para probar"], $admin);
afirmar("Se descarta una incidencia de prueba", 200, $codigo);
[$codigo] = subirFotoPorHttp($idCerrada, jpegDePrueba(), $vecino);
afirmar("A una incidencia cerrada no se le agregan fotos (409)", 409, $codigo);

// Las fotos que hayan quedado de estas incidencias (por ejemplo si una prueba fallo a la mitad) se borran del disco;
// las fichas se van en cascada con las incidencias en la limpieza.
$restantes = $pdo->prepare("SELECT archivo FROM foto_incidencia WHERE id_incidencia IN (?, ?)");
$restantes->execute([$idFoto, $idCerrada]);
foreach ($restantes->fetchAll(PDO::FETCH_COLUMN) as $archivoSobrante) {
    if (FotoIncidencia::nombreValido($archivoSobrante) && is_file(FotoIncidencia::ruta($archivoSobrante))) {
        unlink(FotoIncidencia::ruta($archivoSobrante));
    }
}
