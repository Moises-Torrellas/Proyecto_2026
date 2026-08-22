<?php

namespace App\servicios;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Converter;

class GenerarDocumentosWord
{
    // Estilo global para evitar saltos de línea indeseados (elimina el espacio debajo de los párrafos)
    private static $pStyle = ['spaceAfter' => 0, 'spaceBefore' => 0];

    public static function GenerarFichaDeportiva(array $datos, string $modulo, string $anio_inicio)
    {
        $modulo = preg_replace('/[^a-zA-Z0-9]/', '', $modulo);
        try {
            ini_set('memory_limit', '512M');
            set_time_limit(300);

            $phpWord = new PhpWord();
            
            // Márgenes reducidos para aprovechar la hoja al máximo
            $section = $phpWord->addSection([
                'marginLeft' => Converter::cmToTwip(1.2),
                'marginRight' => Converter::cmToTwip(1.2),
                'marginTop' => Converter::cmToTwip(1.0),
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

            // CABECERA (Header) - Membrete 3 para Alto Rendimiento conservando proporción
            $header = $section->addHeader();
            $rutaMembrete3 = __DIR__ . '/../../public/img/Menbrete 3.png';
            if (file_exists($rutaMembrete3)) {
                $header->addImage($rutaMembrete3, [
                    'width' => Converter::cmToPoint(18.5), // Solo asignamos el ancho, el alto se ajusta solo
                    'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER
                ]);
            }

            // Título: Nombre del Atleta y Disciplina
            $table = $section->addTable(['width' => 100 * 50, 'unit' => 'pct']);
            $table->addRow();
            
            $cellText = $table->addCell(8000, ['valign' => 'center']);
            $nombresApellidos = strtoupper($atleta['nombres'] . " " . $atleta['apellidos']);
            $nombresEspaciados = implode(' ', str_split(str_replace(' ', '  ', $nombresApellidos)));
            
            $cellText->addText($nombresEspaciados, ['bold' => true, 'size' => 12, 'name' => 'Arial'], array_merge(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER], self::$pStyle));
            $cellText->addText("H O C K E Y   E N   L Í N E A", ['bold' => true, 'size' => 10, 'name' => 'Arial'], array_merge(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER], self::$pStyle));
            
            $cellFoto = $table->addCell(2000, ['valign' => 'center']);
            $rutaFoto = __DIR__ . '/../../public/img/atletas/' . $atleta['foto'];
            if (file_exists($rutaFoto) && $atleta['foto'] !== 'default.png') {
                $cellFoto->addImage($rutaFoto, [
                    'width' => 85, 
                    'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT
                ]);
            } else {
                $cellFoto->addText("Foto", [], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            }

            $section->addTextBreak(1, null, self::$pStyle);

            // Personal Info (Reducido a tamaño 9 para imitar el original y ahorrar espacio)
            $fontStyle = ['size' => 9, 'name' => 'Arial'];
            $sexo = (isset($atleta['genero']) && $atleta['genero'] == 'F') ? 'Femenino' : 'Masculino';
            $fechaNacObj = new \DateTime($atleta['fecha_nac']);
            $fechaNacFormatted = $fechaNacObj->format('d/m/Y');
            $direccion = !empty($atleta['direccion']) ? $atleta['direccion'] : "Calle 62 entre carreras 14A y 14B Edificio Pozo Blanco, apartamento 8A";
            $telefonoStr = !empty($atleta['telefono']) ? $atleta['telefono'] : "0416 553 3382";

            $section->addText("Cedula de Identidad: " . $atleta['doc_identidad'] . "                Edad: " . $edad . " años                Sexo: " . $sexo . ",  Fecha y lugar de nacimiento: " . $fechaNacFormatted . " Barquisimeto-Estado Lara", $fontStyle, self::$pStyle);
            $section->addText("Dirección: " . $direccion . "                 Municipio: Iribarren", $fontStyle, self::$pStyle);
            $section->addText("Correo: " . $correoMostrar . "               Teléfono: " . $telefonoStr, $fontStyle, self::$pStyle);
            $section->addText("Tipo de sangre: A+,  Es Alérgico: Si,    Especificar: Alergia a mariscos, polvo y olores fuertes.", $fontStyle, self::$pStyle);

            // Linea separadora gruesa gris
            $section->addLine(['weight' => 2, 'width' => 500, 'height' => 0, 'color' => 'A0A0A0']);

            $section->addText("D A T O S   D E L   E N T R E N A D O R", ['bold' => true, 'size' => 10, 'name' => 'Arial'], self::$pStyle);
            $section->addText("Nombre y apellidos: Gonzalo Villaverde               C.I: 17.011.775", $fontStyle, self::$pStyle);
            $section->addText("Teléfono: 0412 519 5639   Correo: dorfgrun@gmail.com", $fontStyle, self::$pStyle);
            
            $section->addLine(['weight' => 2, 'width' => 500, 'height' => 0, 'color' => 'A0A0A0']);

            $tituloEventos = "E V E N T O S   Y   R E S U L T A D O S";
            if (!empty($anio_inicio)) {
                $tituloEventos .= "  " . implode(' ', str_split($anio_inicio));
            }
            $section->addText($tituloEventos, ['bold' => true, 'size' => 10, 'name' => 'Arial'], self::$pStyle);

            // Historial list super compacto
            foreach ($torneos as $torneo) {
                $anioTorneo = (new \DateTime($torneo['fecha_fin']))->format('Y');
                $textoLogroEquipo = $torneo['premios_grupales'] ? implode(', ', $torneo['premios_grupales']) : 'N/A';
                $textoLogroInd = $torneo['premios_individuales'] ? implode(', ', $torneo['premios_individuales']) : 'N/A';
                
                $textRun = $section->addTextRun(self::$pStyle);
                $textRun->addText("{$anioTorneo}, ", ['bold' => true, 'size' => 9, 'name' => 'Arial']);
                $textRun->addText("{$torneo['nombre_torneo']}. Posición: {$atleta['nombre_posicion']}. Categoría: {$atleta['nombre_categoria']}. ", $fontStyle);
                $textRun->addText("Logro de equipo: ", ['bold' => true, 'size' => 9, 'name' => 'Arial']);
                $textRun->addText("{$textoLogroEquipo}. ", $fontStyle);
                $textRun->addText("Logro individual: ", ['bold' => true, 'size' => 9, 'name' => 'Arial']);
                $textRun->addText("{$textoLogroInd}.", $fontStyle);
            }
            
            $section->addLine(['weight' => 2, 'width' => 500, 'height' => 0, 'color' => 'A0A0A0']);

            $section->addText("D A T O    B A N C A R I O", ['bold' => true, 'size' => 10, 'name' => 'Arial'], self::$pStyle);
            $section->addText("Número de cuenta del Banco de Venezuela", $fontStyle, self::$pStyle);
            $section->addText("0102 0308 2100 0102 1596", ['bold' => true, 'size' => 12, 'name' => 'Arial'], self::$pStyle);

            $section->addLine(['weight' => 2, 'width' => 500, 'height' => 0, 'color' => 'A0A0A0']);

            $section->addText("A N E X A R   D O C U M E N T A C I O N", ['bold' => true, 'size' => 10, 'name' => 'Arial'], self::$pStyle);
            
            $section->addListItem("Copia de la cedula de identidad.", 0, $fontStyle, \PhpOffice\PhpWord\Style\ListItem::TYPE_BULLET_FILLED, self::$pStyle);
            $section->addListItem("Certificación Bancaria. Ficha Deportiva.", 0, $fontStyle, \PhpOffice\PhpWord\Style\ListItem::TYPE_BULLET_FILLED, self::$pStyle);
            $section->addListItem("Oficio de Postulación por la asociación.", 0, $fontStyle, \PhpOffice\PhpWord\Style\ListItem::TYPE_BULLET_FILLED, self::$pStyle);

            $section->addTextBreak(1, null, self::$pStyle);
            $section->addText("Firma y sello de la Asociación", ['bold' => true, 'size' => 11, 'name' => 'Arial'], array_merge(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT], self::$pStyle));

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
            $section = $phpWord->addSection([
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

            // CABECERA (Header) - Membrete 1, Título y Membrete 2
            $header = $section->addHeader();
            $headerTable = $header->addTable(['width' => 100 * 50, 'unit' => 'pct']);
            $headerTable->addRow();
            
            $col1 = $headerTable->addCell(3000, ['valign' => 'center']);
            $rutaMembrete1 = __DIR__ . '/../../public/img/Menbrete 1.png';
            if (file_exists($rutaMembrete1)) {
                // Quitamos el height para evitar estiramiento
                $col1->addImage($rutaMembrete1, ['width' => 60, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            }
            
            $col2 = $headerTable->addCell(4000, ['valign' => 'center']);
            $col2->addText("FEDERACIÓN VENEZOLANA DE PATINAJE", ['size' => 10, 'bold' => true, 'color' => '808080', 'name' => 'Times New Roman'], array_merge(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER], self::$pStyle));
            $col2->addText("RIF. J-30492888-2", ['size' => 8, 'color' => '808080', 'name' => 'Times New Roman'], array_merge(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER], self::$pStyle));

            $col3 = $headerTable->addCell(3000, ['valign' => 'center']);
            $rutaMembrete2 = __DIR__ . '/../../public/img/Menbrete 2.png';
            if (file_exists($rutaMembrete2)) {
                $col3->addImage($rutaMembrete2, ['width' => 70, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            }

            $section->addLine(['weight' => 1, 'width' => 500, 'height' => 0, 'color' => '000000']);

            // Tablas ultra compactas (márgenes internos reducidos)
            $styleTable = ['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 20];
            $phpWord->addTableStyle('FichaTable', $styleTable);
            
            $table = $section->addTable('FichaTable');
            
            // Row 1: FICHA TECNICA
            $table->addRow();
            $table->addCell(10000, ['gridSpan' => 5, 'valign' => 'center'])->addText('FICHA TECNICA', ['bold' => true, 'size' => 12, 'name' => 'Times New Roman'], array_merge(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER], self::$pStyle));

            // Row 2: DATOS PERSONALES
            $table->addRow();
            $table->addCell(10000, ['gridSpan' => 5])->addText('DATOS PERSONALES', ['size' => 7, 'name' => 'Times New Roman'], self::$pStyle);

            // Row 3: NOMBRE
            $table->addRow();
            $table->addCell(2000)->addText('NOMBRE', ['size' => 7, 'name' => 'Times New Roman'], self::$pStyle);
            $table->addCell(6000, ['gridSpan' => 3, 'valign' => 'center'])->addText(ucwords(strtolower($atleta['nombres'] . " " . $atleta['apellidos'])), ['size' => 9, 'name' => 'Times New Roman'], array_merge(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER], self::$pStyle));
            
            $cellFoto = $table->addCell(2000, ['vMerge' => 'restart', 'valign' => 'center']);
            $rutaFoto = __DIR__ . '/../../public/img/atletas/' . $atleta['foto'];
            if (file_exists($rutaFoto) && $atleta['foto'] !== 'default.png') {
                $cellFoto->addImage($rutaFoto, ['width' => 70, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            }

            // Row 4: CEDULA DE IDENTIDAD
            $table->addRow();
            $table->addCell(2000)->addText('CEDULA DE IDENTIDAD', ['size' => 7, 'name' => 'Times New Roman'], self::$pStyle);
            $table->addCell(2000)->addText($atleta['doc_identidad'], ['size' => 9, 'name' => 'Times New Roman'], self::$pStyle);
            
            $cellPasaporte = $table->addCell(2000);
            $cellPasaporte->addText("PASAPORTE:", ['size' => 7, 'name' => 'Times New Roman'], self::$pStyle);
            
            $table->addCell(2000)->addText('', ['size' => 9, 'name' => 'Times New Roman'], self::$pStyle);
            $table->addCell(2000, ['vMerge' => 'continue']);

            // Row 5: EDAD, FECHA, GENERO, SANGRE
            $fechaNacObj = new \DateTime($atleta['fecha_nac']);
            $fechaNacFormatted = $fechaNacObj->format('d/m/Y');
            $sexo = (isset($atleta['genero']) && $atleta['genero'] == 'F') ? 'Femenino' : 'Masculino';

            $table->addRow();
            $cellEdad = $table->addCell(2000);
            $cellEdad->addText("EDAD:", ['size' => 7, 'name' => 'Times New Roman'], self::$pStyle);
            $cellEdad->addText($edad . " años", ['size' => 9, 'name' => 'Times New Roman'], self::$pStyle);
            
            $cellFecha = $table->addCell(2000);
            $cellFecha->addText("FECHA DE NACIMIENTO:", ['size' => 7, 'name' => 'Times New Roman'], self::$pStyle);
            $cellFecha->addText($fechaNacFormatted, ['size' => 9, 'name' => 'Times New Roman'], self::$pStyle);
            
            $cellGenero = $table->addCell(2000);
            $cellGenero->addText("GENERO:", ['size' => 7, 'name' => 'Times New Roman'], self::$pStyle);
            $cellGenero->addText($sexo, ['size' => 9, 'name' => 'Times New Roman'], self::$pStyle);
            
            $cellSangre = $table->addCell(2000);
            $cellSangre->addText("TIPO DE SANGRE:", ['size' => 7, 'name' => 'Times New Roman'], self::$pStyle);
            $cellSangre->addText("A+", ['size' => 9, 'name' => 'Times New Roman'], self::$pStyle);
            
            $table->addCell(2000, ['vMerge' => 'continue']);

            // Row 6: TALLA, PESO, ESTATURA, CALZADO
            $table->addRow();
            $cellTalla = $table->addCell(2000);
            $cellTalla->addText("TALLA:", ['size' => 7, 'name' => 'Times New Roman'], self::$pStyle);
            $cellTalla->addText("Pantalón: L", ['size' => 9, 'name' => 'Times New Roman'], self::$pStyle);
            $cellTalla->addText("Franela: L", ['size' => 9, 'name' => 'Times New Roman'], self::$pStyle);
            
            $cellPeso = $table->addCell(2000);
            $cellPeso->addText("PESO:", ['size' => 7, 'name' => 'Times New Roman'], self::$pStyle);
            $cellPeso->addText($atleta['peso_kg'] . " kg", ['size' => 9, 'name' => 'Times New Roman'], self::$pStyle);
            
            $cellEstatura = $table->addCell(2000);
            $cellEstatura->addText("ESTATURA:", ['size' => 7, 'name' => 'Times New Roman'], self::$pStyle);
            $cellEstatura->addText($atleta['estatura_cm'], ['size' => 9, 'name' => 'Times New Roman'], self::$pStyle);
            
            $cellCalzado = $table->addCell(2000);
            $cellCalzado->addText("CALZADO:", ['size' => 7, 'name' => 'Times New Roman'], self::$pStyle);
            $cellCalzado->addText("42", ['size' => 9, 'name' => 'Times New Roman'], self::$pStyle);
            
            $table->addCell(2000, ['vMerge' => 'continue']);

            // Row 7: ENTIDAD, CONCENTRADO, DISCIPLINA
            $table->addRow();
            $cellEntidad = $table->addCell(2000);
            $cellEntidad->addText("ENTIDAD QUE REPRESENTA:", ['size' => 7, 'name' => 'Times New Roman'], self::$pStyle);
            $cellEntidad->addText("Lara", ['size' => 9, 'name' => 'Times New Roman'], self::$pStyle);
            
            $cellConcentrado = $table->addCell(2000);
            $cellConcentrado->addText("CONCENTRADO:", ['size' => 7, 'name' => 'Times New Roman'], self::$pStyle);
            $cellConcentrado->addText("No aplica", ['size' => 9, 'name' => 'Times New Roman'], self::$pStyle);
            
            $cellDisciplina = $table->addCell(4000, ['gridSpan' => 2]);
            $cellDisciplina->addText("DISCIPLINA:", ['size' => 7, 'name' => 'Times New Roman'], self::$pStyle);
            $cellDisciplina->addText("Hockey en línea", ['size' => 9, 'name' => 'Times New Roman'], self::$pStyle);
            
            $table->addCell(2000, ['vMerge' => 'continue']);

            // Row 8: DIRECCION
            $table->addRow();
            $table->addCell(2000)->addText("DIRECCION DE HABITACION", ['size' => 7, 'name' => 'Times New Roman'], self::$pStyle);
            $direccion = !empty($atleta['direccion']) ? $atleta['direccion'] : "Calle 62 entre carreras 14A y 14B edificio Pozo Blanco, apartamento 8A. Barquisimeto estado Lara";
            $table->addCell(8000, ['gridSpan' => 4])->addText($direccion, ['size' => 9, 'name' => 'Times New Roman'], self::$pStyle);

            // Row 9: INSTAGRAM, CORREO
            $table->addRow();
            $table->addCell(2000)->addText("INSTAGRAM", ['size' => 7, 'name' => 'Times New Roman'], self::$pStyle);
            $table->addCell(2000)->addText("No aplica", ['size' => 9, 'name' => 'Times New Roman'], self::$pStyle);
            $table->addCell(2000)->addText("CORREO ELECTRONICO", ['size' => 7, 'name' => 'Times New Roman'], self::$pStyle);
            $table->addCell(4000, ['gridSpan' => 2])->addText($correoMostrar, ['size' => 9, 'name' => 'Times New Roman'], self::$pStyle);

            $section->addTextBreak(1, null, self::$pStyle);
            
            // Tabla Representantes 
            $phpWord->addTableStyle('FichaRepTable', $styleTable);
            $tableRep = $section->addTable('FichaRepTable');
            
            $tableRep->addRow();
            $tableRep->addCell(10000, ['gridSpan' => 5, 'valign' => 'center', 'bgColor' => 'D9D9D9'])->addText('DATOS DE LOS PADRES Y/O REPRESENTANTE (MENORES DE EDAD)', ['bold' => true, 'size' => 8, 'name' => 'Times New Roman'], array_merge(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER], self::$pStyle));
            
            $tableRep->addRow();
            $tableRep->addCell(2000)->addText('NOMBRE', ['size' => 7, 'name' => 'Times New Roman'], self::$pStyle);
            $tableRep->addCell(2000)->addText('CEDULA', ['size' => 7, 'name' => 'Times New Roman'], self::$pStyle);
            $tableRep->addCell(2000)->addText('TELEFONO', ['size' => 7, 'name' => 'Times New Roman'], self::$pStyle);
            $tableRep->addCell(2000)->addText('CORREO', ['size' => 7, 'name' => 'Times New Roman'], self::$pStyle);
            $tableRep->addCell(2000)->addText('INSTAGRAM', ['size' => 7, 'name' => 'Times New Roman'], self::$pStyle);

            $tableRep->addRow();
            if ($edad < 18 && !empty($atleta['nombre_representante'])) {
                $tableRep->addCell(2000)->addText($atleta['nombre_representante'], ['size' => 8, 'name' => 'Times New Roman'], self::$pStyle);
                $tableRep->addCell(2000)->addText("12.944.555", ['size' => 8, 'name' => 'Times New Roman'], self::$pStyle); 
                $tableRep->addCell(2000)->addText("0416 553 3382", ['size' => 8, 'name' => 'Times New Roman'], self::$pStyle); 
                $tableRep->addCell(2000)->addText($atleta['correo_representante'] ?? '', ['size' => 8, 'name' => 'Times New Roman'], self::$pStyle);
                $tableRep->addCell(2000)->addText('@representante', ['size' => 8, 'name' => 'Times New Roman'], self::$pStyle);
            } else {
                $tableRep->addCell(2000)->addText('', ['size' => 8, 'name' => 'Times New Roman'], self::$pStyle);
                $tableRep->addCell(2000)->addText('', ['size' => 8, 'name' => 'Times New Roman'], self::$pStyle);
                $tableRep->addCell(2000)->addText('', ['size' => 8, 'name' => 'Times New Roman'], self::$pStyle);
                $tableRep->addCell(2000)->addText('', ['size' => 8, 'name' => 'Times New Roman'], self::$pStyle);
                $tableRep->addCell(2000)->addText('', ['size' => 8, 'name' => 'Times New Roman'], self::$pStyle);
            }

            $section->addTextBreak(1, null, self::$pStyle);
            $tableFirmas = $section->addTable(['width' => 100 * 50, 'unit' => 'pct']);
            $tableFirmas->addRow();
            $tableFirmas->addCell(5000)->addText('Firma de Asociación', ['bold' => true, 'name' => 'Times New Roman'], array_merge(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER], self::$pStyle));
            $tableFirmas->addCell(5000)->addText('Firma del Club', ['bold' => true, 'name' => 'Times New Roman'], array_merge(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER], self::$pStyle));

            // FOOTER (Pie de página real)
            $footer = $section->addFooter();
            $footer->addText("Av. Avenida Teherán, Caracas 1020, Venezuela, Edificio del IND", ['size' => 8, 'color' => '808080', 'name' => 'Times New Roman'], array_merge(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER], self::$pStyle));
            $footer->addText("fevepatin2025@gmail.com teléfonos +584145679749/ +584242980527", ['size' => 8, 'color' => '808080', 'name' => 'Times New Roman'], array_merge(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER], self::$pStyle));

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