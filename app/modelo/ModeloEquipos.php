<?php

namespace App\modelo;

use PDO;
use Exception;

class ModeloEquipos extends Conexion
{
    private ?int $id = null;
    private ?string $nombre = null;
    private ?array $atletas = null; // <- Añadido para capturar los atletas desde el JS

    public function __construct()
    {
        parent::__construct();
        // Alineado con la estructura real de la tabla 'equipos'
        $this->campoWhitelist = [
            'id'     => 'codigo_equipo',
            'nombre' => 'nombre'
        ];
        $this->llavePrimaria = 'codigo_equipo';
    }

    public function ProcesarDatos(array $datos): array
    {
        if (empty($datos)) {
            throw new Exception('No se proporcionaron datos para procesar el registro del equipo.');
        }

        $this->ValidarExpresiones($datos);

        $this->id      = isset($datos['id']) ? (int)$datos['id'] : null;
        $this->nombre  = isset($datos['nombre']) ? mb_convert_case(trim($datos['nombre']), MB_CASE_TITLE, "UTF-8") : null;
        $this->atletas = isset($datos['atletas']) ? (array)$datos['atletas'] : []; // <- Captura el array atletas[] del FormData

        return match ($datos['accion'] ?? null) {
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
            
            // Los equipos ya no tienen id_categoria, solo consultamos sus datos básicos
            $sentencia = "SELECT e.codigo_equipo AS id_equipos, e.nombre, 
                                 (SELECT COUNT(DISTINCT de.codigo_atleta) FROM detalles_equipos de WHERE de.codigo_equipo = e.codigo_equipo) AS cantidad_atletas 
                          FROM equipos e 
                          WHERE 1=1";

            if (!empty($filtro['filtro'])) {
                $p = "%" . trim($filtro['filtro']) . "%";
                $sentencia .= " AND e.nombre LIKE :f1";
                $params[':f1'] = $p;
            }

            $stmt = $conex->prepare($sentencia);
            $stmt->execute($params);
            
            return ['accion' => 'consultar', 'datos' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (Exception $e) {
            logs('Equipos', $e->getMessage(), 'Modelo_Consultar');
            return ['accion' => 'error', 'mensaje' => $e->getMessage()];
        }
    }

    public function ConsultarEquipos(): array
    {
        try {
            $stmt = $this->conex()->query("SELECT e.codigo_equipo AS id_equipos, e.nombre, (SELECT COUNT(DISTINCT de.codigo_atleta) FROM detalles_equipos de WHERE de.codigo_equipo = e.codigo_equipo) AS cantidad_atletas FROM equipos e");
            return ['accion' => 'buscar', 'datos' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (Exception $e) {
            logs('Equipos', $e->getMessage(), 'Modelo_ConsultarEquipos');
            return ['accion' => 'error', 'mensaje' => $e->getMessage()];
        }
    }

    public function ConsultarAtletasParaAsignacion(array $filtro = []): array
    {
        try {
            $conex = $this->conex();
            $params = [];

            // Se obtiene la categoría y posición de la tabla inscripciones
            $sentencia = "SELECT a.codigo_atleta AS id_atleta,
                                 MAX(ia.numero_doc) AS doc_identidad,
                                 MAX(a.p_nombre) AS nombres,
                                 MAX(a.p_apellidos) AS apellidos,
                                 MAX(c.nombre) AS nombre_categoria,
                                 MAX(p.nombre) AS nombre_posicion
                          FROM atletas a
                          LEFT JOIN identidad_atleta ia ON a.codigo_atleta = ia.codigo_atleta
                          INNER JOIN inscripciones i ON a.codigo_atleta = i.codigo_atleta
                          INNER JOIN categorias c ON c.codigo_categoria = i.codigo_categoria
                          INNER JOIN posiciones p ON p.codigo_posicion = i.codigo_posicion
                          WHERE i.estatus = 1"; // Solo consideramos inscripciones activas

            if (!empty($filtro['filtro'])) {
                $p = '%' . trim($filtro['filtro']) . '%';
                $sentencia .= " AND (ia.numero_doc LIKE :f1 OR a.p_nombre LIKE :f2 OR a.p_apellidos LIKE :f3)";
                $params[':f1'] = $p;
                $params[':f2'] = $p;
                $params[':f3'] = $p;
            }

            $sentencia .= " GROUP BY a.codigo_atleta ORDER BY apellidos ASC, nombres ASC";
            
            $stmt = $conex->prepare($sentencia);
            $stmt->execute($params);

            $normalizados = array_map(fn($row) => [
                'id'        => (int)($row['id_atleta'] ?? 0),
                'doc_i'     => $row['doc_identidad'] ?? '',
                'nombre'    => trim(($row['nombres'] ?? '') . ' ' . ($row['apellidos'] ?? '')),
                'categoria' => $row['nombre_categoria'] ?? '',
                'posicion'  => $row['nombre_posicion'] ?? ''
            ], $stmt->fetchAll(PDO::FETCH_ASSOC));

            return ['accion' => 'consultarAtletasModal', 'datos' => $normalizados];
        } catch (Exception $e) {
            logs('Equipos', $e->getMessage(), 'Modelo_ConsultarAtletasParaAsignacion');
            return ['accion' => 'error', 'mensaje' => $e->getMessage()];
        }
    }

    public function ConsultarAtletasAsignadosEquipo(int $idEquipo): array
    {
        try {
            $sentencia = "SELECT a.codigo_atleta AS id_atleta,
                                 MAX(ia.numero_doc) AS doc_identidad,
                                 MAX(a.p_nombre) AS nombres,
                                 MAX(a.p_apellidos) AS apellidos,
                                 MAX(c.nombre) AS nombre_categoria,
                                 MAX(p.nombre) AS nombre_posicion
                          FROM detalles_equipos de
                          INNER JOIN atletas a ON a.codigo_atleta = de.codigo_atleta
                          LEFT JOIN identidad_atleta ia ON a.codigo_atleta = de.codigo_atleta
                          INNER JOIN inscripciones i ON a.codigo_atleta = i.codigo_atleta
                          INNER JOIN categorias c ON c.codigo_categoria = i.codigo_categoria
                          INNER JOIN posiciones p ON p.codigo_posicion = i.codigo_posicion
                          WHERE de.codigo_equipo = :idEquipo AND i.estatus = 1
                          GROUP BY a.codigo_atleta";

            $stmt = $this->conex()->prepare($sentencia);
            $stmt->bindValue(':idEquipo', $idEquipo, PDO::PARAM_INT);
            $stmt->execute();

            $normalizados = array_map(fn($row) => [
                'id'        => (int)($row['id_atleta'] ?? 0),
                'doc_i'     => $row['doc_identidad'] ?? '',
                'nombre'    => trim(($row['nombres'] ?? '') . ' ' . ($row['apellidos'] ?? '')),
                'categoria' => $row['nombre_categoria'] ?? '',
                'posicion'  => $row['nombre_posicion'] ?? ''
            ], $stmt->fetchAll(PDO::FETCH_ASSOC));

            return ['accion' => 'consultarAtletasAsignadosEquipo', 'datos' => $normalizados];
        } catch (Exception $e) {
            logs('Equipos', $e->getMessage(), 'Modelo_ConsultarAtletasAsignadosEquipo');
            return ['accion' => 'error', 'mensaje' => $e->getMessage(), 'datos' => []];
        }
    }

    public function GuardarDetallesEquipo(int $idEquipo, array $idsAtleta): void
    {
        if ($idEquipo <= 0) {
            throw new Exception('ID de equipo inválido.');
        }

        $idsAtleta = array_values(array_filter(array_map('intval', $idsAtleta)));

        $conex = null;
        try {
            $conex = $this->conex();
            
            // Detectamos si ya hay una transacción activa (viniendo de Incluir o Modificar)
            // para evitar errores de transacciones anidadas en PDO.
            $isNested = $conex->inTransaction();
            if (!$isNested) $conex->beginTransaction();

            // === 1. OBTENER RANKING GLOBAL DE CATEGORÍAS ===
            $stmtRank = $conex->query("SELECT codigo_categoria FROM categorias ORDER BY edad_max DESC");
            $rankingCategorias = $stmtRank->fetchAll(PDO::FETCH_COLUMN, 0);

            // Validar reglas solo si se envían atletas
            if (!empty($idsAtleta)) {
                // === 2. OBTENER LAS CATEGORÍAS DE LOS ATLETAS QUE SE VAN A ASIGNAR ===
                $placeholders = implode(',', array_fill(0, count($idsAtleta), '?'));
                $sqlAtletasCat = "SELECT DISTINCT i.codigo_categoria 
                                  FROM atletas a
                                  INNER JOIN inscripciones i ON a.codigo_atleta = i.codigo_atleta
                                  WHERE a.codigo_atleta IN ($placeholders) AND i.estatus = 1";
                
                $stmtAtletasCat = $conex->prepare($sqlAtletasCat);
                foreach ($idsAtleta as $i => $idA) {
                    $stmtAtletasCat->bindValue($i + 1, $idA, PDO::PARAM_INT);
                }
                $stmtAtletasCat->execute();
                $catsSeleccionadas = $stmtAtletasCat->fetchAll(PDO::FETCH_COLUMN, 0);

                // === 3. VALIDACIÓN DE REGLAS DE NEGOCIO ===
                $cantidadCats = count($catsSeleccionadas);
                
                if ($cantidadCats === 0) {
                    throw new Exception('No se pudo determinar la categoría de los atletas (verifique que tengan inscripciones activas).');
                }

                if ($cantidadCats > 2) {
                    throw new Exception('Un equipo no puede tener atletas de más de 2 categorías distintas.');
                }

                if ($cantidadCats === 2) {
                    $idx1 = array_search($catsSeleccionadas[0], $rankingCategorias);
                    $idx2 = array_search($catsSeleccionadas[1], $rankingCategorias);

                    if ($idx1 === false || $idx2 === false) {
                        throw new Exception('Una de las categorías de los atletas no existe en el sistema.');
                    }

                    if (abs($idx1 - $idx2) > 1) {
                        throw new Exception('El equipo solo puede conformarse por atletas de la misma categoría o de una categoría inferior inmediata.');
                    }
                }
            }

            // === 4. PROCESAR GUARDADO ===
            $stmtDel = $conex->prepare('DELETE FROM detalles_equipos WHERE codigo_equipo = :codigo_equipo');
            $stmtDel->bindValue(':codigo_equipo', $idEquipo, PDO::PARAM_INT);
            $stmtDel->execute();

            if (!empty($idsAtleta)) {
                $stmtIns = $conex->prepare('INSERT INTO detalles_equipos (codigo_equipo, codigo_atleta) VALUES (:codigo_equipo, :codigo_atleta)');
                foreach ($idsAtleta as $idAtleta) {
                    $stmtIns->bindValue(':codigo_equipo', $idEquipo, PDO::PARAM_INT);
                    $stmtIns->bindValue(':codigo_atleta', $idAtleta, PDO::PARAM_INT);
                    $stmtIns->execute();
                }
            }

            if (!$isNested) $conex->commit();
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction() && !$isNested) $conex->rollBack();
            throw $e;
        }
    }

    private function Incluir(): array
    {
        $conex = null;
        try {
            if (!empty($this->nombre) && $this->verificarExistencia('nombre', $this->nombre, 'equipos', null, bloquear: true)) {
                throw new Exception(DUPLICATE_NAME);
            }

            $conex = $this->conex();
            $conex->beginTransaction();

            $sql = "INSERT INTO equipos (nombre) VALUES (:nombre)";
            $stmt = $conex->prepare($sql);
            $stmt->bindValue(':nombre', $this->nombre);
            $stmt->execute();

            $idEquipo = (int)$conex->lastInsertId();
            
            // === SOLUCIÓN: Registrar los atletas seleccionados en la tabla pivote ===
            $this->GuardarDetallesEquipo($idEquipo, $this->atletas);

            $conex->commit();

            return ['accion' => 'incluir', 'mensaje' => 'Equipo registrado correctamente.'];
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) $conex->rollBack();
            logs('Equipos', $e->getMessage(), 'Modelo_Incluir');
            return ['accion' => 'error', 'mensaje' => $e->getMessage()];
        }
    }

    private function Modificar(): array
    {
        $conex = null;
        try {
            if (!$this->verificarExistenciaPropia('nombre', $this->nombre, $this->id, 'equipos', null, bloquear: true)) {
                if ($this->verificarExistencia('nombre', $this->nombre, 'equipos', null, bloquear: true)) {
                    throw new Exception(DUPLICATE_NAME);
                }
            }

            $conex = $this->conex();
            $conex->beginTransaction();

            $sql = "UPDATE equipos SET nombre = :nombre WHERE codigo_equipo = :id";
            $stmt = $conex->prepare($sql);
            $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);
            $stmt->bindValue(':nombre', $this->nombre);
            $stmt->execute();

            // === SOLUCIÓN: Actualizar los atletas asignados (limpia viejos, pone nuevos) ===
            $this->GuardarDetallesEquipo($this->id, $this->atletas);

            $conex->commit();
            return ['accion' => 'modificar', 'mensaje' => 'Equipo modificado correctamente.'];
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) $conex->rollBack();
            logs('Equipos', $e->getMessage(), 'Modelo_Modificar');
            return ['accion' => 'error', 'mensaje' => $e->getMessage()];
        }
    }

    private function Buscar(): array
    {
        try {
            $sentencia = "SELECT codigo_equipo AS id_equipos, nombre 
                          FROM equipos 
                          WHERE codigo_equipo = :id";

            $stmt = $this->conex()->prepare($sentencia);
            $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);
            $stmt->execute();
            
            return ['accion' => 'buscar', 'datos' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (Exception $e) {
            logs('Equipos', $e->getMessage(), 'Modelo_Buscar');
            return ['accion' => 'error', 'mensaje' => $e->getMessage()];
        }
    }

    private function Eliminar(): array
    {
        $conex = null;
        try {
            $conex = $this->conex();
            $conex->beginTransaction();

            if (!$this->verificarExistencia('id', $this->id, 'equipos', null, bloquear: true)) {
                throw new Exception(INVALID_ID);
            }
            
            if ($this->verificarExistencia('id', $this->id, 'participaciones', null, bloquear: true)) {
                throw new Exception(ASSOCIATES);
            }

            $stmtDel = $conex->prepare("DELETE FROM detalles_equipos WHERE codigo_equipo = :id");
            $stmtDel->bindValue(':id', $this->id, PDO::PARAM_INT);
            $stmtDel->execute();

            $stmt = $conex->prepare("DELETE FROM equipos WHERE codigo_equipo = :id");
            $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);
            $stmt->execute();

            $conex->commit();
            return ['accion' => 'eliminar', 'mensaje' => 'Equipo eliminado correctamente.'];
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) $conex->rollBack();
            logs('Equipos', $e->getMessage(), 'Modelo_Eliminar');
            return ['accion' => 'error', 'mensaje' => $e->getMessage()];
        }
    }

    private function ValidarExpresiones(array $datos): void
    {
        if (!empty($datos['id']) && filter_var($datos['id'], FILTER_VALIDATE_INT) === false) {
            throw new Exception('Id inválido.');
        }
        if (!empty($datos['nombre']) && !preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s]{3,30}$/u', $datos['nombre'])) {
            throw new Exception('Nombre inválido.');
        }
    }
}