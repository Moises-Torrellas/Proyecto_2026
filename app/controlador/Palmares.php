<?php

use App\modelo\ModeloPalmares;
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
$permisos = procesarPermisos($id_modulo, 'ingresar_palmares');

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
    $respuestaGrp = $objModelo->ConsultarGrupal();

    $variables = [
        'registroInd' => $respuestaInd['datos'] ?? [],
        'registroGrp' => $respuestaGrp['datos'] ?? [],
        'permisos'    => $permisos
    ];
    cargarVista($pagina, $variables);
}

function manejarSolicitudPalmares($obj, $id_modulo, $bitacoraObj, array $permisos): void
{
    try {
        $tokenRecibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!isset($_SESSION['token']) || !hash_equals($_SESSION['token'], $tokenRecibido)) {
            throw new Exception('Error de seguridad: Token inválido o expirado.');
        }

        $accion = isset($_POST['accion']) ? filter_var($_POST['accion'], FILTER_SANITIZE_SPECIAL_CHARS) : '';

        // Seguridad centralizada y enrutamiento
        switch ($accion) {
            case 'consultarIndividual':
                if (empty($permisos['ingresar_palmares'])) throw new Exception('No tienes permisos para consultar palmarés individual.');
                consultarIndividual($obj, $permisos);
                break;
            case 'consultarGrupal':
                if (empty($permisos['ingresar_palmares'])) throw new Exception('No tienes permisos para consultar palmarés grupal.');
                consultarGrupal($obj, $permisos);
                break;
            case 'MultiConsulta':
                MultiConsulta();
                break;
            case 'buscarIndividual':
                if (empty($permisos['modificar_palmares'])) throw new Exception('No tienes permisos para modificar palmarés individual.');
                buscarIndividual($obj);
                break;
            case 'buscarGrupal':
                if (empty($permisos['modificar_palmares'])) throw new Exception('No tienes permisos para modificar palmarés grupal.');
                buscarGrupal($obj);
                break;
            case 'incluir':
                if (empty($permisos['registrar_palmares'])) throw new Exception('No tienes permisos para registrar palmarés.');
                incluir($obj, $id_modulo, $bitacoraObj);
                break;
            case 'eliminar':
                if (empty($permisos['eliminar_palmares'])) throw new Exception('No tienes permisos para eliminar palmarés.');
                eliminar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'modificar':
                if (empty($permisos['modificar_palmares'])) throw new Exception('No tienes permisos para modificar palmarés.');
                modificar($obj, $id_modulo, $bitacoraObj);
                break;
            default:
                throw new Exception('Acción no permitida.');
        }
    } catch (Exception $e) {
        logs('Palmares', $e->getMessage(), 'Controlador_manejarSolicitud');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function consultarIndividual($obj, $permisos): void
{
    $filtro['filtro'] = isset($_POST['filtro']) ? filter_var($_POST['filtro'], FILTER_SANITIZE_SPECIAL_CHARS) : '';
    $respuesta = $obj->ConsultarIndividual($filtro);

    $registroInd = $respuesta['datos'] ?? [];
    $solo_lista = true;
    $tipo_lista = 'individual';

    include(__DIR__ . '/../vista/Palmares.php');
}

function consultarGrupal($obj, $permisos): void
{
    $filtro['filtro'] = isset($_POST['filtro']) ? filter_var($_POST['filtro'], FILTER_SANITIZE_SPECIAL_CHARS) : '';
    $respuesta = $obj->ConsultarGrupal($filtro);

    $registroGrp = $respuesta['datos'] ?? [];
    $solo_lista = true;
    $tipo_lista = 'grupal';

    include(__DIR__ . '/../vista/Palmares.php');
}

function MultiConsulta(): void
{
    try {
        $modeloTorneos = new ModeloTorneos();
        $modeloPremios = new ModeloPremios();
        $modeloAtletas = new ModeloAtletas();
        $modeloEquipos = new ModeloEquipos();

        // PASO CLAVE: Pasamos el filtro ['estatus' => 3] al modelo de torneos
        // (Asegúrate de que el método Consultar en ModeloTorneos acepte este filtro)
        $torneos = $modeloTorneos->Consultar(['estatus' => 3])['datos'] ?? [];
        
        $premios = $modeloPremios->Consultar()['datos'] ?? [];
        $atletasFiltrados = array_filter($modeloAtletas->Consultar()['datos'] ?? [], fn($item) => !isset($item['estatus']) || (int)$item['estatus'] === 1);
        $equiposFiltrados = array_filter($modeloEquipos->Consultar()['datos'] ?? [], fn($item) => !isset($item['estatus']) || (int)$item['estatus'] === 1);

        // Formateo de datos
        $torneosArray = array_map(fn($t) => ['id_torneo' => $t['codigo_torneo'], 'nombre' => $t['nombre'], 'fecha_inicio' => $t['fecha_inicio']], $torneos);
        $premiosArray = array_map(fn($p) => ['id_premio' => $p['codigo_premio'], 'nombre' => $p['nombre'], 'tipo' => $p['tipo']], $premios);
        $atletasArray = array_map(fn($a) => ['id_atleta' => $a['id_atleta'], 'nombres' => $a['nombres'], 'apellidos' => $a['apellidos'], 'doc_identidad' => $a['doc_identidad']], $atletasFiltrados);
        $equiposArray = array_map(fn($e) => ['id_equipos' => $e['id_equipos'], 'nombre' => $e['nombre'] ?? ''], $equiposFiltrados);

        echo json_encode([
            'accion'  => 'MultiConsulta',
            'torneos' => array_values($torneosArray),
            'premios' => array_values($premiosArray),
            'atletas' => array_values($atletasArray),
            'equipos' => array_values($equiposArray)
        ]);
    } catch (Exception $e) {
        logs('Palmares', $e->getMessage(), 'Controlador_MultiConsulta');
        echo json_encode(['accion' => 'error', 'mensaje' => 'Error en la carga masiva de datos: ' . $e->getMessage()]);
    }
}

function buscarIndividual($obj): void
{
    try {
        validar_datos(['id' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.']]);
        $resultado = $obj->BuscarIndividual((int)$_POST['id']);
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Palmares', $e->getMessage(), 'Controlador_BuscarIndividual');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function buscarGrupal($obj): void
{
    try {
        validar_datos(['id' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.']]);
        $resultado = $obj->BuscarGrupal((int)$_POST['id']);
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Palmares', $e->getMessage(), 'Controlador_BuscarGrupal');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function incluir($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $tipo_palmares = $_POST['tipo_palmares'] ?? '';

        $validaciones = [
            'torneo'        => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Torneo inválido.'],
            'premio'        => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Premio inválido.'],
            'tipo_palmares' => ['regla' => '/^(individual|grupal)$/', 'mensaje' => 'Tipo de palmarés inválido.']
        ];

        if ($tipo_palmares === 'individual') {
            $validaciones['atleta'] = ['regla' => '/^[0-9]+$/', 'mensaje' => 'Atleta inválido.'];
        } else {
            $validaciones['equipo'] = ['regla' => '/^[0-9]+$/', 'mensaje' => 'Equipo inválido.'];
        }

        validar_datos($validaciones);

        $datos = [
            'torneo'        => (int)$_POST['torneo'],
            'premio'        => (int)$_POST['premio'],
            'tipo_palmares' => $tipo_palmares,
            'accion'        => 'incluir'
        ];

        if ($tipo_palmares === 'individual') {
            $datos['atleta'] = (int)$_POST['atleta'];
            $desc = "al atleta ID: " . $datos['atleta'];
        } else {
            $datos['equipo'] = (int)$_POST['equipo'];
            $desc = "al equipo ID: " . $datos['equipo'];
        }

        // Inyectamos los modelos auxiliares requeridos por el modelo principal
        $obj->setModeloParticipaciones(new ModeloParticipaciones());
        $obj->setModeloPremios(new ModeloPremios());

        $resultado = $obj->ProcesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Registró un palmarés {$tipo_palmares} {$desc}");
            echo json_encode(['accion' => 'incluir', 'mensaje' => 'Palmarés registrado exitosamente.', 'tipo_palmares' => $tipo_palmares]);
            return;
        }

        // Manejo estandarizado de errores
        $codigoError = $resultado['codigo'] ?? '';
        $mensaje = match ($codigoError) {
            INVALID_ID    => 'El torneo, premio o entidad (atleta/equipo) seleccionado no existe.',
            VALIDATION    => 'El tipo de premio es incorrecto o no se registró participación en el torneo.',
            DB_CONNECTION => 'Ocurrió un error al conectarse con la base de datos.',
            DUPLICATE     => 'Esta entidad ya tiene registrado este premio en el torneo especificado.',
            default       => $resultado['mensaje'] ?? 'Ocurrió un error inesperado en el registro.'
        };

        echo json_encode(['accion' => 'error', 'mensaje' => $mensaje]);
    } catch (Exception $e) {
        logs('Palmares', $e->getMessage(), 'Controlador_Incluir');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function modificar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $tipo_palmares = $_POST['tipo_palmares'] ?? '';

        $validaciones = [
            'id'            => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.'],
            'premio'        => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Premio inválido.'],
            'torneo'        => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Torneo inválido.'], // <-- AGREGADO
            'tipo_palmares' => ['regla' => '/^(individual|grupal)$/', 'mensaje' => 'Tipo de palmarés inválido.']
        ];

        if ($tipo_palmares === 'individual') {
            $validaciones['atleta'] = ['regla' => '/^[0-9]+$/', 'mensaje' => 'Atleta inválido.'];
        } else {
            $validaciones['equipo'] = ['regla' => '/^[0-9]+$/', 'mensaje' => 'Equipo inválido.'];
        }

        validar_datos($validaciones);

        $datos = [
            'id'            => (int)$_POST['id'],
            'premio'        => (int)$_POST['premio'],
            'torneo'        => (int)$_POST['torneo'], // <-- AGREGADO
            'tipo_palmares' => $tipo_palmares,
            'accion'        => 'modificar'
        ];

        if ($tipo_palmares === 'individual') {
            $datos['atleta'] = (int)$_POST['atleta'];
        } else {
            $datos['equipo'] = (int)$_POST['equipo'];
        }

        $obj->setModeloParticipaciones(new ModeloParticipaciones());
        $obj->setModeloPremios(new ModeloPremios());

        $resultado = $obj->ProcesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Modificó el palmarés {$tipo_palmares} ID: " . $datos['id']);
            echo json_encode(['accion' => 'modificar', 'mensaje' => 'Palmarés modificado exitosamente.', 'tipo_palmares' => $tipo_palmares]);
            return;
        }

        $codigoError = $resultado['codigo'] ?? '';
        $mensaje = match ($codigoError) {
            INVALID_ID    => 'El palmarés, torneo, premio o entidad seleccionado no existe.',
            VALIDATION    => 'El tipo de premio es incorrecto o no se registró participación en el torneo.',
            ASSOCIATES    => 'No se puede modificar este palmarés porque ya forma parte de un historial.',
            DB_CONNECTION => 'Ocurrió un error al conectarse con la base de datos.',
            DUPLICATE     => 'Esta entidad ya tiene registrado este premio en el torneo especificado.',
            default       => $resultado['mensaje'] ?? 'Ocurrió un error inesperado en la modificación.'
        };

        echo json_encode(['accion' => 'error', 'mensaje' => $mensaje]);
    } catch (Exception $e) {
        logs('Palmares', $e->getMessage(), 'Controlador_Modificar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function eliminar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        validar_datos([
            'id'            => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.'],
            'tipo_palmares' => ['regla' => '/^(individual|grupal)$/', 'mensaje' => 'Tipo de palmarés inválido.']
        ]);

        $tipo_palmares = $_POST['tipo_palmares'];
        $id = (int)$_POST['id'];

        $datos = [
            'id'            => $id,
            'tipo_palmares' => $tipo_palmares,
            'accion'        => 'eliminar'
        ];

        $resultado = $obj->ProcesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Eliminó el palmarés {$tipo_palmares} ID: {$id}");
            echo json_encode(['accion' => 'eliminar', 'mensaje' => 'Palmarés eliminado correctamente.', 'tipo_palmares' => $tipo_palmares]);
            return;
        }

        $codigoError = $resultado['codigo'] ?? '';
        $mensaje = match ($codigoError) {
            INVALID_ID    => 'El palmarés no existe.',
            ASSOCIATES    => 'No se puede eliminar este palmarés porque ya forma parte de un historial.',
            DB_CONNECTION => 'Ocurrió un error al conectarse con la base de datos.',
            default       => $resultado['mensaje'] ?? 'Ocurrió un error inesperado en la eliminación.'
        };

        echo json_encode(['accion' => 'error', 'mensaje' => $mensaje]);
    } catch (Exception $e) {
        logs('Palmares', $e->getMessage(), 'Controlador_Eliminar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}
