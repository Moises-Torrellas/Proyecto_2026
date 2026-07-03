<?php

namespace App\modelo;

use Exception;
use PDO;

class ModeloPrincipal extends Conexion
{

    public function __construct()
    {}

public function ConsultarTarjetas(): array
    {
        $conex = null;
        try {
            $conex = $this->conex();

            // 1. Atletas Activos (Última inscripción con estatus = 1)
            $sqlActivos = "SELECT COUNT(*) FROM inscripciones i 
                        INNER JOIN (SELECT codigo_atleta, MAX(codigo_inscripcion) as max_id 
                                    FROM inscripciones GROUP BY codigo_atleta) ult 
                        ON i.codigo_inscripcion = ult.max_id 
                        WHERE i.estatus = 1";
            $activos = $conex->query($sqlActivos)->fetchColumn();

            // 2. Cargos Pendientes (estatus = 1)
            $sqlCargos = "SELECT COUNT(*) FROM cargos WHERE estatus = 1";
            $cargos = $conex->query($sqlCargos)->fetchColumn();

            // 3. Participaciones en Torneos (Cantidad de torneos únicos)
            $sqlTorneos = "SELECT COUNT(DISTINCT codigo_torneo) FROM participaciones";
            $torneos = $conex->query($sqlTorneos)->fetchColumn();

            // 4. Asignaciones con estatus = 1
            $sqlAsign = "SELECT COUNT(*) FROM asignaciones WHERE estatus = 1";
            $asignaciones = $conex->query($sqlAsign)->fetchColumn();

            $resultados = [
                'activos' => (int)$activos,
                'cargos' => (int)$cargos,
                'torneos' => (int)$torneos,
                'asignaciones' => (int)$asignaciones
            ];

            return array('accion' => 'exito', 'datos' => $resultados);

        } catch (Exception $e) {
            logs('Dashboard', $e->getMessage(), 'Modelo_ConsultarTarjetas');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = null;
        }
    }

    public function ConsultarGraficos(): array
    {
        $conex = null;
        try {
            $conex = $this->conex();

            // CTE/Subconsulta para atletas activos
            $baseActivos = "SELECT i.codigo_atleta, i.codigo_categoria 
                            FROM inscripciones i 
                            INNER JOIN (SELECT codigo_atleta, MAX(codigo_inscripcion) as max_id 
                                        FROM inscripciones GROUP BY codigo_atleta) ult 
                            ON i.codigo_inscripcion = ult.max_id 
                            WHERE i.estatus = 1";

            // Atletas Solventes vs Deudores
            $totalActivos = $conex->query("SELECT COUNT(*) FROM ($baseActivos) as a")->fetchColumn();
            
            // Contamos cuántos de los activos tienen al menos 1 cargo pendiente (estatus 1)
            $sqlDeudores = "SELECT COUNT(DISTINCT a.codigo_atleta) 
                            FROM ($baseActivos) as a 
                            INNER JOIN cargos c ON a.codigo_atleta = c.codigo_atleta 
                            WHERE c.estatus = 1";
            $deudores = $conex->query($sqlDeudores)->fetchColumn();
            
            $solventes = $totalActivos - $deudores;
            $porcentaje = $totalActivos > 0 ? round(($solventes * 100) / $totalActivos, 2) : 0;

            // Atletas Activos por Categoría
            $sqlCategorias = "SELECT c.nombre, COUNT(a.codigo_atleta) as cantidad 
                            FROM categorias c 
                            LEFT JOIN ($baseActivos) as a ON c.codigo_categoria = a.codigo_categoria 
                            GROUP BY c.codigo_categoria 
                            HAVING cantidad > 0";
            $categorias = $conex->query($sqlCategorias)->fetchAll(PDO::FETCH_ASSOC);

            $resultados = [
                'solvencia' => [
                    'solventes' => (int)$solventes,
                    'deudores' => (int)$deudores,
                    'porcentaje' => $porcentaje
                ],
                'categorias' => $categorias
            ];

            return array('accion' => 'exito', 'datos' => $resultados);

        } catch (Exception $e) {
            logs('Dashboard', $e->getMessage(), 'Modelo_ConsultarGraficos');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = null;
        }
    }

    public function ConsultarCalendario(): array
    {
        $conex = null;
        try {
            $conex = $this->conex();
            $eventos = [];
            $anio_actual = (int)date('Y'); // Toma el año en curso para los cumpleaños

            // 1. Obtener Torneos
            $torneos = $conex->query("SELECT codigo_torneo as id, nombre, fecha_inicio, fecha_fin FROM torneos")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($torneos as $t) {
                $eventos[] = [
                    'id' => 'T-' . $t['id'],
                    'title' => '🏆 ' . $t['nombre'],
                    'start' => $t['fecha_inicio'],
                    'end' => date('Y-m-d', strtotime($t['fecha_fin'] . ' +1 day')), // Requerido por FullCalendar
                    'color' => '#d35400',
                    'tipo' => 'torneo'
                ];
            }

            // 2. Cumpleaños (Solo atletas activos)
            $sqlCumples = "SELECT a.codigo_atleta as id, a.p_nombre, a.p_apellidos, a.fecha_nac 
                        FROM atletas a 
                        INNER JOIN inscripciones i ON a.codigo_atleta = i.codigo_atleta 
                        INNER JOIN (SELECT codigo_atleta, MAX(codigo_inscripcion) as max_id 
                                    FROM inscripciones GROUP BY codigo_atleta) ult 
                        ON i.codigo_inscripcion = ult.max_id 
                        WHERE i.estatus = 1 AND a.fecha_nac IS NOT NULL";
            $atletas = $conex->query($sqlCumples)->fetchAll(PDO::FETCH_ASSOC);

            foreach ($atletas as $a) {
                $anio_nac = (int)date('Y', strtotime($a['fecha_nac']));
                $edad = $anio_actual - $anio_nac;
                $fecha_evento = $anio_actual . date('-m-d', strtotime($a['fecha_nac']));

                $eventos[] = [
                    'id' => 'C-' . $a['id'],
                    'title' => '🎂 Cumpleaños de ' . $a['p_nombre'] . ' ' . $a['p_apellidos'] . ' (' . $edad . ' años)',
                    'start' => $fecha_evento,
                    'color' => '#2980b9',
                    'display' => 'list-item',
                    'tipo' => 'cumple'
                ];
            }

            return array('accion' => 'exito', 'datos' => $eventos);

        } catch (Exception $e) {
            logs('Dashboard', $e->getMessage(), 'Modelo_ConsultarCalendario');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = null;
        }
    }

    
}