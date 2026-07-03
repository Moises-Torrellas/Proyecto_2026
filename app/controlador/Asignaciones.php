<?php

use App\modelo\ModeloAsignaciones;
use App\modelo\ModeloAtletas;
use App\modelo\ModeloArticulosInventario;

require_once __DIR__ . '/Base.php';

$id_modulo = _MD_ASIGNACIONES_; 
$permisos = procesarPermisos($id_modulo, '');

$nombreClaseModelo = 'App\modelo\ModeloAsignaciones';
if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objAsignaciones = new ModeloAsignaciones();

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitudAsignacion($objAsignaciones, $id_modulo, $bitacora ?? null, $permisos);
} else {
    registrarBitacora($bitacora ?? null, $id_modulo, 'Ingreso al Modulo de Asignaciones');
    $respuesta = $objAsignaciones->ConsultarAsignaciones(); 
    
    $error_bd = '';
    if (isset($respuesta['accion']) && $respuesta['accion'] === 'error') {
        $error_bd = !empty($respuesta['mensaje']) ? $respuesta['mensaje'] : 'Error al conectar con la base de datos.';
    }

    $registro = $respuesta['datos'] ?? [];
    
    $variables = ['registro' => $registro, 'permisos' => $permisos, 'error_bd' => $error_bd];
    cargarVista($pagina, $variables);
}

function manejarSolicitudAsignacion($obj, $id_modulo, $bitacoraObj, array $permisos): void
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
            default:
                throw new Exception('Acción no permitida.');
        }
    } catch (Exception $e) {
        logs('Asignaciones', $e->getMessage(), 'Controlador_ManejarSolicitud');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function consultar($obj, $permisos): void {
    $respuesta = $obj->ConsultarAsignaciones();
    $registro = $respuesta['datos'] ?? []; 
    $solo_lista = true;
    include (__DIR__.'/../vista/Asignaciones.php');
}

function MultiConsulta(): void {
    try {
        $modeloAtletas = new ModeloAtletas();
        $modeloArticulos = new ModeloArticulosInventario(); 

        $respAtletas = $modeloAtletas->Consultar(); 
        $respEquip = $modeloArticulos->ConsultarArticulosLibres(); 

        echo json_encode([
            'accion'  => 'MultiConsulta',
            'atletas' => $respAtletas['datos'] ?? [],
            'equipos' => $respEquip['datos'] ?? []
        ]);
    } catch (Exception $e) {
        echo json_encode(['accion' => 'error', 'codigo' => DB_CONNECTION]);
    }
}

function incluir($obj, $id_modulo, $bitacoraObj): void {
    try {
        validar_requeridos(['codigo_atleta', 'codigo_articulo', 'fecha_asignacion']);

        $datos = [
            'accion'           => 'incluir', 
            'codigo_atleta'    => $_POST['codigo_atleta'], 
            'codigo_articulo'  => $_POST['codigo_articulo'], 
            'fecha_asignacion' => $_POST['fecha_asignacion']
        ];
        
        $obj->setArticulos(new ModeloArticulosInventario());
        
        $resultado = $obj->ProcesarDatos($datos);
        
        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Asignó el artículo ID: " . $datos['codigo_articulo']);
            $resultado = array('accion' => 'exito', 'mensaje' => 'Asignación procesada exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                VALIDATION    => 'El artículo seleccionado ya no está disponible.',
                DB_CONNECTION => 'Ocurrió un error al conectarse con la base de datos.',
                default       => 'Ocurrió un error inesperado al procesar la asignación.'
            };
        }
        echo json_encode($resultado);
    } catch (Exception $e) { 
        logs('Asignaciones', $e->getMessage(), 'Controlador_Incluir');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]); 
    }
}

function modificar($obj, $id_modulo, $bitacoraObj): void {
    try {
        validar_requeridos(['id_asignacion', 'codigo_atleta', 'codigo_articulo', 'fecha_asignacion']);

        $datos = [
            'accion'           => 'modificar', 
            'id_asignacion'    => $_POST['id_asignacion'], 
            'codigo_atleta'    => $_POST['codigo_atleta'], 
            'codigo_articulo'  => $_POST['codigo_articulo'], 
            'fecha_asignacion' => $_POST['fecha_asignacion']
        ];
        
        $obj->setArticulos(new ModeloArticulosInventario());
        
        $resultado = $obj->ProcesarDatos($datos);
        
        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Modificó asignación ID: " . $datos['id_asignacion']);
            $resultado = array('accion' => 'exito', 'mensaje' => 'Asignación modificada exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                INVALID_ID    => 'La asignación original no fue encontrada.',
                VALIDATION    => 'El nuevo artículo seleccionado no está disponible.',
                DB_CONNECTION => 'Ocurrió un error al conectarse con la base de datos.',
                default       => 'Ocurrió un error inesperado al modificar.'
            };
        }
        echo json_encode($resultado);
    } catch (Exception $e) { 
        logs('Asignaciones', $e->getMessage(), 'Controlador_Modificar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]); 
    }
}

function anular($obj, $id_modulo, $bitacoraObj): void {
    try {
        validar_requeridos(['id_asignacion', 'codigo_articulo']);

        $datos = [
            'accion'          => 'anular', 
            'id_asignacion'   => $_POST['id_asignacion'], 
            'codigo_articulo' => $_POST['codigo_articulo']
        ];
        
        $obj->setArticulos(new ModeloArticulosInventario());
        
        $resultado = $obj->ProcesarDatos($datos);
        
        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Anuló asignación ID: " . $datos['id_asignacion']);
            $resultado = array('accion' => 'exito', 'mensaje' => 'Asignación anulada exitosamente.'); 
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                DB_CONNECTION => 'Ocurrió un error al conectarse con la base de datos.',
                default       => 'Ocurrió un error inesperado al anular la asignación.'
            };
        }
        echo json_encode($resultado);
    } catch (Exception $e) { 
        logs('Asignaciones', $e->getMessage(), 'Controlador_Anular');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]); 
    }
}