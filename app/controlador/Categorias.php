<?php

use App\modelo\ModeloCategorias;

// 1. Cargamos las funciones base
require_once __DIR__ . '/Base.php';

// 2. Configuración del módulo
$id_modulo = _MD_CATEGORIAS_;

// 3. Procesar permisos (Retorna el array de permisos)
$permisos = procesarPermisos($id_modulo, $bitacora ?? null);

// 4. Lógica de despacho (Router interno)
$nombreClaseModelo = 'App\modelo\ModeloCategorias';

if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloCategorias();

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitudCategorias($objModelo, $id_modulo, $bitacora ?? null, $permisos);
} else {
    $respuesta = $objModelo->Consultar();
    $registro = $respuesta['datos'] ?? [];
    $variables = ['registro' => $registro, 'permisos' => $permisos];
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
                if (!$permisos['ingresar']) throw new Exception('No tienes permisos para consultar categorias.');
                consultar($obj, $permisos);
                break;
            case 'buscar':
                if (!$permisos['modificar']) throw new Exception('No tienes permisos para buscar/modificar categorías.');
                buscar($obj);
                break;
            case 'incluir':
                if (!$permisos['registrar']) throw new Exception('No tienes permisos para registrar categorias.');
                incluir($obj, $id_modulo, $bitacoraObj);
                break;
            case 'eliminar':
                if (!$permisos['eliminar']) throw new Exception('No tienes permisos para eliminar categorías.');
                eliminar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'modificar':
                if (!$permisos['modificar']) throw new Exception('No tienes permisos para modificar categorías.');
                modificar($obj, $id_modulo, $bitacoraObj);
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
        $validaciones = ['id' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.']];
        validar_datos($validaciones);

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
        $validaciones = [
            'nombre'   => ['regla' => '/^[a-zA-Z0-9\-\s]{2,30}$/', 'mensaje' => 'Nombre de categoría inválido.'],
            'edad_min' => ['regla' => '/^[0-9]{1,2}$/', 'mensaje' => 'Edad mínima inválida. Debe ser un número.'],
            'edad_max' => ['regla' => '/^[0-9]{1,2}$/', 'mensaje' => 'Edad máxima inválida. Debe ser un número.']
        ];

        validar_datos($validaciones);
        
        if ((int)$_POST['edad_min'] > (int)$_POST['edad_max']) {
            throw new Exception('La edad mínima no puede ser mayor que la edad máxima.');
        }

        $datos = [
            'nombre'      => $_POST['nombre'],
            'edad_minima' => $_POST['edad_min'],
            'edad_maxima' => $_POST['edad_max'],
            'accion'      => 'incluir'
        ];

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Registró la categoría: " . $_POST['nombre']);
            $resultado = ['accion' => 'incluir', 'mensaje' => 'Categoría registrada exitosamente.'];
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                'Ya existe una categoría registrada con este nombre.' => $resultado['codigo'],
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
        $validaciones = [
            'id'       => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.'],
            'nombre'   => ['regla' => '/^[a-zA-Z0-9\-\s]{2,30}$/', 'mensaje' => 'Nombre de categoría inválido.'],
            'edad_min' => ['regla' => '/^[0-9]{1,2}$/', 'mensaje' => 'Edad mínima inválida.'],
            'edad_max' => ['regla' => '/^[0-9]{1,2}$/', 'mensaje' => 'Edad máxima inválida.']
        ];

        validar_datos($validaciones);

        if ((int)$_POST['edad_min'] > (int)$_POST['edad_max']) {
            throw new Exception('La edad mínima no puede ser mayor que la edad máxima.');
        }

        $datos = [
            'id'          => $_POST['id'],
            'nombre'      => $_POST['nombre'],
            'edad_minima' => $_POST['edad_min'],
            'edad_maxima' => $_POST['edad_max'],
            'accion'      => 'modificar'
        ];

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Modificó la categoría: " . $_POST['nombre']);
            $resultado = ['accion' => 'modificar', 'mensaje' => 'Categoría modificada exitosamente.'];
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                'Ya existe otra categoría registrada con este nombre.' => $resultado['codigo'],
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
        $validaciones = ['id' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.']];
        validar_datos($validaciones);

        $datos = [
            'id' => $_POST['id'],
            'accion' => 'eliminar'
        ];

        $resultado = $obj->procesarDatos($datos);
        
        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Eliminó la categoría con ID: " . $_POST['id']);
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