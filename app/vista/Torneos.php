<!DOCTYPE html>
<html lang="es">

<head>
    <?php include('complementos/head.php'); ?>
    <title>Torneos</title>
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
                            <h2 class="titulo_pagina" id="titulo">Torneos</h2>
                        </div>
                        <div class="contenedor_busqueda">
                            <input type="text" placeholder="Buscar torneo por nombre..." autocomplete="off" id="busqueda">
                            <i class="fi fi-br-search icon_input"></i>
                        </div>
                        <div class="botones">
                            <button class="btn btn_azul" id="incluir">Nuevo Torneo</button>
                            <button class="btn btn_verde" id="generar">Generar Reporte</button>
                        </div>
                    </div>
                    <div class="contenedor_resultados">
                        <div id="resultadoconsulta" class="resultadoconsulta">
                            </div>
                    </div>
                    <?php include('complementos/botonera.php'); ?>
                </div>
            </div>
        </div>
    </section>

    <section class="contenedor_modal" id="contenedor_modal">
        <div class="modal modal_grande ocultar" id="modal">
            <div class="cabecera_modal">
                <h2 class="titulo_modal" id="titulo_modal"></h2>
                <a type="button" class="cerrar_modal" id="cerrar_modal">&times;</a>
            </div>
            <div class="contenido_modal">
                <form id="f" autocomplete="off">
                    <input type="hidden" id="codigo_torneo" name="codigo_torneo">
                    
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="nombre" name="nombre">
                                <label for="nombre" class="titulo_formulario">Nombre del Torneo</label>
                                <span class="mensaje" id="nombre_spam"></span>
                            </div>
                        </div>
                        <div class="colum">
                            <div class="caja_formulario">
                                <select name="estatus" id="estatus" class="formulario select">
                                    <option value="" disabled selected>Seleccione...</option>
                                    <option value="1">Activo</option>
                                    <option value="2">Finalizado</option>
                                </select>
                                <label for="estatus" class="titulo_formulario">Estatus</label>
                                <span class="mensaje" id="estatus_spam"></span>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="date" class="formulario" id="fecha_inicio" name="fecha_inicio">
                                <label for="fecha_inicio" class="titulo_formulario">Fecha de Inicio</label>
                                <span class="mensaje" id="fecha_inicio_spam"></span>
                            </div>
                        </div>
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="date" class="formulario" id="fecha_fin" name="fecha_fin">
                                <label for="fecha_fin" class="titulo_formulario">Fecha de Fin</label>
                                <span class="mensaje" id="fecha_fin_spam"></span>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="colum" style="width: 100%;">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="ubicacion" name="ubicacion" placeholder="Ej: Cancha Múltiple del Este, Barquisimeto">
                                <label for="ubicacion" class="titulo_formulario">Ubicación</label>
                                <span class="mensaje" id="ubicacion_spam"></span>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="colum">
                            <button type="button" class="btn btn_azul" id="proceso"></button>
                            <button type="button" class="btn btn_verde" id="limpiar">Limpiar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
    
    <script src="js/main.js"></script>
    <script src="js/torneos.js"></script>
    <?php include('complementos/mensajeError.php'); ?>
</body>

</html>