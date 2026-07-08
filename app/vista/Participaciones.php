<?php if (isset($solo_lista) && $solo_lista === true) :
    if (empty($registro)) : ?>
        <div class="listado_vacio">
            <p>No se encontraron registros</p>
        </div>
        <?php else :
        $torneoActual = null;
        $totalRegistros = count($registro);

        foreach ($registro as $index => $dato) :
            $idTorneo = $dato['codigo_torneo']; // Ajustado

            if ($idTorneo !== $torneoActual) :

                if ($torneoActual !== null) : ?>
                    </div>
                    </div>
                    </div>
                    </div> <?php endif;

                        $torneoActual = $idTorneo;

                        $idxAux = $index;
                        $cantidadEquipos = 0;
                        while (isset($registro[$idxAux]) && $registro[$idxAux]['codigo_torneo'] == $idTorneo) { // Ajustado
                            $cantidadEquipos++;
                            $idxAux++;
                        }

                        // LÓGICA DE ESTATUS DINÁMICO
                        $estatusTorneo = (int)$dato['torneo_estatus'];
                        $textoEstatus = match ($estatusTorneo) {
                            1 => 'Por disputarse',
                            2 => 'En Curso',
                            3 => 'Finalizado',
                            default => 'Desconocido' // Es buena práctica tener un valor por defecto
                        };

                        $claseEstatus = match ($estatusTorneo) {
                            1 => 'estatus_g',
                            2 => 'estatus_v',
                            3 => 'estatus_r',
                            default => 'estatus_default'
                        };
                            ?>
                <div class="listado_contenedor_grupal">

                    <div class="listado_item" onclick="toggleDetalles(this)">
                        <div class="listado_col_principal">
                            <div class="listado_avatar_null"><i class="icon_con" data-lucide="trophy"></i></div>
                            <div class="listado_info_base">
                                <span class="listado_titulo"><?= htmlspecialchars($dato['torneo_nombre']) ?></span>
                            </div>
                        </div>

                        <div class="listado_col_datos">
                            <div class="listado_dato_grupo">
                                <small>Estado</small>
                                <span class="<?= $claseEstatus ?>"><?= $textoEstatus ?></span>
                            </div>
                            <div class="listado_dato_grupo">
                                <small>Equipos Inscritos</small>
                                <span><?= $cantidadEquipos ?> Equipo(s)</span>
                            </div>
                        </div>

                        <div class="listado_col_acciones">
                            <i data-lucide="chevron-down" class="icono_flecha_detalle"></i>
                        </div>
                    </div>

                    <div class="listado_detalle_oculto">
                        <div class="detalle_expandido_container" style="padding: 15px;">

                            <div class="tarjeta_resumen estado_exito">
                                <div class="tarjeta_icono"><i data-lucide="users"></i></div>
                                <div class="tarjeta_texto">
                                    <label>Resumen de Participación</label>
                                    <span class="texto_resaltado">Total de Equipos: <?= $cantidadEquipos ?></span>
                                </div>
                            </div>

                            <hr class="separador_seccion">

                            <div class="lista_sub_items">
                            <?php endif;
                        $botonesAccion = '';
                        if ($estatusTorneo === 1) {
                            if (!empty($permisos['modificar_partici'])) {
                                // Ajustado a codigo_participacion
                                $botonesAccion .= '<button class="btn_t cbt_v" onclick="buscar(' . $dato['codigo_participacion'] . ')" data-tippy-content="Modificar"><i class="fi fi-sr-pencil"></i></button> ';
                            }
                            if (!empty($permisos['eliminar_partici'])) {
                                // Ajustado a codigo_participacion
                                $botonesAccion .= '<button class="btn_t cbt_r" onclick="eliminar(' . $dato['codigo_participacion'] . ')" data-tippy-content="Eliminar Inscripción"><i class="fi fi-sr-cross-circle"></i></button>';
                            }
                        }
                            ?>
                            <div class="sub_item_fila">
                                <div class="sub_item_info">
                                    <span class="sub_item_titulo"><?= htmlspecialchars($dato['equipo_nombre']) ?></span>
                                </div>

                                <div class="sub_item_centro">
                                    <span class="estatus_v">Inscrito</span>
                                </div>

                                <div class="sub_item_acciones">
                                    <?= $botonesAccion ?>
                                </div>
                            </div>

                            <?php if ($index === $totalRegistros - 1) : ?>
                            </div>
                        </div>
                    </div>
                </div> <?php endif; ?>

        <?php endforeach; ?>
    <?php endif;
    exit(); ?>
<?php endif; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include('complementos/head.php'); ?>
    <title>Participaciones</title>
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
                            <h2 class="titulo_pagina" id="titulo">Participaciones</h2>
                        </div>
                        <div class="contenedor_busqueda">
                            <input type="text" placeholder="Buscar..." autocomplete="off" id="busqueda">
                            <i class="fi fi-br-search icon_input"></i>
                        </div>
                        <div class="botones">
                            <?php if (!empty($permisos['registrar_partici'])) : ?>
                                <button class="btn btn_azul" id="incluir">Nueva Participacion</button>
                            <?php endif; ?>
                            <?php if (!empty($permisos['generar_partici'])) : ?>
                                <button class="btn btn_verde" id="generar">Generar Reporte</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="contenedor_resultados">
                        <div id="resultadoconsulta" class="resultadoconsulta">
                            <?php if (empty($registro)) : ?>
                                <div class="listado_vacio">
                                    <p>No se encontraron registros</p>
                                </div>
                                <?php else :
                                $torneoActual = null;
                                $totalRegistros = count($registro);

                                foreach ($registro as $index => $dato) :
                                    $idTorneo = $dato['codigo_torneo']; // Ajustado

                                    if ($idTorneo !== $torneoActual) :
                                        if ($torneoActual !== null) : ?>
                        </div>
                    </div>
                </div>
            </div> <?php endif;

                                        $torneoActual = $idTorneo;

                                        $idxAux = $index;
                                        $cantidadEquipos = 0;
                                        while (isset($registro[$idxAux]) && $registro[$idxAux]['codigo_torneo'] == $idTorneo) { // Ajustado
                                            $cantidadEquipos++;
                                            $idxAux++;
                                        }

                                        // LÓGICA DE ESTATUS DINÁMICO
                                        $estatusTorneo = (int)$dato['torneo_estatus'];
                                        $textoEstatus = match ($estatusTorneo) {
                                            1 => 'Por disputarse',
                                            2 => 'En Curso',
                                            3 => 'Finalizado',
                                            default => 'Desconocido' // Es buena práctica tener un valor por defecto
                                        };

                                        $claseEstatus = match ($estatusTorneo) {
                                            1 => 'estatus_g',
                                            2 => 'estatus_v',
                                            3 => 'estatus_r',
                                            default => 'estatus_default'
                                        };
                    ?>
        <div class="listado_contenedor_grupal">

            <div class="listado_item" onclick="toggleDetalles(this)">
                <div class="listado_col_principal">
                    <div class="listado_avatar_null"><i class="icon_con" data-lucide="trophy"></i></div>
                    <div class="listado_info_base">
                        <span class="listado_titulo"><?= htmlspecialchars($dato['torneo_nombre']) ?></span>
                    </div>
                </div>

                <div class="listado_col_datos">
                    <div class="listado_dato_grupo">
                        <small>Estado</small>
                        <span class="<?= $claseEstatus ?>"><?= $textoEstatus ?></span>
                    </div>
                    <div class="listado_dato_grupo">
                        <small>Equipos Inscritos</small>
                        <span><?= $cantidadEquipos ?> Equipo(s)</span>
                    </div>
                </div>

                <div class="listado_col_acciones">
                    <i data-lucide="chevron-down" class="icono_flecha_detalle"></i>
                </div>
            </div>

            <div class="listado_detalle_oculto">
                <div class="detalle_expandido_container" style="padding: 15px;">

                    <div class="tarjeta_resumen estado_exito">
                        <div class="tarjeta_icono"><i data-lucide="users"></i></div>
                        <div class="tarjeta_texto">
                            <label>Resumen de Participación</label>
                            <span class="texto_resaltado">Total de Equipos: <?= $cantidadEquipos ?></span>
                        </div>
                    </div>

                    <hr class="separador_seccion">

                    <div class="lista_sub_items">
                    <?php endif;
                                    $botonesAccion = '';
                                    if ($estatusTorneo === 1) {
                                        if (!empty($permisos['modificar_partici'])) {
                                            // Ajustado a codigo_participacion
                                            $botonesAccion .= '<button class="btn_t cbt_v" onclick="buscar(' . $dato['codigo_participacion'] . ')" data-tippy-content="Modificar"><i class="fi fi-sr-pencil"></i></button> ';
                                        }
                                        if (!empty($permisos['eliminar_partici'])) {
                                            // Ajustado a codigo_participacion
                                            $botonesAccion .= '<button class="btn_t cbt_r" onclick="eliminar(' . $dato['codigo_participacion'] . ')" data-tippy-content="Eliminar Inscripción"><i class="fi fi-sr-cross-circle"></i></button>';
                                        }
                                    }

                    ?>
                    <div class="sub_item_fila">
                        <div class="sub_item_info">
                            <span class="sub_item_titulo"><?= htmlspecialchars($dato['equipo_nombre']) ?></span>
                        </div>

                        <div class="sub_item_centro">
                            <span class="estatus_v">Inscrito</span>
                        </div>

                        <div class="sub_item_acciones">
                            <?= $botonesAccion ?>
                        </div>
                    </div>

                    <?php if ($index === $totalRegistros - 1) : ?>
                    </div>
                </div>
            </div>
        </div> <?php endif; ?>

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
                    <input type="hidden" id="codigo_participacion" name="codigo_participacion">
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <select name="codigo_torneo" id="codigo_torneo" class="formulario select">
                                    <option value="">Seleccione...</option>
                                </select>
                                <label for="codigo_torneo" class="titulo_formulario">Torneo</label>
                                <span class="mensaje" id="torneo_ind_span"></span>
                            </div>
                        </div>
                        <div class="colum">
                            <div class="caja_formulario">
                                <select name="codigo_equipo" id="codigo_equipo" class="formulario select">
                                    <option value="">Seleccione...</option>
                                </select>
                                <label for="codigo_equipo" class="titulo_formulario">Equipo</label>
                                <span class="mensaje" id="equipo_span"></span>
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
    <script src="js/participaciones.js"></script>
</body>

</html>