<?php

namespace App\modelo;

use App\modelo\ModeloBase;
use App\modelo\ModeloEquipamientos;
use Exception;
use PDO;

class ModeloAsignaciones extends ModeloBase
{
    // Atributos privados encapsulados
    private $id_asignacion;
    private $id_atleta;
    private $id_equipamiento;
    private $fecha_asignacion;
    private $estatus;
    private $anulado;
    private $motivo; 

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

    public function ProcesarDatos(array $datos): array
    {
        if (empty($datos)) {
            throw new Exception('No se proporcionaron datos para procesar la asignación.');
        }

        $this->id_asignacion    = $datos['id_asignacion'] ?? null;
        $this->id_atleta        = $datos['id_atleta'] ?? null;
        $this->id_equipamiento  = $datos['id_equipamiento'] ?? null;
        $this->fecha_asignacion = $datos['fecha_asignacion'] ?? null; 
        $this->estatus          = $datos['estatus'] ?? null;
        $this->anulado          = $datos['anulado'] ?? null;
        $this->motivo           = isset($datos['motivo']) ? trim($datos['motivo']) : '';

        $accion = $datos['accion'] ?? null;

        return match ($accion) {
            'consultar' => $this->ConsultarAsignaciones(),
            'incluir'   => $this->IncluirAsignacion(),
            'modificar' => $this->ModificarAsignacion(),
            'anular'    => $this->AnularAsignacion(),
            default     => throw new Exception('La acción solicitada para la asignación no es válida.')
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
                           a.id_atleta,
                           CONCAT(at.nombres, ' ', at.apellidos) as atleta,
                           at.doc_identidad,
                           c.nombre as articulo,
                           e.id_equipamiento
                    FROM asignaciones a
                    INNER JOIN atletas at ON a.id_atleta = at.id_atleta
                    INNER JOIN equipamientos e ON a.id_equipamiento = e.id_equipamiento
                    INNER JOIN catalogos c ON e.id_catalogo = c.id_catalogo
                    WHERE a.anulado = 0 AND a.estatus = 1 /* <--- ESTA LÍNEA ES LA MAGIA */
                    ORDER BY a.fecha_asignacion DESC";
            
            $stmt = $conex->prepare($sql);
            $stmt->execute();
            $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ['accion' => 'consultar', 'datos' => $datos];
        } catch (Exception $e) {
            logs('Asignaciones', $e->getMessage(), 'Modelo_Consultar');
            return ['accion' => 'error', 'mensaje' => 'Error BD: ' . $e->getMessage()];
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

            // 1. Bloqueo transaccional: Verificamos si el equipo físico sigue disponible
            $stmtCheck = $conex->prepare("SELECT estatus FROM equipamientos WHERE id_equipamiento = ? FOR UPDATE");
            $stmtCheck->execute([$this->id_equipamiento]);
            $estadoEquipo = $stmtCheck->fetchColumn();
            
            if ($estadoEquipo != 1) {
                throw new Exception("El equipo seleccionado ya fue asignado o no está disponible en almacén.");
            }

            // 2. Inserción del documento legal del préstamo (Estatus 1 = Activa, Anulado = 0)
            $sqlInsert = "INSERT INTO asignaciones (id_atleta, id_equipamiento, fecha_asignacion, estatus, anulado) VALUES (?, ?, ?, 1, 0)";
            $stmtInsert = $conex->prepare($sqlInsert);
            $stmtInsert->execute([$this->id_atleta, $this->id_equipamiento, $this->fecha_asignacion]);

            // 3. SRP: Delegamos la modificación física del equipo a su propio modelo inyectándole la conexión
            $modeloEquipos = new ModeloEquipamientos();
            $modeloEquipos->CambiarEstatus($this->id_equipamiento, 2, $conex);

            $conex->commit();
            return ['accion' => 'exito', 'mensaje' => 'Asignación procesada. Equipo descontado del almacén.'];
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            logs('Asignaciones', $e->getMessage(), 'Modelo_Incluir');
            return ['accion' => 'error', 'codigo' => $e->getMessage()];
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

            // 1. Rastreamos cuál era el equipo asignado originalmente para devolverlo si es necesario
            $stmtOld = $conex->prepare("SELECT id_equipamiento FROM asignaciones WHERE id_asignacion = ? FOR UPDATE");
            $stmtOld->execute([$this->id_asignacion]);
            $viejoEquipo = $stmtOld->fetchColumn();

            if (!$viejoEquipo) {
                throw new Exception("La asignación original no existe en el sistema.");
            }

            // 2. Lógica de Intercambio Físico: Si el usuario seleccionó un activo diferente
            if ($viejoEquipo != $this->id_equipamiento) {
                
                // Aseguramos que el activo nuevo esté disponible
                $stmtCheck = $conex->prepare("SELECT estatus FROM equipamientos WHERE id_equipamiento = ? FOR UPDATE");
                $stmtCheck->execute([$this->id_equipamiento]);
                
                if ($stmtCheck->fetchColumn() != 1) {
                    throw new Exception("El nuevo equipo seleccionado no está disponible en almacén.");
                }

                $modeloEquipos = new ModeloEquipamientos();
                
                // Reingresamos la pieza anterior (estatus 1 = Disponible)
                $modeloEquipos->CambiarEstatus($viejoEquipo, 1, $conex);
                
                // Bloqueamos la nueva pieza (estatus 2 = Prestado)
                $modeloEquipos->CambiarEstatus($this->id_equipamiento, 2, $conex);
            }

            // 3. Actualizamos la cabecera de la asignación
            $sqlUpdate = "UPDATE asignaciones SET id_atleta = ?, id_equipamiento = ?, fecha_asignacion = ? WHERE id_asignacion = ?";
            $stmtUpdate = $conex->prepare($sqlUpdate);
            $stmtUpdate->execute([$this->id_atleta, $this->id_equipamiento, $this->fecha_asignacion, $this->id_asignacion]);

            $conex->commit();
            return ['accion' => 'exito', 'mensaje' => 'Modificación exitosa. Inventario actualizado.'];
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            logs('Asignaciones', $e->getMessage(), 'Modelo_Modificar');
            return ['accion' => 'error', 'codigo' => $e->getMessage()];
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

            // 1. Anulación Lógica: Mantenemos el registro histórico pero lo invalidamos
            $stmtAnular = $conex->prepare("UPDATE asignaciones SET anulado = 1, estatus = 0 WHERE id_asignacion = ?");
            $stmtAnular->execute([$this->id_asignacion]);

            // 2. SRP: Usamos el modelo cruzado para liberar la pieza
            $modeloEquipos = new ModeloEquipamientos();
            $modeloEquipos->CambiarEstatus($this->id_equipamiento, 1, $conex);

            $conex->commit();
            return ['accion' => 'exito', 'mensaje' => 'Asignación anulada. El equipo regresó al inventario libre.'];
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            logs('Asignaciones', $e->getMessage(), 'Modelo_Anular');
            return ['accion' => 'error', 'codigo' => $e->getMessage()];
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
}