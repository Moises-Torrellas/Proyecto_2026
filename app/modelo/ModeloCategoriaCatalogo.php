<?php

namespace App\modelo;

use Exception;

class ModeloCategoriaCatalogo extends Conexion
{
    private $id_categoria; // Ajustado a id_categoria
    private $nombre;
    private $descripcion;

    public function __construct()
    {
        parent::__construct();
        $this->campoWhitelist = [
            'id_categoria' => 'id_categoria', // Ajustado a id_categoria
            'nombre' => 'nombre'
        ];
        $this->llavePrimaria = 'id_categoria';
    }

    public function ProcesarDatos(array $datos): array
    {
        if (empty($datos)) {
            throw new Exception('No se proporcionaron datos para procesar.');
        }
        $this->ValidarExpresiones($datos);
        
        $this->id_categoria = $datos['id_categoria'] ?? null; // Ajustado
        $this->nombre = mb_convert_case(trim($datos['nombre'] ?? ''), MB_CASE_TITLE, "UTF-8");
        $this->descripcion = $datos['descripcion'] ?? null;
        
        $accion = $datos['accion'] ?? null;
        
        return match ($accion) {
            'incluir'   => $this->Incluir(),
            'eliminar'  => $this->Eliminar(),
            'buscar'    => $this->Buscar(),
            'modificar' => $this->Modificar(),
            default => throw new Exception('La accion no es valida')
        };
    }

    public function Consultar(array $filtro = []): array
    {
        try {
            $conex = $this->conex();
            $params = []; 

            $sentencia = "SELECT * FROM categoria_catalogo WHERE 1=1";

            if (!empty($filtro['filtro'])) {
                $p = "%" . $filtro['filtro'] . "%";
                $sentencia .= " AND nombre LIKE :f1";
                $params[':f1'] = $p;
            }

            if (!empty($this->nombre)) {
                $sentencia .= " AND nombre LIKE :nombre";
                $params[':nombre'] = trim($this->nombre) . "%";
            }

            $sentencia .= " ORDER BY id_categoria ASC";

            $stmt = $conex->prepare($sentencia);
            $stmt->execute($params);
            $datos = $stmt->fetchAll();

            return array('accion' => 'consultar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('CategoriaCatalogo', $e->getMessage(), 'Modelo_Consultar');
            return array('accion' => 'error', 'mensaje' => 'Error al listar: ' . $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    private function Incluir(): array
    {
        try {
            if ($this->verificarExistencia('nombre', $this->nombre, 'categoria_catalogo', NULL)) {
                return array('accion' => 'error', 'mensaje' => 'Ya existe una categoría registrada con este nombre.');
            }

            $conex = $this->conex();
            $sentencia = "INSERT INTO categoria_catalogo (`nombre`, `descripcion`) VALUES (:nombre, :descripcion)";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':descripcion', $this->descripcion);
            $stmt->execute();

            return array('accion' => 'incluir', 'mensaje' => 'Categoría registrada exitosamente.');
        } catch (Exception $e) {
            logs('CategoriaCatalogo', $e->getMessage(), 'Modelo_Incluir');
            return array('accion' => 'error', 'mensaje' => 'Error al incluir: ' . $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }                                              

    private function Modificar(): array
    {
        try {
            // Ajustado a id_categoria
            if (!$this->verificarExistenciaPropia('nombre', $this->nombre, $this->id_categoria, 'categoria_catalogo', NULL)) {
                if ($this->verificarExistencia('nombre', $this->nombre, 'categoria_catalogo', NULL)) {
                    return array('accion' => 'error', 'mensaje' => 'Ya existe otra categoría registrada con este nombre.');
                }
            }
            $conex = $this->conex();
            $sentencia = "UPDATE categoria_catalogo SET 
            nombre = :nombre, 
            descripcion = :descripcion
            WHERE id_categoria = :id_categoria";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':descripcion', $this->descripcion);
            $stmt->bindParam(':id_categoria', $this->id_categoria); // Ajustado
            $stmt->execute();

            return array('accion' => 'modificar', 'mensaje' => 'Categoría modificada exitosamente.');
        } catch (Exception $e) {
            logs('CategoriaCatalogo', $e->getMessage(), 'Modelo_Modificar');
            return array('accion' => 'error', 'mensaje' => 'Error al modificar: ' . $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    function Buscar(): array
    {
        try {
            $conex = $this->conex();
            $sentencia = "SELECT * FROM categoria_catalogo WHERE id_categoria = :id_categoria"; // Ajustado
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id_categoria', $this->id_categoria); // Ajustado
            $stmt->execute();
            $datos = $stmt->fetchAll();
            return array('accion' => 'buscar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('CategoriaCatalogo', $e->getMessage(), 'Modelo_Buscar');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    private function Eliminar(): array
    {
        try {
            // CORRECCIÓN 1: Ajustado a id_categoria
            if (!$this->verificarExistencia('id_categoria', $this->id_categoria, 'categoria_catalogo', NULL)) {
                return array('accion' => 'error', 'mensaje' => 'La categoría no existe.');
            }
            
            // CORRECCIÓN 2: Ajustado a la tabla catalogo según el diagrama
            if ($this->verificarExistencia('id_categoria', $this->id_categoria, 'catalogo', NULL)) {
                return array('accion' => 'error', 'mensaje' => 'No se puede eliminar: la categoría tiene artículos del catálogo asociados.');
            }

            $conex = $this->conex();
            $sentencia = "DELETE FROM categoria_catalogo WHERE id_categoria = :id_categoria"; // Ajustado
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id_categoria', $this->id_categoria); // Ajustado
            $stmt->execute();
            
            return array('accion' => 'eliminar', 'mensaje' => 'Categoría eliminada exitosamente.');
        } catch (Exception $e) {
            logs('CategoriaCatalogo', $e->getMessage(), 'Modelo_Eliminar');
            return array('accion' => 'error', 'mensaje' => 'Hubo un error al eliminar la categoría.');
        } finally {
            $conex = NULL;
        }
    }
    
    private function ValidarExpresiones(array $datos): void
    {
        // Ajustado a id_categoria
        if (!empty($datos['id_categoria']) && !preg_match('/^[0-9]+$/', $datos['id_categoria'])) {
            throw new Exception('Id inválido.');
        }
        if (!empty($datos['nombre']) && !preg_match('/^[a-zA-Z0-9\-\s]{2,30}$/', $datos['nombre'])) {
            throw new Exception('Nombre de categoría inválido.');
        }
        if (!empty($datos['descripcion']) && !preg_match('/^[a-zA-Z0-9\-\s]{2,30}$/', $datos['descripcion'])) {
            throw new Exception('Descripción inválida.');
        }
    }
}