<?php

use App\modelo\ModeloPagos;
use App\modelo\ModeloCuentasCobrar;
use App\modelo\ModeloMonedas;
use App\modelo\ModeloMetodosPago;

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
            case 'generar':
                if (empty($permisos['generar_pago'])) throw new Exception('No tienes permisos para generar reportes de pagos.');
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
        
        $resultado = $obj->ProcesarDatos($datos);
        
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
        validar_requeridos(['cuenta', 'metodo', 'moneda', 'monto', 'fecha']);

        $datos = [
            'cuenta'     => $_POST['cuenta'],
            'metodo'     => trim($_POST['metodo']),
            'moneda'     => trim($_POST['moneda']),
            'monto'      => trim($_POST['monto']),
            'tasa'       => isset($_POST['tasa']) ? trim($_POST['tasa']) : '1',
            'fecha'      => trim($_POST['fecha']),
            'referencia' => isset($_POST['referencia']) ? trim($_POST['referencia']) : '',
        ];

        if (isset($_POST['monto_vuelto']) && (float)$_POST['monto_vuelto'] > 0) {
            $datos['monto_vuelto']         = trim($_POST['monto_vuelto']);
            $datos['codigo_metodo_vuelto'] = trim($_POST['codigo_metodo_vuelto'] ?? '');
            $datos['codigo_moneda_vuelto'] = trim($_POST['codigo_moneda_vuelto'] ?? '');
            $datos['referencia_vuelto']    = trim($_POST['referencia_vuelto'] ?? '');
            $datos['fecha_vuelto']         = trim($_POST['fecha_vuelto'] ?? date('Y-m-d'));
        }

        $datos['accion'] = 'incluir';
        $resultado = $obj->ProcesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            $cuentas_str = is_array($datos['cuenta']) ? implode(', ', $datos['cuenta']) : $datos['cuenta'];
            $desc_bitacora = $resultado['desc_bitacora'] ?? "Registro de Pago a las cuentas por cobrar: " . $cuentas_str;
            $datos_previos = '';
            $datos_nuevos = $resultado['datos_nuevos'] ?? '';
            registrarBitacora($bitacoraObj, $id_modulo, $desc_bitacora, $datos_previos, $datos_nuevos);
            $resultado = array(
                'accion' => 'incluir', 
                'mensaje' => 'Pago registrado exitosamente.', 
                'vuelto' => $resultado['vuelto'] ?? 0,
                'id_pago' => $resultado['id_pago'] ?? 0
            );
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo'] ?? '') {
                INVALID_ID         => 'El método de pago seleccionado no existe.',
                INVALID_ID . '0'   => 'La moneda seleccionada no es válida.',
                EMPTY_SELECTION    => 'Debe seleccionar al menos una cuenta por cobrar.',
                DB_CONNECTION      => 'Ocurrio un error al conectarse con la base de datos.',
                default            => $resultado['mensaje'] ?? 'Ocurrió un error inesperado en el registro del pago.'
            };
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
        validar_requeridos(['id']);

        $datos = [
            'id'               => trim($_POST['id']),
            'motivo_anulacion' => trim($_POST['motivo_anulacion'] ?? 'Anulación voluntaria'),
            'accion'           => 'eliminar'
        ];

        $resultado = $obj->ProcesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            $desc_bitacora = $resultado['desc_bitacora'] ?? "Anulación del Pago: " . $datos['id'];
            if (!empty($datos['motivo_anulacion'])) {
                $desc_bitacora .= ' Motivo: ' . $datos['motivo_anulacion'];
            }
            $datos_previos = $resultado['datos_previos'] ?? '';
            $datos_nuevos = '';
            registrarBitacora($bitacoraObj, $id_modulo, $desc_bitacora, $datos_previos, $datos_nuevos);
            $resultado = array('accion' => 'eliminar', 'mensaje' => 'Pago anulado exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo'] ?? '') {
                INVALID_ID         => 'El pago no existe.',
                ALREADY_ANNULLED   => 'El pago ya se encuentra anulado.',
                DB_CONNECTION      => 'Ocurrio un error al conectarse con la base de datos.',
                default            => $resultado['mensaje'] ?? 'Ocurrió un error inesperado en la anulación del pago.'
            };
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
        validar_requeridos(['codigo_pago', 'codigo_metodo', 'codigo_moneda', 'monto_vuelto']);

        $datos = [
            'codigo_pago'   => trim($_POST['codigo_pago']),
            'codigo_metodo' => trim($_POST['codigo_metodo']),
            'codigo_moneda' => trim($_POST['codigo_moneda']),
            'monto_vuelto'  => trim($_POST['monto_vuelto']),
            'fecha_vuelto'  => trim($_POST['fecha_vuelto'] ?? date('Y-m-d')),
            'referencia'    => trim($_POST['referencia_vuelto'] ?? ''),
            'accion'        => 'registrar_vuelto'
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
        $datosFiltro = ['accion' => 'generar'];

        $datosFiltro['anulados'] = isset($_POST['anulados']) ? 1 : 0;
        if (!empty($_POST['metodo']))  $datosFiltro['metodo']  = trim($_POST['metodo']);
        if (!empty($_POST['moneda']))  $datosFiltro['moneda']  = trim($_POST['moneda']);
        if (!empty($_POST['fecha']))   $datosFiltro['fecha']   = trim($_POST['fecha']);
        if (!empty($_POST['fecha_f'])) $datosFiltro['fecha_f'] = trim($_POST['fecha_f']);

        $respuesta = $obj->procesarDatos($datosFiltro);

        if (isset($respuesta['accion']) && $respuesta['accion'] === 'error') {
            $mensajeError = ($respuesta['mensaje'] == DB_CONNECTION) ? 'Error al conectar con la base de datos.' : $respuesta['mensaje'];
            echo json_encode(['accion' => 'error', 'mensaje' => $mensajeError]);
            return;
        }

        $datos = $respuesta['datos'] ?? [];
        if (empty($datos)) {
            echo json_encode(['accion' => 'error', 'mensaje' => 'No se encontraron pagos para hacer el reporte.']);
            return;
        }
        $nombreVista = 'R_Pagos';
        $objG = new GenerarReporte();
        
        $formato = $_POST['formato'] ?? 'pdf';
        if ($formato === 'excel') {
            $reporte = $objG->generarExcel($nombreVista, $datos, 'Pagos');
        } else {
            $reporte = $objG->generarPDF($nombreVista, $datos, 'Pagos');
        }

        if (isset($reporte['accion']) && $reporte['accion'] === 'reporte') {
            registrarBitacora($bitacoraObj, $id_modulo, "Generó reporte de Pagos en " . strtoupper($formato));
        }
        echo json_encode($reporte);
    } catch (Exception $e) {
        logs('Pagos', $e->getMessage(), 'Controlador_Generar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}
