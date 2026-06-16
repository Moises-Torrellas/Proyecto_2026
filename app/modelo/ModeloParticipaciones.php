<?php

namespace App\modelo;

use PDO;
use Exception;

class ModeloParticipaciones extends Conexion
{
    private $id;
    private $torneo;
    private $equipo;

    public function __construct()
    {
        parent::__construct();
        $this->campoWhitelist = [
            'id_participacion' => 'id_participacion',
            'id_equipo' => 'id_equipos',
            'id_torneo' => 'id_torneo'
        ];
        $this->llavePrimaria = 'id_participacion';
    }

    public function ProcesarDatos(array $datos): array
    {
        if (empty($datos)) {
            throw new Exception('No se proporcionaron datos para procesar.');
        }

        $this->id     = isset($datos['id']) ? (int)$datos['id'] : null;
        $this->torneo = isset($datos['torneo']) ? (int)$datos['torneo'] : null;
        $this->equipo = isset($datos['equipo']) ? (int)$datos['equipo'] : null;

        $accion = $datos['accion'] ?? null;

        return match ($accion) {
            'incluir'   => $this->Incluir(),
            'eliminar'  => $this->Eliminar(),
            'modificar' => $this->Modificar(),
            'buscar'    => $this->Buscar(),
            default     => throw new Exception('La acción solicitada para participaciones no es válida.')
        };
    }

    public function Consultar(array $filtro = []): array
    {
        try {
            $conex = $this->conex();
            $params = [];

            // SE AGREGÓ: t.estatus AS torneo_estatus
            $sentencia = "SELECT 
            p.id_participacion, 
            t.id_torneo, 
            t.nombre AS torneo_nombre, 
            t.estatus AS torneo_estatus, 
            e.id_equipos, 
            e.nombre AS equipo_nombre
        FROM participaciones p
        INNER JOIN torneos t ON p.id_torneo = t.id_torneo
        INNER JOIN equipos e ON p.id_equipo = e.id_equipos
        WHERE 1=1";

            if (!empty($filtro['filtro'])) {
                $p = "%" . trim($filtro['filtro']) . "%";
                $sentencia .= " AND (t.nombre LIKE :f1 OR e.nombre LIKE :f2)";
                $params[':f1'] = $p;
                $params[':f2'] = $p;
            }

            $sentencia .= " ORDER BY t.id_torneo DESC, e.nombre ASC";

            $stmt = $conex->prepare($sentencia);
            $stmt->execute($params);
            $datos = $stmt->fetchAll();

            return array('accion' => 'consultar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Participaciones', $e->getMessage(), 'Modelo_Consultar');
            return array('accion' => 'error');
        } finally {
            $conex = NULL;
        }
    }

    private function Incluir(): array
    {
        $conex = null;
        try {
            $conex = $this->conex();
            $conex->beginTransaction();

            if (!$this->verificarExistencia('id_torneo', $this->torneo, 'torneos', NULL)) {
                throw new Exception(INVALID_ID);
            }
            if (!$this->verificarExistencia('id_equipo', $this->equipo, 'equipos', NULL)) {
                throw new Exception(INVALID_ID . '0');
            }

            if ($this->validarParticipacionGrupal($this->torneo, $this->equipo)) {
                throw new Exception(DUPLICATE);
            }

            $sql = "INSERT INTO participaciones (id_torneo, id_equipo) 
                    VALUES (:torneo, :equipo)";

            $stmt = $conex->prepare($sql);
            $stmt->bindValue(':torneo', $this->torneo, PDO::PARAM_INT);
            $stmt->bindValue(':equipo', $this->equipo, PDO::PARAM_INT);

            $stmt->execute();
            $conex->commit();

            return array('accion' => 'exito');
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            logs('Participaciones', $e->getMessage(), 'Modelo_Incluir');
            return array('accion' => 'error', 'codigo' => $e->getMessage());
        } finally {
            $conex = null;
        }
    }


    public function Buscar(): array
{
    $conex = null;
    try {
        $conex = $this->conex();
        $sql = "SELECT 
                    p.id_participacion, 
                    p.id_torneo, 
                    p.id_equipo
                FROM participaciones p
                WHERE p.id_participacion = :id";

        $stmt = $conex->prepare($sql);
        $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);
        $stmt->execute();
        
        $resultado = $stmt->fetchAll();

        if (!$resultado) {
            return array('accion' => 'error', 'mensaje' => 'Registro no encontrado');
        }

        return array('accion' => 'consultar', 'datos' => $resultado);

    } catch (Exception $e) {
        logs('Participaciones', $e->getMessage(), 'Modelo_Buscar');
        return array('accion' => 'error', 'mensaje' => 'Error al buscar el registro');
    } finally {
        $conex = null;
    }
}

    private function Modificar(): array
    {
        $conex = null;
        try {
            $conex = $this->conex();
            $conex->beginTransaction();

            if (!$this->verificarExistencia('id_participacion', $this->id, 'participaciones', null)) {
                throw new Exception(INVALID_ID . '2');
            }

            if (!$this->verificarExistencia('id_torneo', $this->torneo, 'torneos', null)) {
                throw new Exception(INVALID_ID);
            }
            if (!$this->verificarExistencia('id_equipo', $this->equipo, 'equipos', null)) {
                throw new Exception(INVALID_ID . '0');
            }

            if ($this->validarParticipacionDuplicadaPropia($this->torneo, $this->equipo, $this->id)) {
                throw new Exception(DUPLICATE);
            }

            $sql = "UPDATE participaciones SET 
                        id_torneo = :torneo, 
                        id_equipo = :equipo 
                    WHERE id_participacion = :id";

            $stmt = $conex->prepare($sql);
            $stmt->bindValue(':torneo', $this->torneo, PDO::PARAM_INT);
            $stmt->bindValue(':equipo', $this->equipo, PDO::PARAM_INT);
            $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);

            $stmt->execute();
            $conex->commit();

            return array('accion' => 'exito');
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            logs('Participaciones', $e->getMessage(), 'Modelo_Modificar');
            return array('accion' => 'error', 'codigo' => $e->getMessage());
        } finally {
            $conex = null;
        }
    }

    private function Eliminar(): array
    {
        $conex = null;
        try {
            $conex = $this->conex();
            $conex->beginTransaction();

            if (!$this->verificarExistencia('id_participacion', $this->id, 'participaciones', null)) {
                throw new Exception(INVALID_ID);
            }

            $sql = "DELETE FROM participaciones WHERE id_participacion = :id";
            $stmt = $conex->prepare($sql);
            $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);
            $stmt->execute();

            $conex->commit();
            return array('accion' => 'exito');
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            logs('Participaciones', $e->getMessage(), 'Modelo_Eliminar');
            return array('accion' => 'error', 'codigo' => $e->getMessage());
        } finally {
            $conex = null;
        }
    }

    public function validarParticipacionIndividual(int $id_torneo, int $id_atleta): bool
    {
        $conex = null;
        try {
            $conex = $this->conex();
            $stmt = $conex->prepare(
                "SELECT COUNT(*) FROM participaciones par
                INNER JOIN detalles_equipos de ON par.id_equipo = de.id_equipo
                WHERE par.id_torneo = :id_torneo AND de.id_atleta = :id_atleta"
            );
            $stmt->bindValue(':id_torneo', $id_torneo, PDO::PARAM_INT);
            $stmt->bindValue(':id_atleta', $id_atleta, PDO::PARAM_INT);
            $stmt->execute();

            return ((int)$stmt->fetchColumn() > 0);
        } catch (Exception $e) {
            logs('Participaciones', $e->getMessage(), 'ModeloParticipaciones_validarParticipacionIndividual');
            throw $e;
        } finally {
            $conex = null;
        }
    }

    public function validarParticipacionGrupal(int $id_torneo, int $id_equipo): bool
    {
        $conex = null;
        try {
            $conex = $this->conex();
            $stmt = $conex->prepare(
                "SELECT COUNT(*) FROM participaciones 
                 WHERE id_torneo = :id_torneo AND id_equipo = :id_equipo"
            );
            $stmt->bindValue(':id_torneo', $id_torneo, PDO::PARAM_INT);
            $stmt->bindValue(':id_equipo', $id_equipo, PDO::PARAM_INT);
            $stmt->execute();

            return ((int)$stmt->fetchColumn() > 0);
        } catch (Exception $e) {
            logs('Participaciones', $e->getMessage(), 'ModeloParticipaciones_validarParticipacionGrupal');
            throw $e;
        } finally {
            $conex = null;
        }
    }

    public function validarParticipacionDuplicadaPropia(int $id_torneo, int $id_equipo, int $id): bool
    {
        $conex = null;
        try {
            $conex = $this->conex();
            $stmt = $conex->prepare(
                "SELECT COUNT(*) FROM participaciones 
                 WHERE id_torneo = :id_torneo AND id_equipo = :id_equipo AND id_participacion != :id"
            );
            $stmt->bindValue(':id_torneo', $id_torneo, PDO::PARAM_INT);
            $stmt->bindValue(':id_equipo', $id_equipo, PDO::PARAM_INT);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            return ((int)$stmt->fetchColumn() > 0);
        } catch (Exception $e) {
            logs('Participaciones', $e->getMessage(), 'ModeloParticipaciones_validarParticipacionDuplicadaPropia');
            throw $e;
        } finally {
            $conex = null;
        }
    }
}
