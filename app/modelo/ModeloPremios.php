<?php

namespace App\modelo;

use Exception;

class ModeloPremios extends Conexion
{
    private $id;
    private $nombre;
    private $tipo;

    public function __construct()
    {
        parent::__construct();
        // Definimos los campos permitidos para usar en las validaciones
        $this->campoWhitelist = [
            'nombre' => 'nombre',
            'id' => 'id_premio'
        ];
        // Definimos la llave primaria de la tabla en la base de datos
        $this->llavePrimaria = 'id_premio';
    }

    public function ProcesarDatos(array $datos): array
    {
        if (empty($datos)) {
            throw new Exception('No se proporcionaron datos para procesar.');
        }

        $this->id = $datos['id'] ?? null;

        $this->tipo = isset($datos['tipo']) ? strtoupper(trim($datos['tipo'])) : null;
        
        $this->nombre = mb_convert_case(trim($datos['nombre'] ?? ''), MB_CASE_TITLE, "UTF-8");
        
        $accion = $datos['accion'] ?? null;
        return match ($accion) {
            'incluir'   => $this->Incluir(),
            'eliminar'  => $this->Eliminar(),
            'buscar'    => $this->Buscar(),
            'modificar' => $this->Modificar(),
            'generar'   => $this->Consultar(),
            default     => throw new Exception('La accion no es valida')
        };
    }

    public function Consultar(array $filtro = []): array
    {
        try {
            $conex = $this->conex();
            $params = [];

            $sentencia = "SELECT * FROM premios WHERE 1=1";

            if (!empty($filtro['filtro'])) {
                $p = "%" . $filtro['filtro'] . "%";
                $sentencia .= " AND (nombre LIKE :f1 OR tipo LIKE :f2)";
                $params[':f1'] = $p;
                $params[':f2'] = $p;
            }

            if (!empty($this->nombre)) {
                $sentencia .= " AND nombre LIKE :nombre";
                $params[':nombre'] = trim($this->nombre) . "%";
            }

            if (!empty($this->tipo)) {
                $sentencia .= " AND tipo = :tipo";
                $params[':tipo'] = $this->tipo;
            }

            $sentencia .= " ORDER BY id_premio ASC";

            $stmt = $conex->prepare($sentencia);
            $stmt->execute($params);
            $datos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            return array('accion' => 'consultar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Premios', $e->getMessage(), 'Modelo_Consultar');
            die("Error técnico en la consulta SQL de la Base de Datos: " . $e->getMessage());
            return array('accion' => 'error');
        } finally {
            $conex = NULL;
        }
    }

    private function Incluir(): array
    {
        try {
            $conex = $this->conex();
            $conex->beginTransaction();
            
            if ($this->verificarExistencia('nombre', $this->nombre, 'premios', NULL, bloquear: true)) {
                throw new Exception(DUPLICATE_NAME);
            }

            $sentencia = "INSERT INTO premios (`nombre`, `tipo`) VALUES (:nombre, :tipo)";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':tipo', $this->tipo);

            $stmt->execute();
            $conex->commit();
            return array('accion' => 'exito');
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollback();
            }
            logs('Premios', $e->getMessage(), 'Modelo');
            return array('accion' => 'error', 'codigo' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    private function Modificar(): array
    {
        try {
            $conex = $this->conex();
            $conex->beginTransaction();

            if (!$this->verificarExistenciaPropia('nombre', $this->nombre, $this->id, 'premios', NULL, bloquear: true)) {
                if ($this->verificarExistencia('nombre', $this->nombre, 'premios', NULL, bloquear: true)) {
                    throw new Exception(DUPLICATE_NAME);
                }
            }

            $sentencia = "UPDATE premios SET nombre = :nombre, tipo = :tipo WHERE id_premio = :id_premio";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':tipo', $this->tipo);
            $stmt->bindParam(':id_premio', $this->id);
            $stmt->execute();

            $conex->commit();
            return array('accion' => 'exito');
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollback();
            }
            logs('Premios', $e->getMessage(), 'Modelo_Modificar');
            return array('accion' => 'error', 'codigo' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    public function Buscar(): array
    {
        try {
            $conex = $this->conex();
            $sentencia = "SELECT * FROM premios WHERE id_premio = :id";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            return array('accion' => 'buscar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Premios', $e->getMessage(), 'Modelo_Buscar');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    private function Eliminar(): array
    {
        try {
            $conex = $this->conex();
            $conex->beginTransaction();
            
            // CORRECCIÓN: Regresamos a la clave 'id' para que use la lista blanca de ModeloBase
            if (!$this->verificarExistencia('id', $this->id, 'premios', NULL, bloquear:true)) {
                throw new Exception(INVALID_ID);
            }
            if ($this->verificarExistencia('id', $this->id, 'detalles_palmares', NULL, bloquear:true)) {
                throw new Exception(ASSOCIATES);
            }
            
            $sentencia = "DELETE FROM premios WHERE id_premio = :id";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
            $conex->commit();
            return array('accion' => 'exito');
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollback();
            }
            logs('Premios', $e->getMessage(), 'Modelo_Eliminar');
            return array('accion' => 'error', 'codigo' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }
    public function validarTipoPremio(int $id_premio, string $tipoEsperado): void
    {
        $conex = null;
        try {
            $conex = $this->conex();
            $stmt = $conex->prepare("SELECT tipo FROM premios WHERE id_premio = :id_premio");
            $stmt->bindValue(':id_premio', $id_premio, \PDO::PARAM_INT);
            $stmt->execute();
            $premio = $stmt->fetch();

            if (!$premio) {
                throw new Exception(INVALID_ID);
            }

            if ($premio['tipo'] !== $tipoEsperado) {
                throw new Exception(VALIDATION);
            }
        } finally {
            $conex = null;
        }
    }
}