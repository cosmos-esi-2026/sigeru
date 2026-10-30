<?php

// Avisos (extras, parte 13): textos de cada evento y copias identicas.

require_once __DIR__ . "/../api-gestion/core/Notificador.php";

titulo("28. Avisos: textos de cada evento");

foreach (["en_curso", "resuelta", "descartada"] as $estado) {
    [$t, $m] = Notificador::incidenciaEstado("INC-0003", $estado);
    afirmar("El aviso al vecino ($estado) nombra el reclamo y entra en los limites", [true, true, true], [
        str_contains($t, "INC-0003") && str_contains($m, "INC-0003"),
        mb_strlen($t) <= Notificador::LARGO_TITULO, mb_strlen($m) <= Notificador::LARGO_MENSAJE,
    ]);
}
afirmar("Un estado raro no rompe: avisa que cambio", "Tu reclamo INC-0009 cambió", Notificador::incidenciaEstado("INC-0009", "otro")[0]);
afirmar("El aviso al vecino no nombra a la cuadrilla ni al personal (privacidad)", false,
    (bool) preg_match('/Cuadrilla (Norte|Sur)|Luis|Carla|Ana/', implode(" ", Notificador::incidenciaEstado("INC-0003", "en_curso"))));
afirmar("El aviso a la cuadrilla trae el numero y la direccion del contenedor", "Se asignó la incidencia INC-0003 (Av. Brasil 3040) a tu cuadrilla.",
    Notificador::incidenciaAsignada("INC-0003", "Av. Brasil 3040")[1]);
afirmar("El de la recolecion trae numero, ruta y fecha", "Se planificó la recolección REC-0007 de la ruta Ruta Centro Manana para el 21/10/2026 06:30.",
    Notificador::recoleccionAsignada("REC-0007", "Ruta Centro Manana", "21/10/2026 06:30")[1]);
afirmar("El de la incidencia critica va a los administradores con el numero", "Incidencia crítica: INC-0004", Notificador::incidenciaCritica("INC-0004", "Sarandi 620")[0]);
afirmar("El del centro pasado del 80 % dice el porcentaje con coma", ["Centro Norte pasó del 80 %", "El centro «Centro Norte» está al 84,5 % de su capacidad: conviene planificar un retiro."],
    Notificador::centroLleno("Centro Norte", 84.5, "alerta"));
afirmar("El del centro lleno pide retirar material", "Centro Norte llegó a su capacidad", Notificador::centroLleno("Centro Norte", 100.0, "lleno")[0]);
afirmar("La fecha del aviso se escribe dia/mes/anio hora:minuto", ["21/10/2026 06:30", "21/10/2026 06:30", "sin fecha"],
    [Notificador::fechaLegible("2026-10-21 06:30:00"), Notificador::fechaLegible("2026-10-21T06:30"), Notificador::fechaLegible("sin fecha")]);

titulo("28 bis. Avisos: copias identicas");

afirmar("core/Notificador.php es identico en api-gestion y api-recoleccion", true,
    file_get_contents(__DIR__ . "/../api-gestion/core/Notificador.php") === file_get_contents(__DIR__ . "/../api-recoleccion/core/Notificador.php"));
