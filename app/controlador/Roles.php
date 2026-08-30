<?php

use App\modelo\ModeloRoles;
use App\modelo\ModeloPermisos;
use App\servicios\GenerarReporte;

// 1. Cargamos las funciones base
require_once __DIR__ . '/Base.php';

// 2. Configuración del módulo
$id_modulo = _MD_ROLES_;

// 3. Procesar permisos (Ahora retorna un array en lugar de usar global)
$permisos = procesarPermisos($id_modulo, 'ingresar_rol');

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
    registrarBitacora($bitacora, $id_modulo, 'Ingreso al Modulo');
    $respuesta = $objModelo->Consultar();

    $error_bd = '';
    if (isset($respuesta['accion']) && $respuesta['accion'] === 'error') {
        $error_bd = ($respuesta['mensaje'] == DB_CONNECTION) ? 'Error al conectar con la base de datos.' : '';
    } else {
        $registro = $respuesta['datos'] ?? [];
    }

    $variables = ['registro' => $registro, 'permisos' => $permisos, 'error_bd' => $error_bd];
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
                if (empty($permisos['ingresar_rol'])) throw new Exception('No tiene permisos para consultar roles.');
                consultarRolesData($obj, $permisos);
                break;
            case 'buscar':
                if (empty($permisos['modificar_rol'])) throw new Exception('No tiene permisos para buscar roles.');
                buscarRolesData($obj);
                break;

            case 'incluir':
                if (empty($permisos['registrar_rol'])) throw new Exception('No tiene permisos para incluir roles.');
                incluirRolesData($obj, $id_modulo, $bitacoraObj);
                break;

            case 'modificar':
                if (empty($permisos['modificar_rol'])) throw new Exception('No tiene permisos para modificar roles.');
                modificarRolesData($obj, $id_modulo, $bitacoraObj);
                break;
            case 'guardar_permisos':
                if (empty($permisos['permisos_rol'])) throw new Exception('No tiene permisos para modificar permisos.');
                guardarPermisosData($obj, $id_modulo, $bitacoraObj);
                break;
            case 'eliminar':
                if (empty($permisos['eliminar_rol'])) throw new Exception('No tiene permisos para eliminar roles.');
                eliminarRolesData($obj, $id_modulo, $bitacoraObj);
                break;
            case 'CargarPermisos':
                if (empty($permisos['permisos_rol'])) throw new Exception('No tiene permisos para modificar permisos.');
                CargarPermisos();
                break;
            case 'generar':
                if (empty($permisos['generar_rol'])) throw new Exception('No tiene permisos para generar reportes.');
                generar($obj, $id_modulo, $bitacoraObj);
                break;

            default:
                throw new Exception('Acción no reconocida.');
        }
    } catch (Exception $e) {
        logs('Roles', $e->getMessage(), 'Controlador_ManejarSolicitud');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function consultarRolesData($obj, $permisos): void
{
    try {
        $filtro['filtro'] = $_POST['filtro'] ?? '';
        $respuesta = $obj->Consultar($filtro);

        if (isset($respuesta['accion']) && $respuesta['accion'] === 'error') {
            $mensajeError = ($respuesta['mensaje'] == DB_CONNECTION) ? 'Error al conectar con la base de datos.' : $respuesta['mensaje'];
            echo json_encode(['accion' => 'error', 'mensaje' => $mensajeError]);
            return;
        }

        $registro = $respuesta['datos'] ?? [];
        $solo_lista = true;
        include(__DIR__ . '/../vista/Roles.php');
    } catch (throwable $e) {
        logs('Representantes', $e->getMessage(), 'Controlador_Consultar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
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

function CargarPermisos(): void
{
    try {
        validar_requeridos(['id']);
        $idsProtegidos = [1, 2];
        if (in_array($_POST['id'], $idsProtegidos)) {
            throw new Exception('Los permisos de este rol no pueden ser modificados');
        }
        $obj = new \App\modelo\ModeloRoles();
        $resultado = $obj->CargarPermisos($_POST['id']);
        $respuesta = ['datos' => $resultado['datos'], 'accion' => 'CargarPermisos'];
        echo json_encode($respuesta);
    } catch (Exception $e) {
        logs('Roles', $e->getMessage(), 'Controlador_Buscar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function incluirRolesData($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $requeridos = ['nombre'];
        if (!empty($_POST['descripcion'])) {
            $requeridos[] = 'descripcion';
        }
        validar_requeridos($requeridos);

        $datos = [
            'nombre' => $_POST['nombre'],
            'descripcion'   => $_POST['descripcion'],
            'accion' => 'incluir'
        ];

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            $datos_nuevos = [
                'nombre' => $_POST['nombre'],
                'descripcion' => $_POST['descripcion'] ?? ''
            ];
            registrarBitacora($bitacoraObj, $id_modulo, "Registró el Rol: " . $_POST['nombre'], '', json_encode($datos_nuevos));
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
        $requeridos = ['nombre','id'];
        if (!empty($_POST['descripcion'])) {
            $requeridos[] = 'descripcion';
        }
        validar_requeridos($requeridos);

        $datos = [
            'nombre' => $_POST['nombre'],
            'descripcion'   => $_POST['descripcion'],
            'id' => $_POST['id'],
            'accion' => 'modificar'
        ];

        $respuestaVieja = $obj->procesarDatos(['id' => $_POST['id'], 'accion' => 'buscar']);
        $datosPrevios = '';
        if (isset($respuestaVieja['accion']) && $respuestaVieja['accion'] === 'buscar' && !empty($respuestaVieja['datos'])) {
            $viejo = $respuestaVieja['datos'][0];
            $datosPrevios = json_encode([
                'nombre' => $viejo['nombre_rol'],
                'descripcion' => $viejo['descripcion'] ?? ''
            ]);
        }

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            $datosNuevos = json_encode([
                'nombre' => $_POST['nombre'],
                'descripcion' => $_POST['descripcion'] ?? ''
            ]);
            registrarBitacora($bitacoraObj, $id_modulo, "Modificó el Rol: " . $_POST['nombre'], $datosPrevios, $datosNuevos);
            $resultado = array('accion' => 'modificar', 'mensaje' => 'Rol modificado exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                DUPLICATE_NAME => 'Ya existe un rol registrado con este nombre.',
                INVALID_ID => 'No existe el rol que intenta modificar.',
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

function guardarPermisosData($obj, $id_modulo, $bitacoraObj): void
{
    try {
        validar_requeridos(['id']);

        $datos = [
            'id'             => $_POST['id'],
            'accion'         => 'guardar_permisos',
            'permisos'       => $_POST['permisos'] ?? [] // Array of id_permiso => 1
        ];

        $resultado = $obj->procesarDatos($datos);
        if ($resultado['accion'] === 'exito') {
            $respuestaVieja = $obj->procesarDatos(['id' => $_POST['id'], 'accion' => 'buscar']);
            $nombreRolPermisos = $_POST['id'];
            if (isset($respuestaVieja['accion']) && $respuestaVieja['accion'] === 'buscar' && !empty($respuestaVieja['datos'])) {
                $viejo = $respuestaVieja['datos'][0];
                $nombreRolPermisos = $viejo['nombre_rol'];
            }
            registrarBitacora($bitacoraObj, $id_modulo, "Modificó permisos al rol: " . $nombreRolPermisos);
            echo json_encode(['accion' => 'guardar_permisos', 'mensaje' => 'Permisos guardados correctamente.']);
        } else {
            throw new Exception($resultado['codigo'] ?? 'Ocurrió un error al guardar los permisos.');
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

        $respuestaVieja = $obj->procesarDatos(['id' => $_POST['id'], 'accion' => 'buscar']);
        $datosPrevios = '';
        if (isset($respuestaVieja['accion']) && $respuestaVieja['accion'] === 'buscar' && !empty($respuestaVieja['datos'])) {
            $viejo = $respuestaVieja['datos'][0];
            $datosPrevios = json_encode([
                'nombre' => $viejo['nombre_rol'],
                'descripcion' => $viejo['descripcion'] ?? ''
            ]);
        }

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            $nombreRolEliminado = isset($viejo) ? $viejo['nombre_rol'] : $_POST['id'];
            registrarBitacora($bitacoraObj, $id_modulo, "Eliminó el Rol: " . $nombreRolEliminado, $datosPrevios, '');
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

function generar($obj, $id_modulo, $bitacoraObj)
{
    try {
        $validacionesReporte = [];
        $datosFiltro = ['accion' => 'reporte'];

        if (!empty($_POST['nombre'])) {
            $validacionesReporte['nombre'] = ['regla' => '/^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,30}$/', 'mensaje' => 'Nombre inválido.'];
            $datosFiltro['nombre'] = $_POST['nombre'];
        }

        if (!empty($validacionesReporte)) {
            validar_datos($validacionesReporte);
        }

        $respuesta = $obj->procesarDatos($datosFiltro);
        $datos = $respuesta['datos'] ?? [];

        if (empty($datos)) {
            echo json_encode(['accion' => 'error', 'mensaje' => 'No se encontraron registros para hacer el reporte.']);
            exit();
        }

        $nombreVista = 'R_Roles';
        $objG = new GenerarReporte();
        
        $formato = $_POST['formato'] ?? 'pdf';
        if ($formato === 'excel') {
            $reporte = $objG->generarExcel($nombreVista, $datos, 'Roles');
        } else {
            $reporte = $objG->generarPDF($nombreVista, $datos, 'Roles');
        }

        if (isset($reporte['accion']) && $reporte['accion'] === 'reporte') {
            registrarBitacora($bitacoraObj, $id_modulo, "Generó reporte de roles en " . strtoupper($formato), '', '');
        }
        echo json_encode($reporte);
    } catch (Exception $e) {
        logs('Roles', $e->getMessage(), 'Controlador_Generar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}
