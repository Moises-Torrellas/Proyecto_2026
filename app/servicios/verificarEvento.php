<?php
// app/scripts/VerificadorDiario.php

namespace App\servicios;

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php'; 

use Exception;
use PDO;
use App\modelo\Conexion;
use App\modelo\ModeloAtletas;
use App\modelo\ModeloTorneos;
use App\modelo\ModeloCuentasCobrar;
use App\modelo\ModeloNotificaciones;
use App\modelo\ModeloTasaCambios;


class verificarEvento extends Conexion {
    private $db;
    private $notificacion;

    public function __construct() {
        $this->db = $this->conexSG();
        $this->notificacion = new ModeloNotificaciones();
    }

    public function procesar() {
        try {
            // 2. Instanciamos los modelos
            $atletaModel = new ModeloAtletas();
            $torneoModel = new ModeloTorneos();
            $cuentasModel = new ModeloCuentasCobrar();

            // --- CUMPLEAÑOS ---
            $atletas = $atletaModel->ConsultarCumple();
            foreach ($atletas as $atleta) {
                $msg = "Hoy está de cumpleaños el atleta: {$atleta['nombres']} {$atleta['apellidos']}.";
                $this->notificacion->notificarATodos("Cumpleaños Feliz", $msg, 1);
            }

            // --- TORNEOS PROXIMOS ---
            $torneos = $torneoModel->ConsultarProximos();
            foreach ($torneos as $torneo) {
                $msg = "El torneo '{$torneo['nombre']}' comenzará pronto ({$torneo['fecha_inicio']}).";
                $this->notificacion->notificarATodos("Torneo Próximo", $msg, 3);
            }

            // --- CARGOS ATRASADOS ---
            $cargos = $cuentasModel->ConsultarAtrasados();
            foreach ($cargos as $cargo) {
                $msg = "Cargo atrasado de {$cargo['p_nombre']} {$cargo['p_apellidos']} por '{$cargo['concepto']}'. Saldo pendiente: {$cargo['monto_pendiente']}. Fecha emisión: {$cargo['fecha_emision']}.";
                $this->notificacion->notificarATodos("Cargo Atrasado", $msg, 2);
            }

            // --- SINCRONIZACIÓN AUTOMÁTICA DE TASA DE CAMBIO ---
            $this->sincronizarTasasCambio();

            // --- ALERTAS DE STOCK MÍNIMO EN INVENTARIO ---
            $this->verificarStockMinimo();
            
        } catch (Exception $e) {
            if (function_exists('logs')) logs('VerificadorDiario', $e->getMessage(), 'Procesar');
        }
    }

    /**
     * Sincroniza automáticamente las tasas de cambio para todas las monedas no-base activas.
     * Si la tasa del día ya existe, no la repite. Envía notificación a todos los usuarios.
     */
    private function sincronizarTasasCambio(): void
    {
        try {
            $tasaModel = new ModeloTasaCambios();
            $conex = $this->conex();
            $fechaHoy = date('Y-m-d');

            // Obtener todas las monedas activas que NO son la base
            $stmtMonedas = $conex->prepare("SELECT codigo_moneda, nombre, simbolo, abreviatura FROM monedas WHERE estatus = 1 AND base != 1");
            $stmtMonedas->execute();
            $monedasNoBase = $stmtMonedas->fetchAll(PDO::FETCH_ASSOC);

            if (empty($monedasNoBase)) return;

            $tasasActualizadas = [];

            foreach ($monedasNoBase as $moneda) {
                try {
                    // obtenerTasaDelDia ya verifica si existe la tasa para hoy;
                    // si no existe, consulta la API y la registra como 'automatica'
                    $valorTasa = $tasaModel->obtenerTasaDelDia($moneda['codigo_moneda']);
                    
                    if ($valorTasa) {
                        $tasasActualizadas[] = "{$moneda['nombre']} ({$moneda['simbolo']}): " . number_format($valorTasa, 4);
                    }
                } catch (Exception $e) {
                    // Si falla la API para una moneda específica, continuamos con las demás
                    if (function_exists('logs')) {
                        logs('VerificadorDiario', "Error sincronizando tasa para {$moneda['nombre']}: " . $e->getMessage(), 'SincronizarTasas');
                    }
                }
            }

            // Enviar notificación consolidada si hubo actualizaciones
            if (!empty($tasasActualizadas)) {
                $detalleTasas = implode(' | ', $tasasActualizadas);
                $msg = "Las tasas de cambio fueron actualizadas automáticamente a la fecha de hoy ({$fechaHoy}). {$detalleTasas}";
                $this->notificacion->notificarATodos("Tasa de Cambio Actualizada", $msg, 3);
            }

        } catch (Exception $e) {
            if (function_exists('logs')) {
                logs('VerificadorDiario', $e->getMessage(), 'SincronizarTasasCambio');
            }
        }
    }

    /**
     * Verifica qué artículos del catálogo han alcanzado su stock mínimo
     * y envía notificaciones de alerta a todos los usuarios.
     */
    private function verificarStockMinimo(): void
    {
        try {
            $conex = $this->conex();

            // Consultar catálogos con su stock disponible vs stock mínimo
            $sql = "SELECT c.id_catalogo, 
                           c.nombre, 
                           c.talla, 
                           c.stock_minimo,
                           IFNULL(StockDisponibleCatalogo(c.id_catalogo), 0) AS stock_disponible
                    FROM catalogo c
                    WHERE c.stock_minimo > 0";

            $stmt = $conex->prepare($sql);
            $stmt->execute();
            $catalogos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($catalogos as $catalogo) {
                $stockDisponible = (int) $catalogo['stock_disponible'];
                $stockMinimo = (int) $catalogo['stock_minimo'];

                // Si el stock disponible ya es igual o menor al mínimo, notificar
                if ($stockDisponible <= $stockMinimo) {
                    $nombreArticulo = $catalogo['nombre'];
                    if (!empty($catalogo['talla'])) {
                        $nombreArticulo .= " (Talla: {$catalogo['talla']})";
                    }

                    $msg = "⚠️ El artículo '{$nombreArticulo}' ha alcanzado su stock mínimo. Stock disponible: {$stockDisponible} / Mínimo: {$stockMinimo}.";
                    $this->notificacion->notificarATodos("Alerta de Inventario", $msg, 4);
                }
            }

        } catch (Exception $e) {
            if (function_exists('logs')) {
                logs('VerificadorDiario', $e->getMessage(), 'VerificarStockMinimo');
            }
        }
    }
}

if (php_sapi_name() === 'cli') {
    $verificador = new verificarEvento();
    $verificador->procesar();
}