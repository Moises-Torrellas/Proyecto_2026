<?php

namespace App\modelo;

use App\modelo\ModeladoBase;
use Exception;
use SensitiveParameter;

class ModeloPosiciones extends ModeloBase
{
    private $id;
    private $nombre;
    private $abreviatura;
    private $descripcion;
    public function __construct()
    {
        parent::__construct();
        $this->campoWhitelist = [
            'nombre' => 'nombre',
            'abreviatura' => 'abreviatura',
            'id' => 'id_posicion'
        ];
        $this->llavePrimaria = 'id_posicion';
    }
    public function ProcesarDatos(array $datos): array
    {
        if (empty($datos)) {
            throw new Exception('No se proporcionaron datos para procesar.');
        }

        $this->id = $datos['id'] ?? null;
        $this->nombre = mb_convert_case(trim($datos['nombre'] ?? ''), MB_CASE_TITLE, "UTF-8");
        $this->abreviatura = mb_convert_case(trim($datos['abreviatura'] ?? ''), MB_CASE_UPPER, "UTF-8");
        $this->descripcion = mb_convert_case(trim($datos['descripcion'] ?? ''), MB_CASE_TITLE, "UTF-8");
        $accion = $datos['accion'] ?? null;
        return match ($accion) {
            'buscar' => $this->Buscar(),
            'incluir' => $this->Incluir(),
            'eliminar' => $this->Eliminar(),
            'modificar' => $this->Modificar(),
            default => throw new Exception('La accion no es valida')
        };
    }

    public function Consultar(array $filtro = [])
    {
        try {
            $conex = $this->conex();
            $params = [];
            $sentencia = "SELECT * FROM posiciones WHERE 1=1";

            if (!empty($filtro['filtro'])) {
                $p = "%" . $filtro['filtro'] . "%";
                $sentencia .= " AND (
                nombre LIKE :f1 OR 
                abreviatura LIKE :f2
            )";
                $params[':f1'] = $p;
                $params[':f2'] = $p;
            }

            $stmt = $conex->prepare($sentencia);
            $stmt->execute($params);
            $datos = $stmt->fetchAll();
            return array('accion' => 'consultar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Posiciones', $e->getMessage(), 'Modelo_Consultar');
            return array('accion' => 'error', 'mensaje' => 'Error al listar las posiciones.');
        }
    }

    private function Buscar(): array
    {
        try {
            $conex = $this->conex();
            $sentencia = "SELECT * FROM posiciones WHERE id_posicion = :id";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            return array('accion' => 'buscar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Posiciones', $e->getMessage(), 'Modelo_Buscar');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    private function Incluir() :array
    {
        try {
            if ($this->verificarExistencia('nombre', $this->nombre, 'posiciones', NULL)) {
                return array('accion' => 'error', 'mensaje' => 'Ya existe una posición registrada con este nombre.');
            }
            if ($this->verificarExistencia('abreviatura', $this->abreviatura, 'posiciones', NULL)) {
                return array('accion' => 'error', 'mensaje' => 'Ya existe una posición registrada con esta abreviatura.');
            }
            $conex = $this->conex();
            $sentencia = "INSERT INTO posiciones (nombre, abreviatura, descripcion) VALUES (:n, :a, :d)";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':n', $this->nombre);
            $stmt->bindParam(':a', $this->abreviatura);
            $stmt->bindParam(':d', $this->descripcion);
            $stmt->execute();
            return array('accion' => 'incluir', 'mensaje' => 'Posicion registrada exitosamente.');
        } catch (Exception $e) {
            logs('Posiciones', $e->getMessage(), 'Modelo_Buscar');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    private function Eliminar(): array
    {
        try {
            if (!$this->verificarExistencia('id', $this->id, 'posiciones', NULL)) {
                return array('accion' => 'error', 'mensaje' => 'La posicion no existe.');
            }
            if ($this->verificarExistencia('id', $this->id, 'atletas', NULL)) {
                return array('accion' => 'error', 'mensaje' => 'La posicion tiene atletas asociados.');
            }
            $conex = $this->conex();
            $sentencia = "DELETE FROM posiciones WHERE id_posicion = :id";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
            return array('accion' => 'eliminar', 'mensaje' => 'Posición eliminada exitosamente.');
        } catch (Exception $e) {
            logs('Posiciones', $e->getMessage(), 'Modelo_Eliminar');
            return array('accion' => 'error', 'mensaje' => 'Hubo un error al eliminar la posición.');
        } finally {
            $conex = NULL;
        }
    }

    private function Modificar() : array
    {
        try {
            if(!$this->verificarExistenciaPropia('nombre', $this->nombre, $this->id, 'posiciones', NULL)){
                if($this->verificarExistencia('nombre', $this->nombre, 'posiciones', NULL)){
                    return array('accion' => 'error', 'mensaje' => 'Ya existe una posición registrada con este nombre.');
                }
            }
            if(!$this->verificarExistenciaPropia('abreviatura', $this->abreviatura, $this->id, 'posiciones', NULL)){
                if($this->verificarExistencia('abreviatura', $this->abreviatura, 'posiciones', NULL)){
                    return array('accion' => 'error', 'mensaje' => 'Ya existe una posición registrada con esta abreviatura.');
                }
            }

            $conex = $this->conex();
            $sentencia = "UPDATE posiciones SET nombre = :n, abreviatura = :a, descripcion = :d WHERE id_posicion = :id";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':n', $this->nombre);
            $stmt->bindParam(':a', $this->abreviatura);
            $stmt->bindParam(':d', $this->descripcion);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
            return array('accion' => 'modificar', 'mensaje' => 'Posicion modificada exitosamente.');
        } catch (Exception $e) {
            logs('Posiciones', $e->getMessage(), 'Modelo_Modificar');
            return array('accion' => 'error', 'mensaje' => 'Hubo un error al modificar la posición.');
        } finally {
            $conex = NULL;
        }
    }
}
