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
                    </div>

                    <div class="listado_col_acciones">
                        <div onclick="event.stopPropagation();" style="display:flex; gap:5px;">
                            <?php if (($permisos['modificar'] ?? false)) : ?>
                                <button id="cbt_v" class="btn_t cbt_v" onclick="buscar(<?= (int)$dato['id_equipos'] ?>)" data-tippy-content="Modificar">
                                    <i class="fi fi-sr-pencil"></i>
                                </button>
                            <?php endif; ?>
                            <?php if (($permisos['eliminar'] ?? false)) : ?>
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
                            <?php if (($permisos['registrar'] ?? false)) : ?>
                                <button class="btn btn_azul" id="incluir">Nuevo Equipo</button>
                            <?php endif; ?>
                            <?php if (($permisos['reporte'] ?? false)) : ?>
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
                                            </div>

                                            <div class="listado_col_acciones">
                                                <div onclick="event.stopPropagation();" style="display:flex; gap:5px;">
                                                    <?php if (($permisos['modificar'] ?? false)) : ?>
                                                        <button id="cbt_v" class="btn_t cbt_v" onclick="buscar(<?= (int)$dato['id_equipos'] ?>)" data-tippy-content="Modificar">
                                                            <i class="fi fi-sr-pencil"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if (($permisos['eliminar'] ?? false)) : ?>
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

    <section class="contenedor_modal modal_contenedor" id="contenedor_modal">
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
                                <label for="nombre" class="titulo_formulario">Nombre de Equipo</label>
                                <span class="mensaje" id="nombre_spam"></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="colum">
                            <button type="button" class="btn btn_azul" id="asignar">Seleccionar Atletas</button>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="colum colum_tabla_completa">
                            <label for="" class="titulo_formulario titulo_formulario_tabla" id="label_tabla">Atletas Seleccionados</label>
                            <div class="caja_formulario caja_tabla ct_t">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Cédula</th>
                                            <th>Atleta</th>
                                            <th>Categoría</th>
                                            <th>Posición</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tabla_Atletas_Seleccionados">
                                    </tbody>
                                </table>
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

    <section class="contenedor_modal" id="secundario_modal_contenedor">
        <div class="modal modal_grande ocultar" id="secundario_modal">
            <div class="cabecera_modal">
                <h2 class="titulo_modal">Asignar Atletas</h2>
                <a type="button" class="cerrar_modal" id="cerrar_modal_Secundario">&times;</a>
            </div>
            <div class="contenido_modal">
                <form autocomplete="off">
                    <div class="row" style="margin-bottom: 15px;">
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="buscar_atleta_modal" placeholder="Buscar por nombre o cédula...">
                                <label for="buscar_atleta_modal" class="titulo_formulario">Buscar Atleta</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="colum colum_tabla_completa">
                            <label for="" class="titulo_formulario titulo_formulario_tabla">Listado de Atletas</label>
                            <div class="caja_formulario caja_tabla ct_t">
                                <table>
                                    <thead>
                                        <tr>
                                            <th style="width: 80px; text-align: center;">Selección</th>
                                            <th>Cédula</th>
                                            <th>Atleta</th>
                                            <th>Categoría</th>
                                            <th>Posición</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tabla_Atletas">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row" style="margin-top: 10px;">
                        <div class="colum" style="flex-direction: row; align-items: center; gap: 8px;">
                            <label class="checkbox-container">
                                <input type="checkbox" id="check_todos_atletas" class="checkbox">
                                <span class="custom-checkbox"></span>
                            </label>
                            <label for="check_todos_atletas" style="cursor:pointer; color: var(--color-texto);">Seleccionar todos los atletas</label>
                        </div>
                    </div>
                    
                    <div class="row" style="margin-top: 20px;">
                        <div class="colum">
                            <button type="button" class="btn btn_azul" id="listo">Listo</button>
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