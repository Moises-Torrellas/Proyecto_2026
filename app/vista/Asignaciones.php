<!DOCTYPE html>
<html lang="es">
<head>
    <?php include('complementos/head.php'); ?>
    <title>Gestión de Asignaciones</title>
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
                            <h2 class="titulo_pagina">Asignaciones</h2>
                        </div>
                        <div class="botones">
                            <button class="btn btn_azul" id="btn_nuevo">
                                <i class="fi fi-sr-add-document"></i> Nueva Asignación
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
                <h2 class="titulo_modal" id="titulo_modal">Registrar Asignación</h2>
                <a type="button" class="cerrar_modal" id="cerrar_modal">&times;</a>
            </div>
            <div class="contenido_modal">
                <form id="f" autocomplete="off">
                    <input type="hidden" id="id_asignacion" name="id_asignacion">
                    
                    <div class="row">
                        <div class="colum" style="width: 100%;">
                            <label>Atleta</label>
                            <div class="caja_formulario">
                                <select class="formulario select2" id="id_atleta" name="id_atleta" required>
                                    <option value="">Seleccione un atleta...</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row" style="margin-top: 15px;">
                        <div class="colum" style="width: 100%;">
                            <label>Equipamiento (Almacén Libre)</label>
                            <div class="caja_formulario">
                                <select class="formulario select2" id="id_equipamiento" name="id_equipamiento" required>
                                    <option value="">Seleccione una pieza disponible...</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row" style="margin-top: 15px;">
                        <div class="colum" style="width: 100%;">
                            <label>Fecha de Asignación</label>
                            <div class="caja_formulario">
                                <input type="date" class="formulario" id="fecha_asignacion" name="fecha_asignacion" required>
                            </div>
                        </div>
                    </div>
                    <div class="row" style="margin-top: 25px;">
                        <div class="colum">
                            <button type="button" class="btn btn_azul" id="btn_guardar" data-accion="incluir">Confirmar Préstamo</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
    
    <script src="js/main.js"></script>
    <script src="js/asignaciones.js"></script>
</body>
</html>