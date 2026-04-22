<?php
namespace App\modelo;

use App\modelo\Conexion;
use Exception;

class ModeloRepresentantes extends Conexion
{
    public function __construct()
    {
        
    }

    public function Consultar(array $filtro = []): array
    {
        try {
            $conex = $this->conex();
            $sentencia = "SELECT * FROM representantes";
            $stmt = $conex->prepare($sentencia);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            return array('accion' => 'buscar', 'datos' => $datos);
        } catch (Exception $e) {
            error_log("Error en consultar Usuarios: " . $e->getMessage());
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            
        }
    }
}
