<?php
// app/models/Notificacion.php

namespace App\modelo;

use Exception;
use PDO;

class ModeloNotificaciones extends Conexion
{
    public function __construct()
    {
        parent::__construct();
    }

    public function registrarYNotificar(int $idUsuario, string $titulo, string $mensaje, int $tipo): bool
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
            $stmt->bindValue(':tipo', $tipo, PDO::PARAM_INT);
            $stmt->execute();

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
            $sql = "SELECT id_notificacion, titulo, mensaje, tipo, creado_en, estatus 
                FROM notificaciones 
                WHERE id_usuario = :id_usuario AND estatus = 1
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



    public function verificarChequeoDeHoy(): bool
    {
        $conex = $this->conexSG();
        $sql = "SELECT COUNT(*) FROM notificaciones 
                WHERE DATE(creado_en) = CURDATE() 
                AND tipo IN (1, 2, 3)";
        $stmt = $conex->query($sql);
        if (!$stmt) {
            return false;
        }
        return (int)$stmt->fetchColumn() > 0;
    }

    public function marcarComoVisto(int $id_notificacion): bool
    {
        try {
            $conex = $this->conexSG();
            $sql = "UPDATE notificaciones SET estatus = 2 WHERE id_notificacion = :id";
            $stmt = $conex->prepare($sql);
            $stmt->bindValue(':id', $id_notificacion, \PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\Exception $e) {
            if (function_exists('logs')) {
                logs('Notificaciones', $e->getMessage(), 'Modelo_MarcarComoVisto');
            }
            return false;
        }
    }
}
