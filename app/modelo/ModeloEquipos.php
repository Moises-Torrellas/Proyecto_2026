<?php

namespace App\modelo;

use Exception;

class ModeloEquipos extends Conexion
{
    private $id;
    private $nombre;
    private $categoria;

    public function ConsultarAtletasParaAsignacion(array $filtro = []): array
    {
        try {
            $conex = $this->conex();
            $params = [];

            $sentencia = "SELECT a.codigo_atleta AS id_atleta,
                                a.doc_identidad,
                                a.nombres,
                                a.apellidos,
                                c.nombre AS nombre_categoria,
                                p.nombre AS nombre_posicion
                        FROM atletas a
                        INNER JOIN categorias c ON c.id_categorias = a.id_categoria
                        INNER JOIN posiciones p ON p.id_posicion = a.id_posicion
                        WHERE 1=1";

            if (!empty($filtro['filtro'])) {
                $p = '%' . trim($filtro['filtro']) . '%';
                $sentencia .= " AND (a.doc_identidad LIKE :f1 OR a.nombres LIKE :f2 OR a.apellidos LIKE :f3)";
                $params[':f1'] = $p;
                $params[':f2'] = $p;
                $params[':f3'] = $p;
            }

            $sentencia .= " ORDER BY a.estatus = 1 DESC, a.apellidos ASC, a.nombres ASC";

            $stmt = $conex->prepare($sentencia);
            $stmt->execute($params);
            $datos = $stmt->fetchAll();

            // Normalizamos para que el JS sea consistente
            $normalizados = array_map(function ($row) {
                return [
                    'id' => (int)($row['id_atleta'] ?? 0),
                    'doc_i' => $row['doc_identidad'] ?? '',
                    'nombre' => trim(($row['nombres'] ?? '') . ' ' . ($row['apellidos'] ?? '')),
                    'categoria' => $row['nombre_categoria'] ?? '',
                    'posicion' => $row['nombre_posicion'] ?? ''
                ];
            }, $datos);

            return ['accion' => 'consultarAtletasModal', 'datos' => $normalizados];
        } catch (Exception $e) {
            logs('Equipos', $e->getMessage(), 'Modelo_ConsultarAtletasParaAsignacion');
            return ['accion' => 'error', 'mensaje' => $e->getMessage()];
        }
    }

    public function ConsultarAtletasAsignadosEquipo(int $idEquipo): array

    {
        try {
            $conex = $this->conex();
            $sentencia = "SELECT a.id_atleta,
                                 a.doc_identidad,
                                 a.nombres,
                                 a.apellidos,
                                 c.nombre AS nombre_categoria,
                                 p.nombre AS nombre_posicion
                          FROM detalles_equipos de
                          INNER JOIN atletas a ON a.id_atleta = de.id_atleta
                          INNER JOIN categorias c ON c.id_categorias = a.id_categoria
                          INNER JOIN posiciones p ON p.id_posicion = a.id_posicion
                          WHERE de.id_equipo = :idEquipo";

            $stmt = $conex->prepare($sentencia);
            $stmt->bindValue(':idEquipo', $idEquipo, \PDO::PARAM_INT);
            $stmt->execute();
            $datos = $stmt->fetchAll();

            $normalizados = array_map(function ($row) {
                return [
                    'id' => (int)($row['id_atleta'] ?? 0),
                    'doc_i' => $row['doc_identidad'] ?? '',
                    'nombre' => trim(($row['nombres'] ?? '') . ' ' . ($row['apellidos'] ?? '')),
                    'categoria' => $row['nombre_categoria'] ?? '',
                    'posicion' => $row['nombre_posicion'] ?? ''
                ];
            }, $datos);

            return ['accion' => 'consultarAtletasAsignadosEquipo', 'datos' => $normalizados];
        } catch (Exception $e) {
            logs('Equipos', $e->getMessage(), 'Modelo_ConsultarAtletasAsignadosEquipo');
            return ['accion' => 'error', 'mensaje' => $e->getMessage(), 'datos' => []];
        } finally {
            $conex = null;
        }
    }

    
    public function GuardarDetallesEquipo($idEquipo, array $idsAtleta): void
    {


        $conex = null;

        try {
            $idEquipo = (int)$idEquipo;
            if ($idEquipo <= 0) {
                throw new Exception('id_equipos inválido.');
            }

            $idsAtleta = array_values(array_filter(array_map('intval', $idsAtleta)));
            if (empty($idsAtleta)) {
                return;
            }

            $conex = $this->conex();
            $conex->beginTransaction();

            // 1) Obtener categoría del equipo
            $stmtEq = $conex->prepare('SELECT id_categoria FROM equipos WHERE id_equipos = :id_equipo');
            $stmtEq->bindValue(':id_equipo', $idEquipo, \PDO::PARAM_INT);
            $stmtEq->execute();
            $catEquipo = $stmtEq->fetchColumn();

            if ($catEquipo === false || $catEquipo === null) {
                throw new Exception('No se pudo obtener la categoría del equipo.');
            }
            $catEquipo = (int)$catEquipo;

            // 2) Validar que TODOS los atletas pertenezcan a la misma categoría del equipo
            $placeholders = implode(',', array_fill(0, count($idsAtleta), '?'));
            $sqlValid = "SELECT id_atleta FROM atletas WHERE id_atleta IN ($placeholders) AND id_categoria <> ?";
            $stmtValid = $conex->prepare($sqlValid);

            // bind: ids atletas primero
            $i = 1;
            foreach ($idsAtleta as $idA) {
                $stmtValid->bindValue($i, (int)$idA, \PDO::PARAM_INT);
                $i++;
            }
            // y al final: categoría del equipo
            $stmtValid->bindValue($i, $catEquipo, \PDO::PARAM_INT);

            $stmtValid->execute();
            $idsNoValidos = $stmtValid->fetchAll(\PDO::FETCH_COLUMN, 0);

            if (!empty($idsNoValidos)) {
                throw new Exception('Solo se pueden asignar atletas de la misma categoría del equipo.');
            }

            // 3) Guardar (si todo está OK)
            $stmtDel = $conex->prepare('DELETE FROM detalles_equipos WHERE id_equipo = :id_equipo');
            $stmtDel->bindValue(':id_equipo', $idEquipo, \PDO::PARAM_INT);
            $stmtDel->execute();

            $stmtIns = $conex->prepare('INSERT INTO detalles_equipos (id_equipo, id_atleta) VALUES (:id_equipo, :id_atleta)');

            foreach ($idsAtleta as $idAtleta) {
                $stmtIns->bindValue(':id_equipo', $idEquipo, \PDO::PARAM_INT);
                $stmtIns->bindValue(':id_atleta', $idAtleta, \PDO::PARAM_INT);
                $stmtIns->execute();
            }

            $conex->commit();
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            throw $e;
        } finally {
            $conex = null;
        }
    }





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

        $this->ValidarExpresiones($datos);

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

    public function ConsultarEquipos(){
        try {
            $conex = $this->conex();
            $sentencia = "SELECT * FROM `equipos` WHERE 1";

            $stmt = $conex->prepare($sentencia);
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

            $columnas = [];
            $marcadores = [];

            $columnas[] = "nombre";
            $marcadores[] = ":nombre";
            $columnas[] = "id_categoria";
            $marcadores[] = ":id_categoria";

            $sql = "INSERT INTO equipos (" . implode(", ", $columnas) . ") 
                VALUES (" . implode(", ", $marcadores) . ")";

            $stmt = $conex->prepare($sql);

            $stmt->bindValue(':nombre', $this->nombre);
            $stmt->bindValue(':id_categoria', $this->categoria, \PDO::PARAM_INT);

            $stmt->execute();

            $idEquipo = (int)$conex->lastInsertId();

            $conex->commit();
            return array('accion' => 'exito', 'id_equipos' => $idEquipo);
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

    private function ValidarExpresiones(array $datos): void
    {
        if (!empty($datos['id']) && !preg_match('/^[0-9]+$/', $datos['id'])) {
            throw new Exception('Id inválido.');
        }
        if (!empty($datos['nombre']) && !preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s]{3,30}$/', $datos['nombre'])) {
            throw new Exception('Nombre inválido.');
        }
        if (!empty($datos['categoria']) && !preg_match('/^[0-9]+$/', $datos['categoria'])) {
            throw new Exception('Categoría inválida.');
        }
    }
}
