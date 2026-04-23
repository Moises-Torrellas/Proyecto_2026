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

    protected function verificarExistencia(string $campo, $valor, string $tabla, ?int $estatus = 1, string $db = 'general'): bool
    {
        try {
            if (!array_key_exists($campo, $this->campoWhitelist)) {
                throw new Exception("Campo [$campo] no permitido en whitelist.");
            }

            $columna = $this->campoWhitelist[$campo];

            // 1. Iniciamos la consulta base
            $sql = "SELECT COUNT(*) FROM `{$tabla}` WHERE $columna = :valor";

            // 2. Si se proporcionó un estatus, lo añadimos a la consulta
            if ($estatus !== null) {
                $sql .= " AND estatus = :estatus";
            }

            $stmt = $this->getConexion($db)->prepare($sql);
            $stmt->bindValue(':valor', $valor);

            // 3. Solo vinculamos el estatus si lo incluimos en el SQL
            if ($estatus !== null) {
                $stmt->bindValue(':estatus', $estatus, PDO::PARAM_INT);
            }

            $stmt->execute();
            return (int)$stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("[" . get_class($this) . "][verificarExistencia] " . $e->getMessage());
            return false;
        }
    }

    protected function verificarExistenciaPropia(string $campo, $valor, $id, string $tabla, ?int $estatus = 1, string $db = 'general'): bool
    {
        try {
            $columna = $this->campoWhitelist[$campo];

            // Consulta pura: ¿El registro con mi ID tiene este valor?
            $sql = "SELECT COUNT(*) FROM `{$tabla}` 
                WHERE {$this->llavePrimaria} = :id 
                AND $columna = :valor";

            // Solo añadimos estatus si la tabla lo usa (en representantes envías NULL)
            if ($estatus !== null) {
                $sql .= " AND estatus = :estatus";
            }

            $stmt = $this->getConexion($db)->prepare($sql);
            $stmt->bindValue(':id', $id);
            $stmt->bindValue(':valor', $valor);

            if ($estatus !== null) {
                $stmt->bindValue(':estatus', $estatus, PDO::PARAM_INT);
            }

            $stmt->execute();
            return (int)$stmt->fetchColumn() > 0; // TRUE: Me pertenece, FALSE: No me pertenece

        } catch (Exception $e) {
            error_log("Error en pertenencia: " . $e->getMessage());
            return false;
        }
    }
}
