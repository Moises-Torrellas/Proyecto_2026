<?php if (isset($solo_lista) && $solo_lista === true):
    if (empty($registro)): ?>
        <div class="listado_vacio">
            <p>No se encontraron registros</p>
        </div>
        <?php else:
        foreach ($registro as $dato):

            $fechaCorta = explode(' ', $dato['fecha_emision'])[0];
            $esAnulado = ((int)$dato['anulado']) === 1;
            $estiloGris = $esAnulado ? 'style="filter: grayscale(1); opacity: 0.6; background-color: #f4f4f4;"' : '';
            if ($esAnulado) {
                $estatusHTML = '<span class="estatus_r" style="color: #6c757d; border-color: #6c757d;">Anulado</span>';
            } else {
                $estatusHTML = ((int)$dato['estatus'] === 1)
                    ? '<span class="estatus_v">Pagado</span>'
                    : '<span class="estatus_a">Pendiente</span>';
            }

            // Lógica de renderizado de los Botones de Acción con Validación de Permisos
            if ($esAnulado || (int)$dato['estatus'] === 1 || (int)$dato['monto_pendiente'] < (int)$dato['monto_total']) {
                $botonesAccion = '';
            } else {
                $botonesAccion = '';
                if ($permisos['modificar']) {
                    $botonesAccion .= '<button id="cbt_v" class="btn_t cbt_v" onclick="buscar(' . $dato['id_cobrar'] . ')" data-tippy-content="Modificar"><i class="fi fi-sr-pencil"></i></button> ';
                }
                if ($permisos['eliminar']) {
                    $botonesAccion .= '<button id="cbt_r" class="btn_t cbt_r" onclick="anular(' . $dato['id_cobrar'] . ')" data-tippy-content="Anular"><i class="fi fi-sr-cross-circle"></i></button>';
                }
            }
        ?>
            <div id="registro" class="listado_contenedor_grupal" <?= $estiloGris ?>>
                <div class="listado_item" onclick="toggleDetalles(this)">
                    <div class="listado_col_principal">
                        <div class="listado_avatar_null"><i class="icon_con" data-lucide="receipt"></i></div>
                        <div class="listado_info_base">
                            <span class="listado_titulo"><?= htmlspecialchars($dato['concepto_nombre']) ?></span>
                        </div>
                    </div>

                    <div class="listado_col_datos">
                        <div class="listado_dato_grupo">
                            <small>Fecha de Emision</small>
                            <span><?= htmlspecialchars($fechaCorta) ?></span>
                        </div>
                        <div class="listado_dato_grupo">
                            <small>Monto</small>
                            <span class="listado_resaltado"><?= htmlspecialchars($dato['moneda_simbolo'] . ' ' . $dato['monto_total']) ?></span>
                        </div>
                        <div class="listado_dato_grupo">
                            <small>Monto Pendiente</small>
                            <span><?= htmlspecialchars($dato['moneda_simbolo'] . ' ' . $dato['monto_pendiente']) ?></span>
                        </div>
                        <div class="listado_dato_grupo">
                            <small>Estatus</small>
                            <?= $estatusHTML ?>
                        </div>
                    </div>

                    <div class="listado_col_acciones">
                        <div onclick="event.stopPropagation();" style="display:flex; gap:5px;">
                            <?= $botonesAccion ?>
                        </div>
                        <i data-lucide="chevron-down" class="icono_flecha_detalle"></i>
                    </div>
                </div>

                <div class="listado_detalle_oculto">
                    <div class="detalle_expandido_container">
                        <div class="detalle_fila">
                            <div class="detalle_card">
                                <div class="detalle_card_icon"><i data-lucide="circle-star"></i></div>
                                <div class="detalle_card_txt">
                                    <label>Atleta</label>
                                    <span><?= htmlspecialchars($dato['atleta_nombre'] . ' ' . $dato['atleta_apellido']) ?></span>
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
    <title>Cuentas por Cobrar</title>
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
                            <h2 class="titulo_pagina" id="titulo">Cuentas por Cobrar</h2>
                        </div>
                        <div class="contenedor_busqueda">
                            <input type="text" placeholder="Buscar cargo..." autocomplete="off" id="busqueda">
                            <i class="fi fi-br-search icon_input"></i>
                        </div>
                        <div class="botones">
                            <?php if ($permisos['registrar']): ?>
                                <button class="btn btn_azul" id="incluir">Nuevo Cargo</button>
                            <?php endif; ?>
                            <?php if ($permisos['reporte']): ?>
                                <button class="btn btn_verde" id="generar">Generar Reporte</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="contenedor_resultados">
                        <div id="resultadoconsulta" class="resultadoconsulta">
                            <?php if (empty($registro)): ?>
                                <div class="listado_vacio">
                                    <p>No se encontraron registros</p>
                                </div>
                                <?php else:
                                foreach ($registro as $dato):

                                    $fechaCorta = explode(' ', $dato['fecha_emision'])[0];
                                    $esAnulado = ((int)$dato['anulado']) === 1;
                                    $estiloGris = $esAnulado ? 'style="filter: grayscale(1); opacity: 0.6; background-color: #f4f4f4;"' : '';
                                    if ($esAnulado) {
                                        $estatusHTML = '<span class="estatus_r" style="color: #6c757d; border-color: #6c757d;">Anulado</span>';
                                    } else {
                                        $estatusHTML = ((int)$dato['estatus'] === 1)
                                            ? '<span class="estatus_v">Pagado</span>'
                                            : '<span class="estatus_a">Pendiente</span>';
                                    }
                                    // Lógica de renderizado de los Botones de Acción con Validación de Permisos
                                    if ($esAnulado || (int)$dato['estatus'] === 1 || (int)$dato['monto_pendiente'] < (int)$dato['monto_total']) {
                                        $botonesAccion = '';
                                        
                                    } else {
                                        $botonesAccion = '';
                                        if ($permisos['modificar']) {
                                            $botonesAccion .= '<button id="cbt_v" class="btn_t cbt_v" onclick="buscar(' . $dato['id_cobrar'] . ')" data-tippy-content="Modificar"><i class="fi fi-sr-pencil"></i></button> ';
                                        }
                                        if ($permisos['eliminar']) {
                                            $botonesAccion .= '<button id="cbt_r" class="btn_t cbt_r" onclick="anular(' . $dato['id_cobrar'] . ')" data-tippy-content="Anular"><i class="fi fi-sr-cross-circle"></i></button>';
                                        }
                                    }
                                ?>
                                    <div id="registro" class="listado_contenedor_grupal" <?= $estiloGris ?>>
                                        <div class="listado_item" onclick="toggleDetalles(this)">
                                            <div class="listado_col_principal">
                                                <div class="listado_avatar_null"><i class="icon_con" data-lucide="receipt"></i></div>
                                                <div class="listado_info_base">
                                                    <span class="listado_titulo"><?= htmlspecialchars($dato['concepto_nombre']) ?></span>
                                                </div>
                                            </div>

                                            <div class="listado_col_datos">
                                                <div class="listado_dato_grupo">
                                                    <small>Fecha de Emision</small>
                                                    <span><?= htmlspecialchars($fechaCorta) ?></span>
                                                </div>
                                                <div class="listado_dato_grupo">
                                                    <small>Monto</small>
                                                    <span class="listado_resaltado"><?= htmlspecialchars($dato['moneda_simbolo'] . ' ' . $dato['monto_total']) ?></span>
                                                </div>
                                                <div class="listado_dato_grupo">
                                                    <small>Monto Pendiente</small>
                                                    <span><?= htmlspecialchars($dato['moneda_simbolo'] . ' ' . $dato['monto_pendiente']) ?></span>
                                                </div>
                                                <div class="listado_dato_grupo">
                                                    <small>Estatus</small>
                                                    <?= $estatusHTML ?>
                                                </div>
                                            </div>

                                            <div class="listado_col_acciones">
                                                <div onclick="event.stopPropagation();" style="display:flex; gap:5px;">
                                                    <?= $botonesAccion ?>
                                                </div>
                                                <i data-lucide="chevron-down" class="icono_flecha_detalle"></i>
                                            </div>
                                        </div>

                                        <div class="listado_detalle_oculto">
                                            <div class="detalle_expandido_container">
                                                <div class="detalle_fila">
                                                    <div class="detalle_card">
                                                        <div class="detalle_card_icon"><i data-lucide="circle-star"></i></div>
                                                        <div class="detalle_card_txt">
                                                            <label>Atleta</label>
                                                            <span><?= htmlspecialchars($dato['atleta_nombre'] . ' ' . $dato['atleta_apellido']) ?></span>
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
                                <select name="id_atleta" id="id_atleta" class="formulario select">
                                </select>
                                <label for="id_atleta" class="titulo_formulario">Atleta</label>
                                <span class="mensaje" id="id_atleta_span"></span>
                            </div>
                        </div>
                        <div class="colum">
                            <div class="caja_formulario">
                                <select name="id_concepto" id="id_concepto" class="formulario select">
                                </select>
                                <label for="id_concepto" class="titulo_formulario">Concepto de Cobro</label>
                                <span class="mensaje" id="id_concepto_span"></span>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <select name="id_moneda" id="id_moneda" class="formulario select">
                                </select>
                                <label for="id_moneda" class="titulo_formulario">Moneda</label>
                                <span class="mensaje" id="id_moneda_span"></span>
                            </div>
                        </div>
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="monto_total" name="monto_total" placeholder="Ej: 50.00">
                                <label for="monto_total" class="titulo_formulario">Monto Total</label>
                                <span class="mensaje" id="monto_total_spam"></span>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="monto_pendiente" name="monto_pendiente" readonly>
                                <label for="monto_pendiente" class="titulo_formulario">Monto Pendiente</label>
                                <span class="mensaje" id="monto_pendiente_spam"></span>
                            </div>
                        </div>
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="date" class="formulario" id="fecha_emision" name="fecha_emision">
                                <label for="fecha_emision" class="titulo_formulario">Fecha de Emisión</label>
                                <span class="mensaje" id="fecha_emision_spam"></span>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="date" class="formulario" id="fecha_vencimiento" name="fecha_vencimiento">
                                <label for="fecha_vencimiento" class="titulo_formulario">Fecha de Vencimiento</label>
                                <span class="mensaje" id="fecha_vencimiento_spam"></span>
                            </div>
                        </div>
                        <div class="colum">
                            <div class="caja_formulario">
                                <select name="estatus" id="estatus" class="formulario select" disabled>
                                    <option value="Pendiente">Pendiente</option>
                                    <option value="Abonado">Abonado</option>
                                    <option value="Pagado">Pagado</option>
                                </select>
                                <label for="estatus" class="titulo_formulario">Estatus</label>
                                <span class="mensaje" id="estatus_span"></span>
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
    <script src="js/cuentas_cobrar.js?v=<?= time(); ?>"></script>
</body>

</html>