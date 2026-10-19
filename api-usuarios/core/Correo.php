<?php

class CorreoException extends RuntimeException {}

class Correo
{
    private static function config(string $nombre): string
    {
        $valor = getenv($nombre);
        return $valor === false ? "" : (string) $valor;
    }

    private static function modoArchivo(): bool
    {
        if (self::config("SIGERU_MAIL_MODE") !== "archivo") {
            return false;
        }
        $host = parse_url(self::urlPublica(), PHP_URL_HOST);
        return in_array($host, ["localhost", "127.0.0.1"], true);
    }

    private static function urlPublica(): string
    {
        $url = trim(self::config("SIGERU_PUBLIC_URL"));
        return $url === "" ? "http://localhost/sigeru" : $url;
    }

    // "archivo" (pruebas en localhost), "smtp" (envio real) o "sin_configurar".
    public static function estado(): string
    {
        try {
            self::verificarConfiguracion();
        } catch (CorreoException $e) {
            return "sin_configurar";
        }
        return self::modoArchivo() ? "archivo" : "smtp";
    }

    public static function carpetaArchivo(): string
    {
        return __DIR__ . "/../../tmp/correo";
    }

    public static function verificarConfiguracion(): void
    {
        if (self::modoArchivo()) {
            return;
        }
        foreach (["SIGERU_SMTP_URL", "SIGERU_SMTP_USER", "SIGERU_SMTP_PASS",
                  "SIGERU_MAIL_FROM", "SIGERU_PUBLIC_URL"] as $nombre) {
            if (trim(self::config($nombre)) === "") {
                throw new CorreoException("Falta configurar " . $nombre);
            }
        }
        $url = self::config("SIGERU_SMTP_URL");
        $esquema = parse_url($url, PHP_URL_SCHEME);
        if (!in_array($esquema, ["smtp", "smtps"], true) || !filter_var(self::config("SIGERU_MAIL_FROM"), FILTER_VALIDATE_EMAIL)) {
            throw new CorreoException("Configuración SMTP inválida");
        }
        $base = self::config("SIGERU_PUBLIC_URL");
        $partes = parse_url($base);
        if (!is_array($partes) || !isset($partes["host"]) ||
            !in_array($partes["scheme"] ?? "", ["http", "https"], true) ||
            (($partes["scheme"] ?? "") !== "https" && !in_array($partes["host"], ["localhost", "127.0.0.1"], true))) {
            throw new CorreoException("SIGERU_PUBLIC_URL debe ser HTTPS, salvo localhost");
        }
        if (!function_exists("curl_init")) {
            throw new CorreoException("PHP necesita la extensión curl para enviar correo");
        }
    }

    // Enlace para crear o renovar la contraseña (alta por el administrador o recuperación).
    public static function enviarEnlace(string $destino, string $nombre, string $token, bool $recuperacion = false): void
    {
        $enlace = rtrim(self::urlPublica(), "/") . "/frontend-usuarios/crear-clave.html?token=" . rawurlencode($token);
        $asunto = $recuperacion ? "Recuperá tu contraseña de SiGeRU" : "Activá tu acceso a SiGeRU";
        $indicacion = $recuperacion
            ? "Recibimos un pedido para cambiar tu contraseña. Abrí este enlace:"
            : "Para crear o renovar tu contraseña de SiGeRU, abrí este enlace:";
        $texto = "Hola " . $nombre . ",\r\n\r\n" .
            $indicacion . "\r\n" .
            $enlace . "\r\n\r\n" .
            "El enlace vence en una hora y solo puede usarse una vez.\r\n" .
            "Si no esperabas este mensaje, podés ignorarlo.\r\n";
        $html = self::plantillaHtml(
            $asunto,
            "<p>Hola <strong>" . self::e($nombre) . "</strong>,</p><p>" . self::e($indicacion) . "</p>",
            $recuperacion ? "Crear contraseña nueva" : "Crear mi contraseña",
            $enlace,
            "El enlace vence en una hora y solo puede usarse una vez. Si no esperabas este mensaje, podés ignorarlo."
        );
        self::despachar($destino, $asunto, $texto, $html, "");
    }

    // Bienvenida al registrarse. Nunca lleva contraseña ni token: solo el acceso a la página.
    public static function enviarBienvenida(string $destino, string $nombre): void
    {
        $enlace = rtrim(self::urlPublica(), "/") . "/frontend-usuarios/login.html";
        $asunto = "¡Bienvenido/a a SiGeRU!";
        $texto = "Hola " . $nombre . ",\r\n\r\n" .
            "¡Gracias por registrarte en SiGeRU, el Sistema de Gestión de Residuos Urbanos!\r\n\r\n" .
            "Con tu cuenta podés:\r\n" .
            " - Informar un problema (un contenedor roto, lleno o con basura alrededor).\r\n" .
            " - Seguir el estado de tus reclamos en \"Mis reclamos\".\r\n" .
            " - Ver en el mapa dónde están los contenedores de tu barrio.\r\n\r\n" .
            "Iniciá sesión acá:\r\n" . $enlace . "\r\n\r\n" .
            "Si no fuiste vos quien creó la cuenta, respondé este correo y la damos de baja.\r\n\r\n" .
            "Equipo COSMOS - SiGeRU\r\n";
        $html = self::plantillaHtml(
            $asunto,
            "<p>Hola <strong>" . self::e($nombre) . "</strong>,</p>" .
            "<p>¡Gracias por registrarte en SiGeRU, el Sistema de Gestión de Residuos Urbanos!</p>" .
            "<p>Con tu cuenta podés:</p><ul>" .
            "<li>Informar un problema: un contenedor roto, lleno o con basura alrededor.</li>" .
            "<li>Seguir el estado de tus reclamos en «Mis reclamos».</li>" .
            "<li>Ver en el mapa dónde están los contenedores de tu barrio.</li></ul>",
            "Iniciar sesión",
            $enlace,
            "Si no fuiste vos quien creó la cuenta, respondé este correo y la damos de baja. — Equipo COSMOS"
        );
        self::despachar($destino, $asunto, $texto, $html, "-bienvenida");
    }

    private static function e(string $valor): string
    {
        return htmlspecialchars($valor, ENT_QUOTES, "UTF-8");
    }

    private static function plantillaHtml(string $titulo, string $cuerpo, string $boton, string $enlace, string $pie): string
    {
        return '<!doctype html><html lang="es"><body style="margin:0;background:#f3f2fa;font-family:Arial,Helvetica,sans-serif;color:#222">' .
            '<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:24px 12px">' .
            '<table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px;background:#fff;border-radius:10px;overflow:hidden">' .
            '<tr><td style="background:#4a3fb0;color:#fff;padding:18px 24px;font-size:20px;font-weight:bold">SiGeRU</td></tr>' .
            '<tr><td style="padding:24px;font-size:15px;line-height:1.5"><h1 style="font-size:19px;margin:0 0 14px">' . self::e($titulo) . '</h1>' .
            $cuerpo .
            '<p style="margin:24px 0"><a href="' . self::e($enlace) . '" style="background:#4a3fb0;color:#fff;text-decoration:none;padding:12px 22px;border-radius:6px;display:inline-block">' . self::e($boton) . '</a></p>' .
            '<p style="font-size:12px;color:#666;word-break:break-all">Si el botón no funciona, copiá este enlace en tu navegador:<br>' . self::e($enlace) . '</p>' .
            '<p style="font-size:12px;color:#666">' . self::e($pie) . '</p>' .
            '</td></tr></table></td></tr></table></body></html>';
    }

    private static function despachar(string $destino, string $asunto, string $texto, string $html, string $sufijoArchivo): void
    {
        self::verificarConfiguracion();
        if (!filter_var($destino, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $destino)) {
            throw new CorreoException("Destino de correo inválido");
        }
        $from = self::modoArchivo() ? "sigeru@localhost" : self::config("SIGERU_MAIL_FROM");
        if (self::modoArchivo()) {
            $carpeta = self::carpetaArchivo();
            if (!is_dir($carpeta) && !mkdir($carpeta, 0775, true) && !is_dir($carpeta)) {
                throw new CorreoException("No se pudo crear la carpeta de correo de prueba");
            }
            file_put_contents($carpeta . "/" . hash("sha256", strtolower($destino)) . $sufijoArchivo . ".txt", $asunto . "\n" . $texto);
            return;
        }
        $limite = "sigeru_" . bin2hex(random_bytes(12));
        $mensaje = "From: =?UTF-8?B?" . base64_encode("SiGeRU") . "?= <" . $from . ">\r\n" .
            "To: " . $destino . "\r\n" .
            "Subject: =?UTF-8?B?" . base64_encode($asunto) . "?=\r\n" .
            "MIME-Version: 1.0\r\n" .
            "Content-Type: multipart/alternative; boundary=\"" . $limite . "\"\r\n\r\n" .
            "--" . $limite . "\r\n" .
            "Content-Type: text/plain; charset=UTF-8\r\n" .
            "Content-Transfer-Encoding: base64\r\n\r\n" .
            chunk_split(base64_encode($texto), 76, "\r\n") .
            "--" . $limite . "\r\n" .
            "Content-Type: text/html; charset=UTF-8\r\n" .
            "Content-Transfer-Encoding: base64\r\n\r\n" .
            chunk_split(base64_encode($html), 76, "\r\n") .
            "--" . $limite . "--\r\n";
        $flujo = fopen("php://temp", "w+");
        fwrite($flujo, $mensaje);
        rewind($flujo);
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => self::config("SIGERU_SMTP_URL"),
            CURLOPT_USERNAME => self::config("SIGERU_SMTP_USER"),
            CURLOPT_PASSWORD => self::config("SIGERU_SMTP_PASS"),
            CURLOPT_MAIL_FROM => $from,
            CURLOPT_MAIL_RCPT => [$destino],
            CURLOPT_USE_SSL => CURLUSESSL_ALL,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_UPLOAD => true,
            CURLOPT_INFILE => $flujo,
            CURLOPT_INFILESIZE => strlen($mensaje),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 20,
        ]);
        $resultado = curl_exec($curl);
        $error = curl_error($curl);
        $estado = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);
        fclose($flujo);
        if ($resultado === false || $estado >= 400) {
            error_log("SiGeRU: falló el envío SMTP: " . $error . " (" . $estado . ")");
            throw new CorreoException("No se pudo enviar el correo");
        }
    }
}
