<?php

use App\modelo\ModeloAtletas;

// 1. Cargamos las funciones base
require_once __DIR__ . '/Base.php';

// 2. Configuración del módulo (Corregido al ID de Representantes)
$id_modulo = _MD_ATLETAS_;

// 3. Procesar permisos (Retorna el array de permisos)
$permisos = procesarPermisos($id_modulo, $bitacora ?? null);

// 4. Lógica de despacho (Router interno)
$nombreClaseModelo = 'App\modelo\ModeloAtletas';

if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloAtletas();

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitud($objModelo, $id_modulo, $bitacora ?? null, $permisos);
} else {
    cargarVista($pagina);
}

function manejarSolicitud($obj, $id_modulo, $bitacoraObj, array $permisos): void
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
                consultar($obj);
                break;
            case 'consultarR':
                consultarRepresentantes($obj);
                break;
            case 'consultarP':
                consultarPosiciones($obj);
                break;
            case 'consultarC':
                consultarCategorias($obj);
                break;
            case 'incluir':
                if (!$permisos['incluir']) throw new Exception('No tienes permisos para registrar atletas.');
                incluir($obj, $id_modulo, $bitacoraObj);
                break;
            case 'buscar':
                if (!$permisos['modificar']) throw new Exception('No tienes permisos para modificar Atletas.');
                buscar($obj);
                break;
            case 'modificar':
                if (!$permisos['modificar']) throw new Exception('No tienes permisos para modificar Atletas.');
                modificar($obj, $id_modulo, $bitacoraObj);
                break;
            default:
                throw new Exception('Acción no permitida.');
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

/**
 * --- LÓGICA DE ACCIONES ---
 */

function consultar($obj): void
{
    $filtro['filtro'] = $_POST['filtro'] ?? '';
    $respuesta = $obj->Consultar($filtro);
    if (isset($respuesta['accion']) && $respuesta['accion'] == 'error') {
        $respuesta['mensaje'] = 'Error al listar los representantes';
    }
    echo json_encode($respuesta);
}
function consultarRepresentantes($obj): void
{
    $respuesta = $obj->ConsultarRepresentantes();
    if (isset($respuesta['accion']) && $respuesta['accion'] == 'error') {
        $respuesta['mensaje'] = 'Error al listar los representantes';
    }
    echo json_encode($respuesta);
}
function consultarCategorias($obj): void
{
    $filtro['filtro'] = $_POST['filtro'] ?? '';
    $respuesta = $obj->ConsultarCategorias();
    if (isset($respuesta['accion']) && $respuesta['accion'] == 'error') {
        $respuesta['mensaje'] = 'Error al listar los representantes';
    }
    echo json_encode($respuesta);
}
function consultarPosiciones($obj): void
{
    $filtro['filtro'] = $_POST['filtro'] ?? '';
    $respuesta = $obj->ConsultarPosiciones();
    if (isset($respuesta['accion']) && $respuesta['accion'] == 'error') {
        $respuesta['mensaje'] = 'Error al listar los representantes';
    }
    echo json_encode($respuesta);
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
        logs('Atletas', $e->getMessage(), 'Controlador_Buscar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}
function incluir($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $validaciones = [
            'fecha_nac' => ['regla'   => '/^\d{4}-\d{2}-\d{2}$/', 'mensaje' => 'Formato de fecha inválido. Use AAAA-MM-DD.'],
            'nombre'   => ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,60}$/', 'mensaje' => 'Nombres inválido.'],
            'apellido' => ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,60}$/', 'mensaje' => 'Apellidos inválido.'],
            'categoria' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Categoria inválida.'],
            'posicion' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Posición inválida.'],
            'genero' => ['regla'   => '/^[HM]$/', 'mensaje' => 'Genero inválido.'],
        ];

        $datos = [
            'fecha_nac' => $_POST['fecha_nac'],
            'nombre' => $_POST['nombre'],
            'apellido' => $_POST['apellido'],
            'posicion' => $_POST['posicion'],
            'categoria' => $_POST['categoria'],
            'genero' => $_POST['genero'],
        ];

        if (isset($_POST['representante'])) {
            $validaciones['representante'] = ['regla' => '/^[1-9]+$/', 'mensaje' => 'Representante inválido.'];
            $datos['representante'] = $_POST['representante'];
        }
        if (isset($_POST['doc_i'])) {
            $validaciones['doc_i'] = ['regla' => '/^[0-9]{7,8}$/', 'mensaje' => 'Cedula inválida. Debe contener de 7 a 8 dígitos.'];
            $datos['doc_identidad'] = $_POST['doc_i'];
        }
        if (isset($_POST['telefono'])) {
            $validaciones['telefono'] = ['regla' => '/^[0-9]{4}[-]{1}[0-9]{7}$/', 'mensaje' => 'Telefono invalido.'];
            $datos['telefono'] = $_POST['telefono'];
        }
        if (isset($_POST['direccion'])) {
            $validaciones['direccion'] = ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,150}$/', 'mensaje' => 'Direccion inválida.'];
            $datos['direccion'] = $_POST['direccion'];
        }

        validar_datos($validaciones);

        if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('La foto del atleta es obligatoria.');
        }

        $fecha_nac = $_POST['fecha_nac'];
        $anio_nac = (int)date('Y', strtotime($fecha_nac));
        $anio_act = (int)date('Y'); // 2026
        $edad_cal = $anio_act - $anio_nac;
        if ($edad_cal < 18) {
            if (empty($_POST['representante']) || $_POST['representante'] == "0") {
                throw new Exception('El atleta es menor de edad necesita asociar un representante.');
            }
        }
        if ($edad_cal > 9) {
            if (empty($_POST['doc_i'])) {
                throw new Exception('Necesita ingresar el documento de identidad del atleta.');
            }
        }

        $foto_nombre = subirImagen($_FILES['foto'], 'atleta', $datos['fecha_nac'], 'atletas', 'default.png');

        $datos['foto'] = [$foto_nombre];
        $datos['accion'] = 'incluir';
        $resultado = $obj->ProcesarDatos($datos);
        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Registro al Atleta: " . $datos['nombre'] . " " . $datos['apellido']);
            $resultado = array('accion' => 'incluir', 'mensaje' => 'Atleta registrado exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {

            $resultado['mensaje'] = match ($resultado['codigo']) {
                DUPLICATE_CEDULA => 'La cedula ingresada ya pertenece a un atleta registrado.',
                DUPLICATE_PHONE  => 'El telefono ingresado ya pertenece a un atleta registrado.',
                INVALID_ID       => 'La categoria ingresada no existe en los registros del club.',
                INVALID_ID . '0'   => 'La posicion ingresada no existe en los registros del club.',
                INVALID_ID . '1'   => 'El representante ingresado no existe en los registros del club.',
                default          => 'Ocurrió un error inesperado en el registro.'
            };
        }
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Atletas', $e->getMessage(), 'Controlador_Incluir');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}
function modificar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $validaciones = [
            'id' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.'],
            'fecha_nac' => ['regla'   => '/^\d{4}-\d{2}-\d{2}$/', 'mensaje' => 'Formato de fecha inválido. Use AAAA-MM-DD.'],
            'nombre'   => ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,60}$/', 'mensaje' => 'Nombres inválido.'],
            'apellido' => ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,60}$/', 'mensaje' => 'Apellidos inválido.'],
            'categoria' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Categoria inválida.'],
            'posicion' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Posición inválida.'],
            'genero' => ['regla'   => '/^[HM]$/', 'mensaje' => 'Genero inválido.'],
            'foto_actual' => ['regla'   => '/^atleta_\d{4}-\d{2}-\d{2}_\d+\.(png|jpg|jpeg|webp)$/', 'mensaje' => 'El nombre de la foto tiene un formato inválido o una extensión no permitida.'],
        ];

        $datos = [
            'id' => $_POST['id'],
            'fecha_nac' => $_POST['fecha_nac'],
            'nombre' => $_POST['nombre'],
            'apellido' => $_POST['apellido'],
            'posicion' => $_POST['posicion'],
            'categoria' => $_POST['categoria'],
            'genero' => $_POST['genero'],
        ];

        if (isset($_POST['representante'])) {
            $validaciones['representante'] = ['regla' => '/^[1-9]+$/', 'mensaje' => 'Representante inválido.'];
            $datos['representante'] = $_POST['representante'];
        }
        if (isset($_POST['doc_i'])) {
            $validaciones['doc_i'] = ['regla' => '/^[0-9]{7,8}$/', 'mensaje' => 'Cedula inválida. Debe contener de 7 a 8 dígitos.'];
            $datos['doc_identidad'] = $_POST['doc_i'];
        }
        if (isset($_POST['telefono'])) {
            $validaciones['telefono'] = ['regla' => '/^[0-9]{4}[-]{1}[0-9]{7}$/', 'mensaje' => 'Telefono invalido.'];
            $datos['telefono'] = $_POST['telefono'];
        }
        if (isset($_POST['direccion'])) {
            $validaciones['direccion'] = ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,150}$/', 'mensaje' => 'Direccion inválida.'];
            $datos['direccion'] = $_POST['direccion'];
        }

        validar_datos($validaciones);


        $fecha_nac = $_POST['fecha_nac'];
        $anio_nac = (int)date('Y', strtotime($fecha_nac));
        $anio_act = (int)date('Y'); // 2026
        $edad_cal = $anio_act - $anio_nac;
        if ($edad_cal < 18) {
            if (empty($_POST['representante']) || $_POST['representante'] == "0") {
                throw new Exception('El atleta es menor de edad necesita asociar un representante.');
            }
        }
        if ($edad_cal > 9) {
            if (empty($_POST['doc_i'])) {
                throw new Exception('Necesita ingresar el documento de identidad del atleta.');
            }
        }

        if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            $foto_nombre = $_POST['foto_actual'];
        }else{
            $foto_nombre = subirImagen($_FILES['foto'], 'atleta', $datos['fecha_nac'], 'atletas', $_POST['foto_actual']);
        }

        $datos['foto'] = [$foto_nombre];
        $datos['accion'] = 'modificar';
        $resultado = $obj->ProcesarDatos($datos);
        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Modifico al Atleta: " . $datos['nombre'] . " " . $datos['apellido']);
            $resultado = array('accion' => 'modificar', 'mensaje' => 'Atleta modificado exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {

            $resultado['mensaje'] = match ($resultado['codigo']) {
                DUPLICATE_CEDULA => 'La cedula ingresada ya pertenece a un atleta registrado.',
                DUPLICATE_PHONE  => 'El telefono ingresado ya pertenece a un atleta registrado.',
                INVALID_ID       => 'La categoria ingresada no existe en los registros del club.',
                INVALID_ID . '0'   => 'La posicion ingresada no existe en los registros del club.',
                INVALID_ID . '1'   => 'El representante ingresado no existe en los registros del club.',
                default          => 'Ocurrió un error inesperado en el registro.'
            };
        }
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Atletas', $e->getMessage(), 'Controlador_Modificar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}
