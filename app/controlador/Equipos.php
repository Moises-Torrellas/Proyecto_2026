<?php

use App\modelo\ModeloEquipos;
use App\servicios\GenerarReporte;

// 1. Cargamos las funciones base
require_once __DIR__ . '/Base.php';

// 2. Configuración del módulo
$id_modulo = _MD_EQUIPOS_;

// 3. Procesar permisos
$permisos = procesarPermisos($id_modulo, '');

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
    // --- CARGA INICIAL DE LA VISTA ---
    registrarBitacora($bitacora ?? null, $id_modulo, 'Ingreso al Modulo');
    $respuesta = $objModelo->Consultar();

    $error_bd = '';
    if (isset($respuesta['accion']) && $respuesta['accion'] === 'error') {
        $error_bd = ($respuesta['mensaje'] == DB_CONNECTION) ? 'Error al conectar con la base de datos.' : '';
    }

    $registro = adjuntarAtletasAEquipos($objModelo, $respuesta['datos'] ?? []);

    $variables = ['registro' => $registro, 'permisos' => $permisos, 'error_bd' => $error_bd];
    cargarVista($pagina, $variables);
}

function adjuntarAtletasAEquipos($objModelo, array $equipos): array
{
    foreach ($equipos as &$equipo) {
        $idEquipo = isset($equipo['id_equipos']) ? (int)$equipo['id_equipos'] : 0;
        if ($idEquipo > 0) {
            $respAtletas = $objModelo->ConsultarAtletasAsignadosEquipo($idEquipo);
            $equipo['atletas'] = $respAtletas['datos'] ?? [];
        } else {
            $equipo['atletas'] = [];
        }
    }
    return $equipos;
}

function manejarSolicitudEquipos($obj, $id_modulo, $bitacoraObj, array $permisos): void
{
    try {
        $tokenRecibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!isset($_SESSION['token']) || !hash_equals($_SESSION['token'], $tokenRecibido)) {
            throw new Exception('Error de seguridad: Token inválido o expirado.');
        }

        $accion = isset($_POST['accion']) ? filter_var($_POST['accion'], FILTER_SANITIZE_SPECIAL_CHARS) : '';

        // Enrutador de acciones (Se removió MultiConsulta)
        match ($accion) {
            'consultar' => $permisos['ingresar'] ? consultar($obj, $permisos) : throw new Exception('No tienes permisos para consultar equipos.'),
            'consultarAtletasModal' => ($permisos['ingresar'] || $permisos['modificar']) ? consultarAtletasModal($obj) : throw new Exception('No tienes permisos para consultar atletas.'),
            'consultarAtletasAsignadosEquipo' => consultarAtletasAsignadosEquipo($obj),
            'buscar'    => $permisos['modificar'] ? buscar($obj) : throw new Exception('No tienes permisos para modificar equipos.'),
            'incluir'   => $permisos['registrar'] ? incluir($obj, $id_modulo, $bitacoraObj) : throw new Exception('No tienes permisos para registrar equipos.'),
            'modificar' => $permisos['modificar'] ? modificar($obj, $id_modulo, $bitacoraObj) : throw new Exception('No tienes permisos para modificar equipos.'),
            'eliminar'  => $permisos['eliminar'] ? eliminar($obj, $id_modulo, $bitacoraObj) : throw new Exception('No tienes permisos para eliminar equipos.'),
            'generar'   => $permisos['reporte'] ? generar($obj, $id_modulo, $bitacoraObj) : throw new Exception('No tienes permisos para generar un reporte de los equipos.'),
            default     => throw new Exception('Acción no permitida: ' . $accion)
        };

    } catch (Exception $e) {
        logs('Equipos', $e->getMessage(), 'Controlador_ManejarSolicitud');
        enviarJSON(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

/**
 * --- HELPER PARA RESPUESTAS JSON ---
 */
function enviarJSON(array $data): void
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit();
}


function consultarAtletasModal($obj): void
{
    $filtro['filtro'] = $_POST['filtro'] ?? '';
    enviarJSON($obj->ConsultarAtletasParaAsignacion($filtro));
}

function consultarAtletasAsignadosEquipo($obj): void
{
    $idEquipo = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($idEquipo <= 0) {
        enviarJSON(['accion' => 'error', 'mensaje' => 'ID de equipo inválido.']);
    }
    enviarJSON($obj->ConsultarAtletasAsignadosEquipo($idEquipo));
}

function consultar($obj, $permisos): void
{
    $filtro['filtro'] = $_POST['filtro'] ?? '';
    $respuesta = $obj->Consultar($filtro);
    
    // Reutilizamos la función DRY para cargar atletas
    $registro = adjuntarAtletasAEquipos($obj, $respuesta['datos'] ?? []);
    
    $solo_lista = true;
    include(__DIR__ . '/../vista/Equipos.php');
}

function buscar($obj): void
{
    validar_requeridos(['id']);
    $datos = ['id' => $_POST['id'], 'accion' => 'buscar'];
    enviarJSON($obj->procesarDatos($datos));
}

function incluir($obj, $id_modulo, $bitacoraObj): void
{
    try {
        // Se quitó 'categoria' de los campos requeridos
        validar_requeridos(['nombre']);
        
        $atletas = (array)($_POST['atletas'] ?? []);
        if (empty($atletas)) {
            throw new Exception('Para registrar un equipo debe asignar al menos 1 atleta.');
        }

        // Estructura limpia enviada al modelo
        $datos = [
            'nombre'  => $_POST['nombre'],
            'atletas' => $atletas,
            'accion'  => 'incluir'
        ];

        $resultado = $obj->procesarDatos($datos);

        // Control flexible si el modelo responde exitosamente sin importar el string exacto
        if (isset($resultado['accion']) && $resultado['accion'] !== 'error') {
            try { registrarBitacora($bitacoraObj, $id_modulo, "Registró al Equipo: " . $_POST['nombre']); } catch (Exception $e) {}
            enviarJSON(['accion' => 'incluir', 'mensaje' => 'Equipo registrado exitosamente.']);
        }

        // Manejo y mapeo de excepciones controladas de BD o del negocio
        $errorRaw = $resultado['codigo'] ?? $resultado['mensaje'] ?? '';
        $mensajeError = match ($errorRaw) {
            DUPLICATE_NAME => 'Ya existe un equipo registrado con ese nombre.',
            DB_CONNECTION  => 'Ocurrió un error al conectarse con la base de datos.',
            default        => (!empty($errorRaw)) ? $errorRaw : 'Ocurrió un error inesperado en el registro.'
        };
        
        enviarJSON(['accion' => 'error', 'mensaje' => $mensajeError]);

    } catch (Exception $e) {
        logs('Equipos', $e->getMessage(), 'Controlador_Incluir');
        enviarJSON(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function modificar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        // Se quitó 'categoria' de los campos requeridos
        validar_requeridos(['id', 'nombre']);

        $atletas = (array)($_POST['atletas'] ?? []);
        if (empty($atletas)) {
            throw new Exception('Para modificar un equipo debe asignar al menos 1 atleta.');
        }

        // Estructura limpia enviada al modelo
        $datos = [
            'id'      => $_POST['id'],
            'nombre'  => $_POST['nombre'],
            'atletas' => $atletas,
            'accion'  => 'modificar'
        ];

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] !== 'error') {
            try { registrarBitacora($bitacoraObj, $id_modulo, "Modificó al equipo: " . $_POST['nombre']); } catch (Exception $e) {}
            enviarJSON(['accion' => 'modificar', 'mensaje' => 'Equipo modificado exitosamente.']);
        }

        $errorRaw = $resultado['codigo'] ?? $resultado['mensaje'] ?? '';
        $mensajeError = match ($errorRaw) {
            DUPLICATE_NAME => 'Ya existe un Equipo registrado con este nombre.',
            DB_CONNECTION  => 'Ocurrió un error al conectarse con la base de datos.',
            default        => (!empty($errorRaw)) ? $errorRaw : 'Ocurrió un error inesperado en la modificación.'
        };
        
        enviarJSON(['accion' => 'error', 'mensaje' => $mensajeError]);

    } catch (Exception $e) {
        logs('Equipos', $e->getMessage(), 'Controlador_Modificar');
        enviarJSON(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function eliminar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        validar_requeridos(['id']);
        $datos = ['id' => $_POST['id'], 'accion' => 'eliminar'];
        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] !== 'error') {
            try { registrarBitacora($bitacoraObj, $id_modulo, "Eliminó al equipo ID: " . $_POST['id']); } catch (Exception $e) {}
            enviarJSON(['accion' => 'eliminar', 'mensaje' => 'Equipo eliminado exitosamente.']);
        }

        $errorRaw = $resultado['codigo'] ?? $resultado['mensaje'] ?? '';
        $mensajeError = match ($errorRaw) {
            INVALID_ID    => 'El equipo no existe.',
            ASSOCIATES    => 'El equipo tiene participaciones asociadas en campeonatos.',
            DB_CONNECTION => 'Ocurrió un error al conectarse con la base de datos.',
            default       => (!empty($errorRaw)) ? $errorRaw : 'Ocurrió un error inesperado en la eliminación.'
        };
        enviarJSON(['accion' => 'error', 'mensaje' => $mensajeError]);

    } catch (Exception $e) {
        logs('Equipos', $e->getMessage(), 'Controlador_Eliminar');
        enviarJSON(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function generar($obj, $id_modulo, $bitacoraObj): void
{
    $respuesta = $obj->procesarDatos(['accion' => 'generar']);
    $datos = $respuesta['datos'] ?? [];

    if (empty($datos)) {
        enviarJSON(['accion' => 'error', 'mensaje' => 'No se encontraron equipos para hacer el reporte.']);
    }

    $objG = new GenerarReporte();
    $pdf = $objG->generarPDF('R_Equipos', $datos, 'Equipos');

    if (isset($pdf['accion']) && $pdf['accion'] === 'reporte') {
        try { registrarBitacora($bitacoraObj, $id_modulo, "Generó reporte de equipos."); } catch (Exception $e) {}
    }
    enviarJSON($pdf);
}