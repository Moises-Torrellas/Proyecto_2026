<?php

use App\modelo\ModeloEquipos;
use App\modelo\ModeloCategorias;
use App\servicios\GenerarReporte;

// 1. Cargamos las funciones base
require_once __DIR__ . '/Base.php';


// 2. Configuración del módulo (Corregido al ID de Equipos)
$id_modulo = _MD_EQUIPOS_;

// 3. Procesar permisos (Retorna el array de permisos)
$permisos = procesarPermisos($id_modulo, $bitacora);

// 4. Lógica de despacho (Router interno)
$nombreClaseModelo = 'App\modelo\ModeloEquipos';

if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloEquipos();

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitudEquipos($objModelo, $id_modulo, $bitacora, $permisos);
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

function manejarSolicitudEquipos($obj, $id_modulo, $bitacoraObj, array $permisos): void
{
    try {
        $tokenRecibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!isset($_SESSION['token']) || !hash_equals($_SESSION['token'], $tokenRecibido)) {
            throw new Exception('Error de seguridad: Token inválido o expirado.');
        }

        $accion = isset($_POST['accion']) ? filter_var($_POST['accion'], FILTER_SANITIZE_SPECIAL_CHARS) : '';

        // Seguridad centralizada
        switch ($accion) {
            case 'MultiConsulta':
                if (!$permisos['ingresar']) throw new Exception('No tienes permisos para acceder a este módulo.');
                multiConsulta();
                break;

            case 'consultar':
                if (!$permisos['ingresar']) throw new Exception('No tienes permisos para consultar equipos.');
                consultar($obj, $permisos);
                break;
            case 'buscar':
                if (!$permisos['modificar']) throw new Exception('No tienes permisos para modificar equipos.');
                buscar($obj);
                break;
            case 'incluir':
                if (!$permisos['registrar']) throw new Exception('No tienes permisos para registrar equipos.');
                incluir($obj, $id_modulo, $bitacoraObj);
                break;
            case 'eliminar':
                if (!$permisos['eliminar']) throw new Exception('No tienes permisos para eliminar equipos.');
                eliminar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'modificar':
                if (!$permisos['modificar']) throw new Exception('No tienes permisos para modificar equipos.');
                modificar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'generar':
                if (!$permisos['reporte']) throw new Exception('No tienes permisos para generar un reporte de los equipos.');
                generar($obj, $id_modulo, $bitacoraObj);
                break;

            default:
                throw new Exception('Acción no permitida.');
        }
    } catch (Exception $e) {
        logs('Equipos', $e->getMessage(), 'Controlador_ManejarSolicitud');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

/**
 * --- LÓGICA DE ACCIONES ---
 */

function multiConsulta(): void
{
    try {
        $modeloCat = new ModeloCategorias();
        $categorias = $modeloCat->Consultar();

        echo json_encode([
            'accion' => 'MultiConsulta',
            'categorias' => $categorias['datos'] ?? []
        ]);
    } catch (Exception $e) {
        logs('Equipos', $e->getMessage(), 'Controlador_MultiConsulta');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function consultar($obj, $permisos): void
{
    $filtro['filtro'] = $_POST['filtro'] ?? '';
    $respuesta = $obj->Consultar($filtro);

    $registro = $respuesta['datos'] ?? [];
    $solo_lista = true;

    include(__DIR__ . '/../vista/Equipos.php');
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
        logs('Equipos', $e->getMessage(), 'Controlador_Buscar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function incluir($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $validaciones = [
            'nombre'   => ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,30}$/', 'mensaje' => 'Nombre inválido.'],
            'categoria' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Categoría seleccionada inválida.']
        ];

        validar_datos($validaciones);

        $datos = [
            'nombre'     => $_POST['nombre'],
            'categoria' => $_POST['categoria']
        ];
        $datos['accion'] = 'incluir';

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {

            registrarBitacora($bitacoraObj, $id_modulo, "Registró al equipo: " . $_POST['nombre'] . ' ' );
            $resultado = array('accion' => 'incluir', 'mensaje' => 'Equipo registrado exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {

            $resultado['mensaje'] = match ($resultado['codigo']) {
                DUPLICATE_NAME => 'Ya existe un equipo registrado con este nombre.',
                default          => 'Ocurrió un error inesperado en el registro.'
            };
        }
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Equipos', $e->getMessage(), 'Controlador_Incluir');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}
function modificar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $validaciones = [
            'id' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.'],
            'nombre'   => ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,30}$/', 'mensaje' => 'Nombre inválido.'],
        ];

        validar_datos($validaciones);

        $datos = [
            'id' => $_POST['id'],
            'nombre'     => $_POST['nombre']
        ];
        $datos['accion'] = 'modificar';

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {

            registrarBitacora($bitacoraObj, $id_modulo, "Modifico al Equipos: " . $_POST['nombre']);
            $resultado = array('accion' => 'modificar', 'mensaje' => 'Equipos modificado exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {

            $resultado['mensaje'] = match ($resultado['codigo']) {
                DUPLICATE_NAME => 'Ya existe un equipo registrado con este nombre.',
                default          => 'Ocurrió un error inesperado en la modificacion.'
            };
        }

        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Equipos', $e->getMessage(), 'Controlador');
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

            registrarBitacora($bitacoraObj, $id_modulo, "Elimino al Equipo: " . $_POST['id']);
            $resultado = array('accion' => 'eliminar', 'mensaje' => 'Equipos eliminado exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {

            $resultado['mensaje'] = match ($resultado['codigo']) {
                INVALID_ID => 'El Equipos no existe.',
                ASSOCIATES  => 'El Equipo esta asociado a un palmare.',
                default          => 'Ocurrió un error inesperado en la eliminacion.'
            };
        }
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Equiposs', $e->getMessage(), 'Controlador_Eliminar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function generar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $validacionesReporte = [];
        $datosFiltro = ['accion' => 'generar'];

        // Filtro y validación por Nombre
        if (!empty($_POST['nombre'])) {
            // Permite letras, espacios, acentos y la Ñ
            $validacionesReporte['nombre'] = [
                'regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/', 
                'mensaje' => 'Nombre inválido. Solo se permiten letras y espacios.'
            ];
            $datosFiltro['nombre'] = trim($_POST['nombre']);
        }

        validar_datos($validacionesReporte);

        $respuesta = $obj->procesarDatos($datosFiltro);
        $datos = $respuesta['datos'];
        if (empty($datos)) {
            echo json_encode(['accion' => 'error', 'mensaje' => 'No se encontraron equipos para hacer el reporte.']);
            exit();
        }
        
        $nombreVista = 'R_Equipos';
        $objG = new GenerarReporte();
        $pdf = $objG->generarPDF($nombreVista, $datos, 'Equipos');
        
        if (isset($pdf['accion']) && $pdf['accion'] === 'reporte') {
            registrarBitacora($bitacoraObj, $id_modulo, "Generó reporte de equipos.");
        }
        echo json_encode($pdf);
    } catch (Exception $e) {
        logs('Equipos', $e->getMessage(), 'Controlador_Generar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}
