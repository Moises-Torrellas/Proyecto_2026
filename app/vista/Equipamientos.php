<!DOCTYPE html>
<html lang="es">

<head>
    <?php include('complementos/head.php'); ?>
    <title>Equipamientos</title>
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
                            <h2 class="titulo_pagina" id="titulo">Equipamientos</h2>
                        </div>
                        <div class="botones">
                            <button class="btn btn_azul" id="btn_nuevo">
                                <i class="fi fi-sr-boxes"></i> Registrar Equipamiento
                            </button>
                        </div>
                    </div>
                    
                    <div class="contenedor_resultados">
                        <div id="resultadoconsulta" class="resultadoconsulta">
                            </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="contenedor_modal" id="contenedor_modal">
        <div class="modal modal_mediano ocultar" id="modal">
            <div class="cabecera_modal">
                <h2 class="titulo_modal" id="titulo_modal">Registrar Equipamiento</h2>
                <a type="button" class="cerrar_modal" id="cerrar_modal">&times;</a>
            </div>
            <div class="contenido_modal">
                <form id="f" autocomplete="off">
                    <input type="hidden" id="id_equipamiento" name="id_equipamiento">
                    
                    <div class="row">
                        <div class="colum" style="width: 100%;">
                            <label for="id_catalogo">Artículo del Catálogo</label>
                            <div class="caja_formulario">
                                <select class="formulario select2" id="id_catalogo" name="id_catalogo" required>
                                    <option value="">Seleccione un artículo...</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row" style="margin-top: 15px;">
                        <div class="colum" style="width: 100%;">
                            <label for="id_estado">Condición / Estado Físico</label>
                            <div class="caja_formulario">
                                <select class="formulario" id="id_estado" name="id_estado" required>
                                    <option value="">Seleccione un estado...</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row" style="margin-top: 25px;">
                        <div class="colum">
                            <button type="button" class="btn btn_azul" id="btn_guardar" data-accion="incluir">Guardar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
    
    <script src="js/main.js"></script>
    <script src="js/equipamientos.js"></script>
</body>

</html>