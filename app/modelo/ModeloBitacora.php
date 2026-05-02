<?php

namespace App\modelo;

use App\modelo\Conexion;
use App\interface\InterBitacora;
use Exception;
use PDOException;

class ModeloBitacora extends Conexion implements InterBitacora
{

    private $conexion = null;

    public function __construct() {}

    public function RegistrarAccion($id_modulo, $accion, $id_usuario)
    {
        try{
            $this->conexion = self::conexSG();
            $sql = 'INSERT INTO `bitacora`(`id_modulo`, `acciones`, `fecha`, `hora`, `idUsuario`) 
                            VALUES (:modulo,:accion,:fecha,:hora,:usuario)';
            $stmt = $this->conexion->prepare($sql);
            $parametros = [
                ':modulo' => $id_modulo,
                ':accion' => $accion,
                ':fecha' => date('Y-m-d'),
                ':hora' => date('H:i:s'),
                ':usuario' => $id_usuario
            ];
            $stmt->execute($parametros);

        }catch(PDOException $e){
            echo "Error al registrar la acción en la bitácora: " . $e->getMessage();
        }finally{
            $this->conexion = null;
        }
    }

    public function Consultar(array $filtro = []): array
{
    try {
        $conex = $this->conexSG();
        $params = [];

        // 1. Iniciamos la sentencia con los JOINs necesarios
        $sentencia = "SELECT 
                        b.id_bitacora,
                        u.nombreUsuario,
                        u.apellidoUsuario,
                        u.cedulaUsuario,
                        m.nombre_modulo,
                        b.acciones,
                        b.fecha,
                        b.hora 
                    FROM bitacora b
                    INNER JOIN usuarios u ON u.idUsuario = b.idUsuario
                    INNER JOIN modulo m ON m.id_modulo = b.id_modulo
                    WHERE 1=1";

        // 2. BUSCADOR GENERAL (Filtra por nombre de usuario, cédula o acción)
        if (!empty($filtro['filtro'])) {
            $p = "%" . $filtro['filtro'] . "%";
            $sentencia .= " AND (
                            u.nombreUsuario LIKE :f1 OR 
                            u.cedulaUsuario LIKE :f2 OR 
                            b.acciones LIKE :f3 OR
                            m.nombre_modulo LIKE :f4
                        )";
            $params[':f1'] = $p;
            $params[':f2'] = $p;
            $params[':f3'] = $p;
            $params[':f4'] = $p;
        }

        $sentencia .= " ORDER BY b.id_bitacora ASC";

        $stmt = $conex->prepare($sentencia);
        $stmt->execute($params);

        $datos = $stmt->fetchAll();

        return array('accion' => 'consultar', 'datos' => $datos);

    } catch (Exception $e) {
        // Asegúrate de usar logs() con 's' para evitar el error previo
        logs('Bitacora', $e->getMessage(), 'Modelo_Consultar'); 
        return array('accion' => 'error', 'mensaje' => 'Error al listar bitácora: ' . $e->getMessage());
    } finally {
        $conex = NULL;
    }
}
}
