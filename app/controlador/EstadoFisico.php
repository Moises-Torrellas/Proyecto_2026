<?php

use App\modelo\ModeloEstadoFisico;
use App\servicios\GenerarReporte;

require_once __DIR__ . '/Base.php';

$id_modulo = _MD_ESTADO_FISICO_;

$permisos = procesarPermisos($id_modulo, 'ingresar_estfisico');

$nombreClaseModelo = 'App\modelo\ModeloEstadoFisico';

if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloEstadoFisico();

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitud($objModelo, $id_modulo, $bitacora ?? null, $permisos);
} else {
    // Texto de la bitácora estandarizado
    registrarBitacora($bitacora, $id_modulo, 'Ingreso al Modulo');
    $respuesta = $objModelo->Consultar();
    $registro = $respuesta['datos'] ?? [];

    $error_bd = '';
    if (isset($respuesta['accion']) && $respuesta['accion'] === 'error') {
        $error_bd = ($respuesta['mensaje'] == DB_CONNECTION) ? 'Error al conectar con la base de datos.' : '';
    }

    $variables = ['registro' => $registro, 'permisos' => $permisos, 'error_bd' => $error_bd];
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

        switch ($accion) {
            case 'consultar':
                if (empty($permisos['ingresar_estfisico'])) throw new Exception('No tienes permisos para ingresar a consultar el estado físico.');
                consultar($obj, $permisos);
                break;
            case 'buscar':
                if (empty($permisos['modificar_estfisico'])) throw new Exception('No tienes permisos para modificar el estado físico.');
                buscar($obj, $permisos);
                break;
            case 'incluir':
                if (empty($permisos['registrar_estfisico'])) throw new Exception('No tienes permisos para registrar el estado físico.');
                incluir($obj, $id_modulo, $bitacoraObj);
                break;
            case 'eliminar':
                if (empty($permisos['eliminar_estfisico'])) throw new Exception('No tienes permisos para eliminar el estado físico.');
                eliminar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'modificar':
                if (empty($permisos['modificar_estfisico'])) throw new Exception('No tienes permisos para modificar el estado físico.');
                modificar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'generar':
                if (empty($permisos['generar_estfisico'])) throw new Exception('No tienes permisos para generar reportes.');
                generar($obj, $id_modulo, $bitacoraObj);
                break;    
            default:
                throw new Exception('Acción no permitida.');
        }
    } catch (Exception $e) {
        logs('EstadoFisico', $e->getMessage(), 'Controlador_ManejarSolicitud');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function consultar($obj, $permisos): void
{
    try {
        $filtro['filtro'] = $_POST['filtro'] ?? '';
        $respuesta = $obj->Consultar($filtro);

        if (isset($respuesta['accion']) && $respuesta['accion'] === 'error') {
            $mensajeError = ($respuesta['mensaje'] == DB_CONNECTION) ? 'Error al conectar con la base de datos.' : $respuesta['mensaje'];
            echo json_encode(['accion' => 'error', 'mensaje' => $mensajeError]);
            return;
        }

        $registro = $respuesta['datos'] ?? [];
        $solo_lista = true;
        include(__DIR__ . '/../vista/EstadoFisico.php');
    } catch (throwable $e) {
        logs('EstadoFisico', $e->getMessage(), 'Controlador_Consultar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function buscar($obj, $permisos): void
{
    try {
        $validaciones = ['id_estado' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.']];
        validar_datos($validaciones);

        $datos = [
            'id_estado' => $_POST['id_estado'],
            'accion' => 'buscar'
        ];

        $resultado = $obj->procesarDatos($datos);
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('EstadoFisico', $e->getMessage(), 'Controlador_Buscar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function incluir($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $validaciones = [
            'nombre'   => ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,30}$/', 'mensaje' => 'Nombre de Estado Físico inválido.'],
            'nivel_estado' => ['regla' => '/^[0-9]{1}$/', 'mensaje' => 'Nivel inválido. Debe ser un número.'],
        ];

        validar_datos($validaciones);

        if ($_POST['nivel_estado'] > 3 || $_POST['nivel_estado'] < 1) {
            throw new Exception('No es un nivel válido.');
        }

        $datos = [
            'nombre'       => $_POST['nombre'],
            'nivel_estado' => $_POST['nivel_estado'],
            'accion'       => 'incluir'
        ];

        $resultado = $obj->procesarDatos($datos);
        
        $datos_previos = '';
        $datos_nuevos = $resultado['datos_nuevos'] ?? '';

        if (isset($resultado['accion']) && $resultado['accion'] === 'incluir') {
            registrarBitacora($bitacoraObj, $id_modulo, "Registró el estado físico: " . $_POST['nombre'], $datos_previos, $datos_nuevos);
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            registrarBitacora($bitacoraObj, $id_modulo, "Falló al registrar el estado físico: " . $_POST['nombre'] . " - " . ($resultado['mensaje'] ?? ''), $datos_previos, $datos_nuevos);
        }

        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('EstadoFisico', $e->getMessage(), 'Controlador_Incluir');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function modificar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $validaciones = [
            'id_estado'    => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.'],
            'nombre'       => ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,30}$/', 'mensaje' => 'Nombre de estado físico inválido.'],
            'nivel_estado' => ['regla' => '/^[0-9]{1}$/', 'mensaje' => 'Nivel inválido. Debe ser un número.'],
        ];

        validar_datos($validaciones);

        if ($_POST['nivel_estado'] > 3 || $_POST['nivel_estado'] < 1) {
            throw new Exception('No es un nivel válido.');
        }

        $datos = [
            'id_estado'    => $_POST['id_estado'],
            'nombre'       => $_POST['nombre'],
            'nivel_estado' => $_POST['nivel_estado'],
            'accion'       => 'modificar'
        ];

        // BITÁCORA: Traer los datos previos antes de modificar
        $consultar_datos_previos = $obj->Buscar($_POST['id_estado']);
        $datos_previos = json_encode($consultar_datos_previos['datos'][0] ?? []);

        $resultado = $obj->procesarDatos($datos);
        $datos_nuevos = $resultado['datos_nuevos'] ?? '';

        if (isset($resultado['accion']) && $resultado['accion'] === 'modificar') {
            registrarBitacora($bitacoraObj, $id_modulo, "Modificó el estado físico: " . $_POST['nombre'], $datos_previos, $datos_nuevos);
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            registrarBitacora($bitacoraObj, $id_modulo, "Falló al modificar el estado físico: " . $_POST['nombre'] . " - " . ($resultado['mensaje'] ?? ''), $datos_previos, $datos_nuevos);
        }

        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('EstadoFisico', $e->getMessage(), 'Controlador_Modificar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function eliminar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $validaciones = ['id_estado' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.']];
        validar_datos($validaciones);

        $datos = [
            'id_estado' => $_POST['id_estado'],
            'accion' => 'eliminar'
        ];

        // BITÁCORA: Tomar la foto antes de borrar
        $consultar_datos_previos = $obj->Buscar($_POST['id_estado']);
        $datos_previos = json_encode($consultar_datos_previos['datos'][0] ?? []);
        $datos_nuevos = '';

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'eliminar') {
            registrarBitacora($bitacoraObj, $id_modulo, "Eliminó el Estado Físico ID: " . $_POST['id_estado'], $datos_previos, $datos_nuevos);
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            registrarBitacora($bitacoraObj, $id_modulo, "Falló al eliminar el Estado Físico ID: " . $_POST['id_estado'] . " - " . ($resultado['mensaje'] ?? ''), $datos_previos, $datos_nuevos);
        }
        
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('EstadoFisico', $e->getMessage(), 'Controlador_Eliminar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }  
}

function generar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $filtros = [];
        if (!empty($_POST['nivel_estado'])) {
            $filtros['nivel_estado'] = filter_var($_POST['nivel_estado'], FILTER_SANITIZE_NUMBER_INT);
        }

        $respuesta = $obj->Consultar($filtros);
        $datos = $respuesta['datos'] ?? [];

        if (empty($datos)) {
            echo json_encode(['accion' => 'error', 'mensaje' => 'No se encontraron registros para generar el reporte.']);
            exit();
        }

        $nombreVista = 'R_EstadoFisico';
        $objG = new GenerarReporte();
        $pdf = $objG->generarPDF($nombreVista, $datos, 'Estado Fisico');

        if (isset($pdf['accion']) && $pdf['accion'] === 'reporte') {
            registrarBitacora($bitacoraObj, $id_modulo, "Generó reporte del módulo Estados Físicos.");
        }
        
        echo json_encode($pdf);
    } catch (Exception $e) {
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}