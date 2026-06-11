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

    /**
     * Verifica si un palmarés individual forma parte de un historial.
     * Retorna true si está en el historial (y por ende bloqueado), false si no.
     */
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
