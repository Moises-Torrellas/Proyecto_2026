<?php
if (isset($solo_lista) && $solo_lista === true) :
    if (empty($registro)) : ?>
        <div class="listado_vacio"><p>No hay respaldos almacenados.</p></div>
    <?php else : 
        foreach ($registro as $dato) : 
            $estatus = ($dato['estatus'] == 1) ? 'Guardado' : 'Eliminado';
            $clase = ($dato['estatus'] == 1) ? 'estatus_v' : 'estatus_r';
    ?>
        <div class="listado_contenedor_grupal">
            <div class="listado_item">
                <div class="listado_col_datos">
                    <div class="listado_dato_grupo" style="width: 40%;">
                        <small>Archivo</small>
                        <span style="font-weight: bold; color: var(--texto-principal);"><?= $dato['nombre'] ?></span>
                    </div>
                    <div class="listado_dato_grupo">
                        <small>Fecha de Creación</small>
                        <span><?= $dato['fecha'] ?></span>
                    </div>
                    <div class="listado_dato_grupo">
                        <small>Creado por</small>
                        <span><?= $dato['creador'] ?></span>
                    </div>
                    <div class="listado_dato_grupo">
                        <small>Peso</small>
                        <span><?= $dato['tamano'] ?></span>
                    </div>
                    <div class="listado_dato_grupo">
                        <small>Estatus</small>
                        <span class="<?= $clase ?>"><?= $estatus ?></span>
                    </div>
                </div>
                <div class="listado_col_acciones">
                    <?php if ($dato['estatus'] == 1) : ?>
                        <div style="display:flex; gap:5px;">
                            <button class="btn_t cbt_v" onclick="restaurar('<?= $dato['nombre'] ?>')" title="Restaurar esta versión"><i class="fi fi-sr-time-past"></i></button>
                            <button class="btn_t cbt_r" onclick="eliminar('<?= $dato['nombre'] ?>')" title="Eliminar respaldo"><i class="fi fi-sr-trash-xmark"></i></button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; endif;
    exit(); ?>
<?php endif; ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <?php include('complementos/head.php'); ?>
    <title>Respaldo del Sistema</title>
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
                            <h2 class="titulo_pagina" id="titulo">Mantenimiento BD</h2>
                        </div>
                        <div class="contenedor_busqueda">
                            <input type="text" placeholder="Buscar..." autocomplete="off" id="busqueda">
                            <i class="fi fi-br-search icon_input"></i>
                        </div>
                        <div class="botones">
                            <button class="btn btn_azul" id="btn_generar"> Crear Punto de Restauración</button>
                        </div>
                    </div>
                    
                    <div class="contenedor_resultados">
                        <div id="resultadoconsulta" class="resultadoconsulta">
                            <?php if (empty($registro)) : ?>
                                <div class="listado_vacio"><p>No hay respaldos almacenados.</p></div>
                            <?php else : 
                                foreach ($registro as $dato) : 
                                    $estatus = ($dato['estatus'] == 1) ? 'Guardado' : 'Eliminado';
                                    $clase = ($dato['estatus'] == 1) ? 'estatus_v' : 'estatus_r';
                            ?>
                                <div class="listado_contenedor_grupal">
                                    <div class="listado_item">
                                        <div class="listado_col_datos">
                                            <div class="listado_dato_grupo" style="width: 40%;">
                                                <small>Archivo</small>
                                                <span style="font-weight: bold; color: var(--texto-principal);"><?= $dato['nombre'] ?></span>
                                            </div>
                                            <div class="listado_dato_grupo">
                                                <small>Fecha de Creación</small>
                                                <span><?= $dato['fecha'] ?></span>
                                            </div>
                                            <div class="listado_dato_grupo">
                                                <small>Creado por</small>
                                                <span><?= $dato['creador'] ?></span>
                                            </div>
                                            <div class="listado_dato_grupo">
                                                <small>Peso</small>
                                                <span><?= $dato['tamano'] ?></span>
                                            </div>
                                            <div class="listado_dato_grupo">
                                                <small>Estatus</small>
                                                <span class="<?= $clase ?>"><?= $estatus ?></span>
                                            </div>
                                        </div>
                                        <div class="listado_col_acciones">
                                            <?php if ($dato['estatus'] == 1) : ?>
                                                <div style="display:flex; gap:5px;">
                                                    <button class="btn_t cbt_v" onclick="restaurar('<?= $dato['nombre'] ?>')" title="Restaurar esta versión"><i class="fi fi-sr-time-past"></i></button>
                                                    <button class="btn_t cbt_r" onclick="eliminar('<?= $dato['nombre'] ?>')" title="Eliminar respaldo"><i class="fi fi-sr-trash-xmark"></i></button>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                    <?php include('complementos/botonera.php'); ?>
                </div>
            </div>
        </div>
    </section>

    <script src="js/main.js"></script>
    <script src="js/respaldo.js"></script>
</body>
</html>
