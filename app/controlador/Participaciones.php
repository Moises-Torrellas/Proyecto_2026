<?php

use App\modelo\ModeloParticipaciones;
use App\servicios\GenerarReporte;

use App\modelo\ModeloEquipos;
use App\modelo\ModeloTorneos;

// Cargamos las funciones base para los controladores
require_once __DIR__ . '/Base.php';

// Configuración del id del módulo
$id_modulo = _MD_PARTICIPACIONES_;

$permisos = procesarPermisos($id_modulo, '');

$nombreClaseModelo = 'App\modelo\ModeloPosiciones';
if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloParticipaciones();
$pagina = 'Participaciones'; // Se define para la carga de vista

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
                if (empty($permisos['ingresar_partici'])) throw new Exception('no tienes acceso al modulo.');
                consultar($obj, $permisos);
                break;
            case 'MultiConsulta':
                if (empty($permisos['ingresar_partici'])) throw new Exception('no tienes acceso al modulo.');
                MultiConsulta();
                break;
            case 'incluir':
                if (empty($permisos['registrar_partici'])) throw new Exception('No tienes permiso para realizar esta acción.');
                incluir($obj, $id_modulo, $bitacoraObj);
                break;
            case 'buscar':
                if (empty($permisos['modificar_partici'])) throw new Exception('No tienes permiso para realizar esta acción.');
                buscar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'modificar':
                if (empty($permisos['modificar_partici'])) throw new Exception('No tienes permiso para realizar esta acción.');
                modificar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'eliminar':
                if (empty($permisos['eliminar_partici'])) throw new Exception('No tienes permiso para realizar esta acción.');
                eliminar($obj, $id_modulo, $bitacoraObj);
                break;
            
            default:
                throw new Exception('Acción no permitida.');
        }
    } catch (Exception $e) {
        logs('Participaciones', $e->getMessage(), 'Controlador_ManejarSolicitud'); // Corregido de Devoluciones a Participaciones
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

        $respTorneo = $torneo->Consultar(['estatus' => 1]); 
        $respEquipo = $equipo->ConsultarEquipos(); 

        echo json_encode([
            'accion' => 'MultiConsulta',
            'torneo' => $respTorneo['datos'] ?? [],
            'equipo' => $respEquipo['datos'] ?? []
        ]);
    } catch (Exception $e) {
        echo json_encode(['accion' => 'error', 'mensaje' => 'Error al cargar listas.']);
    }
}

function buscar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        // Ajustado a codigo_participacion
        $validaciones = ['codigo_participacion' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Código inválido.']];
        validar_datos($validaciones);

        $datos = [
            'codigo_participacion' => $_POST['codigo_participacion'],
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
            'codigo_torneo' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'El código del torneo es inválido.'],
            'codigo_equipo' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'El código del equipo es inválido.'],
        ];

        validar_datos($validaciones);

        $datos['accion'] = 'incluir';
        
        $datos['codigo_torneo'] = $_POST['codigo_torneo'];
        $datos['codigo_equipo'] = $_POST['codigo_equipo'];

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

function modificar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $validaciones = [
            'codigo_participacion' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Código inválido.'],
            'codigo_torneo'        => ['regla' => '/^[0-9]+$/', 'mensaje' => 'El código del torneo es inválido.'],
            'codigo_equipo'        => ['regla' => '/^[0-9]+$/', 'mensaje' => 'El código del equipo es inválido.']
        ];

        validar_datos($validaciones);

        $datos = [
            'codigo_participacion' => $_POST['codigo_participacion'],
            'codigo_torneo'        => $_POST['codigo_torneo'],
            'codigo_equipo'        => $_POST['codigo_equipo']
        ];
        $datos['accion'] = 'modificar';

        $resultado = $obj->ProcesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Modifico una participacion");
            $resultado = array('accion' => 'modificar', 'mensaje' => 'Participación modificada exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                INVALID_ID . '2'   => 'La participacion que intenta editar no existe.',
                INVALID_ID         => 'El torneo seleccionado no existe.',
                INVALID_ID . '0'   => 'El equipo seleccionado no existe.',
                DUPLICATE          => 'Este equipo ya se encuentra inscrito en el torneo.',
                DB_CONNECTION      => 'Ocurrió un error al conectarse con la base de datos.',
                'STATUS_ERROR'     => 'No se puede modificar ni eliminar una participacion de un torneo finalizado o en curso.',
                default            => 'Ocurrió un error inesperado en la modificación.'
            };
        }

        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Participaciones', $e->getMessage(), 'Controlador_Modificar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function eliminar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $validaciones = [
            'codigo_participacion' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Código inválido.']
        ];

        validar_datos($validaciones);

        $datos = [
            'codigo_participacion' => $_POST['codigo_participacion'],
            'accion' => 'eliminar'
        ];

        $resultado = $obj->ProcesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Elimino una participacion");
            $resultado = array('accion' => 'eliminar', 'mensaje' => 'Participación eliminada exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                INVALID_ID         => 'La participacion que intenta eliminar no existe.',
                'STATUS_ERROR'     => 'No se puede modificar ni eliminar una participacion de un torneo finalizado o en curso.',
                default            => 'Ocurrió un error inesperado en la eliminacion.'
            };
        }

        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Participaciones', $e->getMessage(), 'Controlador_Eliminar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}