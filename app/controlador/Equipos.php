<?php

use App\modelo\ModeloEquipos;
use App\modelo\ModeloCategorias;
use App\servicios\GenerarReporte;

// 1. Cargamos las funciones base
require_once __DIR__ . '/Base.php';

// 2. Configuración del módulo
$id_modulo = _MD_EQUIPOS_;

// 3. Procesar permisos
$permisos = procesarPermisos($id_modulo, $bitacora ?? null);

// 4. Lógica de despacho (Router interno)
$nombreClaseModelo = 'App\modelo\ModeloEquipos';

if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloEquipos();

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitudEquipos($objModelo, $id_modulo, $bitacora ?? null, $permisos);
} else {
    registrarBitacora($bitacora ?? null, $id_modulo, 'Ingreso al Modulo');
    $respuesta = $objModelo->Consultar();

    $error_bd = '';
    if (isset($respuesta['accion']) && $respuesta['accion'] === 'error') {
        $error_bd = ($respuesta['mensaje'] == DB_CONNECTION) ? 'Error al conectar con la base de datos.' : '';
    }

    $registro = $respuesta['datos'] ?? [];

    // Cargar atletas asignados por cada equipo para que funcione el detalle_expandido_container
    foreach ($registro as &$equipo) {
        $idEquipo = isset($equipo['id_equipos']) ? (int)$equipo['id_equipos'] : 0;
        if ($idEquipo > 0) {
            $respAtletas = $objModelo->ConsultarAtletasAsignadosEquipo($idEquipo);
            $equipo['atletas'] = ($respAtletas['datos'] ?? []);
        } else {
            $equipo['atletas'] = [];
        }
    }
    unset($equipo);

    $variables = ['registro' => $registro, 'permisos' => $permisos, 'error_bd' => $error_bd];
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

        switch ($accion) {
            case 'consultar':
                if (!$permisos['ingresar']) throw new Exception('No tienes permisos para consultar equipos.');
                consultar($obj, $permisos);
                break;
            case 'consultarAtletasModal':
                if (!$permisos['ingresar'] && !$permisos['modificar']) throw new Exception('No tienes permisos para consultar atletas.');
                consultarAtletasModal($obj);
                break;
            case 'consultarAtletasAsignadosEquipo':
                consultarAtletasAsignadosEquipo($obj);
                break;

            case 'MultiConsulta':
                if (!$permisos['ingresar']) throw new Exception('No tienes permisos para consultar equipos.');
                MultiConsulta();
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
                throw new Exception('Acción no permitida: ' . $accion);
        }
    } catch (Exception $e) {
        logs('Equipos', $e->getMessage(), 'Controlador_ManejarSolicitud');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function MultiConsulta(): void
{
    try {
        $modeloCat = new ModeloCategorias();
        $catRespuesta = $modeloCat->Consultar();

        // 1. Forzamos al navegador a interpretar la respuesta como JSON estricto
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode([
            'accion' => 'MultiConsulta',
            'categoria' => $catRespuesta['datos'] ?? []
        ]);

        // 2. Matamos la ejecución aquí para evitar que se cuele cualquier espacio en blanco al final del archivo
        exit();

    } catch (Exception $e) {
        logs('Equipos', $e->getMessage(), 'Controlador_MultiConsulta');

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['accion' => 'error', 'mensaje' => 'Error al inicializar los catálogos del módulo.']);
        exit();
    }
}

function consultarAtletasModal($obj): void
{
    try {
        $filtro['filtro'] = $_POST['filtro'] ?? '';
        $respuesta = $obj->ConsultarAtletasParaAsignacion($filtro);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($respuesta);
        exit();
    } catch (Exception $e) {
        logs('Equipos', $e->getMessage(), 'Controlador_ConsultarAtletasModal');
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
        exit();
    }
}

function consultarAtletasAsignadosEquipo($obj): void
{
    try {
        $idEquipo = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($idEquipo <= 0) {
            throw new Exception('id_equipos inválido.');
        }

        $respuesta = $obj->ConsultarAtletasAsignadosEquipo($idEquipo);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($respuesta);
        exit();
    } catch (Exception $e) {
        logs('Equipos', $e->getMessage(), 'Controlador_ConsultarAtletasAsignadosEquipo');
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
        exit();
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

    // Cargar atletas asignados por cada equipo para que funcione el detalle_expandido_container
    // (detalle_expandido_container muestra $dato['atletas']).
    foreach ($registro as &$equipo) {
        $idEquipo = isset($equipo['id_equipos']) ? (int)$equipo['id_equipos'] : 0;
        if ($idEquipo > 0) {
            $respAtletas = $obj->ConsultarAtletasAsignadosEquipo($idEquipo);
            $equipo['atletas'] = ($respAtletas['datos'] ?? []);
        } else {
            $equipo['atletas'] = [];
        }
    }
    unset($equipo);

    $solo_lista = true;
    include(__DIR__ . '/../vista/Equipos.php');
}

function buscar($obj): void
{

    try {
        validar_requeridos(['id']);

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


    $idEquipoCreado = null;
    try {
        validar_requeridos(['nombre', 'categoria']);

        $datos = [
            'nombre' => $_POST['nombre'],
            'categoria' => $_POST['categoria'],
            'accion' => 'incluir'
        ];

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            $idEquipoCreado = $resultado['id_equipos'] ?? null;

            // Guardar detalles_equipo
            // Validación requerida: obligatoriamente debe existir al menos 1 atleta asignado
            $atletas = $_POST['atletas'] ?? [];
            if (!is_array($atletas)) {
                $atletas = [$atletas];
            }

            // Nota: en el flujo actual el JS envía atletas[]; si no hay ninguno,
            // $atletas llega vacío y no se debe permitir el registro del equipo.
            if (count($atletas) < 1) {
                throw new Exception('Para registrar un equipo debe asignar al menos 1 atleta.');
            }

            $obj->GuardarDetallesEquipo($idEquipoCreado, $atletas);



            try {
                registrarBitacora($bitacoraObj, $id_modulo, "Registró al Equipo: " . $_POST['nombre']);
            } catch (Exception $e) {
                // no afecta el flujo principal
            }
            $resultado = ['accion' => 'incluir', 'mensaje' => 'Equipo registrado exitosamente.'];
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {


            $resultado['mensaje'] = match ($resultado['codigo']) {
                DUPLICATE_NAME => 'Ya existe un equipo registrado con ese nombre.',
                INVALID_ID => 'La categoría ingresada no existe en los registros del club.',
                DB_CONNECTION      => 'Ocurrio un error al conectarse con la base de datos.',
                default => 'Ocurrió un error inesperado en el registro.'
            };
        }
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Equipos', $e->getMessage(), 'Controlador_Incluir');

        // Rollback físico: si ya se insertó el equipo y falló la validación de atletas, lo eliminamos
        if ($idEquipoCreado !== null) {
            try {
                $datosRollback = [
                    'id' => $idEquipoCreado,
                    'accion' => 'eliminar'
                ];
                $obj->procesarDatos($datosRollback);
            } catch (Exception $rollbackEx) {
                // si rollback falla, igual devolvemos el error original
            }
        }

        $msg = $e->getMessage();
        if (stripos($msg, 'Solo se pueden asignar atletas de la misma categoría del equipo') !== false) {
            $msg = 'No se puede asignar atletas de una categoría distinta a la del equipo.';
        }
        echo json_encode(['accion' => 'error', 'mensaje' => $msg]);
    }
}

function modificar($obj, $id_modulo, $bitacoraObj): void
{
    try {

        validar_requeridos(['id', 'nombre', 'categoria']);

        $datos = [
            'id' => $_POST['id'],
            'nombre' => $_POST['nombre'],
            'categoria' => $_POST['categoria'],
            'accion' => 'modificar'
        ];

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Modificó al equipo: " . $_POST['nombre']);

            // Guardar detalles_equipo
            $idEquipo = $_POST['id'] ?? null;
            $atletas = $_POST['atletas'] ?? [];
            if (!is_array($atletas)) {
                $atletas = [$atletas];
            }

            // Validación requerida: obligatoriamente debe existir al menos 1 atleta asignado
            if (empty($atletas) || count($atletas) < 1) {
                throw new Exception('Para modificar un equipo debe asignar al menos 1 atleta.');
            }

            $obj->GuardarDetallesEquipo($idEquipo, $atletas);


            $resultado = ['accion' => 'modificar', 'mensaje' => 'Equipo modificado exitosamente.'];
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                DUPLICATE_NAME => 'Ya existe un Equipo registrado con este nombre.',
                INVALID_ID => 'La categoría ingresada no existe en los registros del club.',
                DB_CONNECTION      => 'Ocurrio un error al conectarse con la base de datos.',
                default => 'Ocurrió un error inesperado en la modificación.'
            };
        }

        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Equipos', $e->getMessage(), 'Controlador_Modificar');
        $msg = $e->getMessage();
        if (stripos($msg, 'Solo se pueden asignar atletas de la misma categoría del equipo') !== false) {
            $msg = 'No se puede asignar atletas de una categoría distinta a la del equipo.';
        }
        echo json_encode(['accion' => 'error', 'mensaje' => $msg]);
    }
}

function eliminar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        validar_requeridos(['id']);

        $datos = [
            'id' => $_POST['id'],
            'accion' => 'eliminar'
        ];

        $resultado = $obj->procesarDatos($datos);
        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Eliminó al equipo ID: " . $_POST['id']);
            $resultado = ['accion' => 'eliminar', 'mensaje' => 'Equipo eliminado exitosamente.'];
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                INVALID_ID => 'El equipo no existe.',
                ASSOCIATES => 'El equipo tiene palmarés asociados.',
                DB_CONNECTION      => 'Ocurrio un error al conectarse con la base de datos.',
                default => 'Ocurrió un error inesperado en la eliminación.'
            };
        }
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Equipos', $e->getMessage(), 'Controlador_Eliminar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function generar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $datosFiltro = ['accion' => 'generar'];

        $respuesta = $obj->procesarDatos($datosFiltro);
        $datos = $respuesta['datos'] ?? [];

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

