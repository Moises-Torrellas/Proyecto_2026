<?php

use App\modelo\ModeloPagos;
use App\modelo\ModeloCuentasCobrar;
use App\modelo\ModeloMonedas;
use App\modelo\ModeloMetodosPago;

// 1. Cargamos las funciones base
require_once __DIR__ . '/Base.php';

// 2. Configuración del módulo (Corregido al ID de Representantes)
$id_modulo = _MD_PAGOS_;

// 3. Procesar permisos (Retorna el array de permisos)
$permisos = procesarPermisos($id_modulo, $bitacora);

// 4. Lógica de despacho (Router interno)
$nombreClaseModelo = 'App\modelo\ModeloPagos';

if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloPagos();

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitud($objModelo, $id_modulo, $bitacora, $permisos);
} else {
    registrarBitacora($bitacora , $id_modulo, 'Ingreso al Modulo');
    cargarVista($pagina, $permisos);
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
            case 'consultarM':
                consultarM();
                break;
            case 'consultarMP':
                consultarMP();
                break;
            case 'consultarC':
                consultarC();
                break;
            default:
                throw new Exception('Acción no permitida.');
        }
    } catch (Exception $e) {
        logs('Pagos', $e->getMessage(), 'Controlador_ManejarSolicitud');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function consultarM(): void
{
    try {
        $objM = new ModeloMonedas();
        $respuesta = $objM->Consultar();
        $respuesta['accion'] = 'consultarM';
        echo json_encode($respuesta);
    } catch (Exception $e) {
        logs('Pagos', $e->getMessage(), 'Controlador_ConsultarM');
        echo json_encode(['accion' => 'error', 'mensaje' => 'Error al listar las monedas.']);
    }
}
function consultarMP(): void
{
    try {
        $objMP = new ModeloMetodosPago();
        $respuesta = $objMP->Consultar();
        $respuesta['accion'] = 'consultarMP';
        echo json_encode($respuesta);
    } catch (Exception $e) {
        logs('Pagos', $e->getMessage(), 'Controlador_ConsultarMP');
        echo json_encode(['accion' => 'error', 'mensaje' => 'Error al listar los metodos de pago.']);
    }
}

function consultarC(): void {
    try {
        $objC = new ModeloCuentasCobrar();
        $respuesta = $objC->Consultar();
        $respuesta['accion'] = 'consultarC';
        echo json_encode($respuesta);
    } catch (Exception $e) {
        logs('Pagos', $e->getMessage(), 'Controlador_ConsultarC');
        echo json_encode(['accion' => 'error', 'mensaje' => 'Error al listar las cuentas cobrar.']);
    }
}
