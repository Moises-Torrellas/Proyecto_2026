<?php

use App\modelo\ModeloPosiciones;

// 1. Cargamos las funciones base
require_once __DIR__ . '/Base.php';

// 2. Configuración del módulo (Corregido al ID de Representantes)
$id_modulo = _MD_REPRESENTANTES_;

// 3. Procesar permisos (Retorna el array de permisos)
$permisos = procesarPermisos($id_modulo, $bitacora ?? null);

// 4. Lógica de despacho (Router interno)
$nombreClaseModelo = 'App\modelo\ModeloPosiciones';

if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloPosiciones();

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitudRepresentantes($objModelo, $id_modulo, $bitacora ?? null, $permisos);
} else {
    cargarVista($pagina);
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
                consultar($obj);
                break;
            case 'buscar':
                if (!$permisos['modificar']) throw new Exception('No tienes permisos para modificar posiciones.');
                buscar($obj);
                break;
            case 'incluir':
                if (!$permisos['incluir']) throw new Exception('No tienes permisos para registrar posiciones.');
                incluir($obj, $id_modulo, $bitacoraObj);
                break;
            case 'eliminar':
                if (!$permisos['eliminar']) throw new Exception('No tienes permisos para eliminar posiciones.');
                eliminar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'modificar':
                if (!$permisos['modificar']) throw new Exception('No tienes permisos para modificar posiciones.');
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
        logs('Posiciones', $e->getMessage(), 'Controlador_Buscar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function incluir($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $validaciones = [
            'nombre' => ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,30}$/', 'mensaje' => 'Nombre inválido.'],
            'abreviatura' => ['regla' => '/^[a-zA-Z]{2,4}$/', 'mensaje' => 'Abreviatura inválida.'],
            'descripcion' => ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,150}$/', 'mensaje' => 'Descripcion inválida.']
        ];

        if (empty($_POST['descripcion'])) {
            unset($validaciones['descripcion']);
        }

        validar_datos($validaciones);

        $datos = [
            'nombre'     => $_POST['nombre'],
            'abreviatura' => $_POST['abreviatura'],
            'descripcion'   => $_POST['descripcion'],
        ];
        $datos['accion'] = 'incluir';

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'incluir') {
            registrarBitacora($bitacoraObj, $id_modulo, "Registró la pasición: " . $_POST['nombre']);
        }

        echo json_encode($resultado);

    } catch (Exception $e) {
        logs('Posiciones', $e->getMessage(), 'Controlador_Incluir');
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
            registrarBitacora($bitacoraObj, $id_modulo, "Eliminó la posición: " . $_POST['id']);
        }
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Posiciones', $e->getMessage(), 'Controlador_Eliminar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function modificar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $validaciones = [
            'id' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.'],
            'nombre' => ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,30}$/', 'mensaje' => 'Nombre inválido.'],
            'abreviatura' => ['regla' => '/^[a-zA-Z]{2,4}$/', 'mensaje' => 'Abreviatura inválida.'],
            'descripcion' => ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,150}$/', 'mensaje' => 'Descripcion inválida.'],
        ];

        if (empty($_POST['descripcion'])) {
            unset($validaciones['descripcion']);
        }

        validar_datos($validaciones);

        $datos = [
            'id' => $_POST['id'],
            'nombre'     => $_POST['nombre'],
            'abreviatura' => $_POST['abreviatura'],
            'descripcion'   => $_POST['descripcion'],
        ];
        $datos['accion'] = 'modificar';

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'incluir') {
            registrarBitacora($bitacoraObj, $id_modulo, "Modificó la pasición: " . $_POST['id']);
        }

        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Posiciones', $e->getMessage(), 'Controlador_Modificar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}
