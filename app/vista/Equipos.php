<?php if (isset($solo_lista) && $solo_lista === true) : ?>
    <?php if (empty($registro)) : ?>
        <div class="listado_vacio">
            <p>No se encontraron registros</p>
        </div>
    <?php else : ?>
        <?php foreach ($registro as $dato) : ?>
            <div class="listado_contenedor_grupal">
                <div class="listado_item" onclick="toggleDetalles(this)">
                    <div class="listado_col_datos">
                        <div class="listado_dato_grupo">
                            <small>Nombre de Equipo</small>
                            <span><?= htmlspecialchars($dato['nombre'] ?? '') ?></span>
                        </div>
                        <div class="listado_dato_grupo">
                            <small>Atletas Asignados</small>
                            <span><?= htmlspecialchars($dato['cantidad_atletas'] ?? '0') ?></span>
                        </div>
                        <div class="listado_dato_grupo">
                            <small>Premios Ganados</small>
                            <span><i class="fi fi-sr-trophy"></i> <?= htmlspecialchars($dato['total_premios'] ?? '0') ?></span>
                        </div>
                    </div>

                    <div class="listado_col_acciones">
                        <div onclick="event.stopPropagation();" style="display:flex; gap:5px;">
                            <?php if (!empty($permisos['modificar_equipo'])) : ?>
                                <button id="cbt_v" class="btn_t cbt_v" onclick="buscar(<?= (int)$dato['id_equipos'] ?>)" data-tippy-content="Modificar">
                                    <i class="fi fi-sr-pencil"></i>
                                </button>
                            <?php endif; ?>
                            <?php if (!empty($permisos['eliminar_equipo'])) : ?>
                                <button id="cbt_r" class="btn_t cbt_r" onclick="eliminar(<?= (int)$dato['id_equipos'] ?>)" data-tippy-content="Eliminar">
                                    <i class="fi fi-sr-trash-xmark"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                        <i data-lucide="chevron-down" class="icono_flecha_detalle"></i>
                    </div>
                </div>
                
                <div class="listado_detalle_oculto" style="display:none;">
                    <div class="detalle_expandido_container" style="padding: 15px;">
                        <div class="lista_sub_items">
                            <?php 
                            $atletasEquipo = $dato['atletas'] ?? [];
                            if (empty($atletasEquipo)) : 
                            ?>
                                <div class="sub_item_fila" style="justify-content: center; opacity: 0.6;">
                                    <div class="sub_item_info" style="align-items: center;">
                                        <span class="sub_item_titulo">No hay atletas asignados</span>
                                    </div>
                                </div>
                            <?php else : ?>
                                <?php foreach ($atletasEquipo as $atleta) : ?>
                                    <div class="sub_item_fila">
                                        <div class="sub_item_info">
                                            <span class="sub_item_titulo"><?= htmlspecialchars($atleta['nombre'] ?? '') ?></span>
                                            <div class="sub_item_fechas">
                                                <span>C.I: <?= htmlspecialchars($atleta['doc_i'] ?? '') ?></span>
                                            </div>
                                        </div>
                                        <div class="sub_item_bloque_metricas_horizontal" style="display: flex; flex-direction: row; gap: 15px; align-items: center; flex-wrap: nowrap; justify-content: flex-end;">
                                            <div class="metrica_item">
                                                Posición:
                                                <strong><?= htmlspecialchars($atleta['posicion'] ?? '') ?></strong>
                                            </div>
                                            <div class="metrica_item">
                                                Categoría:
                                                <strong><?= htmlspecialchars($atleta['categoria'] ?? '') ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
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
    <title>Equipos</title>
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
                            <h2 class="titulo_pagina" id="titulo">Equipos</h2>
                        </div>
                        <div class="contenedor_busqueda">
                            <input type="text" placeholder="Buscar..." autocomplete="off" id="busqueda">
                            <i class="fi fi-br-search icon_input"></i>
                        </div>
                        <div class="botones">
                            <?php if (!empty($permisos['registrar_equipo'])) : ?>
                                <button class="btn btn_azul" id="incluir">Nuevo Equipo</button>
                            <?php endif; ?>
                            <?php if (!empty($permisos['generar_equipo'])) : ?>
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
                            <?php else : ?>
                                <?php foreach ($registro as $dato) : ?>
                                    <div class="listado_contenedor_grupal">
                                        <div class="listado_item" onclick="toggleDetalles(this)">
                                            <div class="listado_col_datos">
                                                <div class="listado_dato_grupo">
                                                    <small>Nombre de Equipo</small>
                                                    <span><?= htmlspecialchars($dato['nombre'] ?? '') ?></span>
                                                </div>
                                                <div class="listado_dato_grupo">
                                                    <small>Atletas Asignados</small>
                                                    <span><?= htmlspecialchars($dato['cantidad_atletas'] ?? '0') ?></span>
                                                </div>
                                                <div class="listado_dato_grupo">
                                                    <small>Premios Ganados</small>
                                                    <span><i class="fi fi-sr-trophy"></i> <?= htmlspecialchars($dato['total_premios'] ?? '0') ?></span>
                                                </div>
                                            </div>

                                            <div class="listado_col_acciones">
                                                <div onclick="event.stopPropagation();" style="display:flex; gap:5px;">
                                                    <?php if (!empty($permisos['modificar_equipo'])) : ?>
                                                        <button id="cbt_v" class="btn_t cbt_v" onclick="buscar(<?= (int)$dato['id_equipos'] ?>)" data-tippy-content="Modificar">
                                                            <i class="fi fi-sr-pencil"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if (!empty($permisos['eliminar_equipo'])) : ?>
                                                        <button id="cbt_r" class="btn_t cbt_r" onclick="eliminar(<?= (int)$dato['id_equipos'] ?>)" data-tippy-content="Eliminar">
                                                            <i class="fi fi-sr-trash-xmark"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                                <i data-lucide="chevron-down" class="icono_flecha_detalle"></i>
                                            </div>
                                        </div>
                                        
                                        <div class="listado_detalle_oculto" style="display:none;">
                                            <div class="detalle_expandido_container" style="padding: 15px;">
                                                <div class="lista_sub_items">
                                                    <?php 
                                                    $atletasEquipo = $dato['atletas'] ?? [];
                                                    if (empty($atletasEquipo)) : 
                                                    ?>
                                                        <div class="sub_item_fila" style="justify-content: center; opacity: 0.6;">
                                                            <div class="sub_item_info" style="align-items: center;">
                                                                <span class="sub_item_titulo">No hay atletas asignados</span>
                                                            </div>
                                                        </div>
                                                    <?php else : ?>
                                                        <?php foreach ($atletasEquipo as $atleta) : ?>
                                                            <div class="sub_item_fila">
                                                                <div class="sub_item_info">
                                                                    <span class="sub_item_titulo"><?= htmlspecialchars($atleta['nombre'] ?? '') ?></span>
                                                                    <div class="sub_item_fechas">
                                                                        <span>C.I: <?= htmlspecialchars($atleta['doc_i'] ?? '') ?></span>
                                                                    </div>
                                                                </div>
                                                                <div class="sub_item_bloque_metricas_horizontal" style="display: flex; flex-direction: row; gap: 15px; align-items: center; flex-wrap: nowrap; justify-content: flex-end;">
                                                                    <div class="metrica_item">
                                                                        Posición:
                                                                        <strong><?= htmlspecialchars($atleta['posicion'] ?? '') ?></strong>
                                                                    </div>
                                                                    <div class="metrica_item">
                                                                        Categoría:
                                                                        <strong><?= htmlspecialchars($atleta['categoria'] ?? '') ?></strong>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
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
        <div class="modal modal_mediano modal_lote ocultar" id="modal">
            <div class="cabecera_modal">
                <h2 class="titulo_modal" id="titulo_modal">Registrar Equipo</h2>
                <a type="button" class="cerrar_modal" id="cerrar_modal">&times;</a>
            </div>
            <div class="contenido_modal">
                <form id="f" autocomplete="off">
                    <input type="hidden" id="id" name="id">

                    <!-- Nombre del equipo (Incluir / Modificar) -->
                    <div class="row" id="row_nombre">
                        <div class="colum">
                            <div class="caja_formulario caja_lote">
                                <input type="text" class="formulario" id="nombre" name="nombre">
                                <label for="nombre" class="titulo_formulario">Nombre de Equipo</label>
                                <span class="mensaje" id="nombre_spam"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Selector de equipo (Solo para el reporte) -->
                    <div class="row" id="row_equipo_reporte" style="display:none;">
                        <div class="colum">
                            <div class="caja_formulario caja_lote">
                                <select class="formulario select2" id="id_equipo" name="id_equipo">
                                    <option value="" selected disabled>Seleccione un equipo</option>
                                    <?php if (!empty($registro)) : ?>
                                        <?php foreach ($registro as $dato) : ?>
                                            <option value="<?= (int)$dato['id_equipos'] ?>"><?= htmlspecialchars($dato['nombre'] ?? '') ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <label for="id_equipo" class="titulo_formulario">Equipo a Reportar</label>
                            </div>
                        </div>
                    </div>

                    <!-- Sección de selección por lote de atletas (Incluir / Modificar) -->
                    <div id="seccion_lote_equipo">
                        <div class="row">
                            <div class="colum">
                                <div class="caja_formulario caja_lote caja_con_accion">
                                    <div class="caja_select_accion">
                                        <select class="formulario select2" id="lote_atleta">
                                            <option value="" selected disabled>Seleccione un atleta...</option>
                                        </select>
                                    </div>
                                    <button type="button" class="btn_accion_campo" id="btn_agregar_lote_equipo" data-tippy-content="Agregar al equipo">
                                        <i data-lucide="plus"></i>
                                    </button>
                                    <label for="lote_atleta" class="titulo_formulario">Atleta a Agregar</label>
                                </div>
                            </div>
                        </div>

                        <div class="lista_lote">
                            <div class="lista_lote_cabecera">
                                <span class="lista_lote_titulo">
                                    <i data-lucide="users"></i>
                                    Atletas del Equipo
                                    <span class="lista_lote_contador" id="contador_lote_equipo">0</span>
                                </span>
                                <button type="button" class="lista_lote_vaciar" id="btn_vaciar_lote_equipo" disabled>Vaciar lista</button>
                            </div>
                            <div class="lista_lote_cuerpo" id="cuerpo_lote_equipo"></div>
                        </div>
                    </div>

                    <div class="row row_final">
                        <div class="colum">
                            <button type="button" class="btn btn_azul" id="proceso" data-accion="incluir">Registrar Equipo</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <script src="js/main.js"></script>
    <script src="js/Equipos.js"></script>
    <?php include('complementos/mensajeError.php'); ?>
</body>
</html>