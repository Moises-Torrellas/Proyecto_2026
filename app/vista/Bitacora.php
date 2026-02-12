<!DOCTYPE html>
<html lang="en">

<head>
    <?php include('complementos/head.php'); ?>
    <title>Gestionar Bitacora</title>
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
                            <h2 class="titulo_pagina" id="titulo">Gestionar Bitacora</h2>
                        </div>
                        <div class="contenedor_busqueda">
                            <input type="text" placeholder="Buscar..." autocomplete="off" id="busqueda">
                            <i class="fi fi-br-search icon_input"></i>
                        </div>
                        <div class="botones">
                            <button class="btn btn_verde" id="generar">Generar Reporte</button>
                        </div>
                    </div>
                    <div class="contenedor_tabla">
                        <div class="tabla" id="tabla">
                            <table class="" id="tablageneral">
                                <thead>
                                    <tr>
                                        <th>Codigo</th>
                                        <th>Usuario</th>
                                        <th>Modulo</th>
                                        <th>Accion</th>
                                        <th>Fecha</th>
                                        <th>Hora</th>
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
        <div class="modal ocultar" id="modal">
            <div class="cabecera_modal">
                <h2 class="titulo_modal">Titulo Modal</h2>
                <a type="button" class="cerrar_modal" id="cerrar_modal">&times;</a>
            </div>
            <div class="contenido_modal">
                <form id="f" autocomplete="off">
                    <input type="hidden" id="token" name="token" value="<?php echo $_SESSION['token']; ?>">
                </form>
            </div>
        </div>
    </section>
    <script src="js/main.js"></script>
    <script src="js/bitacora.js"></script>
</body>

</html>