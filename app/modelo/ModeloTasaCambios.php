<?php

namespace App\modelo;

use Exception;
use PDO;

class ModeloTasaCambios extends Conexion
{
    private $codigo_tasa;
    private $codigo_moneda;
    private $fecha;
    private $valor_tasa;

    public function __construct()
    {
        parent::__construct();
        $this->campoWhitelist = [
            'codigo_tasa' => 'codigo_tasa',
            'codigo_moneda' => 'codigo_moneda',
            'fecha' => 'fecha',
            'valor_tasa' => 'valor_tasa'
        ];
        $this->llavePrimaria = 'codigo_tasa';
    }

    public function ProcesarDatos(array $datos): array
    {
        if (empty($datos)) {
            throw new Exception('No se proporcionaron datos para procesar.');
        }

        $this->codigo_tasa = $datos['id'] ?? null;
        $this->codigo_moneda = $datos['codigo_moneda'] ?? null;
        $this->fecha = $datos['fecha'] ?? date('Y-m-d');
        $this->valor_tasa = $datos['valor_tasa'] ?? null;

        $accion = $datos['accion'] ?? null;

        return match ($accion) {
            'registrar' => $this->Registrar(),
            'sincronizar' => $this->Sincronizar(),
            default => throw new Exception('La accion no es valida')
        };
    }
    public function Consultar(): array
    {
        try {
            $conex = $this->conex();
            $sentencia = "SELECT t.codigo_tasa, t.fecha, t.valor_tasa, m.nombre AS moneda, m.simbolo 
                        FROM tasa_cambios t 
                        INNER JOIN monedas m ON t.codigo_moneda = m.codigo_moneda 
                        ORDER BY t.fecha DESC, t.codigo_tasa DESC";

            $stmt = $conex->prepare($sentencia);
            $stmt->execute();
            $datos = $stmt->fetchAll();

            return array('accion' => 'consultar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('TasaCambios', $e->getMessage(), 'Modelo_Consultar');
            return array('accion' => 'error', 'mensaje' => 'Error al consultar el historial de tasas.');
        } finally {
            $conex = NULL;
        }
    }

    public function ConsultarTasaDelDia($conex, $fecha_pago, $codigo_moneda): array
    {
        try {
            $sentencia = "SELECT t.valor_tasa
                      FROM tasa_cambios t 
                      INNER JOIN monedas m ON t.codigo_moneda = m.codigo_moneda
                      WHERE t.fecha = :fecha_pago AND t.codigo_moneda = :codigo_moneda";

            $stmt = $conex->prepare($sentencia);

            $stmt->bindParam(':fecha_pago', $fecha_pago);
            $stmt->bindParam(':codigo_moneda', $codigo_moneda);

            $stmt->execute();

            $datos = $stmt->fetch();

            if (!$datos) {
                return array('accion' => 'error', 'mensaje' => 'No se encontró una tasa registrada para esta fecha y moneda.');
            }

            return array('tasa' => $datos['valor_tasa']);
        } catch (Exception $e) {
            logs('TasaCambios', $e->getMessage(), 'Modelo_Consultar');
            return array('accion' => 'error', 'mensaje' => 'Error al consultar la tasa de cambio.');
        }
    }

    public function ConsultarMonedasNoBase(): array
    {
        try {
            $conex = $this->conex();
            $sentencia = "SELECT codigo_moneda, nombre, abreviatura, simbolo FROM monedas WHERE estatus = 1 ORDER BY nombre ASC";
            $stmt = $conex->prepare($sentencia);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            return array('accion' => 'consultarM', 'datos' => $datos);
        } catch (Exception $e) {
            logs('TasaCambios', $e->getMessage(), 'Modelo_ConsultarMonedasNoBase');
            return array('accion' => 'error', 'mensaje' => 'Error al cargar las monedas.');
        } finally {
            $conex = NULL;
        }
    }

    private function Registrar(): array
    {
        try {
            $conex = $this->conex();
            $conex->beginTransaction();

            // 1. Verificamos que la moneda exista
            if (!$this->verificarExistencia('codigo_moneda', $this->codigo_moneda, 'monedas', NULL, bloquear: true)) {
                throw new Exception("La moneda seleccionada no existe.");
            }

            // 2. Buscamos si ya hay un registro de esta moneda para la fecha seleccionada (hoy)
            $sqlCheck = "SELECT codigo_tasa FROM tasa_cambios WHERE codigo_moneda = :moneda AND fecha = :fecha LIMIT 1";
            $stmtCheck = $conex->prepare($sqlCheck);
            $stmtCheck->execute([
                ':moneda' => $this->codigo_moneda,
                ':fecha' => $this->fecha
            ]);
            $tasaExistente = $stmtCheck->fetchColumn();

            // 3. Decidimos: ¿Actualizamos o Insertamos?
            if ($tasaExistente) {
                // Ya existe: Actualizamos el monto
                $sentencia = "UPDATE tasa_cambios SET valor_tasa = :tasa WHERE codigo_tasa = :codigo_tasa";
                $stmt = $conex->prepare($sentencia);
                $stmt->bindParam(':tasa', $this->valor_tasa);
                $stmt->bindParam(':codigo_tasa', $tasaExistente);
                $stmt->execute();

                $mensaje = 'Tasa de cambio actualizada exitosamente.';
            } else {
                // No existe: Insertamos uno nuevo
                $sentencia = "INSERT INTO tasa_cambios (codigo_moneda, fecha, valor_tasa) VALUES (:codigo_moneda, :fecha, :tasa)";
                $stmt = $conex->prepare($sentencia);
                $stmt->bindParam(':codigo_moneda', $this->codigo_moneda);
                $stmt->bindParam(':fecha', $this->fecha);
                $stmt->bindParam(':tasa', $this->valor_tasa);
                $stmt->execute();

                $mensaje = 'Tasa de cambio registrada exitosamente.';
            }

            $conex->commit();
            return array('accion' => 'exito', 'mensaje' => $mensaje);
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollback();
            }
            logs('TasaCambios', $e->getMessage(), 'Modelo_Registrar');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    private function Sincronizar(): array
    {
        try {
            if (!$this->codigo_moneda) {
                throw new Exception("Debe seleccionar una moneda para sincronizar.");
            }

            $conex = $this->conex();

            $stmtBase = $conex->prepare("SELECT abreviatura FROM monedas WHERE base = 1 AND estatus = 1 LIMIT 1");
            $stmtBase->execute();
            $isoBase = $stmtBase->fetchColumn();
            if (!$isoBase) {
                throw new Exception("No existe una moneda base configurada en el sistema.");
            }

            $stmtMoneda = $conex->prepare("SELECT abreviatura FROM monedas WHERE codigo_moneda = :id");
            $stmtMoneda->bindParam(':id', $this->codigo_moneda);
            $stmtMoneda->execute();
            $isoMoneda = $stmtMoneda->fetchColumn();
            if (!$isoMoneda) {
                throw new Exception("Moneda seleccionada inválida.");
            }

            $tasa = $this->obtenerTasaDeAPI($isoBase, $isoMoneda);

            $this->valor_tasa = $tasa;
            $this->fecha = date('Y-m-d');

            return $this->Registrar();
        } catch (Exception $e) {
            logs('TasaCambios', $e->getMessage(), 'Modelo_Sincronizar');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        }
    }

    public function obtenerTasaDelDia($codigo_moneda)
    {
        try {
            $conex = $this->conex();
            $fecha_hoy = date('Y-m-d');

            $stmt = $conex->prepare("SELECT valor_tasa FROM tasa_cambios WHERE codigo_moneda = :moneda AND fecha = :fecha ORDER BY codigo_tasa DESC LIMIT 1");
            $stmt->execute([':moneda' => $codigo_moneda, ':fecha' => $fecha_hoy]);
            $tasaLocal = $stmt->fetchColumn();

            if ($tasaLocal !== false) {
                return (float) $tasaLocal;
            }

            $stmtBase = $conex->prepare("SELECT abreviatura FROM monedas WHERE base = 1 AND estatus = 1 LIMIT 1");
            $stmtBase->execute();
            $isoBase = $stmtBase->fetchColumn();

            $stmtMoneda = $conex->prepare("SELECT abreviatura FROM monedas WHERE codigo_moneda = :id");
            $stmtMoneda->execute([':id' => $codigo_moneda]);
            $isoMoneda = $stmtMoneda->fetchColumn();

            if ($isoBase && $isoMoneda) {
                $tasaApi = $this->obtenerTasaDeAPI($isoBase, $isoMoneda);

                $this->codigo_moneda = $codigo_moneda;
                $this->fecha = $fecha_hoy;
                $this->valor_tasa = $tasaApi;
                $this->Registrar();
                return (float) $tasaApi;
            }

            throw new Exception("No se pudo resolver la tasa del día.");
        } catch (Exception $e) {
            throw new Exception("Error obteniendo tasa del dia: " . $e->getMessage());
        }
    }

    public function obtenerTasaDeAPI($monedaBase, $monedaPago)
    {
        if (($monedaBase === 'USD' && $monedaPago === 'USDT') || ($monedaBase === 'USDT' && $monedaPago === 'USD')) {
            return 1.0000;
        }
        if ($monedaBase === $monedaPago) {
            return 1.0000;
        }

        $apiKey = defined('EXCHANGE_RATE_API_KEY') ? EXCHANGE_RATE_API_KEY : '';
        if (empty($apiKey)) {
            throw new Exception("La clave de API de tasas de cambio no está configurada.");
        }
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
        throw new Exception("No se pudo obtener la tasa de cambio de {$monedaBase} a {$monedaPago} via API.");
    }
}
