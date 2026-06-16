<?php
function procesarPermisos(int $id_modulo, $bitacora = null, bool $soloValidar = false): array
{
    $nivelUsuario = $_SESSION['nivel_rol'] ?? 99;

    if ($nivelUsuario === 1) {
        return [
            'ingresar'  => true,
            'registrar' => true,
            'modificar' => true,
            'eliminar'  => true,
            'reporte'   => true,
            'otros'     => true
        ];
    }

    $modulosProhibidos = [_MD_USUARIOS_, _MD_ROLES_, _MD_BITACORA_];
    if ($nivelUsuario === 2 && in_array($id_modulo, $modulosProhibidos)) {
        if ($soloValidar) return []; 
        
        $_SESSION['alerta'] = [
            'icono'   => 'error',
            'titulo'  => 'Acceso denegado',
            'mensaje' => 'No tienes permisos asignados para este módulo.'
        ];
        header("Location:"."Principal");
        exit();
    }

    if ($nivelUsuario === 2) {

        return [
            'ingresar'  => true,
            'registrar' => true,
            'modificar' => true,
            'eliminar'  => true,
            'reporte'   => true,
            'otros'     => true
        ];
    }

    if (isset($_SESSION['permisos'][$id_modulo])) {
        $p = $_SESSION['permisos'][$id_modulo];

        if (!isset($p['ingresar']) || !$p['ingresar']) {
            if ($soloValidar) return [];
            
            $_SESSION['alerta'] = [
                'icono'   => 'error',
                'titulo'  => 'Acceso denegado',
                'mensaje' => 'No tienes permitido ingresar a este módulo.'
            ];
            header("Location:"."Principal");
            exit();
        }

        $permisos = [
            'ingresar'  => (bool)$p['ingresar'],
            'registrar' => (isset($p['registrar']) && $p['registrar']),
            'modificar' => (isset($p['modificar']) && $p['modificar']),
            'eliminar'  => (isset($p['eliminar']) && $p['eliminar']),
            'reporte'   => (isset($p['reporte']) && $p['reporte']),
            'otros'     => (isset($p['otros']) && $p['otros'])
        ];

        return $permisos;
    } else {
        if ($soloValidar) return [];
        
        $_SESSION['alerta'] = [
            'icono'   => 'error',
            'titulo'  => 'Acceso denegado',
            'mensaje' => 'No tienes permisos asignados para este módulo.'
        ];
        header("Location:"."Principal");
        exit();
    }
}

function cargarVista(string $pagina,array $datos = []): void
{   
    
    $archivoVista = sprintf(__DIR__ . '/../vista/%s.php', $pagina);

    if (is_file($archivoVista)) {
        $_SESSION['token'] = bin2hex(random_bytes(32));
        extract($datos);
        require($archivoVista);
    } else {
        require(__DIR__ . '/../vista/complementos/404.php');
        exit();
    }
}


function comprobarAjax(): bool
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Registra una acción en la bitácora.
 */
function registrarBitacora($bitacora, int $id_modulo, string $mensaje): void
{
    if (isset($_SESSION['id']) && $bitacora !== null) {
        $bitacora->RegistrarAccion($id_modulo, $mensaje, $_SESSION['id']);
    }
}

/**
 * Valida un arreglo de datos simples contra expresiones regulares.
 */
function validar_datos(array $data): void
{
    foreach ($data as $campo => $valor) {
        if (!isset($_POST[$campo]) || empty(trim($_POST[$campo]))) {
            throw new Exception("El campo $campo es obligatorio.");
        }
    }
    foreach ($data as $campo => $valor) {
        if (isset($valor['regla'])) {
            if (!preg_match($valor['regla'], $_POST[$campo])) {
                throw new Exception($valor['mensaje']);
            }
        }
    }
}

// Función para usar en el Controlador
function validar_requeridos(array $campos): void
{
    foreach ($campos as $campo) {
        if (!isset($_POST[$campo]) || trim($_POST[$campo]) === '') {
            throw new Exception("El campo $campo es obligatorio.");
        }
    }
}

/**
 * Valida datos que vienen en formato de arreglo desde el formulario.
 */
function validarArrays(array $data): void
{
    foreach ($data as $campo => $valor) {
        // 1. Verificar si el campo existe en el POST
        if (!isset($_POST[$campo])) {
            throw new Exception("El campo $campo es obligatorio.");
        }

        $datosRecibidos = $_POST[$campo];

        // 2. Si es un array, validamos cada elemento interno
        if (is_array($datosRecibidos)) {
            foreach ($datosRecibidos as $indice => $contenido) {
                if (isset($valor['regla'])) {
                    if (!preg_match($valor['regla'], (string)$contenido)) {
                        throw new Exception("Error en $campo: " . $valor['mensaje']);
                    }
                }
            }
        } else {
            // 3. Si por error no es un array, lo validamos como campo simple
            if (isset($valor['regla']) && !preg_match($valor['regla'], (string)$datosRecibidos)) {
                throw new Exception($valor['mensaje']);
            }
        }
    }
}

function subirImagen ($archivo, $prefijo, $cedula, $carpeta_destino, $foto_actual = 'default.png') {
    // Si no hay archivo o viene con error, devolvemos el nombre de la foto actual
    if (!isset($archivo) || $archivo['error'] !== UPLOAD_ERR_OK) {
        return $foto_actual;
    }

    $file_tmp  = $archivo['tmp_name'];
    $file_name = $archivo['name'];
    $file_size = $archivo['size'];
    $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    // 1. Validación de Tipo
    $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($file_ext, $extensiones_permitidas)) {
        throw new Exception('Extensión no permitida (solo JPG, PNG, WEBP).');
    }

    // 2. Validación de Tamaño (2MB)
    if ($file_size > 5 * 1024 * 1024) {
        throw new Exception('La imagen supera el límite de 2MB.');
    }

    // 3. Definir Rutas
    $ruta_absoluta = __DIR__ . '/../../public/img/' . $carpeta_destino . '/';
    
    // Crear carpeta si no existe
    if (!is_dir($ruta_absoluta)) {
        mkdir($ruta_absoluta, 0777, true);
    }

    // 4. Limpieza: Borrar foto anterior si no es la default
    if ($foto_actual !== 'default.png' && !empty($foto_actual)) {
        $ruta_vieja = $ruta_absoluta . $foto_actual;
        if (file_exists($ruta_vieja)) {
            unlink($ruta_vieja);
        }
    }

    // 5. Generar nuevo nombre y mover
    $nombre = $prefijo . "_" . $cedula . "_" . time() . "." . $file_ext;
    $ruta_final = $ruta_absoluta . $nombre;

    if (move_uploaded_file($file_tmp, $ruta_final)) {
        return $nombre;
    }

    throw new Exception('Error al subir la imagen al servidor.');
}

function logs(string $modulo, string $mensaje, string $origen = ''): void
{
    // Ruta: /tu_proyecto/logs/modulo_nombre.log
    $directorio = __DIR__ . '/../../logs/';
    $archivo = $directorio . "log_" . strtolower($modulo) . ".log";

    // Crear carpeta si no existe
    if (!is_dir($directorio)) {
        mkdir($directorio, 0777, true);
    }

    $fecha = date('Y-m-d H:i:s');
    $log = "[$fecha] [$origen] ERROR: $mensaje" . PHP_EOL;

    // Escribir al final del archivo (FILE_APPEND)
    file_put_contents($archivo, $log, FILE_APPEND);
}
