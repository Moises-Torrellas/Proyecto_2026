<?php

namespace App\modelo;

use Exception;
use PDO;

class ModeloRespaldo extends Conexion
{
    private $mysqlDumpPath;
    private $mysqlCliPath;
    private $dbHost;
    private $dbName;
    private $user;
    private $pass;
    private $rutaSegura;

    public function __construct()
    {
        parent::__construct();
        $this->campoWhitelist = [];
        $this->llavePrimaria = '';
        
        // Asignamos las credenciales dinámicamente desde las constantes de configuración
        $this->dbHost = _DB_HOST_;
        $this->dbName = _DB_NAME_;
        $this->user   = _DB_USER_;
        $this->pass   = _DB_PASS_;
        
        // 1. Detectar automáticamente la ruta de mysqldump y mysql según la PC / Servidor
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->mysqlDumpPath = $this->detectarBinarioWindows('mysqldump.exe');
            $this->mysqlCliPath  = $this->detectarBinarioWindows('mysql.exe');
        } else {
            $this->mysqlDumpPath = 'mysqldump'; 
            $this->mysqlCliPath  = 'mysql'; 
        }

        // 2. Definimos la ruta segura para guardar los archivos
        $this->rutaSegura = realpath(__DIR__ . '/../../') . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR;
        
        if (!is_dir($this->rutaSegura)) {
            mkdir($this->rutaSegura, 0777, true);
        }
    }

    public function ProcesarDatos(array $datos): array
    {
        if (empty($datos)) {
            throw new Exception('No se proporcionaron datos para procesar.');
        }

        $accion = $datos['accion'] ?? null;

        $filtro = $datos['filtro'] ?? '';

        return match ($accion) {
            'consultar' => $this->ConsultarBackups($filtro),
            'generar'   => $this->GenerarBackup(),
            'restaurar' => $this->RestaurarBackup($datos['archivo'] ?? ''),
            'eliminar'  => $this->EliminarBackup($datos['archivo'] ?? ''),
            default     => throw new Exception('La acción no es válida.')
        };
    }

    private function ConsultarBackups(string $filtro = ''): array
    {
        try {
            $sql = "SELECT r.nombre_archivo as nombre, 
                           DATE_FORMAT(r.fecha_creacion, '%d/%m/%Y %h:%i %p') as fecha, 
                           r.peso as tamano,
                           r.estatus, 
                           u.nombreUsuario, 
                           u.apellidoUsuario
                    FROM bds2.respaldos r
                    INNER JOIN bds2.usuarios u ON r.id_usuario = u.idUsuario
                    WHERE 1=1";
            
            if (!empty($filtro)) {
                $sql .= " AND (
                            r.nombre_archivo LIKE ? 
                            OR DATE_FORMAT(r.fecha_creacion, '%d/%m/%Y') LIKE ?
                            OR u.nombreUsuario LIKE ?
                            OR u.apellidoUsuario LIKE ?
                          )";
            }

            $sql .= " ORDER BY r.fecha_creacion DESC";
                   
            $conex = $this->conex();
            $stmt = $conex->prepare($sql);
            
            if (!empty($filtro)) {
                $filtroVal = "%$filtro%";
                $stmt->bindValue(1, $filtroVal, PDO::PARAM_STR);
                $stmt->bindValue(2, $filtroVal, PDO::PARAM_STR);
                $stmt->bindValue(3, $filtroVal, PDO::PARAM_STR);
                $stmt->bindValue(4, $filtroVal, PDO::PARAM_STR);
            }
            
            $stmt->execute();
            $lista = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Le añadimos el nombre del usuario al array que va a JS
            foreach ($lista as &$item) {
                $item['creador'] = $item['nombreUsuario'] . ' ' . $item['apellidoUsuario'];
            }

            return ['accion' => 'consultar', 'datos' => $lista];
        } catch (Exception $e) {
            logs('Respaldo', $e->getMessage(), 'Modelo_Consultar');
            return ['accion' => 'error', 'codigo' => $e->getMessage()];
        }
    }

    private function GenerarBackup(): array
    {
        try {
            $fecha = date('Y-m-d_H-i-s');
            $nombreArchivo = "backup_{$this->dbName}_{$fecha}.sql";
            $rutaCompleta = $this->rutaSegura . $nombreArchivo;

            $paramPassword = !empty($this->pass) ? "--password=\"{$this->pass}\"" : "";
            $comando = "\"{$this->mysqlDumpPath}\" --host={$this->dbHost} --user={$this->user} {$paramPassword} {$this->dbName} > \"{$rutaCompleta}\" 2>&1";
            
            $output = [];
            $resultado = null;
            exec($comando, $output, $resultado);

            if ($resultado !== 0) {
                $errorConsola = file_exists($rutaCompleta) ? file_get_contents($rutaCompleta) : implode("\n", $output);
                if (file_exists($rutaCompleta)) unlink($rutaCompleta);
                
                // Aseguramos que el error sea UTF-8 válido para no romper json_encode
                $errorConsola = mb_convert_encoding($errorConsola, 'UTF-8', 'auto');
                
                logs('Respaldo', "Comando fallido: {$comando} | Error: " . trim($errorConsola), 'Modelo_Generar');
                throw new Exception("Error de MySQL: " . trim($errorConsola));
            }

            // Calculamos el peso y guardamos en bds2 con estatus = 1 y id_usuario
            $pesoCalculado = round(filesize($rutaCompleta) / 1024, 2) . ' KB';
            $idUsuario = $_SESSION['id'];
            
            $conex = $this->conex();
            $sql = "INSERT INTO bds2.respaldos (nombre_archivo, peso, fecha_creacion, id_usuario, estatus) VALUES (?, ?, NOW(), ?, 1)";
            $stmt = $conex->prepare($sql);
            $stmt->execute([$nombreArchivo, $pesoCalculado, $idUsuario]);

            return ['accion' => 'exito', 'nombre' => $nombreArchivo];
        } catch (Exception $e) {
            logs('Respaldo', $e->getMessage(), 'Modelo_Generar');
            return ['accion' => 'error', 'codigo' => $e->getMessage()];
        }
    }

    private function RestaurarBackup(string $nombreArchivo): array
    {
        try {
            // basename elimina intentos de retroceder carpetas (Path Traversal)
            $nombreLimpio = basename($nombreArchivo);
            $rutaCompleta = $this->rutaSegura . $nombreLimpio;

            if (empty($nombreLimpio) || !file_exists($rutaCompleta)) {
                throw new Exception('El archivo de respaldo no existe en el servidor.');
            }

            // Usaremos el cliente mysql vía consola
            $paramPassword = !empty($this->pass) ? "--password=\"{$this->pass}\"" : "";
            
            // Ejecutamos la restauración inyectando el script sql con <
            $comando = "\"{$this->mysqlCliPath}\" --host={$this->dbHost} --user={$this->user} {$paramPassword} {$this->dbName} < \"{$rutaCompleta}\" 2>&1";
            
            $output = [];
            $resultado = null;
            exec($comando, $output, $resultado);

            if ($resultado !== 0) {
                $errorConsola = implode("\n", $output);
                $errorConsola = mb_convert_encoding($errorConsola, 'UTF-8', 'auto');
                
                logs('Respaldo', "Comando fallido: {$comando} | Error: " . trim($errorConsola), 'Modelo_Restaurar');
                throw new Exception('Error de sintaxis o ejecución al restaurar usando el comando MySQL.');
            }

            return ['accion' => 'exito'];
        } catch (Exception $e) {
            logs('Respaldo', $e->getMessage(), 'Modelo_Restaurar');
            return ['accion' => 'error', 'codigo' => $e->getMessage()];
        } finally {
            $conex = NULL; 
        }
    }

    private function EliminarBackup(string $nombreArchivo): array
    {
        try {
            $nombreLimpio = basename($nombreArchivo);
            $rutaCompleta = $this->rutaSegura . $nombreLimpio;

            // Borramos el archivo físico para no acumular basura
            if (file_exists($rutaCompleta)) {
                unlink($rutaCompleta);
            }

            // Borrado lógico (Soft Delete): Cambiamos el estatus a 2 en bds2
            $conex = $this->conex();
            $sql = "UPDATE bds2.respaldos SET estatus = 2 WHERE nombre_archivo = ?";
            $stmt = $conex->prepare($sql);
            $stmt->execute([$nombreLimpio]);

            return ['accion' => 'exito'];
        } catch (Exception $e) {
            return ['accion' => 'error', 'codigo' => 'No se pudo eliminar el archivo.'];
        }
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
            // Esto resuelve el problema de si el usuario está usando Laragon o XAMPP
            // independientemente de en qué carpeta esté guardado el proyecto.
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
            // Si no podemos consultar la base de datos por alguna razón, continuamos con el escaneo manual
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