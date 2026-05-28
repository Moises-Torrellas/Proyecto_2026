<?php

use App\modelo\ModeloTorneos;

// 1. Cargamos las funciones base
require_once __DIR__ . '/Base.php';

// 2. Configuración del módulo 
$id_modulo = _MD_TORNEOS_;

// 3. Procesar permisos (Retorna el array de permisos)
$permisos = procesarPermisos($id_modulo, $bitacora);

// 4. Lógica de despacho (Router interno)
$nombreClaseModelo = 'App\modelo\ModeloTorneos';

if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloTorneos();

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitudTorneos($objModelo, $id_modulo, $bitacora, $permisos);
} else {
    registrarBitacora($bitacora , $id_modulo, 'Ingreso al Modulo');
    cargarVista($pagina);
}

/**
 * --- FUNCIONES DEL CONTROLADOR ---
 */

function manejarSolicitudTorneos($obj, $id_modulo, $bitacoraObj, array $permisos): void
{
    try {
        $tokenRecibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!isset($_SESSION['token']) || !hash_equals($_SESSION['token'], $tokenRecibido)) {
            throw new Exception('Error de seguridad: Token inválido o expirado.');
        }

        $accion = isset($_POST['accion']) ? filter_var($_POST['accion'], FILTER_SANITIZE_SPECIAL_CHARS) : '';

        switch ($accion) {
            case 'consultar':
                consultar($obj);
                break;
            case 'buscar':
                if (!$permisos['modificar']) throw new Exception('No tienes permisos para buscar/modificar torneos.');
                buscar($obj);
                break;
            case 'incluir':
                if (!$permisos['registrar']) throw new Exception('No tienes permisos para registrar torneos.');
                incluir($obj, $id_modulo, $bitacoraObj);
                break;
            case 'eliminar':
                if (!$permisos['eliminar']) throw new Exception('No tienes permisos para eliminar torneos.');
                eliminar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'modificar':
                if (!$permisos['modificar']) throw new Exception('No tienes permisos para modificar torneos.');
                modificar($obj, $id_modulo, $bitacoraObj);
                break;
            default:
                throw new Exception('Acción no permitida.');
        }
    } catch (Exception $e) {
        logs('Torneos', $e->getMessage(), 'Controlador_ManejarSolicitud');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

/**
 * --- LÓGICA DE ACCIONES ---
 */

function consultar($obj): void
{
    $filtro['filtro'] = $_POST['filtro'] ?? '';
    $respuesta = $obj->Consultar($filtro);
    
    if (isset($respuesta['accion']) && $respuesta['accion'] == 'error') {
        $respuesta['mensaje'] = 'Error al listar los torneos.';
    }
    
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
        logs('Torneos', $e->getMessage(), 'Controlador_Buscar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function incluir($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $validaciones = [
            'nombre'       => ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\-\s]{2,30}$/u', 'mensaje' => 'Nombre de torneo inválido.'],
            'fecha_inicio' => ['regla' => '/^\d{4}-\d{2}-\d{2}$/', 'mensaje' => 'Fecha de inicio inválida.'],
            'fecha_fin'    => ['regla' => '/^\d{4}-\d{2}-\d{2}$/', 'mensaje' => 'Fecha de fin inválida.'],
            'ubicacion'    => ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s.,#-]{5,150}$/u', 'mensaje' => 'Ubicación inválida.'],
            'estatus'      => ['regla' => '/^[0-9]$/', 'mensaje' => 'Estatus inválido.']
        ];

        validar_datos($validaciones);

        if (strtotime($_POST['fecha_inicio']) > strtotime($_POST['fecha_fin'])) {
            throw new Exception('La fecha de inicio no puede ser mayor que la fecha de fin.');
        }

        $datos = [
            'nombre'       => $_POST['nombre'],
            'fecha_inicio' => $_POST['fecha_inicio'],
            'fecha_fin'    => $_POST['fecha_fin'],
            'ubicacion'    => $_POST['ubicacion'],
            'estatus'      => $_POST['estatus'],
            'accion'       => 'incluir'
        ];

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Registró el torneo: " . $_POST['nombre']);
            $resultado = ['accion' => 'incluir', 'mensaje' => 'Torneo registrado exitosamente.'];
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            // Mapeo de errores específicos del modelo
            $resultado['mensaje'] = match ($resultado['codigo']) {
                'Ya existe un torneo registrado con este nombre.' => $resultado['codigo'],
                default => 'Ocurrió un error inesperado en el registro del torneo.'
            };
        }

        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Torneos', $e->getMessage(), 'Controlador_Incluir');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function modificar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $validaciones = [
            'id'           => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.'],
            'nombre'       => ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\-\s]{2,30}$/u', 'mensaje' => 'Nombre de torneo inválido.'],
            'fecha_inicio' => ['regla' => '/^\d{4}-\d{2}-\d{2}$/', 'mensaje' => 'Fecha de inicio inválida.'],
            'fecha_fin'    => ['regla' => '/^\d{4}-\d{2}-\d{2}$/', 'mensaje' => 'Fecha de fin inválida.'],
            'ubicacion'    => ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s.,#-]{5,150}$/u', 'mensaje' => 'Ubicación inválida.'],
            'estatus'      => ['regla' => '/^[0-9]$/', 'mensaje' => 'Estatus inválido.']
        ];

        validar_datos($validaciones);

        if (strtotime($_POST['fecha_inicio']) > strtotime($_POST['fecha_fin'])) {
            throw new Exception('La fecha de inicio no puede ser mayor que la fecha de fin.');
        }

        $datos = [
            'id'           => $_POST['id'],
            'nombre'       => $_POST['nombre'],
            'fecha_inicio' => $_POST['fecha_inicio'],
            'fecha_fin'    => $_POST['fecha_fin'],
            'ubicacion'    => $_POST['ubicacion'],
            'estatus'      => $_POST['estatus'],
            'accion'       => 'modificar'
        ];

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Modificó el torneo: " . $_POST['nombre']);
            $resultado = ['accion' => 'modificar', 'mensaje' => 'Torneo modificado exitosamente.'];
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                'Ya existe otro torneo registrado con este nombre.' => $resultado['codigo'],
                default => 'Ocurrió un error inesperado al modificar el torneo.'
            };
        }

        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Torneos', $e->getMessage(), 'Controlador_Modificar');
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
            registrarBitacora($bitacoraObj, $id_modulo, "Eliminó el torneo con ID: " . $_POST['id']);
            $resultado = ['accion' => 'eliminar', 'mensaje' => 'Torneo eliminado exitosamente.'];
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                'El torneo no existe.' => $resultado['codigo'],
                'No se puede eliminar: el torneo tiene equipos o atletas asociados.' => $resultado['codigo'],
                default => 'Ocurrió un error inesperado al eliminar el torneo.'
            };
        }
        
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Torneos', $e->getMessage(), 'Controlador_Eliminar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}