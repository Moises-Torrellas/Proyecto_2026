<?php

use App\modelo\ModeloUsuarios;
use App\modelo\ModeloRoles;
use App\servicios\ReporteUsuario;

// 1. Cargamos las funciones base
require_once __DIR__ . '/Base.php';

// 2. Configuración del módulo
$id_modulo = _MD_USUARIOS_;

// 3. Procesar permisos (esto llena la variable global $permisosGenerales)
$permisos = procesarPermisos($id_modulo, $bitacora ?? null);

// 4. Lógica de despacho (Router interno)
$nombreClaseModelo = 'App\modelo\ModeloUsuarios';

if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloUsuarios();

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitudUsuarios($objModelo, $id_modulo, $bitacora ?? null, $permisos);
} else {
    cargarVista($pagina);
}

function manejarSolicitudUsuarios($obj, $id_modulo, $bitacoraObj, $permisos): void
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
                consultarUsuarios($obj);
                break;
                
            case 'consultarRoles':
                $modeloRoles = new ModeloRoles();
                consultarRoles($modeloRoles);
                break;
                
            case 'incluir':
                if (!$permisos['incluir']) throw new Exception('No tienes permisos para registrar usuarios.');
                incluirUsuario($obj, $id_modulo, $bitacoraObj);
                break;
                
            case 'modificar':
                if (!$permisos['modificar']) throw new Exception('No tienes permisos para modificar usuarios.');
                modificarUsuario($obj, $id_modulo, $bitacoraObj);
                break;
                
            case 'eliminar':
                if (!$permisos['eliminar']) throw new Exception('No tiene permisos para eliminar usuarios.');
                eliminarUsuario($obj, $id_modulo, $bitacoraObj);
                break;
                
            case 'buscar':
                if (!$permisos['modificar']) throw new Exception('No tiene permisos para buscar/ver detalles.');
                buscarUsuario($obj);
                break;
                
            case 'bloquear':
                if (!$permisos['otros']) throw new Exception('No tiene permisos para bloquear usuarios.');
                bloquearUsuario($obj, $id_modulo, $bitacoraObj);
                break;
                
            case 'generar':
                if (!$permisos['reporte']) throw new Exception('No tienes permisos para reportes.');
                $reporte = new ReporteUsuario();
                generarReporteUsuarios($obj, $reporte);
                break;
                
            default:
                throw new Exception('Acción no permitida.');
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function consultarUsuarios($obj): void
{
    $filtro['filtro'] = $_POST['filtro'] ?? '';
    $respuesta = $obj->Consultar($filtro);
    echo json_encode($respuesta);
}

function consultarRoles($obj): void
{
    $roles = $obj->consultar();
    $roles['accion'] = 'consultarRoles';
    echo json_encode($roles);
}

function incluirUsuario($obj, $id_modulo, $bitacoraObj): void
{
    $validaciones = [
        'cedula' => ['regla' => '/^[0-9]{7,8}$/', 'mensaje' => 'Cédula inválida. Debe contener de 7 a 8 dígitos.'],
        'nombre' => ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,30}$/', 'mensaje' => 'Nombre inválido. Solo letras y espacios.'],
        'apellido' => ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,30}$/', 'mensaje' => 'Apellido inválido. Solo letras y espacios.'],
        'telefono' => ['regla' => '/^[0-9]{4}[-]{1}[0-9]{7}$/', 'mensaje' => 'Teléfono inválido.'],
        'contraseña' => ['regla' => '/^(?=.*[0-9])(?=.*[A-Z])(?=.*[a-z])(?=.*[!@#\$%\^\&*\)\(+=._-])[0-9A-Za-z!@#\$%\^\&*\)\(+=._-]{8,20}$/', 'mensaje' => 'Contraseña inválida (requiere mayúscula, minúscula, número y símbolo).'],
        'correo' => ['regla' => '/^(?=.{3,60}$)[^\s@]+@[^\s@]+\.(com|org|net|edu|gov|mil|info|io|co|es|mx|ar|cl|pe|br|ve)$/', 'mensaje' => 'Correo electrónico inválido.'],
        'rol' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Rol inválido.']
    ];

    validar_datos($validaciones);

    $datos = [
        'cedula' => $_POST['cedula'],
        'nombre' => $_POST['nombre'],
        'apellido' => $_POST['apellido'],
        'telefono' => $_POST['telefono'],
        'contraseña' => $_POST['contraseña'],
        'correo' => $_POST['correo'],
        'roles_id' => $_POST['rol'],
        'accion' => 'incluir'
    ];

    $resultado = $obj->procesarDatos($datos);

    if (isset($resultado['accion']) && $resultado['accion'] === 'incluir') {
        registrarBitacora($bitacoraObj, $id_modulo, "Registró el usuario: " . $_POST['cedula']);
    }

    echo json_encode($resultado);
}

function modificarUsuario($obj, $id_modulo, $bitacoraObj): void
{
    $validaciones = [
        'id' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.'],
        'cedula' => ['regla' => '/^[0-9]{7,8}$/', 'mensaje' => 'Cédula inválida.'],
        'nombre' => ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,30}$/', 'mensaje' => 'Nombre inválido.'],
        'apellido' => ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,30}$/', 'mensaje' => 'Apellido inválido.'],
        'telefono' => ['regla' => '/^[0-9]{4}[-]{1}[0-9]{7}$/', 'mensaje' => 'Teléfono inválido.'],
        'correo' => ['regla' => '/^(?=.{3,60}$)[^\s@]+@[^\s@]+\.(com|org|net|edu|gov|mil|info|io|co|es|mx|ar|cl|pe|br|ve)$/', 'mensaje' => 'Correo electrónico inválido.'],
        'rol' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Rol inválido.']
    ];

    if (!empty($_POST['contraseña'])) {
        $validaciones['contraseña'] = ['regla' => '/^(?=.*[0-9])(?=.*[A-Z])(?=.*[a-z])(?=.*[!@#\$%\^\&*\)\(+=._-])[0-9A-Za-z!@#\$%\^\&*\)\(+=._-]{8,20}$/', 'mensaje' => 'Contraseña inválida.'];
    }

    validar_datos($validaciones);

    $datos = [
        'id' => $_POST['id'],
        'cedula' => $_POST['cedula'],
        'nombre' => $_POST['nombre'],
        'apellido' => $_POST['apellido'],
        'telefono' => $_POST['telefono'],
        'correo' => $_POST['correo'],
        'roles_id' => $_POST['rol'],
        'accion' => 'modificar'
    ];

    if (!empty($_POST['contraseña'])) {
        $datos['contraseña'] = $_POST['contraseña'];
    }

    $resultado = $obj->procesarDatos($datos);

    if (isset($resultado['accion']) && $resultado['accion'] === 'modificar') {
        registrarBitacora($bitacoraObj, $id_modulo, "Modificó un usuario ID: " . $_POST['id']);
    }

    echo json_encode($resultado);
}

function eliminarUsuario($obj, $id_modulo, $bitacoraObj): void
{
    validar_datos(['id' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.']]);

    if ($_POST['id'] == $_SESSION['id']) {
        throw new Exception('No puedes eliminar tu propio usuario.');
    }

    $resultado = $obj->procesarDatos(['id' => $_POST['id'], 'accion' => 'eliminar']);

    if (isset($resultado['accion']) && $resultado['accion'] === 'eliminar') {
        registrarBitacora($bitacoraObj, $id_modulo, "Eliminó un usuario de forma exitosa");
    }

    echo json_encode($resultado);
}

function buscarUsuario($obj): void
{
    validar_datos(['id' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.']]);

    $resultado = $obj->procesarDatos(['id' => $_POST['id'], 'accion' => 'buscar']);
    echo json_encode($resultado);
}

function bloquearUsuario($obj, $id_modulo, $bitacoraObj): void
{
    validar_datos([
        'id' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.'],
        'bloqueo' => ['regla' => '/^[1-2]+$/', 'mensaje' => 'Error interno de bloqueo.']
    ]);

    if ($_POST['id'] == $_SESSION['id']) {
        throw new Exception('No puedes bloquear tu propio usuario.');
    }

    $resultado = $obj->procesarDatos(['id' => $_POST['id'], 'bloqueo' => $_POST['bloqueo'], 'accion' => 'bloquear']);

    if (isset($resultado['tipo'])) {
        registrarBitacora($bitacoraObj, $id_modulo, ucfirst($resultado['tipo']) . " usuario ID: " . $_POST['id']);
    }

    echo json_encode($resultado);
}

function generarReporteUsuarios($obj, $reporte): void
{
    $validacionesReporte = [];
    $datosFiltro = ['accion' => 'reporte'];

    if (!empty($_POST['cedula'])) {
        $validacionesReporte['cedula'] = ['regla' => '/^[0-9]{1,8}$/', 'mensaje' => 'Cédula inválida.'];
        $datosFiltro['cedula'] = $_POST['cedula'];
    }
    if (!empty($_POST['nombre'])) {
        $validacionesReporte['nombre'] = ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{1,30}$/', 'mensaje' => 'Nombre inválido.'];
        $datosFiltro['nombre'] = $_POST['nombre'];
    }
    if (!empty($_POST['apellido'])) {
        $validacionesReporte['apellido'] = ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{1,30}$/', 'mensaje' => 'Apellido inválido.'];
        $datosFiltro['apellido'] = $_POST['apellido'];
    }
    if (!empty($_POST['rol'])) {
        $validacionesReporte['rol'] = ['regla' => '/^[0-9]+$/', 'mensaje' => 'Rol inválido.'];
        $datosFiltro['roles_id'] = $_POST['rol'];
    }

    // Solo valida si se envió algún filtro, de lo contrario asume reporte general
    if (!empty($validacionesReporte)) {
        validar_datos($validacionesReporte);
    }

    $resultado = $obj->procesarDatos($datosFiltro);

    if ($resultado['accion'] === 'consultar' && !empty($resultado['datos'])) {
        $respuesta = $reporte->crearPdfUsuarios($resultado['datos']);
        echo json_encode($respuesta);
    } else {
        throw new Exception('No se encontraron registros para el reporte.');
    }
}