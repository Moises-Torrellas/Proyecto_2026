<?php

namespace App\modelo;

use App\modelo\ModeloBase;
use Exception;
use SensitiveParameter;

class ModeloCalidad extends ModeloBase
{
    private $id;
    private $nombre;
    private $nivel;

    public function __construct()
    {
        parent::__construct();
        $this->campoWhitelist = [
            'id' => 'id_estado',
            'nombre' => 'nombre'
        ];
        $this->llavePrimaria = 'id_estado';
    }

    public function ProcesarDatos(array $datos): array
    {
        if (empty($datos)) {
            throw new Exception('No se proporcionaron datos para procesar.');
        }
        $this->id = $datos['id'] ?? null;
        $this->nombre = mb_convert_case(trim($datos['nombre'] ?? ''), MB_CASE_TITLE, "UTF-8");
        $this->nivel = $datos['nivel'] ?? null;
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

            // 1. Sentencia base
            $sentencia = "SELECT * FROM estado_equipamiento WHERE 1=1";

            // 2. BUSCADOR GENERAL (Afecta a ambas columnas si se escribe algo en el input de búsqueda)
            if (!empty($filtro['filtro'])) {
                $p = "%" . $filtro['filtro'] . "%";
                // Cerramos el paréntesis del OR para que no choque con otros AND
                $sentencia .= " AND (nombre LIKE :f1 OR nivel_estado LIKE :f2)";
                $params[':f1'] = $p;
                $params[':f2'] = $p;
            }

            // 3. FILTROS ESPECÍFICOS (Por propiedades del objeto)
            // Filtro por Nombre
            if (!empty($this->nombre)) {
                $sentencia .= " AND nombre LIKE :nombre";
                $params[':nombre'] = trim($this->nombre) . "%";
            }

            // Filtro por Nivel de Estado
            if (!empty($this->nivel_estado)) {
                $sentencia .= " AND nivel_estado = :nivel";
                $params[':nivel'] = $this->nivel;
            }

            // 4. Orden (Ajustado a la tabla estado_equipamiento)
            // Cambié id_categorias por id, o la columna primaria que uses
            $sentencia .= " ORDER BY nombre ASC";

            $stmt = $conex->prepare($sentencia);
            $stmt->execute($params);

            $datos = $stmt->fetchAll();

            return array('accion' => 'consultar', 'datos' => $datos);
        } catch (Exception $e) {
            // Asegúrate de que la función logs() esté disponible
            logs('EstadoEquipamiento', $e->getMessage(), 'Modelo_Consultar');
            return array('accion' => 'error', 'mensaje' => 'Error al listar: ' . $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }
    function Buscar(): array
    {
        try {
            $conex = $this->conex();
            $sentencia = "SELECT * FROM estado_equipamiento WHERE id_estado = :id";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            return array('accion' => 'buscar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('EstadoEquipamiento', $e->getMessage(), 'Modelo_Buscar');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    private function Incluir(): array
    {
        try {
            if ($this->verificarExistencia('nombre', $this->nombre, 'estado_equipamiento', NULL)) {
                return array('accion' => 'error', 'mensaje' => 'Ya existe una calidad registrada con este nombre.');
            }

            $conex = $this->conex();
            $sentencia = "INSERT INTO estado_equipamiento (`nombre`, `nivel_estado`) VALUES (:nombre, :nivel)";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':nivel', $this->nivel);
            $stmt->execute();

            return array('accion' => 'incluir', 'mensaje' => 'Calidad registrada exitosamente.');
        } catch (Exception $e) {
            logs('EstadoEquipamiento', $e->getMessage(), 'Modelo_Incluir');
            return array('accion' => 'error', 'mensaje' => 'Error al incluir: ' . $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    private function Modificar(): array
    {
        try {
            if (!$this->verificarExistenciaPropia('nombre', $this->nombre, $this->id, 'estado_equipamiento', NULL)) {
                if ($this->verificarExistencia('nombre', $this->nombre, 'estado_equipamiento', NULL)) {
                    return array('accion' => 'error', 'mensaje' => 'Ya existe otra calidad registrada con este nombre.');
                }
            }
            $conex = $this->conex();
            $sentencia = "UPDATE estado_equipamiento SET 
            nombre = :nombre, 
            nivel_estado = :nivel
            WHERE id_estado = :id";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':nivel', $this->nivel);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();

            return array('accion' => 'modificar', 'mensaje' => 'Calidad modificada exitosamente.');
        } catch (Exception $e) {
            logs('EstadoEquipamiento', $e->getMessage(), 'Modelo_Modificar');
            return array('accion' => 'error', 'mensaje' => 'Error al modificar: ' . $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    private function Eliminar(): array
    {
        try {
            if (!$this->verificarExistencia('id', $this->id, 'estado_equipamiento', NULL)) {
                return array('accion' => 'error', 'mensaje' => 'La calidad no existe.');
            }

            $conex = $this->conex();
            $sentencia = "DELETE FROM estado_equipamiento WHERE id_estado = :id";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();

            return array('accion' => 'eliminar', 'mensaje' => 'Calidad eliminada exitosamente.');
        } catch (Exception $e) {
            logs('EstadoEquipamiento', $e->getMessage(), 'Modelo_Eliminar');
            return array('accion' => 'error', 'mensaje' => 'Hubo un error al eliminar la categoría.');
        } finally {
            $conex = NULL;
        }
    }
}
