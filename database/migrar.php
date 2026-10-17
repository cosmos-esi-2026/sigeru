<?php

if (PHP_SAPI !== "cli") {
    http_response_code(404);
    exit;
}

require_once __DIR__ . "/lib/Migraciones.php";

function variable(string $clave, string $porDefecto): string
{
    $valor = getenv($clave);
    return $valor === false || $valor === "" ? $porDefecto : $valor;
}

function conectar(): PDO
{
    $dsn = "mysql:host=" . variable("SIGERU_DB_HOST", "127.0.0.1") .
           ";port="      . variable("SIGERU_DB_PORT", "3306") .
           ";dbname="    . variable("SIGERU_DB_NAME", "sigeru") .
           ";charset=utf8mb4";

    try {
        return new PDO($dsn, variable("SIGERU_DB_USER", "root"), variable("SIGERU_DB_PASS", ""), [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        fwrite(STDERR, "No se pudo conectar con la base: " . $e->getMessage() . "\n");
        exit(2);
    }
}

function aplicadas(PDO $pdo): array
{
    $existe = $pdo->query("SHOW TABLES LIKE 'migracion'")->fetchColumn() !== false;
    if (!$existe) {
        return [];
    }
    return $pdo->query("SELECT nombre FROM migracion")->fetchAll(PDO::FETCH_COLUMN);
}

function archivosValidos(string $carpeta): array
{
    $archivos = Migraciones::archivos($carpeta);

    foreach ($archivos as $archivo) {
        if (!Migraciones::nombreValido($archivo)) {
            fwrite(STDERR, "Nombre de migracion invalido: $archivo (tiene que ser NNN-palabras-con-guiones.sql)\n");
            exit(1);
        }
    }
    $repetidos = Migraciones::numerosRepetidos($archivos);
    if ($repetidos !== []) {
        fwrite(STDERR, "Numeros de migracion repetidos: " . implode(", ", $repetidos) . "\n");
        exit(1);
    }
    return $archivos;
}

$carpeta = variable("SIGERU_MIGRACIONES", __DIR__ . "/migraciones");
$soloEstado = in_array("--estado", $argv, true);

$archivos = archivosValidos($carpeta);
$pdo = conectar();
$hechas = aplicadas($pdo);
$pendientes = array_values(array_diff($archivos, $hechas));

if ($soloEstado) {
    foreach ($archivos as $archivo) {
        echo (in_array($archivo, $hechas, true) ? "  aplicada   " : "  PENDIENTE  ") . $archivo . "\n";
    }
    $huerfanas = array_diff($hechas, $archivos);
    foreach ($huerfanas as $nombre) {
        echo "  sin archivo  $nombre\n";
    }
    echo count($pendientes) === 0
        ? "La base esta al dia (" . count($archivos) . " migraciones).\n"
        : count($pendientes) . " migracion(es) pendiente(s).\n";
    exit(count($pendientes) === 0 ? 0 : 1);
}

$pdo->exec("CREATE TABLE IF NOT EXISTS migracion (
                nombre      VARCHAR(120) NOT NULL PRIMARY KEY,
                aplicada_en DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

if ($pendientes === []) {
    echo "La base ya estaba al dia (" . count($archivos) . " migraciones).\n";
    exit(0);
}

foreach ($pendientes as $archivo) {
    echo "Aplicando $archivo ... ";
    $sentencias = Migraciones::dividirSentencias(file_get_contents($carpeta . "/" . $archivo));

    foreach ($sentencias as $numero => $sentencia) {
        try {
            $pdo->exec($sentencia);
        } catch (PDOException $e) {
            fwrite(STDERR, "FALLO\n  Archivo: $archivo, sentencia " . ($numero + 1) . "\n  " . $e->getMessage() .
                           "\n  " . substr($sentencia, 0, 200) .
                           "\nQuedo sin registrar: se puede corregir y volver a correr.\n");
            exit(1);
        }
    }

    $registro = $pdo->prepare("INSERT INTO migracion (nombre) VALUES (:nombre)");
    $registro->execute([":nombre" => $archivo]);
    echo "ok\n";
}

echo "Listo: " . count($pendientes) . " migracion(es) aplicada(s).\n";
exit(0);
