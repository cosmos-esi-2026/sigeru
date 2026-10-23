<?php

// Parte de correr-tests.php (se incluye desde alli y usa sus funciones y sus sesiones).
// Plan de rutas para la defensa (22/10/2026). Una zona propia TLIM llena hasta el tope; se borra todo al final.

require_once __DIR__ . "/../api-gestion/models/Limites.php";

titulo("47. Tope de contenedores por zona (municipio)");

$pdo->exec("DELETE FROM contenedor WHERE codigo LIKE 'TLIM-%'");
$pdo->exec("DELETE FROM zona WHERE nombre LIKE 'TLIM Zona%'");

$pdo->prepare("INSERT INTO zona (nombre, descripcion) VALUES (?, 'zona de prueba del tope')")->execute(["TLIM Zona $sufijo"]);
$idZonaLlena = (int) $pdo->lastInsertId();
$pdo->prepare("INSERT INTO zona (nombre, descripcion) VALUES (?, 'otra zona de prueba del tope')")->execute(["TLIM Zona B $sufijo"]);
$idZonaOtra = (int) $pdo->lastInsertId();
$tope = Limites::MAX_CONTENEDORES_POR_ZONA;

// Los primeros (tope - 1) directo en la base, que es mas rapido; el ultimo y el que se pasa, por la API.
$insertar = $pdo->prepare("INSERT INTO contenedor (codigo, direccion, latitud, longitud, capacidad, estado, id_zona, id_tipo_residuo) VALUES (?, ?, -34.9000000, -56.2000000, 800, 'disponible', ?, 1)");
for ($i = 1; $i < $tope; $i++) {
    $insertar->execute(["TLIM-$sufijo-$i", "Calle de prueba $i", $idZonaLlena]);
}
$alta = fn(string $codigo, int $zona) => peticion("POST", "/api-gestion/contenedores", ["codigo" => $codigo, "direccion" => "Calle de prueba", "tipo_residuo" => "Mixto", "id_zona" => $zona], $admin);

[$codigo] = $alta("TLIM-$sufijo-$tope", $idZonaLlena);
afirmar("El contenedor numero $tope de la zona entra: 201", 201, $codigo);
[$codigo, $cuerpo] = $alta("TLIM-$sufijo-" . ($tope + 1), $idZonaLlena);
afirmar("El siguiente no entra: 409 con un mensaje que dice el maximo y la zona", [409, true, true], [$codigo, str_contains($cuerpo["mensaje"] ?? "", (string) $tope), str_contains($cuerpo["mensaje"] ?? "", "TLIM Zona $sufijo")]);
afirmar("Y no se creo", 0, (int) $pdo->query("SELECT COUNT(*) FROM contenedor WHERE codigo = 'TLIM-$sufijo-" . ($tope + 1) . "'")->fetchColumn());
[$codigo] = $alta("TLIM-$sufijo-otra", $idZonaOtra);
afirmar("Otra zona con lugar sigue aceptando altas: 201", 201, $codigo);
afirmar("La zona llena tiene exactamente el tope en servicio", $tope, (int) $pdo->query("SELECT COUNT(*) FROM contenedor WHERE id_zona = $idZonaLlena AND estado <> 'retirado'")->fetchColumn());

$idOtra = (int) $pdo->query("SELECT id_contenedor FROM contenedor WHERE codigo = 'TLIM-$sufijo-otra'")->fetchColumn();
[$codigo, $cuerpo] = peticion("PUT", "/api-gestion/contenedores/$idOtra", ["id_zona" => $idZonaLlena], $admin);
afirmar("Mover un contenedor a la zona llena tampoco se puede: 409", [409, true], [$codigo, str_contains($cuerpo["mensaje"] ?? "", (string) $tope)]);
afirmar("El contenedor se quedo donde estaba", $idZonaOtra, (int) $pdo->query("SELECT id_zona FROM contenedor WHERE id_contenedor = $idOtra")->fetchColumn());
[$codigo] = peticion("PUT", "/api-gestion/contenedores/$idOtra", ["direccion" => "Otra calle de prueba"], $admin);
afirmar("Cambiar un dato que no es la zona no se ve afectado por el tope: 200", 200, $codigo);

$idUno = (int) $pdo->query("SELECT id_contenedor FROM contenedor WHERE codigo = 'TLIM-$sufijo-1'")->fetchColumn();
[$codigo] = peticion("DELETE", "/api-gestion/contenedores/$idUno", ["motivo" => "prueba del tope"], $admin);
afirmar("Retirar uno de la zona llena: 200", 200, $codigo);
[$codigo] = $alta("TLIM-$sufijo-" . ($tope + 1), $idZonaLlena);
afirmar("Con uno menos en servicio, ahora si entra otro: 201 (los retirados no cuentan)", 201, $codigo);
[$codigo] = peticion("PUT", "/api-gestion/contenedores/$idUno", ["estado" => "disponible"], $admin);
afirmar("Volver a poner en servicio al retirado en una zona llena: 409", 409, $codigo);
peticion("DELETE", "/api-gestion/contenedores/$idOtra", ["motivo" => "prueba del tope"], $admin);
[$codigo] = peticion("PUT", "/api-gestion/contenedores/$idOtra", ["id_zona" => $idZonaLlena], $admin);
afirmar("Mover a la zona llena un contenedor que esta retirado no suma al cupo: 200", 200, $codigo);

$pdo->exec("DELETE FROM contenedor WHERE codigo LIKE 'TLIM-%'");
$pdo->exec("DELETE FROM zona WHERE nombre LIKE 'TLIM Zona%'");
afirmar("Los contenedores y las zonas de prueba se borraron", [0, 0], [
    (int) $pdo->query("SELECT COUNT(*) FROM contenedor WHERE codigo LIKE 'TLIM-%'")->fetchColumn(),
    (int) $pdo->query("SELECT COUNT(*) FROM zona WHERE nombre LIKE 'TLIM Zona%'")->fetchColumn(),
]);
