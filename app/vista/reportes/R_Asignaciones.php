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

        /* 2. Header: Ocupará el 100% real de la hoja sin dejar bordes blancos */
        .header {
            position: fixed;
            top: -130px;
            left: 0px;
            right: 0px;
            height: 60px;
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

        .logo-mascota {
            position: absolute;
            right: 40px;
            top: 18px;
            width: 60px;
        }

        /* 3. Contenedor de datos */
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
            border-collapse: collapse; 
            table-layout: fixed; 
            word-wrap: break-word;
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

        /* 4. Footer */
        .footer {
            position: fixed;
            bottom: -50px;
            left: 40px;
            right: 40px;
            height: 50px;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            font-size: 12px;
            background-color: #ffffff;
        }

        .footer-logo-container {
            text-align: center;
            margin-bottom: 12px;
        }

        .footer img {
            width: 100px;
            display: inline-block;
        }

        .footer-meta {
            width: 100%;
            border-collapse: collapse; 
            table-layout: fixed; 
            word-wrap: break-word;
        }

        .footer-meta td {
            padding: 0;
            font-size: 12px;
            color: #4a5568;
        }

        .text-left { text-align: left; }
        .text-right { text-align: right; }

        .page-number:before {
            content: "Página " counter(page);
        }

        /* Clases para Asignaciones Devueltas/Inactivas */
        .fila-anulada {
            filter: grayscale(1);
            opacity: 0.6;
            background-color: #f4f4f4;
        }

        .fila-anulada td {
            text-decoration: line-through;
        }

        .fila-anulada .no-strike {
            text-decoration: none !important;
        }
    </style>
</head>

<body>

    <div class="header">
        <h1>REPORTE DE ASIGNACIONES</h1>
        <p>Sistema de Gestión Administrativo - Cannibals Lara</p>
        <img src="<?= $logo ?>" class="logo-mascota" alt="Logo">
    </div>

    <div class="content">
        <div class="info-grid">
            <div class="info-item"><strong>FECHA DE EMISIÓN</strong><br><?= $fecha_reporte ?></div>
            <div class="info-item"><strong>GENERADO POR</strong><br><?= htmlspecialchars($usuario) ?></div>
        </div>
        
        <div class="resumen-ejecutivo">
            <strong>Resumen Ejecutivo:</strong> El presente documento detalla el registro de equipamiento deportivo asignado a los atletas del club. Incluye el estatus actual de cada pieza (en uso o devuelto) organizado por el perfil de jugador.
        </div>

        <div class="section-title">Historial de Equipamiento</div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 8%;">N°</th>
                    <th style="width: 45%;">Atleta</th>
                    <th style="width: 27%;">C.I. / Documento</th>
                    <th style="width: 20%; text-align: center;">Equipos Activos</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $id = 0;
                foreach ($datos as $atleta) :
                    $id++;
                    
                    // 1. Conteo de equipos activos (Simplificado a estatus 1)
                    $equiposActivos = 0;
                    foreach ($atleta['asignaciones'] as $asig) {
                        if ($asig['estatus'] == 1) {
                            $equiposActivos++;
                        }
                    }
                ?>
                    <tr>
                        <td class="data-cell"><?= $id ?></td>
                        <td class="data-cell"><strong><?= htmlspecialchars($atleta['nombre_completo']) ?></strong></td>
                        <td class="data-cell">CI: <?= htmlspecialchars($atleta['doc_identidad']) ?></td>
                        <td class="data-cell" style="text-align: center; font-weight: bold; color: #32B10B;">
                            <?= $equiposActivos ?>
                        </td>
                    </tr>

                    <?php if (!empty($atleta['asignaciones'])) : ?>
                        <tr>
                            <td colspan="4" class="no-strike" style="background-color: #f8fafc; padding: 5px 15px 10px 30px; border-bottom: 1px solid #e2e8f0;">
                                <div style="font-size: 11px; margin-bottom: 4px; color: #4a5568; font-weight: bold;">Detalle de Piezas Registradas:</div>
                                <table style="width: 100%; border-collapse: collapse; table-layout: fixed; word-wrap: break-word; font-size: 11px;">
                                    <thead>
                                        <tr style="border-bottom: 1px dashed #cbd5e0;">
                                            <th style="padding: 3px 0; background: transparent; color: #718096; font-size: 10px;">Artículo / Equipo</th>
                                            <th style="padding: 3px 0; background: transparent; color: #718096; font-size: 10px;">Fecha de Préstamo</th>
                                            <th style="padding: 3px 0; background: transparent; color: #718096; font-size: 10px; text-align: right;">Estatus</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($atleta['asignaciones'] as $det) : 
                                            // 2. Lógica binaria de estado (1 = En Uso, 0 = Inactivo)
                                            $esInactivo = ($det['estatus'] == 0);
                                            $claseFila = $esInactivo ? 'fila-anulada' : '';
                                            
                                            // Añadimos el código del club si existe para mayor claridad
                                            $codigoClub = isset($det['codigo_club']) && !empty($det['codigo_club']) ? ' - Cód: ' . $det['codigo_club'] : '';
                                        ?>
                                            <tr class="<?= $claseFila ?>">
                                                <td style="padding: 4px 0; color: #4a5568; font-weight: bold;">
                                                    <?= htmlspecialchars(mb_strtoupper($det['articulo'], 'UTF-8')) . htmlspecialchars($codigoClub) ?>
                                                </td>
                                                <td style="padding: 4px 0; color: #718096;">
                                                    <?= htmlspecialchars($det['fecha_vista']) ?>
                                                </td>
                                                <td class="no-strike" style="padding: 4px 0; text-align: right;">
                                                    <?= $esInactivo 
                                                        ? '<span style="color: #a0aec0; font-weight: bold;">Devuelta / Inactiva</span>' 
                                                        : '<span style="color: #38a169; font-weight: bold;">En Uso</span>' 
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    <?php endif; ?>

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
                <td class="text-left">Cannibals Lara - Reporte de Asignaciones</td>
                <td class="text-right"><span class="page-number"></span></td>
            </tr>
        </table>
    </div>

</body>
</html>