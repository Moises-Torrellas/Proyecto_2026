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
                        <div class="perfil_grid reportes_grid">
                            
                            <!-- Tarjeta 1: Atletas -->
                            <div class="card_perfil">
                                <h3><i data-lucide="users"></i> Atletas por Categorías</h3>
                                <div class="canvas_reporte">
                                    <canvas id="chart_atletas"></canvas>
                                </div>
                                <div class="filtros_reporte">
                                    <div class="row">
                                        <div class="colum">
                                            <div class="caja_formulario" style="margin-bottom: 0;">
                                                <select name="filtro_categoria" id="filtro_categoria" class="formulario select">
                                                </select>
                                                <label for="filtro_categoria" class="titulo_formulario">Categoría</label>
                                            </div>
                                        </div>
                                        <div class="colum">
                                            <div class="caja_formulario" style="margin-bottom: 0;">
                                                <select name="filtro_genero" id="filtro_genero" class="formulario select">
                                                    <option value="todos" selected>Todos los géneros</option>
                                                    <option value="H">Masculino (Hombre)</option>
                                                    <option value="M">Femenino (Mujer)</option>
                                                </select>
                                                <label for="filtro_genero" class="titulo_formulario">Género</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row" style="margin-top: 15px;">
                                        <div class="colum">
                                            <div class="caja_formulario" style="margin-bottom: 0;">
                                                <label for="filtro_retirados" class="titulo_formulario">Incluir Atletas Retirados</label>
                                                <label class="checkbox-container">
                                                    <input type="checkbox" id="filtro_retirados" name="filtro_retirados" class="checkbox" value="1" checked>
                                                    <span class="custom-checkbox"></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row" style="margin-top: 20px;">
                                    <div class="colum" style="display: flex; justify-content: flex-end;">
                                        <button type="button" class="btn btn_azul btn-generar" data-tipo="atletas">Generar PDF</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Tarjeta 2: Recaudacion -->
                            <div class="card_perfil">
                                <h3><i data-lucide="dollar-sign"></i> Efectividad de Recaudación y Morosidad</h3>
                                <div class="canvas_reporte">
                                    <canvas id="chart_recaudacion"></canvas>
                                </div>
                                <div class="filtros_reporte">
                                    <div class="row">
                                        <div class="colum">
                                            <div class="caja_formulario" style="margin-bottom: 0;">
                                                <select name="filtro_moneda" id="filtro_moneda" class="formulario select">
                                                </select>
                                                <label for="filtro_moneda" class="titulo_formulario">Moneda</label>
                                            </div>
                                        </div>
                                        <div class="colum">
                                            <div class="caja_formulario" style="margin-bottom: 0;">
                                                <select name="filtro_concepto" id="filtro_concepto" class="formulario select">
                                                </select>
                                                <label for="filtro_concepto" class="titulo_formulario">Concepto de Pago</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row" style="margin-top: 15px;">
                                        <div class="colum">
                                            <div class="caja_formulario" style="margin-bottom: 0;">
                                                <input type="date" name="filtro_desde" id="filtro_desde" class="formulario input" placeholder="dd/mm/yyyy">
                                                <label for="filtro_desde" class="titulo_formulario">Desde</label>
                                                <span class="mensaje" id="filtro_desde_spam"></span>
                                            </div>
                                        </div>
                                        <div class="colum">
                                            <div class="caja_formulario" style="margin-bottom: 0;">
                                                <input type="date" name="filtro_hasta" id="filtro_hasta" class="formulario input" placeholder="dd/mm/yyyy">
                                                <label for="filtro_hasta" class="titulo_formulario">Hasta</label>
                                                <span class="mensaje" id="filtro_hasta_spam"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row" style="margin-top: 20px;">
                                    <div class="colum" style="display: flex; justify-content: flex-end;">
                                        <button type="button" class="btn btn_azul btn-generar" data-tipo="recaudacion">Generar PDF</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Tarjeta 3: Inventario -->
                            <div class="card_perfil">
                                <h3><i data-lucide="box"></i> Flujo y Estado de Implementos Asignados</h3>
                                <div class="canvas_reporte">
                                    <canvas id="chart_inventario"></canvas>
                                </div>
                                <div class="filtros_reporte">
                                    <div class="row">
                                        <div class="colum">
                                            <div class="caja_formulario" style="margin-bottom: 0;">
                                                <select name="filtro_cat_inventario" id="filtro_cat_inventario" class="formulario select">
                                                </select>
                                                <label for="filtro_cat_inventario" class="titulo_formulario">Categoría del Catálogo</label>
                                            </div>
                                        </div>
                                        <div class="colum">
                                            <div class="caja_formulario" style="margin-bottom: 0;">
                                                <select name="filtro_estado_fisico" id="filtro_estado_fisico" class="formulario select">
                                                    <option value="todos" selected>Todos los Estados</option>
                                                    <option value="1">Buen Estado</option>
                                                    <option value="2">Desgaste Medio</option>
                                                    <option value="3">Mal Estado</option>
                                                </select>
                                                <label for="filtro_estado_fisico" class="titulo_formulario">Estado Físico (Devolución)</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row" style="margin-top: 15px;">
                                        <div class="colum">
                                            <div class="caja_formulario" style="margin-bottom: 0;">
                                                <input type="date" name="filtro_inv_desde" id="filtro_inv_desde" class="formulario input" placeholder="dd/mm/yyyy">
                                                <label for="filtro_inv_desde" class="titulo_formulario">Asignado Desde</label>
                                                <span class="mensaje" id="filtro_inv_desde_spam"></span>
                                            </div>
                                        </div>
                                        <div class="colum">
                                            <div class="caja_formulario" style="margin-bottom: 0;">
                                                <input type="date" name="filtro_inv_hasta" id="filtro_inv_hasta" class="formulario input" placeholder="dd/mm/yyyy">
                                                <label for="filtro_inv_hasta" class="titulo_formulario">Asignado Hasta</label>
                                                <span class="mensaje" id="filtro_inv_hasta_spam"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row" style="margin-top: 20px;">
                                    <div class="colum" style="display: flex; justify-content: flex-end;">
                                        <button type="button" class="btn btn_azul btn-generar" data-tipo="inventario">Generar PDF</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Tarjeta 4: Rendimiento -->
                            <div class="card_perfil">
                                <h3><i data-lucide="activity"></i> Rendimiento Ofensivo por Atletas</h3>
                                <div class="canvas_reporte">
                                    <canvas id="chart_rendimiento"></canvas>
                                </div>
                                <div class="filtros_reporte">
                                    <div class="row">
                                        <div class="colum">
                                            <div class="caja_formulario" style="margin-bottom: 0;">
                                                <select name="filtro_atleta" id="filtro_atleta" class="formulario select">
                                                </select>
                                                <label for="filtro_atleta" class="titulo_formulario">Atleta</label>
                                            </div>
                                        </div>
                                        <div class="colum">
                                            <div class="caja_formulario" style="margin-bottom: 0;">
                                                <select name="filtro_temporada" id="filtro_temporada" class="formulario select">
                                                    <option value="todas" selected>Todos los Torneos</option>
                                                </select>
                                                <label for="filtro_temporada" class="titulo_formulario">Torneo</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row" style="margin-top: 20px;">
                                    <div class="colum" style="display: flex; justify-content: flex-end;">
                                        <button type="button" class="btn btn_azul btn-generar" data-tipo="rendimiento">Generar PDF</button>
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
    <script src="js/reportes.js"></script>
</body>

</html>
