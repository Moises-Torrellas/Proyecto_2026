<?php

namespace App\servicios;

use Dompdf\Dompdf;
use Dompdf\Options;

class GenerarReporte
{
    public static function generarPDF(string $nombreVista, array $datos, string $modulo)
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
            include __DIR__ . "/../vista/reportes/{$nombreVista}.php";
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

    public static function generarExcel(string $nombreVista, array $datos, string $modulo)
    {
        $moduloOriginal = $modulo;
        $modulo = preg_replace('/[^a-zA-Z0-9]/', '', $modulo);
        try {
            ini_set('memory_limit', '512M');
            set_time_limit(300);

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Reporte ' . $modulo);

            $formateador = new \IntlDateFormatter('es_ES', \IntlDateFormatter::LONG, \IntlDateFormatter::NONE, 'America/Caracas');
            $formateador->setPattern("d 'de' MMMM, y");
            $fecha_reporte = $formateador->format(new \DateTime());
            $usuario = $_SESSION['nombre'] . ' ' . $_SESSION['apellido'] . ' - ' . $_SESSION['rol'];

            $titulo_excel = 'Reporte de ' . $modulo;
            if ($moduloOriginal == 'Equipos' && isset($_SESSION['nombre_equipo_reporte'])) {
                $titulo_excel = 'Integrantes del Equipo: ' . $_SESSION['nombre_equipo_reporte'];
            }

            $sheet->setCellValue('A1', 'Club Deportivo Moises Torrellas');
            $sheet->setCellValue('A2', $titulo_excel);
            $sheet->setCellValue('A3', 'Fecha de Emisión: ' . $fecha_reporte);
            $sheet->setCellValue('A4', 'Generado por: ' . $usuario);

            if (empty($datos)) {
                $sheet->setCellValue('A6', 'No hay datos disponibles para generar el reporte.');
            } else {
                // Definir mapeos para los módulos si se requieren nombres de columnas específicos
                $mapeos = [
                    'Atletas' => [
                        'doc_identidad' => 'Cédula/Doc',
                        'nombres' => 'Nombres',
                        'apellidos' => 'Apellidos',
                        'genero' => 'Género',
                        'fecha_nac' => 'Fecha Nac.',
                        'nombre_categoria' => 'Categoría',
                        'nombre_posicion' => 'Posición',
                        'nombre_rep' => 'Representante',
                        'estatus' => 'Estatus'
                    ],
                    'Representantes' => [
                        'cedula' => 'Cédula',
                        'nombre' => 'Nombres',
                        'apellido' => 'Apellidos',
                        'telefono' => 'Teléfono',
                        'direccion' => 'Dirección',
                        'correo' => 'Correo',
                        'instagram' => 'Instagram'
                    ],
                    'Posiciones' => [
                        'nombre' => 'Posición',
                        'abreviatura' => 'Abreviatura',
                        'descripcion' => 'Descripción'
                    ],
                    'Categorías' => [
                        'nombre' => 'Categoría',
                        'edad_minima' => 'Edad Mínima',
                        'edad_maxima' => 'Edad Máxima'
                    ],
                    'Métodos de Pago' => [
                        'nombre' => 'Método',
                        'nec_referencia' => 'Exige Referencia',
                        'estatus' => 'Estatus'
                    ],
                    'Conceptos' => [
                        'nombre' => 'Nombre del Concepto',
                        'monto' => 'Monto',
                        'frecuencia' => 'Frecuencia',
                        'dias_gracia' => 'Días de Gracia',
                        'estatus' => 'Estatus'
                    ],
                    'Monedas' => [
                        'nombre' => 'Nombre de la Moneda',
                        'abreviatura' => 'Abreviatura',
                        'simbolo' => 'Símbolo',
                        'base' => 'Moneda Base',
                        'estatus' => 'Estatus'
                    ],
                    'Torneos' => [
                        'nombre' => 'Torneo',
                        'fecha_inicio' => 'Fecha de Inicio',
                        'fecha_fin' => 'Fecha de Fin',
                        'ubicacion' => 'Ubicación',
                        'estatus' => 'Estatus'
                    ],
                    'Premios' => [
                        'nombre' => 'Premio',
                        'tipo' => 'Tipo'
                    ],
                    'Palmares' => [
                        'atleta_nombres' => 'Nombres',
                        'atleta_apellidos' => 'Apellidos',
                        'nombre_equipo' => 'Equipo',
                        'nombre_premio' => 'Premio',
                        'nombre_torneo' => 'Torneo',
                        'fecha_torneo' => 'Fecha de Torneo'
                    ],
                    'Equipos' => [
                        'doc_i' => 'Cédula/Doc.',
                        'nombre' => 'Nombres y Apellidos',
                        'categoria' => 'Categoría',
                        'posicion' => 'Posición'
                    ],
                    'Estadisticas' => [
                        'nombres' => 'Nombres',
                        'apellidos' => 'Apellidos',
                        'torneo_nombre' => 'Torneo',
                        'fecha_inicio' => 'Fecha de Inicio',
                        'partidos_jugados' => 'Partidos Jugados',
                        'goles' => 'Goles',
                        'asistencias' => 'Asistencias',
                        'goles_contra' => 'Goles Contra',
                        'penalizaciones' => 'Penalizaciones',
                        'average' => 'Average'
                    ]
                ];

                $columnas = $mapeos[$moduloOriginal] ?? [];
                
                // Si el módulo no tiene un mapeo definido, se generan las columnas dinámicamente
                if (empty($columnas)) {
                    $keys = array_keys($datos[0]);
                    foreach ($keys as $key) {
                        $columnas[$key] = ucwords(str_replace('_', ' ', $key));
                    }
                }

                $numCols = count($columnas);
                $colLetraFin = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($numCols);

                $sheet->mergeCells("A1:{$colLetraFin}1");
                $sheet->mergeCells("A2:{$colLetraFin}2");
                $sheet->mergeCells("A3:{$colLetraFin}3");
                $sheet->mergeCells("A4:{$colLetraFin}4");
                $sheet->getStyle('A1:A4')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A3:A4')->getFont()->setItalic(true)->setSize(11);

                $row = 6;
                $colIndex = 1;
                foreach ($columnas as $key => $headerText) {
                    $colString = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                    $sheet->setCellValue($colString . $row, $headerText);
                    $colIndex++;
                }

                $headerStyle = [
                    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FF1C9B4C']
                    ]
                ];
                $sheet->getStyle("A{$row}:{$colLetraFin}{$row}")->applyFromArray($headerStyle);
                $row++;

                // Aplanado y preparación de datos
                $datosEstructurados = [];
                if ($moduloOriginal === 'Palmares') {
                    foreach ($datos as $r) {
                        foreach ($r['premios'] as $premio) {
                            $subFila = [];
                            if (isset($r['atleta_nombres'])) {
                                $subFila['atleta_nombres'] = $r['atleta_nombres'];
                                $subFila['atleta_apellidos'] = $r['atleta_apellidos'];
                            } else {
                                $subFila['nombre_equipo'] = $r['nombre_equipo'];
                            }
                            $subFila['nombre_premio'] = $premio['nombre_premio'];
                            $subFila['nombre_torneo'] = $premio['nombre_torneo'];
                            $subFila['fecha_torneo'] = $premio['fecha_torneo'];
                            $datosEstructurados[] = $subFila;
                        }
                    }
                } else {
                    $datosEstructurados = $datos;
                }

                foreach ($datosEstructurados as $d) {
                    $colIndex = 1;
                    foreach ($columnas as $key => $headerText) {
                        $colString = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                        $val = $d[$key] ?? '';
                        
                        // Lógica específica para Atletas
                        if ($moduloOriginal == 'Atletas') {
                            if ($key == 'nombre_rep' && !empty($d['nombre_rep'])) {
                                $val = trim($d['nombre_rep'] . ' ' . ($d['apellido_rep'] ?? ''));
                            } elseif ($key == 'genero') {
                                $val = ($val == 'H') ? 'Hombre' : 'Mujer';
                            }
                        }

                        // Lógica específica para Posiciones
                        if ($moduloOriginal == 'Posiciones') {
                            if ($key == 'descripcion' && empty($val)) {
                                $val = 'Sin Descripción';
                            }
                        }

                        // Lógica específica para Métodos de Pago
                        if ($moduloOriginal == 'Métodos de Pago') {
                            if ($key == 'nec_referencia') {
                                $val = ($val == 1) ? 'Sí' : 'No';
                            }
                        }

                        // Lógica específica para Conceptos
                        if ($moduloOriginal == 'Conceptos') {
                            if ($key == 'monto') {
                                $val = number_format((float)$val, 2, ',', '.');
                            } elseif ($key == 'frecuencia') {
                                $frecuencia_db = strtoupper($val);
                                $val = match($frecuencia_db) {
                                    'L' => 'Libre',
                                    'M' => 'Mensual',
                                    'A' => 'Anual',
                                    'U' => 'Única',
                                    'T' => 'Multa',
                                    default => $frecuencia_db
                                };
                            } elseif ($key == 'dias_gracia') {
                                $val = ($val == 0) ? 'No Aplica' : $val . ' Días';
                            }
                        }

                        // Conversión especial para Premios -> tipo
                        if ($moduloOriginal === 'Premios' && $key === 'tipo') {
                            $val = (strtoupper(trim($val)) === 'I') ? 'Individual' : 'Grupal';
                        }

                        // Lógica específica para Monedas
                        if ($moduloOriginal == 'Monedas') {
                            if ($key == 'base') {
                                $val = ($val == 1) ? 'Sí' : 'No';
                            }
                        }

                        // Lógica específica para Torneos
                        if ($moduloOriginal == 'Torneos') {
                            if ($key == 'estatus') {
                                $val = match($val) {
                                    1 => 'Por Disputarse',
                                    2 => 'En Curso',
                                    3 => 'Finalizado',
                                    default => $val
                                };
                            }
                        }

                        // Lógica general de Estatus
                        if ($key == 'estatus' && is_numeric($val) && $moduloOriginal != 'Torneos') {
                            $val = ($val == 1) ? 'Activo' : 'Inactivo/Retirado';
                        }
                        
                        $sheet->setCellValue($colString . $row, $val);
                        $colIndex++;
                    }
                    $row++;
                }

                for ($i = 1; $i <= $numCols; $i++) {
                    $colString = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
                    $sheet->getColumnDimension($colString)->setAutoSize(true);
                }
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
