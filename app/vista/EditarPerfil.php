<!DOCTYPE html>
<html lang="en">

<head>
    <?php include('complementos/head.php'); ?>
    <title>Editar Perfil</title>
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
                            <h2 class="titulo_pagina" id="titulo">Editar Perfil</h2>
                        </div>
                    </div>
                    
                    <div class="contenedor_panel">
                        <div class="contenido_modal" style="display: flex; flex-direction: column; gap: 2rem;">
                            
                            <div class="seccion_perfil">
                                <h3 style="margin-bottom: 1rem; border-bottom: 1px solid #ccc; padding-bottom: 5px;">Información Personal</h3>
                                <form id="form_personal" autocomplete="off" enctype="multipart/form-data">
                                    <input type="hidden" id="id" name="id" value="<?= $_SESSION['id'] ?? '' ?>">
                                    <div class="row">
                                        <div class="colum">
                                            <div class="caja_formulario subir_foto">
                                                <div class="preview_contenedor">
                                                    <?php 
                                                        $ruta_foto = (isset($_SESSION['foto']) && !empty($_SESSION['foto'])) ? $_SESSION['foto'] : 'public/img/camara.png'; 
                                                    ?>
                                                    <img id="foto_previa" src="<?= $ruta_foto ?>" alt="Foto de perfil">
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
                                            <div class="caja_formulario">
                                                <input type="text" class="formulario" id="cedula" name="cedula" value="<?= $_SESSION['cedula'] ?? '' ?>">
                                                <label for="cedula" class="titulo_formulario">Cédula</label>
                                                <span class="mensaje" id="cedula_spam"></span>
                                            </div>
                                        </div>
                                        
                                        <div class="colum">
                                            <div class="caja_formulario">
                                                <input type="text" class="formulario" id="nombre" name="nombre" value="<?= $_SESSION['nombre'] ?? '' ?>">
                                                <label for="nombre" class="titulo_formulario">Nombre</label>
                                                <span class="mensaje" id="nombre_spam"></span>
                                            </div>
                                        </div>
                                        
                                        <div class="colum">
                                            <div class="caja_formulario">
                                                <input type="text" class="formulario" id="apellido" name="apellido" value="<?= $_SESSION['apellido'] ?? '' ?>">
                                                <label for="apellido" class="titulo_formulario">Apellido</label>
                                                <span class="mensaje" id="apellido_spam"></span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="colum">
                                            <button type="button" class="btn btn_azul" id="btn_editar_personal">Guardar Información Personal</button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <div class="seccion_perfil">
                                <h3 style="margin-bottom: 1rem; border-bottom: 1px solid #ccc; padding-bottom: 5px;">Información de Contacto</h3>
                                <form id="form_contacto" autocomplete="off">
                                    <div class="row">
                                        <div class="colum">
                                            <div class="caja_formulario">
                                                <input type="text" class="formulario" id="telefono" name="telefono" value="<?= $_SESSION['telefono'] ?? '' ?>">
                                                <label for="telefono" class="titulo_formulario">Teléfono</label>
                                                <span class="mensaje" id="telefono_spam"></span>
                                            </div>
                                        </div>
                                        <div class="colum">
                                            <div class="caja_formulario">
                                                <input type="text" class="formulario" id="correo" name="correo" value="<?= $_SESSION['correo'] ?? '' ?>">
                                                <label for="correo" class="titulo_formulario">Correo</label>
                                                <span class="mensaje" id="correo_spam"></span>
                                            </div>
                                        </div>
                                        <div class="colum"></div>
                                        <div class="colum"></div>
                                    </div>

                                    <div class="row">
                                        <div class="colum">
                                            <button type="button" class="btn btn_azul" id="btn_editar_contacto">Guardar Contacto</button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <div class="seccion_perfil">
                                <h3 style="margin-bottom: 1rem; border-bottom: 1px solid #ccc; padding-bottom: 5px;">Seguridad</h3>
                                <form id="form_seguridad" autocomplete="off">
                                    <div class="row">
                                        <div class="colum">
                                            <div class="caja_formulario">
                                                <input type="password" class="formulario" id="contrasena" name="contrasena">
                                                <label for="contrasena" class="titulo_formulario">Nueva Contraseña</label>
                                                <span class="mensaje" id="contrasena_spam"></span>
                                                <i class="fi fi-sr-eye ojo icon_input_ojo"></i>
                                            </div>
                                        </div>
                                        <div class="colum">
                                            <div class="caja_formulario">
                                                <input type="password" class="formulario" id="confirmar_contrasena" name="confirmar_contrasena">
                                                <label for="confirmar_contrasena" class="titulo_formulario">Confirmar Contraseña</label>
                                                <span class="mensaje" id="confirmar_contrasena_spam"></span>
                                                <i class="fi fi-sr-eye ojo icon_input_ojo"></i>
                                            </div>
                                        </div>
                                        <div class="colum"></div>
                                        <div class="colum"></div>
                                    </div>

                                    <div class="row">
                                        <div class="colum">
                                            <button type="button" class="btn btn_azul" id="btn_editar_seguridad">Actualizar Contraseña</button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                        </div> </div>
                </div>
            </div>
        </div>
    </section>
    <script src="js/main.js"></script>
    <script src="js/EditarPerfil.js"></script>
</body>

</html>