<?php

namespace App\modelo;

use App\modelo\ModeloBase;
use Exception;
use PDO;

class ModeloDevoluciones extends ModeloBase
{
    private $id_devolucion;
    private $id_asignacion;
    private $id_estado;
    private $fecha_devolucion;
    private $observacion;
    private $motivo; 

    public function __construct()
    {
        parent::__construct();
    
        $this->campoWhitelist = [
            'id_devolucion'    => 'id_devolucion',
            'id_asignacion'    => 'id_asignacion',
            'id_estado'        => 'id_estado',
            'fecha_devolucion' => 'fecha_devolucion',
            'observacion'      => 'observacion',
            'motivo'           => 'motivo'
        ];
        
        $this->llavePrimaria = 'id_devolucion';
    }

    public function ProcesarDatos(array $datos): array
    {
        if (empty($datos)) {
            throw new Exception('No se proporcionaron datos para procesar la devolución.');
        }

        $this->id_devolucion    = $datos['id_devolucion'] ?? null;
        $this->id_asignacion    = $datos['id_asignacion'] ?? null;
        $this->id_estado        = $datos['id_estado'] ?? null;
        $this->fecha_devolucion = $datos['fecha_devolucion'] ?? null; 
        $this->observacion      = isset($datos['observacion']) ? trim($datos['observacion']) : '';
        $this->motivo           = isset($datos['motivo']) ? trim($datos['motivo']) : '';

        $accion = $datos['accion'] ?? null;

        return match ($accion) {
            'consultar' => $this->ConsultarDevoluciones(),
            'incluir'   => $this->IncluirDevolucion(),
            'modificar' => $this->ModificarDevolucion(),
            'anular'    => $this->AnularDevolucion(),
            'generar'   => $this->ConsultarDevoluciones($datos), 
            default     => throw new Exception('La acción solicitada para la devolución no es válida.')
        };
    }

    public function ConsultarDevoluciones(array $filtro = []): array
    {
        $conex = null;
        try {
            $conex = $this->conex();
            $params = [];
            
            // Agregamos WHERE 1=1 para concatenar filtros dinámicos fácilmente
            $sql = "SELECT d.id_devolucion, 
                           DATE_FORMAT(d.fecha_devolucion, '%d/%m/%Y') as fecha_vista,
                           d.fecha_devolucion,
                           d.id_asignacion,
                           d.id_estado,
                           d.observacion, 
                           CONCAT(IFNULL(at.nombres, ''), ' ', IFNULL(at.apellidos, '')) as asignaciones,
                           IFNULL(ee.nombre, 'Sin especificar') as articulo
                    FROM devoluciones d
                    LEFT JOIN asignaciones a ON d.id_asignacion = a.id_asignacion
                    LEFT JOIN atletas at ON a.id_atleta = at.id_atleta
                    LEFT JOIN estado_equipamiento ee ON d.id_estado = ee.id_estado
                    WHERE 1=1"; 
            
            // Filtros opcionales para el reporte
            if (!empty($filtro['fecha_devolucion'])) {
                $sql .= " AND DATE(d.fecha_devolucion) = :fecha_devolucion";
                $params[':fecha_devolucion'] = $filtro['fecha_devolucion'];
            }
            if (!empty($filtro['id_asignacion'])) {
                $sql .= " AND d.id_asignacion = :id_asignacion";
                $params[':id_asignacion'] = $filtro['id_asignacion'];
            }
            if (!empty($filtro['id_estado'])) {
                $sql .= " AND d.id_estado = :id_estado";
                $params[':id_estado'] = $filtro['id_estado'];
            }

            $sql .= " ORDER BY d.fecha_devolucion DESC";
            
            $stmt = $conex->prepare($sql);
            $stmt->execute($params);
            $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ['accion' => 'consultar', 'datos' => $datos];
        } catch (Exception $e) {
            logs('Devoluciones', $e->getMessage(), 'Modelo_Consultar');
            return ['accion' => 'error', 'mensaje' => 'Error BD: ' . $e->getMessage()];
        } finally {
            $conex = null;
        }
    }

    private function IncluirDevolucion(): array
    {
        $conex = null;
        try {
            $conex = $this->conex();
            $conex->beginTransaction();

            $sqlInsert = "INSERT INTO devoluciones (id_asignacion, id_estado, fecha_devolucion, observacion) VALUES (?, ?, ?, ?)";
            $stmtInsert = $conex->prepare($sqlInsert);
            $stmtInsert->execute([$this->id_asignacion, $this->id_estado, $this->fecha_devolucion, $this->observacion]);

            $conex->commit();
            return ['accion' => 'exito', 'mensaje' => 'Devolución procesada correctamente.'];
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            logs('Devoluciones', $e->getMessage(), 'Modelo_Incluir');
            return ['accion' => 'error', 'codigo' => $e->getMessage()];
        } finally {
            $conex = null;
        }
    }

    private function ModificarDevolucion(): array
    {
        $conex = null;
        try {
            $conex = $this->conex();
            $conex->beginTransaction();

            $sqlUpdate = "UPDATE devoluciones SET id_asignacion = ?, id_estado = ?, fecha_devolucion = ?, observacion = ? WHERE id_devolucion = ?";
            $stmtUpdate = $conex->prepare($sqlUpdate);
            $stmtUpdate->execute([$this->id_asignacion, $this->id_estado, $this->fecha_devolucion, $this->observacion, $this->id_devolucion]);

            $conex->commit();
            return ['accion' => 'exito', 'mensaje' => 'Modificación de devolución exitosa.'];
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            logs('Devoluciones', $e->getMessage(), 'Modelo_Modificar');
            return ['accion' => 'error', 'codigo' => $e->getMessage()];
        } finally {
            $conex = null;
        }
    }

    private function AnularDevolucion(): array
    {
        $conex = null;
        try {
            $conex = $this->conex();
            $conex->beginTransaction();

            // Borrado físico real en la tabla
            $stmtAnular = $conex->prepare("DELETE FROM devoluciones WHERE id_devolucion = ?");
            $stmtAnular->execute([$this->id_devolucion]);

            $conex->commit();
            return ['accion' => 'exito', 'mensaje' => 'Devolución eliminada correctamente.'];
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            logs('Devoluciones', $e->getMessage(), 'Modelo_Anular');
            return ['accion' => 'error', 'codigo' => $e->getMessage()];
        } finally {
            $conex = null;
        }
    }
}