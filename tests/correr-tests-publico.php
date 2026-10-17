<?php

// Datos publicos de la landing (parte 8): se leen sin sesion, traen solo agregados y lugares publicos,
// y coinciden con lo que ve el administrador. Se ejecuta dentro de correr-tests.php.

titulo("22e. Datos publicos de la landing");

[$codigo, $cuerpo] = peticion("GET", "/api-gestion/publico/mapa");
afirmar("El mapa publico se lee sin sesion (200)", 200, $codigo);
$publico = $cuerpo["datos"] ?? [];
foreach (["Administrador" => $admin, "Vecino" => $vecino] as $rol => $sesion) {
    [$codigoRol] = peticion("GET", "/api-gestion/publico/mapa", null, $sesion);
    afirmar("Tambien lo lee un $rol (200)", 200, $codigoRol);
}

$clavesContenedor = ["codigo", "direccion", "latitud", "longitud", "estado", "tipo_residuo", "zona"];
afirmar("Cada contenedor trae solo los campos publicos", [],
    array_values(array_filter($publico["contenedores"] ?? [], fn ($c) => array_keys($c) !== $clavesContenedor)));
afirmar("Cada centro trae solo nombre, direccion y ubicacion", [],
    array_values(array_filter($publico["centros"] ?? [], fn ($c) => array_keys($c) !== ["nombre", "direccion", "latitud", "longitud"])));
$crudo = (string) json_encode($publico);
afirmar("No hay ningun correo, telefono ni id de usuario en la respuesta", false,
    str_contains($crudo, "@") || str_contains($crudo, "id_usuario") || str_contains($crudo, "telefono"));
afirmar("Los contenedores retirados no se muestran", [], array_values(array_filter($publico["contenedores"] ?? [], fn ($c) => $c["estado"] === "retirado")));

[$codigo, $cuerpo] = peticion("GET", "/api-gestion/contenedores", null, $admin);
$enServicio = array_filter($cuerpo["datos"] ?? [], fn ($c) => $c["estado"] !== "retirado");
afirmar("Son los mismos contenedores en servicio que ve el administrador", count($enServicio), count($publico["contenedores"] ?? []));
afirmar("Y el resumen cuenta lo mismo", [count($enServicio), count(array_filter($enServicio, fn ($c) => $c["estado"] === "lleno"))],
    [$publico["resumen"]["contenedores"] ?? null, $publico["resumen"]["llenos"] ?? null]);
afirmar("Disponibles + llenos + en mantenimiento = en servicio", $publico["resumen"]["contenedores"] ?? -1,
    ($publico["resumen"]["disponibles"] ?? 0) + ($publico["resumen"]["llenos"] ?? 0) + ($publico["resumen"]["en_mantenimiento"] ?? 0));
[$codigo, $cuerpo] = peticion("GET", "/api-gestion/centros", null, $admin);
afirmar("Los centros publicos son los activos", count(array_filter($cuerpo["datos"] ?? [], fn ($c) => $c["estado"] === "activo")), count($publico["centros"] ?? []));
[$codigo, $cuerpo] = peticion("GET", "/api-gestion/incidencias?estado=resuelta", null, $admin);
afirmar("Las incidencias resueltas del resumen son las del listado", count($cuerpo["datos"] ?? []), $publico["resumen"]["incidencias_resueltas"] ?? null);

[$codigo] = peticion("POST", "/api-gestion/publico/mapa", []);
afirmar("No se escribe en lo publico sin sesion (401)", 401, $codigo);
[$codigo] = peticion("POST", "/api-gestion/publico/mapa", [], $admin);
afirmar("Ni con sesion de administrador (403)", 403, $codigo);
[$codigo] = peticion("GET", "/api-gestion/publico/inventado");
afirmar("Un recurso publico que no existe: 404", 404, $codigo);
[$codigo] = peticion("GET", "/api-gestion/publico");
afirmar("Sin indicar cual: 404", 404, $codigo);
