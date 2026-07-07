<?php if (isset($solo_lista) && $solo_lista === true) :
    if (empty($registro)) : ?>
        <div class="listado_vacio">
            <p>No se encontraron torneos registrados</p>
        </div>
        <?php else :
        foreach ($registro as $dato) : ?>
            <div class="listado_contenedor_grupal">
                <div class="listado_item" onclick="toggleDetalles(this)">
                    <div class="listado_col_datos">
                        <div class="listado_dato_grupo">
                            <small>Torneo</small>
                            <span class="listado_resaltado"><?= htmlspecialchars($dato['nombre']) ?></span>
                        </div>
                        <div class="listado_dato_grupo">
                            <small>Fecha Inicio</small>
                            <span><?= htmlspecialchars($dato['fecha_inicio']) ?></span>
                        </div>
                        <div class="listado_dato_grupo">
                            <small>Fecha Fin</small>
                            <span><?= htmlspecialchars($dato['fecha_fin']) ?></span>
                        </div>
                        <div class="listado_dato_grupo">
                            <small>Ubicación</small>
                            <span><?= htmlspecialchars($dato['ubicacion']) ?></span>
                        </div>
                        <div class="listado_dato_grupo">
                            <small>Estatus</small>
                            <?php if ($dato['estatus'] == 1) : ?>
                                <span class="estatus_v">Activo</span>
                            <?php else : ?>
                                <span class="estatus_r">Finalizado</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="listado_col_acciones">
                        <div onclick="event.stopPropagation();" style="display:flex; gap:5px;">
                            <?php if (!empty($permisos['modificar_torneo'])) : ?>
                                <button id="cbt_v" class="btn_t cbt_v" onclick="buscar(<?= $dato['codigo_torneo'] ?>)" title="Modificar" data-tippy-content="Modificar"><i class="fi fi-sr-pencil"></i></button>
                            <?php endif; ?>
                            <?php if (!empty($permisos['eliminar_torneo'])) : ?>
                                <button id="cbt_r" class="btn_t cbt_r" onclick="eliminar(<?= $dato['codigo_torneo'] ?>)" title="Eliminar" data-tippy-content="Eliminar"><i class="fi fi-sr-trash-xmark"></i></button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    <?php exit(); ?>
<?php endif; ?>
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
                            <?php if (!empty($permisos['registrar_torneo'])) : ?>
                                <button class="btn btn_azul" id="incluir">Nuevo Torneo</button>
                            <?php endif; ?>
                            <?php if (!empty($permisos['generar_torneos'])) : ?>
                                <button class="btn btn_verde" id="generar">Generar Reporte</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="contenedor_resultados">
                        <div id="resultadoconsulta" class="resultadoconsulta">
                            <?php if (empty($registro)) : ?>
                                <div class="listado_vacio">
                                    <p>No se encontraron torneos registrados</p>
                                </div>
                                <?php else :
                                foreach ($registro as $dato) : ?>
                                    <div class="listado_contenedor_grupal">
                                        <div class="listado_item" onclick="toggleDetalles(this)">
                                            <div class="listado_col_datos">
                                                <div class="listado_dato_grupo">
                                                    <small>Torneo</small>
                                                    <span class="listado_resaltado"><?= htmlspecialchars($dato['nombre']) ?></span>
                                                </div>
                                                <div class="listado_dato_grupo">
                                                    <small>Fecha Inicio</small>
                                                    <span><?= htmlspecialchars($dato['fecha_inicio']) ?></span>
                                                </div>
                                                <div class="listado_dato_grupo">
                                                    <small>Fecha Fin</small>
                                                    <span><?= htmlspecialchars($dato['fecha_fin']) ?></span>
                                                </div>
                                                <div class="listado_dato_grupo">
                                                    <small>Ubicación</small>
                                                    <span><?= htmlspecialchars($dato['ubicacion']) ?></span>
                                                </div>
                                                <div class="listado_dato_grupo">
                                                    <small>Estatus</small>
                                                    <?php if ($dato['estatus'] == 1) : ?>
                                                        <span class="estatus_v">Activo</span>
                                                    <?php else : ?>
                                                        <span class="estatus_r">Finalizado</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <div class="listado_col_acciones">
                                                <div onclick="event.stopPropagation();" style="display:flex; gap:5px;">
                                                    <?php if (!empty($permisos['modificar_torneo'])) : ?>
                                                        <button id="cbt_v" class="btn_t cbt_v" onclick="buscar(<?= $dato['codigo_torneo'] ?>)" title="Modificar" data-tippy-content="Modificar"><i class="fi fi-sr-pencil"></i></button>
                                                    <?php endif; ?>
                                                    <?php if (!empty($permisos['eliminar_torneo'])) : ?>
                                                        <button id="cbt_r" class="btn_t cbt_r" onclick="eliminar(<?= $dato['codigo_torneo'] ?>)" title="Eliminar" data-tippy-content="Eliminar"><i class="fi fi-sr-trash-xmark"></i></button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
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