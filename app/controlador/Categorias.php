<?php

use App\modelo\ModeloCategorias;

// 1. Cargamos las funciones base
require_once __DIR__ . '/Base.php';

// 2. Configuración del módulo (Corregido al ID de Categorías)
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
                consultar($obj);
                break;
            case 'buscar':
                if (!$permisos['modificar']) throw new Exception('No tienes permisos para modificar categorias.');
                buscar($obj);
                break;
            case 'incluir':
                if (!$permisos['incluir']) throw new Exception('No tienes permisos para registrar categorias.');
                incluir($obj, $id_modulo, $bitacoraObj);
                break;
            case 'eliminar':
                if (!$permisos['eliminar']) throw new Exception('No tienes permisos para eliminar categorias.');
                eliminar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'modificar':
                if (!$permisos['modificar']) throw new Exception('No tienes permisos para modificar categorias.');
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

function consultar($obj): void
{
    $filtro['filtro'] = $_POST['filtro'] ?? '';
    $respuesta = $obj->Consultar($filtro);
    echo json_encode($respuesta);
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
        logs('Categorias', $e->getMessage(), 'Controlador');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function incluir($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $validaciones = [
            'nombre'   => ['regla' => '/^[a-zA-Z0-9\-\s]{2,30}$/', 'mensaje' => 'Nombre de categoría inválido.'],
            // Permite de 1 a 2 dígitos (Ej: 5, 12, 99)
            'edad_min' => ['regla' => '/^[0-9]{1,2}$/', 'mensaje' => 'Edad mínima inválida. Debe ser un número.'],
            'edad_max' => ['regla' => '/^[0-9]{1,2}$/', 'mensaje' => 'Edad máxima inválida. Debe ser un número.']
        ];

        validar_datos($validaciones);
        //Validacion logica adicional: la edad minima no puede ser mayor a la edad máxima
        if ((int)$_POST['edad_min'] > (int)$_POST['edad_max']) {
            throw new Exception('La edad mínima no puede ser mayor que la edad máxima.');
        }

         $datos = [
            'nombre'     => $_POST['nombre'],
            'edad_minima' => $_POST['edad_min'],
            'edad_maxima' => $_POST['edad_max']
        ];  
        $datos['accion'] = 'incluir';

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'incluir') {
            registrarBitacora($bitacoraObj, $id_modulo, "Registró la categoría: " . $_POST['nombre']);
        }

        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Categorias', $e->getMessage(), 'Controlador');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function modificar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $validaciones = [
            'id' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.'],
            'nombre'   => ['regla' => '/^[a-zA-Z0-9\-\s]{2,30}$/', 'mensaje' => 'Nombre de categoría inválido.'],
            // Permite de 1 a 2 dígitos (Ej: 5, 12, 99)
            'edad_min' => ['regla' => '/^[0-9]{1,2}$/', 'mensaje' => 'Edad mínima inválida. Debe ser un número.'],
            'edad_max' => ['regla' => '/^[0-9]{1,2}$/', 'mensaje' => 'Edad máxima inválida. Debe ser un número.']
        ];

        validar_datos($validaciones);

        if ((int)$_POST['edad_min'] > (int)$_POST['edad_max']) {
            throw new Exception('La edad mínima no puede ser mayor que la edad máxima.');
        }

        $datos = [
            'id' => $_POST['id'],
            'nombre'     => $_POST['nombre'],
            'edad_minima' => $_POST['edad_min'],
            'edad_maxima' => $_POST['edad_max']
        ];
        $datos['accion'] = 'modificar';

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'incluir') {
            registrarBitacora($bitacoraObj, $id_modulo, "modificó la categoría: " . $_POST['nombre']);
        }

        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Categorias', $e->getMessage(), 'Controlador');
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
        if (isset($resultado['accion']) && $resultado['accion'] === 'eliminar') {
            registrarBitacora($bitacoraObj, $id_modulo, "Eliminó la categoría: " . $_POST['id']);
        }
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Categorias', $e->getMessage(), 'Controlador');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}
