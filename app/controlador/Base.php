<?php
use App\modelo\ModeloAsignaciones;
use App\modelo\ModeloAtletas;
use App\modelo\ModeloEquipamientos;

/**
 * Procesa y valida los permisos del usuario según su nivel de rol.
 */
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

/**
 * Carga la vista e inyecta las cabeceras de seguridad estrictas exigidas por OWASP ZAP.
 */
function cargarVista(string $pagina, array $datos = []): void
{   
    // 1. Configuración de Cookies de Sesión Seguras (Mitiga SameSite y HttpOnly)
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_samesite', 'Lax');
    }

    // 2. Generar un token único por petición (Nonce) para mitigar alertas CSP inline
    $nonce = bin2hex(random_bytes(16));

    // 3. Inyección de Cabeceras HTTP estrictas (Baja alertas de CSP, Clickjacking y Sniffing)
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-$nonce'; style-src 'self' 'nonce-$nonce'; img-src 'self' data:; font-src 'self';");
    header("X-Frame-Options: DENY");
    header("X-Content-Type-Options: nosniff");
    header("X-XSS-Protection: 1; mode=block");

    $archivoVista = sprintf(__DIR__ . '/../vista/%s.php', $pagina);

    if (is_file($archivoVista)) {
        $_SESSION['token'] = bin2hex(random_bytes(32));
        
        // Compartimos el valor de $nonce con la vista HTML de forma automática
        $datos['nonce'] = $nonce; 
        
        extract($datos);
        require($archivoVista);
    } else {
        require(__DIR__ . '/../vista/complementos/404.php');
        exit();
    }
}

/**
 * Comprueba si la petición fue realizada mediante AJAX (XMLHttpRequest).
 */
function comprobarAjax(): bool
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Registra una acción en el módulo de bitácora del sistema.
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

/**
 * Valida datos complejos que vienen estructurados en formato de matriz o arreglo.
 */
function validarArrays(array $data): void
{
    foreach ($data as $campo => $valor) {
        if (!isset($_POST[$campo])) {
            throw new Exception("El campo $campo es obligatorio.");
        }

        $datosRecibidos = $_POST[$campo];

        if (is_array($datosRecibidos)) {
            foreach ($datosRecibidos as $indice => $contenido) {
                if (isset($valor['regla'])) {
                    if (!preg_match($valor['regla'], (string)$contenido)) {
                        throw new Exception("Error en $campo: " . $valor['mensaje']);
                    }
                }
            }
        } else {
            if (isset($valor['regla']) && !preg_match($valor['regla'], (string)$datosRecibidos)) {
                throw new Exception($valor['mensaje']);
            }
        }
    }
}

/**
 * Gestiona la subida segura de imágenes al servidor, validando extensión y peso.
 */
function subirImagen ($archivo, $prefijo, $cedula, $carpeta_destino, $foto_actual = 'default.png') {
    if (!isset($archivo) || $archivo['error'] !== UPLOAD_ERR_OK) {
        return $foto_actual;
    }

    $file_tmp  = $archivo['tmp_name'];
    $file_name = $archivo['name'];
    $file_size = $archivo['size'];
    $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($file_ext, $extensiones_permitidas)) {
        throw new Exception('Extensión no permitida (solo JPG, PNG, WEBP).');
    }

    if ($file_size > 5 * 1024 * 1024) {
        throw new Exception('La imagen supera el límite permitido.');
    }

    $ruta_absoluta = __DIR__ . '/../../public/img/' . $carpeta_destino . '/';
    
    if (!is_dir($ruta_absoluta)) {
        mkdir($ruta_absoluta, 0777, true);
    }

    if ($foto_actual !== 'default.png' && !empty($foto_actual)) {
        $ruta_vieja = $ruta_absoluta . $foto_actual;
        if (file_exists($ruta_vieja)) {
            unlink($ruta_vieja);
        }
    }

    $nombre = $prefijo . "_" . $cedula . "_" . time() . "." . $file_ext;
    $ruta_final = $ruta_absolute . $nombre;

    if (move_uploaded_file($file_tmp, $ruta_final)) {
        return $nombre;
    }

    throw new Exception('Error al subir la imagen al servidor.');
}

/**
 * Genera logs de errores persistentes en formato de texto plano para auditoría.
 */
function logs(string $modulo, string $mensaje, string $origen = ''): void
{
    $directorio = __DIR__ . '/../../logs/';
    $archivo = $directorio . "log_" . strtolower($modulo) . ".log";

    if (!is_dir($directorio)) {
        mkdir($directorio, 0777, true);
    }

    $fecha = date('Y-m-d H:i:s');
    $log = "[$fecha] [$origen] ERROR: $mensaje" . PHP_EOL;

    file_put_contents($archivo, $log, FILE_APPEND);
}