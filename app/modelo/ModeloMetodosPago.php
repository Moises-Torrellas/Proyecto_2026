<?php

namespace App\modelo;

use App\modelo\ModeloBase;
use Exception;

class ModeloMetodosPago extends Conexion
{
    private $id;
    private $nombre;
    private $nec_referencia;
    private $bloqueo;
    
    public function __construct()
    {
        parent::__construct();
        //Definimos los campos permitidos para usar en las validaciones
        $this->campoWhitelist = [
            'nombre' => 'nombre',
            'nec_referencia' => 'nec_referencia',
            'estatus' => 'estatus',
            'id' => 'codigo_metodo'
        ];
        //Definimos la llave primaria de la tabla en la base de datos
        $this->llavePrimaria = 'codigo_metodo';
    }


    public function ProcesarDatos(array $datos): array
    {
        //si datos esta vacio ejecutamos la excepcion
        if (empty($datos)) {
            throw new Exception('No se proporcionaron datos para procesar.');
        }
        //Procesamos los datos
        $this->id = $datos['id'] ?? null;
        $this->bloqueo = $datos['bloqueo'] ?? null;
        $this->nec_referencia = $datos['nec_referencia'] ?? '';
        $this->nombre = mb_convert_case(trim($datos['nombre'] ?? ''), MB_CASE_TITLE, "UTF-8");
        //ejecutamos la accion enviada por el controlador
        $accion = $datos['accion'] ?? null;
        return match ($accion) {
            'incluir'   => $this->Incluir(),
            'eliminar'  => $this->Eliminar(),
            'buscar' => $this->Buscar(),
            'modificar' => $this->Modificar(),
            'bloquear' => $this->Bloquear(),
            default => throw new Exception('La accion no es valida')
        };
    }

    public function Consultar(array $filtro = []): array
    {
        try {
            $conex = $this->conex();
            $params = []; // Unificamos el nombre de la variable

            // 1. Iniciamos la sentencia con WHERE 1=1 para concatenar AND tranquilamente
            $sentencia = "SELECT * FROM metodos_pago WHERE 1=1";

            // 2. BUSCADOR GENERAL (El que viene del keyup)
            if (!empty($filtro['filtro'])) {
                $p = "%" . $filtro['filtro'] . "%";
                $sentencia .= " AND (
                nombre LIKE :f1 
            )";
                $params[':f1'] = $p;
                $params[':f2'] = $p;
                $params[':f3'] = $p;
            }

            // 3. FILTROS ESPECÍFICOS (Si vienen del Modal o propiedades del objeto)

            if (!empty($this->nombre)) {
                $sentencia .= " AND nombre LIKE :nombre";
                $params[':nombre'] = "%" . trim($this->nombre) . "%";
            }

            // 4. Orden (Asegúrate de usar una columna que exista, como id_metodos)
            $sentencia .= " ORDER BY codigo_metodo ASC";

            $stmt = $conex->prepare($sentencia);

            // IMPORTANTE: Pasar los parámetros al execute
            $stmt->execute($params);

            $datos = $stmt->fetchAll();

            return array('accion' => 'consultar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('metodos_pago', $e->getMessage(), 'Modelo_Consultar');
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
            if ($this->verificarExistencia('nombre', $this->nombre, 'metodos_pago', NULL, bloquear: true)) {
                throw new Exception(DUPLICATE_NAME);
            }

            $sentencia = "INSERT INTO metodos_pago (`nombre`, `nec_referencia`) VALUES (:nombre, :nec_referencia)";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':nec_referencia', $this->nec_referencia);
            $stmt->execute();

            $conex->commit();
            return array('accion' => 'exito');
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollback();
            }
            logs('Metodos_Pago', $e->getMessage(), 'Modelo');
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
            if (!$this->verificarExistenciaPropia('nombre', $this->nombre, $this->id, 'metodos_pago', NULL, bloquear: true)) {
                if ($this->verificarExistencia('nombre', $this->nombre, 'metodos_pago', NULL, bloquear: true)) {
                    throw new Exception(DUPLICATE_NAME); // CORREGIDO: Todo en una sola línea
                }
            }

            $sentencia = "UPDATE metodos_pago SET 
            nombre = :nombre, 
            nec_referencia = :nec_referencia
            WHERE codigo_metodo = :id_metodos"; 
            
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':nec_referencia', $this->nec_referencia);
            $stmt->bindParam(':id_metodos', $this->id);
            $stmt->execute();

            $conex->commit();
            return array('accion' => 'exito');
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollback();
            }
            logs('metodos_pago', $e->getMessage(), 'Modelo_Modificar');
            return array('accion' => 'error', 'codigo' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    public function Buscar(): array
    {
        try {
            $conex = $this->conex();
            $sentencia = "SELECT * FROM metodos_pago WHERE codigo_metodo = :id";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            return array('accion' => 'buscar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('metodos_pago', $e->getMessage(), 'Modelo');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    public function ConsultarMetodos(): array
    {
        try {
            $conex = $this->conex();
            $sentencia = "SELECT * FROM metodos_pago WHERE estatus = 1";
            $stmt = $conex->prepare($sentencia);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            return array('accion' => 'buscar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('metodos_pago', $e->getMessage(), 'Modelo');
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
            if (!$this->verificarExistencia('id', $this->id, 'metodos_pago', NULL, bloquear:true)) {
                throw new Exception(INVALID_ID);
            }
            if ($this->verificarExistencia('id', $this->id, 'pagos', NULL, bloquear:true)) {
                throw new Exception(ASSOCIATES);
            }
            $sentencia = "DELETE FROM metodos_pago WHERE codigo_metodo = :id";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
            $conex->commit();
            return array('accion' => 'exito');
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollback();
            }
            logs('metodos_pago', $e->getMessage(), 'Modelo_Eliminar');
            return array('accion' => 'error', 'codigo' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    private function Bloquear(): array
    {
        try {
            $conex = $this->conex();
            $conex->beginTransaction();

            if (!$this->verificarExistencia('id', $this->id, 'metodos_pago', NULL, bloquear: true)) {
                throw new Exception(INVALID_ID);
            }

            $nuevoEstado = ($this->bloqueo == 1) ? 2 : 1;

            $sql = "UPDATE `metodos_pago` SET `estatus` = :estado WHERE codigo_metodo = :id";
            $stmt = $conex->prepare($sql);

            $stmt->execute([
                ':estado' => $nuevoEstado,
                ':id' => $this->id
            ]);


            $conex->commit();
            return ['accion' => 'exito'];
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            logs('metodos_pago', $e->getMessage(), 'Modelo_Bloquear');
            return ['accion' => 'error', 'codigo' => $e->getMessage()];
        } finally {
            $conex = null;
        }
    }
}