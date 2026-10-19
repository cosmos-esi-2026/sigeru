<?php

// Avisos (extras, parte 13): textos de cada evento, aviso por correo en modo archivo y copias identicas.

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

titulo("28 bis. Avisos: correo en modo archivo y copias identicas");

configurarCorreo(["SIGERU_MAIL_MODE" => "archivo"]);
$destinoAviso = "aviso." . bin2hex(random_bytes(4)) . "@sigeru.uy";
$ficheroAviso = Correo::carpetaArchivo() . "/" . hash("sha256", $destinoAviso) . "-aviso.txt";
Correo::enviarAviso($destinoAviso, "Ana", "Tu reclamo INC-0003 fue resuelto", "Tu reclamo INC-0003 se resolvió.", "incidencia.html?id=3");
$aviso = is_file($ficheroAviso) ? file_get_contents($ficheroAviso) : "";
afirmar("El aviso deja su mensaje en tmp/correo con su propio nombre (no pisa el de bienvenida ni el de enlaces)", true, $aviso !== "");
afirmar("El asunto es el titulo y el cuerpo saluda y trae el mensaje", true,
    str_starts_with($aviso, "Tu reclamo INC-0003 fue resuelto") && str_contains($aviso, "Hola Ana") && str_contains($aviso, "se resolvió"));
afirmar("Lleva el enlace a la pantalla y como desactivar los avisos, y ningun token", true,
    str_contains($aviso, "http://localhost/sigeru/frontend-usuarios/incidencia.html?id=3") && str_contains($aviso, "Recibir avisos por correo") && !str_contains($aviso, "token="));
foreach (["javascript:alert(1)", "../../login.html", "http://otro.sitio/x.html", "incidencia.html?id=3&x=<script>", "//evil.html"] as $malo) {
    Correo::enviarAviso($destinoAviso, "Ana", "T", "M", $malo);
    afirmar("Un enlace raro no sale en el correo ($malo): vuelve al inicio", true,
        str_contains(file_get_contents($ficheroAviso), "frontend-usuarios/dashboard.html") && !str_contains(file_get_contents($ficheroAviso), "otro.sitio"));
}
Correo::enviarAviso($destinoAviso, "Ana", "T", "M", null);
afirmar("Sin enlace lleva al inicio", true, str_contains(file_get_contents($ficheroAviso), "frontend-usuarios/dashboard.html"));
$rechazado = false;
try {
    Correo::enviarAviso("a@sigeru.uy" . chr(13) . chr(10) . "Bcc: otro@sigeru.uy", "Ana", "T", "M");
} catch (CorreoException $e) {
    $rechazado = true;
}
afirmar("Un destino con salto de linea tambien se rechaza en los avisos", true, $rechazado);
@unlink($ficheroAviso);
configurarCorreo([]);

afirmar("core/Notificador.php es identico en api-gestion y api-recoleccion", true,
    file_get_contents(__DIR__ . "/../api-gestion/core/Notificador.php") === file_get_contents(__DIR__ . "/../api-recoleccion/core/Notificador.php"));
