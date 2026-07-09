<?php

namespace App\modelo;

use Exception;

class ModeloRepresentantes extends Conexion
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
            'id' => 'codigo_representante'
        ];

        $this->llavePrimaria = 'codigo_representante';
    }
    private function ValidarExpresiones(array $datos): void
    {
        if (!empty($datos['id']) && !preg_match('/^[0-9]+$/', $datos['id'])) {
            throw new Exception('Id inválido.');
        }
        if (!empty($datos['cedula']) && !preg_match('/^[0-9]{1,8}$/', $datos['cedula'])) {
            throw new Exception('Cédula inválida.');
        }
        if (!empty($datos['nombre']) && !preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,30}$/', $datos['nombre'])) {
            throw new Exception('Nombre inválido.');
        }
        if (!empty($datos['apellido']) && !preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,30}$/', $datos['apellido'])) {
            throw new Exception('Apellido inválido.');
        }
        if (!empty($datos['telefono']) && !preg_match('/^[0-9]{4}[-]{1}[0-9]{7}$/', $datos['telefono'])) {
            throw new Exception('Teléfono inválido.');
        }
        if (!empty($datos['direccion']) && !preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,150}$/', $datos['direccion'])) {
            throw new Exception('Dirección inválida.');
        }
        if (!empty($datos['nacionalidad']) && !preg_match('/^[VEP]$/', $datos['nacionalidad'])) {
            throw new Exception('Nacionalidad inválida. Solo se permite V, E o P.');
        }
    }

    public function ProcesarDatos(array $datos): array
    {

        if (empty($datos)) {
            throw new Exception('No se proporcionaron datos para procesar.');
        }

        $this->ValidarExpresiones($datos);

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
            'generar'   => $this->Consultar(),
            default => throw new Exception('La accion no es valida')
        };
    }

    public function Consultar(array $filtro = []): array
    {
        $conex=null;
        try {
            $conex = $this->conex();
            $params = [];


            $sentencia = "SELECT * FROM representantes WHERE 1=1";


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


            if (!empty($this->cedula)) {
                $sentencia .= " AND cedula LIKE :cedula";
                $params[':cedula'] = trim($this->cedula) . "%";
            }

            if (!empty($this->nacionalidad)) {
                $sentencia .= " AND nacionalidad LIKE :nacionalidad";
                $params[':nacionalidad'] = "%" . trim($this->nacionalidad) . "%";
            }


            $sentencia .= " ORDER BY codigo_representante ASC";

            $stmt = $conex->prepare($sentencia);


            $stmt->execute($params);

            $datos = $stmt->fetchAll();

            return array('accion' => 'consultar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Representantes', $e->getMessage(), 'Modelo_Consultar');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    private function Incluir(): array
    {
        $conex=null;
        $datos_nuevos = [
            'cedula' => $this->cedula,
            'nacionalidad' => $this->nacionalidad,
            'nombre' => $this->nombre,
            'apellido' => $this->apellido,
            'telefono' => $this->telefono,
            'direccion' => $this->direccion
        ];
        
        try {
            $conex = $this->conex();
            $conex->beginTransaction();
            if ($this->verificarExistencia('cedula', $this->cedula, 'representantes', NULL, bloquear: true)) {
                throw new Exception(DUPLICATE_CEDULA);
            }
            if ($this->verificarExistencia('telefono', $this->telefono, 'representantes', NULL, bloquear: true)) {
                throw new Exception(DUPLICATE_PHONE);
            }

            $sentencia = "INSERT INTO representantes (`cedula`, `tipo_doc`, `nombre`, `apellido`, `telefono`, `direccion`) VALUES (:cedula, :nacionalidad,:nombre, :apellido,:telefono, :direccion)";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':cedula', $this->cedula);
            $stmt->bindParam(':nacionalidad', $this->nacionalidad);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':apellido', $this->apellido);
            $stmt->bindParam(':telefono', $this->telefono);
            $stmt->bindParam(':direccion', $this->direccion);
            $stmt->execute();

            $conex->commit();
            return array('accion' => 'exito',  'datos_nuevos' => json_encode($datos_nuevos));
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollback();
            }
            logs('Representantes', $e->getMessage(), 'Modelo');
            return array('accion' => 'error', 'codigo' => $e->getMessage(), 'datos_nuevos' => json_encode($datos_nuevos));
        } finally {
            $conex = NULL;
        }
    }

    private function Modificar(): array
    {
        $conex=null;
        $datos_nuevos = [
            'cedula' => $this->cedula,
            'nacionalidad' => $this->nacionalidad,
            'nombre' => $this->nombre,
            'apellido' => $this->apellido,
            'telefono' => $this->telefono,
            'direccion' => $this->direccion
        ];
        
        try {
            $conex = $this->conex();
            $conex->beginTransaction();

            if (!$this->verificarExistenciaPropia('cedula', $this->cedula, $this->id, 'representantes', NULL, bloquear: true)) {
                if ($this->verificarExistencia('cedula', $this->cedula, 'representantes', NULL, bloquear: true)) {
                    throw new Exception(DUPLICATE_CEDULA);
                }
            }
            if (!$this->verificarExistenciaPropia('telefono', $this->telefono, $this->id, 'representantes', NULL, bloquear: true)) {
                if ($this->verificarExistencia('telefono', $this->telefono, 'representantes', NULL, bloquear: true)) {
                    throw new Exception(DUPLICATE_PHONE);
                }
            }
            $sentencia = "UPDATE representantes SET 
            cedula = :cedula, 
            tipo_doc = :nacionalidad, 
            nombre = :nombre, 
            apellido = :apellido, 
            telefono = :telefono, 
            direccion = :direccion 
            WHERE codigo_representante = :codigo_representante";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':cedula', $this->cedula);
            $stmt->bindParam(':nacionalidad', $this->nacionalidad);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':apellido', $this->apellido);
            $stmt->bindParam(':telefono', $this->telefono);
            $stmt->bindParam(':direccion', $this->direccion);
            $stmt->bindParam(':codigo_representante', $this->id);
            $stmt->execute();

            $conex->commit();
            return array('accion' => 'exito', 'datos_nuevos' => json_encode($datos_nuevos));
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollback();
            }
            logs('Representantes', $e->getMessage(), 'Modelo_Modificar');
            return array('accion' => 'error', 'codigo' => $e->getMessage(), 'datos_nuevos' => json_encode($datos_nuevos));
        } finally {
            $conex = NULL;
        }
    }

    public function Buscar($id = null): array
    {
        $conex=null;
        try {
            $codigo = ($id === null) ? $this->id : $id;
            $conex = $this->conex();
            $sentencia = "SELECT * FROM representantes WHERE codigo_representante = :id";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id', $codigo);
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
        $conex=null;
        try {
            $conex = $this->conex();
            $conex->beginTransaction();
            if (!$this->verificarExistencia('id', $this->id, 'representantes', NULL, bloquear: true)) {
                throw new Exception(INVALID_ID);
            }
            if ($this->verificarExistencia('id', $this->id, 'atletas', NULL, bloquear: true)) {
                throw new Exception(ASSOCIATES);
            }
            $sentencia = "DELETE FROM representantes WHERE codigo_representante = :id";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
            $conex->commit();
            return array('accion' => 'exito');
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollback();
            }
            logs('Representantes', $e->getMessage(), 'Modelo_Eliminar');
            return array('accion' => 'error', 'codigo' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }
    public function verificarRepresentantes(int $id): bool
    {
        return $this->verificarExistencia('id', $id, 'representantes', NULL);
    }
}
