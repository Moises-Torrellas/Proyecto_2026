<?php

namespace App\modelo;

use PDO;
use PDOException;
use Exception;

abstract class Conexion
{
    private static $instancia = null;
    private static $instanciaSG = null;
    
    //Se declaran estas propiedades para evitar el error de "propiedades dinámicas" en PHP modernos.
    protected $conex = null;
    protected $conexSG = null;



    protected function __construct() {}

    protected function conex()
    {
        return self::$instancia ??= self::crearConexion(_DB_HOST_, _DB_NAME_, _DB_USER_, _DB_PASS_);
    }

    protected function conexSG()
    {
        return self::$instanciaSG ??= self::crearConexion(_DB_HOST_SG_, _DB_NAME_SG_, _DB_USER_SG_, _DB_PASS_SG_);
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