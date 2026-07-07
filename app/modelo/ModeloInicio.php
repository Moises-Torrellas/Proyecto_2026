<?php

namespace App\modelo;

use Exception;

class ModeloInicio extends Conexion
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
            $sql = 'SELECT usuarios.idUsuario, usuarios.nombreUsuario, usuarios.apellidoUsuario, usuarios.foto, usuarios.pass_hash, usuarios.bloqueo, usuarios.intentos_fallidos,
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
            if (!password_verify($this->clave, $resultado['pass_hash'])) {
                $intentos = (int)$resultado['intentos_fallidos'] + 1;
                $updateIntentos = $conex->prepare("UPDATE usuarios SET intentos_fallidos = :intentos WHERE idUsuario = :id");
                $updateIntentos->execute([':intentos' => $intentos, ':id' => $resultado['idUsuario']]);
                
                // Si llega a 3 intentos y no es Super Usuario (id_rol = 1)
                if ($intentos >= 3 && (int)$resultado['id_rol'] !== 1) {
                    $bloquear = $conex->prepare("UPDATE usuarios SET bloqueo = 2 WHERE idUsuario = :id");
                    $bloquear->execute([':id' => $resultado['idUsuario']]);
                    
                    return ['accion' => 'bloqueado', 'resultado' => 0, 'mensaje' => 'Has superado el límite de intentos (3). Su usuario ha sido bloqueado por seguridad. Comuníquese con un administrador para desbloquear su usuario.', 'idUsuario' => $resultado['idUsuario']];
                }
                
                return ['accion' => 'inicio', 'resultado' => 0, 'mensaje' => 'La contraseña es incorrecta. Intento ' . $intentos . ' de 3.'];
            }
            
            if ((int)$resultado['intentos_fallidos'] > 0) {
                 $resetIntentos = $conex->prepare("UPDATE usuarios SET intentos_fallidos = 0 WHERE idUsuario = :id");
                 $resetIntentos->execute([':id' => $resultado['idUsuario']]);
            }

            $permisos = [];
            
            if ((int)$resultado['nivel_rol'] === 1) {
                $sqlPermisos = "SELECT m.id_modulo, p.clave, p.nombre as nombre_permiso, 1 AS asignado
                                FROM modulos m
                                INNER JOIN permisos p ON p.id_modulo = m.id_modulo
                                WHERE m.id_modulo NOT IN (4, 5, 8, 1, 2, 3, 99)";
                $stmtPermisos = $conex->prepare($sqlPermisos);
                $stmtPermisos->execute();
                $permisos_db = $stmtPermisos->fetchAll();
            } else {
                $sqlPermisos = "SELECT m.id_modulo, p.clave, p.nombre as nombre_permiso,
                                CASE 
                                    WHEN e.id_permiso IS NOT NULL THEN e.tipo
                                    WHEN pr.id_permiso_rol IS NOT NULL THEN 1 
                                    ELSE 0 
                                END AS asignado
                            FROM modulos m
                            INNER JOIN permisos p ON p.id_modulo = m.id_modulo
                            LEFT JOIN permisos_rol pr ON pr.id_permiso = p.id_permiso AND pr.id_rol = :id_rol
                            LEFT JOIN excepciones e ON e.id_permiso = p.id_permiso AND e.id_usuario = :id
                            WHERE m.id_modulo NOT IN (4, 5, 8, 1, 2, 3, 99) 
                            AND p.estatus != 2 AND m.estatus != 2";

                $stmtPermisos = $conex->prepare($sqlPermisos);
                $stmtPermisos->bindParam(':id_rol', $resultado['id_rol'], \PDO::PARAM_INT);
                $stmtPermisos->bindParam(':id', $resultado['idUsuario'], \PDO::PARAM_INT);
                $stmtPermisos->execute();
                $permisos_db = $stmtPermisos->fetchAll();
            }

            foreach ($permisos_db as $p) {
                if ($p['asignado'] == 1) {
                    $permisos[] = $p;
                }
            }

            $modeloUsuarios = new ModeloUsuarios();
            $modeloUsuarios->registrarUltimoIngreso((int)$resultado['idUsuario']);

            // Ejecutar la validación de notificaciones diarias
            if (class_exists('App\servicios\verificarEvento')) {
                $verificador = new \App\servicios\verificarEvento();
                $verificador->procesar();
            } else {
                // Instanciarlo requiriendo el archivo si no lo cargó el autoload
                require_once __DIR__ . '/../servicios/verificarEvento.php';
                $verificador = new \App\servicios\verificarEvento();
                $verificador->procesar();
            }

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
