<?php

namespace App\modelo;

use App\interface\InterBitacora;
use Exception;

class ModeloBitacora extends Conexion implements InterBitacora
{

    public function __construct() {}

    public function RegistrarAccion($id_modulo, $accion, $id_usuario, $datos_previos = '', $datos_nuevos = '', $entorno = '')
    {
        $conex = null;
        try{
            $conex = $this->conexSG();
            $conex->beginTransaction();
            $sql = 'INSERT INTO `bitacora`(`id_modulo`, `acciones`, `datos_previos`, `datos_nuevos`, `entorno`, `fecha_hora`, `idUsuario`) 
                            VALUES (:modulo,:accion,:datos_previos,:datos_nuevos,:entorno,NOW(),:usuario)';
            $stmt = $conex->prepare($sql);
            $parametros = [
                ':modulo' => $id_modulo,
                ':accion' => $accion,
                ':datos_previos' => $datos_previos,
                ':datos_nuevos' => $datos_nuevos,
                ':entorno' => $entorno,
                ':usuario' => $id_usuario
            ];
            $stmt->execute($parametros);

            $conex->commit();

        }catch(Exception $e){
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            if (function_exists('logs')) {
                logs('Bitacora', $e->getMessage(), 'Modelo_RegistrarAccion');
            } else {
                error_log('[' . date('Y-m-d H:i:s') . '] [Modelo_RegistrarAccion] ERROR: ' . $e->getMessage());
            }
        }finally{
            $conex = null;
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
                        b.datos_previos,
                        b.datos_nuevos,
                        b.entorno,
                        DATE(b.fecha_hora) AS fecha,
                        TIME(b.fecha_hora) AS hora 
                    FROM bitacora b
                    INNER JOIN usuarios u ON u.idUsuario = b.idUsuario
                    INNER JOIN modulos m ON m.id_modulo = b.id_modulo
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
