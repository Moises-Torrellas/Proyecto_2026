<?php if (!isset($solo_lista)) { ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include('complementos/head.php'); ?>
    <title>Devoluciones</title>
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
                            <h2 class="titulo_pagina">Devoluciones</h2>
                        </div>
                        <div class="botones">
                            <?php if(!empty($permisos['registrar'])): ?>
                                <button class="btn btn_azul" id="btn_nuevo">
                                    <i class="fi fi-sr-add-document"></i> Nueva Devolución
                                </button>
                            <?php endif; ?>
                            
                            <?php if (!empty($permisos['reporte'])): ?>
                                <button class="btn btn_verde" id="generar">
                                    <i class="fi fi-sr-document"></i> Generar Reporte
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="contenedor_resultados">
                        <div id="resultadoconsulta" class="resultadoconsulta">
<?php } ?>

                            <?php if (empty($registro)) { ?>
                                <div class="listado_vacio"><p>No hay devoluciones activas.</p></div>
                            <?php } else { 
                                foreach ($registro as $dato) { ?>
                                    <div class="listado_contenedor_grupal">
                                        <div class="listado_item">
                                            <div class="listado_col_datos">
                                                <div class="listado_dato_grupo" style="width: 15%;">
                                                    <small>Fecha Devolución</small>
                                                    <span style="font-weight: bold;"><?= $dato['fecha_devolucion'] ?></span>
                                                </div>
                                                <div class="listado_dato_grupo" style="width: 35%;">
                                                    <small>Asignaciones</small>
                                                    <span style="font-weight: bold; color: var(--texto-principal);"><?= $dato['asignaciones'] ?></span>
                                                    <small>ID Asig: <?= $dato['id_asignacion'] ?></small>
                                                </div>
                                                <div class="listado_dato_grupo" style="width: 30%;">
                                                    <small>Estado</small>
                                                    <span><?= $dato['articulo'] ?></span>
                                                    <small style="color: #6c757d;">(Pza ID: #<?= $dato['id_estado'] ?>)</small>
                                                </div>
                                            </div>
                                            <div class="listado_col_acciones">
                                                <div style="display:flex; gap:5px;">
                                                    <?php if (!empty($permisos['modificar'])) { ?>
                                                        <button class="btn_t cbt_v" onclick="editar(<?= $dato['id_devolucion'] ?>, <?= $dato['id_asignacion'] ?>, <?= $dato['id_estado'] ?>, '<?= $dato['fecha_devolucion'] ?>', '<?= $dato['observacion'] ?>')" title="Modificar"><i class="fi fi-sr-edit"></i></button>
                                                    <?php } ?>
                                                    <?php if (!empty($permisos['eliminar'])) { ?>
                                                        <button class="btn_t cbt_r" onclick="anular(<?= $dato['id_devolucion'] ?>)" title="Anular Devolución"><i class="fi fi-sr-ban"></i></button>
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
                <h2 class="titulo_modal" id="titulo_modal">Registrar Devolución</h2>
                <a type="button" class="cerrar_modal" id="cerrar_modal">&times;</a>
            </div>
            <div class="contenido_modal">
                <form id="f" autocomplete="off">
                    <input type="hidden" id="id_devolucion" name="id_devolucion">
                    <div class="row">
                        <div class="colum" style="width: 100%;">
                            <label>Asignaciones</label>
                            <div class="caja_formulario">
                                <select class="formulario select2" id="id_asignacion" name="id_asignacion">
                                    <option value="">Seleccione una Asignacion...</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row" style="margin-top: 15px;">
                        <div class="colum" style="width: 100%;">
                            <label>Estado</label>
                            <div class="caja_formulario">
                                <select class="formulario select2" id="id_estado" name="id_estado">
                                    <option value="">Seleccione la Calidad...</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row" style="margin-top: 15px;">
                        <div class="colum" style="width: 100%;">
                            <label>Fecha Devolución</label>
                            <div class="caja_formulario">
                                <input type="date" class="formulario" id="fecha_devolucion" name="fecha_devolucion">
                            </div>
                        </div>
                    </div>
                    <div class="row" style="margin-top: 15px;">
                        <div class="colum" style="width: 100%;">
                            <label>Observación</label>
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="observacion" name="observacion">
                            </div>
                        </div>
                    </div>
                    <div class="row" style="margin-top: 25px;">
                        <div class="colum">
                            <button type="button" class="btn btn_azul" id="btn_guardar" data-accion="incluir">Registrar Devolución</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
    <script src="js/main.js"></script>
    <script src="js/devoluciones.js"></script>
</body>
</html>
<?php } ?>