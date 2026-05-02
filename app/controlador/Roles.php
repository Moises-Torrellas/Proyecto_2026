<?php

use App\modelo\ModeloRoles;

// 1. Cargamos las funciones base
require_once __DIR__ . '/Base.php';

// 2. Configuración del módulo
$id_modulo = _MD_ROLES_;

// 3. Procesar permisos (Ahora retorna un array en lugar de usar global)
$permisos = procesarPermisos($id_modulo, $bitacora ?? null);

// 4. Lógica de despacho (Router interno)
$nombreClaseModelo = 'App\modelo\ModeloRoles';

if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloRoles();

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitudRoles($objModelo, $id_modulo, $bitacora ?? null, $permisos);
} else {
    cargarVista($pagina);
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

            case 'consultarModulo':
                consultarModuloData($obj);
                break;

            case 'buscar':
                if (!$permisos['modificar']) throw new Exception('No tiene permisos para buscar roles.');
                buscarRolesData($obj);
                break;

            case 'incluir':
                if (!$permisos['incluir']) throw new Exception('No tiene permisos para incluir roles.');
                incluirRolesData($obj, $id_modulo, $bitacoraObj);
                break;

            case 'modificar':
                if (!$permisos['modificar']) throw new Exception('No tiene permisos para modificar roles.');
                modificarRolesData($obj, $id_modulo, $bitacoraObj);
                break;
            case 'eliminar':
                if (!$permisos['eliminar']) throw new Exception('No tiene permisos para eliminar roles.');
                eliminarRolesData($obj, $id_modulo, $bitacoraObj);
                break;

            default:
                throw new Exception('Acción no reconocida.');
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

/**
 * --- LÓGICA DE CADA ACCIÓN ---
 */

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
    validar_datos(['id' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.']]);
    $resultado = $obj->procesarDatos(['id' => $_POST['id'], 'accion' => 'buscar']);
    echo json_encode($resultado);
}

function incluirRolesData($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $validaciones = ['nombre' => ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{1,30}$/', 'mensaje' => 'Nombre inválido.']];

        validar_datos($validaciones);

        if (is_array($_POST['id_modulo'])) {
            if (count($_POST['id_modulo']) !== count(array_unique($_POST['id_modulo']))) {
                throw new Exception("No se permiten módulos duplicados");
            }
        }

        $validacionesP = [
            'id_modulo' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'ID de módulo inválido.'],
        ];

        $datos = [
            'nombre' => $_POST['nombre'],
            'id_modulo' => $_POST['id_modulo'],
            'accion' => 'incluir'
        ];

        if (isset($_POST['check_incluir']) && !empty($_POST['check_incluir'])) {
            $validacionesP['check_incluir'] = ['regla' => '/^[1]+$/', 'mensaje' => 'Valor de permiso incluir inválido.'];
            $datos['c_incluir'] = $_POST['check_incluir'];
        }
        if (isset($_POST['check_modificar']) && !empty($_POST['check_modificar'])) {
            $validacionesP['check_modificar'] = ['regla' => '/^[1]+$/', 'mensaje' => 'Valor de permiso modificar inválido.'];
            $datos['c_modificar'] = $_POST['check_modificar'];
        }
        if (isset($_POST['check_eliminar']) && !empty($_POST['check_eliminar'])) {
            $validacionesP['check_eliminar'] = ['regla' => '/^[1]+$/', 'mensaje' => 'Valor de permiso eliminar inválido.'];
            $datos['c_eliminar'] = $_POST['check_eliminar'];
        }
        if (isset($_POST['check_reporte']) && !empty($_POST['check_reporte'])) {
            $validacionesP['check_reporte'] = ['regla' => '/^[1]+$/', 'mensaje' => 'Valor de permiso reporte inválido.'];
            $datos['c_reporte'] = $_POST['check_eliminar'];
        }
        if (isset($_POST['check_otros']) && !empty($_POST['check_otros'])) {
            $validacionesP['check_otros'] = ['regla' => '/^[1]+$/', 'mensaje' => 'Valor de permiso otros inválido.'];
            $datos['c_otros'] = $_POST['check_otros'];
        }

        validarArrays($validacionesP);



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
        $validaciones = ['nombre' => ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{1,30}$/', 'mensaje' => 'Nombre inválido.'],
                        'id' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.']];

        validar_datos($validaciones);

        if (is_array($_POST['id_modulo'])) {
            if (count($_POST['id_modulo']) !== count(array_unique($_POST['id_modulo']))) {
                throw new Exception("No se permiten módulos duplicados");
            }
        }

        $validacionesP = [
            'id_modulo'      => ['regla' => '/^[0-9]+$/', 'mensaje' => 'ID de módulo inválido.'],
        ];

        $datos = [
            'id'          => $_POST['id'],
            'nombre'      => $_POST['nombre'],
            'id_modulo'   => $_POST['id_modulo'],
            'accion'         => 'modificar'
        ];

        if (isset($_POST['check_incluir']) && !empty($_POST['check_incluir'])) {
            $validacionesP['check_incluir'] = ['regla' => '/^[1]+$/', 'mensaje' => 'Valor de permiso incluir inválido.'];
            $datos['c_incluir'] = $_POST['check_incluir'];
        }
        if (isset($_POST['check_modificar']) && !empty($_POST['check_modificar'])) {
            $validacionesP['check_modificar'] = ['regla' => '/^[1]+$/', 'mensaje' => 'Valor de permiso modificar inválido.'];
            $datos['c_modificar'] = $_POST['check_modificar'];
        }
        if (isset($_POST['check_eliminar']) && !empty($_POST['check_eliminar'])) {
            $validacionesP['check_eliminar'] = ['regla' => '/^[1]+$/', 'mensaje' => 'Valor de permiso eliminar inválido.'];
            $datos['c_eliminar'] = $_POST['check_eliminar'];
        }
        if (isset($_POST['check_reporte']) && !empty($_POST['check_reporte'])) {
            $validacionesP['check_reporte'] = ['regla' => '/^[1]+$/', 'mensaje' => 'Valor de permiso reporte inválido.'];
            $datos['c_reporte'] = $_POST['check_eliminar'];
        }
        if (isset($_POST['check_otros']) && !empty($_POST['check_otros'])) {
            $validacionesP['check_otros'] = ['regla' => '/^[1]+$/', 'mensaje' => 'Valor de permiso otros inválido.'];
            $datos['c_otros'] = $_POST['check_otros'];
        }

        validarArrays($validacionesP);

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


function eliminarRolesData($obj, $id_modulo, $bitacoraObj){
    try {
        $validaciones = ['id' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.']];

        validar_datos($validaciones);

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
                ASSOCIATES.'0'  => 'El rol que intenta eliminar no existe',
                default          => 'Ocurrió un error inesperado en el registro del rol.'
            };
        }
        echo json_encode($resultado);

    } catch (Exception $e) {
        logs('Roles', $e->getMessage(), 'Controlador_Eliminar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}


