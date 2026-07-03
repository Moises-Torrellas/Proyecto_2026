<?php if (isset($solo_lista) && $solo_lista === true) : ?>
    <?php if (empty($registro)) : ?>
        <div class="listado_vacio">
            <p>No se encontraron registros de asignaciones</p>
        </div>
    <?php else : ?>
        <?php foreach ($registro as $atleta) :
            // 1. Calcular totales puramente basados en estatus
            $asignacionesActivas = 0;
            $totalAsignaciones = count($atleta['asignaciones']);

            foreach ($atleta['asignaciones'] as $asig) {
                if ($asig['estatus'] == 1) { // 1 = Activa/En Uso
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
                                // Lógica de estados restaurada a tu vista original
                                $esActivo = false;
                                if ($asignacion['estatus'] == 0) {
                                    $textoEstatus = 'Inactiva / Devuelta';
                                    $claseEstatus = 'estatus_v';
                                    $claseFila = ''; 
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
                                            <?php if(isset($asignacion['codigo_club']) && !empty($asignacion['codigo_club'])): ?>
                                                <span style="margin-left: 10px; color: #888;">| Código: <?= htmlspecialchars($asignacion['codigo_club']) ?></span>
                                            <?php endif; ?>
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
                                                <button class="btn_t cbt_v" onclick="editar(<?= $asignacion['id_asignacion'] ?>, <?= $atleta['codigo_atleta'] ?>, <?= $asignacion['codigo_articulo'] ?>, '<?= $asignacion['fecha_real'] ?>')" data-tippy-content="Modificar">
                                                    <i class="fi fi-sr-pencil"></i>
                                                </button>
                                            <?php endif; ?>
                                            <?php if (!empty($permisos['eliminar'])) : ?>
                                                <button class="btn_t cbt_r" onclick="anular(<?= $asignacion['id_asignacion'] ?>, <?= $asignacion['codigo_articulo'] ?>)" data-tippy-content="Anular / Liberar Equipo">
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
                            <?php if (!empty($permisos['registrar'])) : ?>
                                <button class="btn btn_azul" id="btn_nuevo">Nueva Asignación</button>
                            <?php endif; ?>
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
        <div class="modal modal_mediano ocultar" id="modal">
            <div class="cabecera_modal">
                <h2 class="titulo_modal" id="titulo_modal">Registrar Asignación</h2>
                <a type="button" class="cerrar_modal" id="cerrar_modal">&times;</a>
            </div>
            <div class="contenido_modal">
                <form id="f" autocomplete="off">
                    <input type="hidden" id="id_asignacion" name="id_asignacion">

                    <div class="row" id="row_atleta">
                        <div class="colum">
                            <div class="caja_formulario">
                                <select class="formulario select2" id="codigo_atleta" name="codigo_atleta" required>
                                    <option value="" selected disabled>Seleccione un atleta...</option>
                                </select>
                                <label for="codigo_atleta" class="titulo_formulario">Atleta</label>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="colum" id="col_articulo">
                            <div class="caja_formulario">
                                <select class="formulario select2" id="codigo_articulo" name="codigo_articulo" required>
                                    <option value="" selected disabled>Seleccione un artículo...</option>
                                </select>
                                <label for="codigo_articulo" class="titulo_formulario">Artículo del Inventario</label>
                            </div>
                        </div>

                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="date" class="formulario" id="fecha_asignacion" name="fecha_asignacion" required>
                                <label for="fecha_asignacion" id="lbl_fecha" class="titulo_formulario">Fecha de Asignación</label>
                            </div>
                        </div>

                        <!-- Columna de Fecha Fin Oculta (Solo para el reporte) -->
                        <div class="colum" id="col_fecha_fin" style="display: none;">
                            <div class="caja_formulario">
                                <input type="date" class="formulario" id="fecha_f" name="fecha_f">
                                <label for="fecha_f" class="titulo_formulario">Fecha Fin</label>
                            </div>
                        </div>
                    </div>

                    <!-- Fila de Checkbox Oculta (Solo para el reporte) -->
                    <div class="row" id="row_anulados" style="display: none;">
                        <div class="colum">
                            <div class="caja_formulario" style="text-align: center;">
                                <label class="titulo_formulario">Incluir Asignaciones Inactivas o Devueltas en el Reporte</label>
                                <label class="checkbox-container" style="justify-content: center; margin-top: 10px;">
                                    <input type="checkbox" id="anulados" name="anulados" class="checkbox" value="1">
                                    <span class="custom-checkbox"></span>
                                </label>
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

    <script src="js/main.js"></script>
    <script src="js/asignaciones.js"></script>
    <?php include('complementos/mensajeError.php'); ?>
</body>

</html>