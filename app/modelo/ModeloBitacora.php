<?php

namespace App\modelo;

use App\modelo\Conexion;
use App\interface\InterBitacora;
use PDOException;

class ModeloBitacora extends Conexion implements InterBitacora
{

    private string $accion = '';
    private int $id_modulo;
    private int $id_usuario;

    private $conexion = null;

    public function __construct() {}

    public function RegistrarAccion($id_modulo, $accion, $id_usuario)
    {
        try{
            $this->conexion = self::getConexSG();
            $sql = 'INSERT INTO `bitacora`(`id_modulo`, `acciones`, `fecha`, `hora`, `idUsuario`) 
                            VALUES (:modulo,:accion,:fecha,:hora,:usuario)';
            $stmt = $this->conexion->prepare($sql);
            $parametros = [
                ':modulo' => $id_modulo,
                ':accion' => $accion,
                ':fecha' => date('Y-m-d'),
                ':hora' => date('H:i:s'),
                ':usuario' => $id_usuario
            ];
            $respuesta = $stmt->execute($parametros);

            if($respuesta){
                return ['accion' => 'incluir', 'resultado' => 1, 'mensaje' => 'Acción registrada en la bitácora'];
            } else {
                return ['accion' => 'incluir', 'resultado' => 0, 'mensaje' => 'No se pudo registrar la acción en la bitácora'];
            }

        }catch(PDOException $e){
            echo "Error al registrar la acción en la bitácora: " . $e->getMessage();
        }finally{
            $this->conexion = null;
        }
    }
}
