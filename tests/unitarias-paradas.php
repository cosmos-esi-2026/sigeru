<?php

// Los contenedores de una ruta (plan de rutas para la defensa, bloque 2): la forma del pedido PUT /rutas/{id}/contenedores. Es la parte pura de RutaParadas;
// lo que depende de la base (conflictos, zona, mantenimiento, recolecciones en curso) lo prueban las pruebas HTTP.

require_once __DIR__ . "/../api-recoleccion/models/RutaParadas.php";

titulo("49. Paradas de una ruta: forma del pedido");

$valida = fn(array $datos) => RutaParadas::errorDeLista($datos);

afirmar("Una lista valida en orden, sin mover: sin error", null, $valida(["contenedores" => [5, 3, 9]]));
afirmar("Una lista vacia es valida (es como se vacia una ruta)", null, $valida(["contenedores" => []]));
afirmar("Los ids pueden venir como texto numerico", null, $valida(["contenedores" => ["5", "3"]]));
afirmar("Con mover verdadero o falso", [null, null], [$valida(["contenedores" => [1], "mover" => true]), $valida(["contenedores" => [1], "mover" => false])]);
afirmar("Falta la clave contenedores: rechazado", true, str_contains((string) $valida([]), "«contenedores»"));
afirmar("contenedores que no es una lista (un texto, un numero, un objeto con claves): rechazado", [true, true, true], [
    $valida(["contenedores" => "1,2,3"]) !== null, $valida(["contenedores" => 7]) !== null, $valida(["contenedores" => ["a" => 1, "b" => 2]]) !== null,
]);
afirmar("Un id que no es entero positivo (cero, negativo, texto, decimal, nulo, lista): rechazado", [true, true, true, true, true, true], [
    $valida(["contenedores" => [0]]) !== null, $valida(["contenedores" => [-4]]) !== null, $valida(["contenedores" => ["abc"]]) !== null,
    $valida(["contenedores" => [1.5]]) !== null, $valida(["contenedores" => [null]]) !== null, $valida(["contenedores" => [[1]]]) !== null,
]);
afirmar("Ids repetidos: rechazado con un mensaje claro", "Hay contenedores repetidos en la lista", $valida(["contenedores" => [4, 8, 4]]));
afirmar("«4» y 4 cuentan como repetidos", "Hay contenedores repetidos en la lista", $valida(["contenedores" => [4, "4"]]));

$tope = Limites::MAX_CONTENEDORES_POR_RUTA;
afirmar("Justo el tope de paradas es valido", null, $valida(["contenedores" => range(1, $tope)]));
afirmar("Una mas que el tope: rechazado con el mensaje del tope", Limites::mensajeRutaLlena(), $valida(["contenedores" => range(1, $tope + 1)]));
afirmar("mover que no es booleano (1, «si», null): rechazado", [true, true, true], [
    $valida(["contenedores" => [1], "mover" => 1]) !== null, $valida(["contenedores" => [1], "mover" => "si"]) !== null, $valida(["contenedores" => [1], "mover" => null]) !== null,
]);
afirmar("idsDe devuelve enteros en el mismo orden", [7, 2, 9], RutaParadas::idsDe(["contenedores" => ["7", 2, "9"]]));
