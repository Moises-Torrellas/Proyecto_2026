<?php

namespace App\controlador;

use App\modelo\ModeloRoles;
use App\controlador\Base;
use Exception;

class Roles extends Base
{
    public function __construct($bitacora)
    {
        parent::__construct($bitacora, _MD_ROLES_);
        $this->ProcesarPermisos();
    }

    public function ProcesarSolicitud(string $pagina)
    {
        $nombreClase = 'App\modelo\ModeloRoles';
        if (!class_exists($nombreClase)) {
            require_once(__DIR__ . '/../vista/complementos/404.php');
            exit();
        } else {
            $obj = new ModeloRoles();
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
                case 'consultarModulo':
                    $this->ConsultarModulo($obj);
                    break;
                case 'buscar':
                    $this->BuscarRoles($obj);
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
        $roles = $obj->consultar();
        echo json_encode($roles);
    }
    private function ConsultarModulo($obj): void
    {
        $modulos = $obj->consultarModulo();
        echo json_encode($modulos);
    }

    private function BuscarRoles($obj): void
    {
        try {
            if ($this->modificar) {
                $data['id'] = ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.'];

                $this->validar_datos($data);

                $datos = ['id' => $_POST['id'], 'accion' => 'buscar']; // Preparar los datos

                $resultado = $obj->procesarDatos($datos); // Procesar los datos
                echo json_encode($resultado);
            } else {
                throw new Exception('No tiene permisos para modificar roles.');
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
        }
    }
}
