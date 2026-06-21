<?php

use App\modelo\ModeloAtletas;
use App\modelo\ModeloRepresentantes;
use App\modelo\ModeloPosiciones;
use App\modelo\ModeloCategorias;
use App\modelo\ModeloHistorial;

use APP\servicios\GenerarCurriculum;
use App\servicios\GenerarReporte;

require_once __DIR__ . '/Base.php';

//Configuración del id del modulo
$id_modulo = _MD_ATLETAS_;

//Procesar permisos 
$permisos = procesarPermisos($id_modulo, $bitacora);

$nombreClaseModelo = 'App\modelo\ModeloAtletas';

if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$obj = new ModeloAtletas();

if (comprobarAjax() && !empty($_POST)) {
    try {
        $tokenRecibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!isset($_SESSION['token']) || !hash_equals($_SESSION['token'], $tokenRecibido)) {
            throw new Exception('Error de seguridad: Token inválido o expirado.');
        }

        $accion = isset($_POST['accion']) ? filter_var($_POST['accion'], FILTER_SANITIZE_SPECIAL_CHARS) : '';

        switch ($accion) {
            case 'consultar':
                if (!$permisos['ingresar']) throw new Exception('No tienes permisos para consultar atletas.');
                $filtro['filtro'] = $_POST['filtro'] ?? '';
                $respuesta = $obj->Consultar($filtro);

                $registro = $respuesta['datos'] ?? [];
                $solo_lista = true;

                include(__DIR__ . '/../vista/Atletas.php');
                break;
            case 'MultiConsulta':
                if (!$permisos['ingresar']) throw new Exception('No tienes permisos para consultar atletas.');

                $modeloRep = new ModeloRepresentantes();
                $modeloPos = new ModeloPosiciones();
                $modeloCat = new ModeloCategorias();

                $repRespuesta = $modeloRep->Consultar();
                $posRespuesta = $modeloPos->Consultar();
                $catRespuesta = $modeloCat->Consultar();

                echo json_encode([
                    'accion'         => 'MultiConsulta',
                    'representantes' => $repRespuesta['datos'] ?? [],
                    'posiciones'     => $posRespuesta['datos'] ?? [],
                    'categorias'     => $catRespuesta['datos'] ?? []
                ]);

                break;

            case 'incluir':

                if (!$permisos['registrar']) throw new Exception('No tienes permisos para registrar atletas.');
                validar_requeridos(['fecha_nac', 'nombre', 'apellido', 'posicion', 'categoria', 'genero']);

                $datos = [
                    'fecha_nac' => $_POST['fecha_nac'],
                    'nombre' => $_POST['nombre'],
                    'apellido' => $_POST['apellido'],
                    'posicion' => $_POST['posicion'],
                    'categoria' => $_POST['categoria'],
                    'genero' => $_POST['genero'],
                    'dorsal' => $_POST['dorsal'] ?? 0,
                    'peso' => $_POST['peso'] ?? 0,
                    'estatura' => $_POST['estatura'] ?? 0,
                ];

                if (isset($_POST['representante'])) {
                    $datos['representante'] = $_POST['representante'];
                }
                if (isset($_POST['doc_i'])) {
                    $datos['doc_identidad'] = $_POST['doc_i'];
                }
                if (isset($_POST['telefono'])) {
                    $datos['telefono'] = $_POST['telefono'];
                }
                if (isset($_POST['direccion'])) {
                    $datos['direccion'] = $_POST['direccion'];
                }

                if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception('La foto del atleta es obligatoria.');
                }


                $foto_nombre = subirImagen($_FILES['foto'], 'atleta', $datos['fecha_nac'], 'atletas', 'default.png');

                $datos['foto'] = [$foto_nombre];
                $datos['accion'] = 'incluir';

                $modeloCat = new ModeloCategorias();
                $modeloRep = new ModeloRepresentantes();
                $modeloPos = new ModeloPosiciones();

                $obj->setModeloCategorias($modeloCat);
                $obj->setModeloPosiciones($modeloPos);
                $obj->setModeloRepresentantes($modeloRep);

                $resultado = $obj->ProcesarDatos($datos);
                if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
                    registrarBitacora($bitacora, $id_modulo, "Registro al Atleta: " . $datos['nombre'] . " " . $datos['apellido']);
                    $resultado = array('accion' => 'incluir', 'mensaje' => 'Atleta registrado exitosamente.');
                } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {

                    $resultado['mensaje'] = match ($resultado['codigo']) {
                        DUPLICATE_CEDULA => 'La cedula ingresada ya pertenece a un atleta registrado.',
                        DUPLICATE_PHONE  => 'El telefono ingresado ya pertenece a un atleta registrado.',
                        INVALID_ID       => 'La categoria ingresada no existe en los registros del club.',
                        INVALID_ID . '0'   => 'La posicion ingresada no existe en los registros del club.',
                        INVALID_ID . '1'   => 'El representante ingresado no existe en los registros del club.',
                        DB_CONNECTION      => 'Ocurrio un error al conectarse con la base de datos.',
                        default          => 'Ocurrió un error inesperado en el registro.'
                    };
                }
                echo json_encode($resultado);

                break;

            case 'buscar':

                if (!$permisos['modificar']) throw new Exception('No tienes permisos para modificar Atletas.');

                validar_requeridos(['id']);

                $datos = [
                    'id' => $_POST['id'],
                    'accion' => 'buscar'
                ];

                $resultado = $obj->procesarDatos($datos);
                echo json_encode($resultado);

                break;

            case 'modificar':
                if (!$permisos['modificar']) throw new Exception('No tienes permisos para modificar Atletas.');
                validar_requeridos(['id', 'fecha_nac', 'nombre', 'apellido', 'posicion', 'categoria', 'genero', 'foto_actual']);

                $datos = [
                    'id' => $_POST['id'],
                    'fecha_nac' => $_POST['fecha_nac'],
                    'nombre' => $_POST['nombre'],
                    'apellido' => $_POST['apellido'],
                    'posicion' => $_POST['posicion'],
                    'categoria' => $_POST['categoria'],
                    'genero' => $_POST['genero'],
                    'dorsal' => $_POST['dorsal'] ?? 0,
                    'peso' => $_POST['peso'] ?? 0,
                    'estatura' => $_POST['estatura'] ?? 0,
                    'foto_actual' => $_POST['foto_actual']
                ];

                if (isset($_POST['representante'])) {
                    $datos['representante'] = $_POST['representante'];
                }
                if (isset($_POST['doc_i'])) {
                    $datos['doc_identidad'] = $_POST['doc_i'];
                }
                if (isset($_POST['telefono'])) {
                    $datos['telefono'] = $_POST['telefono'];
                }
                if (isset($_POST['direccion'])) {
                    $datos['direccion'] = $_POST['direccion'];
                }



                if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
                    $foto_nombre = $_POST['foto_actual'];
                } else {
                    $foto_nombre = subirImagen($_FILES['foto'], 'atleta', $datos['fecha_nac'], 'atletas', $_POST['foto_actual']);
                }

                $datos['foto'] = [$foto_nombre];
                $datos['accion'] = 'modificar';
                $resultado = $obj->ProcesarDatos($datos);
                if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
                    registrarBitacora($bitacora, $id_modulo, "Modifico al Atleta: " . $datos['nombre'] . " " . $datos['apellido']);
                    $resultado = array('accion' => 'modificar', 'mensaje' => 'Atleta modificado exitosamente.');
                } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {

                    $resultado['mensaje'] = match ($resultado['codigo']) {
                        DUPLICATE_CEDULA => 'La cedula ingresada ya pertenece a un atleta registrado.',
                        DUPLICATE_PHONE  => 'El telefono ingresado ya pertenece a un atleta registrado.',
                        INVALID_ID       => 'La categoria ingresada no existe en los registros del club.',
                        INVALID_ID . '0'   => 'La posicion ingresada no existe en los registros del club.',
                        INVALID_ID . '1'   => 'El representante ingresado no existe en los registros del club.',
                        DB_CONNECTION      => 'Ocurrio un error al conectarse con la base de datos.',
                        default          => 'Ocurrió un error inesperado en el registro.'
                    };
                }
                echo json_encode($resultado);
                break;
            case 'eliminar':
                if (!$permisos['eliminar']) throw new Exception('No tienes permisos para retirar Atletas.');
                validar_requeridos(['id']);

                $datos = [
                    'id' => $_POST['id'],
                    'motivo_retiro' => $_POST['motivo_retiro'] ?? 'Retiro voluntario',
                    'accion' => 'eliminar'
                ];

                $resultado = $obj->ProcesarDatos($datos);
                if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
                    registrarBitacora($bitacora, $id_modulo, "Retiro al Atleta: " . $datos['id']);
                    $resultado = array('accion' => 'eliminar', 'mensaje' => 'Atleta retirado exitosamente.');
                } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {

                    $resultado['mensaje'] = match ($resultado['codigo']) {
                        INVALID_ID       => 'El atleta no existe.',
                        DB_CONNECTION      => 'Ocurrio un error al conectarse con la base de datos.',
                        default          => 'Ocurrió un error inesperado en el retiro.'
                    };
                }
                echo json_encode($resultado);
                break;
            case 'reinscribir':
                if (!$permisos['modificar']) throw new Exception('No tienes permisos para re-inscribir Atletas.');
                validar_requeridos(['id', 'posicion', 'categoria']);

                $datos = [
                    'id' => $_POST['id'],
                    'posicion' => $_POST['posicion'],
                    'categoria' => $_POST['categoria'],
                    'dorsal' => $_POST['dorsal'] ?? 0,
                    'peso' => $_POST['peso'] ?? 0,
                    'estatura' => $_POST['estatura'] ?? 0,
                    'accion' => 'reinscribir'
                ];

                $resultado = $obj->ProcesarDatos($datos);
                if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
                    registrarBitacora($bitacora, $id_modulo, "Re-inscribió al Atleta: " . $datos['id']);
                    $resultado = array('accion' => 'reinscribir', 'mensaje' => 'Atleta re-inscrito exitosamente.');
                } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
                    $resultado['mensaje'] = 'Ocurrió un error inesperado al re-inscribir al atleta.';
                }
                echo json_encode($resultado);
                break;
            case 'generar':
                if (!$permisos['reporte']) throw new Exception('No tienes permisos para generar un reporte de Atletas.');

                $datosFiltro = ['accion' => 'generar'];

                if (!empty($_POST['edad'])) {
                    $datosFiltro['edad'] = $_POST['edad'];
                }
                if (!empty($_POST['nombre'])) {
                    $datosFiltro['nombre'] = $_POST['nombre'];
                }
                if (!empty($_POST['apellido'])) {
                    $datosFiltro['apellido'] = $_POST['apellido'];
                }
                if (!empty($_POST['categoria'])) {
                    $datosFiltro['categoria'] = $_POST['categoria'];
                }
                if (!empty($_POST['posicion'])) {
                    $datosFiltro['posicion'] = $_POST['posicion'];
                }
                if (!empty($_POST['genero']) && $_POST['genero'] != 'T') {
                    $datosFiltro['genero'] = $_POST['genero'];
                }
                if (!empty($_POST['estatus']) && $_POST['estatus'] != 'T') {
                    $datosFiltro['estatus'] = $_POST['estatus'];
                }
                if (!empty($_POST['doc_i'])) {
                    $datosFiltro['doc_identidad'] = $_POST['doc_i'];
                }

                $respuesta =  $obj->procesarDatos($datosFiltro);

                if (isset($respuesta['accion']) && $respuesta['accion'] === 'error') {
                    $mensajeError = ($respuesta['mensaje'] == DB_CONNECTION) ? 'Error al conectar con la base de datos.' : $respuesta['mensaje'];
                    echo json_encode(['accion' => 'error', 'mensaje' => $mensajeError]);
                    return;
                }

                $datos = $respuesta['datos'];

                if (empty($datos)) {
                    echo json_encode(['accion' => 'error', 'mensaje' => 'No se encontraron atletas para hacer el reporte.']);
                    return;
                }

                $nombreVista = 'R_Atletas';
                $objG = new GenerarReporte();
                $pdf = $objG->generarPDF($nombreVista, $datos, 'Atletas');
                if (isset($pdf['accion']) && $pdf['accion'] === 'reporte') {
                    registrarBitacora($bitacora, $id_modulo, "Generó reporte de atletas.");
                }
                echo json_encode($pdf);

                break;
            case 'generarCurriculum':
                if (!$permisos['reporte']) throw new Exception('No tienes permisos para generar un curriculum de Atletas.');

                validar_requeridos(['id']);
                $id_atleta = (int)$_POST['id'];

                $modeloHistorial = new ModeloHistorial();
                $datosCurriculum = $modeloHistorial->consultarCurriculum($id_atleta);

                if (empty($datosCurriculum) || empty($datosCurriculum['atleta'])) {
                    echo json_encode(['accion' => 'error', 'mensaje' => 'No se encontró información para generar el currículum de este atleta.']);
                    exit();
                }

                $nombreVista = 'Curriculum';
                $nombres = $datosCurriculum['atleta']['nombres'];
                $apellidos = $datosCurriculum['atleta']['apellidos'];
                $docIdentidad = $datosCurriculum['atleta']['doc_identidad'];
                $nombreArchivoRaw = $nombres . '_' . $apellidos . '_' . $docIdentidad;
                $nombreArchivo = str_replace(' ', '_', $nombreArchivoRaw);

                $pdf = \App\servicios\GenerarCurriculum::GenerarCu($nombreVista, $datosCurriculum, $nombreArchivo);

                if (isset($pdf['accion']) && $pdf['accion'] === 'reporte') {

                    $atletaNombre = $datosCurriculum['atleta']['nombres'] . " " . $datosCurriculum['atleta']['apellidos'];
                    registrarBitacora($bitacora, $id_modulo, "Generó currículum deportivo del atleta: " . $atletaNombre);
                }

                echo json_encode($pdf);
                break;
            default:
                throw new Exception('Acción no permitida.');
        }
    } catch (Exception $e) {
        logs('Atletas', $e->getMessage(), 'Controlador_manejarSolicitud');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
} else {
    registrarBitacora($bitacora, $id_modulo, 'Ingreso al Modulo');
    $respuesta = $obj->Consultar();

    $registro = [];
    $error_bd = '';

    if (isset($respuesta['accion']) && $respuesta['accion'] === 'error') {
        $error_bd = ($respuesta['mensaje'] == DB_CONNECTION) ? 'Error al conectar con la base de datos.' : '';
    } else {
        $registro = $respuesta['datos'] ?? [];
    }
    $variables = ['registro' => $registro, 'permisos' => $permisos, 'error_bd' => $error_bd];
    cargarVista($pagina, $variables);
}
