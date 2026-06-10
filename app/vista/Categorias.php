<?php if (isset($solo_lista) && $solo_lista === true) :
    if (empty($registro)) : ?>
        <div class="listado_vacio">
            <p>No se encontraron registros</p>
        </div>
    <?php else :
        foreach ($registro as $dato) : ?>
            <div class="listado_contenedor_grupal">
                <div class="listado_item">
                    <div class="listado_col_datos">
                        <div class="listado_dato_grupo">
                            <small>Categoría</small>
                            <span class="listado_resaltado"><?= htmlspecialchars($dato['nombre']) ?></span>
                        </div>
                        <div class="listado_dato_grupo">
                            <small>Edad Mínima</small>
                            <span><?= $dato['edad_min'] . " años" ?></span>
                        </div>
                        <div class="listado_dato_grupo">
                            <small>Edad Máxima</small>
                            <span><?= $dato['edad_max'] . " años" ?></span>
                        </div>
                    </div>

                    <div class="listado_col_acciones">
                        <div onclick="event.stopPropagation();" style="display:flex; gap:5px;">
                                <?php if ($permisos['modificar']) : ?>
                                <button id="cbt_v" class="btn_t cbt_v" onclick="buscar(<?= $dato['id_categorias'] ?>)" title="Modificar" data-tippy-content="Modificar"><i class="fi fi-sr-pencil"></i></button>
                                <?php endif; ?>
                                <?php if ($permisos['eliminar']) : ?>
                                <button id="cbt_r" class="btn_t cbt_r" onclick="eliminar(<?= $dato['id_categorias'] ?>)" title="Eliminar" data-tippy-content="Eliminar"><i class="fi fi-sr-trash-xmark"></i></button>
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
<html lang="es">

<head>
    <?php include('complementos/head.php'); ?>
    <title>Categorías</title>
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
                            <h2 class="titulo_pagina" id="titulo">Categorías</h2>
                        </div>
                        <div class="contenedor_busqueda">
                            <input type="text" placeholder="Buscar..." autocomplete="off" id="busqueda">
                            <i class="fi fi-br-search icon_input"></i>
                        </div>
                        <div class="botones">
                            <button class="btn btn_azul" id="incluir">Nueva Categoría</button>
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
                                foreach ($registro as $dato) : ?>
                                    <div class="listado_contenedor_grupal">
                                        <div class="listado_item">
                                            <div class="listado_col_datos">
                                                <div class="listado_dato_grupo">
                                                    <small>Categoría</small>
                                                    <span class="listado_resaltado"><?= htmlspecialchars($dato['nombre']) ?></span>
                                                </div>
                                                <div class="listado_dato_grupo">
                                                    <small>Edad Mínima</small>
                                                    <span><?= $dato['edad_min'] . " años" ?></span>
                                                </div>
                                                <div class="listado_dato_grupo">
                                                    <small>Edad Máxima</small>
                                                    <span><?= $dato['edad_max'] . " años" ?></span>
                                                </div>
                                            </div>

                                            <div class="listado_col_acciones">
                                                <div onclick="event.stopPropagation();" style="display:flex; gap:5px;">
                                                        <?php if ($permisos['modificar']) : ?>
                                                        <button id="cbt_v" class="btn_t cbt_v" onclick="buscar(<?= $dato['id_categorias'] ?>)" title="Modificar" data-tippy-content="Modificar"><i class="fi fi-sr-pencil"></i></button>
                                                        <?php endif; ?>
                                                        <?php if ($permisos['eliminar']) : ?>
                                                        <button id="cbt_r" class="btn_t cbt_r" onclick="eliminar(<?= $dato['id_categorias'] ?>)" title="Eliminar" data-tippy-content="Eliminar"><i class="fi fi-sr-trash-xmark"></i></button>
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
                                <label for="nombre" class="titulo_formulario">Nombre (Ej: U-12)</label>
                                <span class="mensaje" id="nombre_spam"></span>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="edad_min" name="edad_min">
                                <label for="edad_min" class="titulo_formulario">Edad Mínima</label>
                                <span class="mensaje" id="edad_min_spam"></span>
                            </div>
                        </div>
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="edad_max" name="edad_max">
                                <label for="edad_max" class="titulo_formulario">Edad Máxima</label>
                                <span class="mensaje" id="edad_max_spam"></span>
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
    <script src="js/categorias.js"></script>
</body>

</html>
