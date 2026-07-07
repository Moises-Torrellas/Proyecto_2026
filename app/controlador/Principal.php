<?php
use App\modelo\ModeloPrincipal;
use App\modelo\ModeloAutomatizacion;

require_once(__DIR__ . "/Base.php");

$obj = new ModeloPrincipal();
$objAutomatizacion = new ModeloAutomatizacion();
$objAutomatizacion->EjecutarProcesos();

if (comprobarAjax() && !empty($_POST)) {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'cargar_graficos') {
        $respuesta = $obj->ConsultarGraficos();
        header('Content-Type: application/json');
        echo json_encode($respuesta);
        exit;
    }

    if ($accion === 'cargar_calendario') {
        $respuesta = $obj->ConsultarCalendario();
        header('Content-Type: application/json');
        echo json_encode($respuesta);
        exit;
    }

} else {

    
    // CARGA DIRECTA DE LAS TARJETAS
    $respuesta = $obj->ConsultarTarjetas();

    $registro = [];
    $error_bd = '';

    if (isset($respuesta['accion']) && $respuesta['accion'] === 'error') {
        $error_bd = ($respuesta['mensaje'] == DB_CONNECTION) ? 'Error al conectar con la base de datos.' : 'Error al cargar las métricas principales.';
    } else {
        // Inicializamos con 0 en caso de no haber registros
        $registro = $respuesta['datos'] ?? ['activos' => 0, 'cargos' => 0, 'torneos' => 0, 'asignaciones' => 0];
    }
    
    $variables = ['registro' => $registro, 'permisos' => $permisos ?? [], 'error_bd' => $error_bd];
    cargarVista($pagina, $variables);
}