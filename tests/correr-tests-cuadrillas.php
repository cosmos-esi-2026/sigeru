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
    $pdo->exec("DELETE FROM enlace_clave WHERE id_usuario IN (SELECT id_usuario FROM usuario WHERE email LIKE 'tcua-%@test.uy')");
    $pdo->exec("DELETE FROM usuario WHERE email LIKE 'tcua-%@test.uy'");
    $pdo->exec("DELETE FROM solicitud_registro WHERE email LIKE 'tcua-%@test.uy'");
    $pdo->exec("DELETE FROM cuadrilla WHERE nombre LIKE 'TCUA %'");
    $pdo->exec("DELETE FROM limite_peticion WHERE clave LIKE 'solicitud:%' OR clave LIKE 'registro:%'");
};
$limpiarCua();

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

$usuario = fn(string $quien, array $extra = []) => ["nombre" => "Tcua", "apellido" => $quien, "email" => "tcua-$quien-$sufijo@test.uy"] + $extra;
[$codigo, $cuerpo] = peticion("POST", "/api-usuarios/usuarios", $usuario("a", ["rol" => "Cuadrilla"]), $admin);
if ($codigo === 503) {
    omitir("Usuarios con cuadrilla: el correo no esta en modo archivo (arrancar Apache con SIGERU_MAIL_MODE=archivo)");
} else {
    afirmar("Un usuario Cuadrilla sin cuadrilla: 400 (sin ella no veria ninguna ruta)", 400, $codigo);
    [$codigo] = peticion("POST", "/api-usuarios/usuarios", $usuario("a", ["rol" => "Cuadrilla", "id_cuadrilla" => null]), $admin);
    afirmar("Con id_cuadrilla null tampoco: 400", 400, $codigo);
    [$codigo] = peticion("POST", "/api-usuarios/usuarios", $usuario("a", ["rol" => "Cuadrilla", "id_cuadrilla" => 99999]), $admin);
    afirmar("Una cuadrilla que no existe: 400", 400, $codigo);
    [$codigo] = peticion("POST", "/api-usuarios/usuarios", $usuario("a", ["rol" => "Cuadrilla", "id_cuadrilla" => "abc"]), $admin);
    afirmar("Una cuadrilla con texto: 400", 400, $codigo);
    peticion("DELETE", "/api-recoleccion/cuadrillas/$idCua2", null, $admin);
    [$codigo, $cuerpo] = peticion("POST", "/api-usuarios/usuarios", $usuario("a", ["rol" => "Cuadrilla", "id_cuadrilla" => $idCua2]), $admin);
    afirmar("Una cuadrilla dada de baja no recibe gente nueva: 400", [400, true], [$codigo, str_contains($cuerpo["mensaje"] ?? "", "baja")]);
    [$codigo] = peticion("POST", "/api-usuarios/usuarios", $usuario("v", ["rol" => "Vecino", "id_cuadrilla" => $idCua1]), $admin);
    afirmar("Un Vecino no se asocia a una cuadrilla: 400", 400, $codigo);
    [$codigo, $cuerpo] = peticion("POST", "/api-usuarios/usuarios", $usuario("a", ["rol" => "Cuadrilla", "id_cuadrilla" => $idCua1]), $admin);
    $idUsuC = (int) ($cuerpo["datos"]["id_usuario"] ?? 0);
    afirmar("Con una cuadrilla activa se crea: 201 y queda asociado", [201, $idCua1, "TCUA Uno $sufijo"], [$codigo, (int) ($cuerpo["datos"]["id_cuadrilla"] ?? 0), $cuerpo["datos"]["cuadrilla"] ?? null]);

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
}


titulo("49. Registro y pedidos de acceso: nadie se hace cuadrilla solo");

[$codigo, $cuerpo] = peticion("POST", "/api-usuarios/registro", ["nombre" => "Tcua", "apellido" => "Registro", "email" => "tcua-registro-$sufijo@test.uy", "password" => "sigeru2026", "rol" => "Cuadrilla", "id_cuadrilla" => $idCua1], null);
afirmar("El registro publico ignora rol e id_cuadrilla: queda Vecino y sin cuadrilla", [201, "Vecino", null], [$codigo, $cuerpo["datos"]["rol"] ?? null, array_key_exists("id_cuadrilla", $cuerpo["datos"] ?? []) ? $cuerpo["datos"]["id_cuadrilla"] : "falta"]);
$filaReg = $pdo->query("SELECT r.nombre AS rol, u.id_cuadrilla FROM usuario u JOIN rol r ON r.id_rol = u.id_rol WHERE u.email = 'tcua-registro-$sufijo@test.uy'")->fetch();
afirmar("Y en la base tambien", ["Vecino", null], [$filaReg["rol"] ?? null, is_array($filaReg) ? $filaReg["id_cuadrilla"] : "falta"]);

$pedido = ["nombre" => "Tcua", "apellido" => "Pide", "email" => "tcua-pide-$sufijo@test.uy", "tipo_cuenta" => "cuadrilla", "cuadrilla_indicada" => "Cuadrilla Municipio A"];
[$codigo] = peticion("POST", "/api-usuarios/solicitudes", array_diff_key($pedido, ["cuadrilla_indicada" => 1]), null);
afirmar("Un pedido de cuadrilla sin decir cual: 400", 400, $codigo);
[$codigo] = peticion("POST", "/api-usuarios/solicitudes", ["tipo_cuenta" => "administrador"] + $pedido, null);
afirmar("Un tipo de cuenta inventado: 400 (no se puede pedir ser administrador)", 400, $codigo);
[$codigo] = peticion("POST", "/api-usuarios/solicitudes", ["cuadrilla_indicada" => str_repeat("x", 81)] + $pedido, null);
afirmar("Una cuadrilla de mas de 80 letras: 400", 400, $codigo);
[$codigo, $cuerpo] = peticion("POST", "/api-usuarios/solicitudes", $pedido, null);
$idSol = (int) ($cuerpo["datos"]["id_solicitud"] ?? 0);
afirmar("El pedido sin sesion se acepta: 201, pendiente y de tipo cuadrilla", [201, "pendiente", "cuadrilla", "Cuadrilla Municipio A"],
    [$codigo, $cuerpo["datos"]["estado"] ?? null, $cuerpo["datos"]["tipo_cuenta"] ?? null, $cuerpo["datos"]["cuadrilla_indicada"] ?? null]);
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
[$codigo, $cuerpo] = peticion("PUT", "/api-usuarios/solicitudes/$idSol", ["decision" => "aprobada", "id_cuadrilla" => $idCua1], $admin);
if ($codigo === 503) {
    omitir("Aprobar un pedido de cuadrilla: el correo no esta en modo archivo");
} else {
    afirmar("Aprobada con la cuadrilla real: 200, usuario Cuadrilla con su id_cuadrilla y pendiente de contrasena", [200, "Cuadrilla", $idCua1, "pendiente"],
        [$codigo, $cuerpo["datos"]["usuario"]["rol"] ?? null, (int) ($cuerpo["datos"]["usuario"]["id_cuadrilla"] ?? 0), $cuerpo["datos"]["usuario"]["estado"] ?? null]);
    [$codigo] = peticion("PUT", "/api-usuarios/solicitudes/$idSol", ["decision" => "aprobada", "id_cuadrilla" => $idCua1], $admin);
    afirmar("Aprobarla dos veces: 409", 409, $codigo);
}
$pedido2 = ["email" => "tcua-pide2-$sufijo@test.uy"] + $pedido;
[, $cuerpo] = peticion("POST", "/api-usuarios/solicitudes", $pedido2, null);
$idSol2 = (int) ($cuerpo["datos"]["id_solicitud"] ?? 0);
[$codigo, $cuerpo] = peticion("PUT", "/api-usuarios/solicitudes/$idSol2", ["decision" => "rechazada"], $admin);
afirmar("Rechazar no pide cuadrilla y no crea cuenta: 200", [200, 0], [$codigo, (int) $pdo->query("SELECT COUNT(*) FROM usuario WHERE email = 'tcua-pide2-$sufijo@test.uy'")->fetchColumn()]);
[, $cuerpo] = peticion("POST", "/api-usuarios/solicitudes", ["email" => "tcua-vecino-$sufijo@test.uy", "nombre" => "Tcua", "apellido" => "Vecino"], null);
afirmar("Una solicitud comun (sin tipo) sigue siendo de vecino", "vecino", $cuerpo["datos"]["tipo_cuenta"] ?? null);

$limpiarCua();
afirmar("Se borraron las cuadrillas, usuarios y solicitudes de prueba", [0, 0, 0], [
    (int) $pdo->query("SELECT COUNT(*) FROM cuadrilla WHERE nombre LIKE 'TCUA %'")->fetchColumn(),
    (int) $pdo->query("SELECT COUNT(*) FROM usuario WHERE email LIKE 'tcua-%@test.uy'")->fetchColumn(),
    (int) $pdo->query("SELECT COUNT(*) FROM solicitud_registro WHERE email LIKE 'tcua-%@test.uy'")->fetchColumn(),
]);
