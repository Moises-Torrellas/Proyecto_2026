<?php

namespace App\modelo;

use Exception;

class ModeloCategoriaEquipamiento extends Conexion
{
    private $id;
    private $nombre;
    private $descripcion;

    public function __construct()
    {
        parent::__construct();
        $this->campoWhitelist = [
            'id' => 'id_categoria',
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
        $this->id = $datos['id'] ?? null;
        $this->nombre = mb_strtoupper(trim($datos['nombre'] ?? ''), "UTF-8");
        $this->descripcion = $datos['descripcion'] ?? null;
           $accion = $datos['accion'] ?? null;
        return match ($accion) {
            'incluir'   => $this->Incluir(),
            'eliminar'  => $this->Eliminar(),
            'buscar' => $this->Buscar(),
            'modificar' => $this->Modificar(),
            default => throw new Exception('La accion no es valida')
        };
    }

    public function Consultar(array $filtro = []): array
    {
        try {
            $conex = $this->conex();
            $params = []; // Unificamos el nombre de la variable

            // 1. Iniciamos la sentencia con WHERE 1=1 para concatenar AND tranquilamente
            $sentencia = "SELECT * FROM categoria_catalogo WHERE 1=1";

            // 2. BUSCADOR GENERAL (Por nombre de categoría)
            if (!empty($filtro['filtro'])) {
                $p = "%" . $filtro['filtro'] . "%";
                $sentencia .= " AND 
                nombre LIKE :f1
                ";
                $params[':f1'] = $p;
            }

            // 3. FILTROS ESPECÍFICOS (Si vienen del Modal o propiedades del objeto)
            if (!empty($this->nombre)) {
                $sentencia .= " AND nombre LIKE :nombre";
                $params[':nombre'] = trim($this->nombre) . "%";
            }

            // 4. Orden (Asegúrate de usar una columna que exista, como id_categorias)
            $sentencia .= " ORDER BY id_categoria ASC";

            $stmt = $conex->prepare($sentencia);

            // IMPORTANTE: Pasar los parámetros al execute
            $stmt->execute($params);

            $datos = $stmt->fetchAll();

            return array('accion' => 'consultar', 'datos' => $datos);
        } catch (Exception $e) {
        logs('CategoriaEquipamiento', $e->getMessage(), 'Modelo_Consultar');
            return array('accion' => 'error', 'mensaje' => 'Error al listar: ' . $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    private function Incluir(): array
    {
        try {
            if ($this->verificarExistencia('nombre', $this->nombre, 'categoria_catalogo', NULL)) {
                return array('accion' => 'error', 'mensaje' => 'Ya existe una categoria equipamiento registrada con este nombre.');
            }

            $conex = $this->conex();
            $sentencia = "INSERT INTO categoria_catalogo (`nombre`, `descripcion`) VALUES (:nombre, :descripcion)";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':descripcion', $this->descripcion);
            $stmt->execute();

            return array('accion' => 'incluir', 'mensaje' => 'Categoria equipamiento registrada exitosamente.');
        } catch (Exception $e) {
            logs('CategoriaEquipamiento', $e->getMessage(), 'Modelo');
            return array('accion' => 'error', 'mensaje' => 'Error al incluir: ' . $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }                                                      

    private function Modificar(): array
    {
        try {
           if (!$this->verificarExistenciaPropia('nombre', $this->nombre, $this->id, 'categoria_catalogo', NULL)) {
                if ($this->verificarExistencia('nombre', $this->nombre, 'categoria_catalogo', NULL)) {
                    return array('accion' => 'error', 'mensaje' => 'Ya existe otra categoria registrada con este nombre.');
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
            $stmt->bindParam(':id_categoria', $this->id);
            $stmt->execute();

            return array('accion' => 'modificar', 'mensaje' => 'Categoria equipamiento modificada exitosamente.');
        } catch (Exception $e) {
            logs('CategoriaEquipamiento', $e->getMessage(), 'Modelo');
            return array('accion' => 'error', 'mensaje' => 'Error al modificar: ' . $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    function Buscar(): array
    {
        try {
            $conex = $this->conex();
            $sentencia = "SELECT * FROM categoria_catalogo WHERE id_categoria = :id";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            return array('accion' => 'buscar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('CategoriaEquipamiento', $e->getMessage(), 'Modelo');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }
private function Eliminar(): array
    {
        try {
            // CORRECCIÓN 1: Volvemos a usar 'id' genérico como lo espera tu ModeloBase
            if (!$this->verificarExistencia('id', $this->id, 'categoria_catalogo', NULL)) {
                return array('accion' => 'error', 'mensaje' => 'La categoría equipamiento no existe.');
            }
            
            // CORRECCIÓN 2: Aquí también usamos 'id' (Asegúrate de que la tabla 'atletas' exista)
            if ($this->verificarExistencia('id', $this->id, 'catalogos', NULL)) {
                return array('accion' => 'error', 'mensaje' => 'No se puede eliminar: la categoría tiene catalogos asociados.');
            }

            $conex = $this->conex();
            $sentencia = "DELETE FROM categoria_catalogo WHERE id_categoria = :id";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
            
            return array('accion' => 'eliminar', 'mensaje' => 'Categoría equipamiento eliminada exitosamente.');
        } catch (Exception $e) {
            logs('CategoriaEquipamiento', $e->getMessage(), 'Modelo');
            return array('accion' => 'error', 'mensaje' => 'Hubo un error al eliminar la categoría equipamiento.');
        } finally {
            $conex = NULL;
        }
    }
    
    private function ValidarExpresiones(array $datos): void
    {
        if (!empty($datos['id']) && !preg_match('/^[0-9]+$/', $datos['id'])) {
            throw new Exception('Id inválido.');
        }
        if (!empty($datos['nombre']) && !preg_match('/^[a-zA-Z0-9\-\s]{2,30}$/', $datos['nombre'])) {
            throw new Exception('Nombre de categoría equipamiento inválido.');
        }
        if (!empty($datos['descripcion']) && !preg_match('/^[a-zA-Z0-9\-\s]{2,30}$/', $datos['descripcion'])) {
            throw new Exception('Descripcion inválida.');
        }
    }
} 
