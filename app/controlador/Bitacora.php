<?php

use App\modelo\ModeloBitacora;

// 1. Cargamos las funciones base
require_once __DIR__ . '/Base.php';

// 2. Configuración del módulo
$id_modulo = _MD_BITACORA_;

// 3. Procesar permisos (esto llena la variable global $permisosGenerales)
$permisos = procesarPermisos($id_modulo, $bitacora ?? null);

// 4. Lógica de despacho (Router interno)
$nombreClaseModelo = 'App\modelo\ModeloBitacora';

if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloBitacora();

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitudUsuarios($objModelo, $id_modulo, $bitacora ?? null, $permisos);
} else {
    cargarVista($pagina);
}

function manejarSolicitudUsuarios($obj, $id_modulo, $bitacoraObj, $permisos): void
{
    // Centralizamos la variable global de permisos aquí

    try {
        $tokenRecibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!isset($_SESSION['token']) || !hash_equals($_SESSION['token'], $tokenRecibido)) {
            throw new Exception('Error de seguridad: Token inválido o expirado.');
        }

        $accion = isset($_POST['accion']) ? filter_var($_POST['accion'], FILTER_SANITIZE_SPECIAL_CHARS) : '';

        // Validamos permisos antes de ejecutar las funciones
        switch ($accion) {
            case 'consultar':
                consultar($obj);
                break;
            default:
                throw new Exception('Acción no permitida.');
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function consultar($obj): void
{
    $filtro['filtro'] = $_POST['filtro'] ?? '';
    $respuesta = $obj->Consultar($filtro);
    echo json_encode($respuesta);
}