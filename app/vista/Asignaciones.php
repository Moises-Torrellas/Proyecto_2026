<?php if (!isset($solo_lista)) { ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include('complementos/head.php'); ?>
    <title>Asignaciones</title>
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
                        
                        <div class="contenedor_busqueda">
                            <input type="text" placeholder="Buscar..." autocomplete="off" id="busqueda">
                            <i class="fi fi-br-search icon_input"></i>
                        </div>

                        <div class="botones">
                            <button class="btn btn_azul" id="btn_nuevo">Nueva Asignación</button>
                            <button class="btn btn_verde" id="generar">Generar Reporte</button>
                        </div>
                    </div>
                    <div class="contenedor_resultados">
                        <div id="resultadoconsulta" class="resultadoconsulta">
<?php } ?>

                            <?php if (empty($registro)) { ?>
                                <div class="listado_vacio"><p>No hay asignaciones activas.</p></div>
                            <?php } else { 
                                foreach ($registro as $dato) { ?>
                                    <div class="listado_contenedor_grupal">
                                        <div class="listado_item">
                                            <div class="listado_col_datos">
                                                <div class="listado_dato_grupo" style="width: 15%;">
                                                    <small>Fecha</small>
                                                    <span style="font-weight: bold;"><?= $dato['fecha_vista'] ?></span>
                                                </div>
                                                <div class="listado_dato_grupo" style="width: 35%;">
                                                    <small>Atleta</small>
                                                    <span style="font-weight: bold; color: var(--texto-principal);"><?= $dato['atleta'] ?></span>
                                                    <small>CI: <?= $dato['doc_identidad'] ?></small>
                                                </div>
                                                <div class="listado_dato_grupo" style="width: 30%;">
                                                    <small>Equipo Asignado</small>
                                                    <span><?= $dato['articulo'] ?></span>
                                                    <small style="color: #6c757d;">(Pza ID: #<?= $dato['id_equipamiento'] ?>)</small>
                                                </div>
                                                <div class="listado_dato_grupo">
                                                    <small>Estado</small>
                                                    <span style="color:#ffc107; font-weight:bold;">EN USO</span>
                                                </div>
                                            </div>
                                            <div class="listado_col_acciones">
                                                <div style="display:flex; gap:5px;">
                                                    
                                                    <?php if (!empty($permisos['modificar'])) { ?>
                                                        <button class="btn_t cbt_v" onclick="editar(<?= $dato['id_asignacion'] ?>, <?= $dato['id_atleta'] ?>, <?= $dato['id_equipamiento'] ?>, '<?= $dato['fecha_real'] ?>')" data-tippy-content="Modificar">
                                                            <i class="fi fi-sr-pencil"></i>
                                                        </button>
                                                    <?php } ?>
                                                    
                                                    <?php if (!empty($permisos['eliminar'])) { ?>
                                                        <button class="btn_t cbt_r" onclick="anular(<?= $dato['id_asignacion'] ?>, <?= $dato['id_equipamiento'] ?>)" data-tippy-content="Anular Asignación">
                                                            <i class="fi fi-sr-trash"></i>
                                                        </button>
                                                    <?php } ?>

                                                </div>
                                            </div>
                                        </div>
                                    </div>
                            <?php } } ?>
                            <?php if (!isset($solo_lista)) { ?>
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
                            <div class="caja_formulario">
                                <select class="formulario select" id="id_atleta" name="id_atleta">
                                    <option value="" selected disabled>Seleccione un atleta...</option>
                                </select>
                                <label for="id_atleta" class="titulo_formulario">Atleta</label>
                                <span class="mensaje" id="id_atleta_span"></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="colum" style="width: 100%;">
                            <div class="caja_formulario">
                                <select class="formulario select" id="id_equipamiento" name="id_equipamiento">
                                    <option value="" selected disabled>Seleccione una pieza...</option>
                                </select>
                                <label for="id_equipamiento" class="titulo_formulario">Equipamiento (Almacén Libre)</label>
                                <span class="mensaje" id="id_equipamiento_span"></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="colum" style="width: 100%;">
                            <div class="caja_formulario">
                                <input type="date" class="formulario" id="fecha_asignacion" name="fecha_asignacion">
                                <label for="fecha_asignacion" class="titulo_formulario">Fecha de Asignación</label>
                                <span class="mensaje" id="fecha_asignacion_span"></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
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
<?php } ?>