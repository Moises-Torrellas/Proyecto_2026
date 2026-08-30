<?php

namespace App\modelo;

use App\interface\InterBitacora;
use Exception;
use PDO;

class ModeloBitacora extends Conexion implements InterBitacora
{

    public function __construct() {}

    public function RegistrarAccion($id_modulo, $accion, $id_usuario, $datos_previos = NULL, $datos_nuevos = NULL, $entorno = '')
    {
        $conex = null;
        try{
            $conex = $this->conexSG();
            $sql = 'CALL pa_incluir_bitacora(:modulo,:accion,:datos_previos,:datos_nuevos,:entorno,:usuario,@resultado)';
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

        // 1. Iniciamos la sentencia con la vista
        $sentencia = "SELECT * FROM vista_consulta_bitacora WHERE 1=1";

        // 2. BUSCADOR GENERAL (Filtra por nombre de usuario, cédula o acción)
        if (!empty($filtro['filtro'])) {
            $p = "%" . $filtro['filtro'] . "%";
            $sentencia .= " AND (
                            nombreUsuario LIKE :f1 OR 
                            cedulaUsuario LIKE :f2 OR 
                            acciones LIKE :f3 OR
                            nombre_modulo LIKE :f4
                        )";
            $params[':f1'] = $p;
            $params[':f2'] = $p;
            $params[':f3'] = $p;
            $params[':f4'] = $p;
        }

        if (!empty($filtro['id_modulo'])) {
            $sentencia .= " AND nombre_modulo = (SELECT nombre_modulo FROM modulos WHERE id_modulo = :id_modulo LIMIT 1)";
            $params[':id_modulo'] = $filtro['id_modulo'];
        }
        if (!empty($filtro['idUsuario'])) {
            $sentencia .= " AND cedulaUsuario = (SELECT cedulaUsuario FROM usuarios WHERE idUsuario = :idUsuario LIMIT 1)";
            $params[':idUsuario'] = $filtro['idUsuario'];
        }
        if (!empty($filtro['fecha_inicio']) && !empty($filtro['fecha_fin'])) {
            $sentencia .= " AND fecha BETWEEN :fecha_inicio AND :fecha_fin";
            $params[':fecha_inicio'] = $filtro['fecha_inicio'];
            $params[':fecha_fin'] = $filtro['fecha_fin'];
        }

        // Limit and Offset only if it's not a report
        if (isset($filtro['accion']) && $filtro['accion'] === 'reporte') {
            $sentencia .= " ORDER BY id_bitacora DESC";
        } else {
            $sentencia .= " ORDER BY id_bitacora DESC";
            $limit = isset($filtro['limit']) ? (int) $filtro['limit'] : 100;
            $offset = isset($filtro['offset']) ? (int) $filtro['offset'] : 0;
            $sentencia .= " LIMIT :limit OFFSET :offset";
        }

        $stmt = $conex->prepare($sentencia);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        if (!isset($filtro['accion']) || $filtro['accion'] !== 'reporte') {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }
        
        $stmt->execute();

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

    public function consultarUsuarios()
    {
        try {
            $conex = $this->conexSG();
            $sentencia = "SELECT idUsuario, cedulaUsuario, nombreUsuario, apellidoUsuario FROM usuarios ORDER BY nombreUsuario ASC";
            $stmt = $conex->prepare($sentencia);
            $stmt->execute();
            return array('accion' => 'consultar_usuarios', 'datos' => $stmt->fetchAll());
        } catch (Exception $e) {
            logs('Bitacora', $e->getMessage(), 'Modelo_consultarUsuarios');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    public function consultarModulos()
    {
        try {
            $conex = $this->conexSG();
            $sentencia = "SELECT id_modulo, nombre_modulo FROM modulos ORDER BY nombre_modulo ASC";
            $stmt = $conex->prepare($sentencia);
            $stmt->execute();
            return array('accion' => 'consultar_modulos', 'datos' => $stmt->fetchAll());
        } catch (Exception $e) {
            logs('Bitacora', $e->getMessage(), 'Modelo_consultarModulos');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }
}
