<?php if (isset($solo_lista) && $solo_lista === true) :
    if (empty($registro)) : ?>
        <div class="listado_vacio">
            <p>No se encontraron registros de estadísticas</p>
        </div>
        <?php else :
        $atletaActual = null;
        $totalRegistros = count($registro);

        // BUCLE ÚNICO TOTALMENTE LINEAL O(N)
        foreach ($registro as $index => $dato) :
            $idAtleta = $dato['id_atleta'];

            // 1. DETECTAR CAMBIO DE ATLETA (O PRIMER REGISTRO)
            if ($idAtleta !== $atletaActual) :
                // Si ya veníamos procesando un atleta anterior, cerramos sus contenedores
                if ($atletaActual !== null) : ?>
                    </div>
                    </div>
                    </div>
                    </div> <?php
                        endif;

                        $atletaActual = $idAtleta;

                        // OPTIMIZACIÓN: Pre-calcular los totales globales del atleta actual
                        $idxAux = $index;
                        $totalGoles = 0;
                        $totalPartidos = 0;
                        $totalParticipaciones = 0;

                        while (isset($registro[$idxAux]) && $registro[$idxAux]['id_atleta'] == $idAtleta) {
                            $totalGoles += (int)$registro[$idxAux]['goles'];
                            $totalPartidos += (int)$registro[$idxAux]['partidos_jugados'];
                            $totalParticipaciones++;
                            $idxAux++;
                        }

                        $claseEstadoGeneral = ($totalGoles > 0) ? 'estado_exito' : 'estado_neutral';
                            ?>
                <div class="listado_contenedor_grupal">

                    <div class="listado_item" onclick="toggleDetalles(this)">
                        <div class="listado_col_principal">
                            <div class="listado_avatar_null"><i class="icon_con" data-lucide="circle-user"></i></div>
                            <div class="listado_info_base">
                                <span class="listado_titulo"><?= htmlspecialchars($dato['nombres'] . ' ' . $dato['apellidos']) ?></span>
                            </div>
                        </div>

                        <div class="listado_col_datos">
                            <div class="listado_dato_grupo">
                                <small>RENDIMIENTO GLOBAL</small>
                                <span class="estatus_v"><?= $totalGoles ?> Goles</span>
                            </div>
                            <div class="listado_dato_grupo">
                                <small>PARTICIPACIÓN</small>
                                <span><?= $totalParticipaciones ?> Torneo(s)</span>
                            </div>
                        </div>

                        <div class="listado_col_acciones">
                            <i data-lucide="chevron-down" class="icono_flecha_detalle"></i>
                        </div>
                    </div>

                    <div class="listado_detalle_oculto">
                        <div class="detalle_expandido_container" style="padding: 15px;">

                            <div class="tarjeta_resumen <?= $claseEstadoGeneral ?>">
                                <div class="tarjeta_icono"><i data-lucide="trophy"></i></div>
                                <div class="tarjeta_texto">
                                    <label>RESUMEN ESTADISTICO</label>
                                    <span class="texto_resaltado">Total Partidos: <?= $totalPartidos ?> | Total Goles: <?= $totalGoles ?></span>
                                </div>
                            </div>

                            <hr class="separador_seccion">

                            <div class="lista_sub_items">
                            <?php endif; ?>

                            <?php
                            // 2. RENDERIZAR FILA DE TORNEO CON MÉTRICAS EN FILA HORIZONTAL
                            $botonesAccion = '';
                            if (isset($permisos['modificar']) && $permisos['modificar']) {
                                $botonesAccion .= '<button class="btn_t cbt_v" onclick="buscar(' . $dato['id_estadisticas'] . ')" data-tippy-content="Modificar"><i class="fi fi-sr-pencil"></i></button> ';
                            }
                            if (isset($permisos['eliminar']) && $permisos['eliminar']) {
                                $botonesAccion .= '<button class="btn_t cbt_r" onclick="eliminar(' . $dato['id_estadisticas'] . ')" data-tippy-content="Eliminar"><i class="fi fi-sr-cross-circle"></i></button>';
                            }
                            ?>
                            <div class="sub_item_fila_estadistica">
                                <div class="sub_item_info_torneo">
                                    <span class="torneo_titulo_resaltado"><?= htmlspecialchars(mb_strtoupper($dato['torneo_nombre'], 'UTF-8')) ?></span>
                                    <span class="torneo_fecha_sub">Fecha: <?= date('d/m/Y', strtotime($dato['fecha_inicio'])) ?></span>
                                </div>

                                <div class="sub_item_bloque_metricas_horizontal">
                                    <div class="metrica_item">PJ: <strong><?= $dato['partidos_jugados'] ?></strong></div>
                                    <div class="metrica_item">Goles: <strong><?= $dato['goles'] ?></strong></div>
                                    <div class="metrica_item">Asistencias: <strong><?= $dato['asistencias'] ?></strong></div>
                                    <div class="metrica_item">Goles Contra: <strong><?= $dato['goles_contra'] ?></strong></div>
                                    <div class="metrica_item">Penalizaciones: <strong><?= $dato['penalizaciones'] ?></strong></div>
                                    <div class="metrica_item">Average: <strong><?= number_format((float)$dato['average'], 2) ?></strong></div>
                                </div>

                                <div class="sub_item_acciones_estadistica">
                                    <?= $botonesAccion ?>
                                </div>
                            </div>

                            <?php if ($index === $totalRegistros - 1) : ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        <?php endforeach; ?>
    <?php endif;
    exit(); ?>
<?php endif; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include('complementos/head.php'); ?>
    <title>Estadisticas</title>
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
                            <h2 class="titulo_pagina" id="titulo">Estadisticas</h2>
                        </div>
                        <div class="contenedor_busqueda">
                            <input type="text" placeholder="Buscar..." autocomplete="off" id="busqueda">
                            <i class="fi fi-br-search icon_input"></i>
                        </div>
                        <div class="botones">
                            <?php if ($permisos['registrar']) : ?>
                                <button class="btn btn_azul" id="incluir">Nuevas Estadisticas</button>
                            <?php endif; ?>
                            <?php if ($permisos['reporte']) : ?>
                                <button class="btn btn_verde" id="generar">Generar Reporte</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="contenedor_resultados">
                        <div id="resultadoconsulta" class="resultadoconsulta">
                            <?php if (empty($registro)) : ?>
                                <div class="listado_vacio">
                                    <p>No se encontraron registros de estadísticas</p>
                                </div>
                                <?php else :
                                $atletaActual = null;
                                $totalRegistros = count($registro);

                                // BUCLE ÚNICO TOTALMENTE LINEAL O(N)
                                foreach ($registro as $index => $dato) :
                                    $idAtleta = $dato['id_atleta'];

                                    // 1. DETECTAR CAMBIO DE ATLETA (O PRIMER REGISTRO)
                                    if ($idAtleta !== $atletaActual) :
                                        // Si ya veníamos procesando un atleta anterior, cerramos sus contenedores
                                        if ($atletaActual !== null) : ?>
                        </div>
                    </div>
                </div>
            </div> <?php
                                        endif;

                                        $atletaActual = $idAtleta;

                                        // OPTIMIZACIÓN: Pre-calcular los totales globales del atleta actual
                                        $idxAux = $index;
                                        $totalGoles = 0;
                                        $totalPartidos = 0;
                                        $totalParticipaciones = 0;

                                        while (isset($registro[$idxAux]) && $registro[$idxAux]['id_atleta'] == $idAtleta) {
                                            $totalGoles += (int)$registro[$idxAux]['goles'];
                                            $totalPartidos += (int)$registro[$idxAux]['partidos_jugados'];
                                            $totalParticipaciones++;
                                            $idxAux++;
                                        }

                                        $claseEstadoGeneral = ($totalGoles > 0) ? 'estado_exito' : 'estado_neutral';
                    ?>
        <div class="listado_contenedor_grupal">

            <div class="listado_item" onclick="toggleDetalles(this)">
                <div class="listado_col_principal">
                    <div class="listado_avatar_null"><i class="icon_con" data-lucide="circle-user"></i></div>
                    <div class="listado_info_base">
                        <span class="listado_titulo"><?= htmlspecialchars($dato['nombres'] . ' ' . $dato['apellidos']) ?></span>
                    </div>
                </div>

                <div class="listado_col_datos">
                    <div class="listado_dato_grupo">
                        <small>RENDIMIENTO GLOBAL</small>
                        <span class="estatus_v"><?= $totalGoles ?> Goles</span>
                    </div>
                    <div class="listado_dato_grupo">
                        <small>PARTICIPACIÓN</small>
                        <span><?= $totalParticipaciones ?> Torneo(s)</span>
                    </div>
                </div>

                <div class="listado_col_acciones">
                    <i data-lucide="chevron-down" class="icono_flecha_detalle"></i>
                </div>
            </div>

            <div class="listado_detalle_oculto">
                <div class="detalle_expandido_container" style="padding: 15px;">

                    <div class="tarjeta_resumen <?= $claseEstadoGeneral ?>">
                        <div class="tarjeta_icono"><i data-lucide="trophy"></i></div>
                        <div class="tarjeta_texto">
                            <label>RESUMEN ESTADISTICO</label>
                            <span class="texto_resaltado">Total Partidos: <?= $totalPartidos ?> | Total Goles: <?= $totalGoles ?></span>
                        </div>
                    </div>

                    <hr class="separador_seccion">

                    <div class="lista_sub_items">
                    <?php endif; ?>

                    <?php
                                    // 2. RENDERIZAR FILA DE TORNEO CON MÉTRICAS EN FILA HORIZONTAL
                                    $botonesAccion = '';
                                    if (isset($permisos['modificar']) && $permisos['modificar']) {
                                        $botonesAccion .= '<button class="btn_t cbt_v" onclick="buscar(' . $dato['id_estadisticas'] . ')" data-tippy-content="Modificar"><i class="fi fi-sr-pencil"></i></button> ';
                                    }
                                    if (isset($permisos['eliminar']) && $permisos['eliminar']) {
                                        $botonesAccion .= '<button class="btn_t cbt_r" onclick="eliminar(' . $dato['id_estadisticas'] . ')" data-tippy-content="Eliminar"><i class="fi fi-sr-cross-circle"></i></button>';
                                    }
                    ?>
                    <div class="sub_item_fila_estadistica">
                        <div class="sub_item_info_torneo">
                            <span class="torneo_titulo_resaltado"><?= htmlspecialchars(mb_strtoupper($dato['torneo_nombre'], 'UTF-8')) ?></span>
                            <span class="torneo_fecha_sub">Fecha: <?= date('d/m/Y', strtotime($dato['fecha_inicio'])) ?></span>
                        </div>

                        <div class="sub_item_bloque_metricas_horizontal">
                            <div class="metrica_item">PJ: <strong><?= $dato['partidos_jugados'] ?></strong></div>
                            <div class="metrica_item">Goles: <strong><?= $dato['goles'] ?></strong></div>
                            <div class="metrica_item">Asistencias: <strong><?= $dato['asistencias'] ?></strong></div>
                            <div class="metrica_item">Goles Contra: <strong><?= $dato['goles_contra'] ?></strong></div>
                            <div class="metrica_item">Penalizaciones: <strong><?= $dato['penalizaciones'] ?></strong></div>
                            <div class="metrica_item">Average: <strong><?= number_format((float)$dato['average'], 2) ?></strong></div>
                        </div>

                        <div class="sub_item_acciones_estadistica">
                            <?= $botonesAccion ?>
                        </div>
                    </div>

                    <?php if ($index === $totalRegistros - 1) : ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

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
        <div class="modal ocultar" id="modal">
            <div class="cabecera_modal">
                <h2 class="titulo_modal" id="titulo_modal"></h2>
                <a type="button" class="cerrar_modal" id="cerrar_modal">&times;</a>
            </div>
            <div class="contenido_modal">
                <form id="f" autocomplete="off">
                    <input type="hidden" id="id" name="id">
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <select name="participacion" id="participacion" class="formulario select">

                                </select>
                                <label for="participacion" class="titulo_formulario">Participación / Torneo</label>
                            </div>
                        </div>
                        <div class="colum">
                            <div class="caja_formulario">
                                <select name="atleta" id="atleta" class="formulario select">

                                </select>
                                <label for="atleta" class="titulo_formulario">Atleta</label>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="goles" name="goles">
                                <label for="goles" class="titulo_formulario">Goles</label>
                                <span class="mensaje" id="goles_spam"></span>
                            </div>
                        </div>
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="asistencias" name="asistencias">
                                <label for="asistencias" class="titulo_formulario">Asistencias</label>
                                <span class="mensaje" id="asistencias_spam"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="penalizaciones" name="penalizaciones">
                                <label for="penalizaciones" class="titulo_formulario">Penalizaciones</label>
                                <span class="mensaje" id="penalizaciones_spam"></span>
                            </div>
                        </div>
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="goles_c" name="goles_c">
                                <label for="goles_c" class="titulo_formulario">Goles en Contra</label>
                                <span class="mensaje" id="goles_c_spam"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="partido" name="partido">
                                <label for="partido" class="titulo_formulario">Partidos Jugados</label>
                                <span class="mensaje" id="partido_spam"></span>
                            </div>
                        </div>
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="average" name="average">
                                <label for="average" class="titulo_formulario">Average</label>
                                <span class="mensaje" id="average_spam"></span>
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
    <script src="js/Estadisticas.js"></script>
</body>

</html>