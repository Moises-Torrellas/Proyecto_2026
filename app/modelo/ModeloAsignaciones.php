<?php

namespace App\modelo;

use Exception;
use PDO;

class ModeloAsignaciones extends Conexion
{
    private $id_asignacion;
    private $id_atleta;
    private $id_equipamiento;
    private $fecha_asignacion;
    private $estatus;
    private $anulado;
    private $motivo;
    
    // Variables para el filtro de reportes
    private $filtro;
    private $fecha_inicio;
    private $fecha_fin;
    
    private $objEquipamientos;

    public function __construct()
    {
        parent::__construct();

        $this->campoWhitelist = [
            'id_asignacion'    => 'id_asignacion',
            'id_atleta'        => 'id_atleta',
            'id_equipamiento'  => 'id_equipamiento',
            'fecha_asignacion' => 'fecha_asignacion',
            'estatus'          => 'estatus',
            'anulado'          => 'anulado',
            'motivo'           => 'motivo'
        ];

        $this->llavePrimaria = 'id_asignacion';
    }

    public function setEquipamientos(ModeloEquipamientos $equipamientos)
    {
        $this->objEquipamientos = $equipamientos;
    }

    public function ProcesarDatos(array $datos): array
    {
        if (empty($datos)) {
            return ['accion' => 'error', 'codigo' => 'ERR_VACIO'];
        }

        $this->ValidarExpresiones($datos);

        // Mapeo de datos principales
        $this->id_asignacion    = $datos['id_asignacion'] ?? null;
        $this->id_atleta        = $datos['id_atleta'] ?? null;
        $this->id_equipamiento  = $datos['id_equipamiento'] ?? null;
        $this->fecha_asignacion = $datos['fecha_asignacion'] ?? null;
        $this->estatus          = $datos['estatus'] ?? null;
        $this->motivo           = isset($datos['motivo_anulacion']) ? trim($datos['motivo_anulacion']) : (isset($datos['motivo']) ? trim($datos['motivo']) : '');

        // Mapeo de filtros para reportes/búsquedas
        $this->filtro           = $datos['filtro'] ?? '';
        $this->fecha_inicio     = $datos['fecha_inicio'] ?? '';
        $this->fecha_fin        = $datos['fecha_fin'] ?? '';
        $this->anulado          = $datos['anulados'] ?? 0;

        $accion = $datos['accion'] ?? null;

        return match ($accion) {
            'consultar' => $this->ConsultarAsignaciones(),
            'incluir'   => $this->IncluirAsignacion(),
            'modificar' => $this->ModificarAsignacion(),
            'anular'    => $this->AnularAsignacion(),
            default     => ['accion' => 'error', 'codigo' => 'ERR_ACCION']
        };
    }

    public function ConsultarAsignaciones(): array
    {
        $conex = null;
        try {
            $conex = $this->conex();
            
            $sql = "SELECT a.id_asignacion, 
                           DATE_FORMAT(a.fecha_asignacion, '%d/%m/%Y') as fecha_vista,
                           a.fecha_asignacion as fecha_real,
                           a.estatus as estatus_asignacion,
                           a.anulado,
                           a.id_atleta,
                           CONCAT(at.nombres, ' ', at.apellidos) as atleta,
                           at.doc_identidad,
                           c.nombre as articulo,
                           e.id_equipamiento
                    FROM asignaciones a
                    INNER JOIN atletas at ON a.id_atleta = at.id_atleta
                    INNER JOIN equipamientos e ON a.id_equipamiento = e.id_equipamiento
                    INNER JOIN catalogos c ON e.id_catalogo = c.id_catalogo
                    WHERE 1=1"; 
            
            // Filtro del buscador de la tabla principal
            if (!empty($this->filtro)) {
                $sql .= " AND (at.nombres LIKE :filtro OR at.apellidos LIKE :filtro OR at.doc_identidad LIKE :filtro OR c.nombre LIKE :filtro)";
            }

            // Filtros del Modal de Reportes
            if (!empty($this->id_atleta)) {
                $sql .= " AND a.id_atleta = :id_atleta";
            }
            if (!empty($this->id_equipamiento)) {
                $sql .= " AND a.id_equipamiento = :id_equipamiento";
            }
            if (!empty($this->fecha_inicio) && !empty($this->fecha_fin)) {
                $sql .= " AND a.fecha_asignacion BETWEEN :fecha_inicio AND :fecha_fin";
            } else if (!empty($this->fecha_inicio)) {
                $sql .= " AND a.fecha_asignacion >= :fecha_inicio";
            } else if (!empty($this->fecha_fin)) {
                $sql .= " AND a.fecha_asignacion <= :fecha_fin";
            }

            // Si el checkbox de anulados NO está marcado, solo traemos los activos
            if (empty($this->anulado)) {
                $sql .= " AND a.estatus = 1 AND a.anulado = 0";
            }
            
            $sql .= " ORDER BY at.nombres ASC, a.fecha_asignacion DESC";
            
            $stmt = $conex->prepare($sql);
            
            // Bindeo de variables
            if (!empty($this->filtro)) $stmt->bindValue(':filtro', '%' . $this->filtro . '%');
            if (!empty($this->id_atleta)) $stmt->bindValue(':id_atleta', $this->id_atleta);
            if (!empty($this->id_equipamiento)) $stmt->bindValue(':id_equipamiento', $this->id_equipamiento);
            if (!empty($this->fecha_inicio)) $stmt->bindValue(':fecha_inicio', $this->fecha_inicio);
            if (!empty($this->fecha_fin)) $stmt->bindValue(':fecha_fin', $this->fecha_fin);

            $stmt->execute();
            $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Agrupación de datos para la vista de acordeón
            $agrupado = [];
            foreach ($datos as $fila) {
                $id = $fila['id_atleta'];
                if (!isset($agrupado[$id])) {
                    $agrupado[$id] = [
                        'id_atleta' => $id,
                        'nombre_completo' => $fila['atleta'],
                        'doc_identidad' => $fila['doc_identidad'],
                        'asignaciones' => []
                    ];
                }
                $agrupado[$id]['asignaciones'][] = [
                    'id_asignacion' => $fila['id_asignacion'],
                    'id_equipamiento' => $fila['id_equipamiento'],
                    'articulo' => $fila['articulo'],
                    'fecha_vista' => $fila['fecha_vista'],
                    'fecha_real' => $fila['fecha_real'],
                    'estatus' => $fila['estatus_asignacion'],
                    'anulado' => $fila['anulado']
                ];
            }

            return ['accion' => 'consultar', 'datos' => array_values($agrupado)];
        } catch (Exception $e) {
            logs('Asignaciones', $e->getMessage(), 'Modelo_Consultar');
            return ['accion' => 'error', 'codigo' => 'ERR_BD'];
        } finally {
            $conex = null;
        }
    }

    private function IncluirAsignacion(): array
    {
        $conex = null;
        try {
            $conex = $this->conex();
            $conex->beginTransaction();

            if (!$this->verificarExistencia('id_atleta', $this->id_atleta, 'atletas', null)) {
                return ['accion' => 'error', 'codigo' => 'ERR_ATLETA_NO_EXISTE'];
            }

            $stmtCheck = $conex->prepare("SELECT estatus FROM equipamientos WHERE id_equipamiento = ? FOR UPDATE");
            $stmtCheck->execute([$this->id_equipamiento]);
            $estadoEquipo = $stmtCheck->fetchColumn();

            if ($estadoEquipo === false) return ['accion' => 'error', 'codigo' => 'ERR_EQUIPO_NO_EXISTE'];
            if ($estadoEquipo != 1) return ['accion' => 'error', 'codigo' => 'ERR_EQUIPO_OCUPADO'];

            $sqlInsert = "INSERT INTO asignaciones (id_atleta, id_equipamiento, fecha_asignacion, estatus, anulado) VALUES (?, ?, ?, 1, 0)";
            $stmtInsert = $conex->prepare($sqlInsert);
            $stmtInsert->execute([$this->id_atleta, $this->id_equipamiento, $this->fecha_asignacion]);

            $this->objEquipamientos->CambiarEstatus($this->id_equipamiento, 2, $conex);

            $conex->commit();
            return ['accion' => 'exito', 'mensaje' => 'Asignación procesada exitosamente.'];
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) $conex->rollBack();
            logs('Asignaciones', $e->getMessage(), 'Modelo_Incluir');
            return ['accion' => 'error', 'codigo' => 'ERR_BD'];
        } finally {
            $conex = null;
        }
    }

    private function ModificarAsignacion(): array
    {
        $conex = null;
        try {
            $conex = $this->conex();
            $conex->beginTransaction();

            if (!$this->verificarExistencia('id_asignacion', $this->id_asignacion, 'asignaciones', null)) {
                return ['accion' => 'error', 'codigo' => 'ERR_NO_EXISTE'];
            }

            $stmtOld = $conex->prepare("SELECT id_equipamiento FROM asignaciones WHERE id_asignacion = ? FOR UPDATE");
            $stmtOld->execute([$this->id_asignacion]);
            $viejoEquipo = $stmtOld->fetchColumn();

            if ($viejoEquipo != $this->id_equipamiento) {
                $stmtCheck = $conex->prepare("SELECT estatus FROM equipamientos WHERE id_equipamiento = ? FOR UPDATE");
                $stmtCheck->execute([$this->id_equipamiento]);
                if ($stmtCheck->fetchColumn() != 1) return ['accion' => 'error', 'codigo' => 'ERR_EQUIPO_NO_DISPONIBLE'];

                $this->objEquipamientos->CambiarEstatus($viejoEquipo, 1, $conex);
                $this->objEquipamientos->CambiarEstatus($this->id_equipamiento, 2, $conex);
            }

            $sqlUpdate = "UPDATE asignaciones SET id_atleta = ?, id_equipamiento = ?, fecha_asignacion = ? WHERE id_asignacion = ?";
            $conex->prepare($sqlUpdate)->execute([$this->id_atleta, $this->id_equipamiento, $this->fecha_asignacion, $this->id_asignacion]);

            $conex->commit();
            return ['accion' => 'exito', 'mensaje' => 'Modificación exitosa.'];
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) $conex->rollBack();
            logs('Asignaciones', $e->getMessage(), 'Modelo_Modificar');
            return ['accion' => 'error', 'codigo' => 'ERR_BD'];
        } finally {
            $conex = null;
        }
    }

    private function AnularAsignacion(): array
    {
        $conex = null;
        try {
            $conex = $this->conex();
            $conex->beginTransaction();

            if (!$this->verificarExistencia('id_asignacion', $this->id_asignacion, 'asignaciones', null)) {
                return ['accion' => 'error', 'codigo' => 'ERR_NO_EXISTE'];
            }

            $stmtCheck = $conex->prepare("SELECT id_equipamiento FROM asignaciones WHERE id_asignacion = ? FOR UPDATE");
            $stmtCheck->execute([$this->id_asignacion]);
            $id_equipo_actual = $stmtCheck->fetchColumn();

            $stmtAnular = $conex->prepare("UPDATE asignaciones SET anulado = 1, estatus = 0 WHERE id_asignacion = ?");
            $stmtAnular->execute([$this->id_asignacion]);

            if ($id_equipo_actual !== false) {
                $this->objEquipamientos->CambiarEstatus($id_equipo_actual, 1, $conex);
            }

            $conex->commit();
            return ['accion' => 'exito', 'mensaje' => 'Asignación anulada. Equipo liberado.'];
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) $conex->rollBack();
            logs('Asignaciones', $e->getMessage(), 'Modelo_Anular');
            return ['accion' => 'error', 'codigo' => 'ERR_BD'];
        } finally {
            $conex = null;
        }
    }

    public function CambiarEstatusAsignacion($id_asignacion, $nuevo_estatus, $conex = null): bool
    {
        $c = $conex ?? $this->conex();

        try {
            $sql = "UPDATE asignaciones SET estatus = :estatus WHERE id_asignacion = :id";
            $stmt = $c->prepare($sql);
            $stmt->execute([
                ':estatus' => $nuevo_estatus,
                ':id'      => $id_asignacion
            ]);

            return true;
        } catch (Exception $e) {
            logs('Asignaciones', $e->getMessage(), 'Modelo_CambiarEstatusAsignacion');
            throw new Exception("Error al actualizar el estatus de la asignación.");
        }
    }

    private function ValidarExpresiones(array $datos): void
    {
        if (!empty($datos['id_asignacion']) && !preg_match('/^[0-9]+$/', $datos['id_asignacion'])) {
            throw new Exception('ID de asignación inválido.');
        }
        if (!empty($datos['id_atleta']) && !preg_match('/^[0-9]+$/', $datos['id_atleta'])) {
            throw new Exception('Atleta inválido.');
        }
        if (!empty($datos['id_equipamiento']) && !preg_match('/^[0-9]+$/', $datos['id_equipamiento'])) {
            throw new Exception('Equipamiento inválido.');
        }
        if (!empty($datos['fecha_asignacion']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $datos['fecha_asignacion'])) {
            throw new Exception('Formato de fecha inválido.');
        }
        if (!empty($datos['fecha_inicio']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $datos['fecha_inicio'])) {
            throw new Exception('Formato de fecha de inicio inválido.');
        }
        if (!empty($datos['fecha_fin']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $datos['fecha_fin'])) {
            throw new Exception('Formato de fecha de fin inválido.');
        }

        $motivo = isset($datos['motivo_anulacion']) ? trim($datos['motivo_anulacion']) : (isset($datos['motivo']) ? trim($datos['motivo']) : '');
        if (!empty($motivo) && strlen($motivo) < 5) {
            throw new Exception('El motivo debe tener al menos 5 letras.');
        }
    }
}