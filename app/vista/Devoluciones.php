<?php if (!isset($solo_lista)) { ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include('complementos/head.php'); ?>
    <title>Devoluciones</title>
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
                            <h2 class="titulo_pagina">Devoluciones</h2>
                        </div>
                        <div class="botones">
                            <?php if (!empty($permisos['registrar'])) : ?>
                                <button class="btn btn_azul" id="btn_nuevo">
                                    <i class="fi fi-sr-add-document"></i> Nueva Devolución
                                </button>
                            <?php endif; ?>
                            
                            <?php if (!empty($permisos['reporte'])) : ?>
                                <button class="btn btn_verde" id="generar">
                                    <i class="fi fi-sr-document"></i> Generar Reporte
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="contenedor_resultados">
                        <div id="resultadoconsulta" class="resultadoconsulta">
<?php } ?>

    <?php if (empty($registro)): ?>
        <div class="listado_vacio">
            <p>No se encontraron registros</p>
        </div>
    <?php else:
        $atletaActual = null;
        $totalRegistros = count($registro);

        foreach ($registro as $index => $dato):
            $idAtleta = $dato['id_atleta'];

            // 1. DETECTAR CAMBIO DE ATLETA
            if ($idAtleta !== $atletaActual):
                if ($atletaActual !== null): ?>
                            </div>
                        </div>
                    </div>
                </div> 
                <?php endif;

                $atletaActual = $idAtleta;
                ?>
                
                <div class="listado_contenedor_grupal">
                    <div class="listado_item" onclick="$(this).next('.listado_detalle_oculto').slideToggle();">
                        <div class="listado_col_principal">
                            <div class="listado_avatar_null"><i class="icon_con" data-lucide="circle-user"></i></div>
                            <div class="listado_info_base">
                                <span class="listado_titulo"><?= htmlspecialchars($dato['atleta_nombre'] . ' ' . $dato['atleta_apellido']) ?></span>
                            </div>
                        </div>

                        <div class="listado_col_datos">
                            <div class="listado_dato_grupo">
                                <small>Devoluciones</small>
                                <span><?= $dato['total_devoluciones_atleta'] ?> Registro(s)</span>
                            </div>
                        </div>

                        <div class="listado_col_acciones">
                            <i data-lucide="chevron-down" class="icono_flecha_detalle"></i>
                        </div>
                    </div>

                    <div class="listado_detalle_oculto" style="display:none;">
                        <div class="detalle_expandido_container" style="padding: 15px;">
                            <div class="lista_sub_items">
            <?php endif; 

            // 2. RENDERIZAR LAS DEVOLUCIONES
            $claseEstatus = 'estatus_v'; 
            if ($dato['id_estado'] == 2) $claseEstatus = 'estatus_a';
            if ($dato['id_estado'] > 2) $claseEstatus = 'estatus_r';

            $botonesAccion = '';
            if ($permisos['modificar']) {
                $botonesAccion .= '<button class="btn_t cbt_v" onclick="editar(' . $dato['id_devolucion'] . ', ' . $dato['id_asignacion'] . ', ' . $dato['id_estado'] . ', \'' . $dato['fecha_devolucion'] . '\', \'' . htmlspecialchars($dato['observacion']) . '\')" title="Modificar"><i class="fi fi-sr-pencil"></i></button> ';
            }
            if ($permisos['eliminar']) {
                $botonesAccion .= '<button class="btn_t cbt_r" onclick="confirmarAnulacion(' . $dato['id_devolucion'] . ')" title="Anular"><i class="fi fi-sr-trash"></i></button>';
            }
            ?>
                            <div class="sub_item_fila" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center;">
                                <div style="width: 25%;">
                                    <strong style="font-size: 14px;"><?= htmlspecialchars($dato['articulo_nombre']) ?></strong>
                                </div>
                                <div style="width: 20%;">
                                    <strong>Fecha</strong><br>
                                    <span style="color: #6b7280; font-size:13px;"><?= $dato['fecha_vista'] ?></span>
                                </div>
                                <div style="width: 20%; text-align: center;">
                                    <strong>Calidad</strong><br>
                                    <span class="<?= $claseEstatus ?>" style="padding: 3px 8px; border-radius: 12px; font-size: 11px; margin-top:4px; display:inline-block;"><?= $dato['calidad'] ?></span>
                                </div>
                                <div style="width: 35%; text-align: right; display:flex; justify-content:flex-end; align-items:center; gap:10px;">
                                    <div style="text-align: right; margin-right: 10px;">
                                        <strong>Observación</strong><br>
                                        <span style="font-size:12px; color:#6b7280;"><?= empty(trim($dato['observacion'])) ? 'N/A' : htmlspecialchars($dato['observacion']) ?></span>
                                    </div>
                                    <div><?= $botonesAccion ?></div>
                                </div>
                            </div>
                            <?php if (empty($registro)) { ?>
                                <div class="listado_vacio"><p>No hay devoluciones activas.</p></div>
                            <?php } else {
                                foreach ($registro as $dato) { ?>
                                    <div class="listado_contenedor_grupal">
                                        <div class="listado_item">
                                            <div class="listado_col_datos">
                                                <div class="listado_dato_grupo" style="width: 15%;">
                                                    <small>Fecha Devolución</small>
                                                    <span style="font-weight: bold;"><?= $dato['fecha_devolucion'] ?></span>
                                                </div>
                                                <div class="listado_dato_grupo" style="width: 35%;">
                                                    <small>Asignaciones</small>
                                                    <span style="font-weight: bold; color: var(--texto-principal);"><?= $dato['asignaciones'] ?></span>
                                                    <small>ID Asig: <?= $dato['id_asignacion'] ?></small>
                                                </div>
                                                <div class="listado_dato_grupo" style="width: 30%;">
                                                    <small>Estado</small>
                                                    <span><?= $dato['articulo'] ?></span>
                                                    <small style="color: #6c757d;">(Pza ID: #<?= $dato['id_estado'] ?>)</small>
                                                </div>
                                            </div>
                                            <div class="listado_col_acciones">
                                                <div style="display:flex; gap:5px;">
                                                    <?php if (!empty($permisos['modificar'])) { ?>
                                                        <button class="btn_t cbt_v" onclick="editar(<?= $dato['id_devolucion'] ?>, <?= $dato['id_asignacion'] ?>, <?= $dato['id_estado'] ?>, '<?= $dato['fecha_devolucion'] ?>', '<?= $dato['observacion'] ?>')" title="Modificar"><i class="fi fi-sr-edit"></i></button>
                                                    <?php } ?>
                                                    <?php if (!empty($permisos['eliminar'])) { ?>
                                                        <button class="btn_t cbt_r" onclick="anular(<?= $dato['id_devolucion'] ?>)" title="Anular Devolución"><i class="fi fi-sr-ban"></i></button>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php }
                            } ?>

            <?php if ($index === $totalRegistros - 1): ?>
                            </div>
                        </div>
                    </div>
                </div> 
            <?php endif; ?>

        <?php endforeach; ?>
    <?php endif; ?>

<?php if (!isset($solo_lista)) { ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="contenedor_modal" id="contenedor_modal">
        <div class="modal modal_mediano ocultar" id="modal">
            <div class="cabecera_modal">
                <h2 class="titulo_modal" id="titulo_modal">Registrar Devolución</h2>
                <a type="button" class="cerrar_modal" id="cerrar_modal">&times;</a>
            </div>
            <div class="contenido_modal">
                <form id="f" autocomplete="off">
                    <input type="hidden" id="id_devolucion" name="id_devolucion">
                    
                    <div class="row">
                        <div class="colum" style="width: 100%;">
                            <label for="id_asignacion">Asignacion</label>
                            <div class="caja_formulario">
                                <select class="formulario select2" id="id_asignacion" name="id_asignacion">
                                    <option value=""></option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row" style="margin-top: 15px; display: flex; gap: 15px;">
                        <div class="colum" style="width: 50%;">
                            <label for="fecha_devolucion">Fecha de Devolucion</label>
                            <div class="caja_formulario">
                                <input type="date" class="formulario" id="fecha_devolucion" name="fecha_devolucion">
                            </div>
                        </div>
                        <div class="colum" style="width: 50%;">
                            <label for="id_estado">Calidad</label>
                            <div class="caja_formulario">
                                <select class="formulario select2" id="id_estado" name="id_estado">
                                    <option value=""></option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row" style="margin-top: 15px;">
                        <div class="colum" style="width: 100%;">
                            <label for="observacion">Observación</label>
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="observacion" name="observacion">
                            </div>
                        </div>
                    </div>

                    <div class="row" style="margin-top: 25px;">
                        <div class="colum">
                            <button type="button" class="btn btn_azul" id="btn_guardar" data-accion="incluir">Confirmar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
    <script src="js/main.js"></script>
    <script src="js/devoluciones.js"></script>
</body>
</html>
                            <?php } ?>
