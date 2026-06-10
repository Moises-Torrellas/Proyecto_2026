<?php

use App\modelo\ModeloDevoluciones;
use App\modelo\ModeloAsignaciones;
use App\modelo\ModeloCalidad; 
use App\servicios\GenerarReporte;

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
        if (!isset($_SESSION['token']) || !hash_equals($_SESSION['token'], $tokenRecibido)) throw new Exception('Error de seguridad.');

        $accion = filter_var($_POST['accion'], FILTER_SANITIZE_SPECIAL_CHARS);

        switch ($accion) {
            case 'consultar':
                if (empty($permisos['ingresar'])) throw new Exception('Sin permisos.');
                consultar($obj, $permisos);
                break;
            case 'MultiConsulta':
                if (empty($permisos['ingresar'])) throw new Exception('Sin permisos.');
                MultiConsulta();
                break;
            case 'incluir':
                if (empty($permisos['registrar'])) throw new Exception('Sin permisos.');
                incluir($obj, $id_modulo, $bitacoraObj);
                break;
            case 'modificar':
                if (empty($permisos['modificar'])) throw new Exception('Sin permisos.');
                modificar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'anular':
                if (empty($permisos['eliminar'])) throw new Exception('Sin permisos.');
                anular($obj, $id_modulo, $bitacoraObj);
                break;
            case 'generar':
                if (empty($permisos['reporte'])) throw new Exception('No tienes permisos para generar un reporte.');
                generar($obj, $id_modulo, $bitacoraObj);
                break;
            default:
                throw new Exception('Acción no permitida.');
        }
    } catch (Exception $e) {
        logs('Devoluciones', $e->getMessage(), 'Controlador_ManejarSolicitud');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
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

        $respAsignaciones = $modeloAsignaciones->ConsultarAgrupado(); 
        $respEstado = $modeloEstado->Consultar(); 

        echo json_encode([
            'accion'       => 'MultiConsulta',
            'asignaciones' => $respAsignaciones['datos'] ?? [],
            'estados'      => $respEstado['datos'] ?? []
        ]);
    } catch (Exception $e) {
        echo json_encode(['accion' => 'error', 'mensaje' => 'Error al cargar listas.']);
    }
}

function incluir($obj, $id_modulo, $bitacoraObj): void {
    try {
        $validaciones = [
            'id_asignacion'    => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Asignación inválida.'],
            'id_estado'        => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Estado inválido.'],
            'fecha_devolucion' => ['regla' => '/^\d{4}-\d{2}-\d{2}$/', 'mensaje' => 'Formato de fecha inválido.']
        ];
        validar_datos($validaciones);

        $datos = [
            'accion' => 'incluir', 
            'id_asignacion' => $_POST['id_asignacion'], 
            'id_estado' => $_POST['id_estado'], 
            'fecha_devolucion' => $_POST['fecha_devolucion'],
            'observacion' => filter_var($_POST['observacion'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS)
        ];
        
        $resultado = $obj->ProcesarDatos($datos);
        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') registrarBitacora($bitacoraObj, $id_modulo, "Registró la devolución de asignación ID: " . $datos['id_asignacion']);
        echo json_encode($resultado);
    } catch (Exception $e) { echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]); }
}

function modificar($obj, $id_modulo, $bitacoraObj): void {
    try {
        $validaciones = [
            'id_devolucion'    => ['regla' => '/^[0-9]+$/', 'mensaje' => 'ID inválido.'],
            'id_asignacion'    => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Asignación inválida.'],
            'id_estado'        => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Estado inválido.'],
            'fecha_devolucion' => ['regla' => '/^\d{4}-\d{2}-\d{2}$/', 'mensaje' => 'Formato de fecha inválido.']
        ];
        validar_datos($validaciones);

        $datos = [
            'accion' => 'modificar', 
            'id_devolucion' => $_POST['id_devolucion'], 
            'id_asignacion' => $_POST['id_asignacion'], 
            'id_estado' => $_POST['id_estado'], 
            'fecha_devolucion' => $_POST['fecha_devolucion'],
            'observacion' => filter_var($_POST['observacion'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS)
        ];
        
        $resultado = $obj->ProcesarDatos($datos);
        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') registrarBitacora($bitacoraObj, $id_modulo, "Modificó devolución ID: " . $datos['id_devolucion']);
        echo json_encode($resultado);
    } catch (Exception $e) { echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]); }
}

function anular($obj, $id_modulo, $bitacoraObj): void {
    try {
        $validaciones = ['id_devolucion' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Inválido.']];
        validar_datos($validaciones);
        
        $motivo = trim(filter_var($_POST['motivo'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS));
        if (strlen($motivo) < 5) throw new Exception('El motivo debe tener al menos 5 letras.');

        $datos = ['accion' => 'anular', 'id_devolucion' => $_POST['id_devolucion']];
        $resultado = $obj->ProcesarDatos($datos);
        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') registrarBitacora($bitacoraObj, $id_modulo, "Anuló devolución ID: " . $datos['id_devolucion']);
        echo json_encode($resultado);
    } catch (Exception $e) { echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]); }
}

function generar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $validacionesReporte = [];
        $datosFiltro = ['accion' => 'generar'];

        // Capturamos los filtros si el usuario los llenó
        if (!empty($_POST['fecha_devolucion'])) {
            $validacionesReporte['fecha_devolucion'] = ['regla' => '/^\d{4}-\d{2}-\d{2}$/', 'mensaje' => 'Formato de fecha inválido.'];
            $datosFiltro['fecha_devolucion'] = $_POST['fecha_devolucion'];
        }
        if (!empty($_POST['id_asignacion'])) {
            $validacionesReporte['id_asignacion'] = ['regla' => '/^[0-9]+$/', 'mensaje' => 'Asignación inválida.'];
            $datosFiltro['id_asignacion'] = $_POST['id_asignacion'];
        }
        if (!empty($_POST['id_estado'])) {
            $validacionesReporte['id_estado'] = ['regla' => '/^[0-9]+$/', 'mensaje' => 'Estado inválido.'];
            $datosFiltro['id_estado'] = $_POST['id_estado'];
        }

        if (!empty($validacionesReporte)) {
            validar_datos($validacionesReporte);
        }

        $respuesta = $obj->procesarDatos($datosFiltro);
        $datos = $respuesta['datos'] ?? [];
        
        if (empty($datos)) {
            echo json_encode(['accion' => 'error', 'mensaje' => 'No se encontraron devoluciones con los filtros seleccionados.']);
            exit();
        }
        
        $nombreVista = 'R_Devoluciones';
        $objG = new \App\servicios\GenerarReporte();
        $pdf = $objG->generarPDF($nombreVista, $datos, 'Devoluciones');
        
        if (isset($pdf['accion']) && $pdf['accion'] === 'reporte') {
            registrarBitacora($bitacoraObj, $id_modulo, "Generó reporte de devoluciones.");
        }
        echo json_encode($pdf);
    } catch (Exception $e) {
        logs('Devoluciones', $e->getMessage(), 'Controlador_Generar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}