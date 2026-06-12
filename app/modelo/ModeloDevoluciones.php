<?php

namespace App\modelo;

use App\modelo\ModeloBase;
use App\modelo\ModeloAsignaciones;
use App\modelo\ModeloEquipamientos;
use Exception;
use PDO;

class ModeloDevoluciones extends ModeloBase
{
    private $id_devolucion;
    private $id_asignacion;
    private $id_estado;
    private $fecha_devolucion;
    private $observacion;

    // Herramientas inyectadas
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

    // SETTERS PARA LA INYECCIÓN DE DEPENDENCIAS
    public function setAsignaciones(ModeloAsignaciones $asig) { 
        $this->objAsignaciones = $asig; 
    }
    
    public function setEquipamientos(ModeloEquipamientos $equip) { 
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
                        ee.nombre as calidad, at.id_atleta, at.nombres as atleta_nombre,
                        at.apellidos as atleta_apellido, cat.nombre as articulo_nombre,
                        (SELECT COUNT(*) FROM devoluciones d2 
                         INNER JOIN asignaciones a2 ON d2.id_asignacion = a2.id_asignacion 
                         WHERE a2.id_atleta = at.id_atleta) as total_devoluciones_atleta
                    FROM devoluciones d
                    INNER JOIN asignaciones asig ON d.id_asignacion = asig.id_asignacion
                    INNER JOIN atletas at ON asig.id_atleta = at.id_atleta
                    INNER JOIN estado_equipamiento ee ON d.id_estado = ee.id_estado
                    INNER JOIN equipamientos eq ON asig.id_equipamiento = eq.id_equipamiento
                    INNER JOIN catalogos cat ON eq.id_catalogo = cat.id_catalogo
                    WHERE 1=1 "; 
            
            $params = [];
            if (!empty($filtros['id_asignacion'])) { $sql .= " AND d.id_asignacion = ? "; $params[] = $filtros['id_asignacion']; }
            if (!empty($filtros['id_estado'])) { $sql .= " AND d.id_estado = ? "; $params[] = $filtros['id_estado']; }
            
            $sql .= " ORDER BY at.id_atleta ASC, d.fecha_devolucion DESC";

            $stmt = $conex->prepare($sql);
            $stmt->execute($params);
            return ['accion' => 'consultar', 'datos' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (Exception $e) {
            return ['accion' => 'error', 'codigo' => $e->getMessage()];
        }
    }

    private function IncluirDevolucion(): array
    {
        $conex = null;
        try {
            $conex = $this->conex();
            
            // INICIAMOS LA TRANSACCIÓN UNIFICADA
            $conex->beginTransaction();

            // 1. Buscamos el equipo y validamos que la asignación esté activa
            // Usamos FOR UPDATE para bloquear la fila y evitar que dos personas la devuelvan al mismo tiempo
            $stmtAsig = $conex->prepare("SELECT id_equipamiento FROM asignaciones WHERE id_asignacion = ? AND anulado = 0 AND estatus = 1 FOR UPDATE");
            $stmtAsig->execute([$this->id_asignacion]);
            $idEquipamiento = $stmtAsig->fetchColumn();

            if (!$idEquipamiento) {
                throw new Exception("La asignación no es válida o ya fue devuelta.");
            }

            // 2. ACTUALIZAMOS ASIGNACIONES: Usando la inyección si existe, o un plan B directo
            if ($this->objAsignaciones) {
                $this->objAsignaciones->CambiarEstatusAsignacion($this->id_asignacion, 2, $conex);
            } else {
                $conex->prepare("UPDATE asignaciones SET estatus = 2 WHERE id_asignacion = ?")->execute([$this->id_asignacion]);
            }

            // 3. ACTUALIZAMOS EQUIPAMIENTO: Lo liberamos (estatus 1) y registramos su desgaste (id_estados)
            $stmtEqUpd = $conex->prepare("UPDATE equipamientos SET estatus = 1, id_estados = ? WHERE id_equipamiento = ?");
            $stmtEqUpd->execute([$this->id_estado, $idEquipamiento]);

            // 4. REGISTRAMOS LA DEVOLUCIÓN:
            $stmtInsert = $conex->prepare("INSERT INTO devoluciones (id_asignacion, id_estado, fecha_devolucion, observacion) VALUES (?, ?, ?, ?)");
            $stmtInsert->execute([$this->id_asignacion, $this->id_estado, $this->fecha_devolucion, $this->observacion]);

            // SI TODO SALIÓ BIEN, GUARDAMOS
            $conex->commit();
            return ['accion' => 'exito', 'mensaje' => 'Devolución procesada y equipo liberado.'];

        } catch (Exception $e) {
            // SI FALLA, REVERTIMOS TODO
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
            $conex->beginTransaction();

            // Recuperamos datos históricos
            $stmtAsig = $conex->prepare("SELECT id_asignacion FROM devoluciones WHERE id_devolucion = ? FOR UPDATE");
            $stmtAsig->execute([$this->id_devolucion]);
            $idAsig = $stmtAsig->fetchColumn();

            $stmtEq = $conex->prepare("SELECT id_equipamiento FROM asignaciones WHERE id_asignacion = ? FOR UPDATE");
            $stmtEq->execute([$idAsig]);
            $idEq = $stmtEq->fetchColumn();

            if (!$idAsig || !$idEq) throw new Exception("Registro no encontrado.");

            // 1. Borramos la devolución
            $conex->prepare("DELETE FROM devoluciones WHERE id_devolucion = ?")->execute([$this->id_devolucion]);

            // 2. Revertimos Asignación (Vuelve a Activa = 1)
            if ($this->objAsignaciones) {
                $this->objAsignaciones->CambiarEstatusAsignacion($idAsig, 1, $conex);
            } else {
                $conex->prepare("UPDATE asignaciones SET estatus = 1 WHERE id_asignacion = ?")->execute([$idAsig]);
            }

            // 3. Revertimos Equipo (Vuelve a Ocupado = 2)
            $conex->prepare("UPDATE equipamientos SET estatus = 2 WHERE id_equipamiento = ?")->execute([$idEq]);

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