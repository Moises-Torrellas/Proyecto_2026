<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <style>
        /* 1. Forzar a que la hoja no tenga ningún margen exterior */
        @page {
            margin: 130px 0px 80px 0px;
            size: A4 landscape; /* Cambiado a landscape para que quepan mejor las columnas */
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #2d3748;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }

        /* 2. Header */
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
            word-wrap: break-word; 
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
        <h1>REPORTE DE PARTICIPACIONES</h1>
        <p>Sistema de Gestión Administrativo - Cannibals Lara</p>
        <img src="<?= $logo ?? '' ?>" class="logo-mascota" alt="Logo">
    </div>

    <div class="content">
        <div class="info-grid">
            <div class="info-item"><strong>FECHA DE EMISIÓN</strong><br><?= $fecha_reporte ?? date('d/m/Y') ?></div>
            <div class="info-item"><strong>GENERADO POR</strong><br><?= $usuario ?? 'Administrador' ?></div>
        </div>
        
        <div class="resumen-ejecutivo">
            <strong>Resumen Ejecutivo:</strong> El presente documento detalla la información de las participaciones de los equipos en los torneos registrados, especificando la cantidad de atletas inscritos en cada uno.
        </div>

        <div class="section-title">Participaciones Registradas</div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 30%;">Torneo</th>
                    <th style="width: 15%;">Fecha de Inicio</th>
                    <th style="width: 25%;">Equipo Inscrito</th>
                    <th style="width: 10%;">Atletas</th>
                    <th style="width: 15%;">Estado Torneo</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $id = 0;
                if (!empty($datos)) {
                    foreach ($datos as $r): 
                        $id++; 
                        $torneo_nombre = htmlspecialchars($r['torneo_nombre']);
                        $fecha_inicio = date('d/m/Y', strtotime($r['fecha_inicio']));
                        $equipo_nombre = htmlspecialchars($r['equipo_nombre']);
                        $cantidad_atletas = $r['cantidad_atletas'];
                        
                        $estatus = htmlspecialchars($r['torneo_estatus']);
                        $estatus_texto = '';
                        $color_estatus = '';
                        if ($estatus == 1) {
                            $estatus_texto = 'Por Disputarse';
                            $color_estatus = '#f59e0b'; // Naranja
                        } else if ($estatus == 2) {
                            $estatus_texto = 'En Curso';
                            $color_estatus = '#3b82f6'; // Azul
                        } else if ($estatus == 3) {
                            $estatus_texto = 'Finalizado';
                            $color_estatus = '#ef4444'; // Rojo
                        } else {
                            $estatus_texto = $estatus;
                            $color_estatus = '#2d3748';
                        }
                ?>
                    <tr>
                        <td class="data-cell"><?= $id ?></td>
                        <td class="data-cell"><strong><?= $torneo_nombre ?></strong></td>
                        <td class="data-cell"><?= $fecha_inicio ?></td>
                        <td class="data-cell"><?= $equipo_nombre ?></td>
                        <td class="data-cell"><?= $cantidad_atletas ?></td>
                        <td class="data-cell" style="color: <?= $color_estatus ?>; font-weight: bold;"><?= $estatus_texto ?></td>
                    </tr>
                <?php 
                    endforeach; 
                } 
                ?>
            </tbody>
        </table>
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
