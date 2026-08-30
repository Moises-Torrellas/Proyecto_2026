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

    public function Buscar($id = null): array
    {
        try {
            $conex = $this->conex();
            $sentencia = "SELECT 
                            d.fecha_devolucion, d.observacion,
                            va.atleta, va.doc_identidad, va.articulo, ee.nombre as estado_fisico
                          FROM devoluciones d
                          INNER JOIN vista_asignaciones_general va ON d.id_asignacion = va.id_asignacion
                          INNER JOIN estado_fisico ee ON d.id_estado = ee.id_estado
                          WHERE d.id_devolucion = :id";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return array('accion' => 'buscar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Devoluciones', $e->getMessage(), 'Modelo_Buscar');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        }
    }

    public function ConsultarDevoluciones(array $filtros = []): array
    {
        $conex = null;
        try {
            $conex = $this->conex();
            
            $sql = "SELECT 
                        id_devolucion, 
                        DATE_FORMAT(fecha_devolucion, '%d/%m/%Y') as fecha_vista,
                        fecha_devolucion, id_asignacion, id_estado, 
                        COALESCE(NULLIF(TRIM(observacion), ''), 'Sin observaciones') as observacion, 
                        estado_fisico, nivel_estado, 
                        codigo_atleta, atleta_nombre, atleta_apellido, doc_identidad,
                        articulo_nombre, codigo_club, total_devoluciones_atleta
                    FROM vista_resumen_devoluciones
                    WHERE 1=1 "; 
            
            $params = [];
            
            if (!empty($filtros['filtro'])) {
                $sql .= " AND (atleta_nombre LIKE ? OR atleta_apellido LIKE ? OR doc_identidad LIKE ? OR articulo_nombre LIKE ? OR DATE_FORMAT(fecha_devolucion, '%d/%m/%Y') LIKE ? OR fecha_devolucion LIKE ?)";
                $p = '%' . $filtros['filtro'] . '%';
                $params = array_merge($params, [$p, $p, $p, $p, $p, $p]);
            }
            if (!empty($filtros['codigo_atleta'])) { $sql .= " AND codigo_atleta = ? "; $params[] = $filtros['codigo_atleta']; }
            if (!empty($filtros['id_asignacion'])) { $sql .= " AND id_asignacion = ? "; $params[] = $filtros['id_asignacion']; }
            if (!empty($filtros['id_estado'])) { $sql .= " AND id_estado = ? "; $params[] = $filtros['id_estado']; }
            if (!empty($filtros['fecha_desde'])) { $sql .= " AND fecha_devolucion >= ? "; $params[] = $filtros['fecha_desde'] . ' 00:00:00'; }
            if (!empty($filtros['fecha_hasta'])) { $sql .= " AND fecha_devolucion <= ? "; $params[] = $filtros['fecha_hasta'] . ' 23:59:59'; }
            
            $sql .= " ORDER BY atleta_nombre ASC, fecha_devolucion DESC";

            $stmt = $conex->prepare($sql);
            $stmt->execute($params);
            $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $agrupado = [];
            foreach ($datos as $fila) {
                $id = $fila['codigo_atleta'];
                if (!isset($agrupado[$id])) {
                    $agrupado[$id] = [
                        'codigo_atleta' => $id,
                        'nombre_completo' => $fila['atleta_nombre'] . ' ' . $fila['atleta_apellido'],
                        'doc_identidad' => $fila['doc_identidad'] ?? 'Sin CI',
                        'total_devoluciones_atleta' => $fila['total_devoluciones_atleta'],
                        'devoluciones' => []
                    ];
                }
                $agrupado[$id]['devoluciones'][] = [
                    'id_devolucion' => $fila['id_devolucion'],
                    'id_asignacion' => $fila['id_asignacion'],
                    'id_estado' => $fila['id_estado'],
                    'fecha_vista' => $fila['fecha_vista'],
                    'fecha_devolucion' => $fila['fecha_devolucion'],
                    'articulo_nombre' => $fila['articulo_nombre'],
                    'codigo_club' => $fila['codigo_club'],
                    'estado_fisico' => $fila['estado_fisico'],
                    'nivel_estado' => $fila['nivel_estado'],
                    'observacion' => $fila['observacion']
                ];
            }

            return ['accion' => 'consultar', 'datos' => array_values($agrupado)];
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

            // El procedimiento almacenado maneja la transacción de forma segura y el trigger maneja el inventario
            $stmt = $conex->prepare("CALL ProcesarDevolucionSegura(?, ?, ?)");
            $stmt->execute([$this->id_asignacion, $this->id_estado, $this->observacion]);

            $stmtId = $conex->prepare("SELECT id_devolucion FROM devoluciones WHERE id_asignacion = ? ORDER BY id_devolucion DESC LIMIT 1");
            $stmtId->execute([$this->id_asignacion]);
            $id_insertado = $stmtId->fetchColumn();

            return ['accion' => 'exito', 'mensaje' => 'Devolución procesada y equipo liberado.', 'id_devolucion' => $id_insertado];

        } catch (Exception $e) {
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

            $stmtCheck = $conex->prepare("SELECT COUNT(*) FROM asignaciones WHERE codigo_articulo = ? AND estatus = 1");
            $stmtCheck->execute([$codigoArticulo]);
            $enUso = $stmtCheck->fetchColumn();

            if ($enUso > 0) {
                throw new Exception("No se puede anular la devolución. El artículo ya ha sido reasignado a otro atleta y está en uso.");
            }

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