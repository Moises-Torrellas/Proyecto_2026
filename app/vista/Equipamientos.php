<?php if (!isset($solo_lista)) { ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include('complementos/head.php'); ?>
    <title>Equipamientos</title>
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
                            <h2 class="titulo_pagina" id="titulo">Equipamientos</h2>
                        </div>
                        <div class="contenedor_busqueda">
                            <input type="text" placeholder="Buscar..." autocomplete="off" id="busqueda">
                            <i class="fi fi-br-search icon_input"></i>
                        </div>
                        <div class="botones">
                            <button class="btn btn_azul" id="btn_nuevo">Nuevo Equipamiento</button>
                            <button class="btn btn_verde" id="generar">Generar Reporte</button>
                        </div>
                    </div>
                    <div class="contenedor_resultados">
                        <div id="resultadoconsulta" class="resultadoconsulta">
<?php } ?>

    <?php if (empty($registro)) { ?>
        <div class="listado_vacio"><p>No hay equipamientos registrados.</p></div>
    <?php } else { 
        foreach ($registro as $grupo) { ?>
            <div class="listado_contenedor_grupal">
                <div class="listado_item" onclick="toggleDetalles(this)">
                    <div class="listado_col_principal">
                        <div class="listado_avatar_null" style="background-color: var(--verde-suave); color: var(--verde);">
                            <i class="icon_con" data-lucide="package"></i>
                        </div>
                        <div class="listado_info_base">
                            <span class="listado_titulo"><?= htmlspecialchars($grupo['articulo']) ?></span>
                            <small>Categoría: <?= htmlspecialchars($grupo['categoria']) ?></small>
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

                <!-- Detalle Acordeón con los cambios visuales para Modo Oscuro/Claro -->
                <div class="listado_detalle_oculto">
                    <div class="detalle_expandido_container">
                        <h4>Inventario Físico (Piezas Individuales):</h4>
                        
                        <!-- Forzamos el apilamiento vertical -->
                        <div class="detalle_fila" style="display: flex; flex-direction: column; gap: 10px;">
                            <?php foreach ($grupo['piezas'] as $pieza) { 
                                // Lógica de semáforo
                                if ($pieza['estatus'] == 1) {
                                    $clase = 'estatus_v'; $txt = 'Disponible';
                                } elseif ($pieza['estatus'] == 2) {
                                    $clase = 'estatus_a'; $txt = 'En Uso';
                                } else {
                                    $clase = 'estatus_r'; $txt = 'Inactivo';
                                }
                            ?>
                                <div class="detalle_card" style="width: 100%; display: flex; justify-content: space-between; align-items: center;">
                                    <div style="display:flex; gap:15px; align-items:center;">
                                        <div class="detalle_card_icon"><i data-lucide="box"></i></div>
                                        
                                            <div class="detalle_card_txt" style="display: flex; flex-direction: column; gap: 3px;">
                                            
                                            <span style="color: var(--texto-principal); font-weight: bold; font-size: 13px; text-transform: uppercase;">
                                                Condición: <?= htmlspecialchars($pieza['estado_fisico']) ?>
                                            </span>
                                            
                                            <span style="color: var(--texto-principal); font-size: 12px; opacity: 0.7;">
                                                Detalle: <?= htmlspecialchars($pieza['detalle_material'] ?? 'N/A') ?>
                                            </span>

                                            <span class="<?= $clase ?>" style="width: fit-content; margin-top: 3px;"><?= $txt ?></span>
                                        </div>
                                        
                                    </div>
                                    <div style="display:flex; gap:5px;" onclick="event.stopPropagation();">
                                        <?php if (!empty($permisos['modificar'])) { ?>
                                            <button class="btn_t cbt_v" onclick="editar(<?= $pieza['id_equipamiento'] ?>, <?= $grupo['id_catalogo'] ?>, <?= $pieza['id_estado'] ?>)" data-tippy-content="Modificar">
                                                <i class="fi fi-sr-pencil"></i>
                                            </button>
                                        <?php } ?>
                                        <?php if (!empty($permisos['eliminar'])) { ?>
                                            <button class="btn_t cbt_r" onclick="eliminar(<?= $pieza['id_equipamiento'] ?>)" data-tippy-content="Eliminar">
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
                </div>
            </div>
        </div>
    </section>

    <!-- Modal Formulario -->
    <section class="contenedor_modal" id="contenedor_modal">
        <div class="modal modal_mediano ocultar" id="modal">
            <div class="cabecera_modal">
                <h2 class="titulo_modal" id="titulo_modal">Registrar Equipamiento</h2>
                <a type="button" class="cerrar_modal" id="cerrar_modal">&times;</a>
            </div>
            <div class="contenido_modal">
                <form id="f" autocomplete="off">
                    <input type="hidden" id="id_equipamiento" name="id_equipamiento">
                    
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <select class="formulario select2" id="id_catalogo" name="id_catalogo" required>
                                    <option value="" selected disabled>Seleccione un artículo...</option>
                                </select>
                                <label for="id_catalogo" class="titulo_formulario">Artículo del Catálogo</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <select class="formulario" id="id_estado" name="id_estado" required>
                                    <option value="" selected disabled>Seleccione un estado...</option>
                                </select>
                                <label for="id_estado" class="titulo_formulario">Condición / Estado Físico</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row" style="margin-top: 10px;">
                        <div class="colum">
                            <button type="button" class="btn btn_azul" id="btn_guardar">Guardar</button>
                            <button type="button" class="btn btn_verde" onclick="limpia()">Limpiar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
    
    <script src="js/main.js"></script>
    <script src="js/equipamientos.js"></script>
</body>
</html>
<?php } ?>