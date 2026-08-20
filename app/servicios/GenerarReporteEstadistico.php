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
    
    public static function generarExcel(string $tipoReporte, array $datos, string $modulo, $grafico = null)
    {
        $modulo = preg_replace('/[^a-zA-Z0-9]/', '', $modulo);
        try {
            ini_set('memory_limit', '512M');
            set_time_limit(300);

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Reporte Estadistico');

            $formateador = new \IntlDateFormatter('es_ES', \IntlDateFormatter::LONG, \IntlDateFormatter::NONE, 'America/Caracas');
            $formateador->setPattern("d 'de' MMMM, y");
            $fecha_reporte = $formateador->format(new \DateTime());
            $usuario = $_SESSION['nombre'] . ' ' . $_SESSION['apellido'] . ' - ' . $_SESSION['rol'];

            // Encabezado estético del reporte (Centrado)
            $sheet->setCellValue('A1', 'Club Deportivo Moises Torrellas');
            $sheet->setCellValue('A2', 'Reporte Estadístico - ' . strtoupper($tipoReporte));
            $sheet->setCellValue('A3', 'Fecha de Emisión: ' . $fecha_reporte);
            $sheet->setCellValue('A4', 'Generado por: ' . $usuario);
            
            // Determinar la última columna según el tipo de reporte para centrar
            $lastCol = 'E'; // Por defecto para la mayoría (Atletas, Inventario)
            if ($tipoReporte === 'recaudacion' || $tipoReporte === 'rendimiento') {
                $lastCol = 'D'; // Estos tienen 4 columnas (A-D)
            }

            // Unir celdas y centrar el texto del encabezado
            for ($i = 1; $i <= 4; $i++) {
                $sheet->mergeCells("A$i:$lastCol$i");
                $sheet->getStyle("A$i")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            }

            $sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A3:A4')->getFont()->setItalic(true)->setSize(11);

            // Insertar gráfico si existe (primero que la tabla)
            $tempImageFile = null;
            $row = 6; // Iniciar debajo del encabezado
            
            if ($grafico) {
                $imageParts = explode(";base64,", $grafico);
                if (count($imageParts) === 2) {
                    $imageTypeAux = explode("image/", $imageParts[0]);
                    $imageType = $imageTypeAux[1] ?? 'png';
                    $imageBase64 = base64_decode($imageParts[1]);

                    $tempImageFile = sys_get_temp_dir() . '/grafico_' . uniqid() . '.' . $imageType;
                    file_put_contents($tempImageFile, $imageBase64);

                    $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                    $drawing->setName('Gráfico Estadístico');
                    $drawing->setDescription('Gráfico del reporte');
                    $drawing->setPath($tempImageFile);
                    // Colocar el gráfico centrado (aproximadamente en la columna B o C dependiendo del ancho)
                    $drawing->setCoordinates(($lastCol === 'D' ? 'A' : 'B') . $row); 
                    // Establecer una altura que ocupe unas 15-18 filas
                    $drawing->setHeight(280);
                    $drawing->setWorksheet($sheet);
                    
                    // Dejar espacio para la tabla debajo del gráfico (aprox 16 filas)
                    $row += 16;
                }
            } else {
                $row += 2; // Espacio normal si no hay gráfico
            }

            $startTableRow = $row;

            if ($tipoReporte === 'recaudacion') {
                $sheet->setCellValue('A' . $row, 'Concepto');
                $sheet->setCellValue('B' . $row, 'Moneda');
                $sheet->setCellValue('C' . $row, 'Total Cargado');
                $sheet->setCellValue('D' . $row, 'Total Recaudado');
                $row++;
                foreach ($datos as $d) {
                    $sheet->setCellValue('A' . $row, $d['concepto'] ?? '');
                    $sheet->setCellValue('B' . $row, $d['moneda'] ?? '');
                    $sheet->setCellValue('C' . $row, $d['total_cargado'] ?? 0);
                    $sheet->setCellValue('D' . $row, $d['total_recaudado'] ?? 0);
                    $row++;
                }
            } elseif ($tipoReporte === 'inventario') {
                $sheet->setCellValue('A' . $row, 'Artículo');
                $sheet->setCellValue('B' . $row, 'Uso Activo');
                $sheet->setCellValue('C' . $row, 'Devuelto (Buen Estado)');
                $sheet->setCellValue('D' . $row, 'Devuelto (Desgaste Medio)');
                $sheet->setCellValue('E' . $row, 'Devuelto (Mal Estado)');
                $row++;
                foreach ($datos as $d) {
                    $sheet->setCellValue('A' . $row, $d['articulo'] ?? '');
                    $sheet->setCellValue('B' . $row, $d['uso_activo'] ?? 0);
                    $sheet->setCellValue('C' . $row, $d['devuelto_bueno'] ?? 0);
                    $sheet->setCellValue('D' . $row, $d['devuelto_medio'] ?? 0);
                    $sheet->setCellValue('E' . $row, $d['devuelto_malo'] ?? 0);
                    $row++;
                }
            } elseif ($tipoReporte === 'rendimiento') {
                $sheet->setCellValue('A' . $row, 'Atleta');
                $sheet->setCellValue('B' . $row, 'Torneo');
                $sheet->setCellValue('C' . $row, 'Goles');
                $sheet->setCellValue('D' . $row, 'Asistencias');
                $row++;
                foreach ($datos as $d) {
                    $sheet->setCellValue('A' . $row, $d['atleta'] ?? '');
                    $sheet->setCellValue('B' . $row, $d['torneo'] ?? '');
                    $sheet->setCellValue('C' . $row, $d['total_goles'] ?? 0);
                    $sheet->setCellValue('D' . $row, $d['total_asistencias'] ?? 0);
                    $row++;
                }
            } else {
                $sheet->setCellValue('A' . $row, 'Categoría');
                $sheet->setCellValue('B' . $row, 'Atletas Masc. (Activos)');
                $sheet->setCellValue('C' . $row, 'Atletas Masc. (Retirados)');
                $sheet->setCellValue('D' . $row, 'Atletas Fem. (Activas)');
                $sheet->setCellValue('E' . $row, 'Atletas Fem. (Retiradas)');
                $row++;
                foreach ($datos as $d) {
                    $sheet->setCellValue('A' . $row, $d['categoria'] ?? '');
                    $sheet->setCellValue('B' . $row, $d['masc_activos'] ?? 0);
                    $sheet->setCellValue('C' . $row, $d['masc_retirados'] ?? 0);
                    $sheet->setCellValue('D' . $row, $d['fem_activos'] ?? 0);
                    $sheet->setCellValue('E' . $row, $d['fem_retirados'] ?? 0);
                    $row++;
                }
            }

            // Aplicar estilos estéticos a la tabla
            $highestCol = $sheet->getHighestColumn();
            
            // Estilo para la cabecera de la tabla
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1C9B4C'] // Verde del club/tema
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]
                ]
            ];
            $sheet->getStyle('A' . $startTableRow . ':' . $highestCol . $startTableRow)->applyFromArray($headerStyle);

            // Estilo para los datos de la tabla
            if ($row > ($startTableRow + 1)) {
                $dataStyle = [
                    'borders' => [
                        'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    ]
                ];
                $sheet->getStyle('A' . ($startTableRow + 1) . ':' . $highestCol . ($row - 1))->applyFromArray($dataStyle);
            }

            // Autoajustar columnas
            foreach (range('A', $highestCol) as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Configurar página para impresión (ajustar a 1 página de ancho)
            $sheet->getPageSetup()->setFitToPage(true);
            $sheet->getPageSetup()->setFitToWidth(1);
            $sheet->getPageSetup()->setFitToHeight(0);
            
            // Opcional: Centrar horizontalmente en la página impresa
            $sheet->getPageSetup()->setHorizontalCentered(true);


            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            
            $nombreArchivo = $modulo . "_" . time() . "_" . uniqid() . ".xlsx";
            $subDirectorio = "docs/reportes/" . strtolower($modulo);
            $rutaRelativa = $subDirectorio . "/" . $nombreArchivo;
            $rutaAbsoluta = __DIR__ . "/../../public/" . $rutaRelativa;

            if (!is_dir(dirname($rutaAbsoluta))) {
                mkdir(dirname($rutaAbsoluta), 0755, true);
            }

            $writer->save($rutaAbsoluta);

            if ($tempImageFile && file_exists($tempImageFile)) {
                unlink($tempImageFile);
            }

            return ['accion' => 'reporte', 'archivo' => $rutaRelativa];
        } catch (\Exception $e) {
            return ['accion' => 'error', 'mensaje' => $e->getMessage()];
        }
    }
}
