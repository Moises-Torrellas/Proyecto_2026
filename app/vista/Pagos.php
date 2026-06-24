<?php
if (isset($solo_lista) && $solo_lista === true) :
    if (empty($registro)) : ?>
        <div class="listado_vacio">
            <p>No se encontraron registros de pagos</p>
        </div>
        <?php else :
        foreach ($registro as $dato) :
            $fechaPago = date('d/m/Y', strtotime($dato['fecha_pago']));
            $simboloMoneda = htmlspecialchars($dato['simbolo'] . ' ' . $dato['abre']);
            $montoFormateado = number_format($dato['monto_pagado'], 2, ',', '.');

            $esAnulado = ((int)$dato['estatus']) !== 1;
            $estiloGris = $esAnulado ? 'style="filter: grayscale(1); opacity: 0.6; background-color: #f4f4f4;"' : '';

            if ($esAnulado) {
                $estatusHTML = '<span class="estatus_r">Anulado</span>';
                $botonesAccion = '';
            } else {
                $estatusHTML = '<span class="estatus_v">Realizado</span>';


                $botonesAccion = '';
                if ($permisos['eliminar']) {
                    $botonesAccion = '<button id="cbt_r" class="btn_t cbt_r" onclick="eliminar(' . $dato['id_pago'] . ')" data-tippy-content="Anular"><i class="fi fi-sr-cross-circle"></i></button>';
                }
            }
        ?>
            <div id="registro" class="listado_contenedor_grupal" <?= $estiloGris ?>>
                <div class="listado_item" onclick="toggleDetalles(this)">

                    <div class="listado_col_principal">
                        <div class="listado_avatar_null"><i class="icon_con" data-lucide="banknote"></i></div>
                        <div class="listado_info_base">
                            <span class="listado_titulo">
                                <?= !empty($dato['concepto_pago']) ? htmlspecialchars($dato['concepto_pago']) : 'Pago General' ?>
                            </span>
                        </div>
                    </div>

                    <div class="listado_col_datos">
                        <div class="listado_dato_grupo">
                            <small>Monto Pagado</small>
                            <span><?= $simboloMoneda ?> <?= $montoFormateado ?></span>
                        </div>
                        <div class="listado_dato_grupo">
                            <small>Referencia</small>
                            <span><?= !empty($dato['referencia']) ? htmlspecialchars($dato['referencia']) : 'N/A' ?></span>
                        </div>
                        <div class="listado_dato_grupo">
                            <small>Fecha</small>
                            <span><?= $fechaPago ?></span>
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
                            <div class="detalle_card" style="width: 100%;">
                                <div class="detalle_card_icon"><i data-lucide="trending-up"></i></div>
                                <div class="detalle_card_txt">
                                    <label>Detalles Financieros del Pago General</label>
                                    <span>Moneda: <?= htmlspecialchars($dato['moneda']) ?></span>
                                </div>
                            </div>
                            <div class="detalle_card" style="width: 100%;">
                                <div class="detalle_card_icon"><i data-lucide="wallet"></i></div>
                                <div class="detalle_card_txt">
                                    <label>Metodo de Pago</label>
                                    <span>Metodo: <?= htmlspecialchars($dato['nombre_metodo_pago']) ?></span>
                                </div>
                            </div>
                        </div>
                        <h4 class="titulo_des">Desglose de Cuentas Abonadas:</h4>
                        
                        <?php if (!empty($dato['detalles'])) :
                            $bloquesDetalles = array_chunk($dato['detalles'], 3);
                            foreach ($bloquesDetalles as $bloque) : ?>
                                <div class="detalle_fila">
                                    <?php foreach ($bloque as $det) : ?>
                                        <div class="detalle_card">
                                            <div class="detalle_card_icon"><i data-lucide="file-text"></i></div>
                                            <div class="detalle_card_txt">
                                                <label><?= htmlspecialchars($det['concepto']) ?></label>
                                                <span><?= htmlspecialchars($det['atleta']) ?></span>
                                                <small>Abono: <b style="color:#28a745;"><?= number_format($det['monto'], 2, ',', '.') ?> <?= htmlspecialchars($det['moneda']) ?></b></small>
                                                <small>Tasa: <b style="color:#28a745;"><?= number_format($det['tasa'], 4, ',', '.') ?> <?= htmlspecialchars($det['moneda_tasa']) ?></b></small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <div class="detalle_fila">
                                <span>No hay cuentas asociadas a este pago.</span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($dato['vueltos'])) : ?>
                            <h4 class="titulo_des">Vueltos Registrados:</h4>
                            <div class="detalle_fila">
                                <?php foreach ($dato['vueltos'] as $v) : ?>
                                    <div class="detalle_card">
                                        <div class="detalle_card_icon"><i data-lucide="hand-coins"></i></div>
                                        <div class="detalle_card_txt">
                                            <label>Vuelto Entregado</label>
                                            <span>Monto: <b style="color:#28a745;"><?= number_format($v['monto_vuelto'], 2, ',', '.') ?> <?= htmlspecialchars($v['simbolo'] . ' ' . $v['abreviatura']) ?></b></span>
                                            <small>Método: <?= htmlspecialchars($v['nombre_metodo_vuelto'] ?? 'N/A') ?></small>
                                            <small>Fecha: <?= date('d/m/Y', strtotime($v['fecha_vuelto'])) ?></small>
                                            <?php if(!empty($v['referencia'])): ?>
                                            <small>Ref: <?= htmlspecialchars($v['referencia']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
<?php
        endforeach;
    endif;
    exit();
endif;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include('complementos/head.php'); ?>
    <title>Pagos</title>
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
                            <h2 class="titulo_pagina" id="titulo">Pagos</h2>
                        </div>
                        <div class="contenedor_busqueda">
                            <input type="text" placeholder="Buscar..." autocomplete="off" id="busqueda">
                            <i class="fi fi-br-search icon_input"></i>
                        </div>
                        <div class="botones">
                            <?php if ($permisos['registrar']) : ?>
                            <button class="btn btn_azul" id="incluir">Nuevo Pago</button>
                            <?php endif; ?>
                            <?php if ($permisos['reporte']) : ?>
                            <button class="btn btn_verde" id="generar">Generar Reporte</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="contenedor_resultados">
                        <div id="resultadoconsulta" class="resultadoconsulta">
                            <?php
                            if (empty($registro)) : ?>
                                <div class="listado_vacio">
                                    <p>No se encontraron registros de pagos</p>
                                </div>
                                <?php else :
                                foreach ($registro as $dato) :
                                    $fechaPago = date('d/m/Y', strtotime($dato['fecha_pago']));
                                    $simboloMoneda = htmlspecialchars($dato['simbolo'] . ' ' . $dato['abre']);
                                    $montoFormateado = number_format($dato['monto_pagado'], 2, ',', '.');

                                    $esAnulado = ((int)$dato['estatus']) !== 1;
                                    $estiloGris = $esAnulado ? 'style="filter: grayscale(1); opacity: 0.6; background-color: #f4f4f4;"' : '';

                                    if ($esAnulado) {
                                        $estatusHTML = '<span class="estatus_r">Anulado</span>';
                                        $botonesAccion = '';
                                    } else {
                                        $estatusHTML = '<span class="estatus_v">Realizado</span>';


                                        $botonesAccion = '';
                                        if ($permisos['eliminar']) {
                                            $botonesAccion = '<button id="cbt_r" class="btn_t cbt_r" onclick="eliminar(' . $dato['id_pago'] . ')" data-tippy-content="Anular"><i class="fi fi-sr-cross-circle"></i></button>';
                                        }
                                    }
                                ?>
                                    <div id="registro" class="listado_contenedor_grupal" <?= $estiloGris ?>>
                                        <div class="listado_item" onclick="toggleDetalles(this)">

                                            <div class="listado_col_principal">
                                                <div class="listado_avatar_null"><i class="icon_con" data-lucide="banknote"></i></div>
                                                <div class="listado_info_base">
                                                    <span class="listado_titulo">
                                                        <?= !empty($dato['concepto_pago']) ? htmlspecialchars($dato['concepto_pago']) : 'Pago General' ?>
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="listado_col_datos">
                                                <div class="listado_dato_grupo">
                                                    <small>Monto Pagado</small>
                                                    <span><?= $simboloMoneda ?> <?= $montoFormateado ?></span>
                                                </div>
                                                <div class="listado_dato_grupo">
                                                    <small>Referencia</small>
                                                    <span><?= !empty($dato['referencia']) ? htmlspecialchars($dato['referencia']) : 'N/A' ?></span>
                                                </div>
                                                <div class="listado_dato_grupo">
                                                    <small>Fecha</small>
                                                    <span><?= $fechaPago ?></span>
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
                                                    <div class="detalle_card" style="width: 100%;">
                                                        <div class="detalle_card_icon"><i data-lucide="trending-up"></i></div>
                                                        <div class="detalle_card_txt">
                                                            <label>Detalles Financieros del Pago General</label>
                                                            <span>Moneda: <?= htmlspecialchars($dato['moneda']) ?></span>
                                                        </div>
                                                    </div>
                                                    <div class="detalle_card" style="width: 100%;">
                                                        <div class="detalle_card_icon"><i data-lucide="wallet"></i></div>
                                                        <div class="detalle_card_txt">
                                                            <label>Metodo de Pago</label>
                                                            <span>Metodo: <?= htmlspecialchars($dato['nombre_metodo_pago']) ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <h4 class="titulo_des">Desglose de Cuentas Abonadas:</h4>
                                                
                                                <?php if (!empty($dato['detalles'])) :
                                                    $bloquesDetalles = array_chunk($dato['detalles'], 3);
                                                    foreach ($bloquesDetalles as $bloque) : ?>
                                                        <div class="detalle_fila">
                                                            <?php foreach ($bloque as $det) : ?>
                                                                <div class="detalle_card">
                                                                    <div class="detalle_card_icon"><i data-lucide="file-text"></i></div>
                                                                    <div class="detalle_card_txt">
                                                                        <label><?= htmlspecialchars($det['concepto']) ?></label>
                                                                        <span><?= htmlspecialchars($det['atleta']) ?></span>
                                                                        <small>Abono: <b style="color:#28a745;"><?= number_format($det['monto'], 2, ',', '.') ?> <?= htmlspecialchars($det['moneda']) ?></b></small>
                                                                        <small>Tasa: <b style="color:#28a745;"><?= number_format($det['tasa'], 4, ',', '.') ?> <?= htmlspecialchars($det['moneda_tasa']) ?></b></small>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php else : ?>
                                                    <div class="detalle_fila">
                                                        <span>No hay cuentas asociadas a este pago.</span>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if (!empty($dato['vueltos'])) : ?>
                            <h4 class="titulo_des">Vueltos Registrados:</h4>
                            <div class="detalle_fila">
                                <?php foreach ($dato['vueltos'] as $v) : ?>
                                    <div class="detalle_card">
                                        <div class="detalle_card_icon"><i data-lucide="hand-coins"></i></div>
                                        <div class="detalle_card_txt">
                                            <label>Vuelto Entregado</label>
                                            <span>Monto: <b style="color:#28a745;"><?= number_format($v['monto_vuelto'], 2, ',', '.') ?> <?= htmlspecialchars($v['simbolo'] . ' ' . $v['abreviatura']) ?></b></span>
                                            <small>Método: <?= htmlspecialchars($v['nombre_metodo_vuelto'] ?? 'N/A') ?></small>
                                            <small>Fecha: <?= date('d/m/Y', strtotime($v['fecha_vuelto'])) ?></small>
                                            <?php if(!empty($v['referencia'])): ?>
                                            <small>Ref: <?= htmlspecialchars($v['referencia']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                                    </div>
                            <?php
                                endforeach;
                            endif;
                            ?>
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
                        <div class="colum colum_select_multiple">
                            <div class="caja_formulario">
                                <select name="cuenta[]" id="cuenta" class="formulario select" multiple="multiple">

                                </select>
                                <label for="cuenta" class="titulo_formulario">Cuenta por Cobrar</label>
                                <span class="mensaje" id="cuenta_span"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="colum ">
                            <div class="caja_formulario">
                                <select name="metodo" id="metodo" class="formulario select">

                                </select>
                                <label for="metodo" class="titulo_formulario">Metodo de Pago</label>
                                <span class="mensaje" id="metodo_span"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <select name="moneda" id="moneda" class="formulario select">

                                </select>
                                <label for="moneda" class="titulo_formulario">Moneda</label>
                                <span class="mensaje" id="moneda_span"></span>
                            </div>
                        </div>
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="monto" name="monto">
                                <label for="monto" class="titulo_formulario">Monto del Pago</label>
                                <span class="mensaje" id="monto_spam"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="monto_cambio" readonly>
                                <label for="monto_cambio" class="titulo_formulario">Monto al Cambio</label>
                                <span class="mensaje" id="monto_c_spam"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="date" class="formulario" id="fecha" name="fecha">
                                <label for="fecha" class="titulo_formulario">Fecha del Pago</label>
                                <span class="mensaje" id="fecha_spam"></span>
                            </div>
                        </div>
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="date" class="formulario" id="fecha_f" name="fecha_f">
                                <label for="fecha_f" class="titulo_formulario">Fecha Fin</label>
                                <span class="mensaje" id="fecha_f_spam"></span>
                            </div>
                        </div>
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="tasa" name="tasa">
                                <label for="tasa" class="titulo_formulario">Tasa de Cambio (Si aplica)</label>
                                <span class="mensaje" id="tasa_spam"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="referencia" name="referencia">
                                <label for="referencia" class="titulo_formulario">Referencia del Pago</label>
                                <span class="mensaje" id="referencia_spam"></span>
                            </div>
                        </div>
                        <div class="colum">
                            <div class="caja_formulario">
                                <label for="referencia" class="titulo_formulario">Incluir Pagos Anulados</label>
                                <label class="checkbox-container">
                                    <input type="checkbox" id="anulados" name="anulados" class="checkbox" value="1">
                                    <span class="custom-checkbox"></span>
                                </label>
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

    <section class="contenedor_modal" id="secundario_modal_contenedor">
        <div class="modal ocultar" id="secundario_modal">
            <div class="cabecera_modal">
                <h2 class="titulo_modal">Registrar Vuelto</h2>
                <a type="button" class="cerrar_modal" id="cerrar_modal_Secundario">&times;</a>
            </div>
            <div class="contenido_modal">
                <form id="f_vuelto" autocomplete="off">
                    <input type="hidden" id="codigo_pago_vuelto" name="codigo_pago">
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="monto_vuelto" name="monto_vuelto" readonly>
                                <label for="monto_vuelto" class="titulo_formulario">Monto Vuelto</label>
                            </div>
                        </div>
                        <div class="colum">
                            <div class="caja_formulario">
                                <select name="codigo_moneda" id="codigo_moneda_vuelto" class="formulario select">
                                </select>
                                <label for="codigo_moneda_vuelto" class="titulo_formulario">Moneda</label>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <select name="codigo_metodo" id="codigo_metodo_vuelto" class="formulario select">
                                </select>
                                <label for="codigo_metodo_vuelto" class="titulo_formulario">Método de Pago</label>
                            </div>
                        </div>
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="referencia_vuelto" name="referencia_vuelto">
                                <label for="referencia_vuelto" class="titulo_formulario">Referencia (Opcional)</label>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="date" class="formulario" id="fecha_vuelto" name="fecha_vuelto">
                                <label for="fecha_vuelto" class="titulo_formulario">Fecha del Vuelto</label>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="colum">
                            <button type="button" class="btn btn_azul" id="proceso_vuelto">Guardar Vuelto</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <script src="js/main.js"></script>
    <script src="js/pagos.js"></script>
    <?php include('complementos/mensajeError.php'); ?>
</body>

</html>