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

        /* 2. Header: Ocupa el 100% real de la hoja sin dejar bordes blancos */
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

        .grafico-placeholder { border: 1px dashed #cbd5e0; padding: 5px; text-align: center; margin-bottom: 30px; }

        .chart { width: 100%; }

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
            border-collapse: collapse; table-layout: fixed; word-wrap: break-word;
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
    </style>
</head>

<body>

    <div class="header">
        <h1>REPORTE ESTADÍSTICO DE RENDIMIENTO DEPORTIVO</h1>
        <p>Sistema de Gestión Administrativo - Cannibals Lara</p>
        <img src="<?= $logo ?>" class="logo-mascota" alt="Logo">
    </div>

    <div class="content">
        <div class="info-grid">
            <div class="info-item"><strong>FECHA DE EMISIÓN</strong><br><?= $fecha_reporte ?></div>
            <div class="info-item"><strong>GENERADO POR</strong><br><?= $usuario ?></div>
        </div>
        
        <div class="resumen-ejecutivo">
            <strong>Resumen Ejecutivo:</strong> Este documento refleja la auditoría de rendimiento técnico y efectividad ofensiva de los atletas del club. Permite evaluar y contrastar de manera directa el volumen de goles anotados y asistencias otorgadas por cada jugador en sus respectivos torneos, facilitando el seguimiento evolutivo de la plantilla y la toma de decisiones técnicas.
        </div>

        <div class="section-title">Visualización Estadística (Goles vs. Asistencias)</div>
        <div class="grafico-placeholder"><img src="<?= $charC ?>" class="chart" alt="Gráfico de Rendimiento"></div>

        <div class="section-title">Desglose Colectivo e Individual de Rendimiento</div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 8%;">ID</th>
                    <th style="width: 34%;">Atleta / Jugador</th>
                    <th style="width: 28%;">Torneo / Competencia</th>
                    <th style="width: 15%;">Total Goles</th>
                    <th style="width: 15%;">Asistencias</th>
                </tr>
            </thead>
            <tbody>
                <?php $id = 0;
                foreach ($datos as $r) :
                    $id++; 
                    $atleta = !empty($r['atleta']) ? htmlspecialchars($r['atleta']) : 'N/A';
                    $torneo = !empty($r['torneo']) ? htmlspecialchars($r['torneo']) : 'N/A';
                    $goles = (int)($r['total_goles'] ?? 0);
                    $asistencias = (int)($r['total_asistencias'] ?? 0);
                ?>
                    <tr>
                        <td class="data-cell"><?= $id ?></td>
                        <td class="data-cell" style="font-weight: 500;"><?= $atleta ?></td>
                        <td class="data-cell" style="color: #4a5568;"><?= $torneo ?></td>
                        <td class="data-cell" style="color: #d97706; font-weight: bold;"><?= $goles ?></td>
                        <td class="data-cell" style="color: #6b46c1; font-weight: bold;"><?= $asistencias ?></td>
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
                <td class="text-left">Cannibals Lara - Reporte Técnico Automatizado</td>
                <td class="text-right"><span class="page-number"></span></td>
            </tr>
        </table>
    </div>

</body>

</html>