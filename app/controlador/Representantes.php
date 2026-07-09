<?php

use App\modelo\ModeloRepresentantes;
use App\servicios\GenerarReporte;

require_once __DIR__ . '/Base.php';

$id_modulo = _MD_REPRESENTANTES_;
$permisos = procesarPermisos($id_modulo, 'ingresar_representantes');

$nombreClaseModelo = 'App\modelo\ModeloRepresentantes';

if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloRepresentantes();

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitud($objModelo, $id_modulo, $bitacora, $permisos);
} else {
    //registrarBitacora($bitacora, $id_modulo, 'Ingreso al Modulo');
    $respuesta = $objModelo->Consultar();

    $registro = [];
    $error_bd = '';

    if (isset($respuesta['accion']) && $respuesta['accion'] === 'error') {
        $error_bd = ($respuesta['mensaje'] == DB_CONNECTION) ? 'Error al conectar con la base de datos.' : '';
    } else {
        $registro = $respuesta['datos'] ?? [];
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
                if (empty($permisos['ingresar_representantes'])) throw new Exception('No tienes permisos para consultar representantes.');
                consultar($obj, $permisos);
                break;
            case 'buscar':
                if (empty($permisos['modificar_representante'])) throw new Exception('No tienes permisos para modificar representantes.');
                buscar($obj);
                break;
            case 'incluir':
                if (empty($permisos['registrar_representante'])) throw new Exception('No tienes permisos para registrar representantes.');
                incluir($obj, $id_modulo, $bitacoraObj);
                break;
            case 'eliminar':
                if (empty($permisos['eliminar_representante'])) throw new Exception('No tienes permisos para eliminar representantes.');
                eliminar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'modificar':
                if (empty($permisos['modificar_representante'])) throw new Exception('No tienes permisos para modificar representantes.');
                modificar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'generar':
                if (empty($permisos['generar_representante'])) throw new Exception('No tienes permisos para generar un reporte de los representantes.');
                generar($obj, $id_modulo, $bitacoraObj);
                break;
            default:
                throw new Exception('Acción no permitida.');
        }
    } catch (Exception $e) {
        logs('Representantes', $e->getMessage(), 'Controlador_ManejarSolicitud');
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
        include(__DIR__ . '/../vista/Representantes.php');
    } catch (throwable $e) {
        logs('Representantes', $e->getMessage(), 'Controlador_Consultar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function buscar($obj): void
{
    try {
        validar_requeridos(['id']);

        $datos = [
            'id' => $_POST['id'],
            'accion' => 'buscar'
        ];

        $resultado = $obj->procesarDatos($datos);
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Representantes', $e->getMessage(), 'Controlador_Buscar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function incluir($obj, $id_modulo, $bitacoraObj): void
{
    try {
        validar_requeridos(['nacionalidad', 'cedula', 'nombre', 'apellido', 'telefono', 'direccion']);

        $datos = [
            'nacionalidad' => $_POST['nacionalidad'],
            'cedula'       => $_POST['cedula'],
            'nombre'       => $_POST['nombre'],
            'apellido'     => $_POST['apellido'],
            'telefono'     => $_POST['telefono'],
            'direccion'    => $_POST['direccion'],
            'accion'       => 'incluir'
        ];

        $resultado = $obj->procesarDatos($datos);

        $datos_previos = '';
        $datos_nuevos = $resultado['datos_nuevos'] ?? '';

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Registró al representante: " . $_POST['cedula'] . ' ' . $_POST['nombre'] . ' ' . $_POST['apellido'], $datos_previos, $datos_nuevos);
            $resultado = array('accion' => 'incluir', 'mensaje' => 'Representante registrado exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            // Mantenemos el mapeo de errores de integridad de BD
            $resultado['mensaje'] = match ($resultado['codigo']) {
                DUPLICATE_CEDULA => 'Ya existe un representante registrado con esta cédula.',
                DUPLICATE_PHONE  => 'Ya existe un representante registrado con este teléfono.',
                DB_CONNECTION      => 'Ocurrio un error al conectarse con la base de datos.',
                default          => 'Ocurrió un error inesperado en el registro.'
            };
            registrarBitacora($bitacoraObj, $id_modulo, "Fallo al registrar al representante: " . $_POST['cedula'] . ' - ' . $resultado['mensaje'], $datos_previos, $datos_nuevos);
        }
        echo json_encode($resultado);
    } catch (Exception $e) {
        // Las excepciones de expresiones regulares caerán aquí directamente
        logs('Representantes', $e->getMessage(), 'Controlador_Incluir');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function modificar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        validar_requeridos(['id', 'nacionalidad', 'cedula', 'nombre', 'apellido', 'telefono', 'direccion']);

        $datos = [
            'id'           => $_POST['id'],
            'nacionalidad' => $_POST['nacionalidad'],
            'cedula'       => $_POST['cedula'],
            'nombre'       => $_POST['nombre'],
            'apellido'     => $_POST['apellido'],
            'telefono'     => $_POST['telefono'],
            'direccion'    => $_POST['direccion'],
            'accion'       => 'modificar'
        ];

        $consultar_datos_previos = $obj->Buscar($_POST['id']);
        $resultado = $obj->procesarDatos($datos);

        $datos_previos = json_encode($consultar_datos_previos['datos']);
        $datos_nuevos = $resultado['datos_nuevos'] ?? '';

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Modificó al representante: " . $_POST['cedula'] . ' - ' . $_POST['nombre'] . ' ' . $_POST['apellido'], $datos_previos, $datos_nuevos);
            $resultado = array('accion' => 'modificar', 'mensaje' => 'Representante modificado exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                DUPLICATE_CEDULA => 'Ya existe un representante registrado con esta cédula.',
                DUPLICATE_PHONE  => 'Ya existe un representante registrado con este teléfono.',
                DB_CONNECTION      => 'Ocurrio un error al conectarse con la base de datos.',
                default          => 'Ocurrió un error inesperado en la modificacion.'
            };
            registrarBitacora($bitacoraObj, $id_modulo, "Fallo al modificar al representante: " . $_POST['cedula'] . ' - ' . $resultado['mensaje'], $datos_previos, $datos_nuevos);
        }

        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Representantes', $e->getMessage(), 'Controlador');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function eliminar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        validar_requeridos(['id']);

        $datos = [
            'id' => $_POST['id'],
            'accion' => 'eliminar'
        ];
        
        $consultar_datos_previos= $obj->Buscar($_POST['id']);
        $resultado = $obj->procesarDatos($datos);

        $datos_previos = json_encode($consultar_datos_previos['datos']);
        $datos_nuevos = '';

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Eliminó al representante: " .$datos_previos['cedula']. ' - '.$consultar_datos_previos['datos']['nombre'].' '.$consultar_datos_previos['datos']['apellido'] , $datos_previos, $datos_nuevos);
            $resultado = array('accion' => 'eliminar', 'mensaje' => 'Representante eliminado exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                INVALID_ID => 'El representante no existe.',
                ASSOCIATES  => 'El representante tiene atletas asociados.',
                DB_CONNECTION      => 'Ocurrio un error al conectarse con la base de datos.',
                default          => 'Ocurrió un error inesperado en la eliminacion.'
            };
            registrarBitacora($bitacoraObj, $id_modulo, "Fallo al eliminar al representante: " . $_POST['id'] . ' - ' . $resultado['mensaje'], $datos_previos, $datos_nuevos);
        }
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Representantes', $e->getMessage(), 'Controlador_Eliminar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function generar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $datosFiltro = ['accion' => 'generar'];

        if (!empty($_POST['cedula'])) {
            $datosFiltro['cedula'] = $_POST['cedula'];
        }
        if (!empty($_POST['nacionalidad'])) {
            $datosFiltro['nacionalidad'] = $_POST['nacionalidad'];
        }

        $respuesta =  $obj->procesarDatos($datosFiltro);

        if (isset($respuesta['accion']) && $respuesta['accion'] === 'error') {
            $mensajeError = ($respuesta['mensaje'] == DB_CONNECTION) ? 'Error al conectar con la base de datos.' : $respuesta['mensaje'];
            echo json_encode(['accion' => 'error', 'mensaje' => $mensajeError]);
            return;
        }

        $datos = $respuesta['datos'] ?? [];

        if (empty($datos)) {
            echo json_encode(['accion' => 'error', 'mensaje' => 'No se encontraron representantes para hacer el reporte.']);
            return;
        }

        $nombreVista = 'R_Representante';
        $objG = new GenerarReporte();
        $pdf = $objG->generarPDF($nombreVista, $datos, 'Representantes');

        if (isset($pdf['accion']) && $pdf['accion'] === 'reporte') {
            registrarBitacora($bitacoraObj, $id_modulo, "Generó reporte de representantes.");
        }
        echo json_encode($pdf);
    } catch (Exception $e) {
        logs('Representantes', $e->getMessage(), 'Controlador_Generar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}
