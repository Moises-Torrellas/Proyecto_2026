<?php

namespace App\servicios;

use Dompdf\Dompdf;
use Dompdf\Options;

class GenerarReporteEstadistico
{
    public static function generarPDF(string $nombreVista, array $datos, string $modulo, $grafico)
    {
        $modulo = preg_replace('/[^a-zA-Z0-9]/', '', $modulo);
        try {
            ini_set('memory_limit', '512M');
            set_time_limit(300);
            $options = new Options();
            $options->set('isRemoteEnabled', true); // Permite cargar imágenes
            $options->set('isHtml5ParserEnabled', true);
            $dompdf = new Dompdf($options);

            $formateador = new \IntlDateFormatter('es_ES', \IntlDateFormatter::LONG, \IntlDateFormatter::NONE, 'America/Caracas');
            $formateador->setPattern("d 'de' MMMM, y");
            $fecha_reporte = $formateador->format(new \DateTime());
            $usuario = $_SESSION['nombre'] . ' ' . $_SESSION['apellido'] . ' - ' . $_SESSION['rol'];
            
            ob_start();
            $ruta_logo = __DIR__ . '/../../public/img/logo.png';
            $ruta_logo_footer =  __DIR__ . '/../../public/img/logo_2.png';
            $logo = file_exists($ruta_logo) ? 'data:image/png;base64,' . base64_encode(file_get_contents($ruta_logo)) : '';
            $logo_footer = file_exists($ruta_logo_footer) ? 'data:image/png;base64,' . base64_encode(file_get_contents($ruta_logo_footer)) : '';
            $charC = $grafico;
            include __DIR__ . "/../vista/reportes/estadisticos/{$nombreVista}.php";
            $html = ob_get_clean();

            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            // 1. Definimos la estructura: public/docs/reportes/{modulo}
            $nombreArchivo = $modulo . "_" . time() . "_" . uniqid() . ".pdf";
            $subDirectorio = "docs/reportes/" . strtolower($modulo);
            $rutaRelativa = $subDirectorio . "/" . $nombreArchivo;
            $rutaAbsoluta = __DIR__ . "/../../public/" . $rutaRelativa;

            // 2. Crear carpetas de forma recursiva si no existen
            // mkdir(ruta, permisos, recursivo=true)
            if (!is_dir(dirname($rutaAbsoluta))) {
                mkdir(dirname($rutaAbsoluta), 0755, true);
            }

            // 3. Guardar el archivo
            file_put_contents($rutaAbsoluta, $dompdf->output());

            return ['accion' => 'reporte', 'archivo' => $rutaRelativa];
        } catch (\Exception $e) {
            return ['accion' => 'error', 'mensaje' => $e->getMessage()];
        }
    }
}
