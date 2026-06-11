<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Curriculum Deportivo - Cannibals Lara</title>
    <style>
        /* ==========================================================================
           1. ESTILOS COMPARTIDOS (PLANTILLA GLOBAL DE REPORTES)
           ========================================================================== */
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
            font-size: 11pt;
            line-height: 1.5;
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

        .text-left {
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .page-number:before {
            content: "Página " counter(page);
        }

        /* ==========================================================================
           2. ESTILOS PROPIOS DEL CURRICULUM (PRESERVADOS INTACTOS)
           ========================================================================== */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        /* Bloque del Perfil del Atleta */
        .profile-container {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }

        .profile-row {
            display: table-row;
        }

        /* 1. Incrementamos el ancho de la celda principal */
        .profile-photo-cell {
            display: table-cell;
            width: 170px;
            /* Aumentado (antes 140px) */
            vertical-align: top;
        }

        /* 2. Ajustamos el contenedor para que sea más grande */
        .photo-container {
            width: 170px;
            /* Aumentado (antes 140px) */
            height: 215px;
            /* Aumentado para mantener la proporción de retrato (antes 165px) */
            background-color: #f1f5f9;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            text-align: center;
            overflow: hidden;
        }

        /* 3. Solucionamos el estiramiento */
        .photo-container img {
            width: 100%;
            height: auto;
            /* 'auto' mantiene la proporción real sin aplastar el rostro */
            min-height: 100%;
            /* Asegura que cubra el fondo si la imagen es un poco corta */
            object-fit: cover;
            /* Funciona para mantener la proporción en la vista web del navegador */
            object-position: top;
            /* Si hay un recorte, asegura que la cabeza no quede por fuera */
        }

        .profile-info-cell {
            display: table-cell;
            padding-left: 25px;
            vertical-align: top;
        }

        .player-name {
            font-size: 18pt;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 12px;
            text-transform: uppercase;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 5px;
        }

        .info-grid {
            display: table;
            width: 100%;
        }

        .info-row {
            display: table-row;
        }

        .info-label {
            display: table-cell;
            font-weight: bold;
            color: #475569;
            padding: 4px 0;
            width: 110px;
            font-size: 10.5pt;
        }

        .info-value {
            display: table-cell;
            color: #1e293b;
            padding: 4px 0;
            font-size: 10.5pt;
        }

        /* Títulos de sección con barra de acento */
        .section-title {
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #0f172a;
            border-left: 5px solid #32B10B;
            padding-left: 10px;
            margin-top: 25px;
            margin-bottom: 15px;
            page-break-after: avoid;
        }

        /* Bloque de Números Estadísticos Totales */
        .stats-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .stats-cell {
            width: 20%;
            text-align: center;
            background-color: #f8fafc;
            border-top: 4px solid #32B10B;
            padding: 12px 5px;
            border-right: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
        }

        .stats-cell:first-child {
            border-left: 1px solid #e2e8f0;
        }

        .stats-val {
            font-size: 20pt;
            font-weight: bold;
            color: #2e9e0b;
            margin-bottom: 2px;
        }

        .stats-lbl {
            font-size: 8.5pt;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        /* Bloques de Desglose por Torneo */
        .tournament-block {
            margin-bottom: 25px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            background-color: #ffffff;
            page-break-inside: avoid;
        }

        .tournament-header {
            background-color: #f1f5f9;
            padding: 10px 15px;
            font-weight: bold;
            font-size: 11pt;
            color: #1e293b;
            border-bottom: 1px solid #e2e8f0;
        }

        .tournament-header-date {
            float: right;
            color: #64748b;
            font-weight: normal;
            font-size: 10pt;
        }

        .tournament-body {
            padding: 15px;
        }

        .breakdown-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .breakdown-table th {
            background-color: #f8fafc;
            color: #475569;
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
            padding: 6px 10px;
            border-bottom: 2px solid #e2e8f0;
            text-align: center;
        }

        .breakdown-table td {
            padding: 8px 10px;
            text-align: center;
            font-size: 10.5pt;
            border-bottom: 1px solid #f1f5f9;
        }

        /* Secciones de Premiaciones con Etiquetas/Badges */
        .awards-container {
            display: table;
            width: 100%;
            margin-top: 10px;
            border-top: 1px dashed #e2e8f0;
            padding-top: 10px;
        }

        .awards-column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .awards-column:first-child {
            padding-right: 15px;
        }

        .awards-column:last-child {
            padding-left: 15px;
            border-left: 1px dashed #e2e8f0;
        }

        .awards-title {
            font-size: 9.5pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #475569;
            margin-bottom: 6px;
        }

        .award-badge {
            display: inline-block;
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
            font-size: 9pt;
            padding: 3px 8px;
            border-radius: 4px;
            margin-right: 5px;
            margin-bottom: 5px;
        }

        .award-none {
            font-size: 9.5pt;
            color: #94a3b8;
            font-style: italic;
        }
    </style>
</head>

<body>

    <div class="header">
        <h1>Curriculum Deportivo</h1>
        <p>Sistema de Gestión Administrativo - Cannibals Lara</p>
        <?php if (!empty($logo)): ?>
            <img src="<?= $logo ?>" alt="Logo Club" class="logo-mascota">
        <?php endif; ?>
    </div>

    <div class="content">

        <div class="profile-container">
            <div class="profile-row">
                <div class="profile-photo-cell">
                    <div class="photo-container">
                        <?php if (!empty($foto)): ?>
                            <img src="<?= $foto ?>" alt="Foto Atleta">
                        <?php else: ?>
                            <div style="padding-top: 45px; font-size: 9pt; color: #94a3b8;">SIN FOTO</div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="profile-info-cell">
                    <div class="player-name"><?= htmlspecialchars($atleta['nombres'] . ' ' . $atleta['apellidos']) ?></div>
                    <div class="info-grid">
                        <div class="info-row">
                            <div class="info-label">Nacimiento:</div>
                            <div class="info-value"><?= date('d/m/Y', strtotime($atleta['fecha_nac'])) ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Edad:</div>
                            <div class="info-value">
                                <?php
                                $nacimiento = new DateTime($atleta['fecha_nac']);
                                $hoy = new DateTime();
                                echo $hoy->diff($nacimiento)->y . ' Años';
                                ?>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Categoría:</div>
                            <div class="info-value"><?= htmlspecialchars($atleta['nombre_categoria']) ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Posición:</div>
                            <div class="info-value"><?= htmlspecialchars($atleta['nombre_posicion']) ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Doc. Identidad:</div>
                            <div class="info-value"><?= htmlspecialchars($atleta['doc_identidad']) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="section-title">Estadísticas Totales</div>
        <table class="stats-table">
            <tr>
                <td class="stats-cell">
                    <div class="stats-val"><?= $totales['goles'] ?></div>
                    <div class="stats-lbl">Goles</div>
                </td>
                <td class="stats-cell">
                    <div class="stats-val"><?= $totales['asistencias'] ?></div>
                    <div class="stats-lbl">Asistencias</div>
                </td>
                <td class="stats-cell">
                    <div class="stats-val"><?= $totales['penalizaciones'] ?></div>
                    <div class="stats-lbl">Penalizaciones</div>
                </td>
                <td class="stats-cell">
                    <div class="stats-val"><?= $totales['goles_contra'] ?></div>
                    <div class="stats-lbl">Goles Contra</div>
                </td>
                <td class="stats-cell">
                    <div class="stats-val"><?= $totales['partidos_jugados'] ?></div>
                    <div class="stats-lbl">Partidos Jugados</div>
                </td>
                <td class="stats-cell">
                    <div class="stats-val"><?= number_format($totales['average'], 2) ?></div>
                    <div class="stats-lbl">Average</div>
                </td>
            </tr>
        </table>

        <div class="section-title">Desglose por Torneo y Premiaciones</div>

        <?php if (!empty($torneos)): ?>
            <?php foreach ($torneos as $t): ?>
                <div class="tournament-block">
                    <div class="tournament-header">
                        <?= htmlspecialchars($t['nombre_torneo']) ?>
                        <span class="tournament-header-date">Fecha Fin: <?= date('d/m/Y', strtotime($t['fecha_fin'])) ?></span>
                    </div>
                    <div class="tournament-body">
                        <table class="breakdown-table">
                            <thead>
                                <tr>
                                    <th>PJ</th>
                                    <th>Goles</th>
                                    <th>Asistencias</th>
                                    <th>Penalizaciones</th>
                                    <th>Goles Contra</th>
                                    <th>Promedio</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><?= $t['partidos_jugados'] ?></td>
                                    <td><?= $t['goles'] ?></td>
                                    <td><?= $t['asistencias'] ?></td>
                                    <td><?= $t['penalizaciones'] ?></td>
                                    <td><?= $t['goles_contra'] ?></td>
                                    <td><?= number_format($t['average'], 2) ?></td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="awards-container">
                            <div class="awards-column">
                                <div class="awards-title">Premios Individuales</div>
                                <?php if (!empty($t['premios_individuales'])): ?>
                                    <?php foreach ($t['premios_individuales'] as $premioInd): ?>
                                        <span class="award-badge"><?= htmlspecialchars($premioInd) ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="award-none">Ninguno</span>
                                <?php endif; ?>
                            </div>
                            <div class="awards-column">
                                <div class="awards-title">Premios Grupales</div>
                                <?php if (!empty($t['premios_grupales'])): ?>
                                    <?php foreach ($t['premios_grupales'] as $premioGrp): ?>
                                        <span class="award-badge"><?= htmlspecialchars($premioGrp) ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="award-none">Ninguno</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="font-style: italic; color: #64748b;">No existen registros de torneos completados para este atleta.</p>
        <?php endif; ?>

    </div>

    <div class="footer">
        <table class="footer-meta">
            <tr>
                <td class="text-left">Cannibals Lara - Reporte Automatizado de Currículum</td>
                <td class="text-right"><span class="page-number"></span></td>
            </tr>
        </table>
    </div>

</body>

</html>