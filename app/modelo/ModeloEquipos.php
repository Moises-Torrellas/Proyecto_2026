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
        //Definimos los campos permitidos para usar en las validaciones
        $this->campoWhitelist = [
            'nombre' => 'nombre',
            'categoria' => 'categoria',
            'id' => 'id_equipos'
        ];
        //Definimos la llave primaria de la tabla en la base de datos
        $this->llavePrimaria = 'id_equipos';
    }


    public function ProcesarDatos(array $datos): array
    {
        //si datos esta vacio ejecutamos la excepcion
        if (empty($datos)) {
            throw new Exception('No se proporcionaron datos para procesar.');
        }
        //Procesamos los datos
        $this->id = $datos['id'] ?? null;
        $this->categoria = $datos['categoria'] ?? null;
        $this->nombre = mb_convert_case(trim($datos['nombre'] ?? ''), MB_CASE_TITLE, "UTF-8");
        //ejecutamos la accion enviada por el controlador
        $accion = $datos['accion'] ?? null;
        return match ($accion) {
            'incluir'   => $this->Incluir(),
            'eliminar'  => $this->Eliminar(),
            'buscar' => $this->Buscar(),
            'modificar' => $this->Modificar(),
            'generar'   => $this->Consultar(),
            default => throw new Exception('La accion no es valida')
        };
    }

    public function Consultar(array $filtro = []): array
    {
        try {
            $conex = $this->conex();
            $params = []; // Unificamos el nombre de la variable

            // 1. Iniciamos la sentencia con WHERE 1=1 para concatenar AND tranquilamente
            $sentencia = "SELECT * FROM equipos WHERE 1=1";

            // 2. BUSCADOR GENERAL (El que viene del keyup)
            if (!empty($filtro['filtro'])) {
                $p = "%" . $filtro['filtro'] . "%";
                $sentencia .= " AND ( 
                nombre LIKE :f1
            )";
                $params[':f1'] = $p;
                
            }

            // 3. FILTROS ESPECÍFICOS (Si vienen del Modal o propiedades del objeto)
            if (!empty($this->nombre)) {
            $sentencia .= " AND nombre LIKE :nombre_obj";
            $params[':nombre_obj'] = "%" . trim($this->nombre) . "%";
        }

            // 4. Orden (Asegúrate de usar una columna que exista, como id_representante)
            $sentencia .= " ORDER BY id_equipos ASC";

            $stmt = $conex->prepare($sentencia);

            // IMPORTANTE: Pasar los parámetros al execute
            $stmt->execute($params);

            $datos = $stmt->fetchAll();

            return array('accion' => 'consultar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Equipos', $e->getMessage(), 'Modelo_Consultar');
            return array('accion' => 'error');
        } finally {
            $conex = NULL;
        }
    }

    private function Incluir()
    {
        $conex = null;
        try {
            // 1. Validar que la categoría exista en la tabla maestro de categorias
            if (!$this->verificarExistencia('categoria', $this->categoria, 'categorias', NULL)) {
                throw new Exception(INVALID_ID);
            }

            $conex = $this->conex();
            $conex->beginTransaction();

            // 2. Verificar duplicados (ej: que no haya otro equipo con el mismo nombre en la misma categoría)
            // Aquí puedes ajustar según las reglas de tu club
            
            $sql = "INSERT INTO equipos (nombre_equipo, id_categoria) VALUES (:nombre, :id_categoria)";
            $stmt = $conex->prepare($sql);
            
            $stmt->bindValue(':nombre', $this->nombre);
            $stmt->bindValue(':id_categoria', $this->categoria, \PDO::PARAM_INT);
            $stmt->execute();

            $conex->commit();
            return array('accion' => 'exito');
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            logs('Equipos', $e->getMessage(), 'Modelo');
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

            $sql = "UPDATE equipos SET nombre_equipo = :nombre, id_categoria = :id_categoria WHERE id_equipo = :id";
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
            logs('Equipos', $e->getMessage(), 'Modelo'); //
            return array('accion' => 'error', 'codigo' => $e->getMessage());
        } finally {
            $conex = null;
        }
    }

    function Buscar(): array
    {
        try {
            $conex = $this->conex();
            $sentencia = "SELECT * FROM equipos WHERE id_equipos = :id";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            return array('accion' => 'buscar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Equipos', $e->getMessage(), 'Modelo');
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
