<?php

namespace App\modelo;

use Exception;
use PDO;

class ModeloRespaldo extends Conexion
{
    /** Tipos de respaldo almacenados en el campo `tipo` de la tabla respaldos */
    public const TIPO_SISTEMA   = 1;
    public const TIPO_SEGURIDAD = 2;

    private $mysqlDumpPath;
    private $mysqlCliPath;
    private $esWindows;
    private $rutaSegura;

    public function __construct()
    {
        parent::__construct();
        $this->campoWhitelist = [];
        $this->llavePrimaria = '';

        $this->esWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        // 1. Detectar automáticamente la ruta de mysqldump y mysql según la PC / Servidor
        if ($this->esWindows) {
            $this->mysqlDumpPath = $this->detectarBinarioWindows('mysqldump.exe');
            $this->mysqlCliPath  = $this->detectarBinarioWindows('mysql.exe');
        } else {
            $this->mysqlDumpPath = $this->detectarBinarioLinux(['mysqldump', 'mariadb-dump']);
            $this->mysqlCliPath  = $this->detectarBinarioLinux(['mysql', 'mariadb']);
        }

        // 2. Definimos la ruta segura para guardar los archivos
        $this->rutaSegura = realpath(__DIR__ . '/../../') . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR;

        if (!is_dir($this->rutaSegura)) {
            @mkdir($this->rutaSegura, 0775, true);
        }
    }

    use VerificarExistencia;

    public function ProcesarDatos(array $datos): array
    {
        if (empty($datos)) {
            throw new Exception('No se proporcionaron datos para procesar.');
        }

        $accion = $datos['accion'] ?? null;
        $filtro = $datos['filtro'] ?? '';

        return match ($accion) {
            'consultar' => $this->ConsultarBackups($filtro),
            'generar'   => $this->GenerarBackup((int)($datos['tipo'] ?? self::TIPO_SISTEMA)),
            'restaurar' => $this->RestaurarBackup($datos['archivo'] ?? ''),
            'eliminar'  => $this->EliminarBackup($datos['archivo'] ?? ''),
            default     => throw new Exception('La acción no es válida.')
        };
    }

    private function ConsultarBackups(string $filtro = ''): array
    {
        try {
            // La tabla respaldos vive en la BD de seguridad: se usa su propia conexión
            // (en el VPS el nombre/usuario de esa BD no es necesariamente "bds2").
            $sql = "SELECT r.nombre_archivo as nombre, 
                           DATE_FORMAT(r.fecha_creacion, '%d/%m/%Y %h:%i %p') as fecha, 
                           r.peso as tamano,
                           r.estatus, 
                           r.tipo,
                           CASE r.tipo WHEN 1 THEN 'Sistema' WHEN 2 THEN 'Seguridad' ELSE 'Sistema' END as tipo_nombre,
                           u.nombreUsuario, 
                           u.apellidoUsuario
                    FROM respaldos r
                    INNER JOIN usuarios u ON r.id_usuario = u.idUsuario
                    WHERE 1=1";

            if (!empty($filtro)) {
                $sql .= " AND (
                            r.nombre_archivo LIKE ? 
                            OR DATE_FORMAT(r.fecha_creacion, '%d/%m/%Y') LIKE ?
                            OR u.nombreUsuario LIKE ?
                            OR u.apellidoUsuario LIKE ?
                            OR (CASE r.tipo WHEN 1 THEN 'Sistema' WHEN 2 THEN 'Seguridad' ELSE 'Sistema' END) LIKE ?
                          )";
            }

            $sql .= " ORDER BY r.fecha_creacion DESC";

            $conex = $this->conexSG();
            $stmt = $conex->prepare($sql);

            if (!empty($filtro)) {
                $filtroVal = "%$filtro%";
                for ($i = 1; $i <= 5; $i++) {
                    $stmt->bindValue($i, $filtroVal, PDO::PARAM_STR);
                }
            }

            $stmt->execute();
            $lista = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Le añadimos el nombre del usuario al array que va a la vista
            foreach ($lista as &$item) {
                $item['creador'] = $item['nombreUsuario'] . ' ' . $item['apellidoUsuario'];
            }
            unset($item);

            return ['accion' => 'consultar', 'datos' => $lista];
        } catch (Exception $e) {
            logs('Respaldo', $e->getMessage(), 'Modelo_Consultar');
            return ['accion' => 'error', 'codigo' => $e->getMessage()];
        }
    }

    private function GenerarBackup(int $tipo = self::TIPO_SISTEMA): array
    {
        $archivoCredenciales = null;
        $rutaCompleta = null;
        $registrado = false;

        try {
            if (!in_array($tipo, [self::TIPO_SISTEMA, self::TIPO_SEGURIDAD], true)) {
                throw new Exception('Tipo de base de datos no válido.');
            }

            $this->verificarEntorno($this->mysqlDumpPath, 'mysqldump');
            @set_time_limit(0);

            $credenciales = $this->obtenerCredenciales($tipo);
            $dbTarget = $credenciales['nombre'];

            $fecha = date('Y-m-d_H-i-s');
            $nombreArchivo = "backup_{$dbTarget}_{$fecha}.sql";
            $rutaCompleta = $this->rutaSegura . $nombreArchivo;

            // Las credenciales van en un archivo temporal (no en la línea de comandos):
            //  - Evita problemas con caracteres especiales del password en el VPS ($, !, &, comillas...)
            //  - Evita el warning "Using a password on the command line" que se colaba en el .sql
            $archivoCredenciales = $this->crearArchivoCredenciales($credenciales);

            $opciones = [
                '--defaults-extra-file=' . $this->q($archivoCredenciales), // Debe ser la primera opción
                '--default-character-set=utf8mb4',
                '--single-transaction',   // Respaldo consistente sin necesitar LOCK TABLES
                '--no-tablespaces',       // Evita exigir el privilegio PROCESS (MySQL 8 en VPS)
                '--routines',
                '--triggers',
                '--add-drop-table',
            ];

            // MySQL (no MariaDB) agrega SET @@GLOBAL.GTID_PURGED que exige SUPER al restaurar
            if ($this->esMysqlOficial($this->mysqlDumpPath)) {
                $opciones[] = '--set-gtid-purged=OFF';
            }

            $opciones[] = '--result-file=' . $this->q($rutaCompleta);

            $comando = $this->q($this->mysqlDumpPath) . ' ' . implode(' ', $opciones) . ' ' . $this->q($dbTarget) . ' 2>&1';

            $output = [];
            $resultado = null;
            exec($comando, $output, $resultado);

            if ($resultado !== 0 || !file_exists($rutaCompleta) || filesize($rutaCompleta) === 0) {
                $errorConsola = $this->limpiarErrorConsola(implode("\n", $output));
                logs('Respaldo', "Fallo mysqldump ({$dbTarget}) código {$resultado}: {$errorConsola}", 'Modelo_Generar');
                throw new Exception("Error de MySQL al respaldar {$dbTarget}: " . ($errorConsola ?: 'No se generó el archivo.'));
            }

            // Quitamos los DEFINER para que la restauración no exija privilegios SUPER en el VPS
            $this->quitarDefiners($rutaCompleta);

            // Calculamos el peso y guardamos el registro en la BD de seguridad
            clearstatcache(true, $rutaCompleta);
            $pesoCalculado = round(filesize($rutaCompleta) / 1024, 2) . ' KB';
            $idUsuario = $_SESSION['id'];

            $conex = $this->conexSG();
            $sql = "INSERT INTO respaldos (nombre_archivo, peso, fecha_creacion, id_usuario, estatus, tipo) VALUES (?, ?, NOW(), ?, 1, ?)";
            $stmt = $conex->prepare($sql);
            $stmt->execute([$nombreArchivo, $pesoCalculado, $idUsuario, $tipo]);
            $registrado = true;

            return ['accion' => 'exito', 'nombre' => $nombreArchivo, 'tipo' => $tipo];
        } catch (Exception $e) {
            // Si falló algo, no dejamos archivos huérfanos sin registro
            if (!$registrado && $rutaCompleta && file_exists($rutaCompleta)) {
                @unlink($rutaCompleta);
            }
            logs('Respaldo', $e->getMessage(), 'Modelo_Generar');
            return ['accion' => 'error', 'codigo' => $e->getMessage()];
        } finally {
            if ($archivoCredenciales && file_exists($archivoCredenciales)) {
                @unlink($archivoCredenciales);
            }
        }
    }

    private function RestaurarBackup(string $nombreArchivo): array
    {
        $archivoCredenciales = null;

        try {
            // basename elimina intentos de retroceder carpetas (Path Traversal)
            $nombreLimpio = basename($nombreArchivo);
            $rutaCompleta = $this->rutaSegura . $nombreLimpio;

            if (empty($nombreLimpio) || !preg_match('/\.sql$/i', $nombreLimpio) || !file_exists($rutaCompleta)) {
                throw new Exception('El archivo de respaldo no existe en el servidor.');
            }

            $this->verificarEntorno($this->mysqlCliPath, 'mysql');
            @set_time_limit(0);

            // Determinamos a qué BD pertenece el respaldo (campo tipo) ANTES de restaurar,
            // ya que si se restaura la BD de seguridad la tabla respaldos se sobrescribe.
            $tipo = $this->obtenerTipoRespaldo($nombreLimpio);
            $credenciales = $this->obtenerCredenciales($tipo);
            $targetDb = $credenciales['nombre'];

            $archivoCredenciales = $this->crearArchivoCredenciales($credenciales);

            $comando = $this->q($this->mysqlCliPath)
                . ' --defaults-extra-file=' . $this->q($archivoCredenciales)
                . ' --default-character-set=utf8mb4 '
                . $this->q($targetDb)
                . ' < ' . $this->q($rutaCompleta) . ' 2>&1';

            $output = [];
            $resultado = null;
            exec($comando, $output, $resultado);

            if ($resultado !== 0) {
                $errorConsola = $this->limpiarErrorConsola(implode("\n", $output));
                logs('Respaldo', "Fallo restauración ({$targetDb}) código {$resultado}: {$errorConsola}", 'Modelo_Restaurar');
                throw new Exception("Error al restaurar {$targetDb}: " . ($errorConsola ?: 'El cliente MySQL devolvió un error.'));
            }

            return ['accion' => 'exito', 'tipo' => $tipo];
        } catch (Exception $e) {
            logs('Respaldo', $e->getMessage(), 'Modelo_Restaurar');
            return ['accion' => 'error', 'codigo' => $e->getMessage()];
        } finally {
            if ($archivoCredenciales && file_exists($archivoCredenciales)) {
                @unlink($archivoCredenciales);
            }
        }
    }

    private function EliminarBackup(string $nombreArchivo): array
    {
        try {
            $nombreLimpio = basename($nombreArchivo);
            $rutaCompleta = $this->rutaSegura . $nombreLimpio;

            // Borramos el archivo físico para no acumular basura
            if (!empty($nombreLimpio) && file_exists($rutaCompleta)) {
                unlink($rutaCompleta);
            }

            // Borrado lógico (Soft Delete): Cambiamos el estatus a 2 en la BD de seguridad
            $conex = $this->conexSG();
            $sql = "UPDATE respaldos SET estatus = 2 WHERE nombre_archivo = ?";
            $stmt = $conex->prepare($sql);
            $stmt->execute([$nombreLimpio]);

            return ['accion' => 'exito'];
        } catch (Exception $e) {
            logs('Respaldo', $e->getMessage(), 'Modelo_Eliminar');
            return ['accion' => 'error', 'codigo' => 'No se pudo eliminar el archivo.'];
        }
    }

    /* ================================================================== */
    /* HELPERS                                                            */
    /* ================================================================== */

    /**
     * Devuelve las credenciales (definidas en el .env) de la BD según el tipo.
     * 1 = BD del sistema (DB_*), 2 = BD de seguridad (DB_*_SG).
     */
    private function obtenerCredenciales(int $tipo): array
    {
        if ($tipo === self::TIPO_SEGURIDAD) {
            $cred = ['host' => _DB_HOST_SG_, 'nombre' => _DB_NAME_SG_, 'usuario' => _DB_USER_SG_, 'clave' => _DB_PASS_SG_];
        } else {
            $cred = ['host' => _DB_HOST_, 'nombre' => _DB_NAME_, 'usuario' => _DB_USER_, 'clave' => _DB_PASS_];
        }

        if (empty($cred['nombre']) || empty($cred['usuario'])) {
            throw new Exception('Las credenciales de la base de datos no están configuradas en el archivo .env.');
        }

        // Solo permitimos nombres de BD válidos (se usan en la línea de comandos)
        if (!preg_match('/^[A-Za-z0-9_\-$]+$/', $cred['nombre'])) {
            throw new Exception('El nombre de la base de datos contiene caracteres no permitidos.');
        }

        return $cred;
    }

    /**
     * Obtiene el tipo (1 sistema / 2 seguridad) de un respaldo desde la tabla respaldos.
     * Para registros antiguos sin tipo, se deduce por el nombre del archivo.
     */
    private function obtenerTipoRespaldo(string $nombreArchivo): int
    {
        try {
            $stmt = $this->conexSG()->prepare("SELECT tipo FROM respaldos WHERE nombre_archivo = ? LIMIT 1");
            $stmt->execute([$nombreArchivo]);
            $tipo = (int)$stmt->fetchColumn();
            if (in_array($tipo, [self::TIPO_SISTEMA, self::TIPO_SEGURIDAD], true)) {
                return $tipo;
            }
        } catch (Exception $e) {
            logs('Respaldo', $e->getMessage(), 'Modelo_ObtenerTipo');
        }

        if (preg_match('/^backup_(.+)_(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})\.sql$/', $nombreArchivo, $m)) {
            if ($m[1] === _DB_NAME_SG_ || $m[1] === 'bds2') {
                return self::TIPO_SEGURIDAD;
            }
        }
        return self::TIPO_SISTEMA;
    }

    /**
     * Crea un archivo temporal de opciones [client] con usuario/clave/host.
     * Se pasa a mysqldump/mysql con --defaults-extra-file.
     */
    private function crearArchivoCredenciales(array $cred): string
    {
        $host = $cred['host'] ?: 'localhost';
        $puerto = null;

        // Soporta "host:puerto" en el .env
        if (preg_match('/^([^:]+):(\d+)$/', $host, $m)) {
            $host = $m[1];
            $puerto = $m[2];
        }

        // Encierra el valor en comillas para soportar caracteres especiales (#, ;, espacios, etc.)
        $esc = function ($v) {
            $v = str_replace('\\', '\\\\', (string)$v);
            if (strpos($v, '"') === false) return '"' . $v . '"';
            if (strpos($v, "'") === false) return "'" . $v . "'";
            return '"' . str_replace('"', '\\"', $v) . '"';
        };

        $contenido = "[client]\n"
            . "user=" . $esc($cred['usuario']) . "\n"
            . "password=" . $esc($cred['clave']) . "\n"
            . "host=" . $esc($host) . "\n";

        if ($puerto) {
            $contenido .= "port={$puerto}\n";
        }

        // Preferimos la carpeta de backups (protegida); si no, el temp del sistema
        $dir = is_writable($this->rutaSegura) ? $this->rutaSegura : sys_get_temp_dir() . DIRECTORY_SEPARATOR;
        $ruta = $dir . '.mycnf_' . bin2hex(random_bytes(8)) . '.cnf';

        if (file_put_contents($ruta, $contenido) === false) {
            throw new Exception('No se pudo crear el archivo temporal de credenciales.');
        }
        @chmod($ruta, 0600);

        return $ruta;
    }

    /** Verifica que el servidor permita ejecutar el respaldo */
    private function verificarEntorno(string $binario, string $nombre): void
    {
        $deshabilitadas = array_map('trim', explode(',', (string)ini_get('disable_functions')));
        if (!function_exists('exec') || in_array('exec', $deshabilitadas, true)) {
            throw new Exception('La función exec() está deshabilitada en PHP del servidor (php.ini > disable_functions).');
        }

        if (!is_dir($this->rutaSegura) || !is_writable($this->rutaSegura)) {
            throw new Exception('La carpeta "backups" no existe o no tiene permisos de escritura para el usuario del servidor web.');
        }

        // En Linux, si no se encontró la ruta absoluta del binario, avisamos claramente
        if (!$this->esWindows && $binario === '') {
            throw new Exception("No se encontró el programa {$nombre} en el servidor. Instale el cliente de MySQL/MariaDB (ej: apt install mysql-client o mariadb-client).");
        }
    }

    /** Escapa un argumento para la consola (Windows o Linux) */
    private function q(string $valor): string
    {
        if ($this->esWindows) {
            return '"' . str_replace('"', '""', $valor) . '"';
        }
        return escapeshellarg($valor);
    }

    /** Detecta si el binario es de MySQL oficial (no MariaDB) para usar opciones exclusivas */
    private function esMysqlOficial(string $binario): bool
    {
        $out = [];
        $code = null;
        @exec($this->q($binario) . ' --version 2>&1', $out, $code);
        $version = implode(' ', $out);
        return $code === 0 && stripos($version, 'mariadb') === false && stripos($version, 'mysql') !== false;
    }

    /** Elimina las cláusulas DEFINER del archivo para poder restaurarlo sin privilegios SUPER */
    private function quitarDefiners(string $ruta): void
    {
        $temporal = $ruta . '.tmp';
        $entrada = @fopen($ruta, 'r');
        $salida = @fopen($temporal, 'w');

        if (!$entrada || !$salida) {
            if ($entrada) fclose($entrada);
            if ($salida) fclose($salida);
            return; // Si no se puede procesar se deja el archivo original
        }

        while (($linea = fgets($entrada)) !== false) {
            if (stripos($linea, 'DEFINER') !== false) {
                $linea = preg_replace('/\sDEFINER\s*=\s*(`[^`]*`|\'[^\']*\'|[^\s@]+)@(`[^`]*`|\'[^\']*\'|[^\s*]+)/i', '', $linea);
            }
            fwrite($salida, $linea);
        }

        fclose($entrada);
        fclose($salida);

        if (!@rename($temporal, $ruta)) {
            @unlink($temporal);
        }
    }

    /** Limpia la salida de consola (quita warnings y asegura UTF-8 para json_encode) */
    private function limpiarErrorConsola(string $texto): string
    {
        $lineas = array_filter(explode("\n", $texto), function ($l) {
            $l = trim($l);
            return $l !== '' && stripos($l, '[Warning]') === false && stripos($l, 'Deprecated program name') === false;
        });
        $texto = trim(implode(' ', $lineas));
        return mb_convert_encoding($texto, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
    }

    /** Busca mysqldump/mysql en el servidor Linux (VPS) */
    private function detectarBinarioLinux(array $nombres): string
    {
        $rutas = ['/usr/bin/', '/usr/local/bin/', '/usr/local/mysql/bin/', '/opt/lampp/bin/', '/bin/'];

        foreach ($nombres as $nombre) {
            foreach ($rutas as $ruta) {
                if (@is_executable($ruta . $nombre)) {
                    return $ruta . $nombre;
                }
            }
        }

        // Último intento: preguntarle al shell
        if (function_exists('exec')) {
            foreach ($nombres as $nombre) {
                $out = [];
                @exec('command -v ' . escapeshellarg($nombre) . ' 2>/dev/null', $out);
                if (!empty($out[0]) && @is_executable(trim($out[0]))) {
                    return trim($out[0]);
                }
            }
        }

        return '';
    }

    /**
     * Detecta automáticamente la ruta de un binario MySQL en Windows (mysqldump.exe o mysql.exe).
     * Determina la ruta exacta consultando al servidor MySQL activo para evitar conflictos
     * entre XAMPP y Laragon.
     */
    private function detectarBinarioWindows(string $nombreBinario): string
    {
        $fallback = str_replace('.exe', '', $nombreBinario);
        
        try {
            // ── Prioridad 1: Preguntarle al propio servidor MySQL dónde está instalado ──
            $conex = $this->conex();
            $stmt = $conex->query("SELECT @@basedir as base");
            if ($stmt) {
                $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!empty($resultado['base'])) {
                    // Limpiamos la ruta y construimos la ruta al binario
                    $baseDir = rtrim($resultado['base'], '/\\');
                    $rutaExacta = $baseDir . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . $nombreBinario;
                    if (file_exists($rutaExacta)) {
                        return $rutaExacta;
                    }
                }
            }
        } catch (Exception $e) {
            // Continuar con el escaneo manual si falla
        }

        // ── Prioridad 2: Escaneo manual (Fallback) ──
        $unidades = ['C:', 'D:'];
        $esLaragon = stripos(__DIR__, 'laragon') !== false;

        if ($esLaragon) {
            $ruta = $this->buscarEnLaragon($unidades, $nombreBinario);
            if ($ruta) return $ruta;
            
            $ruta = $this->buscarEnXampp($unidades, $nombreBinario);
            if ($ruta) return $ruta;
        } else {
            $ruta = $this->buscarEnXampp($unidades, $nombreBinario);
            if ($ruta) return $ruta;
            
            $ruta = $this->buscarEnLaragon($unidades, $nombreBinario);
            if ($ruta) return $ruta;
        }

        return $fallback;
    }

    private function buscarEnXampp(array $unidades, string $nombreBinario): ?string
    {
        foreach ($unidades as $u) {
            $ruta = $u . '\\xampp\\mysql\\bin\\' . $nombreBinario;
            if (file_exists($ruta)) {
                return $ruta;
            }
        }
        return null;
    }

    private function buscarEnLaragon(array $unidades, string $nombreBinario): ?string
    {
        $carpetasBin = ['mysql', 'mariadb'];
        foreach ($unidades as $u) {
            foreach ($carpetasBin as $carpeta) {
                $basePath = $u . '\\laragon\\bin\\' . $carpeta;
                if (!is_dir($basePath)) {
                    continue;
                }
                $subdirs = glob($basePath . '\\*', GLOB_ONLYDIR);
                if (!empty($subdirs)) {
                    foreach ($subdirs as $dir) {
                        $candidato = $dir . '\\bin\\' . $nombreBinario;
                        if (file_exists($candidato)) {
                            return $candidato;
                        }
                    }
                }
            }
        }
        return null;
    }
}
