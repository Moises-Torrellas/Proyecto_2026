<?php

if (isset($solo_lista) && $solo_lista === true) :
    if (empty($registro)) : ?>
        <div class="listado_vacio">
            <p>No se encontraron registros</p>
        </div>
    <?php else :
        foreach ($registro as $dato) :
            $icon = ($dato['estatus'] == 1) ? 'fi-sr-unlock' : 'fi-sr-lock';
            $color = ($dato['estatus'] == 1) ? 'cbt_g' : 'cbt_a';
            ?>
            <div class="listado_contenedor_grupal">
                <div class="listado_item">
                    <div class="listado_col_datos">
                        <div class="listado_dato_grupo">
                            <small>Nombre</small>
                            <span><?= htmlspecialchars($dato['nombre']) ?></span>
                        </div>
                        <div class="listado_dato_grupo">
                            <small>Abreviatura</small>
                            <span style="font-weight: bold; color: #2ec135;"><?= htmlspecialchars($dato['abreviatura']) ?></span>
                        </div>
                        <div class="listado_dato_grupo">
                            <small>Simbolo</small>
                            <span><?= htmlspecialchars($dato['simbolo']) ?></span>
                        </div>
                    </div>
                    <div class="listado_col_acciones">
                        <div style="display:flex; gap:5px;">
                        <?php if ($permisos['modificar']) : ?>
                                <button class="btn_t cbt_v" onclick="buscar(<?= $dato['id_moneda'] ?>)" data-tippy-content="Modificar"><i class="fi fi-sr-pencil"></i></button>
                        <?php endif; ?>
                        <?php if ($permisos['eliminar']) : ?>
                                <button class="btn_t cbt_r" onclick="eliminar(<?= $dato['id_moneda'] ?>)" data-tippy-content="Eliminar"><i class="fi fi-sr-trash-xmark"></i></button>
                        <?php endif; ?>
                        <?php if ($permisos['otros']) : ?>
                                <button class="btn_t <?= $color ?>" onclick="bloquear(<?= $dato['id_moneda'] ?>, <?= $dato['estatus'] ?>, this)" data-tippy-content="Bloquear"><i class="fi <?= $icon ?>"></i></button>
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
    <title>Monedas</title>
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
                            <h2 class="titulo_pagina" id="titulo">Monedas</h2>
                        </div>
                        <div class="contenedor_busqueda">
                            <input type="text" placeholder="Buscar..." autocomplete="off" id="busqueda">
                            <i class="fi fi-br-search icon_input"></i>
                        </div>
                        <div class="botones">
                            <?php if ($permisos['registrar']) : ?>
                                <button class="btn btn_azul" id="incluir">Nueva Moneda</button>
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
                                foreach ($registro as $dato) :
                                    $icon = ($dato['estatus'] == 1) ? 'fi-sr-unlock' : 'fi-sr-lock';
                                    $color = ($dato['estatus'] == 1) ? 'cbt_g' : 'cbt_a';
                                    ?>
                                    <div class="listado_contenedor_grupal">
                                        <div class="listado_item">
                                            <div class="listado_col_datos">
                                                <div class="listado_dato_grupo">
                                                    <small>Nombre</small>
                                                    <span><?= htmlspecialchars($dato['nombre']) ?></span>
                                                </div>
                                                <div class="listado_dato_grupo">
                                                    <small>Abreviatura</small>
                                                    <span style="font-weight: bold; color: #2ec135;"><?= htmlspecialchars($dato['abreviatura']) ?></span>
                                                </div>
                                                <div class="listado_dato_grupo">
                                                    <small>Simbolo</small>
                                                    <span><?= htmlspecialchars($dato['simbolo']) ?></span>
                                                </div>
                                            </div>
                                            <div class="listado_col_acciones">
                                                <div style="display:flex; gap:5px;">
                                                <?php if ($permisos['modificar']) : ?>
                                                        <button class="btn_t cbt_v" onclick="buscar(<?= $dato['id_moneda'] ?>)" data-tippy-content="Modificar"><i class="fi fi-sr-pencil"></i></button>
                                                <?php endif; ?>
                                                <?php if ($permisos['eliminar']) : ?>
                                                        <button class="btn_t cbt_r" onclick="eliminar(<?= $dato['id_moneda'] ?>)" data-tippy-content="Eliminar"><i class="fi fi-sr-trash-xmark"></i></button>
                                                <?php endif; ?>
                                                <?php if ($permisos['otros']) : ?>
                                                        <button class="btn_t <?= $color ?>" onclick="bloquear(<?= $dato['id_moneda'] ?>, <?= $dato['estatus'] ?>, this)" data-tippy-content="Bloquear"><i class="fi <?= $icon ?>"></i></button>
                                                <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                endforeach;
                            endif; ?>
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
                                <input type="text" class="formulario" id="abreviatura" name="abreviatura">
                                <label for="abreviatura" class="titulo_formulario">Abreviatura</label>
                                <span class="mensaje" id="abreviatura_spam"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="simbolo" name="simbolo">
                                <label for="simbolo" class="titulo_formulario">Simbolo</label>
                                <span class="mensaje" id="simbolo_spam"></span>
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
    <script src="js/monedas.js"></script>
</body>

</html>
