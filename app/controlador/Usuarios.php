<?php

use App\modelo\ModeloUsuarios;

// 1. Cargamos las funciones base
require_once __DIR__ . '/Base.php';

// 2. Configuración del módulo
$id_modulo = _MD_USUARIOS_;

// 3. Procesar permisos (esto llena la variable global $permisosGenerales)
$permisos = procesarPermisos($id_modulo, $bitacora);

// 4. Lógica de despacho (Router interno)
$nombreClaseModelo = 'App\modelo\ModeloUsuarios';

if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloUsuarios();

if (/* comprobarAjax() && */!empty($_POST)) {
    manejarSolicitudUsuarios($objModelo, $id_modulo, $bitacora, $permisos);
} else {
    registrarBitacora($bitacora, $id_modulo, 'Ingreso al Modulo');
    $variables = ['permisos' => $permisos];
    cargarVista($pagina, $variables);
}

function manejarSolicitudUsuarios($obj, $id_modulo, $bitacoraObj, $permisos): void
{
    // Centralizamos la variable global de permisos aquí

    try {
        /* $tokenRecibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!isset($_SESSION['token']) || !hash_equals($_SESSION['token'], $tokenRecibido)) {
            throw new Exception('Error de seguridad: Token inválido o expirado.');
        } */

        $accion = isset($_POST['accion']) ? filter_var($_POST['accion'], FILTER_SANITIZE_SPECIAL_CHARS) : '';

        // Validamos permisos antes de ejecutar las funciones
        switch ($accion) {
            case 'consultar':
                consultarUsuarios($obj);
                break;

            case 'consultarRoles':
                consultarRoles($obj);
                break;

            case 'incluir':
                //if (!$permisos['registrar']) throw new Exception('No tienes permisos para registrar usuarios.');
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

            case 'CargarPermisosUsuario':
                if (!$permisos['otros']) throw new Exception('No tiene permisos para ver permisos de usuarios.');
                CargarPermisosUsuario($obj);
                break;

            case 'guardar_permisos_usuario':
                if (!$permisos['otros']) throw new Exception('No tiene permisos para modificar permisos de usuarios.');
                guardarPermisosUsuario($obj, $id_modulo, $bitacoraObj);
                break;

            default:
                throw new Exception('Acción no permitida.');
        }
    } catch (Exception $e) {
        logs('Usuarios', $e->getMessage(), 'Controlador_ManejarSolicitud');
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
    $respuesta = $obj->consultarRoles();
    if (isset($respuesta['accion']) && $respuesta['accion'] == 'error') {
        $respuesta['mensaje'] = 'Error al listar los roles';
    }
    echo json_encode($respuesta);
}

function incluirUsuario($obj, $id_modulo, $bitacoraObj): void
{
    try {
        logs('Usuarios', 'POST RECIBIDO: ' . print_r($_POST, true), 'Controlador_Incluir');
        if (empty($_POST)) {
            logs('Usuarios', '¡ALERTA! El array POST está vacío.', 'Controlador_Incluir');
        }
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

        $foto_nombre = subirImagen($_FILES['foto'], 'user', $_POST['cedula'], 'usuarios',);


        $datos = [
            'cedula'     => $_POST['cedula'],
            'nombre'     => $_POST['nombre'],
            'apellido'   => $_POST['apellido'],
            'telefono'   => $_POST['telefono'],
            'contraseña' => $_POST['contraseña'],
            'correo'     => $_POST['correo'],
            'roles_id'   => $_POST['rol'],
            'foto'       => $foto_nombre, // Se envía el nombre del archivo
            'accion'     => 'incluir'
        ];

        // 4. Ejecución en el Modelo
        $resultado = $obj->procesarDatos($datos);

        // 5. Auditoría y Respuesta
        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {

            registrarBitacora($bitacoraObj, $id_modulo, "Registro al usuario: " . $_POST['cedula'] . ' ' . $_POST['nombre'] . ' ' . $_POST['apellido']);
            $resultado = array('accion' => 'incluir', 'mensaje' => 'Usuario registrado exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {

            $resultado['mensaje'] = match ($resultado['codigo']) {
                VALIDATION       => 'Debe proporcionar una contraseña al crear el usuario.',
                INVALID_ID       => 'El rol con el que intenta registrar este usuario no existe.',
                DUPLICATE_CEDULA => 'La cedula ingresada ya pertenece a un usuario registrado.',
                DUPLICATE_PHONE  => 'El telefono ingresado ya pertenece a un usuario registrado.',
                DUPLICATE_EMAIL  => 'El correo ingresado ya pertenece a un usuario registrado.',
                default          => 'Ocurrió un error inesperado en el registro.'
            };
        }

        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Usuarios', $e->getMessage(), 'Controlador_Incluir');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function modificarUsuario($obj, $id_modulo, $bitacoraObj): void
{
    try {
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

        $foto_nombre = subirImagen($_FILES['foto'], 'user', $_POST['cedula'], 'usuarios', $_POST['foto_actual']);

        $datos = [
            'id' => $_POST['id'],
            'cedula' => $_POST['cedula'],
            'nombre' => $_POST['nombre'],
            'apellido' => $_POST['apellido'],
            'foto' => $foto_nombre,
            'telefono' => $_POST['telefono'],
            'correo' => $_POST['correo'],
            'roles_id' => $_POST['rol'],
            'accion' => 'modificar'
        ];

        if (!empty($_POST['contraseña'])) {
            $datos['contraseña'] = $_POST['contraseña'];
        }

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {

            registrarBitacora($bitacoraObj, $id_modulo, "Modifico al usuario: " . $_POST['cedula'] . ' ' . $_POST['nombre'] . ' ' . $_POST['apellido']);
            $resultado = array('accion' => 'modificar', 'mensaje' => 'Usuario modificado exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {

            $resultado['mensaje'] = match ($resultado['codigo']) {
                INVALID_ID       => 'El rol con el que intenta modificar este usuario no existe.',
                DUPLICATE_EMAIL  => 'El correo ingresado ya pertenece a un usuario registrado.',
                DUPLICATE_PHONE  => 'El telefono ingresado ya pertenece a un usuario registrado.',
                DUPLICATE_CEDULA => 'La cedula ingresada ya pertenece a un usuario registrado.',
                DUPLICATE_EMAIL . '0'  => 'El correo ya fue usado por otro usuario (eliminado). No se puede reutilizar.',
                DUPLICATE_CEDULA . '0'  => 'La cedula ya fue usado por otro usuario (eliminado). No se puede reutilizar.',
                DUPLICATE_PHONE . '0'  => 'El Telefono ya fue usado por otro usuario (eliminado). No se puede reutilizar.',
                default          => 'Ocurrió un error inesperado en la modificacion.'
            };
        }

        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Usuarios', $e->getMessage(), 'Controlador_Modificar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}
function eliminarUsuario($obj, $id_modulo, $bitacoraObj): void
{
    try {
        validar_datos(['id' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.']]);

        if ($_POST['id'] == $_SESSION['id']) {
            throw new Exception('No puedes eliminar tu propio usuario.');
        }

        $resultado = $obj->procesarDatos(['id' => $_POST['id'], 'accion' => 'eliminar']);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {

            registrarBitacora($bitacoraObj, $id_modulo, "Elimino al usuario: " . $_POST['id']);
            $resultado = array('accion' => 'eliminar', 'mensaje' => 'Usuario eliminado exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {

            $resultado['mensaje'] = match ($resultado['codigo']) {
                INVALID_ID       => 'El usuario que intenta eliminar ya no existe',
                ASSOCIATES  => 'No puede eliminar al Super Usuario',
                default          => 'Ocurrió un error inesperado en la eliminacion.'
            };
        }

        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Usuarios', $e->getMessage(), 'Controlador_Eliminar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function buscarUsuario($obj): void
{
    validar_datos(['id' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.']]);

    $resultado = $obj->procesarDatos(['id' => $_POST['id'], 'accion' => 'buscar']);
    echo json_encode($resultado);
}

function bloquearUsuario($obj, $id_modulo, $bitacoraObj): void
{
    try {
        validar_datos([
            'id' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.'],
            'bloqueo' => ['regla' => '/^[1-2]+$/', 'mensaje' => 'Error interno de bloqueo.']
        ]);

        if ($_POST['id'] == $_SESSION['id']) {
            throw new Exception('No puedes bloquear tu propio usuario.');
        }

        $datos = [
            'id' => $_POST['id'],
            'bloqueo' => $_POST['bloqueo'],
            'accion' => 'bloquear'
        ];

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            $nuevoEstado = ($_POST['bloqueo'] == 1) ? 2 : 1;
            $mensajeExito = ($nuevoEstado == 2) ? "Usuario bloqueado exitosamente." : "Usuario desbloqueado exitosamente.";
            $mensajeBitacora = ($nuevoEstado == 2) ? "Bloqueo al usuario: " : "Desbloqueo al usuario: ";
            registrarBitacora($bitacoraObj, $id_modulo, $mensajeBitacora . $_POST['id']);
            $resultado = array('accion' => 'bloquear', 'mensaje' => $mensajeExito);
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {

            $resultado['mensaje'] = match ($resultado['codigo']) {
                ASSOCIATES => 'El Super Usuario no puede ser bloqueado.',
                INVALID_ID => 'El usuario que intenta modificar ya no existe.',
                default    => 'No se pudo completar la operación de bloqueo.'
            };
        }

        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Usuarios', $e->getMessage(), 'Controlador_Bloquear');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function CargarPermisosUsuario($obj): void
{
    try {
        validar_datos(['id' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.']]);

        $datos = ['id' => $_POST['id'], 'accion' => 'CargarPermisosUsuario'];
        $resultado = $obj->procesarDatos($datos);
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Usuarios', $e->getMessage(), 'Controlador_CargarPermisosUsuario');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function guardarPermisosUsuario($obj, $id_modulo, $bitacoraObj): void
{
    try {
        validar_datos([
            'id' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.']
        ]);

        $datos = [
            'id'             => $_POST['id'],
            'accion'         => 'guardar_permisos_usuario'
        ];

        $validacionesP = [];
        if (isset($_POST['check_ingresar']) && !empty($_POST['check_ingresar'])) {
            $validacionesP['check_ingresar'] = ['regla' => '/^[1]+$/', 'mensaje' => 'Valor de permiso ingresar inválido.'];
            $datos['c_ingresar'] = $_POST['check_ingresar'];
        }
        if (isset($_POST['check_registrar']) && !empty($_POST['check_registrar'])) {
            $validacionesP['check_registrar'] = ['regla' => '/^[1]+$/', 'mensaje' => 'Valor de permiso registrar inválido.'];
            $datos['c_registrar'] = $_POST['check_registrar'];
        }
        if (isset($_POST['check_modificar']) && !empty($_POST['check_modificar'])) {
            $validacionesP['check_modificar'] = ['regla' => '/^[1]+$/', 'mensaje' => 'Valor de permiso modificar inválido.'];
            $datos['c_modificar'] = $_POST['check_modificar'];
        }
        if (isset($_POST['check_eliminar']) && !empty($_POST['check_eliminar'])) {
            $validacionesP['check_eliminar'] = ['regla' => '/^[1]+$/', 'mensaje' => 'Valor de permiso eliminar inválido.'];
            $datos['c_eliminar'] = $_POST['check_eliminar'];
        }
        if (isset($_POST['check_reporte']) && !empty($_POST['check_reporte'])) {
            $validacionesP['check_reporte'] = ['regla' => '/^[1]+$/', 'mensaje' => 'Valor de permiso reporte inválido.'];
            $datos['c_reporte'] = $_POST['check_reporte'];
        }
        if (isset($_POST['check_otros']) && !empty($_POST['check_otros'])) {
            $validacionesP['check_otros'] = ['regla' => '/^[1]+$/', 'mensaje' => 'Valor de permiso otras opciones inválido.'];
            $datos['c_otros'] = $_POST['check_otros'];
        }

        if (!empty($validacionesP)) {
            validarArrays($validacionesP);
        }

        $resultado = $obj->procesarDatos($datos);
        if ($resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Modifico permisos al usuario: " . $_POST['id']);
            echo json_encode(['accion' => 'guardar_permisos_usuario', 'mensaje' => 'Permisos guardados correctamente.']);
        } else {
            throw new Exception($resultado['codigo']);
        }
    } catch (Exception $e) {
        logs('Usuarios', $e->getMessage(), 'Controlador_GuardarPermisosUsuario');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

/* function generarReporteUsuarios($obj, $reporte): void
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
 */