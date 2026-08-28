<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <style>
        /* 1. Forzar a que la hoja no tenga ningún margen exterior */
        @page {
            margin: 130px 0px 80px 0px;
            size: A4 landscape;
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
            top: -130px; /* Sube al espacio reservado */
            left: 0px;
            right: 0px;
            height: 60px; /* Fijamos altura interna para que cuadre exacto en los 140px */
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
            font-size: 11px;
        }

        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .info-row {
            display: table-row;
        }

        .info-cell {
            display: table-cell;
            width: 33%;
            padding-right: 15px;
        }

        .label {
            color: #718096;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
            display: block;
        }

        .value {
            font-weight: 600;
            font-size: 14px;
        }

        /* 4. Footer idéntico al Header para que el pegado sea perfecto */
        .footer {
            position: fixed;
            bottom: -80px; 
            left: 0px;
            right: 0px;
            height: 40px;
            background-color: #1a202c;
            color: #a0aec0;
            text-align: center;
            padding: 10px 40px;
            font-size: 12px;
            border-top: 3px solid #32B10B;
        }
        
        .footer .page-number:after {
            content: counter(page);
        }

        /* 5. Utilidades y status */
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-active { background-color: #def7ec; color: #03543f; }
        .status-inactive { background-color: #fde8e8; color: #9b1c1c; }
    </style>
</head>

<body>

    <div class="header">
        <h1>Sistema de Gestión Deportiva</h1>
        <p><?= htmlspecialchars($titulo) ?> - Fecha de emisión: <?= date('d/m/Y') ?></p>
    </div>

    <div class="footer">
        Este documento es generado automáticamente por el sistema. Página <span class="page-number"></span>
    </div>

    <div class="content">

        <div class="section-title">Resumen del Reporte</div>
        
        <div class="resumen-ejecutivo">
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-cell">
                        <span class="label">Total Registros</span>
                        <span class="value"><?= count($datos) ?> acciones</span>
                    </div>
                    <div class="info-cell">
                        <span class="label">Filtro Módulo</span>
                        <span class="value"><?= htmlspecialchars($filtros['modulo'] ?? 'Todos') ?></span>
                    </div>
                    <div class="info-cell">
                        <span class="label">Filtro Usuario</span>
                        <span class="value"><?= htmlspecialchars($filtros['usuario'] ?? 'Todos') ?></span>
                    </div>
                </div>
                <div class="info-row" style="padding-top: 15px; display: table-row;">
                    <div class="info-cell" style="padding-top: 15px;">
                        <span class="label">Desde Fecha</span>
                        <span class="value"><?= htmlspecialchars($filtros['fecha_inicio'] ?? 'N/A') ?></span>
                    </div>
                    <div class="info-cell" style="padding-top: 15px;">
                        <span class="label">Hasta Fecha</span>
                        <span class="value"><?= htmlspecialchars($filtros['fecha_fin'] ?? 'N/A') ?></span>
                    </div>
                    <div class="info-cell" style="padding-top: 15px;">
                    </div>
                </div>
            </div>
            <p style="margin: 15px 0 0 0; color: #4a5568; line-height: 1.5;">
                A continuación, se detalla el listado de las acciones registradas en la bitácora del sistema que cumplen con los criterios de filtrado seleccionados.
            </p>
        </div>

        <div class="section-title">Detalle de Acciones</div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 15%">Fecha y Hora</th>
                    <th style="width: 20%">Usuario</th>
                    <th style="width: 15%">Módulo</th>
                    <th style="width: 20%">Acción</th>
                    <th style="width: 15%">Datos Previos</th>
                    <th style="width: 15%">Datos Nuevos</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($datos)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 20px;">No se encontraron registros para los filtros seleccionados.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($datos as $dato): ?>
                        <?php
                            $fechaFormateada = date('d/m/Y', strtotime($dato['fecha'])) . ' ' . date('h:i A', strtotime($dato['hora']));
                            $datosPrevios = (!empty($dato['datos_previos']) && $dato['datos_previos'] !== 'null') ? $dato['datos_previos'] : 'No Aplica';
                            $datosNuevos = (!empty($dato['datos_nuevos']) && $dato['datos_nuevos'] !== 'null') ? $dato['datos_nuevos'] : 'No Aplica';
                        ?>
                        <tr>
                            <td class="data-cell">
                                <?= $fechaFormateada ?>
                            </td>
                            <td class="data-cell">
                                <?= htmlspecialchars($dato['cedulaUsuario']) ?><br>
                                <strong><?= htmlspecialchars($dato['nombreUsuario'] . ' ' . $dato['apellidoUsuario']) ?></strong>
                            </td>
                            <td class="data-cell">
                                <?= htmlspecialchars($dato['nombre_modulo']) ?>
                            </td>
                            <td class="data-cell">
                                <?= htmlspecialchars($dato['acciones']) ?>
                            </td>
                            <td class="data-cell" style="font-size: 10px; color:#555;">
                                <?= htmlspecialchars($datosPrevios) ?>
                            </td>
                            <td class="data-cell" style="font-size: 10px; color:#28a745;">
                                <?= htmlspecialchars($datosNuevos) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

    </div>

</body>
</html>
