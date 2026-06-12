<?php

namespace App\modelo;

use PDO;
use Exception;

class ModeloParticipaciones extends ModeloBase
{
    public function __construct()
    {
        parent::__construct();
        $this->campoWhitelist = [
            'id_participacion' => 'id_participacion',
            'id_equipo' => 'id_equipo',
            'id_torneo' => 'id_torneo'
        ];
        $this->llavePrimaria = 'id_participacion';
    }

    public function ProcesarDatos(array $datos): array
    {
        // Implementación base si es requerida
        return [];
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
}
