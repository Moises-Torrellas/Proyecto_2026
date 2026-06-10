<?php if (isset($solo_lista) && $solo_lista === true) :
    // RECARGA VÍA AJAX
    $registro = ($tipo_lista === 'individual') ? ($registroInd ?? []) : ($registroGrp ?? []);
    
    if (empty($registro)) : ?>
        <div class="listado_vacio">
            <p>No se encontraron registros</p>
        </div>
    <?php else :
        // 1. PRIMER BUCLE: Recorremos los Atletas o Equipos
        foreach ($registro as $dato) :
            
            // Preparamos la info de la cabecera dependiendo del tipo
            if ($tipo_lista === 'individual') {
                $nombrePadre = $dato['atleta_nombres'] . ' ' . $dato['atleta_apellidos'];
                $iconoPadre = '<i class="icon_con" data-lucide="circle-user"></i>';
                $datoSub = 'Atleta';
            } else {
                $nombrePadre = $dato['nombre_equipo'];
                $iconoPadre = '<i class="icon_con" data-lucide="shield-half"></i>';
                $datoSub = 'Equipo';
            }
            ?>
            
            <div class="listado_contenedor_grupal">
                <div class="listado_item" onclick="toggleDetalles(this)">
                    <div class="listado_col_principal">
                        <div class="listado_avatar_null"><?= $iconoPadre ?></div>
                        <div class="listado_info_base">
                            <span class="listado_titulo"><?= htmlspecialchars($nombrePadre) ?></span>
                            <?php if ($tipo_lista === 'grupal') : ?>
                                <span class="listado_subtitulo"><?= htmlspecialchars($dato['nombre_categoria']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="listado_col_datos">
                        <div class="listado_dato_grupo">
                            <small>Tipo</small>
                            <span class="estatus_v"><?= $datoSub ?></span>
                        </div>
                        <div class="listado_dato_grupo">
                            <small>Total Premios</small>
                            <span><?= $dato['total_premios'] ?></span>
                        </div>
                    </div>

                    <div class="listado_col_acciones">
                        <i data-lucide="chevron-down" class="icono_flecha_detalle"></i>
                    </div>
                </div>

                <div class="listado_detalle_oculto">
                    <div class="detalle_expandido_container" style="padding: 15px;">
                        <div class="lista_sub_items">
                            
                            <?php 
                            // 2. SEGUNDO BUCLE: Recorremos los premios específicos
                            foreach ($dato['premios'] as $premio) : 
                                
                                $id_item = ($tipo_lista === 'individual') ? $premio['id_individual'] : $premio['id_grupal'];
                                $tipo_param = "'{$tipo_lista}'";

                                $botonesAccion = '';
                                if ((int)$premio['en_historial'] === 0) {
                                    if (isset($permisos['modificar']) && $permisos['modificar']) {
                                        $botonesAccion .= '<button class="btn_t cbt_v" onclick="buscar(' . $id_item . ', ' . $tipo_param . ')" data-tippy-content="Modificar"><i class="fi fi-sr-pencil"></i></button> ';
                                    }
                                    if (isset($permisos['eliminar']) && $permisos['eliminar']) {
                                        $botonesAccion .= '<button class="btn_t cbt_r" onclick="eliminar(' . $id_item . ', ' . $tipo_param . ')" data-tippy-content="Eliminar"><i class="fi fi-sr-trash"></i></button>';
                                    }
                                } else {
                                    $botonesAccion = '<span class="texto_alerta" style="font-size: 0.8rem; padding: 5px; border: 1px solid red; border-radius: 5px;" data-tippy-content="En historial. No se puede modificar ni eliminar."><i class="fi fi-sr-lock"></i> Bloqueado</span>';
                                }
                                ?>
                                
                                <div class="sub_item_fila">
                                    <div class="sub_item_info" style="flex: 1.5;">
                                        <span class="sub_item_titulo"><?= htmlspecialchars($premio['nombre_premio']) ?></span>
                                    </div>
                                    <div class="sub_item_centro" style="flex: 2;">
                                        <span class="titulo_b"><?= htmlspecialchars($premio['nombre_torneo']) ?></span>
                                    </div>
                                    <div class="sub_item_centro">
                                        <span class="estatus_v"><?= explode(' ', $premio['fecha_torneo'])[0] ?></span>
                                    </div>
                                    <div class="sub_item_acciones">
                                        <?= $botonesAccion ?>
                                    </div>
                                </div>
                                
                            <?php endforeach; ?>
                            
                        </div>
                    </div>
                </div>
            </div>
            
        <?php endforeach; ?>
        
<?php endif;
    exit();
endif; ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <?php include('complementos/head.php'); ?>
    <title>Palmarés</title>
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
                            <h2 class="titulo_pagina" id="titulo">Palmarés</h2>
                        </div>
                        <div class="contenedor_busqueda">
                            <input type="text" placeholder="Buscar..." autocomplete="off" id="busqueda">
                            <i class="fi fi-br-search icon_input"></i>
                        </div>
                        <div class="botones">
                            <?php if ($permisos['registrar']) : ?>
                                <button class="btn btn_azul" id="incluir">Nuevo Palmarés</button>
                            <?php endif; ?>
                            <?php if ($permisos['reporte']) : ?>
                                <button class="btn btn_verde" id="generar">Generar Reporte</button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="pestanas-contenedor">
                        <div class="pestanas-header">
                            <button class="pestana-btn activa" data-target="#tab-individual"><i class="fi fi-sr-user"></i> Palmarés Individual</button>
                            <button class="pestana-btn" data-target="#tab-grupal"><i class="fi fi-sr-users"></i> Palmarés Grupal</button>
                        </div>
                        <div class="pestanas-body">
                            
                            <div class="pestana-content activa" id="tab-individual" style="flex: 1; flex-direction: column;">
                                <div class="contenedor_resultados" style="flex-grow: 1; overflow-y: auto;">
                                    <div id="resultadoconsulta-ind" class="resultadoconsulta" data-tipo="individual">
                                        <?php if (empty($registroInd)) : ?>
                                            <div class="listado_vacio">
                                                <p>No se encontraron registros</p>
                                            </div>
                                        <?php else : ?>
                                            <?php foreach ($registroInd as $dato) : ?>
                                                <div class="listado_contenedor_grupal">
                                                    <div class="listado_item" onclick="toggleDetalles(this)">
                                                        <div class="listado_col_principal">
                                                            <div class="listado_avatar_null"><i class="icon_con" data-lucide="circle-user"></i></div>
                                                            <div class="listado_info_base">
                                                                <span class="listado_titulo"><?= htmlspecialchars($dato['atleta_nombres'] . ' ' . $dato['atleta_apellidos']) ?></span>
                                                            </div>
                                                        </div>

                                                        <div class="listado_col_datos">
                                                            <div class="listado_dato_grupo">
                                                                <small>Tipo</small>
                                                                <span class="estatus_v">Atleta</span>
                                                            </div>
                                                            <div class="listado_dato_grupo">
                                                                <small>Total Premios</small>
                                                                <span><?= $dato['total_premios'] ?></span>
                                                            </div>
                                                        </div>

                                                        <div class="listado_col_acciones">
                                                            <i data-lucide="chevron-down" class="icono_flecha_detalle"></i>
                                                        </div>
                                                    </div>

                                                    <div class="listado_detalle_oculto">
                                                        <div class="detalle_expandido_container" style="padding: 15px;">
                                                            <div class="lista_sub_items">
                                                                <?php foreach ($dato['premios'] as $premio) : 
                                                                    $id_item = $premio['id_individual'];
                                                                    $tipo_param = "'individual'";

                                                                    $botonesAccion = '';
                                                                    if ((int)$premio['en_historial'] === 0) {
                                                                        if (isset($permisos['modificar']) && $permisos['modificar']) {
                                                                            $botonesAccion .= '<button class="btn_t cbt_v" onclick="buscar(' . $id_item . ', ' . $tipo_param . ')" data-tippy-content="Modificar"><i class="fi fi-sr-pencil"></i></button> ';
                                                                        }
                                                                        if (isset($permisos['eliminar']) && $permisos['eliminar']) {
                                                                            $botonesAccion .= '<button class="btn_t cbt_r" onclick="eliminar(' . $id_item . ', ' . $tipo_param . ')" data-tippy-content="Eliminar"><i class="fi fi-sr-trash"></i></button>';
                                                                        }
                                                                    } else {
                                                                        $botonesAccion = '<span class="texto_alerta" style="font-size: 0.8rem; padding: 5px; border: 1px solid red; border-radius: 5px;" data-tippy-content="En historial. No se puede modificar ni eliminar."><i class="fi fi-sr-lock"></i> Bloqueado</span>';
                                                                    }
                                                                ?>
                                                                    <div class="sub_item_fila">
                                                                        <div class="sub_item_info" style="flex: 1.5;">
                                                                            <span class="sub_item_titulo"><?= htmlspecialchars($premio['nombre_premio']) ?></span>
                                                                        </div>
                                                                        <div class="sub_item_centro" style="flex: 2;">
                                                                            <span class="titulo_b"><?= htmlspecialchars($premio['nombre_torneo']) ?></span>
                                                                        </div>
                                                                        <div class="sub_item_centro">
                                                                            <span class="estatus_v"><?= explode(' ', $premio['fecha_torneo'])[0] ?></span>
                                                                        </div>
                                                                        <div class="sub_item_acciones">
                                                                            <?= $botonesAccion ?>
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
                                <div class="contenedor_botonera" style="margin-top: auto;">
                                    <div class="c_paginacion">
                                        <select id="rowsPerPage-ind" class="cantidad_paginacion select">
                                            <option value="10" selected>10</option>
                                            <option value="20">20</option>
                                            <option value="50">50</option>
                                        </select>
                                    </div>
                                    <div id="botonera-ind" class="c_paginacion_botonera"></div>
                                    <div class="c_paginacion">
                                        <span class="cantidad_registros">Mostrando <span id="cantidadRegistros-ind"></span> registros</span>
                                    </div>
                                </div>
                            </div>

                            <div class="pestana-content" id="tab-grupal" style="flex: 1; flex-direction: column;">
                                <div class="contenedor_resultados" style="flex-grow: 1; overflow-y: auto;">
                                    <div id="resultadoconsulta-grp" class="resultadoconsulta" data-tipo="grupal">
                                        <?php if (empty($registroGrp)) : ?>
                                            <div class="listado_vacio">
                                                <p>No se encontraron registros</p>
                                            </div>
                                        <?php else : ?>
                                            <?php foreach ($registroGrp as $dato) : ?>
                                                <div class="listado_contenedor_grupal">
                                                    <div class="listado_item" onclick="toggleDetalles(this)">
                                                        <div class="listado_col_principal">
                                                            <div class="listado_avatar_null"><i class="icon_con" data-lucide="shield-half"></i></div>
                                                            <div class="listado_info_base">
                                                                <span class="listado_titulo"><?= htmlspecialchars($dato['nombre_equipo']) ?></span>
                                                                <span class="listado_subtitulo"><?= htmlspecialchars($dato['nombre_categoria']) ?></span>
                                                            </div>
                                                        </div>

                                                        <div class="listado_col_datos">
                                                            <div class="listado_dato_grupo">
                                                                <small>Tipo</small>
                                                                <span class="estatus_v">Equipo</span>
                                                            </div>
                                                            <div class="listado_dato_grupo">
                                                                <small>Total Premios</small>
                                                                <span><?= $dato['total_premios'] ?></span>
                                                            </div>
                                                        </div>

                                                        <div class="listado_col_acciones">
                                                            <i data-lucide="chevron-down" class="icono_flecha_detalle"></i>
                                                        </div>
                                                    </div>

                                                    <div class="listado_detalle_oculto">
                                                        <div class="detalle_expandido_container" style="padding: 15px;">
                                                            <div class="lista_sub_items">
                                                                <?php foreach ($dato['premios'] as $premio) : 
                                                                    $id_item = $premio['id_grupal'];
                                                                    $tipo_param = "'grupal'";

                                                                    $botonesAccion = '';
                                                                    if ((int)$premio['en_historial'] === 0) {
                                                                        if (isset($permisos['modificar']) && $permisos['modificar']) {
                                                                            $botonesAccion .= '<button class="btn_t cbt_v" onclick="buscar(' . $id_item . ', ' . $tipo_param . ')" data-tippy-content="Modificar"><i class="fi fi-sr-pencil"></i></button> ';
                                                                        }
                                                                        if (isset($permisos['eliminar']) && $permisos['eliminar']) {
                                                                            $botonesAccion .= '<button class="btn_t cbt_r" onclick="eliminar(' . $id_item . ', ' . $tipo_param . ')" data-tippy-content="Eliminar"><i class="fi fi-sr-trash"></i></button>';
                                                                        }
                                                                    } else {
                                                                        $botonesAccion = '<span class="texto_alerta" style="font-size: 0.8rem; padding: 5px; border: 1px solid red; border-radius: 5px;" data-tippy-content="En historial. No se puede modificar ni eliminar."><i class="fi fi-sr-lock"></i> Bloqueado</span>';
                                                                    }
                                                                ?>
                                                                    <div class="sub_item_fila">
                                                                        <div class="sub_item_info" style="flex: 1.5;">
                                                                            <span class="sub_item_titulo"><?= htmlspecialchars($premio['nombre_premio']) ?></span>
                                                                        </div>
                                                                        <div class="sub_item_centro" style="flex: 2;">
                                                                            <span class="titulo_b"><?= htmlspecialchars($premio['nombre_torneo']) ?></span>
                                                                        </div>
                                                                        <div class="sub_item_centro">
                                                                            <span class="estatus_v"><?= explode(' ', $premio['fecha_torneo'])[0] ?></span>
                                                                        </div>
                                                                        <div class="sub_item_acciones">
                                                                            <?= $botonesAccion ?>
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
                                <div class="contenedor_botonera" style="margin-top: auto;">
                                    <div class="c_paginacion">
                                        <select id="rowsPerPage-grp" class="cantidad_paginacion select">
                                            <option value="10" selected>10</option>
                                            <option value="20">20</option>
                                            <option value="50">50</option>
                                        </select>
                                    </div>
                                    <div id="botonera-grp" class="c_paginacion_botonera"></div>
                                    <div class="c_paginacion">
                                        <span class="cantidad_registros">Mostrando <span id="cantidadRegistros-grp"></span> registros</span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>

    <section class="contenedor_modal" id="contenedor_modal">
        <div class="modal ocultar" id="modal">
            <div class="cabecera_modal">
                <h2 class="titulo_modal" id="titulo_modal">Gestión de Palmarés</h2>
                <a type="button" class="cerrar_modal" id="cerrar_modal">&times;</a>
            </div>
            <div class="contenido_modal">
                <form id="f" autocomplete="off">
                    <input type="hidden" id="id" name="id">
                    <input type="hidden" id="tipo_palmares" name="tipo_palmares">

                    <div id="seccion_individual" style="display: none;">
                        <div class="row">
                            <div class="colum">
                                <div class="caja_formulario">
                                    <select name="torneo" id="torneo_ind" class="formulario select">
                                        <option value="">Seleccione...</option>
                                    </select>
                                    <label for="torneo_ind" class="titulo_formulario">Torneo</label>
                                    <span class="mensaje" id="torneo_ind_span"></span>
                                </div>
                            </div>
                            <div class="colum">
                                <div class="caja_formulario">
                                    <select name="premio" id="premio_ind" class="formulario select">
                                        <option value="">Seleccione...</option>
                                    </select>
                                    <label for="premio_ind" class="titulo_formulario">Premio (Individual)</label>
                                    <span class="mensaje" id="premio_ind_span"></span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="colum">
                                <div class="caja_formulario">
                                    <select name="atleta" id="atleta" class="formulario select">
                                        <option value="">Seleccione...</option>
                                    </select>
                                    <label for="atleta" class="titulo_formulario">Atleta</label>
                                    <span class="mensaje" id="atleta_span"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="seccion_grupal" style="display: none;">
                        <div class="row">
                            <div class="colum">
                                <div class="caja_formulario">
                                    <select name="torneo" id="torneo_grp" class="formulario select">
                                        <option value="">Seleccione...</option>
                                    </select>
                                    <label for="torneo_grp" class="titulo_formulario">Torneo</label>
                                    <span class="mensaje" id="torneo_grp_span"></span>
                                </div>
                            </div>
                            <div class="colum">
                                <div class="caja_formulario">
                                    <select name="premio" id="premio_grp" class="formulario select">
                                        <option value="">Seleccione...</option>
                                    </select>
                                    <label for="premio_grp" class="titulo_formulario">Premio (Grupal)</label>
                                    <span class="mensaje" id="premio_grp_span"></span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="colum">
                                <div class="caja_formulario">
                                    <select name="equipo" id="equipo" class="formulario select">
                                        <option value="">Seleccione...</option>
                                    </select>
                                    <label for="equipo" class="titulo_formulario">Equipo</label>
                                    <span class="mensaje" id="equipo_span"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="colum">
                            <button type="button" class="btn btn_azul" id="proceso"></button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <script src="js/main.js"></script>
    <script src="js/palmares.js"></script>
</body>
</html>