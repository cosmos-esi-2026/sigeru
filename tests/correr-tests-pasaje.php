<?php

// Parte de correr-tests.php (se incluye desde alli y usa sus funciones y sus sesiones).
// "Cuando pasa el camion por mi zona" (extras, parte 15). Todo lo que se crea lleva el prefijo TPAS (un camion, una ruta de la zona
// Aires Puros, dos vecinos y sus recolecciones, con fechas relativas a hoy) y se borra al final.

titulo("33. Pasaje por zona: preparar los datos de prueba");

$pdo->exec("DELETE FROM recoleccion WHERE id_camion IN (SELECT id_camion FROM camion WHERE matricula LIKE 'TPAS-%')");
$pdo->exec("DELETE FROM camion WHERE matricula LIKE 'TPAS-%'");
$pdo->exec("DELETE FROM ruta WHERE nombre LIKE 'TPAS %'");
$pdo->exec("DELETE FROM usuario WHERE email LIKE 'tpas-%@test.uy'");

$hashZ = password_hash(CLAVE_DE_PRUEBA, PASSWORD_DEFAULT);
$vecinosZ = [];
foreach (["a", "b"] as $clave) {
    $pdo->prepare("INSERT INTO usuario (nombre, apellido, email, password_hash, id_rol, estado) VALUES ('Tpas', ?, ?, ?, (SELECT id_rol FROM rol WHERE nombre = 'Vecino'), 'activo')")
        ->execute([strtoupper($clave), "tpas-$clave-$sufijo@test.uy", $hashZ]);
    $vecinosZ[$clave] = sys_get_temp_dir() . "/sigeru_test_tpas_$clave.txt";
    iniciarSesion("tpas-$clave-$sufijo@test.uy", $vecinosZ[$clave]);
}
[$zA, $zB] = [$vecinosZ["a"], $vecinosZ["b"]];
[, $cuerpo] = peticion("POST", "/api-recoleccion/camiones", ["matricula" => "TPAS-$sufijo", "capacidad" => 4000], $admin);
$idCamionZ = (int) ($cuerpo["datos"]["id_camion"] ?? 0);
[, $cuerpo] = peticion("POST", "/api-recoleccion/rutas", ["nombre" => "TPAS Ruta $sufijo", "dia_semana" => "martes", "hora_inicio" => "06:15", "id_zona" => 5], $admin);
$idRutaZ = (int) ($cuerpo["datos"]["id_ruta"] ?? 0);
$pasajeDe = fn(string $cookies) => (peticion("GET", "/api-recoleccion/proximo-pasaje", null, $cookies)[1]["datos"] ?? []);

[$codigo] = peticion("GET", "/api-recoleccion/proximo-pasaje", null, null);
afirmar("Proximo pasaje sin sesion: 401", 401, $codigo);
[$codigo] = peticion("PUT", "/api-usuarios/mi-zona", ["id_zona" => 5], null);
afirmar("Elegir zona sin sesion: 401", 401, $codigo);
$obtenidos = [];
foreach (["admin" => $admin, "operario" => $operario, "cuadrilla" => $cuadrilla] as $quien => $cookies) {
    [$obtenidos["pasaje $quien"]] = peticion("GET", "/api-recoleccion/proximo-pasaje", null, $cookies);
    [$obtenidos["zona $quien"]] = peticion("PUT", "/api-usuarios/mi-zona", ["id_zona" => 5], $cookies);
}
afirmar("El pasaje y la zona son del vecino: los demas roles reciben 403", array_fill_keys(array_keys($obtenidos), 403), $obtenidos);
[$codigo] = peticion("POST", "/api-recoleccion/proximo-pasaje", ["x" => 1], $zA);
afirmar("El pasaje solo se lee (POST): 403", 403, $codigo);


titulo("33 bis. Pasaje por zona: elegir la zona y los estados honestos");

$d = $pasajeDe($zA);
afirmar("Sin zona elegida: sin_zona, y no inventa fecha", ["sin_zona", null, null], [$d["estado"] ?? null, array_key_exists("zona", $d) ? $d["zona"] : "falta", array_key_exists("proximo", $d) ? $d["proximo"] : "falta"]);
foreach ([["id_zona" => 0], ["id_zona" => -3], ["id_zona" => "abc"], ["id_zona" => 2.5], []] as $malo) {
    [$codigo] = peticion("PUT", "/api-usuarios/mi-zona", $malo, $zA);
    afirmar("Elegir una zona invalida " . json_encode($malo) . ": 400", 400, $codigo);
}
[$codigo] = peticion("PUT", "/api-usuarios/mi-zona", ["id_zona" => 99999], $zA);
afirmar("Una zona que no existe: 404", 404, $codigo);
[$codigo, $cuerpo] = peticion("PUT", "/api-usuarios/mi-zona", ["id_zona" => 5], $zA);
afirmar("El vecino elige Aires Puros: 200", [200, 5], [$codigo, $cuerpo["datos"]["id_zona"] ?? null]);
$d = $pasajeDe($zA);
afirmar("Sin pasajes planificados para su zona: lo dice y no inventa fecha", ["sin_planificadas", "Aires Puros", null, null], [$d["estado"] ?? null, $d["zona"] ?? null, array_key_exists("proximo", $d) ? $d["proximo"] : "falta", array_key_exists("cancelada", $d) ? $d["cancelada"] : "falta"]);

$fecha = fn(int $dias) => date("Y-m-d", strtotime("+$dias days"));
$planZ = ["id_ruta" => $idRutaZ, "id_camion" => $idCamionZ, "id_cuadrilla" => 1, "id_centro" => 1];
[, $cuerpo] = peticion("POST", "/api-recoleccion/recolecciones", $planZ + ["fecha_hora_inicio" => $fecha(3) . "T06:30"], $admin);
$idR1 = (int) ($cuerpo["datos"]["id_recoleccion"] ?? 0);
$d = $pasajeDe($zA);
afirmar("Con una planificada: el proximo pasaje es ese dia y hora", ["programado", $fecha(3) . " 06:30:00"], [$d["estado"] ?? null, $d["proximo"] ?? null]);
[, $cuerpo] = peticion("POST", "/api-recoleccion/recolecciones", $planZ + ["fecha_hora_inicio" => $fecha(1) . "T14:00"], $admin);
$idR2 = (int) ($cuerpo["datos"]["id_recoleccion"] ?? 0);
$d = $pasajeDe($zA);
afirmar("Con dos: el mas cercano (manana 14:00)", $fecha(1) . " 14:00:00", $d["proximo"] ?? null);
$clavesDelPasaje = array_keys($d);
sort($clavesDelPasaje);
afirmar("Solo trae estado, zona, dia y hora: nunca camion, cuadrilla ni ruta", ["cancelada", "estado", "id_zona", "proximo", "zona"], $clavesDelPasaje);
afirmar("El otro vecino (sin zona) no ve el de su vecino", "sin_zona", $pasajeDe($zB)["estado"] ?? null);

[$codigo] = peticion("POST", "/api-recoleccion/recolecciones/$idR2/cancelacion", ["observacion" => "Se reprograma"], $admin);
$d = $pasajeDe($zA);
afirmar("Si el administrador cancela esa recoleccion, el vecino ve la siguiente y se le avisa de la cancelada", [200, "programado", $fecha(3) . " 06:30:00", $fecha(1) . " 14:00:00"], [$codigo, $d["estado"] ?? null, $d["proximo"] ?? null, $d["cancelada"] ?? null]);
peticion("PUT", "/api-usuarios/mi-zona", ["id_zona" => 5], $zB);
afirmar("Otro vecino de la misma zona ve lo mismo", $fecha(3) . " 06:30:00", $pasajeDe($zB)["proximo"] ?? null);
peticion("POST", "/api-recoleccion/recolecciones/$idR1/cancelacion", ["observacion" => "Tampoco"], $admin);
$d = $pasajeDe($zA);
afirmar("Canceladas las dos: vuelve a no haber planificadas y se avisa de la primera cancelada", ["sin_planificadas", null, $fecha(1) . " 14:00:00"], [$d["estado"] ?? null, array_key_exists("proximo", $d) ? $d["proximo"] : "falta", $d["cancelada"] ?? null]);

[$codigo, $cuerpo] = peticion("PUT", "/api-usuarios/mi-zona", ["id_zona" => 1], $zA);
$d = $pasajeDe($zA);
afirmar("La zona se puede cambiar: ahora es Ciudad Vieja", [200, "Ciudad Vieja", 1], [$codigo, $d["zona"] ?? null, $d["id_zona"] ?? null]);
[$codigo, $cuerpo] = peticion("PUT", "/api-usuarios/mi-zona", ["id_zona" => null], $zA);
afirmar("Y se puede sacar con null: vuelve a sin_zona", [200, "sin_zona"], [$codigo, $pasajeDe($zA)["estado"] ?? null]);
[$codigo, $cuerpo] = peticion("GET", "/api-gestion/zonas", null, $zA);
afirmar("La lista de zonas para elegir la puede leer el vecino", [200, true], [$codigo, count($cuerpo["datos"] ?? []) >= 5]);

$pdo->exec("DELETE FROM recoleccion WHERE id_camion IN (SELECT id_camion FROM camion WHERE matricula LIKE 'TPAS-%')");
$pdo->exec("DELETE FROM camion WHERE matricula LIKE 'TPAS-%'");
$pdo->exec("DELETE FROM ruta WHERE nombre LIKE 'TPAS %'");
$pdo->exec("DELETE FROM usuario WHERE email LIKE 'tpas-%@test.uy'");
foreach ($vecinosZ as $archivo) {
    if (file_exists($archivo)) {
        unlink($archivo);
    }
}
afirmar("No quedan camiones, rutas ni vecinos de prueba del pasaje", [0, 0, 0], [
    (int) $pdo->query("SELECT COUNT(*) FROM camion WHERE matricula LIKE 'TPAS-%'")->fetchColumn(),
    (int) $pdo->query("SELECT COUNT(*) FROM ruta WHERE nombre LIKE 'TPAS %'")->fetchColumn(),
    (int) $pdo->query("SELECT COUNT(*) FROM usuario WHERE email LIKE 'tpas-%@test.uy'")->fetchColumn(),
]);
afirmar("Ninguno de los vecinos del seed tiene zona (los datos de demostracion no se ensucian)", 0, (int) $pdo->query("SELECT COUNT(*) FROM usuario WHERE id_zona IS NOT NULL")->fetchColumn());
