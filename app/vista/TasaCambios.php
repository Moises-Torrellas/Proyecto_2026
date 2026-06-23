<?php if (isset($solo_lista) && $solo_lista === true) : ?>
    <?php if (empty($registro)) : ?>
        <div class="listado_vacio">
            <p>No se encontraron registros de tasas de cambio</p>
        </div>
    <?php else : ?>
        <?php foreach ($registro as $dato) : ?>
            <div class="listado_contenedor_grupal">
                <div class="listado_item">
                    <div class="listado_col_datos">
                        <div class="listado_dato_grupo">
                            <small>Fecha</small>
                            <span><?= explode(' ', $dato['fecha'])[0] ?></span>
                        </div>
                        <div class="listado_dato_grupo">
                            <small>Moneda</small>
                            <span><?= htmlspecialchars($dato['moneda']) ?></span>
                        </div>
                        <div class="listado_dato_grupo">
                            <small>Tasa</small>
                            <span style="font-weight: bold; color: #2ec135;"><?= htmlspecialchars($dato['valor_tasa']) ?> <?= htmlspecialchars($dato['simbolo']) ?></span>
                        </div>
                    </div>
                    <div class="listado_col_acciones">
                        <div style="display:flex; gap:5px;">
                            <?php if ($permisos['eliminar']) : ?>
                                <button class="btn_t cbt_r" onclick="eliminar(<?= $dato['codigo_tasa'] ?>)" data-tippy-content="Eliminar"><i class="fi fi-sr-trash-xmark"></i></button>
                            <?php endif; ?>
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
    <title>Tasa de Cambios</title>
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
                            <h2 class="titulo_pagina" id="titulo">Tasas de Cambio</h2>
                        </div>
                        <div class="botones">
                            <?php if ($permisos['registrar']) : ?>
                                <button class="btn btn_verde" id="sincronizar_btn">Sincronizar Monto</button>
                                <button class="btn btn_azul" id="actualizar_btn">Actualizar Monto Manual</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="contenedor_resultados">
                        <div id="resultadoconsulta" class="resultadoconsulta">
                            <?php if (empty($registro)) : ?>
                                <div class="listado_vacio">
                                    <p>No se encontraron registros de tasas de cambio</p>
                                </div>
                            <?php else : ?>
                                <?php foreach ($registro as $dato) : ?>
                                    <div class="listado_contenedor_grupal">
                                        <div class="listado_item">
                                            <div class="listado_col_datos">
                                                <div class="listado_dato_grupo">
                                                    <small>Fecha</small>
                                                    <span><?= explode(' ', $dato['fecha'])[0] ?></span>
                                                </div>
                                                <div class="listado_dato_grupo">
                                                    <small>Moneda</small>
                                                    <span><?= htmlspecialchars($dato['moneda']) ?></span>
                                                </div>
                                                <div class="listado_dato_grupo">
                                                    <small>Tasa</small>
                                                    <span style="font-weight: bold; color: #2ec135;"><?= htmlspecialchars($dato['valor_tasa']) ?> <?= htmlspecialchars($dato['simbolo']) ?></span>
                                                </div>
                                            </div>
                                            <div class="listado_col_acciones">
                                                <div style="display:flex; gap:5px;">
                                                    <?php if ($permisos['eliminar']) : ?>
                                                        <button class="btn_t cbt_r" onclick="eliminar(<?= $dato['codigo_tasa'] ?>)" data-tippy-content="Eliminar"><i class="fi fi-sr-trash-xmark"></i></button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php include('complementos/botonera.php'); ?>
            </div>
        </div>
    </section>

    <section class="contenedor_modal" id="contenedor_modal">
        <div class="modal ocultar" id="modal">
            <div class="cabecera_modal">
                <h2 class="titulo_modal" id="titulo_modal">Gestionar Tasa</h2>
                <a type="button" class="cerrar_modal" id="cerrar_modal">&times;</a>
            </div>
            <div class="contenido_modal">
                <form id="f" autocomplete="off">
                    <input type="hidden" id="accion_modal" name="accion">

                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <select name="codigo_moneda" id="codigo_moneda" class="formulario select">
                                </select>
                                <label for="codigo_moneda" class="titulo_formulario">Moneda a Convertir (ej: USD)</label>
                                <span class="mensaje" id="codigo_moneda_span"></span>
                            </div>
                        </div>
                    </div>

                    <div class="row" id="div_monto_manual" style="display:none;">
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="tasa_bolivares" name="tasa_bolivares" placeholder="Ej: 36.50">
                                <label for="tasa_bolivares" class="titulo_formulario">Tasa en Bolívares</label>
                                <span class="mensaje" id="tasa_bolivares_span"></span>
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
    <script src="js/tasa_cambios.js"></script>
    <?php include('complementos/mensajeError.php'); ?>
</body>

</html>