<?php

namespace App\modelo;

use Exception;

class ModeloRoles extends Conexion
{
    private int $id;
    private string $nombre;
    private string $descripcion;
    private $objPermiso;
    private array $permisos_seleccionados;

    public function __construct()
    {
        parent::__construct();
        $this->campoWhitelist = [
            'nombre' => 'nombre_rol',
            'id_modulo' => 'id_modulo',
            'id' => 'id_rol',
        ];
        $this->llavePrimaria = 'id_rol';
    }

    public function procesarDatos(array $datos)
    {
        if (empty($datos)) {
            throw new Exception('No se proporcionaron datos para procesar.');
        }

        $this->ValidarExpresiones($datos);

        $this->id = $datos['id'] ?? 0;
        $this->nombre = mb_convert_case(trim($datos['nombre'] ?? ''), MB_CASE_TITLE, "UTF-8");
        $this->descripcion = mb_convert_case(trim($datos['descripcion'] ?? ''), MB_CASE_TITLE, "UTF-8");
        $this->permisos_seleccionados = $datos['permisos'] ?? [];
        $accion = $datos['accion'] ?? null;

        return match ($accion) {
            'incluir' => $this->Incluir(),
            'modificar' => $this->Modificar(),
            'buscar' => $this->Buscar(),
            'eliminar' => $this->Eliminar(),
            'guardar_permisos' => $this->GuardarPermisos(),
            default => throw new Exception("Acción no válida."),
        };
    }

    public function setPermiso(ModeloPermisos $permiso){
        $this->objPermiso = $permiso;
    }
    public function consultar(array $filtro = []): array
    {
        try {
            $conex = $this->conexSG();
            $params = [];

            // 1. Base de la consulta: Excluimos el nivel 1 (Super Usuario) por seguridad
            $sentencia = "SELECT * FROM roles WHERE nivel_rol != 1";

            // 2. Aplicamos el buscador general si existe
            if (!empty($filtro['filtro'])) {
                $p = "%" . $filtro['filtro'] . "%";
                $sentencia .= " AND (nombre_rol LIKE :f1)";
                $params[':f1'] = $p;
            }

            // 3. Ordenamos por el ID del rol
            $sentencia .= " ORDER BY id_rol ASC";

            $stmt = $conex->prepare($sentencia);

            // 4. Ejecutamos pasando los parámetros para evitar inyecciones SQL
            $stmt->execute($params);
            $datos = $stmt->fetchAll();

            return ['accion' => 'consultar', 'datos' => $datos];
        } catch (Exception $e) {
            // Registro del error en la bitácora de errores del sistema
            logs('Roles', $e->getMessage(), 'Modelo_Consultar');
            return ['accion' => 'error', 'mensaje' => 'Error al listar roles: ' . $e->getMessage()];
        } finally {
            $conex = null;
        }
    }

    public function Buscar()
    {
        try {
            $conex = $this->conexSG();
            $sentencia = 'SELECT nombre_rol, id_rol, descripcion FROM `roles` WHERE id_rol = :id;';
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            $resultado = array('accion' => 'buscar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Roles', $e->getMessage(), 'Modelo_Buscar');
            $resultado = array('accion' => 'error', 'mensaje' => $e->getMessage());
        }
        return $resultado;
    }

    public function CargarPermisos($id)
    {
        try {
            $conex = $this->conexSG();
            $sentencia = 'SELECT 
                            :id1 AS id_rol, 
                            (SELECT nombre_rol FROM roles WHERE id_rol = :id2) AS nombre_rol, 
                            m.id_modulo, m.nombre_modulo,m.icono, m.estatus AS estatus_modulo,
                            p.id_permiso, p.nombre AS nombre_permiso, p.descripcion, p.clave,
                            CASE WHEN pr.id_permiso_rol IS NOT NULL THEN 1 ELSE 0 END AS asignado
                        FROM modulos m
                        INNER JOIN permisos p ON p.id_modulo = m.id_modulo
                        LEFT JOIN permisos_rol pr ON pr.id_permiso = p.id_permiso AND pr.id_rol = :id3
                        WHERE m.id_modulo NOT IN (4, 5, 8, 1, 2, 3, 99)
                        ORDER BY m.id_modulo ASC, p.id_permiso ASC';
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id1', $id);
            $stmt->bindParam(':id2', $id);
            $stmt->bindParam(':id3', $id);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            $resultado = array('accion' => 'CargarPermisos', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Roles', $e->getMessage(), 'Modelo_CargarPermisos');
            $resultado = array('accion' => 'error', 'mensaje' => $e->getMessage());
        }
        return $resultado;
    }

    private function Incluir()
    {
        try {
            $conex = null;
            $conex = $this->conexSG();
            $conex->beginTransaction();

            if ($this->verificarExistencia('nombre', $this->nombre, 'roles', 1, 'sg', bloquear: true)) {
                throw new Exception(DUPLICATE_NAME);
            }

            // 1. Definimos los arreglos base (lo que siempre es obligatorio)
            $campos     = ['nombre_rol'];
            $marcadores = [':nombre'];
            $params     = [':nombre' => $this->nombre];

            // 2. Evaluamos los campos opcionales
            if (!empty($this->descripcion)) {
                $campos[]               = 'descripcion';
                $marcadores[]           = ':descripcion';
                $params[':descripcion'] = $this->descripcion;
            }

            // 3. Construimos el string SQL usando implode
            $sql = 'INSERT INTO `roles` (`' . implode('`, `', $campos) . '`) VALUES (' . implode(', ', $marcadores) . ')';

            // 4. Preparamos y ejecutamos
            $stmt = $conex->prepare($sql);
            $stmt->execute($params);

            $conex->commit();
            return ['accion' => 'exito'];
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            logs('Roles', $e->getMessage(), 'Modelo_Incluir');
            return ['accion' => 'error', 'codigo' => $e->getMessage()];
        } finally {
            $conex = null;
        }
    }

    private function Modificar()
{
    try {
        $conex = null;
        $conex = $this->conexSG();
        $conex->beginTransaction();

        if (!$this->verificarExistencia('id', $this->id, 'roles', 1, 'sg', bloquear: true)) {
                throw new Exception(INVALID_ID);
        }
        if (!$this->verificarExistenciaPropia('nombre', $this->nombre, $this->id, 'roles', 1, 'sg', bloquear:true)) {
            if ($this->verificarExistencia('nombre', $this->nombre, 'roles', 1, 'sg', bloquear: true)) {
                throw new Exception(DUPLICATE_NAME);
            }
        }

        $setCampos  = ['`nombre_rol` = :nombre'];
        $parametros = [
            ':id'     => $this->id,
            ':nombre' => $this->nombre
        ];

        if (!empty($this->descripcion)) {
            $setCampos[] = '`descripcion` = :descripcion';
            $parametros[':descripcion'] = $this->descripcion;
        }

        // 3. Construimos el string SQL dinámicamente usando implode
        $sql = 'UPDATE `roles` SET ' . implode(', ', $setCampos) . ' WHERE `id_rol` = :id';
        
        // 4. Preparamos y ejecutamos
        $stmt = $conex->prepare($sql);
        $stmt->execute($parametros);

        $conex->commit();
        return ['accion' => 'exito'];
        
    } catch (Exception $e) {
        if ($conex && $conex->inTransaction()) {
            $conex->rollBack();
        }
        logs('Roles', $e->getMessage(), 'Modelo_Modificar');
        return ['accion' => 'error', 'codigo' => $e->getMessage()];
    } finally {
        $conex = null;
    }
}
    private function GuardarPermisos()
    {
        $conex = null;
        try {
            $conex = $this->conexSG();
            $conex->beginTransaction();

            $sql = 'DELETE FROM `permisos_rol` WHERE `id_rol` = :id';
            $stmt = $conex->prepare($sql);
            $stmt->execute([':id' => $this->id]);

            $sqlInsert = 'INSERT INTO `permisos_rol` (`id_rol`, `id_permiso`) VALUES (:id_rol, :id_permiso)';
            $stmtInsert = $conex->prepare($sqlInsert);

            foreach ($this->permisos_seleccionados as $id_permiso => $valor) {
                if ($valor == 1) {
                    $stmtInsert->execute([
                        ':id_rol' => $this->id,
                        ':id_permiso' => (int)$id_permiso
                    ]);
                }
            }

            $conex->commit();
            return ['accion' => 'exito'];
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            logs('Roles', $e->getMessage(), 'Modelo_GuardarPermisos');
            return ['accion' => 'error', 'codigo' => $e->getMessage()];
        } finally {
            $conex = null;
        }
    }

    private function Eliminar()
    {
        try {
            $conex = null;
            $idsProtegidos = [1, 2];
            if (in_array($this->id, $idsProtegidos)) {
                throw new Exception(INVALID_ID);
            }

            $conex = $this->conexSG();
            $conex->beginTransaction();
            if ($this->verificarExistencia('id', $this->id, 'usuarios', 1, bloquear: true)) {
                throw new Exception(ASSOCIATES);
            }
            if ($this->verificarExistencia('id', $this->id, 'roles', NULL, bloquear: true)) {
                throw new Exception(INVALID_ID . '0');
            }

            $sentencia = 'DELETE FROM roles WHERE id_rol = :id';
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();

            $conex->commit();
            return ['accion' => 'exito'];
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            logs('Roles', $e->getMessage(), 'Modelo_Eliminar');
            return ['accion' => 'error', 'codigo' => $e->getMessage()];
        } finally {
            $conex = null;
        }
    }

    private function ValidarExpresiones(array $datos): void
    {
        if (!empty($datos['id']) && !preg_match('/^[0-9]+$/', $datos['id'])) {
            throw new Exception('Id inválido.');
        }
        if (!empty($datos['nombre']) && !preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{1,30}$/', $datos['nombre'])) {
            throw new Exception('Nombre inválido.');
        }
        // Validacion de permisos dinámicos eliminada porque se parsean como enteros
    }
}
