<?php

namespace App\modelo;

use Exception;

class ModeloReportes extends Conexion
{
    public function __construct()
    {
        parent::__construct();
    }

    public function Consultar($filtros = [])
    {
        try {
            $conex = $this->conex();

            $sentencia = "SELECT 
            c.nombre AS categoria,
            c.edad_min,
            c.edad_max,
            COUNT(i.codigo_inscripcion) AS total_atletas,
            SUM(CASE WHEN a.genero = 'H' AND i.estatus = 1 THEN 1 ELSE 0 END) AS masc_activos,
            SUM(CASE WHEN a.genero = 'H' AND i.estatus = 2 THEN 1 ELSE 0 END) AS masc_retirados,
            SUM(CASE WHEN a.genero = 'M' AND i.estatus = 1 THEN 1 ELSE 0 END) AS fem_activos,
            SUM(CASE WHEN a.genero = 'M' AND i.estatus = 2 THEN 1 ELSE 0 END) AS fem_retirados
        FROM categorias c
        INNER JOIN (
            SELECT i1.* FROM inscripciones i1
            INNER JOIN (
                SELECT codigo_atleta, MAX(codigo_inscripcion) AS max_id
                FROM inscripciones
                GROUP BY codigo_atleta
            ) i2 ON i1.codigo_inscripcion = i2.max_id
        ) i ON c.codigo_categoria = i.codigo_categoria
        INNER JOIN atletas a ON i.codigo_atleta = a.codigo_atleta
        WHERE 1=1";

            $params = [];

            // Filtro por Categoría
            if (!empty($filtros['categoria']) && $filtros['categoria'] !== 'todos') {
                $sentencia .= " AND c.codigo_categoria = :categoria";
                $params[':categoria'] = $filtros['categoria'];
            }

            // Filtro por Género
            if (!empty($filtros['genero']) && $filtros['genero'] !== 'todos') {
                $sentencia .= " AND a.genero = :genero";
                $params[':genero'] = $filtros['genero'];
            }

            // Filtro de Retirados
            if (isset($filtros['incluir_retirados']) && $filtros['incluir_retirados'] === '0') {
                $sentencia .= " AND i.estatus = 1";
            }

            $sentencia .= " GROUP BY c.codigo_categoria, c.nombre, c.edad_min, c.edad_max";

            $stmt = $conex->prepare($sentencia);
            $stmt->execute($params);
            $datos = $stmt->fetchAll();

            return array('accion' => 'consultar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Estadisticas', $e->getMessage(), 'Modelo_ConsultarEstadisticas');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    // Consulta para Efectividad de Recaudación y Morosidad dividida por Monedas
    public function ConsultarRecaudacion($filtros = [])
    {
        try {
            $conex = $this->conex();
            $params = [];

            // NOTA: Se eliminó la restricción estricta de c.estatus = 1 para incluir cargos pagados,
            // excluyendo únicamente los que estén explícitamente anulados (asumiendo estatus = 0 como anulado)
            $sentencia = "SELECT 
            con.nombre AS concepto,
            m.abreviatura AS moneda,
            SUM(c.monto_total) AS total_cargado,
            SUM(COALESCE(p_agg.total_pagado, 0)) AS total_recaudado
        FROM conceptos con
        INNER JOIN cargos c ON con.codigo_concepto = c.codigo_concepto
        INNER JOIN monedas m ON c.codigo_moneda = m.codigo_moneda
        LEFT JOIN (
            SELECT dp.codigo_cargo, SUM(dp.monto_abonado) AS total_pagado
            FROM detalles_pagos dp
            INNER JOIN pagos p ON dp.codigo_pago = p.codigo_pago
            WHERE p.estatus = 1 -- Asegúrate de que 1 sea el estatus de un pago aprobado/procesado
            GROUP BY dp.codigo_cargo
        ) p_agg ON c.codigo_cargo = p_agg.codigo_cargo
        WHERE c.estatus != 0"; // Cambiado para evitar omitir los cargos que cambiaron de estado al pagarse

            // Filtro por Moneda
            if (!empty($filtros['moneda']) && $filtros['moneda'] !== 'todos') {
                $sentencia .= " AND c.codigo_moneda = :moneda";
                $params[':moneda'] = $filtros['moneda'];
            }

            // Filtro por Concepto
            if (!empty($filtros['concepto']) && $filtros['concepto'] !== 'todos') {
                $sentencia .= " AND con.codigo_concepto = :concepto";
                $params[':concepto'] = $filtros['concepto'];
            }

            // Filtros de fechas
            if (!empty($filtros['fecha_desde'])) {
                $sentencia .= " AND c.fecha_emision >= :fecha_desde";
                $params[':fecha_desde'] = $filtros['fecha_desde'];
            }

            if (!empty($filtros['fecha_hasta'])) {
                $sentencia .= " AND c.fecha_emision <= :fecha_hasta";
                $params[':fecha_hasta'] = $filtros['fecha_hasta'];
            }

            $sentencia .= " GROUP BY con.codigo_concepto, con.nombre, m.abreviatura";

            $stmt = $conex->prepare($sentencia);
            $stmt->execute($params);
            $datos = $stmt->fetchAll();

            return array('accion' => 'consultar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Estadisticas', $e->getMessage(), 'Modelo_ConsultarRecaudacion');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    public function ObtenerCategorias()
    {
        try {
            $conex = $this->conex();
            $stmt = $conex->prepare("SELECT codigo_categoria, nombre FROM categorias");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        } finally {
            $conex = null;
        }
    }

    public function ObtenerMonedas()
    {
        try {
            $conex = $this->conex();
            $stmt = $conex->prepare("SELECT codigo_moneda, abreviatura, nombre FROM monedas WHERE estatus = 1");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        } finally {
            $conex = null;
        }
    }

    public function ObtenerConceptos()
    {
        try {
            $conex = $this->conex();
            $stmt = $conex->prepare("SELECT codigo_concepto, nombre FROM conceptos WHERE estatus = 1");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        } finally {
            $conex = null;
        }
    }

    // Consulta para Flujo y Estado de Implementos Asignados
    // Consulta para Flujo y Estado de Implementos Asignados
    public function ConsultarInventario($filtros = [])
    {
        try {
            $conex = $this->conex();
            $params = [];

            // Se separaron los niveles numéricos 1 (Bueno), 2 (Medio) y 3 (Malo)
            $sentencia = "SELECT 
            cat.nombre AS articulo,
            COUNT(a.id_asignacion) AS total_asignados,
            SUM(CASE WHEN a.estatus = 1 THEN 1 ELSE 0 END) AS uso_activo,
            SUM(CASE WHEN a.estatus = 2 AND ef.nivel_estado = 1 THEN 1 ELSE 0 END) AS devuelto_bueno,
            SUM(CASE WHEN a.estatus = 2 AND ef.nivel_estado = 2 THEN 1 ELSE 0 END) AS devuelto_medio,
            SUM(CASE WHEN a.estatus = 2 AND ef.nivel_estado = 3 THEN 1 ELSE 0 END) AS devuelto_malo
        FROM categoria_catalogo cc
        INNER JOIN catalogo cat ON cc.id_categoria = cat.id_categoria
        INNER JOIN articulos_inventario ai ON cat.id_catalogo = ai.id_catalogo
        LEFT JOIN asignaciones a ON ai.codigo_articulo = a.codigo_articulo
        LEFT JOIN devoluciones d ON a.id_asignacion = d.id_asignacion
        LEFT JOIN estado_fisico ef ON d.id_estado = ef.id_estado
        WHERE 1=1";

            // Filtro por Categoría de Catálogo (id_categoria)
            if (!empty($filtros['categoria_inventario']) && $filtros['categoria_inventario'] !== 'todos') {
                $sentencia .= " AND cc.id_categoria = :categoria_inv";
                $params[':categoria_inv'] = $filtros['categoria_inventario'];
            }

            // Filtro por Estado Físico evaluando nivel_estado (1, 2 o 3)
            if (!empty($filtros['estado_fisico']) && $filtros['estado_fisico'] !== 'todos') {
                $sentencia .= " AND ef.nivel_estado = :estado_fisico";
                $params[':estado_fisico'] = $filtros['estado_fisico'];
            }

            // Filtros por Rango de Fechas (fecha_asignacion)
            if (!empty($filtros['fecha_desde'])) {
                $sentencia .= " AND a.fecha_asignacion >= :fecha_desde";
                $params[':fecha_desde'] = $filtros['fecha_desde'];
            }
            if (!empty($filtros['fecha_hasta'])) {
                $sentencia .= " AND a.fecha_asignacion <= :fecha_hasta";
                $params[':fecha_hasta'] = $filtros['fecha_hasta'];
            }

            // Agrupamos por el ID real del catálogo
            $sentencia .= " GROUP BY cat.id_catalogo, cat.nombre";

            $stmt = $conex->prepare($sentencia);
            $stmt->execute($params);
            $datos = $stmt->fetchAll();

            return array('accion' => 'consultar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Estadisticas', $e->getMessage(), 'Modelo_ConsultarInventario');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    public function ObtenerCategoriasCatalogo()
    {
        try {
            $conex = $this->conex();
            // Corregido aquí también el nombre de la tabla y de la llave primaria
            $stmt = $conex->prepare("SELECT id_categoria, nombre FROM categoria_catalogo");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        } finally {
            $conex = null;
        }
    }

    public function ConsultarRendimiento($filtros = [])
    {
        try {
            $conex = $this->conex();
            $params = [];

            // Seleccionamos el nombre completo del atleta, el torneo y la suma de sus estadísticas ofensivas
            $sentencia = "SELECT 
                CONCAT(a.p_nombre, ' ', a.p_apellidos) AS atleta,
                t.nombre AS torneo,
                SUM(COALESCE(dp.goles, 0)) AS total_goles,
                SUM(COALESCE(dp.asistencias, 0)) AS total_asistencias
            FROM detalles_participacion dp
            INNER JOIN atletas a ON dp.codigo_atleta = a.codigo_atleta
            INNER JOIN participaciones par ON dp.codigo_participacion = par.codigo_participacion
            INNER JOIN torneos t ON par.codigo_torneo = t.codigo_torneo
            WHERE 1=1";

            // Filtro por Torneo
            if (!empty($filtros['torneo']) && $filtros['torneo'] !== 'todos') {
                $sentencia .= " AND par.codigo_torneo = :torneo";
                $params[':torneo'] = $filtros['torneo'];
            }

            // Filtro por Atleta específico
            if (!empty($filtros['atleta']) && $filtros['atleta'] !== 'todos') {
                $sentencia .= " AND a.codigo_atleta = :atleta";
                $params[':atleta'] = $filtros['atleta'];
            }

            $sentencia .= " GROUP BY a.codigo_atleta, t.codigo_torneo, a.p_nombre, a.p_apellidos, t.nombre
                            ORDER BY t.nombre ASC, total_goles DESC";

            $stmt = $conex->prepare($sentencia);
            $stmt->execute($params);
            return array('accion' => 'consultar', 'datos' => $stmt->fetchAll());
        } catch (Exception $e) {
            logs('Estadisticas', $e->getMessage(), 'Modelo_ConsultarRendimiento');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    // Nuevo método para rellenar el selector de filtros en la vista
    public function ObtenerAtletas()
    {
        $conex = $this->conex();
        $stmt = $conex->prepare("SELECT codigo_atleta, CONCAT(p_nombre, ' ', p_apellidos) AS nombre FROM atletas ORDER BY p_nombre ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function ObtenerTorneos()
    {
        $conex = $this->conex();
        $stmt = $conex->prepare("SELECT codigo_torneo, nombre FROM torneos");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
}
