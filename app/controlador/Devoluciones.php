<?php

use App\modelo\ModeloDevoluciones;
use App\modelo\ModeloAsignaciones;
use App\modelo\ModeloArticulosInventario; 
use App\modelo\ModeloEstadoFisico; 

require_once __DIR__ . '/Base.php';

$id_modulo = _MD_DEVOLUCIONES_; 
$permisos = procesarPermisos($id_modulo, '');

$nombreClaseModelo = 'App\modelo\ModeloDevoluciones';
if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloDevoluciones();
$objModelo->setAsignaciones(new ModeloAsignaciones());
$objModelo->setEquipamientos(new ModeloArticulosInventario());

$pagina = 'Devoluciones';

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitudDevolucion($objModelo, $id_modulo, $bitacora ?? null, $permisos);
} else {
    try {
        registrarBitacora($bitacora ?? null, $id_modulo, 'Ingreso al Modulo de Devoluciones');
        $respuesta = $objModelo->ConsultarDevoluciones();
        $registro = $respuesta['datos'] ?? [];
        
        $variables = ['registro' => $registro, 'permisos' => $permisos];
        cargarVista($pagina, $variables);
    } catch (Exception $e) {
        die("Error al cargar el módulo: " . $e->getMessage());
    }
}

function manejarSolicitudDevolucion($obj, $id_modulo, $bitacoraObj, array $permisos): void
{
    try {
        $tokenRecibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!isset($_SESSION['token']) || !hash_equals($_SESSION['token'], $tokenRecibido)) {
            echo json_encode(['accion' => 'error', 'mensaje' => 'Error de seguridad CSRF.']);
            return;
        }

        $accion = filter_var($_POST['accion'], FILTER_SANITIZE_SPECIAL_CHARS);

        switch ($accion) {
            case 'consultar':
                if (empty($permisos['ingresar_devoluciones'])) throw new Exception(VALIDATION);
                consultar($obj, $permisos);
                break;
            case 'MultiConsulta':
                MultiConsulta();
                break;
            case 'incluir':
                if (empty($permisos['registrar_devoluciones'])) throw new Exception(VALIDATION);
                procesarFormulario($obj, 'incluir', $id_modulo, $bitacoraObj);
                break;
            case 'modificar':
                if (empty($permisos['modificar_devoluciones'])) throw new Exception(VALIDATION);
                procesarFormulario($obj, 'modificar', $id_modulo, $bitacoraObj);
                break;
            case 'anular':
                if (empty($permisos['eliminar_devoluciones'])) throw new Exception(VALIDATION);
                anular($obj, $id_modulo, $bitacoraObj);
                break;
            case 'generar':
                if (empty($permisos['reporte_devoluciones'])) throw new Exception(VALIDATION);
                generarReporte($obj, $id_modulo, $bitacoraObj);
                break;
            default:
                throw new Exception(VALIDATION);
        }
    } catch (Exception $e) {
        echo json_encode(['accion' => 'error', 'codigo' => $e->getMessage(), 'mensaje' => decodificarError($e->getMessage())]);
    }
}

function consultar($obj, $permisos): void {
    try {
        $respuesta = $obj->ConsultarDevoluciones();
        $registro = $respuesta['datos'] ?? [];
        
        $solo_lista = true;
        include (__DIR__.'/../vista/Devoluciones.php'); 
    } catch (Exception $e) {
        echo "<div class='error'>Error al consultar los registros: " . $e->getMessage() . "</div>";
    }
}

function MultiConsulta(): void {
    try {
        $modeloAsignaciones = new ModeloAsignaciones();
        $modeloEstado = new ModeloEstadoFisico(); 

        $conex = $modeloAsignaciones->conex();
        $sql = "SELECT a.id_asignacion, a.estatus as estatus_asignacion,
                       CONCAT(at.p_nombre, ' ', at.p_apellidos) as atleta,
                       c.nombre as articulo
                FROM asignaciones a
                INNER JOIN atletas at ON a.codigo_atleta = at.codigo_atleta
                INNER JOIN articulos_inventario e ON a.codigo_articulo = e.codigo_articulo
                INNER JOIN catalogo c ON e.id_catalogo = c.id_catalogo
                ORDER BY a.fecha_asignacion DESC";
        
        $stmt = $conex->prepare($sql);
        $stmt->execute();
        $asignaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $respEstado = $modeloEstado->Consultar(); 

        echo json_encode([
            'accion'       => 'MultiConsulta',
            'asignaciones' => $asignaciones,
            'estados'      => $respEstado['datos'] ?? []
        ]);
    } catch (Exception $e) {
        echo json_encode(['accion' => 'error', 'codigo' => DB_CONNECTION, 'mensaje' => $e->getMessage()]);
    }
}

function procesarFormulario($obj, $accion, $id_modulo, $bitacoraObj): void {
    try {
        $datos = [
            'accion'           => $accion, 
            'id_devolucion'    => filter_var($_POST['id_devolucion'] ?? null, FILTER_SANITIZE_NUMBER_INT),
            'id_asignacion'    => filter_var($_POST['id_asignacion'] ?? '', FILTER_SANITIZE_NUMBER_INT), 
            'id_estado'        => filter_var($_POST['id_estado'] ?? '', FILTER_SANITIZE_NUMBER_INT), 
            'fecha_devolucion' => filter_var($_POST['fecha_devolucion'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS),
            'observacion'      => filter_var($_POST['observacion'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS)
        ];
        
        if (empty($datos['id_asignacion']) || empty($datos['id_estado']) || empty($datos['fecha_devolucion'])) {
            throw new Exception("Faltan campos obligatorios por completar.");
        }

        $resultado = $obj->ProcesarDatos($datos);
        
        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, ($accion === 'incluir' ? "Registró" : "Modificó") . " devolución ID Asig: " . $datos['id_asignacion']);
            echo json_encode(['accion' => 'exito', 'mensaje' => $resultado['mensaje'] ?? 'Operación realizada correctamente.']);
        } else {
            throw new Exception($resultado['mensaje'] ?? 'Error desconocido al procesar la solicitud.');
        }
    } catch (Exception $e) {
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function anular($obj, $id_modulo, $bitacoraObj): void {
    try {
        $datos = [
            'accion' => 'anular', 
            'id_devolucion' => filter_var($_POST['id_devolucion'] ?? '', FILTER_SANITIZE_NUMBER_INT),
            'motivo_anulacion' => filter_var($_POST['motivo_anulacion'] ?? 'Sin motivo', FILTER_SANITIZE_SPECIAL_CHARS)
        ];

        if (empty($datos['id_devolucion'])) {
            throw new Exception("ID de devolución no válido.");
        }
        
        $resultado = $obj->ProcesarDatos($datos);
        
        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Anuló devolución ID: " . $datos['id_devolucion']);
            echo json_encode(['accion' => 'exito', 'mensaje' => $resultado['mensaje'] ?? 'Anulación exitosa.']);
        } else {
            throw new Exception($resultado['mensaje'] ?? 'No se pudo anular el registro.');
        }
    } catch (Exception $e) {
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function generarReporte($obj, $id_modulo, $bitacoraObj): void {
    try {
        $datosFiltro = [
            'accion' => 'generar',
            'id_asignacion' => !empty($_POST['id_asignacion']) ? filter_var($_POST['id_asignacion'], FILTER_SANITIZE_NUMBER_INT) : null,
            'id_estado' => !empty($_POST['id_estado']) ? filter_var($_POST['id_estado'], FILTER_SANITIZE_NUMBER_INT) : null,
            'fecha_devolucion' => !empty($_POST['fecha_devolucion']) ? filter_var($_POST['fecha_devolucion'], FILTER_SANITIZE_SPECIAL_CHARS) : null,
        ];

        $respuesta = $obj->ProcesarDatos($datosFiltro);
        $datos = $respuesta['datos'] ?? [];
        
        if (empty($datos)) {
            throw new Exception('No hay registros con los filtros seleccionados.');
        }
        
        $objG = new \App\servicios\GenerarReporte();
        $pdf = $objG->generarPDF('R_Devoluciones', $datos, 'Devoluciones');
        
        if (isset($pdf['accion']) && $pdf['accion'] === 'reporte') {
            registrarBitacora($bitacoraObj, $id_modulo, "Generó reporte filtrado de devoluciones.");
            echo json_encode($pdf);
        } else {
            throw new Exception("Error al generar el documento PDF.");
        }
    } catch (Exception $e) {
        echo json_encode(['accion' => 'error', 'codigo' => VALIDATION, 'mensaje' => $e->getMessage()]);
    }
}

function decodificarError($codigo) {
    $errores = [
        VALIDATION => 'Existen datos inválidos o faltantes en el formulario.',
        DB_CONNECTION => 'Ocurrió un error de conexión con la base de datos.',
        INVALID_ID => 'El registro seleccionado no es válido.'
    ];
    return $errores[$codigo] ?? 'Error desconocido del servidor.';
}