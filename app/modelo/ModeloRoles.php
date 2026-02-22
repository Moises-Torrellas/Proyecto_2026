<?php

namespace App\modelo;

use App\modelo\Conexion;
use Exception;

class ModeloRoles extends Conexion
{
    private $conexion = null;

    public function __construct() {}

    public function consultar()
    {
        try {
            $this->conexion = parent::conexSG();
            $sentencia = 'SELECT * FROM roles';
            $str = $this->conexion->prepare($sentencia);
            $str->execute();
            $datos = $str->fetchAll();
            return ['accion' => 'consultar', 'datos' => $datos];
        } catch (Exception $e) {
            error_log($e->getMessage());
            return['accion' => 'error', 'mensaje' => $e->getMessage()];
        }
    }
}
