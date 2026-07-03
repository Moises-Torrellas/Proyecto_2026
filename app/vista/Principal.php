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
                            <h2 class="titulo_pagina" id="titulo">Panel de Control</h2>
                        </div>
                        <div class="contenedor_busqueda">
                        </div>
                        <div class="botones">
                        </div>
                    </div>
                    <div class="contenedor_panel">
                        <div class="cards">
                            <div class="card" style="border-bottom-color: #007bff;">
                                <h3>ATLETAS ACTIVOS</h3>
                                <div class="info_card">
                                    <i class="icon_card fi-br-hockey-stick-puck" style="color: #007bff; background-color: #007bff25;"></i>
                                    <h1><?= htmlspecialchars($registro['activos'] ?? 0) ?></h1>
                                </div>
                            </div>
                            <div class="card" style="border-bottom-color: #FF4040;">
                                <h3>CARGOS PENDIENTES</h3>
                                <div class="info_card">
                                    <i class="icon_card fi fi-br-receipt" style="color: #FF4040; background-color: #FF404025;"></i>
                                    <h1><?= htmlspecialchars($registro['cargos'] ?? 0) ?></h1>
                                </div>
                            </div>
                            <div class="card" style="border-bottom-color: #28a745;">
                                <h3>PARTICIPACIONES EN TORNEOS</h3>
                                <div class="info_card">
                                    <i class="icon_card fi fi-br-trophy" style="color: #28a745; background-color: #28a74525;"></i>
                                    <h1><?= htmlspecialchars($registro['torneos'] ?? 0) ?></h1>
                                </div>
                            </div>
                            <div class="card" style="border-bottom-color: #ffc107;">
                                <h3>EQUIPAMIENTOS ASIGNADOS</h3>
                                <div class="info_card">
                                    <i class="icon_card fi fi-br-shield-plus" style="color: #ffc107; background-color: #ffc10725;"></i>
                                    <h1><?= htmlspecialchars($registro['asignaciones'] ?? 0) ?></h1>
                                </div>
                            </div>
                        </div>
                        <div class="graficos">
                            <!-- Bloque Izquierdo: Calendario (Ocupa 3 columnas) -->
                            <div class="chart-container calendario-seccion">
                                <h4>Torneos</h4>
                                <div id="calendario_eventos"></div>
                            </div>

                            <!-- Bloque Derecho: Contenedor para los dos gráficos (Ocupa 1 columna) -->
                            <div class="columna-derecha-stats">

                                <!-- Gráfico 1: Atletas Solventes -->
                                <div class="chart-container grafico-lateral">
                                    <h4>Atletas Solventes</h4>
                                    <div style="position: relative; height: 180px;">
                                        <canvas id="gaugeChart"></canvas>
                                        <div style="position: absolute; top: 60%; left: 50%; transform: translate(-50%, -50%); text-align: center;">
                                            <h1 style="margin: 0; font-size: 24px;">75.55%</h1>
                                            <span style="color: #28a745; font-weight: bold; font-size: 14px;">+10%</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Gráfico 2: Resumen de Ingresos -->
                                <div class="chart-container card_grafico">
                                    <h4>Distribución de Atletas por Categoría</h4>
                                    <div style="position: relative; flex-grow: 1;">
                                        <canvas id="doughnutChart"></canvas>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <script src="js/main.js"></script>
    <script src="js/principal.js"></script>
    <?php if (isset($_SESSION['alerta'])) : ?>
        <div id="alerta-backend"
            data-icono="<?= $_SESSION['alerta']['icono'] ?>"
            data-titulo="<?= $_SESSION['alerta']['titulo'] ?>"
            data-mensaje="<?= $_SESSION['alerta']['mensaje'] ?>">
        </div>
        <?php unset($_SESSION['alerta']); ?>
    <?php endif; ?>
</body>

</html>