<?php

use App\modelo\ModeloRepresentantes;
use App\servicios\GenerarReporte;

// 1. Cargamos las funciones base
require_once __DIR__ . '/Base.php';


// 2. Configuración del módulo (Corregido al ID de Representantes)
$id_modulo = _MD_REPRESENTANTES_;

// 3. Procesar permisos (Retorna el array de permisos)
$permisos = procesarPermisos($id_modulo, $bitacora);

// 4. Lógica de despacho (Router interno)
$nombreClaseModelo = 'App\modelo\ModeloRepresentantes';

if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloRepresentantes();

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitudRepresentantes($objModelo, $id_modulo, $bitacora, $permisos);
} else {
    registrarBitacora($bitacora , $id_modulo, 'Ingreso al Modulo');
    $respuesta = $objModelo->Consultar();
    $registro = $respuesta['datos'] ?? [];
    $variables = ['registro' => $registro, 'permisos' => $permisos];
    cargarVista($pagina, $variables);
}

/**
 * --- FUNCIONES DEL CONTROLADOR ---
 */

function manejarSolicitudRepresentantes($obj, $id_modulo, $bitacoraObj, array $permisos): void
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
                if (!$permisos['ingresar']) throw new Exception('No tienes permisos para consultar representantes.');
                consultar($obj, $permisos);
                break;
            case 'buscar':
                if (!$permisos['modificar']) throw new Exception('No tienes permisos para modificar representantes.');
                buscar($obj);
                break;
            case 'incluir':
                if (!$permisos['registrar']) throw new Exception('No tienes permisos para registrar representantes.');
                incluir($obj, $id_modulo, $bitacoraObj);
                break;
            case 'eliminar':
                if (!$permisos['eliminar']) throw new Exception('No tienes permisos para eliminar representantes.');
                eliminar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'modificar':
                if (!$permisos['modificar']) throw new Exception('No tienes permisos para modificar representantes.');
                modificar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'generar':
                if (!$permisos['reporte']) throw new Exception('No tienes permisos para generar un reporte de los representantes.');
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

/**
 * --- LÓGICA DE ACCIONES ---
 */


function consultar($obj, $permisos): void
{
    $filtro['filtro'] = $_POST['filtro'] ?? '';
    $respuesta = $obj->Consultar($filtro);

    $registro = $respuesta['datos'] ?? [];
    $solo_lista = true;
    include(__DIR__ . '/../vista/Representantes.php');
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
        logs('Representantes', $e->getMessage(), 'Controlador_Buscar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function incluir($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $validaciones = [
            'cedula'   => ['regla' => '/^[0-9]{7,8}$/', 'mensaje' => 'Cédula inválida.'],
            'nombre'   => ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,30}$/', 'mensaje' => 'Nombre inválido.'],
            'apellido' => ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,30}$/', 'mensaje' => 'Apellido inválido.'],
            'telefono' => ['regla' => '/^[0-9]{4}[-]{1}[0-9]{7}$/', 'mensaje' => 'Teléfono inválido.'],
            'direccion' => ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,150}$/', 'mensaje' => 'Direccion invítica.'],
            'nacionalidad' => ['regla'   => '/^[VEP]$/', 'mensaje' => 'Nacionalidad inválida. Solo se permite V, E o P.']
        ];

        validar_datos($validaciones);

        $datos = [
            'nacionalidad' => $_POST['nacionalidad'],
            'cedula'   => $_POST['cedula'],
            'nombre'     => $_POST['nombre'],
            'apellido'   => $_POST['apellido'],
            'telefono' => $_POST['telefono'],
            'direccion' => $_POST['direccion']
        ];
        $datos['accion'] = 'incluir';

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {

            registrarBitacora($bitacoraObj, $id_modulo, "Registró al representante: " . $_POST['cedula'] . ' ' . $_POST['nombre'] . ' ' . $_POST['apellido']);
            $resultado = array('accion' => 'incluir', 'mensaje' => 'Representante registrado exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {

            $resultado['mensaje'] = match ($resultado['codigo']) {
                DUPLICATE_CEDULA => 'Ya existe un representante registrado con esta cédula.',
                DUPLICATE_PHONE  => 'Ya existe un representante registrado con este teléfono.',
                default          => 'Ocurrió un error inesperado en el registro.'
            };
        }
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Representantes', $e->getMessage(), 'Controlador_Incluir');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}
function modificar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $validaciones = [
            'id' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.'],
            'cedula'   => ['regla' => '/^[0-9]{7,8}$/', 'mensaje' => 'Cédula inválida.'],
            'nombre'   => ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,30}$/', 'mensaje' => 'Nombre inválido.'],
            'apellido' => ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,30}$/', 'mensaje' => 'Apellido inválido.'],
            'telefono' => ['regla' => '/^[0-9]{4}[-]{1}[0-9]{7}$/', 'mensaje' => 'Teléfono inválido.'],
            'direccion' => ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,150}$/', 'mensaje' => 'Direccion invítica.'],
            'nacionalidad' => ['regla'   => '/^[VEP]$/', 'mensaje' => 'Nacionalidad inválida. Solo se permite V, E o P.']
        ];

        validar_datos($validaciones);

        $datos = [
            'id' => $_POST['id'],
            'nacionalidad' => $_POST['nacionalidad'],
            'cedula'   => $_POST['cedula'],
            'nombre'     => $_POST['nombre'],
            'apellido'   => $_POST['apellido'],
            'telefono' => $_POST['telefono'],
            'direccion' => $_POST['direccion']
        ];
        $datos['accion'] = 'modificar';

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {

            registrarBitacora($bitacoraObj, $id_modulo, "Modifico al representante: " . $_POST['cedula'] . ' ' . $_POST['nombre'] . ' ' . $_POST['apellido']);
            $resultado = array('accion' => 'modificar', 'mensaje' => 'Representante modificado exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {

            $resultado['mensaje'] = match ($resultado['codigo']) {
                DUPLICATE_CEDULA => 'Ya existe un representante registrado con esta cédula.',
                DUPLICATE_PHONE  => 'Ya existe un representante registrado con este teléfono.',
                default          => 'Ocurrió un error inesperado en la modificacion.'
            };
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
        $validaciones = ['id' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.']];
        validar_datos($validaciones);

        $datos = [
            'id' => $_POST['id'],
            'accion' => 'eliminar'
        ];

        $resultado = $obj->procesarDatos($datos);
        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {

            registrarBitacora($bitacoraObj, $id_modulo, "Elimino al representante: " . $_POST['id']);
            $resultado = array('accion' => 'eliminar', 'mensaje' => 'Representante eliminado exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {

            $resultado['mensaje'] = match ($resultado['codigo']) {
                INVALID_ID => 'El representante no existe.',
                ASSOCIATES  => 'El representante tiene atletas asociados.',
                default          => 'Ocurrió un error inesperado en la eliminacion.'
            };
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
        $validacionesReporte = [];
        $datosFiltro = ['accion' => 'generar'];

        if (!empty($_POST['cedula'])) {
            $validacionesReporte['cedula'] = ['regla' => '/^[0-9]{1,8}$/', 'mensaje' => 'Cédula inválida.'];
            $datosFiltro['cedula'] = $_POST['cedula'];
        }
        if (!empty($_POST['nacionalidad'])) {
            $validacionesReporte['nacionalidad'] = ['regla'   => '/^[VEP]$/', 'mensaje' => 'Nacionalidad inválida. Solo se permite V, E o P.'];
            $datosFiltro['nacionalidad'] = $_POST['nacionalidad'];
        }

        validar_datos($validacionesReporte);

        $respuesta =  $obj->procesarDatos($datosFiltro);
        $datos = $respuesta['datos'];
        if (empty($datos)) {
            echo json_encode(['accion' => 'error', 'mensaje' => 'No se encontraron representantes para hacer el reporte.']);
            exit();
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
