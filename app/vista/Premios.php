<?php if (isset($solo_lista) && $solo_lista === true):
    if (empty($registro)): ?>
        <div class="listado_vacio">
            <p>No se encontraron registros</p>
        </div>
        <?php else:
        foreach ($registro as $dato):
            $txtReferencia = ($dato['tipo'] == 1)  ? 'Individual' : 'Grupal';
            $icon = ($dato['estatus'] == 1) ? 'fi-sr-unlock' : 'fi-sr-lock';
            $color = ($dato['estatus'] == 1) ? 'cbt_g' : 'cbt_a'; ?>
            <div class="listado_contenedor_grupal">
                <div class="listado_item" onclick="toggleDetalles(this)">
                    <div class="listado_col_datos">
                        <div class="listado_dato_grupo">
                            <small>Premios</small>
                            <span><?= htmlspecialchars($dato['nombre']) ?></span>
                        </div>
                        <div class="listado_dato_grupo">
                            <small>Tipo</small>
                            <span><?= $txtTipo ?></span>
                        </div>
                    </div>

                    <div class="listado_col_acciones">
                        <div onclick="event.stopPropagation();" style="display:flex; gap:5px;">
                            <?php if ($permisos['modificar']): ?>
                                <button id="cbt_v" class="btn_t cbt_v" onclick="buscar(<?= $dato['id_metodos'] ?>)" data-tippy-content="Modificar"><i class="fi fi-sr-pencil"></i></button>
                            <?php endif; ?>
                            <?php if ($permisos['eliminar']): ?>
                                <button id="cbt_r" class="btn_t cbt_r" onclick="eliminar(<?= $dato['id_metodos'] ?>)" data-tippy-content="Eliminar"><i class="fi fi-sr-trash-xmark"></i></button>
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
<html lang="en">

<head>
    <?php include('complementos/head.php'); ?>
    <title>Premios</title>
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
                            <h2 class="titulo_pagina" id="titulo">Premios</h2>
                        </div>
                        <div class="contenedor_busqueda">
                            <input type="text" placeholder="Buscar..." autocomplete="off" id="busqueda">
                            <i class="fi fi-br-search icon_input"></i>
                        </div>
                        <div class="botones">
                            <?php if($permisos['registrar']):?>
                            <button class="btn btn_azul" id="incluir">Nuevo Premio</button>
                            <?php endif; ?>
                            <?php if ($permisos['reporte']): ?>
                            <button class="btn btn_verde" id="generar">Generar Reporte</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="contenedor_resultados">
                        <div id="resultadoconsulta" class="resultadoconsulta">
                            <?php if (empty($registro)): ?>
                                <div class="listado_vacio">
                                    <p>No se encontraron registros</p>
                                </div>
                                <?php else:
                                foreach ($registro as $dato): 
                                    $txtReferencia = ($dato['tipo'] == 1)  ? 'Individual' : 'Grupal';
                                    $icon = ($dato['estatus'] == 1) ? 'fi-sr-unlock' : 'fi-sr-lock';
                                    $color = ($dato['estatus'] == 1) ? 'cbt_g' : 'cbt_a'; ?>
                                    <div class="listado_contenedor_grupal">
                                        <div class="listado_item" onclick="toggleDetalles(this)">
                                            <div class="listado_col_datos">
                                                <div class="listado_dato_grupo">
                                                    <small>Premio</small>
                                                    <span><?= htmlspecialchars($dato['nombre']) ?></span>
                                                </div>
                                                <div class="listado_dato_grupo">
                                                    <small>Tipo</small>
                                                    <span><?= $txtTipo ?></span>
                                                </div>
                                            </div>

                                            <div class="listado_col_acciones">
                                                <div onclick="event.stopPropagation();" style="display:flex; gap:5px;">
                                                    <?php if ($permisos['modificar']): ?>
                                                        <button id="cbt_v" class="btn_t cbt_v" onclick="buscar(<?= $dato['id_metodos'] ?>)" data-tippy-content="Modificar"><i class="fi fi-sr-pencil"></i></button>
                                                    <?php endif; ?>
                                                    <?php if ($permisos['eliminar']): ?>
                                                        <button id="cbt_r" class="btn_t cbt_r" onclick="eliminar(<?= $dato['id_metodos'] ?>)" data-tippy-content="Eliminar"><i class="fi fi-sr-trash-xmark"></i></button>
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
                                <input type="text" class="formulario" id="nombre" name="nombre">
                                <label for="nombre" class="titulo_formulario">Nombre</label>
                                <span class="mensaje" id="nombre_spam"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <select name="tipo" id="tipo" class="formulario select">
                                    <option value="1" selected>Individual</option>
                                    <option value="2" selected>Grupal</option>
                                </select>
                                <label for="tipo" class="titulo_formulario">Tipo</label>
                                <span class="mensaje" id="nec_referencia_span"></span>
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
    <script src="js/premios.js"></script>
</body>

</html>