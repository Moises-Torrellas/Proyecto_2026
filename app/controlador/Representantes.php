<?php

use App\modelo\ModeloRepresentantes;

require_once __DIR__ . '/Base.php';

$id_modulo = _MD_USUARIOS_;

procesarPermisos($id_modulo, $bitacora ?? null);

$nombreClaseModelo = 'App\modelo\ModeloRepresentantes';

if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloRepresentantes();

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitudUsuarios($objModelo, $id_modulo, $bitacora ?? null);
} else {
    cargarVista($pagina);
}

function manejarSolicitudUsuarios($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $tokenRecibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!isset($_SESSION['token']) || !hash_equals($_SESSION['token'], $tokenRecibido)) {
            throw new Exception('Error de seguridad: Token inválido o expirado.');
        }

        $accion = isset($_POST['accion']) ? filter_var($_POST['accion'], FILTER_SANITIZE_SPECIAL_CHARS) : '';

        switch ($accion) {
            
            default:
                throw new Exception('Acción no permitida.');
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}