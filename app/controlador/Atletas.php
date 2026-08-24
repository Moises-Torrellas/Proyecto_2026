<?php

use App\modelo\ModeloAtletas;
use App\modelo\ModeloRepresentantes;
use App\modelo\ModeloPosiciones;
use App\modelo\ModeloCategorias;
use App\modelo\ModeloHistorial;

use APP\servicios\GenerarCurriculum;
use App\servicios\GenerarReporte;

// 1. Cargamos las funciones base
require_once __DIR__ . '/Base.php';

// 2. Configuración del módulo
$id_modulo = _MD_ATLETAS_;

// 3. Procesar permisos (Retorna el array de permisos)
$permisos = procesarPermisos($id_modulo, 'ingresar_atleta');

// 4. Lógica de despacho (Router interno)
$nombreClaseModelo = 'App\modelo\ModeloAtletas';

if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloAtletas();

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitudAtletas($objModelo, $id_modulo, $bitacora, $permisos);
} else {
    registrarBitacora($bitacora, $id_modulo, 'Ingreso al Modulo');
    $respuesta = $objModelo->Consultar();

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

/**
 * --- FUNCIONES DEL CONTROLADOR ---
 */

function manejarSolicitudAtletas($obj, $id_modulo, $bitacoraObj, array $permisos): void
{
    try {
        $tokenRecibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!isset($_SESSION['token']) || !hash_equals($_SESSION['token'], $tokenRecibido)) {
            throw new Exception('Error de seguridad: Token inválido o expirado.');
        }

        $accion = isset($_POST['accion']) ? filter_var($_POST['accion'], FILTER_SANITIZE_SPECIAL_CHARS) : '';

        // Seguridad centralizada y enrutamiento
        switch ($accion) {
            case 'consultar':
                if (empty($permisos['ingresar_atleta'])) throw new Exception('No tienes permisos para consultar atletas.');
                consultar($obj, $permisos);
                break;
            case 'MultiConsulta':
                if (empty($permisos['ingresar_atleta'])) throw new Exception('No tienes permisos para consultar atletas.');
                multiConsulta();
                break;
            case 'incluir':
                if (empty($permisos['incluir_atleta'])) throw new Exception('No tienes permisos para registrar atletas.');
                incluir($obj, $id_modulo, $bitacoraObj);
                break;
            case 'buscar':
                if (empty($permisos['modificar_atleta']) && empty($permisos['reinscribir_atleta'])) {
                    throw new Exception('No tienes permisos para consultar los datos del Atleta (modificar o re-inscribir).');
                }
                buscar($obj);
                break;
            case 'modificar':
                if (empty($permisos['modificar_atleta'])) throw new Exception('No tienes permisos para modificar Atletas.');
                modificar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'eliminar':
                if (empty($permisos['retirar_atleta'])) throw new Exception('No tienes permisos para retirar Atletas.');
                eliminar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'reinscribir':
                if (empty($permisos['reinscribir_atleta'])) throw new Exception('No tienes permisos para re-inscribir Atletas.');
                reinscribir($obj, $id_modulo, $bitacoraObj);
                break;
            case 'generar':
                if (empty($permisos['generar_atletas'])) throw new Exception('No tienes permisos para generar un reporte de Atletas.');
                generar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'generarDocumentos':
                generarDocumentos($id_modulo, $bitacoraObj);
                break;
            case 'historial':
                if (empty($permisos['ingresar_atleta'])) throw new Exception('No tienes permisos para ver el historial.');
                historial($obj);
                break;
            default:
                throw new Exception('Acción no permitida.');
        }
    } catch (Exception $e) {
        logs('Atletas', $e->getMessage(), 'Controlador_manejarSolicitud');
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

    include(__DIR__ . '/../vista/Atletas.php');
}

function multiConsulta(): void
{
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
        logs('Atletas', $e->getMessage(), 'Controlador_Buscar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function incluir($obj, $id_modulo, $bitacoraObj): void
{
    try {
        validar_requeridos(['fecha_nac', 'nombre', 'apellido', 'posicion', 'categoria', 'genero']);

        $fechaNacObj = new DateTime($_POST['fecha_nac']);
        $hoy = new DateTime();
        $edad = $hoy->format('Y') - $fechaNacObj->format('Y');

        if ($edad < 4) {
            throw new Exception('El atleta debe tener al menos 4 años.');
        } elseif ($edad > 60) {
            throw new Exception('El atleta no puede tener más de 60 años.');
        }

        $datos = [
            'fecha_nac' => $_POST['fecha_nac'],
            'nombre'    => $_POST['nombre'],
            'apellido'  => $_POST['apellido'],
            'posicion'  => $_POST['posicion'],
            'categoria' => $_POST['categoria'],
            'genero'    => $_POST['genero'],
            'dorsal'    => $_POST['dorsal'] ?? 0,
            'peso'      => $_POST['peso'] ?? 0,
            'estatura'  => $_POST['estatura'] ?? 0,
        ];

        $datos['representante'] = $_POST['representante'] ?? '';
        $datos['doc_identidad'] = $_POST['doc_i'] ?? '';
        $datos['telefono'] = $_POST['telefono'] ?? '';
        $datos['direccion'] = $_POST['direccion'] ?? '';
        $datos['lugar_nacimiento'] = $_POST['lugar_nacimiento'] ?? '';
        $datos['correo'] = filter_var($_POST['correo'] ?? '', FILTER_SANITIZE_EMAIL);
        $datos['municipio'] = $_POST['municipio'] ?? '';
        $datos['instagram'] = $_POST['instagram'] ?? '';
        $datos['talla_pantalon'] = $_POST['talla_pantalon'] ?? '';
        $datos['talla_franela'] = $_POST['talla_franela'] ?? '';
        $datos['talla_calzado'] = $_POST['talla_calzado'] ?? '';
        $datos['tipo_sangre'] = $_POST['tipo_sangre'] ?? '';
        $datos['es_alergico'] = $_POST['es_alergico'] ?? 0;
        $datos['alergias_detalle'] = $_POST['alergias_detalle'] ?? '';

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

        $datos_previos = '';
        $datos_nuevos = $resultado['datos_nuevos'] ?? '';

        $identificador = $datos['doc_identidad'] ?? 'Sin Cédula';

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {

            registrarBitacora($bitacoraObj, $id_modulo, "Registró al Atleta: " . $identificador . " - " . $datos['nombre'] . " " . $datos['apellido'], $datos_previos, $datos_nuevos);

            require_once __DIR__ . '/../servicios/verificarEvento.php';
            $verificador = new \App\servicios\verificarEvento();
            $verificador->procesar();

            $resultado = array('accion' => 'incluir', 'mensaje' => 'Atleta registrado exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {

            $resultado['mensaje'] = match ($resultado['codigo']) {
                DUPLICATE_CEDULA => 'La cedula ingresada ya pertenece a un atleta registrado.',
                DUPLICATE_PHONE  => 'El telefono ingresado ya pertenece a un atleta registrado.',
                INVALID_ID       => 'La categoria ingresada no existe en los registros del club.',
                INVALID_ID . '0' => 'La posicion ingresada no existe en los registros del club.',
                INVALID_ID . '1' => 'El representante ingresado no existe en los registros del club.',
                DB_CONNECTION    => 'Ocurrio un error al conectarse con la base de datos.',
                default          => 'Ocurrió un error inesperado en el registro.'
            };

            // 3. Registramos el error en bitácora
            $mensaje_error = "Falló al registrar al atleta: " . $identificador . " - " . $resultado['mensaje'];
            registrarBitacora($bitacoraObj, $id_modulo, $mensaje_error, $datos_previos, $datos_nuevos);
        }

        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Atletas', $e->getMessage(), 'Controlador_Incluir');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function modificar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        validar_requeridos(['id', 'fecha_nac', 'nombre', 'apellido', 'posicion', 'categoria', 'genero', 'foto_actual']);

        $fechaNacObj = new DateTime($_POST['fecha_nac']);
        $hoy = new DateTime();
        $edad = $hoy->format('Y') - $fechaNacObj->format('Y');

        if ($edad < 4) {
            throw new Exception('El atleta debe tener al menos 4 años.');
        } elseif ($edad > 60) {
            throw new Exception('El atleta no puede tener más de 60 años.');
        }

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

        $datos['representante'] = $_POST['representante'] ?? '';
        $datos['doc_identidad'] = $_POST['doc_i'] ?? '';
        $datos['telefono'] = $_POST['telefono'] ?? '';
        $datos['direccion'] = $_POST['direccion'] ?? '';
        $datos['lugar_nacimiento'] = $_POST['lugar_nacimiento'] ?? '';
        $datos['correo'] = filter_var($_POST['correo'] ?? '', FILTER_SANITIZE_EMAIL);
        $datos['municipio'] = $_POST['municipio'] ?? '';
        $datos['instagram'] = $_POST['instagram'] ?? '';
        $datos['talla_pantalon'] = $_POST['talla_pantalon'] ?? '';
        $datos['talla_franela'] = $_POST['talla_franela'] ?? '';
        $datos['talla_calzado'] = $_POST['talla_calzado'] ?? '';
        $datos['tipo_sangre'] = $_POST['tipo_sangre'] ?? '';
        $datos['es_alergico'] = $_POST['es_alergico'] ?? 0;
        $datos['alergias_detalle'] = $_POST['alergias_detalle'] ?? '';

        if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            $foto_nombre = $_POST['foto_actual'];
        } else {
            $foto_nombre = subirImagen($_FILES['foto'], 'atleta', $datos['fecha_nac'], 'atletas', $_POST['foto_actual']);
        }

        $datos['foto'] = [$foto_nombre];
        $datos['accion'] = 'modificar';

        // 1. Buscamos los datos PREVIOS antes de modificarlos
        $consultar_datos_previos = $obj->Buscar($_POST['id']);
        $atleta_previo = $consultar_datos_previos['datos'][0] ?? null; // Posición [0] por el fetchAll()
        if (isset($atleta_previo['id_atleta'])) unset($atleta_previo['id_atleta']);
        $datos_previos_json = json_encode($atleta_previo);

        // 2. Procesamos la modificación
        $resultado = $obj->ProcesarDatos($datos);

        // 3. Extraemos los datos NUEVOS del resultado
        $arr_nuevos = json_decode($resultado['datos_nuevos'] ?? '{}', true) ?: [];
        if (isset($arr_nuevos['id'])) unset($arr_nuevos['id']);
        if (isset($arr_nuevos['id_atleta'])) unset($arr_nuevos['id_atleta']);
        $datos_nuevos_json = json_encode($arr_nuevos);

        // Generamos un identificador seguro para el mensaje
        $identificador = $datos['doc_identidad'] ?? 'R-' . $atleta_previo['cedula_rep'];

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Modificó al Atleta: " . $identificador . " - " . $datos['nombre'] . " " . $datos['apellido'], $datos_previos_json, $datos_nuevos_json);

            // Verificador dinámico de eventos (Cumpleaños, etc.)
            require_once __DIR__ . '/../servicios/verificarEvento.php';
            $verificador = new \App\servicios\verificarEvento();
            $verificador->procesar();

            $resultado = array('accion' => 'modificar', 'mensaje' => 'Atleta modificado exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                DUPLICATE_CEDULA => 'La cedula ingresada ya pertenece a un atleta registrado.',
                DUPLICATE_PHONE  => 'El telefono ingresado ya pertenece a un atleta registrado.',
                INVALID_ID       => 'La categoria ingresada no existe en los registros del club.',
                INVALID_ID . '0' => 'La posicion ingresada no existe en los registros del club.',
                INVALID_ID . '1' => 'El representante ingresado no existe en los registros del club.',
                DB_CONNECTION    => 'Ocurrio un error al conectarse con la base de datos.',
                default          => 'Ocurrió un error inesperado en la modificación.'
            };

            $mensaje_error = "Falló al modificar al Atleta: " . $identificador . " - " . $resultado['mensaje'];
            registrarBitacora($bitacoraObj, $id_modulo, $mensaje_error, $datos_previos_json, $datos_nuevos_json);
        }

        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Atletas', $e->getMessage(), 'Controlador_Modificar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function eliminar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        validar_requeridos(['id']);

        $datos = [
            'id' => $_POST['id'],
            'motivo_retiro' => $_POST['motivo_retiro'] ?? 'Retiro voluntario',
            'accion' => 'eliminar'
        ];

        // 1. Buscamos los datos PREVIOS del atleta antes de retirarlo
        $consultar_datos_previos = $obj->Buscar($_POST['id']);
        $atleta_previo = $consultar_datos_previos['datos'][0] ?? null;

        // 2. Preparamos los JSON para la bitácora (datos_nuevos va vacío)
        $datos_previos_json = json_encode($atleta_previo);
        $datos_nuevos_json = '';

        // 3. Generamos el identificador seguro usando la misma lógica excelente de modificar
        // Usamos el operador null safe o validación por si el atleta_previo viene vacío en un caso extremo
        $identificador = $atleta_previo['doc_identidad'] ?? 'R-' . ($atleta_previo['cedula_rep'] ?? 'Desconocido');

        // 4. Procesamos el retiro en el modelo
        $resultado = $obj->ProcesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {

            // Armamos un mensaje descriptivo con el nombre del atleta
            $nombre_completo = ($atleta_previo['p_nombre'] ?? '') . " " . ($atleta_previo['p_apellidos'] ?? '');
            $mensaje = "Retiró al Atleta: " . $identificador . " - " . trim($nombre_completo);

            registrarBitacora($bitacoraObj, $id_modulo, $mensaje, $datos_previos_json, $datos_nuevos_json);

            $resultado = array('accion' => 'eliminar', 'mensaje' => 'Atleta retirado exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {

            $resultado['mensaje'] = match ($resultado['codigo']) {
                INVALID_ID       => 'El atleta no existe.',
                ASSOCIATES       => 'No se puede retirar el atleta porque tiene un cargo pendiente por pagar.',
                ASSOCIATES . '1' => 'No se puede retirar el atleta porque tiene un equipamiento asignado.',
                DB_CONNECTION    => 'Ocurrió un error al conectarse con la base de datos.',
                default          => 'Ocurrió un error inesperado en el retiro.'
            };

            // Registramos el fallo en la bitácora
            $mensaje_error = "Falló al retirar al Atleta: " . $identificador . " - " . $resultado['mensaje'];
            registrarBitacora($bitacoraObj, $id_modulo, $mensaje_error, $datos_previos_json, $datos_nuevos_json);
        }

        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Atletas', $e->getMessage(), 'Controlador_Eliminar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function reinscribir($obj, $id_modulo, $bitacoraObj): void
{
    try {
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

        // 1. Buscamos los datos PREVIOS del atleta antes de reinscribirlo
        $consultar_datos_previos = $obj->Buscar($_POST['id']);
        $atleta_previo = $consultar_datos_previos['datos'][0] ?? null;
        $datos_previos_json = json_encode($atleta_previo);

        $resultado = $obj->ProcesarDatos($datos);

        $datos_nuevos_json = $resultado['datos_nuevos'] ?? '';

        $identificador = $atleta_previo['doc_identidad'] ?? 'R-' . ($atleta_previo['cedula_rep'] ?? 'Desconocido');
        $nombre_completo = trim(($atleta_previo['p_nombre'] ?? '') . ' ' . ($atleta_previo['p_apellidos'] ?? ''));

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {

            registrarBitacora($bitacoraObj, $id_modulo, "Re-inscribió al Atleta: " . $identificador . " - " . $nombre_completo, $datos_previos_json, $datos_nuevos_json);

            $resultado = array('accion' => 'reinscribir', 'mensaje' => 'Atleta re-inscrito exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                DB_CONNECTION    => 'Ocurrió un error al conectarse con la base de datos.',
                default          => 'Ocurrió un error inesperado al re-inscribir al atleta.'
            };
            $mensaje_error = "Falló al re-inscribir al Atleta: " . $identificador . " - " . $resultado['mensaje'];
            registrarBitacora($bitacoraObj, $id_modulo, $mensaje_error, $datos_previos_json, $datos_nuevos_json);
        }

        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Atletas', $e->getMessage(), 'Controlador_Reinscribir');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function generar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $datosFiltro = ['accion' => 'generar'];

        if (!empty($_POST['edad'])) $datosFiltro['edad'] = $_POST['edad'];
        if (!empty($_POST['nombre'])) $datosFiltro['nombre'] = $_POST['nombre'];
        if (!empty($_POST['apellido'])) $datosFiltro['apellido'] = $_POST['apellido'];
        if (!empty($_POST['categoria'])) $datosFiltro['categoria'] = $_POST['categoria'];
        if (!empty($_POST['posicion'])) $datosFiltro['posicion'] = $_POST['posicion'];
        if (!empty($_POST['genero']) && $_POST['genero'] != 'T') $datosFiltro['genero'] = $_POST['genero'];
        if (!empty($_POST['estatus']) && $_POST['estatus'] != 'T') $datosFiltro['estatus'] = $_POST['estatus'];
        if (!empty($_POST['doc_i'])) $datosFiltro['doc_identidad'] = $_POST['doc_i'];

        $respuesta = $obj->procesarDatos($datosFiltro);

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
        $formato = $_POST['formato'] ?? 'pdf';
        
        if ($formato === 'excel') {
            $reporte = $objG->generarExcel($nombreVista, $datos, 'Atletas');
        } else {
            $reporte = $objG->generarPDF($nombreVista, $datos, 'Atletas');
        }

        if (isset($reporte['accion']) && $reporte['accion'] === 'reporte') {
            // Se envían strings vacíos para datos_previos y datos_nuevos
            registrarBitacora($bitacoraObj, $id_modulo, "Generó reporte de atletas en formato " . strtoupper($formato) . ".", '', '');
        }

        echo json_encode($reporte);
    } catch (Exception $e) {
        logs('Atletas', $e->getMessage(), 'Controlador_Generar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function generarDocumentos($id_modulo, $bitacoraObj): void
{
    try {
        validar_requeridos(['id', 'tipo_documento']);
        $id_atleta = (int)$_POST['id'];
        $tipo_documento = $_POST['tipo_documento'];
        $anio_inicio = $_POST['anio_inicio'] ?? '';
        
        $fecha_inicio = '';
        if (!empty($anio_inicio)) {
            $fecha_inicio = $anio_inicio . '-01-01';
        }

        $modeloHistorial = new ModeloHistorial();
        $datosCurriculum = $modeloHistorial->consultarCurriculum($id_atleta, $fecha_inicio);

        if (empty($datosCurriculum) || empty($datosCurriculum['atleta'])) {
            echo json_encode(['accion' => 'error', 'mensaje' => 'No se encontró información para generar el documento de este atleta.']);
            exit();
        }

        $nombres = $datosCurriculum['atleta']['nombres'];
        $apellidos = $datosCurriculum['atleta']['apellidos'];
        $docIdentidad = $datosCurriculum['atleta']['doc_identidad'];
        $nombreArchivoRaw = $nombres . '_' . $apellidos . '_' . $docIdentidad;
        $nombreArchivo = str_replace(' ', '_', $nombreArchivoRaw);

        $resultado = [];
        if ($tipo_documento === 'curriculum') {
            $nombreVista = 'Curriculum';
            $resultado = \App\servicios\GenerarCurriculum::GenerarCu($nombreVista, $datosCurriculum, $nombreArchivo);
        } else if ($tipo_documento === 'ficha_alto_rendimiento') {
            require_once __DIR__ . '/../servicios/GenerarDocumentosWord.php';
            $resultado = \App\servicios\GenerarDocumentosWord::GenerarFichaDeportiva($datosCurriculum, $nombreArchivo, $anio_inicio);
        } else if ($tipo_documento === 'ficha_tecnica') {
            require_once __DIR__ . '/../servicios/GenerarDocumentosWord.php';
            $resultado = \App\servicios\GenerarDocumentosWord::GenerarFichaTecnica($datosCurriculum, $nombreArchivo);
        } else {
            echo json_encode(['accion' => 'error', 'mensaje' => 'Tipo de documento no válido.']);
            exit();
        }

        if (isset($resultado['accion']) && $resultado['accion'] === 'reporte') {
            $atletaNombre = $datosCurriculum['atleta']['nombres'] . " " . $datosCurriculum['atleta']['apellidos'];
            registrarBitacora($bitacoraObj, $id_modulo, "Generó documento ($tipo_documento) del atleta: " . $atletaNombre, '', '');
        }

        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Atletas', $e->getMessage(), 'Controlador_GenerarDocumentos');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function historial($obj): void
{
    try {
        validar_requeridos(['id']);
        $datos = [
            'id' => $_POST['id'],
            'accion' => 'historial'
        ];
        $resultado = $obj->procesarDatos($datos);
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Atletas', $e->getMessage(), 'Controlador_Historial');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}
