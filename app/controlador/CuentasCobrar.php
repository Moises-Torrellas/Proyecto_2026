<?php

use App\modelo\ModeloCuentasCobrar;

// 1. Cargamos las funciones base
require_once __DIR__ . '/Base.php';

// 2. Configuración del módulo
$id_modulo = _MD_CUENTAS_;   

// 3. Procesar permisos (Retorna el array de permisos)
$permisos = procesarPermisos($id_modulo, $bitacora);

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
    $registro = $respuesta['datos'] ?? [];
    $variables =['registro' => $registro, 'permisos' => $permisos ];
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
                if (!$permisos['ingresar']) throw new Exception('No tienes permisos para consultar cuentas por cobrar.');
                consultar($obj, $permisos);
                break;
            case 'consultarA':
                if (!$permisos['ingresar']) throw new Exception('No tienes permisos para consultar cuentas por cobrar.');
                consultarA($obj);
                break;
            case 'consultarCo':
                if (!$permisos['ingresar']) throw new Exception('No tienes permisos para consultar cuentas por cobrar.');
                consultarCo($obj);
                break;
            case 'consultarM':
                if (!$permisos['ingresar']) throw new Exception('No tienes permisos para consultar cuentas por cobrar.');
                consultarM($obj);
                break;
            case 'buscar':
                if (!$permisos['modificar']) throw new Exception('No tienes permisos para modificar cuentas por cobrar.');
                buscar($obj);
                break;
            case 'incluir':
                if (!$permisos['registrar']) throw new Exception('No tienes permisos para registrar cuentas por cobrar.');
                incluir($obj, $id_modulo, $bitacoraObj);
                break;
            case 'eliminar':
                if (!$permisos['eliminar']) throw new Exception('No tienes permisos para eliminar cuentas por cobrar.');
                eliminar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'modificar':
                if (!$permisos['modificar']) throw new Exception('No tienes permisos para modificar cuentas por cobrar.');
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

function consultarA($obj): void
{
    $respuesta = $obj->ConsultarAtletas();
    echo json_encode($respuesta);
}

function consultarCo($obj): void
{
    $respuesta = $obj->ConsultarConceptos();
    echo json_encode($respuesta);
}

// NUEVA FUNCIÓN PARA MONEDAS
function consultarM($obj): void
{
    $respuesta = $obj->ConsultarMonedas();
    echo json_encode($respuesta);
}

function buscar($obj): void
{
    try {
        $validaciones = ['id' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.']];
        validar_datos($validaciones);

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
        $reglaMonto = '/^[0-9]+(\.[0-9]{1,2})?$/';
        $reglaFecha = '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/';

        $validaciones = [
            'id_concepto'       => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Concepto inválido.'],
            'id_atleta'         => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Atleta inválido.'],
            'id_moneda'         => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Moneda inválida.'],
            'monto_total'       => ['regla' => $reglaMonto, 'mensaje' => 'Monto total inválido.'],
            'fecha_emision'     => ['regla' => $reglaFecha, 'mensaje' => 'Fecha de emisión inválida.'],
            'fecha_vencimiento' => ['regla' => $reglaFecha, 'mensaje' => 'Fecha de vencimiento inválida.']
        ];

        validar_datos($validaciones);

        $datos = [
            'id_concepto'       => $_POST['id_concepto'],
            'id_atleta'         => $_POST['id_atleta'],
            'id_moneda'         => $_POST['id_moneda'],
            'monto_total'       => $_POST['monto_total'],
            'fecha_emision'     => $_POST['fecha_emision'],
            'fecha_vencimiento' => $_POST['fecha_vencimiento'],
            'estatus'           => 'Pendiente', 
            'accion'            => 'incluir'
        ];

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Generó un cargo de " . $_POST['monto_total'] . " al atleta ID: " . $_POST['id_atleta']);
            $resultado = array('accion' => 'incluir', 'mensaje' => 'Cuenta por cobrar registrada exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = 'Ocurrió un error inesperado al registrar el cargo: ' . ($resultado['codigo'] ?? '');
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
        $reglaMonto = '/^[0-9]+(\.[0-9]{1,2})?$/';
        $reglaFecha = '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/';

        $validaciones = [
             'id'                => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.'],
             'id_concepto'       => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Concepto inválido.'],
             'id_atleta'         => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Atleta inválido.'],
             'id_moneda'         => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Moneda inválida.'],
             'monto_total'       => ['regla' => $reglaMonto, 'mensaje' => 'Monto total inválido.'],
             'fecha_emision'     => ['regla' => $reglaFecha, 'mensaje' => 'Fecha de emisión inválida.'],
             'fecha_vencimiento' => ['regla' => $reglaFecha, 'mensaje' => 'Fecha de vencimiento inválida.'],
             'estatus'           => ['regla' => '/^.+$/', 'mensaje' => 'El campo estatus es obligatorio.']
        ];

        validar_datos($validaciones);

        $datos = [
            'id'                => $_POST['id'],
            'id_concepto'       => $_POST['id_concepto'],
            'id_atleta'         => $_POST['id_atleta'],
            'id_moneda'         => $_POST['id_moneda'],
            'monto_total'       => $_POST['monto_total'],
            'fecha_emision'     => $_POST['fecha_emision'],
            'fecha_vencimiento' => $_POST['fecha_vencimiento'],
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
        $validaciones = ['id' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.']];
        validar_datos($validaciones);

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