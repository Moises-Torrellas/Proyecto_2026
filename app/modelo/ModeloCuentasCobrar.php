<?php

namespace App\modelo;

use Exception;

class ModeloCuentasCobrar extends Conexion
{
    private $codigo_cargo;
    private $codigo_concepto;
    private $codigo_atleta;
    private $monto_total;
    private $fecha_emision;
    private $estatus;

    public function __construct()
    {
        parent::__construct();
        // Ajustado para coincidir con la tabla cargos
        $this->campoWhitelist = [
            'id' => 'codigo_cargo',
            'id_cobrar' => 'codigo_cargo',
            'id_concepto' => 'codigo_concepto',
            'id_atleta' => 'codigo_atleta',
            'estatus' => 'estatus'
        ];
        $this->llavePrimaria = 'codigo_cargo';
    }

    public function ProcesarDatos(array $datos): array
    {
        if (empty($datos)) {
            throw new Exception('No se proporcionaron datos para procesar.');
        }

        $this->codigo_cargo = $datos['id'] ?? null;
        $this->codigo_concepto = $datos['id_concepto'] ?? null;
        $this->codigo_atleta = $datos['id_atleta'] ?? null;
        $this->monto_total = $datos['monto_total'] ?? 0;

        $this->fecha_emision = !empty($datos['fecha_emision']) ? $datos['fecha_emision'] : date('Y-m-d');
        $this->estatus = $datos['estatus'] ?? null;

        $accion = $datos['accion'] ?? null;
        return match ($accion) {
            'incluir'   => $this->Incluir(),
            'eliminar'  => $this->Eliminar(),
            'buscar'    => $this->Buscar(),
            'modificar' => $this->Modificar(),
            default => throw new Exception('La accion no es valida')
        };
    }

    public function Consultar(array $filtro = []): array
    {
        try {
            $conex = $this->conex();
            $params = [];

            // Se mantiene el uso de tu vista, asegúrate de que la vista en BD esté apuntando a la tabla 'cargos'
            $sentencia = "SELECT * FROM vista_cargos WHERE 1=1";

            if (!empty($filtro['filtro'])) {
                $p = "%" . trim($filtro['filtro']) . "%";
                $sentencia .= " AND (
                atleta_nombre LIKE :f1 OR 
                atleta_apellido LIKE :f2 OR 
                concepto_nombre LIKE :f3 OR
                estatus_texto LIKE :f5
            )";
                $params[':f1'] = $p;
                $params[':f2'] = $p;
                $params[':f3'] = $p;
                $params[':f5'] = $p;
            }

            $sentencia .= " ORDER BY id_atleta, fecha_emision DESC";

            $stmt = $conex->prepare($sentencia);
            $stmt->execute($params);
            $datos = $stmt->fetchAll();

            return array('accion' => 'consultar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('CuentasCobrar', $e->getMessage(), 'Modelo_Consultar');
            return array('accion' => 'error');
        } finally {
            $conex = NULL;
        }
    }

    public function ConsultarCargos(): array
    {
        try {
            $conex = $this->conex();

            // Se añadió: (c.monto_total - IFNULL(pagos.total_abonado, 0)) AS monto_pendiente
            $sentencia = "SELECT 
                c.codigo_cargo,
                a.p_nombre, 
                a.p_apellidos, 
                co.nombre AS concepto, 
                c.fecha_emision, 
                (c.monto_total - IFNULL(pagos.total_abonado, 0)) AS monto_pendiente,
                m.simbolo AS simbolo_moneda,
                m.abreviatura AS moneda_abreviatura
            FROM 
                cargos c
            INNER JOIN 
                atletas a ON c.codigo_atleta = a.codigo_atleta
            INNER JOIN 
                conceptos co ON c.codigo_concepto = co.codigo_concepto
            INNER JOIN 
                monedas m ON c.codigo_moneda = m.codigo_moneda
            LEFT JOIN (
                SELECT dp.codigo_cargo, SUM(dp.monto_abonado) AS total_abonado 
                FROM detalles_pagos dp
                INNER JOIN pagos p ON dp.codigo_pago = p.codigo_pago
                WHERE p.estatus = 1  -- AQUÍ ESTÁ EL CAMBIO CLAVE
                GROUP BY dp.codigo_cargo
            ) AS pagos ON c.codigo_cargo = pagos.codigo_cargo
            WHERE 
                c.estatus = 1;";

            $stmt = $conex->prepare($sentencia);
            $stmt->execute();
            $datos = $stmt->fetchAll();

            return array('accion' => 'buscar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('CuentasCobrar', $e->getMessage(), 'Modelo_Buscar');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    private function Incluir(): array
    {
        try {
            $conex = $this->conex();
            $conex->beginTransaction();

            if (!$this->verificarExistencia('id_concepto', $this->codigo_concepto, 'conceptos', NULL, bloquear: true)) {
                throw new Exception("El concepto de cobro seleccionado no existe.");
            }

            // Buscar la moneda base actual
            $stmtBase = $conex->prepare("SELECT codigo_moneda FROM monedas WHERE base = 1 AND estatus = 1 LIMIT 1");
            $stmtBase->execute();
            $moneda_base = $stmtBase->fetchColumn() ?: 2; // Default to 2 if not found

            $sentencia = "INSERT INTO cargos (`codigo_concepto`, `codigo_atleta`, `monto_total`, `fecha_emision`, `codigo_moneda`) 
                          VALUES (:codigo_concepto, :codigo_atleta, :monto_total, :fecha_emision, :codigo_moneda)";

            $stmt = $conex->prepare($sentencia);
            $stmt->bindValue(':codigo_moneda', $moneda_base);

            // Iteramos sobre el array de atletas para registrarlos uno por uno
            foreach ($this->codigo_atleta as $id_atleta) {
                if (!$this->verificarExistencia('id_atleta', $id_atleta, 'atletas', NULL, bloquear: true)) {
                    throw new Exception("El atleta seleccionado no existe o está inactivo.");
                }

                if ($this->validarFrecuencia($conex, $this->codigo_concepto, $id_atleta, $this->fecha_emision)) {
                    throw new Exception("El atleta ya tiene asignado este concepto para el periodo correspondiente.");
                }

                $stmt->bindParam(':codigo_concepto', $this->codigo_concepto);
                $stmt->bindParam(':codigo_atleta', $id_atleta);
                $stmt->bindParam(':monto_total', $this->monto_total);
                $stmt->bindParam(':fecha_emision', $this->fecha_emision);

                $stmt->execute();
            }

            $conex->commit();
            return array('accion' => 'exito');
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollback();
            }
            logs('CuentasCobrar', $e->getMessage(), 'Modelo_Incluir');
            return array('accion' => 'error', 'codigo' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    private function Modificar(): array
    {
        try {
            $conex = $this->conex();
            $conex->beginTransaction();

            if (!$this->verificarExistencia('id', $this->codigo_cargo, 'cargos', NULL, bloquear: true)) {
                throw new Exception(INVALID_ID);
            }

            $sentencia = "UPDATE cargos SET 
                          codigo_concepto = :codigo_concepto, 
                          codigo_atleta = :codigo_atleta, 
                          monto_total = :monto_total, 
                          fecha_emision = :fecha_emision,
                          estatus = :estatus 
                          WHERE codigo_cargo = :id";

            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':codigo_concepto', $this->codigo_concepto);
            // Si en modificar envías un solo atleta, nos aseguramos de que sea un string/int y no un array
            $atleta_id = is_array($this->codigo_atleta) ? $this->codigo_atleta[0] : $this->codigo_atleta;
            $stmt->bindParam(':codigo_atleta', $atleta_id);
            $stmt->bindParam(':monto_total', $this->monto_total);
            $stmt->bindParam(':fecha_emision', $this->fecha_emision);
            $stmt->bindParam(':estatus', $this->estatus);
            $stmt->bindParam(':id', $this->codigo_cargo);
            $stmt->execute();

            $conex->commit();
            return array('accion' => 'exito');
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollback();
            }
            logs('CuentasCobrar', $e->getMessage(), 'Modelo_Modificar');
            return array('accion' => 'error', 'codigo' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    public function Buscar(int $id = null): array
    {
        try {
            $conex = $this->conex();

            $sentencia = "SELECT * FROM vista_cargos WHERE id_cobrar = :id";

            $stmt = $conex->prepare($sentencia);
            if ($id === null) {
                $id = $this->codigo_cargo;
            }
            $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
            $stmt->execute();
            $datos = $stmt->fetchAll();

            return array('accion' => 'buscar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('CuentasCobrar', $e->getMessage(), 'Modelo_Buscar');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    private function Eliminar(): array
    {
        try {
            $conex = $this->conex();
            $conex->beginTransaction();

            if (!$this->verificarExistencia('id', $this->codigo_cargo, 'cargos', NULL, bloquear: true)) {
                throw new Exception(INVALID_ID);
            }

            if ($this->verificarExistencia('id', $this->codigo_cargo, 'detalles_pagos', NULL, bloquear: true)) {
                throw new Exception(ASSOCIATES);
            }

            $sentencia = "UPDATE cargos SET estatus = 3 WHERE codigo_cargo = :id";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id', $this->codigo_cargo);
            $stmt->execute();

            $conex->commit();
            return array('accion' => 'exito');
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollback();
            }
            logs('CuentasCobrar', $e->getMessage(), 'Modelo_Anular');
            return array('accion' => 'error', 'codigo' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    public function validarFrecuencia($conex, $id_concepto, $id_atleta, $fecha_emision): bool
    {
        try {
            $sql = "SELECT (SELECT COUNT(codigo_cargo) 
                            FROM cargos 
                            WHERE codigo_concepto = c.codigo_concepto 
                              AND codigo_atleta = :id_atleta 
                              AND estatus != 3
                              AND (
                                  (c.frecuencia = 'M' AND MONTH(fecha_emision) = MONTH(:fecha1) AND YEAR(fecha_emision) = YEAR(:fecha2)) OR
                                  (c.frecuencia = 'A' AND YEAR(fecha_emision) = YEAR(:fecha3)) OR
                                  (c.frecuencia = 'U')
                              )
                           ) as colisiones
                    FROM conceptos c 
                    WHERE c.codigo_concepto = :id_concepto";

            $stmt = $conex->prepare($sql);
            $stmt->bindParam(':id_concepto', $id_concepto);
            $stmt->bindParam(':id_atleta', $id_atleta);

            $stmt->bindParam(':fecha1', $fecha_emision);
            $stmt->bindParam(':fecha2', $fecha_emision);
            $stmt->bindParam(':fecha3', $fecha_emision);

            $stmt->execute();

            $resultado = $stmt->fetch(\PDO::FETCH_ASSOC);

            return ($resultado && $resultado['colisiones'] > 0);
        } catch (Exception $e) {
            logs('CuentasCobrar', $e->getMessage(), 'Modelo_ValidarFrecuencia');
            throw new Exception("Error al validar la frecuencia del concepto en la base de datos.");
        }
    }

    private function obtenerMonedaBaseId(\PDO $conex): int
    {
        $stmt = $conex->prepare(
            "SELECT codigo_moneda FROM monedas WHERE base = 1 AND estatus = 1 LIMIT 1"
        );
        $stmt->execute();
        $id = $stmt->fetchColumn();

        if (!$id) {
            throw new Exception('No hay moneda base configurada en el sistema.');
        }

        return (int) $id;
    }

    public function ModificarEstatus(int $id, int $estatus, ?\PDO $conexExterna = null): bool
    {
        $transaccionPropia = false;
        try {
            if ($conexExterna !== null) {
                $conex = $conexExterna;
            } else {
                $conex = $this->conex();
                $conex->beginTransaction();
                $transaccionPropia = true;
            }

            $sentencia = "UPDATE cargos SET estatus = :estatus WHERE codigo_cargo = :id";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':estatus', $estatus);
            $stmt->execute();

            if ($transaccionPropia) {
                $conex->commit();
            }
            return true;
        } catch (Exception $e) {
            if ($transaccionPropia && isset($conex) && $conex->inTransaction()) {
                $conex->rollback();
            }
            logs('CuentasCobrar', $e->getMessage(), 'Modelo_ModificarEstatus');
            return false;
        } finally {
            if ($transaccionPropia) {
                $conex = NULL;
            }
        }
    }

    public function ConsultarAtrasados(): array
    {
        try {
            $conex = $this->conex();
            $sentencia = "SELECT 
                c.codigo_cargo,
                a.p_nombre, 
                a.p_apellidos, 
                co.nombre AS concepto, 
                c.fecha_emision, 
                (c.monto_total - IFNULL(pagos.total_abonado, 0)) AS monto_pendiente
            FROM 
                cargos c
            INNER JOIN 
                atletas a ON c.codigo_atleta = a.codigo_atleta
            INNER JOIN 
                conceptos co ON c.codigo_concepto = co.codigo_concepto
            LEFT JOIN (
                SELECT dp.codigo_cargo, SUM(dp.monto_abonado) AS total_abonado 
                FROM detalles_pagos dp
                INNER JOIN pagos p ON dp.codigo_pago = p.codigo_pago
                WHERE p.estatus = 1
                GROUP BY dp.codigo_cargo
            ) AS pagos ON c.codigo_cargo = pagos.codigo_cargo
            WHERE 
                c.estatus = 1 
                AND c.fecha_emision < CURDATE()
            HAVING monto_pendiente > 0;";

            $stmt = $conex->prepare($sentencia);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            logs('CuentasCobrar', $e->getMessage(), 'Modelo_ConsultarAtrasados');
            return [];
        } finally {
            $conex = NULL;
        }
    }
}
