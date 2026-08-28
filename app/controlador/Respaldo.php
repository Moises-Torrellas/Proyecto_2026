<?php

use App\modelo\ModeloRespaldo;
use App\modelo\ModeloBitacora;

require_once __DIR__ . '/Base.php';
$id_modulo = _MD_RESPALDO_; 
$permisos = procesarPermisos($id_modulo, 'ingresar_respaldos');
$nombreClaseModelo = 'App\modelo\ModeloRespaldo';

if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloRespaldo();
$bitacoraObj = new ModeloBitacora();

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitudRespaldo($objModelo, $id_modulo, $bitacoraObj, $permisos);
} else {
    registrarBitacora($bitacoraObj, $id_modulo, 'Ingreso al Modulo');
    $respuesta = $objModelo->ProcesarDatos(['accion' => 'consultar']);
    
    $registro = [];
    if (!isset($respuesta['accion']) || $respuesta['accion'] !== 'error') {
        $registro = $respuesta['datos'] ?? [];
    }
    
    $variables = ['registro' => $registro, 'permisos' => $permisos];
    cargarVista($pagina, $variables);
}

function manejarSolicitudRespaldo($obj, $id_modulo, $bitacoraObj, array $permisos): void
{
    try {
        $tokenRecibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!isset($_SESSION['token']) || !hash_equals($_SESSION['token'], $tokenRecibido)) {
            throw new Exception('Error de seguridad: Token inválido.');
        }

        $accion = filter_var($_POST['accion'], FILTER_SANITIZE_SPECIAL_CHARS);
        switch ($accion) {
            case 'consultar':
                if (empty($permisos['ingresar_respaldos'])) throw new Exception('Sin permisos para consultar respaldos.');
                consultarBackups($obj, $permisos);
                break;
            case 'generar':
                if (empty($permisos['registrar_respaldo'])) throw new Exception('Sin permisos para generar.');
                generarBackup($obj, $id_modulo, $bitacoraObj);
                break;
            case 'restaurar':
                if (empty($permisos['modificar_respaldo'])) throw new Exception('Sin permisos para restaurar.');
                restaurarBackup($obj, $id_modulo, $bitacoraObj);
                break;
            case 'eliminar':
                if (empty($permisos['eliminar_respaldo'])) throw new Exception('Sin permisos para eliminar.');
                eliminarBackup($obj, $id_modulo, $bitacoraObj);
                break;
            default:
                throw new Exception('Acción no permitida.');
        }
    } catch (Exception $e) {
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function consultarBackups($obj, $permisos): void {
    try {
        $filtro['filtro'] = $_POST['filtro'] ?? '';
        $respuesta = $obj->ProcesarDatos(['accion' => 'consultar', 'filtro' => $filtro['filtro']]);
        
        if (isset($respuesta['accion']) && $respuesta['accion'] == 'error') {
            echo json_encode(['accion' => 'error', 'mensaje' => 'Error al leer el directorio de respaldos.']);
            return;
        }

        $registro = $respuesta['datos'] ?? [];
        $solo_lista = true;
        include(__DIR__ . '/../vista/Respaldo.php');
    } catch (Exception $e) {
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function generarBackup($obj, $id_modulo, $bitacoraObj): void {
    try {
        $resultado = $obj->ProcesarDatos(['accion' => 'generar']);
        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Generó el respaldo: " . $resultado['nombre']);
            $resultado = ['accion' => 'generar', 'mensaje' => 'Respaldo creado de forma segura en el servidor.'];
        } else {
            $resultado['mensaje'] = $resultado['codigo'] ?? 'Ocurrió un error al generar el respaldo.';
        }
        echo json_encode($resultado);
    } catch (Exception $e) {
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function restaurarBackup($obj, $id_modulo, $bitacoraObj): void {
    try {
        $datos = [
            'accion' => 'restaurar',
            'archivo' => $_POST['archivo']
        ];
        $resultado = $obj->ProcesarDatos($datos);
        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Restauró el sistema usando: " . $_POST['archivo']);
            $resultado = ['accion' => 'restaurar', 'mensaje' => 'La base de datos ha sido restaurada con éxito.'];
        } else {
            $resultado['mensaje'] = $resultado['codigo'] ?? 'Error de seguridad al restaurar.';
        }
        echo json_encode($resultado);
    } catch (Exception $e) {
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function eliminarBackup($obj, $id_modulo, $bitacoraObj): void {
    try {
        $datos = ['accion' => 'eliminar', 'archivo' => $_POST['archivo']];
        $resultado = $obj->ProcesarDatos($datos);
        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Eliminó el archivo de respaldo: " . $_POST['archivo']);
            $resultado = ['accion' => 'eliminar', 'mensaje' => 'Respaldo eliminado del servidor.'];
        }
        echo json_encode($resultado);
    } catch (Exception $e) {
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}