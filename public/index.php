<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_name('SISTEMA_CBS_SESSION');
    session_start();
}
require_once(__DIR__ . "/../config/config.php");
require __DIR__ . '/../vendor/autoload.php';

require_once(__DIR__ . "/../routes/rutas.php");
$pagina = isset($_GET['pagina']) ? $_GET['pagina'] : "Inicio";
//echo "Página solicitada: " . $pagina; // Agrega esta línea para depuración
manejarRuta($pagina);
?>