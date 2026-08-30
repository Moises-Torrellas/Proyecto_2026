<?php

use App\modelo\ModeloMetodosPago;
use App\servicios\GenerarReporte;
// Si usas Dompdf con Composer, asegúrate de tener el autoload requerido aquí o en tu Base.php
// require_once __DIR__ . '/../../vendor/autoload.php'; 

// 1. Cargamos las funciones base
require_once __DIR__ . '/Base.php';

// 2. Configuración del módulo
$id_modulo = _MD_METODOS_;

// 3. Procesar permisos (Retorna el array de permisos)
$permisos = procesarPermisos($id_modulo, 'ingresar_metodop');

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
                if (empty($permisos['ingresar_metodop'])) throw new Exception('No tienes permisos para consultar el metodo de pago.');
                consultar($obj, $permisos);
                break;
            case 'buscar':
                if (empty($permisos['modificar_metodop'])) throw new Exception('No tienes permisos para modificar el metodo de pago.');
                buscar($obj);
                break;
            case 'incluir':
                if (empty($permisos['registrar_metodosp'])) throw new Exception('No tienes permisos para registrar el metodo de pago.');
                incluir($obj, $id_modulo, $bitacoraObj);
                break;
            case 'eliminar':
                if (empty($permisos['eliminar_metodop'])) throw new Exception('No tienes permisos para eliminar el metodo de pago.');
                eliminar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'modificar':
                if (empty($permisos['modificar_metodop'])) throw new Exception('No tienes permisos para modificar el metodo de pago.');
                modificar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'bloquear':
                if (empty($permisos['bloquear_metodop'])) throw new Exception('No tienes permisos para bloquear Metodos de pago.');
                bloquear($obj, $id_modulo, $bitacoraObj);
                break;
            case 'generar':
                if (empty($permisos['generar_metodop'])) throw new Exception('No tienes permisos para generar reportes.');
                generar($obj, $id_modulo, $bitacoraObj);
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
            $datos_nuevos_json = json_encode([
                'nombre' => $_POST['nombre'],
                'nec_referencia' => $_POST['nec_referencia']
            ]);
            registrarBitacora($bitacoraObj, $id_modulo, "Registro el método de pago: " . $_POST['nombre'], '', $datos_nuevos_json);
            $resultado = array('accion' => 'incluir', 'mensaje' => 'Método de pago registrado exitosamente.');
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

        $consultar_datos_previos = $obj->Buscar($_POST['id']);
        $metodo_previo = $consultar_datos_previos['datos'][0] ?? null;
        if (isset($metodo_previo['codigo_metodo'])) unset($metodo_previo['codigo_metodo']);
        $datos_previos_json = json_encode($metodo_previo);

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            $datos_nuevos_json = json_encode([
                'nombre' => $_POST['nombre'],
                'nec_referencia' => $_POST['nec_referencia']
            ]);
            registrarBitacora($bitacoraObj, $id_modulo, "Modificó el método de pago: " . $_POST['nombre'], $datos_previos_json, $datos_nuevos_json);
            $resultado = array('accion' => 'modificar', 'mensaje' => 'Método de pago modificado exitosamente.');
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

        $consultar_datos_previos = $obj->Buscar($_POST['id']);
        $metodo_previo = $consultar_datos_previos['datos'][0] ?? null;
        if (isset($metodo_previo['codigo_metodo'])) unset($metodo_previo['codigo_metodo']);
        $datos_previos_json = json_encode($metodo_previo);

        $resultado = $obj->procesarDatos($datos);
        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            $nombre_metodo = $metodo_previo['nombre'] ?? $_POST['id'];
            registrarBitacora($bitacoraObj, $id_modulo, "Eliminó el método de pago: " . $nombre_metodo, $datos_previos_json, '');
            $resultado = array('accion' => 'eliminar', 'mensaje' => 'Método de pago eliminado exitosamente.'); 

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

function generar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $filtro = [];
        if (!empty($_POST['nombre'])) {
            $filtro['filtro'] = trim($_POST['nombre']);
        }
        
        $respuesta = $obj->Consultar($filtro);
        $datos = $respuesta['datos'] ?? [];

        if (empty($datos)) {
            echo json_encode([
                'accion' => 'error', 
                'mensaje' => 'No se encontraron registros con ese nombre. El reporte fue cancelado.'
            ]);
            return; 
        }

        $nombreVista = 'R_MetodosPago';
        $objG = new GenerarReporte();
        
        $formato = $_POST['formato'] ?? 'pdf';
        if ($formato === 'excel') {
            $reporte = $objG->generarExcel($nombreVista, $datos, 'Métodos de Pago');
        } else {
            $reporte = $objG->generarPDF($nombreVista, $datos, 'Métodos de Pago');
        }

        if (isset($reporte['accion']) && $reporte['accion'] === 'reporte') {
            registrarBitacora($bitacoraObj, $id_modulo, "Generó reporte de Métodos de Pago en " . strtoupper($formato));
        }

        echo json_encode($reporte);

    } catch (Exception $e) {
        logs('Metodos_Pago', $e->getMessage(), 'Controlador_Generar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}