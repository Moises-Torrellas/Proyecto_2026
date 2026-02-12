<?php

namespace App\modelo;

use App\modelo\Conexion;
use Exception;

class ModeloInicio extends Conexion
{

    private $conexion = null;
    private string $cedula = '';
    private string $clave = '';

    public function __construct() {}

    public function ProcesarDatos(array $datos): array
    {
        $this->cedula = $datos['cedula'] ?? '';
        $this->clave = $datos['clave'] ?? '';

        if(empty($this->cedula) || empty($this->clave)) {
            return ['accion' => 'inicio', 'resultado' => 0, 'mensaje' => 'La cédula y la contraseña son obligatorias'];
        }

        return $this->IniciarSesion();
    }

    private function IniciarSesion(): array
    {
        try {
            $this->conexion = self::getConexSG();
            $sql = 'SELECT usuarios.idUsuario,usuarios.nombreUsuario,usuarios.apellidoUsuario,roles.nombre_rol,roles.id_rol,usuarios.contraseña 
                            FROM `usuarios` 
                            INNER JOIN roles ON roles.id_rol=usuarios.id_rol WHERE cedulaUsuario = :cedula;';
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindParam(':cedula', $this->cedula, \PDO::PARAM_STR);
            $stmt->execute();
            $resultado = $stmt->fetch();

            $sql ='SELECT permiso.id_modulo,permiso.eliminar,permiso.modificar,permiso.incluir 
                            FROM `usuarios` 
                            INNER JOIN permiso ON permiso.id_rol=usuarios.id_rol WHERE usuarios.idUsuario=:id;';
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindParam(':id', $resultado['idUsuario'], \PDO::PARAM_INT);
            $stmt->execute();
            $permisos = $stmt->fetch();
            
            if($resultado && $permisos){
                if (password_verify($this->clave, $resultado['contraseña'])) {
                    return ['accion' => 'inicio', 'resultado' => 1, 'datos' => $resultado, 'permisos' => $permisos, 'mensaje' => 'BIENVENIDO', 'url' => _URL_.'Principal'];
                } else {
                    return ['accion' => 'inicio', 'resultado' => 0, 'mensaje' => 'La contraseña es incorrecta'];
                }
            } else {
                return ['accion' => 'inicio', 'resultado' => 2, 'mensaje' => 'La cédula no existe'];
            }
        } catch (Exception $e) {
            error_log("Error en incluir: " . $e->getMessage());
            return ['accion' => 'error', 'mensaje' => 'Error interno: ' . $e->getMessage()];
        }finally {
            $this->conexion = null;
        }
    }


}
