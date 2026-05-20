<?php
// app/scripts/VerificadorDiario.php

namespace App\servicios;

require_once __DIR__ . '/../../vendor/autoload.php'; 

use Exception;
use PDO;
use App\modelo\ModeloBase;
use App\modelo\ModeloAtletas;
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
            // 1. Obtenemos absolutamente a TODOS los usuarios activos del sistema
            $sqlUsuarios = "SELECT idUsuario FROM usuarios WHERE estatus = 1";
            $todosLosUsuarios = $this->db->query($sqlUsuarios)->fetchAll();

            if (empty($todosLosUsuarios)) return;

            // 2. Instanciamos los modelos
            $atletaModel = new ModeloAtletas();

            // --- CUMPLEAÑOS ---
            $atletas = $atletaModel->ConsultarCumple();
            foreach ($atletas as $atleta) {
                $msg = "Hoy está de cumpleaños el atleta: {$atleta['nombres']} {$atleta['apellidos']}.";
                foreach ($todosLosUsuarios as $usuario) {
                    $id = (int)$usuario['idUsuario'];
                    $this->notificacion->registrarYNotificar($id, "Cumpleaños Feliz", $msg, 'cumpleaños');
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