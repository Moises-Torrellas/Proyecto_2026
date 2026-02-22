<?php

namespace App\modelo;

use App\modelo\Conexion;
use Exception;

class ModeloRoles extends Conexion
{
    private $conexion = null;

    private int $id;
    private string $nombre;
    private array $id_modulo;
    private array $c_incluir;
    private array $c_modificar;
    private array $c_eliminar;
    private array $c_reporte;
    private array $c_otros;

    public function __construct() {}

    public function procesarDatos(array $datos)
    {

        if (empty($datos)) {
            throw new Exception('No se proporcionaron datos para procesar.');
        }

        $this->id = $datos['id'] ?? null;
        $accion = $datos['accion'] ?? null;
        return match ($accion) {
            'buscar' => $this->Buscar(),

            'reporte' => $this->Consultar(),
            default     => throw new Exception("Acción no válida."),
        };
    }
    public function consultar()
    {
        try {
            $this->conexion = parent::conexSG();
            $sentencia = 'SELECT * FROM roles';
            $stmt = $this->conexion->prepare($sentencia);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            return ['accion' => 'consultar', 'datos' => $datos];
        } catch (Exception $e) {
            error_log($e->getMessage());
            return ['accion' => 'error', 'mensaje' => $e->getMessage()];
        }
    }

    public function consultarModulo()
    {
        try {
            $this->conexion = parent::conexSG();
            $sentencia = 'SELECT modulo.id_modulo, modulo.nombre_modulo FROM `modulo` WHERE modulo.id_modulo!=4 AND modulo.id_modulo!=5;';
            $stmt = $this->conexion->prepare($sentencia);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            $resultado = array('accion' => 'consultarModulo', 'datos' => $datos);
        } catch (Exception $e) {
            error_log($e->getMessage());
            $resultado = array('accion' => 'error', 'mensaje' => $e->getMessage());
        }
        return $resultado;
    }

    public function Buscar()
    {
        try {
            $this->conexion = self::conexSG();
            $sentencia = 'SELECT roles.id_rol,roles.nombre_rol,permiso.eliminar,permiso.modificar,permiso.incluir,permiso.reporte,permiso.otros,modulo.nombre_modulo,modulo.id_modulo FROM `roles` 
            INNER JOIN permiso ON roles.id_rol=permiso.id_rol 
            INNER JOIN modulo ON permiso.id_modulo=modulo.id_modulo 
            WHERE roles.id_rol=:id';
            $stmt = $this->conexion->prepare($sentencia);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            $resultado = array('accion' => 'buscar', 'datos' => $datos);
        } catch (Exception $e) {
            error_log($e->getMessage());
            $resultado = array('accion' => 'error', 'mensaje' => $e->getMessage());
        }
        return $resultado;
    }
}
