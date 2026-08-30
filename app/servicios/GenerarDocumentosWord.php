<?php

namespace App\servicios;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Converter;

class GenerarDocumentosWord
{
    // Estilo global para evitar saltos de línea indeseados (elimina el espacio debajo de los párrafos)
    private static $pStyle = ['spaceAfter' => 0, 'spaceBefore' => 0];

    /**
     * Agrega una línea separadora horizontal al documento
     */
    private static function addSeparatorLine($section)
    {
        $section->addTextBreak(1, ['size' => 4], self::$pStyle);
        $section->addLine([
            'weight' => 1,
            'width' => Converter::cmToPoint(17),
            'height' => 0,
            'color' => '000000'
        ]);
    }

    public static function GenerarFichaDeportiva(array $datos, string $modulo, string $anio_inicio)
    {
        $modulo = preg_replace('/[^a-zA-Z0-9]/', '', $modulo);
        try {
            ini_set('memory_limit', '512M');
            set_time_limit(300);

            $phpWord = new PhpWord();
            
            // Márgenes reducidos para aprovechar la hoja al máximo - Tamaño Carta
            $section = $phpWord->addSection([
                'pageSizeW' => Converter::cmToTwip(21.59),
                'pageSizeH' => Converter::cmToTwip(27.94),
                'marginLeft' => Converter::cmToTwip(1.5),
                'marginRight' => Converter::cmToTwip(1.5),
                'marginTop' => Converter::cmToTwip(0.8),
                'marginBottom' => Converter::cmToTwip(1.0),
            ]);

            $atleta = $datos['atleta'];
            $torneos = $datos['torneos'];

            // Logica Correos
            $edad = self::calcularEdad($atleta['fecha_nac']);
            $correoMostrar = "";
            if ($edad >= 18) {
                $correoMostrar = !empty($atleta['correo_atleta']) ? $atleta['correo_atleta'] : ($atleta['correo_representante'] ?? '');
            } else {
                if (!empty($atleta['correo_atleta']) && !empty($atleta['correo_representante'])) {
                    $correoMostrar = $atleta['correo_atleta'] . ' , ' . $atleta['correo_representante'];
                } else if (!empty($atleta['correo_atleta'])) {
                    $correoMostrar = $atleta['correo_atleta'];
                } else {
                    $correoMostrar = $atleta['correo_representante'] ?? '';
                }
            }

            $pCenter = array_merge(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER], self::$pStyle);
            $pRight = array_merge(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT], self::$pStyle);

            // CABECERA (Header) - Membrete 3 para Alto Rendimiento
            $header = $section->addHeader();
            $rutaMembrete3 = __DIR__ . '/../../public/img/Menbrete 3.png';
            if (file_exists($rutaMembrete3)) {
                $header->addImage($rutaMembrete3, [
                    'width' => Converter::cmToPoint(18), 
                    'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER
                ]);
            }

            // Ancho total disponible en la página (en twips): Letter ~21cm - 3cm márgenes = 18cm
            $anchoTotalTwips = Converter::cmToTwip(18);

            // Título: Nombre del Atleta y Disciplina con Foto a la derecha
            $tableNombre = $section->addTable([
                'width' => 100 * 50,
                'unit' => 'pct',
                'cellMargin' => 0,
            ]);
            $tableNombre->addRow();
            
            // Celda de texto (nombre + disciplina) - ocupa mayor parte
            $cellText = $tableNombre->addCell(
                Converter::cmToTwip(14.5),
                ['valign' => 'center']
            );
            $nombresApellidos = strtoupper($atleta['nombres'] . " " . $atleta['apellidos']);
            $nombresEspaciados = implode(' ', str_split(str_replace(' ', '  ', $nombresApellidos)));
            
            $cellText->addText($nombresEspaciados, ['bold' => true, 'size' => 14, 'name' => 'Arial'], $pCenter);
            $cellText->addText("HOCKEY EN LÍNEA", ['bold' => true, 'size' => 12, 'name' => 'Arial'], $pCenter);
            
            // Celda de la foto con borde y centrado
            $cellFoto = $tableNombre->addCell(
                Converter::cmToTwip(3.5),
                [
                    'valign' => 'center',
                    'borderSize' => 6,
                    'borderColor' => '000000',
                ]
            );
            $rutaFoto = __DIR__ . '/../../public/img/atletas/' . $atleta['foto'];
            if (file_exists($rutaFoto) && $atleta['foto'] !== 'default.png') {
                $cellFoto->addImage($rutaFoto, [
                    'width' => 80,
                    'height' => 100,
                    'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                    'marginLeft' => Converter::cmToPoint(0.3),
                    'marginTop' => Converter::cmToPoint(0.2),
                ]);
            } else {
                $cellFoto->addText("Foto", ['size' => 10, 'name' => 'Arial'], $pCenter);
            }

            $section->addTextBreak(1, ['size' => 6], self::$pStyle);

            // Personal Info
            $fontNormal = ['size' => 9, 'name' => 'Arial'];
            $fontBoldLabel = ['size' => 9, 'name' => 'Arial', 'bold' => true];
            $sexo = (isset($atleta['genero']) && $atleta['genero'] == 'F') ? 'Femenino' : 'Masculino';
            $fechaNacObj = new \DateTime($atleta['fecha_nac']);
            $fechaNacFormatted = $fechaNacObj->format('d/m/Y');
            $direccion = !empty($atleta['direccion']) ? $atleta['direccion'] : 'No registrada';
            $telefonoStr = !empty($atleta['telefono']) ? $atleta['telefono'] : 'No registrado';
            $municipioStr = !empty($atleta['municipio']) ? $atleta['municipio'] : 'No registrado';
            $lugarNacimiento = !empty($atleta['lugar_nacimiento']) ? $atleta['lugar_nacimiento'] : 'No registrado';
            $tipoSangre = !empty($atleta['tipo_sangre']) ? $atleta['tipo_sangre'] : 'No registrado';
            $esAlergico = (isset($atleta['es_alergico']) && $atleta['es_alergico'] == 1) ? 'Si' : 'No';
            $alergiasDetalle = !empty($atleta['alergias_detalle']) ? $atleta['alergias_detalle'] : 'No aplica';

            // Línea 1: Cedula, Edad, Sexo (en una sola línea con tabulación)
            $tr1 = $section->addTextRun(self::$pStyle);
            $tr1->addText("Cedula de Identidad: ", $fontBoldLabel);
            $tr1->addText($atleta['doc_identidad'] . "       ", $fontNormal);
            $tr1->addText("Edad: ", $fontBoldLabel);
            $tr1->addText($edad . " años       ", $fontNormal);
            $tr1->addText("Sexo: ", $fontBoldLabel);
            $tr1->addText($sexo, $fontNormal);

            // Línea 2: Fecha y lugar de nacimiento
            $tr2 = $section->addTextRun(self::$pStyle);
            $tr2->addText("Fecha y lugar de nacimiento: ", $fontBoldLabel);
            $tr2->addText($fechaNacFormatted . " " . $lugarNacimiento, $fontNormal);

            // Línea 3: Dirección
            $tr3 = $section->addTextRun(self::$pStyle);
            $tr3->addText("Dirección: ", $fontBoldLabel);
            $tr3->addText($direccion, $fontNormal);

            // Línea 4: Municipio
            $tr4 = $section->addTextRun(self::$pStyle);
            $tr4->addText("Municipio: ", $fontBoldLabel);
            $tr4->addText($municipioStr, $fontNormal);

            // Línea 5: Correo
            $tr5 = $section->addTextRun(self::$pStyle);
            $tr5->addText("Correo: ", $fontBoldLabel);
            $tr5->addText($correoMostrar, $fontNormal);

            // Línea 6: Teléfono
            $tr6 = $section->addTextRun(self::$pStyle);
            $tr6->addText("Teléfono: ", $fontBoldLabel);
            $tr6->addText($telefonoStr, $fontNormal);

            // Línea 7: Tipo de sangre y alergias
            $tr7 = $section->addTextRun(self::$pStyle);
            $tr7->addText("Tipo de sangre: ", $fontBoldLabel);
            $tr7->addText($tipoSangre . ",  ", $fontNormal);
            $tr7->addText("Es Alérgico: ", $fontBoldLabel);
            $tr7->addText($esAlergico . ",    ", $fontNormal);
            $tr7->addText("Especificar: ", $fontBoldLabel);
            $tr7->addText($alergiasDetalle . ".", $fontNormal);

            // ============ SEPARADOR: DATOS DEL ENTRENADOR ============
            self::addSeparatorLine($section);

            $section->addText("D A T O S   D E L   E N T R E N A D O R", ['bold' => true, 'size' => 10, 'name' => 'Arial'], self::$pStyle);
            
            $trEnt1 = $section->addTextRun(self::$pStyle);
            $trEnt1->addText("Nombre y apellidos: ", $fontBoldLabel);
            $trEnt1->addText("Gonzalo Villaverde               ", $fontNormal);
            $trEnt1->addText("C.I: ", $fontBoldLabel);
            $trEnt1->addText("17.011.775", $fontNormal);

            $trEnt2 = $section->addTextRun(self::$pStyle);
            $trEnt2->addText("Teléfono: ", $fontBoldLabel);
            $trEnt2->addText("0412 519 5639   ", $fontNormal);
            $trEnt2->addText("Correo: ", $fontBoldLabel);
            $trEnt2->addText("dorfgrun@gmail.com", $fontNormal);
            
            // ============ SEPARADOR: EVENTOS Y RESULTADOS ============
            self::addSeparatorLine($section);

            $tituloEventos = "E V E N T O S   Y   R E S U L T A D O S";
            if (!empty($anio_inicio)) {
                $tituloEventos .= "  " . implode(' ', str_split($anio_inicio));
            }
            $section->addText($tituloEventos, ['bold' => true, 'size' => 10, 'name' => 'Arial'], self::$pStyle);

            // Historial con viñetas (bullets)
            $fontBold9 = ['bold' => true, 'size' => 9, 'name' => 'Arial'];
            foreach ($torneos as $torneo) {
                $anioTorneo = (new \DateTime($torneo['fecha_fin']))->format('Y');
                $textoLogroEquipo = $torneo['premios_grupales'] ? implode(', ', $torneo['premios_grupales']) : 'N/A';
                $textoLogroInd = $torneo['premios_individuales'] ? implode(', ', $torneo['premios_individuales']) : 'N/A';
                
                $listItemRun = $section->addListItemRun(0, ['listType' => \PhpOffice\PhpWord\Style\ListItem::TYPE_BULLET_FILLED], self::$pStyle);
                $listItemRun->addText("{$anioTorneo}, ", $fontBold9);
                $listItemRun->addText("{$torneo['nombre_torneo']}. Posición: {$atleta['nombre_posicion']}. Categoría: {$atleta['nombre_categoria']}. ", $fontNormal);
                $listItemRun->addText("Logro de equipo: ", $fontBold9);
                $listItemRun->addText("{$textoLogroEquipo}. ", $fontNormal);
                $listItemRun->addText("Logro Individual: ", $fontBold9);
                $listItemRun->addText("{$textoLogroInd}.", $fontNormal);
            }
            
            // ============ SEPARADOR: DATO BANCARIO ============
            self::addSeparatorLine($section);

            $section->addText("D A T O   B A N C A R I O", ['bold' => true, 'size' => 10, 'name' => 'Arial'], self::$pStyle);
            $section->addText("Número de cuenta del Banco de Venezuela", $fontNormal, self::$pStyle);
            $section->addText("0102 0308 2100 0102 1596", ['bold' => true, 'size' => 14, 'name' => 'Arial'], self::$pStyle);

            // ============ SEPARADOR: ANEXAR DOCUMENTACION ============
            self::addSeparatorLine($section);

            $section->addText("A N E X A R", ['bold' => true, 'size' => 10, 'name' => 'Arial'], $pCenter);
            $section->addText("D O C U M E N T A C I O N", ['bold' => true, 'size' => 10, 'name' => 'Arial'], $pCenter);
            
            $section->addListItem("Copia de la cedula de identidad.", 0, $fontNormal, \PhpOffice\PhpWord\Style\ListItem::TYPE_BULLET_FILLED, self::$pStyle);
            $section->addListItem("Certificación Bancaria. Ficha Deportiva.", 0, $fontNormal, \PhpOffice\PhpWord\Style\ListItem::TYPE_BULLET_FILLED, self::$pStyle);
            $section->addListItem("Oficio de Postulación por la asociación.", 0, $fontNormal, \PhpOffice\PhpWord\Style\ListItem::TYPE_BULLET_FILLED, self::$pStyle);

            $section->addTextBreak(2, null, self::$pStyle);
            $section->addText("Firma y sello de la Asociación", ['bold' => true, 'size' => 11, 'name' => 'Arial'], $pRight);

            $nombreArchivo = $modulo . "_FichaAltoRendimiento.docx";
            return self::guardarDocumento($phpWord, $nombreArchivo);
        } catch (\Exception $e) {
            return ['accion' => 'error', 'mensaje' => $e->getMessage()];
        }
    }

    public static function GenerarFichaTecnica(array $datos, string $modulo)
    {
        $modulo = preg_replace('/[^a-zA-Z0-9]/', '', $modulo);
        try {
            ini_set('memory_limit', '512M');
            set_time_limit(300);

            $phpWord = new PhpWord();
            // Tamaño Carta
            $section = $phpWord->addSection([
                'pageSizeW' => Converter::cmToTwip(21.59),
                'pageSizeH' => Converter::cmToTwip(27.94),
                'marginLeft' => Converter::cmToTwip(1.2),
                'marginRight' => Converter::cmToTwip(1.2),
                'marginTop' => Converter::cmToTwip(1.0),
                'marginBottom' => Converter::cmToTwip(1.0),
            ]);

            $atleta = $datos['atleta'];
            $edad = self::calcularEdad($atleta['fecha_nac']);
            $correoMostrar = "";
            if ($edad >= 18) {
                $correoMostrar = !empty($atleta['correo_atleta']) ? $atleta['correo_atleta'] : ($atleta['correo_representante'] ?? '');
            } else {
                if (!empty($atleta['correo_atleta']) && !empty($atleta['correo_representante'])) {
                    $correoMostrar = $atleta['correo_atleta'] . ' , ' . $atleta['correo_representante'];
                } else if (!empty($atleta['correo_atleta'])) {
                    $correoMostrar = $atleta['correo_atleta'];
                } else {
                    $correoMostrar = $atleta['correo_representante'] ?? '';
                }
            }

            $fontLabel = ['size' => 7, 'name' => 'Times New Roman', 'bold' => true];
            $fontValue = ['size' => 9, 'name' => 'Times New Roman'];
            $fontValueSmall = ['size' => 8, 'name' => 'Times New Roman'];

            // CABECERA (Header) - Logo FVP + Texto + Logo Olímpico
            $header = $section->addHeader();
            $headerTable = $header->addTable(['width' => 100 * 50, 'unit' => 'pct']);
            $headerTable->addRow();
            
            // Columna 1: Logo FVP (izquierda)
            $col1 = $headerTable->addCell(2500, ['valign' => 'center']);
            $rutaMembrete1 = __DIR__ . '/../../public/img/Menbrete 1.png';
            if (file_exists($rutaMembrete1)) {
                $col1->addImage($rutaMembrete1, [
                    'width' => 75,
                    'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER
                ]);
            }
            
            // Columna 2: Texto central
            $col2 = $headerTable->addCell(5000, ['valign' => 'center']);
            $col2->addText(
                "FEDERACIÓN VENEZOLANA DE PATINAJE",
                ['size' => 11, 'bold' => true, 'color' => '808080', 'name' => 'Times New Roman'],
                array_merge(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER], self::$pStyle)
            );
            $col2->addText(
                "RIF. J-30492888-2",
                ['size' => 8, 'color' => '808080', 'name' => 'Times New Roman'],
                array_merge(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER], self::$pStyle)
            );

            // Columna 3: Logo olímpico (derecha)
            $col3 = $headerTable->addCell(2500, ['valign' => 'center']);
            $rutaMembrete2 = __DIR__ . '/../../public/img/Menbrete 2.png';
            if (file_exists($rutaMembrete2)) {
                $col3->addImage($rutaMembrete2, [
                    'width' => 80,
                    'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER
                ]);
            }

            // Línea separadora debajo del header
            $section->addLine(['weight' => 1, 'width' => Converter::cmToPoint(18.6), 'height' => 0, 'color' => '000000']);
            $section->addTextBreak(1, ['size' => 2], self::$pStyle);

            // ======= TABLA PRINCIPAL: FICHA TÉCNICA =======
            // Anchos de columna en twips (total ~18.6cm = ~10574 twips)
            $anchoTotal = Converter::cmToTwip(18.6);
            $colLabel = Converter::cmToTwip(3.2);      // columna etiqueta
            $colVal1 = Converter::cmToTwip(3.0);       // columna valor 1
            $colVal2 = Converter::cmToTwip(3.2);       // columna valor 2
            $colVal3 = Converter::cmToTwip(3.2);       // columna valor 3
            $colFoto = Converter::cmToTwip(3.5);       // columna foto (más ancha)
            // Ajustar para que sumen el total
            $colVal3 = $anchoTotal - $colLabel - $colVal1 - $colVal2 - $colFoto;

            $styleTable = [
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => 40,
            ];
            $phpWord->addTableStyle('FichaTable', $styleTable);
            
            $table = $section->addTable('FichaTable');
            
            // Row 1: FICHA TECNICA (título centrado, ocupa todas las columnas)
            $table->addRow();
            $table->addCell(
                $anchoTotal,
                ['gridSpan' => 5, 'valign' => 'center', 'borderBottomSize' => 12, 'borderBottomColor' => '000000']
            )->addText(
                'FICHA TECNICA',
                ['bold' => true, 'size' => 14, 'name' => 'Times New Roman'],
                array_merge(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER], self::$pStyle)
            );

            // Row 2: DATOS PERSONALES
            $table->addRow();
            $table->addCell($anchoTotal, ['gridSpan' => 5])->addText(
                'DATOS PERSONALES',
                ['size' => 7, 'name' => 'Times New Roman', 'bold' => true],
                self::$pStyle
            );

            // Row 3: NOMBRE + foto (inicio merge vertical)
            $table->addRow();
            $table->addCell($colLabel)->addText('NOMBRE', $fontLabel, self::$pStyle);
            $nombreCompleto = ucwords(strtolower($atleta['nombres'] . " " . $atleta['apellidos']));
            $table->addCell($colVal1 + $colVal2 + $colVal3, ['gridSpan' => 3, 'valign' => 'center'])->addText(
                $nombreCompleto,
                ['size' => 10, 'name' => 'Times New Roman'],
                array_merge(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER], self::$pStyle)
            );
            
            // Celda de foto con merge vertical y centrado
            $cellFoto = $table->addCell($colFoto, [
                'vMerge' => 'restart',
                'valign' => 'center',
            ]);
            $rutaFoto = __DIR__ . '/../../public/img/atletas/' . $atleta['foto'];
            if (file_exists($rutaFoto) && $atleta['foto'] !== 'default.png') {
                $cellFoto->addImage($rutaFoto, [
                    'width' => 85,
                    'height' => 105,
                    'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                    'marginTop' => Converter::cmToPoint(0.2),
                ]);
            }

            // Row 4: CEDULA DE IDENTIDAD + PASAPORTE
            $table->addRow();
            $table->addCell($colLabel)->addText('CEDULA DE IDENTIDAD', $fontLabel, self::$pStyle);
            $table->addCell($colVal1)->addText($atleta['doc_identidad'], $fontValue, self::$pStyle);
            $table->addCell($colVal2)->addText("PASAPORTE:", $fontLabel, self::$pStyle);
            $table->addCell($colVal3)->addText('', $fontValue, self::$pStyle);
            $table->addCell($colFoto, ['vMerge' => 'continue']);

            // Row 5: EDAD, FECHA NAC, GENERO, TIPO SANGRE
            $fechaNacObj = new \DateTime($atleta['fecha_nac']);
            $fechaNacFormatted = $fechaNacObj->format('d/m/Y');
            $sexo = (isset($atleta['genero']) && $atleta['genero'] == 'F') ? 'Femenino' : 'Masculino';
            $tipoSangre = !empty($atleta['tipo_sangre']) ? $atleta['tipo_sangre'] : 'No registrado';

            $table->addRow();
            $cellEdad = $table->addCell($colLabel);
            $cellEdad->addText("EDAD:", $fontLabel, self::$pStyle);
            $cellEdad->addText($edad . " años", $fontValue, self::$pStyle);
            
            $cellFecha = $table->addCell($colVal1);
            $cellFecha->addText("FECHA DE NACIMIENTO:", $fontLabel, self::$pStyle);
            $cellFecha->addText($fechaNacFormatted, $fontValue, self::$pStyle);
            
            $cellGenero = $table->addCell($colVal2);
            $cellGenero->addText("GENERO:", $fontLabel, self::$pStyle);
            $cellGenero->addText($sexo, $fontValue, self::$pStyle);
            
            $cellSangre = $table->addCell($colVal3);
            $cellSangre->addText("TIPO DE SANGRE:", $fontLabel, self::$pStyle);
            $cellSangre->addText($tipoSangre, $fontValue, self::$pStyle);
            
            $table->addCell($colFoto, ['vMerge' => 'continue']);

            // Row 6: TALLA, PESO, ESTATURA, CALZADO
            $tallaPantalon = !empty($atleta['talla_pantalon']) ? $atleta['talla_pantalon'] : 'N/A';
            $tallaFranela = !empty($atleta['talla_franela']) ? $atleta['talla_franela'] : 'N/A';
            $tallaCalzado = !empty($atleta['talla_calzado']) ? $atleta['talla_calzado'] : 'N/A';

            $table->addRow();
            $cellTalla = $table->addCell($colLabel);
            $cellTalla->addText("TALLA:", $fontLabel, self::$pStyle);
            $cellTalla->addText("Pantalón: " . $tallaPantalon, $fontValueSmall, self::$pStyle);
            $cellTalla->addText("Franela: " . $tallaFranela, $fontValueSmall, self::$pStyle);
            
            $cellPeso = $table->addCell($colVal1);
            $cellPeso->addText("PESO:", $fontLabel, self::$pStyle);
            $cellPeso->addText($atleta['peso_kg'] . " kg", $fontValue, self::$pStyle);
            
            $cellEstatura = $table->addCell($colVal2);
            $cellEstatura->addText("ESTATURA:", $fontLabel, self::$pStyle);
            $cellEstatura->addText($atleta['estatura_cm'], $fontValue, self::$pStyle);
            
            $cellCalzado = $table->addCell($colVal3);
            $cellCalzado->addText("CALZADO:", $fontLabel, self::$pStyle);
            $cellCalzado->addText($tallaCalzado, $fontValue, self::$pStyle);
            
            $table->addCell($colFoto, ['vMerge' => 'continue']);

            // Row 7: ENTIDAD, CONCENTRADO, DISCIPLINA
            $table->addRow();
            $cellEntidad = $table->addCell($colLabel);
            $cellEntidad->addText("ENTIDAD QUE REPRESENTA:", $fontLabel, self::$pStyle);
            $cellEntidad->addText("Lara", $fontValue, self::$pStyle);
            
            $cellConcentrado = $table->addCell($colVal1);
            $cellConcentrado->addText("CONCENTRADO:", $fontLabel, self::$pStyle);
            $cellConcentrado->addText("No aplica", $fontValue, self::$pStyle);
            
            $cellDisciplina = $table->addCell($colVal2 + $colVal3, ['gridSpan' => 2]);
            $cellDisciplina->addText("DISCIPLINA:", $fontLabel, self::$pStyle);
            $cellDisciplina->addText("Hockey en línea", $fontValue, self::$pStyle);
            
            $table->addCell($colFoto, ['vMerge' => 'continue']);

            // Row 8: DIRECCION DE HABITACION (fila completa sin foto)
            $table->addRow();
            $table->addCell($colLabel)->addText("DIRECCION DE HABITACION", $fontLabel, self::$pStyle);
            $direccion = !empty($atleta['direccion']) ? $atleta['direccion'] : 'No registrada';
            $table->addCell($anchoTotal - $colLabel, ['gridSpan' => 4])->addText(
                $direccion,
                $fontValue,
                self::$pStyle
            );

            // Row 9: INSTAGRAM, CORREO ELECTRONICO (fila completa)
            $instagramAtleta = !empty($atleta['instagram_atleta']) ? $atleta['instagram_atleta'] : 'No aplica';
            $table->addRow();
            $table->addCell($colLabel)->addText("INSTAGRAM", $fontLabel, self::$pStyle);
            $table->addCell($colVal1)->addText($instagramAtleta, $fontValue, self::$pStyle);
            $table->addCell($colVal2)->addText("CORREO ELECTRONICO", $fontLabel, self::$pStyle);
            $table->addCell($colVal3 + $colFoto, ['gridSpan' => 2])->addText($correoMostrar, $fontValue, self::$pStyle);

            $section->addTextBreak(1, ['size' => 6], self::$pStyle);
            
            // ======= TABLA REPRESENTANTES =======
            $phpWord->addTableStyle('FichaRepTable', $styleTable);
            $tableRep = $section->addTable('FichaRepTable');
            
            // Encabezado gris
            $tableRep->addRow();
            $tableRep->addCell(
                $anchoTotal,
                ['gridSpan' => 5, 'valign' => 'center', 'bgColor' => 'D9D9D9']
            )->addText(
                'DATOS DE LOS PADRES Y/O REPRESENTANTE (MENORES DE EDAD)',
                ['bold' => true, 'size' => 8, 'name' => 'Times New Roman'],
                array_merge(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER], self::$pStyle)
            );
            
            // Fila de encabezados de columna
            $colRepAncho = intval($anchoTotal / 5);
            $tableRep->addRow();
            $tableRep->addCell($colRepAncho)->addText('NOMBRE', ['size' => 7, 'bold' => true, 'name' => 'Times New Roman'], self::$pStyle);
            $tableRep->addCell($colRepAncho)->addText('CEDULA', ['size' => 7, 'bold' => true, 'name' => 'Times New Roman'], self::$pStyle);
            $tableRep->addCell($colRepAncho)->addText('TELEFONO', ['size' => 7, 'bold' => true, 'name' => 'Times New Roman'], self::$pStyle);
            $tableRep->addCell($colRepAncho)->addText('CORREO', ['size' => 7, 'bold' => true, 'name' => 'Times New Roman'], self::$pStyle);
            $tableRep->addCell($colRepAncho)->addText('INSTAGRAM', ['size' => 7, 'bold' => true, 'name' => 'Times New Roman'], self::$pStyle);

            // Fila de datos del representante
            $tableRep->addRow(Converter::cmToTwip(1.2));
            if ($edad < 18 && !empty($atleta['nombre_representante'])) {
                $cedulaRep = !empty($atleta['cedula_representante']) ? $atleta['cedula_representante'] : '';
                $telefonoRep = !empty($atleta['telefono_representante']) ? $atleta['telefono_representante'] : '';
                $instagramRep = !empty($atleta['instagram_representante']) ? $atleta['instagram_representante'] : '';
                $tableRep->addCell($colRepAncho)->addText($atleta['nombre_representante'], $fontValueSmall, self::$pStyle);
                $tableRep->addCell($colRepAncho)->addText($cedulaRep, $fontValueSmall, self::$pStyle); 
                $tableRep->addCell($colRepAncho)->addText($telefonoRep, $fontValueSmall, self::$pStyle); 
                $tableRep->addCell($colRepAncho)->addText($atleta['correo_representante'] ?? '', $fontValueSmall, self::$pStyle);
                $tableRep->addCell($colRepAncho)->addText($instagramRep, $fontValueSmall, self::$pStyle);
            } else {
                $tableRep->addCell($colRepAncho)->addText('', $fontValueSmall, self::$pStyle);
                $tableRep->addCell($colRepAncho)->addText('', $fontValueSmall, self::$pStyle);
                $tableRep->addCell($colRepAncho)->addText('', $fontValueSmall, self::$pStyle);
                $tableRep->addCell($colRepAncho)->addText('', $fontValueSmall, self::$pStyle);
                $tableRep->addCell($colRepAncho)->addText('', $fontValueSmall, self::$pStyle);
            }

            // ======= FIRMAS =======
            $section->addTextBreak(3, null, self::$pStyle);
            
            $tableFirmas = $section->addTable(['width' => 100 * 50, 'unit' => 'pct']);
            $tableFirmas->addRow();
            
            $cellFirma1 = $tableFirmas->addCell(5000, ['valign' => 'top']);
            $cellFirma1->addText(
                'Firma de Asociación',
                ['bold' => true, 'size' => 10, 'name' => 'Times New Roman'],
                array_merge(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER], self::$pStyle)
            );
            
            $cellFirma2 = $tableFirmas->addCell(5000, ['valign' => 'top']);
            $cellFirma2->addText(
                'Firma del Club',
                ['bold' => true, 'size' => 10, 'name' => 'Times New Roman'],
                array_merge(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER], self::$pStyle)
            );

            // FOOTER (Pie de página)
            $footer = $section->addFooter();
            $footer->addText(
                "Av. Avenida Teherán, Caracas 1020, Venezuela, Edificio del IND",
                ['size' => 8, 'color' => '808080', 'name' => 'Times New Roman'],
                array_merge(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER], self::$pStyle)
            );
            $footer->addText(
                "fevepatin2025@gmail.com teléfonos +584145679749/ +584242980527",
                ['size' => 8, 'color' => '808080', 'name' => 'Times New Roman'],
                array_merge(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER], self::$pStyle)
            );

            $nombreArchivo = $modulo . "_FichaTecnica.docx";
            return self::guardarDocumento($phpWord, $nombreArchivo);
        } catch (\Exception $e) {
            return ['accion' => 'error', 'mensaje' => $e->getMessage()];
        }
    }

    private static function guardarDocumento(PhpWord $phpWord, string $nombreArchivo)
    {
        $subDirectorio = "docs/reportes/curriculums";
        $rutaRelativa = $subDirectorio . "/" . $nombreArchivo;
        $rutaAbsoluta = __DIR__ . "/../../public/" . $rutaRelativa;

        if (!is_dir(dirname($rutaAbsoluta))) {
            mkdir(dirname($rutaAbsoluta), 0755, true);
        }

        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($rutaAbsoluta);

        return ['accion' => 'reporte', 'archivo' => $rutaRelativa];
    }

    private static function calcularEdad(string $fechaNac): int
    {
        $nacimiento = new \DateTime($fechaNac);
        $hoy = new \DateTime();
        return $hoy->diff($nacimiento)->y;
    }
}