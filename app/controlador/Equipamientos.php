<?php
use App\modelo\ModeloEquipamientos;

require_once __DIR__ . '/Base.php';

$id_modulo = _MD_EQUIPAMIENTO_; 
$permisos = procesarPermisos($id_modulo, $bitacora ?? null);

$nombreClaseModelo = 'App\modelo\ModeloEquipamientos';

if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloEquipamientos();

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitudEquipamiento($objModelo, $id_modulo, $bitacora ?? null, $permisos);
} else {
    cargarVista($pagina);
}

function manejarSolicitudEquipamiento($obj, $id_modulo, $bitacoraObj, array $permisos): void
{
    try {
        $tokenRecibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!isset($_SESSION['token']) || !hash_equals($_SESSION['token'], $tokenRecibido)) {
            throw new Exception('Error de seguridad: Token inválido.');
        }

        $accion = filter_var($_POST['accion'], FILTER_SANITIZE_SPECIAL_CHARS);

        switch ($accion) {
            case 'consultar':
                echo json_encode($obj->ProcesarDatos(['accion' => 'consultar']));
                break;
            case 'cargar_combos':
                echo json_encode($obj->ProcesarDatos(['accion' => 'cargar_combos']));
                break;
            case 'incluir':
                // ¡AQUÍ ESTÁ EL CAMBIO! De 'incluir' a 'registrar'
                if (empty($permisos['registrar'])) throw new Exception('No tienes permisos para registrar inventario.');
                
                if (!is_numeric($_POST['id_catalogo']) || !is_numeric($_POST['id_estado'])) throw new Exception('Datos inválidos.');
                $datos = [
                    'accion' => 'incluir',
                    'id_catalogo' => $_POST['id_catalogo'],
                    'id_estado' => $_POST['id_estado']
                ];
                $resultado = $obj->ProcesarDatos($datos);
                if ($resultado['accion'] === 'exito') registrarBitacora($bitacoraObj, $id_modulo, "Registró una nueva pieza de equipamiento.");
                echo json_encode($resultado);
                break;
            case 'modificar':
                if (empty($permisos['modificar'])) throw new Exception('No tienes permisos para modificar.');
                if (!is_numeric($_POST['id_equipamiento'])) throw new Exception('ID Inválido.');
                
                $datos = [
                    'accion' => 'modificar',
                    'id_equipamiento' => $_POST['id_equipamiento'],
                    'id_catalogo' => $_POST['id_catalogo'],
                    'id_estado' => $_POST['id_estado']
                ];
                $resultado = $obj->ProcesarDatos($datos);
                if ($resultado['accion'] === 'exito') registrarBitacora($bitacoraObj, $id_modulo, "Modificó el equipo ID: " . $_POST['id_equipamiento']);
                echo json_encode($resultado);
                break;
            case 'eliminar':
                if (empty($permisos['eliminar'])) throw new Exception('No tienes permisos para eliminar.');
                $resultado = $obj->ProcesarDatos(['accion' => 'eliminar', 'id_equipamiento' => $_POST['id_equipamiento']]);
                if ($resultado['accion'] === 'exito') registrarBitacora($bitacoraObj, $id_modulo, "Eliminó el equipo ID: " . $_POST['id_equipamiento']);
                
                // Si el modelo devolvió un mensaje de error personalizado (ej. llave foránea), lo mapeamos
                if ($resultado['accion'] === 'error') $resultado['mensaje'] = $resultado['codigo'];
                echo json_encode($resultado);
                break;
            default:
                throw new Exception('Acción no permitida.');
        }
    } catch (Exception $e) {
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}