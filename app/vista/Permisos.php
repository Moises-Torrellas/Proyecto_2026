<?php if (isset($solo_lista) && $solo_lista === true) :
    if (empty($registro)) : ?>
        <div class="listado_vacio">
            <p>No se encontraron registros</p>
        </div>
        <?php else :
        $moduloActual = null;
        $totalRegistros = count($registro);

        foreach ($registro as $index => $dato) :
            $idModulo = $dato['id_modulo'];

            if ($idModulo !== $moduloActual) :

                if ($moduloActual !== null) : ?>
                    </div>
                    </div>
                    </div>
                    </div> <?php endif;

                        $moduloActual = $idModulo;

                        $idxAux = $index;
                        $cantidadPermisos = 0;
                        while (isset($registro[$idxAux]) && $registro[$idxAux]['id_modulo'] == $idModulo) {
                            $cantidadPermisos++;
                            $idxAux++;
                        }

                        $estatusModulo = (int)$dato['estatus_modulo'];
                        $textoEstatus = ($estatusModulo === 1) ? 'Activo' : 'Bloqueado';
                        $claseEstatus = ($estatusModulo === 1) ? 'estatus_v' : 'estatus_r';

                            ?>
                <div class="listado_contenedor_grupal">

                    <div class="listado_item" onclick="toggleDetalles(this)">
                        <div class="listado_col_principal">
                            <div class="listado_avatar_null"><i class="icon_con" data-lucide="<?= $dato['icono'] ?>"></i></div>
                            <div class="listado_info_base">
                                <span class="listado_titulo"><?= htmlspecialchars($dato['nombre_modulo']) ?></span>
                            </div>
                        </div>

                        <div class="listado_col_datos">
                            <div class="listado_dato_grupo">
                                <small>Estado Módulo</small>
                                <span class="<?= $claseEstatus ?>"><?= $textoEstatus ?></span>
                            </div>
                            <div class="listado_dato_grupo">
                                <small>Permisos Asignados</small>
                                <span><?= $cantidadPermisos ?> Permiso(s)</span>
                            </div>
                        </div>

                        <div class="listado_col_acciones">
                            <i data-lucide="chevron-down" class="icono_flecha_detalle"></i>
                        </div>
                    </div>

                    <div class="listado_detalle_oculto">
                        <div class="detalle_expandido_container" style="padding: 15px;">

                            <div class="tarjeta_resumen estado_exito">
                                <div class="tarjeta_icono"><i data-lucide="key"></i></div>
                                <div class="tarjeta_texto">
                                    <label>Resumen del Módulo</label>
                                    <span class="texto_resaltado">Total de Permisos: <?= $cantidadPermisos ?></span>
                                </div>
                            </div>

                            <hr class="separador_seccion">

                            <div class="lista_sub_items">
                            <?php endif;
                        $estatusPermiso = (isset($dato['estatus_permiso'])) ? (int)$dato['estatus_permiso'] : 1;
                        $icon = ($estatusPermiso === 1) ? 'fi-sr-unlock' : 'fi-sr-lock';
                        $color = ($estatusPermiso === 1) ? 'cbt_g' : 'cbt_a';

                        $botonesAccion = '';
                        if (!empty($permisos['modificar_permisos'])) {
                            $botonesAccion .= '<button class="btn_t cbt_v" onclick="buscar(' . $dato['id_permiso'] . ')" data-tippy-content="Modificar"><i class="fi fi-sr-pencil"></i></button> ';
                        }
                        if (!empty($permisos['bloquear_permisos'])) {
                            // Se asume que el cambio de estatus a inactivo funge como eliminar
                            $botonesAccion .= '<button class="btn_t ' . $color . '" onclick="bloquear(' . $dato['id_permiso'] . ','.$dato['estatus_permiso'].')" data-tippy-content="Bloquear"><i class="fi ' . $icon . '"></i></button>';
                        }

                        // Lógica de estatus individual para el permiso

                        $textoPermiso = ($estatusPermiso === 1) ? 'Activo' : 'Bloqueado';
                        $clasePermiso = ($estatusPermiso === 1) ? 'estatus_v' : 'estatus_r';

                            ?>
                            <div class="sub_item_fila">
                                <div class="sub_item_info" style="flex: 2;">
                                    <span class="sub_item_titulo"><?= htmlspecialchars($dato['nombre_permiso']) ?></span>
                                    <small style="display: block; color: #666; font-size: 0.85em; margin-top: 2px;">Descripción: <?= htmlspecialchars($dato['descripcion']) ?></small>
                                    <small style="display: block; color: #666; font-size: 0.85em; margin-top: 2px;">Clave: <?= htmlspecialchars($dato['clave']) ?></small>
                                </div>

                                <div class="sub_item_centro">
                                    <span class="<?= $clasePermiso ?>"><?= $textoPermiso ?></span>
                                </div>

                                <div class="sub_item_acciones">
                                    <?= $botonesAccion ?>
                                </div>
                            </div>

                            <?php if ($index === $totalRegistros - 1) : ?>
                            </div>
                        </div>
                    </div>
                </div> <?php endif; ?>

        <?php endforeach; ?>
    <?php endif;
    exit(); ?>
<?php endif; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include('complementos/head.php'); ?>
    <title>Permisos</title>
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
                            <h2 class="titulo_pagina" id="titulo">Permisos</h2>
                        </div>
                        <div class="contenedor_busqueda">
                            <input type="text" placeholder="Buscar..." autocomplete="off" id="busqueda">
                            <i class="fi fi-br-search icon_input"></i>
                        </div>
                        <div class="botones">
                            <?php if (!empty($permisos['registrar_permisos'])) : ?>
                            <button class="btn btn_azul" id="incluir">Nuevo Permiso</button>
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
                                $moduloActual = null;
                                $totalRegistros = count($registro);

                                foreach ($registro as $index => $dato) :
                                    $idModulo = $dato['id_modulo'];

                                    if ($idModulo !== $moduloActual) :

                                        if ($moduloActual !== null) : ?>
                        </div>
                    </div>
                </div>
            </div> <?php endif;

                                        $moduloActual = $idModulo;

                                        $idxAux = $index;
                                        $cantidadPermisos = 0;
                                        while (isset($registro[$idxAux]) && $registro[$idxAux]['id_modulo'] == $idModulo) {
                                            $cantidadPermisos++;
                                            $idxAux++;
                                        }

                                        $estatusModulo = (int)$dato['estatus_modulo'];
                                        $textoEstatus = ($estatusModulo === 1) ? 'Activo' : 'Bloqueado';
                                        $claseEstatus = ($estatusModulo === 1) ? 'estatus_v' : 'estatus_r';

                    ?>
        <div class="listado_contenedor_grupal">

            <div class="listado_item" onclick="toggleDetalles(this)">
                <div class="listado_col_principal">
                    <div class="listado_avatar_null"><i class="icon_con" data-lucide="<?= $dato['icono'] ?>"></i></div>
                    <div class="listado_info_base">
                        <span class="listado_titulo"><?= htmlspecialchars($dato['nombre_modulo']) ?></span>
                    </div>
                </div>

                <div class="listado_col_datos">
                    <div class="listado_dato_grupo">
                        <small>Estado Módulo</small>
                        <span class="<?= $claseEstatus ?>"><?= $textoEstatus ?></span>
                    </div>
                    <div class="listado_dato_grupo">
                        <small>Permisos Asignados</small>
                        <span><?= $cantidadPermisos ?> Permiso(s)</span>
                    </div>
                </div>

                <div class="listado_col_acciones">
                    <i data-lucide="chevron-down" class="icono_flecha_detalle"></i>
                </div>
            </div>

            <div class="listado_detalle_oculto">
                <div class="detalle_expandido_container" style="padding: 15px;">

                    <div class="tarjeta_resumen estado_exito">
                        <div class="tarjeta_icono"><i data-lucide="key"></i></div>
                        <div class="tarjeta_texto">
                            <label>Resumen del Módulo</label>
                            <span class="texto_resaltado">Total de Permisos: <?= $cantidadPermisos ?></span>
                        </div>
                    </div>

                    <hr class="separador_seccion">

                    <div class="lista_sub_items">
                    <?php endif;
                                    $estatusPermiso = (isset($dato['estatus_permiso'])) ? (int)$dato['estatus_permiso'] : 1;
                                    $icon = ($estatusPermiso === 1) ? 'fi-sr-unlock' : 'fi-sr-lock';
                                    $color = ($estatusPermiso === 1) ? 'cbt_g' : 'cbt_a';

                                    $botonesAccion = '';
                                    if (!empty($permisos['modificar_permisos'])) {
                                        $botonesAccion .= '<button class="btn_t cbt_v" onclick="buscar(' . $dato['id_permiso'] . ')" data-tippy-content="Modificar"><i class="fi fi-sr-pencil"></i></button> ';
                                    }
                                    if (!empty($permisos['bloquear_permisos'])) {
                                        // Se asume que el cambio de estatus a inactivo funge como eliminar
                                        $botonesAccion .= '<button class="btn_t ' . $color . '" onclick="bloquear(' . $dato['id_permiso'] . ','.$dato['estatus_permiso'].')" data-tippy-content="Bloquear"><i class="fi ' . $icon . '"></i></button>';
                                    }

                                    // Lógica de estatus individual para el permiso

                                    $textoPermiso = ($estatusPermiso === 1) ? 'Activo' : 'Bloqueado';
                                    $clasePermiso = ($estatusPermiso === 1) ? 'estatus_v' : 'estatus_r';

                    ?>
                    <div class="sub_item_fila">
                        <div class="sub_item_info" style="flex: 2;">
                            <span class="sub_item_titulo"><?= htmlspecialchars($dato['nombre_permiso']) ?></span>
                            <small style="display: block; color: #666; font-size: 0.85em; margin-top: 2px;">Descripción: <?= htmlspecialchars($dato['descripcion']) ?></small>
                            <small style="display: block; color: #666; font-size: 0.85em; margin-top: 2px;">Clave: <?= htmlspecialchars($dato['clave']) ?></small>
                        </div>

                        <div class="sub_item_centro">
                            <span class="<?= $clasePermiso ?>"><?= $textoPermiso ?></span>
                        </div>

                        <div class="sub_item_acciones">
                            <?= $botonesAccion ?>
                        </div>
                    </div>

                    <?php if ($index === $totalRegistros - 1) : ?>
                    </div>
                </div>
            </div>
        </div> <?php endif; ?>

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
                                <label for="nombre" class="titulo_formulario">Nombre del Permiso</label>
                                <span class="mensaje" id="nombre_spam"></span>
                            </div>
                        </div>
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="descripcion" name="descripcion">
                                <label for="descripcion" class="titulo_formulario">Descripcion (Opcional)</label>
                                <span class="mensaje" id="descripcion_spam"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="clave" name="clave">
                                <label for="clave" class="titulo_formulario">Clave del Permiso</label>
                                <span class="mensaje" id="clave_spam"></span>
                            </div>
                        </div>
                        <div class="colum">
                            <div class="caja_formulario">
                                <select name="modulo" id="modulo" class="formulario select">
                                </select>
                                <label for="modulo" class="titulo_formulario">Modulo</label>
                                <span class="mensaje" id="modulo_span"></span>
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
    <script src="js/permisos.js"></script>
    <?php include('complementos/mensajeError.php'); ?>
</body>

</html>