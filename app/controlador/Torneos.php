<?php

use App\modelo\ModeloTorneos;

// 1. Cargamos las funciones base
require_once __DIR__ . '/Base.php';

// 2. Configuración del módulo 
$id_modulo = _MD_TORNEOS_;

// 3. Procesar permisos (Retorna el array de permisos)
$permisos = procesarPermisos($id_modulo, 'ingresar_torneos');

// 4. Lógica de despacho (Router interno)
$nombreClaseModelo = 'App\modelo\ModeloTorneos';

if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloTorneos();

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitudTorneos($objModelo, $id_modulo, $bitacora, $permisos);
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

function manejarSolicitudTorneos($obj, $id_modulo, $bitacoraObj, array $permisos): void
{
    try {
        $tokenRecibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!isset($_SESSION['token']) || !hash_equals($_SESSION['token'], $tokenRecibido)) {
            throw new Exception('Error de seguridad: Token inválido o expirado.');
        }

        $accion = isset($_POST['accion']) ? filter_var($_POST['accion'], FILTER_SANITIZE_SPECIAL_CHARS) : '';

        switch ($accion) {
            case 'consultar':
            if (empty($permisos['ingresar_torneos'])) throw new Exception('No tienes permisos para consultar torneos.');
                consultar($obj, $permisos);
                break;
            case 'buscar':
                if (empty($permisos['modificar_torneo'])) throw new Exception('No tienes permisos para buscar/modificar torneos.');
                buscar($obj);
                break;
            case 'incluir':
                if (empty($permisos['registrar_torneo'])) throw new Exception('No tienes permisos para registrar torneos.');
                incluir($obj, $id_modulo, $bitacoraObj);
                break;
            case 'eliminar':
                if (empty($permisos['eliminar_torneo'])) throw new Exception('No tienes permisos para eliminar torneos.');
                eliminar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'modificar':
                if (empty($permisos['modificar_torneo'])) throw new Exception('No tienes permisos para modificar torneos.');
                modificar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'generar':
                if (empty($permisos['generar_torneos'])) throw new Exception('No tienes permisos para generar reportes.');
                generar($obj, $id_modulo, $bitacoraObj);
                break;
            default:
                throw new Exception('Acción no permitida.');
        }
    } catch (Exception $e) {
        logs('Torneos', $e->getMessage(), 'Controlador_ManejarSolicitud');
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

    include(__DIR__ . '/../vista/Torneos.php');
}

function buscar($obj): void
{
    try {
        validar_requeridos(['codigo_torneo']); // Ajustado a la BD

        $datos = [
            'codigo_torneo' => $_POST['codigo_torneo'], // Ajustado a la BD
            'accion' => 'buscar'
        ];

        $resultado = $obj->procesarDatos($datos);
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Torneos', $e->getMessage(), 'Controlador_Buscar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function incluir($obj, $id_modulo, $bitacoraObj): void
{
    try {
        validar_requeridos(['nombre', 'fecha_inicio', 'fecha_fin', 'ubicacion', 'estatus']);

        $datos = [
            'nombre'       => $_POST['nombre'],
            'fecha_inicio' => $_POST['fecha_inicio'],
            'fecha_fin'    => $_POST['fecha_fin'],
            'ubicacion'    => $_POST['ubicacion'],
            'estatus'      => $_POST['estatus'],
            'accion'       => 'incluir'
        ];

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            // Verificador dinámico de eventos (Torneos, etc.)
            require_once __DIR__ . '/../servicios/verificarEvento.php';
            $verificador = new \App\servicios\verificarEvento();
            $verificador->procesar();

            $datos_nuevos_json = json_encode([
                'nombre'       => $_POST['nombre'],
                'fecha_inicio' => $_POST['fecha_inicio'],
                'fecha_fin'    => $_POST['fecha_fin'],
                'ubicacion'    => $_POST['ubicacion'],
                'estatus'      => $_POST['estatus']
            ]);
            registrarBitacora($bitacoraObj, $id_modulo, "Registró el torneo: " . $_POST['nombre'], '', $datos_nuevos_json);

            $resultado = ['accion' => 'incluir', 'mensaje' => 'Torneo registrado exitosamente.'];
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                'Ya existe un torneo registrado con este nombre.' => $resultado['codigo'],
                default => 'Ocurrió un error inesperado en el registro del torneo.'
            };
        }

        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Torneos', $e->getMessage(), 'Controlador_Incluir');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function modificar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        // Se cambió 'id' por 'codigo_torneo'
        validar_requeridos(['codigo_torneo', 'nombre', 'fecha_inicio', 'fecha_fin', 'ubicacion', 'estatus']);

        $datos = [
            'codigo_torneo' => $_POST['codigo_torneo'], // Ajustado a la BD
            'nombre'       => $_POST['nombre'],
            'fecha_inicio' => $_POST['fecha_inicio'],
            'fecha_fin'    => $_POST['fecha_fin'],
            'ubicacion'    => $_POST['ubicacion'],
            'estatus'      => $_POST['estatus'],
            'accion'       => 'modificar'
        ];

        $consultar_datos_previos = $obj->Buscar($_POST['codigo_torneo']);
        $datos_previos = $consultar_datos_previos['datos'][0] ?? null;
        if (isset($datos_previos['codigo_torneo'])) unset($datos_previos['codigo_torneo']);
        $datos_previos_json = json_encode($datos_previos);

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            // Verificador dinámico de eventos (Torneos, etc.)
            require_once __DIR__ . '/../servicios/verificarEvento.php';
            $verificador = new \App\servicios\verificarEvento();
            $verificador->procesar();

            $datos_nuevos_json = json_encode([
                'nombre'       => $_POST['nombre'],
                'fecha_inicio' => $_POST['fecha_inicio'],
                'fecha_fin'    => $_POST['fecha_fin'],
                'ubicacion'    => $_POST['ubicacion'],
                'estatus'      => $_POST['estatus']
            ]);
            registrarBitacora($bitacoraObj, $id_modulo, "Modificó el torneo: " . $_POST['nombre'], $datos_previos_json, $datos_nuevos_json);

            $resultado = ['accion' => 'modificar', 'mensaje' => 'Torneo modificado exitosamente.'];
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                'Ya existe otro torneo registrado con este nombre.' => $resultado['codigo'],
                default => 'Ocurrió un error inesperado al modificar el torneo.'
            };
        }

        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Torneos', $e->getMessage(), 'Controlador_Modificar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function eliminar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        validar_requeridos(['codigo_torneo']); // Ajustado a la BD

        $datos = [
            'codigo_torneo' => $_POST['codigo_torneo'], // Ajustado a la BD
            'accion' => 'eliminar'
        ];

        $consultar_datos_previos = $obj->Buscar($_POST['codigo_torneo']);
        $datos_previos = $consultar_datos_previos['datos'][0] ?? null;
        if (isset($datos_previos['codigo_torneo'])) unset($datos_previos['codigo_torneo']);
        $datos_previos_json = json_encode($datos_previos);

        $resultado = $obj->procesarDatos($datos);
        
        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            $nombre_torneo = $datos_previos['nombre'] ?? $_POST['codigo_torneo'];
            registrarBitacora($bitacoraObj, $id_modulo, "Eliminó el torneo: " . $nombre_torneo, $datos_previos_json, '');
            $resultado = ['accion' => 'eliminar', 'mensaje' => 'Torneo eliminado exitosamente.'];
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                'El torneo no existe.' => $resultado['codigo'],
                'No se puede eliminar: el torneo tiene equipos o atletas asociados.' => $resultado['codigo'],
                default => 'Ocurrió un error inesperado al eliminar el torneo.'
            };
        }
        
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Torneos', $e->getMessage(), 'Controlador_Eliminar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function generar($obj, $id_modulo, $bitacoraObj)
{
    try {
        $datosFiltro = ['accion' => 'generar'];
        
        if (!empty($_POST['fecha_inicio'])) {
            $datosFiltro['fecha_inicio'] = $_POST['fecha_inicio'];
        }
        if (!empty($_POST['fecha_fin'])) {
            $datosFiltro['fecha_fin'] = $_POST['fecha_fin'];
        }
        if (!empty($_POST['estatus'])) {
            $datosFiltro['estatus'] = $_POST['estatus'];
        }

        $respuesta = $obj->procesarDatos($datosFiltro);
        $datos = $respuesta['datos'] ?? [];
        if (empty($datos)) {
            echo json_encode(['accion' => 'error', 'mensaje' => 'No se encontraron torneos para hacer el reporte.']);
            exit();
        }

        require_once __DIR__ . '/../servicios/GenerarReporte.php';
        $objG = new \App\servicios\GenerarReporte();
        
        $formato = $_POST['formato'] ?? 'pdf';
        if ($formato === 'excel') {
            $reporte = $objG->generarExcel('R_Torneos', $datos, 'Torneos');
        } else {
            $reporte = $objG->generarPDF('R_Torneos', $datos, 'Torneos');
        }

        if (isset($reporte['accion']) && $reporte['accion'] === 'reporte') {
            registrarBitacora($bitacoraObj, $id_modulo, "Generó reporte de torneos en " . strtoupper($formato));
        }
        echo json_encode($reporte);
    } catch (Exception $e) {
        logs('Torneos', $e->getMessage(), 'Controlador_Generar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}