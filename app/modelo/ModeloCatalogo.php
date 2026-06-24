<?php

namespace App\modelo;

use Exception;

class ModeloCatalogo extends Conexion
{
    private $id_catalogo;
    private $nombre;
    private $stock_minimo;
    private $id_categoria;
    private $talla;

    public function __construct()
    {
        parent::__construct();
        $this->campoWhitelist = [
            'id_catalogo' => 'id_catalogo',
            'nombre' => 'nombre',
            'stock_minimo' => 'stock_minimo',
            'id_categoria' => 'id_categoria',
            'talla' => 'talla'
        ];
        $this->llavePrimaria = 'id_catalogo';
    }

    public function ProcesarDatos(array $datos): array
    {
        if (empty($datos)) {
            throw new Exception('No se proporcionaron datos para procesar.');
        }

        $this->ValidarExpresiones($datos);

        $this->id_catalogo = $datos['id_catalogo'] ?? null;
        $this->nombre = mb_convert_case(trim($datos['nombre'] ?? ''), MB_CASE_TITLE, "UTF-8");
        $this->stock_minimo = $datos['stock_minimo'] ?? 0;
        $this->id_categoria = $datos['id_categoria'] ?? null;
        $this->talla = mb_strtoupper(trim($datos['talla'] ?? ''), "UTF-8");

        $accion = $datos['accion'] ?? null;
        return match ($accion) {
            'incluir'   => $this->Incluir(),
            'eliminar'  => $this->Eliminar(),
            'buscar'    => $this->Buscar(),
            'modificar' => $this->Modificar(),
            'generar'   => $this->Consultar(), 
            default => throw new Exception('La accion no es valida')
        };
    }

    public function Consultar(array $filtro = []): array
    {
        try {
            $conex = $this->conex();
            $params = [];

            $sentencia = "SELECT c.*, 
                                 cat.nombre as categoria_nombre
                          FROM catalogo c
                          INNER JOIN categoria_catalogo cat ON c.id_categoria = cat.id_categoria
                          WHERE 1=1"; 
            
            if (!empty($filtro['filtro'])) {
                $p = "%" . $filtro['filtro'] . "%";
                $sentencia .= " AND (
                    c.nombre LIKE :f1 OR 
                    cat.nombre LIKE :f2 OR 
                    c.talla LIKE :f3
                )";
                $params[':f1'] = $p;
                $params[':f2'] = $p;
                $params[':f3'] = $p;
            }

            if (!empty($this->id_categoria)) {
                $sentencia .= " AND c.id_categoria = :id_categoria";
                $params[':id_categoria'] = $this->id_categoria;
            }

            if (!empty($this->talla)) {
                $sentencia .= " AND c.talla = :talla";
                $params[':talla'] = $this->talla;
            }

            $sentencia .= " ORDER BY c.nombre ASC";

            $stmt = $conex->prepare($sentencia);
            $stmt->execute($params);
            $datos = $stmt->fetchAll();

            return array('accion' => 'consultar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Catalogo', $e->getMessage(), 'Modelo_Consultar');
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

            if (!$this->verificarExistencia('id_categoria', $this->id_categoria, 'categoria_catalogo', NULL, bloquear: true)) {
                throw new Exception("La categoría seleccionada no existe.");
            }

            $sentencia = "INSERT INTO catalogo (`nombre`, `stock_minimo`, `id_categoria`, `talla`) 
                          VALUES (:nombre, :stock_minimo, :id_categoria, :talla)";
            
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':stock_minimo', $this->stock_minimo);
            $stmt->bindParam(':id_categoria', $this->id_categoria);
            $stmt->bindParam(':talla', $this->talla);
            $stmt->execute();

            $conex->commit();
            return array('accion' => 'exito');
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollback();
            }
            logs('Catalogo', $e->getMessage(), 'Modelo_Incluir');
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

            if (!$this->verificarExistencia('id_catalogo', $this->id_catalogo, 'catalogo', NULL, bloquear: true)) {
                throw new Exception(INVALID_ID);
            }

            $sentencia = "UPDATE catalogo SET 
                          nombre = :nombre, 
                          stock_minimo = :stock_minimo, 
                          id_categoria = :id_categoria, 
                          talla = :talla 
                          WHERE id_catalogo = :id_catalogo";

            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':stock_minimo', $this->stock_minimo);
            $stmt->bindParam(':id_categoria', $this->id_categoria);
            $stmt->bindParam(':talla', $this->talla);
            $stmt->bindParam(':id_catalogo', $this->id_catalogo);
            $stmt->execute();

            $conex->commit();
            return array('accion' => 'exito');
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollback();
            }
            logs('Catalogo', $e->getMessage(), 'Modelo_Modificar');
            return array('accion' => 'error', 'codigo' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    function Buscar(): array
    {
        try {
            $conex = $this->conex();
            $sentencia = "SELECT * FROM catalogo WHERE id_catalogo = :id_catalogo";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id_catalogo', $this->id_catalogo);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            return array('accion' => 'buscar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Catalogo', $e->getMessage(), 'Modelo_Buscar');
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

            if (!$this->verificarExistencia('id_catalogo', $this->id_catalogo, 'catalogo', NULL, bloquear:true)) {
                throw new Exception(INVALID_ID);
            }
            
            if ($this->verificarExistencia('id_catalogo', $this->id_catalogo, 'articulos_inventario', NULL, bloquear:true)) {
                throw new Exception("No se puede eliminar el catálogo porque tiene artículos en el inventario físico asociados.");
            }

            $sentencia = "DELETE FROM catalogo WHERE id_catalogo = :id_catalogo";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id_catalogo', $this->id_catalogo);
            $stmt->execute();

            $conex->commit();
            return array('accion' => 'exito');
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollback();
            }
            logs('Catalogo', $e->getMessage(), 'Modelo_Eliminar');
            return array('accion' => 'error', 'codigo' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    private function ValidarExpresiones(array $datos): void
    {
        if (!empty($datos['id_catalogo']) && !preg_match('/^[0-9]+$/', $datos['id_catalogo'])) {
            throw new Exception('Id inválido.');
        }
        if (!empty($datos['nombre']) && !preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\-\.]{3,50}$/', $datos['nombre'])) {
            throw new Exception('Nombre inválido.');
        }
        if (!empty($datos['id_categoria']) && !preg_match('/^[0-9]+$/', $datos['id_categoria'])) {
            throw new Exception('Categoría inválida.');
        }
        if (isset($datos['stock_minimo']) && $datos['stock_minimo'] !== '' && !preg_match('/^[0-9]+$/', $datos['stock_minimo'])) {
            throw new Exception('Stock mínimo debe ser un número entero.');
        }
        if (!empty($datos['talla']) && !preg_match('/^[a-zA-Z0-9\s\-\/]{1,10}$/', $datos['talla'])) {
            throw new Exception('Talla inválida.');
        }
    }
}