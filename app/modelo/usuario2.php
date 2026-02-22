<?php

namespace App\modelo;

use App\modelo\Conexion;
use Exception;

class ModeloUsuarios2 extends Conexion
{
    private $conexion = null;
    private $id;
    private $cedula;
    private $nombre;
    private $apellido;
    private $telefono;
    private $contrasena;
    private $correo;
    private $rol;

    private $actualizar_contrasena = false;

    // Lista blanca de campos permitidos para consultas dinámicas
    private array $campoWhitelist = [
        'idUsuario' => 'idUsuario',
        'cedulaUsuario' => 'cedulaUsuario',
        'correo' => 'correo',
        'id_rol' => 'id_rol'
    ];

    public function __construct() {}

    public function procesarDatos(array $datos): array
    {
        if (empty($datos)) {
            throw new Exception('No se proporcionaron datos para procesar.');
        }

        $this->id = $datos['id'] ?? null;

        $this->nombre = mb_convert_case(trim($datos['nombre'] ?? ''), MB_CASE_TITLE, "UTF-8");
        $this->apellido = mb_convert_case(trim($datos['apellido'] ?? ''), MB_CASE_TITLE, "UTF-8");

        $this->cedula = trim($datos['cedula'] ?? '');
        $this->telefono = trim($datos['telefono'] ?? '');
        $this->correo = mb_strtolower(trim($datos['correo'] ?? ''), "UTF-8");
        $this->rol = $datos['roles_id'] ?? null;

        // Validar correo
        if (!empty($this->correo) && !filter_var($this->correo, FILTER_VALIDATE_EMAIL)) {
            return ['accion' => 'error', 'mensaje' => 'Correo inválido.'];
        }

        // Manejo de contraseña opcional/obligatoria según acción
        if (isset($datos['contraseña']) && $datos['contraseña'] !== '') {
            $pass = $datos['contraseña'];
            if (strlen($pass) < 8) {
                return ['accion' => 'error', 'mensaje' => 'La contraseña debe tener al menos 8 caracteres.'];
            }
            $this->contrasena = password_hash($pass, PASSWORD_DEFAULT);
            $this->actualizar_contrasena = true;
        }

        $accion = $datos['accion'] ?? null;
        return match ($accion) {
            'incluir'   => $this->Incluir(),
            'eliminar'  => $this->Eliminar(),
            'modificar' => $this->Modificar(),
            'buscar'    => $this->buscar(),
            default     => throw new Exception("Acción no válida."),
        };
    }

    public function Consultar(): array
    {
        try {
            $this->conexion = self::conexSG();
            $this->conexion->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $sentencia = 'SELECT usuarios.idUsuario,usuarios.cedulaUsuario,usuarios.nombreUsuario,usuarios.apellidoUsuario,usuarios.telefonoUsuario,usuarios.correo,roles.nombre_rol 
                            FROM `usuarios` INNER JOIN roles ON roles.id_rol=usuarios.id_rol WHERE usuarios.estatus=1 ORDER BY usuarios.idUsuario ASC';
            $str = $this->conexion->prepare($sentencia);
            $str->execute();
            $datos = $str->fetchAll(\PDO::FETCH_ASSOC);
            $resultado = array('accion' => 'consultar', 'datos' => $datos);
        } catch (Exception $e) {
            error_log("[ModeloUsuarios2][Consultar] " . $e->getMessage());
            $resultado = array('accion' => 'error', 'mensaje' => 'Error al consultar usuarios.');
        } finally {
            $this->conexion = null;
        }
        return $resultado;
    }

    private function Incluir(): array
    {
        try {
            // Requerir contraseña para creación
            if (!$this->actualizar_contrasena) {
                return ['accion' => 'error', 'mensaje' => 'Debe proporcionar una contraseña al crear el usuario.'];
            }

            // Validaciones básicas
            if (empty($this->cedula) || empty($this->nombre) || empty($this->apellido) || empty($this->correo)) {
                return ['accion' => 'error', 'mensaje' => 'Faltan datos obligatorios.'];
            }

            $this->conexion = self::conexSG();
            $this->conexion->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // Usar transacción para evitar race conditions
            $this->conexion->beginTransaction();

            // 1. Verificaciones previas (solo campos permitidos)
            if ($this->verificarExistencia('cedulaUsuario', $this->cedula, 1)) {
                $this->conexion->rollBack();
                return ['accion' => 'error', 'mensaje' => 'La cédula ya está registrada.'];
            }
            if ($this->verificarExistencia('correo', $this->correo, 1)) {
                $this->conexion->rollBack();
                return ['accion' => 'error', 'mensaje' => 'El correo ya está registrado.'];
            }
            if (!$this->verificarRol($this->rol)) {
                $this->conexion->rollBack();
                return ['accion' => 'error', 'mensaje' => 'El rol no existe.'];
            }

            // 2. Determinar si es REACTIVACIÓN (usuario eliminado previamente)
            $Reactivacion = $this->verificarExistencia('cedulaUsuario', $this->cedula, 0);

            if ($Reactivacion) {
                $sql = "UPDATE `usuarios` SET 
                            `nombreUsuario` = :nombre,
                            `apellidoUsuario` = :apellido,
                            `telefonoUsuario` = :telefono,
                            `contraseña` = :contra,
                            `correo` = :correo,
                            `id_rol` = :rol,
                            `estatus` = 1 
                            WHERE cedulaUsuario = :cedula";
            } else {
                $sql = "INSERT INTO `usuarios`
                            (`cedulaUsuario`, `nombreUsuario`, `apellidoUsuario`, `telefonoUsuario`, `contraseña`,`correo`, `id_rol`, `estatus`) 
                            VALUES 
                            (:cedula, :nombre, :apellido, :telefono, :contra, :correo, :rol, 1)";
            }

            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(':cedula', $this->cedula, \PDO::PARAM_STR);
            $stmt->bindValue(':nombre', $this->nombre, \PDO::PARAM_STR);
            $stmt->bindValue(':apellido', $this->apellido, \PDO::PARAM_STR);
            $stmt->bindValue(':telefono', $this->telefono, \PDO::PARAM_STR);
            $stmt->bindValue(':contra', $this->contrasena, \PDO::PARAM_STR);
            $stmt->bindValue(':correo', $this->correo, \PDO::PARAM_STR);
            $stmt->bindValue(':rol', $this->rol, \PDO::PARAM_INT);

            $respuesta = $stmt->execute();

            if ($respuesta) {
                $this->conexion->commit();
                return ['accion' => 'incluir', 'mensaje' => "Usuario registrado exitosamente."];
            } else {
                $this->conexion->rollBack();
                throw new Exception('No se pudo registrar el usuario.');
            }
        } catch (Exception $e) {
            if ($this->conexion && $this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            error_log("[ModeloUsuarios2][Incluir] " . $e->getMessage());
            return ['accion' => 'error', 'mensaje' => 'Error al registrar usuario.'];
        } finally {
            $this->conexion = null;
        }
    }

    private function Modificar(): array
    {
        try {
            if (empty($this->id)) {
                return ['accion' => 'error', 'mensaje' => 'ID inválido.'];
            }

            $this->conexion = self::conexSG();
            $this->conexion->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // Verificar rol objetivo
            if (!$this->verificarRol($this->rol)) {
                return ['accion' => 'error', 'mensaje' => 'El rol no existe.'];
            }

            // Evitar cambiar rol de un administrador real (comprobar rol actual en BD)
            $currentRole = $this->getUserRoleById($this->id);
            if ($currentRole === 1 && $this->rol != 1) {
                return ['accion' => 'error', 'mensaje' => 'No puedes cambiar el rol del administrador.'];
            }

            // Si los datos de cedula/correo no coinciden con el propio registro, comprobar duplicados
            if (!$this->coincide($this->cedula, $this->id, $this->correo)) {
                if ($this->verificarExistencia('correo', $this->correo, 1)) {
                    return ['accion' => 'error', 'mensaje' => 'El correo ya pertenece a otro usuario activo.'];
                }
                if ($this->verificarExistencia('cedulaUsuario', $this->cedula, 1)) {
                    return ['accion' => 'error', 'mensaje' => 'La cédula ya pertenece a otro usuario activo.'];
                }
            }

            $parametros = [
                ':id' => $this->id,
                ':cedula' => $this->cedula,
                ':nombre' => $this->nombre,
                ':apellido' => $this->apellido,
                ':telefono' => $this->telefono,
                ':correo' => $this->correo,
                ':rol' => $this->rol,
            ];

            $sql = "UPDATE `usuarios` SET 
                        `cedulaUsuario` = :cedula,
                        `nombreUsuario` = :nombre,
                        `apellidoUsuario` = :apellido,
                        `correo` = :correo,
                        `telefonoUsuario` = :telefono,
                        `id_rol` = :rol";

            if ($this->actualizar_contrasena) {
                $sql .= ", `contraseña` = :contra";
                $parametros[':contra'] = $this->contrasena;
            }

            $sql .= " WHERE idUsuario = :id";

            $stmt = $this->conexion->prepare($sql);
            $respuesta = $stmt->execute($parametros);

            if ($respuesta) {
                return ['accion' => 'modificar', 'resultado' => 1, 'mensaje' => "Usuario modificado exitosamente."];
            } else {
                throw new Exception('No se pudo modificar el usuario.');
            }
        } catch (Exception $e) {
            error_log("[ModeloUsuarios2][Modificar] " . $e->getMessage());
            return ['accion' => 'error', 'mensaje' => 'Error al modificar usuario.'];
        } finally {
            $this->conexion = null;
        }
    }

    private function Eliminar(): array
    {
        try {
            if (empty($this->id)) {
                return ['accion' => 'error', 'mensaje' => 'ID inválido.'];
            }

            $this->conexion = self::conexSG();
            $this->conexion->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // Comprobar existencia
            if (!$this->verificarExistencia('idUsuario', $this->id, 1)) {
                return ['accion' => 'error', 'mensaje' => 'El usuario que intenta eliminar no existe.'];
            }

            // Evitar eliminar administradores (comprobar rol en BD)
            $currentRole = $this->getUserRoleById($this->id);
            if ($currentRole === 1) {
                return ['accion' => 'error', 'mensaje' => 'No puedes eliminar un administrador.'];
            }

            $sql = "UPDATE `usuarios` SET `estatus` = 0 WHERE idUsuario = :id";
            $stmt = $this->conexion->prepare($sql);
            $parametros = [':id' => $this->id];
            $respuesta = $stmt->execute($parametros);

            if ($respuesta) {
                return ['accion' => 'eliminar', 'mensaje' => "Usuario eliminado exitosamente."];
            } else {
                throw new Exception('No se pudo eliminar el usuario.');
            }
        } catch (Exception $e) {
            error_log("[ModeloUsuarios2][Eliminar] " . $e->getMessage());
            return ['accion' => 'error', 'mensaje' => 'Error al eliminar usuario.'];
        } finally {
            $this->conexion = null;
        }
    }

    private function buscar(): array
    {
        try {
            $this->conexion = self::conexSG();
            $this->conexion->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $sentencia = 'SELECT usuarios.idUsuario,usuarios.cedulaUsuario,usuarios.nombreUsuario,usuarios.apellidoUsuario,usuarios.telefonoUsuario,usuarios.correo,usuarios.id_rol FROM `usuarios` 
            WHERE usuarios.idUsuario=:id AND usuarios.estatus=1';
            $str = $this->conexion->prepare($sentencia);
            $str->bindValue(':id', $this->id, \PDO::PARAM_INT);
            $str->execute();
            $datos = $str->fetchAll(\PDO::FETCH_ASSOC);
            $resultado = array('accion' => 'buscar', 'datos' => $datos);
        } catch (Exception $e) {
            error_log("[ModeloUsuarios2][buscar] " . $e->getMessage());
            $resultado = array('accion' => 'error', 'mensaje' => 'Error al buscar usuario.');
        } finally {
            $this->conexion = null;
        }
        return $resultado;
    }

    private function verificarExistencia($campo, $valor, $estatus): bool
    {
        try {
            // Validar campo contra whitelist
            if (!array_key_exists($campo, $this->campoWhitelist)) {
                throw new Exception('Campo no permitido.');
            }

            $columna = $this->campoWhitelist[$campo];

            $this->conexion = self::conexSG();
            $this->conexion->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $sentencia = "SELECT COUNT(*) FROM `usuarios` WHERE $columna = :valor AND estatus = :estatus";
            $str = $this->conexion->prepare($sentencia);
            $str->bindValue(':valor', $valor);
            $str->bindValue(':estatus', $estatus, \PDO::PARAM_INT);
            $str->execute();

            $count = (int)$str->fetchColumn();
            return $count > 0;
        } catch (Exception $e) {
            error_log("[ModeloUsuarios2][verificarExistencia] " . $e->getMessage());
            return false;
        }
    }

    private function coincide($cedula, $id, $correo): bool
    {
        try {
            $this->conexion = self::conexSG();
            $this->conexion->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $sentencia = "SELECT idUsuario FROM `usuarios` WHERE idUsuario=:id AND cedulaUsuario=:cedula AND correo=:correo AND estatus=1";
            $str = $this->conexion->prepare($sentencia);
            $str->bindValue(':id', $id, \PDO::PARAM_INT);
            $str->bindValue(':cedula', $cedula, \PDO::PARAM_STR);
            $str->bindValue(':correo', $correo, \PDO::PARAM_STR);
            $str->execute();

            $respuesta = $str->fetchAll();
            return (bool)$respuesta;
        } catch (Exception $e) {
            error_log("[ModeloUsuarios2][coincide] " . $e->getMessage());
            return false;
        }
    }

    private function verificarRol($rol): bool
    {
        try {
            $this->conexion = self::conexSG();
            $this->conexion->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $sentencia = "SELECT id_rol FROM `roles` WHERE id_rol = :rol";
            $str = $this->conexion->prepare($sentencia);
            $str->bindValue(':rol', $rol, \PDO::PARAM_INT);
            $str->execute();

            $respuesta = $str->fetchAll();
            return (bool)$respuesta;
        } catch (Exception $e) {
            error_log("[ModeloUsuarios2][verificarRol] " . $e->getMessage());
            return false;
        }
    }

    private function getUserRoleById($id)
    {
        try {
            $this->conexion = self::conexSG();
            $this->conexion->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $sentencia = "SELECT id_rol FROM `usuarios` WHERE idUsuario = :id AND estatus = 1";
            $str = $this->conexion->prepare($sentencia);
            $str->bindValue(':id', $id, \PDO::PARAM_INT);
            $str->execute();
            $res = $str->fetch(\PDO::FETCH_ASSOC);
            return $res['id_rol'] ?? null;
        } catch (Exception $e) {
            error_log("[ModeloUsuarios2][getUserRoleById] " . $e->getMessage());
            return null;
        }
    }
}
