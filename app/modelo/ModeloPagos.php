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
    private $fecha;
    private $fecha_f;
    private $referencia;
    private $estatus;
    private $anulados;

    private $objCuentas;
    private $objTasa;
    private $objMonedas;

    public function __construct()
    {
        parent::__construct();
        $this->campoWhitelist = [
            'id' => 'metodo_pago',
            'id_cuenta' => 'codig_cargo',
            'id_metodo' => 'codigo_metodo',
            'id_moneda' => 'codigo_moneda',
        ];
        $this->llavePrimaria = 'codigo_pago';
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
        $this->fecha      = !empty($datos['fecha']) ? trim($datos['fecha']) : null;
        $this->fecha_f    = !empty($datos['fecha_f']) ? trim($datos['fecha_f']) : null;
        $this->referencia = isset($datos['referencia']) ? trim($datos['referencia']) : null;
        $this->estatus    = $datos['estatus'] ?? 1;

        $accion = $datos['accion'] ?? null;

        /* $usuario = $_GET['nombre'];
        echo "Bienvenido, " . $usuario; */

        return match ($accion) {
            'incluir'   => $this->Incluir($datos),
            'eliminar'  => $this->Eliminar(),
            'generar'   => $this->ConsultarReporte(),
            'registrar_vuelto' => $this->RegistrarVuelto($datos),
            'consultar_tasas_disponibles' => $this->ConsultarTasasDisponibles($datos),
            default => throw new Exception('La accion solicitada para el pago no es valida.')
        };
    }

    public function ConsultarTasasDisponibles($datos): array
    {
        if(!$this->objTasa) { $this->objTasa = new ModeloTasaCambios(); }
        return $this->objTasa->ConsultarTasasDisponibles($datos);
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

            $conceptoFormateado = $row['concepto_pago'] ?? '';
            if (!empty($row['fecha_cargo'])) {
                $meses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
                $timeCargo = strtotime($row['fecha_cargo']);
                $mesIndex = (int)date('m', $timeCargo) - 1;
                $fechaCargoFormateada = date('d', $timeCargo) . ' de ' . $meses[$mesIndex] . ' de ' . date('Y', $timeCargo);
                $conceptoFormateado .= ' (' . $fechaCargoFormateada . ')';
            }

            if (count($pagosAgrupados[$id]['detalles']) === 0 && !empty($conceptoFormateado)) {
                $pagosAgrupados[$id]['concepto_pago'] = $conceptoFormateado;
            } else if (count($pagosAgrupados[$id]['detalles']) > 0) {
                $pagosAgrupados[$id]['concepto_pago'] = 'Pago Múltiple';
            }

            if (!empty($row['id_detalle_pago'])) {
                $pagosAgrupados[$id]['detalles'][] = [
                    'id_detalle_pago' => $row['id_detalle_pago'],
                    'atleta' => $row['nombre_atleta'] . ' ' . $row['nombre_apellido'],
                    'concepto' => $conceptoFormateado,
                    'monto' => $row['monto_abonado'],
                    'tasa' => $row['tasa_cambio'],
                    'moneda' => $row['simbolo_cuenta'] . ' ' . $row['abre_cuenta'],
                    'moneda_tasa' => $row['simbolo'] . ' ' . $row['abre']
                ];
            }
        }

        foreach ($pagosAgrupados as $id => &$pago) {
            $montoAbonadoTotal = 0;
            foreach ($pago['detalles'] as $detalle) {
                $monto = (float)$detalle['monto'];
                $tasa = (float)$detalle['tasa'];
                // Si la tasa > 0, significa que el pago fue en otra moneda. Abonado(Base) * Tasa = Abonado(Pago)
                if ($tasa > 0) {
                    $montoAbonadoTotal += $monto * $tasa;
                } else {
                    $montoAbonadoTotal += $monto;
                }
            }
            $montoPagado = (float)$pago['monto_pagado'];
            $vueltoEsperado = round($montoPagado - $montoAbonadoTotal, 2);
            if ($vueltoEsperado < 0.01) $vueltoEsperado = 0;
            $pago['vuelto_esperado'] = $vueltoEsperado;
        }
        unset($pago);

        try {
            $conex = $this->conex();
            $stmtVueltos = $conex->prepare("SELECT v.*, m.simbolo, m.abreviatura, mp.nombre AS nombre_metodo_vuelto FROM vueltos v INNER JOIN monedas m ON v.codigo_moneda = m.codigo_moneda INNER JOIN metodos_pago mp ON v.codigo_metodo = mp.codigo_metodo");
            $stmtVueltos->execute();
            $vueltosAll = $stmtVueltos->fetchAll();
            foreach ($vueltosAll as $v) {
                if (isset($pagosAgrupados[$v['codigo_pago']])) {
                    $monto_base = (float)$pagosAgrupados[$v['codigo_pago']]['monto_vuelto'];
                    $v['monto_exceso_base'] = $monto_base;
                    $v['tasa_usada'] = ($monto_base > 0) ? ((float)$v['monto_vuelto'] / $monto_base) : 0;
                    $pagosAgrupados[$v['codigo_pago']]['vueltos'][] = $v;
                }
            }
        } catch (Exception $e) {
        }

        return array_values($pagosAgrupados);
    }

    private function Incluir(array $datos): array
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
            $this->objMonedas = new ModeloMonedas();
            $monedaPago = $this->objMonedas->Buscar($this->id_moneda);
            $monedaPagoData = $monedaPago['datos'];
            if (!$monedaPagoData) throw new Exception(INVALID_ID . '0');

            $columnas = ["codigo_metodo", "codigo_moneda", "monto_pago", "fecha", "estatus"];
            $marcadores = [":codigo_metodo", ":codigo_moneda", ":monto_pago", ":fecha", "1"];

            if ($this->referencia !== null && $this->referencia !== '') {
                $columnas[] = "referencia";
                $marcadores[] = ":referencia";
            }

            // 1. Insertamos el pago maestro
            $sql = "INSERT INTO pagos (" . implode(", ", $columnas) . ") VALUES (" . implode(", ", $marcadores) . ")";
            $stmt = $conex->prepare($sql);

            $stmt->bindValue(':codigo_metodo', $this->id_metodo, PDO::PARAM_INT);
            $stmt->bindValue(':codigo_moneda', $this->id_moneda, PDO::PARAM_INT);
            $stmt->bindValue(':monto_pago', $this->monto);
            $stmt->bindValue(':fecha', $this->fecha);

            if ($this->referencia !== null && $this->referencia !== '') {
                $stmt->bindValue(':referencia', $this->referencia);
            }

            $stmt->execute();
            $id_pago = $conex->lastInsertId();

            $stmtInsertDetalle = $conex->prepare("INSERT INTO detalles_pagos (codigo_pago, codigo_cargo, monto_abonado, tasa_cambio) VALUES (?, ?, ?, ?)");

            $vuelto = $this->monto;

            $this->objTasa = new ModeloTasaCambios();
            $datosTasa = $this->objTasa->ConsultarTasaDelDia($conex, $this->fecha, $this->id_moneda);
            if (isset($datosTasa['accion']) && $datosTasa['accion'] === 'error') {
                throw new Exception($datosTasa['mensaje']);
            }
            $tasa_cambio = (float)$datosTasa['tasa'];
            // -----------------------------
            $this->objCuentas = new ModeloCuentasCobrar();

            foreach ($this->id_cuenta as $id_cobrar) {
                if ($vuelto <= 0) break;

                $stmtCuenta = $this->objCuentas->Buscar($id_cobrar);
                $cuentaData = $stmtCuenta['datos'] ?? null;

                if (!$cuentaData) {
                    continue;
                }

                $monto_total = floatval($cuentaData[0]['monto_total']);
                $monto_abonado_historico = $this->ConsultarMontoAbonado($id_cobrar, $conex);

                $monto_pendiente = $monto_total - $monto_abonado_historico;

                if ($monto_pendiente <= 0) {
                    continue;
                }

                $deuda_en_moneda_pago = $monto_pendiente * $tasa_cambio;

                if ($vuelto >= $deuda_en_moneda_pago) {
                    $monto_abonado_cuenta = $monto_pendiente;
                    $vuelto -= $deuda_en_moneda_pago;
                } else {
                    $monto_abonado_cuenta = $vuelto / $tasa_cambio;
                    $vuelto = 0;
                }

                // El estatus del cargo ahora se actualiza automáticamente mediante el trigger en la BD
                $stmtInsertDetalle->execute([$id_pago, $id_cobrar, $monto_abonado_cuenta, $tasa_cambio]);
            }

            $conex->commit();
            
            // Si vino información de vuelto, lo registramos usando la transacción de RegistrarVuelto (o aquí mismo)
            if (isset($datos['monto_vuelto']) && $datos['monto_vuelto'] > 0) {
                $datosVuelto = [
                    'codigo_pago' => $id_pago,
                    'codigo_metodo' => $datos['codigo_metodo_vuelto'] ?? null,
                    'codigo_moneda' => $datos['codigo_moneda_vuelto'] ?? null,
                    'monto_vuelto' => $datos['monto_vuelto'],
                    'referencia' => $datos['referencia_vuelto'] ?? null,
                    'fecha_vuelto' => $datos['fecha_vuelto'] ?? date('Y-m-d')
                ];
                $this->RegistrarVuelto($datosVuelto);
                $vuelto = 0; // Ya fue registrado, no devolver exceso al JS
            }

            $desc_bitacora = "Registro de Pago por el monto de {$this->monto}";
            if ($this->referencia !== null && $this->referencia !== '') {
                $desc_bitacora .= " con referencia {$this->referencia}";
            }

            $datos_nuevos_clean = [
                'monto' => $this->monto,
                'fecha' => $this->fecha,
                'referencia' => $this->referencia ?? 'No aplica',
                'tasa_usada' => $tasa_cambio
            ];

            return array('accion' => 'exito', 'vuelto' => $vuelto, 'id_pago' => $id_pago, 'desc_bitacora' => $desc_bitacora, 'datos_nuevos' => json_encode($datos_nuevos_clean));
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
            
            $stmt = $conex->prepare("CALL RegistrarVueltoSeguro(:metodo, :pago, :moneda, :monto, :fecha, :referencia)");
            $stmt->execute([
                ':metodo' => $datos['codigo_metodo'],
                ':pago' => $datos['codigo_pago'],
                ':moneda' => $datos['codigo_moneda'],
                ':monto' => $datos['monto_vuelto'],
                ':fecha' => $datos['fecha_vuelto'] ?? date('Y-m-d'),
                ':referencia' => $datos['referencia'] ?? null
            ]);

            return ['accion' => 'exito_vuelto', 'mensaje' => 'Vuelto registrado exitosamente (SP)'];
        } catch (Exception $e) {
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

            // 1. Verificamos que el pago exista y no esté anulado ya
            // Usamos codigo_pago que es la llave primaria correcta según tu diagrama
            $stmtVerif = $conex->prepare("SELECT p.monto_pago, p.fecha, p.referencia, p.estatus, mp.nombre as metodo_pago, m.simbolo FROM pagos p INNER JOIN metodos_pago mp ON p.codigo_metodo = mp.codigo_metodo INNER JOIN monedas m ON p.codigo_moneda = m.codigo_moneda WHERE p.codigo_pago = ? FOR UPDATE");
            $stmtVerif->execute([$this->id]);
            $pagoDatos = $stmtVerif->fetch(PDO::FETCH_ASSOC);

            $pago = $pagoDatos;

            if (!$pago) {
                throw new Exception("El pago seleccionado no existe.");
            }
            if ((int)$pago['estatus'] !== 1) { // Asumiendo que 1 es activo, cualquier otra cosa es anulado
                throw new Exception("Este pago ya se encuentra anulado.");
            }

            // 2. Buscamos todos los cargos que fueron pagados o abonados con este recibo
            $stmtDetalles = $conex->prepare("SELECT codigo_cargo FROM detalles_pagos WHERE codigo_pago = ?");
            $stmtDetalles->execute([$this->id]);
            $detalles = $stmtDetalles->fetchAll();

            // 3. Devolvemos el estatus de esos cargos a "Pendiente" (1)
            $this->objCuentas = new ModeloCuentasCobrar();
            foreach ($detalles as $det) {
                $codigo_cargo = (int) $det['codigo_cargo'];

                // Llamamos a tu modelo de cuentas para que actualice el estatus a 1 (Pendiente)
                // Le pasamos la conexión actual para que se mantenga dentro de la transacción
                $this->objCuentas->ModificarEstatus($codigo_cargo, 1, $conex);
            }

            // 4. Finalmente, anulamos el pago cambiándole el estatus a 2
            // NOTA: Tu vista SQL al ver que este pago ya no es "estatus = 1", 
            // automáticamente restará este abono y devolverá el "monto_pendiente" a su cantidad original.
            $stmt = $conex->prepare("UPDATE pagos SET estatus = 2 WHERE codigo_pago = :id");
            $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);
            $stmt->execute();

            $conex->commit();
            
            $desc_bitacora = "Anuló el Pago por el monto de {$pagoDatos['simbolo']} {$pagoDatos['monto_pago']}";
            if (!empty($pagoDatos['referencia'])) {
                $desc_bitacora .= " con referencia {$pagoDatos['referencia']}";
            }

            $datos_previos_clean = [
                'monto' => $pagoDatos['monto_pago'],
                'fecha' => $pagoDatos['fecha'],
                'referencia' => $pagoDatos['referencia'] ?? 'No aplica',
                'metodo' => $pagoDatos['metodo_pago'],
                'moneda' => $pagoDatos['simbolo']
            ];

            return ['accion' => 'exito', 'mensaje' => 'Pago anulado correctamente. Las cuentas han vuelto a estado Pendiente.', 'desc_bitacora' => $desc_bitacora, 'datos_previos' => json_encode($datos_previos_clean)];
        } catch (Exception $e) {
            // Si algo falla, deshacemos todos los cambios
            if (isset($conex) && $conex->inTransaction()) {
                $conex->rollBack();
            }
            logs('Pagos', $e->getMessage(), 'Modelo_Eliminar');
            return ['accion' => 'error', 'mensaje' => $e->getMessage()];
        } finally {
            $conex = null;
        }
    }
    public function ConsultarMontoAbonado($codigo_cargo, $conex): float
    {
        try {
            $sentencia = "SELECT ObtenerMontoAbonado(:codigo_cargo) as total_abonado";

            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':codigo_cargo', $codigo_cargo, PDO::PARAM_INT);
            $stmt->execute();

            $resultado = $stmt->fetch();
            return (float)$resultado['total_abonado'];
            
        } catch (Exception $e) {
            logs('Pagos', $e->getMessage(), 'Modelo_ConsultarMontoAbonado');
            return 0.0;
        }
    }
}
