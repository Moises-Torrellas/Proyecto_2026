<?php

use App\modelo\ModeloCuentasCobrar;
use App\modelo\ModeloAtletas;
use App\modelo\ModeloConceptos;
use App\modelo\ModeloMonedas;

// 1. Cargamos las funciones base
require_once __DIR__ . '/Base.php';

// 2. Configuración del módulo
$id_modulo = _MD_CUENTAS_;   

// 3. Procesar permisos (Retorna el array de permisos)
$permisos = procesarPermisos($id_modulo, 'ingresar_cargo');

// 4. Lógica de despacho (Router interno)
$nombreClaseModelo = 'App\modelo\ModeloCuentasCobrar';

if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloCuentasCobrar();

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitudCuentasCobrar($objModelo, $id_modulo, $bitacora, $permisos);
} else {
    registrarBitacora($bitacora , $id_modulo, 'Ingreso al Modulo');
    $respuesta = $objModelo->Consultar();
    
    $error_bd = '';
    if (isset($respuesta['accion']) && $respuesta['accion'] === 'error') {
        $error_bd = 'Error al conectar con la base de datos.';
    }

    $registro = $respuesta['datos'] ?? [];
    $variables = ['registro' => $registro, 'permisos' => $permisos, 'error_bd' => $error_bd];
    cargarVista($pagina, $variables);
}

/**
 * --- FUNCIONES DEL CONTROLADOR ---
 */

function manejarSolicitudCuentasCobrar($obj, $id_modulo, $bitacoraObj, array $permisos): void
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
                if (empty($permisos['ingresar_cargo'])) throw new Exception('No tienes permisos para consultar cuentas por cobrar.');
                consultar($obj, $permisos);
                break;
            case 'consultarA':
                if (empty($permisos['ingresar_cargo'])) throw new Exception('No tienes permisos para consultar cuentas por cobrar.');
                consultarA();
                break;
            case 'consultarCo':
                if (empty($permisos['ingresar_cargo'])) throw new Exception('No tienes permisos para consultar cuentas por cobrar.');
                consultarCo();
                break;
            case 'consultarMoneda':
                if (empty($permisos['ingresar_cargo'])) throw new Exception('No tienes permisos para consultar cuentas por cobrar.');
                consultarMoneda();
                break;
            case 'buscar':
                if (empty($permisos['modificar_cargo'])) throw new Exception('No tienes permisos para modificar cuentas por cobrar.');
                buscar($obj);
                break;
            case 'incluir':
                if (empty($permisos['registrar_cargo'])) throw new Exception('No tienes permisos para registrar cuentas por cobrar.');
                incluir($obj, $id_modulo, $bitacoraObj);
                break;
            case 'eliminar':
                if (empty($permisos['anular_cargo'])) throw new Exception('No tienes permisos para eliminar cuentas por cobrar.');
                eliminar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'modificar':
                if (empty($permisos['modificar_cargo'])) throw new Exception('No tienes permisos para modificar cuentas por cobrar.');
                modificar($obj, $id_modulo, $bitacoraObj);
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

function consultar($obj, $permisos): void
{
    $filtro['filtro'] = $_POST['filtro'] ?? '';
    $respuesta = $obj->Consultar($filtro);
    
    $registro = $respuesta['datos'] ?? []; 
    $solo_lista = true;

    include (__DIR__.'/../vista/CuentasCobrar.php');
}

function consultarA(): void
{   
    $objAtleta = new ModeloAtletas();
    $respuesta = $objAtleta->ConsultarAtletas();
    echo json_encode($respuesta);
}

function consultarCo(): void
{
    $objConcepto = new ModeloConceptos();
    $respuesta = $objConcepto->ConsultarConcepto();
    echo json_encode($respuesta);
}

function consultarMoneda(): void
{
    $objMoneda = new ModeloMonedas();
    $respuesta = $objMoneda->obtenerMonedaBase();
    echo json_encode($respuesta);
}


function buscar($obj): void
{
    try {
        validar_requeridos(['id']);

        $datos = [
            'id' => $_POST['id'],
            'accion' => 'buscar'
        ];

        $resultado = $obj->procesarDatos($datos);
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('CuentasCobrar', $e->getMessage(), 'Controlador_Buscar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function incluir($obj, $id_modulo, $bitacoraObj): void
{
    try {
        validar_requeridos(['id_concepto', 'monto_total', 'fecha_emision']);

        $datos = [
            'id_concepto'       => $_POST['id_concepto'],
            'id_atleta'         => is_array($_POST['id_atleta']) ? $_POST['id_atleta'] : [$_POST['id_atleta']],
            'monto_total'       => $_POST['monto_total'],
            'fecha_emision'     => $_POST['fecha_emision'], 
            'accion'            => 'incluir'
        ];

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Generó cargo(s) de " . $_POST['monto_total']);
            $resultado = array('accion' => 'incluir', 'mensaje' => 'Cuenta por cobrar registrada exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = ($resultado['codigo'] ?? '');
        }

        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('CuentasCobrar', $e->getMessage(), 'Controlador_Incluir');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function modificar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        validar_requeridos(['id', 'id_concepto', 'monto_total', 'fecha_emision', 'estatus']);

        // Extraer el primer atleta en caso de que llegue como array (en modificar siempre es 1)
        $id_atleta = is_array($_POST['id_atleta']) ? $_POST['id_atleta'][0] : $_POST['id_atleta'];

        $datos = [
            'id'                => $_POST['id'],
            'id_concepto'       => $_POST['id_concepto'],
            'id_atleta'         => $id_atleta,
            'monto_total'       => $_POST['monto_total'],
            'fecha_emision'     => $_POST['fecha_emision'],
            'estatus'           => $_POST['estatus'],
            'accion'            => 'modificar'
        ];

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Modificó la cuenta por cobrar ID: " . $_POST['id']);
            $resultado = array('accion' => 'modificar', 'mensaje' => 'Cuenta por cobrar modificada exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = 'Ocurrió un error inesperado en la modificación.';
        }

        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('CuentasCobrar', $e->getMessage(), 'Controlador_Modificar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function eliminar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        validar_requeridos(['id']);

        $datos = [
            'id' => $_POST['id'],
            'accion' => 'eliminar'
        ];

        $resultado = $obj->procesarDatos($datos);
        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Anuló la cuenta por cobrar ID: " . $_POST['id']);
            $resultado = array('accion' => 'eliminar', 'mensaje' => 'Cuenta por cobrar anulada correctamente (estatus: Anulado).');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                INVALID_ID  => 'La cuenta por cobrar no existe.',
                ASSOCIATES  => 'No se puede eliminar la cuenta porque ya tiene pagos asociados registrados.',
                default     => 'Ocurrió un error inesperado en la eliminación.'
            };
        }
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('CuentasCobrar', $e->getMessage(), 'Controlador_Eliminar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}