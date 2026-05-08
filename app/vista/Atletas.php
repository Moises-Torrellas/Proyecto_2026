<!DOCTYPE html>
<html lang="en">

<head>
    <?php include('complementos/head.php'); ?>
    <title>Atletas</title>
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
                            <h2 class="titulo_pagina" id="titulo">Atletas</h2>
                        </div>
                        <div class="contenedor_busqueda">
                            <input type="text" placeholder="Buscar..." autocomplete="off" id="busqueda">
                            <i class="fi fi-br-search icon_input"></i>
                        </div>
                        <div class="botones">
                            <button class="btn btn_azul" id="incluir">Nuevo Atleta</button>

                            <button class="btn btn_verde" id="generar">Generar Reporte de Metodo de Pago</button>
                        </div>
                    </div>
                    <div class="contenedor_resultados">
                        <div id="resultadoconsulta" class="resultadoconsulta">
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
                                <input type="date" class="formulario" id="fecha_nac" name="fecha_nac">
                                <label for="fecha_nac" class="titulo_formulario">Fecha de Nacimiento</label>
                                <span class="mensaje" id="fecha_nac_spam"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="nombre" name="nombre">
                                <label for="nombre" class="titulo_formulario">Primer y Segundo Nombre</label>
                                <span class="mensaje" id="nombre_spam"></span>
                            </div>
                        </div>
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="apellido" name="apellido">
                                <label for="apellido" class="titulo_formulario">Primer y Segundo Apellido</label>
                                <span class="mensaje" id="apellido_spam"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <select name="categoria" id="categoria" class="formulario select">

                                </select>
                                <label for="categoria" class="titulo_formulario">Categoria</label>
                                <span class="mensaje" id="categoria_span"></span>
                            </div>
                        </div>
                        <div class="colum">
                            <div class="caja_formulario">
                                <select name="posicion" id="posicion" class="formulario select">

                                </select>
                                <label for="posicion" class="titulo_formulario">Posición</label>
                                <span class="mensaje" id="posicion_span"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">

                        <div class="colum">
                            <div class="caja_formulario">
                                <select name="genero" id="genero" class="formulario select">
                                    <option value="H">Hombre</option>
                                    <option value="M">Mujer</option>
                                </select>
                                <label for="genero" class="titulo_formulario">Genero</label>
                                <span class="mensaje" id="genero_span"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <select name="representante" id="representante" class="formulario select">

                                </select>
                                <label for="representante" class="titulo_formulario">Representante</label>
                                <span class="mensaje" id="representante_span"></span>
                            </div>
                        </div>
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="doc_i" name="doc_i">
                                <label for="doc_i" class="titulo_formulario">Documento de Identidad</label>
                                <span class="mensaje" id="doc_i_spam"></span>
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
                            <div class="caja_formulario subir_foto">

                                <div class="preview_contenedor">
                                    <img id="foto_previa" src="">
                                </div>

                                <input type="file" class="input_foto_oculto" id="foto" name="foto" accept="image/*">

                                <label for="foto" class="btn_subir_foto">
                                    <i data-lucide="upload-cloud"></i> Seleccionar Foto
                                </label>

                                <span class="mensaje" id="foto_spam"></span>
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
    <script src="js/atletas.js"></script>
</body>

</html>