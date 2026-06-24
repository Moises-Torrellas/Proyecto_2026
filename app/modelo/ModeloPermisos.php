<?php

namespace App\modelo;

use Exception;

class ModeloPermisos extends Conexion
{
    private $id;
    private $nombre;
    private $descripcion;
    private $clave;
    private $modulo;
    private $bloqueo;

    public function __construct()
    {
        parent::__construct();

        $this->campoWhitelist = [
            'id'          => 'id_permiso',
            'modulo'      => 'id_modulo',
            'nombre'      => 'nombre',
            'clave'       => 'clave',
            'descripcion' => 'descripcion',
        ];

        $this->llavePrimaria = 'id_permiso';
    }

    private function ValidarExpresiones(array $datos): void
    {
        if (!empty($datos['id']) && !preg_match('/^[0-9]+$/', $datos['id'])) {
            throw new Exception('Id inválido.');
        }
        if (!empty($datos['bloqueo']) && !preg_match('/^[1-2]+$/', $datos['bloqueo'])) {
            throw new Exception('bloqueo inválido.');
        }
        if (!empty($datos['modulo']) && !preg_match('/^[0-9]+$/', $datos['modulo'])) {
            throw new Exception('Id de módulo inválido.');
        }
        if (!empty($datos['nombre']) && !preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,50}$/', $datos['nombre'])) {
            throw new Exception('Nombre inválido.');
        }
        if (!empty($datos['descripcion']) && !preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,100}$/', $datos['descripcion'])) {
            throw new Exception('Descripción inválida.');
        }
        if (!empty($datos['clave']) && !preg_match('/^[a-z_]{5,50}$/', $datos['clave'])) {
            throw new Exception('La clave del permiso es inválida. Debe estar en minúsculas, usar guion bajo y tener entre 5 y 50 caracteres.');
        }
    }

    public function ProcesarDatos(array $datos): array
    {
        if (empty($datos)) {
            throw new Exception('No se proporcionaron datos para procesar.');
        }

        $this->ValidarExpresiones($datos);
        $this->id          = $datos['id'] ?? null;
        $this->bloqueo          = $datos['bloqueo'] ?? null;
        $this->modulo      = $datos['modulo'] ?? null;
        $this->nombre      = mb_convert_case(trim($datos['nombre'] ?? ''), MB_CASE_TITLE, "UTF-8");
        $this->descripcion = !empty($datos['descripcion']) ? mb_convert_case(trim($datos['descripcion']), MB_CASE_TITLE, "UTF-8") : null;
        $this->clave       = mb_convert_case(trim($datos['clave'] ?? ''), MB_CASE_LOWER, "UTF-8");

        $accion = $datos['accion'] ?? null;
        return match ($accion) {
            'buscar'    => $this->Buscar(),
            'bloquear'    => $this->Bloqueo(),
            'incluir'   => $this->Incluir(),
            'modificar' => $this->Modificar(),
            default     => throw new Exception('La acción no es válida')
        };
    }

    public function Consultar(array $filtro = []): array
    {
        $conex = null;
        try {
            $conex = $this->conexSG();
            $params = [];

            // Consulta ajustada con alias para evitar choques de nombres en la vista
            $sentencia = "SELECT 
                            p.id_permiso, 
                            p.nombre AS nombre_permiso, 
                            p.clave, 
                            p.descripcion, 
                            p.estatus AS estatus_permiso, 
                            m.id_modulo, 
                            m.nombre_modulo, 
                            m.estatus AS estatus_modulo,
                            m.icono 
                          FROM permisos p 
                          INNER JOIN modulos m ON p.id_modulo = m.id_modulo 
                          WHERE 1=1";

            if (!empty($filtro['filtro'])) {
                $p = "%" . $filtro['filtro'] . "%";
                // El filtro sigue buscando por el nombre del permiso o su clave
                $sentencia .= " AND (p.nombre LIKE :f1 OR p.clave LIKE :f2)";
                $params[':f1'] = $p;
                $params[':f2'] = $p;
            }

            // Ordenamiento vital para que el ciclo while de la vista agrupe por módulo
            $sentencia .= " ORDER BY m.id_modulo ASC, p.id_permiso ASC";
            
            $stmt = $conex->prepare($sentencia);
            $stmt->execute($params);
            $datos = $stmt->fetchAll();

            return array('accion' => 'consultar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Permisos', $e->getMessage(), 'Modelo_Consultar');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    public function Buscar(): array
    {
        $conex = null;
        try {
            $conex = $this->conexSG();
            $sentencia = "SELECT * FROM permisos WHERE id_permiso = :id";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            return array('accion' => 'buscar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Permisos', $e->getMessage(), 'Modelo_Buscar');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    private function Incluir(): array
    {
        $conex = null;
        try {
            $conex = $this->conexSG();
            $conex->beginTransaction();

            // Verificar si el módulo padre existe y está activo
            if (!$this->verificarExistencia('modulo', $this->modulo, 'modulos', 1, 'sg', bloquear: true)) {
                throw new Exception('El módulo asociado no existe o está inactivo.');
            }

            if ($this->verificarExistencia('clave', $this->clave, 'permisos', 1, 'sg', bloquear: true)) {
                throw new Exception('La clave técnica del permiso ya está registrada.');
            }
            if ($this->verificarExistencia('nombre', $this->nombre, 'permisos', 1, 'sg', bloquear: true)) {
                throw new Exception('El nombre del permiso ya está registrado.');
            }

            // Construcción dinámica del INSERT usando implode
            $columnas = ['id_modulo', 'nombre', 'clave'];
            $valores  = [':id_modulo', ':nombre', ':clave'];
            $params = [
                ':id_modulo' => $this->modulo,
                ':nombre'    => $this->nombre,
                ':clave'     => $this->clave,
            ];

            // Si viene descripción, la agregamos dinámicamente a los arreglos
            if (!empty($this->descripcion)) {
                $columnas[] = 'descripcion';
                $valores[]  = ':descripcion';
                $params[':descripcion'] = $this->descripcion;
            }

            $strColumnas = implode(', ', $columnas);
            $strValores  = implode(', ', $valores);

            $sentencia = "INSERT INTO `permisos` ($strColumnas) VALUES ($strValores)";
            $stmt = $conex->prepare($sentencia);
            $stmt->execute($params);

            $conex->commit();
            return array('accion' => 'exito');
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollback();
            }
            logs('Permisos', $e->getMessage(), 'Modelo_Incluir');
            return array('accion' => 'error', 'codigo' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    private function Modificar(): array
    {
        $conex = null;
        try {
            $conex = $this->conexSG();
            $conex->beginTransaction();

            // Verificar si el permiso existe
            if (!$this->verificarExistencia('id', $this->id, 'permisos', 1, 'sg', bloquear: true)) {
                throw new Exception('El permiso seleccionado no existe.');
            }

            // Validar que el nombre modificado no choque con el de otro permiso existente
            if (!$this->verificarExistenciaPropia('nombre', $this->nombre, $this->id, 'permisos', 1, 'sg', bloquear: true)) {
                if ($this->verificarExistencia('nombre', $this->nombre, 'permisos', 1, 'sg', bloquear: true)) {
                    throw new Exception('Ya existe otro permiso con ese nombre.');
                }
            }

            $sentencia = "UPDATE `permisos` 
                          SET `nombre` = :nombre,
                              `descripcion` = :descripcion
                          WHERE `id_permiso` = :id";
            
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':descripcion', $this->descripcion);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();

            $conex->commit();
            return array('accion' => 'exito');
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollback();
            }
            logs('Permisos', $e->getMessage(), 'Modelo_Modificar');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    private function Bloqueo(): array
    {
        $conex = null;
        try {
            $conex = $this->conexSG();
            $conex->beginTransaction();

            // Calculamos el nuevo estado basado en el valor que trae el atributo
            $nuevoEstado = ($this->bloqueo == 1) ? 2 : 1;

            $sql = "UPDATE `permisos` SET `estatus` = :estado WHERE id_permiso = :id";
            $stmt = $conex->prepare($sql);
            
            $stmt->execute([
                ':estado' => $nuevoEstado,
                ':id'     => $this->id
            ]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('No se encontró el permiso o el estatus ya era el solicitado.');
            }

            $conex->commit();
            return ['accion' => 'exito'];
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            logs('Permisos', $e->getMessage(), 'Modelo_Bloqueo');
            return ['accion' => 'error', 'mensaje' => $e->getMessage()];
        } finally {
            $conex = null;
        }
    }
}