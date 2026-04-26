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
        // Agregamos 'nombre' al whitelist para evitar el error al Modificar
        $this->campoWhitelist = [
            'id' => 'id_torneo', // Corregido a singular según la base de datos
            'nombre' => 'nombre'
        ];
        $this->llavePrimaria = 'id_torneo'; // Corregido a singular
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
        // Convertimos la ubicación a formato Título o Mayúsculas para mantener orden
        $this->ubicacion = mb_convert_case(trim($datos['ubicacion'] ?? ''), MB_CASE_TITLE, "UTF-8");
        $this->estatus = $datos['estatus'] ?? null; // Se eliminó el espacio accidental que tenía 'estatus '
        
        $accion = $datos['accion'] ?? null;
        
        return match ($accion) {
            'incluir'   => $this->Incluir(),
            'eliminar'  => $this->Eliminar(),
            'buscar'    => $this->Buscar(),
            'modificar' => $this->Modificar(),
            'consultar' => $this->Consultar(), // Añadido para el listado general
            default => throw new Exception('La acción no es válida')
        };
    }

    public function Consultar(array $filtro = []): array
    {
        try {
            $conex = $this->conex();
            $params = []; 

            $sentencia = "SELECT * FROM torneos WHERE 1=1";

            // BUSCADOR GENERAL (Por nombre de torneo)
            if (!empty($filtro['filtro'])) {
                $p = "%" . $filtro['filtro'] . "%";
                $sentencia .= " AND nombre LIKE :f1";
                $params[':f1'] = $p;
            }

            // FILTROS ESPECÍFICOS
            if (!empty($this->nombre)) {    
                $sentencia .= " AND nombre LIKE :nombre";
                $params[':nombre'] = trim($this->nombre) . "%";
            }

            // ORDEN (Corregido a id_torneo en singular)
            $sentencia .= " ORDER BY id_torneo ASC";

            $stmt = $conex->prepare($sentencia);
            $stmt->execute($params);
            $datos = $stmt->fetchAll();

            return array('accion' => 'consultar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Torneos', $e->getMessage(), 'Modelo_Consultar');
            return array('accion' => 'error', 'mensaje' => 'Error al listar: ' . $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    private function Incluir(): array
    {
        try {
            if ($this->verificarExistencia('nombre', $this->nombre, 'torneos', NULL)) {
                return array('accion' => 'error', 'mensaje' => 'Ya existe un torneo registrado con este nombre.');
            }

            $conex = $this->conex();
            $sentencia = "INSERT INTO torneos (`nombre`, `fecha_inicio`, `fecha_fin`, `ubicacion`, `estatus`) VALUES (:nombre, :fecha_inicio, :fecha_fin, :ubicacion, :estatus)";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':fecha_inicio', $this->fecha_inicio);
            $stmt->bindParam(':fecha_fin', $this->fecha_fin);
            $stmt->bindParam(':ubicacion', $this->ubicacion);
            $stmt->bindParam(':estatus', $this->estatus);
            $stmt->execute();

            return array('accion' => 'incluir', 'mensaje' => 'Torneo registrado exitosamente.');
        } catch (Exception $e) {
            logs('Torneos', $e->getMessage(), 'Modelo');
            return array('accion' => 'error', 'mensaje' => 'Error al incluir: ' . $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }                                              

    private function Modificar(): array
    {
        try {
            if (!$this->verificarExistenciaPropia('nombre', $this->nombre, $this->id, 'torneos', NULL)) {
                if ($this->verificarExistencia('nombre', $this->nombre, 'torneos', NULL)) {
                    return array('accion' => 'error', 'mensaje' => 'Ya existe otro torneo registrado con este nombre.');
                }
            }
            $conex = $this->conex();
            // Corregido a id_torneo en singular
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

            return array('accion' => 'modificar', 'mensaje' => 'Torneo modificado exitosamente.');
        } catch (Exception $e) {
            logs('Torneos', $e->getMessage(), 'Modelo');
            return array('accion' => 'error', 'mensaje' => 'Error al modificar: ' . $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    function Buscar(): array
    {
        try {
            $conex = $this->conex();
            // Corregido a id_torneo en singular
            $sentencia = "SELECT * FROM torneos WHERE id_torneo = :id";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            
            return array('accion' => 'buscar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Torneos', $e->getMessage(), 'Modelo');
            return array('accion' => 'error', 'mensaje' => 'Error al buscar: ' . $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    private function Eliminar(): array
    {
        try {
            if (!$this->verificarExistencia('id', $this->id, 'torneos', NULL)) {
                return array('accion' => 'error', 'mensaje' => 'El torneo no existe.');
            }
            
            // Validación de dependencias: Evita borrar un torneo si ya tiene equipos o atletas
            if ($this->verificarExistencia('id', $this->id, 'equipos', NULL)) {
                return array('accion' => 'error', 'mensaje' => 'No se puede eliminar: el torneo tiene equipos o atletas asociados.');
            }

            $conex = $this->conex();
            // Corregido a id_torneo en singular
            $sentencia = "DELETE FROM torneos WHERE id_torneo = :id";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
            
            return array('accion' => 'eliminar', 'mensaje' => 'Torneo eliminado exitosamente.');
        } catch (Exception $e) {
            logs('Torneos', $e->getMessage(), 'Modelo');
            return array('accion' => 'error', 'mensaje' => 'Hubo un error al eliminar el torneo.');
        } finally {
            $conex = NULL;
        }
    }   
}