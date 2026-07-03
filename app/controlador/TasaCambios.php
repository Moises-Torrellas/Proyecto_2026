<?php

use App\modelo\ModeloTasaCambios;

require_once __DIR__ . '/Base.php';

$id_modulo = _MD_METODOS_; // TODO: Verificar si necesitamos un ID especifico, por ahora reusamos uno comun o lo omitimos de los permisos estrictos

$permisos = procesarPermisos($id_modulo, '');

$nombreClaseModelo = 'App\modelo\ModeloTasaCambios';

if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloTasaCambios();

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitudTasaCambios($objModelo, $id_modulo, $bitacora, $permisos);
} else {
    registrarBitacora($bitacora, $id_modulo, 'Ingreso al Modulo de Tasas de Cambio');
    $respuesta = $objModelo->Consultar();
    
    $error_bd = '';
    if (isset($respuesta['accion']) && $respuesta['accion'] === 'error') {
        $error_bd = 'Error al conectar con la base de datos.';
    }

    $registro = $respuesta['datos'] ?? [];
    $variables = ['registro' => $registro, 'permisos' => $permisos, 'error_bd' => $error_bd];
    cargarVista($pagina, $variables);
}

function manejarSolicitudTasaCambios($obj, $id_modulo, $bitacoraObj, array $permisos): void
{
    try {
        $tokenRecibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!isset($_SESSION['token']) || !hash_equals($_SESSION['token'], $tokenRecibido)) {
            throw new Exception('Error de seguridad: Token inválido o expirado.');
        }

        $accion = isset($_POST['accion']) ? filter_var($_POST['accion'], FILTER_SANITIZE_SPECIAL_CHARS) : '';

        switch ($accion) {
            case 'consultar':
                if (!$permisos['ingresar']) throw new Exception('No tienes permisos.');
                consultar($obj, $permisos);
                break;
            case 'consultarM':
                consultarM($obj);
                break;
            case 'sincronizar':
                if (!$permisos['modificar']) throw new Exception('No tienes permisos.');
                sincronizar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'registrar':
                if (!$permisos['registrar']) throw new Exception('No tienes permisos.');
                registrar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'eliminar':
                if (!$permisos['eliminar']) throw new Exception('No tienes permisos.');
                eliminarTasa($obj, $id_modulo, $bitacoraObj);
                break;
            default:
                throw new Exception('Acción no permitida.');
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function consultar($obj, $permisos): void
{
    $respuesta = $obj->Consultar();
    $registro = $respuesta['datos'] ?? []; 
    $solo_lista = true;
    include (__DIR__.'/../vista/TasaCambios.php');
}

function consultarM($obj): void
{
    $respuesta = $obj->ConsultarMonedasNoBase();
    echo json_encode($respuesta);
}

function sincronizar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        validar_requeridos(['codigo_moneda']);
        $datos = [
            'codigo_moneda' => $_POST['codigo_moneda'],
            'accion' => 'sincronizar'
        ];

        $resultado = $obj->procesarDatos($datos);
        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Sincronizó tasa de cambio para moneda ID: " . $_POST['codigo_moneda']);
        }
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('TasaCambios', $e->getMessage(), 'Controlador_Sincronizar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function registrar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        validar_requeridos(['codigo_moneda', 'tasa_bolivares']);
        $datos = [
            'codigo_moneda' => $_POST['codigo_moneda'],
            'valor_tasa' => $_POST['tasa_bolivares'],
            'accion' => 'registrar'
        ];

        $resultado = $obj->procesarDatos($datos);
        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Registró tasa manual para moneda ID: " . $_POST['codigo_moneda']);
        }
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('TasaCambios', $e->getMessage(), 'Controlador_Registrar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function eliminarTasa($obj, $id_modulo, $bitacoraObj): void
{
    try {
        validar_requeridos(['id']);
        $datos = [
            'id' => $_POST['id'],
            'accion' => 'eliminar'
        ];

        $resultado = $obj->procesarDatos($datos);
        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Eliminó registro de tasa de cambio ID: " . $_POST['id']);
        }
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('TasaCambios', $e->getMessage(), 'Controlador_Eliminar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}
