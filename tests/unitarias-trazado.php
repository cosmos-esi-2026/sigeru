<?php

// Trazado por calles (plan de rutas para la defensa, bloque 3): las piezas puras y la cascada de servicios con respuestas simuladas (sin red).

require_once __DIR__ . "/../api-recoleccion/models/Trazado.php";

titulo("48. Trazado: firma, polilinea, tramos y calidad");

$jsonRutas = __DIR__ . "/../database/rutas_defensa.json";
if (is_file($jsonRutas)) {
    $rutasDefensa = json_decode((string) file_get_contents($jsonRutas), true);
    $iguales = 0;
    foreach ($rutasDefensa as $r) {
        $paradas = array_map(fn($p) => ["latitud" => $p["lat"], "longitud" => $p["lon"]], $r["paradas"]);
        $iguales += Trazado::firma($paradas) === $r["firma"] ? 1 : 0;
    }
    afirmar("La firma de PHP coincide con la que calculo Python para las " . count($rutasDefensa) . " rutas del seed de defensa", count($rutasDefensa), $iguales);
}
$dosParadas = [["latitud" => "-34.9000000", "longitud" => "-56.2000000"], ["latitud" => "-34.9100000", "longitud" => "-56.2100000"]];
$otraParada = $dosParadas;
$otraParada[1]["longitud"] = "-56.2100001";
afirmar("La firma cambia con un decimal de la septima cifra, y no con el mismo dato", [false, true], [Trazado::firma($dosParadas) === Trazado::firma($otraParada), Trazado::firma($dosParadas) === Trazado::firma($dosParadas)]);
afirmar("Una firma es un sha256 (64 caracteres hexadecimales)", 1, preg_match('/^[0-9a-f]{64}$/', Trazado::firma($dosParadas)));

// Vector clasico de la polilinea de Google (precision 5): (38.5, -120.2), (40.7, -120.95), (43.252, -126.453)
afirmar("Polilinea codificada: el ejemplo clasico se decodifica a [lon, lat]", [[-120.2, 38.5], [-120.95, 40.7], [-126.453, 43.252]], Trazado::decodificarPolilinea("_p~iF~ps|U_ulLnnqC_mqNvxq`@", 5));
$codificar = function (array $puntos, int $precision = 6): string {
    $salida = "";
    $f = 10 ** $precision;
    [$la, $lo] = [0, 0];
    $numero = function (int $n): string {
        $n = $n < 0 ? ~($n << 1) : $n << 1;
        $texto = "";
        while ($n >= 0x20) {
            $texto .= chr((0x20 | ($n & 0x1F)) + 63);
            $n >>= 5;
        }
        return $texto . chr($n + 63);
    };
    foreach ($puntos as [$lon, $lat]) {
        $salida .= $numero((int) round($lat * $f) - $la) . $numero((int) round($lon * $f) - $lo);
        [$la, $lo] = [(int) round($lat * $f), (int) round($lon * $f)];
    }
    return $salida;
};
$puntosMdeo = [[-56.2000, -34.9000], [-56.1995, -34.9004], [-56.1990, -34.9011], [-56.2010, -34.9015]];
afirmar("Polilinea de precision 6: ida y vuelta de cuatro puntos de Montevideo", $puntosMdeo, Trazado::decodificarPolilinea($codificar($puntosMdeo), 6));
afirmar("Una polilinea truncada se rechaza", "polilinea truncada", (function () {
    try {
        Trazado::decodificarPolilinea("_p~iF~ps|U_ulLnnqC_mqNvxq", 5);
        return "no fallo";
    } catch (RuntimeException $e) {
        return $e->getMessage();
    }
})());

$veinte = array_map(fn($i) => ["latitud" => -34.9 - $i * 0.001, "longitud" => -56.2], range(0, 19));
afirmar("Tramos de a 10 puntos que se solapan en uno: 20 paradas son 3 pedidos (10, 10 y 2)", [10, 10, 2], array_map("count", Trazado::tramos($veinte, 10)));
afirmar("El ultimo punto de un tramo es el primero del siguiente", [true, true], [
    Trazado::tramos($veinte, 10)[0][9] === Trazado::tramos($veinte, 10)[1][0], Trazado::tramos($veinte, 10)[1][9] === Trazado::tramos($veinte, 10)[2][0],
]);
afirmar("Casos borde: 10 paradas un tramo; 11 paradas dos; 2 paradas uno; 1 parada ninguno", [[10], [10, 2], [2], []], [
    array_map("count", Trazado::tramos(array_slice($veinte, 0, 10), 10)), array_map("count", Trazado::tramos(array_slice($veinte, 0, 11), 10)),
    array_map("count", Trazado::tramos(array_slice($veinte, 0, 2), 10)), Trazado::tramos(array_slice($veinte, 0, 1), 10),
]);

$cuerpo = json_decode(Trazado::cuerpoValhalla(array_slice($veinte, 0, 3)), true);
afirmar("El pedido a Valhalla es de camion, en kilometros, con polilinea de precision 6 y las dimensiones supuestas del recolector", ["truck", "polyline6", 3, 9.0, "kilometers"], [
    $cuerpo["costing"], $cuerpo["shape_format"], count($cuerpo["locations"]), (float) $cuerpo["costing_options"]["truck"]["length"], $cuerpo["directions_options"]["units"],
]);
afirmar("La URL de OSRM lleva longitud,latitud separadas por punto y coma", "https://osrm.test/route/v1/driving/-56.200000,-34.900000;-56.210000,-34.910000?overview=full&geometries=geojson&steps=false",
    Trazado::urlOsrm("https://osrm.test/route/v1/driving/", $dosParadas));

afirmar("Distancia: un grado de latitud son 111195 m", 111195, (int) round(Trazado::distanciaM(0, 0, 1, 0)));
$recta = Trazado::recta($dosParadas);
afirmar("La recta de emergencia une las paradas, dice que es recta y no inventa una duracion ni un servicio", [Trazado::TIPO_RECTA, [[-56.2, -34.9], [-56.21, -34.91]], null, null, 1440], [
    $recta["tipo"], $recta["coordenadas"], $recta["duracion_s"], $recta["servicio"], (int) round($recta["distancia_m"] / 10) * 10,
]);

$linea = [[-56.2000, -34.9000], [-56.2000, -34.9100]];
$cercanas = [["latitud" => -34.9050, "longitud" => -56.20005], ["latitud" => -34.9100, "longitud" => -56.2000]];
afirmar("Calidad: paradas pegadas a la linea (5 m) estan bien", null, Trazado::errorDeCalidad($cercanas, $linea));
$lejanas = [["latitud" => -34.9050, "longitud" => -56.1900]];
afirmar("Calidad: una parada a casi 1 km de la linea se descarta, y el mensaje dice cuanto", true, str_starts_with((string) Trazado::errorDeCalidad($lejanas, $linea), "una parada queda a "));
afirmar("Calidad: una linea sin puntos se descarta", "la linea no tiene puntos", Trazado::errorDeCalidad($cercanas, []));

titulo("48 bis. Trazado: la cascada de servicios con respuestas simuladas");

$paradas = array_map(fn($i) => ["latitud" => -34.9 - $i * 0.002, "longitud" => -56.2 - $i * 0.0005], range(0, 19));
$respuestaValhalla = function (string $cuerpo) use ($codificar): array {
    $locs = json_decode($cuerpo, true)["locations"];
    $legs = [];
    for ($i = 0; $i < count($locs) - 1; $i++) {
        $legs[] = ["shape" => $codificar([[$locs[$i]["lon"], $locs[$i]["lat"]], [$locs[$i + 1]["lon"], $locs[$i + 1]["lat"]]])];
    }
    return [200, json_encode(["trip" => ["legs" => $legs, "summary" => ["length" => 0.25 * count($legs), "time" => 60 * count($legs)]]])];
};
$respuestaOsrm = function (string $url) use ($paradas): array {
    $coords = array_map(fn($p) => [$p["longitud"], $p["latitud"]], $paradas);
    return [200, json_encode(["code" => "Ok", "routes" => [["distance" => 4750.0, "duration" => 1140.0, "geometry" => ["type" => "LineString", "coordinates" => $coords]]]])];
};

$llamadas = [];
$pausas = [];
$http = function (string $metodo, string $url, ?string $cuerpo) use (&$llamadas, $respuestaValhalla): array {
    $llamadas[] = [$metodo, $url];
    return $respuestaValhalla((string) $cuerpo);
};
$dormir = function (int $ms) use (&$pausas): void {
    $pausas[] = $ms;
};
// Desde el 30/10/2026 el modo por defecto es recta: las pruebas de los servicios piden el modo calles a proposito.
afirmar("Por defecto (sin variable de entorno) el trazado es en linea recta y no pide nada a internet", Trazado::TIPO_RECTA, Trazado::modo());
putenv("SIGERU_TRAZADO_MODO=calles");
afirmar("Con SIGERU_TRAZADO_MODO=calles se piden las calles a los servicios", Trazado::TIPO_CALLES, Trazado::modo());
$r = Trazado::calcular($paradas, $http, $dormir);
afirmar("Valhalla responde: 20 paradas son 3 pedidos POST al primer servicio, y la ruta queda trazada por calles", [3, "POST", Trazado::TIPO_CALLES, "valhalla-truck", 20, []], [
    count($llamadas), $llamadas[0][0], $r["tipo"], $r["servicio"], count($r["coordenadas"]), $r["intentos"],
]);
afirmar("La distancia y la duracion son la suma de los tramos (19 tramos de 0,25 km y 60 s)", [4750, 1140], [$r["distancia_m"], $r["duracion_s"]]);
afirmar("Entre un pedido y el siguiente se espera al menos 1 segundo (politica: un pedido por segundo)", [2, true], [count($pausas), min($pausas ?: [0]) >= 1000]);
afirmar("El primer punto de la linea es la primera parada y el ultimo la ultima", [[-56.2, -34.9], [round(-56.2 - 19 * 0.0005, 6), round(-34.9 - 19 * 0.002, 6)]], [$r["coordenadas"][0], end($r["coordenadas"])]);

$llamadas = [];
$httpCae = function (string $metodo, string $url, ?string $cuerpo) use (&$llamadas, $respuestaOsrm): array {
    $llamadas[] = $url;
    return str_contains($url, "valhalla") ? [500, "error"] : $respuestaOsrm($url);
};
$r = Trazado::calcular($paradas, $httpCae, $dormir);
afirmar("Si Valhalla falla, se pasa a OSRM de FOSSGIS y se anota por que fallo el primero", ["osrm-fossgis-auto", Trazado::TIPO_CALLES, 1, true], [
    $r["servicio"], $r["tipo"], count($r["intentos"]), str_contains($r["intentos"][0] ?? "", "HTTP 500"),
]);

$httpTodoCae = fn(string $m, string $u, ?string $c) => [0, ""];
$r = Trazado::calcular($paradas, $httpTodoCae, $dormir);
afirmar("Si ninguno responde, cae a linea recta con un aviso y el detalle de cada intento", [Trazado::TIPO_RECTA, 3, true, true], [
    $r["tipo"], count($r["intentos"]), str_contains($r["aviso"], "línea recta"), str_contains($r["intentos"][0], "sin conexion"),
]);
$httpMalo = fn(string $m, string $u, ?string $c) => [200, "<html>no soy json</html>"];
afirmar("Una respuesta que no es JSON tambien cuenta como fallo", Trazado::TIPO_RECTA, Trazado::calcular($paradas, $httpMalo, $dormir)["tipo"]);

$httpLejos = function (string $m, string $u, ?string $c): array {
    return [200, json_encode(["code" => "Ok", "routes" => [["distance" => 10.0, "duration" => 5.0, "geometry" => ["type" => "LineString", "coordinates" => [[-56.0, -34.5], [-56.01, -34.51]]]]]])];
};
$r = Trazado::calcular($paradas, $httpLejos, $dormir);
afirmar("Una respuesta con una linea que no pasa por las paradas se descarta (y se cae a recta)", [Trazado::TIPO_RECTA, true], [$r["tipo"], str_contains(implode(" ", $r["intentos"]), "una parada queda a")]);

putenv("SIGERU_TRAZADO_MODO=recta");
$llamadas = [];
$r = Trazado::calcular($paradas, function () use (&$llamadas) {
    $llamadas[] = 1;
    return [200, "{}"];
}, $dormir);
putenv("SIGERU_TRAZADO_MODO");
afirmar("Con SIGERU_TRAZADO_MODO=recta no se llama a ningun servicio externo", [0, Trazado::TIPO_RECTA], [count($llamadas), $r["tipo"]]);
afirmar("Con menos de 2 paradas no hay que trazar: recta y aviso, sin pedidos", [Trazado::TIPO_RECTA, true], [Trazado::calcular([$paradas[0]], $httpTodoCae, $dormir)["tipo"], str_contains(Trazado::calcular([], $httpTodoCae, $dormir)["aviso"], "al menos 2")]);

putenv("SIGERU_VALHALLA_URL=https://otro.test/ruta");
afirmar("Las URL de los servicios no van fijas: se cambian por variable de entorno", "https://otro.test/ruta", Trazado::proveedores()[0]["url"]);
putenv("SIGERU_VALHALLA_URL");
afirmar("Sin la variable vuelve la URL publica de FOSSGIS", "https://valhalla1.openstreetmap.de/route", Trazado::proveedores()[0]["url"]);
afirmar("El User-Agent identifica la aplicacion y no lleva correos", [true, false], [str_contains(Trazado::USER_AGENT, "SiGeRU"), str_contains(Trazado::USER_AGENT, "@")]);
