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
        } catch (Exception $e) {
            logs('Automatizacion', $e->getMessage(), 'EjecutarProcesos');
        }
    }

    private function GenerarCargosAnuales()
    {
        $conex = $this->conex();
        $año_actual = date('Y');
        
        // Buscar concepto anual
        $stmtC = $conex->prepare("SELECT codigo_concepto, monto FROM conceptos WHERE frecuencia = 'A' AND estatus = 1 LIMIT 1");
        $stmtC->execute();
        $concepto = $stmtC->fetch(PDO::FETCH_ASSOC);
        
        if (!$concepto) return;
        
        // Moneda base actual
        $moneda = $this->obtenerMonedaBase($conex);

        // Atletas activos
        $stmtA = $conex->prepare("SELECT codigo_atleta FROM atletas WHERE estatus = 1");
        $stmtA->execute();
        $atletas = $stmtA->fetchAll(PDO::FETCH_ASSOC);

        foreach ($atletas as $atleta) {
            // Verificar si ya tiene el cargo este año
            $stmtV = $conex->prepare("SELECT COUNT(*) FROM cargos WHERE codigo_atleta = :atleta AND codigo_concepto = :concepto AND YEAR(fecha_emision) = :anio");
            $stmtV->execute([
                ':atleta' => $atleta['codigo_atleta'],
                ':concepto' => $concepto['codigo_concepto'],
                ':anio' => $año_actual
            ]);
            
            if ($stmtV->fetchColumn() == 0) {
                $this->insertarCargo($conex, $concepto['codigo_concepto'], $atleta['codigo_atleta'], $concepto['monto'], date('Y-m-d'), $moneda);
            }
        }
    }

    private function GenerarCargosMensuales()
    {
        $conex = $this->conex();
        $mes_actual = date('m');
        $año_actual = date('Y');
        
        // Buscar concepto mensual
        $stmtC = $conex->prepare("SELECT codigo_concepto, monto FROM conceptos WHERE frecuencia = 'M' AND estatus = 1 LIMIT 1");
        $stmtC->execute();
        $concepto = $stmtC->fetch(PDO::FETCH_ASSOC);
        
        if (!$concepto) return;
        
        $moneda = $this->obtenerMonedaBase($conex);

        // Atletas activos
        $stmtA = $conex->prepare("SELECT codigo_atleta FROM atletas WHERE estatus = 1");
        $stmtA->execute();
        $atletas = $stmtA->fetchAll(PDO::FETCH_ASSOC);

        foreach ($atletas as $atleta) {
            // Verificar si ya tiene el cargo este mes y año
            $stmtV = $conex->prepare("SELECT COUNT(*) FROM cargos WHERE codigo_atleta = :atleta AND codigo_concepto = :concepto AND MONTH(fecha_emision) = :mes AND YEAR(fecha_emision) = :anio");
            $stmtV->execute([
                ':atleta' => $atleta['codigo_atleta'],
                ':concepto' => $concepto['codigo_concepto'],
                ':mes' => $mes_actual,
                ':anio' => $año_actual
            ]);
            
            if ($stmtV->fetchColumn() == 0) {
                $this->insertarCargo($conex, $concepto['codigo_concepto'], $atleta['codigo_atleta'], $concepto['monto'], date('Y-m-d'), $moneda);
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
        
        // Buscar cargos pendientes, no multados, que ya superaron los días de gracia
        // Y que el concepto original tiene dias_gracia > 0
        $sql = "SELECT c.codigo_cargo, c.codigo_atleta 
                FROM cargos c
                INNER JOIN conceptos co ON c.codigo_concepto = co.codigo_concepto
                WHERE c.estatus = 1 AND c.multado = 0 
                AND co.dias_gracia > 0 
                AND DATE_ADD(c.fecha_emision, INTERVAL co.dias_gracia DAY) < CURDATE()";
                
        $stmt = $conex->prepare($sql);
        $stmt->execute();
        $vencidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($vencidos as $cargo) {
            $conex->beginTransaction();
            try {
                // Insertar multa
                $this->insertarCargo($conex, $multa['codigo_concepto'], $cargo['codigo_atleta'], $multa['monto'], date('Y-m-d'), $moneda);
                
                // Marcar cargo original como multado
                $stmtU = $conex->prepare("UPDATE cargos SET multado = 1 WHERE codigo_cargo = :id");
                $stmtU->execute([':id' => $cargo['codigo_cargo']]);
                
                $conex->commit();
            } catch (Exception $e) {
                $conex->rollBack();
                logs('Automatizacion', $e->getMessage(), 'GenerarMultas_Item');
            }
        }
    }
    
    private function obtenerMonedaBase($conex)
    {
        $stmtBase = $conex->prepare("SELECT codigo_moneda FROM monedas WHERE base = 1 AND estatus = 1 LIMIT 1");
        $stmtBase->execute();
        return $stmtBase->fetchColumn() ?: 2;
    }
    
    private function insertarCargo($conex, $concepto, $atleta, $monto, $fecha, $moneda)
    {
        $sql = "INSERT INTO cargos (codigo_concepto, codigo_atleta, monto_total, fecha_emision, codigo_moneda) VALUES (:concepto, :atleta, :monto, :fecha, :moneda)";
        $stmt = $conex->prepare($sql);
        $stmt->execute([
            ':concepto' => $concepto,
            ':atleta' => $atleta,
            ':monto' => $monto,
            ':fecha' => $fecha,
            ':moneda' => $moneda
        ]);
    }
}
