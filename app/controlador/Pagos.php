<?php

use App\modelo\ModeloPagos;
use App\modelo\ModeloCuentasCobrar;
use App\modelo\ModeloMonedas;
use App\modelo\ModeloMetodosPago;

// 1. Cargamos las funciones base
require_once __DIR__ . '/Base.php';

// 2. Configuración del módulo
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
    manejarSolicitud($objModelo, $id_modulo, $bitacora ?? null, $permisos);
} else {
    registrarBitacora($bitacora, $id_modulo, 'Ingreso al Modulo');
    $respuesta = $objModelo->Consultar();
    $registro = $respuesta['datos'] ?? [];
    $variables = ['registro' => $registro, 'permisos' => $permisos];
    cargarVista($pagina, $variables);
}

function manejarSolicitud($obj, $id_modulo, $bitacoraObj, array $permisos): void
{
    try {
        $tokenRecibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!isset($_SESSION['token']) || !hash_equals($_SESSION['token'], $tokenRecibido)) {
            throw new Exception('Error de seguridad: Token inválido o expirado.');
        }
        /* $usuario = $_GET['nombre'];
        echo "Bienvenido, " . $usuario; */
        $accion = isset($_POST['accion']) ? filter_var($_POST['accion'], FILTER_SANITIZE_SPECIAL_CHARS) : '';

        // Seguridad centralizada
        switch ($accion) {
            case 'consultar':
                if (!$permisos['ingresar']) throw new Exception('No tienes permisos para consultar pagos.');
                consultar($obj, $permisos);
                break;
            case 'incluir':
                if (!$permisos['registrar']) throw new Exception('No tienes permisos para registrar pagos.');
                incluir($obj, $id_modulo, $bitacoraObj);
                break;
            case 'eliminar':
                if (!$permisos['eliminar']) throw new Exception('No tienes permisos para anular pagos.');
                eliminar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'MultiConsulta':
                MultiConsulta();
                break;
            case 'consultarTasa':
                consultarTasa($obj);
                break;
            default:
                throw new Exception('Acción no permitida.');
        }
    } catch (Exception $e) {
        logs('Pagos', $e->getMessage(), 'Controlador_manejarSolicitud');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

/**
 * --- LÓGICA DE ACCIONES ---
 */
function consultarTasa($obj): void
{
    try {
        $moneda_base = isset($_POST['moneda_base']) ? strtoupper(trim($_POST['moneda_base'])) : 'USD';
        $moneda_pago = isset($_POST['moneda_pago']) ? strtoupper(trim($_POST['moneda_pago'])) : 'VES';

        $tasa = $obj->obtenerTasaBackend($moneda_base, $moneda_pago);

        echo json_encode(['accion' => 'consultarTasa', 'exito' => true, 'tasa' => $tasa]);
    } catch (Exception $e) {
        logs('Pagos', $e->getMessage(), 'Controlador_consultarTasa');
        echo json_encode(['accion' => 'consultarTasa', 'exito' => false, 'mensaje' => $e->getMessage()]);
    }
}

function consultar($obj, $permisos): void
{
    $filtro['filtro'] = $_POST['filtro'] ?? '';
    $respuesta = $obj->Consultar($filtro);

    $registro = $respuesta['datos'] ?? [];
    $solo_lista = true;


    include(__DIR__ . '/../vista/Pagos.php');
}

function MultiConsulta(): void
{
    try {
        // 1. Instanciar los modelos
        $modeloCuentas = new ModeloCuentasCobrar();
        $modeloMonedas = new ModeloMonedas();
        $modeloMP      = new ModeloMetodosPago();

        $respCuentas = $modeloCuentas->Consultar();
        $cuentasFiltradas = array_filter($respCuentas['datos'] ?? [], function ($item) {
            return (int)$item['anulado'] === 0 && floatval($item['monto_pendiente']) > 0 && (int)$item['estatus'] === 0;
        });

        // 3. Obtener y filtrar Monedas (Solo estatus 1)
        $respMonedas = $modeloMonedas->Consultar();
        $monedasFiltradas = array_filter($respMonedas['datos'] ?? [], function ($item) {
            return isset($item['estatus']) && (int)$item['estatus'] === 1;
        });

        // 4. Obtener y filtrar Métodos de Pago (Solo estatus 1)
        $respMP = $modeloMP->Consultar();
        $metodosFiltrados = array_filter($respMP['datos'] ?? [], function ($item) {
            return isset($item['estatus']) && (int)$item['estatus'] === 1;
        });
        echo json_encode([
            'accion'  => 'MultiConsulta',
            'cuentas' => array_values($cuentasFiltradas),
            'monedas' => array_values($monedasFiltradas),
            'metodos' => array_values($metodosFiltrados)
        ]);
    } catch (Exception $e) {
        logs('Pagos', $e->getMessage(), 'Controlador_MultiConsulta');
        echo json_encode([
            'accion' => 'error',
            'mensaje' => 'Error en la carga masiva de datos: ' . $e->getMessage()
        ]);
    }
}

function incluir($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $validacionesArrays = [
            'cuenta' => ['regla' => '/^[1-9][0-9]*$/', 'mensaje' => 'Cuenta por cobrar inválida.']
        ];
        validarArrays($validacionesArrays);

        $validaciones = [
            'metodo' => ['regla' => '/^[1-9][0-9]*$/', 'mensaje' => 'Método de pago inválido.'],
            'moneda' => ['regla' => '/^[1-9][0-9]*$/', 'mensaje' => 'Moneda inválida.'],
            'monto'  => ['regla' => '/^\d+(\.\d{1,2})?$/', 'mensaje' => 'Monto del pago inválido.'],
            'tasa'   => ['regla' => '/^\d+(\.\d{1,4})?$/', 'mensaje' => 'Tasa de cambio inválida.'],
        ];

        $datos = [
            'cuenta' => $_POST['cuenta'],
            'metodo' => trim($_POST['metodo']),
            'moneda' => trim($_POST['moneda']),
            'monto'  => trim($_POST['monto']),
            'tasa'   => trim($_POST['tasa']),
            'fecha'  => trim($_POST['fecha']),
        ];

        if (!empty($_POST['referencia'])) {
            $validaciones['referencia'] = ['regla' => '/^[a-zA-Z0-9\-\_]+$/', 'mensaje' => 'Referencia inválida. Solo alfanuméricos y guiones.'];
            $datos['referencia'] = trim($_POST['referencia']);
        }

        if (!empty($_POST['fecha'])) {
            $validaciones['fecha'] = ['regla' => '/^\d{4}-\d{2}-\d{2}$/', 'mensaje' => 'Formato de fecha inválido. Use AAAA-MM-DD.'];
        }

        validar_datos($validaciones);

        if (!empty($_POST['fecha'])) {
            $fecha_ingresada = strtotime($_POST['fecha']);
            $fecha_actual = strtotime(date('Y-m-d'));
            if ($fecha_ingresada > $fecha_actual) {
                throw new Exception('La fecha del pago no puede ser futura.');
            }
        }

        $obj->setCuentas(new ModeloCuentasCobrar());
        //$obj->setMetodos(new ModeloMetodosPago());
        $obj->setMonedas(new ModeloMonedas());

        $datos['accion'] = 'incluir';
        $resultado = $obj->ProcesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            $cuentas_str = is_array($datos['cuenta']) ? implode(', ', $datos['cuenta']) : $datos['cuenta'];
            registrarBitacora($bitacoraObj, $id_modulo, "Registro de Pago a las cuentas por cobrar: " . $cuentas_str);
            $resultado = array('accion' => 'incluir', 'mensaje' => 'Pago registrado exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            /* $resultado['mensaje'] = match ($resultado['codigo']) {
                INVALID_ID         => 'El método de pago seleccionado no existe.',
                INVALID_ID . '0'   => 'La moneda seleccionada no es válida.',
                EMPTY_SELECTION    => 'Debe seleccionar al menos una cuenta por cobrar.',
                DB_CONNECTION      => 'Ocurrio un error al conectarse con la base de datos.',
                default            => 'Ocurrió un error inesperado en el registro del pago.'
            }; */

            $resultado['mensaje'] = 'Ocurrio un error en el registro del pago: ' . $resultado['mensaje'];
        }
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Pagos', $e->getMessage(), 'Controlador_Incluir');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function eliminar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $validaciones = [
            'id' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id de pago inválido.'],
            'motivo_anulacion' => ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', 'mensaje' => 'Motivo de anulación inválido.']
        ];
        $_POST['id'] = trim($_POST['id']);
        validar_datos($validaciones);

        $datos = [
            'id' => $_POST['id'],
            'accion' => 'eliminar'
        ];

        $obj->setCuentas(new ModeloCuentasCobrar());

        $resultado = $obj->ProcesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Anulación del Pago: " . $datos['id'] . ' Motivo: ' . $_POST['motivo_anulacion']);
            $resultado = array('accion' => 'eliminar', 'mensaje' => 'Pago anulado exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            /* $resultado['mensaje'] = match ($resultado['codigo']) {
                INVALID_ID         => 'El pago no existe.',
                ALREADY_ANNULLED   => 'El pago ya se encuentra anulado.',
                DB_CONNECTION      => 'Ocurrio un error al conectarse con la base de datos.',
                default            => 'Ocurrió un error inesperado en la anulación del pago.'
            }; */
            $resultado['mensaje'] = 'Ocurrio un error en el registro del pago: ' . $resultado['mensaje'];
        }
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Pagos', $e->getMessage(), 'Controlador_Eliminar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}
