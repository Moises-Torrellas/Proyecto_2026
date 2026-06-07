<?php

namespace App\modelo;

use App\modelo\ModeloBase;
use Exception;

class ModeloCuentasCobrar extends ModeloBase
{
    private $id;
    private $id_concepto;
    private $id_atleta;
    private $id_moneda; // NUEVO: Para la base de datos
    private $monto;     // CAMBIO: Se ajusta al nombre del diagrama
    private $fecha_emision;
    private $fecha_vencimiento;
    private $estatus;

    public function __construct()
    {
        parent::__construct();
        $this->campoWhitelist = [
            'id' => 'id_cobrar',
            'id_cobrar' => 'id_cobrar',
            'id_concepto' => 'id_concepto',
            'id_conceptos' => 'id_conceptos',
            'id_atleta' => 'id_atleta',
            'id_moneda' => 'id_moneda',
            'monto_pendiente' => 'monto_pendiente', // <-- AGREGADO PARA LA LISTA BLANCA
            'estatus' => 'estatus'
        ];
        $this->llavePrimaria = 'id_cobrar';
    }

    public function ProcesarDatos(array $datos): array
    {
        if (empty($datos)) {
            throw new Exception('No se proporcionaron datos para procesar.');
        }

        $this->id = $datos['id'] ?? null;
        $this->id_concepto = $datos['id_concepto'] ?? null;
        $this->id_atleta = $datos['id_atleta'] ?? null;
        $this->id_moneda = $datos['id_moneda'] ?? null;
        $this->monto = $datos['monto_total'] ?? 0;

        // Leemos las fechas enviadas por el formulario
        $this->fecha_emision = !empty($datos['fecha_emision']) ? $datos['fecha_emision'] : date('Y-m-d');

        $this->fecha_vencimiento = !empty($datos['fecha_vencimiento']) ? $datos['fecha_vencimiento'] : date('Y-m-d', strtotime($this->fecha_emision . ' + 30 days'));

        $this->estatus = mb_convert_case(trim($datos['estatus'] ?? 'Pendiente'), MB_CASE_TITLE, "UTF-8");

        $accion = $datos['accion'] ?? null;
        return match ($accion) {
            'incluir'   => $this->Incluir(),
            'eliminar'  => $this->Eliminar(),
            'buscar'    => $this->Buscar(),
            'modificar' => $this->Modificar(),
            default => throw new Exception('La accion no es valida')
        };
    }

    public function Consultar(array $filtro = []): array
    {
        try {
            $conex = $this->conex();
            $params = [];

            // 1. Consultamos directamente la nueva vista
            $sentencia = "SELECT * FROM vista_cuentas_cobrar WHERE 1=1";

            // 2. Buscador general dinámico
            if (!empty($filtro['filtro'])) {
                $p = "%" . trim($filtro['filtro']) . "%";

                // Agregamos estatus_texto a la búsqueda
                $sentencia .= " AND (
                atleta_nombre LIKE :f1 OR 
                atleta_apellido LIKE :f2 OR 
                concepto_nombre LIKE :f3 OR
                moneda_nombre LIKE :f4 OR
                estatus_texto LIKE :f5
            )";
                $params[':f1'] = $p;
                $params[':f2'] = $p;
                $params[':f3'] = $p;
                $params[':f4'] = $p;
                $params[':f5'] = $p;
            }

            // 3. Ordenamiento basado en la vista
            $sentencia .= " ORDER BY fecha_emision DESC";

            $stmt = $conex->prepare($sentencia);
            $stmt->execute($params);
            $datos = $stmt->fetchAll();

            return array('accion' => 'consultar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('CuentasCobrar', $e->getMessage(), 'Modelo_Consultar');
            return array('accion' => 'error');
        } finally {
            $conex = NULL;
        }
    }

    public function ConsultarAtletas(): array
    {
        try {
            $conex = $this->conex();
            $sentencia = "SELECT id_atleta, nombres as nombre, apellidos as apellido FROM atletas ORDER BY nombres ASC";
            $stmt = $conex->prepare($sentencia);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            return array('accion' => 'consultarA', 'datos' => $datos);
        } catch (Exception $e) {
            logs('CuentasCobrar', $e->getMessage(), 'Modelo_ConsultarAtletas');
            return array('accion' => 'error', 'mensaje' => 'Error al cargar los atletas.');
        } finally {
            $conex = NULL;
        }
    }

    public function ConsultarConceptos(): array
    {
        try {
            $conex = $this->conex();
            // ADAPTACIÓN: Seleccionamos también la columna 'monto' de la tabla conceptos para usarla en JS
            $sentencia = "SELECT id_conceptos as id_concepto, nombre, monto FROM conceptos ORDER BY nombre ASC";
            $stmt = $conex->prepare($sentencia);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            return array('accion' => 'consultarCo', 'datos' => $datos);
        } catch (Exception $e) {
            logs('CuentasCobrar', $e->getMessage(), 'Modelo_ConsultarConceptos');
            return array('accion' => 'error', 'mensaje' => 'Error al cargar los conceptos.');
        } finally {
            $conex = NULL;
        }
    }

    // NUEVO MÉTODO PARA CARGAR SELECT DE MONEDAS
    public function ConsultarMonedas(): array
    {
        try {
            $conex = $this->conex();
            $sentencia = "SELECT id_moneda, nombre FROM monedas WHERE estatus = 1 ORDER BY nombre ASC";
            $stmt = $conex->prepare($sentencia);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            return array('accion' => 'consultarM', 'datos' => $datos);
        } catch (Exception $e) {
            logs('CuentasCobrar', $e->getMessage(), 'Modelo_ConsultarMonedas');
            return array('accion' => 'error', 'mensaje' => 'Error al cargar las monedas.');
        } finally {
            $conex = NULL;
        }
    }

    private function Incluir(): array
    {
        try {
            $conex = $this->conex();
            $conex->beginTransaction(); // Aquí inicias la transacción

            if (!$this->verificarExistencia('id_atleta', $this->id_atleta, 'atletas', NULL, bloquear: true)) {
                throw new Exception("El atleta seleccionado no existe en el sistema.");
            }

            if (!$this->verificarExistencia('id_conceptos', $this->id_concepto, 'conceptos', NULL, bloquear: true)) {
                throw new Exception("El concepto de cobro seleccionado no existe.");
            }

            if (!$this->verificarExistencia('id_moneda', $this->id_moneda, 'monedas', NULL, bloquear: true)) {
                throw new Exception("La moneda seleccionada no existe.");
            }

            if ($this->validarFrecuencia($conex, $this->id_concepto, $this->id_atleta, $this->fecha_emision)) {
                throw new Exception("El atleta ya tiene asignado este concepto para el periodo correspondiente.");
            }

            // Añadimos monto_pendiente a los campos y los valores de la sentencia SQL
            $sentencia = "INSERT INTO cuentas_cobrar (`id_concepto`, `id_atleta`, `id_moneda`, `monto_personalizado`, `monto_pendiente`, `fecha_emision`, `fecha_vencimiento`, `estatus`) 
                        VALUES (:id_concepto, :id_atleta, :id_moneda, :monto, :monto_pendiente, :fecha_emision, :fecha_vencimiento, :estatus)";

            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id_concepto', $this->id_concepto);
            $stmt->bindParam(':id_atleta', $this->id_atleta);
            $stmt->bindParam(':id_moneda', $this->id_moneda);
            $stmt->bindParam(':monto', $this->monto); // Se guarda en monto_personalizado

            // ASIGNACIÓN REQUERIDA: Al crear, el saldo inicial es el total
            $stmt->bindParam(':monto_pendiente', $this->monto);

            $stmt->bindParam(':fecha_emision', $this->fecha_emision);
            $stmt->bindParam(':fecha_vencimiento', $this->fecha_vencimiento);
            $stmt->bindParam(':estatus', $this->estatus);

            $stmt->execute();
            // CRÍTICO: Si esta línea no se ejecuta o está antes del execute, los datos se borran al cerrar la conexión
            $conex->commit();

            return array('accion' => 'exito');
        } catch (Exception $e) {
            // Si algo falla adentro, deshace el intento de inserción para no dejar datos corruptos
            if ($conex && $conex->inTransaction()) {
                $conex->rollback();
            }
            logs('CuentasCobrar', $e->getMessage(), 'Modelo_Incluir');

            // Retornamos el error para que el controlador sepa que NO fue exitoso
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

            if (!$this->verificarExistencia('id_cobrar', $this->id, 'cuentas_cobrar', NULL, bloquear: true)) {
                throw new Exception(INVALID_ID);
            }

            // Agregamos fecha_emision y fecha_vencimiento al UPDATE
            $sentencia = "UPDATE cuentas_cobrar SET 
                          id_concepto = :id_concepto, 
                          id_atleta = :id_atleta, 
                          id_moneda = :id_moneda,
                          monto_personalizado = :monto, 
                          fecha_emision = :fecha_emision,
                          fecha_vencimiento = :fecha_vencimiento,
                          estatus = :estatus 
                          WHERE id_cobrar = :id_cobrar";

            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id_concepto', $this->id_concepto);
            $stmt->bindParam(':id_atleta', $this->id_atleta);
            $stmt->bindParam(':id_moneda', $this->id_moneda);
            $stmt->bindParam(':monto', $this->monto);
            $stmt->bindParam(':fecha_emision', $this->fecha_emision);
            $stmt->bindParam(':fecha_vencimiento', $this->fecha_vencimiento);
            $stmt->bindParam(':estatus', $this->estatus);
            $stmt->bindParam(':id_cobrar', $this->id);
            $stmt->execute();

            $conex->commit();
            return array('accion' => 'exito');
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollback();
            }
            logs('CuentasCobrar', $e->getMessage(), 'Modelo_Modificar');
            return array('accion' => 'error', 'codigo' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    public function Buscar(int $id = null): array
    {
        try {
            $conex = $this->conex();

            // CORRECCIÓN: Cambiado monto a monto_personalizado para evitar el error 1054
            $sentencia = "SELECT *, 
                                    monto_personalizado as monto_total, 
                                    monto_pendiente as monto_pendiente 
                            FROM cuentas_cobrar 
                            WHERE id_cobrar = :id";

            $stmt = $conex->prepare($sentencia);
            if ($id===null) {
                $id = $this->id;
            }
            $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            return array('accion' => 'buscar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('CuentasCobrar', $e->getMessage(), 'Modelo_Buscar');
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

            if (!$this->verificarExistencia('id_cobrar', $this->id, 'cuentas_cobrar', NULL, bloquear: true)) {
                throw new Exception(INVALID_ID);
            }

            if ($this->verificarExistencia('id_cobrar', $this->id, 'pagos', NULL, bloquear: true)) {
                throw new Exception("No se puede anular un cargo que ya tiene pagos registrados.");
            }

            // CAMBIO: Activamos la bandera de anulado
            $sentencia = "UPDATE cuentas_cobrar SET anulado = 1 WHERE id_cobrar = :id";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();

            $conex->commit();
            return array('accion' => 'exito');
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollback();
            }
            logs('CuentasCobrar', $e->getMessage(), 'Modelo_Anular');
            return array('accion' => 'error', 'codigo' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    public function validarFrecuencia($conex, $id_concepto, $id_atleta, $fecha_emision): bool
    {
        try {
            $sql = "SELECT (SELECT COUNT(id_cobrar) 
                            FROM cuentas_cobrar 
                            WHERE id_concepto = c.id_conceptos 
                              AND id_atleta = :id_atleta 
                              AND (anulado = 0 OR anulado IS NULL)
                              AND (
                                  (c.regla = 'M' AND MONTH(fecha_emision) = MONTH(:fecha1) AND YEAR(fecha_emision) = YEAR(:fecha2)) OR
                                  (c.regla = 'A' AND YEAR(fecha_emision) = YEAR(:fecha3)) OR
                                  (c.regla = 'U')
                              )
                           ) as colisiones
                    FROM conceptos c 
                    WHERE c.id_conceptos = :id_concepto";

            $stmt = $conex->prepare($sql);
            $stmt->bindParam(':id_concepto', $id_concepto);
            $stmt->bindParam(':id_atleta', $id_atleta);

            // Asignamos la fecha a cada uno de los parámetros que exige el SQL
            $stmt->bindParam(':fecha1', $fecha_emision);
            $stmt->bindParam(':fecha2', $fecha_emision);
            $stmt->bindParam(':fecha3', $fecha_emision);

            $stmt->execute();

            $resultado = $stmt->fetch(\PDO::FETCH_ASSOC);

            // Si hay colisiones retorna true, si da 0 o la regla es 'L' retorna false
            return ($resultado && $resultado['colisiones'] > 0);
        } catch (Exception $e) {
            logs('CuentasCobrar', $e->getMessage(), 'Modelo_ValidarFrecuencia');
            // Lanzamos la excepción hacia arriba para que la transacción haga rollback
            throw new Exception("Error al validar la frecuencia del concepto en la base de datos.");
        }
    }

    public function ModificarEstatus(int $id, int $estatus, float $monto, ?\PDO $conexExterna = null): bool
    {
        $transaccionPropia = false;
        try {
            if ($conexExterna !== null) {
                $conex = $conexExterna;
            } else {
                $conex = $this->conex();
                $conex->beginTransaction();
                $transaccionPropia = true;
            }

            $sentencia = "UPDATE cuentas_cobrar SET estatus = :estatus, monto_pendiente = :monto WHERE id_cobrar = :id";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':estatus', $estatus);
            $stmt->bindParam(':monto', $monto);
            $stmt->execute();

            if ($transaccionPropia) {
                $conex->commit();
            }
            return true;
        } catch (Exception $e) {
            if ($transaccionPropia && isset($conex) && $conex->inTransaction()) {
                $conex->rollback();
            }
            logs('CuentasCobrar', $e->getMessage(), 'Modelo_ModificarEstatus');
            return false;
        } finally {
            if ($transaccionPropia) {
                $conex = NULL;
            }
        }
    }
}
