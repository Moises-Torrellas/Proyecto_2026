<?php

use App\modelo\ModeloAsignaciones;
use App\modelo\ModeloAtletas;
use App\modelo\ModeloArticulosInventario;
use App\servicios\GenerarReporte; 
require_once __DIR__ . '/Base.php';

$id_modulo = _MD_ASIGNACIONES_; 
$permisos = procesarPermisos($id_modulo, 'ingresar_asignaciones');

$nombreClaseModelo = 'App\modelo\ModeloAsignaciones';
if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objAsignaciones = new ModeloAsignaciones();

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitudAsignacion($objAsignaciones, $id_modulo, $bitacora ?? null, $permisos);
} else {
    // La vista se carga de manera normal sin bitácora por seguridad
    $respuesta = $objAsignaciones->ConsultarAsignaciones(); 
    
    $error_bd = '';
    if (isset($respuesta['accion']) && $respuesta['accion'] === 'error') {
        $error_bd = !empty($respuesta['mensaje']) ? $respuesta['mensaje'] : 'Error al conectar con la base de datos.';
    }

    $registro = $respuesta['datos'] ?? [];
    
    $variables = ['registro' => $registro, 'permisos' => $permisos, 'error_bd' => $error_bd];
    cargarVista($pagina, $variables);
}

function manejarSolicitudAsignacion($obj, $id_modulo, $bitacoraObj, array $permisos): void
{
    try {
        $tokenRecibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!isset($_SESSION['token']) || !hash_equals($_SESSION['token'], $tokenRecibido)) throw new Exception('Error de seguridad.');

        $accion = filter_var($_POST['accion'], FILTER_SANITIZE_SPECIAL_CHARS);

        switch ($accion) {
            case 'consultar':
                if (empty($permisos['ingresar_asignaciones'])) throw new Exception('Sin permisos.');
                consultar($obj, $permisos);
                break;
            case 'MultiConsulta':
                if (empty($permisos['ingresar_asignaciones'])) throw new Exception('Sin permisos.');
                MultiConsulta();
                break;
            case 'incluir':
                if (empty($permisos['registrar_asignacion'])) throw new Exception('Sin permisos.');
                incluir($obj, $id_modulo, $bitacoraObj);
                break;
            case 'modificar':
                if (empty($permisos['modificar_asignacion'])) throw new Exception('Sin permisos.');
                modificar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'anular':
                if (empty($permisos['anular_asignacion'])) throw new Exception('Sin permisos.');
                anular($obj, $id_modulo, $bitacoraObj);
                break;
            case 'generar': 
                if (empty($permisos['generar_asignaciones'])) throw new Exception('Sin permisos para generar reportes.');
                generar($obj, $id_modulo, $bitacoraObj);
                break;
            default:
                throw new Exception('Acción no permitida.');
        }
    } catch (Exception $e) {
        logs('Asignaciones', $e->getMessage(), 'Controlador_ManejarSolicitud');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function consultar($obj, $permisos): void {
    $datos = ['accion' => 'consultar', 'filtro' => $_POST['filtro'] ?? ''];
    $respuesta = $obj->ProcesarDatos($datos);
    $registro = $respuesta['datos'] ?? []; 
    $solo_lista = true;
    include (__DIR__.'/../vista/Asignaciones.php');
}

function MultiConsulta(): void {
    try {
        $modeloAtletas = new ModeloAtletas();
        $modeloArticulos = new ModeloArticulosInventario();
        
        $respAtletas = $modeloAtletas->ConsultarAtletas(); 
        $respEquip = $modeloArticulos->ConsultarArticulosLibres();

        echo json_encode([
            'accion'  => 'MultiConsulta',
            'atletas' => $respAtletas['datos'] ?? [],
            'equipos' => $respEquip['datos'] ?? []
        ]);
    } catch (Exception $e) {
        echo json_encode(['accion' => 'error', 'codigo' => DB_CONNECTION]);
    }
}

function incluir($obj, $id_modulo, $bitacoraObj): void {
    try {
        validar_requeridos(['codigo_atleta', 'codigo_articulo', 'fecha_asignacion']);

        $datos = [
            'accion'           => 'incluir', 
            'codigo_atleta'    => $_POST['codigo_atleta'], 
            'codigo_articulo'  => $_POST['codigo_articulo'], 
            'fecha_asignacion' => $_POST['fecha_asignacion']
        ];
        
        $obj->setArticulos(new ModeloArticulosInventario());
        
        $resultado = $obj->ProcesarDatos($datos);
        
        $datos_previos = '';
        $datos_nuevos = $resultado['datos_nuevos'] ?? '';

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Registró asignación del artículo ID: " . $datos['codigo_articulo'] . " al atleta ID: " . $datos['codigo_atleta'], $datos_previos, $datos_nuevos);
            $resultado = array('accion' => 'exito', 'mensaje' => 'Asignación procesada exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                VALIDATION    => 'El artículo seleccionado ya no está disponible.',
                DB_CONNECTION => 'Ocurrió un error al conectarse con la base de datos.',
                default       => 'Ocurrió un error inesperado al procesar la asignación.'
            };
            registrarBitacora($bitacoraObj, $id_modulo, "Falló al registrar asignación: " . $resultado['mensaje'], $datos_previos, $datos_nuevos);
        }
        echo json_encode($resultado);
    } catch (Exception $e) { 
        logs('Asignaciones', $e->getMessage(), 'Controlador_Incluir');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]); 
    }
}

function modificar($obj, $id_modulo, $bitacoraObj): void {
    try {
        validar_requeridos(['id_asignacion', 'codigo_atleta', 'codigo_articulo', 'fecha_asignacion']);

        $datos = [
            'accion'           => 'modificar', 
            'id_asignacion'    => $_POST['id_asignacion'], 
            'codigo_atleta'    => $_POST['codigo_atleta'], 
            'codigo_articulo'  => $_POST['codigo_articulo'], 
            'fecha_asignacion' => $_POST['fecha_asignacion']
        ];
        
        $obj->setArticulos(new ModeloArticulosInventario());
        
        // BITÁCORA: Capturamos el antes y el después
        $consultar_datos_previos = $obj->Buscar($_POST['id_asignacion']);
        $resultado = $obj->ProcesarDatos($datos);
        
        $datos_previos = json_encode($consultar_datos_previos['datos'][0] ?? []);
        $datos_nuevos = $resultado['datos_nuevos'] ?? '';

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Modificó asignación ID: " . $datos['id_asignacion'], $datos_previos, $datos_nuevos);
            $resultado = array('accion' => 'exito', 'mensaje' => 'Asignación modificada exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                INVALID_ID    => 'La asignación original no fue encontrada.',
                'ERR_ESTATUS' => 'No puede modificar una asignación que ya ha sido devuelta o anulada.',
                VALIDATION    => 'El nuevo artículo seleccionado no está disponible.',
                DB_CONNECTION => 'Ocurrió un error al conectarse con la base de datos.',
                default       => 'Ocurrió un error inesperado al modificar.'
            };
            registrarBitacora($bitacoraObj, $id_modulo, "Falló al modificar asignación ID: " . $datos['id_asignacion'] . " - " . $resultado['mensaje'], $datos_previos, $datos_nuevos);
        }
        echo json_encode($resultado);
    } catch (Exception $e) { 
        logs('Asignaciones', $e->getMessage(), 'Controlador_Modificar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]); 
    }
}

function anular($obj, $id_modulo, $bitacoraObj): void {
    try {
        validar_requeridos(['id_asignacion', 'codigo_articulo']);

        $datos = [
            'accion'          => 'anular', 
            'id_asignacion'   => $_POST['id_asignacion'], 
            'codigo_articulo' => $_POST['codigo_articulo']
        ];
        
        $obj->setArticulos(new ModeloArticulosInventario());
        
        // BITÁCORA: Capturamos los datos antes de anular
        $consultar_datos_previos = $obj->Buscar($_POST['id_asignacion']);
        $resultado = $obj->ProcesarDatos($datos);
        
        $asignacion = $consultar_datos_previos['datos'][0] ?? null;
        $datos_previos_json = json_encode($asignacion);
        $datos_nuevos = '';

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Anuló asignación ID: " . $datos['id_asignacion'], $datos_previos_json, $datos_nuevos);
            $resultado = array('accion' => 'exito', 'mensaje' => 'Asignación anulada exitosamente.'); 
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                DB_CONNECTION => 'Ocurrió un error al conectarse con la base de datos.',
                default       => 'Ocurrió un error inesperado al anular la asignación.'
            };
            registrarBitacora($bitacoraObj, $id_modulo, "Falló al anular asignación ID: " . $datos['id_asignacion'] . " - " . $resultado['mensaje'], $datos_previos_json, $datos_nuevos);
        }
        echo json_encode($resultado);
    } catch (Exception $e) { 
        logs('Asignaciones', $e->getMessage(), 'Controlador_Anular');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]); 
    }
}

function generar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $validacionesReporte = [];
        $datosFiltro = [
            'accion' => 'generar',
            'anulados' => $_POST['anulados'] ?? 0,
            'filtro' => $_POST['filtro'] ?? ''
        ]; 

        // Bug corregido: se usa el id correcto del input HTML -> fecha_asignacion
        if (!empty($_POST['fecha_asignacion'])) {
            $validacionesReporte['fecha_asignacion'] = ['regla' => '/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/', 'mensaje' => 'Formato de fecha inválido. Use AAAA-MM-DD.'];
            $datosFiltro['fecha_asignacion'] = $_POST['fecha_asignacion'];
        }
        if (!empty($_POST['fecha_f'])) {
            $validacionesReporte['fecha_f'] = ['regla' => '/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/', 'mensaje' => 'Formato de fecha inválido. Use AAAA-MM-DD.'];
            $datosFiltro['fecha_f'] = $_POST['fecha_f'];
        }

        if (!empty($validacionesReporte)) {
            validar_datos($validacionesReporte);
        }

        $respuesta = $obj->ProcesarDatos($datosFiltro);

        if (isset($respuesta['accion']) && $respuesta['accion'] === 'error') {
            echo json_encode(['accion' => 'error', 'mensaje' => 'Ocurrió un error al consultar las asignaciones para el reporte.']);
            exit();
        }

        $datos = $respuesta['datos'] ?? [];
        if (empty($datos)) {
            echo json_encode(['accion' => 'error', 'mensaje' => 'No se encontraron asignaciones para generar el reporte con los filtros ingresados.']);
            exit();
        }

        $formato = filter_input(INPUT_POST, 'formato', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'pdf';
        $nombreVista = 'R_Asignaciones'; 
        
        if ($formato === 'excel') {
            $pdf = \App\servicios\GenerarReporte::generarExcel($nombreVista, $datos, 'Asignaciones');
        } else {
            $objG = new GenerarReporte();
            $pdf = $objG->generarPDF($nombreVista, $datos, 'Asignaciones');
        }
        
        if (isset($pdf['accion']) && $pdf['accion'] === 'reporte') {
            registrarBitacora($bitacoraObj, $id_modulo, "Generó reporte de Asignaciones en formato " . strtoupper($formato));
        }
        
        echo json_encode($pdf);
    } catch (Exception $e) {
        logs('Asignaciones', $e->getMessage(), 'Controlador_Generar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}