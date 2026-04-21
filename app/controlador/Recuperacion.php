<?php

use App\modelo\ModeloRecuperacion;

// 1. Cargamos las funciones base
require_once __DIR__ . '/Base.php';

// 2. Configuración del módulo
$id_modulo = _MD_RECUPERACION_;

// 3. Procesar permisos (llena la variable global $permisosGenerales)
// Se asume que $bitacora llega desde el router/index
procesarPermisos($id_modulo, $bitacora ?? null);

// 4. Lógica de despacho
$nombreClaseModelo = 'App\modelo\ModeloRecuperacion';

if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloRecuperacion();

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitudRecuperacion($objModelo, $id_modulo, $bitacora ?? null);
} else {
    cargarVista($pagina);
}

function manejarSolicitudRecuperacion($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $tokenRecibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!isset($_SESSION['token']) || !hash_equals($_SESSION['token'], $tokenRecibido)) {
            throw new Exception('Error de seguridad: Token inválido o expirado.');
        }

        $accion = isset($_POST['accion']) ? filter_var($_POST['accion'], FILTER_SANITIZE_SPECIAL_CHARS) : '';

        switch ($accion) {
            case 'comprobar':
                comprobarUsuario($obj);
                break;
            case 'comprobarCodigo':
                comprobarCodigoSeguridad($obj);
                break;
            case 'reenviar':
                reenviarCodigoRecuperacion($obj);
                break;
            case 'cambiar':
                cambiarPassword($obj, $id_modulo, $bitacoraObj);
                break;
            default:
                throw new Exception('Acción no reconocida en el sistema de recuperación.');
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function comprobarUsuario($obj): void
{
    $validaciones = [
        'cedula' => ['regla' => '/^[0-9]{7,8}$/', 'mensaje' => 'Cédula inválida. Debe contener de 7 a 8 dígitos.'],
    ];
    
    validar_datos($validaciones);
    
    $datos = ['cedula' => $_POST['cedula'], 'accion' => 'comprobar'];
    $resultado = $obj->procesarDatos($datos);
    echo json_encode($resultado);
}

function comprobarCodigoSeguridad($obj): void
{
    $validaciones = [
        'codigo' => ['regla' => '/^[0-9]{6}$/', 'mensaje' => 'El código solo contiene 6 números.'],
    ];
    
    validar_datos($validaciones);
    
    $datos = ['codigo' => $_POST['codigo'], 'accion' => 'comprobarCodigo'];
    $resultado = $obj->procesarDatos($datos);
    echo json_encode($resultado);
}

function reenviarCodigoRecuperacion($obj): void
{
    // No requiere validación de POST ya que usa datos de sesión usualmente
    $resultado = $obj->reenviar();
    echo json_encode($resultado);
}

function cambiarPassword($obj, $id_modulo, $bitacoraObj): void
{
    $validaciones = [
        'contraseña' => [
            'regla' => '/^(?=.*[0-9])(?=.*[A-Z])(?=.*[a-z])(?=.*[!@#\$%\^\&*\)\(+=._-])[0-9A-Za-z!@#\$%\^\&*\)\(+=._-]{8,20}$/', 
            'mensaje' => 'Contraseña inválida. Debe tener entre 8 y 20 caracteres, incluir mayúscula, minúscula, número y símbolo.'
        ]
    ];
    
    validar_datos($validaciones);
    
    $datos = [
        'contraseña' => $_POST['contraseña'],
        'accion' => 'cambiar'
    ];
    
    $resultado = $obj->procesarDatos($datos);
    
    // Si el cambio fue exitoso, lo registramos en la bitácora
    if (isset($resultado['accion']) && $resultado['accion'] === 'cambio_exitoso') {
        registrarBitacora($bitacoraObj, $id_modulo, "El usuario restableció su contraseña satisfactoriamente.");
    }
    
    echo json_encode($resultado);
}