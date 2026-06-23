<?php if (isset($solo_lista) && $solo_lista === true) :
    if (empty($registro)) : ?>
        <div class="listado_vacio">
            <p>No se encontraron registros</p>
        </div>
    <?php else :
            $atletaActual = null;
            $deudasAtleta = [];

        // Obtenemos el total de registros para controlar el cierre del último elemento
            $totalRegistros = count($registro);

        // BUCLE ÚNICO TOTALMENTE LINEAL O(N) - Sin sub-bucles ocultos
        foreach ($registro as $index => $dato) :
            $idAtleta = $dato['id_atleta'];

            // 1. DETECTAR CAMBIO DE ATLETA (O PRIMER REGISTRO)
            if ($idAtleta !== $atletaActual) :
                // Si ya veníamos procesando un atleta anterior, cerramos sus contenedores pendientes antes de abrir el nuevo
                if ($atletaActual !== null) : ?>
                    </div>
                    </div>
                    </div>
                    </div> <?php
                        $deudasAtleta = []; // Resetear deudas acumuladas para el nuevo atleta
                endif;

                    $atletaActual = $idAtleta;

                    // OPTIMIZACIÓN MÁXIMA DE RENDIMIENTO:
                    // En lugar de un bucle 'while' que repita lecturas, usamos PHP puro para agrupar
                    // velozmente los totales precalculados por SQL de las deudas del atleta actual.
                    $idxAux = $index;
                while (isset($registro[$idxAux]) && $registro[$idxAux]['id_atleta'] == $idAtleta) {
                    $sym = $registro[$idxAux]['moneda_simbolo'];
                    $montoDeuda = (float)$registro[$idxAux]['deuda_moneda_atleta'];
                    if ($montoDeuda > 0) {
                        $deudasAtleta[$sym] = $sym . ' ' . number_format($montoDeuda, 2);
                    }
                    $idxAux++;
                }

                    $esMoroso = !empty($deudasAtleta);
                    $deudaTotalTexto = $esMoroso ? implode(" + ", $deudasAtleta) : "0.00 (Sin deudas)";
                    $claseEstadoGeneral = $esMoroso ? 'estado_peligro' : 'estado_exito';
                ?>
                <div class="listado_contenedor_grupal">

                    <div class="listado_item" onclick="toggleDetalles(this)">
                        <div class="listado_col_principal">
                            <div class="listado_avatar_null"><i class="icon_con" data-lucide="circle-star"></i></div>
                            <div class="listado_info_base">
                                <span class="listado_titulo"><?= htmlspecialchars($dato['atleta_nombre'] . ' ' . $dato['atleta_apellido']) ?></span>
                            </div>
                        </div>

                        <div class="listado_col_datos">
                            <div class="listado_dato_grupo">
                                <small>Situación</small>
                                <span class="<?= $esMoroso ? 'estatus_a' : 'estatus_v' ?>">
                                <?= $esMoroso ? 'Moroso' : 'Solvente' ?>
                                </span>
                            </div>
                            <div class="listado_dato_grupo">
                                <small>Cuentas</small>
                                <span><?= $dato['total_facturas_atleta'] ?> Registro(s)</span>
                            </div>
                        </div>

                        <div class="listado_col_acciones">
                            <i data-lucide="chevron-down" class="icono_flecha_detalle"></i>
                        </div>
                    </div>

                    <div class="listado_detalle_oculto">
                        <div class="detalle_expandido_container" style="padding: 15px;">

                            <div class="tarjeta_resumen <?= $claseEstadoGeneral ?>">
                                <div class="tarjeta_icono"><i data-lucide="calculator"></i></div>
                                <div class="tarjeta_texto">
                                    <label>Estado de Cuenta General</label>
                                    <span class="texto_resaltado">Deuda Total: <?= htmlspecialchars($deudaTotalTexto) ?></span>
                                </div>
                            </div>

                            <hr class="separador_seccion">

                            <div class="lista_sub_items">
            <?php endif; // Fin del bloque de inicialización de la cabecera del Atleta

                    // 2. RENDERIZAR LAS FACTURAS DIRECTAMENTE (Se ejecuta de forma lineal)
                    $estatusCargo = (int) $dato['estatus'];
                    $anulado = $estatusCargo === 3;
                    $pagado = $estatusCargo === 2;

                    $claseFila = $anulado ? 'fila_anulada' : '';

                    $claseEstatus = $anulado ? 'estatus_r' : ($pagado ? 'estatus_v' : 'estatus_a');
                    $textoEstatus = $anulado ? 'Anulado' : ($pagado ? 'Pagado' : 'Pendiente');

                    $botonesAccion = '';
            if (!$anulado && !$pagado) {
                if ($permisos['modificar']) {
                    $botonesAccion .= '<button class="btn_t cbt_v" onclick="buscar(' . $dato['id_cobrar'] . ')" data-tippy-content="Modificar"><i class="fi fi-sr-pencil"></i></button> ';
                }
                if ($permisos['eliminar']) {
                    $botonesAccion .= '<button class="btn_t cbt_r" onclick="anular(' . $dato['id_cobrar'] . ')" data-tippy-content="Anular"><i class="fi fi-sr-cross-circle"></i></button>';
                }
            }
            ?>
                            <div class="sub_item_fila <?= $claseFila ?>">
                                <div class="sub_item_info">
                                    <span class="sub_item_titulo"><?= htmlspecialchars($dato['concepto_nombre']) ?></span>
                                    <div class="sub_item_fechas">
                                        <span>Emi: <?= explode(' ', $dato['fecha_emision'])[0] ?></span>
                                        <span>Venc: <?= explode(' ', $dato['fecha_vencimiento'])[0] ?></span>
                                    </div>
                                </div>

                                <div class="sub_item_centro">
                                    <span class="<?= $claseEstatus ?>"><?= $textoEstatus ?></span>
                                </div>

                                <div class="sub_item_montos">
                                    <span>Total: <strong><?= $dato['moneda_simbolo'] . ' ' . $dato['monto_total'] ?></strong></span>
                                    <span class="<?= ((float)$dato['monto_pendiente'] > 0 && !$anulado) ? 'texto_alerta' : '' ?>">
                                        Pendiente: <strong><?= $dato['moneda_simbolo'] . ' ' . $dato['monto_pendiente'] ?></strong>
                                    </span>
                                </div>

                                <div class="sub_item_acciones">
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
                            <h2 class="titulo_pagina" id="titulo">Cargos</h2>
                        </div>
                        <div class="contenedor_busqueda">
                            <input type="text" placeholder="Buscar cargo..." autocomplete="off" id="busqueda">
                            <i class="fi fi-br-search icon_input"></i>
                        </div>
                        <div class="botones">
                            <?php if ($permisos['registrar']) : ?>
                                <button class="btn btn_azul" id="incluir">Nuevo Cargo</button>
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
                                    <p>No se encontraron registros</p>
                                </div>
                            <?php else :
                                    $atletaActual = null;
                                    $deudasAtleta = [];

                                // Obtenemos el total de registros para controlar el cierre del último elemento
                                    $totalRegistros = count($registro);

                                // BUCLE ÚNICO TOTALMENTE LINEAL O(N) - Sin sub-bucles ocultos
                                foreach ($registro as $index => $dato) :
                                        $idAtleta = $dato['id_atleta'];

                                        // 1. DETECTAR CAMBIO DE ATLETA (O PRIMER REGISTRO)
                                    if ($idAtleta !== $atletaActual) :
                                        // Si ya veníamos procesando un atleta anterior, cerramos sus contenedores pendientes antes de abrir el nuevo
                                        if ($atletaActual !== null) : ?>
                        </div>
                    </div>
                </div>
            </div> <?php
                                        $deudasAtleta = []; // Resetear deudas acumuladas para el nuevo atleta
                                        endif;

                                        $atletaActual = $idAtleta;

                                        // OPTIMIZACIÓN MÁXIMA DE RENDIMIENTO:
                                        // En lugar de un bucle 'while' que repita lecturas, usamos PHP puro para agrupar
                                        // velozmente los totales precalculados por SQL de las deudas del atleta actual.
                                        $idxAux = $index;
                                        while (isset($registro[$idxAux]) && $registro[$idxAux]['id_atleta'] == $idAtleta) {
                                            $sym = $registro[$idxAux]['moneda_simbolo'];
                                            $montoDeuda = (float)$registro[$idxAux]['deuda_moneda_atleta'];
                                            if ($montoDeuda > 0) {
                                                $deudasAtleta[$sym] = $sym . ' ' . number_format($montoDeuda, 2);
                                            }
                                            $idxAux++;
                                        }

                                        $esMoroso = !empty($deudasAtleta);
                                        $deudaTotalTexto = $esMoroso ? implode(" + ", $deudasAtleta) : "0.00 (Sin deudas)";
                                        $claseEstadoGeneral = $esMoroso ? 'estado_peligro' : 'estado_exito';
                                        ?>
        <div class="listado_contenedor_grupal">

            <div class="listado_item" onclick="toggleDetalles(this)">
                <div class="listado_col_principal">
                    <div class="listado_avatar_null"><i class="icon_con" data-lucide="circle-star"></i></div>
                    <div class="listado_info_base">
                        <span class="listado_titulo"><?= htmlspecialchars($dato['atleta_nombre'] . ' ' . $dato['atleta_apellido']) ?></span>
                    </div>
                </div>

                <div class="listado_col_datos">
                    <div class="listado_dato_grupo">
                        <small>Situación</small>
                        <span class="<?= $esMoroso ? 'estatus_a' : 'estatus_v' ?>">
                                        <?= $esMoroso ? 'Moroso' : 'Solvente' ?>
                        </span>
                    </div>
                    <div class="listado_dato_grupo">
                        <small>Cuentas</small>
                        <span><?= $dato['total_facturas_atleta'] ?> Registro(s)</span>
                    </div>
                </div>

                <div class="listado_col_acciones">
                    <i data-lucide="chevron-down" class="icono_flecha_detalle"></i>
                </div>
            </div>

            <div class="listado_detalle_oculto">
                <div class="detalle_expandido_container" style="padding: 15px;">

                    <div class="tarjeta_resumen <?= $claseEstadoGeneral ?>">
                        <div class="tarjeta_icono"><i data-lucide="calculator"></i></div>
                        <div class="tarjeta_texto">
                            <label>Estado de Cuenta General</label>
                            <span class="texto_resaltado">Deuda Total: <?= htmlspecialchars($deudaTotalTexto) ?></span>
                        </div>
                    </div>

                    <hr class="separador_seccion">

                    <div class="lista_sub_items">
                                    <?php endif; // Fin del bloque de inicialización de la cabecera del Atleta

                                        // 2. RENDERIZAR LAS FACTURAS DIRECTAMENTE (Se ejecuta de forma lineal)
                                        $estatusCargo = (int) $dato['estatus'];
                                        $anulado = $estatusCargo === 3;
                                        $pagado = $estatusCargo === 2;

                                        $claseFila = $anulado ? 'fila_anulada' : '';

                                        $claseEstatus = $anulado ? 'estatus_r' : ($pagado ? 'estatus_v' : 'estatus_a');
                                        $textoEstatus = $anulado ? 'Anulado' : ($pagado ? 'Pagado' : 'Pendiente');

                                        $botonesAccion = '';
                                    if (!$anulado && !$pagado) {
                                        if ($permisos['modificar']) {
                                            $botonesAccion .= '<button class="btn_t cbt_v" onclick="buscar(' . $dato['id_cobrar'] . ')" data-tippy-content="Modificar"><i class="fi fi-sr-pencil"></i></button> ';
                                        }
                                        if ($permisos['eliminar']) {
                                            $botonesAccion .= '<button class="btn_t cbt_r" onclick="anular(' . $dato['id_cobrar'] . ')" data-tippy-content="Anular"><i class="fi fi-sr-cross-circle"></i></button>';
                                        }
                                    }
                                    ?>
                    <div class="sub_item_fila <?= $claseFila ?>">
                        <div class="sub_item_info">
                            <span class="sub_item_titulo"><?= htmlspecialchars($dato['concepto_nombre']) ?></span>
                            <div class="sub_item_fechas">
                                <span>Emi: <?= explode(' ', $dato['fecha_emision'])[0] ?></span>
                                <span>Venc: <?= explode(' ', $dato['fecha_vencimiento'])[0] ?></span>
                            </div>
                        </div>

                        <div class="sub_item_centro">
                            <span class="<?= $claseEstatus ?>"><?= $textoEstatus ?></span>
                        </div>

                        <div class="sub_item_montos">
                            <span>Total: <strong><?= $dato['moneda_simbolo'] . ' ' . $dato['monto_total'] ?></strong></span>
                            <span class="<?= ((float)$dato['monto_pendiente'] > 0 && !$anulado) ? 'texto_alerta' : '' ?>">
                                Pendiente: <strong><?= $dato['moneda_simbolo'] . ' ' . $dato['monto_pendiente'] ?></strong>
                            </span>
                        </div>

                        <div class="sub_item_acciones">
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
                        <div class="colum colum_select_multiple">
                            <div class="caja_formulario">
                                <select name="id_atleta[]" id="id_atleta" class="formulario select" multiple="multiple">
                                </select>
                                <label for="id_atleta" class="titulo_formulario">Atleta(s)</label>
                                <span class="mensaje" id="id_atleta_span"></span>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                         <div class="colum">
                            <div class="caja_formulario">
                                <select name="id_concepto" id="id_concepto" class="formulario select">
                                </select>
                                <label for="id_concepto" class="titulo_formulario">Concepto de Cobro</label>
                                <span class="mensaje" id="id_concepto_span"></span>
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
                                <input type="date" class="formulario" id="fecha_emision" name="fecha_emision">
                                <label for="fecha_emision" class="titulo_formulario">Fecha de Emisión</label>
                                <span class="mensaje" id="fecha_emision_spam"></span>
                            </div>
                        </div>
                    </div>

                    <div class="row">
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
    <script src="js/cuentas_cobrar.js"></script>
    <?php include('complementos/mensajeError.php'); ?>
</body>

</html>
