<?php

// Importamos el servicio en lugar del modelo
use App\servicios\Microservices;

// 1. Cargamos las funciones base
require_once __DIR__ . '/Base.php';

require_once __DIR__ . '/../servicios/Microservices.php';

$id_modulo = _MD_IA_; 

$permisos = procesarPermisos($id_modulo, $bitacora ?? null);

if (comprobarAjax() && !empty($_POST)) {
    // Pasamos null donde iba el modelo, ya que usaremos el Servicio
    manejarSolicitudIA(null, $id_modulo, $bitacora ?? null, $permisos);
} else {
    cargarVista($pagina);
}

/**
 * --- FUNCIONES DEL CONTROLADOR ---
 */

function manejarSolicitudIA($obj, $id_modulo, $bitacoraObj, array $permisos): void
{
    try {
        $tokenRecibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!isset($_SESSION['token']) || !hash_equals($_SESSION['token'], $tokenRecibido)) {
            throw new Exception('Error de seguridad: Token inválido o expirado.');
        }

        $accion = isset($_POST['accion']) ? filter_var($_POST['accion'], FILTER_SANITIZE_SPECIAL_CHARS) : '';

        switch ($accion) {
            case 'generar':
                if (!$permisos['consultar']) throw new Exception('No tienes permisos para usar la IA.');
                // Llamamos a la función
                generarRespuesta($id_modulo, $bitacoraObj); 
                break;

            default:
                throw new Exception('Acción no permitida.');
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

/**
 * Acciones específicas
 */

// Quitamos el $obj de los parámetros ya que usaremos el Servicio localmente
function generarRespuesta($id_modulo, $bitacoraObj): void
{
    try {
        $validaciones = [
            'pregunta' => ['regla' => '/^.{5,2000}$/s', 'mensaje' => 'La pregunta debe tener entre 5 y 2000 caracteres.']
        ];

        validar_datos($validaciones);

        $preguntaLimpia = trim($_POST['pregunta']);

        // --- AQUÍ USAMOS EL SERVICIO DIRECTAMENTE ---
        $servicioIA = new Microservices();
        $resultado = $servicioIA->consultarGemini($preguntaLimpia);

        // Si fue exitoso, guardamos en la bitácora
        if (isset($resultado['status']) && $resultado['status'] === 'success') {
            registrarBitacora($bitacoraObj, $id_modulo, "Consultó a la IA");
        }

        // Devolvemos el JSON a la vista
        echo json_encode($resultado);

    } catch (Exception $e) {
        logs('InteligenciaArtificial', $e->getMessage(), 'Controlador');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}