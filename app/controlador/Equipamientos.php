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
    registrarBitacora($bitacora ?? null, $id_modulo, 'Ingreso al Modulo de Equipamientos');
    $respuesta = $objModelo->ProcesarDatos(['accion' => 'consultar']);
    $registro = $respuesta['datos'] ?? [];
    $variables = ['registro' => $registro, 'permisos' => $permisos];
    cargarVista($pagina, $variables);
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
                $respuesta = $obj->ProcesarDatos(['accion' => 'consultar']);
                $registro = $respuesta['datos'] ?? [];
                $solo_lista = true;
                include(__DIR__ . '/../vista/Equipamientos.php');
                break;
            case 'cargar_combos':
                echo json_encode($obj->ProcesarDatos(['accion' => 'cargar_combos']));
                break;
            case 'incluir':
                if (empty($permisos['registrar'])) throw new Exception('Sin permisos.');
                if (!is_numeric($_POST['id_catalogo']) || !is_numeric($_POST['id_estado'])) throw new Exception('Datos inválidos.');
                
                $resultado = $obj->ProcesarDatos(['accion' => 'incluir', 'id_catalogo' => $_POST['id_catalogo'], 'id_estado' => $_POST['id_estado']]);
                if ($resultado['accion'] === 'exito') registrarBitacora($bitacoraObj, $id_modulo, "Registró equipo.");
                else $resultado['mensaje'] = traducirErrores($resultado['codigo']);
                echo json_encode($resultado);
                break;
            case 'modificar':
                if (empty($permisos['modificar'])) throw new Exception('Sin permisos.');
                if (!is_numeric($_POST['id_equipamiento'])) throw new Exception('ID Inválido.');
                
                $resultado = $obj->ProcesarDatos(['accion' => 'modificar', 'id_equipamiento' => $_POST['id_equipamiento'], 'id_catalogo' => $_POST['id_catalogo'], 'id_estado' => $_POST['id_estado']]);
                if ($resultado['accion'] === 'exito') registrarBitacora($bitacoraObj, $id_modulo, "Modificó equipo ID: " . $_POST['id_equipamiento']);
                else $resultado['mensaje'] = traducirErrores($resultado['codigo']);
                echo json_encode($resultado);
                break;
            case 'eliminar':
                if (empty($permisos['eliminar'])) throw new Exception('Sin permisos.');
                
                $resultado = $obj->ProcesarDatos(['accion' => 'eliminar', 'id_equipamiento' => $_POST['id_equipamiento']]);
                if ($resultado['accion'] === 'exito') registrarBitacora($bitacoraObj, $id_modulo, "Eliminó equipo ID: " . $_POST['id_equipamiento']);
                else $resultado['mensaje'] = traducirErrores($resultado['codigo']);
                echo json_encode($resultado);
                break;
            default:
                throw new Exception('Acción no permitida.');
        }
    } catch (Exception $e) {
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function traducirErrores($codigo) {
    return match($codigo) {
        defined('_ERR_USO_') ? _ERR_USO_ : 'ERR_USO' => 'No se puede eliminar: El equipo tiene historial activo.',
        defined('_ERR_BD_') ? _ERR_BD_ : 'ERR_BD' => 'Error de comunicación con la base de datos.',
        default => 'Ocurrió un error inesperado.'
    };
}