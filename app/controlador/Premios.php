<?php

use App\modelo\ModeloPremios;
use App\servicios\GenerarReporte;

// 1. Cargamos las funciones base
require_once __DIR__ . '/Base.php';

// 2. Configuración del módulo
$id_modulo = _MD_PREMIOS_;

// 3. Procesar permisos (Retorna el array de permisos)
$permisos = procesarPermisos($id_modulo, '');

// 4. Lógica de despacho (Router interno)
$nombreClaseModelo = 'App\modelo\ModeloPremios';

if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloPremios();
$pagina = 'Premios'; 

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitudPremios($objModelo, $id_modulo, $bitacora, $permisos);
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

function manejarSolicitudPremios($obj, $id_modulo, $bitacoraObj, array $permisos): void
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
                if (empty($permisos['ingresar_premio'])) throw new Exception('No tienes permisos para consultar premios.');
                consultar($obj, $permisos);
                break;
            case 'buscar':
                if (empty($permisos['modificar_premio'])) throw new Exception('No tienes permisos para modificar premios.');
                buscar($obj);
                break;
            case 'incluir':
                if (empty($permisos['registrar_premio'])) throw new Exception('No tienes permisos para registrar premios.');
                incluir($obj, $id_modulo, $bitacoraObj);
                break;
            case 'eliminar':
                if (empty($permisos['eliminar_premio'])) throw new Exception('No tienes permisos para eliminar premios.');
                eliminar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'modificar':
                if (empty($permisos['modificar_premio'])) throw new Exception('No tienes permisos para modificar premios.');
                modificar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'generar':
                if (empty($permisos['generar_premio'])) throw new Exception('No tienes permisos para generar un reporte de los premios.');
                generar($obj, $id_modulo, $bitacoraObj);
                break;
            default:
                throw new Exception('Acción no permitida.');
        }
    } catch (Exception $e) {
        logs('Premios', $e->getMessage(), 'Controlador_ManejarSolicitud');
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

    include(__DIR__ . '/../vista/Premios.php');
}

function buscar($obj): void
{
    try {
        // Ajustado a codigo_premio
        $validaciones = ['codigo_premio' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Código inválido.']];
        validar_datos($validaciones);

        $datos = [
            'codigo_premio' => $_POST['codigo_premio'], // Ajustado
            'accion' => 'buscar'
        ];

        $resultado = $obj->procesarDatos($datos);
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Premios', $e->getMessage(), 'Controlador_Buscar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function incluir($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $validaciones = [
            'nombre' => ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,30}$/', 'mensaje' => 'Nombre inválido.'],
            'tipo'   => ['regla' => '/^[GI]$/', 'mensaje' => 'Tipo inválido. Solo se permite G o I.']
        ];

        validar_datos($validaciones);

        $datos = [
            'tipo'   => $_POST['tipo'],
            'nombre' => $_POST['nombre']
        ];
        $datos['accion'] = 'incluir';

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Registró al Premio: " . $_POST['nombre'] . ' ' . $_POST['tipo']);
            $resultado = array('accion' => 'incluir', 'mensaje' => 'Premio registrado exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                DUPLICATE_NAME => 'Ya existe un Premio registrado con ese Nombre.',
                DB_CONNECTION      => 'Ocurrio un error al conectarse con la base de datos.',
                default        => 'Ocurrió un error inesperado en el registro.'
            };
        }
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Premios', $e->getMessage(), 'Controlador_Incluir');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function modificar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        // Ajustado a codigo_premio
        $validaciones = [
            'codigo_premio' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Código inválido.'],
            'nombre'        => ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,30}$/', 'mensaje' => 'Nombre inválido.'],
            'tipo'          => ['regla' => '/^[GI]$/', 'mensaje' => 'Tipo inválido. Solo se permite G o I.']
        ];

        validar_datos($validaciones);

        $datos = [
            'codigo_premio' => $_POST['codigo_premio'], // Ajustado
            'nombre'        => $_POST['nombre'],
            'tipo'          => $_POST['tipo'] 
        ];
        $datos['accion'] = 'modificar';

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Modifico al Premio: " . $_POST['nombre'] . ' ' . $_POST['tipo']);
            $resultado = array('accion' => 'modificar', 'mensaje' => 'Premio modificado exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                DUPLICATE_NAME => 'Ya existe un Premio registrado con ese nombre.',
                default        => 'Ocurrió un error inesperado en la modificacion.'
            };
        }

        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Premios', $e->getMessage(), 'Controlador');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function eliminar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        // Ajustado a codigo_premio
        $validaciones = ['codigo_premio' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Código inválido.']];
        validar_datos($validaciones);

        $datos = [
            'codigo_premio' => $_POST['codigo_premio'], // Ajustado
            'accion'        => 'eliminar'
        ];

        $resultado = $obj->procesarDatos($datos);
        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Elimino al Premio: " . $_POST['codigo_premio']); // Ajustado para bitácora
            $resultado = array('accion' => 'eliminar', 'mensaje' => 'Premio eliminado exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                INVALID_ID => 'El Premio no existe.',
                ASSOCIATES => 'El Premio tiene detalle palmare asociados.',
                DB_CONNECTION      => 'Ocurrio un error al conectarse con la base de datos.',
                default    => 'Ocurrió un error inesperado en la eliminacion.'
            };
        }
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Premios', $e->getMessage(), 'Controlador_Eliminar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function generar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $validacionesReporte = [];
        $datosFiltro = ['accion' => 'generar'];

        if (!empty($_POST['nombre'])) {
            $validacionesReporte['nombre'] = ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,30}$/', 'mensaje' => 'Nombre inválido.'];
            $datosFiltro['nombre'] = $_POST['nombre'];
        }
        if (!empty($_POST['tipo'])) {
            $validacionesReporte['tipo'] = ['regla' => '/^[GI]$/', 'mensaje' => 'Tipo inválida. Solo se permite G o I.'];
            $datosFiltro['tipo'] = $_POST['tipo'];
        }

        validar_datos($validacionesReporte);

        $respuesta =  $obj->procesarDatos($datosFiltro);
        $datos = $respuesta['datos'];
        if (empty($datos)) {
            echo json_encode(['accion' => 'error', 'mensaje' => 'No se encontraron premios para hacer el reporte.']);
            exit();
        }
        $nombreVista = 'R_Premios';
        $objG = new GenerarReporte();
        $pdf = $objG->generarPDF($nombreVista, $datos, 'Premios');
        if (isset($pdf['accion']) && $pdf['accion'] === 'reporte') {
            registrarBitacora($bitacoraObj, $id_modulo, "Generó reporte de premios.");
        }
        echo json_encode($pdf);
    } catch (Exception $e) {
        logs('Premios', $e->getMessage(), 'Controlador_Generar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}