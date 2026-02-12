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
                            <h2 class="titulo_pagina" id="titulo">Gestionar Roles</h2>
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
                    <div class="contenedor_tabla">
                        <div class="tabla" id="tabla">
                            <table class="" id="tablageneral">
                                <thead>
                                    <tr>
                                        <th>Codigo</th>
                                        <th>Nombre</th>
                                        <th>Opciones</th>
                                    </tr>
                                </thead>
                                <tbody id="resultadoconsulta">

                                </tbody>
                            </table>
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
                    <input type="hidden" id="token" name="token" value="<?php echo $_SESSION['token']; ?>">
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="nombre" name="nombre">
                                <label for="nombre" class="titulo_formulario">Nombre del Rol</label>
                                <span class="mensaje" id="nombre_spam"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <select name="" id="modulo" class="formulario select">

                                </select>
                                <label for="modulo" class="titulo_formulario">Modulo</label>
                                <span class="mensaje" id="modulo_span"></span>
                                <button type="button" class="btn btn_azul btn_formulario" id="add">Agregar</button>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="colum colum_tabla_completa">
                            <label for="" class="titulo_formulario titulo_formulario_tabla">Permisos</label>
                            <div class="caja_formulario caja_tabla ct_t">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Modulo</th>
                                            <th>Incluir</th>
                                            <th>Modificar</th>
                                            <th>Eliminar</th>
                                            <th>Reportes</th>
                                            <th>Otras Opciones</th>
                                            <th>Opción</th>
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
</body>

</html>