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
                                <h2 class="titulo_pagina" id="titulo">Devoluciones</h2>
                            </div>

                            <div class="contenedor_busqueda">
                                <input type="text" placeholder="Buscar..." autocomplete="off" id="busqueda">
                                <i class="fi fi-br-search icon_input"></i>
                            </div>

                            <div class="botones">
                                <?php if (!empty($permisos['registrar_devoluciones'])): ?>
                                    <button class="btn btn_azul" id="btn_nuevo">Nueva Devolución</button>
                                <?php endif; ?>

                                <?php if (!empty($permisos['reporte_devoluciones'])): ?>
                                    <button class="btn btn_verde" id="generar">Generar Reporte</button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="contenedor_resultados">
                            <div id="resultadoconsulta" class="resultadoconsulta">
                            <?php } ?>

                            <?php if (empty($registro)): ?>
                                <div class="listado_vacio">
                                    <p>No se encontraron registros</p>
                                </div>
                            <?php else:
                                $formatearFecha = function ($fecha) {
                                    $meses = ['01' => 'enero', '02' => 'febrero', '03' => 'marzo', '04' => 'abril', '05' => 'mayo', '06' => 'junio', '07' => 'julio', '08' => 'agosto', '09' => 'septiembre', '10' => 'octubre', '11' => 'noviembre', '12' => 'diciembre'];
                                    $partes = explode('-', explode(' ', $fecha)[0]);
                                    if (count($partes) == 3) {
                                        return $partes[2] . ' de ' . $meses[$partes[1]] . ' del ' . $partes[0];
                                    }
                                    return $fecha;
                                };
                            ?>

                                <?php foreach ($registro as $atleta): ?>
                                    <div class="listado_contenedor_grupal">
                                        <div class="listado_item" onclick="$(this).next('.listado_detalle_oculto').slideToggle();">
                                            <div class="listado_col_principal">
                                                <div class="listado_avatar_null"><i class="icon_con" data-lucide="circle-star"></i></div>
                                                <div class="listado_info_base">
                                                    <span class="listado_titulo"><?= htmlspecialchars($atleta['nombre_completo']) ?></span>
                                                    <span class="listado_subtitulo">CI: <?= htmlspecialchars($atleta['doc_identidad']) ?></span>
                                                </div>
                                            </div>

                                            <div class="listado_col_datos">
                                                <div class="listado_dato_grupo">
                                                    <small>Devoluciones</small>
                                                    <span><?= $atleta['total_devoluciones_atleta'] ?> Registro(s)</span>
                                                </div>
                                            </div>

                                            <div class="listado_col_acciones">
                                                <i data-lucide="chevron-down" class="icono_flecha_detalle"></i>
                                            </div>
                                        </div>

                                        <div class="listado_detalle_oculto" style="display:none;">
                                            <div class="detalle_expandido_container" style="padding: 15px;">

                                                <div class="tarjeta_resumen estado_exito">
                                                    <div class="tarjeta_icono"><i data-lucide="package-check"></i></div>
                                                    <div class="tarjeta_texto">
                                                        <label>RESUMEN DE DEVOLUCIONES</label>
                                                        <span class="texto_resaltado">Total Histórico: <?= $atleta['total_devoluciones_atleta'] ?></span>
                                                    </div>
                                                </div>

                                                <hr class="separador_seccion">

                                                <div class="lista_sub_items">
                                                    <?php foreach ($atleta['devoluciones'] as $dato):
                                                        $claseEstatus = 'estatus_v';
                                                        if ($dato['nivel_estado'] == 2) $claseEstatus = 'estatus_a';
                                                        if ($dato['nivel_estado'] >= 3) $claseEstatus = 'estatus_r';

                                                        $botonesAccion = '';
                                                        if (!empty($permisos['modificar_devoluciones'])) {
                                                            $botonesAccion .= '<button class="btn_t cbt_v" onclick="editar(' . $dato['id_devolucion'] . ', ' . $dato['id_asignacion'] . ', ' . $dato['id_estado'] . ', \'' . $dato['fecha_devolucion'] . '\', \'' . htmlspecialchars($dato['observacion']) . '\')" data-tippy-content="Modificar"><i class="fi fi-sr-pencil"></i></button> ';
                                                        }
                                                        if (!empty($permisos['eliminar_devoluciones'])) {
                                                            $botonesAccion .= '<button class="btn_t cbt_r" onclick="anular(' . $dato['id_devolucion'] . ')" data-tippy-content="Anular"><i class="fi fi-sr-cross-circle"></i></button>';
                                                        }
                                                    ?>
                                                        <div class="sub_item_fila">
                                                            <div class="sub_item_info">
                                                                <span class="sub_item_titulo"><?= htmlspecialchars(mb_strtoupper($dato['articulo_nombre'], 'UTF-8')) ?></span>
                                                                <div class="sub_item_fechas">
                                                                    <span>Devuelto: <?= htmlspecialchars($formatearFecha($dato['fecha_devolucion'])) ?></span>
                                                                    <?php if (isset($dato['codigo_club']) && !empty($dato['codigo_club'])): ?>
                                                                        <span style="margin-left: 10px; color: #888;">| Código: <?= htmlspecialchars($dato['codigo_club']) ?></span>
                                                                    <?php endif; ?>
                                                                    <?php if (!empty(trim($dato['observacion']))) : ?>
                                                                        <span>Obs: <?= htmlspecialchars($dato['observacion']) ?></span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                            <div class="sub_item_bloque_metricas_horizontal" style="display: flex; flex-direction: row; gap: 15px; align-items: center; flex-wrap: nowrap; justify-content: flex-end;">
                                                                <div class="metrica_item">
                                                                    Condición de Entrega:
                                                                    <strong class="<?= $claseEstatus ?>"><?= $dato['estado_fisico'] ?></strong>
                                                                </div>
                                                            </div>
                                                            <div class="sub_item_acciones" onclick="event.stopPropagation();">
                                                                <?= $botonesAccion ?>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                            <?php endif; ?>

                            <?php if (!isset($solo_lista)) { ?>
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
                    <h2 class="titulo_modal" id="titulo_modal">Registrar Devolución</h2>
                    <a type="button" class="cerrar_modal" id="cerrar_modal">&times;</a>
                </div>
                <div class="contenido_modal">
                    <form id="f" autocomplete="off">
                        <input type="hidden" id="id_devolucion" name="id_devolucion">

                        <div class="row" id="row_atleta_reporte" style="display: none;">
                            <div class="colum">
                                <div class="caja_formulario">
                                    <select class="formulario select2" id="filtro_atleta" name="filtro_atleta">
                                        <option value="" selected>Todos los atletas</option>
                                    </select>
                                    <label for="filtro_atleta" class="titulo_formulario">Atleta / Cédula</label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="colum" id="col_asignacion">
                                <div class="caja_formulario">
                                    <select class="formulario select2" id="id_asignacion" name="id_asignacion" required>
                                        <option value="" selected disabled>Seleccione una asignación...</option>
                                    </select>
                                    <label for="id_asignacion" class="titulo_formulario">Asignación</label>
                                </div>
                            </div>
                            <div class="colum" id="col_estado">
                                <div class="caja_formulario">
                                    <select class="formulario select2" id="id_estado" name="id_estado" required>
                                        <option value="" selected disabled>Seleccione un Estado Fisico...</option>
                                    </select>
                                    <label for="id_estado" class="titulo_formulario">Estado Fisico</label>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="colum" id="col_fecha_devolucion">
                                <div class="caja_formulario">
                                    <input type="date" class="formulario" id="fecha_devolucion" name="fecha_devolucion" required>
                                    <label for="fecha_devolucion" id="lbl_fecha" class="titulo_formulario">Fecha de Devolución</label>
                                </div>
                            </div>

                            <!-- Columna de Fecha Hasta Oculta (Solo para el reporte) -->
                            <div class="colum" id="col_fecha_hasta" style="display: none;">
                                <div class="caja_formulario">
                                    <input type="date" class="formulario" id="fecha_hasta" name="fecha_hasta">
                                    <label for="fecha_hasta" class="titulo_formulario">Fecha Hasta</label>
                                </div>
                            </div>
                        </div>

                        <div class="row" id="row_observacion">
                            <div class="colum">
                                <div class="caja_formulario">
                                    <input type="text" class="formulario" id="observacion" name="observacion" placeholder="Ej. El equipo tiene un raspón">
                                    <label for="observacion" class="titulo_formulario">Observación (Opcional)</label>
                                </div>
                            </div>
                        </div>

                        <div class="row row_final">
                            <div class="colum">
                                <button type="button" class="btn btn_azul" id="btn_guardar" data-accion="incluir">Confirmar</button>
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