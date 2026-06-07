<?php

namespace App\modelo;

use App\modelo\Conexion;
use \PDO;
use \Exception;

abstract class ModeloBase extends Conexion
{
    protected $conexSG;
    protected $conex;
    protected $llavePrimaria;
    protected $campoWhitelist = [];

    public function __construct()
    {
        // Inicializamos ambas conexiones desde la clase padre
        $this->conexSG = parent::conexSG();
        $this->conex = parent::conex();
    }

    protected function getConexion(string $db = 'general')
    {
        return ($db === 'general') ? $this->conex : $this->conexSG;
    }

    protected function verificarExistencia(string $campo, $valor, string $tabla, ?int $estatus = 1, string $db = 'general', bool $bloquear = false): bool
    {
        try {
            if (!array_key_exists($campo, $this->campoWhitelist)) {
                throw new Exception("Campo [$campo] no permitido en whitelist.");
            }

            $columna = $this->campoWhitelist[$campo];

            // 1. Construimos la consulta
            $sql = "SELECT COUNT(*) FROM `{$tabla}` WHERE $columna = :valor";

            if ($estatus !== null) {
                $sql .= " AND estatus = :estatus";
            }

            // 2. Aplicamos FOR UPDATE si se solicita (requiere transacción activa)
            if ($bloquear) {
                $sql .= " FOR UPDATE";
            }

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

            // Bloqueo preventivo de la fila específica por su llave primaria
            if ($bloquear) {
                $sql .= " FOR UPDATE";
            }

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
