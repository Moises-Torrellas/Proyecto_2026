<?php

use App\modelo\ModeloBitacora;

// 1. Cargamos las funciones base
require_once __DIR__ . '/Base.php';

// 2. Configuración del módulo
$id_modulo = _MD_BITACORA_;

// 3. Procesar permisos (esto llena la variable global $permisosGenerales)
$permisos = procesarPermisos($id_modulo, '');

// 4. Lógica de despacho (Router interno)
$nombreClaseModelo = 'App\modelo\ModeloBitacora';

if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloBitacora();

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitud($objModelo, $id_modulo, $bitacora, $permisos);
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

function manejarSolicitud($obj, $id_modulo, $bitacoraObj, $permisos): void
{
    // Centralizamos la variable global de permisos aquí

    try {
        $tokenRecibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!isset($_SESSION['token']) || !hash_equals($_SESSION['token'], $tokenRecibido)) {
            throw new Exception('Error de seguridad: Token inválido o expirado.');
        }

        $accion = isset($_POST['accion']) ? filter_var($_POST['accion'], FILTER_SANITIZE_SPECIAL_CHARS) : '';

        // Validamos permisos antes de ejecutar las funciones
        switch ($accion) {
            case 'consultar':
                consultar($obj);
                break;
            case 'generar':
                if (empty($permisos['generar_bitacora'])) throw new Exception('No tiene permisos para generar reportes.');
                generar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'consultar_usuarios':
                consultarUsuarios($obj);
                break;
            case 'consultar_modulos':
                consultarModulos($obj);
                break;
            default:
                throw new Exception('Acción no permitida.');
        }
    } catch (Exception $e) {
        logs('Bitacora', $e->getMessage(), 'Controlador_ManejarSolicitud');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function consultar($obj): void
{
  try {
        $filtro['filtro'] = $_POST['filtro'] ?? '';
        $filtro['offset'] = isset($_POST['offset']) ? (int)$_POST['offset'] : 0;
        $filtro['limit'] = 100;
        $respuesta = $obj->Consultar($filtro);

        if (isset($respuesta['accion']) && $respuesta['accion'] === 'error') {
            $mensajeError = ($respuesta['mensaje'] == DB_CONNECTION) ? 'Error al conectar con la base de datos.' : $respuesta['mensaje'];
            echo json_encode(['accion' => 'error', 'mensaje' => $mensajeError]);
            return;
        }

        $registro = $respuesta['datos'] ?? [];
        $solo_lista = true;
        include(__DIR__ . '/../vista/Bitacora.php');
    } catch (throwable $e) {
        logs('bitacora', $e->getMessage(), 'Controlador_Consultar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function consultarUsuarios($obj): void
{
    $respuesta = $obj->consultarUsuarios();
    if (isset($respuesta['accion']) && $respuesta['accion'] == 'error') {
        $respuesta['mensaje'] = 'Error al listar los usuarios';
    }
    echo json_encode($respuesta);
}

function consultarModulos($obj): void
{
    $respuesta = $obj->consultarModulos();
    if (isset($respuesta['accion']) && $respuesta['accion'] == 'error') {
        $respuesta['mensaje'] = 'Error al listar los modulos';
    }
    echo json_encode($respuesta);
}

function generar($obj, $id_modulo, $bitacoraObj)
{
    try {
        $validacionesReporte = [];
        $datosFiltro = ['accion' => 'reporte'];

        if (!empty($_POST['filtro_modulo'])) {
            $validacionesReporte['filtro_modulo'] = ['regla' => '/^[0-9]+$/', 'mensaje' => 'Módulo inválido.'];
            $datosFiltro['id_modulo'] = $_POST['filtro_modulo'];
        }
        if (!empty($_POST['filtro_usuario'])) {
            $validacionesReporte['filtro_usuario'] = ['regla' => '/^[0-9]+$/', 'mensaje' => 'Usuario inválido.'];
            $datosFiltro['idUsuario'] = $_POST['filtro_usuario'];
        }
        if (!empty($_POST['fecha_inicio']) && !empty($_POST['fecha_fin'])) {
            $validacionesReporte['fecha_inicio'] = ['regla' => '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', 'mensaje' => 'Fecha inicio inválida.'];
            $validacionesReporte['fecha_fin'] = ['regla' => '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', 'mensaje' => 'Fecha fin inválida.'];
            $datosFiltro['fecha_inicio'] = $_POST['fecha_inicio'];
            $datosFiltro['fecha_fin'] = $_POST['fecha_fin'];
            
            if (strtotime($datosFiltro['fecha_inicio']) > strtotime($datosFiltro['fecha_fin'])) {
                throw new Exception("La fecha de inicio no puede ser mayor a la fecha de fin.");
            }
        } else if (!empty($_POST['fecha_inicio']) || !empty($_POST['fecha_fin'])) {
            throw new Exception("Debe seleccionar ambas fechas para filtrar por rango.");
        }

        if (!empty($validacionesReporte)) {
            validar_datos($validacionesReporte);
        }

        $formato = $_POST['formato'] ?? 'pdf';
        
        $respuesta = $obj->Consultar($datosFiltro);

        if (isset($respuesta['accion']) && $respuesta['accion'] === 'error') {
            throw new Exception($respuesta['mensaje']);
        }

        $datosReporte = $respuesta['datos'] ?? [];

        if (empty($datosReporte)) {
            throw new Exception("No hay registros que coincidan con los filtros seleccionados.");
        }

        require_once __DIR__ . '/../servicios/GenerarReporte.php';
        $reporte = new App\servicios\GenerarReporte();
        
        $parametrosVista = [
            'titulo' => 'Reporte de Bitácora',
            'datos' => $datosReporte,
            'filtros' => [
                'modulo' => !empty($_POST['filtro_modulo']) ? 'Filtrado' : 'Todos',
                'usuario' => !empty($_POST['filtro_usuario']) ? 'Filtrado' : 'Todos',
                'fecha_inicio' => $_POST['fecha_inicio'] ?? 'N/A',
                'fecha_fin' => $_POST['fecha_fin'] ?? 'N/A'
            ]
        ];

        // Definimos la orientación horizontal (landscape) porque la tabla de bitácora es ancha
        if ($formato === 'pdf') {
            $resultadoReporte = $reporte->generarPDF('R_Bitacora', $datosReporte, 'Bitacora', $parametrosVista, 'landscape');
        } else if ($formato === 'excel') {
            $resultadoReporte = $reporte->generarExcel('R_Bitacora', $datosReporte, 'Bitacora', $parametrosVista);
        } else {
            throw new Exception("Formato de reporte no válido.");
        }
        
        if (isset($resultadoReporte['accion']) && $resultadoReporte['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Generó un reporte de Bitácora en formato " . strtoupper($formato));
        }

        echo json_encode($resultadoReporte);

    } catch (Exception $e) {
        logs('Bitacora', $e->getMessage(), 'Controlador_Generar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}