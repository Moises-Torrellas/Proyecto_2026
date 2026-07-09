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

        /* Ajuste fino del logo superior derecho */
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
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 12px;
        }

        /* Fila que agrupa los artículos */
        .group-header td {
            background-color: #e2e8f0;
            font-weight: bold;
            font-size: 13px;
            padding: 10px;
            border-bottom: 2px solid #cbd5e0;
            border-top: 2px solid #cbd5e0;
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

        /* Colores para estatus y condición */
        .txt-verde { color: #32B10B; font-weight: bold; }
        .txt-azul { color: #2b6cb0; font-weight: bold; }
        .txt-rojo { color: #e53e3e; font-weight: bold; }

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
    </style>
</head>

<body>

    <div class="header">
        <h1>REPORTE DE ARTICULOS DE INVENTARIO</h1>
        <p>Sistema de Gestión Administrativo - Cannibals Lara</p>
        <img src="<?= $logo ?>" class="logo-mascota" alt="Logo">
    </div>

    <div class="content">
        <div class="info-grid">
            <div class="info-item"><strong>FECHA DE EMISIÓN</strong><br><?= $fecha_reporte ?></div>
            <div class="info-item"><strong>GENERADO POR</strong><br><?= $usuario ?></div>
        </div>
        
        <div class="resumen-ejecutivo">
            <strong>Resumen Ejecutivo:</strong> El presente documento detalla el inventario físico actual de implementos e indumentaria de la academia. Se desglosan las unidades específicas registradas bajo su código interno, indicando el estado físico de conservación y la disponibilidad operativa de cada pieza para las actividades deportivas.
        </div>

        <div class="section-title">Detalle de Unidades en Inventario</div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 20%;">Código Club</th>
                    <th style="width: 50%;">Estado / Condición Física</th>
                    <th style="width: 30%;">Estatus Actual</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($datos)): ?>
                    <tr>
                        <td colspan="3" class="data-cell" style="text-align: center; padding: 20px;">
                            No hay artículos registrados en el inventario en este momento.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($datos as $grupo) : ?>
                        <tr class="group-header">
                            <td colspan="3">
                                <?= htmlspecialchars($grupo['articulo']) ?> 
                                <span style="font-weight: normal; color: #4a5568;"> | Categoría: <?= htmlspecialchars($grupo['categoria']) ?></span>
                                <span style="float: right;">Total: <?= count($grupo['piezas']) ?> unds.</span>
                            </td>
                        </tr>
                        
                        <?php foreach ($grupo['piezas'] as $pieza) : 
                            // Lógica de Estatus
                            if ($pieza['estatus'] == 1) {
                                $txtEstatus = 'Disponible';
                                $claseEstatus = 'txt-verde';
                            } elseif ($pieza['estatus'] == 2) {
                                $txtEstatus = 'En Uso';
                                $claseEstatus = 'txt-azul';
                            } else {
                                $txtEstatus = 'Inactivo';
                                $claseEstatus = 'txt-rojo';
                            }
                            
                            // Lógica visual para la condición física (Opcional, basado en el nivel_estado que tienes en tu BD)
                            $claseCondicion = 'txt-verde';
                            if ($pieza['nivel_estado'] == 2) $claseCondicion = 'txt-azul';
                            if ($pieza['nivel_estado'] >= 3) $claseCondicion = 'txt-rojo';
                        ?>
                            <tr>
                                <td class="data-cell" style="font-weight: bold;"><?= htmlspecialchars($pieza['codigo_club']) ?></td>
                                <td class="data-cell">
                                    <span class="<?= $claseCondicion ?>">&#9679;</span> 
                                    <?= htmlspecialchars($pieza['estado_fisico']) ?>
                                </td>
                                <td class="data-cell">
                                    <span class="<?= $claseEstatus ?>"><?= $txtEstatus ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="footer">
        <div class="footer-logo-container">
            <img src="<?= $logo_footer ?>" alt="Cannibals">
        </div>

        <table class="footer-meta">
            <tr>
                <td class="text-left">Cannibals Lara - Reporte Automatizado de Articulos de Inventario</td>
                <td class="text-right"><span class="page-number"></span></td>
            </tr>
        </table>
    </div>

</body>

</html>