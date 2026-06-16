<?php

namespace App\modelo;

use Exception;

class ModeloReportes extends Conexion
{
    public function Consultar()
    {
        try {
            $conex = $this->conex();

            $sentencia = "SELECT 
            c.nombre AS categoria,
            c.edad_min,
            c.edad_max,
            COUNT(a.id_atleta) AS total_atletas,
            SUM(CASE WHEN a.genero = 'H' AND a.estatus = 1 THEN 1 ELSE 0 END) AS masc_activos,
            SUM(CASE WHEN a.genero = 'H' AND a.estatus = 2 THEN 1 ELSE 0 END) AS masc_retirados,
            SUM(CASE WHEN a.genero = 'M' AND a.estatus = 1 THEN 1 ELSE 0 END) AS fem_activos,
            SUM(CASE WHEN a.genero = 'M' AND a.estatus = 2 THEN 1 ELSE 0 END) AS fem_retirados
        FROM categorias c
        INNER JOIN atletas a ON c.id_categorias = a.id_categoria
        GROUP BY 
            c.id_categorias, 
            c.nombre, 
            c.edad_min, 
            c.edad_max";

            $stmt = $conex->prepare($sentencia);
            $stmt->execute();

            $datos = $stmt->fetchAll();

            return array('accion' => 'consultar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Estadisticas', $e->getMessage(), 'Modelo_ConsultarEstadisticas');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }
}
