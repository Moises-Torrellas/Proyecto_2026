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
            margin-top: 20px;
            background-color: #f7fafc;
            padding-top: 5px;
            padding-bottom: 5px;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse; table-layout: fixed; word-wrap: break-word;
            margin-top: 10px;
            margin-bottom: 20px;
        }

        th {
            background-color: #edf2f7;
            text-align: center;
            padding: 10px;
            font-size: 12px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        th.left-align {
            text-align: left;
        }

        td.data-cell {
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 12px;
            text-align: center;
            word-wrap: break-word; 
        }

        td.left-align {
            text-align: left;
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

        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .page-number:before { content: "Página " counter(page); }
    </style>
</head>

<body>

    <div class="header">
        <h1>REPORTE DE ESTADÍSTICAS</h1>
        <p>Sistema de Gestión Administrativo - Cannibals Lara</p>
        <img src="<?= $logo ?? '' ?>" class="logo-mascota" alt="Logo">
    </div>

    <div class="content">
        <div class="info-grid">
            <div class="info-item"><strong>FECHA DE EMISIÓN</strong><br><?= $fecha_reporte ?? date('d/m/Y') ?></div>
            <div class="info-item"><strong>GENERADO POR</strong><br><?= $usuario ?? 'Administrador' ?></div>
        </div>
        
        <div class="resumen-ejecutivo">
            <strong>Resumen Ejecutivo:</strong> Este reporte consolida las estadísticas de rendimiento de los atletas de Cannibals Lara, desglosando los torneos y sus respectivas métricas de juego (Goles, Asistencias, Average, etc.).
        </div>

        <?php 
        if (!empty($datos)) {
            // Agrupar por atleta
            $atletasGroup = [];
            foreach ($datos as $dato) {
                $id = $dato['id_atleta'];
                if (!isset($atletasGroup[$id])) {
                    $atletasGroup[$id] = [
                        'nombre' => $dato['nombres'] . ' ' . $dato['apellidos'],
                        'torneos' => []
                    ];
                }
                $atletasGroup[$id]['torneos'][] = $dato;
            }

            foreach ($atletasGroup as $atleta) {
        ?>
            <div class="section-title">Estadísticas de: <?= htmlspecialchars($atleta['nombre']) ?></div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 30%;" class="left-align">Torneo</th>
                        <th style="width: 10%;">PJ</th>
                        <th style="width: 10%;">Goles</th>
                        <th style="width: 10%;">Asist.</th>
                        <th style="width: 10%;">Penal.</th>
                        <th style="width: 15%;">G. Contra</th>
                        <th style="width: 15%;">Average</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($atleta['torneos'] as $t): ?>
                        <tr>
                            <td class="data-cell left-align">
                                <strong><?= htmlspecialchars($t['torneo_nombre']) ?></strong><br>
                                <span style="font-size: 10px; color: #718096;"><?= date("d/m/Y", strtotime($t['fecha_inicio'])) ?></span>
                            </td>
                            <td class="data-cell"><?= htmlspecialchars($t['partidos_jugados']) ?></td>
                            <td class="data-cell"><?= htmlspecialchars($t['goles']) ?></td>
                            <td class="data-cell"><?= htmlspecialchars($t['asistencias']) ?></td>
                            <td class="data-cell"><?= htmlspecialchars($t['penalizaciones']) ?></td>
                            <td class="data-cell"><?= htmlspecialchars($t['goles_contra']) ?></td>
                            <td class="data-cell"><?= number_format((float)$t['average'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php 
            }
        } else {
        ?>
            <div style="text-align: center; padding: 20px; color: #a0aec0;">No hay datos para mostrar con los filtros seleccionados.</div>
        <?php } ?>
    </div>

    <div class="footer">
        <div class="footer-logo-container">
            <img src="<?= $logo_footer ?? '' ?>" alt="Cannibals">
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
