<?php

use App\modelo\ModeloPagos;
use App\modelo\ModeloCuentasCobrar;
use App\modelo\ModeloMonedas;
use App\modelo\ModeloMetodosPago;
use App\modelo\ModeloTasaCambios;

use App\servicios\GenerarReporte;

// 1. Cargamos las funciones base
require_once __DIR__ . '/Base.php';

// 2. Configuración del módulo
$id_modulo = _MD_PAGOS_;

// 3. Procesar permisos (Retorna el array de permisos)
$permisos = procesarPermisos($id_modulo, 'ingresar_pago');

// 4. Lógica de despacho (Router interno)
$nombreClaseModelo = 'App\modelo\ModeloPagos';

if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}



$objPago = new ModeloPagos();

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitud($objPago, $id_modulo, $bitacora ?? null, $permisos);
} else {
    registrarBitacora($bitacora, $id_modulo, 'Ingreso al Modulo');
    $respuesta = $objPago->Consultar();
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
                if (empty($permisos['ingresar_pago'])) throw new Exception('No tienes permisos para consultar pagos.');
                consultar($obj, $permisos);
                break;
            case 'incluir':
                if (empty($permisos['registrar_pago'])) throw new Exception('No tienes permisos para registrar pagos.');
                incluir($obj, $id_modulo, $bitacoraObj);
                break;
            case 'eliminar':
                if (empty($permisos['anular_pago'])) throw new Exception('No tienes permisos para anular pagos.');
                eliminar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'MultiConsulta':
                if (empty($permisos['ingresar_pago'])) throw new Exception('No tienes permisos para consultar pagos.');
                MultiConsulta();
                break;
            case 'registrar_vuelto':
                if (empty($permisos['registrar_pago'])) throw new Exception('No tienes permisos.');
                registrar_vuelto($obj, $id_modulo, $bitacoraObj);
                break;
            case 'consultar_tasas_disponibles':
                if (empty($permisos['ingresar_pago'])) throw new Exception('No tienes permisos.');
                consultar_tasas_disponibles($obj);
                break;
            /* case 'consultarTasa':
                consultarTasa($obj);
                break; */
            case 'generar':
                if (empty($permisos['generar_pago']))
                generar($obj, $id_modulo, $bitacoraObj);
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
function consultar_tasas_disponibles($obj): void
{
    try {
        $datos = [
            'accion' => 'consultar_tasas_disponibles',
            'fecha' => $_POST['fecha'] ?? date('Y-m-d'),
            'codigo_moneda' => $_POST['codigo_moneda'] ?? null
        ];
        $resultado = $obj->procesarDatos($datos);
        echo json_encode($resultado);
    } catch (Exception $e) {
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
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
        $modeloCuentas = new ModeloCuentasCobrar();
        $modeloMonedas = new ModeloMonedas();
        $modeloMP      = new ModeloMetodosPago();

        $respCuentas = $modeloCuentas->ConsultarCargos();
        $respMonedas = $modeloMonedas->ConsultarMonedas();
        $respMP      = $modeloMP->ConsultarMetodos();
        
        $cuentasDatos = isset($respCuentas['datos']) ? $respCuentas['datos'] : [];
        $monedasDatos = isset($respMonedas['datos']) ? $respMonedas['datos'] : [];
        $metodosDatos = isset($respMP['datos']) ? $respMP['datos'] : [];
        
        echo json_encode([
            'accion'  => 'MultiConsulta',
            'cuentas' => $cuentasDatos,
            'monedas' => $monedasDatos,
            'metodos' => $metodosDatos
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
        ];

        $datos = [
            'cuenta' => $_POST['cuenta'],
            'metodo' => trim($_POST['metodo']),
            'moneda' => trim($_POST['moneda']),
            'monto'  => trim($_POST['monto']),
            'tasa'   => isset($_POST['tasa']) ? trim($_POST['tasa']) : '1',
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

        $obj->setMonedas(new ModeloMonedas());
        $obj->setCuentas(new ModeloCuentasCobrar());
        $obj->setTasa(new ModeloTasaCambios());

        $datos['accion'] = 'incluir';
        $resultado = $obj->ProcesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            $cuentas_str = is_array($datos['cuenta']) ? implode(', ', $datos['cuenta']) : $datos['cuenta'];
            registrarBitacora($bitacoraObj, $id_modulo, "Registro de Pago a las cuentas por cobrar: " . $cuentas_str);
            $resultado = array(
                'accion' => 'incluir', 
                'mensaje' => 'Pago registrado exitosamente.', 
                'vuelto' => $resultado['vuelto'] ?? 0,
                'id_pago' => $resultado['id_pago'] ?? 0
            );
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                INVALID_ID         => 'El método de pago seleccionado no existe.',
                INVALID_ID . '0'   => 'La moneda seleccionada no es válida.',
                EMPTY_SELECTION    => 'Debe seleccionar al menos una cuenta por cobrar.',
                DB_CONNECTION      => 'Ocurrio un error al conectarse con la base de datos.',
                default            => 'Ocurrió un error inesperado en el registro del pago.'
            };

            //$resultado['mensaje'] = 'Ocurrio un error en el registro del pago: ' . $resultado['mensaje'];
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
            $resultado['mensaje'] = match ($resultado['codigo']) {
                INVALID_ID         => 'El pago no existe.',
                ALREADY_ANNULLED   => 'El pago ya se encuentra anulado.',
                DB_CONNECTION      => 'Ocurrio un error al conectarse con la base de datos.',
                default            => 'Ocurrió un error inesperado en la anulación del pago.'
            };
            //$resultado['mensaje'] = 'Ocurrio un error en el registro del pago: ' . $resultado['mensaje'];
        }
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Pagos', $e->getMessage(), 'Controlador_Eliminar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function registrar_vuelto($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $validaciones = [
            'codigo_pago' => ['regla' => '/^[1-9][0-9]*$/', 'mensaje' => 'Pago inválido.'],
            'codigo_metodo' => ['regla' => '/^[1-9][0-9]*$/', 'mensaje' => 'Método de pago inválido.'],
            'codigo_moneda' => ['regla' => '/^[1-9][0-9]*$/', 'mensaje' => 'Moneda inválida.'],
            'monto_vuelto' => ['regla' => '/^\d+(\.\d{1,2})?$/', 'mensaje' => 'Monto inválido.']
        ];
        validar_datos($validaciones);

        $datos = [
            'codigo_pago' => $_POST['codigo_pago'],
            'codigo_metodo' => $_POST['codigo_metodo'],
            'codigo_moneda' => $_POST['codigo_moneda'],
            'monto_vuelto' => $_POST['monto_vuelto'],
            'fecha_vuelto' => $_POST['fecha_vuelto'] ?? date('Y-m-d'),
            'referencia' => $_POST['referencia_vuelto'] ?? '',
            'accion' => 'registrar_vuelto'
        ];

        $resultado = $obj->ProcesarDatos($datos);
        if (isset($resultado['accion']) && $resultado['accion'] === 'exito_vuelto') {
            registrarBitacora($bitacoraObj, $id_modulo, "Registro de vuelto para el pago: " . $datos['codigo_pago']);
        }
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Pagos', $e->getMessage(), 'Controlador_RegistrarVuelto');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function generar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $validacionesReporte = [];
        $datosFiltro = ['accion' => 'generar'];

        $datosFiltro['anulados'] = isset($_POST['anulados']) ? 1 : 0;
        if (!empty($_POST['metodo'])) {
            $validacionesReporte['metodo'] = ['regla' => '/^[1-9][0-9]*$/', 'mensaje' => 'Método de pago inválido.'];
            $datosFiltro['metodo'] = $_POST['metodo'];
        }
        if (!empty($_POST['moneda'])) {
            $validacionesReporte['moneda'] = ['regla' => '/^[1-9][0-9]*$/', 'mensaje' => 'Moneda inválida.'];
            $datosFiltro['moneda'] = $_POST['moneda'];
        }
        if (!empty($_POST['fecha'])) {
            $validacionesReporte['fecha'] = ['regla' => '/^\d{4}-\d{2}-\d{2}$/', 'mensaje' => 'Formato de fecha inválido. Use AAAA-MM-DD.'];
            $datosFiltro['fecha'] = $_POST['fecha'];
        }
        if (!empty($_POST['fecha_f'])) {
            $validacionesReporte['fecha_f'] = ['regla' => '/^\d{4}-\d{2}-\d{2}$/', 'mensaje' => 'Formato de fecha inválido. Use AAAA-MM-DD.'];
            $datosFiltro['fecha_f'] = $_POST['fecha_f'];
        }

        validar_datos($validacionesReporte);

        $respuesta = $obj->procesarDatos($datosFiltro);

        // NUEVO: Validar si el modelo retornó un error estructurado
        if (isset($respuesta['accion']) && $respuesta['accion'] === 'error') {
            echo json_encode(['accion' => 'error', 'mensaje' => $respuesta['mensaje']]);
            exit();
        }

        $datos = $respuesta['datos'] ?? [];
        if (empty($datos)) {
            echo json_encode(['accion' => 'error', 'mensaje' => 'No se encontraron pagos para hacer el reporte.']);
            exit();
        }
        $nombreVista = 'R_Pagos';
        $objG = new GenerarReporte();
        $pdf = $objG->generarPDF($nombreVista, $datos, 'Pagos');
        if (isset($pdf['accion']) && $pdf['accion'] === 'reporte') {
            registrarBitacora($bitacoraObj, $id_modulo, "Generó reporte de Pagos.");
        }
        echo json_encode($pdf);
    } catch (Exception $e) {
        logs('Pagos', $e->getMessage(), 'Controlador_Generar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}
