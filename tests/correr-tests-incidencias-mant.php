<?php

// Parte de correr-tests.php (se incluye desde alli y usa sus funciones y sus sesiones).
// Plan de rutas para la defensa, bloque 7: prioridad segun el tipo de problema, tipo «lleno», derivacion a mantenimiento de lo roto
// y la cuadrilla que informa en terreno. Todo con contenedores propios TMANT; el historial es de solo agregado, asi que se borra por la incidencia.

titulo("46. Incidencias: prioridad por tipo, lleno, roto y derivacion a mantenimiento");

$limpiarMant = function () use ($pdo): void {
    $pdo->exec("DELETE FROM incidencia WHERE id_contenedor IN (SELECT id_contenedor FROM contenedor WHERE codigo LIKE 'TMANT-%')");
    $pdo->exec("DELETE FROM recoleccion WHERE id_camion IN (SELECT id_camion FROM camion WHERE matricula LIKE 'TMANT-%')");
    $pdo->exec("DELETE FROM camion WHERE matricula LIKE 'TMANT-%'");
    $pdo->exec("DELETE FROM ruta WHERE nombre LIKE 'TMANT %'");
    $pdo->exec("DELETE FROM contenedor WHERE codigo LIKE 'TMANT-%'");
};
$limpiarMant();

foreach (["D", "L", "R", "R2", "F", "Z"] as $letra) {
    peticion("POST", "/api-gestion/contenedores", ["codigo" => "TMANT-$letra$sufijo", "direccion" => "Contenedor de prueba de mantenimiento $letra",
        "capacidad" => 1100, "tipo_residuo" => "Mixto", "id_zona" => 2], $admin);
}
$informe = fn(string $letra, string $tipo, array $extra = []) => ["codigo_contenedor" => "TMANT-$letra$sufijo", "tipo_problema" => $tipo, "descripcion" => "Prueba de $tipo en $letra"] + $extra;
$estadoDe = function (string $letra) use ($admin, $sufijo): ?string {
    [, $c] = peticion("GET", "/api-gestion/contenedores?q=TMANT-$letra$sufijo", null, $admin);
    foreach ($c["datos"] ?? [] as $f) {
        if ($f["codigo"] === "TMANT-$letra$sufijo") { return $f["estado"]; }
    }
    return null;
};

// ---- prioridad por tipo
[$codigo, $cuerpo] = peticion("POST", "/api-gestion/incidencias", $informe("D", "desbordado"), $vecino);
$idDes = (int) ($cuerpo["datos"]["id_incidencia"] ?? 0);
afirmar("Un desborde sin prioridad nace ALTA", [201, "alta"], [$codigo, $cuerpo["datos"]["prioridad"] ?? null]);
[$codigo, $cuerpo] = peticion("POST", "/api-gestion/incidencias", $informe("L", "lleno"), $vecino);
$idLleno = (int) ($cuerpo["datos"]["id_incidencia"] ?? 0);
afirmar("Un contenedor lleno (sin desbordar) nace MEDIA y se acepta como tipo: 201", [201, "media", "lleno"], [$codigo, $cuerpo["datos"]["prioridad"] ?? null, $cuerpo["datos"]["tipo_problema"] ?? null]);
afirmar("Informar «lleno» deja el contenedor en estado lleno", "lleno", $estadoDe("L"));
[$codigo, $cuerpo] = peticion("POST", "/api-gestion/incidencias", $informe("R", "roto"), $vecino);
$idRoto = (int) ($cuerpo["datos"]["id_incidencia"] ?? 0);
afirmar("Un contenedor roto nace media y se marca como de mantenimiento, sin derivar todavia", [201, "media", true, false], [$codigo, $cuerpo["datos"]["prioridad"] ?? null, $cuerpo["datos"]["de_mantenimiento"] ?? null, $cuerpo["datos"]["derivada"] ?? null]);
afirmar("Informar «roto» no cambia el estado del contenedor (lo decide el administrador al derivar)", "disponible", $estadoDe("R"));
[$codigo, $cuerpo] = peticion("POST", "/api-gestion/incidencias", $informe("R2", "lleno", ["prioridad" => "alta"]), $vecino);
afirmar("Si quien informa elige la prioridad, se respeta", "alta", $cuerpo["datos"]["prioridad"] ?? null);

// ---- el administrador se entera de lo roto
$numeroRoto = sprintf("INC-%04d", $idRoto);
afirmar("Al informarse un contenedor roto, los administradores reciben un aviso", true,
    (int) $pdo->query("SELECT COUNT(*) FROM notificacion WHERE tipo = 'contenedor_roto' AND titulo LIKE '%$numeroRoto%'")->fetchColumn() >= 1);

// ---- lo roto no se asigna a una cuadrilla
[$codigo, $cuerpo] = peticion("POST", "/api-gestion/incidencias/$idRoto/asignacion", ["id_cuadrilla" => 1], $admin);
afirmar("Un contenedor roto no se asigna a una cuadrilla: 409 y explica por que", [409, true], [$codigo, str_contains(mb_strtolower($cuerpo["mensaje"] ?? ""), "mantenimiento")]);
[$codigo] = peticion("POST", "/api-gestion/incidencias/$idLleno/asignacion", ["id_cuadrilla" => 1], $admin);
afirmar("Un lleno si se puede asignar: 200", 200, $codigo);

// ---- derivacion
foreach (["Vecino" => $vecino, "Cuadrilla" => $cuadrilla, "Operario" => $operario, "sin sesion" => null] as $quien => $cookies) {
    [$codigo] = peticion("POST", "/api-gestion/incidencias/$idRoto/derivacion", ["nota" => "x"], $cookies);
    afirmar("Derivar es solo del administrador ($quien)", $cookies === null ? 401 : 403, $codigo);
}
[$codigo, $cuerpo] = peticion("POST", "/api-gestion/incidencias/$idDes/derivacion", ["nota" => "x"], $admin);
afirmar("Un desborde no se deriva a mantenimiento: 409", 409, $codigo);
[$codigo] = peticion("POST", "/api-gestion/incidencias/$idRoto/derivacion", ["nota" => str_repeat("x", 201)], $admin);
afirmar("Una nota de mas de 200 caracteres: 400", 400, $codigo);
[$codigo, $cuerpo] = peticion("POST", "/api-gestion/incidencias/$idRoto/derivacion", ["nota" => "Se llamo a Mantenimiento de la Intendencia"], $admin);
afirmar("El administrador deriva el roto: 200, queda derivada con su nota y sigue abierta", [200, true, "Se llamo a Mantenimiento de la Intendencia", "abierta"],
    [$codigo, $cuerpo["datos"]["derivada"] ?? null, $cuerpo["datos"]["derivada_nota"] ?? null, $cuerpo["datos"]["estado"] ?? null]);
afirmar("El contenedor pasa a mantenimiento", "mantenimiento", $estadoDe("R"));
afirmar("El hecho queda en el historial (solo agregado)", true, str_contains((string) (ultimoAsiento($cuerpo)["observacion"] ?? ""), "Derivada a mantenimiento"));
afirmar("Y el vecino que informo recibe el aviso de que paso a mantenimiento", true,
    (int) $pdo->query("SELECT COUNT(*) FROM notificacion WHERE titulo LIKE '%$numeroRoto%' AND titulo LIKE '%mantenimiento%'")->fetchColumn() >= 1);
[$codigo] = peticion("POST", "/api-gestion/incidencias/$idRoto/derivacion", ["nota" => "otra vez"], $admin);
afirmar("Derivarla dos veces: 409", 409, $codigo);
[$codigo] = peticion("POST", "/api-gestion/incidencias/$idRoto/asignacion", ["id_cuadrilla" => 1], $admin);
afirmar("Derivada y todo, tampoco se asigna: 409", 409, $codigo);

// ---- al resolver vuelve a estar disponible
[$codigo, $cuerpo] = peticion("POST", "/api-gestion/incidencias/$idRoto/resolucion", ["observacion" => "Lo arreglaron"], $admin);
afirmar("El administrador la cierra cuando lo arreglan: resuelta", [200, "resuelta"], [$codigo, $cuerpo["datos"]["estado"] ?? null]);
afirmar("El contenedor vuelve a estar disponible", "disponible", $estadoDe("R"));
[$codigo] = peticion("POST", "/api-gestion/incidencias/$idRoto/derivacion", ["nota" => "x"], $admin);
afirmar("Una incidencia cerrada no se deriva: 409", 409, $codigo);

// ---- la cuadrilla informa en terreno: solo sobre lo que ve
[, $cuerpo] = peticion("POST", "/api-recoleccion/camiones", ["matricula" => "TMANT-$sufijo", "capacidad" => 4000], $admin);
$idCamionM = (int) ($cuerpo["datos"]["id_camion"] ?? 0);
[, $cuerpo] = peticion("POST", "/api-recoleccion/rutas", ["nombre" => "TMANT Ruta $sufijo", "dia_semana" => "jueves", "hora_inicio" => "06:15", "id_zona" => 2], $admin);
$idRutaM = (int) ($cuerpo["datos"]["id_ruta"] ?? 0);
ponerEnRuta($pdo, $idRutaM, ["TMANT-F$sufijo"]);
[$codigo] = peticion("POST", "/api-recoleccion/recolecciones", ["id_ruta" => $idRutaM, "id_camion" => $idCamionM, "id_cuadrilla" => 1, "fecha_hora_inicio" => "2026-12-10T06:30", "id_centro" => 1], $admin);
afirmar("Se planifica una recoleccion de prueba con la cuadrilla 1", 201, $codigo);
[$codigo, $cuerpo] = peticion("POST", "/api-gestion/incidencias", $informe("F", "lleno"), $cuadrilla);
$idCua = (int) ($cuerpo["datos"]["id_incidencia"] ?? 0);
afirmar("Una cuadrilla informa un contenedor de su ruta: 201", 201, $codigo);
[$codigo] = peticion("POST", "/api-gestion/incidencias", $informe("Z", "lleno"), $cuadrilla);
afirmar("Pero no uno que no es de su ruta: 404", 404, $codigo);
[$codigo, $cuerpo] = peticion("GET", "/api-gestion/incidencias/$idCua", null, $cuadrilla);
afirmar("La cuadrilla ve lo que ella informo", [200, true], [$codigo, ($cuerpo["datos"]["id_incidencia"] ?? 0) === $idCua]);
[, $cuerpo] = peticion("GET", "/api-gestion/incidencias", null, $cuadrilla);
afirmar("Y aparece en su lista", true, in_array($idCua, idsDe($cuerpo), true));
[, $cuerpo] = peticion("GET", "/api-gestion/incidencias", null, $otraCuadrilla);
afirmar("Otra cuadrilla no la ve", false, in_array($idCua, idsDe($cuerpo), true));
[$codigo] = peticion("GET", "/api-gestion/incidencias/$idCua", null, $otraCuadrilla);
afirmar("Ni abre su detalle: 403 o 404", true, in_array($codigo, [403, 404], true));

$limpiarMant();
afirmar("Los datos de prueba de mantenimiento se borraron", [0, 0], [
    (int) $pdo->query("SELECT COUNT(*) FROM contenedor WHERE codigo LIKE 'TMANT-%'")->fetchColumn(),
    (int) $pdo->query("SELECT COUNT(*) FROM ruta WHERE nombre LIKE 'TMANT %'")->fetchColumn(),
]);
