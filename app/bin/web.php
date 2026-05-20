<?php
// app/bin/socket-server.php

namespace App\bin;

require __DIR__ . '/../../vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

class AlertasSocket implements MessageComponentInterface {
    protected $clientes;

    public function __construct() {
        $this->clientes = [];
    }

    public function onOpen(ConnectionInterface $conn) {
        $headers = $conn->httpRequest->getHeaders();
        $cookieHeader = $headers['Cookie'][0] ?? '';
        
        $idUsuario = null;

        if (!empty($cookieHeader)) {
            preg_match('/SISTEMA_CBS_SESSION=([^;]+)/', $cookieHeader, $matches);
            $sessionID = $matches[1] ?? null;

            if ($sessionID) {
                // Forzamos la ruta real donde XAMPP guarda las cookies de sesión en Windows
                $sessionPath = "C:/xampp/tmp/sess_" . $sessionID;
                
                if (file_exists($sessionPath)) {
                    $sessionData = false;
                    // Reintentar hasta 5 veces (medio segundo) si el archivo está bloqueado por otro script PHP
                    for ($i = 0; $i < 5; $i++) {
                        $sessionData = @file_get_contents($sessionPath);
                        if ($sessionData !== false) break;
                        usleep(100000); // 100ms
                    }
                    
                    if ($sessionData !== false) {
                        $sessionArray = $this->unserializeSession($sessionData);
                        
                        // Buscamos cualquier variante del ID que use tu Login
                        $idUsuario = $sessionArray['id'] ?? $sessionArray['id_usuario'] ?? null;
                    }
                }
            }
        }

        if ($idUsuario) {
            $conn->idUsuario = $idUsuario;
            $this->clientes[$idUsuario][$conn->resourceId] = $conn;
            echo "[CONEXIÓN SEGURA] Usuario {$idUsuario} conectado.\n";
        } else {
            echo "[RECHAZADO] Intento de conexión sin sesión válida en XAMPP.\n";
            $conn->close();
        }
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        
        // Si el mensaje viene con un id_usuario específico, se lo enviamos solo a él
        if (isset($data['id_usuario'])) {
            $destino = $data['id_usuario'];
            if (isset($this->clientes[$destino])) {
                foreach ($this->clientes[$destino] as $conn) {
                    $conn->send($msg);
                }
                echo "[ENVIADO] Alerta enviada al usuario {$destino}.\n";
            }
        } else {
            // Si no tiene destino específico (es global), retransmitir a todos
            foreach ($this->clientes as $idUsuario => $conexiones) {
                foreach ($conexiones as $conn) {
                    $conn->send($msg);
                }
            }
            echo "[BROADCAST] Alerta enviada a todos los conectados.\n";
        }
    }

    public function onClose(ConnectionInterface $conn) {
        if (isset($conn->idUsuario)) {
            unset($this->clientes[$conn->idUsuario][$conn->resourceId]);
            if (empty($this->clientes[$conn->idUsuario])) {
                unset($this->clientes[$conn->idUsuario]);
            }
            echo "[DESCONEXIÓN] Usuario {$conn->idUsuario} desconectado.\n";
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        $conn->close();
    }

    private function unserializeSession($str) {
        $data = [];
        $offset = 0;
        while ($offset < strlen($str)) {
            $num = strpos($str, "|", $offset);
            if ($num === false) break;
            $varname = substr($str, $offset, $num - $offset);
            $offset = $num + 1;
            
            // Filtro seguro para evitar advertencias de inyección de clases no cargadas
            $dataValue = unserialize(substr($str, $offset), ['allowed_classes' => false]);
            $data[$varname] = $dataValue;
            $offset += strlen(serialize($dataValue));
        }
        return $data;
    }
}

$server = IoServer::factory(
    new HttpServer(new WsServer(new AlertasSocket())),
    8080
);

echo "Servidor WebSocket Seguro corriendo en el puerto 8080...\n";
$server->run();