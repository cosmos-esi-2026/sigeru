<?php
// ============================================================================
//  SiGeRU 2026 - Tests de las APIs
//
//  Como se corre:
//      ..\herramientas\php\php.exe sigeru\tests\correr-tests.php
//  o directamente:
//      sigeru\tests\correr-tests.cmd
//
//  POR QUE UN RUNNER PROPIO Y NO PHPUNIT:
//  PHPUnit se instala con Composer, que necesita internet y deja una carpeta
//  vendor/ de miles de archivos. El proyecto corre desde un pendrive exFAT con
//  herramientas portables y la regla de no sumar dependencias. Estas 40 lineas
//  hacen lo que necesitamos: correr afirmaciones y contar cuantas pasan.
//
//  QUE PRUEBAN: la API completa por HTTP, no las clases por separado. Es lo que
//  importa acá: que el contrato que consume el frontend funcione de verdad,
//  incluido el control de acceso, que es donde estaban los agujeros.
//
//  REQUIERE: Apache y MySQL corriendo (iniciar-xampp.cmd) y los datos de prueba
//  importados. Los registros que crea los borra al terminar.
// ============================================================================

const BASE = "http://localhost/sigeru";
const CEDULAS_DEL_SEED = ["cuadrilla@sigeru.uy" => "41000019", "carla.nunez@sigeru.uy" => "41000025"];
const CLAVE_DE_PRUEBA = "sigeru2026";   // la de todos los usuarios del seed (8 o mas caracteres, como pide la politica)

$pasaron = 0;
$fallaron = 0;
$fallas = [];

function afirmar(string $descripcion, $esperado, $obtenido): void
{
    global $pasaron, $fallaron, $fallas;

    if ($esperado === $obtenido) {
        $pasaron++;
        echo "  \xE2\x9C\x93 $descripcion\n";
        return;
    }

    $fallaron++;
    $detalle = "$descripcion\n      esperado: " . var_export($esperado, true) .
               "\n      obtenido: " . var_export($obtenido, true);
    $fallas[] = $detalle;
    echo "  \xC3\x97 $detalle\n";
}

function titulo(string $texto): void
{
    echo "\n" . $texto . "\n" . str_repeat("-", strlen($texto)) . "\n";
}

/**
 * Hace una peticion HTTP y devuelve [codigo, cuerpo decodificado].
 * Las cookies se guardan en un archivo por sesion, que es como se mantiene
 * la sesion del servidor entre peticiones.
 */
function peticion(string $metodo, string $ruta, ?array $datos = null, ?string $cookies = null): array
{
    $ch = curl_init(BASE . $ruta);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $metodo,
        CURLOPT_HTTPHEADER     => ["Content-Type: application/json"],
    ]);

    if ($datos !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datos));
    }
    if ($cookies !== null) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookies);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookies);
    }

    $cuerpo = curl_exec($ch);
    $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [$codigo, json_decode($cuerpo, true)];
}

function iniciarSesion(string $email, string $archivoCookies): void
{
    if (file_exists($archivoCookies)) {
        unlink($archivoCookies);
    }
    // Las cuentas de cuadrilla entran solo con cedula (las del seed llevan las de CEDULAS_DEL_SEED).
    $credencial = isset(CEDULAS_DEL_SEED[$email]) ? ["cedula" => CEDULAS_DEL_SEED[$email]] : ["email" => $email];
    peticion("POST", "/api-usuarios/login",
             $credencial + ["password" => CLAVE_DE_PRUEBA], $archivoCookies);
}


// ============================================================================
echo "SiGeRU - Tests de la API\n";
echo "========================\n";

$admin  = sys_get_temp_dir() . "/sigeru_test_admin.txt";
$vecino = sys_get_temp_dir() . "/sigeru_test_vecino.txt";
$sufijo = date("His");   // hace unicos los codigos de esta corrida
$operario  = sys_get_temp_dir() . "/sigeru_test_operario.txt";
$cuadrilla = sys_get_temp_dir() . "/sigeru_test_cuadrilla.txt";
$temporal  = sys_get_temp_dir() . "/sigeru_test_temporal.txt";

require_once __DIR__ . "/../api-gestion/config/database.php";
$pdo = Database::conexion();
$inicioDeLaCorrida = $pdo->query("SELECT NOW()")->fetchColumn();

// Desde el plan de rutas para la defensa solo se vacian contenedores de la ruta de la recoleccion: las pruebas que cargan vaciados
// ponen sus contenedores de prueba (por codigo) en la ruta, con coordenadas, directo en la base.
function ponerEnRuta(PDO $pdo, int $idRuta, array $codigos): void
{
    $orden = (int) $pdo->query("SELECT COALESCE(MAX(orden), 0) FROM ruta_contenedor WHERE id_ruta = " . $idRuta)->fetchColumn();
    $buscar = $pdo->prepare("SELECT id_contenedor FROM contenedor WHERE codigo = ?");
    $poner = $pdo->prepare("INSERT INTO ruta_contenedor (id_ruta, id_contenedor, orden) VALUES (?, ?, ?)");
    $coord = $pdo->prepare("UPDATE contenedor SET latitud = COALESCE(latitud, -34.9), longitud = COALESCE(longitud, -56.2) WHERE id_contenedor = ?");
    foreach ($codigos as $codigo) {
        $buscar->execute([$codigo]);
        $id = (int) $buscar->fetchColumn();
        if ($id > 0) {
            $coord->execute([$id]);
            $poner->execute([$idRuta, $id, ++$orden]);
        }
    }
}

// Las pruebas que dependen del entorno se omiten y el resumen lo dice.
$omitidas = 0;
function omitir(string $descripcion): void
{
    global $omitidas;
    $omitidas++;
    echo "  - OMITIDA: $descripcion\n";
}


// --- 0. El servidor responde ------------------------------------------------
titulo("0. El entorno esta levantado");

[$codigo] = peticion("GET", "/api-gestion/");
afirmar("La API de gestion responde", 200, $codigo);

[$codigo] = peticion("GET", "/api-recoleccion/");
afirmar("La API de recoleccion responde", 200, $codigo);

[$codigo] = peticion("GET", "/api-usuarios/");
afirmar("La API de usuarios responde", 200, $codigo);

if ($codigo !== 200) {
    echo "\nEl servidor no responde. Levanta XAMPP con iniciar-xampp.cmd.\n";
    exit(1);
}


iniciarSesion("admin@sigeru.uy", $admin);
iniciarSesion("vecino@sigeru.uy", $vecino);
iniciarSesion("operario@sigeru.uy", $operario);
iniciarSesion("cuadrilla@sigeru.uy", $cuadrilla);
$otraCuadrilla = sys_get_temp_dir() . "/sigeru_test_otra_cuadrilla.txt";
iniciarSesion("carla.nunez@sigeru.uy", $otraCuadrilla);

foreach (array_slice($argv, 1) as $parte) {
    require __DIR__ . "/" . $parte;
}

$pdo = Database::conexion();
$pdo->prepare("DELETE FROM auditoria WHERE fecha_hora >= ?")->execute([$inicioDeLaCorrida]);
$pdo->prepare("DELETE FROM notificacion WHERE fecha_hora >= ?")->execute([$inicioDeLaCorrida]);
$pdo->prepare("DELETE FROM limite_peticion WHERE fecha_hora >= ?")->execute([$inicioDeLaCorrida]);

// ============================================================================
echo "\n" . str_repeat("=", 50) . "\n";
echo "Pasaron: $pasaron   Fallaron: $fallaron   Omitidas: $omitidas\n";
if ($omitidas > 0) {
    echo "AVISO: hay pruebas omitidas por el entorno; ver las lineas OMITIDA de arriba."  . "\n";
}

if ($fallaron > 0) {
    echo "\nFallas:\n";
    foreach ($fallas as $f) {
        echo "  - $f\n";
    }
    exit(1);
}

echo ($omitidas > 0 ? "Todo verde, salvo lo omitido." : "Todo verde.") . "\n";
exit(0);
