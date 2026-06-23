<?php

namespace App\modelo;

use Exception;
use PDO;

class ModeloPagos extends Conexion
{
    private $id;
    private $id_cuenta;
    private $id_metodo;
    private $id_moneda;
    private $monto;
    private $tasa;
    private $fecha;
    private $fecha_f;
    private $referencia;
    private $estatus;
    private $anulados;

    private $objCuentas;
    //private $objMetodos;
    private $objMonedas;

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

    public function setCuentas(ModeloCuentasCobrar $cuentas)
    {
        $this->objCuentas = $cuentas;
    }
    public function setMonedas(ModeloMonedas $monedas)
    {
        $this->objMonedas = $monedas;
    }

    public function ProcesarDatos(array $datos): array
    {

        if (empty($datos)) {
            throw new Exception('No se proporcionaron datos para procesar.');
        }

        $this->id         = $datos['id'] ?? null;
        $this->id_cuenta  = $datos['cuenta'] ?? null;
        $this->id_metodo  = $datos['metodo'] ?? null;
        $this->id_moneda  = $datos['moneda'] ?? null;
        $this->anulados   = $datos['anulados'] ?? null;
        $this->monto      = isset($datos['monto']) ? (float) $datos['monto'] : null;
        $this->tasa       = !empty($datos['tasa']) ? (float) $datos['tasa'] : 1;
        $this->fecha      = !empty($datos['fecha']) ? trim($datos['fecha']) : null;
        $this->fecha_f    = !empty($datos['fecha_f']) ? trim($datos['fecha_f']) : null;
        $this->referencia = isset($datos['referencia']) ? trim($datos['referencia']) : null;
        $this->estatus    = $datos['estatus'] ?? 1;

        $accion = $datos['accion'] ?? null;

        /* $usuario = $_GET['nombre'];
        echo "Bienvenido, " . $usuario; */

        return match ($accion) {
            'incluir'   => $this->Incluir(),
            'eliminar'  => $this->Eliminar(),
            'generar'   => $this->ConsultarReporte(),
            'registrar_vuelto' => $this->RegistrarVuelto($datos),
            default => throw new Exception('La accion solicitada para el pago no es valida.')
        };
    }

    public function Consultar(array $filtro = []): array
    {
        try {
            $conex = $this->conex();
            $params = [];

            $sentencia = "SELECT * FROM vista_pagos WHERE 1=1";

            // Conserva el buscador de la tabla principal
            if (!empty($filtro['filtro'])) {
                $p = "%" . trim($filtro['filtro']) . "%";
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

            // La tabla general de gestión no se limita, muestra todo el historial (activos y anulados)
            $sentencia .= " ORDER BY fecha_pago DESC, id_pago DESC";

            $stmt = $conex->prepare($sentencia);
            $stmt->execute($params);
            $filas = $stmt->fetchAll();

            // Procesamos con nuestra función helper
            $datos = $this->agruparDetallesPagos($filas);

            return array('accion' => 'consultar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Pagos', $e->getMessage(), 'Modelo_Consultar');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }


    public function ConsultarReporte(array $filtro = []): array
    {
        try {
            $conex = $this->conex();
            $params = [];

            $sentencia = "SELECT * FROM vista_pagos WHERE 1=1";

            if (!empty($filtro['filtro'])) {
                $p = "%" . trim($filtro['filtro']) . "%";
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

            if (!empty($this->id_metodo)) {
                $sentencia .= " AND id_metodos = :metodo";
                $params[':metodo'] = $this->id_metodo;
            }

            if (!empty($this->id_moneda)) {
                $sentencia .= " AND id_moneda = :moneda";
                $params[':moneda'] = $this->id_moneda;
            }

            if (!empty($this->fecha) && !empty($this->fecha_f)) {
                $sentencia .= " AND fecha_pago BETWEEN :fecha_inicio AND :fecha_fin";
                $params[':fecha_inicio'] = $this->fecha;
                $params[':fecha_fin'] = $this->fecha_f;
            } else if (!empty($this->fecha)) {
                $sentencia .= " AND fecha_pago = :fecha_inicio";
                $params[':fecha_inicio'] = $this->fecha;
            } else if (!empty($this->fecha_f)) {
                $sentencia .= " AND fecha_pago = :fecha_fin";
                $params[':fecha_fin'] = $this->fecha_f;
            }

            if (empty($this->anulados)) {
                $sentencia .= " AND estatus = 1";
            }

            $sentencia .= " ORDER BY fecha_pago DESC, id_pago DESC";

            $stmt = $conex->prepare($sentencia);
            $stmt->execute($params);
            $filas = $stmt->fetchAll();

            $datos = $this->agruparDetallesPagos($filas);

            return array('accion' => 'consultar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Pagos', $e->getMessage(), 'Modelo_ConsultarReporte');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    private function agruparDetallesPagos(array $filas): array
    {
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
                    'simbolo' => $row['simbolo'],
                    'abre' => $row['abre'],
                    'moneda' => $row['moneda'],
                    'concepto_pago' => 'Pago Múltiple',
                    'nombre_metodo_pago' => $row['nombre_metodo_pago'],
                    'detalles' => [],
                    'vueltos' => []
                ];
            }

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
                    'tasa' => $row['tasa_cambio'],
                    'moneda' => $row['simbolo_cuenta'] . ' ' . $row['abre_cuenta'],
                    'moneda_tasa' => $row['simbolo'] . ' ' . $row['abre']
                ];
            }
        }

        try {
            $conex = $this->conex();
            $stmtVueltos = $conex->prepare("SELECT v.*, m.simbolo, m.abreviatura, mp.nombre AS nombre_metodo_vuelto FROM vueltos v INNER JOIN monedas m ON v.codigo_moneda = m.codigo_moneda INNER JOIN metodos_pago mp ON v.codigo_metodo = mp.id_metodo");
            $stmtVueltos->execute();
            $vueltosAll = $stmtVueltos->fetchAll();
            foreach ($vueltosAll as $v) {
                if (isset($pagosAgrupados[$v['codigo_pago']])) {
                    $pagosAgrupados[$v['codigo_pago']]['vueltos'][] = $v;
                }
            }
        } catch(Exception $e) {}

        return array_values($pagosAgrupados);
    }

    public function obtenerTasa($monedaBase, $monedaPago)
    {
        $modeloTasa = new ModeloTasaCambios();
        return $modeloTasa->obtenerTasaDeAPI($monedaBase, $monedaPago);
    }

    private function Incluir(): array
    {
        $conex = null;
        try {
            if (!is_array($this->id_cuenta) || empty($this->id_cuenta)) {
                throw new Exception(EMPTY_SELECTION);
            }

            $conex = $this->conex();
            $conex->beginTransaction();

            if (!$this->verificarExistencia('id_metodo', $this->id_metodo, 'metodos_pago', NULL)) {
                throw new Exception(INVALID_ID);
            }
            if (!$this->verificarExistencia('id_moneda', $this->id_moneda, 'monedas', NULL)) {
                throw new Exception(INVALID_ID . '0');
            }

            $monedaPago = $this->objMonedas->Buscar($this->id_moneda);
            $monedaPagoData = $monedaPago['datos'];
            if (!$monedaPagoData) throw new Exception(INVALID_ID . '0');
            $isoPago = mb_strtoupper($monedaPagoData[0]['abreviatura']);

            $columnas = ["id_metodo", "id_moneda", "monto_pago", "fecha", "estatus"];
            $marcadores = [":id_metodo", ":id_moneda", ":monto_pago", ":fecha", "1"];

            if ($this->referencia !== null && $this->referencia !== '') {
                $columnas[] = "referencia";
                $marcadores[] = ":referencia";
            }

            $sql = "INSERT INTO pagos (" . implode(", ", $columnas) . ") VALUES (" . implode(", ", $marcadores) . ")";
            $stmt = $conex->prepare($sql);

            $stmt->bindValue(':id_metodo', $this->id_metodo, PDO::PARAM_INT);
            $stmt->bindValue(':id_moneda', $this->id_moneda, PDO::PARAM_INT);
            $stmt->bindValue(':monto_pago', $this->monto);
            $stmt->bindValue(':fecha', $this->fecha);

            if ($this->referencia !== null && $this->referencia !== '') {
                $stmt->bindValue(':referencia', $this->referencia);
            }

            $stmt->execute();
            $id_pago = $conex->lastInsertId();
            //$stmtCuenta = $conex->prepare("SELECT c.monto_pendiente, m.abreviatura FROM cuentas_cobrar c INNER JOIN monedas m ON c.id_moneda = m.id_moneda WHERE c.id_cobrar = ?");
            //$stmtUpdateCuenta = $conex->prepare("UPDATE cuentas_cobrar SET monto_pendiente = ?, estatus = ? WHERE id_cobrar = ?");
            $stmtInsertDetalle = $conex->prepare("INSERT INTO detalles_pagos (id_pago, id_cobrar, monto_abonado, tasa_cambio) VALUES (?, ?, ?, ?)");

            $vuelto = $this->monto;

            foreach ($this->id_cuenta as $id_cobrar) {
                if ($vuelto <= 0) break;

                $stmtCuenta = $this->objCuentas->Buscar($id_cobrar);
                $cuentaData = $stmtCuenta['datos'] ?? null;

                if (!$cuentaData || floatval($cuentaData[0]['monto_pendiente']) <= 0) {
                    continue;
                }

                $resultadoMoneda = $this->objMonedas->Buscar((int)$cuentaData[0]['id_moneda']);

                $monedaCuentaData = $resultadoMoneda['datos'][0];

                $isoCuenta = mb_strtoupper($monedaCuentaData['abreviatura']);
                $monto_pendiente = floatval($cuentaData[0]['monto_pendiente']);

                $tasa_cambio = $this->obtenerTasa($isoCuenta, $isoPago);
                $deuda_en_moneda_pago = $monto_pendiente * $tasa_cambio;

                if ($vuelto >= $deuda_en_moneda_pago) {
                    $monto_abonado_cuenta = $monto_pendiente;
                    $vuelto -= $deuda_en_moneda_pago;
                    $nuevo_pendiente = 0;
                    $nuevo_estatus = 1; // Pagada
                } else {
                    $monto_abonado_cuenta = $vuelto / $tasa_cambio;
                    $nuevo_pendiente = $monto_pendiente - $monto_abonado_cuenta;
                    $nuevo_estatus = 0; // Sigue pendiente
                    $vuelto = 0;
                }

                $this->objCuentas->ModificarEstatus($id_cobrar, $nuevo_estatus, $nuevo_pendiente, $conex);
                $stmtInsertDetalle->execute([$id_pago, $id_cobrar, $monto_abonado_cuenta, $tasa_cambio]);
            }

            $stmtVuelto = $conex->prepare("UPDATE pagos SET monto_vuelto = ? WHERE id_pago = ?");
            $stmtVuelto->execute([$vuelto, $id_pago]);

            $conex->commit();
            return array('accion' => 'exito', 'vuelto' => $vuelto, 'id_pago' => $id_pago);
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

    private function RegistrarVuelto($datos): array
    {
        $conex = null;
        try {
            $conex = $this->conex();
            $conex->beginTransaction();

            $codigo_pago = $datos['codigo_pago'];
            $codigo_metodo = $datos['codigo_metodo'];
            $codigo_moneda = $datos['codigo_moneda'];
            $monto_vuelto = $datos['monto_vuelto'];
            $referencia = $datos['referencia'] ?? null;
            $fecha_vuelto = $datos['fecha_vuelto'] ?? date('Y-m-d');

            $sql = "INSERT INTO vueltos (codigo_metodo, codigo_pago, codigo_moneda, monto_vuelto, fecha_vuelto, referencia) 
                    VALUES (:metodo, :pago, :moneda, :monto, :fecha, :referencia)";
            $stmt = $conex->prepare($sql);
            $stmt->execute([
                ':metodo' => $codigo_metodo,
                ':pago' => $codigo_pago,
                ':moneda' => $codigo_moneda,
                ':monto' => $monto_vuelto,
                ':fecha' => $fecha_vuelto,
                ':referencia' => $referencia
            ]);

            $conex->commit();
            return ['accion' => 'exito_vuelto', 'mensaje' => 'Vuelto registrado exitosamente'];
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            logs('Pagos', $e->getMessage(), 'Modelo_RegistrarVuelto');
            return ['accion' => 'error', 'mensaje' => $e->getMessage()];
        } finally {
            $conex = null;
        }
    }

    private function Eliminar(): array
    {
        $conex = null;
        try {
            $conex = $this->conex();
            $conex->beginTransaction();

            $stmtVerif = $conex->prepare("SELECT estatus FROM pagos WHERE id_pago = ? FOR UPDATE");
            $stmtVerif->execute([$this->id]);
            $pago = $stmtVerif->fetch();

            if (!$pago) {
                throw new Exception(INVALID_ID);
            }
            if ((int)$pago['estatus'] === 2) {
                throw new Exception(ALREADY_ANNULLED);
            }

            $stmtDetalles = $conex->prepare("SELECT id_cobrar, monto_abonado FROM detalles_pagos WHERE id_pago = ?");
            $stmtDetalles->execute([$this->id]);
            $detalles = $stmtDetalles->fetchAll();

            foreach ($detalles as $det) {
                $cuentaData = $this->objCuentas->Buscar((int)$det['id_cobrar']);
                $cuenta = $cuentaData['datos'][0] ?? null;
                if ($cuenta) {
                    $nuevoPendiente = floatval($cuenta['monto_pendiente']) + floatval($det['monto_abonado']);
                    $this->objCuentas->ModificarEstatus((int)$det['id_cobrar'], 0, $nuevoPendiente, $conex);
                }
            }

            $stmt = $conex->prepare("UPDATE pagos SET estatus = 2 WHERE id_pago = :id");
            $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);
            $stmt->execute();

            $conex->commit();
            return ['accion' => 'exito'];
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
