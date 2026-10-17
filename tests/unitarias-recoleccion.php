<?php

// Parte de unitarias.php (se incluye desde alli y usa su afirmar() y su titulo()).
// Las reglas de las recolecciones que no necesitan base de datos ni servidor.

titulo("15. Recolecciones: el flujo de estados");

afirmar("El numero de recoleccion lleva cuatro cifras", "REC-0042", FlujoRecoleccion::numero(42));

$acciones = ["iniciar", "vaciado", "finalizar", "cancelar"];
$matriz = [];
foreach (FlujoRecoleccion::ESTADOS as $estado) {
    foreach ($acciones as $accion) {
        $matriz[$estado][$accion] = FlujoRecoleccion::puede($accion, $estado);
    }
}
afirmar("Que se puede hacer en cada estado", [
    "planificada" => ["iniciar" => true,  "vaciado" => false, "finalizar" => false, "cancelar" => true],
    "en_curso"    => ["iniciar" => false, "vaciado" => true,  "finalizar" => true,  "cancelar" => true],
    "finalizada"  => ["iniciar" => false, "vaciado" => false, "finalizar" => false, "cancelar" => false],
    "cancelada"   => ["iniciar" => false, "vaciado" => false, "finalizar" => false, "cancelar" => false],
], $matriz);
afirmar("A donde lleva cada accion", ["en_curso", "en_curso", "finalizada", "cancelada"], [
    FlujoRecoleccion::estadoTras("iniciar", "planificada"), FlujoRecoleccion::estadoTras("vaciado", "en_curso"),
    FlujoRecoleccion::estadoTras("finalizar", "en_curso"), FlujoRecoleccion::estadoTras("cancelar", "planificada"),
]);
afirmar("Una accion desconocida no se puede en ningun estado", false, FlujoRecoleccion::puede("borrar", "planificada"));
afirmar("El motivo de rechazo distingue cerrada, ya iniciada y sin iniciar", [
    "La recolección ya está finalizada y no admite más cambios",
    "La recolección ya está en curso",
    "La recolección todavía no empezó: primero hay que iniciarla",
], [
    FlujoRecoleccion::motivoDeRechazo("vaciado", "finalizada"),
    FlujoRecoleccion::motivoDeRechazo("iniciar", "en_curso"),
    FlujoRecoleccion::motivoDeRechazo("vaciado", "planificada"),
]);

titulo("16. Recolecciones: quien alcanza cada una y el camion");

$deLaNorte = ["id_cuadrilla" => 1];
afirmar("El administrador alcanza cualquiera; la cuadrilla, solo las suyas", [true, true, false, false, false, false], [
    FlujoRecoleccion::alcanza(["rol" => "Administrador"], $deLaNorte),
    FlujoRecoleccion::alcanza(["rol" => "Cuadrilla", "id_cuadrilla" => 1], $deLaNorte),
    FlujoRecoleccion::alcanza(["rol" => "Cuadrilla", "id_cuadrilla" => 2], $deLaNorte),
    FlujoRecoleccion::alcanza(["rol" => "Cuadrilla", "id_cuadrilla" => null], $deLaNorte),
    FlujoRecoleccion::alcanza(["rol" => "Operario"], $deLaNorte),
    FlujoRecoleccion::alcanza(["rol" => "Vecino"], $deLaNorte),
]);
afirmar("Solo un camion operativo puede salir", [null, true, true, true], [
    FlujoRecoleccion::motivoCamionNoDisponible("operativo", "SBA-1234"),
    str_contains((string) FlujoRecoleccion::motivoCamionNoDisponible("en_ruta", "SBA-1234"), "en ruta"),
    str_contains((string) FlujoRecoleccion::motivoCamionNoDisponible("mantenimiento", "SBA-1234"), "mantenimiento"),
    str_contains((string) FlujoRecoleccion::motivoCamionNoDisponible("fuera_servicio", "SBA-1234"), "fuera de servicio"),
]);
afirmar("Al cerrar una que estaba en curso, el camion en ruta vuelve a operativo", "operativo",
    FlujoRecoleccion::estadoCamionTrasCierre("en_curso", "en_ruta"));
afirmar("Si no habia salido, o el camion ya cambio, no se toca", [null, null], [
    FlujoRecoleccion::estadoCamionTrasCierre("planificada", "operativo"),
    FlujoRecoleccion::estadoCamionTrasCierre("en_curso", "mantenimiento"),
]);

titulo("17. Recolecciones: validacion de lo que llega");

$nueva = ["id_ruta" => 1, "id_camion" => "1", "id_cuadrilla" => 3, "fecha_hora_inicio" => "2026-10-20T06:30"];
afirmar("Una planificacion completa es valida", null, FlujoRecoleccion::errorDeNueva($nueva));
afirmar("La fecha acepta el formato del navegador y el de la base", ["2026-10-20 06:30:00", "2026-10-20 06:30:00"], [
    FlujoRecoleccion::fechaHora("2026-10-20T06:30"), FlujoRecoleccion::fechaHora("2026-10-20 06:30"),
]);
afirmar("Fechas imposibles o mal escritas no pasan", [null, null, null, null], [
    FlujoRecoleccion::fechaHora("2026-02-30 06:30"), FlujoRecoleccion::fechaHora("20/10/2026 06:30"),
    FlujoRecoleccion::fechaHora("2026-10-20 25:00"), FlujoRecoleccion::fechaHora(["x"]),
]);
afirmar("Falta la ruta, el camion o la cuadrilla", ["Indicá la ruta", "Indicá el camión", "Indicá la cuadrilla"], [
    FlujoRecoleccion::errorDeNueva(array_merge($nueva, ["id_ruta" => 0])),
    FlujoRecoleccion::errorDeNueva(array_merge($nueva, ["id_camion" => "uno"])),
    FlujoRecoleccion::errorDeNueva(array_merge($nueva, ["id_cuadrilla" => null])),
]);
afirmar("Un centro que no es numero se rechaza; vacio se acepta", [true, null], [
    FlujoRecoleccion::errorDeNueva(array_merge($nueva, ["id_centro" => "x"])) !== null,
    FlujoRecoleccion::errorDeNueva(array_merge($nueva, ["id_centro" => ""])),
]);
afirmar("Observaciones de mas de 300 caracteres no", true,
    FlujoRecoleccion::errorDeNueva(array_merge($nueva, ["observaciones" => str_repeat("a", 301)])) !== null);

$vaciado = ["codigo_contenedor" => "CNT-001", "peso_kg" => "210.5", "volumen_l" => 640];
afirmar("Un vaciado completo es valido, y sin volumen tambien", [null, null], [
    FlujoRecoleccion::errorDeVaciado($vaciado, 1100),
    FlujoRecoleccion::errorDeVaciado(["codigo_contenedor" => "CNT-001", "peso_kg" => 10]),
]);
afirmar("El peso es obligatorio, positivo y con tope", [true, true, true, true], [
    FlujoRecoleccion::errorDeVaciado(["codigo_contenedor" => "CNT-001"]) !== null,
    FlujoRecoleccion::errorDeVaciado(["codigo_contenedor" => "CNT-001", "peso_kg" => 0]) !== null,
    FlujoRecoleccion::errorDeVaciado(["codigo_contenedor" => "CNT-001", "peso_kg" => "mucho"]) !== null,
    FlujoRecoleccion::errorDeVaciado(["codigo_contenedor" => "CNT-001", "peso_kg" => FlujoRecoleccion::PESO_MAXIMO_KG + 1]) !== null,
]);
afirmar("El volumen no puede ser negativo ni pasar la capacidad del contenedor", [true, true], [
    FlujoRecoleccion::errorDeVaciado(array_merge($vaciado, ["volumen_l" => -1]), 1100) !== null,
    FlujoRecoleccion::errorDeVaciado(array_merge($vaciado, ["volumen_l" => 1101]), 1100) !== null,
]);
afirmar("Sin contenedor no hay vaciado", "Indicá el contenedor por su código",
    FlujoRecoleccion::errorDeVaciado(["peso_kg" => 10]));

$ruta = ["nombre" => "Ruta de prueba", "dia_semana" => "martes", "hora_inicio" => "06:15", "id_zona" => 2];
afirmar("Una ruta completa es valida", null, FlujoRecoleccion::errorDeRuta($ruta, true));
afirmar("Al editar se valida solo lo que llega", null, FlujoRecoleccion::errorDeRuta(["hora_inicio" => "22:00"], false));
afirmar("Dia, hora, nombre, zona y estado invalidos", [true, true, true, true, true], [
    FlujoRecoleccion::errorDeRuta(array_merge($ruta, ["dia_semana" => "feriado"]), true) !== null,
    FlujoRecoleccion::errorDeRuta(array_merge($ruta, ["hora_inicio" => "6 y cuarto"]), true) !== null,
    FlujoRecoleccion::errorDeRuta(array_merge($ruta, ["nombre" => "R"]), true) !== null,
    FlujoRecoleccion::errorDeRuta(array_merge($ruta, ["id_zona" => "x"]), true) !== null,
    FlujoRecoleccion::errorDeRuta(["estado" => "borrada"], false) !== null,
]);

titulo("18. Recolecciones: filtros y consistencia con la base");

afirmar("Filtros validos", ["estado" => "en_curso", "activas" => true, "cuadrilla" => 1, "desde" => "2026-09-01"],
    FlujoRecoleccion::filtrosDe(["estado" => "en_curso", "activas" => "1", "cuadrilla" => "1", "desde" => "2026-09-01", "otro" => "x"])["filtros"]);
afirmar("Filtros invalidos: estado, numero, fecha y rango al reves", [true, true, true, true], [
    FlujoRecoleccion::filtrosDe(["estado" => "borrada"])["error"] !== null,
    FlujoRecoleccion::filtrosDe(["camion" => "uno"])["error"] !== null,
    FlujoRecoleccion::filtrosDe(["hasta" => "2026-13-01"])["error"] !== null,
    FlujoRecoleccion::filtrosDe(["desde" => "2026-10-02", "hasta" => "2026-10-01"])["error"] !== null,
]);

$schema = file_get_contents(__DIR__ . "/../database/schema.sql");
preg_match("/CREATE TABLE recoleccion .*?estado\s+ENUM\(([^)]*)\)/s", $schema, $enumRecoleccion);
preg_match("/CREATE TABLE ruta .*?dia_semana\s+ENUM\(([^)]*)\)/s", $schema, $enumDias);
$deLaBase = fn (array $coincidencia) => array_map(fn ($v) => trim($v, " '\n\r"), explode(",", $coincidencia[1] ?? ""));
afirmar("Los estados del codigo son los del ENUM de recoleccion", FlujoRecoleccion::ESTADOS, $deLaBase($enumRecoleccion));
afirmar("Los dias del codigo son los del ENUM de ruta", FlujoRecoleccion::DIAS, $deLaBase($enumDias));
