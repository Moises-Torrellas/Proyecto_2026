<?php if (isset($solo_lista) && $solo_lista === true) :
    if (empty($registro)) : ?>
        <div class="listado_vacio">
            <p>No se encontraron registros</p>
        </div>
        <?php else :
        foreach ($registro as $dato) :
            $icon = ($dato['estatus'] == 1) ? 'fi-sr-unlock' : 'fi-sr-lock';
            $color = ($dato['estatus'] == 1) ? 'cbt_g' : 'cbt_a';
            $frecuencia = match ($dato['frecuencia']) {
                'L' => 'Libre',
                'M' => 'Mensual',
                'A' => 'Anual',
                'U' => 'Unico'
            };
            $dias = ($dato['dias_gracia'] == 0) ? 'No Aplica' : $dato['dias_gracia'];
        ?>
            <div class="listado_contenedor_grupal">
                <div class="listado_item" onclick="toggleDetalles(this)">
                    <div class="listado_col_datos">
                        <div class="listado_dato_grupo">
                            <small>Concepto de Cuenta</small>
                            <span><?= htmlspecialchars($dato['nombre']) ?></span>
                        </div>
                        <div class="listado_dato_grupo">
                            <small>Monto</small>
                            <span><?= $dato['monto'] ?></span>
                        </div>
                        <div class="listado_dato_grupo">
                            <small>Frecuencia</small>
                            <span><?= $frecuencia ?></span>
                        </div>
                        <div class="listado_dato_grupo">
                            <small>Dias Limite de Pago</small>
                            <span><?= $dias ?></span>
                        </div>
                    </div>

                    <div class="listado_col_acciones">
                        <div onclick="event.stopPropagation();" style="display:flex; gap:5px;">
                            <?php if ($permisos['modificar']) : ?>
                                <button id="cbt_v" class="btn_t cbt_v" onclick="buscar(<?= $dato['codigo_concepto'] ?>)" data-tippy-content="Modificar"><i class="fi fi-sr-pencil"></i></button>
                            <?php endif; ?>
                            <?php if ($permisos['eliminar']) : ?>
                                <button id="cbt_r" class="btn_t cbt_r" onclick="eliminar(<?= $dato['codigo_concepto'] ?>)" data-tippy-content="Eliminar"><i class="fi fi-sr-trash-xmark"></i></button>
                            <?php endif; ?>
                            <?php if ($permisos['otros']) : ?>
                                <button class="btn_t <?= $color ?>" onclick="cambiarEstatus(<?= $dato['codigo_concepto'] ?>, <?= $dato['estatus'] ?>, this)" data-tippy-content="Bloquear"><i class="fi <?= $icon ?>"></i></button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    <?php exit(); ?>
<?php endif; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include('complementos/head.php'); ?>
    <title>Conceptos</title>
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
                            <h2 class="titulo_pagina" id="titulo">Conceptos de Cargos</h2>
                        </div>
                        <div class="contenedor_busqueda">
                            <input type="text" placeholder="Buscar..." autocomplete="off" id="busqueda">
                            <i class="fi fi-br-search icon_input"></i>
                        </div>
                        <div class="botones">
                            <button class="btn btn_azul" id="incluir">Nuevo Concepto</button>

                            <button class="btn btn_verde" id="generar">Generar Reporte</button>
                        </div>
                    </div>
                    <div class="contenedor_resultados">
                        <div id="resultadoconsulta" class="resultadoconsulta">
                            <?php if (empty($registro)) : ?>
                                <div class="listado_vacio">
                                    <p>No se encontraron registros</p>
                                </div>
                                <?php else :
                                foreach ($registro as $dato) :
                                    $icon = ($dato['estatus'] == 1) ? 'fi-sr-unlock' : 'fi-sr-lock';
                                    $color = ($dato['estatus'] == 1) ? 'cbt_g' : 'cbt_a';
                                    $frecuencia = match ($dato['frecuencia']) {
                                        'L' => 'Libre',
                                        'M' => 'Mensual',
                                        'A' => 'Anual',
                                        'U' => 'Unico'
                                    };
                                    $dias = ($dato['dias_gracia'] == 0) ? 'No Aplica' : $dato['dias_gracia'];
                                ?>
                                    <div class="listado_contenedor_grupal">
                                        <div class="listado_item" onclick="toggleDetalles(this)">
                                            <div class="listado_col_datos">
                                                <div class="listado_dato_grupo">
                                                    <small>Concepto de Cuenta</small>
                                                    <span><?= htmlspecialchars($dato['nombre']) ?></span>
                                                </div>
                                                <div class="listado_dato_grupo">
                                                    <small>Monto</small>
                                                    <span><?= $dato['monto'] ?></span>
                                                </div>
                                                <div class="listado_dato_grupo">
                                                    <small>Frecuencia</small>
                                                    <span><?= $frecuencia ?></span>
                                                </div>
                                                <div class="listado_dato_grupo">
                                                    <small>Dias Limite de Pago</small>
                                                    <span><?= $dias ?></span>
                                                </div>
                                            </div>

                                            <div class="listado_col_acciones">
                                                <div onclick="event.stopPropagation();" style="display:flex; gap:5px;">
                                                    <?php if ($permisos['modificar']) : ?>
                                                        <button id="cbt_v" class="btn_t cbt_v" onclick="buscar(<?= $dato['codigo_concepto'] ?>)" data-tippy-content="Modificar"><i class="fi fi-sr-pencil"></i></button>
                                                    <?php endif; ?>
                                                    <?php if ($permisos['eliminar']) : ?>
                                                        <button id="cbt_r" class="btn_t cbt_r" onclick="eliminar(<?= $dato['codigo_concepto'] ?>)" data-tippy-content="Eliminar"><i class="fi fi-sr-trash-xmark"></i></button>
                                                    <?php endif; ?>
                                                    <?php if ($permisos['otros']) : ?>
                                                        <button class="btn_t <?= $color ?>" onclick="cambiarEstatus(<?= $dato['codigo_concepto'] ?>, <?= $dato['estatus'] ?>, this)" data-tippy-content="Bloquear"><i class="fi <?= $icon ?>"></i></button>
                                                    <?php endif; ?>
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
                <h2 class="titulo_modal" id="titulo_modal">Registrar Concepto</h2>
                <a type="button" class="cerrar_modal" id="cerrar_modal">&times;</a>
            </div>
            <div class="contenido_modal">
                <form id="f" autocomplete="off">
                    <input type="hidden" id="id" name="id">
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="nombre" name="nombre">
                                <label for="nombre" class="titulo_formulario">Nombre</label>
                                <span class="mensaje" id="nombre_spam"></span>
                            </div>
                        </div>
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="monto" name="monto">
                                <label for="monto" class="titulo_formulario">Monto</label>
                                <span class="mensaje" id="monto_spam"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <select name="frecuencia" id="frecuencia" class="formulario select">
                                    <option value="L" selected>Libre</option>
                                    <option value="M">Mensual</option>
                                    <option value="A">Anual</option>
                                    <option value="U">Unico</option>
                                </select>
                                <label for="frecuencia" class="titulo_formulario">Frecuencia</label>
                                <span class="mensaje" id="frecuencia_span"></span>
                            </div>
                        </div>
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="dias" name="dias">
                                <label for="dias" class="titulo_formulario">Dias Limites de Pago</label>
                                <span class="mensaje" id="dias_spam"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="colum">
                            <button type="button" class="btn btn_azul" id="proceso">Registra Concepto</button>
                            <button type="button" class="btn btn_verde" id="limpiar">Limpiar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
    <script src="js/main.js"></script>
    <script src="js/Concepto.js"></script>
    <?php include('complementos/mensajeError.php'); ?>
</body>

</html>