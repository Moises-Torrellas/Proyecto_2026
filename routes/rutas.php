<?php

function manejarRuta($pagina): void
{
    // Manejo de la ruta para cerrar sesión
    if ($pagina === "CerrarSesion") {

        $bitacora = new \App\modelo\ModeloBitacora();

        // Registramos (Asegúrate que la constante _MD_Cerrar_ esté en tu config)
        if (isset($_SESSION['id'])) {
            $bitacora->RegistrarAccion(_MD_CERRAR_, "Cierre de sesión.", $_SESSION['id']);
        }
        // Limpiamos la sesión de forma segura
        $_SESSION = [];
        //  Si se están utilizando cookies para la sesión, eliminamos la cookie de sesión
        if (ini_get("session.use_cookies")) {
            // Obtenemos los parámetros de la cookie de sesión
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                // Usamos los mismos parámetros para eliminar la cookie
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        // Finalmente, destruimos la sesión
        session_destroy();
        // Redirigimos al usuario a la página de inicio después de cerrar sesión
        header("Location: " . _URL_ );
        exit();
    }
    // Definimos las rutas disponibles en el sistema
    $rutas = [
        'Inicio' => 'Inicio',
        'Principal' => 'Principal',
    ];
    // Verificamos si la página solicitada existe en las rutas definidas
    if (array_key_exists($pagina, $rutas)) {
        // Verificamos si el usuario está autenticado antes de permitir el acceso a otras páginas
        if (!isset($_SESSION['id']) && $pagina !== 'Inicio') {
            // Si el usuario no está autenticado, redirigimos a la página de inicio
            header("Location: " . _URL_);
            exit();
        }
        // Construimos el nombre completo de la clase del controlador
        $classNombre = "App\\controlador\\" . $rutas[$pagina];
        // Verificamos si la clase del controlador existe antes de instanciarla
        if (class_exists($classNombre)) {
            // Creamos una instancia de la bitácora para inyectarla en el controlador
            $bitacora = new \App\modelo\ModeloBitacora();
            $controlador = new $classNombre($bitacora); // Inyección de la bitácora en el controlador
            $controlador->ProcesarSolicitud($rutas[$pagina]); // Pasamos la ruta al método del controlador
        }
    } else {
        require_once(__DIR__ . "/../app/vista/complementos/404.php");
    }
}
