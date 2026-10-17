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

    public static function enviarEnlace(string $destino, string $nombre, string $token, bool $recuperacion = false): void
    {
        self::verificarConfiguracion();
        if (!filter_var($destino, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $destino)) {
            throw new CorreoException("Destino de correo inválido");
        }
        $from = self::modoArchivo() ? "sigeru@localhost" : self::config("SIGERU_MAIL_FROM");
        $base = rtrim(self::urlPublica(), "/");
        $enlace = $base . "/frontend-usuarios/crear-clave.html?token=" . rawurlencode($token);
        $asunto = $recuperacion ? "Recuperá tu contraseña de SiGeRU" : "Activá tu acceso a SiGeRU";
        $indicacion = $recuperacion
            ? "Recibimos un pedido para cambiar tu contraseña. Abrí este enlace:"
            : "Para crear o renovar tu contraseña de SiGeRU, abrí este enlace:";
        $cuerpo = "Hola " . $nombre . ",\r\n\r\n" .
            $indicacion . "\r\n" .
            $enlace . "\r\n\r\n" .
            "El enlace vence en una hora y solo puede usarse una vez.\r\n" .
            "Si no esperabas este mensaje, podés ignorarlo.\r\n";
        $mensaje = "From: " . $from . "\r\n" .
            "To: " . $destino . "\r\n" .
            "Subject: =?UTF-8?B?" . base64_encode($asunto) . "?=\r\n" .
            "MIME-Version: 1.0\r\n" .
            "Content-Type: text/plain; charset=UTF-8\r\n" .
            "Content-Transfer-Encoding: base64\r\n\r\n" .
            chunk_split(base64_encode($cuerpo), 76, "\r\n");
        if (self::modoArchivo()) {
            $carpeta = self::carpetaArchivo();
            if (!is_dir($carpeta) && !mkdir($carpeta, 0775, true) && !is_dir($carpeta)) {
                throw new CorreoException("No se pudo crear la carpeta de correo de prueba");
            }
            file_put_contents($carpeta . "/" . hash("sha256", strtolower($destino)) . ".txt", $asunto . "\n" . $cuerpo);
            return;
        }
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
