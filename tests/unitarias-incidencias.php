<?php

// Parte de unitarias.php (se incluye desde alli y usa su afirmar() y su titulo()).
// Las reglas de las incidencias que no necesitan base de datos ni servidor.

titulo("9. Incidencias: el flujo de estados");

afirmar("El numero de incidencia lleva cuatro cifras", "INC-0007", FlujoIncidencia::numero(7));
afirmar("Y no se trunca cuando crece", "INC-12345", FlujoIncidencia::numero(12345));

$acciones = ["asignar", "prioridad", "avance", "resolver", "descartar"];
$matriz = [];
foreach (FlujoIncidencia::ESTADOS as $estado) {
    foreach ($acciones as $accion) {
        $matriz[$estado][$accion] = FlujoIncidencia::puede($accion, $estado);
    }
}
afirmar(
    "Que se puede hacer en cada estado: una cerrada no admite nada, un avance pide estar en curso",
    [
        "abierta"    => ["asignar" => true,  "prioridad" => true,  "avance" => false, "resolver" => true,  "descartar" => true],
        "en_curso"   => ["asignar" => true,  "prioridad" => true,  "avance" => true,  "resolver" => true,  "descartar" => true],
        "resuelta"   => ["asignar" => false, "prioridad" => false, "avance" => false, "resolver" => false, "descartar" => false],
        "descartada" => ["asignar" => false, "prioridad" => false, "avance" => false, "resolver" => false, "descartar" => false],
    ],
    $matriz
);
afirmar("Una accion que no existe no se puede hacer", false, FlujoIncidencia::puede("borrar", "abierta"));
afirmar("Asignar deja la incidencia en curso", "en_curso", FlujoIncidencia::estadoTras("asignar", "abierta"));
afirmar("Reasignar una en curso la deja en curso", "en_curso", FlujoIncidencia::estadoTras("asignar", "en_curso"));
afirmar("Un avance no cambia el estado", "en_curso", FlujoIncidencia::estadoTras("avance", "en_curso"));
afirmar("Resolver la deja resuelta", "resuelta", FlujoIncidencia::estadoTras("resolver", "en_curso"));
afirmar("Descartar la deja descartada", "descartada", FlujoIncidencia::estadoTras("descartar", "abierta"));
afirmar("Cambiar la prioridad no cambia el estado", "abierta", FlujoIncidencia::estadoTras("prioridad", "abierta"));
afirmar("Abierta y en curso cuentan como abiertas; las finales no", [true, true, false, false], array_map(
    fn (string $e) => FlujoIncidencia::estaAbierta($e),
    FlujoIncidencia::ESTADOS
));
afirmar(
    "El motivo de rechazo de una cerrada lo dice",
    "La incidencia ya está resuelta y no admite más cambios",
    FlujoIncidencia::motivoDeRechazo("avance", "resuelta")
);
afirmar(
    "El de un avance sobre una abierta pide asignarla primero",
    "Para registrar un avance la incidencia tiene que estar asignada a una cuadrilla",
    FlujoIncidencia::motivoDeRechazo("avance", "abierta")
);

titulo("10. Incidencias: quien ve y toca cada una");

$administrador = ["rol" => "Administrador", "id_usuario" => 1, "id_cuadrilla" => null];
$cuadrilla1    = ["rol" => "Cuadrilla", "id_usuario" => 2, "id_cuadrilla" => 1];
$cuadrilla2    = ["rol" => "Cuadrilla", "id_usuario" => 5, "id_cuadrilla" => 2];
$cuadrillaSola = ["rol" => "Cuadrilla", "id_usuario" => 9, "id_cuadrilla" => null];
$vecino        = ["rol" => "Vecino", "id_usuario" => 3, "id_cuadrilla" => null];
$otroVecino    = ["rol" => "Vecino", "id_usuario" => 6, "id_cuadrilla" => null];
$operario      = ["rol" => "Operario", "id_usuario" => 4, "id_cuadrilla" => null];
$asignada      = ["id_usuario" => 3, "id_cuadrilla" => 1];
$sinAsignar    = ["id_usuario" => 3, "id_cuadrilla" => null];

afirmar("El administrador alcanza cualquiera", [true, true], [
    FlujoIncidencia::alcanza($administrador, $asignada), FlujoIncidencia::alcanza($administrador, $sinAsignar),
]);
afirmar("Una cuadrilla alcanza solo las asignadas a ella", [true, false, false], [
    FlujoIncidencia::alcanza($cuadrilla1, $asignada),
    FlujoIncidencia::alcanza($cuadrilla2, $asignada),
    FlujoIncidencia::alcanza($cuadrilla1, $sinAsignar),
]);
afirmar("Un integrante sin cuadrilla no alcanza ni las que no tienen cuadrilla", false,
    FlujoIncidencia::alcanza($cuadrillaSola, $sinAsignar));
afirmar("Un vecino alcanza solo las que informo el", [true, false], [
    FlujoIncidencia::alcanza($vecino, $asignada), FlujoIncidencia::alcanza($otroVecino, $asignada),
]);
afirmar("El operario no alcanza ninguna", false, FlujoIncidencia::alcanza($operario, $asignada));
afirmar("Un usuario sin rol tampoco", false, FlujoIncidencia::alcanza([], $asignada));

$asientosDePrueba = [
    ["estado" => "abierta", "id_usuario" => 3, "usuario" => "Marta Rodriguez", "rol" => "Vecino"],
    ["estado" => "en_curso", "id_usuario" => 1, "usuario" => "Ana Perez", "rol" => "Administrador"],
    ["estado" => "en_curso", "id_usuario" => 2, "usuario" => "Luis Gomez", "rol" => "Cuadrilla"],
];
afirmar("El administrador ve el historial completo, con quien informo",
    $asientosDePrueba, FlujoIncidencia::historialVisible($asientosDePrueba, $administrador));
$vistoPorCuadrilla = FlujoIncidencia::historialVisible($asientosDePrueba, $cuadrilla1);
afirmar("La cuadrilla no ve el nombre del vecino en el historial, solo su rol",
    [null, "Vecino"], [$vistoPorCuadrilla[0]["usuario"], $vistoPorCuadrilla[0]["rol"]]);
afirmar("Pero si el de quien asigno y quien trabajo",
    ["Ana Perez", "Luis Gomez"], [$vistoPorCuadrilla[1]["usuario"], $vistoPorCuadrilla[2]["usuario"]]);
afirmar("Y ningun asiento lleva el id de usuario", [], array_values(array_filter($vistoPorCuadrilla, fn ($a) => isset($a["id_usuario"]))));
afirmar("El vecino que informo se ve a si mismo",
    "Marta Rodriguez", FlujoIncidencia::historialVisible($asientosDePrueba, $vecino)[0]["usuario"]);
afirmar("Otro vecino (si llegara a verlo) no ve el nombre",
    null, FlujoIncidencia::historialVisible($asientosDePrueba, $otroVecino)[0]["usuario"]);
$vistoPorVecino = FlujoIncidencia::historialVisible($asientosDePrueba, $vecino);
afirmar("Al vecino el personal se le muestra por su rol, sin nombre (decision del 17/10)",
    [null, "Administrador", null, "Cuadrilla"], [$vistoPorVecino[1]["usuario"], $vistoPorVecino[1]["rol"], $vistoPorVecino[2]["usuario"], $vistoPorVecino[2]["rol"]]);
afirmar("Y tampoco lleva ningun id de usuario", [], array_values(array_filter($vistoPorVecino, fn ($a) => isset($a["id_usuario"]))));

titulo("11. Incidencias: lo que le pasa al contenedor");

afirmar("Un desborde sobre un contenedor disponible lo pasa a lleno", "lleno",
    FlujoIncidencia::estadoContenedorTrasReporte("desbordado", "disponible"));
afirmar("Si ya estaba lleno no cambia nada", null, FlujoIncidencia::estadoContenedorTrasReporte("desbordado", "lleno"));
afirmar("Ni si estaba en mantenimiento", null, FlujoIncidencia::estadoContenedorTrasReporte("desbordado", "mantenimiento"));
afirmar("Los otros problemas no tocan el estado", [null, null, null, null], [
    FlujoIncidencia::estadoContenedorTrasReporte("roto", "disponible"),
    FlujoIncidencia::estadoContenedorTrasReporte("residuo_no_permitido", "disponible"),
    FlujoIncidencia::estadoContenedorTrasReporte("ausente_o_mal_ubicado", "disponible"),
    FlujoIncidencia::estadoContenedorTrasReporte("otro", "disponible"),
]);
afirmar("Cerrar la ultima incidencia devuelve a disponible un contenedor lleno o en mantenimiento",
    ["disponible", "disponible"], [
        FlujoIncidencia::estadoContenedorTrasCierre("lleno", 0), FlujoIncidencia::estadoContenedorTrasCierre("mantenimiento", 0),
    ]);
afirmar("Si quedan otras abiertas sobre el, no se toca", null, FlujoIncidencia::estadoContenedorTrasCierre("lleno", 1));
afirmar("Uno disponible o retirado no cambia", [null, null], [
    FlujoIncidencia::estadoContenedorTrasCierre("disponible", 0), FlujoIncidencia::estadoContenedorTrasCierre("retirado", 0),
]);

titulo("12. Incidencias: validacion de lo que llega");

$nueva = ["codigo_contenedor" => "CNT-001", "tipo_problema" => "roto", "descripcion" => "La tapa esta rota"];

afirmar("Un reporte completo es valido", null, FlujoIncidencia::errorDeNueva($nueva));
afirmar("El contenedor puede venir por id, incluso como texto", null,
    FlujoIncidencia::errorDeNueva(["id_contenedor" => "3", "tipo_problema" => "otro", "descripcion" => "Hay olor fuerte"]));
afirmar("La prioridad es opcional pero tiene que ser una conocida", [null, true], [
    FlujoIncidencia::errorDeNueva($nueva + ["prioridad" => "alta"]),
    str_starts_with((string) FlujoIncidencia::errorDePrioridad("urgente"), "Prioridad inválida"),
]);
afirmar("Sin contenedor se pide el codigo", "Indicá el contenedor por su código",
    FlujoIncidencia::errorDeNueva(["tipo_problema" => "roto", "descripcion" => "La tapa esta rota"]));
afirmar("Un id de contenedor que no es positivo tampoco sirve", "Indicá el contenedor por su código",
    FlujoIncidencia::errorDeNueva(["id_contenedor" => 0, "tipo_problema" => "roto", "descripcion" => "La tapa esta rota"]));
afirmar("Un tipo desconocido se rechaza y lista los validos", true,
    str_starts_with((string) FlujoIncidencia::errorDeNueva(["tipo_problema" => "inventado"] + $nueva), "Tipo de problema inválido. Valores admitidos: desbordado, lleno, roto"));
afirmar("Una descripcion de menos de 5 letras se rechaza", "Contá el problema en al menos 5 caracteres",
    FlujoIncidencia::errorDeNueva(["descripcion" => "Roto"] + $nueva));
// Desde la parte 26 (H-06) la descripcion es opcional: solo el tipo «otro» la exige. Lo que se escriba tiene que tener 5 caracteres o mas.
afirmar("Una hecha de espacios cuenta como vacia: en «otro» se rechaza", "Contá el problema en al menos 5 caracteres",
    FlujoIncidencia::errorDeNueva(["tipo_problema" => "otro", "descripcion" => "         "] + $nueva));
afirmar("Una descripcion que no es texto cuenta como vacia: en «otro» tambien se rechaza", "Contá el problema en al menos 5 caracteres",
    FlujoIncidencia::errorDeNueva(["tipo_problema" => "otro", "descripcion" => ["x"]] + $nueva));
afirmar("En «otro» sin la clave «descripcion» tambien", "Contá el problema en al menos 5 caracteres",
    FlujoIncidencia::errorDeNueva(["codigo_contenedor" => "CNT-001", "tipo_problema" => "otro"]));
afirmar("En cualquier otro tipo, vacia, en blanco o ausente esta bien", [null, null, null], [
    FlujoIncidencia::errorDeNueva(["descripcion" => ""] + $nueva), FlujoIncidencia::errorDeNueva(["descripcion" => "         "] + $nueva),
    FlujoIncidencia::errorDeNueva(["codigo_contenedor" => "CNT-001", "tipo_problema" => "lleno"]),
]);
afirmar("Pero si se escribe algo en cualquier tipo, tiene que tener 5 caracteres o mas", [true, true, true], [
    FlujoIncidencia::errorDeNueva(["tipo_problema" => "lleno", "descripcion" => "abc"] + $nueva) !== null,
    FlujoIncidencia::errorDeNueva(["tipo_problema" => "desbordado", "descripcion" => "  ab  "] + $nueva) !== null,
    FlujoIncidencia::errorDeNueva(["tipo_problema" => "otro", "descripcion" => "abcd"] + $nueva) !== null,
]);
afirmar("Sin texto se guarda el rotulo del tipo; con texto, el texto sin espacios de los bordes", ["Contenedor lleno", "Contenedor roto o dañado", "Otro", "La tapa esta rota"], [
    FlujoIncidencia::descripcionDe(["tipo_problema" => "lleno"]), FlujoIncidencia::descripcionDe(["tipo_problema" => "roto", "descripcion" => "   "]),
    FlujoIncidencia::descripcionDe(["tipo_problema" => "otro", "descripcion" => null]), FlujoIncidencia::descripcionDe(["tipo_problema" => "roto", "descripcion" => "  La tapa esta rota "]),
]);
afirmar("Cada tipo valido tiene su rotulo (y el mismo que usa la pantalla)", [[], []], [
    array_values(array_diff(FlujoIncidencia::TIPOS, array_keys(FlujoIncidencia::TEXTO_TIPO))),
    array_values(array_filter(FlujoIncidencia::TEXTO_TIPO, static fn(string $rotulo) => !str_contains((string) file_get_contents(__DIR__ . "/../frontend-usuarios/js/incidencias-comun.js"), '"' . $rotulo . '"'))),
]);
afirmar("Pasarse de 500 caracteres se rechaza", "La descripción no puede pasar de 500 caracteres",
    FlujoIncidencia::errorDeNueva(["descripcion" => str_repeat("a", 501)] + $nueva));
afirmar("500 justos entran", null, FlujoIncidencia::errorDeNueva(["descripcion" => str_repeat("a", 500)] + $nueva));
afirmar("Una prioridad inventada se rechaza", true,
    str_starts_with((string) FlujoIncidencia::errorDeNueva($nueva + ["prioridad" => "urgente"]), "Prioridad inválida"));

afirmar("entero() acepta enteros positivos, tambien como texto", [12, 12], [FlujoIncidencia::entero(12), FlujoIncidencia::entero("12")]);
afirmar("Y rechaza cero, negativos, decimales, texto y nulos", [null, null, null, null, null, null], [
    FlujoIncidencia::entero(0), FlujoIncidencia::entero("-3"), FlujoIncidencia::entero("1.5"),
    FlujoIncidencia::entero("abc"), FlujoIncidencia::entero(null), FlujoIncidencia::entero(true),
]);
afirmar("Una observacion obligatoria no puede venir vacia", "Escribí una observación", FlujoIncidencia::errorDeObservacion("", true));
afirmar("Una opcional si", null, FlujoIncidencia::errorDeObservacion("", false));
afirmar("300 caracteres entran y 301 no", [null, "La observación no puede pasar de 300 caracteres"], [
    FlujoIncidencia::errorDeObservacion(str_repeat("a", 300), true), FlujoIncidencia::errorDeObservacion(str_repeat("a", 301), true),
]);
afirmar("limpiar() recorta el texto y deja vacio lo que no es texto", ["hola", "", ""], [
    FlujoIncidencia::limpiar("  hola "), FlujoIncidencia::limpiar(5), FlujoIncidencia::limpiar(null),
]);

titulo("13. Incidencias: filtros del listado");

afirmar("Sin filtros no hay nada que aplicar", ["filtros" => [], "error" => null], FlujoIncidencia::filtrosDe([]));
afirmar("Los vacios se ignoran", ["filtros" => [], "error" => null],
    FlujoIncidencia::filtrosDe(["estado" => "", "zona" => "", "desde" => ""]));
afirmar("Estado, tipo, zona y periodo se aceptan",
    ["filtros" => ["estado" => "abierta", "tipo" => "roto", "zona" => 3, "desde" => "2026-09-01", "hasta" => "2026-09-30"], "error" => null],
    FlujoIncidencia::filtrosDe(["estado" => "abierta", "tipo" => "roto", "zona" => "3", "desde" => "2026-09-01", "hasta" => "2026-09-30"]));
afirmar("Un estado desconocido se rechaza", true,
    str_starts_with((string) FlujoIncidencia::filtrosDe(["estado" => "reportada"])["error"], "Filtro 'estado' inválido"));
afirmar("Una zona que no es un numero se rechaza", true,
    str_starts_with((string) FlujoIncidencia::filtrosDe(["zona" => "centro"])["error"], "Filtro 'zona' inválido"));
afirmar("Una fecha imposible se rechaza", true,
    str_starts_with((string) FlujoIncidencia::filtrosDe(["desde" => "2026-02-30"])["error"], "Filtro 'desde' inválido"));
afirmar("Una fecha en otro formato tambien", true,
    str_starts_with((string) FlujoIncidencia::filtrosDe(["hasta" => "30/09/2026"])["error"], "Filtro 'hasta' inválido"));
afirmar("Un periodo al reves se rechaza", "La fecha 'desde' no puede ser posterior a 'hasta'",
    FlujoIncidencia::filtrosDe(["desde" => "2026-10-01", "hasta" => "2026-09-01"])["error"]);
afirmar("Un dia solo (desde = hasta) es un periodo valido", null,
    FlujoIncidencia::filtrosDe(["desde" => "2026-09-08", "hasta" => "2026-09-08"])["error"]);
afirmar("Un filtro que llega como lista no rompe nada", true,
    str_starts_with((string) FlujoIncidencia::filtrosDe(["estado" => ["abierta"]])["error"], "Filtro 'estado' inválido"));

titulo("14. Incidencias: permisos y consistencia con la base");

afirmar("Ver incidencias: administrador, cuadrilla y vecino", ["Administrador", "Cuadrilla", "Vecino"],
    Permisos::rolesPermitidos("incidencias", "GET"));
afirmar("Informar una incidencia es del vecino y de la cuadrilla (en terreno)", ["Vecino", "Cuadrilla"], Permisos::rolesPermitidos("incidencias", "POST"));
afirmar("Cambiar la prioridad es solo del administrador", ["Administrador"], Permisos::rolesPermitidos("incidencias", "PUT"));
afirmar("Nadie borra una incidencia", [], Permisos::rolesPermitidos("incidencias", "DELETE"));
afirmar("Asignar y descartar: administrador", [["Administrador"], ["Administrador"]], [
    Permisos::rolesPermitidos("incidencias/asignacion", "POST"), Permisos::rolesPermitidos("incidencias/descarte", "POST"),
]);
afirmar("Registrar un avance es de la cuadrilla", ["Cuadrilla"], Permisos::rolesPermitidos("incidencias/avance", "POST"));
afirmar("Resolver: administrador o cuadrilla", ["Administrador", "Cuadrilla"], Permisos::rolesPermitidos("incidencias/resolucion", "POST"));

$delOperario = [];
foreach (Permisos::recursos() as $recurso) {
    if (!str_starts_with($recurso, "incidencias") && $recurso !== "cuadrillas") {
        continue;
    }
    foreach (["GET", "POST", "PUT", "DELETE"] as $metodo) {
        if (in_array("Operario", Permisos::rolesPermitidos($recurso, $metodo), true)) {
            $delOperario[] = "$metodo $recurso";
        }
    }
}
afirmar("El operario no toca nada de incidencias ni de cuadrillas", [], $delOperario);
afirmar("Las cuadrillas (en api-recoleccion) se leen y se administran solo siendo administrador", [["Administrador"], ["Administrador"]], [
    $permisosRecoleccion["cuadrillas"]["GET"] ?? null, $permisosRecoleccion["cuadrillas"]["POST"] ?? null,
]);

$gestion = file_get_contents($raiz . "/api-gestion/index.php");
preg_match('/case "incidencias":(.*?)break;/s', $gestion, $bloque);
preg_match_all('/\$subrecurso === "([a-z]+)" && \$metodo/', $bloque[1] ?? "", $subrutas);
$subrecursosDelRouter = array_values(array_unique($subrutas[1]));
$subrecursosConRegla = [];
foreach (Permisos::recursos() as $recurso) {
    if (str_starts_with($recurso, "incidencias/")) {
        $subrecursosConRegla[] = substr($recurso, strlen("incidencias/"));
    }
}
sort($subrecursosDelRouter);
sort($subrecursosConRegla);
afirmar("Cada subrecurso de incidencias del router tiene su regla de permisos", $subrecursosConRegla, $subrecursosDelRouter);

$schema = file_get_contents($raiz . "/database/schema.sql");
preg_match('/CREATE TABLE incidencia \((.*?)\) ENGINE/s', $schema, $tabla);
$valoresDe = function (string $columna) use ($tabla): array {
    preg_match('/' . $columna . '\s+ENUM\(([^)]*)\)/', $tabla[1] ?? "", $enum);
    preg_match_all("/'([a-z_]+)'/", $enum[1] ?? "", $valores);
    return $valores[1];
};
afirmar("Los estados del codigo son los del ENUM de schema.sql", FlujoIncidencia::ESTADOS, $valoresDe("estado"));
afirmar("Los tipos de problema del codigo son los del ENUM de schema.sql", FlujoIncidencia::TIPOS, $valoresDe("tipo_problema"));
afirmar("Las prioridades del codigo son las del ENUM de schema.sql", FlujoIncidencia::PRIORIDADES, $valoresDe("prioridad"));
afirmar("schema.sql crea la tabla del historial y los cuatro triggers de solo agregado", [1, 4], [
    preg_match('/CREATE TABLE historial_incidencia\s*\(/', $schema),
    preg_match_all('/CREATE TRIGGER trg_(?:histinc|estadocont)_sin_(?:update|delete)/', $schema),
]);


// ---- Plan de rutas para la defensa, bloque 7: prioridad por tipo y derivacion a mantenimiento ----
afirmar("Prioridad por tipo: desbordado alta, lleno media, roto media, residuo no permitido y otro baja",
    ["alta", "media", "media", "baja", "baja"],
    array_map([FlujoIncidencia::class, "prioridadDeTipo"], ["desbordado", "lleno", "roto", "residuo_no_permitido", "otro"]));
afirmar("Todos los tipos tienen prioridad por defecto, y es una prioridad valida", [],
    array_values(array_filter(FlujoIncidencia::TIPOS, fn($t) => !in_array(FlujoIncidencia::prioridadDeTipo($t), FlujoIncidencia::PRIORIDADES, true))));
afirmar("Solo lo roto o danado es de mantenimiento", [false, false, true, false],
    array_map([FlujoIncidencia::class, "esDeMantenimiento"], ["desbordado", "lleno", "roto", "otro"]));
afirmar("Derivar solo se puede con la incidencia abierta o en curso", [true, true, false, false],
    [FlujoIncidencia::puede("derivar", "abierta"), FlujoIncidencia::puede("derivar", "en_curso"), FlujoIncidencia::puede("derivar", "resuelta"), FlujoIncidencia::puede("derivar", "descartada")]);
afirmar("Un lleno (aun sin desbordar) deja el contenedor en estado lleno si estaba disponible; si ya estaba en mantenimiento no lo toca",
    ["lleno", null], [FlujoIncidencia::estadoContenedorTrasReporte("lleno", "disponible"), FlujoIncidencia::estadoContenedorTrasReporte("lleno", "mantenimiento")]);
afirmar("Derivar no cambia el estado de la incidencia (sigue abierta o en curso)", ["abierta", "en_curso"],
    [FlujoIncidencia::estadoTras("derivar", "abierta"), FlujoIncidencia::estadoTras("derivar", "en_curso")]);
afirmar("La cuadrilla alcanza lo que ella misma informo, aunque no sea suyo", [true, false],
    [FlujoIncidencia::alcanza(["rol" => "Cuadrilla", "id_usuario" => 7, "id_cuadrilla" => 1], ["id_usuario" => 7, "id_cuadrilla" => null]),
     FlujoIncidencia::alcanza(["rol" => "Cuadrilla", "id_usuario" => 8, "id_cuadrilla" => 1], ["id_usuario" => 7, "id_cuadrilla" => 2])]);
