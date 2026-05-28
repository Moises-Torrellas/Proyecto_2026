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

    private $c_ingresar;
    private $c_registrar;
    private $c_modificar;
    private $c_eliminar;
    private $c_reporte;
    private $c_otros;

    private $obj_roles;

    public function __construct()
    {
        parent::__construct();
        $this->campoWhitelist = [
            'cedula' => 'cedulaUsuario',
            'telefono' => 'telefonoUsuario',
            'correo' => 'correo',
            'estatus' => 'estatus',
            'id' => 'idUsuario',
            'rol' => 'id_rol',
        ];
        $this->llavePrimaria = 'idUsuario';

        $this->obj_roles = new ModeloRoles;
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

        $this->c_ingresar  = $datos['c_ingresar']  ?? [];
        $this->c_registrar = $datos['c_registrar'] ?? [];
        $this->c_modificar = $datos['c_modificar'] ?? [];
        $this->c_eliminar  = $datos['c_eliminar']  ?? [];
        $this->c_reporte   = $datos['c_reporte']   ?? [];
        $this->c_otros     = $datos['c_otros']     ?? [];
        
        $accion = $datos['accion'] ?? null;
        return match ($accion) {
            'incluir'   => $this->Incluir(),
            'modificar' => $this->Modificar(),
            'eliminar'  => $this->Eliminar(),
            'buscar' => $this->Buscar(),
            'bloquear' => $this->Bloquear(),
            'reporte' => $this->Consultar(),
            'CargarPermisosUsuario' => $this->CargarPermisosUsuario(),
            'guardar_permisos_usuario' => $this->GuardarPermisosUsuario(),
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
                    WHERE u.estatus != 0";

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
            $conex = null;
            if (!$this->actualizar_contraseña) {
                throw new Exception(VALIDATION);
            }
            if (!$this->verificarExistencia('rol', $this->rol, 'roles', NULL, 'sg')) {
                throw new Exception(INVALID_ID);
            }

            $conex = $this->conexSG();
            $conex->beginTransaction();

            if ($this->verificarExistencia('cedula', $this->cedula, 'usuarios', 1, 'sg', bloquear: true)) {
                throw new Exception(DUPLICATE_CEDULA);
            }
            if ($this->verificarExistencia('correo', $this->correo, 'usuarios', 1, 'sg', bloquear: true)) {
                throw new Exception(DUPLICATE_EMAIL);
            }
            if ($this->verificarExistencia('telefono', $this->telefono, 'usuarios', 1, 'sg', bloquear: true)) {
                throw new Exception(DUPLICATE_PHONE);
            }

            $Reactivacion = $this->verificarExistencia('cedula', $this->cedula, 'usuarios', 0, 'sg', bloquear: true);

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

            $stmt->execute();

            $idUsuario = $Reactivacion
                ? $conex->query("SELECT idUsuario FROM usuarios WHERE cedulaUsuario = '{$this->cedula}'")->fetchColumn()
                : $conex->lastInsertId();

            $stmtDel = $conex->prepare("DELETE FROM permisos_usuarios WHERE idUsuario = :idUsuario");
            $stmtDel->execute([':idUsuario' => $idUsuario]);

            $sqlCopy = "INSERT INTO permisos_usuarios (idUsuario, id_modulo, ingresar, registrar, eliminar, modificar, reporte, otros)
                        SELECT :idUsuario, id_modulo, ingresar, registrar, eliminar, modificar, reporte, otros
                        FROM permisos_roles WHERE id_rol = :idRol";
            $stmtCopy = $conex->prepare($sqlCopy);
            $stmtCopy->execute([':idUsuario' => $idUsuario, ':idRol' => $this->rol]);

            $conex->commit();
            return ['accion' => 'exito'];
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            logs('Usuarios', $e->getMessage(), 'Modelo_Incluir');
            return ['accion' => 'error', 'codigo' => $e->getMessage()];
        } finally {
            $conex = null;
        }
    }


    private function Modificar(): array
    {
        try {

            $conex = null;
            if ($this->verificarExistencia('cedula', $this->cedula, 'usuarios', 0, 'sg')) {
                throw new Exception(DUPLICATE_CEDULA . '0');
            }
            if ($this->verificarExistencia('correo', $this->correo, 'usuarios', 0, 'sg')) {
                throw new Exception(DUPLICATE_EMAIL . '0');
            }
            if ($this->verificarExistencia('telefono', $this->telefono, 'usuarios', 0, 'sg')) {
                throw new Exception(DUPLICATE_PHONE . '0');
            }

            if (!$this->verificarExistencia('rol', $this->rol, 'roles', NULL, 'sg')) {
                throw new Exception(INVALID_ID);
            }

            $conex = $this->conexSG();
            $conex->beginTransaction();

            $stmtRolActual = $conex->prepare("SELECT id_rol FROM usuarios WHERE idUsuario = :id");
            $stmtRolActual->execute([':id' => $this->id]);
            $rolActual = $stmtRolActual->fetchColumn();

            if (!$this->verificarExistenciaPropia('cedula', $this->cedula, $this->id, 'usuarios', 1, 'sg', bloquear: true)) {
                if ($this->verificarExistencia('cedula', $this->cedula, 'usuarios', 1, 'sg', bloquear: true)) {
                    throw new Exception(DUPLICATE_CEDULA);
                }
            }
            if (!$this->verificarExistenciaPropia('correo', $this->correo, $this->id, 'usuarios', 1, 'sg', bloquear: true)) {
                if ($this->verificarExistencia('correo', $this->correo, 'usuarios', 1, 'sg', bloquear: true)) {
                    throw new Exception(DUPLICATE_EMAIL);
                }
            }
            if (!$this->verificarExistenciaPropia('telefono', $this->telefono, $this->id, 'usuarios', 1, 'sg', bloquear: true)) {
                if ($this->verificarExistencia('telefono', $this->telefono, 'usuarios', 1, 'sg', bloquear: true)) {
                    throw new Exception(DUPLICATE_PHONE);
                }
            }

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
            $stmt->execute();

            if ($rolActual != $this->rol) {
                $stmtDel = $conex->prepare("DELETE FROM permisos_usuarios WHERE idUsuario = :idUsuario");
                $stmtDel->execute([':idUsuario' => $this->id]);

                $sqlCopy = "INSERT INTO permisos_usuarios (idUsuario, id_modulo, ingresar, registrar, eliminar, modificar, reporte, otros)
                            SELECT :idUsuario, id_modulo, ingresar, registrar, eliminar, modificar, reporte, otros
                            FROM permisos_roles WHERE id_rol = :idRol";
                $stmtCopy = $conex->prepare($sqlCopy);
                $stmtCopy->execute([':idUsuario' => $this->id, ':idRol' => $this->rol]);
            }

            $conex->commit();
            return ['accion' => 'exito'];
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            logs('Usuarios', $e->getMessage(), 'Modelo_Modificar');
            return ['accion' => 'error', 'codigo' => $e->getMessage()];
        } finally {
            $conex = null;
        }
    }

    private function Eliminar(): array
    {
        try {
            $conex = null;
            if ($this->id == 1) {
                throw new Exception(ASSOCIATES);
            }

            $conex = $this->conexSG();
            $conex->beginTransaction();
            if (!$this->verificarExistencia('id', $this->id, 'usuarios', 1, 'sg', bloquear: true)) {
                throw new Exception(INVALID_ID);
            }

            $sql = "UPDATE `usuarios` SET 
                            `estatus` = 0
                            WHERE idUsuario = :id";
            $stmt = $conex->prepare($sql);
            $stmt->bindValue(':id', $this->id, \PDO::PARAM_INT);
            $stmt->execute();

            $stmtDel = $conex->prepare("DELETE FROM permisos_usuarios WHERE idUsuario = :idUsuario");
            $stmtDel->execute([':idUsuario' => $this->id]);


            $conex->commit();
            return ['accion' => 'exito'];
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            logs('Usuarios', $e->getMessage(), 'Modelo_Eliminar');
            return ['accion' => 'error', 'codigo' => $e->getMessage()];
        } finally {
            $conex = null;
        }
    }

    private function Bloquear(): array
    {

        try {
            $conex = null;
            if ($this->id == 1) {
                throw new Exception(ASSOCIATES);
            }

            $conex = $this->conexSG();
            $conex->beginTransaction();

            if (!$this->verificarExistencia('id', $this->id, 'usuarios', 1, 'sg', bloquear: true)) {
                throw new Exception(INVALID_ID);
            }

            $nuevoEstado = ($this->bloqueo == 1) ? 2 : 1;

            $sql = "UPDATE `usuarios` SET `bloqueo` = :estado WHERE idUsuario = :id";
            $stmt = $conex->prepare($sql);

            $stmt->execute([
                ':estado' => $nuevoEstado,
                ':id' => $this->id
            ]);


            $conex->commit();
            return ['accion' => 'exito'];
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            logs('Usuarios', $e->getMessage(), 'Modelo_Bloquear');
            return ['accion' => 'error', 'codigo' => $e->getMessage()];
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

    public function ConsultarRoles()
    {
        $respuesta = $this->obj_roles->Consultar();
        $respuesta['accion'] = 'consultarRoles';
        return $respuesta;
    }

    public function CargarPermisosUsuario()
    {
        try {
            $conex = $this->conexSG();
            $sentencia = 'SELECT :id1 AS idUsuario, 
                                (SELECT id_rol FROM usuarios WHERE idUsuario = :id3) AS id_rol,
                                m.id_modulo, m.nombre_modulo, 
                                COALESCE(MAX(pu.ingresar), 0) AS ingresar, COALESCE(MAX(pu.registrar), 0) AS registrar, 
                                COALESCE(MAX(pu.eliminar), 0) AS eliminar, COALESCE(MAX(pu.modificar), 0) AS modificar, 
                                COALESCE(MAX(pu.reporte), 0) AS reporte, COALESCE(MAX(pu.otros), 0) AS otros 
                        FROM modulo m 
                        LEFT JOIN permisos_usuarios pu ON m.id_modulo = pu.id_modulo AND pu.idUsuario = :id2 
                        WHERE m.id_modulo NOT IN (4, 5, 8, 1, 2, 3, 99)
                        GROUP BY m.id_modulo, m.nombre_modulo
                        ORDER BY m.id_modulo ASC';
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id1', $this->id);
            $stmt->bindParam(':id2', $this->id);
            $stmt->bindParam(':id3', $this->id);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            $resultado = array('accion' => 'CargarPermisosUsuario', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Usuarios', $e->getMessage(), 'Modelo_CargarPermisosUsuario');
            $resultado = array('accion' => 'error', 'mensaje' => $e->getMessage());
        }
        return $resultado;
    }

    private function GuardarPermisosUsuario()
    {
        try {
            $conex = null;
            $conex = $this->conexSG();
            $conex->beginTransaction();

            $sql = 'DELETE FROM `permisos_usuarios` WHERE `idUsuario` = :id';
            $stmt = $conex->prepare($sql);
            $stmt->execute([':id' => $this->id]);

            $sql = 'INSERT INTO `permisos_usuarios`(`idUsuario`, `id_modulo`, `ingresar`, `registrar`, `eliminar`, `modificar`, `reporte`, `otros`) 
                    VALUES (:idUsuario,:id_modulo,:ingresar,:registrar,:eliminar,:modificar,:reporte,:otros)';
            $stmt = $conex->prepare($sql);

            // Fetch all valid module ids first
            $sqlModulos = 'SELECT id_modulo FROM modulo WHERE id_modulo NOT IN (4, 5, 8, 1, 2, 3, 99)';
            $stmtModulos = $conex->prepare($sqlModulos);
            $stmtModulos->execute();
            $modulosValidos = $stmtModulos->fetchAll(\PDO::FETCH_COLUMN);

            foreach ($modulosValidos as $modulo) {
                $ing = isset($this->c_ingresar[$modulo]) ? 1 : 0;
                $reg = isset($this->c_registrar[$modulo]) ? 1 : 0;
                $eli = isset($this->c_eliminar[$modulo]) ? 1 : 0;
                $mod = isset($this->c_modificar[$modulo]) ? 1 : 0;
                $rep = isset($this->c_reporte[$modulo]) ? 1 : 0;
                $otr = isset($this->c_otros[$modulo]) ? 1 : 0;

                if ($ing || $reg || $eli || $mod || $rep || $otr) {
                    $stmt->execute([
                        ':idUsuario' => $this->id,
                        ':id_modulo' => (int)$modulo,
                        ':ingresar'  => $ing,
                        ':registrar' => $reg,
                        ':eliminar'  => $eli,
                        ':modificar' => $mod,
                        ':reporte'   => $rep,
                        ':otros'     => $otr
                    ]);
                }
            }

            $conex->commit();
            return ['accion' => 'exito'];
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            logs('Usuarios', $e->getMessage(), 'Modelo_GuardarPermisosUsuario');
            return ['accion' => 'error', 'codigo' => $e->getMessage()];
        } finally {
            $conex = null;
        }
    }
}
