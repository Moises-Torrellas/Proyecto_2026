<?php

use App\modelo\ModeloEstadisticas;
use App\modelo\ModeloAtletas;
use App\modelo\ModeloTorneos;
use App\modelo\ModeloParticipaciones;
use App\modelo\ModeloHistorial;
use App\servicios\GenerarReporte;

// 1. Cargamos las funciones base
require_once __DIR__ . '/Base.php';


$id_modulo = _MD_ESTADISTICAS_;

// 3. Procesar permisos (Retorna el array de permisos)
$permisos = procesarPermisos($id_modulo, $bitacora);

// 4. Lógica de despacho (Router interno)
$nombreClaseModelo = 'App\modelo\ModeloEstadisticas';

if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloEstadisticas();

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
        if (!isset($_SESSION['token']) || !hash_equals($_SESSION['token'], $tokenRecibido)) {
            throw new Exception('Error de seguridad: Token inválido o expirado.');
        }

        $accion = isset($_POST['accion']) ? filter_var($_POST['accion'], FILTER_SANITIZE_SPECIAL_CHARS) : '';

        // Seguridad centralizada
        switch ($accion) {
            case 'consultar':
                if (!$permisos['ingresar']) throw new Exception('No tienes permisos para consultar las estadisticas.');
                consultar($obj, $permisos);
                break;
            case 'MultiConsulta':
                if (!$permisos['ingresar']) throw new Exception('No tienes permisos para consultar las estadisticas.');

                $modeloTor = new ModeloTorneos();
                $modeloAtl = new ModeloAtletas();

                $TorRespuesta = $modeloTor->Consultar();
                $AtlRespuesta = $modeloAtl->Consultar();

                echo json_encode([
                    'accion'         => 'MultiConsulta',
                    'torneos' => $TorRespuesta['datos'] ?? [],
                    'atletas'     => $AtlRespuesta['datos'] ?? []
                ]);

                break;
            case 'buscar':
                if (!$permisos['modificar']) throw new Exception('No tienes permisos para modificar las estadisticas.');
                buscar($obj);
                break;
            case 'incluir':
                if (!$permisos['registrar']) throw new Exception('No tienes permisos para registrar las estadisticas.');
                incluir($obj, $id_modulo, $bitacoraObj);
                break;
            case 'modificar':
                if (!$permisos['modificar']) throw new Exception('No tienes permisos para modificar las estadisticas.');
                modificar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'eliminar':
                if (!$permisos['eliminar']) throw new Exception('No tienes permisos para eliminar las estadisticas.');
                eliminar($obj, $id_modulo, $bitacoraObj);
                break;

            default:
                throw new Exception('Acción no permitida.');
        }
    } catch (Exception $e) {
        logs('Estadisticas', $e->getMessage(), 'Controlador_ManejarSolicitud');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function consultar($obj, $permisos): void
{
    $filtro['filtro'] = $_POST['filtro'] ?? '';
    $respuesta = $obj->Consultar($filtro);

    $registro = $respuesta['datos'] ?? [];
    $solo_lista = true;


    include(__DIR__ . '/../vista/Estadisticas.php');
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
        logs('Estadisticas', $e->getMessage(), 'Controlador_Buscar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function incluir($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $validaciones = [
            'torneo'         => ['regla' => '/^[1-9][0-9]*$/', 'mensaje' => 'Seleccione un torneo válido.'],
            'atleta'         => ['regla' => '/^[1-9][0-9]*$/', 'mensaje' => 'Seleccione un atleta válido.'],
            'goles'          => ['regla' => '/^[0-9]{1,3}$/', 'mensaje' => 'Goles: Ingrese una cantidad válida (0-999).'],
            'asistencias'    => ['regla' => '/^[0-9]{1,3}$/', 'mensaje' => 'Asistencias: Ingrese una cantidad válida (0-999).'],
            'penalizaciones' => ['regla' => '/^[0-9]{1,3}$/', 'mensaje' => 'Penalizaciones: Ingrese una cantidad válida (0-999).'],
            'goles_c'        => ['regla' => '/^[0-9]{1,3}$/', 'mensaje' => 'Goles en contra: Ingrese una cantidad válida (0-999).'],
            'partido'        => ['regla' => '/^([1-9][0-9]{0,2})$/', 'mensaje' => 'Debe ser al menos 1 partido.'],
            'average'        => ['regla' => '/^[0-9]+(\.[0-9]{1,2})?$/', 'mensaje' => 'Formato decimal inválido (ej: 1.50).']
        ];

        validar_datos($validaciones);

        $datos = [
            'torneo'         => $_POST['torneo'],
            'atleta'         => $_POST['atleta'],
            'goles'          => $_POST['goles'],
            'asistencias'    => $_POST['asistencias'],
            'penalizaciones' => $_POST['penalizaciones'],
            'goles_c'        => $_POST['goles_c'],
            'partido'        => $_POST['partido'],
            'average'        => $_POST['average']
        ];
        $datos['accion'] = 'incluir';

        $obj->setParticipaciones(new ModeloParticipaciones());

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {

            registrarBitacora($bitacoraObj, $id_modulo, "Registro estadisticas: ");
            $resultado = array('accion' => 'incluir', 'mensaje' => 'Estadisticas registradas exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {

            $resultado['mensaje'] = match ($resultado['codigo']) {
                INVALID_ID      => 'El torneo ingresado no existe en los registros del club.',
                INVALID_ID.'1'  => 'El Atleta ingresado no existe en los registros del club.',
                EMPTY_SELECTION => 'El tleta seleccionado no participo en el torneo seleccionado.',
                DUPLICATE       => 'Ya este atleta tiene registrada unas estadisticas para este torneo.',
                DB_CONNECTION   => 'Ocurrio un error al conectarse con la base de datos.',
                default         => 'Ocurrió un error inesperado en el registro de las estadisticas.'
            };
        }
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Estadisticas', $e->getMessage(), 'Controlador_Incluir');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function modificar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $validaciones = [
            'id'             => ['regla' => '/^[0-9]*$/', 'mensaje' => 'Id inválido.'],
            'torneo'         => ['regla' => '/^[1-9][0-9]*$/', 'mensaje' => 'Seleccione un torneo válido.'],
            'atleta'         => ['regla' => '/^[1-9][0-9]*$/', 'mensaje' => 'Seleccione un atleta válido.'],
            'goles'          => ['regla' => '/^[0-9]{1,3}$/', 'mensaje' => 'Goles: Ingrese una cantidad válida (0-999).'],
            'asistencias'    => ['regla' => '/^[0-9]{1,3}$/', 'mensaje' => 'Asistencias: Ingrese una cantidad válida (0-999).'],
            'penalizaciones' => ['regla' => '/^[0-9]{1,3}$/', 'mensaje' => 'Penalizaciones: Ingrese una cantidad válida (0-999).'],
            'goles_c'        => ['regla' => '/^[0-9]{1,3}$/', 'mensaje' => 'Goles en contra: Ingrese una cantidad válida (0-999).'],
            'partido'        => ['regla' => '/^([1-9][0-9]{0,2})$/', 'mensaje' => 'Debe ser al menos 1 partido.'],
            'average'        => ['regla' => '/^[0-9]+(\.[0-9]{1,2})?$/', 'mensaje' => 'Formato decimal inválido (ej: 1.50).']
        ];

        validar_datos($validaciones);

        $datos = [
            'id'             => $_POST['id'],
            'torneo'         => $_POST['torneo'],
            'atleta'         => $_POST['atleta'],
            'goles'          => $_POST['goles'],
            'asistencias'    => $_POST['asistencias'],
            'penalizaciones' => $_POST['penalizaciones'],
            'goles_c'        => $_POST['goles_c'],
            'partido'        => $_POST['partido'],
            'average'        => $_POST['average']
        ];
        $datos['accion'] = 'modificar';

        $obj->setParticipaciones(new ModeloParticipaciones());
        $obj->setHistorial(new ModeloHistorial());

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {

            registrarBitacora($bitacoraObj, $id_modulo, "Modifico estadisticas: ");
            $resultado = array('accion' => 'modificar', 'mensaje' => 'Estadisticas modificadas exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {

            $resultado['mensaje'] = match ($resultado['codigo']) {
                INVALID_ID      => 'El torneo ingresado no existe en los registros del club.',
                INVALID_ID.'1'  => 'El Atleta ingresado no existe en los registros del club.',
                INVALID_ID.'2'  => 'Las estadisticas que intenta modificar no existe en los registros del club.',
                EMPTY_SELECTION => 'El atleta seleccionado no participo en el torneo seleccionado.',
                DUPLICATE       => 'Ya este atleta tiene registrada unas estadisticas para este torneo.',
                DB_CONNECTION   => 'Ocurrio un error al conectarse con la base de datos.',
                default         => 'Ocurrió un error inesperado en la modificacion de las estadisticas.'
            };
        }
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Estadisticas', $e->getMessage(), 'Controlador_Modificar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function eliminar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $validaciones = [
            'id'             => ['regla' => '/^[0-9]*$/', 'mensaje' => 'Id inválido.'],
        ];

        validar_datos($validaciones);

        $datos = [
            'id'             => $_POST['id'],
        ];
        $datos['accion'] = 'eliminar';
        $obj->setHistorial(new ModeloHistorial());

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {

            registrarBitacora($bitacoraObj, $id_modulo, "Elimino estadisticas: ");
            $resultado = array('accion' => 'eliminar', 'mensaje' => 'Estadisticas eliminadas exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {

            $resultado['mensaje'] = match ($resultado['codigo']) {
                INVALID_ID      => 'Las estadisticas que intenta modificar no existe en los registros del club.',
                ASSOCIATES      => 'No puede eliminar una estadistica que ya este asociado al historial de un atleta.',
                DB_CONNECTION   => 'Ocurrio un error al conectarse con la base de datos.',
                default         => 'Ocurrió un error inesperado en la eliminacion de las estadisticas.'
            };
        }
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Estadisticas', $e->getMessage(), 'Controlador_Modificar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}
