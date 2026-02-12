<?php
require_once(__DIR__ . "/../config/config.php");
require __DIR__ . '/../vendor/autoload.php';

require_once(__DIR__.'/../app/controlador/sesion.php');
require_once(__DIR__ . "/../routes/rutas.php");
$pagina = isset($_GET['pagina']) ? $_GET['pagina'] : "Inicio";
//echo "Página solicitada: " . $pagina; // Agrega esta línea para depuración
manejarRuta($pagina);
?>