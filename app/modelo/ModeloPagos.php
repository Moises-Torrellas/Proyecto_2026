<?php

namespace App\modelo;

use Exception;
use PDO;

class ModeloPagos extends ModeloBase
{
    private $id;
    private $id_cuenta;
    private $id_metodo;
    private $id_moneda;
    private $monto;
    private $tasa;
    private $fecha;
    private $referencia;
    private $estatus;

    public function __construct()
    {
        parent::__construct();
        $this->campoWhitelist = [
            'id' => 'id_pago',
            'id_cuenta' => 'id_cobrar',
            'id_metodo' => 'id_metodos',
            'id_moneda' => 'id_moneda',
            'monto' => 'monto_pago',
            'tasa' => 'tasa_cambio',
            'fecha' => 'fecha',
            'referencia' => 'referencia',
            'estatus' => 'estatus'
        ];
        $this->llavePrimaria = 'id_pago';
    }

    public function ProcesarDatos(array $datos): array
    {
        // 1. Verificación de integridad inicial
        if (empty($datos)) {
            throw new Exception('No se proporcionaron datos para procesar.');
        }

        // 2. Asignación y saneamiento de atributos básicos
        $this->id         = $datos['id'] ?? null;
        $this->id_cuenta  = $datos['cuenta'] ?? null;
        $this->id_metodo  = $datos['metodo'] ?? null;
        $this->id_moneda  = $datos['moneda'] ?? null;
        $this->monto      = isset($datos['monto']) ? (float) $datos['monto'] : null;
        $this->tasa       = !empty($datos['tasa']) ? (float) $datos['tasa'] : 1;
        $this->fecha      = !empty($datos['fecha']) ? trim($datos['fecha']) : date('Y-m-d');
        $this->referencia = isset($datos['referencia']) ? trim($datos['referencia']) : null;
        $this->estatus    = $datos['estatus'] ?? 1;

        // 5. Ejecución de la acción vía Match
        $accion = $datos['accion'] ?? null;

        return match ($accion) {
            'incluir'   => $this->Incluir(),
            'consultar' => $this->Consultar(),
            'eliminar'  => $this->Eliminar(),
            default => throw new Exception('La accion solicitada para el pago no es valida.')
        };
    }

    public function Consultar(array $filtro = []): array
    {
        try {
            $conex = $this->conex();
            $params = [];

            // 1. Apuntamos directamente a tu vista recién creada
            $sentencia = "SELECT * FROM vista_pagos WHERE 1=1";

            // 2. BUSCADOR GENERAL (Filtro dinámico adaptado a los alias de la vista)
            if (!empty($filtro['filtro'])) {
                $p = "%" . trim($filtro['filtro']) . "%";

                // CORRECCIÓN: Los nombres de columnas ahora reflejan los campos de la VIEW
                $sentencia .= " AND (
                concepto_pago LIKE :f1 OR 
                nombre_atleta LIKE :f2 OR 
                nombre_apellido LIKE :f3 OR
                referencia LIKE :f4 OR
                moneda LIKE :f5
            )";
                $params[':f1'] = $p;
                $params[':f2'] = $p;
                $params[':f3'] = $p;
                $params[':f4'] = $p;
                $params[':f5'] = $p;
            }

            // 3. ORDENAMIENTO (Usando el campo 'fecha_pago' de la vista)
            $sentencia .= " ORDER BY fecha_pago DESC";

            $stmt = $conex->prepare($sentencia);
            $stmt->execute($params);
            $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return array('accion' => 'consultar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Pagos', $e->getMessage(), 'Modelo_Consultar');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    private function Incluir(): array
    {
        $conex = null;
        try {
            $conex = $this->conex();
            $conex->beginTransaction();

            // 1. Verificación de existencia para llaves foráneas (Usando el estándar de ModeloBase)
            if (!$this->verificarExistencia('id_cuenta', $this->id_cuenta, 'cuentas_cobrar', NULL, bloquear: true)) {
                throw new Exception("La cuenta por cobrar seleccionada no existe en el sistema.");
            }
            if (!$this->verificarExistencia('id_metodo', $this->id_metodo, 'metodos_pago', NULL)) {
                throw new Exception("El método de pago no es válido.");
            }
            if (!$this->verificarExistencia('id_moneda', $this->id_moneda, 'monedas', NULL)) {
                throw new Exception("La moneda seleccionada no es válida.");
            }

            // 2. Preparación de la sentencia SQL de Inserción (Al estilo Atletas)
            $columnas = [];
            $marcadores = [];

            // --- DATOS OBLIGATORIOS ---
            $columnas[] = "id_metodo";
            $marcadores[] = ":id_metodo";
            $columnas[] = "id_cobrar";
            $marcadores[] = ":id_cobrar";
            $columnas[] = "id_moneda";
            $marcadores[] = ":id_moneda";
            $columnas[] = "monto_pago";
            $marcadores[] = ":monto_pago";
            $columnas[] = "tasa_cambio";
            $marcadores[] = ":tasa_cambio";
            $columnas[] = "fecha";
            $marcadores[] = ":fecha";
            $columnas[] = "estatus";
            $marcadores[] = "1"; // Valor por defecto directo o por marcador

            // --- DATOS OPCIONALES ---
            if ($this->referencia !== null && $this->referencia !== '') {
                $columnas[] = "referencia";
                $marcadores[] = ":referencia";
            }

            $sql = "INSERT INTO pagos (" . implode(", ", $columnas) . ") 
                VALUES (" . implode(", ", $marcadores) . ")";
            $stmt = $conex->prepare($sql);

            // Vinculación estricta
            $stmt->bindValue(':id_metodo', $this->id_metodo, PDO::PARAM_INT);
            $stmt->bindValue(':id_cobrar', $this->id_cuenta, PDO::PARAM_INT);
            $stmt->bindValue(':id_moneda', $this->id_moneda, PDO::PARAM_INT);
            $stmt->bindValue(':monto_pago', $this->monto);
            $stmt->bindValue(':tasa_cambio', $this->tasa);
            $stmt->bindValue(':fecha', $this->fecha);

            if ($this->referencia !== null && $this->referencia !== '') {
                $stmt->bindValue(':referencia', $this->referencia);
            }

            $stmt->execute();
            $conex->commit();
            return array('accion' => 'exito', 'mensaje' => 'Pago registrado correctamente.');
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            logs('Pagos', $e->getMessage(), 'Modelo_Incluir');
            return array('accion' => 'error', 'codigo' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    private function Eliminar(): array
    {
        try {
            $conex = $this->conex();
            $conex->beginTransaction();
            if (!$this->verificarExistencia('id', $this->id, 'pagos', NULL, bloquear: true)) {
                throw new Exception(INVALID_ID);
            }

            // Solo cambiamos el estatus a anulado (ej. 2 o 0)
            $sql = "UPDATE `pagos` SET `estatus`= 2 WHERE id_pago = :id";
            $stmt = $conex->prepare($sql);
            $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);
            $stmt->execute();

            $conex->commit();
            return ['accion' => 'exito', 'mensaje' => 'Pago anulado exitosamente.'];
        } catch (Exception $e) {
            if (isset($conex) && $conex->inTransaction()) {
                $conex->rollBack();
            }
            logs('Pagos', $e->getMessage(), 'Modelo_Eliminar');
            return ['accion' => 'error', 'codigo' => $e->getMessage()];
        } finally {
            $conex = null;
        }
    }
}
