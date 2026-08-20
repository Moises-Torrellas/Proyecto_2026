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
    
    public static function generarExcel(string $tipoReporte, array $datos, string $modulo)
    {
        $modulo = preg_replace('/[^a-zA-Z0-9]/', '', $modulo);
        try {
            ini_set('memory_limit', '512M');
            set_time_limit(300);

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Reporte Estadistico');

            $row = 1;
            if ($tipoReporte === 'recaudacion') {
                $sheet->setCellValue('A1', 'Concepto');
                $sheet->setCellValue('B1', 'Moneda');
                $sheet->setCellValue('C1', 'Total Cargado');
                $sheet->setCellValue('D1', 'Total Recaudado');
                $row = 2;
                foreach ($datos as $d) {
                    $sheet->setCellValue('A' . $row, $d['concepto'] ?? '');
                    $sheet->setCellValue('B' . $row, $d['moneda'] ?? '');
                    $sheet->setCellValue('C' . $row, $d['total_cargado'] ?? 0);
                    $sheet->setCellValue('D' . $row, $d['total_recaudado'] ?? 0);
                    $row++;
                }
            } elseif ($tipoReporte === 'inventario') {
                $sheet->setCellValue('A1', 'Artículo');
                $sheet->setCellValue('B1', 'Uso Activo');
                $sheet->setCellValue('C1', 'Devuelto (Buen Estado)');
                $sheet->setCellValue('D1', 'Devuelto (Desgaste Medio)');
                $sheet->setCellValue('E1', 'Devuelto (Mal Estado)');
                $row = 2;
                foreach ($datos as $d) {
                    $sheet->setCellValue('A' . $row, $d['articulo'] ?? '');
                    $sheet->setCellValue('B' . $row, $d['uso_activo'] ?? 0);
                    $sheet->setCellValue('C' . $row, $d['devuelto_bueno'] ?? 0);
                    $sheet->setCellValue('D' . $row, $d['devuelto_medio'] ?? 0);
                    $sheet->setCellValue('E' . $row, $d['devuelto_malo'] ?? 0);
                    $row++;
                }
            } elseif ($tipoReporte === 'rendimiento') {
                $sheet->setCellValue('A1', 'Atleta');
                $sheet->setCellValue('B1', 'Torneo');
                $sheet->setCellValue('C1', 'Goles');
                $sheet->setCellValue('D1', 'Asistencias');
                $row = 2;
                foreach ($datos as $d) {
                    $sheet->setCellValue('A' . $row, $d['atleta'] ?? '');
                    $sheet->setCellValue('B' . $row, $d['torneo'] ?? '');
                    $sheet->setCellValue('C' . $row, $d['total_goles'] ?? 0);
                    $sheet->setCellValue('D' . $row, $d['total_asistencias'] ?? 0);
                    $row++;
                }
            } else {
                $sheet->setCellValue('A1', 'Categoría');
                $sheet->setCellValue('B1', 'Atletas Masc. (Activos)');
                $sheet->setCellValue('C1', 'Atletas Masc. (Retirados)');
                $sheet->setCellValue('D1', 'Atletas Fem. (Activas)');
                $sheet->setCellValue('E1', 'Atletas Fem. (Retiradas)');
                $row = 2;
                foreach ($datos as $d) {
                    $sheet->setCellValue('A' . $row, $d['categoria'] ?? '');
                    $sheet->setCellValue('B' . $row, $d['masc_activos'] ?? 0);
                    $sheet->setCellValue('C' . $row, $d['masc_retirados'] ?? 0);
                    $sheet->setCellValue('D' . $row, $d['fem_activos'] ?? 0);
                    $sheet->setCellValue('E' . $row, $d['fem_retirados'] ?? 0);
                    $row++;
                }
            }

            // Aplicar negritas a los encabezados
            $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->getFont()->setBold(true);

            // Autoajustar columnas
            foreach (range('A', $sheet->getHighestColumn()) as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            
            $nombreArchivo = $modulo . "_" . time() . "_" . uniqid() . ".xlsx";
            $subDirectorio = "docs/reportes/" . strtolower($modulo);
            $rutaRelativa = $subDirectorio . "/" . $nombreArchivo;
            $rutaAbsoluta = __DIR__ . "/../../public/" . $rutaRelativa;

            if (!is_dir(dirname($rutaAbsoluta))) {
                mkdir(dirname($rutaAbsoluta), 0755, true);
            }

            $writer->save($rutaAbsoluta);

            return ['accion' => 'reporte', 'archivo' => $rutaRelativa];
        } catch (\Exception $e) {
            return ['accion' => 'error', 'mensaje' => $e->getMessage()];
        }
    }
}
