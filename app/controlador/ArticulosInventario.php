<?php
use App\modelo\ModeloArticulosInventario;
use App\servicios\GenerarReporte; 

require_once __DIR__ . '/Base.php';

$id_modulo = _MD_ARTICULOS_INVENTARIO_;
$permisos = procesarPermisos($id_modulo, 'ingresar_articulos');

$nombreClaseModelo = 'App\modelo\ModeloArticulosInventario';

if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloArticulosInventario();

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitudArticulo($objModelo, $id_modulo, $bitacora ?? null, $permisos);
} else {
    // Unificamos el texto para que la bitácora quede limpia como pediste
    registrarBitacora($bitacora ?? null, $id_modulo, 'Ingreso al Modulo');
    
    $respuesta = $objModelo->ProcesarDatos(['accion' => 'consultar']);
    $registro = $respuesta['datos'] ?? [];
    $variables = ['registro' => $registro, 'permisos' => $permisos];
    cargarVista($pagina, $variables);
}

function manejarSolicitudArticulo($obj, $id_modulo, $bitacoraObj, array $permisos): void
{
    try {
        $tokenRecibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!isset($_SESSION['token']) || !hash_equals($_SESSION['token'], $tokenRecibido)) {
            throw new Exception('Error de seguridad: Token inválido.');
        }

        $accion = filter_var($_POST['accion'], FILTER_SANITIZE_SPECIAL_CHARS);

        switch ($accion) {
            case 'consultar':
                if (empty($permisos['ingresar_articulos'])) throw new Exception('No tienes permisos para consultar los artículos.');
                $respuesta = $obj->ProcesarDatos(['accion' => 'consultar', 'filtro' => $_POST['filtro'] ?? '']);
                $registro = $respuesta['datos'] ?? [];
                $solo_lista = true;
                include(__DIR__ . '/../vista/ArticulosInventario.php'); 
                break;
            
            case 'cargar_combos':
                echo json_encode($obj->ProcesarDatos(['accion' => 'cargar_combos']));
                break;
            
            case 'incluir':
                if (empty($permisos['registrar_articulo'])) throw new Exception('Sin permisos.');
                // Registro por lote: se recibe un JSON con [{id_catalogo, id_estado}, ...]
                $articulos = obtenerArticulosLote();
                
                $resultado = $obj->ProcesarDatos([
                    'accion'    => 'incluir',
                    'articulos' => $articulos
                ]);
                
                $datos_previos = '';
                $datos_nuevos = $resultado['datos_nuevos'] ?? '';

                if ($resultado['accion'] === 'exito') {
                    $codigos = implode(', ', $resultado['codigos'] ?? []);
                    $descripcion = count($articulos) === 1
                        ? "Registró un nuevo artículo en inventario físico ($codigos)."
                        : "Registró " . count($articulos) . " artículos en inventario físico ($codigos).";
                    registrarBitacora($bitacoraObj, $id_modulo, $descripcion, $datos_previos, $datos_nuevos);
                } else {
                    $resultado['mensaje'] = traducirErrores($resultado['codigo'] ?? '');
                    registrarBitacora($bitacoraObj, $id_modulo, "Falló al registrar artículo: " . $resultado['mensaje'], $datos_previos, $datos_nuevos);
                }
                echo json_encode($resultado);
                break;
            
            case 'modificar':
                if (empty($permisos['modificar_articulo'])) throw new Exception('Sin permisos.');
                if (!is_numeric($_POST['codigo_articulo'] ?? null)) throw new Exception('ID Inválido.');
                if (!is_numeric($_POST['id_estado'] ?? null)) throw new Exception('Seleccione un estado físico válido.');
                
                // BITÁCORA: Traer los datos previos antes de modificar
                $consultar_datos_previos = $obj->Buscar($_POST['codigo_articulo']);
                $articulo_actual = $consultar_datos_previos['datos'][0] ?? null;
                if (empty($articulo_actual)) throw new Exception('El artículo que intenta modificar no existe.');
                $datos_previos = json_encode($articulo_actual);

                // Solo el estado físico es modificable: el catálogo se conserva desde la BD
                $resultado = $obj->ProcesarDatos([
                    'accion' => 'modificar', 
                    'codigo_articulo' => $_POST['codigo_articulo'], 
                    'id_catalogo' => $articulo_actual['id_catalogo'], 
                    'id_estado' => $_POST['id_estado']
                ]);
                $datos_nuevos = $resultado['datos_nuevos'] ?? '';

                if ($resultado['accion'] === 'exito') {
                    registrarBitacora($bitacoraObj, $id_modulo, "Modificó artículo Código: " . $_POST['codigo_articulo'], $datos_previos, $datos_nuevos);
                } else {
                    $resultado['mensaje'] = traducirErrores($resultado['codigo'] ?? '');
                    registrarBitacora($bitacoraObj, $id_modulo, "Falló al modificar artículo Código: " . $_POST['codigo_articulo'] . " - " . $resultado['mensaje'], $datos_previos, $datos_nuevos);
                }
                echo json_encode($resultado);
                break;
            
            case 'eliminar':
                if (empty($permisos['eliminar_articulo'])) throw new Exception('Sin permisos.');
                
                // BITÁCORA: Tomar foto antes de retirar
                $consultar_datos_previos = $obj->Buscar($_POST['codigo_articulo']);
                $datos_previos = json_encode($consultar_datos_previos['datos'][0] ?? []);
                $datos_nuevos = '';

                $resultado = $obj->ProcesarDatos(['accion' => 'eliminar', 'codigo_articulo' => $_POST['codigo_articulo']]);
                
                if ($resultado['accion'] === 'exito') {
                    registrarBitacora($bitacoraObj, $id_modulo, "Retiró (Eliminó) artículo Código: " . $_POST['codigo_articulo'], $datos_previos, $datos_nuevos);
                } else {
                    $resultado['mensaje'] = traducirErrores($resultado['codigo'] ?? '');
                    if (isset($resultado['mensaje']) && $resultado['mensaje'] === 'Ocurrió un error inesperado.') {
                        $resultado['mensaje'] = $resultado['mensaje'] ?? 'No se pudo retirar.'; 
                    }
                    registrarBitacora($bitacoraObj, $id_modulo, "Falló al retirar artículo Código: " . $_POST['codigo_articulo'] . " - " . $resultado['mensaje'], $datos_previos, $datos_nuevos);
                }
                echo json_encode($resultado);
                break;

            case 'reincorporar':
                if (empty($permisos['eliminar_articulo'])) throw new Exception('Sin permisos.');
                
                // BITÁCORA: Tomar foto antes de reincorporar
                $consultar_datos_previos = $obj->Buscar($_POST['codigo_articulo']);
                $datos_previos = json_encode($consultar_datos_previos['datos'][0] ?? []);
                $datos_nuevos = '';

                $resultado = $obj->ProcesarDatos(['accion' => 'reincorporar', 'codigo_articulo' => $_POST['codigo_articulo']]);
                
                if ($resultado['accion'] === 'exito') {
                    registrarBitacora($bitacoraObj, $id_modulo, "Reincorporó artículo Código: " . $_POST['codigo_articulo'], $datos_previos, $datos_nuevos);
                } else {
                    $resultado['mensaje'] = traducirErrores($resultado['codigo'] ?? '');
                    registrarBitacora($bitacoraObj, $id_modulo, "Falló al reincorporar artículo Código: " . $_POST['codigo_articulo'] . " - " . $resultado['mensaje'], $datos_previos, $datos_nuevos);
                }
                echo json_encode($resultado);
                break;
            
            case 'generar': 
                if (empty($permisos['generar_articulo'])) throw new Exception('No tienes permisos para generar reportes.');
                generar($obj, $id_modulo, $bitacoraObj);
                break;
            
            default:
                throw new Exception('Acción no permitida.');
        }
    } catch (Exception $e) {
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function generar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $filtros = ['accion' => 'consultar'];
        if (!empty($_POST['id_catalogo'])) {
            $filtros['id_catalogo'] = $_POST['id_catalogo'];
        }
        if (!empty($_POST['id_estado'])) {
            $filtros['id_estado'] = $_POST['id_estado'];
        }
        $respuesta = $obj->ProcesarDatos($filtros);
        $datos = $respuesta['datos'] ?? [];

        if (empty($datos)) {
            echo json_encode(['accion' => 'error', 'mensaje' => 'No se encontraron artículos en el inventario para generar el reporte.']);
            exit();
        }
        
        $formato = filter_input(INPUT_POST, 'formato', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'pdf';
        $nombreVista = 'R_ArticulosInventario'; 
        
        if ($formato === 'excel') {
            $pdf = \App\servicios\GenerarReporte::generarExcel($nombreVista, $datos, 'InventarioFisico');
        } else {
            $objG = new GenerarReporte();
            $pdf = $objG->generarPDF($nombreVista, $datos, 'Inventario Fisico');
        }
        
        if (isset($pdf['accion']) && $pdf['accion'] === 'reporte') {
            registrarBitacora($bitacoraObj, $id_modulo, "Generó reporte del inventario de artículos en formato " . strtoupper($formato));
        }
        
        echo json_encode($pdf);
    } catch (Exception $e) {
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

/**
 * Lee y valida la lista de artículos enviada por el registro por lote.
 * Devuelve un arreglo de ['id_catalogo' => int, 'id_estado' => int].
 */
function obtenerArticulosLote(): array
{
    $maximo = 50;
    $lista = json_decode($_POST['articulos'] ?? '', true);

    if (!is_array($lista) || empty($lista)) {
        throw new Exception('Debe agregar al menos un artículo a la lista.');
    }
    if (count($lista) > $maximo) {
        throw new Exception("Solo puede registrar hasta $maximo artículos por registro.");
    }

    $articulos = [];
    foreach ($lista as $item) {
        $catalogo = (string)($item['id_catalogo'] ?? '');
        $estado   = (string)($item['id_estado'] ?? '');
        if (!ctype_digit($catalogo) || !ctype_digit($estado)) {
            throw new Exception('Datos inválidos: cada artículo debe tener un catálogo y un estado físico válidos.');
        }
        $articulos[] = ['id_catalogo' => (int)$catalogo, 'id_estado' => (int)$estado];
    }
    return $articulos;
}

function traducirErrores($codigo) {
    return match($codigo) {
        defined('_ERR_USO_') ? _ERR_USO_ : 'ERR_USO' => 'No se puede eliminar: El artículo tiene historial activo en asignaciones/devoluciones.',
        defined('_ERR_BD_') ? _ERR_BD_ : 'ERR_BD' => 'Error de comunicación con la base de datos.',
        default => 'Ocurrió un error inesperado.'
    };
}