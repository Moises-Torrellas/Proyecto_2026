<?php

use App\modelo\ModeloEquipos;
use App\modelo\ModeloCategorias;
use App\servicios\GenerarReporte;

// 1. Cargamos las funciones base
require_once __DIR__ . '/Base.php';

// 2. Configuración del módulo
$id_modulo = _MD_EQUIPOS_;

// 3. Procesar permisos
$permisos = procesarPermisos($id_modulo, $bitacora);

// 4. Lógica de despacho (Router interno)
$nombreClaseModelo = 'App\modelo\ModeloEquipos';

if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloEquipos();

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitudEquipos($objModelo, $id_modulo, $bitacora, $permisos);
} else {
    registrarBitacora($bitacora, $id_modulo, 'Ingreso al Modulo');
    $respuesta = $objModelo->Consultar();
    $registro = $respuesta['datos'] ?? [];
    $variables = ['registro' => $registro, 'permisos' => $permisos];
    cargarVista($pagina, $variables);
}

/**
 * --- FUNCIONES DEL CONTROLADOR ---
 */
function manejarSolicitudEquipos($obj, $id_modulo, $bitacoraObj, array $permisos): void
{
    try {
        $tokenRecibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!isset($_SESSION['token']) || !hash_equals($_SESSION['token'], $tokenRecibido)) {
            throw new Exception('Error de seguridad: Token inválido o expirado.');
        }

        $accion = isset($_POST['accion']) ? filter_var($_POST['accion'], FILTER_SANITIZE_SPECIAL_CHARS) : '';

        switch ($accion) {
            case 'consultar':
                if (!$permisos['ingresar']) throw new Exception('No tienes permisos para consultar equipos.');
                consultar($obj, $permisos);
                break;
            case 'MultiConsulta':
                if (!$permisos['ingresar']) throw new Exception('No tienes permisos para consultar equipos.');
                MultiConsulta();
                break;
            case 'buscar':
                if (!$permisos['modificar']) throw new Exception('No tienes permisos para modificar equipos.');
                buscar($obj);
                break;
            case 'incluir':
                if (!$permisos['registrar']) throw new Exception('No tienes permisos para registrar equipos.');
                incluir($obj, $id_modulo, $bitacoraObj);
                break;
            case 'eliminar':
                if (!$permisos['eliminar']) throw new Exception('No tienes permisos para eliminar equipos.');
                eliminar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'modificar':
                if (!$permisos['modificar']) throw new Exception('No tienes permisos para modificar equipos.');
                modificar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'generar':
                if (!$permisos['reporte']) throw new Exception('No tienes permisos para generar un reporte de los equipos.');
                generar($obj, $id_modulo, $bitacoraObj);
                break;
            default:
                throw new Exception('Acción no permitida: ' . $accion);
        }
    } catch (Exception $e) {
        logs('Equipos', $e->getMessage(), 'Controlador_ManejarSolicitud');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function MultiConsulta(): void
{
    try {
        $modeloCat = new ModeloCategorias();
        $catRespuesta = $modeloCat->Consultar();

        // 1. Forzamos al navegador a interpretar la respuesta como JSON estricto
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode([
            'accion' => 'MultiConsulta',
            'categoria' => $catRespuesta['datos'] ?? []
        ]);

        // 2. Matamos la ejecución aquí para evitar que se cuele cualquier espacio en blanco al final del archivo
        exit();

    } catch (Exception $e) {
        logs('Equipos', $e->getMessage(), 'Controlador_MultiConsulta');

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['accion' => 'error', 'mensaje' => 'Error al inicializar los catálogos del módulo.']);
        exit();
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

    include(__DIR__ . '/../vista/Equipos.php');
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
        logs('Equipos', $e->getMessage(), 'Controlador_Buscar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function incluir($obj, $id_modulo, $bitacoraObj): void
{
    // --- BORRA ESTO DESPUÉS DE PROBAR ---
    if (empty($_POST['categoria'])) {
        echo json_encode(['accion' => 'error', 'mensaje' => 'DEBUG: $_POST["categoria"] está vacío. Recibí: ' . json_encode($_POST)]);
        exit();
    }
    // -------------------------------------

    try {
        $validaciones = [
            'nombre' => ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s]{3,30}$/', 'mensaje' => 'Nombre inválido.'],
            'categoria' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Categoría inválida.'],
        ];

        validar_datos($validaciones);

        $datos = [
            'nombre' => $_POST['nombre'],
            'categoria' => $_POST['categoria'],
            'accion' => 'incluir'
        ];

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Registró al Equipo: " . $_POST['nombre']);
            $resultado = ['accion' => 'incluir', 'mensaje' => 'Equipo registrado exitosamente.'];
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                DUPLICATE_NAME => 'Ya existe un equipo registrado con ese nombre.',
                INVALID_ID => 'La categoría ingresada no existe en los registros del club.',
                default => 'Ocurrió un error inesperado en el registro.'
            };
        }
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Equipos', $e->getMessage(), 'Controlador_Incluir');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function modificar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $validaciones = [
            'id' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.'],
            'nombre' => ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s]{3,30}$/', 'mensaje' => 'Nombre inválido.'],
            'categoria' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Categoría inválida.'],
        ];

        validar_datos($validaciones);

        $datos = [
            'id' => $_POST['id'],
            'nombre' => $_POST['nombre'],
            'categoria' => $_POST['categoria'],
            'accion' => 'modificar'
        ];

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Modificó al equipo: " . $_POST['nombre']);
            $resultado = ['accion' => 'modificar', 'mensaje' => 'Equipo modificado exitosamente.'];
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                DUPLICATE_NAME => 'Ya existe un Equipo registrado con este nombre.',
                INVALID_ID => 'La categoría ingresada no existe en los registros del club.',
                default => 'Ocurrió un error inesperado en la modificación.'
            };
        }

        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Equipos', $e->getMessage(), 'Controlador_Modificar');
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
            registrarBitacora($bitacoraObj, $id_modulo, "Eliminó al equipo ID: " . $_POST['id']);
            $resultado = ['accion' => 'eliminar', 'mensaje' => 'Equipo eliminado exitosamente.'];
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                INVALID_ID => 'El equipo no existe.',
                ASSOCIATES => 'El equipo tiene palmarés asociados.',
                default => 'Ocurrió un error inesperado en la eliminación.'
            };
        }
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Equipos', $e->getMessage(), 'Controlador_Eliminar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function generar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $datosFiltro = ['accion' => 'generar'];

        $respuesta = $obj->procesarDatos($datosFiltro);
        $datos = $respuesta['datos'] ?? [];

        if (empty($datos)) {
            echo json_encode(['accion' => 'error', 'mensaje' => 'No se encontraron equipos para hacer el reporte.']);
            exit();
        }

        $nombreVista = 'R_Equipos';
        $objG = new GenerarReporte();
        $pdf = $objG->generarPDF($nombreVista, $datos, 'Equipos');

        if (isset($pdf['accion']) && $pdf['accion'] === 'reporte') {
            registrarBitacora($bitacoraObj, $id_modulo, "Generó reporte de equipos.");
        }
        echo json_encode($pdf);
    } catch (Exception $e) {
        logs('Equipos', $e->getMessage(), 'Controlador_Generar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

