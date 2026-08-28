<?php

namespace App\modelo;

use Exception;
use PDO;

class ModeloPremios extends Conexion
{
    private $codigo_premio; // Cambiado de $id a $codigo_premio
    private $nombre;
    private $tipo;

    public function __construct()
    {
        parent::__construct();
        // Definimos los campos permitidos para usar en las validaciones
        $this->campoWhitelist = [
            'nombre' => 'nombre',
            'codigo_premio' => 'codigo_premio' // Ajustado a la BD
        ];
        // Definimos la llave primaria de la tabla en la base de datos
        $this->llavePrimaria = 'codigo_premio'; // Ajustado a la BD
    }

    public function ProcesarDatos(array $datos): array
    {
        if (empty($datos)) {
            throw new Exception('No se proporcionaron datos para procesar.');
        }

        $this->codigo_premio = $datos['codigo_premio'] ?? null; // Ajustado

        $this->nombre = mb_convert_case(trim($datos['nombre'] ?? ''), MB_CASE_TITLE, "UTF-8");
        $this->tipo   = isset($datos['tipo']) ? trim($datos['tipo']) : null;
        
        $accion = $datos['accion'] ?? null;
        return match ($accion) {
            'incluir'   => $this->Incluir(),
            'modificar' => $this->Modificar(),
            'eliminar'  => $this->Eliminar(),
            'buscar'    => $this->Buscar(),
            'generar'   => $this->Consultar($datos),
            default     => throw new Exception('La acción solicitada no es válida para Premios.')
        };
    }

    public function Consultar(array $filtro = []): array
    {
        try {
            $conex = $this->conex();
            $params = [];

            $sentencia = "SELECT * FROM premios WHERE 1=1";

            if (!empty($filtro['filtro'])) {
                $p = "%" . $filtro['filtro'] . "%";
                $sentencia .= " AND (nombre LIKE :f1 OR tipo LIKE :f2)";
                $params[':f1'] = $p;
                $params[':f2'] = $p;
            }

            if (!empty($this->nombre)) {
                $sentencia .= " AND nombre LIKE :nombre";
                $params[':nombre'] = trim($this->nombre) . "%";
            }

            if (!empty($this->tipo)) {
                $sentencia .= " AND tipo = :tipo";
                $params[':tipo'] = $this->tipo;
            }

            $sentencia .= " ORDER BY codigo_premio ASC"; // Ajustado a la BD

            $stmt = $conex->prepare($sentencia);
            $stmt->execute($params);
            $datos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            return array('accion' => 'consultar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Premios', $e->getMessage(), 'Modelo_Consultar');
            die("Error técnico en la consulta SQL de la Base de Datos: " . $e->getMessage());
            return array('accion' => 'error');
        } finally {
            $conex = NULL;
        }
    }

    private function Incluir(): array
    {
        try {
            $conex = $this->conex();
            $conex->beginTransaction();
            
            if ($this->verificarExistencia('nombre', $this->nombre, 'premios', NULL, bloquear: true)) {
                throw new Exception(DUPLICATE_NAME);
            }

            $sentencia = "INSERT INTO premios (`nombre`, `tipo`) VALUES (:nombre, :tipo)";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':tipo', $this->tipo);

            $stmt->execute();
            $conex->commit();
            return array('accion' => 'exito');
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollback();
            }
            logs('Premios', $e->getMessage(), 'Modelo');
            return array('accion' => 'error', 'codigo' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    private function Modificar(): array
    {
        try {
            $conex = $this->conex();
            $conex->beginTransaction();

            // Ajustado a codigo_premio
            if (!$this->verificarExistenciaPropia('nombre', $this->nombre, $this->codigo_premio, 'premios', NULL, bloquear: true)) {
                if ($this->verificarExistencia('nombre', $this->nombre, 'premios', NULL, bloquear: true)) {
                    throw new Exception(DUPLICATE_NAME);
                }
            }

            $sentencia = "UPDATE premios SET nombre = :nombre, tipo = :tipo WHERE codigo_premio = :codigo_premio"; // Ajustado a la BD
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':tipo', $this->tipo);
            $stmt->bindParam(':codigo_premio', $this->codigo_premio); // Ajustado
            $stmt->execute();

            $conex->commit();
            return array('accion' => 'exito');
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollback();
            }
            logs('Premios', $e->getMessage(), 'Modelo_Modificar');
            return array('accion' => 'error', 'codigo' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    public function Buscar(int $id = null): array
    {
        try {
            $idBuscar = $id ?? $this->codigo_premio;
            $conex = $this->conex();
            $sentencia = "SELECT * FROM premios WHERE codigo_premio = :codigo_premio"; // Ajustado a la BD
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':codigo_premio', $idBuscar); // Ajustado
            $stmt->execute();
            $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return array('accion' => 'buscar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Premios', $e->getMessage(), 'Modelo_Buscar');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    private function Eliminar(): array
    {
        try {
            $conex = $this->conex();
            $conex->beginTransaction();
            
            // CORRECCIÓN: Ajustado a codigo_premio
            if (!$this->verificarExistencia('codigo_premio', $this->codigo_premio, 'premios', NULL, bloquear:true)) {
                throw new Exception(INVALID_ID);
            }
            // Si la llave foránea en 'detalles_palmares' se llama distinto, ajústalo aquí. Asumo que es 'codigo_premio'
            if ($this->verificarExistencia('codigo_premio', $this->codigo_premio, 'palmares_grupal', NULL, bloquear:true)) {
                throw new Exception(ASSOCIATES);
            }
            if ($this->verificarExistencia('codigo_premio', $this->codigo_premio, 'palmares_individual', NULL, bloquear:true)) {
                throw new Exception(ASSOCIATES);
            }
            
            $sentencia = "DELETE FROM premios WHERE codigo_premio = :codigo_premio"; // Ajustado a la BD
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':codigo_premio', $this->codigo_premio); // Ajustado
            $stmt->execute();
            $conex->commit();
            return array('accion' => 'exito');
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollback();
            }
            logs('Premios', $e->getMessage(), 'Modelo_Eliminar');
            return array('accion' => 'error', 'codigo' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    public function validarTipoPremio(int $codigo_premio, string $tipoEsperado): void // Ajustado
    {
        $conex = null;
        try {
            $conex = $this->conex();
            $stmt = $conex->prepare("SELECT tipo FROM premios WHERE codigo_premio = :codigo_premio"); // Ajustado
            $stmt->bindValue(':codigo_premio', $codigo_premio, \PDO::PARAM_INT); // Ajustado
            $stmt->execute();
            $premio = $stmt->fetch();

            if (!$premio) {
                throw new Exception(INVALID_ID);
            }

            if ($premio['tipo'] !== $tipoEsperado) {
                throw new Exception(VALIDATION);
            }
        } finally {
            $conex = null;
        }
    }

    public function AsignarPremioAtleta(int $id_atleta, string $descripcion, float $monto): array
    {
        $conex = null;
        try {
            $conex = $this->conex();
            $stmt = $conex->prepare("CALL RegistrarPremioSeguro(?, ?, ?)");
            $stmt->execute([$id_atleta, $descripcion, $monto]);
            
            return ['accion' => 'exito', 'mensaje' => 'Premio registrado exitosamente para el atleta.'];
        } catch (Exception $e) {
            logs('Premios', $e->getMessage(), 'ModeloPremios_AsignarPremioAtleta');
            return ['accion' => 'error', 'mensaje' => $e->getMessage()];
        } finally {
            $conex = null;
        }
    }
}