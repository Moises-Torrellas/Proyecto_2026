<?php
// app/controlador/Notificacion.php

use App\modelo\ModeloNotificaciones;

// Nota: En tu rutas.php validas con $_SESSION['id'] 
if (!isset($_SESSION['id'])) {
    echo json_encode(['accion' => 'error', 'mensaje' => 'Sesión no válida.']);
    exit();
}

$modelo = new ModeloNotificaciones();
// Usamos $_SESSION['id'] que es el índice que maneja tu enrutador
$datos = $modelo->consultarPorUsuario($_SESSION['id']);

// Cabecera indispensable para indicar que la respuesta es un JSON limpio
header('Content-Type: application/json');

echo json_encode([
    'accion' => 'consultar',
    'datos'  => $datos
], JSON_INVALID_UTF8_SUBSTITUTE);
exit();