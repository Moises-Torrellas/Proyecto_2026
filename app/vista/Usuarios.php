<?php
if (isset($solo_lista) && $solo_lista === true) :
    if (empty($registro)) : ?>
        <div class="listado_vacio">
            <p>No se encontraron registros</p>
        </div>
        <?php else :
        foreach ($registro as $dato) :
            // 1. Lógica de colores e íconos para el estado de bloqueo
            $icon  = ($dato['bloqueo'] == 1) ? 'fi-sr-unlock' : 'fi-sr-lock';
            $color = ($dato['bloqueo'] == 1) ? 'cbt_g' : 'cbt_a';
        ?>
            <div class="listado_contenedor_grupal">
                <div class="listado_item" onclick="toggleDetalles(this)">

                    <div class="listado_col_principal">
                        <?php if ($dato['foto'] == 'default.png') : ?>
                            <div class="listado_avatar_null"><i class="icon_con" data-lucide="circle-user"></i></div>
                        <?php else : ?>
                            <img src="img/usuarios/<?= htmlspecialchars($dato['foto']) ?>" class="listado_avatar" alt="Perfil" onerror="manejarErrorCamara(this)">
                        <?php endif; ?>

                        <div class="listado_info_base">
                            <span class="listado_titulo"><?= htmlspecialchars($dato['nombreUsuario']) ?> <?= htmlspecialchars($dato['apellidoUsuario']) ?></span>
                            <span class="listado_subtitulo"><?= htmlspecialchars($dato['correo']) ?></span>
                        </div>
                    </div>

                    <div class="listado_col_datos">
                        <div class="listado_dato_grupo">
                            <small>Cédula</small>
                            <span><?= htmlspecialchars($dato['cedulaUsuario']) ?></span>
                        </div>
                        <div class="listado_dato_grupo">
                            <small>Telefono</small>
                            <span><?= htmlspecialchars($dato['telefonoUsuario']) ?></span>
                        </div>
                        <div class="listado_dato_grupo">
                            <small>Rol</small>
                            <span class="listado_resaltado"><?= htmlspecialchars($dato['nombre_rol']) ?></span>
                        </div>
                    </div>

                    <div class="listado_col_acciones">
                        <div onclick="event.stopPropagation();" style="display:flex; gap:5px;">
                            <?php if (isset($permisos['otros']) && $permisos['otros']) : ?>
                                <button class="btn_t cbt_m" onclick="CargarPermisos(<?= $dato['idUsuario'] ?>)"><i class="fi fi-sr-user-permissions"></i></button>
                            <?php endif; ?>

                            <?php if (isset($permisos['modificar']) && $permisos['modificar']) : ?>
                                <button class="btn_t cbt_v" onclick="buscar(<?= $dato['idUsuario'] ?>)"><i class="fi fi-sr-pencil"></i></button>
                            <?php endif; ?>

                            <?php if (isset($permisos['eliminar']) && $permisos['eliminar']) : ?>
                                <button class="btn_t cbt_r" onclick="eliminar(<?= $dato['idUsuario'] ?>)"><i class="fi fi-sr-trash-xmark"></i></button>
                            <?php endif; ?>

                            <?php if (isset($permisos['otros']) && $permisos['otros']) : ?>
                                <button class="btn_t <?= $color ?>" onclick="bloquear(<?= $dato['idUsuario'] ?>, <?= $dato['bloqueo'] ?>, this)"><i class="fi <?= $icon ?>"></i></button>
                            <?php endif; ?>
                        </div>
                        <i data-lucide="chevron-down" class="icono_flecha_detalle"></i>
                    </div>
                </div>

                <div class="listado_detalle_oculto">
                    <div class="detalle_expandido_container">
                        <div class="detalle_fila">
                            <div class="detalle_card">
                                <div class="detalle_card_icon"><i data-lucide="calendar"></i></div>
                                <div class="detalle_card_txt">
                                    <label>Ultimo Ingreso</label>
                                    <span><?= !empty($dato['ultimo_ingreso']) ? date('d/m/Y h:i A', strtotime($dato['ultimo_ingreso'])) : 'Sin Ingresos Al Sistema' ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        <?php endforeach; ?>
    <?php endif;
    exit(); ?>
<?php endif; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include('complementos/head.php'); ?>
    <title>Gestionar Usuarios</title>
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
                            <h2 class="titulo_pagina" id="titulo">Usuarios</h2>
                        </div>
                        <div class="contenedor_busqueda">
                            <input type="text" placeholder="Buscar..." autocomplete="off" id="busqueda">
                            <i class="fi fi-br-search icon_input"></i>
                        </div>
                        <div class="botones">
                            <?php //if ($permisos['registrar']) : ?>
                                <button class="btn btn_azul" id="incluir">Nuevo Usuario</button>
                            <?php //endif; ?>
                            <?php //if ($permisos['reporte']) : ?>
                                <button class="btn btn_verde" id="generar">Generar Reporte</button>
                            <?php //endif; ?>
                        </div>
                    </div>
                    <div class="contenedor_resultados">
                        <div id="resultadoconsulta" class="resultadoconsulta">
                            <?php if (empty($registro)) : ?>
                                <div class="listado_vacio">
                                    <p>No se encontraron registros</p>
                                </div>
                                <?php else :
                                foreach ($registro as $dato) :
                                    // 1. Lógica de colores e íconos para el estado de bloqueo
                                    $icon  = ($dato['bloqueo'] == 1) ? 'fi-sr-unlock' : 'fi-sr-lock';
                                    $color = ($dato['bloqueo'] == 1) ? 'cbt_g' : 'cbt_a';
                                ?>
                                    <div class="listado_contenedor_grupal">
                                        <div class="listado_item" onclick="toggleDetalles(this)">

                                            <div class="listado_col_principal">
                                                <?php if ($dato['foto'] == 'default.png') : ?>
                                                    <div class="listado_avatar_null"><i class="icon_con" data-lucide="circle-user"></i></div>
                                                <?php else : ?>
                                                    <img src="img/usuarios/<?= htmlspecialchars($dato['foto']) ?>" class="listado_avatar" alt="Perfil" onerror="manejarErrorCamara(this)">
                                                <?php endif; ?>

                                                <div class="listado_info_base">
                                                    <span class="listado_titulo"><?= htmlspecialchars($dato['nombreUsuario']) ?> <?= htmlspecialchars($dato['apellidoUsuario']) ?></span>
                                                    <span class="listado_subtitulo"><?= htmlspecialchars($dato['correo']) ?></span>
                                                </div>
                                            </div>

                                            <div class="listado_col_datos">
                                                <div class="listado_dato_grupo">
                                                    <small>Cédula</small>
                                                    <span><?= htmlspecialchars($dato['cedulaUsuario']) ?></span>
                                                </div>
                                                <div class="listado_dato_grupo">
                                                    <small>Telefono</small>
                                                    <span><?= htmlspecialchars($dato['telefonoUsuario']) ?></span>
                                                </div>
                                                <div class="listado_dato_grupo">
                                                    <small>Rol</small>
                                                    <span class="listado_resaltado"><?= htmlspecialchars($dato['nombre_rol']) ?></span>
                                                </div>
                                            </div>

                                            <div class="listado_col_acciones">
                                                <div onclick="event.stopPropagation();" style="display:flex; gap:5px;">
                                                    <?php //if (isset($permisos['otros']) && $permisos['otros']) : ?>
                                                        <button class="btn_t cbt_m" onclick="CargarPermisos(<?= $dato['idUsuario'] ?>)"><i class="fi fi-sr-user-permissions"></i></button>
                                                    <?php //endif; ?>

                                                    <?php //if (isset($permisos['modificar']) && $permisos['modificar']) : ?>
                                                        <button class="btn_t cbt_v" onclick="buscar(<?= $dato['idUsuario'] ?>)"><i class="fi fi-sr-pencil"></i></button>
                                                    <?php //endif; ?>

                                                    <?php //if (isset($permisos['eliminar']) && $permisos['eliminar']) : ?>
                                                        <button class="btn_t cbt_r" onclick="eliminar(<?= $dato['idUsuario'] ?>)"><i class="fi fi-sr-trash-xmark"></i></button>
                                                    <?php //endif; ?>

                                                    <?php //if (isset($permisos['otros']) && $permisos['otros']) : ?>
                                                        <button class="btn_t <?= $color ?>" onclick="bloquear(<?= $dato['idUsuario'] ?>, <?= $dato['bloqueo'] ?>, this)"><i class="fi <?= $icon ?>"></i></button>
                                                    <?php //endif; ?>
                                                </div>
                                                <i data-lucide="chevron-down" class="icono_flecha_detalle"></i>
                                            </div>
                                        </div>

                                        <div class="listado_detalle_oculto">
                                            <div class="detalle_expandido_container">
                                                <div class="detalle_fila">
                                                    <div class="detalle_card">
                                                        <div class="detalle_card_icon"><i data-lucide="calendar"></i></div>
                                                        <div class="detalle_card_txt">
                                                            <label>Ultimo Ingreso</label>
                                                            <span><?= !empty($dato['ultimo_ingreso']) ? date('d/m/Y h:i A', strtotime($dato['ultimo_ingreso'])) : 'Sin Ingresos Al Sistema' ?></span>
                                                        </div>
                                                    </div>
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
                <form id="f" autocomplete="off" enctype="multipart/form-data">
                    <input type="hidden" id="id" name="id">
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="cedula" name="cedula">
                                <label for="cedula" class="titulo_formulario">Cedula</label>
                                <span class="mensaje" id="cedula_spam"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="nombre" name="nombre">
                                <label for="nombre" class="titulo_formulario">Nombre</label>
                                <span class="mensaje" id="nombre_spam"></span>
                            </div>
                        </div>
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="apellido" name="apellido">
                                <label for="apellido" class="titulo_formulario">Apellido</label>
                                <span class="mensaje" id="apellido_spam"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="telefono" name="telefono">
                                <label for="telefono" class="titulo_formulario">Telefono</label>
                                <span class="mensaje" id="telefono_spam"></span>
                            </div>
                        </div>
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="correo" name="correo">
                                <label for="correo" class="titulo_formulario">Correo</label>
                                <span class="mensaje" id="correo_spam"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="password" class="formulario" id="contraseña" name="contraseña">
                                <label for="contraseña" class="titulo_formulario">Contraseña</label>
                                <span class="mensaje" id="contraseña_spam"></span>
                                <i class="fi fi-sr-eye ojo icon_input_ojo"></i>
                            </div>
                        </div>
                        <div class="colum">
                            <div class="caja_formulario">
                                <select name="rol" id="roles" class="formulario select">

                                </select>
                                <label for="roles" class="titulo_formulario">Rol</label>
                                <span class="mensaje" id="rol_span"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario subir_foto">

                                <div class="preview_contenedor">
                                    <img id="foto_previa" src="public/img/camara.png">
                                </div>

                                <input type="file" class="input_foto_oculto" id="foto" name="foto" accept="image/*">

                                <label for="foto" class="btn_subir_foto">
                                    <i data-lucide="upload-cloud"></i> Seleccionar Foto
                                </label>

                                <span class="mensaje" id="foto_spam"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row" id="row_permisos" style="display: none;">
                        <div class="colum colum_tabla_completa" style="padding: 0;">
                            <div id="tabla_permisos_container">
                                <div id="tabla_permisos_ui">
                                    <div id="tabla_permisos">

                                    </div>
                                </div>
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
    <script src="js/usuarios.js"></script>
    <?php include('complementos/mensajeError.php'); ?>
</body>

</html>