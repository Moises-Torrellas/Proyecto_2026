<?php

namespace App\modelo;

use Exception;

class ModeloCatalogos extends Conexion
{
    private $id;
    private $nombre;
    private $id_posicion;
    private $stock_minimo;
    private $id_categoria;
    private $talla;

    public function __construct()
    {
        parent::__construct();
        $this->campoWhitelist = [
            'id' => 'id_catalogo',
            'id_catalogo' => 'id_catalogo',
            'nombre' => 'nombre',
            'id_posicion' => 'id_posicion',
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

        $this->id = $datos['id'] ?? null;
        $this->nombre = mb_convert_case(trim($datos['nombre'] ?? ''), MB_CASE_TITLE, "UTF-8");
        // Si el id_posicion viene vacío, lo guardamos como NULL en BD
        $this->id_posicion = !empty($datos['id_posicion']) ? $datos['id_posicion'] : null;
        $this->stock_minimo = $datos['stock_minimo'] ?? 0;
        $this->id_categoria = $datos['id_categoria'] ?? null;
        $this->talla = mb_strtoupper(trim($datos['talla'] ?? ''), "UTF-8"); // Las tallas suelen ir en mayúsculas

        $accion = $datos['accion'] ?? null;
        return match ($accion) {
            'incluir'   => $this->Incluir(),
            'eliminar'  => $this->Eliminar(),
            'buscar'    => $this->Buscar(),
            'modificar' => $this->Modificar(),
            'generar'   => $this->Consultar(), // Reutilizamos consultar para el reporte si es necesario
            default => throw new Exception('La accion no es valida')
        };
    }

    public function Consultar(array $filtro = []): array
    {
        try {
            $conex = $this->conex();
            $params = [];

            $sentencia = "SELECT c.*, 
                                 cat.nombre as categoria_nombre, 
                                 p.nombre as posicion_nombre 
                          FROM catalogos c
                          INNER JOIN categoria_catalogo cat ON c.id_categoria = cat.id_categoria
                          LEFT JOIN posiciones p ON c.id_posicion = p.id_posicion
                          WHERE 1=1"; 
            
            // Buscador General
            if (!empty($filtro['filtro'])) {
                $p = "%" . $filtro['filtro'] . "%";
                $sentencia .= " AND (
                    c.nombre LIKE :f1 OR 
                    cat.nombre LIKE :f2 OR 
                    p.nombre LIKE :f3 OR
                    c.talla LIKE :f4
                )";
                $params[':f1'] = $p;
                $params[':f2'] = $p;
                $params[':f3'] = $p;
                $params[':f4'] = $p;
            }

            // Filtros específicos para reportes
            if (!empty($this->id_categoria)) {
                $sentencia .= " AND c.id_categoria = :id_categoria";
                $params[':id_categoria'] = $this->id_categoria;
            }

            if (!empty($this->id_posicion)) {
                $sentencia .= " AND c.id_posicion = :id_posicion";
                $params[':id_posicion'] = $this->id_posicion;
            }
            // NUEVO: Filtro SQL por talla
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

    // --- MÉTODOS PARA CARGAR LOS SELECTS DEL FORMULARIO ---

    public function ConsultarCategorias(): array
    {
        try {
            $conex = $this->conex();
            $sentencia = "SELECT id_categoria, nombre FROM categoria_catalogo ORDER BY nombre ASC";
            $stmt = $conex->prepare($sentencia);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            return array('accion' => 'consultarCat', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Catalogo', $e->getMessage(), 'Modelo_ConsultarCategorias');
            return array('accion' => 'error', 'mensaje' => 'Error al cargar las categorías.');
        } finally {
            $conex = NULL;
        }
    }

    public function ConsultarPosiciones(): array
    {
        try {
            $conex = $this->conex();
            $sentencia = "SELECT id_posicion as id_posicion, nombre FROM posiciones ORDER BY nombre ASC";
            $stmt = $conex->prepare($sentencia);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            return array('accion' => 'consultarPos', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Catalogo', $e->getMessage(), 'Modelo_ConsultarPosiciones');
            return array('accion' => 'error', 'mensaje' => 'Error al cargar las posiciones.');
        } finally {
            $conex = NULL;
        }
    }

    // --- MÉTODOS CRUD ---

    private function Incluir(): array
    {
        try {
            $conex = $this->conex();
            $conex->beginTransaction();

            if (!$this->verificarExistencia('id_categoria', $this->id_categoria, 'categoria_catalogo', NULL, bloquear: true)) {
                throw new Exception("La categoría seleccionada no existe.");
            }

            if (!empty($this->id_posicion)) {
                if (!$this->verificarExistencia('id_posicion', $this->id_posicion, 'posiciones', NULL, bloquear: true)) {
                    throw new Exception("La posición seleccionada no existe.");
                }
            }

            $sentencia = "INSERT INTO catalogos (`nombre`, `id_posicion`, `stock_minimo`, `id_categoria`, `talla`) 
                          VALUES (:nombre, :id_posicion, :stock_minimo, :id_categoria, :talla)";
            
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':id_posicion', $this->id_posicion);
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

            // CORRECCIÓN 2: Se agregó la 's' a la tabla 'catalogos' en verificarExistencia
            if (!$this->verificarExistencia('id_catalogo', $this->id, 'catalogos', NULL, bloquear: true)) {
                throw new Exception(INVALID_ID);
            }

            $sentencia = "UPDATE catalogos SET 
                          nombre = :nombre, 
                          id_posicion = :id_posicion, 
                          stock_minimo = :stock_minimo, 
                          id_categoria = :id_categoria, 
                          talla = :talla 
                          WHERE id_catalogo = :id_catalogo";

            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':id_posicion', $this->id_posicion);
            $stmt->bindParam(':stock_minimo', $this->stock_minimo);
            $stmt->bindParam(':id_categoria', $this->id_categoria);
            $stmt->bindParam(':talla', $this->talla);
            $stmt->bindParam(':id_catalogo', $this->id);
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
            $sentencia = "SELECT * FROM catalogos WHERE id_catalogo = :id";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id', $this->id);
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

            // CORRECCIÓN 3: Se agregó la 's' a la tabla 'catalogos' en verificarExistencia
            if (!$this->verificarExistencia('id_catalogo', $this->id, 'catalogos', NULL, bloquear:true)) {
                throw new Exception(INVALID_ID);
            }
            
            if ($this->verificarExistencia('id_catalogo', $this->id, 'equipamientos', NULL, bloquear:true)) {
                throw new Exception("No se puede eliminar el catálogo porque tiene equipamientos asociados.");
            }

            // CORRECCIÓN 4: Se agregó la 's' a la consulta DELETE
            $sentencia = "DELETE FROM catalogos WHERE id_catalogo = :id";
            
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id', $this->id);
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
        if (!empty($datos['id']) && !preg_match('/^[0-9]+$/', $datos['id'])) {
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
        if (!empty($datos['id_posicion']) && !preg_match('/^[0-9]+$/', $datos['id_posicion'])) {
            throw new Exception('Posición inválida.');
        }
        if (!empty($datos['talla']) && !preg_match('/^[a-zA-Z0-9\s\-\/]{1,10}$/', $datos['talla'])) {
            throw new Exception('Talla inválida.');
        }
    }
}