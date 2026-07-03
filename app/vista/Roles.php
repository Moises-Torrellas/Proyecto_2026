<?php
if (isset($solo_lista) && $solo_lista === true) :
    if (empty($registro)) : ?>
        <div class="listado_vacio">
            <p>No se encontraron registros</p>
        </div>
        <?php else :
        foreach ($registro as $dato) : ?>
            <div class="listado_contenedor_grupal" style="cursor: auto;">
                <div class="listado_item">
                    <div class="listado_col_datos">
                        <div class="listado_dato_grupo">
                            <small>Nombre</small>
                            <span><?= $dato['nombre_rol'] ?></span>
                        </div>
                        <div class="listado_dato_grupo">
                            <small>Descripcion</small>
                            <span><?= $dato['descripcion'] ?></span>
                        </div>
                    </div>

                    <div class="listado_col_acciones">
                        <div onclick="event.stopPropagation();" style="display:flex; gap:5px;">
                            <button class="btn_t cbt_m" onclick="CargarPermisos(<?= $dato['id_rol'] ?>)" data-tippy-content="Permisos"><i class="fi fi-sr-user-permissions"></i></button>

                            <?php if ($permisos['modificar']) : ?>
                                <button id="cbt_v" class="btn_t cbt_v" onclick="buscar(<?= $dato['id_rol'] ?>)" data-tippy-content="Modificar"><i class="fi fi-sr-pencil"></i></button>
                            <?php endif; ?>

                            <?php if ($permisos['eliminar']) : ?>
                                <button id="cbt_r" class="btn_t cbt_r" onclick="eliminar(<?= $dato['id_rol'] ?>)" data-tippy-content="Eliminar"><i class="fi fi-sr-trash-xmark"></i></button>
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
<html lang="en">

<head>
    <?php include('complementos/head.php'); ?>
    <title>Gestionar Roles</title>
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
                            <h2 class="titulo_pagina" id="titulo">Roles</h2>
                        </div>
                        <div class="contenedor_busqueda">
                            <input type="text" placeholder="Buscar..." autocomplete="off" id="busqueda">
                            <i class="fi fi-br-search icon_input"></i>
                        </div>
                        <div class="botones">
                            <button class="btn btn_azul" id="incluir">Nuevo Rol</button>
                            <button class="btn btn_verde" id="generar">Generar Reporte</button>
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
                                        <div class="listado_contenedor_grupal" style="cursor: auto;">
                                            <div class="listado_item">
                                                <div class="listado_col_datos">
                                                    <div class="listado_dato_grupo">
                                                        <small>Nombre</small>
                                                        <span><?= $dato['nombre_rol'] ?></span>
                                                    </div>
                                                    <div class="listado_dato_grupo">
                                                        <small>Descripcion</small>
                                                        <span><?= $dato['descripcion'] ?></span>
                                                    </div>
                                                </div>

                                                <div class="listado_col_acciones">
                                                    <div onclick="event.stopPropagation();" style="display:flex; gap:5px;">
                                                        <button class="btn_t cbt_m" onclick="CargarPermisos(<?= $dato['id_rol'] ?>)" data-tippy-content="Permisos"><i class="fi fi-sr-user-permissions"></i></button>

                                                        <?php if ($permisos['modificar']) : ?>
                                                            <button id="cbt_v" class="btn_t cbt_v" onclick="buscar(<?= $dato['id_rol'] ?>)" data-tippy-content="Modificar"><i class="fi fi-sr-pencil"></i></button>
                                                        <?php endif; ?>

                                                        <?php if ($permisos['eliminar']) : ?>
                                                            <button id="cbt_r" class="btn_t cbt_r" onclick="eliminar(<?= $dato['id_rol'] ?>)" data-tippy-content="Eliminar"><i class="fi fi-sr-trash-xmark"></i></button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif;?>
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
                    <div class="row" id="row_nombre">
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="nombre" name="nombre">
                                <label for="nombre" class="titulo_formulario">Nombre del Rol</label>
                                <span class="mensaje" id="nombre_spam"></span>
                            </div>
                        </div>
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="descripcion" name="descripcion">
                                <label for="descripcion" class="titulo_formulario">Descripcion del Rol</label>
                                <span class="mensaje" id="descripcion_spam"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row" id="row_permisos" style="display: none;">
                        <div class="colum colum_tabla_completa" style="padding: 0;">
                            <div id="tabla_permisos_container">
                                <table id="tabla_permisos_ui">
                                    <thead>
                                        <tr>
                                            <th>Módulo</th>
                                            <th>Ingresar</th>
                                            <th>Registrar</th>
                                            <th>Modificar</th>
                                            <th>Eliminar</th>
                                            <th>Reportes</th>
                                            <th>Otras Opciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tabla_permisos">

                                    </tbody>
                                </table>
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
    <script src="js/roles.js"></script>
    <?php include('complementos/mensajeError.php'); ?>
</body>

</html>