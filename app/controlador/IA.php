<?php

use App\servicios\Microservices;
use App\modelo\ModeloCategorias; 
use App\modelo\ModeloTorneos;

require_once __DIR__ . '/Base.php';
require_once __DIR__ . '/../servicios/Microservices.php';

// ID del módulo definido en config.php
$id_modulo = _MD_IA_; 

$permisos = procesarPermisos($id_modulo, $bitacora ?? null);

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitudIA(null, $id_modulo, $bitacora ?? null, $permisos);
} else {
    $pagina = $_GET['pagina'] ?? 'Ia';
    cargarVista($pagina);
}

/**
 * --- FUNCIONES DEL CONTROLADOR ---
 */

function manejarSolicitudIA($obj, $id_modulo, $bitacoraObj, array $permisos): void 
{
    try {
        // Seguridad del Token (Comentada para pruebas según tu versión actual)
        /*
        $tokenRecibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!isset($_SESSION['token']) || !hash_equals($_SESSION['token'], $tokenRecibido)) {
            throw new Exception('Error de seguridad: Token inválido o expirado.');
        }
        */

        $accion = isset($_POST['accion']) ? filter_var($_POST['accion'], FILTER_SANITIZE_SPECIAL_CHARS) : '';

        switch ($accion) {
            case 'generar':
                // if (!$permisos['consultar']) throw new Exception('No tienes permisos para usar la IA.');
                generarRespuesta($id_modulo, $bitacoraObj); 
                break;

            default:
                throw new Exception('Acción no permitida.');
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]); 
    }
}

/**
 * Acciones específicas
 */

function generarRespuesta($id_modulo, $bitacoraObj): void
{
    try {
        $validaciones = [
            'pregunta' => ['regla' => '/^.{2,2000}$/s', 'mensaje' => 'Pregunta inválida.']
        ];
        validar_datos($validaciones);

        $preguntaUsuario = trim($_POST['pregunta']);

        // 1. OBTENER DATOS DE CATEGORÍAS
        $objCategorias = new ModeloCategorias();
        $resCategorias = $objCategorias->Consultar([]); 
        
        $datosCategorias = [];
        if (isset($resCategorias['accion']) && $resCategorias['accion'] === 'consultar') {
            $datosCategorias = $resCategorias['datos'];
        }

        // 2. OBTENER DATOS DE TORNEOS
        $objTorneos = new ModeloTorneos();
        $resTorneos = $objTorneos->Consultar([]);

        $datosTorneos = [];
        if (isset($resTorneos['accion']) && $resTorneos['accion'] === 'consultar') {
            $datosTorneos = $resTorneos['datos'];
        }

        // 3. FORMATEAR DATOS DE CATEGORÍAS PARA LA IA
        $textoCategorias = "";
        $totalCategorias = count($datosCategorias);
        
        if ($totalCategorias > 0) {
            foreach ($datosCategorias as $cat) {
                $textoCategorias .= "- {$cat['nombre']} (Para atletas de {$cat['edad_min']} a {$cat['edad_max']} años).\n";
            }
        } else {
            $textoCategorias = "No hay categorías registradas.";
        }

        // 4. FORMATEAR DATOS DE TORNEOS PARA LA IA
        $textoTorneos = "";
        $totalTorneos = count($datosTorneos);

        if ($totalTorneos > 0) {
            foreach ($datosTorneos as $tor) {
                // Convertimos el estatus numérico a texto para la IA
                $estatusTexto = ($tor['estatus'] == 1) ? "Activo/Abierto" : "Inactivo/Finalizado";
                
                $textoTorneos .= "- Torneo: {$tor['nombre']}\n";
                $textoTorneos .= "  Ubicación: {$tor['ubicacion']}\n";
                $textoTorneos .= "  Duración: Desde {$tor['fecha_inicio']} hasta {$tor['fecha_fin']}\n";
                $textoTorneos .= "  Estatus actual: {$estatusTexto}\n\n";
            }
        } else {
            $textoTorneos = "No hay torneos registrados actualmente.";
        }

        // 5. CONSTRUIR EL "SUPER PROMPT" CON TODO EL CONTEXTO
        $contexto = "Eres Cani, el asistente virtual del sistema administrativo y deportivo de la academia de hockey Cannibals Lara. ";
        $contexto .= "Responde siempre de forma amable, profesional y muy concisa. ";
        $contexto .= "Utiliza la siguiente información de la base de datos para responder al usuario:\n\n";
        
        $contexto .= "--- INFORMACIÓN DE CATEGORÍAS ---\n";
        $contexto .= "Total categorías: " . $totalCategorias . "\n";
        $contexto .= $textoCategorias . "\n";

        $contexto .= "--- INFORMACIÓN DE TORNEOS ---\n";
        $contexto .= "Total torneos registrados: " . $totalTorneos . "\n";
        $contexto .= $textoTorneos . "\n";

        $contexto .= "Pregunta del usuario: " . $preguntaUsuario;

        // 6. ENVIAR A PYTHON
        $servicioIA = new Microservices();
        $resultado = $servicioIA->consultarGemini($contexto);

        // Registro en bitácora si la respuesta fue exitosa
        if (isset($resultado['status']) && $resultado['status'] === 'success') {
            registrarBitacora($bitacoraObj, $id_modulo, "Consultó información al asistente Cani sobre Categorías/Torneos.");
        }

        echo json_encode($resultado);

    } catch (Exception $e) {
        logs('InteligenciaArtificial', $e->getMessage(), 'Controlador');
        echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
    }
}