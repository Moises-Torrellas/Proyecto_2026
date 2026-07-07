<?php

namespace App\modelo;

use Exception;
use PDO;

class ModeloDevoluciones extends Conexion
{
    private $id_devolucion;
    private $id_asignacion;
    private $id_estado;
    private $fecha_devolucion;
    private $observacion;

    private $objAsignaciones;
    private $objEquipamientos;

    public function __construct()
    {
        parent::__construct();
        $this->campoWhitelist = [
            'id_devolucion'    => 'id_devolucion',
            'id_asignacion'    => 'id_asignacion',
            'id_estado'        => 'id_estado',
            'fecha_devolucion' => 'fecha_devolucion',
            'observacion'      => 'observacion'
        ];
        $this->llavePrimaria = 'id_devolucion';
    }

    public function setAsignaciones(ModeloAsignaciones $asig) { 
        $this->objAsignaciones = $asig; 
    }
    
    public function setEquipamientos(ModeloArticulosInventario $equip) { 
        $this->objEquipamientos = $equip; 
    }

    public function ProcesarDatos(array $datos): array
    {
        $this->id_devolucion    = $datos['id_devolucion'] ?? null;
        $this->id_asignacion    = $datos['id_asignacion'] ?? null;
        $this->id_estado        = $datos['id_estado'] ?? null;
        $this->fecha_devolucion = $datos['fecha_devolucion'] ?? null; 
        $this->observacion      = isset($datos['observacion']) ? trim($datos['observacion']) : '';

        return match ($datos['accion'] ?? null) {
            'consultar' => $this->ConsultarDevoluciones($datos),
            'generar'   => $this->ConsultarDevoluciones($datos),
            'incluir'   => $this->IncluirDevolucion(),
            'modificar' => $this->ModificarDevolucion(),
            'anular'    => $this->AnularDevolucion($datos['motivo_anulacion'] ?? 'Sin motivo'),
            default     => ['accion' => 'error', 'codigo' => 'Acción no válida']
        };
    }

    public function ConsultarDevoluciones(array $filtros = []): array
    {
        $conex = null;
        try {
            $conex = $this->conex();
            
            $sql = "SELECT 
                        d.id_devolucion, 
                        DATE_FORMAT(d.fecha_devolucion, '%Y-%m-%d') as fecha_vista,
                        d.fecha_devolucion, d.id_asignacion, d.id_estado, d.observacion, 
                        ee.nombre as calidad, ee.nivel_estado, at.codigo_atleta, at.p_nombre as atleta_nombre,
                        at.p_apellidos as atleta_apellido, cat.nombre as articulo_nombre,
                        (SELECT COUNT(*) FROM devoluciones d2 
                         INNER JOIN asignaciones a2 ON d2.id_asignacion = a2.id_asignacion 
                         WHERE a2.codigo_atleta = at.codigo_atleta) as total_devoluciones_atleta
                    FROM devoluciones d
                    INNER JOIN asignaciones asig ON d.id_asignacion = asig.id_asignacion
                    INNER JOIN atletas at ON asig.codigo_atleta = at.codigo_atleta
                    INNER JOIN estado_fisico ee ON d.id_estado = ee.id_estado
                    INNER JOIN articulos_inventario eq ON asig.codigo_articulo = eq.codigo_articulo
                    INNER JOIN catalogo cat ON eq.id_catalogo = cat.id_catalogo
                    WHERE 1=1 "; 
            
            $params = [];
            if (!empty($filtros['id_asignacion'])) { $sql .= " AND d.id_asignacion = ? "; $params[] = $filtros['id_asignacion']; }
            if (!empty($filtros['id_estado'])) { $sql .= " AND d.id_estado = ? "; $params[] = $filtros['id_estado']; }
            if (!empty($filtros['fecha_devolucion'])) { $sql .= " AND d.fecha_devolucion = ? "; $params[] = $filtros['fecha_devolucion']; }
            
            $sql .= " ORDER BY at.codigo_atleta ASC, d.fecha_devolucion DESC";

            $stmt = $conex->prepare($sql);
            $stmt->execute($params);
            return ['accion' => 'consultar', 'datos' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (Exception $e) {
            return ['accion' => 'error', 'codigo' => $e->getMessage()];
        }
    }

    // ====================================================================
    // MÉTODOS DE VALIDACIÓN LÓGICA
    // ====================================================================

    private function VerificarExistenciaAsignacion($id_asignacion, $conex): bool
    {
        // Verifica si la asignación existe y sigue prestada (estatus 1)
        $stmt = $conex->prepare("SELECT 1 FROM asignaciones WHERE id_asignacion = ? AND estatus = 1");
        $stmt->execute([$id_asignacion]);
        return $stmt->fetchColumn() !== false; 
    }

    private function VerificarExistenciaDevolucion($id_devolucion, $conex): bool
    {
        // Verifica si la devolución realmente existe
        $stmt = $conex->prepare("SELECT 1 FROM devoluciones WHERE id_devolucion = ?");
        $stmt->execute([$id_devolucion]);
        return $stmt->fetchColumn() !== false; 
    }

    private function IncluirDevolucion(): array
    {
        $conex = null;
        try {
            $conex = $this->conex();
            
            if (!$this->VerificarExistenciaAsignacion($this->id_asignacion, $conex)) {
                throw new Exception("La asignación no existe, no es válida o ya fue devuelta.");
            }

            $conex->beginTransaction();

            $stmtAsig = $conex->prepare("SELECT codigo_articulo FROM asignaciones WHERE id_asignacion = ? FOR UPDATE");
            $stmtAsig->execute([$this->id_asignacion]);
            $codigoArticulo = $stmtAsig->fetchColumn();

            if ($this->objAsignaciones) {
                $this->objAsignaciones->CambiarEstatusAsignacion($this->id_asignacion, 0, $conex);
            } else {
                $conex->prepare("UPDATE asignaciones SET estatus = 0 WHERE id_asignacion = ?")->execute([$this->id_asignacion]);
            }

            $stmtEqUpd = $conex->prepare("UPDATE articulos_inventario SET estatus = 1, id_estado = ? WHERE codigo_articulo = ?");
            $stmtEqUpd->execute([$this->id_estado, $codigoArticulo]);

            $stmtInsert = $conex->prepare("INSERT INTO devoluciones (id_asignacion, id_estado, fecha_devolucion, observacion) VALUES (?, ?, ?, ?)");
            $stmtInsert->execute([$this->id_asignacion, $this->id_estado, $this->fecha_devolucion, $this->observacion]);

            $conex->commit();
            return ['accion' => 'exito', 'mensaje' => 'Devolución procesada y equipo liberado.'];

        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            return ['accion' => 'error', 'mensaje' => $e->getMessage()];
        } finally {
            $conex = null;
        }
    }

    private function ModificarDevolucion(): array
    {
        $conex = null;
        try {
            $conex = $this->conex();

            if (!$this->VerificarExistenciaDevolucion($this->id_devolucion, $conex)) {
                throw new Exception("El registro de devolución que intenta modificar no existe.");
            }

            $conex->beginTransaction();

            $stmtEq = $conex->prepare("SELECT a.codigo_articulo, d.id_estado FROM devoluciones d INNER JOIN asignaciones a ON d.id_asignacion = a.id_asignacion WHERE d.id_devolucion = ? FOR UPDATE");
            $stmtEq->execute([$this->id_devolucion]);
            $datosViejos = $stmtEq->fetch(PDO::FETCH_ASSOC);

            $stmtUpdate = $conex->prepare("UPDATE devoluciones SET fecha_devolucion = ?, id_estado = ?, observacion = ? WHERE id_devolucion = ?");
            $stmtUpdate->execute([$this->fecha_devolucion, $this->id_estado, $this->observacion, $this->id_devolucion]);

            if ($datosViejos && $datosViejos['id_estado'] != $this->id_estado) {
                $stmtEqUpd = $conex->prepare("UPDATE articulos_inventario SET id_estado = ? WHERE codigo_articulo = ?");
                $stmtEqUpd->execute([$this->id_estado, $datosViejos['codigo_articulo']]);
            }

            $conex->commit();
            return ['accion' => 'exito', 'mensaje' => 'Modificación exitosa.'];
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            return ['accion' => 'error', 'mensaje' => $e->getMessage()];
        } finally {
            $conex = null;
        }
    }

    private function AnularDevolucion($motivo): array
    {
        $conex = null;
        try {
            $conex = $this->conex();
            
            if (!$this->VerificarExistenciaDevolucion($this->id_devolucion, $conex)) {
                throw new Exception("El registro que intenta anular ya no existe en el sistema.");
            }

            $conex->beginTransaction();

            $stmtAsig = $conex->prepare("SELECT id_asignacion FROM devoluciones WHERE id_devolucion = ? FOR UPDATE");
            $stmtAsig->execute([$this->id_devolucion]);
            $idAsig = $stmtAsig->fetchColumn();

            $stmtEq = $conex->prepare("SELECT codigo_articulo FROM asignaciones WHERE id_asignacion = ? FOR UPDATE");
            $stmtEq->execute([$idAsig]);
            $codigoArticulo = $stmtEq->fetchColumn();

            $conex->prepare("DELETE FROM devoluciones WHERE id_devolucion = ?")->execute([$this->id_devolucion]);

            if ($this->objAsignaciones) {
                $this->objAsignaciones->CambiarEstatusAsignacion($idAsig, 1, $conex);
            } else {
                $conex->prepare("UPDATE asignaciones SET estatus = 1 WHERE id_asignacion = ?")->execute([$idAsig]);
            }

            $conex->prepare("UPDATE articulos_inventario SET estatus = 2 WHERE codigo_articulo = ?")->execute([$codigoArticulo]);

            $conex->commit();
            return ['accion' => 'exito', 'mensaje' => 'Anulación procesada, equipo reasignado.'];
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            return ['accion' => 'error', 'mensaje' => $e->getMessage()];
        } finally {
            $conex = null;
        }
    }
}