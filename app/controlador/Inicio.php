<?php

use App\modelo\ModeloInicio;

// 1. Cargamos las funciones base
require_once(__DIR__ . "/Base.php");

// 2. Configuración del módulo


// En el login, generalmente no validamos permisos para entrar, 
// pero procesamos la bitácora si ya hay una sesión.
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
$id_modulo = _MD_INICIO_;
if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitudInicio($objModelo, $id_modulo, $bitacora ?? null);
} else {
    // Si ya está logueado, lo mandamos al Principal
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
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function ejecutarLogin($obj, $id_modulo, $bitacoraObj): void
{
    // 1. Validar datos de entrada
    validarCredenciales($_POST['cedula'] ?? '', $_POST['contraseña'] ?? '');

    $datos = [
        'cedula' => $_POST['cedula'],
        'clave' => $_POST['contraseña']
    ];

    // 2. Llamar al modelo
    $respuesta = $obj->ProcesarDatos($datos);

    // 3. Si el resultado es exitoso (1), configuramos la sesión
    if (isset($respuesta['resultado']) && $respuesta['resultado'] == 1) {

        $_SESSION['id']       = $respuesta['datos']['idUsuario'];
        $_SESSION['rol']      = $respuesta['datos']['nombre_rol'];
        $_SESSION['nombre']    = $respuesta['datos']['nombreUsuario'];
        $_SESSION['apellido']  = $respuesta['datos']['apellidoUsuario'];
        $_SESSION['nivel_rol']  = $respuesta['datos']['nivel_rol'];

        // Indexar permisos para acceso rápido en el resto del sistema
        $permisosIndexados = [];
        if (isset($respuesta['permisos']) && is_array($respuesta['permisos'])) {
            foreach ($respuesta['permisos'] as $p) {
                $permisosIndexados[$p['id_modulo']] = [
                    'incluir'   => ($p['incluir'] == 1),
                    'modificar' => ($p['modificar'] == 1),
                    'eliminar'  => ($p['eliminar'] == 1),
                    'reporte'   => ($p['reporte'] == 1),
                    'otros'     => ($p['otros'] == 1)
                ];
            }
        }
        $_SESSION['permisos'] = $permisosIndexados;

        // Registrar en bitácora
        registrarBitacora($bitacoraObj, $id_modulo, 'Inicio de sesión exitoso');
    }

    echo json_encode($respuesta);
}

/**
 * Validación específica para el login
 */
function validarCredenciales($cedula, $clave): void
{
    if (empty($cedula) || empty($clave)) {
        throw new Exception('Todos los campos son obligatorios.');
    }

    if (!preg_match('/^[0-9]{7,8}$/', $cedula)) {
        throw new Exception('La cédula debe tener entre 7 y 8 dígitos.');
    }

    // Validación de contraseña (mismo regex que tenías)
    $regexClave = '/^(?=.*[0-9])(?=.*[A-Z])(?=.*[a-z])(?=.*[!@#\$%\^\&*\)\(+=._-])[0-9A-Za-z!@#\$%\^\&*\)\(+=._-]{8,20}$/';
    if (!preg_match($regexClave, $clave)) {
        throw new Exception('Contraseña inválida. Debe tener entre 8 y 20 caracteres, incluir mayúscula, minúscula, número y símbolo.');
    }
}
