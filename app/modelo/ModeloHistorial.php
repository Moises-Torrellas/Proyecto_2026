<?php

namespace App\modelo;

use PDO;
use Exception;

class ModeloHistorial extends ModeloBase
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

            // 1. Obtener información personal del atleta
            $sqlAtleta = "SELECT a.*, c.nombre as nombre_categoria , p.nombre as nombre_posicion
                      FROM atletas a 
                      LEFT JOIN categorias c ON a.id_categoria = c.id_categorias 
                      LEFT JOIN posiciones p ON a.id_posicion = p.id_posicion 
                      WHERE a.id_atleta = :id_atleta";
            $stmt = $conex->prepare($sqlAtleta);
            $stmt->bindValue(':id_atleta', $id_atleta, PDO::PARAM_INT);
            $stmt->execute();
            $atleta = $stmt->fetch();

            if (!$atleta) {
                return [];
            }

            // 2. Obtener estadísticas por torneo
            $sqlEstadisticas = "SELECT e.*, t.nombre as nombre_torneo, t.fecha_inicio, t.fecha_fin, t.ubicacion 
                            FROM estadisticas e 
                            INNER JOIN torneos t ON e.id_torneo = t.id_torneo 
                            WHERE e.id_atleta = :id_atleta
                            ORDER BY t.fecha_inicio DESC";
            $stmt = $conex->prepare($sqlEstadisticas);
            $stmt->bindValue(':id_atleta', $id_atleta, PDO::PARAM_INT);
            $stmt->execute();
            $estadisticas = $stmt->fetchAll();

            $sqlPalmaresInd = "SELECT pi.*, p.nombre as nombre_premio, p.tipo as tipo_premio, t.nombre as nombre_torneo, pa.id_torneo
                           FROM palmares_individual pi
                           INNER JOIN premios p ON pi.id_premio = p.id_premio
                           LEFT JOIN palmares pa ON pi.id_palmares = pa.id_palmares
                           LEFT JOIN torneos t ON pa.id_torneo = t.id_torneo
                           WHERE pi.id_atleta = :id_atleta";
            $stmt = $conex->prepare($sqlPalmaresInd);
            $stmt->bindValue(':id_atleta', $id_atleta, PDO::PARAM_INT);
            $stmt->execute();
            $palmaresIndividual = $stmt->fetchAll();

            $sqlPalmaresGrp = "SELECT pg.*, p.nombre as nombre_premio, p.tipo as tipo_premio, eq.nombre as nombre_equipo, pa.id_torneo
                   FROM palmares_grupal pg
                   INNER JOIN premios p ON pg.id_premio = p.id_premio
                   INNER JOIN detalles_equipos de ON pg.id_equipo = de.id_equipo
                   INNER JOIN equipos eq ON pg.id_equipo = eq.id_equipos
                   LEFT JOIN palmares pa ON pg.id_palmares = pa.id_palmares
                   WHERE de.id_atleta = :id_atleta";
            $stmt = $conex->prepare($sqlPalmaresGrp);
            $stmt->bindValue(':id_atleta', $id_atleta, PDO::PARAM_INT);
            $stmt->execute();
            $palmaresGrupal = $stmt->fetchAll();

            // ==========================================================================
            // PROCESAMIENTO Y UNIFICACIÓN PARA LA VISTA
            // ==========================================================================
            $totales = [
                'goles'           => 0,
                'asistencias'     => 0,
                'penalizaciones'  => 0,
                'goles_contra'    => 0,
                'partidos_jugados'    => 0,
                'average'         => 0
            ];

            $torneos = [];

            foreach ($estadisticas as $e) {
                $id_torneo = $e['id_torneo'];

                // Acumular Totales Generales
                $totales['goles']          += (int)$e['goles'];
                $totales['asistencias']    += (int)$e['asistencias'];
                $totales['penalizaciones'] += (int)$e['penalizaciones'];
                $totales['partidos_jugados'] += (int)$e['partidos_jugados'];
                $totales['goles_contra']   += (int)$e['goles_contra'];

                // Filtrar Premios Individuales pertenecientes a este torneo
                $premiosInd = [];
                foreach ($palmaresIndividual as $pi) {
                    if ($pi['id_torneo'] == $id_torneo) {
                        $premiosInd[] = $pi['nombre_premio'];
                    }
                }

                // Filtrar Premios Grupales pertenecientes a este torneo
                $premiosGrp = [];
                foreach ($palmaresGrupal as $pg) {
                    if (isset($pg['id_torneo']) && $pg['id_torneo'] == $id_torneo) {
                        $premiosGrp[] = $pg['nombre_premio'] . " (" . $pg['nombre_equipo'] . ")";
                    }
                }

                // Estructura que requiere la Vista HTML interna del bucle
                $torneos[] = [
                    'nombre_torneo'        => $e['nombre_torneo'],
                    'fecha_fin'            => $e['fecha_fin'],
                    'partidos_jugados'     => $e['partidos_jugados'] ?? 0, // Asegura que exista esta columna
                    'goles'                => $e['goles'],
                    'asistencias'          => $e['asistencias'],
                    'penalizaciones'       => $e['penalizaciones'],
                    'goles_contra'         => $e['goles_contra'],
                    'average'              => $e['average'] ?? 0,
                    'premios_individuales' => $premiosInd,
                    'premios_grupales'     => $premiosGrp
                ];
            }

            // Calcular el promedio/average general del atleta basado en todos sus torneos
            if (count($estadisticas) > 0) {
                $totales['average'] = array_sum(array_column($estadisticas, 'average')) / count($estadisticas);
            }

            // Retornamos los datos estructurados exactamente como los pide la vista
            return [
                'atleta'   => $atleta,
                'totales'  => $totales,
                'torneos'  => $torneos
            ];
        } catch (Exception $e) {
            logs('Historial', $e->getMessage(), 'ModeloHistorial_consultarCurriculum');
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
            $stmt = $conex->prepare("SELECT COUNT(*) FROM historial_p_ind WHERE id_p_ind = :id");
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
            $stmt = $conex->prepare("SELECT COUNT(*) FROM historial_p_grp WHERE id_p_grp = :id");
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
