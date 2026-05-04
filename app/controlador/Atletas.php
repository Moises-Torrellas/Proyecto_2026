<?php

use App\modelo\ModeloAtletas;

// 1. Cargamos las funciones base
require_once __DIR__ . '/Base.php';

// 2. Configuración del módulo (Corregido al ID de Representantes)
$id_modulo = _MD_REPRESENTANTES_;

// 3. Procesar permisos (Retorna el array de permisos)
$permisos = procesarPermisos($id_modulo, $bitacora ?? null);

// 4. Lógica de despacho (Router interno)
$nombreClaseModelo = 'App\modelo\ModeloAtletas';

if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloAtletas();

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitudRepresentantes($objModelo, $id_modulo, $bitacora ?? null, $permisos);
} else {
    cargarVista($pagina);
}

function manejarSolicitudRepresentantes($obj, $id_modulo, $bitacoraObj, array $permisos): void
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
            case 'consultarR':
                consultarRepresentantes($obj);
                break;
            case 'consultarP':
                consultarPosiciones($obj);
                break;
            case 'consultarC':
                consultarCategorias($obj);
                break;
            default:
                throw new Exception('Acción no permitida.');
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

/**
 * --- LÓGICA DE ACCIONES ---
 */

function consultar($obj): void
{
    $filtro['filtro'] = $_POST['filtro'] ?? '';
    $respuesta = $obj->Consultar($filtro);
    if(isset($respuesta['accion']) && $respuesta['accion'] == 'error') {
        $respuesta['mensaje'] ='Error al listar los representantes';
    }
    echo json_encode($respuesta);
}
function consultarRepresentantes($obj): void
{
    $filtro['filtro'] = $_POST['filtro'] ?? '';
    $respuesta = $obj->ConsultarRepresentantes();
    if(isset($respuesta['accion']) && $respuesta['accion'] == 'error') {
        $respuesta['mensaje'] ='Error al listar los representantes';
    }
    echo json_encode($respuesta);
}
function consultarCategorias($obj): void
{
    $filtro['filtro'] = $_POST['filtro'] ?? '';
    $respuesta = $obj->ConsultarCategorias();
    if(isset($respuesta['accion']) && $respuesta['accion'] == 'error') {
        $respuesta['mensaje'] ='Error al listar los representantes';
    }
    echo json_encode($respuesta);
}
function consultarPosiciones($obj): void
{
    $filtro['filtro'] = $_POST['filtro'] ?? '';
    $respuesta = $obj->ConsultarPosiciones();
    if(isset($respuesta['accion']) && $respuesta['accion'] == 'error') {
        $respuesta['mensaje'] ='Error al listar los representantes';
    }
    echo json_encode($respuesta);
}