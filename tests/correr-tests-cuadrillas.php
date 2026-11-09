<?php

// Parte de correr-tests.php (se incluye desde alli y usa sus funciones y sus sesiones).
// Plan de rutas para la defensa, bloque 8: pantalla/API de cuadrillas, asignar la cuadrilla a un usuario Cuadrilla y los pedidos
// de acceso de cuadrillas desde el login. Todo lo que crea lleva TCUA y se borra al final.

titulo("47. Cuadrillas: alta, edicion, baja y matriz de permisos");

$limpiarCua = function () use ($pdo): void {
    $pdo->exec("DELETE FROM recoleccion WHERE id_camion IN (SELECT id_camion FROM camion WHERE matricula LIKE 'TCUA-%')");
    $pdo->exec("DELETE FROM camion WHERE matricula LIKE 'TCUA-%'");
    $pdo->exec("DELETE FROM ruta WHERE nombre LIKE 'TCUA %'");
    $pdo->exec("DELETE FROM notificacion WHERE id_usuario IN (SELECT id_usuario FROM usuario WHERE email LIKE 'tcua-%@test.uy')");
    $pdo->exec("DELETE FROM usuario WHERE email LIKE 'tcua-%@test.uy'");
    $pdo->exec("DELETE FROM solicitud_registro WHERE email LIKE 'tcua-%@test.uy'");
    $pdo->exec("DELETE FROM cuadrilla WHERE nombre LIKE 'TCUA %'");
    $pdo->exec("DELETE FROM limite_peticion WHERE clave LIKE 'solicitud:%' OR clave LIKE 'registro:%'");
};
$limpiarCua();

// Cedulas de prueba: validas (con su digito verificador) y distintas en cada corrida; el sistema las guarda solo con digitos.
require_once __DIR__ . "/../api-usuarios/core/Validacion.php";
$cedulaDePrueba = function (int $n): string {
    static $base = null;
    $base ??= mt_rand(9000000, 9900000);
    for ($dv = 0; $dv < 10; $dv++) {
        $cedula = ($base + $n) . $dv;
        if (Validacion::cedula($cedula)) {
            return $cedula;
        }
    }
};
$formatoCedula = fn(string $c): string => preg_replace('/\B(?=(\d{3})+(?!\d))/', ".", substr($c, 0, -1)) . "-" . substr($c, -1);
[$ced1, $ced2, $ced3, $ced4] = [$cedulaDePrueba(1), $cedulaDePrueba(2), $cedulaDePrueba(3), $cedulaDePrueba(4)];

$esperados = [];
$obtenidos = [];
foreach ($sesiones as $rol => $cookies) {
    $esperados[$rol] = $cookies === null ? 401 : ($rol === "Administrador" ? 404 : 403);
    [$obtenidos[$rol]] = peticion("PUT", "/api-recoleccion/cuadrillas/99999", ["turno" => "nocturno"], $cookies);
}
afirmar("PUT /cuadrillas es solo del administrador (a los demas 401/403; al administrador le falta la cuadrilla: 404)", $esperados, $obtenidos);
$esperados = [];
$obtenidos = [];
foreach ($sesiones as $rol => $cookies) {
    $esperados[$rol] = $cookies === null ? 401 : ($rol === "Administrador" ? 400 : 403);
    [$obtenidos[$rol]] = peticion("POST", "/api-recoleccion/cuadrillas", [], $cookies);
}
afirmar("POST /cuadrillas es solo del administrador (con un cuerpo vacio llega a la validacion: 400)", $esperados, $obtenidos);
$esperados = [];
$obtenidos = [];
foreach ($sesiones as $rol => $cookies) {
    $esperados[$rol] = $cookies === null ? 401 : ($rol === "Administrador" ? 404 : 403);
    [$obtenidos[$rol]] = peticion("DELETE", "/api-recoleccion/cuadrillas/99999", null, $cookies);
}
afirmar("DELETE /cuadrillas es solo del administrador", $esperados, $obtenidos);

foreach ([
    "Sin nombre" => [],
    "Un nombre de una letra" => ["nombre" => "A"],
    "Un nombre de mas de 80 letras" => ["nombre" => str_repeat("x", 81)],
    "Un turno inventado" => ["nombre" => "TCUA turno", "turno" => "madrugada"],
] as $caso => $datos) {
    [$codigo] = peticion("POST", "/api-recoleccion/cuadrillas", $datos, $admin);
    afirmar("Crear con datos malos ($caso): 400", 400, $codigo);
}

[$codigo, $cuerpo] = peticion("POST", "/api-recoleccion/cuadrillas", ["nombre" => "TCUA Uno $sufijo", "turno" => "vespertino"], $admin);
$idCua1 = (int) ($cuerpo["datos"]["id_cuadrilla"] ?? 0);
afirmar("El administrador crea una cuadrilla: 201, activa, con su turno y sin integrantes", [201, "activa", "vespertino", 0],
    [$codigo, $cuerpo["datos"]["estado"] ?? null, $cuerpo["datos"]["turno"] ?? null, $cuerpo["datos"]["integrantes"] ?? null]);
[$codigo] = peticion("POST", "/api-recoleccion/cuadrillas", ["nombre" => "TCUA Uno $sufijo"], $admin);
afirmar("Un nombre repetido: 409", 409, $codigo);
[$codigo, $cuerpo] = peticion("POST", "/api-recoleccion/cuadrillas", ["nombre" => "TCUA Dos $sufijo"], $admin);
$idCua2 = (int) ($cuerpo["datos"]["id_cuadrilla"] ?? 0);
afirmar("Sin turno entra como matutino", "matutino", $cuerpo["datos"]["turno"] ?? null);
[$codigo, $cuerpo] = peticion("PUT", "/api-recoleccion/cuadrillas/$idCua2", ["nombre" => "TCUA Dos renombrada $sufijo", "turno" => "nocturno"], $admin);
afirmar("Se renombra y cambia de turno", [200, "TCUA Dos renombrada $sufijo", "nocturno"], [$codigo, $cuerpo["datos"]["nombre"] ?? null, $cuerpo["datos"]["turno"] ?? null]);
[$codigo] = peticion("PUT", "/api-recoleccion/cuadrillas/$idCua2", ["nombre" => "TCUA Uno $sufijo"], $admin);
afirmar("Renombrarla con el nombre de otra: 409", 409, $codigo);

// Municipio de la cuadrilla (parte 26, migracion 020)
afirmar("Una cuadrilla creada sin municipio lo devuelve en null (puede cubrir cualquiera)", [null, null], [array_key_exists("id_zona", $cuerpo["datos"] ?? []) ? $cuerpo["datos"]["id_zona"] : "falta", array_key_exists("zona", $cuerpo["datos"] ?? []) ? $cuerpo["datos"]["zona"] : "falta"]);
[$codigo, $cuerpo] = peticion("POST", "/api-recoleccion/cuadrillas", ["nombre" => "TCUA Zona $sufijo", "id_zona" => 2], $admin);
$idCuaZ = (int) ($cuerpo["datos"]["id_cuadrilla"] ?? 0);
afirmar("Crear con municipio: 201 y devuelve su id y su nombre", [201, 2, true], [$codigo, $cuerpo["datos"]["id_zona"] ?? null, is_string($cuerpo["datos"]["zona"] ?? null) && $cuerpo["datos"]["zona"] !== ""]);
foreach (["Un municipio que no existe" => 99999, "Un municipio con texto" => "abc", "Un municipio en cero" => 0, "Un municipio negativo" => -1] as $caso => $malo) {
    [$codigo] = peticion("POST", "/api-recoleccion/cuadrillas", ["nombre" => "TCUA Zona mala $sufijo", "id_zona" => $malo], $admin);
    afirmar("Crear con datos malos ($caso): 400", 400, $codigo);
}
[$codigo, $cuerpo] = peticion("PUT", "/api-recoleccion/cuadrillas/$idCuaZ", ["id_zona" => 3], $admin);
afirmar("Se le cambia el municipio", [200, 3], [$codigo, $cuerpo["datos"]["id_zona"] ?? null]);
[$codigo, $cuerpo] = peticion("PUT", "/api-recoleccion/cuadrillas/$idCuaZ", ["turno" => "nocturno"], $admin);
afirmar("Editar otro dato no le saca el municipio", [200, 3], [$codigo, $cuerpo["datos"]["id_zona"] ?? null]);
[$codigo] = peticion("PUT", "/api-recoleccion/cuadrillas/$idCuaZ", ["id_zona" => 99999], $admin);
afirmar("Cambiarlo a un municipio que no existe: 400", 400, $codigo);
[, $cuerpo] = peticion("GET", "/api-recoleccion/cuadrillas", null, $admin);
$deLaLista = current(array_filter($cuerpo["datos"] ?? [], fn($c) => (int) $c["id_cuadrilla"] === $idCuaZ)) ?: [];
afirmar("La lista trae el municipio de cada cuadrilla", [3, true], [$deLaLista["id_zona"] ?? null, is_string($deLaLista["zona"] ?? null)]);
[$codigo, $cuerpo] = peticion("PUT", "/api-recoleccion/cuadrillas/$idCuaZ", ["id_zona" => ""], $admin);
afirmar("Mandar el municipio vacio se lo quita", [200, null, null], [$codigo, array_key_exists("id_zona", $cuerpo["datos"] ?? []) ? $cuerpo["datos"]["id_zona"] : "falta", array_key_exists("zona", $cuerpo["datos"] ?? []) ? $cuerpo["datos"]["zona"] : "falta"]);
foreach (["vecino" => $vecino, "operario" => $operario, "cuadrilla" => $cuadrilla] as $quien => $cookies) {
    [$codigo] = peticion("PUT", "/api-recoleccion/cuadrillas/$idCuaZ", ["id_zona" => 2], $cookies);
    afirmar("El municipio de una cuadrilla no lo cambia $quien: 403", 403, $codigo);
}
[$codigo] = peticion("PUT", "/api-recoleccion/cuadrillas/$idCua2", ["estado" => "rota"], $admin);
afirmar("Un estado inventado: 400", 400, $codigo);
[$codigo, $cuerpo] = peticion("GET", "/api-recoleccion/cuadrillas/$idCua1", null, $admin);
afirmar("El detalle trae sus integrantes (una lista) y los contadores", [200, true, true], [$codigo, is_array($cuerpo["datos"]["personas"] ?? null), isset($cuerpo["datos"]["recolecciones_abiertas"])]);

// una cuadrilla con una recoleccion planificada no se da de baja
[, $cuerpo] = peticion("POST", "/api-recoleccion/camiones", ["matricula" => "TCUA-$sufijo", "capacidad" => 4000], $admin);
$idCamionC = (int) ($cuerpo["datos"]["id_camion"] ?? 0);
[, $cuerpo] = peticion("POST", "/api-recoleccion/rutas", ["nombre" => "TCUA Ruta $sufijo", "dia_semana" => "viernes", "hora_inicio" => "06:15", "id_zona" => 2], $admin);
$idRutaC = (int) ($cuerpo["datos"]["id_ruta"] ?? 0);
[$codigo, $cuerpo] = peticion("POST", "/api-recoleccion/recolecciones", ["id_ruta" => $idRutaC, "id_camion" => $idCamionC, "id_cuadrilla" => $idCua1, "fecha_hora_inicio" => "2026-12-11T06:30", "id_centro" => 1], $admin);
$idRecC = (int) ($cuerpo["datos"]["id_recoleccion"] ?? 0);
afirmar("Se planifica una recoleccion de prueba para la cuadrilla nueva", 201, $codigo);

// H-07 (parte 26): una planificada cuya fecha ya paso sin que nadie la iniciara sale marcada «vencida» (la calcula el servidor con su reloj).
$vencida = function (string $momento) use ($pdo, $idRecC, $admin): array {
    $pdo->exec("UPDATE recoleccion SET fecha_hora_inicio = $momento WHERE id_recoleccion = $idRecC");
    [, $detalle] = peticion("GET", "/api-recoleccion/recolecciones/$idRecC", null, $admin);
    [, $lista] = peticion("GET", "/api-recoleccion/recolecciones?estado=planificada", null, $admin);
    $enLista = current(array_filter($lista["datos"] ?? [], fn($r) => (int) $r["id_recoleccion"] === $idRecC)) ?: [];
    return [$detalle["datos"]["vencida"] ?? "falta", $enLista["vencida"] ?? "falta"];
};
afirmar("Planificada para manana: no esta vencida (ni en el detalle ni en la lista)", [false, false], $vencida("NOW() + INTERVAL 1 DAY"));
afirmar("Planificada para ayer y sin iniciar: vencida, en el detalle y en la lista", [true, true], $vencida("NOW() - INTERVAL 1 DAY"));
afirmar("Planificada para dentro de un minuto: todavia no", [false, false], $vencida("NOW() + INTERVAL 1 MINUTE"));
$pdo->exec("UPDATE recoleccion SET fecha_hora_inicio = '2026-12-11 06:30:00' WHERE id_recoleccion = $idRecC");
[$codigo, $cuerpo] = peticion("DELETE", "/api-recoleccion/cuadrillas/$idCua1", null, $admin);
afirmar("Con una recoleccion planificada no se puede dar de baja: 409 y dice por que", [409, true], [$codigo, str_contains($cuerpo["mensaje"] ?? "", "recolecci")]);
[$codigo] = peticion("PUT", "/api-recoleccion/cuadrillas/$idCua1", ["estado" => "inactiva"], $admin);
afirmar("Ni pasandola a inactiva por la edicion: 409", 409, $codigo);
peticion("POST", "/api-recoleccion/recolecciones/$idRecC/cancelacion", ["observacion" => "prueba"], $admin);
[$codigo, $cuerpo] = peticion("DELETE", "/api-recoleccion/cuadrillas/$idCua1", null, $admin);
afirmar("Cancelada la recoleccion, se da de baja: 200, inactiva", [200, "inactiva"], [$codigo, $cuerpo["datos"]["estado"] ?? null]);
[$codigo] = peticion("DELETE", "/api-recoleccion/cuadrillas/$idCua1", null, $admin);
afirmar("Darla de baja dos veces: 409", 409, $codigo);
[$codigo, $cuerpo] = peticion("PUT", "/api-recoleccion/cuadrillas/$idCua1", ["estado" => "activa"], $admin);
afirmar("Se puede reactivar", [200, "activa"], [$codigo, $cuerpo["datos"]["estado"] ?? null]);


titulo("48. Usuarios: la cuadrilla de un usuario con rol Cuadrilla");

$usuario = fn(string $quien, array $extra = []) => ["nombre" => "Tcua", "apellido" => $quien, "email" => "tcua-$quien-$sufijo@test.uy", "password" => "ClavePrueba-1"] + $extra;
[$codigo, $cuerpo] = peticion("POST", "/api-usuarios/usuarios", $usuario("a", ["rol" => "Cuadrilla", "cedula" => $ced1]), $admin);
afirmar("Un usuario Cuadrilla sin cuadrilla: 400 (sin ella no veria ninguna ruta)", 400, $codigo);
[$codigo] = peticion("POST", "/api-usuarios/usuarios", $usuario("a", ["rol" => "Cuadrilla", "cedula" => $ced1, "id_cuadrilla" => null]), $admin);
afirmar("Con id_cuadrilla null tampoco: 400", 400, $codigo);
[$codigo] = peticion("POST", "/api-usuarios/usuarios", $usuario("a", ["rol" => "Cuadrilla", "cedula" => $ced1, "id_cuadrilla" => 99999]), $admin);
afirmar("Una cuadrilla que no existe: 400", 400, $codigo);
[$codigo] = peticion("POST", "/api-usuarios/usuarios", $usuario("a", ["rol" => "Cuadrilla", "cedula" => $ced1, "id_cuadrilla" => "abc"]), $admin);
afirmar("Una cuadrilla con texto: 400", 400, $codigo);
peticion("DELETE", "/api-recoleccion/cuadrillas/$idCua2", null, $admin);
[$codigo, $cuerpo] = peticion("POST", "/api-usuarios/usuarios", $usuario("a", ["rol" => "Cuadrilla", "cedula" => $ced1, "id_cuadrilla" => $idCua2]), $admin);
afirmar("Una cuadrilla dada de baja no recibe gente nueva: 400", [400, true], [$codigo, str_contains($cuerpo["mensaje"] ?? "", "baja")]);
[$codigo] = peticion("POST", "/api-usuarios/usuarios", $usuario("v", ["rol" => "Vecino", "id_cuadrilla" => $idCua1]), $admin);
afirmar("Un Vecino no se asocia a una cuadrilla: 400", 400, $codigo);
[$codigo, $cuerpo] = peticion("POST", "/api-usuarios/usuarios", $usuario("a", ["rol" => "Cuadrilla", "cedula" => $formatoCedula($ced1), "id_cuadrilla" => $idCua1]), $admin);
$idUsuC = (int) ($cuerpo["datos"]["id_usuario"] ?? 0);
afirmar("Con una cuadrilla activa se crea: 201 y queda asociado", [201, $idCua1, "TCUA Uno $sufijo"], [$codigo, (int) ($cuerpo["datos"]["id_cuadrilla"] ?? 0), $cuerpo["datos"]["cuadrilla"] ?? null]);
afirmar("La cedula se guarda solo con digitos, aunque llegue con puntos y guion", $ced1, $cuerpo["datos"]["cedula"] ?? null);

[$codigo] = peticion("POST", "/api-usuarios/usuarios", ["cedula" => null] + $usuario("b", ["rol" => "Cuadrilla", "id_cuadrilla" => $idCua1]), $admin);
afirmar("Un usuario Cuadrilla sin cedula: 400 (con ella inicia sesion)", 400, $codigo);
[$codigo] = peticion("POST", "/api-usuarios/usuarios", $usuario("b", ["rol" => "Cuadrilla", "id_cuadrilla" => $idCua1, "cedula" => "   "]), $admin);
afirmar("Una cedula en blanco tampoco: 400", 400, $codigo);
[$codigo, $cuerpo] = peticion("POST", "/api-usuarios/usuarios", $usuario("b", ["rol" => "Cuadrilla", "id_cuadrilla" => $idCua1, "cedula" => substr($ced2, 0, -1) . ((int) substr($ced2, -1) + 1) % 10]), $admin);
afirmar("Una cedula con el digito verificador mal: 400", [400, true], [$codigo, str_contains($cuerpo["mensaje"] ?? "", "cédula")]);
[$codigo] = peticion("POST", "/api-usuarios/usuarios", $usuario("b", ["rol" => "Cuadrilla", "id_cuadrilla" => $idCua1, "cedula" => "12ab"]), $admin);
afirmar("Una cedula con letras o muy corta: 400", 400, $codigo);
[$codigo, $cuerpo] = peticion("POST", "/api-usuarios/usuarios", $usuario("b", ["rol" => "Cuadrilla", "id_cuadrilla" => $idCua1, "cedula" => $formatoCedula($ced1)]), $admin);
afirmar("Una cedula que ya tiene otro usuario (aunque venga con otro formato): 409", [409, true], [$codigo, str_contains($cuerpo["mensaje"] ?? "", "cédula")]);

$sesionCedula = function (string $cedula, string $clave): array {
    [$codigo, $cuerpo] = peticion("POST", "/api-usuarios/login", ["cedula" => $cedula, "password" => $clave], null);
    return [$codigo, $cuerpo];
};
[$codigo, $cuerpo] = $sesionCedula($formatoCedula($ced1), "ClavePrueba-1");
afirmar("La cuadrilla entra con cedula (con puntos y guion) y contrasena: 200, rol Cuadrilla", [200, "Cuadrilla", $ced1], [$codigo, $cuerpo["datos"]["rol"] ?? null, $cuerpo["datos"]["cedula"] ?? null]);
[$codigo] = $sesionCedula($ced1, "ClavePrueba-1");
afirmar("Y con la cedula solo con digitos", 200, $codigo);
[$codigo, $cuerpo] = $sesionCedula($ced1, "mala-clave-1");
afirmar("Contrasena equivocada: 401 con mensaje de cedula", [401, true], [$codigo, str_contains($cuerpo["mensaje"] ?? "", "cédula")]);
[$codigo] = $sesionCedula($ced3, "ClavePrueba-1");
afirmar("Una cedula que no existe: 401", 401, $codigo);
[$codigo] = $sesionCedula("", "ClavePrueba-1");
afirmar("Una cedula vacia: 401", 401, $codigo);
[$codigo, $cuerpo] = peticion("POST", "/api-usuarios/login", ["email" => "tcua-a-$sufijo@test.uy", "password" => "ClavePrueba-1"], null);
afirmar("Una cuenta de cuadrilla no entra con el correo, ni con la contrasena correcta: 403 que lo explica", [403, true], [$codigo, str_contains($cuerpo["mensaje"] ?? "", "cédula")]);
[$codigo] = peticion("POST", "/api-usuarios/login", ["email" => "vecino@sigeru.uy", "password" => "sigeru2026"], null);
afirmar("El resto de los roles sigue entrando con correo", 200, $codigo);
[$codigo] = peticion("POST", "/api-usuarios/login", ["cedula" => ["x"], "password" => "ClavePrueba-1"], null);
afirmar("Una cedula que no es texto no rompe el login: 401", 401, $codigo);

[$codigo, $cuerpo] = peticion("PUT", "/api-usuarios/usuarios/$idUsuC", ["nombre" => "Otro"], $admin);
afirmar("Editar otro dato no le saca la cuadrilla", [200, $idCua1], [$codigo, (int) ($cuerpo["datos"]["id_cuadrilla"] ?? 0)]);
[$codigo] = peticion("PUT", "/api-usuarios/usuarios/$idUsuC", ["id_cuadrilla" => null], $admin);
afirmar("Dejar a un Cuadrilla sin cuadrilla: 400", 400, $codigo);
peticion("PUT", "/api-recoleccion/cuadrillas/$idCua2", ["estado" => "activa"], $admin);
[$codigo, $cuerpo] = peticion("PUT", "/api-usuarios/usuarios/$idUsuC", ["id_cuadrilla" => $idCua2], $admin);
afirmar("Se lo cambia a otra cuadrilla", [200, $idCua2], [$codigo, (int) ($cuerpo["datos"]["id_cuadrilla"] ?? 0)]);
[, $cuerpo] = peticion("GET", "/api-usuarios/usuarios?cuadrilla=$idCua2", null, $admin);
afirmar("La lista se filtra por cuadrilla", [$idUsuC], array_map("intval", array_column($cuerpo["datos"] ?? [], "id_usuario")));
[$codigo] = peticion("GET", "/api-usuarios/usuarios?cuadrilla=abc", null, $admin);
afirmar("Un filtro de cuadrilla invalido: 400", 400, $codigo);
[, $cuerpo] = peticion("GET", "/api-recoleccion/cuadrillas/$idCua2", null, $admin);
afirmar("La cuadrilla cuenta su integrante y lo lista por nombre", [1, "Otro a"], [$cuerpo["datos"]["integrantes"] ?? null, trim(($cuerpo["datos"]["personas"][0]["nombre"] ?? "") . " " . ($cuerpo["datos"]["personas"][0]["apellido"] ?? ""))]);
[$codigo, $cuerpo] = peticion("PUT", "/api-usuarios/usuarios/$idUsuC", ["rol" => "Vecino"], $admin);
afirmar("Al pasar a otro rol se le suelta la cuadrilla", [200, null], [$codigo, array_key_exists("id_cuadrilla", $cuerpo["datos"] ?? []) ? $cuerpo["datos"]["id_cuadrilla"] : "falta"]);
[$codigo, $cuerpo] = peticion("PUT", "/api-usuarios/usuarios/$idUsuC", ["rol" => "Cuadrilla"], $admin);
afirmar("Pasarlo a Cuadrilla sin decir la cuadrilla: 400", 400, $codigo);
[$codigo, $cuerpo] = peticion("PUT", "/api-usuarios/usuarios/$idUsuC", ["rol" => "Cuadrilla", "id_cuadrilla" => $idCua1], $admin);
afirmar("Y diciendola, queda: 200", [200, $idCua1], [$codigo, (int) ($cuerpo["datos"]["id_cuadrilla"] ?? 0)]);
[$codigo] = peticion("PUT", "/api-usuarios/usuarios/$idUsuC", ["id_cuadrilla" => $idCua1], $operario);
afirmar("Un Operario no cambia cuadrillas: 403", 403, $codigo);


titulo("49. Registro y pedidos de acceso: nadie se hace cuadrilla solo");

[$codigo, $cuerpo] = peticion("POST", "/api-usuarios/registro", ["nombre" => "Tcua", "apellido" => "Registro", "email" => "tcua-registro-$sufijo@test.uy", "password" => "sigeru2026", "rol" => "Cuadrilla", "id_cuadrilla" => $idCua1], null);
afirmar("El registro publico ignora rol e id_cuadrilla: queda Vecino y sin cuadrilla", [201, "Vecino", null], [$codigo, $cuerpo["datos"]["rol"] ?? null, array_key_exists("id_cuadrilla", $cuerpo["datos"] ?? []) ? $cuerpo["datos"]["id_cuadrilla"] : "falta"]);
$filaReg = $pdo->query("SELECT r.nombre AS rol, u.id_cuadrilla FROM usuario u JOIN rol r ON r.id_rol = u.id_rol WHERE u.email = 'tcua-registro-$sufijo@test.uy'")->fetch();
afirmar("Y en la base tambien", ["Vecino", null], [$filaReg["rol"] ?? null, is_array($filaReg) ? $filaReg["id_cuadrilla"] : "falta"]);

$pedido = ["nombre" => "Tcua", "apellido" => "Pide", "email" => "tcua-pide-$sufijo@test.uy", "tipo_cuenta" => "cuadrilla", "cuadrilla_indicada" => "Cuadrilla Municipio A", "cedula" => $ced2];
[$codigo] = peticion("POST", "/api-usuarios/solicitudes", array_diff_key($pedido, ["cuadrilla_indicada" => 1]), null);
afirmar("Un pedido de cuadrilla sin decir cual: 400", 400, $codigo);
[$codigo] = peticion("POST", "/api-usuarios/solicitudes", array_diff_key($pedido, ["cedula" => 1]), null);
afirmar("Un pedido de cuadrilla sin cedula: 400", 400, $codigo);
[$codigo, $cuerpo] = peticion("POST", "/api-usuarios/solicitudes", ["cedula" => "1234567-0"] + $pedido, null);
afirmar("Con la cedula mal (verificador): 400", [400, true], [$codigo, str_contains($cuerpo["mensaje"] ?? "", "cédula")]);
[$codigo, $cuerpo] = peticion("POST", "/api-usuarios/solicitudes", ["cedula" => $ced1] + $pedido, null);
afirmar("Con la cedula de un usuario que ya existe: 409", [409, true], [$codigo, str_contains($cuerpo["mensaje"] ?? "", "cédula")]);
[$codigo] = peticion("POST", "/api-usuarios/solicitudes", ["tipo_cuenta" => "administrador"] + $pedido, null);
afirmar("Un tipo de cuenta inventado: 400 (no se puede pedir ser administrador)", 400, $codigo);
[$codigo] = peticion("POST", "/api-usuarios/solicitudes", ["cuadrilla_indicada" => str_repeat("x", 81)] + $pedido, null);
afirmar("Una cuadrilla de mas de 80 letras: 400", 400, $codigo);
[$codigo, $cuerpo] = peticion("POST", "/api-usuarios/solicitudes", $pedido, null);
$idSol = (int) ($cuerpo["datos"]["id_solicitud"] ?? 0);
afirmar("El pedido sin sesion se acepta: 201, pendiente y de tipo cuadrilla", [201, "pendiente", "cuadrilla", "Cuadrilla Municipio A"],
    [$codigo, $cuerpo["datos"]["estado"] ?? null, $cuerpo["datos"]["tipo_cuenta"] ?? null, $cuerpo["datos"]["cuadrilla_indicada"] ?? null]);
afirmar("La solicitud guarda la cedula solo con digitos", $ced2, $pdo->query("SELECT cedula FROM solicitud_registro WHERE id_solicitud = $idSol")->fetchColumn());
[$codigo, $cuerpo] = peticion("POST", "/api-usuarios/solicitudes", ["email" => "tcua-repite-$sufijo@test.uy", "cedula" => $formatoCedula($ced2)] + $pedido, null);
afirmar("Otro pedido pendiente con la misma cedula: 409", [409, true], [$codigo, str_contains($cuerpo["mensaje"] ?? "", "cédula")]);
$pdo->exec("DELETE FROM limite_peticion WHERE clave LIKE 'solicitud:%'"); // el freno por IP son 10 pedidos por hora y esta seccion hace mas
afirmar("No crea ninguna cuenta ni sesion", 0, (int) $pdo->query("SELECT COUNT(*) FROM usuario WHERE email = 'tcua-pide-$sufijo@test.uy'")->fetchColumn());
[$codigo] = peticion("POST", "/api-usuarios/solicitudes", ["email" => "vecino@sigeru.uy"] + $pedido, null);
afirmar("Un correo de un usuario existente: 409", 409, $codigo);
[$codigo] = peticion("GET", "/api-usuarios/solicitudes", null, $vecino);
afirmar("Las solicitudes solo las ve el administrador: 403", 403, $codigo);
[$codigo, $cuerpo] = peticion("GET", "/api-usuarios/solicitudes?estado=pendiente", null, $admin);
$miSol = array_values(array_filter($cuerpo["datos"] ?? [], fn($s) => (int) $s["id_solicitud"] === $idSol))[0] ?? [];
afirmar("El administrador la ve marcada como de cuadrilla, con lo que escribio la persona", [200, "cuadrilla", "Cuadrilla Municipio A"], [$codigo, $miSol["tipo_cuenta"] ?? null, $miSol["cuadrilla_indicada"] ?? null]);

[$codigo] = peticion("PUT", "/api-usuarios/solicitudes/$idSol", ["decision" => "aprobada"], $vecino);
afirmar("Un vecino no aprueba nada: 403", 403, $codigo);
[$codigo] = peticion("PUT", "/api-usuarios/solicitudes/$idSol", ["decision" => "aprobada", "rol" => "Administrador", "id_cuadrilla" => $idCua1], $admin);
afirmar("Una solicitud de cuadrilla no se aprueba con otro rol: 400", 400, $codigo);
[$codigo] = peticion("PUT", "/api-usuarios/solicitudes/$idSol", ["decision" => "aprobada"], $admin);
afirmar("Aprobarla sin elegir la cuadrilla real: 400", 400, $codigo);
[$codigo] = peticion("PUT", "/api-usuarios/solicitudes/$idSol", ["decision" => "aprobada", "id_cuadrilla" => 99999], $admin);
afirmar("Con una cuadrilla que no existe: 400", 400, $codigo);
afirmar("Y la solicitud sigue pendiente, sin cuenta", ["pendiente", 0], [
    $pdo->query("SELECT estado FROM solicitud_registro WHERE id_solicitud = $idSol")->fetchColumn(),
    (int) $pdo->query("SELECT COUNT(*) FROM usuario WHERE email = 'tcua-pide-$sufijo@test.uy'")->fetchColumn(),
]);
[$codigo] = peticion("PUT", "/api-usuarios/solicitudes/$idSol", ["decision" => "aprobada", "id_cuadrilla" => $idCua1], $admin);
afirmar("Aprobarla sin definir la contrasena inicial: 400", 400, $codigo);
[$codigo, $cuerpo] = peticion("PUT", "/api-usuarios/solicitudes/$idSol", ["decision" => "aprobada", "id_cuadrilla" => $idCua1, "password" => "ClavePrueba-1"], $admin);
afirmar("Aprobada con la cuadrilla real: 200, usuario Cuadrilla con su id_cuadrilla y activo", [200, "Cuadrilla", $idCua1, "activo"],
    [$codigo, $cuerpo["datos"]["usuario"]["rol"] ?? null, (int) ($cuerpo["datos"]["usuario"]["id_cuadrilla"] ?? 0), $cuerpo["datos"]["usuario"]["estado"] ?? null]);
afirmar("La cuenta trae la cedula del pedido", $ced2, $cuerpo["datos"]["usuario"]["cedula"] ?? null);
[$codigo, $cuerpo] = $sesionCedula($ced2, "ClavePrueba-1");
afirmar("Y la cuadrilla aprobada entra con esa cedula y la contrasena que definio el administrador", [200, "Cuadrilla"], [$codigo, $cuerpo["datos"]["rol"] ?? null]);
[$codigo] = peticion("PUT", "/api-usuarios/solicitudes/$idSol", ["decision" => "aprobada", "id_cuadrilla" => $idCua1, "password" => "ClavePrueba-1"], $admin);
afirmar("Aprobarla dos veces: 409", 409, $codigo);
$pedido2 = ["email" => "tcua-pide2-$sufijo@test.uy", "cedula" => $ced3] + $pedido;
[, $cuerpo] = peticion("POST", "/api-usuarios/solicitudes", $pedido2, null);
$idSol2 = (int) ($cuerpo["datos"]["id_solicitud"] ?? 0);
[$codigo, $cuerpo] = peticion("PUT", "/api-usuarios/solicitudes/$idSol2", ["decision" => "rechazada"], $admin);
afirmar("Rechazar no pide cuadrilla y no crea cuenta: 200", [200, 0], [$codigo, (int) $pdo->query("SELECT COUNT(*) FROM usuario WHERE email = 'tcua-pide2-$sufijo@test.uy'")->fetchColumn()]);
[, $cuerpo] = peticion("POST", "/api-usuarios/solicitudes", ["email" => "tcua-vecino-$sufijo@test.uy", "nombre" => "Tcua", "apellido" => "Vecino", "cedula" => $ced4], null);
afirmar("Una solicitud comun (sin tipo) sigue siendo de vecino, y no guarda cedula", ["vecino", null], [$cuerpo["datos"]["tipo_cuenta"] ?? null, array_key_exists("cedula", $cuerpo["datos"] ?? []) ? $cuerpo["datos"]["cedula"] : "falta"]);
[, $cuerpo] = peticion("POST", "/api-usuarios/registro", ["nombre" => "Tcua", "apellido" => "Cedula", "email" => "tcua-registro2-$sufijo@test.uy", "password" => "sigeru2026", "cedula" => $ced4], null);
afirmar("El registro publico de vecino ignora la cedula", [null], [array_key_exists("cedula", $cuerpo["datos"] ?? []) ? $cuerpo["datos"]["cedula"] : "falta"]);
[$codigo] = $sesionCedula($ced4, "sigeru2026");
afirmar("Y con esa cedula nadie puede entrar: 401", 401, $codigo);

$limpiarCua();
afirmar("Se borraron las cuadrillas, usuarios y solicitudes de prueba", [0, 0, 0], [
    (int) $pdo->query("SELECT COUNT(*) FROM cuadrilla WHERE nombre LIKE 'TCUA %'")->fetchColumn(),
    (int) $pdo->query("SELECT COUNT(*) FROM usuario WHERE email LIKE 'tcua-%@test.uy'")->fetchColumn(),
    (int) $pdo->query("SELECT COUNT(*) FROM solicitud_registro WHERE email LIKE 'tcua-%@test.uy'")->fetchColumn(),
]);
