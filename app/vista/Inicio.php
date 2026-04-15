<!DOCTYPE html>
<html lang="en">

<head>
    <?php include('complementos/head.php'); ?>
    <title>Inicio</title>
</head>

<body data-tema="">
    <?php include('complementos/loader.php'); ?>
    <section class="fondo_inicio">
        <div class="contenedor_inicio">
            <div class="contenedor_logo_inicio">
                <!-- <img class="logo_inicio" src="img/logo.svg" alt=""> -->
                <img src="img/logo.png" alt="" class="logo_inicio">
            </div>
            <div class="contenedor_titulo_login">
                <h1 class="">¡Hola De Nuevo!</h1>
                <h2 class="">Listos Para Trabajar</h2>
            </div>
            <div class="separador">Inicio De Sesión</div>
            <div class="contenedor_formulario_inicio">
                <form autocomplete="off" id="f">
                    <!-- <input type="hidden" id="token" name="token" value="<?php /* echo $_SESSION['token']; */ ?>"> -->
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="cedula" name="cedula">
                                <label for="cedula" class="titulo_formulario">Cedula</label>
                                <span class="mensaje" id="spam_cedula"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="password" class="formulario" id="contraseña" name="contraseña">
                                <label for="contraseña" class="titulo_formulario">Contraseña</label>
                                <span class="mensaje" id="spam_contraseña"></span>
                                <i class="fi fi-sr-eye ojo icon_input" id="ojo"></i>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="colum column_inicio">
                            <button type="button" class="btn btn_azul btn_inicio" id="ingreso">Iniciar Sesión</button>
                            <a href="/Proyecto_2026/public/Recuperacion">¿Has olvidado tu contraseña?</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="contenedor_imagen">
            <img src="img/login-3.jpg" class="imagen_login" alt="Login">
        </div>
    </section>
    <script src="js/main.js"></script>
    <script src="js/inicio.js"></script>
</body>

</html>
