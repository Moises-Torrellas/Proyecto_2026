<?php

use App\modelo\ModeloConceptos;

// 1. Cargamos las funciones base
require_once __DIR__ . '/Base.php';

// 2. Configuración del módulo
$id_modulo = _MD_CONCEPTOS_;

// 3. Procesar permisos (Retorna el array de permisos)
$permisos = procesarPermisos($id_modulo, 'ingresar_conceptos');

// 4. Lógica de despacho (Router interno)
$nombreClaseModelo = 'App\modelo\ModeloConceptos';

if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloConceptos();

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitud($objModelo, $id_modulo, $bitacora, $permisos);
} else {
    registrarBitacora($bitacora , $id_modulo, 'Ingreso al Modulo');
    $respuesta = $objModelo->Consultar();
    
    $error_bd = '';
    if (isset($respuesta['accion']) && $respuesta['accion'] === 'error') {
        $error_bd = 'Error al conectar con la base de datos.';
    }

    $registro = $respuesta['datos'] ?? [];
    $variables = ['registro' => $registro, 'permisos' => $permisos, 'error_bd' => $error_bd];
    cargarVista($pagina, $variables);
}

/**
 * --- FUNCIONES DEL CONTROLADOR ---
 */

function manejarSolicitud($obj, $id_modulo, $bitacoraObj, array $permisos): void
{
    try {
        $tokenRecibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!isset($_SESSION['token']) || !hash_equals($_SESSION['token'], $tokenRecibido)) {
            throw new Exception('Error de seguridad: Token inválido o expirado.');
        }

        $accion = isset($_POST['accion']) ? filter_var($_POST['accion'], FILTER_SANITIZE_SPECIAL_CHARS) : '';

        // Seguridad centralizada usando las claves exactas de tu Base de Datos
        switch ($accion) {
            case 'consultar':
                if (empty($permisos['ingresar_conceptos'])) throw new Exception('No tienes permisos para consultar Concepto de pago.');
                consultar($obj, $permisos);
                break;
            case 'buscar':
                if (empty($permisos['modificar_concepto'])) throw new Exception('No tienes permisos para modificar Concepto de pago.');
                buscar($obj);
                break;
            case 'incluir':
                if (empty($permisos['registrar_concepto'])) throw new Exception('No tienes permisos para registrar Concepto de pago.');
                incluir($obj, $id_modulo, $bitacoraObj);
                break;
            case 'eliminar':
                if (empty($permisos['eliminar_concepto'])) throw new Exception('No tienes permisos para eliminar Concepto de pago.');
                eliminar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'modificar':
                if (empty($permisos['modificar_concepto'])) throw new Exception('No tienes permisos para modificar Concepto de pago.');
                modificar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'estatus':
                if (empty($permisos['bloquear_concepto'])) throw new Exception('No tienes permisos para modificar Concepto de pago.');
                cambiarEstatus($obj, $id_modulo, $bitacoraObj);
                break;
            case 'generar':
                if (empty($permisos['generar_concepto'])) throw new Exception('No tienes permisos para generar reportes.');
                generar($obj, $id_modulo, $bitacoraObj);
                break;

            default:
                throw new Exception('Acción no permitida.');
        }
    } catch (Exception $e) {
        logs('Concepto', $e->getMessage(), 'Controlador_ManejarSolicitud');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

/**
 * --- LÓGICA DE ACCIONES ---
 */

function consultar($obj, $permisos): void
{
    $filtro['filtro'] = $_POST['filtro'] ?? '';
    $respuesta = $obj->Consultar($filtro);

    $registro = $respuesta['datos'] ?? [];
    $solo_lista = true;

    include(__DIR__ . '/../vista/Conceptos.php');
}

function buscar($obj): void
{
    try {
        validar_requeridos(['id']);

        $datos = [
            'id' => $_POST['id'],
            'accion' => 'buscar'
        ];

        $resultado = $obj->procesarDatos($datos);
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Concepto', $e->getMessage(), 'Controlador_Buscar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function incluir($obj, $id_modulo, $bitacoraObj): void
{
    try { 
        validar_requeridos(['nombre', 'monto', 'frecuencia', 'dias']);

        $datos = [
            'nombre'     => $_POST['nombre'],
            'monto'   => $_POST['monto'],
            'frecuencia'   => $_POST['frecuencia'],
            'dias'   => $_POST['dias']
        ];

        $datos['accion'] = 'incluir';

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {

            registrarBitacora($bitacoraObj, $id_modulo, "Registró el Concepto de Pago: " . $_POST['nombre'] . ' ' . $_POST['monto']);
            $resultado = array('accion' => 'incluir', 'mensaje' => 'Concepto de pago registrado exitosamente.');

        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {

            $resultado['mensaje'] = match ($resultado['codigo']) {
                DUPLICATE_NAME => 'Ya existe un concepto de pago con ese nombre.',
                DB_CONNECTION      => 'Ocurrio un error al conectarse con la base de datos.',
                default          => 'Ocurrió un error inesperado en el registro.'
            };

        }
        echo json_encode($resultado);

    } catch (Exception $e) {
        logs('Concepto', $e->getMessage(), 'Controlador_Incluir');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}
function modificar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        validar_requeridos(['id', 'nombre', 'monto', 'frecuencia', 'dias']);

        $datos = [
            'id' => $_POST['id'],
            'nombre' => $_POST['nombre'],
            'monto'     => $_POST['monto'],
            'frecuencia'   => $_POST['frecuencia'],
            'dias'   => $_POST['dias']
        ];
        $datos['accion'] = 'modificar';

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {

            registrarBitacora($bitacoraObj, $id_modulo, "Modifico el proceso de pago: " . $_POST['nombre'] . ' ' . $_POST['monto']);
            $resultado = array('accion' => 'modificar', 'mensaje' => 'Proceso de pago modificado exitosamente.');

        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {

            $resultado['mensaje'] = match ($resultado['codigo']) {
                DUPLICATE_NAME => 'Ya existe un concepto de pago con ese nombre.',
                INVALID_ID     => 'No se pudo encontrar el concepto de pago.',
                DB_CONNECTION      => 'Ocurrio un error al conectarse con la base de datos.',
                default          => 'Ocurrió un error inesperado en la modificacion.'
            };

        }

        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Concepto', $e->getMessage(), 'Controlador');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}
function eliminar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        validar_requeridos(['id']);

        $datos = [
            'id' => $_POST['id'],
            'accion' => 'eliminar'
        ];

        $resultado = $obj->procesarDatos($datos);
        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {

            registrarBitacora($bitacoraObj, $id_modulo, "Elimino el concepto de pago: " . $_POST['id']);
            $resultado = array('accion' => 'eliminar', 'mensaje' => 'Concepto de pago eliminado exitosamente.');

        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {

            $resultado['mensaje'] = match ($resultado['codigo']) {
                INVALID_ID => 'El concepto de pago no existe.',
                ASSOCIATES  => 'El concepto de pago tiene cargos asociados.',
                DB_CONNECTION      => 'Ocurrio un error al conectarse con la base de datos.',
                default          => 'Ocurrió un error inesperado en la eliminacion.'
            };
        }
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Concepto', $e->getMessage(), 'Controlador_Eliminar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function cambiarEstatus($obj, $id_modulo, $bitacoraObj): void
{
    try {
        if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
            throw new Exception('ID inválido o no proporcionado.');
        }

        $datos = [
            'id' => $_POST['id'],
            'estatus' => $_POST['estatus'] ?? 1,
            'accion' => 'estatus'
        ];

        $resultado = $obj->ProcesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Actualizó el estatus del concepto de pago " . $_POST['id']);
            $resultado = array('accion' => 'estatus', 'mensaje' => 'Estatus actualizado exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = 'Ocurrió un error inesperado al actualizar el estatus en la base de datos.';
        }
        
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Concepto', $e->getMessage(), 'Controlador_Estatus');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function generar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $conex = $obj->conex();
        $nombre = trim($_POST['nombre'] ?? '');
        $frecuencia = trim($_POST['frecuencia'] ?? '');
        
        $sql = "SELECT * FROM conceptos WHERE 1=1";
        $params = [];

        if (!empty($nombre)) {
            $sql .= " AND nombre LIKE :nombre";
            $params[':nombre'] = "%" . $nombre . "%";
        }

        if (!empty($frecuencia)) {
            $sql .= " AND frecuencia = :frecuencia";
            $params[':frecuencia'] = $frecuencia;
        }
        
        $sql .= " ORDER BY codigo_concepto ASC";
        
        $stmt = $conex->prepare($sql);
        $stmt->execute($params);
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Validación estricta
        if (empty($datos)) {
            echo json_encode([
                'accion' => 'error', 
                'mensaje' => 'No se encontraron conceptos de pago con ese nombre y frecuencia.'
            ]);
            return;
        }

        registrarBitacora($bitacoraObj, $id_modulo, "Generó reporte de Conceptos");

        $fecha_reporte = date('d/m/Y h:i A');
        $usuario = $_SESSION['nombre_usuario'] ?? 'Administrador';
        
        $logo = __DIR__ . '/../../public/img/logo.png'; 
        $logo_footer = __DIR__ . '/../../public/img/logo_footer.png';

        // 3. Incluimos la vista del PDF
        ob_start();
        include(__DIR__ . '/../vista/reportes/R_Conceptos.php'); 
        $html = ob_get_clean();

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pdfBase64 = base64_encode($dompdf->output());
        
        echo json_encode([
            'accion' => 'generar', 
            'mensaje' => 'Reporte procesado con éxito.',
            'pdf' => $pdfBase64
        ]);

    } catch (Exception $e) {
        logs('Conceptos', $e->getMessage(), 'Controlador_Generar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}