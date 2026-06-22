<?php

namespace App\modelo;

use App\modelo\ModeloBase;
use Exception;

class ModeloMonedas extends Conexion
{
    private $id;
    private $nombre;
    private $abreviatura;
    private $simbolo;
    private $bloqueo;
    public function __construct()
    {
        parent::__construct();
        $this->campoWhitelist = [
            'nombre' => 'nombre',
            'abreviatura' => 'abreviatura',
            'simbolo' => 'simbolo',
            'id'    => 'codigo_moneda'
        ];
        $this->llavePrimaria = 'codigo_moneda';
    }

    public function ProcesarDatos(array $datos)
    {
        if (empty($datos)) {
            throw new Exception('No se proporcionaron datos para procesar.');
        }

        $this->id = $datos['id'] ?? null;
        $this->bloqueo = $datos['bloqueo'] ?? null;
        $this->nombre = mb_convert_case(trim($datos['nombre'] ?? ''), MB_CASE_TITLE, "UTF-8");
        $this->abreviatura = mb_convert_case(trim($datos['abreviatura'] ?? ''), MB_CASE_UPPER, "UTF-8");
        $this->simbolo = mb_convert_case(trim($datos['simbolo'] ?? ''), MB_CASE_TITLE, "UTF-8");


        $accion = $datos['accion'] ?? null;
        return match ($accion) {
            'buscar' => $this->Buscar(),
            'incluir' => $this->Incluir(),
            'eliminar' => $this->Eliminar(),
            'modificar' => $this->Modificar(),
            'bloquear' => $this->Bloquear(),
            'select' => $this->SelecMonedaBase(),
            default => throw new Exception('La accion no es valida')
        };
    }

    public function Consultar(array $filtro = [])
    {
        try {
            $conex = $this->conex();
            $params = [];
            // 1. Asegúrate de dejar un espacio al final de la cadena base
            $sentencia = "SELECT * FROM monedas WHERE 1=1 ";

            if (!empty($filtro['filtro'])) {
                $p = "%" . $filtro['filtro'] . "%";
                // 2. IMPORTANTE: El espacio antes del AND
                $sentencia .= " AND (
                nombre LIKE :f1 OR 
                abreviatura LIKE :f2 OR
                simbolo LIKE :f3
            )";
                $params[':f1'] = $p;
                $params[':f2'] = $p;
                $params[':f3'] = $p;
            }

            $stmt = $conex->prepare($sentencia);
            $stmt->execute($params);

            $datos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            return array('accion' => 'consultar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Monedas', $e->getMessage(), 'Modelo_Consultar');
            return array('accion' => 'error', 'mensaje' => 'Error al listar las monedas.');
        } finally {
            $conex = null;
        }
    }

    function Buscar(int $id = null): array
    {
        try {
            $conex = $this->conex();
            $sentencia = "SELECT * FROM monedas WHERE codigo_moneda = :id";
            $stmt = $conex->prepare($sentencia);
            if ($id === null) $id = $this->id;
            $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            return array('accion' => 'buscar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Monedas', $e->getMessage(), 'Modelo_Buscar');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    private function Incluir()
    {
        $conex = null;
        try {
            $conex = $this->conex();
            $conex->beginTransaction();
            if ($this->verificarExistencia('nombre', $this->nombre, 'monedas', NULL, bloquear: true)) {
                throw new Exception(DUPLICATE_NAME);
            }
            if ($this->verificarExistencia('abreviatura', $this->abreviatura, 'monedas', NULL, bloquear: true)) {
                throw new Exception(VALIDATION . '1');
            }
            if ($this->verificarExistencia('simbolo', $this->simbolo, 'monedas', NULL, bloquear: true)) {
                throw new Exception(VALIDATION . '2');
            }

            $sentencia = "INSERT INTO monedas (nombre, abreviatura, simbolo) VALUES (:nombre, :abreviatura, :simbolo)";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':abreviatura', $this->abreviatura);
            $stmt->bindParam(':simbolo', $this->simbolo);
            $stmt->execute();

            $conex->commit();
            return array('accion' => 'exito');
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) $conex->rollBack();
            logs('Monedas', $e->getMessage(), 'Modelo_Incluir');
            return array('accion' => 'error', 'codigo' => $e->getMessage());
        } finally {
            $conex = null;
        }
    }

    private function Modificar()
    {
        $conex = null;
        try {
            $conex = $this->conex();
            $conex->beginTransaction();
            if (!$this->verificarExistencia('id', $this->id, 'monedas', NULL, bloquear: true)) {
                throw new Exception(INVALID_ID);
            }

            if (!$this->verificarExistenciaPropia('nombre', $this->nombre, $this->id, 'monedas', NULL, bloquear: true)) {
                if ($this->verificarExistencia('nombre', $this->nombre, 'monedas', NULL, bloquear: true)) {
                    throw new Exception(DUPLICATE_NAME);
                }
            }
            if (!$this->verificarExistenciaPropia('abreviatura', $this->abreviatura, $this->id, 'monedas', NULL, bloquear: true)) {
                if ($this->verificarExistencia('abreviatura', $this->abreviatura, 'monedas', NULL, bloquear: true)) {
                    throw new Exception(VALIDATION . '1');
                }
            }
            if (!$this->verificarExistenciaPropia('simbolo', $this->simbolo, $this->id, 'monedas', NULL, bloquear: true)) {
                if ($this->verificarExistencia('simbolo', $this->simbolo, 'monedas', NULL, bloquear: true)) {
                    throw new Exception(VALIDATION . '2');
                }
            }

            $sentencia = "UPDATE monedas SET nombre = :nombre, abreviatura = :abreviatura, simbolo = :simbolo WHERE codigo_moneda = :id_moneda";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id_moneda', $this->id);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':abreviatura', $this->abreviatura);
            $stmt->bindParam(':simbolo', $this->simbolo);
            $stmt->execute();

            $conex->commit();
            return array('accion' => 'exito');
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) $conex->rollBack();
            logs('Monedas', $e->getMessage(), 'Modelo_Modificar');
            return array('accion' => 'error', 'codigo' => $e->getMessage());
        } finally {
            $conex = null;
        }
    }

    private function Eliminar()
    {
        $conex = null;
        try {
            if ($this->verificarExistencia('id', $this->id, 'pagos', NULL)) {
                throw new Exception(ASSOCIATES);
            }
            if ($this->verificarExistencia('id', $this->id, 'vueltos', NULL)) {
                throw new Exception(ASSOCIATES . '2');
            }
            if ($this->verificarExistencia('id', $this->id, 'tasa_cambios', NULL)) {
                throw new Exception(ASSOCIATES . '3');
            }

            $conex = $this->conex();
            $conex->beginTransaction();

            $moneda = $this->obtenerDatosMoneda($conex, $this->id);

            if (!$moneda) {
                throw new Exception(INVALID_ID);
            }

            if ($moneda['base'] == 1) {
                throw new Exception(VALIDATION);
            }

            if ($this->obtenerCantidadTotalMonedas($conex) <= 2) {
                throw new Exception(VALIDATION . '2');
            }

            if ($moneda['estatus'] == 1 && $this->obtenerCantidadMonedasActivas($conex) <= 2) {
                throw new Exception(VALIDATION . '3');
            }

            $sentencia = "DELETE FROM monedas WHERE codigo_moneda = :id_moneda";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id_moneda', $this->id);
            $stmt->execute();
            $conex->commit();
            return array('accion' => 'exito');
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) $conex->rollback();
            logs('Monedas', $e->getMessage(), 'Modelo_Eliminar');
            return array('accion' => 'error', 'codigo' => $e->getMessage());
        } finally {
            $conex = null;
        }
    }

    private function Bloquear(): array
    {
        try {
            $conex = $this->conex();
            $conex->beginTransaction();

            $moneda = $this->obtenerDatosMoneda($conex, $this->id);

            if (!$moneda) {
                throw new Exception(INVALID_ID);
            }

            $nuevoEstado = ($this->bloqueo == 1) ? 2 : 1;

            if ($nuevoEstado == 2) {
                if ($moneda['base'] == 1) {
                    throw new Exception(VALIDATION);
                }

                if ($this->obtenerCantidadMonedasActivas($conex) <= 2) {
                    throw new Exception(VALIDATION . '2');
                }
            }

            $sql = "UPDATE `monedas` SET `estatus` = :estado WHERE codigo_moneda = :id";
            $stmt = $conex->prepare($sql);

            $stmt->execute([
                ':estado' => $nuevoEstado,
                ':id' => $this->id
            ]);

            $conex->commit();
            return ['accion' => 'exito'];
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            logs('Monedas', $e->getMessage(), 'Modelo_Bloquear');
            return ['accion' => 'error', 'codigo' => $e->getMessage()];
        } finally {
            $conex = null;
        }
    }

    private function SelecMonedaBase(): array
    {
        try {
            $conex = $this->conex();
            $conex->beginTransaction();

            if (!$this->verificarExistencia('id', $this->id, 'monedas', NULL, bloquear: true)) {
                throw new Exception(INVALID_ID);
            }

            // Usamos marcadores distintos: :id_case y :id_where
            $sql = "UPDATE `monedas` 
                        SET `base` = CASE 
                            WHEN `codigo_moneda` = :id_case THEN 1 
                            ELSE 2 
                        END 
                        WHERE `codigo_moneda` = :id_where OR `base` = 1";

            $stmt = $conex->prepare($sql);
            $stmt->execute([
                ':id_case'  => $this->id,
                ':id_where' => $this->id
            ]);

            $conex->commit();
            return ['accion' => 'exito'];
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            logs('Monedas', $e->getMessage(), 'Modelo_Selec');
            return ['accion' => 'error', 'codigo' => $e->getMessage()];
        } finally {
            $conex = null;
        }
    }

    private function obtenerCantidadTotalMonedas(\PDO $conex): int
    {
        $stmt = $conex->query("SELECT COUNT(*) FROM monedas");
        return (int) $stmt->fetchColumn();
    }

    private function obtenerCantidadMonedasActivas(\PDO $conex): int
    {
        $stmt = $conex->query("SELECT COUNT(*) FROM monedas WHERE estatus = 1");
        return (int) $stmt->fetchColumn();
    }

    private function obtenerDatosMoneda(\PDO $conex, int $id)
    {
        $stmt = $conex->prepare("SELECT base, estatus FROM monedas WHERE codigo_moneda = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
}
