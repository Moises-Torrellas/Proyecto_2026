<?php if (isset($solo_lista) && $solo_lista === true) : ?>
    <?php if (empty($registro)) : ?>
        <div class="listado_vacio">
            <p>No se encontraron registros</p>
        </div>
    <?php else : ?>
        <?php foreach ($registro as $atleta) : 
            $asignacionesActivas = 0;
            foreach ($atleta['asignaciones'] as $asig) {
                if ($asig['anulado'] == 0 && $asig['estatus'] == 1) {
                    $asignacionesActivas++;
                }
            }
        ?>
            <div class="listado_contenedor_grupal">
                <div class="listado_item" onclick="toggleDetalles(this)">
                    <div class="listado_col_principal">
                        <div class="listado_avatar_null">
                            <i class="icon_con" data-lucide="circle-user"></i>
                        </div>
                        <div class="listado_info_base">
                            <span class="listado_titulo"><?= htmlspecialchars($atleta['nombre_completo']) ?></span>
                            <small>CI: <?= htmlspecialchars($atleta['doc_identidad']) ?></small>
                        </div>
                    </div>

                    <div class="listado_col_datos">
                        <div class="listado_dato_grupo">
                            <small>Equipos en Préstamo</small>
                            <span style="font-weight: bold;"><?= $asignacionesActivas ?> pieza(s) activa(s)</span>
                        </div>
                    </div>

                    <div class="listado_col_acciones">
                        <i data-lucide="chevron-down" class="icono_flecha_detalle"></i>
                    </div>
                </div>

                <div class="listado_detalle_oculto">
                    <div class="detalle_expandido_container">
                        <h4>Detalle de Asignaciones:</h4>
                        <div class="detalle_fila" style="display: flex; flex-direction: column; gap: 10px;">
                            <?php foreach ($atleta['asignaciones'] as $asignacion) : 
                                $esAnulado = ($asignacion['anulado'] == 1 || $asignacion['estatus'] == 0);
                                $textoEstatus = $esAnulado ? 'Anulado' : 'En Uso';
                                
                                $estiloBadge = $esAnulado ? 'background-color: #d1d5db; color: #374151; padding: 2px 8px; border-radius: 4px; font-weight: bold; font-size: 11px;' : '';
                                $claseEstatus = $esAnulado ? '' : 'estatus_a';
                                $estiloTarjeta = $esAnulado ? 'opacity: 0.6; filter: grayscale(100%); background-color: var(--fondo-secundario);' : '';
                            ?>
                                <div class="detalle_card" style="width: 100%; display: flex; justify-content: space-between; align-items: center; <?= $estiloTarjeta ?>">
                                    <div style="display:flex; gap:15px; align-items:center;">
                                        <div class="detalle_card_icon"><i data-lucide="box"></i></div>
                                        <div class="detalle_card_txt" style="display: flex; flex-direction: column; gap: 3px;">
                                            <span style="color: var(--texto-principal); font-weight: bold; font-size: 13px;">
                                                <?= htmlspecialchars($asignacion['articulo']) ?>
                                            </span>
                                            <span style="color: var(--texto-principal); font-size: 12px; opacity: 0.7;">
                                                Fecha de entrega: <?= $asignacion['fecha_vista'] ?>
                                            </span>
                                            <span class="<?= $claseEstatus ?>" style="width: fit-content; margin-top: 3px; <?= $estiloBadge ?>">
                                                <?= $textoEstatus ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div style="display:flex; gap:5px;" onclick="event.stopPropagation();">
                                        <?php if (!$esAnulado) : ?>
                                            <?php if (!empty($permisos['modificar'])) : ?>
                                                <button class="btn_t cbt_v" onclick="editar(<?= $asignacion['id_asignacion'] ?>, <?= $atleta['id_atleta'] ?>, <?= $asignacion['id_equipamiento'] ?>, '<?= $asignacion['fecha_real'] ?>')" data-tippy-content="Modificar">
                                                    <i class="fi fi-sr-pencil"></i>
                                                </button>
                                            <?php endif; ?>
                                            <?php if (!empty($permisos['eliminar'])) : ?>
                                                <button class="btn_t cbt_r" onclick="anular(<?= $asignacion['id_asignacion'] ?>, <?= $asignacion['id_equipamiento'] ?>)" data-tippy-content="Anular">
                                                    <i class="fi fi-sr-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
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
    <title>Asignaciones</title>
    <style>
        body[data-tema="oscuro"] input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(1);
            cursor: pointer;
        }
    </style>
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
                            <h2 class="titulo_pagina">Asignaciones</h2>
                        </div>
                        
                        <div class="contenedor_busqueda">
                            <input type="text" placeholder="Buscar..." autocomplete="off" id="busqueda">
                            <i class="fi fi-br-search icon_input"></i>
                        </div>

                        <div class="botones">
                            <button class="btn btn_azul" id="btn_nuevo">Nueva Asignación</button>
                            <button class="btn btn_verde" id="generar">Generar Reporte</button>
                        </div>
                    </div>
                    <div class="contenedor_resultados">
                        <div id="resultadoconsulta" class="resultadoconsulta">
                            <?php if (empty($registro)) : ?>
                                <div class="listado_vacio"><p>No hay asignaciones registradas.</p></div>
                            <?php else : ?>
                                <?php foreach ($registro as $atleta) : 
                                    $asignacionesActivas = 0;
                                    foreach ($atleta['asignaciones'] as $asig) {
                                        if ($asig['anulado'] == 0 && $asig['estatus'] == 1) {
                                            $asignacionesActivas++;
                                        }
                                    }
                                ?>
                                    <div class="listado_contenedor_grupal">
                                        <div class="listado_item" onclick="toggleDetalles(this)">
                                            <div class="listado_col_principal">
                                                <div class="listado_avatar_null">
                                                    <i class="icon_con" data-lucide="circle-user"></i>
                                                </div>
                                                <div class="listado_info_base">
                                                    <span class="listado_titulo"><?= htmlspecialchars($atleta['nombre_completo']) ?></span>
                                                    <small>CI: <?= htmlspecialchars($atleta['doc_identidad']) ?></small>
                                                </div>
                                            </div>

                                            <div class="listado_col_datos">
                                                <div class="listado_dato_grupo">
                                                    <small>Equipos en Préstamo</small>
                                                    <span style="font-weight: bold;"><?= $asignacionesActivas ?> pieza(s) activa(s)</span>
                                                </div>
                                            </div>

                                            <div class="listado_col_acciones">
                                                <i data-lucide="chevron-down" class="icono_flecha_detalle"></i>
                                            </div>
                                        </div>

                                        <div class="listado_detalle_oculto">
                                            <div class="detalle_expandido_container">
                                                <h4>Detalle de Asignaciones:</h4>
                                                <div class="detalle_fila" style="display: flex; flex-direction: column; gap: 10px;">
                                                    <?php foreach ($atleta['asignaciones'] as $asignacion) : 
                                                        $esAnulado = ($asignacion['anulado'] == 1 || $asignacion['estatus'] == 0);
                                                        $textoEstatus = $esAnulado ? 'Anulado' : 'En Uso';
                                                        
                                                        $estiloBadge = $esAnulado ? 'background-color: #d1d5db; color: #374151; padding: 2px 8px; border-radius: 4px; font-weight: bold; font-size: 11px;' : '';
                                                        $claseEstatus = $esAnulado ? '' : 'estatus_a';
                                                        $estiloTarjeta = $esAnulado ? 'opacity: 0.6; filter: grayscale(100%); background-color: var(--fondo-secundario);' : '';
                                                    ?>
                                                        <div class="detalle_card" style="width: 100%; display: flex; justify-content: space-between; align-items: center; <?= $estiloTarjeta ?>">
                                                            <div style="display:flex; gap:15px; align-items:center;">
                                                                <div class="detalle_card_icon"><i data-lucide="box"></i></div>
                                                                <div class="detalle_card_txt" style="display: flex; flex-direction: column; gap: 3px;">
                                                                    <span style="color: var(--texto-principal); font-weight: bold; font-size: 13px;">
                                                                        <?= htmlspecialchars($asignacion['articulo']) ?>
                                                                    </span>
                                                                    <span style="color: var(--texto-principal); font-size: 12px; opacity: 0.7;">
                                                                        Fecha de entrega: <?= $asignacion['fecha_vista'] ?>
                                                                    </span>
                                                                    <span class="<?= $claseEstatus ?>" style="width: fit-content; margin-top: 3px; <?= $estiloBadge ?>">
                                                                        <?= $textoEstatus ?>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                            
                                                            <div style="display:flex; gap:5px;" onclick="event.stopPropagation();">
                                                                <?php if (!$esAnulado) : ?>
                                                                    <?php if (!empty($permisos['modificar'])) : ?>
                                                                        <button class="btn_t cbt_v" onclick="editar(<?= $asignacion['id_asignacion'] ?>, <?= $atleta['id_atleta'] ?>, <?= $asignacion['id_equipamiento'] ?>, '<?= $asignacion['fecha_real'] ?>')" data-tippy-content="Modificar">
                                                                            <i class="fi fi-sr-pencil"></i>
                                                                        </button>
                                                                    <?php endif; ?>
                                                                    <?php if (!empty($permisos['eliminar'])) : ?>
                                                                        <button class="btn_t cbt_r" onclick="anular(<?= $asignacion['id_asignacion'] ?>, <?= $asignacion['id_equipamiento'] ?>)" data-tippy-content="Anular">
                                                                            <i class="fi fi-sr-trash"></i>
                                                                        </button>
                                                                    <?php endif; ?>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
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
        <div class="modal modal_mediano ocultar" id="modal">
            <div class="cabecera_modal">
                <h2 class="titulo_modal" id="titulo_modal">Registrar Asignación</h2>
                <a type="button" class="cerrar_modal" id="cerrar_modal">&times;</a>
            </div>
            <div class="contenido_modal">
                <form id="f" autocomplete="off">
                    <input type="hidden" id="id_asignacion" name="id_asignacion">
                    
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <select class="formulario select2" id="id_atleta" name="id_atleta" required>
                                    <option value="" selected disabled>Seleccione un atleta...</option>
                                </select>
                                <label for="id_atleta" class="titulo_formulario">Atleta</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <select class="formulario select2" id="id_equipamiento" name="id_equipamiento" required>
                                    <option value="" selected disabled>Seleccione una pieza...</option>
                                </select>
                                <label for="id_equipamiento" class="titulo_formulario">Equipamiento</label>
                            </div>
                        </div>
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="date" class="formulario" id="fecha_asignacion" name="fecha_asignacion" required>
                                <label for="fecha_asignacion" class="titulo_formulario">Fecha de Asignación</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row" style="margin-top: 10px;">
                        <div class="colum">
                            <button type="button" class="btn btn_azul" id="btn_guardar" data-accion="incluir">Confirmar Préstamo</button>
                            <button type="button" class="btn btn_verde" onclick="limpia()">Limpiar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
    
    <script src="js/main.js"></script>
    <script src="js/asignaciones.js"></script>
</body>
</html>