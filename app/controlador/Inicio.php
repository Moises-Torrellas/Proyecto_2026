<?php
// app/controlador/Inicio.php

use App\modelo\ModeloInicio;
use App\modelo\ModeloNotificaciones;
use App\servicios\verificarEvento;

// 1. Cargamos las funciones base
require_once(__DIR__ . "/Base.php");

// 2. Configuración del módulo
$id_modulo = _MD_INICIO_;

if (isset($_SESSION['id'])) {
    procesarPermisos($id_modulo, $bitacora ?? null);
}

// 3. Lógica de despacho (Router)
$nombreClaseModelo = 'App\modelo\ModeloInicio';

if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloInicio();

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitudInicio($objModelo, $id_modulo, $bitacora ?? null);
} else {
    if (isset($_SESSION['id'])) {
        header("Location:" . _URL_ . "Principal");
        exit();
    }
    cargarVista($pagina);
}


function manejarSolicitudInicio($obj, $id_modulo, $bitacoraObj): void
{
    try {
        // Validar Token CSRF
        $tokenRecibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!isset($_SESSION['token']) || !hash_equals($_SESSION['token'], $tokenRecibido)) {
            throw new Exception('Error de seguridad: Token inválido o expirado.');
        }

        $accion = isset($_POST['accion']) ? filter_var($_POST['accion'], FILTER_SANITIZE_SPECIAL_CHARS) : '';

        switch ($accion) {
            case 'inicio':
                ejecutarLogin($obj, $id_modulo, $bitacoraObj);
                break;
            default:
                throw new Exception('Acción no permitida.');
        }
    } catch (Exception $e) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
        exit();
    }
}

function ejecutarLogin($obj, $id_modulo, $bitacoraObj): void
{
    validarCredenciales($_POST['cedula'] ?? '', $_POST['contraseña'] ?? '');

    $datos = [
        'cedula' => $_POST['cedula'],
        'clave' => $_POST['contraseña']
    ];

    $respuesta = $obj->ProcesarDatos($datos);

    if (isset($respuesta['resultado']) && $respuesta['resultado'] == 1) {

        $_SESSION['id']        = $respuesta['datos']['idUsuario'];
        $_SESSION['rol']       = $respuesta['datos']['nombre_rol'];
        $_SESSION['nombre']    = $respuesta['datos']['nombreUsuario'];
        $_SESSION['apellido']  = $respuesta['datos']['apellidoUsuario'];
        $_SESSION['nivel_rol'] = $respuesta['datos']['nivel_rol'];
        $_SESSION['foto']      = $respuesta['datos']['foto'];

        // ====================================================================
        // MAPEO DE PERMISOS REALES DESDE LA TABLA PERMISOS_USUARIOS
        // ====================================================================
        $permisosIndexados = [];
        if (isset($respuesta['permisos']) && is_array($respuesta['permisos'])) {
            foreach ($respuesta['permisos'] as $p) {
                $permisosIndexados[$p['id_modulo']] = [
                    'ingresar'  => (isset($p['ingresar']) && $p['ingresar'] == 1),
                    'registrar' => (isset($p['registrar']) && $p['registrar'] == 1),
                    'modificar' => (isset($p['modificar']) && $p['modificar'] == 1),
                    'eliminar'  => (isset($p['eliminar']) && $p['eliminar'] == 1),
                    'reporte'   => (isset($p['reporte']) && $p['reporte'] == 1),
                    'otros'     => (isset($p['otros']) && $p['otros'] == 1),
                ];
            }
        }
        $_SESSION['permisos'] = $permisosIndexados;

        registrarBitacora($bitacoraObj, $id_modulo, 'Inicio de sesión exitoso');

        // ====================================================================
        // INTEGRACIÓN BLINDADA DEL VERIFICADOR AUTOMÁTICO DE EVENTOS (LAZY CRON)
        // ====================================================================
        ob_start(); 
        try {
            if (class_exists('App\modelo\ModeloNotificaciones')) {
                $notificacion = new ModeloNotificaciones();

                if (!$notificacion->verificarChequeoDeHoy()) {
                    if (class_exists('App\servicios\verificarEvento')) {
                        $verificador = new verificarEvento();
                        $verificador->procesar(); 
                    }
                }
            }
        } catch (\Throwable $cronException) {
            if (function_exists('logs')) {
                logs('Notificaciones_Cron', $cronException->getMessage(), 'Fallo_Chequeo_Login');
            }
        }
        ob_end_clean(); 
        // ====================================================================
    }

    // ====================================================================
    // BLINDAJE ANTI-WARNINGS: Limpia basuras del buffer antes de responder JSON
    // ====================================================================
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($respuesta);
    exit();
}

function validarCredenciales($cedula, $clave): void
{
    if (empty($cedula) || empty($clave)) {
        throw new Exception('Todos los campos son obligatorios.');
    }

    if (!preg_match('/^[0-9]{7,8}$/', $cedula)) {
        throw new Exception('La cédula debe tener entre 7 y 8 dígitos.');
    }

    $regexClave = '/^(?=.*[0-9])(?=.*[A-Z])(?=.*[a-z])(?=.*[!@#\$%\^\&*\)\(+=._-])[0-9A-Za-z!@#\$%\^\&*\)\(+=._-]{8,20}$/';
    if (!preg_match($regexClave, $clave)) {
        throw new Exception('Contraseña inválida. Debe tener entre 8 y 20 caracteres, incluir mayúscula, minúscula, número y símbolo.');
    }
}