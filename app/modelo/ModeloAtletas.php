<?php

namespace App\modelo;

use App\modelo\ModeloBase;
use Exception;

class ModeloAtletas extends ModeloBase
{
    private $id;
    private $doc_identidad;
    private $nombre;
    private $apellido;
    private $telefono;
    private $direccion;
    private $representante;
    private $posicion;
    private $genero;
    private $categoria;
    private $fecha_nac;
    private $foto;



    public function __construct()
    {
        parent::__construct();
        $this->campoWhitelist = [
            'cedula' => 'doc_identidad',
            'telefono' => 'telefono',
            'categoria' => 'id_categorias',
            'posicion' => 'id_posicion',
            'representante' => 'id_representante',
            'id' => 'id_atleta'
        ];
        $this->llavePrimaria = 'id_atleta';
    }

    public function ProcesarDatos(array $datos): array
    {
        // 1. Verificación de integridad inicial
        if (empty($datos)) {
            throw new Exception('No se proporcionaron datos para procesar el registro del atleta.');
        }

        // 2. Asignación y saneamiento de atributos básicos
        $this->id            = $datos['id'] ?? null;
        $this->fecha_nac     = $datos['fecha_nac'] ?? '';
        $this->genero        = $datos['genero'] ?? '';
        $this->categoria  = $datos['categoria'] ?? null;
        $this->posicion      = $datos['posicion'] ?? null;

        // Atributos con formato de Título (Standard de tu proyecto)
        $this->nombre   = mb_convert_case(trim($datos['nombre'] ?? ''), MB_CASE_TITLE, "UTF-8");
        $this->apellido = mb_convert_case(trim($datos['apellido'] ?? ''), MB_CASE_TITLE, "UTF-8");

        // 3. Mapeo de campos condicionales (enviados según la lógica del controlador)
        $this->representante = $datos['representante'] ?? null;
        $this->doc_identidad = isset($datos['doc_identidad']) ? trim($datos['doc_identidad']) : null;
        $this->telefono = isset($datos['telefono']) ? trim($datos['telefono']) : null;
        $this->direccion     = isset($datos['direccion']) ?
            mb_convert_case(trim($datos['direccion']), MB_CASE_TITLE, "UTF-8") : null;

        // 4. Gestión de la Foto
        // Como envías $datos['foto'] = [$foto_nombre], extraemos el string
        if (isset($datos['foto']) && is_array($datos['foto'])) {
            $this->foto = $datos['foto'][0];
        } else {
            $this->foto = $datos['foto'] ?? 'default.png';
        }

        // 5. Ejecución de la acción vía Match
        $accion = $datos['accion'] ?? null;

        return match ($accion) {
            'incluir'   => $this->Incluir(),
            'modificar' => $this->Modificar(),
            'eliminar'  => $this->Eliminar(),
            'buscar'    => $this->Buscar(),
            default     => throw new Exception('La acción solicitada para el atleta no es válida.')
        };
    }
    public function Consultar(array $filtro = []): array
    {
        try {
            $conex = $this->conex();
            $params = [];

            // 1. Base de la consulta (Dejamos el WHERE abierto para los AND)
            $sentencia = "SELECT 
                        a.*, 
                        COALESCE(a.telefono, r.telefono) AS telefono,
                        COALESCE(a.direccion, r.direccion) AS direccion,
                        COALESCE(a.doc_identidad, r.cedula) AS doc_identidad,
                        r.nombre AS nombre_rep,
                        r.apellido AS apellido_rep,
                        r.cedula AS cedula_rep,
                        p.nombre AS nombre_posicion,
                        p.abreviatura AS abrev_posicion,
                        c.nombre AS nombre_categoria,
                        c.edad_min,
                        c.edad_max
                    FROM atletas a
                    LEFT JOIN representantes r ON a.id_representante = r.id_representante
                    LEFT JOIN posiciones p ON a.id_posicion = p.id_posicion
                    LEFT JOIN categorias c ON a.id_categoria = c.id_categorias
                    WHERE 1=1";

            // 2. BUSCADOR GENERAL (Filtro dinámico para el keyup)
            if (!empty($filtro['filtro'])) {
                $p = "%" . $filtro['filtro'] . "%";
                // NOTA: Asegúrate de si en tu BD es 'nombre' o 'nombres' (igual con apellido)
                $sentencia .= " AND (
                a.doc_identidad LIKE :f1 OR 
                a.nombres LIKE :f2 OR 
                a.apellidos LIKE :f3 OR 
                r.cedula LIKE :f4 OR
                p.nombre LIKE :f5 OR
                c.nombre LIKE :f6
            )";
                $params[':f1'] = $p;
                $params[':f2'] = $p;
                $params[':f3'] = $p;
                $params[':f4'] = $p;
                $params[':f5'] = $p;
                $params[':f6'] = $p;
            }

            // 3. FILTROS ESPECÍFICOS
            if (!empty($this->doc_identidad)) {
                $sentencia .= " AND a.doc_identidad = :doc_i";
                $params[':doc_i'] = $this->doc_identidad;
            }

            // CORREGIDO: En tu ProcesarDatos usas $this->categoria, no $this->id_categoria
            if (!empty($this->categoria)) {
                $sentencia .= " AND a.id_categoria = :id_cat";
                $params[':id_cat'] = $this->categoria;
            }

            // 4. UNIFICACIÓN DEL ORDENAMIENTO (Siempre al final de todo)
            // Primero estatus 1, luego por la edad mínima de la categoría, luego alfabético
            $sentencia .= " ORDER BY 
                        CASE WHEN a.estatus = 1 THEN 0 ELSE 1 END ASC";

            $stmt = $conex->prepare($sentencia);
            $stmt->execute($params);
            $datos = $stmt->fetchAll();

            return array('accion' => 'consultar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Atletas', $e->getMessage(), 'Modelo_Consultar_Completo');
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
            if (!$this->verificarExistencia('posicion', $this->posicion, 'posiciones', NULL)) {
                throw new Exception(INVALID_ID . '0');
            }
            if ($this->representante !== null) {
                if (!$this->verificarExistencia('representante', $this->representante, 'representantes', NULL)) {
                    throw new Exception(INVALID_ID . '1');
                }
            }
            $conex = $this->conex();
            $conex->beginTransaction();

            // 1. Verificaciones de duplicados (Cédula y Teléfono)
            if ($this->doc_identidad !== null && $this->doc_identidad !== '') {
                if ($this->verificarExistencia('cedula', $this->doc_identidad, 'atletas', NULL, bloquear: true)) {
                    throw new Exception(DUPLICATE_CEDULA);
                }
            }
            if ($this->telefono !== null && $this->telefono !== '') {
                if ($this->verificarExistencia('telefono', $this->telefono, 'atletas', NULL, bloquear: true)) {
                    throw new Exception(DUPLICATE_PHONE);
                }
            }

            // 2. Definición de partes de la consulta
            $columnas = [];
            $marcadores = [];

            // --- DATOS OBLIGATORIOS ---
            // Estos siempre se incluyen según la lógica de tu controlador
            $columnas[] = "nombres";
            $marcadores[] = ":nombre";
            $columnas[] = "apellidos";
            $marcadores[] = ":apellido";
            $columnas[] = "fecha_nac";
            $marcadores[] = ":fecha_nac";
            $columnas[] = "id_categoria";
            $marcadores[] = ":id_categoria";
            $columnas[] = "id_posicion";
            $marcadores[] = ":id_posicion";
            $columnas[] = "genero";
            $marcadores[] = ":genero";
            $columnas[] = "foto";
            $marcadores[] = ":foto";

            // --- DATOS OPCIONALES ---
            // Se agregan a la consulta solo si no son nulos
            if ($this->doc_identidad !== null) {
                $columnas[] = "doc_identidad";
                $marcadores[] = ":doc_identidad";
            }
            if ($this->telefono !== null && $this->telefono !== '') {
                $columnas[] = "telefono";
                $marcadores[] = ":telefono";
            }
            if ($this->direccion !== null && $this->direccion !== '') {
                $columnas[] = "direccion";
                $marcadores[] = ":direccion";
            }
            if ($this->representante !== null && $this->representante !== '0') {
                $columnas[] = "id_representante";
                $marcadores[] = ":id_representante";
            }

            // 3. Preparación de la sentencia SQL
            $sql = "INSERT INTO atletas (" . implode(", ", $columnas) . ") 
                VALUES (" . implode(", ", $marcadores) . ")";

            $stmt = $conex->prepare($sql);

            // 4. Vinculación de valores con bindValue (Evita Inyección SQL)
            // Vinculación de obligatorios
            $stmt->bindValue(':nombre', $this->nombre);
            $stmt->bindValue(':apellido', $this->apellido);
            $stmt->bindValue(':fecha_nac', $this->fecha_nac);
            $stmt->bindValue(':id_categoria', $this->categoria, \PDO::PARAM_INT);
            $stmt->bindValue(':id_posicion', $this->posicion, \PDO::PARAM_INT);
            $stmt->bindValue(':genero', $this->genero);
            $stmt->bindValue(':foto', $this->foto);

            // Vinculación de opcionales (con la misma lógica de existencia)
            if ($this->doc_identidad !== null) {
                $stmt->bindValue(':doc_identidad', $this->doc_identidad);
            }
            if ($this->telefono !== null && $this->telefono !== '') {
                $stmt->bindValue(':telefono', $this->telefono);
            }
            if ($this->direccion !== null && $this->direccion !== '') {
                $stmt->bindValue(':direccion', $this->direccion);
            }
            if ($this->representante !== null && $this->representante !== '0') {
                $stmt->bindValue(':id_representante', $this->representante, \PDO::PARAM_INT);
            }

            $stmt->execute();

            $conex->commit();
            return array('accion' => 'exito');
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            logs('Atletas', $e->getMessage(), 'Modelo_Incluir');
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
            if (!$this->verificarExistencia('posicion', $this->posicion, 'posiciones', NULL)) {
                throw new Exception(INVALID_ID . '0');
            }
            if ($this->representante !== null) {
                if (!$this->verificarExistencia('representante', $this->representante, 'representantes', NULL)) {
                    throw new Exception(INVALID_ID . '1');
                }
            }
            $conex = $this->conex();
            $conex->beginTransaction();

            if ($this->doc_identidad !== null && $this->doc_identidad !== '') {
                if (!$this->verificarExistenciaPropia('cedula', $this->doc_identidad, $this->id, 'atletas', NULL, bloquear: true)) {
                    if ($this->verificarExistencia('cedula', $this->doc_identidad, 'atletas', NULL, bloquear: true)) {
                        throw new Exception(DUPLICATE_CEDULA);
                    }
                }
            }
            if ($this->telefono !== null && $this->telefono !== '') {
                if (!$this->verificarExistenciaPropia('telefono', $this->telefono, $this->id, 'atletas', NULL, bloquear: true)) {
                    if ($this->verificarExistencia('telefono', $this->telefono, 'atletas', NULL, bloquear: true)) {
                        throw new Exception(DUPLICATE_PHONE);
                    }
                }
            }
            $campos = [];

            // --- DATOS OBLIGATORIOS ---
            $campos[] = "nombres = :nombre";
            $campos[] = "apellidos = :apellido";
            $campos[] = "fecha_nac = :fecha_nac";
            $campos[] = "id_categoria = :id_categoria";
            $campos[] = "id_posicion = :id_posicion";
            $campos[] = "genero = :genero";
            $campos[] = "foto = :foto";
            $campos[] = "estatus = 1";

            // --- DATOS OPCIONALES ---
            // Manejo dinámico idéntico a Incluir()
            if ($this->doc_identidad !== null) {
                $campos[] = "doc_identidad = :doc_identidad";
            }
            if ($this->telefono !== null && $this->telefono !== '') {
                $campos[] = "telefono = :telefono";
            }
            if ($this->direccion !== null && $this->direccion !== '') {
                $campos[] = "direccion = :direccion";
            }
            if ($this->representante !== null && $this->representante !== '0') {
                $campos[] = "id_representante = :id_representante";
            }
            $sql = "UPDATE atletas SET " . implode(", ", $campos) . " WHERE id_atleta = :id";
            $stmt = $conex->prepare($sql);

            $stmt->bindValue(':id', $this->id, \PDO::PARAM_INT);

            $stmt->bindValue(':nombre', $this->nombre);
            $stmt->bindValue(':apellido', $this->apellido);
            $stmt->bindValue(':fecha_nac', $this->fecha_nac);
            $stmt->bindValue(':id_categoria', $this->categoria, \PDO::PARAM_INT);
            $stmt->bindValue(':id_posicion', $this->posicion, \PDO::PARAM_INT);
            $stmt->bindValue(':genero', $this->genero);
            $stmt->bindValue(':foto', $this->foto);

            if ($this->doc_identidad !== null) {
                $stmt->bindValue(':doc_identidad', $this->doc_identidad);
            }
            if ($this->telefono !== null && $this->telefono !== '') {
                $stmt->bindValue(':telefono', $this->telefono);
            }
            if ($this->direccion !== null && $this->direccion !== '') {
                $stmt->bindValue(':direccion', $this->direccion);
            }
            if ($this->representante !== null && $this->representante !== '0') {
                $stmt->bindValue(':id_representante', $this->representante, \PDO::PARAM_INT);
            }

            $stmt->execute();

            $conex->commit();
            return array('accion' => 'exito');
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            logs('Atletas', $e->getMessage(), 'Modelo_Incluir');
            return array('accion' => 'error', 'codigo' => $e->getMessage());
        } finally {
            $conex = null;
        }
    }

    private function Buscar()
    {
        try {
            $conex = $this->conex();
            $sentencia = "SELECT * FROM atletas WHERE id_atleta = :id";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            return array('accion' => 'buscar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Atletas', $e->getMessage(), 'Modelo');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    public function ConsultarCumple()
    {
        try {
            $conex = $this->conex();
            $sql = "SELECT nombres, apellidos FROM atletas 
                WHERE MONTH(fecha_nac) = MONTH(NOW()) 
                AND DAY(fecha_nac) = DAY(NOW())";
            return $conex->query($sql)->fetchAll();
        } catch (Exception $e) {
            logs('Atletas', $e->getMessage(), 'Modelo_ConsultarCumple');
            return [];
        }
    }

    private function Eliminar(): array
    {
        try {
            $conex = null;
            $conex = $this->conex();
            $conex->beginTransaction();
            if (!$this->verificarExistencia('id', $this->id, 'atletas', NULL, bloquear: true)) {
                throw new Exception(INVALID_ID);
            }

            $sql = "UPDATE `atletas` SET `estatus`= 0 WHERE id_atleta = :id";
            $stmt = $conex->prepare($sql);
            $stmt->bindValue(':id', $this->id, \PDO::PARAM_INT);
            $stmt->execute();

            $conex->commit();
            return ['accion' => 'exito'];
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            logs('Atletas', $e->getMessage(), 'Modelo_Eliminar');
            return ['accion' => 'error', 'codigo' => $e->getMessage()];
        } finally {
            $conex = null;
        }
    }
}
