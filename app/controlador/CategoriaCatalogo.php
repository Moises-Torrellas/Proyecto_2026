<?php

use App\modelo\ModeloCategoriaCatalogo;

require_once __DIR__ . '/Base.php';
$id_modulo = _MD_CATEGORIA_CAT_;

$permisos = procesarPermisos($id_modulo, 'ingresar_catcatalogos');

$nombreClaseModelo = 'App\modelo\ModeloCategoriaCatalogo'; // Ajustado

if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloCategoriaCatalogo();

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitudCategorias($objModelo, $id_modulo, $bitacora, $permisos);
} else {
    registrarBitacora($bitacora , $id_modulo, 'Ingreso al Modulo Categoría Catálogo');
    cargarVista($pagina);
}

/**
 * --- FUNCIONES DEL CONTROLADOR ---
 */

function manejarSolicitudCategorias($obj, $id_modulo, $bitacoraObj, array $permisos): void
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
             if (empty($permisos['ingresar_catcatalogos'])) throw new Exception('No tienes permisos para consultar categorias.');
                consultar($obj, $permisos);
                break;
            case 'buscar':
                if (empty($permisos['modificar_catcatalogo'])) throw new Exception('No tienes permisos para modificar categorías.');
                buscar($obj,$permisos);
                break;
            case 'incluir':
                if (empty($permisos['registrar_catcatalogo'])) throw new Exception('No tienes permisos para registrar categorías.');
                incluir($obj, $id_modulo, $bitacoraObj);
                break;
            case 'eliminar':
                if (empty($permisos['eliminar_catcatalogo'])) throw new Exception('No tienes permisos para eliminar categorías.');
                eliminar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'modificar':
                if (empty($permisos['modificar_catcatalogo'])) throw new Exception('No tienes permisos para modificar categorías.');
                modificar($obj, $id_modulo, $bitacoraObj);
                break;

            default:
                throw new Exception('Acción no permitida.');
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

/**
 * Acciones específicas
 */

function consultar($obj, $permisos): void
{
    $filtro['filtro'] = $_POST['filtro'] ?? '';
    $respuesta = $obj->Consultar($filtro);
    echo json_encode($respuesta);
}

function buscar($obj, $permisos): void
{
    try {
        // Ajustado a id_categoria
        validar_requeridos(['id_categoria']);

        $datos = [
            'id_categoria' => $_POST['id_categoria'], // Ajustado
            'accion'       => 'buscar'
        ];

        $resultado = $obj->procesarDatos($datos);
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('CategoriaCatalogo', $e->getMessage(), 'Controlador_Buscar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function incluir($obj, $id_modulo, $bitacoraObj): void
{
    try {
        validar_requeridos(['nombre', 'descripcion']);

        $datos = [
            'nombre'      => $_POST['nombre'],
            'descripcion' => $_POST['descripcion']
        ];  
        $datos['accion'] = 'incluir';

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'incluir') {
            registrarBitacora($bitacoraObj, $id_modulo, "Registró la categoría: " . $_POST['nombre']);
        }

        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('CategoriaCatalogo', $e->getMessage(), 'Controlador_Incluir');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function modificar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        // Ajustado a id_categoria
        validar_requeridos(['id_categoria', 'nombre', 'descripcion']);

        $datos = [
            'id_categoria' => $_POST['id_categoria'], // Ajustado
            'nombre'       => $_POST['nombre'],
            'descripcion'  => $_POST['descripcion'],
        ];
        $datos['accion'] = 'modificar';

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'modificar') { // Corrección: el modelo devuelve 'modificar'
            registrarBitacora($bitacoraObj, $id_modulo, "Modificó la categoría: " . $_POST['nombre']);
        }

        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('CategoriaCatalogo', $e->getMessage(), 'Controlador_Modificar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function eliminar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        // Ajustado a id_categoria
        validar_requeridos(['id_categoria']);

        $datos = [
            'id_categoria' => $_POST['id_categoria'], // Ajustado
            'accion'       => 'eliminar'
        ];

        $resultado = $obj->procesarDatos($datos);
        if (isset($resultado['accion']) && $resultado['accion'] === 'eliminar') {
            registrarBitacora($bitacoraObj, $id_modulo, "Eliminó la categoría: " . $_POST['id_categoria']);
        }
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('CategoriaCatalogo', $e->getMessage(), 'Controlador_Eliminar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}