<?php if (isset($solo_lista) && $solo_lista === true) :
    if (empty($registro)) : ?>
        <div class="listado_vacio">
            <p>No se encontraron registros</p>
        </div>
        <?php else :
        foreach ($registro as $index => $dato) :

            // Adaptación del formateo de fecha y hora original a PHP
            $fechaFormateada = date('d/m/Y', strtotime($dato['fecha']));
            $horaFormateada = date('h:i A', strtotime($dato['hora']));

            // Lógica de datos previos y nuevos con escape HTML
            $datosPrevios = (!empty($dato['datos_previos']) && $dato['datos_previos'] !== 'null') ? htmlspecialchars($dato['datos_previos']) : 'No Aplica';
            $datosNuevos = (!empty($dato['datos_nuevos']) && $dato['datos_nuevos'] !== 'null') ? htmlspecialchars($dato['datos_nuevos']) : 'No Aplica';

            // Lógica para definir la clase de estatus 
            // (Nota: la mantengo calculada tal como en tu JS, aunque en la estructura HTML original no la inyectabas)
            $accionesStr = strtolower($dato['acciones']);
            if ($accionesStr === 'exito' || strpos($accionesStr, 'éxito') !== false || strpos($accionesStr, 'exito') !== false) {
                $estatusClase = 'estatus_v';
            } elseif (strpos($accionesStr, 'error') !== false || strpos($accionesStr, 'fallido') !== false || strpos($accionesStr, 'fracaso') !== false) {
                $estatusClase = 'estatus_r';
            } else {
                $estatusClase = 'estatus_a';
            }
        ?>

            <div class="listado_contenedor_grupal">
                <div class="listado_item" onclick="toggleDetalles(this)">
                    <div class="listado_col_principal">
                        <div class="listado_avatar_null"><i class="icon_con" data-lucide="<?= htmlspecialchars($dato['icono']) ?>"></i></div>
                        <div class="listado_info_base">
                            <span class="listado_titulo">
                                <?= htmlspecialchars($dato['nombre_modulo']) ?>
                            </span>
                        </div>
                    </div>

                    <div class="listado_col_datos">
                        <div class="listado_dato_grupo">
                            <small>Usuario</small>
                            <span><?= htmlspecialchars($dato['nombreUsuario']) ?> <?= htmlspecialchars($dato['apellidoUsuario']) ?></span>
                        </div>
                        <div class="listado_dato_grupo">
                            <small>Acción</small>
                            <span><?= htmlspecialchars($dato['acciones']) ?></span>
                        </div>
                        <div class="listado_dato_grupo">
                            <small>Fecha y Hora</small>
                            <span><?= $fechaFormateada ?> <?= $horaFormateada ?></span>
                        </div>
                    </div>

                    <div class="listado_col_acciones">
                        <i data-lucide="chevron-down" class="icono_flecha_detalle"></i>
                    </div>
                </div>

                <div class="listado_detalle_oculto">
                    <div class="detalle_expandido_container">
                        <h4 class="titulo_des">Detalles de la Acción:</h4>
                        <div class="detalle_fila">
                            <div class="detalle_card" style="width: 100%;">
                                <div class="detalle_card_icon"><i data-lucide="info"></i></div>
                                <div class="detalle_card_txt">
                                    <label>Información Adicional</label>
                                    <span>Cédula: <b><?= htmlspecialchars($dato['cedulaUsuario']) ?></b></span>
                                    <span>Entorno: <b><?= htmlspecialchars($dato['entorno'] ?? 'N/A') ?></b></span>
                                </div>
                            </div>
                        </div>
                        <h4 class="titulo_des">Cambios Realizados:</h4>
                        <div class="detalle_fila">
                            <div class="detalle_card" style="width: 100%;">
                                <div class="detalle_card_icon"><i data-lucide="history"></i></div>
                                <div class="detalle_card_txt">
                                    <label>Datos Previos</label>
                                    <span style="white-space: pre-wrap; font-size: 13px; line-height: 1.5; color: #555;"><?= $datosPrevios ?></span>
                                </div>
                            </div>
                            <div class="detalle_card" style="width: 100%;">
                                <div class="detalle_card_icon"><i data-lucide="file-diff"></i></div>
                                <div class="detalle_card_txt">
                                    <label>Datos Nuevos</label>
                                    <span style="white-space: pre-wrap; font-size: 13px; line-height: 1.5; color: #28a745;"><?= $datosNuevos ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php endforeach; ?>
    <?php endif;
    exit(); ?>
<?php endif; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include('complementos/head.php'); ?>
    <title>Bitacora</title>
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
                            <h2 class="titulo_pagina" id="titulo">Bitacora</h2>
                        </div>
                        <div class="contenedor_busqueda">
                            <input type="text" placeholder="Buscar..." autocomplete="off" id="busqueda">
                            <i class="fi fi-br-search icon_input"></i>
                        </div>
                        <div class="botones">
                            <?php if(!empty($permisos['generar_bitacora'])) : ?>
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
                                foreach ($registro as $index => $dato) :

                                    // Adaptación del formateo de fecha y hora original a PHP
                                    $fechaFormateada = date('d/m/Y', strtotime($dato['fecha']));
                                    $horaFormateada = date('h:i A', strtotime($dato['hora']));

                                    // Lógica de datos previos y nuevos con escape HTML
                                    $datosPrevios = (!empty($dato['datos_previos']) && $dato['datos_previos'] !== 'null') ? htmlspecialchars($dato['datos_previos']) : 'No Aplica';
                                    $datosNuevos = (!empty($dato['datos_nuevos']) && $dato['datos_nuevos'] !== 'null') ? htmlspecialchars($dato['datos_nuevos']) : 'No Aplica';

                                    // Lógica para definir la clase de estatus 
                                    // (Nota: la mantengo calculada tal como en tu JS, aunque en la estructura HTML original no la inyectabas)
                                    $accionesStr = strtolower($dato['acciones']);
                                    if ($accionesStr === 'exito' || strpos($accionesStr, 'éxito') !== false || strpos($accionesStr, 'exito') !== false) {
                                        $estatusClase = 'estatus_v';
                                    } elseif (strpos($accionesStr, 'error') !== false || strpos($accionesStr, 'fallido') !== false || strpos($accionesStr, 'fracaso') !== false) {
                                        $estatusClase = 'estatus_r';
                                    } else {
                                        $estatusClase = 'estatus_a';
                                    }
                                ?>

                                    <div class="listado_contenedor_grupal">
                                        <div class="listado_item" onclick="toggleDetalles(this)">
                                            <div class="listado_col_principal">
                                                <div class="listado_avatar_null"><i class="icon_con" data-lucide="<?= htmlspecialchars($dato['icono']) ?>"></i></div>
                                                <div class="listado_info_base">
                                                    <span class="listado_titulo">
                                                        <?= htmlspecialchars($dato['nombre_modulo']) ?>
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="listado_col_datos">
                                                <div class="listado_dato_grupo">
                                                    <small>Usuario</small>
                                                    <span><?= htmlspecialchars($dato['nombreUsuario']) ?> <?= htmlspecialchars($dato['apellidoUsuario']) ?></span>
                                                </div>
                                                <div class="listado_dato_grupo">
                                                    <small>Acción</small>
                                                    <span><?= htmlspecialchars($dato['acciones']) ?></span>
                                                </div>
                                                <div class="listado_dato_grupo">
                                                    <small>Fecha y Hora</small>
                                                    <span><?= $fechaFormateada ?> <?= $horaFormateada ?></span>
                                                </div>
                                            </div>

                                            <div class="listado_col_acciones">
                                                <i data-lucide="chevron-down" class="icono_flecha_detalle"></i>
                                            </div>
                                        </div>

                                        <div class="listado_detalle_oculto">
                                            <div class="detalle_expandido_container">
                                                <h4 class="titulo_des">Detalles de la Acción:</h4>
                                                <div class="detalle_fila">
                                                    <div class="detalle_card" style="width: 100%;">
                                                        <div class="detalle_card_icon"><i data-lucide="info"></i></div>
                                                        <div class="detalle_card_txt">
                                                            <label>Información Adicional</label>
                                                            <span>Cédula: <b><?= htmlspecialchars($dato['cedulaUsuario']) ?></b></span>
                                                            <span>Entorno: <b><?= htmlspecialchars($dato['entorno'] ?? 'N/A') ?></b></span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <h4 class="titulo_des">Cambios Realizados:</h4>
                                                <div class="detalle_fila">
                                                    <div class="detalle_card" style="width: 100%;">
                                                        <div class="detalle_card_icon"><i data-lucide="history"></i></div>
                                                        <div class="detalle_card_txt">
                                                            <label>Datos Previos</label>
                                                            <span style="white-space: pre-wrap; font-size: 13px; line-height: 1.5; color: #555;"><?= $datosPrevios ?></span>
                                                        </div>
                                                    </div>
                                                    <div class="detalle_card" style="width: 100%;">
                                                        <div class="detalle_card_icon"><i data-lucide="file-diff"></i></div>
                                                        <div class="detalle_card_txt">
                                                            <label>Datos Nuevos</label>
                                                            <span style="white-space: pre-wrap; font-size: 13px; line-height: 1.5; color: #28a745;"><?= $datosNuevos ?></span>
                                                        </div>
                                                    </div>
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
                <form id="f" autocomplete="off" enctype="multipart/form-data">
                    <input type="hidden" id="id" name="id">
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
    <script src="js/bitacora.js"></script>
    <?php include('complementos/mensajeError.php'); ?>
</body>

</html>