<?php
function procesarPermisos(int $id_modulo, $bitacora = null): array
{
    $nivelUsuario = $_SESSION['nivel_rol'] ?? 99;

    $modulosProhibidos = [_MD_USUARIOS_, _MD_ROLES_, _MD_BITACORA_];

    if ($nivelUsuario === 2 && in_array($id_modulo, $modulosProhibidos)) {
        $_SESSION['alerta'] = [
            'icono'   => 'error',
            'titulo'  => 'Acceso denegado',
            'mensaje' => 'No tienes permisos asignados para este módulo.'
        ];
        header("Location:" . _URL_ . "Principal");
        exit();
    }

    if ($nivelUsuario === 1 || $nivelUsuario === 2) {
        return [
            'incluir'   => true,
            'modificar' => true,
            'eliminar'  => true,
            'reporte'   => true,
            'otros'     => true
        ];
    }

    if (isset($_SESSION['permisos'][$id_modulo])) {
        $p = $_SESSION['permisos'][$id_modulo];

        $permisos = [
            'incluir'   => $p['incluir'] == 1,
            'modificar' => $p['modificar'] == 1,
            'eliminar'  => $p['eliminar'] == 1,
            'reporte'   => $p['reporte'] == 1,
            'otros'     => $p['otros'] == 1
        ];

        if (!comprobarAjax() && $bitacora !== null) {
            registrarBitacora($bitacora, $id_modulo, "Accedió al módulo");
        }

        return $permisos;
    } else {
        $_SESSION['alerta'] = [
            'icono'   => 'error',
            'titulo'  => 'Acceso denegado',
            'mensaje' => 'No tienes permisos asignados para este módulo.'
        ];
        header("Location:" . _URL_ . "Principal");
        exit();
    }
}

function cargarVista(string $pagina): void
{
    $archivoVista = sprintf(__DIR__ . '/../vista/%s.php', $pagina);

    if (is_file($archivoVista)) {
        // Generar un token de seguridad para la sesión
        $_SESSION['token'] = bin2hex(random_bytes(32));
        require_once($archivoVista);
    } else {
        require_once(__DIR__ . '/../vista/complementos/404.php');
        exit();
    }
}

/**
 * Comprueba si la solicitud es AJAX.
 */
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
