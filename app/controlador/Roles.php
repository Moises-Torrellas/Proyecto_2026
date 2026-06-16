<?php

use App\modelo\ModeloRoles;

// 1. Cargamos las funciones base
require_once __DIR__ . '/Base.php';

// 2. Configuración del módulo
$id_modulo = _MD_ROLES_;

// 3. Procesar permisos (Ahora retorna un array en lugar de usar global)
$permisos = procesarPermisos($id_modulo, $bitacora);

// 4. Lógica de despacho (Router interno)
$nombreClaseModelo = 'App\modelo\ModeloRoles';

if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloRoles();

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitudRoles($objModelo, $id_modulo, $bitacora, $permisos);
} else {
    registrarBitacora($bitacora , $id_modulo, 'Ingreso al Modulo');
    $respuesta = $objModelo->Consultar();
    
    $error_bd = '';
    if (isset($respuesta['accion']) && $respuesta['accion'] === 'error') {
        $error_bd = ($respuesta['mensaje'] == DB_CONNECTION) ? 'Error al conectar con la base de datos.' : '';
    }
    
    $variables = ['permisos' => $permisos, 'error_bd' => $error_bd];
    cargarVista($pagina, $variables);
}

/**
 * --- FUNCIONES DEL CONTROLADOR ---
 */

function manejarSolicitudRoles($obj, $id_modulo, $bitacoraObj, array $permisos): void
{
    try {
        $tokenRecibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!isset($_SESSION['token']) || !hash_equals($_SESSION['token'], $tokenRecibido)) {
            throw new Exception('Error de seguridad: Token inválido o expirado.');
        }

        $accion = isset($_POST['accion']) ? filter_var($_POST['accion'], FILTER_SANITIZE_SPECIAL_CHARS) : '';

        // Validaciones de permisos centralizadas en el switch
        switch ($accion) {
            case 'consultar':
                consultarRolesData($obj);
                break;
            case 'buscar':
                if (!$permisos['modificar']) throw new Exception('No tiene permisos para buscar roles.');
                buscarRolesData($obj);
                break;

            case 'incluir':
                if (!$permisos['registrar']) throw new Exception('No tiene permisos para incluir roles.');
                incluirRolesData($obj, $id_modulo, $bitacoraObj);
                break;

            case 'modificar':
                if (!$permisos['modificar']) throw new Exception('No tiene permisos para modificar roles.');
                modificarRolesData($obj, $id_modulo, $bitacoraObj);
                break;
            case 'guardar_permisos':
                if (!$permisos['modificar']) throw new Exception('No tiene permisos para modificar permisos.');
                guardarPermisosData($obj);
                break;
            case 'eliminar':
                if (!$permisos['eliminar']) throw new Exception('No tiene permisos para eliminar roles.');
                eliminarRolesData($obj, $id_modulo, $bitacoraObj);
                break;
            case 'CargarPermisos':
                if (!$permisos['otros']) throw new Exception('No tiene permisos para modificar permisos.');
                CargarPermisos($obj);
                break;

            default:
                throw new Exception('Acción no reconocida.');
        }
    } catch (Exception $e) {
        logs('Roles', $e->getMessage(), 'Controlador_ManejarSolicitud');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function consultarRolesData($obj): void
{
    $filtro['filtro'] = $_POST['filtro'] ?? '';
    $respuesta = $obj->Consultar($filtro);
    if(isset($respuesta['accion']) && $respuesta['accion'] == 'error') {
        $respuesta['mensaje'] ='Error al listar los representantes';
    }
    echo json_encode($respuesta);
}


function buscarRolesData($obj): void
{
    try {
        validar_requeridos(['id']);
        $idsProtegidos = [1, 2];
        if (in_array($_POST['id'], $idsProtegidos)) {
            throw new Exception('Este rol no puede ser modificado');
        }
        $resultado = $obj->procesarDatos(['id' => $_POST['id'], 'accion' => 'buscar']);
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Roles', $e->getMessage(), 'Controlador_Buscar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function CargarPermisos($obj) : void{
    try {
        validar_requeridos(['id']);
        $idsProtegidos = [1, 2];
        if (in_array($_POST['id'], $idsProtegidos)) {
            throw new Exception('Los permisos de este rol no pueden ser modificados');
        }
        $resultado = $obj->CargarPermisos($_POST['id']);
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Roles', $e->getMessage(), 'Controlador_Buscar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function incluirRolesData($obj, $id_modulo, $bitacoraObj): void
{
    try {
        validar_requeridos(['nombre']);

        $datos = [
            'nombre' => $_POST['nombre'],
            'accion' => 'incluir'
        ];

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Registro el Rol: " . $_POST['nombre']);
            $resultado = array('accion' => 'incluir', 'mensaje' => 'Rol registrado exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                DUPLICATE_NAME => 'Ya existe un rol registrado con este nombre.',
                ASSOCIATES  => 'Uno de Los modulos que intenta registrar no existe o esta restringido.',
                default          => 'Ocurrió un error inesperado en el registro del rol.'
            };
        }
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Roles', $e->getMessage(), 'Controlador_Incluir');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function modificarRolesData($obj, $id_modulo, $bitacoraObj): void
{
    try {
        validar_requeridos(['nombre', 'id']);

        $datos = [
            'id'             => $_POST['id'],
            'nombre'         => $_POST['nombre'],
            'accion'         => 'modificar'
        ];

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Modifico el Rol: " . $_POST['nombre']);
            $resultado = array('accion' => 'modificar', 'mensaje' => 'Rol modificado exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                DUPLICATE_NAME => 'Ya existe un rol registrado con este nombre.',
                ASSOCIATES  => 'Uno de Los modulos que intenta registrar no existe o esta restringido.',
                default          => 'Ocurrió un error inesperado en el registro del rol.'
            };
        }
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Roles', $e->getMessage(), 'Controlador_Modificar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function guardarPermisosData($obj): void
{
    try {
        validar_requeridos(['id']);

        $datos = [
            'id'             => $_POST['id'],
            'accion'         => 'guardar_permisos'
        ];

        if (isset($_POST['id_modulo']) && is_array($_POST['id_modulo'])) {
            $datos['id_modulo'] = $_POST['id_modulo'];
        } else {
            throw new Exception('Debe seleccionar al menos un módulo.');
        }

        foreach (['check_ingresar' => 'c_ingresar', 'check_registrar' => 'c_registrar', 'check_modificar' => 'c_modificar', 'check_eliminar' => 'c_eliminar', 'check_reporte' => 'c_reporte', 'check_otros' => 'c_otros'] as $postKey => $dataKey) {
            if (isset($_POST[$postKey]) && !empty($_POST[$postKey])) {
                $datos[$dataKey] = $_POST[$postKey];
            }
        }

        $resultado = $obj->procesarDatos($datos);
        if ($resultado['accion'] === 'exito') {
            echo json_encode(['accion' => 'guardar_permisos', 'mensaje' => 'Permisos guardados correctamente.']);
        } else {
            throw new Exception($resultado['codigo']);
        }
    } catch (Exception $e) {
        logs('Roles', $e->getMessage(), 'Controlador_GuardarPermisos');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}


function eliminarRolesData($obj, $id_modulo, $bitacoraObj)
{
    try {
        validar_requeridos(['id']);

        $datos = [
            'id'          => $_POST['id'],
            'accion'         => 'eliminar'
        ];

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Elimino el Rol: " . $_POST['id']);
            $resultado = array('accion' => 'eliminar', 'mensaje' => 'Rol eliminado exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                INVALID_ID => 'No puedes eliminar roles protegidos.',
                ASSOCIATES  => 'El rol tiene usuarios asociados.',
                ASSOCIATES . '0'  => 'El rol que intenta eliminar no existe',
                default          => 'Ocurrió un error inesperado en el registro del rol.'
            };
        }
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Roles', $e->getMessage(), 'Controlador_Eliminar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}
