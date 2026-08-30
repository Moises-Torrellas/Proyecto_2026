<?php

namespace App\servicios;

use Dompdf\Dompdf;
use Dompdf\Options;

class GenerarReporte
{
    public static function generarPDF(string $nombreVista, array $datos, string $modulo, array $parametrosExtra = [], string $orientacion = 'portrait')
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
            
            extract($parametrosExtra); // Extrae titulo, filtros, etc.
            
            include __DIR__ . "/../vista/reportes/{$nombreVista}.php";
            $html = ob_get_clean();

            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', $orientacion);
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

    public static function generarExcel(string $nombreVista, array $datos, string $modulo, array $parametrosExtra = [])
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

            $titulo_excel = $parametrosExtra['titulo'] ?? ('Reporte de ' . $modulo);
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
                        'edad' => 'Edad (Cálculo Automático)', // Se calculará abajo
                        'nombre_categoria' => 'Categoría',
                        'nombre_posicion' => 'Posición',
                        'nombre_rep' => 'Representante',
                        'estatus' => 'Estatus',
                        'peso_kg' => 'Peso (Kg)',
                        'estatura_cm' => 'Estatura (cm)',
                        'dorsal' => 'Dorsal',
                        'fecha_ingreso' => 'Fecha Ingreso',
                        'lugar_nacimiento' => 'Lugar Nac.',
                        'correo' => 'Correo',
                        'instagram' => 'Instagram',
                        'municipio' => 'Municipio',
                        'talla_pantalon' => 'Talla Pantalón',
                        'talla_franela' => 'Talla Franela',
                        'talla_calzado' => 'Talla Calzado',
                        'tipo_sangre' => 'Tipo Sangre',
                        'es_alergico' => 'Es Alérgico (1=Sí/0=No)',
                        'alergias_detalle' => 'Detalle Alergias',
                        'fecha_retiro' => 'Fecha Retiro',
                        'motivo_retiro' => 'Motivo Retiro',
                        'fecha_reingreso' => 'Fecha Reingreso'
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
                        'edad_min' => 'Edad Mínima',
                        'edad_max' => 'Edad Máxima'
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
                    ],
                    'CuentasCobrar' => [
                        'atleta_nombre' => 'Nombres',
                        'atleta_apellido' => 'Apellidos',
                        'concepto_nombre' => 'Concepto',
                        'fecha_emision' => 'Emisión',
                        'monto_total' => 'Monto Total',
                        'monto_pendiente' => 'Pendiente',
                        'estatus' => 'Estatus'
                    ],
                    'Pagos' => [
                        'fecha_pago' => 'Fecha de Pago',
                        'referencia' => 'Referencia General',
                        'nombre_metodo_pago' => 'Método de Pago',
                        'monto_pagado' => 'Monto Pagado',
                        'concepto_pago' => 'Concepto',
                        'atleta' => 'Atleta',
                        'concepto_detalle' => 'Concepto Abonado',
                        'monto_abono' => 'Monto Abonado',
                        'estatus' => 'Estatus'
                    ],
                    'InventarioFisico' => [
                        'codigo_club' => 'Código Interno',
                        'articulo' => 'Artículo',
                        'categoria' => 'Categoría',
                        'estado_fisico' => 'Condición Física',
                        'estatus_txt' => 'Estatus'
                    ],
                    'Catalogo' => [
                        'nombre' => 'Nombre del Artículo',
                        'categoria_nombre' => 'Categoría',
                        'talla' => 'Talla',
                        'stock_minimo' => 'Stock Mínimo',
                        'stock_actual' => 'Stock Actual'
                    ],
                    'Bitacora' => [
                        'fecha' => 'Fecha',
                        'hora' => 'Hora',
                        'cedulaUsuario' => 'Doc. Usuario',
                        'nombreUsuario' => 'Nom. Usuario',
                        'apellidoUsuario' => 'Ape. Usuario',
                        'nombre_modulo' => 'Módulo',
                        'acciones' => 'Acción',
                        'datos_previos' => 'Datos Previos',
                        'datos_nuevos' => 'Datos Nuevos'
                    ],
                    'Asignaciones' => [
                        'doc_identidad' => 'Cédula/Doc.',
                        'atleta' => 'Atleta',
                        'articulo' => 'Artículo',
                        'codigo_club' => 'Código Interno',
                        'fecha_asignacion' => 'Fecha de Asignación',
                        'estatus_txt' => 'Estatus'
                    ],
                    'Devoluciones' => [
                        'doc_identidad' => 'Cédula/Doc.',
                        'nombre_completo' => 'Atleta',
                        'codigo_club' => 'Código Interno',
                        'articulo_nombre' => 'Artículo',
                        'fecha_vista' => 'Fecha de Devolución',
                        'estado_fisico' => 'Condición Física',
                        'observacion' => 'Observación'
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
                } elseif ($moduloOriginal === 'Pagos') {
                    foreach ($datos as $r) {
                        if (!empty($r['detalles'])) {
                            foreach ($r['detalles'] as $det) {
                                $subFila = $r;
                                $subFila['atleta'] = $det['atleta'] ?? 'N/A';
                                $subFila['concepto_detalle'] = $det['concepto'] ?? 'N/A';
                                $subFila['monto_abono'] = number_format($det['monto'] ?? 0, 2, ',', '.') . ' ' . ($det['moneda'] ?? '');
                                $datosEstructurados[] = $subFila;
                            }
                        } else {
                            $r['atleta'] = 'N/A';
                            $r['concepto_detalle'] = 'N/A';
                            $r['monto_abono'] = '0,00';
                            $datosEstructurados[] = $r;
                        }
                    }
                } elseif ($moduloOriginal === 'Devoluciones') {
                    foreach ($datos as $atleta) {
                        if (!empty($atleta['devoluciones'])) {
                            foreach ($atleta['devoluciones'] as $dev) {
                                $subFila = [];
                                $subFila['doc_identidad'] = $atleta['doc_identidad'] ?? 'Sin CI';
                                $subFila['nombre_completo'] = $atleta['nombre_completo'] ?? 'Desconocido';
                                $subFila['codigo_club'] = $dev['codigo_club'] ?? 'N/A';
                                $subFila['articulo_nombre'] = $dev['articulo_nombre'] ?? 'N/A';
                                $subFila['fecha_vista'] = $dev['fecha_vista'] ?? 'N/A';
                                $subFila['estado_fisico'] = $dev['estado_fisico'] ?? 'N/A';
                                $subFila['observacion'] = $dev['observacion'] ?? 'Sin observaciones';
                                $datosEstructurados[] = $subFila;
                            }
                        }
                    }
                } elseif ($moduloOriginal === 'InventarioFisico') {
                    foreach ($datos as $grupo) {
                        if (!empty($grupo['piezas'])) {
                            foreach ($grupo['piezas'] as $pieza) {
                                $subFila = [];
                                $subFila['codigo_club'] = $pieza['codigo_club'] ?? 'S/C';
                                $subFila['articulo'] = $grupo['articulo'] ?? 'S/N';
                                $subFila['categoria'] = $grupo['categoria'] ?? 'S/C';
                                $subFila['estado_fisico'] = $pieza['estado_fisico'] ?? 'S/E';
                                
                                if (isset($pieza['estatus'])) {
                                    if ($pieza['estatus'] == 1) {
                                        $subFila['estatus_txt'] = 'Disponible';
                                    } elseif ($pieza['estatus'] == 2) {
                                        $subFila['estatus_txt'] = 'En Uso';
                                    } else {
                                        $subFila['estatus_txt'] = 'Inactivo';
                                    }
                                } else {
                                    $subFila['estatus_txt'] = 'S/E';
                                }
                                $datosEstructurados[] = $subFila;
                            }
                        }
                    }
                } elseif ($moduloOriginal === 'Asignaciones') {
                    foreach ($datos as $grupo) {
                        if (!empty($grupo['asignaciones'])) {
                            foreach ($grupo['asignaciones'] as $asig) {
                                $subFila = [];
                                $subFila['doc_identidad'] = $grupo['doc_identidad'] ?? 'S/C';
                                $subFila['atleta'] = $grupo['nombre_completo'] ?? 'S/N';
                                $subFila['articulo'] = $asig['articulo'] ?? 'S/A';
                                $subFila['codigo_club'] = $asig['codigo_club'] ?? 'S/C';
                                $subFila['fecha_asignacion'] = $asig['fecha_real'] ?? ($asig['fecha_vista'] ?? '');
                                
                                if (isset($asig['estatus'])) {
                                    if ($asig['estatus'] == 3) {
                                        $subFila['estatus_txt'] = 'Anulado';
                                    } elseif ($asig['estatus'] == 2) {
                                        $subFila['estatus_txt'] = 'Devuelto';
                                    } else {
                                        $subFila['estatus_txt'] = 'En Uso';
                                    }
                                } else {
                                    $subFila['estatus_txt'] = 'S/E';
                                }
                                $datosEstructurados[] = $subFila;
                            }
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
                            } elseif ($key == 'estatus') {
                                $val = ((int)$val === 1) ? 'Activo' : 'Retirado';
                            } elseif ($key == 'edad' && !empty($d['fecha_nac'])) {
                                $anioNacimiento = date('Y', strtotime($d['fecha_nac']));
                                $anioActual = date('Y');
                                $val = $anioActual - $anioNacimiento;
                            } elseif ($key == 'es_alergico') {
                                $val = ((int)$val === 1) ? 'Sí' : 'No';
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
                        if ($key == 'estatus' && is_numeric($val) && !in_array($moduloOriginal, ['Torneos', 'CuentasCobrar', 'Pagos'])) {
                            $val = ($val == 1) ? 'Activo' : 'Inactivo/Retirado';
                        }
                        
                        // Lógica específica para CuentasCobrar
                        if ($moduloOriginal == 'CuentasCobrar') {
                            if ($key == 'estatus') {
                                if ((int)$val === 3) {
                                    $val = 'Anulado';
                                } elseif ((int)$val === 2 || (float)($d['monto_pendiente'] ?? 0) == 0) {
                                    $val = 'Pagado';
                                } elseif ((float)($d['monto_pendiente'] ?? 0) < (float)($d['monto_total'] ?? 0)) {
                                    $val = 'Abonado';
                                } else {
                                    $val = 'Pendiente';
                                }
                            } elseif ($key == 'monto_total' || $key == 'monto_pendiente') {
                                $simbolo = $d['moneda_simbolo'] ?? '';
                                $val = number_format((float)$val, 2, ',', '.') . ' ' . $simbolo;
                            } elseif ($key == 'fecha_emision') {
                                $val = date('d/m/Y', strtotime($val));
                            }
                        }
                        
                        // Lógica específica para Pagos
                        if ($moduloOriginal == 'Pagos') {
                            if ($key == 'estatus') {
                                $val = ((int)$val === 1) ? 'Realizado' : 'Anulado';
                            } elseif ($key == 'monto_pagado') {
                                $val = number_format((float)$val, 2, ',', '.') . ' ' . ($d['abre'] ?? '');
                            } elseif ($key == 'fecha_pago') {
                                $val = date('d/m/Y', strtotime($val));
                            }
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
