<?php

// Operario con trabajo real (extras, parte 12): stock del centro, recepcion, alcance por centro y permisos.

require_once __DIR__ . "/../api-gestion/models/Stock.php";
require_once __DIR__ . "/../api-gestion/models/AlcanceCentro.php";

titulo("26. Operario: stock del centro (calculos puros)");

$movs = [
    ["id_tipo_residuo" => 2, "sentido" => "entrada", "kg" => "100.50"],
    ["id_tipo_residuo" => 2, "sentido" => "salida",  "kg" => "40.25"],
    ["id_tipo_residuo" => 6, "sentido" => "entrada", "kg" => "10"],
    ["id_tipo_residuo" => 6, "sentido" => "salida",  "kg" => "10"],
];
afirmar("El stock es entradas menos salidas por tipo, sin restos de coma flotante", [2 => 60.25, 6 => 0.0], Stock::saldos($movs));
afirmar("Sin movimientos no hay saldos", [], Stock::saldos([]));
afirmar("El total suma los tipos", 60.25, Stock::total(Stock::saldos($movs)));
afirmar("0,1 + 0,2 kg dan 0,3 (se redondea a 2 decimales)", [1 => 0.3], Stock::saldos([
    ["id_tipo_residuo" => 1, "sentido" => "entrada", "kg" => "0.1"], ["id_tipo_residuo" => 1, "sentido" => "entrada", "kg" => "0.2"],
]));

afirmar("La ocupacion es kg sobre capacidad, con un decimal", 33.8, Stock::ocupacion(84500.0, 250000));
afirmar("Sin capacidad cargada no hay ocupacion", null, Stock::ocupacion(100.0, null));
afirmar("Capacidad 0 tampoco", null, Stock::ocupacion(100.0, 0));
foreach ([[null, "sin_capacidad"], [0.0, "normal"], [80.0, "normal"], [80.1, "alerta"], [99.9, "alerta"], [100.0, "lleno"], [120.5, "lleno"]] as [$pct, $nivel]) {
    afirmar("Ocupacion " . var_export($pct, true) . " % es nivel " . $nivel, $nivel, Stock::nivel($pct));
}
afirmar("El aviso salta al PASAR del 80 % (con un decimal), no al llegar", ["normal", "alerta"], [Stock::nivel(Stock::ocupacion(32000.0, 40000)), Stock::nivel(Stock::ocupacion(32040.0, 40000))]);

foreach ([["12", 12.0], ["12.5", 12.5], [12, 12.0], [0.25, 0.25], ["1000000", 1000000.0]] as [$valor, $esperado]) {
    afirmar("Un peso valido: " . var_export($valor, true), $esperado, Stock::kgValidos($valor));
}
foreach ([0, "0", -5, "-5", "abc", "", null, "1.234", 1.234, "1e3", true, [], "1000001", 2000000, "12,5", " "] as $malo) {
    afirmar("Un peso invalido: " . json_encode($malo), null, Stock::kgValidos($malo));
}

afirmar("Retirar menos de lo que hay: sin error", null, Stock::errorDeRetiro(["motivo" => "venta", "kg" => "50"], 60.25));
afirmar("Retirar todo lo que hay se puede", null, Stock::errorDeRetiro(["motivo" => "traslado", "kg" => 60.25], 60.25));
afirmar("Retirar mas de lo que hay: error con las cifras", "No hay tanto stock de ese tipo: hay 60,25 kg y se piden 61 kg", Stock::errorDeRetiro(["motivo" => "venta", "kg" => 61], 60.25));
afirmar("Un motivo inventado", true, str_starts_with((string) Stock::errorDeRetiro(["motivo" => "regalo", "kg" => 1], 10.0), "Motivo inválido"));
afirmar("Un peso de 0 no es un retiro", true, Stock::errorDeRetiro(["motivo" => "venta", "kg" => 0], 10.0) !== null);
afirmar("Retirar de un tipo sin stock", true, Stock::errorDeRetiro(["motivo" => "venta", "kg" => 1], 0.0) !== null);

$entrada = ["motivo" => "recepcion", "sentido" => "entrada", "kg" => "100.00"];
afirmar("Corregir una entrada con stock de sobra: sin error", null, Stock::errorDeCorreccion($entrada, false, 100.0));
afirmar("Corregir una entrada cuando ya salio parte: error", true, Stock::errorDeCorreccion($entrada, false, 40.0) !== null);
afirmar("Corregir una salida siempre se puede (devuelve stock)", null, Stock::errorDeCorreccion(["motivo" => "venta", "sentido" => "salida", "kg" => "5"], false, 0.0));
afirmar("Un movimiento ya corregido no se corrige otra vez", "Ese movimiento ya fue corregido", Stock::errorDeCorreccion($entrada, true, 500.0));
afirmar("Una correccion no se corrige", true, str_starts_with((string) Stock::errorDeCorreccion(["motivo" => "correccion", "sentido" => "salida", "kg" => "1"], false, 9.0), "Una corrección"));
afirmar("El inverso de una entrada es una salida y al reves", ["salida", "entrada"], [Stock::sentidoInverso("entrada"), Stock::sentidoInverso("salida")]);

titulo("26 bis. Operario: lineas de una recepcion");

[$lineas, $error] = Stock::lineasDeRecepcion([["id_tipo_residuo" => 6, "kg" => "815.95"], ["id_tipo_residuo" => "3", "kg" => 210.5]], [1, 2, 3, 6]);
afirmar("Dos tipos validos se normalizan", [[["id_tipo_residuo" => 6, "kg" => 815.95], ["id_tipo_residuo" => 3, "kg" => 210.5]], null], [$lineas, $error]);
foreach ([
    "Sin lineas"                     => [[], [1]],
    "No es una lista"                => [["a" => ["id_tipo_residuo" => 1, "kg" => 1]], [1]],
    "Un tipo que no existe"          => [[["id_tipo_residuo" => 99, "kg" => 1]], [1]],
    "Un tipo repetido"               => [[["id_tipo_residuo" => 1, "kg" => 1], ["id_tipo_residuo" => 1, "kg" => 2]], [1]],
    "Un peso de cero"                => [[["id_tipo_residuo" => 1, "kg" => 0]], [1]],
    "Un peso con texto"              => [[["id_tipo_residuo" => 1, "kg" => "mucho"]], [1]],
    "Una linea que no es un objeto"  => [[5], [1]],
    "Demasiadas lineas"              => [array_map(fn($i) => ["id_tipo_residuo" => 1, "kg" => 1], range(1, 21)), [1]],
] as $nombre => [$entradaMala, $validos]) {
    afirmar("$nombre: error", true, Stock::lineasDeRecepcion($entradaMala, $validos)[1] !== null);
}
afirmar("Nulo no es una lista de lineas", true, Stock::lineasDeRecepcion(null, [1])[1] !== null);
afirmar("El numero de recoleccion tiene 4 cifras", "REC-0003", Stock::numeroDeRecoleccion(3));
afirmar("Los kg se muestran sin ceros de mas", ["12 kg", "12,5 kg", "1.234,56 kg"], [Stock::kgTexto(12.0), Stock::kgTexto(12.5), Stock::kgTexto(1234.56)]);

titulo("26 ter. Operario: alcance por centro");

$operario = ["rol" => "Operario", "id_centro" => "1"];
afirmar("El Operario llega a su centro", true, AlcanceCentro::puede($operario, 1));
afirmar("y no al de al lado", false, AlcanceCentro::puede($operario, 2));
afirmar("Un Operario sin centro no llega a ninguno", [false, false, null], [AlcanceCentro::puede(["rol" => "Operario", "id_centro" => null], 1), AlcanceCentro::puede(["rol" => "Operario"], 0), AlcanceCentro::centroDelUsuario(["rol" => "Operario", "id_centro" => null])]);
afirmar("El centro del Operario sale como entero", 1, AlcanceCentro::centroDelUsuario($operario));
afirmar("El Administrador llega a todos y no esta atado a uno", [true, true, null], [AlcanceCentro::puede(["rol" => "Administrador"], 1), AlcanceCentro::puede(["rol" => "Administrador"], 99), AlcanceCentro::centroDelUsuario(["rol" => "Administrador", "id_centro" => 3])]);
foreach (["Cuadrilla", "Vecino", "", "Otro"] as $rol) {
    afirmar("El rol '$rol' no llega a ningun centro", false, AlcanceCentro::puede(["rol" => $rol, "id_centro" => 1], 1));
}

titulo("26 quater. Operario: matriz de permisos nueva");

$tabla = permisosDe("api-gestion");
$personal = ["Administrador", "Operario"];
foreach ([["centros/stock", "GET"], ["centros/movimientos", "GET"], ["centros/movimientos", "POST"], ["centros/correccion", "POST"],
          ["maquinaria/estado", "POST"], ["maquinaria/historial", "GET"], ["recepciones", "GET"], ["recepciones", "POST"],
          ["estadisticas/centro", "GET"]] as [$recurso, $metodo]) {
    afirmar("$metodo $recurso: lo pueden hacer el Administrador y el Operario", $personal, $tabla[$recurso][$metodo] ?? null);
}
foreach ([["centros/stock", "POST"], ["centros/movimientos", "DELETE"], ["centros/movimientos", "PUT"], ["recepciones", "DELETE"], ["recepciones", "PUT"], ["maquinaria/estado", "PUT"]] as [$recurso, $metodo]) {
    afirmar("$metodo $recurso: no lo puede hacer nadie", [], $tabla[$recurso][$metodo] ?? null);
}
afirmar("El Operario sigue sin poder crear ni borrar centros ni maquinaria", [["Administrador"], ["Administrador"], ["Administrador"]], [$tabla["centros"]["POST"], $tabla["centros"]["DELETE"], $tabla["maquinaria"]["POST"]]);
