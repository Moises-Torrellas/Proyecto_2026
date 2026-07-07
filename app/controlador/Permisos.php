<?php

use App\modelo\ModeloPermisos;
use App\modelo\ModeloModulos;

// 1. Cargamos las funciones base
require_once __DIR__ . '/Base.php';

// 2. Configuración del módulo
$id_modulo = _MD_PERMISOS_;

// 3. Procesar permisos (Ahora retorna un array en lugar de usar global)
$permisos = procesarPermisos($id_modulo, 'ingresar_permisos');

// 4. Lógica de despacho (Router interno)
$nombreClaseModelo = 'App\modelo\ModeloPermisos';

if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objPermisos = new ModeloPermisos();

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitud($objPermisos, $id_modulo, $bitacora, $permisos);
} else {
    registrarBitacora($bitacora, $id_modulo, 'Ingreso al Modulo');
    $respuesta = $objPermisos->Consultar();

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

function manejarSolicitud($obj, $id_modulo, $bitacoraObj, array $permisos): void
{
    try {
        $tokenRecibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!isset($_SESSION['token']) || !hash_equals($_SESSION['token'], $tokenRecibido)) {
            throw new Exception('Error de seguridad: Token inválido o expirado.');
        }

        $accion = isset($_POST['accion']) ? filter_var($_POST['accion'], FILTER_SANITIZE_SPECIAL_CHARS) : '';

        switch ($accion) {
            case 'consultar':
                if (empty($permisos['ingresar_permisos'])) throw new Exception('No tiene permisos para consultar .');
                consultarData($obj, $permisos);
                break;
            case 'consultarModulos':
                if (empty($permisos['ingresar_permisos'])) throw new Exception('No tiene permisos para consultar .');
                consultarModulos();
                break;
            case 'buscar':
                if (empty($permisos['modificar_permisos'])) throw new Exception('No tiene permisos para buscar .');
                buscarData($obj);
                break;
            case 'incluir':
                if (empty($permisos['registrar_permisos'])) throw new Exception('No tiene permisos para incluir .');
                incluirData($obj, $id_modulo, $bitacoraObj);
                break;
            case 'modificar':
                if (empty($permisos['modificar_permisos'])) throw new Exception('No tiene permisos para modificar .');
                modificarData($obj, $id_modulo, $bitacoraObj);
                break;
            case 'bloquear':
                if (empty($permisos['bloquear_permisos'])) throw new Exception('No tiene permisos para bloquear .');
                bloquear($obj, $id_modulo, $bitacoraObj);
                break;
            default:
                throw new Exception('Acción no reconocida.');
        }
    } catch (Exception $e) {
        logs('Roles', $e->getMessage(), 'Controlador_ManejarSolicitud');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function consultarData($obj, $permisos): void
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
        include(__DIR__ . '/../vista/Permisos.php');
    } catch (throwable $e) {
        logs('Representantes', $e->getMessage(), 'Controlador_Consultar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function ConsultarModulos(): void
{
    try {
        $modelomodulos = new ModeloModulos();

        $respmodulos = $modelomodulos->Consultar();

        $modulosDatos = isset($respmodulos['datos']) ? $respmodulos['datos'] : [];

        echo json_encode([
            'accion'  => 'consultarModulos',
            'modulos' => $modulosDatos
        ]);
    } catch (Exception $e) {
        logs('Permisos', $e->getMessage(), 'Controlador_ConsultarModulos');
        echo json_encode([
            'accion' => 'error',
            'mensaje' => 'Error en la carga masiva de datos: ' . $e->getMessage()
        ]);
    }
}

function buscarData($obj): void
{
    try {
        validar_requeridos(['id']);
        $resultado = $obj->procesarDatos(['id' => $_POST['id'], 'accion' => 'buscar']);
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Permisos', $e->getMessage(), 'Controlador_Buscar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}


function incluirData($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $requeridos = ['nombre', 'modulo', 'clave'];
        if (!empty($_POST['descripcion'])) {
            $requeridos[] = 'descripcion';
        }
        validar_requeridos($requeridos);

        $datos = [
            'nombre' => $_POST['nombre'],
            'descripcion'   => $_POST['descripcion'],
            'modulo' => $_POST['modulo'],
            'clave' => $_POST['clave'],
            'accion' => 'incluir'
        ];

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            $resultado = array('accion' => 'incluir', 'mensaje' => 'Permiso registrado exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                DUPLICATE_NAME => 'Ya existe un permiso registrado con este nombre.',
                ASSOCIATES  => 'Uno de Los modulos que intenta registrar no existe o esta restringido.',
                default          => 'Ocurrió un error inesperado en el registro del permiso.'
            };
        }
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Permisos', $e->getMessage(), 'Controlador_Incluir');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function modificarData($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $requeridos = ['nombre', 'id'];
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

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            $resultado = array('accion' => 'modificar', 'mensaje' => 'Permiso modificado exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                DUPLICATE_NAME => 'Ya existe un permiso registrado con este nombre.',
                ASSOCIATES  => 'Uno de Los modulos que intenta registrar no existe o esta restringido.',
                default          => 'Ocurrió un error inesperado en el registro del rol.'
            };
        }
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Permisos', $e->getMessage(), 'Controlador_Modificar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function bloquear($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $requeridos = ['id', 'bloqueo'];
        validar_requeridos($requeridos);

        $datos = [
            'id' => $_POST['id'],
            'bloqueo' => $_POST['bloqueo'],
            'accion' => 'bloquear'
        ];

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            $nuevoEstado = ($_POST['bloqueo'] == 1) ? 2 : 1;
            $mensajeExito = ($nuevoEstado == 2) ? "Permiso bloqueada exitosamente." : "Permiso desbloqueada exitosamente.";
            $resultado = array('accion' => 'bloquear', 'mensaje' => $mensajeExito);
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                DUPLICATE_NAME => 'Ya existe un permiso registrado con este nombre.',
                ASSOCIATES  => 'Uno de Los modulos que intenta registrar no existe o esta restringido.',
                default          => 'Ocurrió un error inesperado en el registro del rol.'
            };
        }
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Permisos', $e->getMessage(), 'Controlador_bloquear');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}
