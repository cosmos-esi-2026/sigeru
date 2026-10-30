<?php

// Sugerencia de asignacion por municipio y turno (parte 26, 24/10/2026; hallazgo H-04 de la evaluacion heuristica). Casos hechos a mano, sin base de datos.

require_once __DIR__ . "/../api-recoleccion/models/FlujoRecoleccion.php";
require_once __DIR__ . "/../api-recoleccion/models/Asignacion.php";

titulo("51 ter. Sugerencia por municipio y turno");

// Ocho municipios, una ruta por municipio (el id de la ruta es 100 + el del municipio), una cuadrilla por municipio, en el mismo orden.
$rutasZ = array_map(fn($z) => ["id_ruta" => 100 + $z, "id_zona" => $z, "hora_inicio" => "06:30"], range(1, 8));
$cuadZ = array_map(fn($z) => ["id_cuadrilla" => $z, "id_zona" => $z, "turno" => "matutino"], range(1, 8));
$camZ = array_map(fn($i) => ["id_camion" => 50 + $i], range(1, 8));
$par = fn(array $s) => array_map(fn($p) => [$p["id_ruta"], $p["id_cuadrilla"]], $s["propuestas"]);

afirmar("Cada ruta recibe la cuadrilla de su municipio", array_map(fn($z) => [100 + $z, $z], range(1, 8)), $par(Asignacion::sugerir($rutasZ, $cuadZ, $camZ)));
afirmar("Aunque las cuadrillas lleguen en otro orden, la de cada municipio sigue siendo la suya (antes se corrian un lugar)", array_map(fn($z) => [100 + $z, $z], range(1, 8)),
    $par(Asignacion::sugerir($rutasZ, array_reverse($cuadZ), $camZ)));
$s = Asignacion::sugerir($rutasZ, $cuadZ, $camZ);
afirmar("Del propio municipio y en turno: ningun aviso", [0, 0], [count(array_filter($s["propuestas"], fn($p) => $p["fuera_de_zona"])), count(array_filter($s["propuestas"], fn($p) => $p["turno_distinto"]))]);

// Falta una cuadrilla: la del municipio 3 esta ocupada (no viene en la lista de libres).
$libres = array_values(array_filter($cuadZ, fn($c) => $c["id_cuadrilla"] !== 3));
$s = Asignacion::sugerir($rutasZ, $libres, $camZ);
afirmar("Si la cuadrilla del municipio no esta libre, la ruta queda sin asignar y no se le roba la de otro: 7 propuestas, la ruta 103 sin cuadrilla, nada sobra", [7, [103], []], [count($s["propuestas"]), $s["rutas_sin_asignar"], $s["cuadrillas_que_sobran"]]);

// Falta la propia pero sobra una de otro municipio: se ofrece, marcada.
$rutasDos = [["id_ruta" => 301, "id_zona" => 3, "hora_inicio" => "06:00"], ["id_ruta" => 302, "id_zona" => 2, "hora_inicio" => "06:00"]];
$cuadDos = [["id_cuadrilla" => 1, "id_zona" => 1, "turno" => "matutino"], ["id_cuadrilla" => 2, "id_zona" => 2, "turno" => "matutino"]];
$s = Asignacion::sugerir($rutasDos, $cuadDos, [["id_camion" => 1], ["id_camion" => 2]]);
afirmar("Falta la cuadrilla del municipio 3 pero sobra la del 1: se ofrece a esa ruta, marcada «fuera de su municipio»; la del 2 va a su ruta",
    [[301, 1, true], [302, 2, false]], array_map(fn($p) => [$p["id_ruta"], $p["id_cuadrilla"], $p["fuera_de_zona"]], $s["propuestas"]));

// Una cuadrilla sin municipio cubre cualquiera, pero va despues de la del propio.
$s = Asignacion::sugerir([["id_ruta" => 1, "id_zona" => 5, "hora_inicio" => "07:00"]], [["id_cuadrilla" => 9, "id_zona" => null, "turno" => "matutino"], ["id_cuadrilla" => 8, "id_zona" => 5, "turno" => "matutino"]], [["id_camion" => 1]]);
afirmar("Entre una sin municipio y una del municipio de la ruta, gana la del municipio", [8, false, [9]], [$s["propuestas"][0]["id_cuadrilla"], $s["propuestas"][0]["fuera_de_zona"], $s["cuadrillas_que_sobran"]]);
$s = Asignacion::sugerir([["id_ruta" => 1, "id_zona" => 5, "hora_inicio" => "07:00"]], [["id_cuadrilla" => 9, "id_zona" => null, "turno" => "matutino"], ["id_cuadrilla" => 7, "id_zona" => 6, "turno" => "matutino"]], [["id_camion" => 1]]);
afirmar("Entre una sin municipio y una de OTRO municipio, gana la que no tiene municipio y no se marca como fuera de zona", [9, false], [$s["propuestas"][0]["id_cuadrilla"], $s["propuestas"][0]["fuera_de_zona"]]);

// Una sola cuadrilla libre y dos rutas: la primera de la lista (la mas urgente) se la lleva aunque no sea de su municipio, marcada.
$s = Asignacion::sugerir([["id_ruta" => 1, "id_zona" => 2, "hora_inicio" => "06:00"], ["id_ruta" => 2, "id_zona" => 1, "hora_inicio" => "06:00"]], [["id_cuadrilla" => 5, "id_zona" => 1, "turno" => "matutino"]], [["id_camion" => 1], ["id_camion" => 2]]);
afirmar("Una cuadrilla del municipio 1 y dos rutas: la del municipio 1 la recibe (es de su municipio) y la mas urgente queda sin cuadrilla", [[[2, 5, false]], [1]], [
    array_map(fn($p) => [$p["id_ruta"], $p["id_cuadrilla"], $p["fuera_de_zona"]], $s["propuestas"]), $s["rutas_sin_asignar"],
]);

// Camiones.
$s = Asignacion::sugerir($rutasZ, $cuadZ, []);
afirmar("Sin camiones libres no hay propuestas: las ocho rutas quedan sin asignar y las ocho cuadrillas sobran", [0, 8, 8], [count($s["propuestas"]), count($s["rutas_sin_asignar"]), count($s["cuadrillas_que_sobran"])]);
$s = Asignacion::sugerir($rutasZ, $cuadZ, array_slice($camZ, 0, 3));
afirmar("Con 3 camiones entran las 3 rutas mas urgentes, en orden, cada una con su camion", [[101, 51], [102, 52], [103, 53]], array_map(fn($p) => [$p["id_ruta"], $p["id_camion"]], $s["propuestas"]));
afirmar("Y las cinco rutas que no entran se avisan, y los camiones no sobran", [[104, 105, 106, 107, 108], []], [$s["rutas_sin_asignar"], $s["camiones_que_sobran"]]);

// Turno: solo avisa.
$s = Asignacion::sugerir([["id_ruta" => 1, "id_zona" => 1, "hora_inicio" => "22:00"]], [["id_cuadrilla" => 1, "id_zona" => 1, "turno" => "matutino"]], [["id_camion" => 1]]);
afirmar("Una ruta nocturna con una cuadrilla matutina: se propone igual, con aviso de turno", [1, true], [$s["propuestas"][0]["id_cuadrilla"], $s["propuestas"][0]["turno_distinto"]]);
afirmar("El turno de cada hora: 04:59 nocturno, 05:00 y 12:59 matutino, 13:00 y 20:59 vespertino, 21:00 y 00:00 nocturno",
    ["nocturno", "matutino", "matutino", "vespertino", "vespertino", "nocturno", "nocturno"],
    array_map([Asignacion::class, "turnoDeLaHora"], ["04:59", "05:00", "12:59", "13:00", "20:59", "21:00", "00:00"]));
afirmar("«06:30:00» tambien se entiende; una hora imposible o vacia no tiene turno", ["matutino", null, null, null], [Asignacion::turnoDeLaHora("06:30:00"), Asignacion::turnoDeLaHora("25:00"), Asignacion::turnoDeLaHora(""), Asignacion::turnoDeLaHora("mañana")]);

// Compatibilidad hacia atras: sin ningun municipio se empareja por posicion, como antes.
$s = Asignacion::sugerir([["id_ruta" => 1], ["id_ruta" => 2]], [["id_cuadrilla" => 7], ["id_cuadrilla" => 8], ["id_cuadrilla" => 9]], [["id_camion" => 3], ["id_camion" => 4]]);
afirmar("Si nadie trae municipio, se empareja en orden y no hay avisos", [[1, 7], [2, 8], [false, false]], [...array_map(fn($p) => [$p["id_ruta"], $p["id_cuadrilla"]], $s["propuestas"]), [$s["propuestas"][0]["fuera_de_zona"], $s["propuestas"][0]["turno_distinto"]]]);

// La tabla de turnos de la pantalla es la misma que la del servidor.
$js = (string) file_get_contents(__DIR__ . "/../frontend-usuarios/js/asignacion.js");
$esperada = "const TURNO_DESDE = { matutino: " . Asignacion::TURNO_DESDE["matutino"] . ", vespertino: " . Asignacion::TURNO_DESDE["vespertino"] . ", nocturno: " . Asignacion::TURNO_DESDE["nocturno"] . " };";
afirmar("asignacion.js usa la misma tabla de turnos que Asignacion::TURNO_DESDE", true, str_contains($js, $esperada));

// Validacion del municipio de una cuadrilla. Se corre en otro proceso: Campos.php de api-gestion ya esta cargado y la clase no se puede declarar dos veces.
$cuad = static function (array $d, bool $alta): ?string {
    $guion = tempnam(sys_get_temp_dir(), "cua");
    file_put_contents($guion, '<?php require ' . var_export(__DIR__ . "/../api-recoleccion/models/ValidacionCuadrilla.php", true) . '; echo json_encode(ValidacionCuadrilla::error(' . var_export($d, true) . ', ' . var_export($alta, true) . '));');
    $salida = shell_exec(escapeshellarg(PHP_BINARY) . " " . escapeshellarg($guion));
    unlink($guion);
    return json_decode((string) $salida, true);
};
afirmar("Un municipio entero, vacio o ausente es valido", [null, null, null, null], [
    $cuad(["nombre" => "Cuadrilla X", "id_zona" => 3], true), $cuad(["nombre" => "Cuadrilla X", "id_zona" => "3"], true),
    $cuad(["nombre" => "Cuadrilla X", "id_zona" => ""], true), $cuad(["nombre" => "Cuadrilla X"], true),
]);
afirmar("Un municipio que no es un numero entero positivo se rechaza", [true, true, true, true], [
    $cuad(["nombre" => "Cuadrilla X", "id_zona" => "abc"], true) !== null, $cuad(["nombre" => "Cuadrilla X", "id_zona" => 0], true) !== null,
    $cuad(["nombre" => "Cuadrilla X", "id_zona" => -2], true) !== null, $cuad(["id_zona" => [1]], false) !== null,
]);

// El esquema, la migracion y el seed de defensa dicen lo mismo.
$schema = (string) file_get_contents(__DIR__ . "/../database/schema.sql");
$migracion = (string) file_get_contents(__DIR__ . "/../database/migraciones/020-cuadrilla-municipio.sql");
afirmar("schema.sql y la migracion 020 crean cuadrilla.id_zona con su clave foranea fk_cuadrilla_zona", [true, true, true, true], [
    (bool) preg_match('/CREATE TABLE cuadrilla \((?:(?!\) ENGINE).)*id_zona\s+INT NULL(?:(?!\) ENGINE).)*fk_cuadrilla_zona/s', $schema),
    str_contains($migracion, "ADD COLUMN id_zona INT NULL"), str_contains($migracion, "fk_cuadrilla_zona"), str_contains($migracion, "information_schema"),
]);
afirmar("El seed de defensa le da municipio a las 8 cuadrillas", 8, preg_match_all("/^\\(\\d, 'Cuadrilla Municipio \\w+', 'matutino', 'activa', \\d\\)[,;]/m", (string) file_get_contents(__DIR__ . "/../database/datos_defensa.sql")));
