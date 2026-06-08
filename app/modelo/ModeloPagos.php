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

            $sentencia = "SELECT 
                            p.id_pago,
                            p.fecha AS fecha_pago,
                            p.monto_pago AS monto_pagado,
                            p.monto_vuelto,
                            p.referencia,
                            p.estatus,
                            p.tasa_cambio,
                            m.simbolo,
                            m.abreviatura AS abre,
                            m.nombre AS moneda,
                            dp.id_detalle_pago,
                            dp.monto_abonado,
                            con.nombre AS concepto_pago,
                            atl.nombres AS nombre_atleta,
                            atl.apellidos AS nombre_apellido,
                            m_cuenta.simbolo AS simbolo_cuenta,
                            m_cuenta.abreviatura AS abre_cuenta
                        FROM pagos p
                        INNER JOIN monedas m ON p.id_moneda = m.id_moneda
                        LEFT JOIN detalles_pagos dp ON p.id_pago = dp.id_pago
                        LEFT JOIN cuentas_cobrar cc ON dp.id_cobrar = cc.id_cobrar
                        LEFT JOIN conceptos con ON cc.id_concepto = con.id_conceptos
                        LEFT JOIN atletas atl ON cc.id_atleta = atl.id_atleta
                        LEFT JOIN monedas m_cuenta ON cc.id_moneda = m_cuenta.id_moneda
                        WHERE 1=1";

            if (!empty($filtro['filtro'])) {
                $p = "%" . trim($filtro['filtro']) . "%";
                $sentencia .= " AND (
                    con.nombre LIKE :f1 OR 
                    atl.nombres LIKE :f2 OR 
                    atl.apellidos LIKE :f3 OR
                    p.referencia LIKE :f4 OR
                    m.nombre LIKE :f5
                )";
                $params[':f1'] = $p;
                $params[':f2'] = $p;
                $params[':f3'] = $p;
                $params[':f4'] = $p;
                $params[':f5'] = $p;
            }

            $sentencia .= " ORDER BY p.fecha DESC, p.id_pago DESC";

            $stmt = $conex->prepare($sentencia);
            $stmt->execute($params);
            $filas = $stmt->fetchAll();

            // Agrupación de pagos y sus detalles en PHP
            $pagosAgrupados = [];
            foreach ($filas as $row) {
                $id = $row['id_pago'];
                if (!isset($pagosAgrupados[$id])) {
                    $pagosAgrupados[$id] = [
                        'id_pago' => $id,
                        'fecha_pago' => $row['fecha_pago'],
                        'monto_pagado' => $row['monto_pagado'],
                        'monto_vuelto' => $row['monto_vuelto'],
                        'referencia' => $row['referencia'],
                        'estatus' => $row['estatus'],
                        'tasa_cambio' => $row['tasa_cambio'],
                        'simbolo' => $row['simbolo'],
                        'abre' => $row['abre'],
                        'moneda' => $row['moneda'],
                        'concepto_pago' => 'Pago Múltiple', 
                        'detalles' => []
                    ];
                }
                
                // Si el pago es de una sola cuenta o queremos mostrar el primer concepto
                if (count($pagosAgrupados[$id]['detalles']) === 0 && !empty($row['concepto_pago'])) {
                    $pagosAgrupados[$id]['concepto_pago'] = $row['concepto_pago'];
                } else if (count($pagosAgrupados[$id]['detalles']) > 0) {
                    $pagosAgrupados[$id]['concepto_pago'] = 'Pago Múltiple';
                }

                if (!empty($row['id_detalle_pago'])) {
                    $pagosAgrupados[$id]['detalles'][] = [
                        'id_detalle_pago' => $row['id_detalle_pago'],
                        'atleta' => $row['nombre_atleta'] . ' ' . $row['nombre_apellido'],
                        'concepto' => $row['concepto_pago'],
                        'monto' => $row['monto_abonado'],
                        'moneda' => $row['simbolo_cuenta'] . ' ' . $row['abre_cuenta']
                    ];
                }
            }

            $datos = array_values($pagosAgrupados);

            return array('accion' => 'consultar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Pagos', $e->getMessage(), 'Modelo_Consultar');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    public function obtenerTasaBackend($monedaBase, $monedaPago)
    {
        if (($monedaBase === 'USD' && $monedaPago === 'USDT') || ($monedaBase === 'USDT' && $monedaPago === 'USD')) {
            return 1.0000;
        }
        if ($monedaBase === $monedaPago) {
            return 1.0000;
        }

        if (!defined('EXCHANGE_RATE_API_KEY')) {
            throw new Exception("La clave de la API (EXCHANGE_RATE_API_KEY) no está definida.");
        }
        
        $apiKey = EXCHANGE_RATE_API_KEY;
        $url = "https://v6.exchangerate-api.com/v6/{$apiKey}/pair/{$monedaBase}/{$monedaPago}";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); 
        $response = curl_exec($ch);
        curl_close($ch);

        $datosApi = json_decode($response, true);

        if (isset($datosApi['result']) && $datosApi['result'] === 'success') {
            $tasa = $datosApi['conversion_rate'] ?? $datosApi['conversion_rates'][$monedaPago] ?? null;
            if ($tasa !== null) {
                return floatval($tasa);
            }
        }
        throw new Exception("No se pudo obtener la tasa de cambio de {$monedaBase} a {$monedaPago}.");
    }

    private function Incluir(): array
    {
        $conex = null;
        try {
            if (!is_array($this->id_cuenta) || empty($this->id_cuenta)) {
                throw new Exception("Debe seleccionar al menos una cuenta por cobrar.");
            }

            $conex = $this->conex();
            $conex->beginTransaction();

            // Verificar método y moneda del pago
            if (!$this->verificarExistencia('id_metodo', $this->id_metodo, 'metodos_pago', NULL)) {
                throw new Exception("El método de pago no es válido.");
            }
            if (!$this->verificarExistencia('id_moneda', $this->id_moneda, 'monedas', NULL)) {
                throw new Exception("La moneda seleccionada no es válida.");
            }

            // Obtener ISO de la moneda de pago
            $stmtMonedaPago = $conex->prepare("SELECT abreviatura FROM monedas WHERE id_moneda = ?");
            $stmtMonedaPago->execute([$this->id_moneda]);
            $monedaPagoObj = $stmtMonedaPago->fetch(PDO::FETCH_ASSOC);
            if (!$monedaPagoObj) throw new Exception("Moneda de pago no encontrada.");
            $isoPago = mb_strtoupper($monedaPagoObj['abreviatura']);

            // Insertar el pago principal
            $columnas = ["id_metodo", "id_moneda", "monto_pago", "tasa_cambio", "fecha", "estatus"];
            $marcadores = [":id_metodo", ":id_moneda", ":monto_pago", ":tasa_cambio", ":fecha", "1"];

            if ($this->referencia !== null && $this->referencia !== '') {
                $columnas[] = "referencia";
                $marcadores[] = ":referencia";
            }

            $sql = "INSERT INTO pagos (" . implode(", ", $columnas) . ") VALUES (" . implode(", ", $marcadores) . ")";
            $stmt = $conex->prepare($sql);

            $stmt->bindValue(':id_metodo', $this->id_metodo, PDO::PARAM_INT);
            $stmt->bindValue(':id_moneda', $this->id_moneda, PDO::PARAM_INT);
            $stmt->bindValue(':monto_pago', $this->monto);
            $stmt->bindValue(':tasa_cambio', $this->tasa);
            $stmt->bindValue(':fecha', $this->fecha);

            if ($this->referencia !== null && $this->referencia !== '') {
                $stmt->bindValue(':referencia', $this->referencia);
            }

            $stmt->execute();
            $id_pago = $conex->lastInsertId();

            // Preparar statements para el bucle
            $stmtCuenta = $conex->prepare("SELECT c.monto_pendiente, m.abreviatura FROM cuentas_cobrar c INNER JOIN monedas m ON c.id_moneda = m.id_moneda WHERE c.id_cobrar = ?");
            $stmtUpdateCuenta = $conex->prepare("UPDATE cuentas_cobrar SET monto_pendiente = ?, estatus = ? WHERE id_cobrar = ?");
            $stmtInsertDetalle = $conex->prepare("INSERT INTO detalles_pagos (id_pago, id_cobrar, monto_abonado) VALUES (?, ?, ?)");

            $vuelto_pago_currency = $this->monto;

            foreach ($this->id_cuenta as $id_cobrar) {
                if ($vuelto_pago_currency <= 0) break; // Si ya se acabó el dinero, no procesar más cuentas

                $stmtCuenta->execute([$id_cobrar]);
                $cuentaData = $stmtCuenta->fetch(PDO::FETCH_ASSOC);
                
                if (!$cuentaData || floatval($cuentaData['monto_pendiente']) <= 0) {
                    continue; // Cuenta inválida o ya pagada
                }

                $monto_pendiente = floatval($cuentaData['monto_pendiente']);
                $isoCuenta = mb_strtoupper($cuentaData['abreviatura']);

                $tasa_cambio = $this->obtenerTasaBackend($isoCuenta, $isoPago);
                $deuda_en_moneda_pago = $monto_pendiente * $tasa_cambio;

                if ($vuelto_pago_currency >= $deuda_en_moneda_pago) {
                    $monto_abonado_cuenta = $monto_pendiente;
                    $vuelto_pago_currency -= $deuda_en_moneda_pago;
                    $nuevo_pendiente = 0;
                    $nuevo_estatus = 1; // Pagada
                } else {
                    $monto_abonado_cuenta = $vuelto_pago_currency / $tasa_cambio;
                    $nuevo_pendiente = $monto_pendiente - $monto_abonado_cuenta;
                    $nuevo_estatus = 0; // Sigue pendiente
                    $vuelto_pago_currency = 0;
                }

                $stmtUpdateCuenta->execute([$nuevo_pendiente, $nuevo_estatus, $id_cobrar]);
                $stmtInsertDetalle->execute([$id_pago, $id_cobrar, $monto_abonado_cuenta]);
            }

            // Actualizar vuelto en el pago principal
            $stmtVuelto = $conex->prepare("UPDATE pagos SET monto_vuelto = ? WHERE id_pago = ?");
            $stmtVuelto->execute([$vuelto_pago_currency, $id_pago]);

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

            // Bloquear el pago y verificar estatus actual
            $stmtVerif = $conex->prepare("SELECT estatus FROM pagos WHERE id_pago = ? FOR UPDATE");
            $stmtVerif->execute([$this->id]);
            $pago = $stmtVerif->fetch(PDO::FETCH_ASSOC);

            if (!$pago) {
                throw new Exception(INVALID_ID);
            }
            if ((int)$pago['estatus'] === 2) {
                throw new Exception("El pago ya se encuentra anulado.");
            }

            // 1. Obtener los detalles del pago para restaurar las cuentas
            $stmtDetalles = $conex->prepare("SELECT id_cobrar, monto_abonado FROM detalles_pagos WHERE id_pago = ?");
            $stmtDetalles->execute([$this->id]);
            $detalles = $stmtDetalles->fetchAll(PDO::FETCH_ASSOC);

            // 2. Restaurar el monto pendiente y el estatus a pendiente (0) en cada cuenta
            $stmtRestaurar = $conex->prepare("UPDATE cuentas_cobrar SET monto_pendiente = monto_pendiente + ?, estatus = 0 WHERE id_cobrar = ?");
            foreach ($detalles as $det) {
                $stmtRestaurar->execute([$det['monto_abonado'], $det['id_cobrar']]);
            }

            // 3. Cambiar el estatus del pago a anulado (2)
            $sql = "UPDATE `pagos` SET `estatus`= 2 WHERE id_pago = :id";
            $stmt = $conex->prepare($sql);
            $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);
            $stmt->execute();

            $conex->commit();
            return ['accion' => 'exito', 'mensaje' => 'Pago anulado y cuentas restauradas exitosamente.'];
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
