<?php

namespace App\modelo;

use App\modelo\ModeloBase;
use Exception;

class ModeloRoles extends ModeloBase
{
    private int $id;
    private string $nombre;
    private array $id_modulo;
    private array $c_incluir;
    private array $c_modificar;
    private array $c_eliminar;
    private array $c_reporte;
    private array $c_otros;

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

        $this->id = $datos['id'] ?? 0;
        $this->nombre = mb_convert_case(trim($datos['nombre'] ?? ''), MB_CASE_TITLE, "UTF-8");
        $accion = $datos['accion'] ?? null;

        $this->id_modulo   = $datos['id_modulo']   ?? [];
        $this->c_incluir   = $datos['c_incluir']   ?? [];
        $this->c_modificar = $datos['c_modificar'] ?? [];
        $this->c_eliminar  = $datos['c_eliminar']  ?? [];
        $this->c_reporte   = $datos['c_reporte']   ?? [];
        $this->c_otros     = $datos['c_otros']     ?? [];


        return match ($accion) {
            'incluir'   => $this->Incluir(),
            'modificar' => $this->Modificar(),
            'buscar'    => $this->Buscar(),
            'eliminar'    => $this->Eliminar(),
            default     => throw new Exception("Acción no válida."),
        };
    }
    public function consultar()
    {
        try {
            $conex = null;
            $conex = $this->conexSG();
            $sentencia = 'SELECT * FROM roles WHERE nivel_rol != 1';
            $stmt = $conex->prepare($sentencia);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            return ['accion' => 'consultar', 'datos' => $datos];
        } catch (Exception $e) {
            logs('Roles', $e->getMessage(), 'Modelo_Consultar');
            return ['accion' => 'error', 'mensaje' => $e->getMessage()];
        }
    }

    public function consultarModulo()
    {
        try {
            $conex = $this->conexSG();
            $sentencia = 'SELECT modulo.id_modulo, modulo.nombre_modulo FROM `modulo` WHERE modulo.id_modulo NOT IN (4, 5, 8, 1, 2, 3)';
            $stmt = $conex->prepare($sentencia);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            $resultado = array('accion' => 'consultarModulo', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Roles', $e->getMessage(), 'Modelo_ConsultarModulos');
            $resultado = array('accion' => 'error', 'mensaje' => $e->getMessage());
        }
        return $resultado;
    }

    public function Buscar()
    {
        try {
            $conex = $this->conexSG();
            $sentencia = 'SELECT roles.id_rol,roles.nombre_rol,permiso.eliminar,permiso.modificar,permiso.incluir,permiso.reporte,permiso.otros,modulo.nombre_modulo,modulo.id_modulo FROM `roles` 
            INNER JOIN permiso ON roles.id_rol=permiso.id_rol 
            INNER JOIN modulo ON permiso.id_modulo=modulo.id_modulo 
            WHERE roles.id_rol=:id';
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

    private function Incluir()
    {
        try {
            $conex = null;

            foreach ($this->id_modulo as $id) {
                if (!$this->verificarExistencia('id_modulo', $id, 'modulo', NULL, 'sg')) {
                    throw new Exception(ASSOCIATES);
                }
            }
            $idsProtegidos = [1, 2, 3, 4, 5, 8];
            if (!empty(array_intersect($this->id_modulo, $idsProtegidos))) {
                throw new Exception(ASSOCIATES);
            }

            $conex = $this->conexSG();
            $conex->beginTransaction();

            if ($this->verificarExistencia('nombre', $this->nombre, 'roles', 1, 'sg', bloquear: true)) {
                throw new Exception(DUPLICATE_NAME);
            }


            $sql = 'INSERT INTO `roles`(`nombre_rol`, `estatus`) VALUES (:nombre, 1)';
            $stmt = $conex->prepare($sql);
            $stmt->execute([':nombre' => $this->nombre]);

            $id_rol = $conex->lastInsertId();

            $sentencia = "INSERT INTO `permiso`(`id_rol`, `id_modulo`, `eliminar`, `modificar`, `incluir`, `reporte`, `otros`) 
                        VALUES (:id_rol, :id_modulo, :eliminar, :modificar, :incluir, :reporte, :otros)";
            $stmtPermiso = $conex->prepare($sentencia);

            foreach ($this->id_modulo as $modulo) {
                $stmtPermiso->execute([
                    ':id_rol'    => $id_rol,
                    ':id_modulo' => (int)$modulo,
                    ':eliminar'  => isset($this->c_eliminar[$modulo]) ? 1 : 0,
                    ':modificar' => isset($this->c_modificar[$modulo]) ? 1 : 0,
                    ':incluir'   => isset($this->c_incluir[$modulo]) ? 1 : 0,
                    ':reporte'   => isset($this->c_reporte[$modulo]) ? 1 : 0,
                    ':otros'     => isset($this->c_otros[$modulo]) ? 1 : 0
                ]);
            }

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
            foreach ($this->id_modulo as $id) {
                if (!$this->verificarExistencia('id_modulo', $id, 'modulo', NULL, 'sg')) {
                    throw new Exception(ASSOCIATES);
                }
            }
            $idsProtegidos = [1, 2, 3, 4, 5, 8];
            if (!empty(array_intersect($this->id_modulo, $idsProtegidos))) {
                throw new Exception(ASSOCIATES);
            }
            $conex = $this->conexSG();
            $conex->beginTransaction();

            if (!$this->verificarExistenciaPropia('nombre', $this->nombre, $this->id, 'roles', 1, 'sg', true)) {
                if ($this->verificarExistencia('nombre', $this->nombre, 'roles', 1, 'sg', bloquear: true)) {
                    throw new Exception(DUPLICATE_NAME);
                }
            }
            $sql = 'UPDATE `roles` SET `nombre_rol`=:nombre WHERE id_rol=:id';
            $stmt = $conex->prepare($sql);
            $parametros = [
                ':id' => $this->id,
                ':nombre' => $this->nombre
            ];
            $stmt->execute($parametros);

            $sql = 'DELETE FROM `permiso` WHERE `id_rol` = :id';
            $stmt = $conex->prepare($sql);
            $stmt->execute([':id' => $this->id]);

            $sql = 'INSERT INTO `permiso`(`id_rol`, `id_modulo`, `eliminar`, `modificar`, `incluir`, `reporte`, `otros`) 
                                VALUES (:id_rol,:id_modulo,:eliminar,:modificar,:incluir,:reporte,:otros)';
            $stmt = $conex->prepare($sql);

            foreach ($this->id_modulo as $modulo) {
                $stmt->execute([
                    ':id_rol'    => $this->id,
                    ':id_modulo' => (int)$modulo,
                    ':eliminar'  => isset($this->c_eliminar[$modulo]) ? 1 : 0,
                    ':modificar' => isset($this->c_modificar[$modulo]) ? 1 : 0,
                    ':incluir'   => isset($this->c_incluir[$modulo]) ? 1 : 0,
                    ':reporte'   => isset($this->c_reporte[$modulo]) ? 1 : 0,
                    ':otros'     => isset($this->c_otros[$modulo]) ? 1 : 0
                ]);
            }

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

    private function Eliminar()
    {
        try {
            $conex = null;
            $idsProtegidos = [1, 2];
            if (!empty(array_intersect($this->id_modulo, $idsProtegidos))) {
                throw new Exception(INVALID_ID);
            }

            $conex = $this->conexSG();
            $conex->beginTransaction();
            if ($this->verificarExistencia('id', $this->id, 'usuarios', 1, bloquear: true)) {
                throw new Exception(ASSOCIATES);
            }
            if ($this->verificarExistencia('id', $this->id, 'roles', NULL, bloquear: true)) {
                throw new Exception(INVALID_ID.'0');
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
}
