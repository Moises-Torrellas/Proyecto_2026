<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <style>
        /* 1. Forzar a que la hoja no tenga ningún margen exterior */
        @page {
            margin: 130px 0px 80px 0px;
            size: A4;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #2d3748;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }

        /* 2. Header: Ahora sí ocupará el 100% real de la hoja sin dejar bordes blancos */
        .header {
            position: fixed;
            top: -130px;
            /* Sube al espacio reservado */
            left: 0px;
            right: 0px;
            height: 60px;
            /* Fijamos altura interna para que cuadre exacto en los 140px */
            background-color: #1a202c;
            color: white;
            padding: 20px 40px;
            border-bottom: 5px solid #32B10B;
        }

        .header h1 {
            margin: 0;
            font-size: 20px;
            text-transform: uppercase;
            width: 80%;
        }

        .header p {
            margin: 5px 0 0;
            font-size: 14px;
            color: #a0aec0;
        }

        /* Ajuste fino del logo superior derecho */
        .logo-mascota {
            position: absolute;
            right: 40px;
            top: 18px;
            width: 60px;
        }

        /* 3. Contenedor de datos: Maneja los espacios de la tabla hacia los bordes de la hoja */
        .content {
            padding: 10px 40px;
        }

        .resumen-ejecutivo {
            background-color: #f7fafc;
            border: 1px solid #e2e8f0;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 13px;
        }

        .section-title {
            font-size: 16px;
            font-weight: bold;
            border-left: 5px solid #32B10B;
            padding-left: 10px;
            margin-bottom: 15px;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse; table-layout: fixed; word-wrap: break-word;
            margin-top: 10px;
        }

        th {
            background-color: #edf2f7;
            text-align: left;
            padding: 10px;
            font-size: 12px;
            border-bottom: 2px solid #e2e8f0;
        }

        td.data-cell {
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 12px;
        }

        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .info-item {
            display: table-cell;
            font-size: 13px;
        }

        .grafico-placeholder {
            border: 1px dashed #cbd5e0;
            padding: 5px;
            text-align: center;
            margin-bottom: 30px;
        }

        .chart {
            width: 100%;
        }

        /* 4. Footer: Posicionado abajo del todo de forma fija, alineado con los lados del contenido */
        .footer {
            position: fixed;
            bottom: -50px;
            /* Pequeño despegue estético del borde inferior del papel */
            left: 40px;
            right: 40px;
            height: 50px;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            font-size: 12px;
            background-color: #ffffff;
        }

        /* Contenedor del logo centrado en el footer */
        .footer-logo-container {
            text-align: center;
            margin-bottom: 12px;
        }

        .footer img {
            width: 100px;
            display: inline-block;
        }

        /* Estructura para separar los textos informativos a los extremos */
        .footer-meta {
            width: 100%;
            border-collapse: collapse; table-layout: fixed; word-wrap: break-word;
        }

        .footer-meta td {
            padding: 0;
            font-size: 12px;
            color: #4a5568;
        }

        .text-left {
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .page-number:before {
            content: "Página " counter(page);
        }
    </style>
</head>

<body>

    <div class="header">
        <h1>REPORTE DE Atletas</h1>
        <p>Sistema de Gestión Administrativo - Cannibals Lara</p>
        <img src="<?= $logo ?>" class="logo-mascota" alt="Logo">
    </div>

    <div class="content">
        <div class="info-grid">
            <div class="info-item"><strong>FECHA DE EMISIÓN</strong><br><?= $fecha_reporte ?></div>
            <div class="info-item"><strong>GENERADO POR</strong><br><?= $usuario ?></div>
        </div>
        <div class="resumen-ejecutivo">
            <strong>Resumen Ejecutivo:</strong> El presente documento contiene el registro detallado de los atletas del club. Esta información es fundamental para la gestión administrativa de los atletas.
        </div>

        <div class="section-title">Desglose por Tabla</div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombres Y Apellidos</th>
                    <th>Documento de identidad</th>
                    <th>edad</th>
                    <th>Genero</th>
                    <th>Pasicion</th>
                    <th>Categoria</th>
                    <th>Representante</th>
                    <th>Estatus</th>
                </tr>
            </thead>
            <tbody>
                <?php $id = 0;
                $anioActual = date('Y');
                foreach ($datos as $r) :
                    $id++;
                    $anioNacimiento = date('Y', strtotime($r['fecha_nac']));
                    $edadCalendario = $anioActual - $anioNacimiento;
                    $genero = ($r['genero'] === 'H') ? 'Hombre' : 'Mujer';
                    $estatus = ((int)$r['estatus'] === 1) ? 'Activo' : 'Retirado';
                    $representante = ($r['nombre_rep'] === null ) ? 'N/A' : $r['nombre_rep'] . ' ' . $r['apellido_rep'];
                    ?>
                    <tr style="background-color: #f7fafc;">
                        <td class="data-cell" style="font-weight: bold; border-bottom: none;"><?= $id ?></td>
                        <td class="data-cell" style="font-weight: bold; border-bottom: none;"><?= htmlspecialchars($r['nombres'] . ' ' . $r['apellidos']) ?></td>
                        <td class="data-cell" style="border-bottom: none;"><?= htmlspecialchars(!empty($r['doc_identidad']) ? $r['doc_identidad'] : 'No Aplica') ?></td>
                        <td class="data-cell" style="border-bottom: none;"><?= $edadCalendario ?></td>
                        <td class="data-cell" style="border-bottom: none;"><?= $genero ?></td>
                        <td class="data-cell" style="border-bottom: none;"><?= htmlspecialchars($r['nombre_posicion']) ?></td>
                        <td class="data-cell" style="border-bottom: none;"><?= htmlspecialchars($r['nombre_categoria']) ?></td>
                        <td class="data-cell" style="border-bottom: none;"><?= htmlspecialchars($representante) ?></td>
                        <td class="data-cell" style="border-bottom: none; text-align: center;">
                            <?= ((int)$r['estatus'] === 1) ? '<span style="color: #38a169; font-weight: bold;">Activo</span>' : '<span style="color: #e53e3e; font-weight: bold;">Retirado</span>' ?>
                        </td>
                    </tr>
                    <!-- Fila de Detalles -->
                    <tr>
                        <td colspan="9" style="background-color: #ffffff; padding: 5px 15px 15px 30px; border-bottom: 1px solid #e2e8f0;">
                            <table style="width: 100%; border-collapse: collapse; table-layout: fixed; word-wrap: break-word; font-size: 11px;">
                                <tr>
                                    <td style="vertical-align: top; width: 33%; padding-right: 10px;">
                                        <div style="color: #4a5568; font-weight: bold; margin-bottom: 5px; border-bottom: 1px dashed #cbd5e0; padding-bottom: 3px;">Datos Físicos e Ingreso</div>
                                        <div style="margin-bottom: 2px;"><strong>Dorsal:</strong> <?= htmlspecialchars(!empty($r['dorsal']) ? $r['dorsal'] : 'No Aplica') ?></div>
                                        <div style="margin-bottom: 2px;"><strong>Peso/Estatura:</strong> <?= htmlspecialchars($r['peso_kg'] ?? '0') ?> kg / <?= htmlspecialchars($r['estatura_cm'] ?? '0') ?> cm</div>
                                        <div style="margin-bottom: 2px;"><strong>Tallas:</strong> Pant: <?= htmlspecialchars($r['talla_pantalon'] ?? 'N/A') ?> | Fra: <?= htmlspecialchars($r['talla_franela'] ?? 'N/A') ?> | Calz: <?= htmlspecialchars($r['talla_calzado'] ?? 'N/A') ?></div>
                                        <div style="margin-bottom: 2px;"><strong>Fecha Ingreso:</strong> <?= !empty($r['fecha_ingreso']) ? date('d/m/Y', strtotime($r['fecha_ingreso'])) : 'No Aplica' ?></div>
                                    </td>
                                    <td style="vertical-align: top; width: 33%; padding-right: 10px;">
                                        <div style="color: #4a5568; font-weight: bold; margin-bottom: 5px; border-bottom: 1px dashed #cbd5e0; padding-bottom: 3px;">Contacto y Residencia</div>
                                        <div style="margin-bottom: 2px;"><strong>Correo:</strong> <?= htmlspecialchars(!empty($r['correo']) ? $r['correo'] : 'No Aplica') ?></div>
                                        <div style="margin-bottom: 2px;"><strong>Instagram:</strong> <?= htmlspecialchars(!empty($r['instagram']) ? $r['instagram'] : 'No Aplica') ?></div>
                                        <div style="margin-bottom: 2px;"><strong>Lugar Nac.:</strong> <?= htmlspecialchars(!empty($r['lugar_nacimiento']) ? $r['lugar_nacimiento'] : 'No Aplica') ?></div>
                                        <div style="margin-bottom: 2px;"><strong>Municipio:</strong> <?= htmlspecialchars(!empty($r['municipio']) ? $r['municipio'] : 'No Aplica') ?></div>
                                    </td>
                                    <td style="vertical-align: top; width: 33%;">
                                        <div style="color: #4a5568; font-weight: bold; margin-bottom: 5px; border-bottom: 1px dashed #cbd5e0; padding-bottom: 3px;">Datos Médicos y Estado</div>
                                        <div style="margin-bottom: 2px;"><strong>Tipo Sangre:</strong> <?= htmlspecialchars(!empty($r['tipo_sangre']) ? $r['tipo_sangre'] : 'No Aplica') ?></div>
                                        <div style="margin-bottom: 2px; <?= (isset($r['es_alergico']) && $r['es_alergico'] == 1) ? 'color: #e53e3e;' : '' ?>">
                                            <strong>Alergias:</strong> <?= (isset($r['es_alergico']) && $r['es_alergico'] == 1) ? htmlspecialchars(!empty($r['alergias_detalle']) ? $r['alergias_detalle'] : 'Sí') : 'Ninguna' ?>
                                        </div>
                                        <?php if ((int)$r['estatus'] === 2 && !empty($r['fecha_retiro'])): ?>
                                        <div style="margin-top: 5px; padding: 4px; background-color: #fff5f5; border-left: 2px solid #fc8181; color: #c53030;">
                                            <strong>Retirado el:</strong> <?= date('d/m/Y', strtotime($r['fecha_retiro'])) ?><br>
                                            <strong>Motivo:</strong> <?= htmlspecialchars($r['motivo_retiro'] ?? 'No especificado') ?>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($r['fecha_reingreso']) && (int)$r['estatus'] === 1): ?>
                                        <div style="margin-top: 5px; padding: 4px; background-color: #f0fff4; border-left: 2px solid #68d391; color: #2f855a;">
                                            <strong>Reingresado el:</strong> <?= date('d/m/Y', strtotime($r['fecha_reingreso'])) ?>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="footer">
        <div class="footer-logo-container">
            <img src="<?= $logo_footer ?>" alt="Cannibals">
        </div>

        <table class="footer-meta">
            <tr>
                <td class="text-left">Cannibals Lara - Reporte Automatizado</td>
                <td class="text-right"><span class="page-number"></span></td>
            </tr>
        </table>
    </div>

</body>

</html>
