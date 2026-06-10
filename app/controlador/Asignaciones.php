<?php
use App\modelo\ModeloAsignaciones;
use App\modelo\ModeloAtletas;
use App\modelo\ModeloEquipamientos;

require_once __DIR__ . '/Base.php';

$id_modulo = _MD_ASIGNACIONES_; 
$permisos = procesarPermisos($id_modulo, $bitacora ?? null);

$nombreClaseModelo = 'App\modelo\ModeloAsignaciones';
if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloAsignaciones();

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitudAsignacion($objModelo, $id_modulo, $bitacora ?? null, $permisos);
} else {
    registrarBitacora($bitacora ?? null, $id_modulo, 'Ingreso al Modulo de Asignaciones');
    $respuesta = $objModelo->ConsultarAgrupado(); 
    $registro = $respuesta['datos'] ?? [];
    
    $variables = ['registro' => $registro, 'permisos' => $permisos];
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
    $respuesta = $obj->ConsultarAgrupado();
    $registro = $respuesta['datos'] ?? []; 
    $solo_lista = true;
    include (__DIR__.'/../vista/Asignaciones.php');
}

function MultiConsulta(): void {
    try {
        $modeloAtletas = new ModeloAtletas();
        $modeloEquip = new ModeloEquipamientos();

        $respAtletas = $modeloAtletas->Consultar(); 
        $respEquip = $modeloEquip->ConsultarEquiposLibres();

        echo json_encode([
            'accion'  => 'MultiConsulta',
            'atletas' => $respAtletas['datos'] ?? [],
            'equipos' => $respEquip['datos'] ?? []
        ]);
    } catch (Exception $e) {
        echo json_encode(['accion' => 'error', 'codigo' => defined('_ERR_BD_') ? _ERR_BD_ : 'ERR_BD']);
    }
}

function incluir($obj, $id_modulo, $bitacoraObj): void {
    try {
        $validaciones = [
            'id_atleta'        => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Atleta inválido.'],
            'id_equipamiento'  => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Equipamiento inválido.'],
            'fecha_asignacion' => ['regla' => '/^\d{4}-\d{2}-\d{2}$/', 'mensaje' => 'Formato de fecha inválido.']
        ];
        validar_datos($validaciones);

        $datos = ['accion' => 'incluir', 'id_atleta' => $_POST['id_atleta'], 'id_equipamiento' => $_POST['id_equipamiento'], 'fecha_asignacion' => $_POST['fecha_asignacion']];
        
        $obj->setEquipamientos(new ModeloEquipamientos());
        
        $resultado = $obj->ProcesarDatos($datos);
        
        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Asignó el equipo ID: " . $datos['id_equipamiento']);
            $resultado = array('accion' => 'incluir', 'mensaje' => 'Asignación procesada exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                defined('_ERR_ESTATUS_') ? _ERR_ESTATUS_ : 'ERR_ESTATUS' => 'El equipo seleccionado ya no está disponible.',
                defined('_ERR_BD_') ? _ERR_BD_ : 'ERR_BD' => 'Ocurrió un error al conectarse con la base de datos.',
                default => 'Ocurrió un error inesperado al procesar la asignación.'
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
        $validaciones = [
            'id_asignacion'    => ['regla' => '/^[0-9]+$/', 'mensaje' => 'ID inválido.'],
            'id_atleta'        => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Atleta inválido.'],
            'id_equipamiento'  => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Equipamiento inválido.'],
            'fecha_asignacion' => ['regla' => '/^\d{4}-\d{2}-\d{2}$/', 'mensaje' => 'Formato de fecha inválido.']
        ];
        validar_datos($validaciones);

        $datos = ['accion' => 'modificar', 'id_asignacion' => $_POST['id_asignacion'], 'id_atleta' => $_POST['id_atleta'], 'id_equipamiento' => $_POST['id_equipamiento'], 'fecha_asignacion' => $_POST['fecha_asignacion']];
        
        $obj->setEquipamientos(new ModeloEquipamientos());
        
        $resultado = $obj->ProcesarDatos($datos);
        
        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Modificó asignación ID: " . $datos['id_asignacion']);
            $resultado = array('accion' => 'modificar', 'mensaje' => 'Asignación modificada exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                defined('_ERR_NO_EXISTE_') ? _ERR_NO_EXISTE_ : 'ERR_NO_EXISTE' => 'La asignación original no fue encontrada.',
                defined('_ERR_ESTATUS_') ? _ERR_ESTATUS_ : 'ERR_ESTATUS' => 'El nuevo equipo seleccionado no está disponible.',
                defined('_ERR_BD_') ? _ERR_BD_ : 'ERR_BD' => 'Ocurrió un error al conectarse con la base de datos.',
                default => 'Ocurrió un error inesperado al modificar.'
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
        $validaciones = ['id_asignacion' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Inválido.'], 'id_equipamiento' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Inválido.']];
        validar_datos($validaciones);
        
        $motivo = trim(filter_var($_POST['motivo_anulacion'] ?? ($_POST['motivo'] ?? ''), FILTER_SANITIZE_SPECIAL_CHARS));
        if (strlen($motivo) < 5) throw new Exception('El motivo debe tener al menos 5 letras.');

        $datos = ['accion' => 'anular', 'id_asignacion' => $_POST['id_asignacion'], 'id_equipamiento' => $_POST['id_equipamiento'], 'motivo_anulacion' => $motivo];
        
        $obj->setEquipamientos(new ModeloEquipamientos());
        
        $resultado = $obj->ProcesarDatos($datos);
        
        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Anuló asignación ID: " . $datos['id_asignacion']);
            $resultado = array('accion' => 'eliminar', 'mensaje' => 'Asignación anulada exitosamente.'); // Se mantiene "eliminar" para que tu JS dispare la notificación verde.
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                defined('_ERR_BD_') ? _ERR_BD_ : 'ERR_BD' => 'Ocurrió un error al conectarse con la base de datos.',
                default => 'Ocurrió un error inesperado al anular la asignación.'
            };
        }
        echo json_encode($resultado);
    } catch (Exception $e) { 
        logs('Asignaciones', $e->getMessage(), 'Controlador_Anular');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]); 
    }
}