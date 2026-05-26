<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        /* Definimos el tamaño del papel A4 */
        @page { margin: 0; size: A4; }
        
        body { 
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; 
            margin: 0; padding: 0; 
            color: #2d3748;
        }

        /* Tabla maestra: Forzamos la altura a 842pt (A4) */
        .wrapper { 
            width: 100%; 
            height: 842pt; 
            border-collapse: collapse; 
            table-layout: fixed;
        }
        
        .wrapper td { padding: 0; vertical-align: top; }

        /* Estilos visuales */
        .header { background-color: #1a202c; color: white; padding: 20px 40px; border-bottom: 5px solid #32B10B; }
        .header h1 { margin: 0; font-size: 20px; text-transform: uppercase; }
        .header p { margin: 5px 0 0; font-size: 14px; color: #a0aec0; }
        .logo-mascota { float: right; width: 60px; margin-top: -55px; }

        .container { padding: 20px 40px; }
        .info-grid { display: table; width: 100%; margin-bottom: 20px; }
        .info-item { display: table-cell; font-size: 13px; }
        .resumen-ejecutivo { background-color: #f7fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 8px; margin-bottom: 25px; }
        .section-title { font-size: 16px; font-weight: bold; border-left: 5px solid #32B10B; padding-left: 10px; margin-bottom: 15px; }
        .grafico-placeholder { border: 1px dashed #cbd5e0; padding: 20px; text-align: center; margin-bottom: 30px; }
        
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #edf2f7; text-align: left; padding: 10px; font-size: 12px; border-bottom: 2px solid #e2e8f0; }
        td.data-cell { padding: 10px; border-bottom: 1px solid #e2e8f0; font-size: 12px; }

        /* Footer */
        .footer { text-align: center; padding: 20px 0; font-size: 12px; border-top: 1px solid #e2e8f0; }
        .footer img { width: 100px; display: block; margin: 0 auto 5px; }
    </style>
</head>
<body>

<table class="wrapper">
    <thead>
        <tr><td style="height: 120px;"> <div class="header">
                <h1>REPORTE ESTADÍSTICO DE ATLETAS POR CATEGORÍA</h1>
                <p>Sistema de Gestión Administrativo - Cannibals Lara</p>
                <img src="logo.png" class="logo-mascota" alt="Logo">
            </div>
        </td></tr>
    </thead>

    <tbody>
        <tr><td style="height: auto;"> <div class="container">
                <div class="info-grid">
                    <div class="info-item"><strong>FECHA DE EMISIÓN</strong><br>24 de Mayo, 2026</div>
                    <div class="info-item"><strong>GENERADO POR</strong><br>Sistema Automatizado (AI Assistant)</div>
                </div>

                <div class="resumen-ejecutivo">
                    <strong>Resumen Ejecutivo:</strong> Este documento refleja la distribución actual de los atletas inscritos en el club.
                </div>

                <div class="section-title">Visualización Estadística</div>
                <div class="grafico-placeholder">[INSERTAR GRÁFICO AQUÍ]</div>

                <div class="section-title">Desglose Operativo por Tabla</div>
                <table class="data-table">
                    <thead>
                        <tr><th>ID</th><th>Nombre Categoría</th><th>Edad Mínima</th><th>Edad Máxima</th><th>Total Atletas</th></tr>
                    </thead>
                    <tbody>
                        <tr><td class="data-cell">1</td><td class="data-cell">U-12</td><td class="data-cell">11 años</td><td class="data-cell">12 años</td><td class="data-cell">15</td></tr>
                    </tbody>
                </table>
            </div>
        </td></tr>
    </tbody>

    <tfoot>
        <tr><td style="height: 80px;"> <div class="footer">
                <img src="logo_2.png" alt="Cannibals">
                Cannibals Lara - Reporte Automatizado &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Página 1 de 1
            </div>
        </td></tr>
    </tfoot>
</table>

</body>
</html>