<?php

use App\modelo\ModeloReportes;
use App\servicios\GenerarReporteEstadistico;

// 1. Cargamos las funciones base
require_once __DIR__ . '/Base.php';


// 2. Configuración del módulo (Corregido al ID de Representantes)
$id_modulo = _MD_REPORTES_;

// 3. Procesar permisos (Retorna el array de permisos)
$permisos = procesarPermisos($id_modulo, $bitacora);

// 4. Lógica de despacho (Router interno)
$nombreClaseModelo = 'App\modelo\ModeloReportes';

if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloReportes();

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitud($objModelo, $id_modulo, $bitacora, $permisos);
} else {
    registrarBitacora($bitacora, $id_modulo, 'Ingreso al Modulo');
    $variables = ['permisos' => $permisos];
    cargarVista($pagina, $variables);
}

function manejarSolicitud($obj, $id_modulo, $bitacoraObj, array $permisos): void
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
            case 'generar':
                generar($id_modulo, $bitacoraObj);
                break;
            default:
                throw new Exception('Acción no permitida.');
        }
    } catch (Exception $e) {
        logs('Reportes', $e->getMessage(), 'Controlador_ManejarSolicitud');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function consultar($obj){
    $respuesta = $obj->Consultar();
    echo json_encode($respuesta);
}

function generar($id_modulo, $bitacoraObj): void
{
    try {
        $nombreVista = 'AtletasCategorias';
        $grafico = $_POST['grafico_img'];
        $datos = isset($_POST['datos_json']) ? json_decode($_POST['datos_json'], true) : [];
        if (empty($datos)) {
            echo json_encode(['accion' => 'error', 'mensaje' => 'No se encontraron representantes para hacer el reporte.']);
            exit();
        }
        $objG = new GenerarReporteEstadistico();
        $pdf = $objG->generarPDF($nombreVista, $datos, 'Reportes', $grafico);
        if (isset($pdf['accion']) && $pdf['accion'] === 'reporte') {
            registrarBitacora($bitacoraObj, $id_modulo, "Generó un reporte estadisticos.");
        }
        echo json_encode($pdf);
    } catch (Exception $e) {
        logs('Reportes', $e->getMessage(), 'Controlador_Generar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}
