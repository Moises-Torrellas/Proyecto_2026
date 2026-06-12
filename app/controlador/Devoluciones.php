<?php

use App\modelo\ModeloDevoluciones;
use App\modelo\ModeloAsignaciones;
use App\modelo\ModeloCalidad; 

require_once __DIR__ . '/Base.php';

$id_modulo = _MD_DEVOLUCIONES_; 
$permisos = procesarPermisos($id_modulo, $bitacora ?? null);

$nombreClaseModelo = 'App\modelo\ModeloDevoluciones';
if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloDevoluciones();
$pagina = 'Devoluciones';

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitudDevolucion($objModelo, $id_modulo, $bitacora ?? null, $permisos);
} else {
    registrarBitacora($bitacora ?? null, $id_modulo, 'Ingreso al Modulo de Devoluciones');
    $respuesta = $objModelo->ConsultarDevoluciones();
    $registro = $respuesta['datos'] ?? [];
    
    $variables = ['registro' => $registro, 'permisos' => $permisos];
    cargarVista($pagina, $variables);
}

function manejarSolicitudDevolucion($obj, $id_modulo, $bitacoraObj, array $permisos): void
{
    try {
        $tokenRecibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!isset($_SESSION['token']) || !hash_equals($_SESSION['token'], $tokenRecibido)) {
            echo json_encode(['accion' => 'error', 'mensaje' => 'Error de seguridad CSRF.']);
            return;
        }

        $accion = filter_var($_POST['accion'], FILTER_SANITIZE_SPECIAL_CHARS);

        switch ($accion) {
            case 'consultar':
                if (empty($permisos['ingresar'])) throw new Exception(VALIDATION);
                consultar($obj, $permisos);
                break;
            case 'MultiConsulta':
                MultiConsulta();
                break;
            case 'incluir':
                if (empty($permisos['registrar'])) throw new Exception(VALIDATION);
                procesarFormulario($obj, 'incluir', $id_modulo, $bitacoraObj);
                break;
            case 'modificar':
                if (empty($permisos['modificar'])) throw new Exception(VALIDATION);
                procesarFormulario($obj, 'modificar', $id_modulo, $bitacoraObj);
                break;
            case 'anular':
                if (empty($permisos['eliminar'])) throw new Exception(VALIDATION);
                anular($obj, $id_modulo, $bitacoraObj);
                break;
            case 'generar':
                if (empty($permisos['reporte'])) throw new Exception(VALIDATION);
                generarReporte($obj, $id_modulo, $bitacoraObj);
                break;
            default:
                throw new Exception(VALIDATION);
        }
    } catch (Exception $e) {
        echo json_encode(['accion' => 'error', 'codigo' => $e->getMessage(), 'mensaje' => decodificarError($e->getMessage())]);
    }
}

function consultar($obj, $permisos): void {
    $respuesta = $obj->ConsultarDevoluciones();
    $registro = $respuesta['datos'] ?? [];
    
    $solo_lista = true;
    include (__DIR__.'/../vista/Devoluciones.php'); 
}

function MultiConsulta(): void {
    try {
        $modeloAsignaciones = new ModeloAsignaciones();
        $modeloEstado = new ModeloCalidad(); 

        $respAsignaciones = $modeloAsignaciones->ConsultarAsignaciones(); 
        $respEstado = $modeloEstado->Consultar(); 

        echo json_encode([
            'accion'       => 'MultiConsulta',
            'asignaciones' => $respAsignaciones['datos'] ?? [],
            'estados'      => $respEstado['datos'] ?? []
        ]);
    } catch (Exception $e) {
        echo json_encode(['accion' => 'error', 'codigo' => DB_CONNECTION]);
    }
}

function procesarFormulario($obj, $accion, $id_modulo, $bitacoraObj): void {
    $datos = [
        'accion' => $accion, 
        'id_devolucion' => $_POST['id_devolucion'] ?? null,
        'id_asignacion' => filter_var($_POST['id_asignacion'], FILTER_SANITIZE_NUMBER_INT), 
        'id_estado' => filter_var($_POST['id_estado'], FILTER_SANITIZE_NUMBER_INT), 
        'fecha_devolucion' => filter_var($_POST['fecha_devolucion'], FILTER_SANITIZE_SPECIAL_CHARS),
        'observacion' => filter_var($_POST['observacion'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS)
    ];
    
    $resultado = $obj->ProcesarDatos($datos);
    if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
        $mensajeBitacora = ($accion === 'incluir') ? "Registró" : "Modificó";
        registrarBitacora($bitacoraObj, $id_modulo, "$mensajeBitacora devolución de asignación ID: " . $datos['id_asignacion']);
        echo json_encode(['accion' => 'exito']);
    } else {
        echo json_encode(['accion' => 'error', 'codigo' => $resultado['codigo'], 'mensaje' => decodificarError($resultado['codigo'])]);
    }
}

function anular($obj, $id_modulo, $bitacoraObj): void {
    $datos = [
        'accion' => 'anular', 
        'id_devolucion' => filter_var($_POST['id_devolucion'], FILTER_SANITIZE_NUMBER_INT),
        'motivo_anulacion' => filter_var($_POST['motivo_anulacion'] ?? 'Anulación directa', FILTER_SANITIZE_SPECIAL_CHARS)
    ];
    
    $resultado = $obj->ProcesarDatos($datos);
    if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
        registrarBitacora($bitacoraObj, $id_modulo, "Anuló devolución ID: " . $datos['id_devolucion'] . " | Motivo: " . $datos['motivo_anulacion']);
        echo json_encode(['accion' => 'exito']);
    } else {
        echo json_encode(['accion' => 'error', 'codigo' => $resultado['codigo']]);
    }
}

function generarReporte($obj, $id_modulo, $bitacoraObj): void {
    // Tomar los filtros provenientes del Modal (si los dejaron en blanco, será null)
    $datosFiltro = [
        'accion' => 'generar',
        'id_asignacion' => !empty($_POST['id_asignacion']) ? $_POST['id_asignacion'] : null,
        'id_estado' => !empty($_POST['id_estado']) ? $_POST['id_estado'] : null,
        'fecha_devolucion' => !empty($_POST['fecha_devolucion']) ? $_POST['fecha_devolucion'] : null,
    ];

    $respuesta = $obj->ProcesarDatos($datosFiltro);
    $datos = $respuesta['datos'] ?? [];
    
    if (empty($datos)) {
        echo json_encode(['accion' => 'error', 'codigo' => VALIDATION, 'mensaje' => 'No hay registros con los filtros seleccionados.']);
        exit();
    }
    
    $objG = new \App\servicios\GenerarReporte();
    $pdf = $objG->generarPDF('R_Devoluciones', $datos, 'Devoluciones');
    
    if (isset($pdf['accion']) && $pdf['accion'] === 'reporte') {
        registrarBitacora($bitacoraObj, $id_modulo, "Generó reporte filtrado de devoluciones.");
    }
    echo json_encode($pdf);
}

function decodificarError($codigo) {
    $errores = [
        VALIDATION => 'Existen datos inválidos o faltantes en el formulario.',
        DB_CONNECTION => 'Ocurrió un error de conexión con la base de datos.',
        INVALID_ID => 'El registro seleccionado no es válido.'
    ];
    return $errores[$codigo] ?? 'Error desconocido del servidor.';
}