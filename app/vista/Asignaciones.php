<?php if (isset($solo_lista) && $solo_lista === true) : ?>
    <?php if (empty($registro)) : ?>
        <div class="listado_vacio">
            <p>No se encontraron registros de asignaciones</p>
        </div>
    <?php else : ?>
        <?php foreach ($registro as $atleta) :
            // 1. Calcular totales para la cabecera y el resumen
            $asignacionesActivas = 0;
            $totalAsignaciones = count($atleta['asignaciones']);

            foreach ($atleta['asignaciones'] as $asig) {
                if ($asig['anulado'] == 0 && $asig['estatus'] == 1) {
                    $asignacionesActivas++;
                }
            }

            // Clase de estado para la tarjeta de resumen (siempre verde)
            $claseEstadoGeneral = 'estado_exito';
        ?>
            <div class="listado_contenedor_grupal">

                <div class="listado_item" onclick="toggleDetalles(this)">
                    <div class="listado_col_principal">
                        <div class="listado_avatar_null">
                            <i class="icon_con" data-lucide="circle-star"></i>
                        </div>
                        <div class="listado_info_base">
                            <span class="listado_titulo"><?= htmlspecialchars($atleta['nombre_completo']) ?></span>
                            <span class="listado_subtitulo">CI: <?= htmlspecialchars($atleta['doc_identidad']) ?></span>
                        </div>
                    </div>

                    <div class="listado_col_datos">
                        <div class="listado_dato_grupo">
                            <small>EQUIPOS EN PRÉSTAMO</small>
                            <span class="estatus_v"><?= $asignacionesActivas ?> Activa(s)</span>
                        </div>
                        <div class="listado_dato_grupo">
                            <small>HISTÓRICO</small>
                            <span><?= $totalAsignaciones ?> Asignación(es)</span>
                        </div>
                    </div>

                    <div class="listado_col_acciones">
                        <i data-lucide="chevron-down" class="icono_flecha_detalle"></i>
                    </div>
                </div>

                <div class="listado_detalle_oculto">
                    <div class="detalle_expandido_container" style="padding: 15px;">

                        <div class="tarjeta_resumen <?= $claseEstadoGeneral ?>">
                            <div class="tarjeta_icono"><i data-lucide="box"></i></div>
                            <div class="tarjeta_texto">
                                <label>RESUMEN DE EQUIPAMIENTO</label>
                                <span class="texto_resaltado">Total Histórico: <?= $totalAsignaciones ?> | Actualmente Activas: <?= $asignacionesActivas ?></span>
                            </div>
                        </div>

                        <hr class="separador_seccion">

                        <div class="lista_sub_items">
                            <?php foreach ($atleta['asignaciones'] as $asignacion) :
                                // Lógica de estados actualizada
                                $esActivo = false;
                                if ($asignacion['anulado'] == 1) {
                                    $textoEstatus = 'Anulado';
                                    $claseEstatus = 'estatus_r';
                                    $claseFila = 'fila_anulada'; // Aplica el fondo gris
                                } elseif ($asignacion['estatus'] == 0) {
                                    $textoEstatus = 'Devuelto';
                                    $claseEstatus = 'estatus_v';
                                    $claseFila = ''; // Normal, no gris
                                } else {
                                    $textoEstatus = 'En Uso';
                                    $claseEstatus = 'estatus_a';
                                    $claseFila = '';
                                    $esActivo = true;
                                }
                            ?>
                                <div class="sub_item_fila <?= $claseFila ?>">

                                    <div class="sub_item_info">
                                        <span class="sub_item_titulo"><?= htmlspecialchars(mb_strtoupper($asignacion['articulo'], 'UTF-8')) ?></span>
                                        <div class="sub_item_fechas">
                                            <span>Fecha de entrega: <?= htmlspecialchars($asignacion['fecha_vista']) ?></span>
                                        </div>
                                    </div>

                                    <div class="sub_item_bloque_metricas_horizontal" style="display: flex; flex-direction: row; gap: 15px; align-items: center; flex-wrap: nowrap; justify-content: flex-end;">
                                        <div class="metrica_item">
                                            Estatus:
                                            <strong class="<?= $claseEstatus ?>"><?= $textoEstatus ?></strong>
                                        </div>
                                    </div>

                                    <div class="sub_item_acciones" onclick="event.stopPropagation();">
                                        <?php if ($esActivo) : ?>
                                            <?php if (!empty($permisos['modificar'])) : ?>
                                                <button class="btn_t cbt_v" onclick="editar(<?= $asignacion['id_asignacion'] ?>, <?= $atleta['id_atleta'] ?>, <?= $asignacion['id_equipamiento'] ?>, '<?= $asignacion['fecha_real'] ?>')" data-tippy-content="Modificar">
                                                    <i class="fi fi-sr-pencil"></i>
                                                </button>
                                            <?php endif; ?>
                                            <?php if (!empty($permisos['eliminar'])) : ?>
                                                <button class="btn_t cbt_r" onclick="anular(<?= $asignacion['id_asignacion'] ?>, <?= $asignacion['id_equipamiento'] ?>)" data-tippy-content="Anular">
                                                    <i class="fi fi-sr-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>

                                </div>
                            <?php endforeach; ?>
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
    <title>Asignaciones</title>
    <style>
        body[data-tema="oscuro"] input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(1);
            cursor: pointer;
        }
    </style>
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
                            <h2 class="titulo_pagina" id="titulo">Asignaciones</h2>
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
                            <?php if (empty($registro)) : ?>
                                <div class="listado_vacio">
                                    <p>No se encontraron registros de asignaciones</p>
                                </div>
                            <?php else : ?>
                                <?php foreach ($registro as $atleta) :
                                    $asignacionesActivas = 0;
                                    $totalAsignaciones = count($atleta['asignaciones']);

                                    foreach ($atleta['asignaciones'] as $asig) {
                                        if ($asig['anulado'] == 0 && $asig['estatus'] == 1) {
                                            $asignacionesActivas++;
                                        }
                                    }

                                    $claseEstadoGeneral = 'estado_exito';
                                ?>
                                    <div class="listado_contenedor_grupal">

                                        <div class="listado_item" onclick="toggleDetalles(this)">
                                            <div class="listado_col_principal">
                                                <div class="listado_avatar_null">
                                                    <i class="icon_con" data-lucide="circle-star"></i>
                                                </div>
                                                <div class="listado_info_base">
                                                    <span class="listado_titulo"><?= htmlspecialchars($atleta['nombre_completo']) ?></span>
                                                    <span class="listado_subtitulo">CI: <?= htmlspecialchars($atleta['doc_identidad']) ?></span>
                                                </div>
                                            </div>

                                            <div class="listado_col_datos">
                                                <div class="listado_dato_grupo">
                                                    <small>EQUIPOS EN PRÉSTAMO</small>
                                                    <span class="estatus_v"><?= $asignacionesActivas ?> Activa(s)</span>
                                                </div>
                                                <div class="listado_dato_grupo">
                                                    <small>HISTÓRICO</small>
                                                    <span><?= $totalAsignaciones ?> Asignación(es)</span>
                                                </div>
                                            </div>

                                            <div class="listado_col_acciones">
                                                <i data-lucide="chevron-down" class="icono_flecha_detalle"></i>
                                            </div>
                                        </div>

                                        <div class="listado_detalle_oculto">
                                            <div class="detalle_expandido_container" style="padding: 15px;">

                                                <div class="tarjeta_resumen <?= $claseEstadoGeneral ?>">
                                                    <div class="tarjeta_icono"><i data-lucide="box"></i></div>
                                                    <div class="tarjeta_texto">
                                                        <label>RESUMEN DE EQUIPAMIENTO</label>
                                                        <span class="texto_resaltado">Total Histórico: <?= $totalAsignaciones ?> | Actualmente Activas: <?= $asignacionesActivas ?></span>
                                                    </div>
                                                </div>

                                                <hr class="separador_seccion">

                                                <div class="lista_sub_items">
                                                    <?php foreach ($atleta['asignaciones'] as $asignacion) :
                                                        // Lógica de estados actualizada
                                                        $esActivo = false;
                                                        if ($asignacion['anulado'] == 1) {
                                                            $textoEstatus = 'Anulado';
                                                            $claseEstatus = 'estatus_r';
                                                            $claseFila = 'fila_anulada'; // Aplica el fondo gris
                                                        } elseif ($asignacion['estatus'] == 0) {
                                                            $textoEstatus = 'Devuelto';
                                                            $claseEstatus = 'estatus_v';
                                                            $claseFila = ''; // Normal, no gris
                                                        } else {
                                                            $textoEstatus = 'En Uso';
                                                            $claseEstatus = 'estatus_a';
                                                            $claseFila = '';
                                                            $esActivo = true;
                                                        }
                                                    ?>
                                                        <div class="sub_item_fila <?= $claseFila ?>">

                                                            <div class="sub_item_info">
                                                                <span class="sub_item_titulo"><?= htmlspecialchars(mb_strtoupper($asignacion['articulo'], 'UTF-8')) ?></span>
                                                                <div class="sub_item_fechas">
                                                                    <span>Fecha de entrega: <?= htmlspecialchars($asignacion['fecha_vista']) ?></span>
                                                                </div>
                                                            </div>

                                                            <div class="sub_item_bloque_metricas_horizontal" style="display: flex; flex-direction: row; gap: 15px; align-items: center; flex-wrap: nowrap; justify-content: flex-end;">
                                                                <div class="metrica_item">
                                                                    Estatus:
                                                                    <strong class="<?= $claseEstatus ?>"><?= $textoEstatus ?></strong>
                                                                </div>
                                                            </div>

                                                            <div class="sub_item_acciones" onclick="event.stopPropagation();">
                                                                <?php if ($esActivo) : ?>
                                                                    <?php if (!empty($permisos['modificar'])) : ?>
                                                                        <button class="btn_t cbt_v" onclick="editar(<?= $asignacion['id_asignacion'] ?>, <?= $atleta['id_atleta'] ?>, <?= $asignacion['id_equipamiento'] ?>, '<?= $asignacion['fecha_real'] ?>')" data-tippy-content="Modificar">
                                                                            <i class="fi fi-sr-pencil"></i>
                                                                        </button>
                                                                    <?php endif; ?>
                                                                    <?php if (!empty($permisos['eliminar'])) : ?>
                                                                        <button class="btn_t cbt_r" onclick="anular(<?= $asignacion['id_asignacion'] ?>, <?= $asignacion['id_equipamiento'] ?>)" data-tippy-content="Anular">
                                                                            <i class="fi fi-sr-trash"></i>
                                                                        </button>
                                                                    <?php endif; ?>
                                                                <?php endif; ?>
                                                            </div>

                                                        </div>
                                                    <?php endforeach; ?>
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
        <div class="modal modal_mediano ocultar" id="modal">
            <div class="cabecera_modal">
                <h2 class="titulo_modal" id="titulo_modal">Registrar Asignación</h2>
                <a type="button" class="cerrar_modal" id="cerrar_modal">&times;</a>
            </div>
            <div class="contenido_modal">
                <form id="f" autocomplete="off">
                    <input type="hidden" id="id_asignacion" name="id_asignacion">

                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <select class="formulario select2" id="id_atleta" name="id_atleta" required>
                                    <option value="" selected disabled>Seleccione un atleta...</option>
                                </select>
                                <label for="id_atleta" class="titulo_formulario">Atleta</label>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <select class="formulario select2" id="id_equipamiento" name="id_equipamiento" required>
                                    <option value="" selected disabled>Seleccione una pieza...</option>
                                </select>
                                <label for="id_equipamiento" class="titulo_formulario">Equipamiento</label>
                            </div>
                        </div>
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="date" class="formulario" id="fecha_asignacion" name="fecha_asignacion" required>
                                <label for="fecha_asignacion" class="titulo_formulario">Fecha de Asignación</label>
                            </div>
                        </div>
                    </div>

                    <div class="row row_final">
                        <div class="colum">
                            <button type="button" class="btn btn_azul" id="btn_guardar" data-accion="incluir">Confirmar Préstamo</button>
                            <button type="button" class="btn btn_verde" onclick="limpia()">Limpiar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
    <section class="contenedor_modal ocultar" id="contenedor_modal_reporte">
            <div class="modal modal_mediano ocultar" id="modal_reporte">
                <div class="cabecera_modal">
                    <h2 class="titulo_modal">Generar Reporte</h2>
                    <a type="button" class="cerrar_modal" onclick="cerrarModalReporte()">&times;</a>
                </div>
                <div class="contenido_modal">
                    <form id="form_reporte" autocomplete="off">
                        <div class="row">
                            <div class="colum">
                                <div class="caja_formulario">
                                    <select class="formulario select2_reporte" id="rep_id_atleta" name="id_atleta">
                                        <option value="" selected disabled>Selecciona una opción</option>
                                    </select>
                                    <label for="rep_id_atleta" class="titulo_formulario">Atleta</label>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="colum">
                                <div class="caja_formulario">
                                    <select class="formulario select2_reporte" id="rep_id_equipamiento" name="id_equipamiento">
                                        <option value="" selected disabled>Selecciona una opción</option>
                                    </select>
                                    <label for="rep_id_equipamiento" class="titulo_formulario">Equipamiento</label>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="colum">
                                <div class="caja_formulario">
                                    <input type="date" class="formulario" id="rep_fecha_inicio" name="fecha_inicio">
                                    <label for="rep_fecha_inicio" class="titulo_formulario">Fecha de Asignación</label>
                                </div>
                            </div>
                            <div class="colum">
                                <div class="caja_formulario">
                                    <input type="date" class="formulario" id="rep_fecha_fin" name="fecha_fin">
                                    <label for="rep_fecha_fin" class="titulo_formulario">Fecha Fin</label>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="colum" style="text-align: center; margin-top: 15px;">
                                <label style="font-size: 13px; font-weight: bold; color: var(--texto-principal);">Incluir Asignaciones Anuladas</label><br>
                                <input type="checkbox" id="rep_anulados" name="anulados" value="1" style="transform: scale(1.5); margin-top: 10px; cursor: pointer;">
                            </div>
                        </div>
                        <div class="row" style="margin-top: 25px; display: flex; justify-content: center; gap: 10px;">
                            <button type="button" class="btn btn_verde" id="btn_ejecutar_reporte">Generar Reporte</button>
                            <button type="button" class="btn btn_gris" onclick="$('#form_reporte')[0].reset(); $('.select2_reporte').val(null).trigger('change');">Limpiar</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>

    <script src="js/main.js"></script>
    <script src="js/asignaciones.js"></script>
    <?php include('complementos/mensajeError.php'); ?>
</body>

</html>