<?php
// app/models/Notificacion.php

namespace App\modelo;

use Exception;
use PDO;

class ModeloNotificaciones extends Conexion
{

    public function registrarYNotificar(int $idUsuario, string $titulo, string $mensaje, string $tipo): bool
    {
        try {
            $conex = $this->conexSG();

            // 1. Guardar en Historial
            $sql = "INSERT INTO notificaciones (id_usuario, titulo, mensaje, tipo) 
                    VALUES (:id_usuario, :titulo, :mensaje, :tipo)";
            $stmt = $conex->prepare($sql);
            $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
            $stmt->bindValue(':titulo', $titulo);
            $stmt->bindValue(':mensaje', $mensaje);
            $stmt->bindValue(':tipo', $tipo);
            $stmt->execute();

            // 2. Preparar transmisión en vivo
            $payload = [
                'id_usuario' => $idUsuario,
                'titulo'     => $titulo,
                'mensaje'    => $mensaje,
                'tipo'       => $tipo,
                'fecha'      => date('d-m-Y h:i A')
            ];

            $this->dispararWebSocket($payload);
            return true;
        } catch (Exception $e) {
            if (function_exists('logs')) {
                logs('Notificaciones', $e->getMessage(), 'Modelo_Registrar');
            }
            return false;
        }
    }

    // Dentro de app/models/Notificacion.php

    public function consultarPorUsuario(int $idUsuario)
    {
        try {
            $conex = $this->conexSG();

            // Traemos las notificaciones ordenadas para que las más recientes salgan primero
            $sql = "SELECT id_notificacion, titulo, mensaje, tipo, creado_en 
                FROM notificaciones 
                WHERE id_usuario = :id_usuario 
                ORDER BY creado_en DESC 
                LIMIT 20"; // Limitamos a 20 para no saturar el modal

            $stmt = $conex->prepare($sql);
            $stmt->bindValue(':id_usuario', $idUsuario, \PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            if (function_exists('logs')) {
                logs('Notificaciones', $e->getMessage(), 'Modelo_ConsultarPorUsuario');
            }
            return [];
        }
    }

    private function dispararWebSocket(array $data)
    {
        $fp = @fsockopen("localhost", 8080, $errno, $errstr, 2);
        if (!$fp) return;

        $sessionName = session_name();
        $sessionId = session_id();
        $cookie = "Cookie: {$sessionName}={$sessionId}\r\n";

        // 1. Handshake HTTP
        $key = base64_encode(random_bytes(16));
        $header = "GET / HTTP/1.1\r\n" .
                  "Host: localhost:8080\r\n" .
                  "Upgrade: websocket\r\n" .
                  "Connection: Upgrade\r\n" .
                  "Sec-WebSocket-Key: $key\r\n" .
                  "Sec-WebSocket-Version: 13\r\n" .
                  $cookie . "\r\n";
        fwrite($fp, $header);
        
        // Leer respuesta de Ratchet para confirmar el upgrade
        stream_set_timeout($fp, 1);
        fread($fp, 1024);

        // 2. Empaquetar y enmascarar el JSON (RFC 6455)
        $payload = json_encode($data, JSON_INVALID_UTF8_SUBSTITUTE);
        if ($payload === false) {
            fclose($fp);
            return;
        }
        $len = strlen($payload);
        $frame = chr(129); // 0x81: Fin + Text Frame

        if ($len <= 125) {
            $frame .= chr($len | 128); // 128 = enmascarado
        } elseif ($len < 65536) {
            $frame .= chr(126 | 128) . pack('n', $len);
        } else {
            fclose($fp);
            return; // Muy grande
        }

        $mask = random_bytes(4);
        $frame .= $mask;
        for ($i = 0; $i < $len; $i++) {
            $frame .= $payload[$i] ^ $mask[$i % 4];
        }

        // 3. Enviar y cerrar
        fwrite($fp, $frame);
        
        // Frame de cierre
        fwrite($fp, chr(136) . chr(128) . random_bytes(4));
        fclose($fp);
    }

    public function verificarChequeoDeHoy(): bool
    {
        $conex = $this->conexSG();
        $sql = "SELECT COUNT(*) FROM notificaciones 
                WHERE DATE(creado_en) = CURDATE() 
                AND tipo IN ('cumpleaños', 'torneo', 'cuentas')";
        $stmt = $conex->query($sql);
        if (!$stmt) {
            return false;
        }
        return (int)$stmt->fetchColumn() > 0;
    }
}
