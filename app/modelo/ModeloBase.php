<?php

namespace App\modelo;

use App\modelo\Conexion;
use Exception;
use PDOException;

abstract class ModeloBase extends Conexion
{
    protected $conexSG = null;
    protected $conex = null;
    protected $llavePrimaria;
    protected $campoWhitelist = [];
    public function __construct() {}
   

    // Tu método protegido con el control de errores centralizado
    protected function getConexion(string $db = 'general')
    {
        try {
            if ($db === 'general') {
                if ($this->conex === null) {
                    $this->conex = parent::conex();
                }
                return $this->conex;
            } else {
                if ($this->conexSG === null) {
                    $this->conexSG = parent::conexSG();
                }
                return $this->conexSG;
            }
        } catch (PDOException $e) {
            throw new Exception(DB_CONNECTION);
        }
    }

    protected function verificarExistencia(string $campo, $valor, string $tabla, ?int $estatus = 1, string $db = 'general', bool $bloquear = false): bool
    {
        try {
            if (!array_key_exists($campo, $this->campoWhitelist)) {
                throw new Exception("Campo [$campo] no permitido en whitelist.");
            }

            $columna = $this->campoWhitelist[$campo];

            $sql = "SELECT COUNT(*) FROM `{$tabla}` WHERE $columna = :valor";

            if ($estatus !== null) {
                $sql .= " AND estatus = :estatus";
            }

            if ($bloquear) {
                $sql .= " FOR UPDATE";
            }

            // Aquí se gatilla la conexión perezosa
            $stmt = $this->getConexion($db)->prepare($sql);
            $stmt->bindValue(':valor', $valor);

            if ($estatus !== null) {
                $stmt->bindValue(':estatus', $estatus, \PDO::PARAM_INT);
            }

            $stmt->execute();
            return (int)$stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    protected function verificarExistenciaPropia(string $campo, $valor, $id, string $tabla, ?int $estatus = 1, string $db = 'general', bool $bloquear = false): bool
    {
        try {
            $columna = $this->campoWhitelist[$campo];

            $sql = "SELECT COUNT(*) FROM `{$tabla}` 
                WHERE {$this->llavePrimaria} = :id 
                AND $columna = :valor";

            if ($estatus !== null) {
                $sql .= " AND estatus = :estatus";
            }

            if ($bloquear) {
                $sql .= " FOR UPDATE";
            }

            // Aquí se gatilla la conexión perezosa
            $stmt = $this->getConexion($db)->prepare($sql);
            $stmt->bindValue(':id', $id);
            $stmt->bindValue(':valor', $valor);

            if ($estatus !== null) {
                $stmt->bindValue(':estatus', $estatus, \PDO::PARAM_INT);
            }

            $stmt->execute();

            return (int)$stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
}
