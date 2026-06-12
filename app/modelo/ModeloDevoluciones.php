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

    public function ProcesarDatos(array $datos): array
    {
        if (empty($datos)) return ['accion' => 'error', 'codigo' => VALIDATION];

        $this->id_devolucion    = $datos['id_devolucion'] ?? null;
        $this->id_asignacion    = $datos['id_asignacion'] ?? null;
        $this->id_estado        = $datos['id_estado'] ?? null;
        $this->fecha_devolucion = $datos['fecha_devolucion'] ?? null; 
        $this->observacion      = isset($datos['observacion']) ? trim($datos['observacion']) : '';

        return match ($datos['accion'] ?? null) {
            'consultar' => $this->ConsultarDevoluciones(),
            'generar'   => $this->ConsultarDevoluciones($datos),
            'incluir'   => $this->IncluirDevolucion(),
            'modificar' => $this->ModificarDevolucion(),
            'anular'    => $this->AnularDevolucion($datos['motivo_anulacion'] ?? 'Sin motivo'),
            default     => ['accion' => 'error', 'codigo' => VALIDATION]
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
                        d.fecha_devolucion,
                        d.id_asignacion,
                        d.id_estado,
                        d.observacion, 
                        ee.nombre as calidad,
                        at.id_atleta,
                        at.nombres as atleta_nombre,
                        at.apellidos as atleta_apellido,
                        cat.nombre as articulo_nombre,
                        (SELECT COUNT(*) 
                         FROM devoluciones d2 
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

            if (!empty($filtros['id_asignacion'])) {
                $sql .= " AND d.id_asignacion = ? ";
                $params[] = $filtros['id_asignacion'];
            }
            if (!empty($filtros['id_estado'])) {
                $sql .= " AND d.id_estado = ? ";
                $params[] = $filtros['id_estado'];
            }
            if (!empty($filtros['fecha_devolucion'])) {
                $sql .= " AND d.fecha_devolucion = ? ";
                $params[] = $filtros['fecha_devolucion'];
            }

            $sql .= " ORDER BY at.id_atleta ASC, d.fecha_devolucion DESC";

            $stmt = $conex->prepare($sql);
            $stmt->execute($params);
            $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ['accion' => 'consultar', 'datos' => $datos];
        } catch (Exception $e) {
            logs('Devoluciones', $e->getMessage(), 'Modelo_Consultar');
            return ['accion' => 'error', 'codigo' => DB_CONNECTION];
        } finally {
            $conex = null;
        }
    }

    private function IncluirDevolucion(): array
    {
        $conex = null;
        try {
            $conex = $this->conex();
            // INICIA TRANSACCIÓN
            $conex->beginTransaction();

            // 1. Registra la devolución
            $sqlInsert = "INSERT INTO devoluciones (id_asignacion, id_estado, fecha_devolucion, observacion) VALUES (?, ?, ?, ?)";
            $stmtInsert = $conex->prepare($sqlInsert);
            $stmtInsert->execute([$this->id_asignacion, $this->id_estado, $this->fecha_devolucion, $this->observacion]);

            // 2. Busca el equipo vinculado a la asignación
            $stmtEq = $conex->prepare("SELECT id_equipamiento FROM asignaciones WHERE id_asignacion = ?");
            $stmtEq->execute([$this->id_asignacion]);
            $idEquipamiento = $stmtEq->fetchColumn();

            if ($idEquipamiento) {
                // 3. Cierra la asignación marcándola como devuelta (Ej: estatus 2)
                $stmtAsig = $conex->prepare("UPDATE asignaciones SET estatus = 2 WHERE id_asignacion = ?");
                $stmtAsig->execute([$this->id_asignacion]);

                // 4. Libera el equipo (Ej: estatus 1) y actualiza su condición física (id_estados)
                $stmtEqUpd = $conex->prepare("UPDATE equipamientos SET estatus = 1, id_estados = ? WHERE id_equipamiento = ?");
                $stmtEqUpd->execute([$this->id_estado, $idEquipamiento]);
            }

            // APLICA TODOS LOS CAMBIOS DE GOLPE
            $conex->commit();
            return ['accion' => 'exito'];
        } catch (Exception $e) {
            // SI HAY ERROR, DESHACE TODO
            if ($conex && $conex->inTransaction()) $conex->rollBack();
            logs('Devoluciones', $e->getMessage(), 'Modelo_Incluir');
            return ['accion' => 'error', 'codigo' => DB_CONNECTION];
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

            $stmtEq = $conex->prepare("SELECT id_equipamiento FROM asignaciones WHERE id_asignacion = ?");
            $stmtEq->execute([$this->id_asignacion]);
            $idEquipamiento = $stmtEq->fetchColumn();

            if ($idEquipamiento) {
                // ACTUALIZA LA NUEVA CALIDAD AL EQUIPO POR SI SE EQUIVOCARON AL REGISTRAR
                $stmtEqUpd = $conex->prepare("UPDATE equipamientos SET id_estados = ? WHERE id_equipamiento = ?");
                $stmtEqUpd->execute([$this->id_estado, $idEquipamiento]);
            }

            $conex->commit();
            return ['accion' => 'exito'];
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) $conex->rollBack();
            logs('Devoluciones', $e->getMessage(), 'Modelo_Modificar');
            return ['accion' => 'error', 'codigo' => DB_CONNECTION];
        } finally {
            $conex = null;
        }
    }

    private function AnularDevolucion($motivo): array
    {
        $conex = null;
        try {
            $conex = $this->conex();
            // INICIA TRANSACCIÓN INVERSA PARA ANULAR
            $conex->beginTransaction();

            $stmtAsigOriginal = $conex->prepare("SELECT id_asignacion FROM devoluciones WHERE id_devolucion = ?");
            $stmtAsigOriginal->execute([$this->id_devolucion]);
            $idAsigGuardada = $stmtAsigOriginal->fetchColumn();

            $idEquipamiento = null;
            if ($idAsigGuardada) {
                $stmtEq = $conex->prepare("SELECT id_equipamiento FROM asignaciones WHERE id_asignacion = ?");
                $stmtEq->execute([$idAsigGuardada]);
                $idEquipamiento = $stmtEq->fetchColumn();
            }

            // BORRA LA DEVOLUCIÓN
            $stmtAnular = $conex->prepare("DELETE FROM devoluciones WHERE id_devolucion = ?");
            $stmtAnular->execute([$this->id_devolucion]);

            if ($idAsigGuardada && $idEquipamiento) {
                // ABRE LA ASIGNACIÓN NUEVAMENTE (estatus 1)
                $stmtRevAsig = $conex->prepare("UPDATE asignaciones SET estatus = 1 WHERE id_asignacion = ?");
                $stmtRevAsig->execute([$idAsigGuardada]);

                // BLOQUEA EL EQUIPO NUEVAMENTE COMO ASIGNADO (estatus 2)
                $stmtRevEq = $conex->prepare("UPDATE equipamientos SET estatus = 2 WHERE id_equipamiento = ?");
                $stmtRevEq->execute([$idEquipamiento]);
            }

            $conex->commit();
            return ['accion' => 'exito', 'motivo' => $motivo];
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) $conex->rollBack();
            logs('Devoluciones', $e->getMessage(), 'Modelo_Anular');
            return ['accion' => 'error', 'codigo' => DB_CONNECTION];
        } finally {
            $conex = null;
        }
    }
}