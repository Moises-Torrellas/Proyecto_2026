<?php

namespace App\modelo;

use Exception;

class ModeloCategorias extends Conexion
{
    private $id;
    private $nombre;
    private $edad_min;
    private $edad_max;


    public function __construct()
    {
        parent::__construct();
        // Definimos el diccionario de campos para las validaciones del ModeloBase
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

        $this->ValidarExpresiones($datos);

        $this->id = $datos['id'] ?? null;
        $this->nombre = mb_strtoupper(trim($datos['nombre'] ?? ''), "UTF-8");
        $this->edad_min = $datos['edad_minima'] ?? null;
        $this->edad_max = $datos['edad_maxima'] ?? null;

        $accion = $datos['accion'] ?? null;

        return match ($accion) {
            'incluir'   => $this->Incluir(),
            'eliminar'  => $this->Eliminar(),
            'buscar'    => $this->Buscar(),
            'modificar' => $this->Modificar(),
            'consultar' => $this->Consultar(),
            default => throw new Exception('La acción no es válida')
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

            // 4. Orden (Asegúrate de usar una columna que exista, como id_categorias)
            $sentencia .= " ORDER BY id_categorias ASC";

            $stmt = $conex->prepare($sentencia);
            $stmt->execute($params);
            $datos = $stmt->fetchAll();

            return array('accion' => 'consultar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Categorias', $e->getMessage(), 'Modelo_Consultar');
            return array('accion' => 'error');
        } finally {
            $conex = NULL;
        }
    }

    private function Incluir(): array
    {
        try {
            $conex = $this->conex();

            if ($this->verificarExistencia('nombre', $this->nombre, 'categorias', NULL)) {
                throw new Exception('Ya existe una categoría registrada con este nombre.');
            }

            $sentencia = "INSERT INTO categorias (`nombre`, `edad_min`, `edad_max`) VALUES (:nombre, :edad_min, :edad_max)";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':edad_min', $this->edad_min);
            $stmt->bindParam(':edad_max', $this->edad_max);
            $stmt->execute();

            return array('accion' => 'exito');
        } catch (Exception $e) {
            logs('Categorias', $e->getMessage(), 'Modelo');
            return array('accion' => 'error', 'codigo' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    private function Modificar(): array
    {
        $conex = NULL;
        try {
            $conex = $this->conex(); // <-- CORRECCIÓN 1: Se inicializa la conexión

            // <-- CORRECCIÓN 2: Se cambia 'representantes' por 'categorias'
            if (!$this->verificarExistenciaPropia('nombre', $this->nombre, $this->id, 'categorias', NULL)) {
                if ($this->verificarExistencia('nombre', $this->nombre, 'categorias', NULL)) {
                    throw new Exception('Ya existe otra categoría registrada con este nombre.');
                }
            }

            $sentencia = "UPDATE categorias SET 
            nombre = :nombre, 
            edad_min = :edad_min, 
            edad_max = :edad_max 
            WHERE id_categorias = :id_categorias";

            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':edad_min', $this->edad_min);
            $stmt->bindParam(':edad_max', $this->edad_max);
            $stmt->bindParam(':id_categorias', $this->id);
            $stmt->execute();

            return array('accion' => 'exito');
        } catch (Exception $e) {
            logs('Categorias', $e->getMessage(), 'Modelo_Modificar');
            return array('accion' => 'error', 'codigo' => $e->getMessage());
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
            $conex = $this->conex();

            if (!$this->verificarExistencia('id', $this->id, 'categorias', NULL)) {
                throw new Exception('La categoría no existe.');
            }

            if ($this->verificarExistencia('id', $this->id, 'atletas', NULL)) {
                throw new Exception('No se puede eliminar: la categoría tiene atletas asociados.');
            }

            $sentencia = "DELETE FROM categorias WHERE id_categorias = :id";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();

            return array('accion' => 'eliminar', 'mensaje' => 'Categoría eliminada exitosamente.');
        } catch (Exception $e) {
            logs('Categorias', $e->getMessage(), 'Modelo_Eliminar');
            return array('accion' => 'error', 'codigo' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    public function verificarCategoria(int $id): bool
    {
        return $this->verificarExistencia('id', $id, 'categorias', NULL);
    }

    private function ValidarExpresiones(array $datos): void
    {
        if (!empty($datos['id']) && !preg_match('/^[0-9]+$/', $datos['id'])) {
            throw new Exception('Id inválido.');
        }
        if (!empty($datos['nombre']) && !preg_match('/^[a-zA-Z0-9\-\s]{2,30}$/', $datos['nombre'])) {
            throw new Exception('Nombre de categoría inválido.');
        }
        if (!empty($datos['edad_minima']) && !preg_match('/^[0-9]{1,2}$/', $datos['edad_minima'])) {
            throw new Exception('Edad mínima inválida.');
        }
        if (!empty($datos['edad_maxima']) && !preg_match('/^[0-9]{1,2}$/', $datos['edad_maxima'])) {
            throw new Exception('Edad máxima inválida.');
        }
        if (!empty($datos['edad_minima']) && !empty($datos['edad_maxima']) && (int)$datos['edad_minima'] > (int)$datos['edad_maxima']) {
            throw new Exception('La edad mínima no puede ser mayor que la edad máxima.');
        }
    }
}
