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
                case 'incluir':
                    $this->IncluirRoles($obj);
                    break;
                case 'modificar':
                    $this->ModificarRoles($obj);
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

    private function IncluirRoles($obj)
    {
        try {
            if ($this->incluir) {
                $data = ['nombre' => ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{1,30}$/', 'mensaje' => 'Nombre inválido. Solo se permiten letras y espacios.'],];

                $this->validar_datos($data);

                $array = [
                    'id_modulo' => [
                        'regla' => '/^[1-9]+$/',
                        'mensaje' => 'ID de módulo debe ser numérico.'
                    ],
                    'check_incluir' => [
                        'regla' => '/^[0-1]$/',
                        'mensaje' => 'Valor de permiso inválido.'
                    ],
                    'check_modificar' => [
                        'regla' => '/^[0-1]$/',
                        'mensaje' => 'Valor de permiso inválido.'
                    ],
                    'check_eliminar' => [
                        'regla' => '/^[0-1]$/',
                        'mensaje' => 'Valor de permiso inválido.'
                    ],
                    'check_reporte' => [
                        'regla' => '/^[0-1]$/',
                        'mensaje' => 'Valor de permiso inválido.'
                    ],
                    'check_otros' => [
                        'regla' => '/^[0-1]$/',
                        'mensaje' => 'Valor de permiso inválido.'
                    ]
                ];
                $arrayValidar = array_intersect_key($array, $_POST);
                if (!empty($arrayValidar)) {
                    $this->validarArrays($arrayValidar);
                }

                $datos = [
                    'accion' => 'incluir',
                    'nombre' => $_POST['nombre'],
                ];
                $camposPermisos = [
                    'id_modulo'       => 'id_modulo',
                    'check_incluir'   => 'c_incluir',
                    'check_modificar' => 'c_modificar',
                    'check_eliminar'  => 'c_eliminar',
                    'check_reporte'   => 'c_reporte',
                    'check_otros'     => 'c_otros'
                ];

                foreach ($camposPermisos as $postKey => $dataKey) {
                    if (isset($_POST[$postKey])) {
                        $datos[$dataKey] = $_POST[$postKey];
                    }
                }

                $respuesta = $obj->procesarDatos($datos);
                if ($respuesta['accion'] == 'incluir') {
                    $this->Bitacora('Registro el rol: ' . $_POST['nombre']);
                }
                echo json_encode($respuesta);
            } else {
                throw new Exception('No tiene permisos para incluir roles.');
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
        }
    }

    private function ModificarRoles($obj): void
    {
        try {
            if ($this->modificar) {
                $data = ['nombre' => ['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{1,30}$/', 'mensaje' => 'Nombre inválido. Solo se permiten letras y espacios.'],];

                $this->validar_datos($data);

                $array = [
                    'id_modulo' => [
                        'regla' => '/^[1-9]+$/',
                        'mensaje' => 'ID de módulo debe ser numérico.'
                    ],
                    'check_incluir' => [
                        'regla' => '/^[0-1]$/',
                        'mensaje' => 'Valor de permiso inválido.'
                    ],
                    'check_modificar' => [
                        'regla' => '/^[0-1]$/',
                        'mensaje' => 'Valor de permiso inválido.'
                    ],
                    'check_eliminar' => [
                        'regla' => '/^[0-1]$/',
                        'mensaje' => 'Valor de permiso inválido.'
                    ],
                    'check_reporte' => [
                        'regla' => '/^[0-1]$/',
                        'mensaje' => 'Valor de permiso inválido.'
                    ],
                    'check_otros' => [
                        'regla' => '/^[0-1]$/',
                        'mensaje' => 'Valor de permiso inválido.'
                    ]
                ];
                $arrayValidar = array_intersect_key($array, $_POST);
                if (!empty($arrayValidar)) {
                    $this->validarArrays($arrayValidar);
                }

                $datos = [
                    'accion' => 'modificar',
                    'nombre' => $_POST['nombre'],
                ];
                $camposPermisos = [
                    'id_modulo'       => 'id_modulo',
                    'check_incluir'   => 'c_incluir',
                    'check_modificar' => 'c_modificar',
                    'check_eliminar'  => 'c_eliminar',
                    'check_reporte'   => 'c_reporte',
                    'check_otros'     => 'c_otros'
                ];

                foreach ($camposPermisos as $postKey => $dataKey) {
                    if (isset($_POST[$postKey])) {
                        $datos[$dataKey] = $_POST[$postKey];
                    }
                }

                $respuesta = $obj->procesarDatos($datos);
                if ($respuesta['accion'] == 'modificar') {
                    $this->Bitacora('Modifico el rol: ' . $_POST['nombre']);
                }
                echo json_encode($respuesta);
            } else {
                throw new Exception('No tiene permisos para modificar roles.');
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
        }
    }
}
