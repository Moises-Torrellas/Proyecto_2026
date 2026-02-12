<!DOCTYPE html>
<html lang="en">

<head>
    <?php include('head.php'); ?>
</head>

<body data-tema="">
    <?php include('loader.php'); ?>
    <div class="contenedor_error">
        <div class="contenedor_mensaje_error">
            <h1 class="mensaje_error">ERROR 404</h1>
            <h2 class="sudmensaje_error">Página no encontrada</h2>
            <button class="btn btn_azul" onclick="window.location.href='/Proyecto_2026/public/Principal'">Página Principal</button>
        </div>
        <div class="contenedor_img_error">
            <img src="img/error_404.svg">
        </div>
    </div>
    <script src="js/main.js"></script>
</body>

</html>