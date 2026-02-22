<?php

namespace App\controlador;

use App\modelo\ModeloUsuarios;
use App\controlador\Base;
use Exception;

class Usuarios extends Base
{

    public function __construct($bitacora)
    {
        parent::__construct($bitacora, _MD_USUARIOS_);
        $this->ProcesarPermisos();
    }

    public function ProcesarSolicitud(string $pagina): void
    {
        $nombreClase = 'App\modelo\ModeloUsuarios';
        if (!class_exists($nombreClase)) {
            require_once(__DIR__ . '/../vista/complementos/404.php');
            exit();
        } else {
            $obj = new ModeloUsuarios();
            if ($this->ComprobarAjax() && !empty($_POST)) {
                $this->ManejarSolicitud($obj);
            } else {
                $this->CargarVista($pagina);
            }
        }
    }

    private function ManejarSolicitud($obj): void
    {
        try {
            $tokenRecibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

            if (!hash_equals($_SESSION['token'], $tokenRecibido)) {
                throw new Exception('Error de seguridad: Token inválido o expirado.');
            }
            $accion = isset($_POST['accion']) ? filter_var($_POST['accion'], FILTER_SANITIZE_SPECIAL_CHARS) : '';
            switch ($accion) {
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
        $filtro['filtro'] = $_POST['filtro'] ?? '';
        $respuesta = $obj->Consultar($filtro);
        echo json_encode($respuesta);
    }

    private function ConsultarRoles($obj): void
    {
        $roles = $obj->consultar();
        $roles['accion'] = 'consultarRoles';
        echo json_encode($roles);
    }
    private function BuscarUsuario($obj): void
    {
        try {
            if ($this->modificar) {
                $data = [
                    'id' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.']
                ];

                $this->validar_datos($data);

                $datos = [
                    'id' => $_POST['id'],
                    'accion' => 'buscar'
                ];

                $resultado = $obj->procesarDatos($datos);
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
                ];

                $this->validar_datos($data);

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
                    $this->Bitacora('Registró un usuario de forma exitosa');
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
                ];

                if (isset($_POST['contraseña']) && !empty($_POST['contraseña'])) {
                    $data['contraseña'] = ['regla' => '/^(?=.*[0-9])(?=.*[A-Z])(?=.*[a-z])(?=.*[!@#\$%\^\&*\)\(+=._-])[0-9A-Za-z!@#\$%\^\&*\)\(+=._-]{8,20}$/', 'mensaje' => 'Contraseña inválida. Debe tener entre 8 y 20 caracteres y contener al menos una mayúscula, una minúscula, un número y un símbolo especial.'];
                }

                $this->validar_datos($data);

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

                if (isset($_POST['contraseña']) && !empty($_POST['contraseña'])) {
                    $datos['contraseña'] =  $_POST['contraseña'];
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
    }

    private function validar_datos(array $data): void
    {
        foreach ($data as $campo => $valor) {

            if (!isset($_POST[$campo]) || empty(trim($_POST[$campo]))) {
                throw new Exception("El campo $campo es obligatorio.");
            }
        }
        foreach ($data as $campo => $valor) {
            if (isset($valor['regla'])) {
                if (!preg_match($valor['regla'], $_POST[$campo])) {
                    throw new Exception($valor['mensaje']);
                }
            }
        }
    }
}
