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
            /* Sube al espacio reservado */
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

        /* 4. Footer: Posicionado abajo del todo de forma fija */
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

        /* Clases auxiliares para estados financieros */
        .pago-anulado {
            filter: grayscale(1);
            opacity: 0.6;
            background-color: #f4f4f4;
        }

        .pago-anulado td {
            text-decoration: line-through;
        }

        .pago-anulado .no-strike {
            text-decoration: none !important;
        }
    </style>
</head>

<body>

    <div class="header">
        <h1>REPORTE DE PAGOS</h1>
        <p>Sistema de Gestión Administrativo - Cannibals Lara</p>
        <img src="<?= $logo ?>" class="logo-mascota" alt="Logo">
    </div>

    <div class="content">
        <div class="info-grid">
            <div class="info-item"><strong>FECHA DE EMISIÓN</strong><br><?= $fecha_reporte ?></div>
            <div class="info-item"><strong>GENERADO POR</strong><br><?= $usuario ?></div>
        </div>
        <div class="resumen-ejecutivo">
            <strong>Resumen Ejecutivo:</strong> El presente documento contiene el registro detallado de los ingresos y pagos generales procesados en el club. Esta información contempla los métodos de pago empleados, desgloses por atleta y montos devueltos de forma automatizada.
        </div>

        <div class="section-title">Historial de Transacciones</div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Fecha</th>
                    <th>Concepto General</th>
                    <th>Referencia</th>
                    <th>Método</th>
                    <th style="text-align: right;">Monto Total</th>
                    <th style="text-align: center;">Estatus</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $id = 0;
                foreach ($datos as $pago) :
                    $id++;
                    $fechaPago = date('d/m/Y', strtotime($pago['fecha_pago']));
                    $montoFormateado = number_format($pago['monto_pagado'], 2, ',', '.') . ' ' . $pago['abre'];
                    $esAnulado = ((int)$pago['estatus']) !== 1;
                    $claseFila = $esAnulado ? 'class="pago-anulado"' : '';
                ?>
                    <tr <?= $claseFila ?>>
                        <td class="data-cell"><?= $id ?></td>
                        <td class="data-cell"><?= $fechaPago ?></td>
                        <td class="data-cell"><strong><?= htmlspecialchars($pago['concepto_pago']) ?></strong></td>
                        <td class="data-cell"><?= !empty($pago['referencia']) ? htmlspecialchars($pago['referencia']) : 'N/A' ?></td>
                        <td class="data-cell"><?= htmlspecialchars($pago['nombre_metodo_pago']) ?></td>
                        <td class="data-cell" style="text-align: right; font-weight: bold;"><?= $pago['simbolo'] ?> <?= $montoFormateado ?></td>
                        <td class="data-cell no-strike" style="text-align: center;">
                            <?= $esAnulado ? '<span style="color: #e53e3e; font-weight: bold;">Anulado</span>' : '<span style="color: #38a169; font-weight: bold;">Realizado</span>' ?>
                        </td>
                    </tr>

                    <?php if (!empty($pago['detalles'])) : ?>
                        <tr class="<?= $esAnulado ? 'pago-anulado' : '' ?>">
                            <td colspan="7" class="no-strike" style="background-color: #f8fafc; padding: 5px 15px 10px 30px; border-bottom: 1px solid #e2e8f0;">
                                <div style="font-size: 11px; margin-bottom: 4px; color: #4a5568; font-weight: bold;">Cuentas Abonadas en esta Transacción:</div>
                                <table style="width: 100%; border-collapse: collapse; table-layout: fixed; word-wrap: break-word; font-size: 11px;">
                                    <thead>
                                        <tr style="border-bottom: 1px dashed #cbd5e0;">
                                            <th style="padding: 3px 0; background: transparent; color: #718096; font-size: 10px;">Atleta</th>
                                            <th style="padding: 3px 0; background: transparent; color: #718096; font-size: 10px;">Concepto Específico</th>
                                            <th style="padding: 3px 0; background: transparent; color: #718096; font-size: 10px; text-align: right;">Abono</th>
                                            <th style="padding: 3px 0; background: transparent; color: #718096; font-size: 10px; text-align: right;">Tasa Aplicada</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pago['detalles'] as $det) : ?>
                                            <tr>
                                                <td style="padding: 4px 0; color: #4a5568;"><?= htmlspecialchars($det['atleta']) ?></td>
                                                <td style="padding: 4px 0; color: #718096;"><?= htmlspecialchars($det['concepto']) ?></td>
                                                <td style="padding: 4px 0; color: #2f855a; font-weight: bold; text-align: right;"><?= number_format($det['monto'], 2, ',', '.') ?> <?= htmlspecialchars($det['moneda']) ?></td>
                                                <td style="padding: 4px 0; color: #718096; text-align: right;">
                                                    <?= (float)$det['tasa'] > 0 ? number_format($det['tasa'], 4, ',', '.') . ' ' . htmlspecialchars($det['moneda_tasa']) : 'N/A' ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>

                                <?php if (!empty($pago['vueltos'])) : ?>
                                    <div style="margin-top: 10px; font-size: 11px; color: #4a5568; font-weight: bold;">Vueltos Registrados:</div>
                                    <table style="width: 100%; border-collapse: collapse; table-layout: fixed; word-wrap: break-word; font-size: 11px;">
                                        <thead>
                                            <tr style="border-bottom: 1px dashed #cbd5e0;">
                                                <th style="padding: 3px 0; background: transparent; color: #718096; font-size: 10px;">Fecha</th>
                                                <th style="padding: 3px 0; background: transparent; color: #718096; font-size: 10px;">Método</th>
                                                <th style="padding: 3px 0; background: transparent; color: #718096; font-size: 10px;">Referencia</th>
                                                <th style="padding: 3px 0; background: transparent; color: #718096; font-size: 10px; text-align: right;">Exceso Base</th>
                                                <th style="padding: 3px 0; background: transparent; color: #718096; font-size: 10px; text-align: right;">Tasa</th>
                                                <th style="padding: 3px 0; background: transparent; color: #718096; font-size: 10px; text-align: right;">Monto Devuelto</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($pago['vueltos'] as $v) : ?>
                                                <tr>
                                                    <td style="padding: 4px 0; color: #4a5568;"><?= date('d/m/Y', strtotime($v['fecha_vuelto'])) ?></td>
                                                    <td style="padding: 4px 0; color: #718096;"><?= htmlspecialchars($v['nombre_metodo_vuelto'] ?? 'N/A') ?></td>
                                                    <td style="padding: 4px 0; color: #718096;"><?= !empty($v['referencia']) ? htmlspecialchars($v['referencia']) : 'N/A' ?></td>
                                                    <td style="padding: 4px 0; color: #718096; text-align: right;"><?= number_format($v['monto_exceso_base'], 2, ',', '.') ?> <?= htmlspecialchars($pago['abre']) ?></td>
                                                    <td style="padding: 4px 0; color: #718096; text-align: right;"><?= ($v['tasa_usada'] > 0 && $v['tasa_usada'] != 1) ? number_format($v['tasa_usada'], 4, ',', '.') . ' ' . htmlspecialchars($v['abreviatura']) : '1.0000' ?></td>
                                                    <td style="padding: 4px 0; color: #2f855a; font-weight: bold; text-align: right;"><?= number_format($v['monto_vuelto'], 2, ',', '.') ?> <?= htmlspecialchars($v['simbolo'] . ' ' . $v['abreviatura']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
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
                <td class="text-left">Cannibals Lara - Reporte Automatizado de Pagos</td>
                <td class="text-right"><span class="page-number"></span></td>
            </tr>
        </table>
    </div>

</body>

</html>