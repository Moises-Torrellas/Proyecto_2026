<?php
if (isset($solo_lista) && $solo_lista === true) :
    if (empty($registro)) : ?>
        <div class="listado_vacio">
            <p>No se encontraron registros</p>
        </div>
    <?php else :
        foreach ($registro as $dato) : ?>
            <div class="listado_contenedor_grupal">
                <div class="listado_item" onclick="toggleDetalles(this)">
                    
                    <div class="listado_col_principal">
                        <div class="listado_avatar_null"><i class="icon_con" data-lucide="box"></i></div>
                        <div class="listado_info_base">
                            <span class="listado_titulo"><?= htmlspecialchars($dato['nombre']) ?></span>
                            <small class="listado_subtitulo"><?= htmlspecialchars($dato['categoria_nombre']) ?></small>
                        </div>
                    </div>

                    <div class="listado_col_datos">
                        <div class="listado_dato_grupo">
                            <small>Talla</small>
                            <span><?= !empty($dato['talla']) ? htmlspecialchars($dato['talla']) : 'N/A' ?></span>
                        </div>
                        <div class="listado_dato_grupo">
                            <small>Stock Mínimo</small>
                            <span class="listado_resaltado"><?= htmlspecialchars($dato['stock_minimo']) ?> unds.</span>
                        </div>
                    </div>

                    <div class="listado_col_acciones">
                        <div onclick="event.stopPropagation();" style="display:flex; gap:5px;">
                                <?php if (!empty($permisos['modificar_catalogo'])) : ?>
                                <button id="cbt_v" class="btn_t cbt_v" onclick="buscar(<?= $dato['id_catalogo'] ?>)" data-tippy-content="Modificar"><i class="fi fi-sr-pencil"></i></button>
                                <?php endif; ?>
                                <?php if (!empty($permisos['eliminar_catalogo'])) : ?>
                                <button id="cbt_r" class="btn_t cbt_r" onclick="eliminar(<?= $dato['id_catalogo'] ?>)" data-tippy-content="Eliminar"><i class="fi fi-sr-trash-xmark"></i></button>
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
<html lang="es">

<head>
    <?php include('complementos/head.php'); ?>
    <title>Catálogo de Artículos</title>
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
                            <h2 class="titulo_pagina" id="titulo">Catálogo</h2>
                        </div>
                        <div class="contenedor_busqueda">
                            <input type="text" placeholder="Buscar artículo..." autocomplete="off" id="busqueda">
                            <i class="fi fi-br-search icon_input"></i>
                        </div>
                        <div class="botones">
                            <?php if (!empty($permisos['registrar_catalogo'])) : ?>
                                <button class="btn btn_azul" id="incluir">Nuevo Artículo</button>
                            <?php endif; ?>
                            <?php if (!empty($permisos['generar_catalogo'])) : ?>
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
                                foreach ($registro as $dato) : ?>
                                    <div class="listado_contenedor_grupal">
                                        <div class="listado_item" onclick="toggleDetalles(this)">
                                            
                                            <div class="listado_col_principal">
                                                <div class="listado_avatar_null"><i class="icon_con" data-lucide="box"></i></div>
                                                <div class="listado_info_base">
                                                    <span class="listado_titulo"><?= htmlspecialchars($dato['nombre']) ?></span>
                                                    <small class="listado_subtitulo"><?= htmlspecialchars($dato['categoria_nombre']) ?></small>
                                                </div>
                                            </div>

                                            <div class="listado_col_datos">
                                                <div class="listado_dato_grupo">
                                                    <small>Talla</small>
                                                    <span><?= !empty($dato['talla']) ? htmlspecialchars($dato['talla']) : 'N/A' ?></span>
                                                </div>
                                                <div class="listado_dato_grupo">
                                                    <small>Stock Mínimo</small>
                                                    <span class="listado_resaltado"><?= htmlspecialchars($dato['stock_minimo']) ?> unds.</span>
                                                </div>
                                            </div>

                                            <div class="listado_col_acciones">
                                                <div onclick="event.stopPropagation();" style="display:flex; gap:5px;">
                                                        <?php if (!empty($permisos['modificar_catalogo'])) : ?>
                                                        <button id="cbt_v" class="btn_t cbt_v" onclick="buscar(<?= $dato['id_catalogo'] ?>)" data-tippy-content="Modificar"><i class="fi fi-sr-pencil"></i></button>
                                                        <?php endif; ?>
                                                        <?php if (!empty($permisos['eliminar_catalogo'])) : ?>
                                                        <button id="cbt_r" class="btn_t cbt_r" onclick="eliminar(<?= $dato['id_catalogo'] ?>)" data-tippy-content="Eliminar"><i class="fi fi-sr-trash-xmark"></i></button>
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
                <h2 class="titulo_modal" id="titulo_modal"></h2>
                <a type="button" class="cerrar_modal" id="cerrar_modal">&times;</a>
            </div>
            <div class="contenido_modal">
                <form id="f" autocomplete="off">
                    <input type="hidden" id="id_catalogo" name="id_catalogo">
                    
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="nombre" name="nombre">
                                <label for="nombre" class="titulo_formulario">Nombre del Artículo</label>
                                <span class="mensaje" id="nombre_spam"></span>
                            </div>
                        </div>
                        <div class="colum">
                            <div class="caja_formulario">
                                <select name="id_categoria" id="id_categoria" class="formulario select">
                                </select>
                                <label for="id_categoria" class="titulo_formulario">Categoría</label>
                                <span class="mensaje" id="id_categoria_span"></span>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="stock_minimo" name="stock_minimo">
                                <label for="stock_minimo" class="titulo_formulario">Stock Mínimo</label>
                                <span class="mensaje" id="stock_minimo_spam"></span>
                            </div>
                        </div>
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="talla" name="talla">
                                <label for="talla" class="titulo_formulario">Talla (Opcional)</label>
                                <span class="mensaje" id="talla_spam"></span>
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
    <script src="js/catalogo.js"></script>
    <?php include('complementos/mensajeError.php'); ?>
</body>

</html>