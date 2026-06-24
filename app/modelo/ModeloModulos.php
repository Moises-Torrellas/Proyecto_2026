<?php

namespace App\modelo;

use Exception;

class ModeloModulos extends Conexion
{
    private $id;
    private $nombre;
    private $descripcion;
    public function __construct()
    {
        parent::__construct();

        $this->campoWhitelist = [
            'nombre' => 'nombre_modulo',
            'id' => 'id_modulo'
        ];

        $this->llavePrimaria = 'id_modulo';
    }
    private function ValidarExpresiones(array $datos): void
    {
        if (!empty($datos['id']) && !preg_match('/^[0-9]+$/', $datos['id'])) {
            throw new Exception('Id inválido.');
        }
        if (!empty($datos['nombre']) && !preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,50}$/', $datos['nombre'])) {
            throw new Exception('Nombre inválido.');
        }
        if (!empty($datos['descripcion']) && !preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,100}$/', $datos['descripcion'])) {
            throw new Exception('Dirección inválida.');
        }
    }

    public function ProcesarDatos(array $datos): array
    {

        if (empty($datos)) {
            throw new Exception('No se proporcionaron datos para procesar.');
        }

        $this->ValidarExpresiones($datos);
        $this->id = $datos['id'] ?? null;
        $this->nombre = mb_convert_case(trim($datos['nombre'] ?? ''), MB_CASE_TITLE, "UTF-8");
        $this->descripcion = mb_convert_case(trim($datos['descripcion'] ?? ''), MB_CASE_TITLE, "UTF-8");

        $accion = $datos['accion'] ?? null;
        return match ($accion) {
            'buscar' => $this->Buscar(),
            'modificar' => $this->Modificar(),
            default => throw new Exception('La accion no es valida')
        };
    }

    public function Consultar(array $filtro = []): array
    {
        $conex=null;
        try {
            $conex = $this->conexSG();
            $params = [];


            $sentencia = "SELECT * FROM modulos WHERE 1=1";


            if (!empty($filtro['filtro'])) {
                $p = "%" . $filtro['filtro'] . "%";
                $sentencia .= " AND (
                nombre_modulo LIKE :f1 
            )";
                $params[':f1'] = $p;
            }


            $sentencia .= " ORDER BY id_modulo ASC";

            $stmt = $conex->prepare($sentencia);


            $stmt->execute($params);

            $datos = $stmt->fetchAll();

            return array('accion' => 'consultar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Modulos', $e->getMessage(), 'Modelo_Consultar');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    function Buscar(): array
    {
        $conex=null;
        try {
            $conex = $this->conexSG();
            $sentencia = "SELECT * FROM modulos WHERE id_modulo = :id";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            return array('accion' => 'buscar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Modulos', $e->getMessage(), 'Modelo');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    private function Modificar(): array
    {
        $conex=null;
        try {
            $conex = $this->conexSG();
            $conex->beginTransaction();

            if (!$this->verificarExistencia('id', $this->id, 'modulos', 1, 'sg', bloquear: true)) {
                    throw new Exception(INVALID_ID);
            }

            if (!$this->verificarExistenciaPropia('nombre', $this->nombre, $this->id, 'modulos', 1, 'sg', bloquear: true)) {
                if ($this->verificarExistencia('nombre', $this->nombre, 'modulos', 1, 'sg', bloquear: true)) {
                    throw new Exception(DUPLICATE_NAME);
                }
            }
            $sentencia = "UPDATE `modulos` 
            SET `nombre_modulo`= :nombre,
            `descripcion`= :descripcion
            WHERE id_modulo = :id";
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
            logs('Modulos', $e->getMessage(), 'Modelo_Modificar');
            return array('accion' => 'error', 'codigo' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }


}
