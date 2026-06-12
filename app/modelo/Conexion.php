<?php

namespace App\modelo;

use PDO;
use PDOException;
use Exception;

abstract class Conexion
{
    private static $instancia = null;
    private static $instanciaSG = null;

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

    private static function crearConexion($host, $name, $user, $pass)
    {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $name);
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 2,
        ];

        try {
            return new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            error_log('Error de Conexion: ' . $e->getMessage());
            throw new Exception(DB_CONNECTION);
        }
    }
}