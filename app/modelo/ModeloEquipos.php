<?php

namespace App\modelo;

use App\modelo\ModeloBase;
use Exception;

class ModeloEquipos extends ModeloBase
{
    private $id;
    private $nombre;
    private $categoria;



    public function __construct()
    {
        parent::__construct();
        $this->campoWhitelist = [
            'categoria' => 'id_categorias',
            'id' => 'id_equipos',
            'nombre' => 'nombre'
        ];
        $this->llavePrimaria = 'id_equipos';
    }

    public function ProcesarDatos(array $datos): array
    {
        // 1. Verificación de integridad inicial
        if (empty($datos)) {
            throw new Exception('No se proporcionaron datos para procesar el registro del equipo.');
        }

        // 2. Asignación y saneamiento de atributos básicos
        $this->id            = $datos['id'] ?? null;
        $this->categoria  = $datos['categoria'] ?? null;

        // Atributos con formato de Título (Standard de tu proyecto)
        $this->nombre   = mb_convert_case(trim($datos['nombre'] ?? ''), MB_CASE_TITLE, "UTF-8");


        // 5. Ejecución de la acción vía Match
        $accion = $datos['accion'] ?? null;

        return match ($accion) {
            'incluir'   => $this->Incluir(),
            'modificar' => $this->Modificar(),
            'eliminar'  => $this->Eliminar(),
            'buscar'    => $this->Buscar(),
            default     => throw new Exception('La acción solicitada para el equipo no es válida.')
        };
    }
    public function Consultar(array $filtro = []): array
{
    try {
        $conex = $this->conex();
        $params = [];

        // 1. Apuntamos a la vista (incluye info de categoría)
        $sentencia = "SELECT e.*, c.nombre AS categoria, c.id_categorias AS id_categorias\r\n                        FROM equipos e\r\n                        INNER JOIN categorias c ON c.id_categorias = e.id_categoria\r\n                        WHERE 1=1";


        // 2. BUSCADOR GENERAL
        if (!empty($filtro['filtro'])) {
            $p = "%" . trim($filtro['filtro']) . "%";
            $sentencia .= " AND (e.nombre LIKE :f1 OR e.id_categoria LIKE :f2 OR c.nombre LIKE :f3)";
            $params[':f1'] = $p;
            $params[':f2'] = $p;
            $params[':f3'] = $p;
        }


        // 3. FILTROS ESPECÍFICOS
        if (!empty($this->categoria)) {
            $sentencia .= " AND e.id_categoria = :id_cat";
            $params[':id_cat'] = $this->categoria;
        }


        // --- CORRECCIONES NECESARIAS AÑADIDAS AQUÍ ---
        
        // 4. Preparar y Ejecutar (Faltaba esto)
        $stmt = $conex->prepare($sentencia);
        $stmt->execute($params);
        $datos = $stmt->fetchAll();

        // 5. Retornar los datos (Faltaba esto)
        return array('accion' => 'consultar', 'datos' => $datos);

    } catch (Exception $e) {
        logs('Equipos', $e->getMessage(), 'Modelo_Consultar_Vista');
        return array('accion' => 'error', 'msg' => $e->getMessage());
    } finally {
        $conex = NULL;
    }
}
    private function Incluir()
    {
        $conex = null;
        try {
            if (!$this->verificarExistencia('categoria', $this->categoria, 'categorias', NULL)) {
                throw new Exception(INVALID_ID);
            }
            $conex = $this->conex();
            $conex->beginTransaction();

            // 1. Verificaciones de duplicados (Cédula y Teléfono)
            if ($this->nombre !== null && $this->nombre !== '') {
                if ($this->verificarExistencia('nombre', $this->nombre, 'equipos', NULL, bloquear: true)) {
                    throw new Exception(DUPLICATE_NAME);
                }
            }

            // 2. Definición de partes de la consulta
            $columnas = [];
            $marcadores = [];

            // --- DATOS OBLIGATORIOS ---
            // Estos siempre se incluyen según la lógica de tu controlador
            $columnas[] = "nombre";
            $marcadores[] = ":nombre";
            $columnas[] = "id_categoria";
            $marcadores[] = ":id_categoria";

            // --- DATOS OPCIONALES ---
            // Se agregan a la consulta solo si no son nulos

            // 3. Preparación de la sentencia SQL
            $sql = "INSERT INTO equipos (" . implode(", ", $columnas) . ") 
                VALUES (" . implode(", ", $marcadores) . ")";

            $stmt = $conex->prepare($sql);

            // 4. Vinculación de valores con bindValue (Evita Inyección SQL)
            // Vinculación de obligatorios
            $stmt->bindValue(':nombre', $this->nombre);
            $stmt->bindValue(':id_categoria', $this->categoria, \PDO::PARAM_INT);

            $stmt->execute();

            $conex->commit();
            return array('accion' => 'exito');
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            logs('Equipos', $e->getMessage(), 'Modelo_Incluir');
            return array('accion' => 'error', 'codigo' => $e->getMessage());
        } finally {
            $conex = null;
        }
    }

    private function Modificar()
    {
        $conex = null;
        try {
            if (!$this->verificarExistencia('categoria', $this->categoria, 'categorias', NULL)) {
                throw new Exception(INVALID_ID);
            }
            $conex = $this->conex();
            $conex->beginTransaction();

                if (!$this->verificarExistenciaPropia('nombre', $this->nombre, $this->id, 'equipos', NULL, bloquear: true)) {
                    if ($this->verificarExistencia('nombre', $this->nombre, 'equipos', NULL, bloquear: true)) {
                        throw new Exception(DUPLICATE_NAME);
                    }
                }
            
            $campos = [];

            // --- DATOS OBLIGATORIOS ---
            $campos[] = "nombre = :nombre";
            $campos[] = "id_categoria = :id_categoria";

            $sql = "UPDATE equipos SET " . implode(", ", $campos) . " WHERE id_equipos = :id";
            $stmt = $conex->prepare($sql);

            $stmt->bindValue(':id', $this->id, \PDO::PARAM_INT);

            $stmt->bindValue(':nombre', $this->nombre);
            $stmt->bindValue(':id_categoria', $this->categoria, \PDO::PARAM_INT);

            $stmt->execute();

            $conex->commit();
            return array('accion' => 'exito');
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            logs('Equipos', $e->getMessage(), 'Modelo_Modificar');
            return array('accion' => 'error', 'codigo' => $e->getMessage());
        } finally {
            $conex = null;
        }
    }

    private function Buscar()
    {
        try {
            $conex = $this->conex();
            $sentencia = "SELECT e.*, c.id_categorias AS id_categorias, c.nombre AS categoria\r\n                            FROM equipos e\r\n                            INNER JOIN categorias c ON c.id_categorias = e.id_categoria\r\n                            WHERE e.id_equipos = :id";

            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            return array('accion' => 'buscar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Equipos', $e->getMessage(), 'Modelo_Buscar');
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
            if (!$this->verificarExistencia('id', $this->id, 'equipos', NULL, bloquear:true)) {
                throw new Exception(INVALID_ID);
            }
            if ($this->verificarExistencia('id', $this->id, 'palmares', NULL, bloquear:true)) {
                throw new Exception(ASSOCIATES);
            }
            $sentencia = "DELETE FROM equipos WHERE id_equipos = :id";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
            $conex->commit();
            return array('accion' => 'exito');
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollback();
            }
            logs('Equipos', $e->getMessage(), 'Modelo_Eliminar');
            return array('accion' => 'error', 'codigo' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }
}
