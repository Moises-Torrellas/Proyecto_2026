<?php

namespace App\modelo;

use App\modelo\ModeloBase;
use Exception;
use PDO;

class ModeloRespaldo extends ModeloBase
{
    private $mysqlPath;
    private $dbName = 'cannibalsbd';
    private $user = 'root';
    private $pass = '';
    private $rutaSegura;

    public function __construct()
    {
        parent::__construct();
        $this->campoWhitelist = [];
        $this->llavePrimaria = '';
        
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
               // Hacemos un INNER JOIN con usuarios para saber quién lo creó
              $sql = "SELECT r.nombre_archivo as nombre, 
                             DATE_FORMAT(r.fecha_creacion, '%d/%m/%Y %h:%i %p') as fecha, 
                             r.peso as tamano, 
                             u.nombreUsuario, 
                             u.apellidoUsuario
                      FROM bds.respaldos r
                      INNER JOIN bds.usuarios u ON r.idUsuario = u.idUsuario
                      ORDER BY r.fecha_creacion DESC";
                      
               $conex = $this->conex();
               $stmt = $conex->prepare($sql);
               $stmt->execute();
               $lista = $stmt->fetchAll(\PDO::FETCH_ASSOC);

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

                // Si se generó bien, calculamos el peso y lo guardamos en su tabla
                $pesoCalculado = round(filesize($rutaCompleta) / 1024, 2) . ' KB';
                $idUsuario = $_SESSION['idUsuario'] ?? 1; // Tomamos el usuario que inició sesión
                
                // Usamos $this->conex() asumiendo que el modelo base conecta a la BD de seguridad
                $conex = $this->conex();
                $sql = "INSERT INTO bds.respaldos (nombre_archivo, peso, fecha_creacion, idUsuario) VALUES (?, ?, NOW(), ?)";
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
            // basename elimina cualquier intento de retroceder carpetas (ej: ../../etc/passwd)
            $nombreLimpio = basename($nombreArchivo);
            $rutaCompleta = $this->rutaSegura . $nombreLimpio;

            if (empty($nombreLimpio) || !file_exists($rutaCompleta)) {
                throw new Exception('El archivo de respaldo no existe en el servidor.');
            }

            $scriptSql = file_get_contents($rutaCompleta);
            
            // Seguridad: Validación rápida de cabecera
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

            if (file_exists($rutaCompleta)) {
                unlink($rutaCompleta);
            }

            return ['accion' => 'exito'];
        } catch (Exception $e) {
            return ['accion' => 'error', 'codigo' => 'No se pudo eliminar el archivo.'];
        }
    }
}