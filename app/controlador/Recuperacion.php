<?php

namespace App\controlador;

use App\modelo\ModeloRecuperacion;
use App\controlador\Base;
use Exception;

class Recuperacion extends Base
{
    public function __construct($bitacora) // inyeccion de la bitacora
    {
        parent::__construct($bitacora, _MD_RECUPERACION_); // llamar al constructor de la clase base para inicializar la bitacora
    }

    public function ProcesarSolicitud(string $pagina): void
    {
        $nombreClase = 'App\modelo\ModeloRecuperacion';
        if (!class_exists($nombreClase)) {
            require_once(__DIR__ . '/../vista/complementos/404.php');
            exit();
        } else {
            $obj = new ModeloRecuperacion();
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
                case 'comprobar':
                    $this->Comprobar($obj);
                    break;
                case 'comprobarCodigo':
                    $this->ComprobarCodigo($obj);
                    break;
                case 'reenviar':
                    $this->ReenviarCodigo($obj);
                    break;
                case 'cambiar':
                    $this->Cambiar($obj);
                    break;
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
            return;
        }
    }

    private function Comprobar($obj)
    {
        try {
            $data = [
                'cedula' => ['regla' => '/^[0-9]{7,8}$/', 'mensaje' => 'Cédula inválida. Debe contener de 7 a 8 dígitos.'],
            ];
            $this->validar_datos($data);
            $datos =['cedula' => $_POST['cedula'], 'accion' => 'comprobar'];
            $resultado = $obj->procesarDatos($datos);
            echo json_encode($resultado);
        } catch (Exception $e) {
            echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
        }
    }
    private function ComprobarCodigo($obj)
    {
        try {
            $data = [
                'codigo' => ['regla' => '/^[0-9]{6}$/', 'mensaje' => 'El codigo solo contiene 6 números.'],
            ];
            $this->validar_datos($data);
            $datos =['codigo' => $_POST['codigo'], 'accion' => 'comprobarCodigo'];
            $resultado = $obj->procesarDatos($datos);
            echo json_encode($resultado);
        } catch (Exception $e) {
            echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
        }
    }
    private function ReenviarCodigo($obj)
    {
        try {
            $resultado = $obj->reenviar();
            echo json_encode($resultado);
        } catch (Exception $e) {
            echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
        }
    }

    private function Cambiar ($obj) : void
    {
        $data['contraseña'] = ['regla' => '/^(?=.*[0-9])(?=.*[A-Z])(?=.*[a-z])(?=.*[!@#\$%\^\&*\)\(+=._-])[0-9A-Za-z!@#\$%\^\&*\)\(+=._-]{8,20}$/', 'mensaje' => 'Contraseña inválida. Debe tener entre 8 y 20 caracteres y contener al menos una mayúscula, una minúscula, un número y un símbolo especial.'];
        $this->validar_datos($data);
        $datos['contraseña'] = $_POST['contraseña'];
        $datos['accion'] = 'cambiar';
        $resultado = $obj->procesarDatos($datos);
        echo json_encode($resultado);
    }

}
