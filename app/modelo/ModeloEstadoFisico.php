<?php

namespace App\modelo;

use Exception;

class ModeloEstadoFisico extends Conexion
{
    private $id_estado;
    private $nombre;
    private $nivel_estado;

    public function __construct()
    {
        parent::__construct();
        $this->campoWhitelist = [
            'id_estado' => 'id_estado',
            'nombre' => 'nombre',
            'nivel_estado' => 'nivel_estado'
        ];
        $this->llavePrimaria = 'id_estado';
    }

    public function ProcesarDatos(array $datos): array
    {
        if (empty($datos)) {
            throw new Exception('No se proporcionaron datos para procesar.');
        }
        
        $this->id_estado = $datos['id_estado'] ?? null;
        $this->nombre = mb_convert_case(trim($datos['nombre'] ?? ''), MB_CASE_TITLE, "UTF-8");
        $this->nivel_estado = $datos['nivel_estado'] ?? null;
        
        $accion = $datos['accion'] ?? null;
        
        return match ($accion) {
            'buscar' => $this->Buscar(),
            'incluir' => $this->Incluir(),
            'modificar' => $this->Modificar(),
            'eliminar' => $this->Eliminar(),
            default => throw new Exception('La accion no es valida')
        };
    }

    public function Consultar(array $filtro = []): array
    {
        try {
            $conex = $this->conex();
            $params = [];

            // 1. Sentencia base adaptada a la tabla estado_fisico
            $sentencia = "SELECT * FROM estado_fisico WHERE 1=1";

            // 2. BUSCADOR GENERAL
            if (!empty($filtro['filtro'])) {
                $p = "%" . $filtro['filtro'] . "%";
                $sentencia .= " AND (nombre LIKE :f1 OR nivel_estado LIKE :f2)";
                $params[':f1'] = $p;
                $params[':f2'] = $p;
            }

            if (!empty($this->nombre)) {
                $sentencia .= " AND nombre LIKE :nombre";
                $params[':nombre'] = trim($this->nombre) . "%";
            }

            if (!empty($this->nivel_estado)) {
                $sentencia .= " AND nivel_estado = :nivel";
                $params[':nivel'] = $this->nivel_estado;
            }

            $sentencia .= " ORDER BY nombre ASC";

            $stmt = $conex->prepare($sentencia);
            $stmt->execute($params);

            $datos = $stmt->fetchAll();

            return array('accion' => 'consultar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('EstadoFisico', $e->getMessage(), 'Modelo_Consultar');
            return array('accion' => 'error', 'mensaje' => 'Error al listar: ' . $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    function Buscar(): array
    {
        try {
            $conex = $this->conex();
            $sentencia = "SELECT * FROM estado_fisico WHERE id_estado = :id_estado";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id_estado', $this->id_estado);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            return array('accion' => 'buscar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('EstadoFisico', $e->getMessage(), 'Modelo_Buscar');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    private function Incluir(): array
    {
        try {
            if ($this->verificarExistencia('nombre', $this->nombre, 'estado_fisico', NULL)) {
                return array('accion' => 'error', 'mensaje' => 'Ya existe un estado físico registrado con este nombre.');
            }

            $conex = $this->conex();
            $sentencia = "INSERT INTO estado_fisico (`nombre`, `nivel_estado`) VALUES (:nombre, :nivel_estado)";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':nivel_estado', $this->nivel_estado);
            $stmt->execute();

            return array('accion' => 'incluir', 'mensaje' => 'Estado físico registrado exitosamente.');
        } catch (Exception $e) {
            logs('EstadoFisico', $e->getMessage(), 'Modelo_Incluir');
            return array('accion' => 'error', 'mensaje' => 'Error al incluir: ' . $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    private function Modificar(): array
    {
        try {
            if (!$this->verificarExistenciaPropia('nombre', $this->nombre, $this->id_estado, 'estado_fisico', NULL)) {
                if ($this->verificarExistencia('nombre', $this->nombre, 'estado_fisico', NULL)) {
                    return array('accion' => 'error', 'mensaje' => 'Ya existe otro estado físico registrado con este nombre.');
                }
            }
            $conex = $this->conex();
            $sentencia = "UPDATE estado_fisico SET 
            nombre = :nombre, 
            nivel_estado = :nivel_estado
            WHERE id_estado = :id_estado";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':nivel_estado', $this->nivel_estado);
            $stmt->bindParam(':id_estado', $this->id_estado);
            $stmt->execute();

            return array('accion' => 'modificar', 'mensaje' => 'Estado físico modificado exitosamente.');
        } catch (Exception $e) {
            logs('EstadoFisico', $e->getMessage(), 'Modelo_Modificar');
            return array('accion' => 'error', 'mensaje' => 'Error al modificar: ' . $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    private function Eliminar(): array
    {
        try {
            if (!$this->verificarExistencia('id_estado', $this->id_estado, 'estado_fisico', NULL)) {
                return array('accion' => 'error', 'mensaje' => 'El estado físico no existe.');
            }

            // Validación de llaves foráneas antes de eliminar
            if ($this->verificarExistencia('id_estado', $this->id_estado, 'articulos_inventario', NULL)) {
                return array('accion' => 'error', 'mensaje' => 'No se puede eliminar: existen artículos de inventario asociados a este estado.');
            }
            if ($this->verificarExistencia('id_estado', $this->id_estado, 'devoluciones', NULL)) {
                return array('accion' => 'error', 'mensaje' => 'No se puede eliminar: existen devoluciones asociadas a este estado.');
            }

            $conex = $this->conex();
            $sentencia = "DELETE FROM estado_fisico WHERE id_estado = :id_estado";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id_estado', $this->id_estado);
            $stmt->execute();

            return array('accion' => 'eliminar', 'mensaje' => 'Estado físico eliminado exitosamente.');
        } catch (Exception $e) {
            logs('EstadoFisico', $e->getMessage(), 'Modelo_Eliminar');
            return array('accion' => 'error', 'mensaje' => 'Hubo un error al eliminar el estado físico.');
        } finally {
            $conex = NULL;
        }
    }
}