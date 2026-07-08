<?php

namespace App\modelo;

use Exception;
use PDO;

class ModeloRespaldo extends Conexion
{
    private $mysqlPath;
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
        $this->dbName = _DB_NAME_;
        $this->user   = _DB_USER_;
        $this->pass   = _DB_PASS_;
        
        // 1. Detectar automáticamente la ruta de mysqldump según la PC / Servidor
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Si es Windows probamos ambas unidades
            if (file_exists('C:\xampp\mysql\bin\mysqldump.exe')) {
                $this->mysqlPath = 'C:\xampp\mysql\bin\mysqldump.exe';
            } elseif (file_exists('D:\xampp\mysql\bin\mysqldump.exe')) {
                $this->mysqlPath = 'D:\xampp\mysql\bin\mysqldump.exe';
            } else {
                $this->mysqlPath = 'mysqldump'; 
            }
        } else {
            $this->mysqlPath = 'mysqldump'; 
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

        return match ($accion) {
            'consultar' => $this->ConsultarBackups(),
            'generar'   => $this->GenerarBackup(),
            'restaurar' => $this->RestaurarBackup($datos['archivo'] ?? ''),
            'eliminar'  => $this->EliminarBackup($datos['archivo'] ?? ''),
            default     => throw new Exception('La acción no es válida.')
        };
    }

    private function ConsultarBackups(): array
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
                    ORDER BY r.fecha_creacion DESC";
                   
            $conex = $this->conex();
            $stmt = $conex->prepare($sql);
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
            $comando = "\"{$this->mysqlPath}\" --user={$this->user} {$paramPassword} {$this->dbName} > \"{$rutaCompleta}\" 2>&1";
            
            $resultado = null;
            system($comando, $resultado);

            if ($resultado !== 0) {
                $errorConsola = file_exists($rutaCompleta) ? file_get_contents($rutaCompleta) : 'Error desconocido';
                if (file_exists($rutaCompleta)) unlink($rutaCompleta);
                throw new Exception("Error de MySQL: " . trim($errorConsola));
            }

            // Calculamos el peso y guardamos en bds2 con estatus = 1 y id_usuario
            $pesoCalculado = round(filesize($rutaCompleta) / 1024, 2) . ' KB';
            $idUsuario = $_SESSION['idUsuario'] ?? 1;
            
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

            $scriptSql = file_get_contents($rutaCompleta);
            
            // Validación de cabecera
            $cabecera = substr($scriptSql, 0, 250);
            if (stripos($cabecera, 'MariaDB dump') === false && stripos($cabecera, 'MySQL dump') === false) {
                throw new Exception('El archivo no tiene la firma de un respaldo válido.');
            }

            $conex = $this->conex();
            $conex->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
            $resultado = $conex->exec($scriptSql);

            if ($resultado === false) {
                throw new Exception('Error interno al ejecutar el script SQL.');
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
}