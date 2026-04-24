<?php

namespace App\modelo;

use App\modelo\ModeloBase;
use Exception;
use SensitiveParameter;

class ModeloCategorias extends ModeloBase
{
    private $id;
    private $nombre;
    private $edad_minima;
    private $edad_maxima;

    public function __construct()
    {
        parent::__construct();
        $this->campoWhitelist = [
            'id' => 'id_categorias',
            'nombre' => 'nombre'
        ];
        $this->llavePrimaria = 'id_categorias';
    }

    public function ProcesarDatos(array $datos): array
    {
        if (empty($datos)) {
            throw new Exception('No se proporcionaron datos para procesar.');
        }
        $this->id = $datos['id'] ?? null;
        $this->nombre = mb_strtoupper(trim($datos['nombre'] ?? ''), "UTF-8");
        $this->edad_minima = $datos['edad_minima'] ?? null;
        $this->edad_maxima = $datos['edad_maxima'] ?? null;
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
            $sentencia = "SELECT * FROM categorias WHERE 1=1";

            // 2. BUSCADOR GENERAL (Por nombre de categoría)
            if (!empty($filtro['filtro'])) {
                $p = "%" . $filtro['filtro'] . "%";
                $sentencia .= " AND (
                nombre LIKE :f1
                ";
                $params[':f1'] = $p;
            }

            // 3. FILTROS ESPECÍFICOS (Si vienen del Modal o propiedades del objeto)
            if (!empty($this->nombre)) {
                $sentencia .= " AND nombre LIKE :nombre";
                $params[':nombre'] = trim($this->nombre) . "%";
            }

            // 4. Orden (Asegúrate de usar una columna que exista, como id_representante)
            $sentencia .= " ORDER BY id_categorias ASC";

            $stmt = $conex->prepare($sentencia);

            // IMPORTANTE: Pasar los parámetros al execute
            $stmt->execute($params);

            $datos = $stmt->fetchAll();

            return array('accion' => 'consultar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Categorias', $e->getMessage(), 'Modelo_Consultar');
            return array('accion' => 'error', 'mensaje' => 'Error al listar: ' . $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    private function Incluir(): array
    {
        try {
            if ($this->verificarExistencia('nombre', $this->nombre, 'categorias', NULL)) {
                return array('accion' => 'error', 'mensaje' => 'Ya existe una categoria registrada con este nombre.');
            }

            $conex = $this->conex();
            $sentencia = "INSERT INTO categorias (`nombre`, `edad_min`, `edad_max`) VALUES (:nombre, :edad_min, :edad_max)";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':edad_min', $this->edad_minima);
            $stmt->bindParam(':edad_max', $this->edad_maxima);
            $stmt->execute();

            return array('accion' => 'incluir', 'mensaje' => 'Categoria registrada exitosamente.');
        } catch (Exception $e) {
            logs('Categorias', $e->getMessage(), 'Modelo');
            return array('accion' => 'error', 'mensaje' => 'Error al incluir: ' . $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }                                                      

    private function Modificar(): array
    {
        try {
            if (!$this->verificarExistenciaPropia('nombre', $this->nombre, $this->id, 'representantes', NULL)) {
                if ($this->verificarExistencia('nombre', $this->nombre, 'categorias', NULL)) {
                    return array('accion' => 'error', 'mensaje' => 'Ya existe otra categoria registrada con este nombre.');
                }
            }
            $conex = $this->conex();
            $sentencia = "UPDATE categorias SET 
            nombre = :nombre, 
            edad_min = :edad_min, 
            edad_max = :edad_max 
            WHERE id_categorias = :id_categorias";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':edad_min', $this->edad_minima);
            $stmt->bindParam(':edad_max', $this->edad_maxima);
            $stmt->bindParam(':id_categorias', $this->id);
            $stmt->execute();

            return array('accion' => 'modificar', 'mensaje' => 'Categoria modificada exitosamente.');
        } catch (Exception $e) {
            logs('Categorias', $e->getMessage(), 'Modelo');
            return array('accion' => 'error', 'mensaje' => 'Error al modificar: ' . $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    function Buscar(): array
    {
        try {
            $conex = $this->conex();
            $sentencia = "SELECT * FROM categorias WHERE id_categorias = :id";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            return array('accion' => 'buscar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Categorias', $e->getMessage(), 'Modelo');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }
private function Eliminar(): array
    {
        try {
            // CORRECCIÓN 1: Volvemos a usar 'id' genérico como lo espera tu ModeloBase
            if (!$this->verificarExistencia('id', $this->id, 'categorias', NULL)) {
                return array('accion' => 'error', 'mensaje' => 'La categoría no existe.');
            }
            
            // CORRECCIÓN 2: Aquí también usamos 'id' (Asegúrate de que la tabla 'atletas' exista)
            if ($this->verificarExistencia('id', $this->id, 'atletas', NULL)) {
                return array('accion' => 'error', 'mensaje' => 'No se puede eliminar: la categoría tiene atletas asociados.');
            }

            $conex = $this->conex();
            $sentencia = "DELETE FROM categorias WHERE id_categorias = :id";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
            
            return array('accion' => 'eliminar', 'mensaje' => 'Categoría eliminada exitosamente.');
        } catch (Exception $e) {
            logs('Categorias', $e->getMessage(), 'Modelo');
            return array('accion' => 'error', 'mensaje' => 'Hubo un error al eliminar la categoría.');
        } finally {
            $conex = NULL;
        }
    }   
} 