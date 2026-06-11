<!DOCTYPE html>
<html lang="en">

<head>
    <?php include('complementos/head.php'); ?>
    <title>Reportes</title>
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
                            <h2 class="titulo_pagina" id="titulo">Reportes Estadisticos</h2>
                        </div>
                        <div class="contenedor_busqueda">
                        </div>
                        <div class="botones">
                        </div>
                    </div>
                    <div class="contenedor_panel">
                        <div class="cards cards-reportes">
                            <div class="card card-reportes" style="border-bottom-color: #007bff;">
                                <h3>Atletas por Categorias</h3>
                                <div class="info_card">
                                    <button class="btn btn_azul" id="incluir">Nuevo Reporte</button>
                                </div>
                            </div>
                            <div class="card card-reportes" style="border-bottom-color: #FF4040;">
                                <h3>Ingresos Mensuales</h3>
                                <div class="info_card">
                                    <button class="btn btn_azul">Nuevo Reporte</button>
                                </div>
                            </div>
                            <div class="card card-reportes" style="border-bottom-color: #28a745;">
                                <h3>Deudas</h3>
                                <div class="info_card">
                                    <button class="btn btn_azul">Nuevo Reporte</button>
                                </div>
                            </div>
                            <div class="card card-reportes" style="border-bottom-color: #ffc107;">
                                <h3>Pagos por Metodos</h3>
                                <div class="info_card">
                                    <button class="btn btn_azul">Nuevo Reporte</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="contenedor_modal" id="contenedor_modal">
        <div class="modal modal_grafico ocultar" id="modal">
            <div class="cabecera_modal">
                <h2 class="titulo_modal" id="titulo_modal"></h2>
                <a type="button" class="cerrar_modal" id="cerrar_modal">&times;</a>
            </div>
            <div class="contenido_modal">
                <form id="f" autocomplete="off">
                    <div class="row row-g">
                        <canvas id="barChart"></canvas>
                    </div>
                    <div class="row">
                        <div class="colum">
                            <button type="button" class="btn btn_azul" id="proceso"></button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
    <script src="js/main.js"></script>
    <script src="js/reportes.js"></script>
</body>

</html>
