<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);    // Impide acceso por JS
    // En desarrollo local (sin HTTPS) estas dos líneas pueden bloquear la sesión, 
    // coméntalas si estás en localhost sin SSL.
    // ini_set('session.cookie_secure', 1);      
    // ini_set('session.cookie_samesite', 'Strict'); 
    
    ini_set('session.use_only_cookies', 1);   // Solo cookies, no URL
    ini_set('session.use_strict_mode', 1);    // Rechazar IDs no generados por el servidor
    
    // Duración y gestión
    ini_set('session.gc_maxlifetime', 1800);  // 30 minutos de inactividad
    ini_set('session.cookie_lifetime', 0);    // Cookie expira al cerrar navegador
    
    // Entropy / seguridad del ID
    ini_set('session.sid_length', 48);        // ID más largo = más seguro
    ini_set('session.sid_bits_per_character', 6); // Más bits por carácter
    
    // Prefijo __Host- requiere cookie_secure=1 y Path=/.
    // Si estás en localhost sin HTTPS, usa un nombre normal:
    session_name('SISTEMA_CBS_SESSION');  
    session_start();
}

if (isset($_SESSION['last_activity'])) {
    $inactivo = time() - $_SESSION['last_activity'];
    if ($inactivo > 1800) { // 10 segundos de inactividad para pruebas
        session_unset();
        session_destroy();
        header('Location: Inicio');
        exit();
    }
}
if (isset($_SESSION['ip']) && $_SESSION['ip'] !== $_SERVER['REMOTE_ADDR']) {
    session_unset();
    session_destroy();
    header('Location: Inicio');
    exit();
}
// Actualizamos el tiempo de la última actividad para esta petición
$_SESSION['last_activity'] = time();
require_once(__DIR__ . "/../config/config.php");
require __DIR__ . '/../vendor/autoload.php';

require_once(__DIR__ . "/../routes/rutas.php");
$pagina = isset($_GET['pagina']) ? $_GET['pagina'] : "Inicio";
//echo "Página solicitada: " . $pagina; // Agrega esta línea para depuración
manejarRuta($pagina);
?>