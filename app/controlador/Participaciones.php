<?php

use App\modelo\ModeloParticipaciones;
use App\servicios\GenerarReporte;

use App\modelo\ModeloEquipos;
use App\modelo\ModeloTorneos;

//Cargamos las funciones base para los controladores
require_once __DIR__ . '/Base.php';

// Configuración del id del módulo
$id_modulo = _MD_PARTICIPACIONES_;

$permisos = procesarPermisos($id_modulo, $bitacora);

$nombreClaseModelo = 'App\modelo\ModeloPosiciones';
if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloParticipaciones();

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitud($objModelo, $id_modulo, $bitacora, $permisos);
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
        if (!isset($_SESSION['token']) || !hash_equals($_SESSION['token'], $tokenRecibido)) throw new Exception('Error de seguridad.');

        $accion = filter_var($_POST['accion'], FILTER_SANITIZE_SPECIAL_CHARS);

        switch ($accion) {
            case 'consultar':
                if (empty($permisos['ingresar'])) throw new Exception('no tienes acceso al modulo.');
                consultar($obj, $permisos);
                break;
            case 'MultiConsulta':
                if (empty($permisos['ingresar'])) throw new Exception('no tienes acceso al modulo.');
                MultiConsulta();
                break;
            case 'incluir':
                if (empty($permisos['registrar'])) throw new Exception('No tienes permiso para realizar esta acción.');
                incluir($obj, $id_modulo, $bitacoraObj);
                break;
            case 'buscar':
                if (empty($permisos['modificar'])) throw new Exception('No tienes permiso para realizar esta acción.');
                buscar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'modificar':
                if (empty($permisos['modificar'])) throw new Exception('No tienes permiso para realizar esta acción.');
                modificar($obj, $id_modulo, $bitacoraObj);
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
    $respuesta = $obj->Consultar();
    $registro = $respuesta['datos'] ?? []; 
    $solo_lista = true;
    include (__DIR__.'/../vista/Participaciones.php');
}

function MultiConsulta(): void {
    try {
        $torneo = new ModeloTorneos();
        $equipo = new ModeloEquipos(); 

        $respTorneo = $torneo->Consultar(); 
        $respEquipo = $equipo->Consultar(); 

        echo json_encode([
            'accion'       => 'MultiConsulta',
            'torneo' => $respTorneo['datos'] ?? [],
            'equipo'      => $respEquipo['datos'] ?? []
        ]);
    } catch (Exception $e) {
        echo json_encode(['accion' => 'error', 'mensaje' => 'Error al cargar listas.']);
    }
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

        $resultado = $obj->ProcesarDatos($datos);
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Participaciones', $e->getMessage(), 'Controlador_Buscar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function incluir($obj, $id_modulo, $bitacoraObj) : void {
    try {
        // Validamos que los IDs de torneo y equipo sean números válidos
        $validaciones = [
            'torneo' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'El ID del torneo es inválido.'],
            'equipo' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'El ID del equipo es inválido.'],
        ];

        validar_datos($validaciones);

        $datos['accion'] = 'incluir';
        
        $datos['torneo'] = $_POST['torneo'];
        $datos['equipo'] = $_POST['equipo'];

        $resultado = $obj->ProcesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Registro una participacion");
            $resultado = array('accion' => 'incluir', 'mensaje' => 'Participacion registrada exitosamente.');
            
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                INVALID_ID         => 'El torneo seleccionado no existe.',
                INVALID_ID . '0'   => 'El equipo seleccionado no existe.',
                DUPLICATE          => 'Este equipo ya se encuentra inscrito en el torneo.',
                DB_CONNECTION      => 'Ocurrió un error al conectarse con la base de datos.',
                default            => 'Ocurrió un error inesperado al inscribir el equipo.'
            };
        }
        
        echo json_encode($resultado);
        
    } catch (Exception $e) {
        logs('Participaciones', $e->getMessage(), 'Controlador_Incluir');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}