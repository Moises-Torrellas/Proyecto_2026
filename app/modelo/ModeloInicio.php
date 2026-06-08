<?php

namespace App\modelo;

use Exception;

class ModeloInicio extends ModeloBase
{
    private string $cedula = '';
    private string $clave = '';

    public function __construct() {}

    public function ProcesarDatos(array $datos): array
    {
        $this->cedula = $datos['cedula'] ?? '';
        $this->clave = $datos['clave'] ?? '';

        if (empty($this->cedula) || empty($this->clave)) {
            return ['accion' => 'inicio', 'resultado' => 0, 'mensaje' => 'La cédula y la contraseña son obligatorias'];
        }

        return $this->IniciarSesion();
    }

    private function IniciarSesion(): array
    {
        try {
            $conex = $this->conexSG();

            // 1. Consultar los datos básicos del usuario y su rol
            $sql = 'SELECT usuarios.idUsuario, usuarios.nombreUsuario, usuarios.apellidoUsuario, usuarios.foto, usuarios.contraseña, usuarios.bloqueo,
                        roles.nombre_rol, roles.id_rol, roles.nivel_rol  
                    FROM `usuarios` 
                    INNER JOIN roles ON roles.id_rol = usuarios.id_rol 
                    WHERE cedulaUsuario = :cedula AND usuarios.estatus != 0;';
                    
            $stmt = $conex->prepare($sql);
            $stmt->bindParam(':cedula', $this->cedula, \PDO::PARAM_STR);
            $stmt->execute();
            $resultado = $stmt->fetch();

            // Validar si el usuario existe
            if (!$resultado) {
                return ['accion' => 'inicio', 'resultado' => 2, 'mensaje' => 'La cédula no existe'];
            }

            // Validar si el usuario está bloqueado (bloqueo == 1 corta el acceso)
            if ((int)$resultado['bloqueo'] !== 1) {
                return ['accion' => 'denegado', 'resultado' => 0, 'mensaje' => 'Usted tiene bloqueado el acceso.'];
            }

            // 2. Validar la contraseña primero antes de buscar permisos
            if (!password_verify($this->clave, $resultado['contraseña'])) {
                return ['accion' => 'inicio', 'resultado' => 0, 'mensaje' => 'La contraseña es incorrecta'];
            }

            // 3. Buscar permisos usando los campos puros de la tabla permisos_usuarios
            $sqlPermisos = 'SELECT permisos_usuarios.id_modulo, permisos_usuarios.ingresar, permisos_usuarios.registrar, permisos_usuarios.eliminar, permisos_usuarios.modificar, permisos_usuarios.reporte, permisos_usuarios.otros 
                            FROM `usuarios` 
                            INNER JOIN permisos_usuarios ON permisos_usuarios.idUsuario = usuarios.idUsuario 
                            WHERE usuarios.idUsuario = :id;';
                            
            $stmtPermisos = $conex->prepare($sqlPermisos);
            $stmtPermisos->bindParam(':id', $resultado['idUsuario'], \PDO::PARAM_INT);
            $stmtPermisos->execute();
            $permisos = $stmtPermisos->fetchAll();
            
            // 4. Retornar login exitoso con la data estructurada
            return [
                'accion' => 'inicio', 
                'resultado' => 1, 
                'datos' => $resultado, 
                'permisos' => $permisos, 
                'mensaje' => 'BIENVENIDO', 
                'url' => 'Principal'
            ];

        } catch (Exception $e) {
            logs('Inicio', $e->getMessage(), 'Modelo_Inicio');
            return ['accion' => 'error', 'mensaje' =>  $e->getMessage()];
        } finally {
            $conex = null;
        }
    }
}