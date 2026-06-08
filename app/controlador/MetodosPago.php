<?php

use App\modelo\ModeloMetodosPago;

// 1. Cargamos las funciones base
require_once __DIR__ . '/Base.php';

// 2. Configuración del módulo
$id_modulo = _MD_METODOS_;

// 3. Procesar permisos (Retorna el array de permisos)
$permisos = procesarPermisos($id_modulo, $bitacora);

// 4. Lógica de despacho (Router interno)
$nombreClaseModelo = 'App\modelo\ModeloMetodosPago';

if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloMetodosPago();

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitudMetodos_Pagos($objModelo, $id_modulo, $bitacora, $permisos);
} else {
    registrarBitacora($bitacora , $id_modulo, 'Ingreso al Modulo');
    $respuesta = $objModelo->Consultar();
    $registro = $respuesta['datos'] ?? [];
    $variables = ['registro' => $registro, 'permisos' => $permisos];
    cargarVista($pagina, $variables);
}

/**
 * --- FUNCIONES DEL CONTROLADOR ---
 */

function manejarSolicitudMetodos_Pagos($obj, $id_modulo, $bitacoraObj, array $permisos): void
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
                if (!$permisos['ingresar']) throw new Exception('No tienes permisos para consultar el metodo de pago.');
                consultar($obj, $permisos);
                break;
            case 'buscar':
                if (!$permisos['modificar']) throw new Exception('No tienes permisos para modificar el metodo de pago.');
                buscar($obj);
                break;
            case 'incluir':
                if (!$permisos['registrar']) throw new Exception('No tienes permisos para registrar el metodo de pago.');
                incluir($obj, $id_modulo, $bitacoraObj);
                break;
            case 'eliminar':
                if (!$permisos['eliminar']) throw new Exception('No tienes permisos para eliminar el metodo de pago.');
                eliminar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'modificar':
                if (!$permisos['modificar']) throw new Exception('No tienes permisos para modificar el metodo de pago.');
                modificar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'bloquear':
                if (!$permisos['otros']) throw new Exception('No tienes permisos para bloquear Metodos de pago.');
                bloquear($obj, $id_modulo, $bitacoraObj);
                break;
            default:
                throw new Exception('Acción no permitida.');
        }
    } catch (Exception $e) {
        logs('Metodos_Pago', $e->getMessage(), 'Controlador_ManejarSolicitud');
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

    include(__DIR__ . '/../vista/MetodosPago.php');
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
        logs('Metodos_Pago', $e->getMessage(), 'Controlador_Buscar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function incluir($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $validaciones = [
            'nombre'         => ['regla' => '/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]{3,30}$/', 'mensaje' => 'Nombre inválido.'],
            'nec_referencia' => ['regla' => '/^[1-2]+$/', 'mensaje' => 'Referencia inválida.']
        ];

        validar_datos($validaciones);

        $datos = [
            'nombre'         => $_POST['nombre'],
            'nec_referencia' => $_POST['nec_referencia']
        ];
        $datos['accion'] = 'incluir';

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {

            registrarBitacora($bitacoraObj, $id_modulo, "Registro al metodo: " . $_POST['nombre']);
            $resultado = array('accion' => 'incluir', 'mensaje' => 'Metodo registrado exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {

            $resultado['mensaje'] = match ($resultado['codigo']) {
                DUPLICATE_NAME => 'Ya existe un metodo de pago registrado con este nombre.',
                DB_CONNECTION      => 'Ocurrio un error al conectarse con la base de datos.',
                default          => 'Ocurrió un error inesperado en la modificacion.'
            };
        }
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Metodos_Pago', $e->getMessage(), 'Controlador_Incluir');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function modificar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $validaciones = [
            'id'             => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.'],
            'nombre'         => ['regla' => '/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]{3,30}$/', 'mensaje' => 'Nombre inválido.'],
            'nec_referencia' => ['regla' => '/^[1-2]+$/', 'mensaje' => 'Referencia inválida.']
        ];

        validar_datos($validaciones);

        $datos = [
            'id'             => $_POST['id'],
            'nombre'         => $_POST['nombre'],
            'nec_referencia' => $_POST['nec_referencia']
        ];
        $datos['accion'] = 'modificar';

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {

            registrarBitacora($bitacoraObj, $id_modulo, "Modifico al metodo: " . $_POST['nombre']);
            $resultado = array('accion' => 'modificar', 'mensaje' => 'Metodo modificado exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {

            $resultado['mensaje'] = match ($resultado['codigo']) {
                DUPLICATE_NAME => 'Ya existe un metodo de pago registrado con este nombre.',
                DB_CONNECTION      => 'Ocurrio un error al conectarse con la base de datos.',
                default          => 'Ocurrió un error inesperado en la modificacion.'
            };
        }

        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Metodos_Pago', $e->getMessage(), 'Controlador');
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

            registrarBitacora($bitacoraObj, $id_modulo, "Elimino al metodo de pago: " . $_POST['id']);
            $resultado = array('accion' => 'eliminar', 'mensaje' => 'Método de pago eliminado exitosamente.'); // CORREGIDO AQUÍ

        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {

            $resultado['mensaje'] = match ($resultado['codigo']) {
                INVALID_ID => 'El metodo de pago no existe.',
                ASSOCIATES  => 'El metodo de pago tiene pagos asociados.',
                DB_CONNECTION      => 'Ocurrio un error al conectarse con la base de datos.',
                default          => 'Ocurrió un error inesperado en la eliminacion.'
            };
        }
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Metodos_Pago', $e->getMessage(), 'Controlador_Eliminar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function bloquear($obj, $id_modulo, $bitacoraObj): void
{
    try {
        validar_datos([
            'id' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.'],
            'bloqueo' => ['regla' => '/^[1-2]+$/', 'mensaje' => 'Error interno de bloqueo.']
        ]);

        $datos = [
            'id' => $_POST['id'],
            'bloqueo' => $_POST['bloqueo'],
            'accion' => 'bloquear'
        ];

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            $nuevoEstado = ($_POST['bloqueo'] == 1) ? 2 : 1;
            $mensajeExito = ($nuevoEstado == 2) ? "Metodo bloqueado exitosamente." : "Metodo desbloqueado exitosamente.";
            $mensajeBitacora = ($nuevoEstado == 2) ? "Bloqueo el metodo: " : "Desbloqueo el metodo: ";
            registrarBitacora($bitacoraObj, $id_modulo, $mensajeBitacora . $_POST['id']);
            $resultado = array('accion' => 'bloquear', 'mensaje' => $mensajeExito);
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {

            $resultado['mensaje'] = match ($resultado['codigo']) {
                INVALID_ID => 'El metodo que intenta bloquear ya no existe.',
                DB_CONNECTION      => 'Ocurrio un error al conectarse con la base de datos.',
                default    => 'No se pudo completar la operación de bloqueo.'
            };
        }

        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Metodos_Pago', $e->getMessage(), 'Controlador_Bloquear');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}
