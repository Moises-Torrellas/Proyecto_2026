<?php

namespace App\controlador;

use App\modelo\ModeloUsuarios;
use App\controlador\Base;
use Exception;

class Usuarios extends Base // Heredar de la clase Base para manejar la bitácora y permisos
{

    public function __construct($bitacora)
    {
        // Llamar al constructor de la clase base
        parent::__construct($bitacora, _MD_USUARIOS_);
        $this->ProcesarPermisos();  // Llamar al método ProcesarPermisos()
    }

    public function ProcesarSolicitud(string $pagina): void
    {

        $nombreClase = 'App\modelo\ModeloUsuarios'; // Verificar si la clase existe
        if (!class_exists($nombreClase)) {
            require_once(__DIR__ . '/../vista/complementos/404.php');
            exit();
        } else {
            $obj = new ModeloUsuarios(); // Crear una instancia de la clase ModeloUsuarios
            if ($this->ComprobarAjax() && !empty($_POST)) { // Verificar si la solicitud es una solicitud AJAX
                $this->ManejarSolicitud($obj); // Manejar la solicitud AJAX
            } else {
                $this->CargarVista($pagina); // Cargar la vista
            }
        }
    }

    private function ManejarSolicitud($obj): void
    {
        try {
            $tokenRecibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''; // Obtener el token de seguridad

            if (!hash_equals($_SESSION['token'], $tokenRecibido)) { // Validar el token de seguridad
                throw new Exception('Error de seguridad: Token inválido o expirado.');
            }
            $accion = isset($_POST['accion']) ? filter_var($_POST['accion'], FILTER_SANITIZE_SPECIAL_CHARS) : '';
            switch ($accion) { // Realizar la acción correspondiente
                case 'consultar':
                    $this->Consultar($obj);
                    break;
                case 'consultarRoles':
                    $modeloRoles = new \App\modelo\ModeloRoles();
                    $this->ConsultarRoles($modeloRoles);
                    break;
                case 'incluir':
                    $this->IncluirUsuario($obj);
                    break;
                case 'eliminar':
                    $this->EliminarUsuario($obj);
                    break;
                case 'bloquear':
                    $this->BloquearUsuario($obj);
                    break;
                case 'modificar':
                    $this->ModificarUsuario($obj);
                    break;
                case 'buscar':
                    $this->BuscarUsuario($obj);
                    break;
                case 'generar':
                    $reporte = new \App\servicios\ReporteUsuario;
                    $this->GenerarReporte($obj, $reporte);
                    break;
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
            return;
        }
    }

    private function Consultar($obj): void
    {
        $filtro['filtro'] = $_POST['filtro'] ?? ''; // Obtener el filtro de la solicitud para la busqueda
        $respuesta = $obj->Consultar($filtro); // Realizar la consulta
        echo json_encode($respuesta);
    }

    private function ConsultarRoles($obj): void
    {
        $roles = $obj->consultar(); // Realizar la consulta
        $roles['accion'] = 'consultarRoles';
        echo json_encode($roles);
    }
    private function BuscarUsuario($obj): void
    {
        try {
            if ($this->modificar) {
                // Validar los datos
                $data = ['id' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.']];

                $this->validar_datos($data); // Validar los datos

                $datos = ['id' => $_POST['id'], 'accion' => 'buscar']; // Preparar los datos

                $resultado = $obj->procesarDatos($datos); // Procesar los datos
                echo json_encode($resultado);
            } else {
                throw new Exception('No tiene permisos para modificar usuarios.');
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
        }
    }

    private function IncluirUsuario($obj): void
    {
        try {
            if ($this->incluir) {

                $data = [
                    'cedula' => ['regla' => '/^[0-9]{7,8}$/', 'mensaje' => 'Cédula inválida. Debe contener de 7 a 8 dígitos.'],
                    'nombre' => ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,30}$/', 'mensaje' => 'Nombre inválido. Solo se permiten letras y espacios, entre 3 y 30 caracteres.'],
                    'apellido' => ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,30}$/', 'mensaje' => 'Apellido inválido. Solo se permiten letras y espacios, entre 3 y 30 caracteres.'],
                    'telefono' => ['regla' => '/^[0-9]{4}[-]{1}[0-9]{7}$/', 'mensaje' => 'Teléfono inválido. Debe contener entre 7 y 15 dígitos.'],
                    'contraseña' => ['regla' => '/^(?=.*[0-9])(?=.*[A-Z])(?=.*[a-z])(?=.*[!@#\$%\^\&*\)\(+=._-])[0-9A-Za-z!@#\$%\^\&*\)\(+=._-]{8,20}$/', 'mensaje' => 'Contraseña inválida. Debe tener entre 8 y 20 caracteres y contener al menos una mayúscula, una minúscula, un número y un símbolo especial.'],
                    'correo' => ['regla' => '/^(?=.{3,60}$)[^\s@]+@[^\s@]+\.(com|org|net|edu|gov|mil|info|io|co|es|mx|ar|cl|pe|br|ve)$/', 'mensaje' => 'Correo electrónico inválido.'],
                    'rol' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Rol inválido.']
                ]; // Validar los datos

                $this->validar_datos($data); // Validar los datos
 
                $datos = [
                    'cedula' => $_POST['cedula'],
                    'nombre' => $_POST['nombre'],
                    'apellido' => $_POST['apellido'],
                    'telefono' => $_POST['telefono'],
                    'contraseña' => $_POST['contraseña'],
                    'correo' => $_POST['correo'],
                    'roles_id' => $_POST['rol'],
                    'accion' => 'incluir'
                ]; // Preparar los datos

                $resultado = $obj->procesarDatos($datos); // Procesar los datos

                if (isset($resultado['accion']) && $resultado['accion'] === 'incluir') {
                    $this->Bitacora('Registró el usuario: ' . $_POST['cedula'] . ', ' . $_POST['nombre'] . ' ' . $_POST['apellido'] . ''); // Registrar la acción en la bitácora
                }
                echo json_encode($resultado);
            } else {
                throw new Exception('No tienes permisos para registrar usuarios.');
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
        }
    }

    private function ModificarUsuario($obj): void
    {
        try {
            if ($this->modificar) {

                $data = [
                    'id' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.'],
                    'cedula' => ['regla' => '/^[0-9]{7,8}$/', 'mensaje' => 'Cédula inválida. Debe contener de 7 a 8 dígitos.'],
                    'nombre' => ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,30}$/', 'mensaje' => 'Nombre inválido. Solo se permiten letras y espacios, entre 3 y 30 caracteres.'],
                    'apellido' => ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,30}$/', 'mensaje' => 'Apellido inválido. Solo se permiten letras y espacios, entre 3 y 30 caracteres.'],
                    'telefono' => ['regla' => '/^[0-9]{4}[-]{1}[0-9]{7}$/', 'mensaje' => 'Teléfono inválido. Debe contener entre 7 y 15 dígitos.'],
                    'correo' => ['regla' => '/^(?=.{3,60}$)[^\s@]+@[^\s@]+\.(com|org|net|edu|gov|mil|info|io|co|es|mx|ar|cl|pe|br|ve)$/', 'mensaje' => 'Correo electrónico inválido.'],
                    'rol' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Rol inválido.']
                ]; // Validar los datos

                if (isset($_POST['contraseña']) && !empty($_POST['contraseña'])) { // Validar la contraseña
                    $data['contraseña'] = ['regla' => '/^(?=.*[0-9])(?=.*[A-Z])(?=.*[a-z])(?=.*[!@#\$%\^\&*\)\(+=._-])[0-9A-Za-z!@#\$%\^\&*\)\(+=._-]{8,20}$/', 'mensaje' => 'Contraseña inválida. Debe tener entre 8 y 20 caracteres y contener al menos una mayúscula, una minúscula, un número y un símbolo especial.'];
                } 

                $this->validar_datos($data); // Validar los datos

                $datos = [
                    'id' => $_POST['id'],
                    'cedula' => $_POST['cedula'],
                    'nombre' => $_POST['nombre'],
                    'apellido' => $_POST['apellido'],
                    'telefono' => $_POST['telefono'],
                    'correo' => $_POST['correo'],
                    'roles_id' => $_POST['rol'],
                    'accion' => 'modificar'
                ]; // Preparar los datos

                if (isset($_POST['contraseña']) && !empty($_POST['contraseña'])) {
                    $datos['contraseña'] =  $_POST['contraseña']; // Preparar la contraseña
                }

                $resultado = $obj->procesarDatos($datos);

                if (isset($resultado['accion']) && $resultado['accion'] === 'modificar') {
                    $this->Bitacora('Modifico un usuario de forma exitosa');
                }
                echo json_encode($resultado);
            } else {
                throw new Exception('No tienes permisos para modificar usuarios.');
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
        }
    }

    private function EliminarUsuario($obj): void
    {
        try {
            if ($this->eliminar) {
                $data = [
                    'id' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.']
                ];
                $this->validar_datos($data);

                if ($_POST['id'] == $_SESSION['id']) {
                    throw new Exception('No puedes eliminar tu propio usuario.');
                }

                $datos = [
                    'id' => $_POST['id'],
                    'accion' => 'eliminar'
                ];

                $resultado = $obj->procesarDatos($datos);

                if (isset($resultado['accion']) && $resultado['accion'] === 'eliminar') {
                    $this->Bitacora('Elimino un usuario de forma exitosa');
                }

                echo json_encode($resultado);
            } else {
                throw new Exception('No tiene permisos para eliminar usuarios.');
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
        }
    }

    private function BloquearUsuario($obj): void
    {
        try {
            if ($this->otros) {
                $data = [
                    'id' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.'],
                    'bloqueo' => ['regla' => '/^[1-2]+$/', 'mensaje' => 'Error interno.']
                ];
                $this->validar_datos($data);

                if ($_POST['id'] == $_SESSION['id']) {
                    throw new Exception('No puedes bloquear tu propio usuario.');
                }

                $datos = [
                    'id' => $_POST['id'],
                    'bloqueo' => $_POST['bloqueo'],
                    'accion' => 'bloquear'
                ];

                $resultado = $obj->procesarDatos($datos);

                if (isset($resultado['tipo']) && $resultado['tipo'] === 'bloquear') {
                    $this->Bitacora('Bloqueo un usuario de forma exitosa');
                } else if (isset($resultado['tipo']) && $resultado['tipo'] === 'desbloquear') {
                    $this->Bitacora('Desbloqueo un usuario de forma exitosa');
                }

                echo json_encode($resultado);
            } else {
                throw new Exception('No tiene permisos para bloquear usuarios.');
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
        }
    }

    private function GenerarReporte($obj, $reporte): void
    {
        if($this->reporte){
        $data = [];
        $datos = [];
        if (!empty($_POST['cedula'])) {
            $data['cedula'] = ['regla' => '/^[0-9]{1,8}$/', 'mensaje' => 'Cédula inválida. Solo puede contener numeros.'];
            $datos['cedula'] = $_POST['cedula'];
        }
        if (!empty($_POST['nombre'])) {
            $data['nombre'] = ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{1,30}$/', 'mensaje' => 'Nombre inválido. Solo se permiten letras y espacios.'];
            $datos['nombre'] = $_POST['nombre'];
        }
        if (!empty($_POST['apellido'])) {
            $data['apellido'] = ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{1,30}$/', 'mensaje' => 'Apellido inválido. Solo se permiten letras y espacios.'];
            $datos['apellido'] = $_POST['apellido'];
        }
        if (!empty($_POST['rol'])) {
            $data['rol'] = ['regla' => '/^[1-9]+$/', 'mensaje' => 'Rol inválido.'];
            $datos['roles_id'] = $_POST['rol'];
        }

        $this->validar_datos($data);
        $datos['accion'] = 'reporte';
        $resultado = $obj->procesarDatos($datos);
        if ($resultado['accion'] === 'consultar' && !empty($resultado['datos'])) {
            $respuesta = $reporte->crearPdfUsuarios($resultado['datos']);
            echo json_encode($respuesta);
        } else {
            echo json_encode(['accion' => 'error', 'mensaje' => 'No se encontraron registros.']);
        }
        }else{
            echo json_encode(['accion' => 'error', 'mensaje' => 'No tienes los permisos para generar un reporte.']);
        }
    }

}
