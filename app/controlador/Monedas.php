<?php

use App\modelo\ModeloMonedas;

// 1. Cargamos las funciones base
require_once __DIR__ . '/Base.php';

// 2. Configuración del módulo
$id_modulo = _MD_MONEDAS_;

// 3. Procesar permisos (Retorna el array de permisos)
$permisos = procesarPermisos($id_modulo, 'ingresar_moneda');

// 4. Lógica de despacho (Router interno)
$nombreClaseModelo = 'App\modelo\ModeloMonedas';

if (!class_exists($nombreClaseModelo)) {
    require_once(__DIR__ . '/../vista/complementos/404.php');
    exit();
}

$objModelo = new ModeloMonedas();

if (comprobarAjax() && !empty($_POST)) {
    manejarSolicitud($objModelo, $id_modulo, $bitacora, $permisos);
} else {
    registrarBitacora($bitacora, $id_modulo, 'Ingreso al Modulo');
    $respuesta = $objModelo->Consultar();
    $registro = $respuesta['datos'] ?? [];
    $variables = ['registro' => $registro, 'permisos' => $permisos];
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

        // Seguridad centralizada (Permisos corregidos)
        switch ($accion) {
            case 'consultar':
                if (empty($permisos['ingresar_moneda'])) throw new Exception('No tienes permisos para consultar Monedas.');
                consultar($obj, $permisos);
                break;
            case 'incluir':
                if (empty($permisos['registrar_moneda'])) throw new Exception('No tienes permisos para registrar Monedas.');
                incluir($obj, $id_modulo, $bitacoraObj);
                break;
            case 'buscar':
                if (empty($permisos['modificar_moneda'])) throw new Exception('No tienes permisos para modificar Monedas.');
                buscar($obj);
                break;
            case 'modificar':
                if (empty($permisos['modificar_moneda'])) throw new Exception('No tienes permisos para modificar Monedas.');
                modificar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'eliminar':
                if (empty($permisos['elimina_moneda'])) throw new Exception('No tienes permisos para eliminar Monedas.');
                eliminar($obj, $id_modulo, $bitacoraObj);
                break;
            case 'bloquear':
                if (empty($permisos['bloquear_moneda'])) throw new Exception('No tienes permisos para bloquear Monedas.');
                bloquear($obj, $id_modulo, $bitacoraObj);
                break;
            case 'select':
                if (empty($permisos['asignar_moneda'])) throw new Exception('No tienes permisos para selecionar la Moneda base.');
                select($obj, $id_modulo, $bitacoraObj);
                break;
            case 'generar':
                if (empty($permisos['generar_moneda'])) throw new Exception('No tienes permisos para generar reportes.');
                generar($obj, $id_modulo, $bitacoraObj);
                break;
            default:
                throw new Exception('Acción no permitida.');
        }
    } catch (Exception $e) {
        logs('Monedas', $e->getMessage(), 'Controlador_ManejarSolicitud');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function consultar($obj, $permisos): void
{
    $filtro['filtro'] = $_POST['filtro'] ?? '';
    $respuesta = $obj->Consultar($filtro);

    $registro = $respuesta['datos'] ?? [];
    $solo_lista = true; 

    include(__DIR__ . '/../vista/Monedas.php');
}

function buscar($obj): void
{
    try {
        $validaciones = ['id' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.']];
        validar_datos($validaciones);

        $datos = [
            'id' => $_POST['id'],
            'accion' => 'buscar'
        ];

        $resultado = $obj->procesarDatos($datos);
        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Monedas', $e->getMessage(), 'Controlador_Buscar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function incluir($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $validaciones = [
            'nombre'      => ['regla'   => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,30}$/', 'mensaje' => 'Nombre inválido (solo letras, entre 3 y 30 caracteres).'],
            'abreviatura' => ['regla'   => '/^[a-zA-Z]{2,4}$/', 'mensaje' => 'Abreviatura inválida (solo letras, entre 2 y 4 caracteres).'],
            'simbolo'     => ['regla'   => '/^[a-zA-ZñÑ\$€£]{1,5}$/u', 'mensaje' => 'Símbolo inválido (máximo 5 caracteres, ej: $, Bs, €).']
        ];

        validar_datos($validaciones);

        $datos = [
            'nombre'      => $_POST['nombre'],
            'abreviatura' => $_POST['abreviatura'],
            'simbolo'     => $_POST['simbolo']
        ];
        $datos['accion'] = 'incluir';

        $respuesta = $obj->ProcesarDatos($datos);

        if (isset($respuesta['accion']) && $respuesta['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Registró la moneda: " . $_POST['nombre']);
            $respuesta = array('accion' => 'incluir', 'mensaje' => 'Moneda registrada exitosamente.');
        } else if (isset($respuesta['accion']) && $respuesta['accion'] === 'error') {
            $respuesta['mensaje'] = match ($respuesta['codigo']) {
                DUPLICATE_NAME => 'Ya existe una moneda registrada con este nombre.',
                VALIDATION . '1'  => 'Ya existe una moneda registrada con esta abreviatura.',
                VALIDATION . '2'  => 'Ya existe una moneda registrada con este símbolo.',
                DB_CONNECTION      => 'Ocurrio un error al conectarse con la base de datos.',
                default          => 'Ocurrió un error inesperado en la modificacion.'
            };
        }
        echo json_encode($respuesta);
    } catch (Exception $e) {
        logs('Monedas', $e->getMessage(), 'Controlador_Incluir');
        echo json_encode(['accion' => 'error', 'mensaje' => 'Ocurrio un error inesperado al intentar registrar la moneda.']);
    }
}

function modificar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $validaciones = [
            'id'          => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.'],
            'nombre'      => ['regla'   => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,30}$/', 'mensaje' => 'Nombre inválido (solo letras, entre 3 y 30 caracteres).'],
            'abreviatura' => ['regla'   => '/^[a-zA-Z]{2,4}$/', 'mensaje' => 'Abreviatura inválida (solo letras, entre 2 y 4 caracteres).'],
            'simbolo'     => ['regla'   => '/^[a-zA-ZñÑ\$€£]{1,5}$/u', 'mensaje' => 'Símbolo inválido (máximo 5 caracteres, ej: $, Bs, €).']
        ];

        validar_datos($validaciones);

        $datos = [
            'id'          => $_POST['id'],
            'nombre'      => $_POST['nombre'],
            'abreviatura' => $_POST['abreviatura'],
            'simbolo'     => $_POST['simbolo']
        ];
        $datos['accion'] = 'modificar';

        $respuesta = $obj->ProcesarDatos($datos);

        if (isset($respuesta['accion']) && $respuesta['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Modifico la moneda: " . $_POST['nombre']);
            $respuesta = array('accion' => 'modificar', 'mensaje' => 'Moneda modificada exitosamente.');
        } else if (isset($respuesta['accion']) && $respuesta['accion'] === 'error') {
            $respuesta['mensaje'] = match ($respuesta['codigo']) {
                DUPLICATE_NAME => 'Ya existe una moneda registrada con este nombre.',
                VALIDATION . '1'  => 'Ya existe una moneda registrada con esta abreviatura.',
                VALIDATION . '2'  => 'Ya existe una moneda registrada con este símbolo.',
                INVALID_ID => 'la Moneda que intenta modificar ya no existe.',
                DB_CONNECTION      => 'Ocurrio un error al conectarse con la base de datos.',
                default          => 'Ocurrió un error inesperado en la modificacion.'
            };
        }
        echo json_encode($respuesta);
    } catch (Exception $e) {
        logs('Monedas', $e->getMessage(), 'Controlador_Incluir');
        echo json_encode(['accion' => 'error', 'mensaje' => 'Ocurrio un error inesperado al intentar modificar la moneda.']);
    }
}

function eliminar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        $validaciones = [
            'id'          => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.'],
        ];

        validar_datos($validaciones);

        $datos = [
            'id'          => $_POST['id']
        ];
        $datos['accion'] = 'eliminar';

        $respuesta = $obj->ProcesarDatos($datos);

        if (isset($respuesta['accion']) && $respuesta['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Elimino la moneda: " . $_POST['id']);
            $respuesta = array('accion' => 'eliminar', 'mensaje' => 'Moneda eliminada exitosamente.');
        } else if (isset($respuesta['accion']) && $respuesta['accion'] === 'error') {
            $respuesta['mensaje'] = match ($respuesta['codigo']) {
                ASSOCIATES => 'No se puede eliminar la moneda porque esta asociado a pagos.',
                ASSOCIATES . '2' => 'No se puede eliminar la moneda porque esta asociado a vueltos.',
                ASSOCIATES . '3' => 'No se puede eliminar la moneda porque esta asociado a tasa de cambio.',
                VALIDATION     => 'No se puede eliminar la moneda base del sistema.',
                VALIDATION . '2'  => 'No se puede eliminar. Deben existir al menos dos monedas en el sistema.',
                VALIDATION . '3'  => 'No se puede eliminar. Deben mantenerse al menos dos monedas activas en el sistema.',
                INVALID_ID => 'La moneda no existe.',
                DB_CONNECTION      => 'Ocurrio un error al conectarse con la base de datos.',
                default          => 'Ocurrió un error inesperado en la eliminacion.'
            };
        }
        echo json_encode($respuesta);
    } catch (Exception $e) {
        logs('Monedas', $e->getMessage(), 'Controlador_Incluir');
        echo json_encode(['accion' => 'error', 'mensaje' => 'Ocurrio un error inesperado al intentar modificar la moneda.']);
    }
}

function bloquear($obj, $id_modulo, $bitacoraObj): void
{
    try {
        validar_datos([
            'id' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.'],
            'bloqueo' => ['regla' => '/^[1-2]+$/', 'mensaje' => 'Error interno de bloqueo.']
        ]);

        $datos = [
            'id' => $_POST['id'],
            'bloqueo' => $_POST['bloqueo'],
            'accion' => 'bloquear'
        ];

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            $nuevoEstado = ($_POST['bloqueo'] == 1) ? 2 : 1;
            $mensajeExito = ($nuevoEstado == 2) ? "Moneda bloqueada exitosamente." : "Moneda desbloqueada exitosamente.";
            $mensajeBitacora = ($nuevoEstado == 2) ? "Bloqueo la moneda: " : "Desbloqueo la moneda: ";
            registrarBitacora($bitacoraObj, $id_modulo, $mensajeBitacora . $_POST['id']);
            $resultado = array('accion' => 'bloquear', 'mensaje' => $mensajeExito);
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                VALIDATION     => 'No se puede bloquear la moneda base del sistema.',
                VALIDATION . '2'  => 'No se puede bloquear. Deben mantenerse al menos dos monedas activas en el sistema.',
                INVALID_ID => 'La moneda no existe.',
                DB_CONNECTION      => 'Ocurrio un error al conectarse con la base de datos.',
                default    => 'No se pudo completar la operación de bloqueo.'
            };
        }

        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Monedas', $e->getMessage(), 'Controlador_Bloquear');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function select($obj, $id_modulo, $bitacoraObj): void
{
    try {
        validar_datos([
            'id' => ['regla' => '/^[0-9]+$/', 'mensaje' => 'Id inválido.']
        ]);

        $datos = [
            'id' => $_POST['id'],
            'accion' => 'select'
        ];

        $resultado = $obj->procesarDatos($datos);

        if (isset($resultado['accion']) && $resultado['accion'] === 'exito') {
            registrarBitacora($bitacoraObj, $id_modulo, "Selecciono la moneda: " . $_POST['id']);
            $resultado = array('accion' => 'select', 'mensaje' => 'Cambio de moneda exitosamente.');
        } else if (isset($resultado['accion']) && $resultado['accion'] === 'error') {
            $resultado['mensaje'] = match ($resultado['codigo']) {
                INVALID_ID => 'La moneda no existe.',
                DB_CONNECTION      => 'Ocurrio un error al conectarse con la base de datos.',
                default    => 'No se pudo completar la operación de cambio.'
            };
        }

        echo json_encode($resultado);
    } catch (Exception $e) {
        logs('Monedas', $e->getMessage(), 'Controlador_Bloquear');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}

function generar($obj, $id_modulo, $bitacoraObj): void
{
    try {
        // 1. Instanciamos la conexión y preparamos la consulta
        $conex = $obj->conex();
        $nombre = trim($_POST['nombre'] ?? '');
        
        $sql = "SELECT * FROM monedas WHERE 1=1";
        $params = [];

        if (!empty($nombre)) {
            $sql .= " AND nombre LIKE :nombre";
            $params[':nombre'] = "%" . $nombre . "%";
        }
        
        $sql .= " ORDER BY codigo_moneda ASC";
        
        $stmt = $conex->prepare($sql);
        $stmt->execute($params);
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Validación: Si no existe, no generamos PDF y retornamos error
        if (empty($datos)) {
            echo json_encode([
                'accion' => 'error', 
                'mensaje' => 'No se encontraron monedas con ese nombre. Verifique e intente nuevamente.'
            ]);
            return;
        }

        registrarBitacora($bitacoraObj, $id_modulo, "Generó reporte de Monedas");

        $fecha_reporte = date('d/m/Y h:i A');
        $usuario = $_SESSION['nombre_usuario'] ?? 'Administrador';
        
        $logo = __DIR__ . '/../../public/img/logo.png'; 
        $logo_footer = __DIR__ . '/../../public/img/logo_footer.png';

        // 3. Incluimos el archivo de la vista
        ob_start();
        include(__DIR__ . '/../vista/reportes/R_Monedas.php'); 
        $html = ob_get_clean();

        // 4. Inicializamos Dompdf y generamos el Base64
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
        logs('Monedas', $e->getMessage(), 'Controlador_Generar');
        echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
    }
}