<?php

namespace App\modelo;

use PDO;
use Exception;

class ModeloHistorial extends Conexion
{
    public function __construct()
    {
        parent::__construct();
    }

    public function ProcesarDatos(array $datos): array
    {
        return [];
    }

    public function consultarCurriculum(int $id_atleta): array
    {
        $conex = null;
        try {
            $conex = $this->conex();

            // 1. Obtener información personal, física, de identidad y de inscripción
            $sqlAtleta = "SELECT 
                            a.codigo_atleta,
                            a.fecha_nac,
                            a.foto,
                            TRIM(CONCAT(a.p_nombre, ' ', COALESCE(a.s_nombre, ''))) AS nombres,
                            TRIM(CONCAT(a.p_apellidos, ' ', COALESCE(a.s_apellidos, ''))) AS apellidos,
                            c.nombre AS nombre_categoria, 
                            p.nombre AS nombre_posicion,
                            CONCAT(ia.tipo_doc, '-', ia.numero_doc) AS doc_identidad,
                            i.dorsal,
                            i.peso_kg,
                            i.estatura_cm
                          FROM atletas a 
                          LEFT JOIN inscripciones i ON a.codigo_atleta = i.codigo_atleta 
                          LEFT JOIN categorias c ON i.codigo_categoria = c.codigo_categoria 
                          LEFT JOIN posiciones p ON i.codigo_posicion = p.codigo_posicion 
                          LEFT JOIN identidad_atleta ia ON a.codigo_atleta = ia.codigo_atleta
                          WHERE a.codigo_atleta = :id_atleta
                          ORDER BY i.fecha_inscripcion DESC LIMIT 1";
            
            $stmt = $conex->prepare($sqlAtleta);
            $stmt->bindValue(':id_atleta', $id_atleta, PDO::PARAM_INT);
            $stmt->execute();
            $atleta = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$atleta) {
                return [];
            }

            // 2. Obtener estadísticas por torneo desde detalles_participacion
            $sqlEstadisticas = "SELECT dp.*, t.nombre as nombre_torneo, t.codigo_torneo as id_torneo, t.fecha_inicio, t.fecha_fin, t.ubicacion 
                                FROM detalles_participacion dp 
                                INNER JOIN participaciones pr ON dp.codigo_participacion = pr.codigo_participacion 
                                INNER JOIN torneos t ON pr.codigo_torneo = t.codigo_torneo 
                                WHERE dp.codigo_atleta = :id_atleta
                                ORDER BY t.fecha_inicio DESC";
            $stmt = $conex->prepare($sqlEstadisticas);
            $stmt->bindValue(':id_atleta', $id_atleta, PDO::PARAM_INT);
            $stmt->execute();
            $estadisticas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 3. Obtener palmarés individual
            $sqlPalmaresInd = "SELECT pi.*, p.nombre as nombre_premio, p.tipo as tipo_premio, t.nombre as nombre_torneo, t.codigo_torneo as id_torneo
                               FROM palmares_individual pi
                               INNER JOIN premios p ON pi.codigo_premio = p.codigo_premio
                               INNER JOIN detalles_participacion dp ON pi.codigo_dtll_prtc = dp.codigo_dtll_prtc
                               INNER JOIN participaciones pr ON dp.codigo_participacion = pr.codigo_participacion
                               INNER JOIN torneos t ON pr.codigo_torneo = t.codigo_torneo
                               WHERE dp.codigo_atleta = :id_atleta";
            $stmt = $conex->prepare($sqlPalmaresInd);
            $stmt->bindValue(':id_atleta', $id_atleta, PDO::PARAM_INT);
            $stmt->execute();
            $palmaresIndividual = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 4. Obtener palmarés grupal
            $sqlPalmaresGrp = "SELECT pg.*, p.nombre as nombre_premio, p.tipo as tipo_premio, eq.nombre as nombre_equipo, t.codigo_torneo as id_torneo
                               FROM palmares_grupal pg
                               INNER JOIN premios p ON pg.codigo_premio = p.codigo_premio
                               INNER JOIN participaciones pr ON pg.codigo_participacion = pr.codigo_participacion
                               INNER JOIN detalles_participacion dp ON pr.codigo_participacion = dp.codigo_participacion
                               INNER JOIN equipos eq ON pr.codigo_equipo = eq.codigo_equipo
                               INNER JOIN torneos t ON pr.codigo_torneo = t.codigo_torneo
                               WHERE dp.codigo_atleta = :id_atleta";
            $stmt = $conex->prepare($sqlPalmaresGrp);
            $stmt->bindValue(':id_atleta', $id_atleta, PDO::PARAM_INT);
            $stmt->execute();
            $palmaresGrupal = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $totales = [
                'goles'           => 0,
                'asistencias'     => 0,
                'penalizaciones'  => 0,
                'goles_contra'    => 0,
                'partidos_jugados'=> 0,
                'average'         => 0
            ];

            $torneos = [];

            foreach ($estadisticas as $e) {
                $id_torneo = $e['id_torneo'];

                $totales['goles']            += (int)$e['goles'];
                $totales['asistencias']      += (int)$e['asistencias'];
                $totales['penalizaciones']   += (int)$e['penalizaciones'];
                $totales['partidos_jugados'] += (int)$e['partidos_jugados'];
                $totales['goles_contra']     += (int)$e['goles_contra'];

                $premiosInd = [];
                foreach ($palmaresIndividual as $pi) {
                    if ($pi['id_torneo'] == $id_torneo) {
                        $premiosInd[] = $pi['nombre_premio'];
                    }
                }

                $premiosGrp = [];
                foreach ($palmaresGrupal as $pg) {
                    if (isset($pg['id_torneo']) && $pg['id_torneo'] == $id_torneo) {
                        $premiosGrp[] = $pg['nombre_premio'] . " (" . $pg['nombre_equipo'] . ")";
                    }
                }

                $torneos[] = [
                    'nombre_torneo'        => $e['nombre_torneo'],
                    'fecha_fin'            => $e['fecha_fin'],
                    'partidos_jugados'     => $e['partidos_jugados'] ?? 0, 
                    'goles'                => $e['goles'],
                    'asistencias'          => $e['asistencias'],
                    'penalizaciones'       => $e['penalizaciones'],
                    'goles_contra'         => $e['goles_contra'],
                    'average'              => $e['average'] ?? 0,
                    'premios_individuales' => $premiosInd,
                    'premios_grupales'     => $premiosGrp
                ];
            }

            if (count($estadisticas) > 0) {
                $totales['average'] = array_sum(array_column($estadisticas, 'average')) / count($estadisticas);
            }

            return [
                'atleta'   => $atleta,
                'totales'  => $totales,
                'torneos'  => $torneos
            ];
        } catch (Exception $e) {
            // Reemplaza esto por tu logica de logs si "logs()" no está definida globalmente
            error_log('Historial: ' . $e->getMessage() . ' en ModeloHistorial_consultarCurriculum');
            throw $e;
        } finally {
            $conex = null;
        }
    }

    public function verificarHistorialIndividual(int $id_individual): bool
    {
        $conex = null;
        try {
            $conex = $this->conex();
            $stmt = $conex->prepare("SELECT COUNT(*) FROM palmares_individual WHERE codigo_individual = :id");
            $stmt->bindValue(':id', $id_individual, PDO::PARAM_INT);
            $stmt->execute();
            return ((int)$stmt->fetchColumn() > 0);
        } catch (Exception $e) {
            logs('Historial', $e->getMessage(), 'ModeloHistorial_verificarHistorialIndividual');
            throw $e;
        } finally {
            $conex = null;
        }
    }

    public function verificarHistorialGrupal(int $id_grupal): bool
    {
        $conex = null;
        try {
            $conex = $this->conex();
            $stmt = $conex->prepare("SELECT COUNT(*) FROM palmares_grupal WHERE codigo_grupal = :id");
            $stmt->bindValue(':id', $id_grupal, PDO::PARAM_INT);
            $stmt->execute();
            return ((int)$stmt->fetchColumn() > 0);
        } catch (Exception $e) {
            logs('Historial', $e->getMessage(), 'ModeloHistorial_verificarHistorialGrupal');
            throw $e;
        } finally {
            $conex = null;
        }
    }
}