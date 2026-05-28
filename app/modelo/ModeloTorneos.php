<?php

namespace App\modelo;

use App\modelo\ModeloBase;
use Exception;

class ModeloTorneos extends ModeloBase
{
    private $id;
    private $nombre;
    private $fecha_inicio;
    private $fecha_fin;
    private $ubicacion;
    private $estatus;

    public function __construct()
    {
        parent::__construct();
        $this->campoWhitelist = [
            'id' => 'id_torneo', 
            'nombre' => 'nombre'
        ];
        $this->llavePrimaria = 'id_torneo'; 
    }

    public function ProcesarDatos(array $datos): array
    {
        if (empty($datos)) {
            throw new Exception('No se proporcionaron datos para procesar.');
        }
        
        $this->id = $datos['id'] ?? null;
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

            $sentencia .= " ORDER BY id_torneo ASC";

            $stmt = $conex->prepare($sentencia);
            $stmt->execute($params);
            $datos = $stmt->fetchAll();

            return array('accion' => 'consultar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Torneos', $e->getMessage(), 'Modelo_Consultar');
            // Como en Representantes, en consultar el error va sin mensaje
            return array('accion' => 'error'); 
        } finally {
            $conex = NULL;
        }
    }

    private function Incluir(): array
    {
        try {
            $conex = $this->conex();
            
            // Usamos Exception en lugar de un return directo
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

            // Retorno estándar
            return array('accion' => 'exito');
        } catch (Exception $e) {
            logs('Torneos', $e->getMessage(), 'Modelo');
            // Capturamos la excepción y la mandamos en el formato 'codigo'
            return array('accion' => 'error', 'codigo' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }                                              

    private function Modificar(): array
    {
        try {
            $conex = $this->conex();
            
            if (!$this->verificarExistenciaPropia('nombre', $this->nombre, $this->id, 'torneos', NULL)) {
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
            WHERE id_torneo = :id_torneo";
            
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':fecha_inicio', $this->fecha_inicio);
            $stmt->bindParam(':fecha_fin', $this->fecha_fin);
            $stmt->bindParam(':ubicacion', $this->ubicacion);
            $stmt->bindParam(':estatus', $this->estatus);
            $stmt->bindParam(':id_torneo', $this->id);
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
            $sentencia = "SELECT * FROM torneos WHERE id_torneo = :id";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id', $this->id);
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

            if (!$this->verificarExistencia('id', $this->id, 'torneos', NULL)) {
                throw new Exception('El torneo no existe.');
            }
            
            if ($this->verificarExistencia('id', $this->id, 'equipos', NULL)) {
                throw new Exception('No se puede eliminar: el torneo tiene equipos o atletas asociados.');
            }

            $sentencia = "DELETE FROM torneos WHERE id_torneo = :id";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
            
            return array('accion' => 'exito');
        } catch (Exception $e) {
            logs('Torneos', $e->getMessage(), 'Modelo_Eliminar');
            return array('accion' => 'error', 'codigo' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }   
}