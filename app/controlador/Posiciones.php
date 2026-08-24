<?php

use App\modelo\ModeloPosiciones;
use App\servicios\GenerarReporte;

//Cargamos las funciones base para los controladores
require_once __DIR__ . '/Base.php';

// Configuración del id del módulo
$id_modulo = _MD_POSICIONES_;

//Procesar permisos (Retorna el array de permisos)
$permisos = procesarPermisos($id_modulo, 'ingresar_posiciones');

//Comprobar si el modelo existe
$nombreClaseModelo = 'App\modelo\ModeloPosiciones';
if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}
// Instanciamos la clase del objeto
$objModelo = new ModeloPosiciones();
// comprobamos si la solicitud es por medio de ajax
if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitud($objModelo, $id_modulo, $bitacora, $permisos);
} else {
    registrarBitacora($bitacora, $id_modulo, 'Ingreso al Modulo');
    $respuesta = $objModelo->Consultar();

    $error_bd = '';
    if (isset($respuesta['accion']) && $respuesta['accion'] === 'error') {
        $error_bd = ($respuesta['mensaje'] == DB_CONNECTION) ? 'Error al conectar con la base de datos.' : '';
    }

    $registro = $respuesta['datos'] ?? [];
    $variables = ['registro' => $registro, 'permisos' => $permisos, 'error_bd' => $error_bd];
    cargarVista($pagina, $variables);
}
// Funcion para manejar las peticiones recibe como parametros el objeto del modelo, el id del modulo, la bitacora y el array de permisos
function manejarSolicitud($obj, $id_modulo, $bitacoraObj, array $permisos): void
{
    try {
        //comprobamos el token de la sesion
        $tokenRecibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!isset($_SESSION['token']) || !hash_equals($_SESSION['token'], $tokenRecibido)) {
            throw new Exception('Error de seguridad: Token inválido o expirado.');
        }

        $accion = isset($_POST['accion']) ? filter_var($_POST['accion'], FILTER_SANITIZE_SPECIAL_CHARS) : '';

        // Seguridad centralizada
        switch ($accion) {
            case 'consultar':
                if (empty($permisos['ingresar_posiciones'])) throw new Exception('No tienes permisos para consultar posiciones.');
                consultar($obj, $permisos);
                break;
            case 'buscar':
                if (empty($permisos['modificar_posicion'])) throw new Exception('No tienes permisos para modificar posiciones.');
                buscar($obj);
                break;
            case 'incluir':
                if (empty($permisos['registrar_posicion'])) throw new Exception('No tienes permisos para registrar posiciones.');
                incluir($obj, $id_modulo, $bitacoraObj);
                break;
            case 'eliminar':
                if (empty($permisos['eliminar_posicion'])) throw new Exception('No tienes permisos para eliminar posiciones.');
                eliminar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'modificar':
                if (empty($permisos['modificar_posicion'])) throw new Exception('No tienes permisos para modificar posiciones.');
                modificar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'generar':
                if (empty($permisos['generar_posiciones'])) throw new Exception('No tienes permisos para generar un reporte de posiciones.');
                generar($obj, $id_modulo, $bitacoraObj);
                break;

            default:
                throw new Exception('Acción no permitida.');
        }
    } catch (Exception $e) {
        logs('Posiciones', $e->getMessage(), 'Controlador_ManejarSolicitud');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function consultar($obj, $permisos): void
{
    $filtro['filtro'] = $_POST['filtro'] ?? '';
    $respuesta = $obj->Consultar($filtro);

    $registro = $respuesta['datos'] ?? [];
    $solo_lista = true;

    include(__DIR__ . '/../vista/Posiciones.php');
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
        logs('Posiciones', $e->getMessage(), 'Controlador_Buscar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function incluir($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $requeridos = ['nombre', 'abreviatura'];
        if (!empty($_POST['descripcion'])) {
            $requeridos[] = 'descripcion';
        }
        validar_requeridos($requeridos);

        $datos = [
            'nombre'     => $_POST['nombre'],
            'abreviatura' => $_POST['abreviatura'],
            'descripcion'   => $_POST['descripcion'],
        ];
        $datos['accion'] = 'incluir';

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'incluir') {
            $datos_nuevos_json = json_encode([
                'nombre' => $_POST['nombre'],
                'abreviatura' => $_POST['abreviatura'],
                'descripcion' => $_POST['descripcion']
            ]);
            registrarBitacora($bitacoraObj, $id_modulo, "Registró la posición: " . $_POST['nombre'], '', $datos_nuevos_json);
        }

        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Posiciones', $e->getMessage(), 'Controlador_Incluir');
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

        $consultar_datos_previos = $obj->Buscar($_POST['id']);
        $posicion_previa = $consultar_datos_previos['datos'][0] ?? null;
        if (isset($posicion_previa['codigo_posicion'])) unset($posicion_previa['codigo_posicion']);
        $datos_previos_json = json_encode($posicion_previa);

        $resultado = $obj->procesarDatos($datos);
        if (isset($resultado['accion']) && $resultado['accion'] === 'eliminar') {
            $nombre_pos = $posicion_previa['nombre'] ?? $_POST['id'];
            registrarBitacora($bitacoraObj, $id_modulo, "Eliminó la posición: " . $nombre_pos, $datos_previos_json, '');
        }
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Posiciones', $e->getMessage(), 'Controlador_Eliminar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function modificar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $requeridos = ['id', 'nombre', 'abreviatura'];
        if (!empty($_POST['descripcion'])) {
            $requeridos[] = 'descripcion';
        }
        validar_requeridos($requeridos);

        $datos = [
            'id' => $_POST['id'],
            'nombre'     => $_POST['nombre'],
            'abreviatura' => $_POST['abreviatura'],
            'descripcion'   => $_POST['descripcion'],
        ];
        $datos['accion'] = 'modificar';

        $consultar_datos_previos = $obj->Buscar($_POST['id']);
        $posicion_previa = $consultar_datos_previos['datos'][0] ?? null;
        if (isset($posicion_previa['codigo_posicion'])) unset($posicion_previa['codigo_posicion']);
        $datos_previos_json = json_encode($posicion_previa);

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'modificar') {
            $datos_nuevos_json = json_encode([
                'nombre' => $_POST['nombre'],
                'abreviatura' => $_POST['abreviatura'],
                'descripcion' => $_POST['descripcion']
            ]);
            registrarBitacora($bitacoraObj, $id_modulo, "Modificó la posición: " . $_POST['nombre'], $datos_previos_json, $datos_nuevos_json);
        }

        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Posiciones', $e->getMessage(), 'Controlador_Modificar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function generar($obj, $id_modulo, $bitacoraObj)
{
    try {
        $validacionesReporte = [];
        $datosFiltro = ['accion' => 'generar'];

        if (!empty($_POST['nombre'])) {
            $validacionesReporte['nombre'] =['regla' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,30}$/', 'mensaje' => 'Nombre inválido.'];
            $datosFiltro['nombre'] = $_POST['nombre'];
        }
        if (!empty($_POST['abreviatura'])) {
            $validacionesReporte['abreviatura'] =  ['regla' => '/^[a-zA-Z]{2,4}$/', 'mensaje' => 'Abreviatura inválida.'];
            $datosFiltro['abreviatura'] = $_POST['abreviatura'];
        }

        validar_datos($validacionesReporte);

        $respuesta =  $obj->procesarDatos($datosFiltro);
        $datos = $respuesta['datos'];
        if (empty($datos)) {
            echo json_encode(['accion' => 'error', 'mensaje' => 'No se encontraron posiciones para hacer el reporte.']);
            exit();
        }
        $nombreVista = 'R_Posiciones';
        $objG = new GenerarReporte();
        
        $formato = $_POST['formato'] ?? 'pdf';
        if ($formato === 'excel') {
            $reporte = $objG->generarExcel($nombreVista, $datos, 'Posiciones');
        } else {
            $reporte = $objG->generarPDF($nombreVista, $datos, 'Posiciones');
        }

        if (isset($reporte['accion']) && $reporte['accion'] === 'reporte') {
            registrarBitacora($bitacoraObj, $id_modulo, "Generó reporte de posiciones en " . strtoupper($formato));
        }
        echo json_encode($reporte);
    } catch (Exception $e) {
        logs('Posiciones', $e->getMessage(), 'Controlador_Generar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}
