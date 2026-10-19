<?php

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../core/Response.php";
require_once __DIR__ . "/../core/Auditoria.php";
require_once __DIR__ . "/EstadisticaController.php";
require_once __DIR__ . "/../models/FlujoIncidencia.php";
require_once __DIR__ . "/../models/ReportePdf.php";

// Reporte de gestion en PDF (extras, parte 20). Reune los mismos datos que muestra la pantalla de Reportes
// (los pide a EstadisticaController, asi las cifras no pueden diferir) y los entrega como un PDF generado aca, sin librerias.
class ReporteController
{
    // GET /reportes/pdf?desde=&hasta=&zona=: solo Administrador. La descarga queda en la auditoria (los GET no se registran solos).
    public function pdf(array $usuario): void
    {
        $consulta = FlujoIncidencia::filtrosDe(array_intersect_key($_GET, array_flip(["desde", "hasta", "zona"])));
        if ($consulta["error"] !== null) {
            Response::error($consulta["error"], 400);
        }
        $filtros = $consulta["filtros"];

        $estadisticas = new EstadisticaController();
        $incidencias = $estadisticas->datosIncidencias($filtros);
        $logo = @file_get_contents(__DIR__ . "/../../frontend-usuarios/img/logo.svg");

        $contenido = ReportePdf::armar([
            "periodo"      => $incidencias["periodo"],
            "incidencias"  => $incidencias,
            "satisfaccion" => $estadisticas->datosSatisfaccion($filtros),
            "urgencias"    => $estadisticas->datosUrgencias(Estadisticas::umbralDe(null)),
            "impacto"      => $estadisticas->datosImpacto(array_diff_key($filtros, ["zona" => 1])),
            "logo_svg"     => $logo === false ? null : $logo,
        ]);

        $this->auditar($usuario);
        $nombre = "sigeru-reporte-" . date("Y-m-d") . ".pdf";
        http_response_code(200);
        header("Content-Type: application/pdf");
        header('Content-Disposition: attachment; filename="' . $nombre . '"');
        header("Content-Length: " . strlen($contenido));
        header("Cache-Control: no-store");
        header("X-Content-Type-Options: nosniff");
        echo $contenido;
        exit;
    }

    private function auditar(array $usuario): void
    {
        try {
            Database::conexion()->prepare(
                "INSERT INTO auditoria (id_usuario, rol, metodo, ruta, codigo, ip) VALUES (:usuario, :rol, 'GET', :ruta, 200, :ip)"
            )->execute([
                ":usuario" => $usuario["id_usuario"],
                ":rol"     => $usuario["rol"],
                ":ruta"    => Auditoria::rutaLimpia($_SERVER["REQUEST_URI"] ?? "/reportes/pdf"),
                ":ip"      => substr((string) ($_SERVER["REMOTE_ADDR"] ?? "?"), 0, 45),
            ]);
        } catch (Throwable $e) {
            error_log("SiGeRU auditoria del reporte PDF: " . $e->getMessage());
        }
    }
}
