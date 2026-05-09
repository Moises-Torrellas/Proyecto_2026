<?php

namespace App\modelo;

use App\modelo\ModeloBase;
use Exception;

class ModeloCuentasCobrar extends ModeloBase
{
    private $id;
    private $id_concepto;
    private $id_atleta;
    private $monto_personalizado; // Adaptado a tu DB
    private $fecha_emision;
    private $fecha_vencimiento;   // Adaptado a tu DB
    private $estatus;

    public function __construct()
    {
        parent::__construct();
        $this->campoWhitelist = [
            'id' => 'id_cobrar',
            'id_cobrar' => 'id_cobrar', 
            'id_concepto' => 'id_concepto',
            'id_conceptos' => 'id_conceptos', 
            'id_atleta' => 'id_atleta',
            'estatus' => 'estatus'
        ];
        $this->llavePrimaria = 'id_cobrar';
    }

    public function ProcesarDatos(array $datos): array
    {
        if (empty($datos)) {
            throw new Exception('No se proporcionaron datos para procesar.');
        }

        $this->id = $datos['id'] ?? null;
        $this->id_concepto = $datos['id_concepto'] ?? null;
        $this->id_atleta = $datos['id_atleta'] ?? null;
        
        // TRUCO: Tomamos el 'monto_total' del JS y lo asignamos al 'monto_personalizado' de la DB
        $this->monto_personalizado = $datos['monto_total'] ?? 0;
        
        $this->fecha_emision = $datos['fecha_emision'] ?? date('Y-m-d H:i:s');
        
        // Como el JS no manda fecha de vencimiento, le sumamos 30 días a la emisión por defecto
        $this->fecha_vencimiento = date('Y-m-d H:i:s', strtotime($this->fecha_emision . ' + 30 days'));

        $this->estatus = mb_convert_case(trim($datos['estatus'] ?? 'Pendiente'), MB_CASE_TITLE, "UTF-8");

        $accion = $datos['accion'] ?? null;
        return match ($accion) {
            'incluir'   => $this->Incluir(),
            'eliminar'  => $this->Eliminar(),
            'buscar'    => $this->Buscar(),
            'modificar' => $this->Modificar(),
            default => throw new Exception('La accion no es valida')
        };
    }

public function Consultar(array $filtro = []): array
    {
        try {
            $conex = $this->conex();
            $params = []; 

            // TRUCO: Usamos un IF en el SQL para que el 0 siempre se lea como 'Pendiente' en el JS
            $sentencia = "SELECT c.*, 
                                 c.monto_personalizado as monto_total, 
                                 c.monto_personalizado as monto_pendiente,
                                 IF(c.estatus = '0', 'Pendiente', c.estatus) as estatus,
                                 a.nombres as atleta_nombre, 
                                 a.apellidos as atleta_apellido, 
                                 co.nombre as concepto_nombre 
                          FROM cuentas_cobrar c 
                          INNER JOIN atletas a ON c.id_atleta = a.id_atleta 
                          INNER JOIN conceptos co ON c.id_concepto = co.id_conceptos 
                          WHERE 1=1";

            if (!empty($filtro['filtro'])) {
                $p = "%" . $filtro['filtro'] . "%";
                $sentencia .= " AND (
                    a.nombres LIKE :f1 OR 
                    a.apellidos LIKE :f2 OR 
                    co.nombre LIKE :f3
                )";
                $params[':f1'] = $p;
                $params[':f2'] = $p;
                $params[':f3'] = $p;
            }

            $sentencia .= " ORDER BY c.fecha_emision DESC";

            $stmt = $conex->prepare($sentencia);
            $stmt->execute($params);
            $datos = $stmt->fetchAll();

            return array('accion' => 'consultar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('CuentasCobrar', $e->getMessage(), 'Modelo_Consultar');
            return array('accion' => 'error');
        } finally {
            $conex = NULL;
        }
    }

    public function ConsultarAtletas(): array
    {
        try {
            $conex = $this->conex();
            $sentencia = "SELECT id_atleta, nombres as nombre, apellidos as apellido FROM atletas ORDER BY nombres ASC";
            $stmt = $conex->prepare($sentencia);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            return array('accion' => 'consultarA', 'datos' => $datos);
        } catch (Exception $e) {
            logs('CuentasCobrar', $e->getMessage(), 'Modelo_ConsultarAtletas');
            return array('accion' => 'error', 'mensaje' => 'Error al cargar los atletas.');
        } finally {
            $conex = NULL;
        }
    }

    public function ConsultarConceptos(): array
    {
        try {
            $conex = $this->conex();
            $sentencia = "SELECT id_conceptos as id_concepto, nombre FROM conceptos ORDER BY nombre ASC";
            $stmt = $conex->prepare($sentencia);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            return array('accion' => 'consultarCo', 'datos' => $datos);
        } catch (Exception $e) {
            logs('CuentasCobrar', $e->getMessage(), 'Modelo_ConsultarConceptos');
            return array('accion' => 'error', 'mensaje' => 'Error al cargar los conceptos.');
        } finally {
            $conex = NULL;
        }
    }

    private function Incluir(): array
    {
        try {
            $conex = $this->conex();
            $conex->beginTransaction();

            if (!$this->verificarExistencia('id_atleta', $this->id_atleta, 'atletas', NULL, bloquear: true)) {
                throw new Exception("El atleta seleccionado no existe en el sistema.");
            }
            
            if (!$this->verificarExistencia('id_conceptos', $this->id_concepto, 'conceptos', NULL, bloquear: true)) {
                throw new Exception("El concepto de cobro seleccionado no existe.");
            }

            // CORRECCIÓN: Usamos las columnas reales de TU base de datos
            $sentencia = "INSERT INTO cuentas_cobrar (`id_concepto`, `id_atleta`, `monto_personalizado`, `fecha_emision`, `fecha_vencimiento`, `estatus`) 
                          VALUES (:id_concepto, :id_atleta, :monto_personalizado, :fecha_emision, :fecha_vencimiento, :estatus)";
            
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id_concepto', $this->id_concepto);
            $stmt->bindParam(':id_atleta', $this->id_atleta);
            $stmt->bindParam(':monto_personalizado', $this->monto_personalizado);
            $stmt->bindParam(':fecha_emision', $this->fecha_emision);
            $stmt->bindParam(':fecha_vencimiento', $this->fecha_vencimiento);
            $stmt->bindParam(':estatus', $this->estatus);
            $stmt->execute();

            $conex->commit();
            return array('accion' => 'exito');
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollback();
            }
            logs('CuentasCobrar', $e->getMessage(), 'Modelo_Incluir');
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

            if (!$this->verificarExistencia('id_cobrar', $this->id, 'cuentas_cobrar', NULL, bloquear: true)) {
                throw new Exception(INVALID_ID);
            }

            // CORRECCIÓN: Actualizado a las columnas reales
            $sentencia = "UPDATE cuentas_cobrar SET 
                          id_concepto = :id_concepto, 
                          id_atleta = :id_atleta, 
                          monto_personalizado = :monto_personalizado, 
                          estatus = :estatus 
                          WHERE id_cobrar = :id_cobrar";
                          
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id_concepto', $this->id_concepto);
            $stmt->bindParam(':id_atleta', $this->id_atleta);
            $stmt->bindParam(':monto_personalizado', $this->monto_personalizado);
            $stmt->bindParam(':estatus', $this->estatus);
            $stmt->bindParam(':id_cobrar', $this->id);
            $stmt->execute();

            $conex->commit();
            return array('accion' => 'exito');
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollback();
            }
            logs('CuentasCobrar', $e->getMessage(), 'Modelo_Modificar');
            return array('accion' => 'error', 'codigo' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    function Buscar(): array
    {
        try {
            $conex = $this->conex();
            
            // TRUCO: Le mandamos los alias al buscador también para que el modal se llene bien
            $sentencia = "SELECT *, monto_personalizado as monto_total, monto_personalizado as monto_pendiente FROM cuentas_cobrar WHERE id_cobrar = :id";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            return array('accion' => 'buscar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('CuentasCobrar', $e->getMessage(), 'Modelo_Buscar');
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
        
        if (!$this->verificarExistencia('id_cobrar', $this->id, 'cuentas_cobrar', NULL, bloquear: true)) {
            throw new Exception(INVALID_ID);
        }
        
        // Verificamos si ya tiene pagos. Si tiene pagos, NO se debería poder anular
        // sin antes anular los pagos asociados.
        if ($this->verificarExistencia('id_cobrar', $this->id, 'pagos', NULL, bloquear: true)) {
            throw new Exception("No se puede anular un cargo que ya tiene pagos registrados."); 
        }

        // CAMBIO: En lugar de DELETE, hacemos un UPDATE del estatus
        $sentencia = "UPDATE cuentas_cobrar SET estatus = 'Anulado' WHERE id_cobrar = :id";
        $stmt = $conex->prepare($sentencia);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        
        $conex->commit();
        return array('accion' => 'exito');
    } catch (Exception $e) {
        if ($conex && $conex->inTransaction()) {
            $conex->rollback();
        }
        logs('CuentasCobrar', $e->getMessage(), 'Modelo_Anular');
        return array('accion' => 'error', 'codigo' => $e->getMessage());
    } finally {
        $conex = NULL;
    }
}
}