<?php

use App\modelo\ModeloCategorias;
use App\servicios\GenerarReporte;

// 1. Cargamos las funciones base
require_once __DIR__ . '/Base.php';

// 2. Configuración del módulo
$id_modulo = _MD_CATEGORIAS_;

// 3. Procesar permisos (Retorna el array de permisos)
$permisos = procesarPermisos($id_modulo, 'ingresar_categorias');

// 4. Lógica de despacho (Router interno)
$nombreClaseModelo = 'App\modelo\ModeloCategorias';

if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloCategorias();

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitudCategorias($objModelo, $id_modulo, $bitacora, $permisos);
} else {
    registrarBitacora($bitacora , $id_modulo, 'Ingreso al Modulo');
    $respuesta = $objModelo->Consultar();
    $registro = $respuesta['datos'] ?? [];

    $error_bd = '';
    if (isset($respuesta['accion']) && $respuesta['accion'] === 'error') {
        $error_bd = ($respuesta['mensaje'] == DB_CONNECTION) ? 'Error al conectar con la base de datos.' : '';
    }

    $variables = ['registro' => $registro, 'permisos' => $permisos, 'error_bd' => $error_bd];
    cargarVista($pagina, $variables);
}

function manejarSolicitudCategorias($obj, $id_modulo, $bitacoraObj, array $permisos): void
{
    try {
        $tokenRecibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!isset($_SESSION['token']) || !hash_equals($_SESSION['token'], $tokenRecibido)) {
            throw new Exception('Error de seguridad: Token inválido o expirado.');
        }

        $accion = isset($_POST['accion']) ? filter_var($_POST['accion'], FILTER_SANITIZE_SPECIAL_CHARS) : '';

        switch ($accion) {
            case 'consultar':
                if (empty($permisos['ingresar_categorias'])) throw new Exception('No tienes permisos para consultar categorias.');
                consultar($obj, $permisos);
                break;
            case 'buscar':
                if (empty($permisos['modificar_categoria'])) throw new Exception('No tienes permisos para buscar/modificar categorías.');
                buscar($obj);
                break;
            case 'incluir':
                if (empty($permisos['registrar_categoria'])) throw new Exception('No tienes permisos para registrar categorias.');
                incluir($obj, $id_modulo, $bitacoraObj);
                break;
            case 'eliminar':
                if (empty($permisos['eliminar_categoria'])) throw new Exception('No tienes permisos para eliminar categorías.');
                eliminar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'modificar':
                if (empty($permisos['modificar_categoria'])) throw new Exception('No tienes permisos para modificar categorías.');
                modificar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'generar':
                if (empty($permisos['generar_categorias'])) throw new Exception('No tienes permisos para generar un reporte de categorías.');
                generar($obj, $id_modulo, $bitacoraObj);
                break;
            default:
                throw new Exception('Acción no permitida.');
        }
    } catch (Exception $e) {
        logs('Categorias', $e->getMessage(), 'Controlador_ManejarSolicitud');
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

    // Nota: Asegúrate de que la vista dependa de estas variables locales
    include(__DIR__ . '/../vista/Categorias.php');
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
        logs('Categorias', $e->getMessage(), 'Controlador_Buscar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function incluir($obj, $id_modulo, $bitacoraObj): void
{
    try {
        validar_requeridos(['nombre', 'edad_min', 'edad_max']);

        $datos = [
            'nombre'      => $_POST['nombre'],
            'edad_minima' => $_POST['edad_min'],
            'edad_maxima' => $_POST['edad_max'],
            'accion'      => 'incluir'
        ];

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            $datos_nuevos_json = json_encode([
                'nombre' => $_POST['nombre'],
                'edad_minima' => $_POST['edad_min'],
                'edad_maxima' => $_POST['edad_max']
            ]);
            registrarBitacora($bitacoraObj, $id_modulo, "Registró la categoría: " . $_POST['nombre'], '', $datos_nuevos_json);
            $resultado = ['accion' => 'incluir', 'mensaje' => 'Categoría registrada exitosamente.'];
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                'Ya existe una categoría registrada con este nombre.' => $resultado['codigo'],
                'Ya existe una categoría que cubre este rango de edad.' => $resultado['codigo'],
                default => 'Ocurrió un error inesperado en el registro de la categoría.'
            };
        }

        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Categorias', $e->getMessage(), 'Controlador_Incluir');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function modificar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        validar_requeridos(['id', 'nombre', 'edad_min', 'edad_max']);

        $datos = [
            'id'          => $_POST['id'],
            'nombre'      => $_POST['nombre'],
            'edad_minima' => $_POST['edad_min'],
            'edad_maxima' => $_POST['edad_max'],
            'accion'      => 'modificar'
        ];

        $consultar_datos_previos = $obj->Buscar($_POST['id']);
        $categoria_previa = $consultar_datos_previos['datos'][0] ?? null;
        if (isset($categoria_previa['codigo_categoria'])) unset($categoria_previa['codigo_categoria']);
        $datos_previos_json = json_encode($categoria_previa);

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            $datos_nuevos_json = json_encode([
                'nombre' => $_POST['nombre'],
                'edad_minima' => $_POST['edad_min'],
                'edad_maxima' => $_POST['edad_max']
            ]);
            registrarBitacora($bitacoraObj, $id_modulo, "Modificó la categoría: " . $_POST['nombre'], $datos_previos_json, $datos_nuevos_json);
            $resultado = ['accion' => 'modificar', 'mensaje' => 'Categoría modificada exitosamente.'];
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                'Ya existe otra categoría registrada con este nombre.' => $resultado['codigo'],
                'Ya existe otra categoría que cubre este rango de edad.' => $resultado['codigo'],
                default => 'Ocurrió un error inesperado al modificar la categoría.'
            };
        }

        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Categorias', $e->getMessage(), 'Controlador_Modificar');
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

        $consultar_datos_previos = $obj->Buscar($_POST['id']);
        $categoria_previa = $consultar_datos_previos['datos'][0] ?? null;
        if (isset($categoria_previa['codigo_categoria'])) unset($categoria_previa['codigo_categoria']);
        $datos_previos_json = json_encode($categoria_previa);

        $resultado = $obj->procesarDatos($datos);
        
        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            $nombre_cat = $categoria_previa['nombre'] ?? $_POST['id'];
            registrarBitacora($bitacoraObj, $id_modulo, "Eliminó la categoría: " . $nombre_cat, $datos_previos_json, '');
            $resultado = ['accion' => 'eliminar', 'mensaje' => 'Categoría eliminada exitosamente.'];
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                'La categoría no existe.' => $resultado['codigo'],
                'No se puede eliminar: la categoría tiene atletas asociados.' => $resultado['codigo'],
                default => 'Ocurrió un error inesperado al eliminar la categoría.'
            };
        }
        
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Categorias', $e->getMessage(), 'Controlador_Eliminar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function generar($obj, $id_modulo, $bitacoraObj)
{
    try {
        $datosFiltro = ['accion' => 'generar'];
        
        // El modelo necesita procesar datos como array, pasamos el request si tiene datos o solo generar
        $respuesta = $obj->procesarDatos($datosFiltro);
        $datos = $respuesta['datos'] ?? [];

        if (empty($datos)) {
            echo json_encode(['accion' => 'error', 'mensaje' => 'No se encontraron categorías para hacer el reporte.']);
            exit();
        }

        $nombreVista = 'R_Categorias';
        $objG = new GenerarReporte();
        
        $formato = $_POST['formato'] ?? 'pdf';
        if ($formato === 'excel') {
            $reporte = $objG->generarExcel($nombreVista, $datos, 'Categorías');
        } else {
            $reporte = $objG->generarPDF($nombreVista, $datos, 'Categorías');
        }

        if (isset($reporte['accion']) && $reporte['accion'] === 'reporte') {
            registrarBitacora($bitacoraObj, $id_modulo, "Generó reporte de categorías en " . strtoupper($formato));
        }
        
        echo json_encode($reporte);
    } catch (Exception $e) {
        logs('Categorias', $e->getMessage(), 'Controlador_Generar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}