<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <style>
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

        .cargo-anulado {
            filter: grayscale(1);
            opacity: 0.6;
            background-color: #f4f4f4;
        }
        
        .cargo-anulado td {
            text-decoration: line-through;
        }
    </style>
</head>

<body>

    <div class="header">
        <h1>REPORTE DE CUENTAS POR COBRAR (CARGOS)</h1>
        <p>Sistema de Gestión Administrativo - Cannibals Lara</p>
        <img src="<?= $logo ?>" class="logo-mascota" alt="Logo">
    </div>

    <div class="content">
        <div class="info-grid">
            <div class="info-item"><strong>FECHA DE EMISIÓN</strong><br><?= $fecha_reporte ?></div>
            <div class="info-item"><strong>GENERADO POR</strong><br><?= $usuario ?></div>
        </div>
        <div class="resumen-ejecutivo">
            <strong>Resumen Ejecutivo:</strong> El presente documento contiene el registro de los cargos y cuentas por cobrar de los atletas, junto con su estatus actual, fecha de vencimiento y montos pendientes correspondientes.
        </div>

        <div class="section-title">Historial de Cargos</div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Atleta</th>
                    <th>Concepto</th>
                    <th>Emisión</th>
                    <th>Total</th>
                    <th>Pendiente</th>
                    <th style="text-align: center;">Estatus</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $id = 0;
                foreach ($datos as $cargo) :
                    $id++;
                    $fechaEmision = date('d/m/Y', strtotime($cargo['fecha_emision']));
                    $montoTotal = number_format($cargo['monto_total'], 2, ',', '.') . ' ' . $cargo['moneda_simbolo'];
                    $montoPendiente = number_format($cargo['monto_pendiente'], 2, ',', '.') . ' ' . $cargo['moneda_simbolo'];
                    $estatus = (int) $cargo['estatus'];
                    
                    $claseFila = '';
                    $textoEstatus = 'Pendiente';
                    $colorEstatus = '#d69e2e'; // Amarillo
                    
                    if ($estatus === 3) {
                        $textoEstatus = 'Anulado';
                        $claseFila = 'class="cargo-anulado"';
                        $colorEstatus = '#e53e3e';
                    } elseif ($estatus === 2 || (float)$cargo['monto_pendiente'] == 0) {
                        $textoEstatus = 'Pagado';
                        $colorEstatus = '#38a169';
                    } elseif ($cargo['monto_pendiente'] < $cargo['monto_total']) {
                        $textoEstatus = 'Abonado';
                        $colorEstatus = '#3182ce';
                    }
                ?>
                    <tr <?= $claseFila ?>>
                        <td class="data-cell"><?= $id ?></td>
                        <td class="data-cell"><strong><?= htmlspecialchars($cargo['atleta_nombre'] . ' ' . $cargo['atleta_apellido']) ?></strong></td>
                        <td class="data-cell"><?= htmlspecialchars($cargo['concepto_nombre']) ?></td>
                        <td class="data-cell"><?= $fechaEmision ?></td>
                        <td class="data-cell" style="font-weight: bold;"><?= $montoTotal ?></td>
                        <td class="data-cell" style="color: <?= (float)$cargo['monto_pendiente'] > 0 ? '#e53e3e' : '#38a169' ?>;"><strong><?= $montoPendiente ?></strong></td>
                        <td class="data-cell" style="text-align: center; font-weight: bold; color: <?= $colorEstatus ?>;">
                            <?= $textoEstatus ?>
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
                <td class="text-left">Cannibals Lara - Reporte Automatizado de Cuentas por Cobrar</td>
                <td class="text-right"><span class="page-number"></span></td>
            </tr>
        </table>
    </div>

</body>

</html>
