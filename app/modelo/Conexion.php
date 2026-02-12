<?php

namespace App\modelo;

use PDO;
use PDOException;

class Conexion
{
    private static $instancia = null;
    private static $instanciaSG = null;

    private function __construct() {}

    /* public static function getConex()
	{
		if (self::$instancia === null) {
			$host = _DB_HOST_;
			$name = _DB_NAME_;
			$user = _DB_USER_;
			$pass = _DB_PASS_;

			self::$instancia = self::crearConexion($host, $name, $user, $pass);
		}

		return self::$instancia;
	} */

    public static function getConexSG()
    {
        if (self::$instanciaSG === null) {
            $host = _DB_HOST_SG_;
            $name = _DB_NAME_SG_;
            $user = _DB_USER_SG_;
            $pass = _DB_PASS_SG_;

            self::$instanciaSG = self::crearConexion($host, $name, $user, $pass);
        }

        return self::$instanciaSG;
    }

    /* public static function conex()
	{
		return self::getConex();
	} */

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
        ];

        try {
            return new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            error_log('Error de Conexion: ' . $e->getMessage());
            throw $e;
        }
    }
}
