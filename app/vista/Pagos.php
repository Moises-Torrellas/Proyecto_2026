<?php
if (isset($solo_lista) && $solo_lista === true):
    if (empty($registro)): ?>
        <div class="listado_vacio">
            <p>No se encontraron registros de pagos</p>
        </div>
        <?php else:
        foreach ($registro as $dato):
            $fechaPago = date('d/m/Y', strtotime($dato['fecha_pago']));
            $simboloMoneda = htmlspecialchars($dato['simbolo'] . ' ' . $dato['abre']);
            $montoFormateado = number_format($dato['monto_pagado'], 2, ',', '.');

            // LÓGICA DE ANULADO ADAPTADA
            // Evaluamos si el estatus es diferente de 1 (lo que significa que está anulado)
            $esAnulado = ((int)$dato['estatus']) !== 1;
            $estiloGris = $esAnulado ? 'style="filter: grayscale(1); opacity: 0.6; background-color: #f4f4f4;"' : '';

            if ($esAnulado) {
                $estatusHTML = '<span class="estatus_r">Anulado</span>';
                $botonesAccion = ''; // Si ya está anulado, no se muestran acciones
            } else {
                $estatusHTML = '<span class="estatus_v">Realizado</span>';

                // Si está activo y tiene permiso, construimos el botón de anulación
                $botonesAccion = '';
                if ($permisos['eliminar']) {
                    $botonesAccion .= '<button id="cbt_r" class="btn_t cbt_r" onclick="eliminar(' . $dato['id_pago'] . ')" data-tippy-content="Anular Transacción">';
                    $botonesAccion .= '<i class="fi fi-sr-cross-circle"></i>';
                    $botonesAccion .= '</button>';
                }
            }
        ?>
            <div id="registro" class="listado_contenedor_grupal" <?= $estiloGris ?>>
                <div class="listado_item" onclick="toggleDetalles(this)">

                    <div class="listado_col_principal">
                        <div class="listado_avatar_null" style="background-color: var(--verde-suave, #22c55e20); color: var(--verde, #22c55e);">
                            <i class="icon_con" data-lucide="banknote"></i>
                        </div>
                        <div class="listado_info_base">
                            <span class="listado_titulo">
                                <?= htmlspecialchars($dato['concepto_pago']) ?>
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
                            <div class="detalle_card">
                                <div class="detalle_card_icon"><i data-lucide="user"></i></div>
                                <div class="detalle_card_txt">
                                    <label>Atleta Asociado</label>
                                    <span><?= htmlspecialchars($dato['nombre_atleta']) ?> <?= htmlspecialchars($dato['nombre_apellido']) ?></span>
                                </div>
                            </div>

                            <div class="detalle_card">
                                <div class="detalle_card_icon"><i data-lucide="trending-up"></i></div>
                                <div class="detalle_card_txt">
                                    <label>Moneda e Historial de Tasa</label>
                                    <span>Moneda: <?= htmlspecialchars($dato['moneda']) ?></span>
                                    <small>Tasa congelada: 1 USD = <?= number_format($dato['tasa_cambio'], 2, ',', '.') ?> <?= htmlspecialchars($dato['abre']) ?></small>
                                </div>
                            </div>
                        </div>
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
                            <button class="btn btn_azul" id="incluir">Nuevo Pago</button>

                            <button class="btn btn_verde" id="generar">Generar Reporte</button>
                        </div>
                    </div>
                    <div class="contenedor_resultados">
                        <div id="resultadoconsulta" class="resultadoconsulta">
                            <?php
                            if (empty($registro)): ?>
                                <div class="listado_vacio">
                                    <p>No se encontraron registros de pagos</p>
                                </div>
                                <?php else:
                                foreach ($registro as $dato):
                                    $fechaPago = date('d/m/Y', strtotime($dato['fecha_pago']));
                                    $simboloMoneda = htmlspecialchars($dato['simbolo'] . ' ' . $dato['abre']);
                                    $montoFormateado = number_format($dato['monto_pagado'], 2, ',', '.');

                                    // LÓGICA DE ANULADO ADAPTADA
                                    // Evaluamos si el estatus es diferente de 1 (lo que significa que está anulado)
                                    $esAnulado = ((int)$dato['estatus']) !== 1;
                                    $estiloGris = $esAnulado ? 'style="filter: grayscale(1); opacity: 0.6; background-color: #f4f4f4;"' : '';

                                    if ($esAnulado) {
                                        $estatusHTML = '<span class="estatus_r">Anulado</span>';
                                        $botonesAccion = ''; // Si ya está anulado, no se muestran acciones
                                    } else {
                                        $estatusHTML = '<span class="estatus_v">Realizado</span>';

                                        // Si está activo y tiene permiso, construimos el botón de anulación
                                        $botonesAccion = '';
                                        if ($permisos['eliminar']) {
                                            $botonesAccion .= '<button id="cbt_r" class="btn_t cbt_r" onclick="eliminar(' . $dato['id_pago'] . ')" data-tippy-content="Anular Transacción">';
                                            $botonesAccion .= '<i class="fi fi-sr-cross-circle"></i>';
                                            $botonesAccion .= '</button>';
                                        }
                                    }
                                ?>
                                    <div id="registro" class="listado_contenedor_grupal" <?= $estiloGris ?>>
                                        <div class="listado_item" onclick="toggleDetalles(this)">

                                            <div class="listado_col_principal">
                                                <div class="listado_avatar_null" style="background-color: var(--verde-suave, #22c55e20); color: var(--verde, #22c55e);">
                                                    <i class="icon_con" data-lucide="banknote"></i>
                                                </div>
                                                <div class="listado_info_base">
                                                    <span class="listado_titulo">
                                                        <?= htmlspecialchars($dato['concepto_pago']) ?>
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
                                                    <div class="detalle_card">
                                                        <div class="detalle_card_icon"><i data-lucide="user"></i></div>
                                                        <div class="detalle_card_txt">
                                                            <label>Atleta Asociado</label>
                                                            <span><?= htmlspecialchars($dato['nombre_atleta']) ?> <?= htmlspecialchars($dato['nombre_apellido']) ?></span>
                                                        </div>
                                                    </div>

                                                    <div class="detalle_card">
                                                        <div class="detalle_card_icon"><i data-lucide="trending-up"></i></div>
                                                        <div class="detalle_card_txt">
                                                            <label>Moneda e Historial de Tasa</label>
                                                            <span>Moneda: <?= htmlspecialchars($dato['moneda']) ?></span>
                                                            <small>Tasa congelada: 1 USD = <?= number_format($dato['tasa_cambio'], 2, ',', '.') ?> <?= htmlspecialchars($dato['abre']) ?></small>
                                                        </div>
                                                    </div>
                                                </div>
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
                        <div class="colum">
                            <div class="caja_formulario">
                                <select name="cuenta" id="cuenta" class="formulario select">

                                </select>
                                <label for="cuenta" class="titulo_formulario">Cuenta por Cobrar</label>
                                <span class="mensaje" id="cuenta_span"></span>
                            </div>
                        </div>
                        <div class="colum">
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
                                <input type="date" class="formulario" id="fecha" name="fecha">
                                <label for="fecha" class="titulo_formulario">Fecha del Pago</label>
                                <span class="mensaje" id="fecha_spam"></span>
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
    <script src="js/pagos.js"></script>
</body>

</html>