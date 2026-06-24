<?php if (!isset($solo_lista)) { ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include('complementos/head.php'); ?>
    <title>Inventario de Artículos</title>
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
                            <h2 class="titulo_pagina" id="titulo">Inventario de Artículos</h2>
                        </div>
                        <div class="contenedor_busqueda">
                            <input type="text" placeholder="Buscar..." autocomplete="off" id="busqueda">
                            <i class="fi fi-br-search icon_input"></i>
                        </div>
                        <div class="botones">
                            <button class="btn btn_azul" id="btn_nuevo">Nuevo Artículo</button>
                            <button class="btn btn_verde" id="generar">Generar Reporte</button>
                        </div>
                    </div>
                    <div class="contenedor_resultados">
                        <div id="resultadoconsulta" class="resultadoconsulta">
<?php } ?>

    <?php if (empty($registro)) { ?>
        <div class="listado_vacio"><p>No hay artículos registrados en el inventario.</p></div>
    <?php } else { 
        foreach ($registro as $grupo) { ?>
            <div class="listado_contenedor_grupal">
                <div class="listado_item" onclick="toggleDetalles(this)">
                    <div class="listado_col_principal">
                        <div class="listado_avatar_null">
                            <i class="icon_con" data-lucide="package"></i>
                        </div>
                        <div class="listado_info_base">
                            <span class="listado_titulo"><?= htmlspecialchars($grupo['articulo']) ?></span>
                            <span class="listado_subtitulo">Categoría: <?= htmlspecialchars($grupo['categoria']) ?></span>
                        </div>
                    </div>

                    <div class="listado_col_datos">
                        <div class="listado_dato_grupo">
                            <small>Total de Piezas</small>
                            <span style="font-weight: bold;"><?= count($grupo['piezas']) ?> unds.</span>
                        </div>
                    </div>

                    <div class="listado_col_acciones">
                        <i data-lucide="chevron-down" class="icono_flecha_detalle"></i>
                    </div>
                </div>

                <div class="listado_detalle_oculto" style="display:none;">
                    <div class="detalle_expandido_container" style="padding: 15px;">
                        
                        <div class="lista_sub_items">
                            <?php foreach ($grupo['piezas'] as $pieza) { 
                                if ($pieza['estatus'] == 1) {
                                    $clase = 'estatus_v'; $txt = 'Disponible';
                                } elseif ($pieza['estatus'] == 2) {
                                    $clase = 'estatus_a'; $txt = 'En Uso';
                                } else {
                                    $clase = 'estatus_r'; $txt = 'Inactivo';
                                }

                                $claseCondicion = 'estatus_v';
                                if ($pieza['nivel_estado'] == 2) {
                                    $claseCondicion = 'estatus_a';
                                } elseif ($pieza['nivel_estado'] >= 3) {
                                    $claseCondicion = 'estatus_r';
                                }
                            ?>
                                <div class="sub_item_fila">
                                    <div class="sub_item_info">
                                        <span class="sub_item_titulo">Código: <?= htmlspecialchars($pieza['codigo_club']) ?></span>
                                    </div>
                                    <div class="sub_item_bloque_metricas_horizontal" style="display: flex; flex-direction: row; gap: 15px; align-items: center; flex-wrap: nowrap; justify-content: flex-end;">
                                        <div class="metrica_item">
                                            Condición:
                                            <strong class="<?= $claseCondicion ?>"><?= htmlspecialchars($pieza['estado_fisico']) ?></strong>
                                        </div>
                                        <div class="metrica_item">
                                            Estatus:
                                            <strong class="<?= $clase ?>"><?= $txt ?></strong>
                                        </div>
                                    </div>
                                    <div class="sub_item_acciones" onclick="event.stopPropagation();">
                                        <?php if (!empty($permisos['modificar'])) { ?>
                                            <button class="btn_t cbt_v" onclick="editar(<?= $pieza['codigo_articulo'] ?>, <?= $grupo['id_catalogo'] ?>, <?= $pieza['id_estado'] ?>)" data-tippy-content="Modificar">
                                                <i class="fi fi-sr-pencil"></i>
                                            </button>
                                        <?php } ?>
                                        <?php if (!empty($permisos['eliminar'])) { ?>
                                            <button class="btn_t cbt_r" onclick="eliminar(<?= $pieza['codigo_articulo'] ?>)" data-tippy-content="Eliminar">
                                                <i class="fi fi-sr-trash"></i>
                                            </button>
                                        <?php } ?>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
    <?php } } ?>

<?php if (!isset($solo_lista)) { ?>
                        </div>
                    </div>
                    <?php include('complementos/botonera.php'); ?>
                </div>
            </div>
        </div>
    </section>

    <section class="contenedor_modal" id="contenedor_modal">
        <div class="modal modal_mediano ocultar" id="modal">
            <div class="cabecera_modal">
                <h2 class="titulo_modal" id="titulo_modal">Registrar Artículo</h2>
                <a type="button" class="cerrar_modal" id="cerrar_modal">&times;</a>
            </div>
            <div class="contenido_modal">
                <form id="f" autocomplete="off">
                    <input type="hidden" id="codigo_articulo" name="codigo_articulo">
                    
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <select class="formulario select2" id="id_catalogo" name="id_catalogo" required>
                                    <option value="" selected disabled>Seleccione un artículo...</option>
                                </select>
                                <label for="id_catalogo" class="titulo_formulario">Artículo del Catálogo</label>
                            </div>
                        </div>
                        <div class="colum">
                            <div class="caja_formulario">
                                <select class="formulario" id="id_estado" name="id_estado" required>
                                    <option value="" selected disabled>Seleccione un estado...</option>
                                </select>
                                <label for="id_estado" class="titulo_formulario">Condición / Estado Físico</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row row_final">
                        <div class="colum">
                            <button type="button" class="btn btn_azul" id="btn_guardar" data-accion="incluir">Guardar</button>
                            <button type="button" class="btn btn_verde" onclick="limpia()">Limpiar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
    
    <script src="js/main.js"></script>
    <script src="js/articulosinventario.js"></script>
</body>
</html>
<?php } ?>