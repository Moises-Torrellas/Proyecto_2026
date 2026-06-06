<?php

// 1. IMPORTAMOS LOS MODELOS QUE VAMOS A NECESITAR
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
    cargarVista($pagina);
}

function manejarSolicitudAsignacion($obj, $id_modulo, $bitacoraObj, array $permisos): void
{
    try {
        $tokenRecibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!isset($_SESSION['token']) || !hash_equals($_SESSION['token'], $tokenRecibido)) {
            throw new Exception('Error de seguridad.');
        }

        $accion = filter_var($_POST['accion'], FILTER_SANITIZE_SPECIAL_CHARS);

        switch ($accion) {
            case 'consultar':
                if (empty($permisos['ingresar'])) throw new Exception('No tiene permisos para consultar asignaciones.');
                echo json_encode($obj->ProcesarDatos(['accion' => 'consultar']));
                break;
                
            case 'MultiConsulta':
                if (empty($permisos['ingresar'])) throw new Exception('No tiene permisos para consultar datos.');
                MultiConsulta();
                break;
                
            case 'incluir':
                if (empty($permisos['registrar'])) throw new Exception('No tiene permisos para registrar asignaciones.');
                
                if (empty($_POST['id_atleta']) || empty($_POST['id_equipamiento']) || empty($_POST['fecha_asignacion'])) {
                    throw new Exception('El formulario contiene campos vacíos.');
                }
                
                $datos = [
                    'accion'           => 'incluir',
                    'id_atleta'        => $_POST['id_atleta'],
                    'id_equipamiento'  => $_POST['id_equipamiento'],
                    'fecha_asignacion' => $_POST['fecha_asignacion']
                ];
                
                $resultado = $obj->ProcesarDatos($datos);
                if ($resultado['accion'] === 'exito') registrarBitacora($bitacoraObj, $id_modulo, "Asignó el equipo ID: " . $_POST['id_equipamiento'] . " al atleta ID: " . $_POST['id_atleta']);
                
                echo json_encode($resultado);
                break;
                
            case 'modificar':
                if (empty($permisos['modificar'])) throw new Exception('No tiene permisos para modificar.');
                
                if (empty($_POST['id_asignacion']) || empty($_POST['id_atleta']) || empty($_POST['id_equipamiento']) || empty($_POST['fecha_asignacion'])) {
                    throw new Exception('Faltan datos para procesar la modificación.');
                }
                
                $datos = [
                    'accion'           => 'modificar',
                    'id_asignacion'    => $_POST['id_asignacion'],
                    'id_atleta'        => $_POST['id_atleta'],
                    'id_equipamiento'  => $_POST['id_equipamiento'],
                    'fecha_asignacion' => $_POST['fecha_asignacion']
                ];
                
                $resultado = $obj->ProcesarDatos($datos);
                if ($resultado['accion'] === 'exito') registrarBitacora($bitacoraObj, $id_modulo, "Modificó asignación ID: " . $_POST['id_asignacion']);
                
                echo json_encode($resultado);
                break;
                
            case 'anular':
                if (empty($permisos['eliminar'])) throw new Exception('No tiene permisos para anular.');
                
                $motivo = trim(filter_var($_POST['motivo'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS));
                if (empty($motivo) || strlen($motivo) < 5) {
                    throw new Exception('El motivo no es válido. Escriba al menos 5 caracteres.');
                }
                
                $datos = [
                    'accion' => 'anular',
                    'id_asignacion' => $_POST['id_asignacion'],
                    'id_equipamiento' => $_POST['id_equipamiento']
                ];

                $resultado = $obj->ProcesarDatos($datos);
                if ($resultado['accion'] === 'exito') {
                    registrarBitacora($bitacoraObj, $id_modulo, "Anuló asignación ID: " . $_POST['id_asignacion'] . ". Motivo: " . $motivo);
                }
                
                echo json_encode($resultado);
                break;
                
            default:
                throw new Exception('Acción no permitida.');
        }
    } catch (Exception $e) {
        logs('Asignaciones', $e->getMessage(), 'Controlador_ManejarSolicitud');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

// FUNCIÓN PARA LLENAR LOS COMBOS USANDO LOS OTROS MODELOS
function MultiConsulta(): void 
{
    try {
        $modeloAtletas = new ModeloAtletas();
        $modeloEquip = new ModeloEquipamientos();

        $respAtletas = $modeloAtletas->Consultar(); 
        $respEquip = $modeloEquip->ProcesarDatos(['accion' => 'consultar']);

        echo json_encode([
            'accion'  => 'MultiConsulta',
            'atletas' => $respAtletas['datos'] ?? [],
            'equipos' => $respEquip['datos'] ?? []
        ]);
    } catch (Exception $e) {
        logs('Asignaciones', $e->getMessage(), 'Controlador_MultiConsulta');
        echo json_encode(['accion' => 'error', 'mensaje' => 'Error al inicializar los catálogos del módulo.']);
    }
}