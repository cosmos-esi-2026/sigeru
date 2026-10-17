<?php

// Correo de enlaces (api-usuarios/core/Correo.php): la configuracion que exige y el
// modo "archivo", que solo existe para probar en localhost sin salir por SMTP.

require_once __DIR__ . "/../api-usuarios/core/Correo.php";

function configurarCorreo(array $variables): void
{
    foreach (["SIGERU_SMTP_URL", "SIGERU_SMTP_USER", "SIGERU_SMTP_PASS", "SIGERU_MAIL_FROM",
              "SIGERU_PUBLIC_URL", "SIGERU_MAIL_MODE"] as $nombre) {
        putenv($nombre);
    }
    foreach ($variables as $nombre => $valor) {
        putenv("$nombre=$valor");
    }
}

function configuracionAceptada(): bool
{
    try {
        Correo::verificarConfiguracion();
        return true;
    } catch (CorreoException $e) {
        return false;
    }
}

titulo("9. Correo: configuracion y modo archivo");

$smtp = [
    "SIGERU_SMTP_URL" => "smtps://smtp.example.com:465",
    "SIGERU_SMTP_USER" => "usuario",
    "SIGERU_SMTP_PASS" => "clave",
    "SIGERU_MAIL_FROM" => "sigeru@example.com",
    "SIGERU_PUBLIC_URL" => "https://sigeru.example.com",
];

configurarCorreo([]);
afirmar("Sin ninguna variable el correo no esta configurado", false, configuracionAceptada());

configurarCorreo($smtp);
afirmar("Con SMTP completo y URL publica HTTPS se acepta", true, configuracionAceptada());

configurarCorreo(array_merge($smtp, ["SIGERU_PUBLIC_URL" => "http://sigeru.example.com"]));
afirmar("Una URL publica HTTP que no es localhost se rechaza", false, configuracionAceptada());

configurarCorreo(array_merge($smtp, ["SIGERU_PUBLIC_URL" => "http://localhost/sigeru"]));
afirmar("HTTP en localhost se acepta", true, configuracionAceptada());

configurarCorreo(array_merge($smtp, ["SIGERU_MAIL_FROM" => "no-es-un-correo"]));
afirmar("Un remitente sin formato de correo se rechaza", false, configuracionAceptada());

configurarCorreo(["SIGERU_MAIL_MODE" => "archivo"]);
afirmar("El modo archivo no pide SMTP y sirve por omisión en localhost", true, configuracionAceptada());

configurarCorreo(["SIGERU_MAIL_MODE" => "archivo", "SIGERU_PUBLIC_URL" => "https://sigeru.example.com"]);
afirmar("El modo archivo se ignora fuera de localhost (pide SMTP)", false, configuracionAceptada());

configurarCorreo(["SIGERU_MAIL_MODE" => "otro"]);
afirmar("Un modo desconocido no abre nada", false, configuracionAceptada());

configurarCorreo(["SIGERU_MAIL_MODE" => "archivo"]);
$destinoPrueba = "unitaria." . bin2hex(random_bytes(4)) . "@sigeru.uy";
$ficheroPrueba = Correo::carpetaArchivo() . "/" . hash("sha256", $destinoPrueba) . ".txt";
$token = bin2hex(random_bytes(32));
Correo::enviarEnlace($destinoPrueba, "Ana", $token);
$contenido = is_file($ficheroPrueba) ? file_get_contents($ficheroPrueba) : "";
afirmar("El modo archivo deja el mensaje en tmp/correo con el nombre del hash del destino", true, $contenido !== "");
afirmar("El mensaje trae el enlace a crear-clave.html con el token", true,
    str_contains($contenido, "http://localhost/sigeru/frontend-usuarios/crear-clave.html?token=" . $token));
afirmar("Y avisa que vence en una hora y se usa una vez", true,
    str_contains($contenido, "vence en una hora") && str_contains($contenido, "solo puede usarse una vez"));
afirmar("El asunto de la activacion es el de acceso", true, str_starts_with($contenido, "Activ"));
Correo::enviarEnlace($destinoPrueba, "Ana", $token, true);
afirmar("El de recuperacion cambia el asunto", true, str_starts_with(file_get_contents($ficheroPrueba), "Recuper"));
@unlink($ficheroPrueba);

$rechazado = false;
try {
    Correo::enviarEnlace("a@sigeru.uy" . chr(13) . chr(10) . "Bcc: otro@sigeru.uy", "Ana", $token);
} catch (CorreoException $e) {
    $rechazado = true;
}
afirmar("Un destino con salto de linea (inyeccion de cabeceras) se rechaza", true, $rechazado);

configurarCorreo([]);
