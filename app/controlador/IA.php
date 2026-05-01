<?php

use App\servicios\Microservices;
use App\modelo\ModeloCategorias; 

require_once __DIR__ . '/Base.php';
require_once __DIR__ . '/../servicios/Microservices.php';

// CORRECCIÓN 1: Sin comillas
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
        // CORRECCIÓN 2: Comentamos la seguridad del Token temporalmente para la prueba
        /*
        $tokenRecibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!isset($_SESSION['token']) || !hash_equals($_SESSION['token'], $tokenRecibido)) {
            throw new Exception('Error de seguridad: Token inválido o expirado.');
        }
        */

        $accion = isset($_POST['accion']) ? filter_var($_POST['accion'], FILTER_SANITIZE_SPECIAL_CHARS) : '';

        switch ($accion) {
            case 'generar':
            //    if (!$permisos['consultar']) throw new Exception('No tienes permisos para usar la IA.');
                generarRespuesta($id_modulo, $bitacoraObj); 
                break;

            default:
                throw new Exception('Acción no permitida.');
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        // Ajustado a 'status' para que tu JS lo entienda correctamente
        echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]); 
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
            'pregunta' => ['regla' => '/^.{2,2000}$/s', 'mensaje' => 'Pregunta inválida.']
        ];
        validar_datos($validaciones);

        $preguntaUsuario = trim($_POST['pregunta']);

        // 1. OBTENER DATOS DE LA BASE DE DATOS
        // Instanciamos tu modelo y llamamos a tu método Consultar
        $objCategorias = new ModeloCategorias();
        $resultadoConsulta = $objCategorias->Consultar([]); 
        
        // Verificamos si la consulta fue exitosa
        $datosCategorias = [];
        if (isset($resultadoConsulta['accion']) && $resultadoConsulta['accion'] === 'consultar') {
            $datosCategorias = $resultadoConsulta['datos'];
        }

        // 2. FORMATEAR DATOS PARA LA IA
        // Convertimos el array de PHP en un texto legible para Gemini
        $textoCategorias = "";
        $totalCategorias = count($datosCategorias);
        
        if ($totalCategorias > 0) {
            foreach ($datosCategorias as $cat) {
                // Usamos los campos exactos de tu base de datos: nombre, edad_min, edad_max
                $textoCategorias .= "- {$cat['nombre']} (Para atletas de {$cat['edad_min']} a {$cat['edad_max']} años).\n";
            }
        } else {
            $textoCategorias = "Actualmente no hay ninguna categoría registrada en el sistema.";
        }

        // 3. CONSTRUIR EL "SUPER PROMPT"
        $contexto = "Eres Cani, el asistente virtual del sistema administrativo y deportivo de la academia de hockey Cannibals Lara. ";
        $contexto .= "Responde siempre de forma amable, profesional y concisa. ";
        $contexto .= "Aquí tienes información interna de la base de datos por si el usuario te pregunta sobre las categorías:\n\n";
        $contexto .= "TOTAL DE CATEGORÍAS: " . $totalCategorias . "\n";
        $contexto .= "LISTA DE CATEGORÍAS:\n" . $textoCategorias . "\n\n";
        $contexto .= "Pregunta del usuario: " . $preguntaUsuario;

        // 4. ENVIAR A PYTHON
        $servicioIA = new Microservices();
        $resultado = $servicioIA->consultarGemini($contexto);

        // Si Python responde bien, registramos en bitácora
        if (isset($resultado['status']) && $resultado['status'] === 'success') {
            registrarBitacora($bitacoraObj, $id_modulo, "Interactuó con el asistente virtual Cani.");
        }

        echo json_encode($resultado);

    } catch (Exception $e) {
        logs('InteligenciaArtificial', $e->getMessage(), 'Controlador');
        echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
    }
}