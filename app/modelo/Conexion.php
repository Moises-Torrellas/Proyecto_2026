<?php

namespace App\modelo;

use PDO;
use PDOException;
use Exception;

abstract class Conexion
{
    private static $instancia = null;
    private static $instanciaSG = null;
    
    // CORRECCIÓN 1: Se declaran estas propiedades para evitar el error de "propiedades dinámicas" en PHP modernos.
    protected $conex = null;
    protected $conexSG = null;

    protected $llavePrimaria;
    protected $campoWhitelist = [];

    protected function __construct() {}

    public static function getConex()
    {
        if (self::$instancia === null) {
            self::$instancia = self::crearConexion(_DB_HOST_, _DB_NAME_, _DB_USER_, _DB_PASS_);
        }
        return self::$instancia;
    }

    public static function getConexSG()
    {
        if (self::$instanciaSG === null) {
            self::$instanciaSG = self::crearConexion(_DB_HOST_SG_, _DB_NAME_SG_, _DB_USER_SG_, _DB_PASS_SG_);
        }
        return self::$instanciaSG;
    }

    public static function conex()
    {
        return self::getConex();
    }

    public static function conexSG()
    {
        return self::getConexSG();
    }

    protected function getConexion(string $db = 'general')
    {
        try {
            if ($db === 'general') {
                if ($this->conex === null) {
                    $this->conex = self::conex();
                }
                return $this->conex;
            } else {
                if ($this->conexSG === null) {
                    $this->conexSG = self::conexSG();
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
                $stmt->bindValue(':estatus', $estatus, PDO::PARAM_INT);
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
            // CORRECCIÓN 3: Se añade la misma validación de array_key_exists que tenías arriba para evitar el warning de "Undefined array key"
            if (!array_key_exists($campo, $this->campoWhitelist)) {
                throw new Exception("Campo [$campo] no permitido en whitelist.");
            }

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
                $stmt->bindValue(':estatus', $estatus, PDO::PARAM_INT);
            }

            $stmt->execute();

            return (int)$stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    private static function crearConexion($host, $name, $user, $pass)
    {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $name);
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 1,
        ];

        try {
            return new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            error_log('Error de Conexion: ' . $e->getMessage());
            throw new Exception(DB_CONNECTION);
        }
    }
}