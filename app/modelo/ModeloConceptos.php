<?php

namespace App\modelo;

use Exception;

class ModeloConceptos extends Conexion
{
    private $id;
    private $nombre;
    private $monto;
    private $estatus;
    private $frecuencia;
    private $dias;
    public function __construct()
    {
        parent::__construct();
        //Definimos los campos permitidos para usar en las validaciones
        $this->campoWhitelist = [
            'nombre' => 'nombre',
            'monto' => 'monto',
            'id' => 'codigo_concepto'
        ];
        //Definimos la llave primaria de la tabla en la base de datos
        $this->llavePrimaria = 'codigo_concepto';
    }


    public function ProcesarDatos(array $datos): array
    {
        //si datos esta vacio ejecutamos la excepcion
        if (empty($datos)) {
            throw new Exception('No se proporcionaron datos para procesar.');
        }
        $this->ValidarExpresiones($datos);
        //Procesamos los datos
        $this->id = $datos['id'] ?? null;
        $this->nombre = mb_convert_case(trim($datos['nombre'] ?? ''), MB_CASE_TITLE, "UTF-8");
        $this->monto = floatval(str_replace(',', '.', trim($datos['monto'] ?? '')));
        $this->estatus = $datos['estatus'] ?? null;
        $this->frecuencia = $datos['frecuencia'] ?? null;
        $this->dias = $datos['dias'] ?? null;
        //ejecutamos la accion enviada por el controlador
        $accion = $datos['accion'] ?? null;
        
        return match ($accion) {
            'incluir'   => $this->Incluir(),
            'eliminar'  => $this->Eliminar(),
            'buscar' => $this->Buscar(),
            'modificar' => $this->Modificar(),
            'estatus'   => $this->Estatus(),
            default => throw new Exception('La accion no es valida')
        };
    }

    public function Consultar(array $filtro = []): array
    {
        try {
            $conex = $this->conex();
            $params = []; // Unificamos el nombre de la variable

            // 1. Iniciamos la sentencia con WHERE 1=1 para concatenar AND tranquilamente
            $sentencia = "SELECT * FROM conceptos WHERE 1=1";

            // 2. BUSCADOR GENERAL (El que viene del keyup)
            if (!empty($filtro['filtro'])) {
                $p = "%" . $filtro['filtro'] . "%";
                $sentencia .= " AND (
                nombre LIKE :f1 OR 
                monto LIKE :f2
            )";
                $params[':f1'] = $p;
                $params[':f2'] = $p;
            }

            // 3. FILTROS ESPECÍFICOS (Si vienen del Modal o propiedades del objeto)
            if (!empty($this->nombre)) {
                $sentencia .= " AND nombre LIKE :nombre";
                $params[':nombre'] = "%" . trim($this->nombre) . "%";
            }

            if (!empty($this->monto)) {
                $sentencia .= " AND monto = :monto";
                $params[':monto'] = trim($this->monto);
            }



            // 4. Orden (Asegúrate de usar una columna que exista, como id_conceptos)
            $sentencia .= " ORDER BY codigo_concepto ASC";

            $stmt = $conex->prepare($sentencia);

            // IMPORTANTE: Pasar los parámetros al execute
            $stmt->execute($params);

            $datos = $stmt->fetchAll();

            return array('accion' => 'consultar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Concepto', $e->getMessage(), 'Modelo_Consultar');
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
            if ($this->verificarExistencia('nombre', $this->nombre, 'conceptos', NULL, bloquear: true)) {
                throw new Exception(DUPLICATE_NAME);
            }

            $sentencia = "INSERT INTO conceptos (`nombre`, `monto`, `frecuencia`, `dias_gracia`) VALUES (:nombre, :monto, :frecuencia, :dias)";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':monto', $this->monto);
            $stmt->bindParam(':frecuencia', $this->frecuencia);
            $stmt->bindParam(':dias', $this->dias);
            $stmt->execute();

            $conex->commit();
            return array('accion' => 'exito');
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollback();
            }
            logs('Concepto', $e->getMessage(), 'Modelo');
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

            if (!$this->verificarExistencia('id', $this->id, 'conceptos', NULL, bloquear: true)) {
                throw new Exception(INVALID_ID);
            }

            if (!$this->verificarExistenciaPropia('nombre', $this->nombre, $this->id, 'conceptos', NULL, bloquear: true)) {
                if ($this->verificarExistencia('nombre', $this->nombre, 'conceptos', NULL, bloquear: true)) {
                    throw new Exception(DUPLICATE_NAME);
                }
            }

            $sentencia = "UPDATE conceptos SET 
            nombre = :nombre, 
            monto = :monto,
            frecuencia = :frecuencia,
            dias_gracia = :dias
            WHERE codigo_concepto = :id_conceptos";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':monto', $this->monto);
            $stmt->bindParam(':id_conceptos', $this->id);
            $stmt->bindParam(':frecuencia', $this->frecuencia);
            $stmt->bindParam(':dias', $this->dias);
            $stmt->execute();

            $conex->commit();
            return array('accion' => 'exito');
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollback();
            }
            logs('Conceptos', $e->getMessage(), 'Modelo_Modificar');
            return array('accion' => 'error', 'codigo' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    public function Buscar(): array
    {
        try {
            $conex = $this->conex();
            $sentencia = "SELECT * FROM conceptos WHERE codigo_concepto = :id";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            return array('accion' => 'buscar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Concepto', $e->getMessage(), 'Modelo');
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
            if (!$this->verificarExistencia('id', $this->id, 'conceptos', NULL, bloquear: true)) {
                throw new Exception(INVALID_ID);
            }
            if (!$this->verificarExistencia('id', $this->id, 'cargos', NULL, bloquear: true)) {
                throw new Exception(ASSOCIATES);
            }
            $sentencia = "DELETE FROM conceptos WHERE codigo_concepto = :id";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
            $conex->commit();
            return array('accion' => 'exito');
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollback();
            }
            logs('Concepto', $e->getMessage(), 'Modelo_Eliminar');
            return array('accion' => 'error', 'codigo' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    private function Estatus(): array
    {
        try {
            $conex = $this->conex();
            $conex->beginTransaction();

            if (!$this->verificarExistencia('id', $this->id, 'conceptos', NULL, bloquear: true)) {
                throw new Exception(INVALID_ID);
            }

            // Alternar entre 1 y 2
            $nuevoEstado = ($this->estatus == 1) ? 2 : 1;

            $sentencia = "UPDATE conceptos SET estatus = :estatus WHERE codigo_concepto = :id_conceptos";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':estatus', $nuevoEstado);
            $stmt->bindParam(':id_conceptos', $this->id);
            $stmt->execute();

            $conex->commit();
            return array('accion' => 'exito');
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollback();
            }
            logs('Concepto', $e->getMessage(), 'Modelo_Estatus');
            return array('accion' => 'error', 'codigo' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    private function ValidarExpresiones(array $datos): void
    {
        if (!empty($datos['id']) && !preg_match('/^[0-9]+$/', $datos['id'])) {
            throw new Exception('Id inválido.');
        }
        if (!empty($datos['nombre']) && !preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,30}$/', $datos['nombre'])) {
            throw new Exception('Nombre inválido.');
        }
        if (!empty($datos['monto']) && !preg_match('/^[0-9]+(\.[0-9]{1,2})?$/', $datos['monto'])) {
            throw new Exception('Monto inválido.');
        }
        if (!empty($datos['frecuencia']) && !preg_match('/^[LMAU]$/', $datos['frecuencia'])) {
            throw new Exception('Frecuencia inválido.');
        }
        if (!empty($datos['dias']) && !preg_match('/^[0-9]{1,3}$/', $datos['dias'])) {
            throw new Exception('dias de pago inválido.');
        }
    }
}
