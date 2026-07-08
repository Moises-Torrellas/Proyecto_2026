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
                                    <button class="btn btn_azul btn-abrir-reporte" data-tipo="atletas">Nuevo Reporte</button>
                                </div>
                            </div>
                            <div class="card card-reportes" style="border-bottom-color: #FF4040;">
                                <h3>Efectividad de Recaudación y Morosidad</h3>
                                <div class="info_card">
                                    <button class="btn btn_azul btn-abrir-reporte" data-tipo="recaudacion">Nuevo Reporte</button>
                                </div>
                            </div>
                            <div class="card card-reportes" style="border-bottom-color: #28a745;">
                                <h3>Flujo y Estado de Implementos Asignados</h3>
                                <div class="info_card">
                                    <button class="btn btn_azul btn-abrir-reporte" data-tipo="inventario">Nuevo Reporte</button>
                                </div>
                            </div>
                            <div class="card card-reportes" style="border-bottom-color: #ffc107;">
                                <h3>Rendimiento Ofensivo por Atletas</h3>
                                <div class="info_card">
                                    <button class="btn btn_azul btn-abrir-reporte" data-tipo="rendimiento">Nuevo Reporte</button>
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
                    <div class="row row-g" id="contenedor_canvas">
                        <canvas id="barChart"></canvas>
                    </div>
                    <div id="grupo_filtros_atletas">
                        <div class="row">
                            <div class="colum">
                                <div class="caja_formulario">
                                    <select name="filtro_categoria" id="filtro_categoria" class="formulario select">
                                    </select>
                                    <label for="filtro_categoria" class="titulo_formulario">Categoría</label>
                                </div>
                            </div>
                            <div class="colum">
                                <div class="caja_formulario">
                                    <select name="filtro_genero" id="filtro_genero" class="formulario select">
                                        <option value="todos" selected>Todos los géneros</option>
                                        <option value="H">Masculino (Hombre)</option>
                                        <option value="M">Femenino (Mujer)</option>
                                    </select>
                                    <label for="filtro_genero" class="titulo_formulario">Género</label>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="colum">
                                <div class="caja_formulario">
                                    <label for="filtro_retirados" class="titulo_formulario">Incluir Atletas Retirados</label>
                                    <label class="checkbox-container">
                                        <input type="checkbox" id="filtro_retirados" name="filtro_retirados" class="checkbox" value="1" checked>
                                        <span class="custom-checkbox"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="grupo_filtros_recaudacion" style="display: none;">
                        <div class="row">
                            <div class="colum">
                                <div class="caja_formulario">
                                    <select name="filtro_moneda" id="filtro_moneda" class="formulario select">
                                    </select>
                                    <label for="filtro_moneda" class="titulo_formulario">Moneda</label>
                                </div>
                            </div>
                            <div class="colum">
                                <div class="caja_formulario">
                                    <select name="filtro_concepto" id="filtro_concepto" class="formulario select">
                                    </select>
                                    <label for="filtro_concepto" class="titulo_formulario">Concepto de Pago</label>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="colum">
                                <div class="caja_formulario">
                                    <input type="date" name="filtro_desde" id="filtro_desde" class="formulario input">
                                    <label for="filtro_desde" class="titulo_formulario">Desde</label>
                                </div>
                            </div>
                            <div class="colum">
                                <div class="caja_formulario">
                                    <input type="date" name="filtro_hasta" id="filtro_hasta" class="formulario input">
                                    <label for="filtro_hasta" class="titulo_formulario">Hasta</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="grupo_filtros_inventario" style="display: none;">
                        <div class="row">
                            <div class="colum">
                                <div class="caja_formulario">
                                    <select name="filtro_cat_inventario" id="filtro_cat_inventario" class="formulario select">
                                    </select>
                                    <label for="filtro_cat_inventario" class="titulo_formulario">Categoría del Catálogo</label>
                                </div>
                            </div>
                            <div class="colum">
                                <div class="caja_formulario">
                                    <select name="filtro_estado_fisico" id="filtro_estado_fisico" class="formulario select">
                                        <option value="todos" selected>Todos los Estados</option>
                                        <option value="1" selected>Buen Estado</option>
                                        <option value="2">Desgaste Medio</option>
                                        <option value="3">Mal Estado</option>
                                    </select>
                                    <label for="filtro_estado_fisico" class="titulo_formulario">Estado Físico (Devolución)</label>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="colum">
                                <div class="caja_formulario">
                                    <input type="date" name="filtro_inv_desde" id="filtro_inv_desde" class="formulario input">
                                    <label for="filtro_inv_desde" class="titulo_formulario">Asignado Desde</label>
                                </div>
                            </div>
                            <div class="colum">
                                <div class="caja_formulario">
                                    <input type="date" name="filtro_inv_hasta" id="filtro_inv_hasta" class="formulario input">
                                    <label for="filtro_inv_hasta" class="titulo_formulario">Asignado Hasta</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="grupo_filtros_rendimiento" style="display: none;">
                        <div class="row">
                            <div class="colum">
                                <div class="caja_formulario">
                                    <select name="filtro_atleta" id="filtro_atleta" class="formulario select">
                                    </select>
                                    <label for="filtro_atleta" class="titulo_formulario">Atleta</label>
                                </div>
                            </div>
                            <div class="colum">
                                <div class="caja_formulario">
                                    <select name="filtro_temporada" id="filtro_temporada" class="formulario select">
                                        <option value="todas" selected>Todos los Torneos</option>
                                    </select>
                                    <label for="filtro_temporada" class="titulo_formulario">Torneo</label>
                                </div>
                            </div>
                        </div>
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