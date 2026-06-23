<?php

namespace App\modelo;

use PDO;
use Exception;

class ModeloEstadisticas extends Conexion
{
    private $id;
    private $atleta;
    private $participacion;
    private $goles;
    private $asistencias;
    private $penalizaciones;
    private $goles_c;
    private $partidos;
    private $average;

    private $ModeloHistorial;
    private $ModeloParticipaciones;

    public function __construct()
    {
        parent::__construct();
        $this->campoWhitelist = [
            'id'             => 'codigo_dtll_prtc',
            'participacion'  => 'codigo_participacion',
            'codigo_atleta'      => 'codigo_atleta',
        ];
        $this->llavePrimaria = 'codigo_dtll_prtc';
    }

    public function setHistorial(ModeloHistorial $obj)
    {
        $this->ModeloHistorial = $obj;
    }
    public function setParticipaciones(ModeloParticipaciones $obj)
    {
        $this->ModeloParticipaciones = $obj;
    }

    public function ProcesarDatos(array $datos): array
    {
        if (empty($datos)) {
            throw new Exception('No se proporcionaron datos para procesar.');
        }

        $this->id             = isset($datos['id']) ? (int)$datos['id'] : null;
        $this->atleta         = isset($datos['atleta']) ? (int)$datos['atleta'] : null;
        
        // Soporte transicional: acepta 'participacion' pero mantiene compatibilidad si el frontend sigue enviando 'torneo'
        $this->participacion  = isset($datos['participacion']) ? (int)$datos['participacion'] : (isset($datos['torneo']) ? (int)$datos['torneo'] : null);

        $this->goles          = isset($datos['goles']) ? (int)$datos['goles'] : 0;
        $this->asistencias    = isset($datos['asistencias']) ? (int)$datos['asistencias'] : 0;
        $this->penalizaciones = isset($datos['penalizaciones']) ? (int)$datos['penalizaciones'] : 0;
        $this->goles_c        = isset($datos['goles_c']) ? (int)$datos['goles_c'] : 0;

        $this->partidos       = isset($datos['partido']) ? (int)$datos['partido'] : 0;
        $this->average        = isset($datos['average']) ? (float)$datos['average'] : null;

        $accion = $datos['accion'] ?? null;

        return match ($accion) {
            'incluir'   => $this->Incluir(),
            'eliminar'  => $this->Eliminar(),
            'buscar'    => $this->Buscar(),
            'modificar' => $this->Modificar(),
            default     => throw new Exception('La acción solicitada para estadísticas no es válida.')
        };
    }

    public function Consultar(array $filtro = []): array
    {
        try {
            $conex = $this->conex();
            $params = [];

            $sentencia = "SELECT 
                        dp.codigo_dtll_prtc AS id_estadisticas, 
                        dp.goles, dp.asistencias, dp.penalizaciones, 
                        dp.partidos_jugados, dp.average, dp.goles_contra,
                        a.codigo_atleta AS id_atleta, a.p_nombre AS nombres, a.p_apellidos AS apellidos,
                        t.nombre AS torneo_nombre, t.fecha_inicio
                    FROM detalles_participacion dp
                    INNER JOIN atletas a ON dp.codigo_atleta = a.codigo_atleta
                    INNER JOIN participaciones p ON dp.codigo_participacion = p.codigo_participacion
                    INNER JOIN torneos t ON p.codigo_torneo = t.codigo_torneo
                    WHERE 1=1";

            if (!empty($filtro['filtro'])) {
                $p = "%" . trim($filtro['filtro']) . "%";

                $sentencia .= " AND (
                a.p_nombre LIKE :f1 OR 
                a.p_apellidos LIKE :f2 OR 
                t.nombre LIKE :f3
            )";

                $params[':f1'] = $p;
                $params[':f2'] = $p;
                $params[':f3'] = $p;
            }

            $sentencia .= " ORDER BY a.codigo_atleta ASC, t.fecha_inicio DESC";

            $stmt = $conex->prepare($sentencia);
            $stmt->execute($params);
            $datos = $stmt->fetchAll();

            return array('accion' => 'consultar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Estadisticas', $e->getMessage(), 'Modelo_Consultar');
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

            // Verificamos que exista la participación y el atleta
            if (!$this->verificarExistencia('participacion', $this->participacion, 'participaciones', NULL)) {
                throw new Exception(INVALID_ID);
            }
            if (!$this->verificarExistencia('codigo_atleta', $this->atleta, 'atletas', NULL)) {
                throw new Exception(INVALID_ID . '1');
            }

            if (!$this->ModeloParticipaciones->validarParticipacionIndividual($this->participacion, $this->atleta)) {
                throw new Exception(EMPTY_SELECTION);
            }

            if ($this->validarEstadisticaDuplicada($this->participacion, $this->atleta)) {
                throw new Exception(DUPLICATE);
            }

            $sql = "INSERT INTO detalles_participacion 
                    (codigo_participacion, codigo_atleta, goles, asistencias, penalizaciones, goles_contra, partidos_jugados, average) 
                    VALUES (:participacion, :atleta, :goles, :asistencias, :penalizaciones, :goles_c, :partidos, :average)";

            $stmt = $conex->prepare($sql);

            // Asignar parámetros
            $stmt->bindValue(':participacion', $this->participacion, PDO::PARAM_INT);
            $stmt->bindValue(':atleta', $this->atleta, PDO::PARAM_INT);
            $stmt->bindValue(':goles', $this->goles, PDO::PARAM_INT);
            $stmt->bindValue(':asistencias', $this->asistencias, PDO::PARAM_INT);
            $stmt->bindValue(':penalizaciones', $this->penalizaciones, PDO::PARAM_INT);
            $stmt->bindValue(':goles_c', $this->goles_c, PDO::PARAM_INT);
            $stmt->bindValue(':partidos', $this->partidos, PDO::PARAM_INT);
            $stmt->bindValue(':average', $this->average);

            $stmt->execute();
            $conex->commit();

            return array('accion' => 'exito');
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            logs('Estadisticas', $e->getMessage(), 'Modelo_Incluir');
            return array('accion' => 'error', 'codigo' => $e->getMessage());
        } finally {
            $conex = null;
        }
    }

    public function Buscar(): array
    {
        try {
            $conex = $this->conex();
            $sql = "SELECT 
                        dp.codigo_dtll_prtc AS id_estadisticas, 
                        dp.codigo_participacion AS id_torneo,
                        dp.codigo_atleta AS id_atleta, 
                        dp.goles, dp.asistencias, dp.penalizaciones, 
                        dp.partidos_jugados, dp.average, dp.goles_contra,
                        a.p_nombre AS nombres, a.p_apellidos AS apellidos,
                        t.nombre AS torneo_nombre, t.fecha_inicio
                    FROM detalles_participacion dp
                    INNER JOIN atletas a ON dp.codigo_atleta = a.codigo_atleta
                    INNER JOIN participaciones p ON dp.codigo_participacion = p.codigo_participacion
                    INNER JOIN torneos t ON p.codigo_torneo = t.codigo_torneo
                    WHERE dp.codigo_dtll_prtc = :id";

            $stmt = $conex->prepare($sql);
            $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);
            $stmt->execute();
            $datos = $stmt->fetchAll();

            if (!$datos) {
                throw new Exception('Registro de estadísticas no encontrado.');
            }

            return array('accion' => 'buscar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Estadisticas', $e->getMessage(), 'Modelo_Buscar');
            return array('accion' => 'error', 'codigo' => $e->getMessage());
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

            if (!$this->verificarExistencia('id', $this->id, 'detalles_participacion', null)) {
                throw new Exception(INVALID_ID . '2');
            }

            if ($this->ModeloHistorial->verificarHistorialIndividual($this->id) || $this->ModeloHistorial->verificarHistorialGrupal($this->id)) {
                throw new Exception(ASSOCIATES);
            }

            if (!$this->verificarExistencia('participacion', $this->participacion, 'participaciones', null)) {
                throw new Exception(INVALID_ID);
            }
            if (!$this->verificarExistencia('codigo_atleta', $this->atleta, 'atletas', null)) {
                throw new Exception(INVALID_ID . '1');
            }

            if (!$this->ModeloParticipaciones->validarParticipacionIndividual($this->participacion, $this->atleta)) {
                throw new Exception(EMPTY_SELECTION);
            }

            if ($this->validarEstadisticaDuplicadaPropia($this->participacion, $this->atleta, $this->id)) {
                throw new Exception(DUPLICATE);
            }

            $sql = "UPDATE detalles_participacion SET 
                        codigo_participacion = :participacion, 
                        codigo_atleta = :atleta, 
                        goles = :goles, 
                        asistencias = :asistencias, 
                        penalizaciones = :penalizaciones, 
                        goles_contra = :goles_c, 
                        partidos_jugados = :partidos, 
                        average = :average 
                    WHERE codigo_dtll_prtc = :id";

            $stmt = $conex->prepare($sql);
            $stmt->bindValue(':participacion', $this->participacion, PDO::PARAM_INT);
            $stmt->bindValue(':atleta', $this->atleta, PDO::PARAM_INT);
            $stmt->bindValue(':goles', $this->goles, PDO::PARAM_INT);
            $stmt->bindValue(':asistencias', $this->asistencias, PDO::PARAM_INT);
            $stmt->bindValue(':penalizaciones', $this->penalizaciones, PDO::PARAM_INT);
            $stmt->bindValue(':goles_c', $this->goles_c, PDO::PARAM_INT);
            $stmt->bindValue(':partidos', $this->partidos, PDO::PARAM_INT);
            $stmt->bindValue(':average', $this->average);
            $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);

            $stmt->execute();
            $conex->commit();

            return array('accion' => 'exito');
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            logs('Estadisticas', $e->getMessage(), 'Modelo_Modificar');
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

            if (!$this->verificarExistencia('id', $this->id, 'detalles_participacion', null)) {
                throw new Exception(INVALID_ID);
            }

            $sql = "DELETE FROM detalles_participacion WHERE codigo_dtll_prtc = :id";
            $stmt = $conex->prepare($sql);
            $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);
            $stmt->execute();

            $conex->commit();
            return array('accion' => 'exito');
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            logs('Estadisticas', $e->getMessage(), 'Modelo_Eliminar');
            return array('accion' => 'error', 'codigo' => $e->getMessage());
        } finally {
            $conex = null;
        }
    }

    public function validarEstadisticaDuplicada(int $id_participacion, int $id_atleta): bool
    {
        $conex = null;
        try {
            $conex = $this->conex();
            $stmt = $conex->prepare(
                "SELECT COUNT(*) FROM detalles_participacion 
                WHERE codigo_participacion = :id_participacion AND codigo_atleta = :id_atleta"
            );
            $stmt->bindValue(':id_participacion', $id_participacion, PDO::PARAM_INT);
            $stmt->bindValue(':id_atleta', $id_atleta, PDO::PARAM_INT);
            $stmt->execute();

            return ((int)$stmt->fetchColumn() > 0);
        } catch (Exception $e) {
            logs('Estadisticas', $e->getMessage(), 'ModeloEstadisticas_validarEstadisticaDuplicada');
            throw $e;
        } finally {
            $conex = null;
        }
    }

    public function validarEstadisticaDuplicadaPropia(int $id_participacion, int $id_atleta, int $id): bool
    {
        $conex = null;
        try {
            $conex = $this->conex();
            $stmt = $conex->prepare(
                "SELECT COUNT(*) FROM detalles_participacion 
            WHERE codigo_participacion = :id_participacion AND codigo_atleta = :id_atleta AND codigo_dtll_prtc != :id"
            );
            $stmt->bindValue(':id_participacion', $id_participacion, PDO::PARAM_INT);
            $stmt->bindValue(':id_atleta', $id_atleta, PDO::PARAM_INT);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            return ((int)$stmt->fetchColumn() > 0);
        } catch (Exception $e) {
            logs('Estadisticas', $e->getMessage(), 'ModeloEstadisticas_validarEstadisticaDuplicadaPropia');
            throw $e;
        } finally {
            $conex = null;
        }
    }
}