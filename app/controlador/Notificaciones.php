<?php
// app/controlador/Notificacion.php

use App\modelo\ModeloNotificaciones;

// Nota: En tu rutas.php validas con $_SESSION['id'] 
if (!isset($_SESSION['id'])) {
    echo json_encode(['accion' => 'error', 'mensaje' => 'Sesión no válida.']);
    exit();
}

$modelo = new ModeloNotificaciones();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'marcar_visto') {
    if (isset($_POST['id_notificacion'])) {
        $id_noti = (int)$_POST['id_notificacion'];
        $exito = $modelo->marcarComoVisto($id_noti);
        echo json_encode([
            'accion' => 'marcar_visto',
            'exito' => $exito
        ]);
    } else {
        echo json_encode(['accion' => 'error', 'mensaje' => 'Faltan parámetros']);
    }
    exit();
}

// Usamos $_SESSION['id'] que es el índice que maneja tu enrutador
$datos = $modelo->consultarPorUsuario($_SESSION['id']);

echo json_encode([
    'accion' => 'consultar',
    'datos'  => $datos
], JSON_INVALID_UTF8_SUBSTITUTE);
exit();