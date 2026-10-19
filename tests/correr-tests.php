<?php
// ============================================================================
//  SiGeRU 2026 - Tests de las APIs
//
//  Como se corre:
//      herramientas\php\php.exe sigeru\tests\correr-tests.php
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
    peticion("POST", "/api-usuarios/login",
             ["email" => $email, "password" => CLAVE_DE_PRUEBA], $archivoCookies);
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

// Las pruebas de enlaces por correo necesitan que Apache arranque con SIGERU_MAIL_MODE=archivo
// (el mensaje queda en tmp/correo). Con SMTP real o sin correo se omiten, y el resumen lo dice.
$omitidas = 0;
function omitir(string $descripcion): void
{
    global $omitidas;
    $omitidas++;
    echo "  - OMITIDA: $descripcion\n";
}
[, $raizUsuarios] = peticion("GET", "/api-usuarios/");
$correoDePrueba = ($raizUsuarios["correo"] ?? null) === "archivo";


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


// --- 1. Control de acceso ---------------------------------------------------
titulo("1. Control de acceso");

[$codigo] = peticion("GET", "/api-usuarios/usuarios");
afirmar("Sin sesion no se puede listar usuarios", 401, $codigo);

[$codigo] = peticion("POST", "/api-usuarios/usuarios",
    ["nombre" => "Intruso", "email" => "intruso@test.uy", "rol" => "Administrador"]);
afirmar("Sin sesion no se puede crear un administrador", 401, $codigo);

// El ataque que funcionaba antes de la 2da entrega: declararse admin por cabecera.
$ch = curl_init(BASE . "/api-usuarios/usuarios");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ["X-Usuario-Email: admin@sigeru.uy", "X-Usuario-Rol: Administrador"],
]);
curl_exec($ch);
$codigoFalsificado = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
afirmar("Falsificar la cabecera de rol ya no sirve", 401, $codigoFalsificado);

iniciarSesion("admin@sigeru.uy", $admin);
[$codigo] = peticion("GET", "/api-usuarios/usuarios", null, $admin);
afirmar("Con sesion de administrador si se puede listar", 200, $codigo);

iniciarSesion("vecino@sigeru.uy", $vecino);
[$codigo] = peticion("GET", "/api-usuarios/usuarios", null, $vecino);
afirmar("Un vecino no puede listar usuarios", 403, $codigo);

[$codigo] = peticion("POST", "/api-gestion/contenedores",
    ["codigo" => "X", "direccion" => "Y"], $vecino);
afirmar("Un vecino no puede crear contenedores", 403, $codigo);

[$codigo] = peticion("GET", "/api-gestion/contenedores", null, $vecino);
afirmar("Un vecino si puede consultar contenedores", 200, $codigo);

[$codigo] = peticion("GET", "/api-gestion/contenedores", null, $admin);
afirmar("La sesion sirve tambien en la otra API", 200, $codigo);


// --- 2. Alta valida ---------------------------------------------------------
titulo("2. Alta valida");

[$codigo, $cuerpo] = peticion("POST", "/api-gestion/contenedores", [
    "codigo"       => "TEST-$sufijo",
    "direccion"    => "Direccion de prueba 123",
    "capacidad"    => 900,
    "tipo_residuo" => "Vidrio",
    "id_zona"      => 1,
    "estado"       => "disponible",
], $admin);
afirmar("Crear un contenedor devuelve 201", 201, $codigo);
afirmar("El contenedor creado vuelve con su codigo", "TEST-$sufijo", $cuerpo["datos"]["codigo"] ?? null);
$idContenedor = $cuerpo["datos"]["id_contenedor"] ?? null;

// La persistencia real: el dato tiene que seguir estando en OTRA peticion.
[$codigo, $cuerpo] = peticion("GET", "/api-gestion/contenedores/$idContenedor", null, $admin);
afirmar("El contenedor persiste entre peticiones", 200, $codigo);
afirmar("Y conserva la capacidad guardada", 900, (int) ($cuerpo["datos"]["capacidad"] ?? 0));

// El alta deja su primera linea de historial sola.
[$codigo, $cuerpo] = peticion("GET", "/api-gestion/contenedores/$idContenedor/historial", null, $admin);
afirmar("El alta genera historial de estado", 1, count($cuerpo["datos"] ?? []));


// --- 3. Alta duplicada ------------------------------------------------------
titulo("3. Alta duplicada");

[$codigo] = peticion("POST", "/api-gestion/contenedores",
    ["codigo" => "TEST-$sufijo", "direccion" => "Otra", "id_zona" => 1, "tipo_residuo" => "Vidrio"], $admin);
afirmar("Un codigo repetido devuelve 409", 409, $codigo);

[$codigo] = peticion("POST", "/api-recoleccion/camiones", ["matricula" => "SBA-1234"], $admin);
afirmar("Una matricula repetida devuelve 409", 409, $codigo);

[$codigo] = peticion("POST", "/api-usuarios/usuarios",
    ["nombre" => "Ana", "email" => "admin@sigeru.uy"], $admin);
afirmar("Un email repetido devuelve 409", 409, $codigo);


// --- 4. Validaciones --------------------------------------------------------
titulo("4. Validaciones");

[$codigo] = peticion("POST", "/api-gestion/contenedores", ["codigo" => "SOLO-CODIGO"], $admin);
afirmar("Falta la direccion: 400", 400, $codigo);

[$codigo] = peticion("POST", "/api-gestion/contenedores",
    ["codigo" => "OTRO-$sufijo", "direccion" => "Calle", "estado" => "volando"], $admin);
afirmar("Un estado inexistente: 400", 400, $codigo);

[$codigo] = peticion("PUT", "/api-usuarios/usuarios/1", ["rol" => "Rey"], $admin);
afirmar("Un rol inexistente: 400", 400, $codigo);

// Ciberseguridad (2da entrega): el backend valida formato de email y politica
// de contrasenas, no solo que el campo no venga vacio.
[$codigo] = peticion("POST", "/api-usuarios/usuarios",
    ["nombre" => "Prueba", "email" => "no-es-un-email", "rol" => "Vecino"], $admin);
afirmar("Un email sin formato valido: 400", 400, $codigo);

// La contrasena ya no la fija el administrador: llega por enlace de correo. Su
// validacion (minimo 8 caracteres) se prueba en la seccion 7b, sobre /crear-clave.

[$codigo] = peticion("PUT", "/api-usuarios/usuarios/1", ["email" => "otro-invalido"], $admin);
afirmar("Modificar con un email invalido: 400", 400, $codigo);

[$codigo] = peticion("POST", "/api-usuarios/solicitudes",
    ["nombre" => "Prueba", "email" => "invalido-otra-vez"]);
afirmar("Una solicitud de registro con email invalido: 400", 400, $codigo);

[$codigo] = peticion("GET", "/api-gestion/contenedores/99999", null, $admin);
afirmar("Un id que no existe: 404", 404, $codigo);

[$codigo] = peticion("POST", "/api-usuarios/login",
    ["email" => "admin@sigeru.uy", "password" => "clave-incorrecta"]);
afirmar("Contrasena incorrecta: 401", 401, $codigo);

[$codigo] = peticion("DELETE", "/api-usuarios/usuarios/1", null, $admin);
afirmar("Un admin no puede darse de baja a si mismo: 409", 409, $codigo);


// --- 5. Baja logica ---------------------------------------------------------
titulo("5. Baja logica");

[$codigo, $cuerpo] = peticion("DELETE", "/api-gestion/contenedores/$idContenedor", null, $admin);
afirmar("La baja devuelve 200", 200, $codigo);
afirmar("El contenedor queda 'retirado', no borrado", "retirado", $cuerpo["datos"]["estado"] ?? null);

[$codigo] = peticion("GET", "/api-gestion/contenedores/$idContenedor", null, $admin);
afirmar("La fila sigue existiendo despues de la baja", 200, $codigo);

[$codigo] = peticion("DELETE", "/api-gestion/contenedores/$idContenedor", null, $admin);
afirmar("Dar de baja dos veces: 409", 409, $codigo);


// --- 6. Maquinaria de los centros de acopio ---------------------------------
// El modulo que cierra el "CRUD de centros de acopio y maquinaria basica" de la
// letra. Se prueba el circuito entero y las dos reglas propias del modulo: el
// centro es obligatorio (la FK es NOT NULL y no hay valor por omision) y el
// tipo tiene que ser uno del ENUM.
titulo("6. Maquinaria de los centros de acopio");

[$codigo] = peticion("GET", "/api-gestion/maquinaria", null, $vecino);
afirmar("Un vecino ya no puede consultar maquinaria (se cerro en la 3ra entrega)", 403, $codigo);

[$codigo] = peticion("POST", "/api-gestion/maquinaria",
    ["codigo" => "MAQ-X", "id_centro" => 1], $vecino);
afirmar("Un vecino no puede crear maquinaria", 403, $codigo);

[$codigo, $cuerpo] = peticion("POST", "/api-gestion/maquinaria", [
    "codigo"    => "MAQ-TEST-$sufijo",
    "tipo"      => "compactadora",
    "marca"     => "Bomag",
    "modelo"    => "BC 473",
    "id_centro" => 1,
    "estado"    => "operativa",
], $admin);
afirmar("Crear una maquinaria devuelve 201", 201, $codigo);
afirmar("Y vuelve con el NOMBRE de su centro, no solo el id",
        "Centro de Acopio Felipe Cardoso", $cuerpo["datos"]["centro"] ?? null);
$idMaquinaria = $cuerpo["datos"]["id_maquinaria"] ?? null;

[$codigo] = peticion("POST", "/api-gestion/maquinaria",
    ["codigo" => "MAQ-TEST-$sufijo", "id_centro" => 1], $admin);
afirmar("Un codigo de maquinaria repetido devuelve 409", 409, $codigo);

[$codigo] = peticion("POST", "/api-gestion/maquinaria",
    ["codigo" => "MAQ-SIN-CENTRO-$sufijo"], $admin);
afirmar("Una maquina sin centro de acopio: 400", 400, $codigo);

[$codigo] = peticion("POST", "/api-gestion/maquinaria",
    ["codigo" => "MAQ-TIPO-$sufijo", "tipo" => "nave espacial", "id_centro" => 1], $admin);
afirmar("Un tipo de maquinaria inexistente: 400", 400, $codigo);

[$codigo, $cuerpo] = peticion("PUT", "/api-gestion/maquinaria/$idMaquinaria",
    ["estado" => "mantenimiento"], $admin);
afirmar("Modificar la maquinaria devuelve 200", 200, $codigo);
afirmar("Y el cambio de estado quedo guardado",
        "mantenimiento", $cuerpo["datos"]["estado"] ?? null);

[$codigo, $cuerpo] = peticion("DELETE", "/api-gestion/maquinaria/$idMaquinaria", null, $admin);
afirmar("La baja de maquinaria devuelve 200", 200, $codigo);
afirmar("La maquina queda 'fuera_servicio', no borrada",
        "fuera_servicio", $cuerpo["datos"]["estado"] ?? null);

[$codigo] = peticion("DELETE", "/api-gestion/maquinaria/$idMaquinaria", null, $admin);
afirmar("Dar de baja la maquinaria dos veces: 409", 409, $codigo);


// --- 7. Solicitudes de registro (CU-011) ------------------------------------
titulo("7. Solicitudes de registro");

[$codigo, $cuerpo] = peticion("POST", "/api-usuarios/solicitudes", [
    "nombre"    => "Test",
    "apellido"  => "Automatico",
    "email"     => "test.$sufijo@sigeru.uy",
    "telefono"  => "099000000",
    "direccion" => "Calle de prueba",
]);
afirmar("El registro publico crea una solicitud (201)", 201, $codigo);
afirmar("Y queda PENDIENTE, no crea un usuario activo",
        "pendiente", $cuerpo["datos"]["estado"] ?? null);
$idSolicitud = $cuerpo["datos"]["id_solicitud"] ?? null;

$idUsuarioCreado = null;
if ($correoDePrueba) {
[$codigo, $cuerpo] = peticion("PUT", "/api-usuarios/solicitudes/$idSolicitud",
    ["decision" => "aprobada", "rol" => "Vecino"], $admin);
afirmar("Aprobar la solicitud devuelve 200", 200, $codigo);
afirmar("Y crea el usuario", "test.$sufijo@sigeru.uy",
        $cuerpo["datos"]["usuario"]["email"] ?? null);
afirmar("Que queda pendiente hasta que defina su contrasena", "pendiente",
        $cuerpo["datos"]["usuario"]["estado"] ?? null);
$idUsuarioCreado = $cuerpo["datos"]["usuario"]["id_usuario"] ?? null;

[$codigo] = peticion("PUT", "/api-usuarios/solicitudes/$idSolicitud",
    ["decision" => "aprobada"], $admin);
afirmar("Aprobarla de nuevo: 409", 409, $codigo);
} else {
    omitir("Aprobar una solicitud (envia un enlace por correo; arrancar Apache con SIGERU_MAIL_MODE=archivo)");
}


// --- 7b. Acceso por enlace de correo ----------------------------------------
// Desde el 17/10 las cuentas nuevas nacen 'pendiente' y el usuario define su
// contrasena con un enlace de un solo uso (1 hora). Estas pruebas necesitan que
// Apache arranque con SIGERU_MAIL_MODE=archivo: el correo se deja en
// sigeru/tmp/correo/ en lugar de salir por SMTP (solo vale en localhost).
titulo("7b. Acceso por enlace de correo");

function archivoCorreo(string $email): string
{
    return __DIR__ . "/../tmp/correo/" . hash("sha256", strtolower($email)) . ".txt";
}

function tokenDelCorreo(string $email): ?string
{
    $ruta = archivoCorreo($email);
    if (!is_file($ruta)) {
        return null;
    }
    return preg_match('/token=([a-f0-9]{64})/', file_get_contents($ruta), $m) ? $m[1] : null;
}

// La carpeta la escribe el usuario de Apache: se crea abierta para que la prueba
// (que puede correr con otro usuario, por ejemplo en Docker) tambien la lea y limpie.
$carpetaCorreo = __DIR__ . "/../tmp/correo";
if (!is_dir($carpetaCorreo)) {
    @mkdir($carpetaCorreo, 0777, true);
    @chmod($carpetaCorreo, 0777);
}

$correoEnlace = "enlace.$sufijo@sigeru.uy";
$correoInexistente = "nadie.$sufijo@sigeru.uy";
@unlink(archivoCorreo($correoEnlace));
$idUsuarioEnlace = null;
if (!$correoDePrueba) {
    omitir("Toda la seccion 7b: necesita que Apache arranque con SIGERU_MAIL_MODE=archivo (hoy el correo esta en modo: " . ($raizUsuarios["correo"] ?? "desconocido") . ")");
} else {

[$codigo, $cuerpo] = peticion("POST", "/api-usuarios/usuarios",
    ["nombre" => "Enlace", "email" => $correoEnlace, "rol" => "Vecino", "password" => "corta"], $admin);
afirmar("El administrador crea la cuenta y se envia el enlace (201)", 201, $codigo);
afirmar("La cuenta queda pendiente", "pendiente", $cuerpo["datos"]["estado"] ?? null);
$idUsuarioEnlace = $cuerpo["datos"]["id_usuario"] ?? null;
$token = tokenDelCorreo($correoEnlace);
afirmar("El correo trae un enlace con un token de 64 caracteres", true, $token !== null);

[$codigo] = peticion("POST", "/api-usuarios/login", ["email" => $correoEnlace, "password" => "corta"]);
afirmar("La contrasena que mando el administrador se ignora: no se puede entrar", 401, $codigo);

[$codigo] = peticion("POST", "/api-usuarios/crear-clave", ["token" => $token, "password" => "corta"]);
afirmar("Una contrasena de menos de 8 caracteres: 400", 400, $codigo);
[$codigo] = peticion("POST", "/api-usuarios/crear-clave", ["token" => str_repeat("a", 64), "password" => "ClaveNueva-123"]);
afirmar("Un token que no existe: 400", 400, $codigo);
[$codigo] = peticion("POST", "/api-usuarios/crear-clave", ["token" => "no-es-un-token", "password" => "ClaveNueva-123"]);
afirmar("Un token con formato invalido: 400", 400, $codigo);

$pdo->prepare("UPDATE enlace_clave SET vence_en = DATE_SUB(NOW(), INTERVAL 1 MINUTE) WHERE id_usuario = ?")
    ->execute([$idUsuarioEnlace]);
[$codigo] = peticion("POST", "/api-usuarios/crear-clave", ["token" => $token, "password" => "ClaveNueva-123"]);
afirmar("Un enlace vencido no sirve: 400", 400, $codigo);

[$codigo] = peticion("POST", "/api-usuarios/usuarios/$idUsuarioEnlace/enlace", [], $vecino);
afirmar("Un vecino no puede reenviar enlaces: 403", 403, $codigo);
[$codigo] = peticion("POST", "/api-usuarios/usuarios/99999/enlace", [], $admin);
afirmar("Reenviar a un usuario que no existe: 404", 404, $codigo);
[$codigo] = peticion("POST", "/api-usuarios/usuarios/$idUsuarioEnlace/enlace", [], $admin);
afirmar("El administrador reenvia el enlace (200)", 200, $codigo);
$token2 = tokenDelCorreo($correoEnlace);
afirmar("El enlace nuevo es distinto del anterior", true, $token2 !== null && $token2 !== $token);
[$codigo] = peticion("POST", "/api-usuarios/crear-clave", ["token" => $token, "password" => "ClaveNueva-123"]);
afirmar("Al emitir uno nuevo el anterior deja de servir: 400", 400, $codigo);

[$codigo] = peticion("POST", "/api-usuarios/crear-clave", ["token" => $token2, "password" => "ClaveNueva-123"]);
afirmar("Con el enlace vigente se crea la contrasena (200)", 200, $codigo);
[$codigo, $cuerpo] = peticion("POST", "/api-usuarios/login", ["email" => $correoEnlace, "password" => "ClaveNueva-123"]);
afirmar("Y ahora se puede entrar", 200, $codigo);
afirmar("La cuenta paso a activa", "activo", $cuerpo["datos"]["estado"] ?? null);
[$codigo] = peticion("POST", "/api-usuarios/crear-clave", ["token" => $token2, "password" => "OtraClave-456"]);
afirmar("Un enlace ya usado no vuelve a servir: 400", 400, $codigo);

[$codigo] = peticion("POST", "/api-usuarios/recuperar-clave", ["email" => "no-es-un-email"]);
afirmar("Recuperar con un email invalido: 400", 400, $codigo);

@unlink(archivoCorreo($correoEnlace));
[$codigo, $cuerpo] = peticion("POST", "/api-usuarios/recuperar-clave", ["email" => $correoEnlace]);
afirmar("Recuperar la clave de un vecino activo responde 200", 200, $codigo);
$mensajeRecuperar = $cuerpo["mensaje"] ?? null;
afirmar("Y se envia el correo", true, tokenDelCorreo($correoEnlace) !== null);

@unlink(archivoCorreo($correoEnlace));
[$codigo] = peticion("POST", "/api-usuarios/recuperar-clave", ["email" => $correoEnlace]);
afirmar("Pedirlo otra vez enseguida responde 200", 200, $codigo);
afirmar("Pero no manda otro correo (freno de 5 minutos)", false, is_file(archivoCorreo($correoEnlace)));

foreach ([$correoInexistente, "admin@sigeru.uy"] as $destino) {
    @unlink(archivoCorreo($destino));
    [$codigo, $cuerpo] = peticion("POST", "/api-usuarios/recuperar-clave", ["email" => $destino]);
    afirmar("Recuperar para $destino responde igual (no revela si existe)", [200, $mensajeRecuperar], [$codigo, $cuerpo["mensaje"] ?? null]);
    afirmar("Y no se manda ningun correo", false, is_file(archivoCorreo($destino)));
}
$pdo->prepare("DELETE FROM enlace_clave WHERE id_usuario = 1")->execute();

[$codigo] = peticion("DELETE", "/api-usuarios/usuarios/$idUsuarioEnlace", null, $admin);
afirmar("Se da de baja a la cuenta de prueba", 200, $codigo);
[$codigo] = peticion("POST", "/api-usuarios/usuarios/$idUsuarioEnlace/enlace", [], $admin);
afirmar("Una cuenta dada de baja no recibe enlaces: 409", 409, $codigo);
}


// --- 8. Matriz de permisos por rol ------------------------------------------
// Sale de la tabla 12.2 de UTU LAB y de los actores de los casos de uso. Cada
// recurso se prueba con los cuatro roles y sin sesion: 401 si no hay sesion, 403
// si el rol no alcanza, 200 si corresponde. La lectura publica se cerro en la
// parte 2 de la mejora de la 3ra entrega.
titulo("8. Matriz de permisos por rol");

iniciarSesion("operario@sigeru.uy", $operario);
iniciarSesion("cuadrilla@sigeru.uy", $cuadrilla);

$sesiones = [
    "Administrador" => $admin,
    "Operario"      => $operario,
    "Cuadrilla"     => $cuadrilla,
    "Vecino"        => $vecino,
    "Sin sesion"    => null,
];
$todos = ["Administrador", "Operario", "Cuadrilla", "Vecino"];

$lectura = [
    "/api-gestion/contenedores"             => $todos,
    "/api-gestion/contenedores/1/historial" => ["Administrador", "Cuadrilla"],
    "/api-recoleccion/camiones"             => ["Administrador"],
    "/api-recoleccion/rutas"                => ["Administrador"],
    "/api-recoleccion/recolecciones"        => ["Administrador", "Cuadrilla"],
    "/api-gestion/centros"                  => ["Administrador", "Operario"],
    "/api-gestion/maquinaria"               => ["Administrador", "Operario"],
    "/api-gestion/tipos-residuo"            => $todos,
    "/api-gestion/zonas"                    => $todos,
    "/api-recoleccion/cuadrillas"           => ["Administrador"],
    "/api-gestion/incidencias"              => ["Administrador", "Cuadrilla", "Vecino"],
    "/api-usuarios/usuarios"                => ["Administrador"],
    "/api-usuarios/solicitudes"             => ["Administrador"],
];
foreach ($lectura as $ruta => $rolesConAcceso) {
    $esperados = [];
    $obtenidos = [];
    foreach ($sesiones as $rol => $cookies) {
        $esperados[$rol] = $cookies === null ? 401 : (in_array($rol, $rolesConAcceso, true) ? 200 : 403);
        [$obtenidos[$rol]] = peticion("GET", $ruta, null, $cookies);
    }
    afirmar("GET $ruta", $esperados, $obtenidos);
}

$escritura = [
    "/api-gestion/contenedores", "/api-recoleccion/camiones", "/api-gestion/centros",
    "/api-gestion/maquinaria", "/api-gestion/tipos-residuo", "/api-gestion/zonas",
    "/api-usuarios/usuarios", "/api-recoleccion/rutas",
];
foreach ($escritura as $ruta) {
    foreach (["POST" => "", "PUT" => "/99999", "DELETE" => "/99999"] as $metodo => $final) {
        if ($ruta === "/api-gestion/centros" && $metodo === "PUT") {
            continue;
        }
        $obtenidos = [];
        foreach (["Operario", "Cuadrilla", "Vecino", "Sin sesion"] as $rol) {
            [$obtenidos[$rol]] = peticion($metodo, $ruta . $final, ["nombre" => "x"], $sesiones[$rol]);
        }
        afirmar(
            "$metodo $ruta$final solo lo puede hacer un administrador",
            ["Operario" => 403, "Cuadrilla" => 403, "Vecino" => 403, "Sin sesion" => 401],
            $obtenidos
        );
    }
}

[$codigo, $cuerpo] = peticion("GET", "/api-gestion/centros/1", null, $admin);
$centro = $cuerpo["datos"];
$capacidadOriginal = (int) $centro["capacidad"];
$estadoOriginal = $centro["estado"];
$estadoOpuesto = $estadoOriginal === "activo" ? "inactivo" : "activo";

[$codigo] = peticion("PUT", "/api-gestion/centros/1", ["capacidad" => $capacidadOriginal + 1], $operario);
afirmar("Un operario puede actualizar la capacidad de un centro", 200, $codigo);
[$codigo, $cuerpo] = peticion("GET", "/api-gestion/centros/1", null, $admin);
afirmar("Y el cambio quedo guardado", $capacidadOriginal + 1, (int) $cuerpo["datos"]["capacidad"]);
peticion("PUT", "/api-gestion/centros/1", ["capacidad" => $capacidadOriginal], $admin);

[$codigo] = peticion("PUT", "/api-gestion/centros/1", ["estado" => $estadoOriginal], $operario);
afirmar("Reenviar el mismo estado no es un cambio: 200", 200, $codigo);
[$codigo] = peticion("PUT", "/api-gestion/centros/1", ["estado" => $estadoOpuesto], $operario);
afirmar("Un operario no puede cambiar el estado de un centro", 403, $codigo);
[$codigo, $cuerpo] = peticion("GET", "/api-gestion/centros/1", null, $admin);
afirmar("Y el estado sigue como estaba", $estadoOriginal, $cuerpo["datos"]["estado"]);
[$codigo] = peticion("PUT", "/api-gestion/centros/99999", ["capacidad" => 1], $operario);
afirmar("El permiso del operario pasa y recien ahi falta el centro: 404", 404, $codigo);
[$codigo] = peticion("PUT", "/api-gestion/centros/1", ["capacidad" => 1], $cuadrilla);
afirmar("Una cuadrilla no puede actualizar centros", 403, $codigo);

[$codigo, $cuerpo] = peticion("GET", "/api-usuarios/sesion", null, $cuadrilla);
afirmar("La sesion de una cuadrilla trae su rol", "Cuadrilla", $cuerpo["datos"]["rol"] ?? null);
afirmar("Y la cuadrilla a la que pertenece", 1, $cuerpo["datos"]["id_cuadrilla"] ?? null);
[$codigo] = peticion("GET", "/api-usuarios/sesion");
afirmar("Sin sesion, /sesion responde 401", 401, $codigo);

// --- 9. Limite de intentos de login y estado de la cuenta ------------------
// 5 fallos en 15 minutos bloquean ese email (429 con Retry-After). El conteo se
// reinicia con un acceso correcto y no distingue mayusculas.
titulo("9. Limite de intentos de login y estado de la cuenta");

function cabeceras(string $metodo, string $ruta, ?array $datos = null): array
{
    $ch = curl_init(BASE . $ruta);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_CUSTOMREQUEST  => $metodo,
        CURLOPT_HTTPHEADER     => ["Content-Type: application/json"],
    ]);
    if ($datos !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datos));
    }
    $respuesta = curl_exec($ch);
    $tamano = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    $resultado = [];
    foreach (explode("\n", substr($respuesta, 0, $tamano)) as $linea) {
        if (str_contains($linea, ":")) {
            [$nombre, $valor] = explode(":", $linea, 2);
            $resultado[strtolower(trim($nombre))] = trim($valor);
        }
    }
    return $resultado;
}

function intentarLogin(string $email, string $clave): int
{
    [$codigo] = peticion("POST", "/api-usuarios/login", ["email" => $email, "password" => $clave]);
    return $codigo;
}

$emailInexistente = "bloqueo-$sufijo@test.uy";
$codigos = [];
for ($i = 0; $i < 5; $i++) {
    $codigos[] = intentarLogin($emailInexistente, "clave-equivocada");
}
afirmar("Los 5 primeros intentos fallidos dan 401", [401, 401, 401, 401, 401], $codigos);
afirmar("El sexto queda bloqueado: 429", 429, intentarLogin($emailInexistente, "clave-equivocada"));
afirmar("El bloqueo no distingue mayusculas", 429, intentarLogin(strtoupper($emailInexistente), "otra"));

$encabezados = cabeceras("POST", "/api-usuarios/login", ["email" => $emailInexistente, "password" => "x"]);
$espera = (int) ($encabezados["retry-after"] ?? 0);
afirmar("La respuesta dice cuanto esperar (Retry-After entre 1 y 900 s)", true, $espera >= 1 && $espera <= 900);
afirmar("Otro email no queda afectado", 401, intentarLogin("otro-$sufijo@test.uy", "mal"));

$emailViejo = "viejo-$sufijo@test.uy";
$viejo = $pdo->prepare("INSERT INTO intento_login (email, ip, fecha_hora, exitoso)
                        VALUES (?, '127.0.0.1', NOW() - INTERVAL 20 MINUTE, 0)");
for ($i = 0; $i < 5; $i++) {
    $viejo->execute([$emailViejo]);
}
afirmar("Cinco fallos de hace 20 minutos ya no cuentan", 401, intentarLogin($emailViejo, "mal"));

$emailTemporal = "temporal-$sufijo@test.uy";
$claveTemporal = "Clave-$sufijo-prueba";
$idRolVecino = (int) $pdo->query("SELECT id_rol FROM rol WHERE nombre = 'Vecino'")->fetchColumn();
$pdo->prepare("INSERT INTO usuario (nombre, apellido, email, password_hash, id_rol, estado)
               VALUES ('Temporal', 'Prueba', ?, ?, ?, 'activo')")
    ->execute([$emailTemporal, password_hash($claveTemporal, PASSWORD_DEFAULT), $idRolVecino]);
$idUsuarioTemporal = (int) $pdo->lastInsertId();

for ($i = 0; $i < 4; $i++) {
    intentarLogin($emailTemporal, "mal");
}
afirmar("Con 4 fallos todavia se puede entrar con la clave correcta", 200, intentarLogin($emailTemporal, $claveTemporal));
for ($i = 0; $i < 4; $i++) {
    intentarLogin($emailTemporal, "mal");
}
afirmar("Un acceso correcto reinicia la cuenta: otros 4 fallos no bloquean", 200, intentarLogin($emailTemporal, $claveTemporal));

$temporal = sys_get_temp_dir() . "/sigeru_test_temporal.txt";
if (file_exists($temporal)) {
    unlink($temporal);
}
peticion("POST", "/api-usuarios/login", ["email" => $emailTemporal, "password" => $claveTemporal], $temporal);
[$codigo] = peticion("GET", "/api-usuarios/sesion", null, $temporal);
afirmar("Con la cuenta activa, /sesion responde 200", 200, $codigo);

$pdo->prepare("UPDATE usuario SET estado = 'inactivo' WHERE id_usuario = ?")->execute([$idUsuarioTemporal]);
[$codigo] = peticion("GET", "/api-usuarios/sesion", null, $temporal);
afirmar("Dada de baja, la misma sesion ya no sirve: /sesion 403", 403, $codigo);
[$codigo] = peticion("GET", "/api-gestion/contenedores", null, $temporal);
afirmar("Ni para leer contenedores: 403", 403, $codigo);
afirmar("Y no puede volver a entrar, ni con la clave correcta", 401, intentarLogin($emailTemporal, $claveTemporal));


// --- 10. Cabeceras y archivos que no se sirven ------------------------------
titulo("10. Cabeceras y archivos que no se sirven");

$encabezados = cabeceras("GET", "/api-gestion/");
afirmar("Las respuestas de la API no se guardan en cache", "no-store", $encabezados["cache-control"] ?? null);
afirmar("Y llevan nosniff", "nosniff", $encabezados["x-content-type-options"] ?? null);

foreach (["/.git/HEAD", "/tests/correr-tests.php", "/docker/docker-compose.yml", "/README.md",
          "/database/schema.sql", "/ingenieria-software/", "/tmp/correo/", "/"] as $privado) {
    [$codigo] = peticion("GET", $privado);
    afirmar("No se sirve $privado", 403, $codigo);
}
foreach (["/landing/", "/frontend-usuarios/login.html", "/landing/fonts/space-grotesk-500.woff2",
          "/frontend-usuarios/css/base.css", "/frontend-usuarios/mapa.html", "/frontend-usuarios/reportes.html", "/frontend-usuarios/rutas.html", "/frontend-usuarios/js/rutas.js", "/frontend-usuarios/auditoria.html", "/frontend-usuarios/js/auditoria.js",
          "/frontend-usuarios/css/reportes.css", "/landing/vendor/leaflet/leaflet.js", "/landing/vendor/leaflet/leaflet.css",
          "/landing/js/publico.js", "/frontend-usuarios/js/graficos.js", "/frontend-usuarios/js/reportes.js",
          "/frontend-usuarios/vendor/leaflet/leaflet.js", "/frontend-usuarios/vendor/leaflet/leaflet.css"] as $publico) {
    [$codigo] = peticion("GET", $publico);
    afirmar("Si se sirve $publico", 200, $codigo);
}

require __DIR__ . "/correr-tests-incidencias.php";
require __DIR__ . "/correr-tests-recoleccion.php";
require __DIR__ . "/correr-tests-operario.php";
require __DIR__ . "/correr-tests-notificaciones.php";
require __DIR__ . "/correr-tests-participacion.php";
require __DIR__ . "/correr-tests-pasaje.php";
require __DIR__ . "/correr-tests-prediccion.php";
require __DIR__ . "/correr-tests-impacto.php";
require __DIR__ . "/correr-tests-estadisticas.php";
require __DIR__ . "/correr-tests-publico.php";
require __DIR__ . "/correr-tests-guia.php";
require __DIR__ . "/correr-tests-listas.php";
require __DIR__ . "/correr-tests-foto.php";
require __DIR__ . "/correr-tests-seguridad.php";


// --- 23. Limpieza ------------------------------------------------------------
// Los tests se llevan puesto lo que crearon: si no, cada corrida deja basura en
// la base y la demo termina llena de registros "TEST-". El historial no se borra
// a mano (la base lo impide): se va con su incidencia y su contenedor, en cascada.
titulo("23. Limpieza");

require_once __DIR__ . "/../api-gestion/config/database.php";
$pdo = Database::conexion();

$pdo->prepare("DELETE FROM maquinaria WHERE id_maquinaria = ?")->execute([$idMaquinaria]);
$pdo->prepare("DELETE FROM incidencia WHERE id_contenedor = ?")->execute([$idContInc]);
$pdo->prepare("DELETE FROM contenedor WHERE id_contenedor IN (?, ?)")->execute([$idContenedor, $idContInc]);
$pdo->prepare("DELETE FROM usuario WHERE id_usuario IN (?, ?)")->execute([$idUsuarioCreado, $idUsuarioEnlace]);
foreach (["test.$sufijo@sigeru.uy", $correoEnlace, $correoInexistente] as $destino) {
    @unlink(archivoCorreo($destino));
}
$pdo->prepare("DELETE FROM solicitud_registro WHERE id_solicitud = ?")->execute([$idSolicitud]);
$pdo->prepare("DELETE FROM usuario WHERE id_usuario = ?")->execute([$idUsuarioTemporal]);
$pdo->prepare("DELETE FROM auditoria WHERE fecha_hora >= ?")->execute([$inicioDeLaCorrida]);
$pdo->prepare("DELETE FROM notificacion WHERE fecha_hora >= ?")->execute([$inicioDeLaCorrida]);
$pdo->prepare("DELETE FROM limite_peticion WHERE fecha_hora >= ?")->execute([$inicioDeLaCorrida]);
$pdo->prepare("DELETE FROM intento_login WHERE fecha_hora >= ? OR email LIKE ?")
    ->execute([$inicioDeLaCorrida, "%-$sufijo@test.uy"]);

[$codigo] = peticion("GET", "/api-gestion/contenedores/$idContenedor", null, $admin);
afirmar("Los datos de prueba quedaron borrados", 404, $codigo);

[$codigo] = peticion("GET", "/api-gestion/maquinaria/$idMaquinaria", null, $admin);
afirmar("La maquinaria de prueba tambien", 404, $codigo);

$restos = (int) $pdo->query(
    "SELECT (SELECT COUNT(*) FROM historial_incidencia WHERE id_incidencia IN ($idInc, $idDesborde, $idRoto))
          + (SELECT COUNT(*) FROM estado_contenedor WHERE id_contenedor IN ($idContenedor, $idContInc))
          + (SELECT COUNT(*) FROM incidencia WHERE id_contenedor = $idContInc)"
)->fetchColumn();
afirmar("Las incidencias de prueba y todos sus historiales se fueron en cascada", 0, $restos);

foreach ([$admin, $vecino, $operario, $cuadrilla, $otraCuadrilla, $temporal] as $archivo) {
    if (file_exists($archivo)) {
        unlink($archivo);
    }
}


// ============================================================================
echo "\n" . str_repeat("=", 50) . "\n";
echo "Pasaron: $pasaron   Fallaron: $fallaron   Omitidas: $omitidas\n";
if ($omitidas > 0) {
    echo "AVISO: hay pruebas omitidas; para correrlas todas, arrancar Apache con SIGERU_MAIL_MODE=archivo."  . "\n";
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
