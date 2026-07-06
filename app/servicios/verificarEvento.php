<?php
// app/scripts/VerificadorDiario.php

namespace App\servicios;

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php'; 

use Exception;
use PDO;
use App\modelo\ModeloBase;
use App\modelo\ModeloAtletas;
use App\modelo\ModeloTorneos;
use App\modelo\ModeloCuentasCobrar;
use App\modelo\ModeloNotificaciones;

class verificarEvento extends ModeloBase {
    private $db;
    private $notificacion;

    public function __construct() {
        $this->db = $this->conexSG();
        $this->notificacion = new ModeloNotificaciones();
    }

    public function procesar() {
        try {
            // Verificar si ya se chequeo hoy
            if ($this->notificacion->verificarChequeoDeHoy()) {
                return; // Ya se notificó hoy
            }

            // 1. Obtenemos absolutamente a TODOS los usuarios activos del sistema
            $sqlUsuarios = "SELECT idUsuario FROM usuarios WHERE estatus = 1";
            $todosLosUsuarios = $this->db->query($sqlUsuarios)->fetchAll();

            if (empty($todosLosUsuarios)) return;

            // 2. Instanciamos los modelos
            $atletaModel = new ModeloAtletas();
            $torneoModel = new ModeloTorneos();
            $cuentasModel = new ModeloCuentasCobrar();

            // --- CUMPLEAÑOS ---
            $atletas = $atletaModel->ConsultarCumple();
            foreach ($atletas as $atleta) {
                $msg = "Hoy está de cumpleaños el atleta: {$atleta['nombres']} {$atleta['apellidos']}.";
                foreach ($todosLosUsuarios as $usuario) {
                    $id = (int)$usuario['idUsuario'];
                    $this->notificacion->registrarYNotificar($id, "Cumpleaños Feliz", $msg, 1);
                }
            }

            // --- TORNEOS PROXIMOS ---
            $torneos = $torneoModel->ConsultarProximos();
            foreach ($torneos as $torneo) {
                $msg = "El torneo '{$torneo['nombre']}' comenzará pronto ({$torneo['fecha_inicio']}).";
                foreach ($todosLosUsuarios as $usuario) {
                    $id = (int)$usuario['idUsuario'];
                    $this->notificacion->registrarYNotificar($id, "Torneo Próximo", $msg, 3);
                }
            }

            // --- CARGOS ATRASADOS ---
            $cargos = $cuentasModel->ConsultarAtrasados();
            foreach ($cargos as $cargo) {
                $msg = "Cargo atrasado de {$cargo['p_nombre']} {$cargo['p_apellidos']} por '{$cargo['concepto']}'. Saldo pendiente: {$cargo['monto_pendiente']}. Fecha emisión: {$cargo['fecha_emision']}.";
                foreach ($todosLosUsuarios as $usuario) {
                    $id = (int)$usuario['idUsuario'];
                    $this->notificacion->registrarYNotificar($id, "Cargo Atrasado", $msg, 2);
                }
            }
            
        } catch (Exception $e) {
            if (function_exists('logs')) logs('VerificadorDiario', $e->getMessage(), 'Procesar');
        }
    }
}

if (php_sapi_name() === 'cli') {
    $verificador = new verificarEvento();
    $verificador->procesar();
}