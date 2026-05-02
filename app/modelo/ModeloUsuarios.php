<?php

namespace App\modelo;

use App\modelo\ModeloBase;
use Exception;

class ModeloUsuarios extends ModeloBase
{

    private $id;
    private $cedula;
    private $nombre;
    private $apellido;
    private $telefono;
    private $contraseña;
    private $correo;
    private $rol;
    private $foto;
    private $bloqueo;

    private $actualizar_contraseña = false;

    public function __construct()
    {
        parent::__construct();
        $this->campoWhitelist = [
            'cedula' => 'cedulaUsuario',
            'telefono' => 'telefonoUsuario',
            'correo' => 'correo',
            'id' => 'idUsuario',
            'rol' => 'id_rol',
        ];
        $this->llavePrimaria = 'idUsuario';
    }

    public function procesarDatos(array $datos): array
    {
        if (empty($datos)) {
            throw new Exception('No se proporcionaron datos para procesar.');
        }

        $this->id = $datos['id'] ?? null;
        $this->bloqueo = $datos['bloqueo'] ?? '';

        $this->nombre = mb_convert_case(trim($datos['nombre'] ?? ''), MB_CASE_TITLE, "UTF-8");
        $this->apellido = mb_convert_case(trim($datos['apellido'] ?? ''), MB_CASE_TITLE, "UTF-8");

        $this->cedula = trim($datos['cedula'] ?? '');
        $this->telefono = trim($datos['telefono'] ?? '');
        $this->correo = mb_strtolower(trim($datos['correo'] ?? ''), "UTF-8");
        $this->rol = $datos['roles_id'] ?? null;
        $this->foto = $datos['foto'] ?? null;

        if (isset($datos['contraseña']) && !empty($datos['contraseña'])) {
            $this->contraseña = password_hash($datos['contraseña'], PASSWORD_BCRYPT);
            $this->actualizar_contraseña = true;
        }

        $accion = $datos['accion'] ?? null;
        return match ($accion) {
            'incluir'   => $this->Incluir(),
            /* 'eliminar'  => $this->Eliminar(),
            'modificar' => $this->Modificar(),
            'buscar' => $this->Buscar(),
            'bloquear' => $this->Bloquear(), */
            'reporte' => $this->Consultar(),
            default     => throw new Exception("Acción no válida."),
        };
    }


    public function Consultar(array $filtro = []): array
    {
        try {
            $conex = $this->conexSG();
            $params = []; // Array para acumular todos los parámetros

            // 1. Base de la consulta
            $sentencia = "SELECT 
                        u.idUsuario,
                        u.cedulaUsuario,
                        u.nombreUsuario,
                        u.apellidoUsuario,
                        u.foto,
                        u.telefonoUsuario,
                        u.correo,
                        u.bloqueo,
                        r.nombre_rol 
                    FROM `usuarios` u 
                    INNER JOIN roles r ON r.id_rol = u.id_rol 
                    WHERE u.estatus = 1";

            // 2. BUSCADOR GLOBAL (Si viene del input de la tabla)
            if (!empty($filtro['filtro'])) {
                $p = "%" . $filtro['filtro'] . "%";
                $sentencia .= " AND (
                            u.cedulaUsuario LIKE :f1 OR 
                            u.nombreUsuario LIKE :f2 OR 
                            u.apellidoUsuario LIKE :f3 OR 
                            u.correo LIKE :f4 OR 
                            r.nombre_rol LIKE :f5
                        )";
                $params[':f1'] = $p;
                $params[':f2'] = $p;
                $params[':f3'] = $p;
                $params[':f4'] = $p;
                $params[':f5'] = $p;
            }

            // 3. BUSCADOR ESPECÍFICO (Del Modal de Reporte)
            if (!empty($this->cedula)) {
                // Usamos % al inicio y al final para máxima compatibilidad
                $sentencia .= " AND u.cedulaUsuario LIKE :cedula";
                $params[':cedula'] = "" . trim($this->cedula) . "%";
            }
            if (!empty($this->nombre)) {
                $sentencia .= " AND u.nombreUsuario LIKE :nombre";
                $params[':nombre'] = "%" . trim($this->nombre) . "%";
            }
            if (!empty($this->apellido)) {
                $sentencia .= " AND u.apellidoUsuario LIKE :apellido";
                $params[':apellido'] = "%" . trim($this->apellido) . "%";
            }

            // Cambiamos !empty por una validación más robusta para IDs numéricos
            if (isset($this->rol) && $this->rol !== "" && $this->rol !== "0") {
                $sentencia .= " AND u.id_rol = :rol";
                $params[':rol'] = (int)$this->rol; // Forzamos a entero
            }

            $sentencia .= " ORDER BY u.idUsuario ASC";

            $str = $conex->prepare($sentencia);

            $str->execute($params);

            $datos = $str->fetchAll();

            return array('accion' => 'consultar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Usuarios', $e->getMessage(), 'Modelo_Consultar');
            return array('accion' => 'error', 'mensaje' => 'Ocurrio un error al listar los usuarios');
        } finally {
            $conex = null;
        }
    }

    private function Incluir(): array
    {
        try {
            // 1. Verificaciones previas
            if (!$this->actualizar_contraseña) {
                return ['accion' => 'error', 'mensaje' => 'Debe proporcionar una contraseña al crear el usuario.'];
            }
            if (!$this->verificarExistencia('rol', $this->rol, 'roles', NULL, 'sg')) {
                return ['accion' => 'error', 'mensaje' => 'El rol no existe.' . $this->rol];
            }

            $conex = $this->conexSG();
            $conex->beginTransaction();

            if ($this->verificarExistencia('cedula', $this->cedula, 'usuarios', 1, 'sg')) {
                return ['accion' => 'error', 'mensaje' => 'La cédula ingresada ya pertenece a un usuario registrado.'];
            }
            if ($this->verificarExistencia('correo', $this->correo, 'usuarios', 1, 'sg')) {
                return ['accion' => 'error', 'mensaje' => 'El correo ingresado ya pertenece a un usuario registrado.'];
            }
            if ($this->verificarExistencia('telefono', $this->telefono, 'usuarios', 1, 'sg')) {
                return ['accion' => 'error', 'mensaje' => 'El telefono ingresado ya pertenece a un usuario registrado.'];
            }

            $Reactivacion = $this->verificarExistencia('cedula', $this->cedula, 'usuarios', 0, 'sg');

            if ($Reactivacion) {
                $sql = "UPDATE `usuarios` SET 
                            `nombreUsuario` = :nombre,
                            `apellidoUsuario` = :apellido,
                            `foto` = :foto,
                            `telefonoUsuario` = :telefono,
                            `contraseña` = :contra,
                            `correo` = :correo,
                            `id_rol` = :rol,
                            `estatus` = 1 
                            WHERE cedulaUsuario = :cedula";
            } else {
                $sql = "INSERT INTO `usuarios`
                            (`cedulaUsuario`, `nombreUsuario`, `apellidoUsuario`,`foto`, `telefonoUsuario`, `contraseña`,`correo`, `id_rol`, `estatus`) 
                            VALUES 
                            (:cedula, :nombre, :apellido,:foto, :telefono, :contra, :correo, :rol, 1)";
            }

            $stmt = $conex->prepare($sql);
            $stmt->bindValue(':cedula', $this->cedula, \PDO::PARAM_STR);
            $stmt->bindValue(':nombre', $this->nombre, \PDO::PARAM_STR);
            $stmt->bindValue(':apellido', $this->apellido, \PDO::PARAM_STR);
            $stmt->bindValue(':foto', $this->foto, \PDO::PARAM_STR);
            $stmt->bindValue(':telefono', $this->telefono, \PDO::PARAM_STR);
            $stmt->bindValue(':contra', $this->contraseña, \PDO::PARAM_STR);
            $stmt->bindValue(':correo', $this->correo, \PDO::PARAM_STR);
            $stmt->bindValue(':rol', $this->rol, \PDO::PARAM_INT);
            $respuesta = $stmt->execute();

            if ($respuesta) {
                $conex->commit();
                return ['accion' => 'incluir', 'mensaje' => "Usuario registrado exitosamente."];
            } else {
                $conex->rollBack();
                throw new Exception('No se pudo registrar el usuario.');
            }
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            logs('Usuarios', $e->getMessage(), 'Modelo_Incluir');
            return ['accion' => 'error', 'mensaje' => 'Ocurrio un error al registrar el usuario'];
        } finally {
            $conex = null;
        }
    }


    /*private function Modificar(): array
    {
        try {
            if ($this->id == 1 && $this->rol != 1) {
                throw new Exception('No puedes cambiar el rol del administrador.');
            }

            if ($this->verificarExistencia('cedulaUsuario', $this->cedula, 0)) {
                return ['accion' => 'error', 'mensaje' => 'La cédula ya fue usada por otro usuario (eliminado). No se puede reutilizar.'];
            }
            if ($this->verificarExistencia('correo', $this->correo, 0)) {
                return ['accion' => 'error', 'mensaje' => 'El correo ya fue usado por otro usuario (eliminado). No se puede reutilizar.'];
            }
            if (!$this->verificarRol($this->rol)) {
                return ['accion' => 'error', 'mensaje' => 'El rol no existe.'];
            }
            if (!$this->verificarExistenciaPropia('cedulaUsuario', $this->cedula, $this->id)) {
                if ($this->verificarExistencia('cedulaUsuario', $this->cedula, 1)) {
                    return ['accion' => 'error', 'mensaje' => 'La cédula ya pertenece a otro usuario activo.'];
                }
            }
            if (!$this->verificarExistenciaPropia('correo', $this->correo, $this->id)) {
                if ($this->verificarExistencia('correo', $this->correo, 1)) {
                    return ['accion' => 'error', 'mensaje' => 'El correo ya pertenece a otro usuario activo.'];
                }
            }
            $conex = $this->conexSG();
            $conex->beginTransaction();
            $sql = "UPDATE `usuarios` SET 
                        `cedulaUsuario` = :cedula,
                        `nombreUsuario` = :nombre,
                        `apellidoUsuario` = :apellido,
                        `foto` = :foto,
                        `correo` = :correo,
                        `telefonoUsuario` = :telefono,
                        `id_rol` = :rol";

            if ($this->actualizar_contraseña) {
                $sql .= ", `contraseña` = :contra";
            }

            $sql .= " WHERE idUsuario = :id";

            $stmt = $conex->prepare($sql);
            $stmt->bindValue(':id', $this->id, \PDO::PARAM_INT);
            $stmt->bindValue(':cedula', $this->cedula, \PDO::PARAM_STR);
            $stmt->bindValue(':nombre', $this->nombre, \PDO::PARAM_STR);
            $stmt->bindValue(':apellido', $this->apellido, \PDO::PARAM_STR);
            $stmt->bindValue(':foto', $this->foto, \PDO::PARAM_STR);
            $stmt->bindValue(':telefono', $this->telefono, \PDO::PARAM_STR);
            $stmt->bindValue(':correo', $this->correo, \PDO::PARAM_STR);
            $stmt->bindValue(':rol', $this->rol, \PDO::PARAM_INT);
            if ($this->actualizar_contraseña) {
                $stmt->bindValue(':contra', $this->contraseña, \PDO::PARAM_STR);
            }
            $respuesta = $stmt->execute();

            if ($respuesta) {
                $conex->commit();
                return ['accion' => 'modificar', 'mensaje' => "Usuario modificado exitosamente."];
            } else {
                $conex->rollBack();
                throw new Exception('No se pudo modificar el usuario.');
            }
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            error_log("Error en modificar Usuarios: " . $e->getMessage());
            return ['accion' => 'error', 'mensaje' => $e->getMessage()];
        } finally {
            $conex = null;
        }
    }

    /*private function Eliminar(): array
    {
        try {
            if ($this->id == 1) {
                throw new Exception('No puedes eliminar el Administrador.');
            }
            if (!$this->verificarExistencia('idUsuario', $this->id, 1)) {
                throw new Exception('El usuario que intenta eliminar no existe.');
            }

            $conex = $this->conexSG();
            $conex->beginTransaction();
            $sql = "UPDATE `usuarios` SET 
                            `estatus` = 0
                            WHERE idUsuario = :id";
            $stmt = $conex->prepare($sql);
            $parametros = [
                ':id' => $this->id
            ];
            $respuesta = $stmt->execute($parametros);

            if ($respuesta) {
                $conex->commit();
                return ['accion' => 'eliminar', 'mensaje' => "Usuario eliminado exitosamente."];
            } else {
                $conex->rollBack();
                throw new Exception('No se pudo eliminar el usuario.');
            }
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            error_log("Error en eliminar Usuarios: " . $e->getMessage());
            return ['accion' => 'error', 'mensaje' => $e->getMessage()];
        } finally {
            $conex = null;
        }
    }

    private function Bloquear(): array
    {
        try {
            if ($this->id == 1) {
                throw new Exception('No puedes bloquear al Administrador.');
            }
            // 1. Verificamos que exista
            if (!$this->verificarExistencia('idUsuario', $this->id, 1)) {
                throw new Exception('El usuario no existe o ya está eliminado.');
            }

            $conex = $this->conexSG();
            $conex->beginTransaction();

            $nuevoEstado = ($this->bloqueo == 1) ? 2 : 1;
            $mensajeExito = ($nuevoEstado == 2) ? "Usuario bloqueado exitosamente." : "Usuario desbloqueado exitosamente.";
            $mensajeError = ($nuevoEstado == 2) ? "No se pudo bloquear al usuario." : "No se pudo desbloquear al usuario.";

            $sql = "UPDATE `usuarios` SET `bloqueo` = :estado WHERE idUsuario = :id";
            $stmt = $conex->prepare($sql);

            $respuesta = $stmt->execute([
                ':estado' => $nuevoEstado,
                ':id' => $this->id
            ]);

            if ($respuesta) {
                if ($nuevoEstado == 2) {
                    $conex->commit();
                    return ['accion' => 'bloquear', 'tipo' => 'bloquear', 'mensaje' => $mensajeExito];
                } else {
                    $conex->commit();
                    return ['accion' => 'bloquear', 'tipo' => 'desbloquear', 'mensaje' => $mensajeExito];
                }
            } else {
                $conex->rollBack();
                throw new Exception($mensajeError);
            }
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            error_log("Error en Bloquear/Desbloquear Usuarios: " . $e->getMessage());
            return ['accion' => 'error', 'mensaje' => $e->getMessage()];
        } finally {
            $conex = null;
        }
    }

    private function Buscar(): array
    {
        try {
            $conex = $this->conexSG();
            $sentencia = 'SELECT * FROM `usuarios` 
            WHERE idUsuario=:id AND estatus=1;';
            $str = $conex->prepare($sentencia);
            $str->bindParam(':id', $this->id);
            $str->execute();
            $datos = $str->fetchAll();
            $resultado = array('accion' => 'buscar', 'datos' => $datos);
        } catch (Exception $e) {
            error_log("Error en buscar Usuarios: $" . $e->getMessage());
            $resultado = array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = null;
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

    private function verificarExistenciaPropia($campo, $valor, $id): bool
    {

        try {
            if (!array_key_exists($campo, $this->campoWhitelist)) {
                throw new Exception('Campo no permitido.');
            }

            $columna = $this->campoWhitelist[$campo];

            $this->conexion = self::conexSG();
            $sentencia = "SELECT usuarios.idUsuario FROM `usuarios` WHERE usuarios.idUsuario=:id AND $campo = :valor AND usuarios.estatus=1;";
            $str = $this->conexion->prepare($sentencia);
            $str->bindParam(':id', $id,);
            $str->bindParam(':valor', $valor);
            $str->execute();

            $respuesta = $str->fetchAll();
            if ($respuesta) {
                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            error_log("Error en coincide Usuarios: " . $e->getMessage());
            return false;
        }
    }
    private function verificarRol($rol): bool
    {
        try {
            $this->conexion = self::conexSG();
            $sentencia = "SELECT roles.id_rol FROM `roles` WHERE roles.id_rol = :rol;";
            $str = $this->conexion->prepare($sentencia);
            $str->bindParam(':rol', $rol);
            $str->execute();

            $respuesta = $str->fetchAll();
            if ($respuesta) {
                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            error_log("Error en verificarRol Usuarios: " . $e->getMessage());
            return false;
        }
    } */
}
