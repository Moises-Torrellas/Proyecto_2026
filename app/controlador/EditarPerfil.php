<?php

use App\modelo\ModeloUsuarios;

// 1. Cargamos las funciones base
require_once __DIR__ . '/Base.php';

// 2. Configuración del módulo
$id_modulo = _MD_USUARIOS_;

// 3. Lógica de despacho (Router interno para AJAX)
$nombreClaseModelo = 'App\modelo\ModeloUsuarios';

if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloUsuarios();

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitudPerfil($objModelo, $id_modulo, $bitacora);
} else {
    registrarBitacora($bitacora, $id_modulo, 'Ingreso a Editar Perfil');
    cargarVista($pagina, []);
}

function manejarSolicitudPerfil($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $tokenRecibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!isset($_SESSION['token']) || !hash_equals($_SESSION['token'], $tokenRecibido)) {
            throw new Exception('Error de seguridad: Token inválido o expirado.');
        }

        $accion = isset($_POST['accion']) ? filter_var($_POST['accion'], FILTER_SANITIZE_SPECIAL_CHARS) : '';

        switch ($accion) {
            case 'editar_personal':
                editarInformacionPersonal($obj, $id_modulo, $bitacoraObj);
                break;

            case 'editar_contacto':
                editarInformacionContacto($obj, $id_modulo, $bitacoraObj);
                break;

            case 'editar_seguridad':
                editarSeguridad($obj, $id_modulo, $bitacoraObj);
                break;

            default:
                throw new Exception('Acción no permitida.');
        }
    } catch (Exception $e) {
        logs('Perfil', $e->getMessage(), 'Controlador_ManejarSolicitudPerfil');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function editarInformacionPersonal($obj, $id_modulo, $bitacoraObj): void
{
    try {
        validar_requeridos(['id', 'cedula', 'nombre', 'apellido']);

        // Se verifica si se adjuntó un archivo nuevo
        $foto_nombre = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            // Nota: Si no se envía 'foto_actual' desde el form, se puede heredar de $_SESSION['foto']
            $foto_actual = $_SESSION['foto'] ?? '';
            $foto_nombre = subirImagen($_FILES['foto'], 'user', $_POST['cedula'], 'usuarios', $foto_actual);
        }

        $datos = [
            'id'           => $_POST['id'],
            'cedula'       => $_POST['cedula'],
            'nombre'       => $_POST['nombre'],
            'apellido'     => $_POST['apellido'],
            'tipo_edicion' => 'personal',
            'accion'       => 'editar_perfil'
        ];

        if ($foto_nombre !== null) {
            $datos['foto'] = $foto_nombre;
        }

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            // Se actualizan las variables de sesión activas para reflejar el cambio inmediato en la interfaz
            $_SESSION['cedula']   = $_POST['cedula'];
            $_SESSION['nombre']   = $_POST['nombre'];
            $_SESSION['apellido'] = $_POST['apellido'];
            if ($foto_nombre !== null) {
                $_SESSION['foto'] =  $foto_nombre; // Ajustar ruta según la estructura de subida
            }

            registrarBitacora($bitacoraObj, $id_modulo, "Actualizó su información personal de perfil.");
            $resultado = ['accion' => 'exito', 'mensaje' => 'Información personal actualizada con éxito.'];
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                DUPLICATE_CEDULA => 'La cédula ingresada ya pertenece a otro usuario registrado.',
                default          => 'Ocurrió un error inesperado al actualizar la información personal.'
            };
        }

        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Perfil', $e->getMessage(), 'Controlador_EditarPersonal');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function editarInformacionContacto($obj, $id_modulo, $bitacoraObj): void
{
    try {
        // Al no venir el ID en este formulario, se extrae de forma segura de la sesión activa
        $id_usuario = $_SESSION['id'] ?? '';
        if (empty($id_usuario)) {
            throw new Exception('Sesión de usuario no válida.');
        }

        validar_requeridos(['telefono', 'correo']);

        $datos = [
            'id'           => $id_usuario,
            'telefono'     => $_POST['telefono'],
            'correo'       => $_POST['correo'],
            'tipo_edicion' => 'contacto',
            'accion'       => 'editar_perfil'
        ];

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            $_SESSION['telefono'] = $_POST['telefono'];
            $_SESSION['correo']   = $_POST['correo'];

            registrarBitacora($bitacoraObj, $id_modulo, "Actualizó su información de contacto de perfil.");
            $resultado = ['accion' => 'exito', 'mensaje' => 'Información de contacto actualizada con éxito.'];
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                DUPLICATE_PHONE => 'El teléfono ingresado ya pertenece a otro usuario registrado.',
                DUPLICATE_EMAIL => 'El correo ingresado ya pertenece a otro usuario registrado.',
                default         => 'Ocurrió un error inesperado al actualizar la información de contacto.'
            };
        }

        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Perfil', $e->getMessage(), 'Controlador_EditarContacto');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function editarSeguridad($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $id_usuario = $_SESSION['id'] ?? '';
        if (empty($id_usuario)) {
            throw new Exception('Sesión de usuario no válida.');
        }

        validar_requeridos(['contrasena', 'confirmar_contrasena']);

        if ($_POST['contrasena'] !== $_POST['confirmar_contrasena']) {
            throw new Exception('Las contraseñas ingresadas no coinciden.');
        }

        $datos = [
            'id'           => $id_usuario,
            'contraseña'   => $_POST['contrasena'], // Se asocia con la clave esperada por el modelo
            'tipo_edicion' => 'seguridad',
            'accion'       => 'editar_perfil'
        ];

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Actualizó sus credenciales de seguridad de perfil.");
            $resultado = ['accion' => 'exito', 'mensaje' => 'Contraseña actualizada con éxito.'];
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = 'Ocurrió un error inesperado al actualizar la seguridad.';
        }

        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Perfil', $e->getMessage(), 'Controlador_EditarSeguridad');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}