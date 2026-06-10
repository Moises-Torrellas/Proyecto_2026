<?php

namespace App\modelo;

use Exception;
use PDO;

class ModeloPalmares extends ModeloBase
{
    private $id;
    private $id_torneo;
    private $id_atleta;
    private $id_equipo;
    private $id_premio;
    private $tipo_palmares;
    private $fecha_registro;

    private $modeloParticipaciones;
    private $modeloHistorial;
    private $modeloPremios;

    public function __construct()
    {
        parent::__construct();
        $this->campoWhitelist = [
            'id'          => 'id_palmares',
            'id_torneo'   => 'id_torneo',
            'id_atleta'   => 'id_atleta',
            'id_equipo'   => 'id_equipos',
            'id_premio'   => 'id_premio',
        ];
        $this->llavePrimaria = 'id_palmares';
    }

    public function setModeloParticipaciones($modelo): void
    {
        $this->modeloParticipaciones = $modelo;
    }

    public function setModeloHistorial($modelo): void
    {
        $this->modeloHistorial = $modelo;
    }

    public function setModeloPremios($modelo): void
    {
        $this->modeloPremios = $modelo;
    }

    public function ProcesarDatos(array $datos): array
    {
        if (empty($datos)) {
            throw new Exception('No se proporcionaron datos para procesar.');
        }

        $this->id            = $datos['id'] ?? null;
        $this->id_torneo     = $datos['torneo'] ?? null;
        $this->id_atleta     = $datos['atleta'] ?? null;
        $this->id_equipo     = $datos['equipo'] ?? null;
        $this->id_premio     = $datos['premio'] ?? null;
        $this->tipo_palmares = $datos['tipo_palmares'] ?? null;
        $this->fecha_registro = date('Y-m-d');

        $accion = $datos['accion'] ?? null;

        return match ($accion) {
            'incluir'   => $this->Incluir(),
            'modificar' => $this->Modificar(),
            'eliminar'  => $this->Eliminar(),
            default => throw new Exception('La acción solicitada para el palmarés no es válida.')
        };
    }

    /**
     * Consulta individual agrupada por atleta
     */
    public function ConsultarIndividual(array $filtro = []): array
    {
        try {
            $conex = $this->conex();
            $params = [];

            $sentencia = "SELECT 
                    pi.id_individual,
                    pi.id_palmares,
                    pi.id_premio,
                    pi.id_atleta,
                    a.nombres AS atleta_nombres,
                    a.apellidos AS atleta_apellidos,
                    a.foto AS atleta_foto,
                    p.nombre AS nombre_premio,
                    p.tipo AS tipo_premio,
                    t.id_torneo,
                    t.nombre AS nombre_torneo,
                    t.fecha_inicio AS fecha_torneo,
                    pal.fecha_registro,
                    CASE WHEN hpi.id_his_ind IS NOT NULL THEN 1 ELSE 0 END AS en_historial
                FROM palmares_individual pi
                INNER JOIN palmares pal ON pi.id_palmares = pal.id_palmares
                INNER JOIN torneos t ON pal.id_torneo = t.id_torneo
                INNER JOIN premios p ON pi.id_premio = p.id_premio
                INNER JOIN atletas a ON pi.id_atleta = a.id_atleta
                LEFT JOIN historial_p_ind hpi ON hpi.id_p_ind = pi.id_individual
                WHERE 1=1";

            if (!empty($filtro['filtro'])) {
                $f = "%" . trim($filtro['filtro']) . "%";
                $sentencia .= " AND (
                    a.nombres LIKE :f1 OR 
                    a.apellidos LIKE :f2 OR 
                    p.nombre LIKE :f3 OR
                    t.nombre LIKE :f4
                )";
                $params[':f1'] = $f;
                $params[':f2'] = $f;
                $params[':f3'] = $f;
                $params[':f4'] = $f;
            }

            $sentencia .= " ORDER BY a.apellidos ASC, a.nombres ASC, t.fecha_inicio DESC";

            $stmt = $conex->prepare($sentencia);
            $stmt->execute($params);
            $filas = $stmt->fetchAll();

            $datos = $this->agruparPalmaresIndividual($filas);

            return array('accion' => 'consultar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Palmares', $e->getMessage(), 'Modelo_ConsultarIndividual');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    /**
     * Consulta grupal agrupada por equipo
     */
    public function ConsultarGrupal(array $filtro = []): array
    {
        try {
            $conex = $this->conex();
            $params = [];

            $sentencia = "SELECT 
                    pg.id_grupal,
                    pg.id_palmares,
                    pg.id_premio,
                    pg.id_equipo,
                    e.nombre AS nombre_equipo,
                    c.nombre AS nombre_categoria,
                    p.nombre AS nombre_premio,
                    p.tipo AS tipo_premio,
                    t.id_torneo,
                    t.nombre AS nombre_torneo,
                    t.fecha_inicio AS fecha_torneo,
                    pal.fecha_registro,
                    CASE WHEN hpg.id_his_grp IS NOT NULL THEN 1 ELSE 0 END AS en_historial
                FROM palmares_grupal pg
                INNER JOIN palmares pal ON pg.id_palmares = pal.id_palmares
                INNER JOIN torneos t ON pal.id_torneo = t.id_torneo
                INNER JOIN premios p ON pg.id_premio = p.id_premio
                INNER JOIN equipos e ON pg.id_equipo = e.id_equipos
                INNER JOIN categorias c ON e.id_categoria = c.id_categorias
                LEFT JOIN historial_p_grp hpg ON hpg.id_p_grp = pg.id_grupal
                WHERE 1=1";

            if (!empty($filtro['filtro'])) {
                $f = "%" . trim($filtro['filtro']) . "%";
                $sentencia .= " AND (
                    e.nombre LIKE :f1 OR 
                    p.nombre LIKE :f2 OR
                    t.nombre LIKE :f3 OR
                    c.nombre LIKE :f4
                )";
                $params[':f1'] = $f;
                $params[':f2'] = $f;
                $params[':f3'] = $f;
                $params[':f4'] = $f;
            }

            $sentencia .= " ORDER BY e.nombre ASC, t.fecha_inicio DESC";

            $stmt = $conex->prepare($sentencia);
            $stmt->execute($params);
            $filas = $stmt->fetchAll();

            $datos = $this->agruparPalmaresGrupal($filas);

            return array('accion' => 'consultar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Palmares', $e->getMessage(), 'Modelo_ConsultarGrupal');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    /**
     * Agrupa los resultados individuales por atleta
     */
    private function agruparPalmaresIndividual(array $filas): array
    {
        $agrupado = [];
        foreach ($filas as $row) {
            $idAtleta = $row['id_atleta'];
            if (!isset($agrupado[$idAtleta])) {
                $agrupado[$idAtleta] = [
                    'id_atleta'        => $idAtleta,
                    'atleta_nombres'   => $row['atleta_nombres'],
                    'atleta_apellidos' => $row['atleta_apellidos'],
                    'atleta_foto'      => $row['atleta_foto'],
                    'total_premios'    => 0,
                    'premios'          => []
                ];
            }
            $agrupado[$idAtleta]['total_premios']++;
            $agrupado[$idAtleta]['premios'][] = [
                'id_individual'  => $row['id_individual'],
                'id_palmares'    => $row['id_palmares'],
                'id_premio'      => $row['id_premio'],
                'nombre_premio'  => $row['nombre_premio'],
                'id_torneo'      => $row['id_torneo'],
                'nombre_torneo'  => $row['nombre_torneo'],
                'fecha_torneo'   => $row['fecha_torneo'],
                'fecha_registro' => $row['fecha_registro'],
                'en_historial'   => (int)$row['en_historial']
            ];
        }
        return array_values($agrupado);
    }

    /**
     * Agrupa los resultados grupales por equipo
     */
    private function agruparPalmaresGrupal(array $filas): array
    {
        $agrupado = [];
        foreach ($filas as $row) {
            $idEquipo = $row['id_equipo'];
            if (!isset($agrupado[$idEquipo])) {
                $agrupado[$idEquipo] = [
                    'id_equipo'        => $idEquipo,
                    'nombre_equipo'    => $row['nombre_equipo'],
                    'nombre_categoria' => $row['nombre_categoria'],
                    'total_premios'    => 0,
                    'premios'          => []
                ];
            }
            $agrupado[$idEquipo]['total_premios']++;
            $agrupado[$idEquipo]['premios'][] = [
                'id_grupal'      => $row['id_grupal'],
                'id_palmares'    => $row['id_palmares'],
                'id_premio'      => $row['id_premio'],
                'nombre_premio'  => $row['nombre_premio'],
                'id_torneo'      => $row['id_torneo'],
                'nombre_torneo'  => $row['nombre_torneo'],
                'fecha_torneo'   => $row['fecha_torneo'],
                'fecha_registro' => $row['fecha_registro'],
                'en_historial'   => (int)$row['en_historial']
            ];
        }
        return array_values($agrupado);
    }

    /**
     * Obtiene o crea el registro padre en la tabla palmares para un torneo
     */
    private function obtenerOCrearPalmares($conex): int
    {
        // Buscar si ya existe un palmares para este torneo
        $stmt = $conex->prepare("SELECT id_palmares FROM palmares WHERE id_torneo = :id_torneo FOR UPDATE");
        $stmt->bindValue(':id_torneo', $this->id_torneo, PDO::PARAM_INT);
        $stmt->execute();
        $resultado = $stmt->fetch();

        if ($resultado) {
            return (int)$resultado['id_palmares'];
        }

        // Si no existe, crear uno nuevo
        $stmt = $conex->prepare("INSERT INTO palmares (id_torneo, fecha_registro) VALUES (:id_torneo, :fecha_registro)");
        $stmt->bindValue(':id_torneo', $this->id_torneo, PDO::PARAM_INT);
        $stmt->bindValue(':fecha_registro', $this->fecha_registro);
        $stmt->execute();

        return (int)$conex->lastInsertId();
    }

    private function Incluir(): array
    {
        $conex = null;
        try {
            if (!$this->modeloParticipaciones || !$this->modeloPremios) {
                throw new Exception('Modelos requeridos no configurados.');
            }

            $conex = $this->conex();
            $conex->beginTransaction();

            // 1. Verificar existencia del torneo
            $stmtTorneo = $conex->prepare("SELECT COUNT(*) FROM torneos WHERE id_torneo = :id");
            $stmtTorneo->bindValue(':id', $this->id_torneo, PDO::PARAM_INT);
            $stmtTorneo->execute();
            if ((int)$stmtTorneo->fetchColumn() === 0) {
                throw new Exception(INVALID_ID);
            }

            // 2. Verificar existencia y validar tipo de premio
            if (!$this->verificarExistencia('id_premio', $this->id_premio, 'premios', null)) {
                throw new Exception(INVALID_ID);
            }
            $tipoEsperado = ($this->tipo_palmares === 'individual') ? 'I' : 'G';
            $this->modeloPremios->validarTipoPremio($this->id_premio, $tipoEsperado);

            // 3. Validar existencia y participación
            if ($this->tipo_palmares === 'individual') {
                if (!$this->verificarExistencia('id_atleta', $this->id_atleta, 'atletas', 1)) {
                    throw new Exception(INVALID_ID);
                }
                if (!$this->modeloParticipaciones->validarParticipacionIndividual($this->id_torneo, $this->id_atleta)) {
                    throw new Exception(VALIDATION);
                }
            } else {
                if (!$this->verificarExistencia('id_equipo', $this->id_equipo, 'equipos', NULL)) {
                    throw new Exception(INVALID_ID);
                }
                if (!$this->modeloParticipaciones->validarParticipacionGrupal($this->id_torneo, $this->id_equipo)) {
                    throw new Exception(VALIDATION);
                }
            }

            $idElemento = ($this->tipo_palmares === 'individual') ? $this->id_atleta : $this->id_equipo;

            if ($this->verificarDuplicado($conex, $this->id_torneo, $idElemento, $this->id_premio, ($this->tipo_palmares === 'individual'))) {
                throw new Exception(DUPLICATE);
            }

            // 4. Obtener o crear registro palmares padre
            $id_palmares = $this->obtenerOCrearPalmares($conex);

            // 5. Insertar en tabla específica según tipo
            if ($this->tipo_palmares === 'individual') {
                $stmt = $conex->prepare(
                    "INSERT INTO palmares_individual (id_palmares, id_premio, id_atleta) 
                     VALUES (:id_palmares, :id_premio, :id_atleta)"
                );
                $stmt->bindValue(':id_palmares', $id_palmares, PDO::PARAM_INT);
                $stmt->bindValue(':id_premio', $this->id_premio, PDO::PARAM_INT);
                $stmt->bindValue(':id_atleta', $this->id_atleta, PDO::PARAM_INT);
            } else {
                $stmt = $conex->prepare(
                    "INSERT INTO palmares_grupal (id_palmares, id_premio, id_equipo) 
                     VALUES (:id_palmares, :id_premio, :id_equipo)"
                );
                $stmt->bindValue(':id_palmares', $id_palmares, PDO::PARAM_INT);
                $stmt->bindValue(':id_premio', $this->id_premio, PDO::PARAM_INT);
                $stmt->bindValue(':id_equipo', $this->id_equipo, PDO::PARAM_INT);
            }

            $stmt->execute();
            $conex->commit();
            return ['accion' => 'exito'];
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            logs('Palmares', $e->getMessage(), 'Modelo_Incluir');
            return ['accion' => 'error', 'codigo' => $e->getMessage()];
        } finally {
            $conex = NULL;
        }
    }

    private function Modificar(): array
    {
        $conex = null;
        try {
            if (!$this->modeloParticipaciones || !$this->modeloHistorial || !$this->modeloPremios) {
                throw new Exception('Modelos requeridos no configurados.');
            }

            $conex = $this->conex();
            $conex->beginTransaction();

            if ($this->tipo_palmares === 'individual') {
                $stmtVerif = $conex->prepare("SELECT id_palmares FROM palmares_individual WHERE id_individual = :id FOR UPDATE");
                $stmtVerif->bindValue(':id', $this->id, PDO::PARAM_INT);
                $stmtVerif->execute();
                $registro = $stmtVerif->fetch();

                if (!$registro) {
                    throw new Exception(INVALID_ID);
                }

                if ($this->modeloHistorial->verificarHistorialIndividual($this->id)) {
                    throw new Exception(ASSOCIATES);
                }

                $stmtPal = $conex->prepare("SELECT id_torneo FROM palmares WHERE id_palmares = :id");
                $stmtPal->bindValue(':id', $registro['id_palmares'], PDO::PARAM_INT);
                $stmtPal->execute();
                $palData = $stmtPal->fetch();
                $this->id_torneo = $palData['id_torneo'];

                if (!$this->verificarExistencia('id_premio', $this->id_premio, 'premios', null)) {
                    throw new Exception(INVALID_ID);
                }
                $this->modeloPremios->validarTipoPremio($this->id_premio, 'I');

                if (!$this->verificarExistencia('id_atleta', $this->id_atleta, 'atletas', 1)) {
                    throw new Exception(INVALID_ID);
                }
                if (!$this->modeloParticipaciones->validarParticipacionIndividual($this->id_torneo, $this->id_atleta)) {
                    throw new Exception(VALIDATION);
                }

                $stmt = $conex->prepare(
                    "UPDATE palmares_individual SET id_premio = :id_premio, id_atleta = :id_atleta 
                    WHERE id_individual = :id"
                );
                $stmt->bindValue(':id_premio', $this->id_premio, PDO::PARAM_INT);
                $stmt->bindValue(':id_atleta', $this->id_atleta, PDO::PARAM_INT);
                $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);
            } else {
                $stmtVerif = $conex->prepare("SELECT id_palmares FROM palmares_grupal WHERE id_grupal = :id FOR UPDATE");
                $stmtVerif->bindValue(':id', $this->id, PDO::PARAM_INT);
                $stmtVerif->execute();
                $registro = $stmtVerif->fetch();

                if (!$registro) {
                    throw new Exception(INVALID_ID);
                }

                if ($this->modeloHistorial->verificarHistorialGrupal($this->id)) {
                    throw new Exception(ASSOCIATES);
                }

                $stmtPal = $conex->prepare("SELECT id_torneo FROM palmares WHERE id_palmares = :id");
                $stmtPal->bindValue(':id', $registro['id_palmares'], PDO::PARAM_INT);
                $stmtPal->execute();
                $palData = $stmtPal->fetch();
                $this->id_torneo = $palData['id_torneo'];

                if (!$this->verificarExistencia('id_premio', $this->id_premio, 'premios', null)) {
                    throw new Exception(INVALID_ID);
                }
                $this->modeloPremios->validarTipoPremio($this->id_premio, 'G');

                if (!$this->verificarExistencia('id_equipo', $this->id_equipo, 'equipos', 1)) {
                    throw new Exception(INVALID_ID);
                }
                if (!$this->modeloParticipaciones->validarParticipacionGrupal($this->id_torneo, $this->id_equipo)) {
                    throw new Exception(VALIDATION);
                }

                $stmt = $conex->prepare(
                    "UPDATE palmares_grupal SET id_premio = :id_premio, id_equipo = :id_equipo 
                    WHERE id_grupal = :id"
                );
                $stmt->bindValue(':id_premio', $this->id_premio, PDO::PARAM_INT);
                $stmt->bindValue(':id_equipo', $this->id_equipo, PDO::PARAM_INT);
                $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);
            }

            $stmt->execute();
            $conex->commit();
            return ['accion' => 'exito'];
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            logs('Palmares', $e->getMessage(), 'Modelo_Modificar');
            return ['accion' => 'error', 'codigo' => $e->getMessage()];
        } finally {
            $conex = NULL;
        }
    }

    private function Eliminar(): array
    {
        $conex = null;
        try {
            if (!$this->modeloHistorial) {
                throw new Exception('ModeloHistorial no configurado.');
            }

            $conex = $this->conex();
            $conex->beginTransaction();

            if ($this->tipo_palmares === 'individual') {
                $stmtVerif = $conex->prepare("SELECT id_palmares FROM palmares_individual WHERE id_individual = :id FOR UPDATE");
                $stmtVerif->bindValue(':id', $this->id, PDO::PARAM_INT);
                $stmtVerif->execute();
                $registro = $stmtVerif->fetch();

                if (!$registro) {
                    throw new Exception(INVALID_ID);
                }

                if ($this->modeloHistorial->verificarHistorialIndividual($this->id)) {
                    throw new Exception(ASSOCIATES);
                }

                $id_palmares = $registro['id_palmares'];

                $stmtDel = $conex->prepare("DELETE FROM palmares_individual WHERE id_individual = :id");
                $stmtDel->bindValue(':id', $this->id, PDO::PARAM_INT);
                $stmtDel->execute();
            } else {
                $stmtVerif = $conex->prepare("SELECT id_palmares FROM palmares_grupal WHERE id_grupal = :id FOR UPDATE");
                $stmtVerif->bindValue(':id', $this->id, PDO::PARAM_INT);
                $stmtVerif->execute();
                $registro = $stmtVerif->fetch();

                if (!$registro) {
                    throw new Exception(INVALID_ID);
                }

                if ($this->modeloHistorial->verificarHistorialGrupal($this->id)) {
                    throw new Exception(ASSOCIATES);
                }

                $id_palmares = $registro['id_palmares'];

                $stmtDel = $conex->prepare("DELETE FROM palmares_grupal WHERE id_grupal = :id");
                $stmtDel->bindValue(':id', $this->id, PDO::PARAM_INT);
                $stmtDel->execute();
            }

            $stmtCheckInd = $conex->prepare("SELECT COUNT(*) FROM palmares_individual WHERE id_palmares = :id");
            $stmtCheckInd->bindValue(':id', $id_palmares, PDO::PARAM_INT);
            $stmtCheckInd->execute();
            $countInd = (int)$stmtCheckInd->fetchColumn();

            $stmtCheckGrp = $conex->prepare("SELECT COUNT(*) FROM palmares_grupal WHERE id_palmares = :id");
            $stmtCheckGrp->bindValue(':id', $id_palmares, PDO::PARAM_INT);
            $stmtCheckGrp->execute();
            $countGrp = (int)$stmtCheckGrp->fetchColumn();

            if ($countInd === 0 && $countGrp === 0) {
                $stmtDelPal = $conex->prepare("DELETE FROM palmares WHERE id_palmares = :id");
                $stmtDelPal->bindValue(':id', $id_palmares, PDO::PARAM_INT);
                $stmtDelPal->execute();
            }

            $conex->commit();
            return ['accion' => 'exito'];
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            logs('Palmares', $e->getMessage(), 'Modelo_Eliminar');
            return ['accion' => 'error', 'codigo' => $e->getMessage()];
        } finally {
            $conex = NULL;
        }
    }

    public function BuscarIndividual(int $id): array
    {
        try {
            $conex = $this->conex();
            $stmt = $conex->prepare(
                "SELECT pi.id_individual, pi.id_palmares, pi.id_premio, pi.id_atleta,
                        pal.id_torneo
                 FROM palmares_individual pi
                 INNER JOIN palmares pal ON pi.id_palmares = pal.id_palmares
                 WHERE pi.id_individual = :id"
            );
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            return ['accion' => 'buscar', 'datos' => $datos];
        } catch (Exception $e) {
            logs('Palmares', $e->getMessage(), 'Modelo_BuscarIndividual');
            return ['accion' => 'error', 'mensaje' => $e->getMessage()];
        } finally {
            $conex = NULL;
        }
    }

    public function BuscarGrupal(int $id): array
    {
        try {
            $conex = $this->conex();
            $stmt = $conex->prepare(
                "SELECT pg.id_grupal, pg.id_palmares, pg.id_premio, pg.id_equipo,
                        pal.id_torneo
                FROM palmares_grupal pg
                INNER JOIN palmares pal ON pg.id_palmares = pal.id_palmares
                WHERE pg.id_grupal = :id"
            );
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            return ['accion' => 'buscar', 'datos' => $datos];
        } catch (Exception $e) {
            logs('Palmares', $e->getMessage(), 'Modelo_BuscarGrupal');
            return ['accion' => 'error', 'mensaje' => $e->getMessage()];
        } finally {
            $conex = NULL;
        }
    }

    private function verificarDuplicado($conex, $idTorneo, $id, $idPremio, $esIndividual): bool
    {
        if ($esIndividual) {
            $sql = "SELECT COUNT(*) 
                FROM palmares_individual pi
                INNER JOIN palmares p ON pi.id_palmares = p.id_palmares
                WHERE p.id_torneo = :id_torneo 
                AND pi.id_atleta = :id 
                AND pi.id_premio = :id_premio";
        } else {
            $sql = "SELECT COUNT(*) 
                FROM palmares_grupal pg
                INNER JOIN palmares p ON pg.id_palmares = p.id_palmares
                WHERE p.id_torneo = :id_torneo 
                AND pg.id_equipo = :id 
                AND pg.id_premio = :id_premio";
        }

        $stmt = $conex->prepare($sql);
        $stmt->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':id_premio', $idPremio, PDO::PARAM_INT);
        $stmt->execute();

        return (int)$stmt->fetchColumn() > 0;
    }
}
