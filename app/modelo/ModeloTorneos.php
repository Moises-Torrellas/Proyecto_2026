<?php

namespace App\modelo;

use Exception;

class ModeloTorneos extends Conexion
{
    private $codigo_torneo; // Cambiado de $id
    private $nombre;
    private $fecha_inicio;
    private $fecha_fin;
    private $ubicacion;
    private $estatus;

    public function __construct()
    {
        parent::__construct();
        $this->campoWhitelist = [
            'codigo_torneo' => 'codigo_torneo', // Ajustado a la BD
            'nombre' => 'nombre'
        ];
        $this->llavePrimaria = 'codigo_torneo'; // Ajustado a la BD
    }

    public function ProcesarDatos(array $datos): array
    {
        if (empty($datos)) {
            throw new Exception('No se proporcionaron datos para procesar.');
        }
        
        $this->ValidarExpresiones($datos);
        
        // Asignamos usando la nueva clave
        $this->codigo_torneo = $datos['codigo_torneo'] ?? null; 
        $this->nombre = mb_strtoupper(trim($datos['nombre'] ?? ''), "UTF-8");
        $this->fecha_inicio = $datos['fecha_inicio'] ?? null;
        $this->fecha_fin = $datos['fecha_fin'] ?? null;
        $this->ubicacion = mb_convert_case(trim($datos['ubicacion'] ?? ''), MB_CASE_TITLE, "UTF-8");
        $this->estatus = $datos['estatus'] ?? null; 
        
        $accion = $datos['accion'] ?? null;
        
        return match ($accion) {
            'incluir'   => $this->Incluir(),
            'eliminar'  => $this->Eliminar(),
            'buscar'    => $this->Buscar(),
            'modificar' => $this->Modificar(),
            'consultar' => $this->Consultar(), 
            default => throw new Exception('La acción no es válida')
        };
    }

    public function Consultar(array $filtro = []): array
    {
        try {
            $conex = $this->conex();
            $params = []; 

            $sentencia = "SELECT * FROM torneos WHERE 1=1";

            if (!empty($filtro['filtro'])) {
                $p = "%" . $filtro['filtro'] . "%";
                $sentencia .= " AND nombre LIKE :f1";
                $params[':f1'] = $p;
            }

            if (!empty($this->nombre)) {    
                $sentencia .= " AND nombre LIKE :nombre";
                $params[':nombre'] = trim($this->nombre) . "%";
            }

            $sentencia .= " ORDER BY codigo_torneo ASC"; // Ajustado a la BD

            $stmt = $conex->prepare($sentencia);
            $stmt->execute($params);
            $datos = $stmt->fetchAll();

            return array('accion' => 'consultar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Torneos', $e->getMessage(), 'Modelo_Consultar');
            return array('accion' => 'error'); 
        } finally {
            $conex = NULL;
        }
    }

    private function Incluir(): array
    {
        try {
            $conex = $this->conex();
            
            if ($this->verificarExistencia('nombre', $this->nombre, 'torneos', NULL)) {
                throw new Exception('Ya existe un torneo registrado con este nombre.');
            }

            $sentencia = "INSERT INTO torneos (`nombre`, `fecha_inicio`, `fecha_fin`, `ubicacion`, `estatus`) VALUES (:nombre, :fecha_inicio, :fecha_fin, :ubicacion, :estatus)";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':fecha_inicio', $this->fecha_inicio);
            $stmt->bindParam(':fecha_fin', $this->fecha_fin);
            $stmt->bindParam(':ubicacion', $this->ubicacion);
            $stmt->bindParam(':estatus', $this->estatus);
            $stmt->execute();

            return array('accion' => 'exito');
        } catch (Exception $e) {
            logs('Torneos', $e->getMessage(), 'Modelo');
            return array('accion' => 'error', 'codigo' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }                                              

    private function Modificar(): array
    {
        try {
            $conex = $this->conex();
            
            if (!$this->verificarExistenciaPropia('nombre', $this->nombre, $this->codigo_torneo, 'torneos', NULL)) {
                if ($this->verificarExistencia('nombre', $this->nombre, 'torneos', NULL)) {
                    throw new Exception('Ya existe otro torneo registrado con este nombre.');
                }
            }
            
            $sentencia = "UPDATE torneos SET 
            nombre = :nombre, 
            fecha_inicio = :fecha_inicio, 
            fecha_fin = :fecha_fin, 
            ubicacion = :ubicacion, 
            estatus = :estatus 
            WHERE codigo_torneo = :codigo_torneo"; // Ajustado a la BD
            
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':fecha_inicio', $this->fecha_inicio);
            $stmt->bindParam(':fecha_fin', $this->fecha_fin);
            $stmt->bindParam(':ubicacion', $this->ubicacion);
            $stmt->bindParam(':estatus', $this->estatus);
            $stmt->bindParam(':codigo_torneo', $this->codigo_torneo); // Ajustado a la BD
            $stmt->execute();

            return array('accion' => 'exito');
        } catch (Exception $e) {
            logs('Torneos', $e->getMessage(), 'Modelo_Modificar');
            return array('accion' => 'error', 'codigo' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    function Buscar(): array
    {
        try {
            $conex = $this->conex();
            $sentencia = "SELECT * FROM torneos WHERE codigo_torneo = :codigo_torneo"; // Ajustado a la BD
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':codigo_torneo', $this->codigo_torneo); // Ajustado a la BD
            $stmt->execute();
            $datos = $stmt->fetchAll();
            
            return array('accion' => 'buscar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Torneos', $e->getMessage(), 'Modelo');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    private function Eliminar(): array
    {
        try {
            $conex = $this->conex();

            if (!$this->verificarExistencia('codigo_torneo', $this->codigo_torneo, 'torneos', NULL)) {
                throw new Exception('El torneo no existe.');
            }
            
            // Si la FK en la tabla equipos se llama distinto, debes ajustarla aquí. Asumo que es codigo_torneo también.
            if ($this->verificarExistencia('codigo_torneo', $this->codigo_torneo, 'equipos', NULL)) {
                throw new Exception('No se puede eliminar: el torneo tiene equipos o atletas asociados.');
            }

            $sentencia = "DELETE FROM torneos WHERE codigo_torneo = :codigo_torneo"; // Ajustado a la BD
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':codigo_torneo', $this->codigo_torneo); // Ajustado a la BD
            $stmt->execute();
            
            return array('accion' => 'exito');
        } catch (Exception $e) {
            logs('Torneos', $e->getMessage(), 'Modelo_Eliminar');
            return array('accion' => 'error', 'codigo' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }   

    private function ValidarExpresiones(array $datos): void
    {
        // Cambiado de 'id' a 'codigo_torneo'
        if (!empty($datos['codigo_torneo']) && !preg_match('/^[0-9]+$/', $datos['codigo_torneo'])) {
            throw new Exception('Código de torneo inválido.');
        }
        if (!empty($datos['nombre']) && !preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\-\s]{2,30}$/u', $datos['nombre'])) {
            throw new Exception('Nombre de torneo inválido.');
        }
        if (!empty($datos['fecha_inicio']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $datos['fecha_inicio'])) {
            throw new Exception('Fecha de inicio inválida.');
        }
        if (!empty($datos['fecha_fin']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $datos['fecha_fin'])) {
            throw new Exception('Fecha de fin inválida.');
        }
        if (!empty($datos['ubicacion']) && !preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s.,#-]{5,150}$/u', $datos['ubicacion'])) {
            throw new Exception('Ubicación inválida.');
        }
        if (!empty($datos['estatus']) && !preg_match('/^[0-9]$/', $datos['estatus'])) {
            throw new Exception('Estatus inválido.');
        }
        if (!empty($datos['fecha_inicio']) && !empty($datos['fecha_fin']) && strtotime($datos['fecha_inicio']) > strtotime($datos['fecha_fin'])) {
            throw new Exception('La fecha de inicio no puede ser mayor que la fecha de fin.');
        }
    }

    public function ConsultarProximos(): array
    {
        try {
            $conex = $this->conex();
            // Torneos que inicien en 1 o 2 días.
            $sentencia = "SELECT nombre, fecha_inicio FROM torneos WHERE estatus = 1 AND (fecha_inicio = CURDATE() + INTERVAL 1 DAY OR fecha_inicio = CURDATE() + INTERVAL 2 DAY)";
            $stmt = $conex->prepare($sentencia);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            logs('Torneos', $e->getMessage(), 'Modelo_ConsultarProximos');
            return [];
        } finally {
            $conex = NULL;
        }
    }
}