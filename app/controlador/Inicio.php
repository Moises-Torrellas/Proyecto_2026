<?php
// app/controlador/Inicio.php

use App\modelo\ModeloInicio;

// 1. Cargamos las funciones base
require_once(__DIR__ . "/Base.php");

// 2. Configuración del módulo
$id_modulo = _MD_INICIO_;

if (isset($_SESSION['id'])) {
    procesarPermisos($id_modulo, '');
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
        header("Location:" . "Principal");
        exit();
    }
    cargarVista($pagina);
}

/**
 * Maneja las solicitudes POST entrantes del módulo de inicio
 */
function manejarSolicitudInicio($obj, $id_modulo, $bitacoraObj): void
{
    try {
        // Validar Token CSRF (Descomentar si reactivas la seguridad en producción)
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
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
        exit();
    }
}

function ejecutarLogin($obj, $id_modulo, $bitacoraObj): void
{
    /*
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
    
    // Tomamos la clave secreta directamente de las variables de entorno
    $recaptcha_secret = $_ENV['RECAPTCHA_SECRET_KEY'] ?? ''; 

    if (empty($recaptcha_response)) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['accion' => 'error', 'resultado' => 0, 'mensaje' => 'Por favor, completa el CAPTCHA.']);
        exit();
    }

    // Petición a los servidores de Google para validar el token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'secret' => $recaptcha_secret,
        'response' => $recaptcha_response
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $respuesta_curl = curl_exec($ch);
    curl_close($ch);

    $datos_recaptcha = json_decode($respuesta_curl);

    if (!$datos_recaptcha->success) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['accion' => 'error', 'resultado' => 0, 'mensaje' => 'Validación de CAPTCHA fallida. Intenta de nuevo.']);
        exit();
    } */

    validarCredenciales($_POST['cedula'] ?? '', $_POST['contraseña'] ?? '');

    $datos = [
        'cedula' => $_POST['cedula'],
        'clave'  => $_POST['contraseña']
    ];

    $respuesta = $obj->ProcesarDatos($datos);

    if (isset($respuesta['resultado']) && $respuesta['resultado'] == 1) {

        // Creación de variables de sesión
        $_SESSION['id']        = $respuesta['datos']['idUsuario'];
        $_SESSION['rol']       = $respuesta['datos']['nombre_rol'];
        $_SESSION['nombre']    = $respuesta['datos']['nombreUsuario'];
        $_SESSION['apellido']  = $respuesta['datos']['apellidoUsuario'];
        $_SESSION['telefono']  = $respuesta['datos']['telefonoUsuario'];
        $_SESSION['correo']    = $respuesta['datos']['correo'];
        $_SESSION['cedula']    = $respuesta['datos']['cedulaUsuario'];
        $_SESSION['nivel_rol'] = (int)$respuesta['datos']['nivel_rol'];
        $_SESSION['foto']      = $respuesta['datos']['foto'];
        
        // Mapeo de permisos
        $permisosIndexados = [];
        if (isset($respuesta['permisos']) && is_array($respuesta['permisos'])) {
            foreach ($respuesta['permisos'] as $p) {
                $idModulo = $p['id_modulo'];
                if (!isset($permisosIndexados[$idModulo])) {
                    $permisosIndexados[$idModulo] = [];
                }
                $permisosIndexados[$idModulo][$p['clave']] = true;
            }
        }
        $_SESSION['permisos'] = $permisosIndexados;
        
        //registrarBitacora($bitacoraObj, $id_modulo, 'Inicio de sesión exitoso');

        $respuestaFinal = [
            'accion'    => 'inicio',
            'resultado' => 1,
            'mensaje'   => '¡Autenticación exitosa!',
            'url'       => 'Principal'
        ];
    } else {
        // Manejo de errores devueltos por el modelo
        if ($respuesta['accion'] == 'error') {
            $respuestaFinal = [
                'accion' => 'error',
                'resultado' => 500,
                'mensaje' => 'Error: Servidor de base de datos no disponible.'
            ];
        } else if ($respuesta['accion'] == 'bloqueado') {
            if ($bitacoraObj !== null && isset($respuesta['idUsuario'])) {
                // Registro manual porque no hay sesión iniciada
                $bitacoraObj->RegistrarAccion($id_modulo, 'Usuario bloqueado por exceder límite de intentos', $respuesta['idUsuario']);
            }
            $respuestaFinal = [
                'accion'    => 'bloqueado',
                'resultado' => 0,
                'mensaje'   => $respuesta['mensaje']
            ];
        } else {
            $respuestaFinal = [
                'accion'    => 'inicio',
                'resultado' => isset($respuesta['resultado']) ? $respuesta['resultado'] : 3,
                'mensaje'   => isset($respuesta['mensaje']) ? $respuesta['mensaje'] : 'La cédula o contraseña no coinciden.'
            ];
        }
    }

    header('Content-Type: application/json; charset=utf-8'); 
    echo json_encode($respuestaFinal);
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
