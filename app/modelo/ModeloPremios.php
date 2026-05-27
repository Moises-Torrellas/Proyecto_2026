<?php

namespace App\modelo;

use App\modelo\ModeloBase;
use Exception;

class ModeloPremios extends ModeloBase
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
            'id' => 'id_premios'
        ];
        // Definimos la llave primaria de la tabla en la base de datos
        $this->llavePrimaria = 'id_premios';
    }

    public function ProcesarDatos(array $datos): array
    {
        // Si datos esta vacio ejecutamos la excepcion
        if (empty($datos)) {
            throw new Exception('No se proporcionaron datos para procesar.');
        }

        // CORRECCIÓN 1: Capturamos el ID que faltaba
        $this->id = $datos['id'] ?? null;
        
        // CORRECCIÓN 2: Procesamos el tipo forzando minúsculas ('i' o 'g')
        $this->tipo = isset($datos['tipo']) ? strtolower(trim($datos['tipo'])) : null;
        
        $this->nombre = mb_convert_case(trim($datos['nombre'] ?? ''), MB_CASE_TITLE, "UTF-8");
        
        // Ejecutamos la accion enviada por el controlador
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

            // 1. Iniciamos la sentencia con WHERE 1=1 para concatenar AND tranquilamente
            $sentencia = "SELECT * FROM premios WHERE 1=1";

            // 2. BUSCADOR GENERAL (El que viene del keyup)
            if (!empty($filtro['filtro'])) {
                $p = "%" . $filtro['filtro'] . "%";
                $sentencia .= " AND (
                nombre LIKE :f1 OR 
                tipo LIKE :f2
            )";
                $params[':f1'] = $p;
                $params[':f2'] = $p;
            }

            // 3. FILTROS ESPECÍFICOS (Si vienen del Modal o propiedades del objeto)
            if (!empty($this->nombre)) {
                $sentencia .= " AND nombre LIKE :nombre";
                $params[':nombre'] = trim($this->nombre) . "%";
            }

            if (!empty($this->tipo)) {
                // CORRECCIÓN 3: Uso de '=' para búsqueda exacta del carácter
                $sentencia .= " AND tipo = :tipo";
                $params[':tipo'] = $this->tipo;
            }

            // 4. Orden
            $sentencia .= " ORDER BY id_premios ASC";

            $stmt = $conex->prepare($sentencia);

            // IMPORTANTE: Pasar los parámetros al execute
            $stmt->execute($params);

            $datos = $stmt->fetchAll();

            return array('accion' => 'consultar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Premios', $e->getMessage(), 'Modelo_Consultar');
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

            $sentencia = "UPDATE premios SET
            nombre = :nombre, 
            tipo = :tipo
            WHERE id_premios = :id_premios";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':tipo', $this->tipo);
            $stmt->bindParam(':id_premios', $this->id);
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

    function Buscar(): array
    {
        try {
            $conex = $this->conex();
            $sentencia = "SELECT * FROM premios WHERE id_premios = :id";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            return array('accion' => 'buscar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Premios', $e->getMessage(), 'Modelo');
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
            
            // CORRECCIÓN 4: Evaluar contra 'id_premios' en lugar de 'id'
            if (!$this->verificarExistencia('id_premios', $this->id, 'premios', NULL, bloquear:true)) {
                throw new Exception(INVALID_ID);
            }
            // Asegúrate de que en la tabla detalles_palmares la columna FK también se llame id_premios
            if ($this->verificarExistencia('id_premios', $this->id, 'detalles_palmares', NULL, bloquear:true)) {
                throw new Exception(ASSOCIATES);
            }
            
            $sentencia = "DELETE FROM premios WHERE id_premios = :id";
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
}