<?php

namespace App\modelo;

use App\modelo\ModeloBase;
use Exception;
use SensitiveParameter;

class ModeloRepresentantes extends ModeloBase
{
    private $id;
    private $cedula;
    private $nombre;
    private $apellido;
    private $telefono;
    private $direccion;
    private $nacionalidad;
    public function __construct()
    {
        parent::__construct();
        $this->campoWhitelist = [
            'cedula' => 'cedula',
            'telefono' => 'telefono',
            'id' => 'id_representante'
        ];
        $this->llavePrimaria = 'id_representante';
    }

    public function ProcesarDatos(array $datos): array
    {
        if (empty($datos)) {
            throw new Exception('No se proporcionaron datos para procesar.');
        }
        $this->cedula = $datos['cedula'] ?? '';
        $this->telefono = $datos['telefono'] ?? '';
        $this->id = $datos['id'] ?? null;
        $this->direccion = mb_convert_case(trim($datos['direccion'] ?? ''), MB_CASE_TITLE, "UTF-8");
        $this->nacionalidad = $datos['nacionalidad'] ?? null;
        $this->nombre = mb_convert_case(trim($datos['nombre'] ?? ''), MB_CASE_TITLE, "UTF-8");
        $this->apellido = mb_convert_case(trim($datos['apellido'] ?? ''), MB_CASE_TITLE, "UTF-8");
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
            $sentencia = "SELECT * FROM representantes WHERE 1=1";

            // 2. BUSCADOR GENERAL (El que viene del keyup)
            if (!empty($filtro['filtro'])) {
                $p = "%" . $filtro['filtro'] . "%";
                $sentencia .= " AND (
                cedula LIKE :f1 OR 
                nombre LIKE :f2 OR 
                apellido LIKE :f3 OR 
                telefono LIKE :f4
            )";
                $params[':f1'] = $p;
                $params[':f2'] = $p;
                $params[':f3'] = $p;
                $params[':f4'] = $p;
            }

            // 3. FILTROS ESPECÍFICOS (Si vienen del Modal o propiedades del objeto)
            if (!empty($this->cedula)) {
                $sentencia .= " AND cedula LIKE :cedula";
                $params[':cedula'] = trim($this->cedula) . "%";
            }

            if (!empty($this->nombre)) {
                $sentencia .= " AND nombre LIKE :nombre";
                $params[':nombre'] = "%" . trim($this->nombre) . "%";
            }

            if (!empty($this->apellido)) {
                $sentencia .= " AND apellido LIKE :apellido";
                $params[':apellido'] = "%" . trim($this->apellido) . "%";
            }

            // 4. Orden (Asegúrate de usar una columna que exista, como id_representante)
            $sentencia .= " ORDER BY id_representante ASC";

            $stmt = $conex->prepare($sentencia);

            // IMPORTANTE: Pasar los parámetros al execute
            $stmt->execute($params);

            $datos = $stmt->fetchAll();

            return array('accion' => 'consultar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Representantes', $e->getMessage(), 'Modelo_Consultar');
            return array('accion' => 'error', 'mensaje' => 'Error al listar: ' . $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    private function Incluir(): array
    {
        try {
            if ($this->verificarExistencia('cedula', $this->cedula, 'representantes', NULL)) {
                return array('accion' => 'error', 'mensaje' => 'Ya existe un representante registrado con esta cedula.');
            }
            if ($this->verificarExistencia('telefono', $this->telefono, 'representantes', NULL)) {
                return array('accion' => 'error', 'mensaje' => 'Ya existe un representante registrado con este telefono.');
            }

            $conex = $this->conex();
            $sentencia = "INSERT INTO representantes (`cedula`, `nacionalidad`, `nombre`, `apellido`, `telefono`, `direccion`) VALUES (:cedula, :nacionalidad,:nombre, :apellido,:telefono, :direccion)";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':cedula', $this->cedula);
            $stmt->bindParam(':nacionalidad', $this->nacionalidad);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':apellido', $this->apellido);
            $stmt->bindParam(':telefono', $this->telefono);
            $stmt->bindParam(':direccion', $this->direccion);
            $stmt->execute();

            return array('accion' => 'incluir', 'mensaje' => 'Representante registrado exitosamente.');
        } catch (Exception $e) {
            logs('Representantes', $e->getMessage(), 'Modelo');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    private function Modificar(): array
    {
        try {
            if (!$this->verificarExistenciaPropia('cedula', $this->cedula, $this->id, 'representantes', NULL)) {
                if ($this->verificarExistencia('cedula', $this->cedula, 'representantes', NULL)) {
                    return array('accion' => 'error', 'mensaje' => 'Ya existe un representante registrado con esta cedula.');
                }
            }
            if (!$this->verificarExistenciaPropia('telefono', $this->telefono, $this->id, 'representantes', NULL)) {
                if ($this->verificarExistencia('telefono', $this->telefono, 'representantes', NULL)) {
                    return array('accion' => 'error', 'mensaje' => 'Ya existe un representante registrado con este telefono.');
                }
            }
            $conex = $this->conex();
            $sentencia = "UPDATE representantes SET 
            cedula = :cedula, 
            nacionalidad = :nacionalidad, 
            nombre = :nombre, 
            apellido = :apellido, 
            telefono = :telefono, 
            direccion = :direccion 
            WHERE id_representante = :id_representante";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':cedula', $this->cedula);
            $stmt->bindParam(':nacionalidad', $this->nacionalidad);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':apellido', $this->apellido);
            $stmt->bindParam(':telefono', $this->telefono);
            $stmt->bindParam(':direccion', $this->direccion);
            $stmt->bindParam(':id_representante', $this->id);
            $stmt->execute();

            return array('accion' => 'modificar', 'mensaje' => 'Representante modificado exitosamente.');
        } catch (Exception $e) {
            logs('Representantes', $e->getMessage(), 'Modelo');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    function Buscar(): array
    {
        try {
            $conex = $this->conex();
            $sentencia = "SELECT * FROM representantes WHERE id_representante = :id";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            return array('accion' => 'buscar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Representantes', $e->getMessage(), 'Modelo');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    private function Eliminar(): array
    {
        try {
            if (!$this->verificarExistencia('id', $this->id, 'representantes', NULL)) {
                return array('accion' => 'error', 'mensaje' => 'El representante no existe.');
            }
            if ($this->verificarExistencia('id', $this->id, 'atletas', NULL)) {
                return array('accion' => 'error', 'mensaje' => 'El representante tiene atletas asociados.');
            }
            $conex = $this->conex();
            $sentencia = "DELETE FROM representantes WHERE id_representante = :id";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
            return array('accion' => 'eliminar', 'mensaje' => 'Representante eliminado exitosamente.');
        } catch (Exception $e) {
            logs('Representantes', $e->getMessage(), 'Modelo');
            return array('accion' => 'error', 'mensaje' => 'Hubo un error al eliminar el representante.');
        } finally {
            $conex = NULL;
        }
    }
}
