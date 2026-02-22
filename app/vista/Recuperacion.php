<!DOCTYPE html>
<html lang="en">

<head>
    <?php include('complementos/head.php'); ?>
    <title>Recuperar</title>
</head>

<body data-tema="">
    <?php include('complementos/loader.php'); ?>
    <section class="fondo_inicio">
        <div class="contenedor_inicio">
            <div class="contenedor_logo_inicio">
                <img class="logo_inicio" src="img/logo.svg" alt="">
                <h2 class="nombre_negocio_inicio">Recuperación De Contraseña</h2>
            </div>
            <div class="contenedor_formulario_inicio s" id="sec_1">
                <form autocomplete="off" id="c">
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="cedula" name="cedula">
                                <label for="cedula" class="titulo_formulario">Ingrese su cedula</label>
                                <span class="mensaje" id="spam_cedula"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="colum column_inicio">
                            <button type="button" class="btn btn_azul" id="comprobar">Enviar Código</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="contenedor_formulario_inicio s" id="sec_2">
                <form autocomplete="off" id="r">
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario cf_2">
                                <input type="text" class="formulario" id="codigo" name="codigo" style="text-align: center;">
                                <label for="codigo" class="titulo_formulario">Ingrese el Código</label>
                                <span class="mensaje" id="spam_codigo"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="colum column_inicio">
                            <button type="button" class="btn btn_azul" id="comprobarCodigo">Comprobar Código</button>
                        </div>
                    </div>
                    <div class="row">
                        <div class="colum column_inicio">
                            <button type="button" class="btn btn_azul btn_a" id="reenviar">Reenviar Código</button>
                            <span id="contador">Reenviar el código en (0:30)</span>
                        </div>
                    </div>
                </form>
            </div>
            <div class="contenedor_formulario_inicio s" id="sec_3">
                <form autocomplete="off" id="f">
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="password" class="formulario" id="contraseña" name="contraseña">
                                <label for="contraseña" class="titulo_formulario">Contraseña</label>
                                <span class="mensaje" id="spam_contraseña"></span>
                                <i class="fi fi-sr-eye icon_input ojo"></i>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="password" class="formulario" id="contraseña_r" name="contraseña_r">
                                <label for="contraseña_r" class="titulo_formulario">Repetir Contraseña</label>
                                <span class="mensaje" id="spam_contraseña_r"></span>
                                <i class="fi fi-sr-eye icon_input ojo"></i>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="colum column_inicio">
                            <button type="button" class="btn btn_azul" id="cambiar">Cambiar Contraseña</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
    <script src="js/main.js"></script>
    <script src="js/recuperar.js"></script>
</body>

</html>