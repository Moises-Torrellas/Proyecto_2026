<?php

use App\modelo\ModeloCalidad;

// 1. Cargamos las funciones base
require_once __DIR__ . '/Base.php';

// 2. Configuración del módulo (Corregido al ID de Categorías)
$id_modulo = _MD_CATEGORIAS_;

// 3. Procesar permisos (Retorna el array de permisos)
$permisos = procesarPermisos($id_modulo, $bitacora ?? null);

// 4. Lógica de despacho (Router interno)
$nombreClaseModelo = 'App\modelo\ModeloCalidad';

if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloCalidad();

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitudCategorias($objModelo, $id_modulo, $bitacora ?? null, $permisos);
} else {
    cargarVista($pagina);
}

/**
 * --- FUNCIONES DEL CONTROLADOR ---
 */

function manejarSolicitudCategorias($obj, $id_modulo, $bitacoraObj, array $permisos): void
{
    try {
        $tokenRecibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!isset($_SESSION['token']) || !hash_equals($_SESSION['token'], $tokenRecibido)) {
            throw new Exception('Error de seguridad: Token inválido o expirado.');
        }

        $accion = isset($_POST['accion']) ? filter_var($_POST['accion'], FILTER_SANITIZE_SPECIAL_CHARS) : '';

        // Seguridad centralizada
        switch ($accion) {
            case 'consultar':
                consultar($obj);
                break;
            /* case 'buscar':
                if (!$permisos['modificar']) throw new Exception('No tienes permisos para modificar categorias.');
                buscar($obj);
                break;
            /*case 'incluir':
                if (!$permisos['incluir']) throw new Exception('No tienes permisos para registrar categorias.');
                incluir($obj, $id_modulo, $bitacoraObj);
                break;
            /*case 'eliminar':
                if (!$permisos['eliminar']) throw new Exception('No tienes permisos para eliminar categorias.');
                eliminar($obj, $id_modulo, $bitacoraObj);
                break;
            /*case 'modificar':
                if (!$permisos['modificar']) throw new Exception('No tienes permisos para modificar categorias.');
                modificar($obj, $id_modulo, $bitacoraObj);
                break; */

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
    $registros = $obj->consultar($filtro);
    echo json_encode($registros);
}