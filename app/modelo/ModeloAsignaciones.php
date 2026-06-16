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

        $this->id_asignacion    = $datos['id_asignacion'] ?? null;
        $this->id_atleta        = $datos['id_atleta'] ?? null;
        $this->id_equipamiento  = $datos['id_equipamiento'] ?? null;
        $this->fecha_asignacion = $datos['fecha_asignacion'] ?? null;
        $this->estatus          = $datos['estatus'] ?? null;
        $this->anulado          = $datos['anulado'] ?? null;
        $this->motivo           = isset($datos['motivo_anulacion']) ? trim($datos['motivo_anulacion']) : (isset($datos['motivo']) ? trim($datos['motivo']) : '');

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

            // Se eliminó "WHERE a.estatus = 1" para permitir traer el histórico completo (activos, devueltos y anulados)
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
                ORDER BY at.nombres ASC, a.fecha_asignacion DESC";

            $stmt = $conex->prepare($sql);
            $stmt->execute();
            $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
            return ['accion' => 'exito', 'mensaje' => 'Asignación procesada.'];
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

            // Buscamos el equipo real asignado en la BD antes de cambiar nada
            $stmtCheck = $conex->prepare("SELECT id_equipamiento FROM asignaciones WHERE id_asignacion = ? FOR UPDATE");
            $stmtCheck->execute([$this->id_asignacion]);
            $id_equipo_actual = $stmtCheck->fetchColumn();

            $stmtAnular = $conex->prepare("UPDATE asignaciones SET anulado = 1, estatus = 0 WHERE id_asignacion = ?");
            $stmtAnular->execute([$this->id_asignacion]);

            if ($id_equipo_actual !== false) {
                $this->objEquipamientos->CambiarEstatus($id_equipo_actual, 1, $conex);
            }

            $conex->commit();
            return ['accion' => 'exito', 'mensaje' => 'Asignación anulada.'];
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

        $motivo = isset($datos['motivo_anulacion']) ? trim($datos['motivo_anulacion']) : (isset($datos['motivo']) ? trim($datos['motivo']) : '');
        if (!empty($motivo) && strlen($motivo) < 5) {
            throw new Exception('El motivo debe tener al menos 5 letras.');
        }
    }
}
