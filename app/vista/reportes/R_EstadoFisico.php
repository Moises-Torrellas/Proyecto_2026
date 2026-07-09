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
        <h1>REPORTE DE ESTADOS FÍSICOS</h1>
        <p>Sistema de Gestión Administrativo - Cannibals Lara</p>
        <img src="<?= $logo ?>" class="logo-mascota" alt="Logo">
    </div>

    <div class="content">
        <div class="info-grid">
            <div class="info-item"><strong>FECHA DE EMISIÓN</strong><br><?= $fecha_reporte ?></div>
            <div class="info-item"><strong>GENERADO POR</strong><br><?= $usuario ?></div>
        </div>
        
        <div class="resumen-ejecutivo">
            <strong>Resumen Ejecutivo:</strong> Catálogo de las condiciones de uso y estados de deterioro configurados en el sistema. Estos parámetros permiten clasificar los implementos e indumentaria deportiva según su nivel de desgaste físico actual, facilitando la toma de decisiones para reparaciones o reemplazos.
        </div>

        <div class="section-title">Listado de Estados de Condición</div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 10%;">N°</th>
                    <th style="width: 50%;">Nombre del Estado</th>
                    <th style="width: 40%;">Nivel de Severidad</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (empty($datos)) :
                ?>
                    <tr>
                        <td colspan="3" class="data-cell" style="text-align: center; padding: 20px;">No hay registros para mostrar.</td>
                    </tr>
                <?php
                else:
                    $contador = 0;
                    foreach ($datos as $item) :
                        $contador++;
                        
                        // Lógica de colores y texto según el nivel
                        if ($item['nivel_estado'] == 1) { 
                            $nivelTxt = "Buen Estado"; 
                            $color = "#32B10B"; 
                        } elseif ($item['nivel_estado'] == 2) { 
                            $nivelTxt = "Desgaste Medio"; 
                            $color = "#d69e2e"; 
                        } else { 
                            $nivelTxt = "Mal Estado"; 
                            $color = "#e53e3e"; 
                        }
                    ?>
                        <tr>
                            <td class="data-cell"><?= $contador ?></td>
                            <td class="data-cell" style="font-weight: bold;"><?= htmlspecialchars($item['nombre']) ?></td>
                            <td class="data-cell" style="color: <?= $color ?>; font-weight: bold;">
                                &#9679; <?= $nivelTxt ?>
                            </td>
                        </tr>
                    <?php endforeach; 
                endif; ?>
            </tbody>
        </table>
    </div>

    <div class="footer">
        <div class="footer-logo-container">
            <img src="<?= $logo_footer ?>" alt="Cannibals">
        </div>

        <table class="footer-meta">
            <tr>
                <td class="text-left">Cannibals Lara - Reporte Automatizado de Estados Físicos</td>
                <td class="text-right"><span class="page-number"></span></td>
            </tr>
        </table>
    </div>

</body>

</html>