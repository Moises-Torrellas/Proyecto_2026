<?php

namespace App\modelo;

use PDO;
use Exception;

class ModeloParticipaciones extends Conexion
{
    private $codigo_participacion;
    private $codigo_torneo;
    private $codigo_equipo;

    public function __construct()
    {
        parent::__construct();
        $this->campoWhitelist = [
            'codigo_participacion' => 'codigo_participacion',
            'codigo_equipo'        => 'codigo_equipo',
            'codigo_torneo'        => 'codigo_torneo'
        ];
        $this->llavePrimaria = 'codigo_participacion';
    }

    public function ProcesarDatos(array $datos): array
    {
        if (empty($datos)) {
            throw new Exception('No se proporcionaron datos para procesar.');
        }

        $this->codigo_participacion = isset($datos['codigo_participacion']) ? (int)$datos['codigo_participacion'] : null;
        $this->codigo_torneo        = isset($datos['codigo_torneo']) ? (int)$datos['codigo_torneo'] : null;
        $this->codigo_equipo        = isset($datos['codigo_equipo']) ? (int)$datos['codigo_equipo'] : null;

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

            $sentencia = "SELECT 
            p.codigo_participacion, 
            t.codigo_torneo, 
            t.nombre AS torneo_nombre, 
            t.estatus AS torneo_estatus,
            t.fecha_inicio,
            e.codigo_equipo, 
            e.nombre AS equipo_nombre
        FROM participaciones p
        INNER JOIN torneos t ON p.codigo_torneo = t.codigo_torneo
        INNER JOIN equipos e ON p.codigo_equipo = e.codigo_equipo
        WHERE 1=1";

            // Filtro de búsqueda (nombre)
            if (!empty($filtro['filtro'])) {
                $p = "%" . trim($filtro['filtro']) . "%";
                $sentencia .= " AND (t.nombre LIKE :f1 OR e.nombre LIKE :f2)";
                $params[':f1'] = $p;
                $params[':f2'] = $p;
            }

            // NUEVO: Filtro por estatus de torneo
            if (isset($filtro['estatus_torneo'])) {
                $sentencia .= " AND t.estatus = :estatus";
                $params[':estatus'] = $filtro['estatus_torneo'];
            }

            $sentencia .= " ORDER BY t.codigo_torneo DESC, e.nombre ASC";

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

            if (!$this->verificarExistencia('codigo_torneo', $this->codigo_torneo, 'torneos', NULL)) {
                throw new Exception(INVALID_ID);
            }
            if (!$this->verificarExistencia('codigo_equipo', $this->codigo_equipo, 'equipos', NULL)) {
                throw new Exception(INVALID_ID . '0');
            }

            if ($this->validarParticipacionGrupal($this->codigo_torneo, $this->codigo_equipo)) {
                throw new Exception(DUPLICATE);
            }

            $sql = "INSERT INTO participaciones (codigo_torneo, codigo_equipo) 
                    VALUES (:codigo_torneo, :codigo_equipo)";

            $stmt = $conex->prepare($sql);
            $stmt->bindValue(':codigo_torneo', $this->codigo_torneo, PDO::PARAM_INT);
            $stmt->bindValue(':codigo_equipo', $this->codigo_equipo, PDO::PARAM_INT);

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
                        p.codigo_participacion, 
                        p.codigo_torneo, 
                        p.codigo_equipo
                    FROM participaciones p
                    WHERE p.codigo_participacion = :codigo_participacion";

            $stmt = $conex->prepare($sql);
            $stmt->bindValue(':codigo_participacion', $this->codigo_participacion, PDO::PARAM_INT);
            $stmt->execute();

            $resultado = $stmt->fetchAll();

            if (!$resultado) {
                return array('accion' => 'error', 'mensaje' => 'Registro no encontrado');
            }

            return array('accion' => 'buscar', 'datos' => $resultado);
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

            if (!$this->verificarExistencia('codigo_participacion', $this->codigo_participacion, 'participaciones', null)) {
                throw new Exception(INVALID_ID . '2');
            }

            if (!$this->verificarExistencia('codigo_torneo', $this->codigo_torneo, 'torneos', null)) {
                throw new Exception(INVALID_ID);
            }
            if (!$this->verificarExistencia('codigo_equipo', $this->codigo_equipo, 'equipos', null)) {
                throw new Exception(INVALID_ID . '0');
            }

            if ($this->validarParticipacionDuplicadaPropia($this->codigo_torneo, $this->codigo_equipo, $this->codigo_participacion)) {
                throw new Exception(DUPLICATE);
            }

            $sql = "UPDATE participaciones SET 
                        codigo_torneo = :codigo_torneo, 
                        codigo_equipo = :codigo_equipo 
                    WHERE codigo_participacion = :codigo_participacion";

            $stmt = $conex->prepare($sql);
            $stmt->bindValue(':codigo_torneo', $this->codigo_torneo, PDO::PARAM_INT);
            $stmt->bindValue(':codigo_equipo', $this->codigo_equipo, PDO::PARAM_INT);
            $stmt->bindValue(':codigo_participacion', $this->codigo_participacion, PDO::PARAM_INT);

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

            if (!$this->verificarExistencia('codigo_participacion', $this->codigo_participacion, 'participaciones', null)) {
                throw new Exception(INVALID_ID);
            }

            $sql = "DELETE FROM participaciones WHERE codigo_participacion = :codigo_participacion";
            $stmt = $conex->prepare($sql);
            $stmt->bindValue(':codigo_participacion', $this->codigo_participacion, PDO::PARAM_INT);
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

    public function validarParticipacionIndividual(int $codigo_torneo, int $codigo_atleta): bool
    {
        $conex = null;
        try {
            $conex = $this->conex();
            $stmt = $conex->prepare(
                "SELECT COUNT(*) FROM participaciones par
                INNER JOIN detalles_equipos de ON par.codigo_equipo = de.codigo_equipo
                WHERE par.codigo_torneo = :codigo_torneo AND de.codigo_atleta = :codigo_atleta"
            );
            $stmt->bindValue(':codigo_torneo', $codigo_torneo, PDO::PARAM_INT);
            $stmt->bindValue(':codigo_atleta', $codigo_atleta, PDO::PARAM_INT);
            $stmt->execute();

            return ((int)$stmt->fetchColumn() > 0);
        } catch (Exception $e) {
            logs('Participaciones', $e->getMessage(), 'ModeloParticipaciones_validarParticipacionIndividual');
            throw $e;
        } finally {
            $conex = null;
        }
    }

    public function validarParticipacionGrupal(int $codigo_torneo, int $codigo_equipo): bool
    {
        $conex = null;
        try {
            $conex = $this->conex();
            $stmt = $conex->prepare(
                "SELECT COUNT(*) FROM participaciones 
                 WHERE codigo_torneo = :codigo_torneo AND codigo_equipo = :codigo_equipo"
            );
            $stmt->bindValue(':codigo_torneo', $codigo_torneo, PDO::PARAM_INT);
            $stmt->bindValue(':codigo_equipo', $codigo_equipo, PDO::PARAM_INT);
            $stmt->execute();

            return ((int)$stmt->fetchColumn() > 0);
        } catch (Exception $e) {
            logs('Participaciones', $e->getMessage(), 'ModeloParticipaciones_validarParticipacionGrupal');
            throw $e;
        } finally {
            $conex = null;
        }
    }

    public function validarParticipacionDuplicadaPropia(int $codigo_torneo, int $codigo_equipo, int $codigo_participacion): bool
    {
        $conex = null;
        try {
            $conex = $this->conex();
            $stmt = $conex->prepare(
                "SELECT COUNT(*) FROM participaciones 
                 WHERE codigo_torneo = :codigo_torneo AND codigo_equipo = :codigo_equipo AND codigo_participacion != :codigo_participacion"
            );
            $stmt->bindValue(':codigo_torneo', $codigo_torneo, PDO::PARAM_INT);
            $stmt->bindValue(':codigo_equipo', $codigo_equipo, PDO::PARAM_INT);
            $stmt->bindValue(':codigo_participacion', $codigo_participacion, PDO::PARAM_INT);
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
