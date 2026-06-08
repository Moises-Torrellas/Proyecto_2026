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
            top: -130px; /* Sube al espacio reservado */
            left: 0px;
            right: 0px;
            height: 60px; /* Fijamos altura interna para que cuadre exacto */
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
            border-collapse: collapse;
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

        /* 4. Footer: Posicionado abajo del todo de forma fija, alineado con los lados del contenido */
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
            border-collapse: collapse;
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
        <h1>REPORTE DE CATÁLOGO DE EQUIPAMIENTOS</h1>
        <p>Sistema de Gestión Administrativo - Cannibals Lara</p>
        <img src="<?= $logo ?>" class="logo-mascota" alt="Logo">
    </div>

    <div class="content">
        <div class="info-grid">
            <div class="info-item"><strong>FECHA DE EMISIÓN</strong><br><?= $fecha_reporte ?></div>
            <div class="info-item"><strong>GENERADO POR</strong><br><?= $usuario ?></div>
        </div>
        
        <div class="resumen-ejecutivo">
            <strong>Resumen Ejecutivo:</strong> El presente documento contiene el registro detallado de los artículos, indumentaria e implementos deportivos registrados en el inventario maestro de la academia. Esta información permite auditar de forma centralizada los requerimientos técnicos por posición, la asignación de tallas disponibles y los umbrales de stock mínimo exigidos para garantizar la correcta continuidad de los entrenamientos.
        </div>

        <div class="section-title">Listado de Artículos Registrados</div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 8%;">N°</th>
                    <th style="width: 32%;">Nombre del Artículo</th>
                    <th style="width: 25%;">Categoría</th>
                    <th style="width: 15%;">Posición</th>
                    <th style="width: 10%;">Talla</th>
                    <th style="width: 10%; text-align: right;">Stock Mín.</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $contador = 0;
                foreach ($datos as $item): 
                    $contador++; 
                ?>
                    <tr>
                        <td class="data-cell"><?= $contador ?></td>
                        <td class="data-cell" style="font-weight: bold;"><?= htmlspecialchars($item['nombre']) ?></td>
                        <td class="data-cell"><?= htmlspecialchars($item['categoria_nombre']) ?></td>
                        <td class="data-cell">
                            <?= !empty($item['posicion_nombre']) ? htmlspecialchars($item['posicion_nombre']) : '<span style="color: #a0aec0;">N/A</span>' ?>
                        </td>
                        <td class="data-cell" style="text-align: center;">
                            <?= !empty($item['talla']) ? htmlspecialchars($item['talla']) : '<span style="color: #a0aec0;">N/A</span>' ?>
                        </td>
                        <td class="data-cell" style="text-align: right; font-weight: bold; color: #2b6cb0;">
                            <?= htmlspecialchars($item['stock_minimo']) ?> u.
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
                <td class="text-left">Cannibals Lara - Reporte Automatizado de Catálogo</td>
                <td class="text-right"><span class="page-number"></span></td>
            </tr>
        </table>
    </div>

</body>

</html>