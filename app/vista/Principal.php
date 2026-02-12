<!DOCTYPE html>
<html lang="en">

<head>
    <?php include('complementos/head.php'); ?>
    <title>Pagina Principal</title>
</head>

<body data-tema="<?= _TEMA_ === 'oscuro' ? 'oscuro' : 'claro' ?>">
    <?php include('complementos/loader.php'); ?>
    <?php include('complementos/circle.php'); ?>
    <section class="contenedor">
        <?php include('complementos/nav_superior.php'); ?>
        <?php include('complementos/nav_lateral.php'); ?>
        <div class="contenido">
            <div class="contenido_modulo">
                <div class="contenedor_funciones">
                    <div class="contenedor_opciones">
                        <div class="contenedor_titulo">
                            <h2 class="titulo_pagina" id="titulo">Pagina Principal</h2>
                        </div>
                        <div class="contenedor_busqueda">
                        </div>
                        <div class="botones">
                        </div>
                    </div>
                    <div class="contenedor_panel">
                        <div class="cards">
                            <div class="card" style="border-bottom-color: #007bff;">
                                <h3>GANANCIAS (MENSUALES)</h3>
                                <h1>$40,000</h1>
                            </div>
                            <div class="card" style="border-bottom-color: #28a745;">
                                <h3>GANANCIAS (ANUALES)</h3>
                                <h1>$215,000</h1>
                            </div>
                            <div class="card" style="border-bottom-color: #17a2b8;">
                                <h3>VENTAS (MENSUALES)</h3>
                                <h1>525</h1>
                            </div>
                            <div class="card" style="border-bottom-color: #ffc107;">
                                <h3>DEVOLUCIONES REALIZADAS</h3>
                                <h1>18</h1>
                            </div>
                        </div>
                        <div class="graficos">
                            <div class="chart-container">
                                <h4>Desglose De Ventas Anuales</h4>
                                <canvas id="lineChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <script src="js/main.js"></script>
    <script src="js/principal.js"></script>
</body>

</html>