<?php

use App\modelo\ModeloPalmares;
use App\modelo\ModeloHistorial;
use App\modelo\ModeloParticipaciones;
use App\modelo\ModeloTorneos;
use App\modelo\ModeloPremios;
use App\modelo\ModeloAtletas;
use App\modelo\ModeloEquipos;
use App\servicios\GenerarReporte;

// 1. Cargamos las funciones base
require_once __DIR__ . '/Base.php';

// 2. Configuración del módulo
$id_modulo = _MD_PALMARES_;

// 3. Procesar permisos (Retorna el array de permisos)
$permisos = procesarPermisos($id_modulo, $bitacora);

// 4. Lógica de despacho (Router interno)
$nombreClaseModelo = 'App\modelo\ModeloPalmares';

if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloPalmares();

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitudPalmares($objModelo, $id_modulo, $bitacora, $permisos);
} else {
    registrarBitacora($bitacora, $id_modulo, 'Ingreso al Modulo');
    
    // Carga inicial: Consultamos ambas listas
    $respuestaInd = $objModelo->ConsultarIndividual();
    $registroInd = $respuestaInd['datos'] ?? [];

    $respuestaGrp = $objModelo->ConsultarGrupal();
    $registroGrp = $respuestaGrp['datos'] ?? [];

    $variables = [
        'registroInd' => $registroInd, 
        'registroGrp' => $registroGrp, 
        'permisos' => $permisos 
    ];
    cargarVista($pagina, $variables);
}

/**
 * --- FUNCIONES DEL CONTROLADOR ---
 */

function manejarSolicitudPalmares($obj, $id_modulo, $bitacoraObj, array $permisos): void
{
    try {
        $tokenRecibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!isset($_SESSION['token']) || !hash_equals($_SESSION['token'], $tokenRecibido)) {
            throw new Exception('Error de seguridad: Token inválido o expirado.');
        }

        $accion = isset($_POST['accion']) ? filter_var($_POST['accion'], FILTER_SANITIZE_SPECIAL_CHARS) : '';

        // Seguridad centralizada
        switch ($accion) {
            case 'consultarIndividual':
                if (!$permisos['ingresar']) throw new Exception('No tienes permisos para consultar palmarés individual.');
                consultarIndividual($obj, $permisos);
                break;
            case 'consultarGrupal':
                if (!$permisos['ingresar']) throw new Exception('No tienes permisos para consultar palmarés grupal.');
                consultarGrupal($obj, $permisos);
                break;
            case 'MultiConsulta':
                MultiConsulta($obj);
                break;
            case 'buscarIndividual':
                if (!$permisos['modificar']) throw new Exception('No tienes permisos para modificar palmarés individual.');
                buscarIndividual($obj);
                break;
            case 'buscarGrupal':
                if (!$permisos['modificar']) throw new Exception('No tienes permisos para modificar palmarés grupal.');
                buscarGrupal($obj);
                break;
            case 'incluir':
                if (!$permisos['registrar']) throw new Exception('No tienes permisos para registrar palmarés.');
                incluir($obj, $id_modulo, $bitacoraObj);
                break;
            case 'eliminar':
                if (!$permisos['eliminar']) throw new Exception('No tienes permisos para eliminar palmarés.');
                eliminar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'modificar':
                if (!$permisos['modificar']) throw new Exception('No tienes permisos para modificar palmarés.');
                modificar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'generar':
                if (!$permisos['reporte']) throw new Exception('No tienes permisos para generar reportes.');
                generar($obj, $id_modulo, $bitacoraObj);
                break;

            default:
                throw new Exception('Acción no permitida.');
        }
    } catch (Exception $e) {
        logs('Palmares', $e->getMessage(), 'Controlador_manejarSolicitud');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

/**
 * --- LÓGICA DE ACCIONES ---
 */

function consultarIndividual($obj, $permisos): void
{
    $filtro['filtro'] = $_POST['filtro'] ?? '';
    $respuesta = $obj->ConsultarIndividual($filtro);
    
    $registroInd = $respuesta['datos'] ?? []; 
    $solo_lista = true;
    $tipo_lista = 'individual';

    include (__DIR__.'/../vista/Palmares.php');
}

function consultarGrupal($obj, $permisos): void
{
    $filtro['filtro'] = $_POST['filtro'] ?? '';
    $respuesta = $obj->ConsultarGrupal($filtro);
    
    $registroGrp = $respuesta['datos'] ?? []; 
    $solo_lista = true;
    $tipo_lista = 'grupal';

    include (__DIR__.'/../vista/Palmares.php');
}

function MultiConsulta($obj): void
{
    try {
        $modeloTorneos = new ModeloTorneos();
        $modeloPremios = new ModeloPremios();
        $modeloAtletas = new ModeloAtletas();
        $modeloEquipos = new ModeloEquipos();

        $respTorneos = $modeloTorneos->Consultar();
        $torneos = $respTorneos['datos'] ?? [];

        $respPremios = $modeloPremios->Consultar();
        $premios = $respPremios['datos'] ?? [];

        $respAtletas = $modeloAtletas->Consultar();
        $atletasFiltrados = array_filter($respAtletas['datos'] ?? [], function ($item) {
            return !isset($item['estatus']) || (int)$item['estatus'] === 1;
        });

        $respEquipos = $modeloEquipos->Consultar();
        $equiposFiltrados = array_filter($respEquipos['datos'] ?? [], function ($item) {
            return !isset($item['estatus']) || (int)$item['estatus'] === 1;
        });

        // Formatear salida para no romper el frontend, asumiendo llaves estándar de los modelos base
        $torneosArray = array_map(function($t) {
            return ['id_torneo' => $t['id_torneo'], 'nombre' => $t['nombre'], 'fecha_inicio' => $t['fecha_inicio']];
        }, $torneos);

        $premiosArray = array_map(function($p) {
            return ['id_premio' => $p['id_premio'], 'nombre' => $p['nombre'], 'tipo' => $p['tipo']];
        }, $premios);

        $atletasArray = array_map(function($a) {
            return ['id_atleta' => $a['id_atleta'], 'nombres' => $a['nombres'], 'apellidos' => $a['apellidos'], 'doc_identidad' => $a['doc_identidad']];
        }, $atletasFiltrados);

        $equiposArray = array_map(function($e) {
            return ['id_equipos' => $e['id_equipos'], 'nombre' => $e['nombre'], 'categoria' => $e['categoria'] ?? ''];
        }, $equiposFiltrados);

        echo json_encode([
            'accion' => 'MultiConsulta',
            'torneos' => array_values($torneosArray),
            'premios' => array_values($premiosArray),
            'atletas' => array_values($atletasArray),
            'equipos' => array_values($equiposArray)
        ]);
    } catch (Exception $e) {
        logs('Palmares', $e->getMessage(), 'Controlador_MultiConsulta');
        echo json_encode([
            'accion' => 'error',
            'mensaje' => 'Error en la carga masiva de datos: ' . $e->getMessage()
        ]);
    }
}

function buscarIndividual($obj): void
{
    try {
        $validaciones = ['id' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.']];
        validar_datos($validaciones);

        $id = (int)$_POST['id'];
        $resultado = $obj->BuscarIndividual($id);
        
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Palmares', $e->getMessage(), 'Controlador_BuscarIndividual');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function buscarGrupal($obj): void
{
    try {
        $validaciones = ['id' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.']];
        validar_datos($validaciones);

        $id = (int)$_POST['id'];
        $resultado = $obj->BuscarGrupal($id);
        
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Palmares', $e->getMessage(), 'Controlador_BuscarGrupal');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function incluir($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $validaciones = [
            'torneo'        => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Torneo inválido.'],
            'premio'        => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Premio inválido.'],
            'tipo_palmares' => ['regla' => '/^(individual|grupal)$/', 'mensaje' => 'Tipo de palmarés inválido.']
        ];
        
        if ($_POST['tipo_palmares'] === 'individual') {
            $validaciones['atleta'] = ['regla' => '/^[0-9]+$/', 'mensaje' => 'Atleta inválido.'];
        } else {
            $validaciones['equipo'] = ['regla' => '/^[0-9]+$/', 'mensaje' => 'Equipo inválido.'];
        }

        validar_datos($validaciones);

        $datos = [
            'torneo'        => $_POST['torneo'],
            'premio'        => $_POST['premio'],
            'tipo_palmares' => $_POST['tipo_palmares'],
            'accion'        => 'incluir'
        ];

        if ($_POST['tipo_palmares'] === 'individual') {
            $datos['atleta'] = $_POST['atleta'];
        } else {
            $datos['equipo'] = $_POST['equipo'];
        }

        $obj->setModeloParticipaciones(new ModeloParticipaciones());
        $obj->setModeloPremios(new ModeloPremios());

        $resultado = $obj->ProcesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            $desc = $_POST['tipo_palmares'] === 'individual' ? "al atleta ID: " . $_POST['atleta'] : "al equipo ID: " . $_POST['equipo'];
            registrarBitacora($bitacoraObj, $id_modulo, "Registró un palmarés {$_POST['tipo_palmares']} " . $desc);
            $resultado = array('accion' => 'incluir', 'mensaje' => 'Palmarés registrado exitosamente.', 'tipo_palmares' => $_POST['tipo_palmares']);
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                INVALID_ID    => 'El torneo, premio o entidad (atleta/equipo) seleccionado no existe.',
                VALIDATION    => 'El tipo de premio es incorrecto o no se registró participación en el torneo.',
                DB_CONNECTION => 'Ocurrió un error al conectarse con la base de datos.', 
                DUPLICATE     => 'Ya este Atleta/Equipo Tiene este Premio en este Torneo.',
                default       => 'Ocurrió un error inesperado en el registro.'
            };
        }

        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Palmares', $e->getMessage(), 'Controlador_Incluir');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function modificar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $validaciones = [
            'id'            => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.'],
            'premio'        => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Premio inválido.'],
            'tipo_palmares' => ['regla' => '/^(individual|grupal)$/', 'mensaje' => 'Tipo de palmarés inválido.']
        ];
        
        if ($_POST['tipo_palmares'] === 'individual') {
            $validaciones['atleta'] = ['regla' => '/^[0-9]+$/', 'mensaje' => 'Atleta inválido.'];
        } else {
            $validaciones['equipo'] = ['regla' => '/^[0-9]+$/', 'mensaje' => 'Equipo inválido.'];
        }

        validar_datos($validaciones);

        $datos = [
            'id'            => $_POST['id'],
            'premio'        => $_POST['premio'],
            'tipo_palmares' => $_POST['tipo_palmares'],
            'accion'        => 'modificar'
        ];

        if ($_POST['tipo_palmares'] === 'individual') {
            $datos['atleta'] = $_POST['atleta'];
        } else {
            $datos['equipo'] = $_POST['equipo'];
        }

        $obj->setModeloParticipaciones(new ModeloParticipaciones());
        $obj->setModeloHistorial(new ModeloHistorial());
        $obj->setModeloPremios(new ModeloPremios());

        $resultado = $obj->ProcesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Modificó el palmarés {$_POST['tipo_palmares']} ID: " . $_POST['id']);
            $resultado = array('accion' => 'modificar', 'mensaje' => 'Palmarés modificado exitosamente.', 'tipo_palmares' => $_POST['tipo_palmares']);
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                INVALID_ID    => 'El palmarés, torneo, premio o entidad (atleta/equipo) seleccionado no existe.',
                VALIDATION    => 'El tipo de premio es incorrecto o no se registró participación en el torneo.',
                ASSOCIATES    => 'No se puede modificar este palmarés porque ya forma parte de un historial.',
                DB_CONNECTION => 'Ocurrió un error al conectarse con la base de datos.',
                DUPLICATE     => 'Ya este Atleta/Equipo Tiene este Premio en este Torneo.',
                default       => 'Ocurrió un error inesperado en la modificación.'
            };
        }

        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Palmares', $e->getMessage(), 'Controlador_Modificar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function eliminar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $validaciones = [
            'id' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.'],
            'tipo_palmares' => ['regla' => '/^(individual|grupal)$/', 'mensaje' => 'Tipo de palmarés inválido.']
        ];
        validar_datos($validaciones);

        $datos = [
            'id' => $_POST['id'],
            'tipo_palmares' => $_POST['tipo_palmares'],
            'accion' => 'eliminar'
        ];

        $obj->setModeloHistorial(new ModeloHistorial());

        $resultado = $obj->ProcesarDatos($datos);
        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Eliminó el palmarés {$_POST['tipo_palmares']} ID: " . $_POST['id']);
            $resultado = array('accion' => 'eliminar', 'mensaje' => 'Palmarés eliminado correctamente.', 'tipo_palmares' => $_POST['tipo_palmares']);
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                INVALID_ID    => 'El palmarés no existe.',
                ASSOCIATES    => 'No se puede eliminar este palmarés porque ya forma parte de un historial.',
                DB_CONNECTION => 'Ocurrió un error al conectarse con la base de datos.',
                default       => 'Ocurrió un error inesperado en la eliminación.'
            };
        }
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Palmares', $e->getMessage(), 'Controlador_Eliminar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function generar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $validacionesReporte = [
            'tipo_reporte' => ['regla' => '/^(estadistico|tabular)$/', 'mensaje' => 'Tipo de reporte inválido.']
        ];
        
        if (!empty($_POST['palmares_fecha_inicio'])) {
            $validacionesReporte['palmares_fecha_inicio'] = ['regla' => '/^\d{4}-\d{2}-\d{2}$/', 'mensaje' => 'Formato de fecha inválido.'];
        }
        if (!empty($_POST['palmares_fecha_fin'])) {
            $validacionesReporte['palmares_fecha_fin'] = ['regla' => '/^\d{4}-\d{2}-\d{2}$/', 'mensaje' => 'Formato de fecha inválido.'];
        }

        validar_datos($validacionesReporte);

        $tipo_reporte = $_POST['tipo_reporte'];
        $parametros = [
            'palmares_fecha_inicio' => $_POST['palmares_fecha_inicio'] ?? '',
            'palmares_fecha_fin' => $_POST['palmares_fecha_fin'] ?? '',
            'palmares_atleta' => $_POST['palmares_atleta'] ?? '',
            'palmares_equipo' => $_POST['palmares_equipo'] ?? ''
        ];

        // Simula la llamada a la consulta en modelo si fuera necesario
        // $respuesta = $obj->procesarDatos(['accion' => 'generar', ...]);
        // Para este módulo, la generación directa es correcta
        $reporte = new GenerarReporte();
        $pdf = $reporte->generarPDF($tipo_reporte, $parametros, 'Palmares');
        
        // Ajustamos la respuesta
        if (isset($pdf['accion']) && $pdf['accion'] === 'reporte') {
            registrarBitacora($bitacoraObj, $id_modulo, "Generó reporte de Palmarés.");
            echo json_encode($pdf);
        } else {
            throw new Exception("No se pudo generar el documento PDF");
        }

    } catch (Exception $e) {
        logs('Palmares', $e->getMessage(), 'Controlador_Generar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}