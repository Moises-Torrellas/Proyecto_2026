<?php

use App\modelo\ModeloReportes;
use App\servicios\GenerarReporteEstadistico;

require_once __DIR__ . '/Base.php';

$id_modulo = _MD_REPORTES_;
$permisos = procesarPermisos($id_modulo, '');
$nombreClaseModelo = 'App\modelo\ModeloReportes';

if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloReportes();

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitud($objModelo, $id_modulo, $bitacora, $permisos);
} else {
    registrarBitacora($bitacora, $id_modulo, 'Ingreso al Modulo');
    $variables = ['permisos' => $permisos];
    cargarVista($pagina, $variables);
}

function manejarSolicitud($obj, $id_modulo, $bitacoraObj, array $permisos): void
{
    try {
        $tokenRecibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!isset($_SESSION['token']) || !hash_equals($_SESSION['token'], $tokenRecibido)) {
            throw new Exception('Error de seguridad: Token inválido o expirado.');
        }

        $accion = isset($_POST['accion']) ? filter_var($_POST['accion'], FILTER_SANITIZE_SPECIAL_CHARS) : '';

        switch ($accion) {
            case 'consultar':
                $tipoReporte = $_POST['tipo_reporte'] ?? 'atletas';

                if ($tipoReporte === 'recaudacion') {
                    $filtros = [
                        'moneda'      => $_POST['moneda'] ?? 'todos',
                        'concepto'    => $_POST['concepto'] ?? 'todos',
                        'fecha_desde' => $_POST['fecha_desde'] ?? '',
                        'fecha_hasta' => $_POST['fecha_hasta'] ?? ''
                    ];
                    $respuesta = $obj->ConsultarRecaudacion($filtros);
                } elseif ($tipoReporte === 'inventario') {
                    $filtros = [
                        'categoria_inventario' => $_POST['categoria_inventario'] ?? 'todos',
                        'estado_fisico'        => $_POST['estado_fisico'] ?? 'todos',
                        'fecha_desde'          => $_POST['fecha_desde'] ?? '',
                        'fecha_hasta'          => $_POST['fecha_hasta'] ?? ''
                    ];
                    $respuesta = $obj->ConsultarInventario($filtros);
                } elseif ($tipoReporte === 'rendimiento') { // Corregido a elseif continuo
                    $filtros = [
                        'torneo' => $_POST['torneo'] ?? 'todos',
                        'atleta' => $_POST['atleta'] ?? 'todos' // Cambiado de equipo a atleta
                    ];
                    $respuesta = $obj->ConsultarRendimiento($filtros);
                } else {
                    $filtros = [
                        'categoria'         => $_POST['categoria'] ?? 'todos',
                        'genero'            => $_POST['genero'] ?? 'todos',
                        'incluir_retirados' => $_POST['incluir_retirados'] ?? '1'
                    ];
                    $respuesta = $obj->Consultar($filtros);
                }

                $respuesta['tipo_reporte'] = $tipoReporte;
                echo json_encode($respuesta);
                break;

            case 'MultiConsulta':
                try {
                    $categorias = $obj->ObtenerCategorias();
                    $monedas = $obj->ObtenerMonedas();
                    $conceptos = $obj->ObtenerConceptos();
                    $categorias_cat = $obj->ObtenerCategoriasCatalogo();

                    echo json_encode([
                        'accion'              => 'MultiConsulta',
                        'categorias'          => $categorias,
                        'monedas'             => $monedas,
                        'conceptos'           => $conceptos,
                        'categorias_catalogo' => $categorias_cat,
                        'torneos'             => $obj->ObtenerTorneos(),
                        'atletas'             => $obj->ObtenerAtletas(), // Cambiado de equipos a atletas
                    ]);
                } catch (Exception $e) {
                    echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
                }
                break;

            case 'generar':
                generar($id_modulo, $bitacoraObj);
                break;

            default:
                throw new Exception('Acción no permitida.');
        }
    } catch (Exception $e) {
        logs('Reportes', $e->getMessage(), 'Controlador_ManejarSolicitud');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function generar($id_modulo, $bitacoraObj): void
{
    try {
        // 1. Detectar el tipo de reporte enviado por el frontend
        $tipoReporte = $_POST['tipo_reporte'] ?? 'atletas';

        // 2. Elegir dinámicamente la plantilla estructurando de forma correcta con elseif
        if ($tipoReporte === 'recaudacion') {
            $nombreVista = 'IngresosRecaudacion';
            $mensajeErrorEmpty = 'No se encontraron registros de recaudación para generar el reporte.';
            $descripcionBitacora = "Generó un reporte estadístico de recaudación de ingresos.";
        } elseif ($tipoReporte === 'inventario') {
            $nombreVista = 'InventarioAsignaciones';
            $mensajeErrorEmpty = 'No se encontraron registros de asignaciones para generar el reporte.';
            $descripcionBitacora = "Generó un reporte estadístico de asignaciones de equipamientos.";
        } elseif ($tipoReporte === 'rendimiento') {
            $nombreVista = 'RendimientoAtletas';
            $mensajeErrorEmpty = 'No se encontraron registros de rendimiento para el reporte.';
            $descripcionBitacora = "Generó un reporte de rendimiento ofensivo y palmarés.";
        } else {
            $nombreVista = 'AtletasCategorias';
            $mensajeErrorEmpty = 'No se encontraron representantes o atletas para hacer el reporte.';
            $descripcionBitacora = "Generó un reporte estadístico de atletas.";
        }

        $grafico = $_POST['grafico_img'] ?? null;
        $datos = isset($_POST['datos_json']) ? json_decode($_POST['datos_json'], true) : [];

        if (empty($datos)) {
            echo json_encode(['accion' => 'error', 'mensaje' => $mensajeErrorEmpty]);
            exit();
        }

        $objG = new GenerarReporteEstadistico();
        $pdf = $objG->generarPDF($nombreVista, $datos, 'Reportes', $grafico);

        if (isset($pdf['accion']) && $pdf['accion'] === 'reporte') {
            registrarBitacora($bitacoraObj, $id_modulo, $descripcionBitacora);
        }
        echo json_encode($pdf);
    } catch (Exception $e) {
        logs('Reportes', $e->getMessage(), 'Controlador_Generar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}
