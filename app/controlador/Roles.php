<?php

use App\modelo\ModeloRoles;

// 1. Cargamos las funciones base (donde están procesarPermisos, validar_datos, etc.)
require_once __DIR__ . '/Base.php';

// 2. Configuración del módulo
$id_modulo = _MD_ROLES_;

// 3. Procesar permisos (llena la variable global $permisosGenerales)
// El objeto $bitacora debe venir del router/index
procesarPermisos($id_modulo, $bitacora ?? null);

// 4. Lógica de despacho (Router interno del controlador)
$nombreClaseModelo = 'App\modelo\ModeloRoles';

if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloRoles();

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitudRoles($objModelo, $id_modulo, $bitacora ?? null);
} else {
    cargarVista($pagina);
}

/**
 * --- FUNCIONES DEL CONTROLADOR ---
 */

function manejarSolicitudRoles($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $tokenRecibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!isset($_SESSION['token']) || !hash_equals($_SESSION['token'], $tokenRecibido)) {
            throw new Exception('Error de seguridad: Token inválido o expirado.');
        }

        $accion = isset($_POST['accion']) ? filter_var($_POST['accion'], FILTER_SANITIZE_SPECIAL_CHARS) : '';

        switch ($accion) {
            case 'consultar':
                consultarRolesData($obj);
                break;
            case 'consultarModulo':
                consultarModuloData($obj);
                break;
            case 'buscar':
                buscarRolesData($obj);
                break;
            case 'incluir':
                incluirRolesData($obj, $id_modulo, $bitacoraObj);
                break;
            case 'modificar':
                modificarRolesData($obj, $id_modulo, $bitacoraObj);
                break;
            default:
                throw new Exception('Acción no reconocida.');
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function consultarRolesData($obj): void
{
    $roles = $obj->consultar();
    echo json_encode($roles);
}

function consultarModuloData($obj): void
{
    $modulos = $obj->consultarModulo();
    echo json_encode($modulos);
}

function buscarRolesData($obj): void
{
    global $permisosGenerales;

    if (!$permisosGenerales['modificar']) {
        throw new Exception('No tiene permisos para modificar roles.');
    }

    validar_datos(['id' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.']]);

    $resultado = $obj->procesarDatos(['id' => $_POST['id'], 'accion' => 'buscar']);
    echo json_encode($resultado);
}

function incluirRolesData($obj, $id_modulo, $bitacoraObj): void
{
    global $permisosGenerales;

    if (!$permisosGenerales['incluir']) {
        throw new Exception('No tiene permisos para incluir roles.');
    }

    // Validar nombre del rol
    validar_datos(['nombre' => ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{1,30}$/', 'mensaje' => 'Nombre inválido. Solo letras y espacios.']]);

    // Validar permisos enviados
    $reglasPermisos = [
        'id_modulo'       => ['regla' => '/^[1-9]+$/', 'mensaje' => 'ID de módulo inválido.'],
        'check_incluir'   => ['regla' => '/^[0-1]$/',  'mensaje' => 'Valor de permiso inválido.'],
        'check_modificar' => ['regla' => '/^[0-1]$/',  'mensaje' => 'Valor de permiso inválido.'],
        'check_eliminar'  => ['regla' => '/^[0-1]$/',  'mensaje' => 'Valor de permiso inválido.'],
        'check_reporte'   => ['regla' => '/^[0-1]$/',  'mensaje' => 'Valor de permiso inválido.'],
        'check_otros'     => ['regla' => '/^[0-1]$/',  'mensaje' => 'Valor de permiso inválido.']
    ];

    $aValidar = array_intersect_key($reglasPermisos, $_POST);
    if (!empty($aValidar)) {
        // Asumiendo que validar_datos maneja múltiples campos o tienes validarArrays
        validar_datos($aValidar); 
    }

    $datos = [
        'accion' => 'incluir',
        'nombre' => $_POST['nombre'],
    ];

    $mapeoPermisos = [
        'id_modulo'       => 'id_modulo',
        'check_incluir'   => 'c_incluir',
        'check_modificar' => 'c_modificar',
        'check_eliminar'  => 'c_eliminar',
        'check_reporte'   => 'c_reporte',
        'check_otros'     => 'c_otros'
    ];

    foreach ($mapeoPermisos as $postKey => $dataKey) {
        if (isset($_POST[$postKey])) {
            $datos[$dataKey] = $_POST[$postKey];
        }
    }

    $respuesta = $obj->procesarDatos($datos);

    if (isset($respuesta['accion']) && $respuesta['accion'] == 'incluir') {
        registrarBitacora($bitacoraObj, $id_modulo, 'Registró el rol: ' . $_POST['nombre']);
    }

    echo json_encode($respuesta);
}

function modificarRolesData($obj, $id_modulo, $bitacoraObj): void
{
    global $permisosGenerales;

    if (!$permisosGenerales['modificar']) {
        throw new Exception('No tiene permisos para modificar roles.');
    }

    validar_datos(['nombre' => ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{1,30}$/', 'mensaje' => 'Nombre inválido.']]);

    $reglasPermisos = [
        'id_modulo'       => ['regla' => '/^[1-9]+$/', 'mensaje' => 'ID de módulo inválido.'],
        'check_incluir'   => ['regla' => '/^[0-1]$/',  'mensaje' => 'Valor de permiso inválido.'],
        'check_modificar' => ['regla' => '/^[0-1]$/',  'mensaje' => 'Valor de permiso inválido.'],
        'check_eliminar'  => ['regla' => '/^[0-1]$/',  'mensaje' => 'Valor de permiso inválido.'],
        'check_reporte'   => ['regla' => '/^[0-1]$/',  'mensaje' => 'Valor de permiso inválido.'],
        'check_otros'     => ['regla' => '/^[0-1]$/',  'mensaje' => 'Valor de permiso inválido.']
    ];

    $aValidar = array_intersect_key($reglasPermisos, $_POST);
    if (!empty($aValidar)) {
        validar_datos($aValidar);
    }

    $datos = [
        'accion' => 'modificar',
        'nombre' => $_POST['nombre'],
    ];

    $mapeoPermisos = [
        'id_modulo'       => 'id_modulo',
        'check_incluir'   => 'c_incluir',
        'check_modificar' => 'c_modificar',
        'check_eliminar'  => 'c_eliminar',
        'check_reporte'   => 'c_reporte',
        'check_otros'     => 'c_otros'
    ];

    foreach ($mapeoPermisos as $postKey => $dataKey) {
        if (isset($_POST[$postKey])) {
            $datos[$dataKey] = $_POST[$postKey];
        }
    }

    $respuesta = $obj->procesarDatos($datos);

    if (isset($respuesta['accion']) && $respuesta['accion'] == 'modificar') {
        registrarBitacora($bitacoraObj, $id_modulo, 'Modificó el rol: ' . $_POST['nombre']);
    }

    echo json_encode($respuesta);
}