<?php
if (isset($solo_lista) && $solo_lista === true) :
    if (empty($registro)) : ?>
        <div class="listado_vacio">
            <p>No se encontraron registros</p>
        </div>
        <?php else :
        foreach ($registro as $dato) : ?>
            <div class="listado_contenedor_grupal">
                <div class="listado_item" onclick="toggleDetalles(this)">
                    <div class="listado_col_datos">
                        <div class="listado_dato_grupo">
                            <small>Nombre y Apellido</small>
                            <span><?= $dato['nombre'] ?> <?= $dato['apellido'] ?></span>
                        </div>
                        <div class="listado_dato_grupo">
                            <small>Cedula</small>
                            <span><?= $dato['tipo_doc'] ?> <?= $dato['cedula'] ?></span>
                        </div>
                        <div class="listado_dato_grupo">
                            <small>Telefono</small>
                            <span><?= $dato['telefono'] ?></span>
                        </div>
                        <div class="listado_dato_grupo">
                            <small>Direccion</small>
                            <span><?= $dato['direccion'] ?></span>
                        </div>
                    </div>

                    <div class="listado_col_acciones">
                        <div onclick="event.stopPropagation();" style="display:flex; gap:5px;">
                            <?php if (!empty($permisos['modificar_representante'])) : ?>
                                <button id="cbt_v" class="btn_t cbt_v" onclick="buscar(<?= $dato['codigo_representante'] ?>)" data-tippy-content="Modificar"><i class="fi fi-sr-pencil"></i></button>
                            <?php endif; ?>
                            <?php if (!empty($permisos['eliminar_representante'])) : ?>
                                <button id="cbt_r" class="btn_t cbt_r" onclick="eliminar(<?= $dato['codigo_representante'] ?>)" data-tippy-content="Eliminar"><i class="fi fi-sr-trash-xmark"></i></button>
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
    <title>Representantes</title>
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
                            <h2 class="titulo_pagina" id="titulo">Representantes</h2>
                        </div>
                        <div class="contenedor_busqueda">
                            <input type="text" placeholder="Buscar..." autocomplete="off" id="busqueda">
                            <i class="fi fi-br-search icon_input"></i>
                        </div>
                        <div class="botones">
                            <?php if (!empty($permisos['registrar_representante'])) : ?>
                                <button class="btn btn_azul" id="incluir">Nuevo Representante</button>
                            <?php endif; ?>
                            <?php if (!empty($permisos['generar_representante'])) : ?>
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
                                <?php else :
                                foreach ($registro as $dato) : ?>
                                    <div class="listado_contenedor_grupal">
                                        <div class="listado_item" onclick="toggleDetalles(this)">
                                            <div class="listado_col_datos">
                                                <div class="listado_dato_grupo">
                                                    <small>Nombre y Apellido</small>
                                                    <span><?= $dato['nombre'] ?> <?= $dato['apellido'] ?></span>
                                                </div>
                                                <div class="listado_dato_grupo">
                                                    <small>Cedula</small>
                                                    <span><?= $dato['tipo_doc'] ?> <?= $dato['cedula'] ?></span>
                                                </div>
                                                <div class="listado_dato_grupo">
                                                    <small>Telefono</small>
                                                    <span><?= $dato['telefono'] ?></span>
                                                </div>
                                                <div class="listado_dato_grupo">
                                                    <small>Direccion</small>
                                                    <span><?= $dato['direccion'] ?></span>
                                                </div>
                                            </div>

                                            <div class="listado_col_acciones">
                                                <div onclick="event.stopPropagation();" style="display:flex; gap:5px;">
                                                    <?php if (!empty($permisos['modificar_representante'])) : ?>
                                                        <button id="cbt_v" class="btn_t cbt_v" onclick="buscar(<?= $dato['codigo_representante'] ?>)" data-tippy-content="Modificar"><i class="fi fi-sr-pencil"></i></button>
                                                    <?php endif; ?>
                                                    <?php if (!empty($permisos['eliminar_representante'])) : ?>
                                                        <button id="cbt_r" class="btn_t cbt_r" onclick="eliminar(<?= $dato['codigo_representante'] ?>)" data-tippy-content="Eliminar"><i class="fi fi-sr-trash-xmark"></i></button>
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
        <div class="modal ocultar" id="modal">
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
                                <select name="nacionalidad" id="nacionalidad" class="formulario select">
                                    <option value="V" selected>V - Venezolano</option>
                                    <option value="E">E - Extrangero</option>
                                    <option value="P">P - Pasaporte</option>
                                </select>
                                <label for="nacionalidad" class="titulo_formulario">Tipo de Documento</label>
                            </div>
                        </div>
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="cedula" name="cedula" placeholder=" ">
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
                                <input type="text" class="formulario" id="direccion" name="direccion">
                                <label for="direccion" class="titulo_formulario">Direccion</label>
                                <span class="mensaje" id="direccion_spam"></span>
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
    <script src="js/representantes.js"></script>
    <?php include('complementos/mensajeError.php'); ?>
</body>

</html>