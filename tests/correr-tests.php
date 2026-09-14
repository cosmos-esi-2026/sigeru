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
             ["email" => $email, "password" => "123456"], $archivoCookies);
}


// ============================================================================
echo "SiGeRU - Tests de la API\n";
echo "========================\n";

$admin  = sys_get_temp_dir() . "/sigeru_test_admin.txt";
$vecino = sys_get_temp_dir() . "/sigeru_test_vecino.txt";
$sufijo = date("His");   // hace unicos los codigos de esta corrida


// --- 0. El servidor responde ------------------------------------------------
titulo("0. El entorno esta levantado");

[$codigo] = peticion("GET", "/api-gestion/");
afirmar("La API de gestion responde", 200, $codigo);

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
    ["codigo" => "TEST-$sufijo", "direccion" => "Otra"], $admin);
afirmar("Un codigo repetido devuelve 409", 409, $codigo);

[$codigo] = peticion("POST", "/api-gestion/camiones", ["matricula" => "SBA-1234"], $admin);
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

[$codigo] = peticion("POST", "/api-usuarios/usuarios",
    ["nombre" => "Prueba", "email" => "prueba.$sufijo@sigeru.uy", "password" => "corta",
     "rol" => "Vecino"], $admin);
afirmar("Una contrasena de menos de 8 caracteres: 400", 400, $codigo);

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
afirmar("Un vecino si puede consultar maquinaria", 200, $codigo);

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

[$codigo, $cuerpo] = peticion("PUT", "/api-usuarios/solicitudes/$idSolicitud",
    ["decision" => "aprobada", "rol" => "Vecino"], $admin);
afirmar("Aprobar la solicitud devuelve 200", 200, $codigo);
afirmar("Y crea el usuario", "test.$sufijo@sigeru.uy",
        $cuerpo["datos"]["usuario"]["email"] ?? null);
$idUsuarioCreado = $cuerpo["datos"]["usuario"]["id_usuario"] ?? null;

[$codigo] = peticion("PUT", "/api-usuarios/solicitudes/$idSolicitud",
    ["decision" => "aprobada"], $admin);
afirmar("Aprobarla de nuevo: 409", 409, $codigo);


// --- 8. Limpieza ------------------------------------------------------------
// Los tests se llevan puesto lo que crearon: si no, cada corrida deja basura en
// la base y la demo termina llena de registros "TEST-".
titulo("8. Limpieza");

require_once __DIR__ . "/../api-gestion/config/database.php";
$pdo = Database::conexion();

$pdo->prepare("DELETE FROM maquinaria WHERE id_maquinaria = ?")->execute([$idMaquinaria]);
$pdo->prepare("DELETE FROM estado_contenedor WHERE id_contenedor = ?")->execute([$idContenedor]);
$pdo->prepare("DELETE FROM contenedor WHERE id_contenedor = ?")->execute([$idContenedor]);
$pdo->prepare("DELETE FROM usuario WHERE id_usuario = ?")->execute([$idUsuarioCreado]);
$pdo->prepare("DELETE FROM solicitud_registro WHERE id_solicitud = ?")->execute([$idSolicitud]);

[$codigo] = peticion("GET", "/api-gestion/contenedores/$idContenedor", null, $admin);
afirmar("Los datos de prueba quedaron borrados", 404, $codigo);

[$codigo] = peticion("GET", "/api-gestion/maquinaria/$idMaquinaria", null, $admin);
afirmar("La maquinaria de prueba tambien", 404, $codigo);

foreach ([$admin, $vecino] as $archivo) {
    if (file_exists($archivo)) {
        unlink($archivo);
    }
}


// ============================================================================
echo "\n" . str_repeat("=", 50) . "\n";
echo "Pasaron: $pasaron   Fallaron: $fallaron\n";

if ($fallaron > 0) {
    echo "\nFallas:\n";
    foreach ($fallas as $f) {
        echo "  - $f\n";
    }
    exit(1);
}

echo "Todo verde.\n";
exit(0);
