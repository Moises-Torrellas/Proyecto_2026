<?php

namespace App\modelo;

use Exception;
use PDO;

class ModeloAutomatizacion extends Conexion
{
    public function __construct()
    {
        parent::__construct();
    }

    public function EjecutarProcesos()
    {
        try {
            $this->GenerarCargosAnuales();
            $this->GenerarCargosMensuales();
            $this->GenerarMultas();
            $this->ActualizarEstatusTorneos();
        } catch (Exception $e) {
            logs('Automatizacion', $e->getMessage(), 'EjecutarProcesos');
        }
    }

    /**
     * Obtiene los atletas cuya ÚLTIMA inscripción está activa (estatus = 1).
     * Si su última inscripción es 2 (retirado), los excluye.
     */
    private function ObtenerAtletasInscripcionActiva($conex)
    {
        $sql = "SELECT a.codigo_atleta 
                FROM atletas a
                WHERE (
                    SELECT i.estatus 
                    FROM inscripciones i 
                    WHERE i.codigo_atleta = a.codigo_atleta 
                    ORDER BY i.fecha_inscripcion DESC, i.codigo_inscripcion DESC 
                    LIMIT 1
                ) = 1";
        
        $stmt = $conex->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function GenerarCargosAnuales()
    {
        $conex = $this->conex();
        
        // Buscar concepto anual
        $stmtC = $conex->prepare("SELECT codigo_concepto, monto FROM conceptos WHERE frecuencia = 'A' AND estatus = 1 LIMIT 1");
        $stmtC->execute();
        $concepto = $stmtC->fetch(PDO::FETCH_ASSOC);
        
        if (!$concepto) return;
        
        $moneda = $this->obtenerMonedaBase($conex);
        $atletas = $this->ObtenerAtletasInscripcionActiva($conex);

        // Delegamos el año actual a MySQL con YEAR(CURDATE())
        $stmtV = $conex->prepare("SELECT COUNT(*) FROM cargos WHERE codigo_atleta = :atleta AND codigo_concepto = :concepto AND YEAR(fecha_emision) = YEAR(CURDATE())");

        foreach ($atletas as $atleta) {
            $stmtV->execute([
                ':atleta' => $atleta['codigo_atleta'],
                ':concepto' => $concepto['codigo_concepto']
            ]);
            
            // Si no tiene el cargo este año, se le genera
            if ($stmtV->fetchColumn() == 0) {
                $this->insertarCargo($conex, $concepto['codigo_concepto'], $atleta['codigo_atleta'], $concepto['monto'], $moneda);
            }
        }
    }

    private function GenerarCargosMensuales()
    {
        $conex = $this->conex();
        
        // Buscar concepto mensual
        $stmtC = $conex->prepare("SELECT codigo_concepto, monto FROM conceptos WHERE frecuencia = 'M' AND estatus = 1 LIMIT 1");
        $stmtC->execute();
        $concepto = $stmtC->fetch(PDO::FETCH_ASSOC);
        
        if (!$concepto) return;
        
        $moneda = $this->obtenerMonedaBase($conex);
        $atletas = $this->ObtenerAtletasInscripcionActiva($conex);

        // Delegamos el mes y año actual a MySQL
        $stmtV = $conex->prepare("SELECT COUNT(*) FROM cargos WHERE codigo_atleta = :atleta AND codigo_concepto = :concepto AND MONTH(fecha_emision) = MONTH(CURDATE()) AND YEAR(fecha_emision) = YEAR(CURDATE())");

        foreach ($atletas as $atleta) {
            $stmtV->execute([
                ':atleta' => $atleta['codigo_atleta'],
                ':concepto' => $concepto['codigo_concepto']
            ]);
            
            // Si no tiene el cargo este mes, se le genera
            if ($stmtV->fetchColumn() == 0) {
                $this->insertarCargo($conex, $concepto['codigo_concepto'], $atleta['codigo_atleta'], $concepto['monto'], $moneda);
            }
        }
    }

    private function GenerarMultas()
    {
        $conex = $this->conex();
        
        // Buscar concepto de multa (frecuencia T)
        $stmtC = $conex->prepare("SELECT codigo_concepto, monto FROM conceptos WHERE frecuencia = 'T' AND estatus = 1 LIMIT 1");
        $stmtC->execute();
        $multa = $stmtC->fetch(PDO::FETCH_ASSOC);
        
        if (!$multa) return;
        
        $moneda = $this->obtenerMonedaBase($conex);
        
        // Buscar cargos pendientes (estatus 1), no multados (0), y que NO sean de tipo multa ('T')
        $sql = "SELECT c.codigo_cargo, c.codigo_atleta 
                FROM cargos c
                INNER JOIN conceptos co ON c.codigo_concepto = co.codigo_concepto
                WHERE c.estatus = 1 
                AND c.multado = 0 
                AND co.frecuencia != 'T'
                AND co.dias_gracia > 0 
                AND DATE_ADD(c.fecha_emision, INTERVAL co.dias_gracia DAY) < CURDATE()";
                
        $stmt = $conex->prepare($sql);
        $stmt->execute();
        $vencidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($vencidos as $cargo) {
            $conex->beginTransaction();
            try {
                // Insertar el cargo de la multa (se insertará con estatus 1 y multado 0 por defecto)
                $this->insertarCargo($conex, $multa['codigo_concepto'], $cargo['codigo_atleta'], $multa['monto'], $moneda);
                
                // Cambiar el flag de multado de 0 a 1 en el cargo original
                $stmtU = $conex->prepare("UPDATE cargos SET multado = 1 WHERE codigo_cargo = :id");
                $stmtU->execute([':id' => $cargo['codigo_cargo']]);
                
                $conex->commit();
            } catch (Exception $e) {
                $conex->rollBack();
                logs('Automatizacion', $e->getMessage(), 'GenerarMultas_Item');
            }
        }
    }

    private function ActualizarEstatusTorneos()
    {
        $conex = $this->conex();
        // Las fechas se calculan netamente con la base de datos (CURDATE())
        $sql = "UPDATE torneos 
                SET estatus = CASE
                    WHEN CURDATE() < fecha_inicio THEN 1
                    WHEN CURDATE() >= fecha_inicio AND CURDATE() <= fecha_fin THEN 2
                    WHEN CURDATE() > fecha_fin THEN 3
                    ELSE estatus
                END
                WHERE estatus IN (1, 2, 3)";
                
        $stmt = $conex->prepare($sql);
        $stmt->execute();
    }
    
    private function obtenerMonedaBase($conex)
    {
        $stmtBase = $conex->prepare("SELECT codigo_moneda FROM monedas WHERE base = 1 AND estatus = 1 LIMIT 1");
        $stmtBase->execute();
        return $stmtBase->fetchColumn() ?: 2; // Si no encuentra, usa el ID 2 por defecto
    }
    
    private function insertarCargo($conex, $concepto, $atleta, $monto, $moneda)
    {
        // Se inyecta directamente CURDATE(), estatus = 1 (pendiente) y multado = 0
        $sql = "INSERT INTO cargos (codigo_concepto, codigo_atleta, monto_total, fecha_emision, codigo_moneda, estatus, multado) 
                VALUES (:concepto, :atleta, :monto, CURDATE(), :moneda, 1, 0)";
        $stmt = $conex->prepare($sql);
        $stmt->execute([
            ':concepto' => $concepto,
            ':atleta' => $atleta,
            ':monto' => $monto,
            ':moneda' => $moneda
        ]);
    }
}