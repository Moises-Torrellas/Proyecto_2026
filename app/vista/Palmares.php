<!DOCTYPE html>
<html lang="es">

<head>
    <?php include('complementos/head.php'); ?>
    <title>Palmarés</title>
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
                            <h2 class="titulo_pagina" id="titulo">Palmarés</h2>
                        </div>
                        
                        <!-- Buscador Global Restaurado -->
                        <div class="contenedor_busqueda">
                            <input type="text" placeholder="Buscar..." autocomplete="off" id="busqueda">
                            <i class="fi fi-br-search icon_input"></i>
                        </div>

                        <div class="botones" style="margin-left: auto;">
                            <button class="btn btn_azul" id="incluir">Nuevo Palmarés</button>
                            <button class="btn btn_verde" id="generar">Generar Reporte</button>
                        </div>
                    </div>
                    
                    <!-- PESTAÑAS (TABS) TIPO ARCHIVERO -->
                    <div class="pestanas-contenedor">
                        <!-- Cabecera de pestañas -->
                        <div class="pestanas-header">
                            <button class="pestana-btn activa" data-target="#tab_individual">
                                <i class="fi fi-sr-user" style="margin-right: 5px;"></i> Individual
                            </button>
                            <button class="pestana-btn" data-target="#tab_grupal">
                                <i class="fi fi-sr-users" style="margin-right: 5px;"></i> Grupal
                            </button>
                        </div>

                        <!-- Cuerpo de la carpeta -->
                        <div class="pestanas-body">
                            
                            <!-- CONTENIDO: INDIVIDUAL -->
                            <div class="pestana-content activa" id="tab_individual">
                                <div class="contenedor_resultados">
                                    <div id="resultado_individual" class="resultadoconsulta">
                                        <div class="listado_vacio"><p>No se encontraron registros</p></div>
                                    </div>
                                </div>
                                
                                <div class="contenedor_botonera">
                                    <div id="c_rows_ind">
                                        <select id="rowsPerPage_individual" class="cantidad_paginacion">
                                            <option value="5" selected>5</option>
                                            <option value="10">10</option>
                                            <option value="20">20</option>
                                        </select>
                                    </div>
                                    <div class="botonera" id="botonera_individual"></div>
                                    <span class="cantidad_registros">Total: <span id="cantidadRegistros_individual">0</span></span>
                                </div>
                            </div>

                            <!-- CONTENIDO: GRUPAL -->
                            <div class="pestana-content" id="tab_grupal">
                                <div class="contenedor_resultados">
                                    <div id="resultado_grupal" class="resultadoconsulta">
                                        <div class="listado_vacio"><p>No se encontraron registros</p></div>
                                    </div>
                                </div>
                                
                                <div class="contenedor_botonera">
                                    <div id="c_rows_grup">
                                        <select id="rowsPerPage_grupal" class="cantidad_paginacion">
                                            <option value="5" selected>5</option>
                                            <option value="10">10</option>
                                            <option value="20">20</option>
                                        </select>
                                    </div>
                                    <div class="botonera" id="botonera_grupal"></div>
                                    <span class="cantidad_registros">Total: <span id="cantidadRegistros_grupal">0</span></span>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- UNICO MODAL DE REGISTRO Y REPORTES -->
    <section class="contenedor_modal" id="contenedor_modal">
        <div class="modal ocultar" id="modal">
            <div class="cabecera_modal">
                <h2 class="titulo_modal" id="titulo_modal">Registrar Palmarés</h2>
                <a type="button" class="cerrar_modal" id="cerrar_modal">&times;</a>
            </div>
            <div class="contenido_modal">
                <form id="f" autocomplete="off">
                    <input type="hidden" id="id" name="id">
                    <input type="hidden" id="tipo_palmares" name="tipo_palmares">

                    <!-- SECCIÓN: REGISTRO PALMARÉS GRUPAL -->
                    <div id="seccion_grupal" style="display: none;">
                        <div class="row">
                            <div class="colum">
                                <div class="caja_formulario">
                                    <select name="torneo_grupal" id="torneo_grupal" class="formulario select">
                                        <option value="" disabled selected>Selecciona un Torneo</option>
                                    </select>
                                    <label for="torneo_grupal" class="titulo_formulario">Torneo</label>
                                    <span class="mensaje" id="torneo_grupal_spam"></span>
                                </div>
                            </div>
                            <div class="colum">
                                <div class="caja_formulario">
                                    <select name="equipo" id="equipo" class="formulario select">
                                        <option value="" disabled selected>Selecciona un Equipo</option>
                                    </select>
                                    <label for="equipo" class="titulo_formulario">Equipo</label>
                                    <span class="mensaje" id="equipo_spam"></span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="colum">
                                <div class="caja_formulario">
                                    <select name="premio_grupal" id="premio_grupal" class="formulario select">
                                        <option value="" disabled selected>Selecciona un Premio Grupal</option>
                                    </select>
                                    <label for="premio_grupal" class="titulo_formulario">Premio Obtenido</label>
                                    <span class="mensaje" id="premio_grupal_spam"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECCIÓN: REGISTRO PALMARÉS INDIVIDUAL -->
                    <div id="seccion_individual" style="display: none;">
                        <div class="row">
                            <div class="colum">
                                <div class="caja_formulario">
                                    <select name="torneo_individual" id="torneo_individual" class="formulario select">
                                        <option value="" disabled selected>Selecciona un Torneo</option>
                                    </select>
                                    <label for="torneo_individual" class="titulo_formulario">Torneo</label>
                                    <span class="mensaje" id="torneo_individual_spam"></span>
                                </div>
                            </div>
                            <div class="colum">
                                <div class="caja_formulario">
                                    <select name="atleta" id="atleta" class="formulario select">
                                        <option value="" disabled selected>Selecciona un Atleta</option>
                                    </select>
                                    <label for="atleta" class="titulo_formulario">Atleta</label>
                                    <span class="mensaje" id="atleta_spam"></span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="colum">
                                <div class="caja_formulario">
                                    <select name="premio_individual" id="premio_individual" class="formulario select">
                                        <option value="" disabled selected>Selecciona un Premio Individual</option>
                                    </select>
                                    <label for="premio_individual" class="titulo_formulario">Premio Obtenido</label>
                                    <span class="mensaje" id="premio_individual_spam"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECCIÓN: FILTROS DE REPORTE -->
                    <div id="seccion_reportes" style="display: none;">
                        <div class="row">
                            <div class="colum" id="col_filtro_torneo">
                                <div class="caja_formulario">
                                    <select name="filtro_torneo" id="filtro_torneo" class="formulario select">
                                        <option value="T" selected>Todos los Torneos</option>
                                    </select>
                                    <label for="filtro_torneo" class="titulo_formulario">Filtrar por Torneo</label>
                                </div>
                            </div>
                            <div class="colum" id="col_filtro_equipo" style="display:none;">
                                <div class="caja_formulario">
                                    <select name="filtro_equipo" id="filtro_equipo" class="formulario select">
                                        <option value="T" selected>Todos los Equipos</option>
                                    </select>
                                    <label for="filtro_equipo" class="titulo_formulario">Filtrar por Equipo</label>
                                </div>
                            </div>
                            <div class="colum" id="col_filtro_atleta" style="display:none;">
                                <div class="caja_formulario">
                                    <select name="filtro_atleta" id="filtro_atleta" class="formulario select">
                                        <option value="T" selected>Todos los Atletas</option>
                                    </select>
                                    <label for="filtro_atleta" class="titulo_formulario">Filtrar por Atleta</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="colum">
                            <button type="button" class="btn btn_azul" id="proceso"></button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
    
    <script src="js/main.js"></script>
    <script src="js/palmares.js"></script>
</body>

</html>