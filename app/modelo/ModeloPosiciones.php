<?php

namespace App\modelo;

use Exception;

class ModeloPosiciones extends Conexion
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
            'id' => 'codigo_posicion'
        ];
        $this->llavePrimaria = 'codigo_posicion';
    }
    public function ProcesarDatos(array $datos): array
    {
        if (empty($datos)) {
            throw new Exception('No se proporcionaron datos para procesar.');
        }

        $this->ValidarExpresiones($datos);

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
            'generar' => $this->Consultar(),
            default => throw new Exception('La accion no es valida')
        };
    }

    public function Consultar(array $filtro = []): array
    {
        try {
            $conex = $this->conex();
            $params = [];

            // 1. Iniciamos la sentencia
            $sentencia = "SELECT * FROM posiciones WHERE 1=1";

            // 2. BUSCADOR GENERAL (El que viene del buscador o keyup)
            if (!empty($filtro['filtro'])) {
                $p = "%" . $filtro['filtro'] . "%";
                $sentencia .= " AND (
                nombre LIKE :f1 OR 
                abreviatura LIKE :f2
            )";
                $params[':f1'] = $p;
                $params[':f2'] = $p;
            }

            // 3. FILTROS ESPECÍFICOS (Si vienen definidos en el objeto)
            if (!empty($this->nombre)) {
                $sentencia .= " AND nombre LIKE :nombre";
                $params[':nombre'] = "%" . trim($this->nombre) . "%";
            }

            if (!empty($this->abreviatura)) {
                $sentencia .= " AND abreviatura LIKE :abreviatura";
                $params[':abreviatura'] = "%" . trim($this->abreviatura) . "%";
            }

            // 4. ORDENAMIENTO
            $sentencia .= " ORDER BY codigo_posicion ASC";

            $stmt = $conex->prepare($sentencia);
            $stmt->execute($params);

            $datos = $stmt->fetchAll();

            return array('accion' => 'consultar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Posiciones', $e->getMessage(), 'Modelo_Consultar');
            return array('accion' => 'error', 'mensaje' => 'Error al listar las posiciones.');
        } finally {
            $conex = NULL;
        }
    }

    private function Buscar(): array
    {
        try {
            $conex = $this->conex();
            $sentencia = "SELECT * FROM posiciones WHERE codigo_posicion = :id";
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

    private function Incluir(): array
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
            $sentencia = "DELETE FROM posiciones WHERE codigo_posicion = :id";
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

    private function Modificar(): array
    {
        try {
            if (!$this->verificarExistenciaPropia('nombre', $this->nombre, $this->id, 'posiciones', NULL)) {
                if ($this->verificarExistencia('nombre', $this->nombre, 'posiciones', NULL)) {
                    return array('accion' => 'error', 'mensaje' => 'Ya existe una posición registrada con este nombre.');
                }
            }
            if (!$this->verificarExistenciaPropia('abreviatura', $this->abreviatura, $this->id, 'posiciones', NULL)) {
                if ($this->verificarExistencia('abreviatura', $this->abreviatura, 'posiciones', NULL)) {
                    return array('accion' => 'error', 'mensaje' => 'Ya existe una posición registrada con esta abreviatura.');
                }
            }

            $conex = $this->conex();
            $sentencia = "UPDATE posiciones SET nombre = :n, abreviatura = :a, descripcion = :d WHERE codigo_posicion = :id";
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

    public function verificarPosiciones(int $id): bool
    {
        return $this->verificarExistencia('id', $id, 'posiciones', NULL);
    }

    private function ValidarExpresiones(array $datos): void
    {
        if (!empty($datos['id']) && !preg_match('/^[0-9]+$/', $datos['id'])) {
            throw new Exception('Id inválido.');
        }
        if (!empty($datos['nombre']) && !preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,30}$/', $datos['nombre'])) {
            throw new Exception('Nombre inválido.');
        }
        if (!empty($datos['abreviatura']) && !preg_match('/^[a-zA-Z]{2,4}$/', $datos['abreviatura'])) {
            throw new Exception('Abreviatura inválida.');
        }
        if (!empty($datos['descripcion']) && !preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,150}$/', $datos['descripcion'])) {
            throw new Exception('Descripcion inválida.');
        }
    }
}
