<?php

function manejarRuta($pagina): void
{
    // Manejo de la ruta para cerrar sesión
    if ($pagina === "CerrarSesion") {

        $bitacora = new \App\modelo\ModeloBitacora();

        // Registramos
        if (isset($_SESSION['id'])) {
            $entorno = $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido';
            $bitacora->RegistrarAccion(1, "Cierre de sesión exitoso.", $_SESSION['id'], null, null, $entorno);
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
        header("Location: " . "Inicio");
        exit();
    }
    // Definimos las rutas disponibles en el sistema
    $rutas = [
        'Inicio' => 'Inicio',
        'Principal' => 'Principal',
        'Usuarios' => 'Usuarios',
        'Recuperacion' => 'Recuperacion',
        'Roles' => 'Roles',
        'Representantes' => 'Representantes',
        'Posiciones' => 'Posiciones',
        'Categorias' => 'Categorias',
        'Torneos' => 'Torneos',
        'EstadoFisico' => 'EstadoFisico',
        'Bitacora' => 'Bitacora',
        'Atletas' => 'Atletas',
        'MetodosPago' => 'MetodosPago',
        'Conceptos' => 'Conceptos',
        'Monedas' => 'Monedas',
        'CuentasCobrar' => 'CuentasCobrar',
        'Notificaciones' => 'Notificaciones',
        'Pagos' => 'Pagos',
        'Respaldo' => 'Respaldo',
        'CategoriaCatalogo'=>'CategoriaCatalogo',
        'Premios' => 'Premios',
        'Reportes' => 'Reportes',
        'Catalogo'=>'Catalogo',
        'Devoluciones' => 'Devoluciones',
        'Equipos'=>'Equipos',
        'Asignaciones' => 'Asignaciones',
        'ArticulosInventario'=>'ArticulosInventario',
        'Palmares'=>'Palmares',
        'Estadisticas'=>'Estadisticas',
        'Participaciones' => 'Participaciones',
        'TasaCambios' => 'TasaCambios',
        'Modulos' => 'Modulos',
        'Permisos' => 'Permisos',
        'EditarPerfil' => 'EditarPerfil',
    ];
    // Verificamos si la página solicitada existe en las rutas definidas
    if (array_key_exists($pagina, $rutas)) {
        // Verificamos si el usuario está autenticado antes de permitir el acceso a otras páginas
        if (!isset($_SESSION['id']) && $pagina !== 'Inicio' && $pagina !== 'Recuperacion') {
            // Si el usuario no está autenticado, redirigimos a la página de inicio
            header("Location: " . "Inicio");
            exit();
        }
        // Construimos el nombre completo de la clase del controlador
        $archivoControlador = __DIR__ . "/../app/controlador/" . $rutas[$pagina] . ".php";
        // Verificamos si la clase del controlador existe antes de instanciarla
        if (file_exists($archivoControlador)) {
            // Instancias necesarias que el script del controlador pueda usar globalmente
            $bitacora = new \App\modelo\ModeloBitacora();

            // Incluimos el archivo que ejecutará la lógica
            $pagina = $rutas[$pagina];
            require_once $archivoControlador;
            
        } else {
            require_once(__DIR__ . "/../app/vista/complementos/404.php");
        }
    } else {
        require_once(__DIR__ . "/../app/vista/complementos/404.php");
    }
}
