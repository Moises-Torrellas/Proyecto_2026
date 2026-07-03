<?php

use App\modelo\ModeloCatalogo;
use App\modelo\ModeloCategoriaCatalogo;
use App\servicios\GenerarReporte;

require_once __DIR__ . '/Base.php';

$id_modulo = _MD_CATALOGO_; 
$permisos = procesarPermisos($id_modulo, '');

$nombreClaseModelo = 'App\modelo\ModeloCatalogo';

if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloCatalogo();

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitudCatalogo($objModelo, $id_modulo, $bitacora ?? null, $permisos);
} else {
    registrarBitacora($bitacora ?? null, $id_modulo, 'Ingreso al Modulo de Catálogo');
    $respuesta = $objModelo->Consultar();
    
    $error_bd = '';
    if (isset($respuesta['accion']) && $respuesta['accion'] === 'error') {
        $error_bd = 'Error al conectar con la base de datos.';
    }

    $registro = $respuesta['datos'] ?? [];
    $variables = ['registro' => $registro, 'permisos' => $permisos, 'error_bd' => $error_bd];
    cargarVista($pagina, $variables);
}

function manejarSolicitudCatalogo($obj, $id_modulo, $bitacoraObj, array $permisos): void
{
    try {
        $tokenRecibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!isset($_SESSION['token']) || !hash_equals($_SESSION['token'], $tokenRecibido)) {
            throw new Exception('Error de seguridad: Token inválido o expirado.');
        }

        $accion = isset($_POST['accion']) ? filter_var($_POST['accion'], FILTER_SANITIZE_SPECIAL_CHARS) : '';

        switch ($accion) {
            case 'consultar':
                if (!$permisos['ingresar']) throw new Exception('No tienes permisos para consultar catálogos.');
                consultar($obj, $permisos);
                break;
            case 'MultiConsulta':
                if (!$permisos['ingresar']) throw new Exception('No tienes permisos para consultar catálogos.');
                MultiConsulta();
                break;
            case 'buscar':
                if (!$permisos['modificar']) throw new Exception('No tienes permisos para modificar catálogos.');
                buscar($obj);
                break;
            case 'incluir':
                if (!$permisos['registrar']) throw new Exception('No tienes permisos para registrar catálogos.');
                incluir($obj, $id_modulo, $bitacoraObj);
                break;
            case 'eliminar':
                if (!$permisos['eliminar']) throw new Exception('No tienes permisos para eliminar catálogos.');
                eliminar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'modificar':
                if (!$permisos['modificar']) throw new Exception('No tienes permisos para modificar catálogos.');
                modificar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'generar':
                if (!$permisos['reporte']) throw new Exception('No tienes permisos para generar reportes.');
                generar($obj, $id_modulo, $bitacoraObj);
                break;
            default:
                throw new Exception('Acción no permitida.');
        }
    } catch (Exception $e) {
        logs('Catalogo', $e->getMessage(), 'Controlador_ManejarSolicitud');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function consultar($obj, $permisos): void
{
    $filtro['filtro'] = $_POST['filtro'] ?? '';
    $respuesta = $obj->Consultar($filtro);
    $registro = $respuesta['datos'] ?? [];
    $solo_lista = true;
    include(__DIR__ . '/../vista/Catalogo.php'); 
}

function MultiConsulta(): void 
{
    try {
        $modeloCat = new ModeloCategoriaCatalogo();
        $catRespuesta = $modeloCat->Consultar();

        echo json_encode([
            'accion'     => 'MultiConsulta',
            'categorias' => $catRespuesta['datos'] ?? []
        ]);
    } catch (Exception $e) {
        logs('Catalogo', $e->getMessage(), 'Controlador_MultiConsulta');
        echo json_encode(['accion' => 'error', 'mensaje' => 'Error al inicializar los catálogos del módulo.']);
    }
}

function buscar($obj): void
{
    try {
        validar_requeridos(['id_catalogo']);

        $datos = [
            'id_catalogo' => $_POST['id_catalogo'],
            'accion'      => 'buscar'
        ];
        echo json_encode($obj->procesarDatos($datos));
    } catch (Exception $e) {
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function incluir($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $requeridos = ['nombre', 'id_categoria', 'stock_minimo'];
        validar_requeridos($requeridos);

        $datos = [
            'nombre'       => $_POST['nombre'],
            'id_categoria' => $_POST['id_categoria'],
            'stock_minimo' => $_POST['stock_minimo'],
            'talla'        => $_POST['talla'] ?? null,
            'accion'       => 'incluir'
        ];

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Registró un artículo en catálogo: " . $_POST['nombre']);
            $resultado = array('accion' => 'incluir', 'mensaje' => 'Artículo registrado exitosamente en el catálogo.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = 'Ocurrió un error inesperado en el registro.';
        }
        echo json_encode($resultado);
    } catch (Exception $e) {
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function modificar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $requeridos = ['id_catalogo', 'nombre', 'id_categoria', 'stock_minimo'];
        validar_requeridos($requeridos);

        $datos = [
            'id_catalogo'  => $_POST['id_catalogo'],
            'nombre'       => $_POST['nombre'],
            'id_categoria' => $_POST['id_categoria'],
            'stock_minimo' => $_POST['stock_minimo'],
            'talla'        => $_POST['talla'] ?? null,
            'accion'       => 'modificar'
        ];

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Modificó artículo en catálogo ID: " . $_POST['id_catalogo']);
            $resultado = array('accion' => 'modificar', 'mensaje' => 'Catálogo modificado exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = 'Ocurrió un error inesperado en la modificación.';
        }

        echo json_encode($resultado);
    } catch (Exception $e) {
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function eliminar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        validar_requeridos(['id_catalogo']);

        $datos = [
            'id_catalogo' => $_POST['id_catalogo'],
            'accion'      => 'eliminar'
        ];

        $resultado = $obj->procesarDatos($datos);
        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Eliminó el artículo del catálogo ID: " . $_POST['id_catalogo']);
            $resultado = array('accion' => 'eliminar', 'mensaje' => 'Artículo eliminado exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = $resultado['codigo'] ?? 'Error al eliminar. Verifica dependencias.';
        }
        echo json_encode($resultado);
    } catch (Exception $e) {
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function generar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $validacionesReporte = [];
        $datosFiltro = ['accion' => 'generar'];

        if (!empty($_POST['id_categoria'])) {
            $validacionesReporte['id_categoria'] = ['regla' => '/^[0-9]+$/', 'mensaje' => 'Categoría inválida.'];
            $datosFiltro['id_categoria'] = $_POST['id_categoria'];
        }

        if (!empty($_POST['talla'])) {
            $validacionesReporte['talla'] = ['regla' => '/^[a-zA-Z0-9\s\-\/]{1,10}$/', 'mensaje' => 'Talla inválida.'];
            $datosFiltro['talla'] = $_POST['talla'];
        }

        validar_datos($validacionesReporte);

        $respuesta = $obj->procesarDatos($datosFiltro);
        $datos = $respuesta['datos'];
        if (empty($datos)) {
            echo json_encode(['accion' => 'error', 'mensaje' => 'No se encontraron artículos para el reporte.']);
            exit();
        }
        
        $nombreVista = 'R_Catalogos'; 
        $objG = new GenerarReporte();
        $pdf = $objG->generarPDF($nombreVista, $datos, 'Catalogo de Equipamientos');
        
        if (isset($pdf['accion']) && $pdf['accion'] === 'reporte') {
            registrarBitacora($bitacoraObj, $id_modulo, "Generó reporte del catálogo.");
        }
        echo json_encode($pdf);
    } catch (Exception $e) {
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}