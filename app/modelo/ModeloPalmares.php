<?php

namespace App\modelo;

use Exception;
use PDO;

class ModeloPalmares extends Conexion
{
    private $id;
    private $id_torneo;
    private $id_atleta;
    private $id_equipo;
    private $id_premio;
    private $tipo_palmares;

    private $modeloParticipaciones;
    private $modeloPremios;

    public function __construct()
    {
        parent::__construct();
        // Ajustado para coincidir con la flexibilidad de recepción de datos
        $this->campoWhitelist = [
            'id'          => 'codigo_individual', // o codigo_grupal dependiendo del contexto
            'id_torneo'   => 'codigo_torneo',
            'id_atleta'   => 'codigo_atleta',
            'id_equipo'   => 'codigo_equipo',
            'id_premio'   => 'codigo_premio',
        ];
    }

    public function setModeloParticipaciones(ModeloParticipaciones $modelo): void
    {
        $this->modeloParticipaciones = $modelo;
    }

    public function setModeloPremios(ModeloPremios $modelo): void
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

        $accion = $datos['accion'] ?? null;

        return match ($accion) {
            'incluir'   => $this->Incluir(),
            'modificar' => $this->Modificar(),
            'eliminar'  => $this->Eliminar(),
            default => throw new Exception('La acción solicitada para el palmarés no es válida.')
        };
    }

    public function ConsultarIndividual(array $filtro = []): array
    {
        try {
            $conex = $this->conex();
            $params = [];

            $sentencia = "SELECT 
                    pi.codigo_individual AS id_individual,
                    pi.codigo_premio AS id_premio,
                    dp.codigo_atleta AS id_atleta,
                    a.p_nombre AS atleta_nombres,
                    a.p_apellidos AS atleta_apellidos,
                    a.foto AS atleta_foto,
                    p.nombre AS nombre_premio,
                    p.tipo AS tipo_premio,
                    part.codigo_torneo AS id_torneo,
                    t.nombre AS nombre_torneo,
                    t.fecha_inicio AS fecha_torneo
                FROM palmares_individual pi
                INNER JOIN premios p ON pi.codigo_premio = p.codigo_premio
                INNER JOIN detalles_participacion dp ON pi.codigo_dtll_prtc = dp.codigo_dtll_prtc
                INNER JOIN atletas a ON dp.codigo_atleta = a.codigo_atleta
                INNER JOIN participaciones part ON dp.codigo_participacion = part.codigo_participacion
                INNER JOIN torneos t ON part.codigo_torneo = t.codigo_torneo
                WHERE 1=1";

            if (!empty($filtro['filtro'])) {
                $f = "%" . trim($filtro['filtro']) . "%";
                $sentencia .= " AND (
                    a.p_nombre LIKE :f1 OR 
                    a.p_apellidos LIKE :f2 OR 
                    p.nombre LIKE :f3 OR
                    t.nombre LIKE :f4
                )";
                $params[':f1'] = $f;
                $params[':f2'] = $f;
                $params[':f3'] = $f;
                $params[':f4'] = $f;
            }

            $sentencia .= " ORDER BY a.p_apellidos ASC, a.p_nombre ASC, t.fecha_inicio DESC";

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

    public function ConsultarGrupal(array $filtro = []): array
    {
        try {
            $conex = $this->conex();
            $params = [];

            $sentencia = "SELECT 
                    pg.codigo_grupal,
                    pg.codigo_premio AS id_premio,
                    part.codigo_equipo AS id_equipo,
                    e.nombre AS nombre_equipo,
                    p.nombre AS nombre_premio,
                    p.tipo AS tipo_premio,
                    part.codigo_torneo AS id_torneo,
                    t.nombre AS nombre_torneo,
                    t.fecha_inicio AS fecha_torneo
                FROM palmares_grupal pg
                INNER JOIN premios p ON pg.codigo_premio = p.codigo_premio
                INNER JOIN participaciones part ON pg.codigo_participacion = part.codigo_participacion
                INNER JOIN equipos e ON part.codigo_equipo = e.codigo_equipo
                INNER JOIN torneos t ON part.codigo_torneo = t.codigo_torneo
                WHERE 1=1";

            if (!empty($filtro['filtro'])) {
                $f = "%" . trim($filtro['filtro']) . "%";
                $sentencia .= " AND (
                    e.nombre LIKE :f1 OR 
                    p.nombre LIKE :f2 OR
                    t.nombre LIKE :f3
                )";
                $params[':f1'] = $f;
                $params[':f2'] = $f;
                $params[':f3'] = $f;
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
                'id_premio'      => $row['id_premio'],
                'nombre_premio'  => $row['nombre_premio'],
                'id_torneo'      => $row['id_torneo'],
                'nombre_torneo'  => $row['nombre_torneo'],
                'fecha_torneo'   => $row['fecha_torneo']
            ];
        }
        return array_values($agrupado);
    }

    private function agruparPalmaresGrupal(array $filas): array
    {
        $agrupado = [];
        foreach ($filas as $row) {
            $idEquipo = $row['id_equipo'];
            if (!isset($agrupado[$idEquipo])) {
                $agrupado[$idEquipo] = [
                    'id_equipo'        => $idEquipo,
                    'nombre_equipo'    => $row['nombre_equipo'],
                    'total_premios'    => 0,
                    'premios'          => []
                ];
            }
            $agrupado[$idEquipo]['total_premios']++;
            $agrupado[$idEquipo]['premios'][] = [
                'codigo_grupal'      => $row['codigo_grupal'],
                'id_premio'      => $row['id_premio'],
                'nombre_premio'  => $row['nombre_premio'],
                'id_torneo'      => $row['id_torneo'],
                'nombre_torneo'  => $row['nombre_torneo'],
                'fecha_torneo'   => $row['fecha_torneo']
            ];
        }
        return array_values($agrupado);
    }

    private function Incluir(): array
    {
        $conex = null;
        try {
            if (!$this->modeloPremios) {
                throw new Exception('Modelos requeridos no configurados.');
            }

            $conex = $this->conex();
            $conex->beginTransaction();
            
            // Validar la existencia y tipo del premio
            if (!$this->verificarExistencia('id_premio', $this->id_premio, 'premios', null)) {
                throw new Exception(INVALID_ID);
            }
            $tipoEsperado = ($this->tipo_palmares === 'individual') ? 'I' : 'G';
            $this->modeloPremios->validarTipoPremio($this->id_premio, $tipoEsperado);

            if ($this->tipo_palmares === 'individual') {
                // 1. Obtener codigo_dtll_prtc cruzando participaciones y detalles_participacion
                $stmt = $conex->prepare("
                    SELECT dp.codigo_dtll_prtc 
                    FROM detalles_participacion dp
                    INNER JOIN participaciones p ON dp.codigo_participacion = p.codigo_participacion
                    WHERE p.codigo_torneo = :torneo AND dp.codigo_atleta = :atleta
                ");
                $stmt->execute([':torneo' => $this->id_torneo, ':atleta' => $this->id_atleta]);
                $codDetalle = $stmt->fetchColumn();

                if (!$codDetalle) {
                    throw new Exception("El atleta no cuenta con una participación registrada en este torneo.");
                }

                // 2. Verificar duplicado
                $stmtDup = $conex->prepare("SELECT COUNT(*) FROM palmares_individual WHERE codigo_premio = :premio AND codigo_dtll_prtc = :detalle");
                $stmtDup->execute([':premio' => $this->id_premio, ':detalle' => $codDetalle]);
                if ((int)$stmtDup->fetchColumn() > 0) {
                    throw new Exception(DUPLICATE);
                }

                // 3. Insertar
                $stmtIn = $conex->prepare("INSERT INTO palmares_individual (codigo_premio, codigo_dtll_prtc) VALUES (:premio, :detalle)");
                $stmtIn->execute([':premio' => $this->id_premio, ':detalle' => $codDetalle]);

            } else {
                // 1. Obtener codigo_participacion del equipo en el torneo
                $stmt = $conex->prepare("SELECT codigo_participacion FROM participaciones WHERE codigo_torneo = :torneo AND codigo_equipo = :equipo");
                $stmt->execute([':torneo' => $this->id_torneo, ':equipo' => $this->id_equipo]);
                $codParticipacion = $stmt->fetchColumn();

                if (!$codParticipacion) {
                    throw new Exception("El equipo no cuenta con una participación registrada en este torneo.");
                }

                // 2. Verificar duplicado
                $stmtDup = $conex->prepare("SELECT COUNT(*) FROM palmares_grupal WHERE codigo_premio = :premio AND codigo_participacion = :participacion");
                $stmtDup->execute([':premio' => $this->id_premio, ':participacion' => $codParticipacion]);
                if ((int)$stmtDup->fetchColumn() > 0) {
                    throw new Exception(DUPLICATE);
                }

                // 3. Insertar
                $stmtIn = $conex->prepare("INSERT INTO palmares_grupal (codigo_premio, codigo_participacion) VALUES (:premio, :participacion)");
                $stmtIn->execute([':premio' => $this->id_premio, ':participacion' => $codParticipacion]);
            }

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
            if (!$this->modeloPremios) {
                throw new Exception('Modelos requeridos no configurados.');
            }

            $conex = $this->conex();
            $conex->beginTransaction();

            if (!$this->verificarExistencia('id_premio', $this->id_premio, 'premios', null)) {
                throw new Exception(INVALID_ID);
            }

            if ($this->tipo_palmares === 'individual') {
                $this->modeloPremios->validarTipoPremio($this->id_premio, 'I');

                $stmtDetalle = $conex->prepare("
                    SELECT dp.codigo_dtll_prtc 
                    FROM detalles_participacion dp
                    INNER JOIN participaciones p ON dp.codigo_participacion = p.codigo_participacion
                    WHERE p.codigo_torneo = :torneo AND dp.codigo_atleta = :atleta
                ");
                $stmtDetalle->execute([':torneo' => $this->id_torneo, ':atleta' => $this->id_atleta]);
                $codDetalle = $stmtDetalle->fetchColumn();

                if (!$codDetalle) {
                    throw new Exception("El atleta no cuenta con una participación registrada en este torneo.");
                }

                $stmt = $conex->prepare("UPDATE palmares_individual SET codigo_premio = :premio, codigo_dtll_prtc = :detalle WHERE codigo_individual = :id");
                $stmt->execute([
                    ':premio'  => $this->id_premio,
                    ':detalle' => $codDetalle,
                    ':id'      => $this->id
                ]);

            } else {
                $this->modeloPremios->validarTipoPremio($this->id_premio, 'G');

                // Resolver de nuevo la participación con los datos actualizados del formulario
                $stmtPart = $conex->prepare("SELECT codigo_participacion FROM participaciones WHERE codigo_torneo = :torneo AND codigo_equipo = :equipo");
                $stmtPart->execute([':torneo' => $this->id_torneo, ':equipo' => $this->id_equipo]);
                $codParticipacion = $stmtPart->fetchColumn();

                if (!$codParticipacion) {
                    throw new Exception("El equipo no cuenta con una participación registrada en este torneo.");
                }

                $stmt = $conex->prepare("UPDATE palmares_grupal SET codigo_premio = :premio, codigo_participacion = :participacion WHERE codigo_grupal = :id");
                $stmt->execute([
                    ':premio'        => $this->id_premio,
                    ':participacion' => $codParticipacion,
                    ':id'            => $this->id
                ]);
            }

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
            $conex = $this->conex();
            $conex->beginTransaction();

            if ($this->tipo_palmares === 'individual') {
                $stmtDel = $conex->prepare("DELETE FROM palmares_individual WHERE codigo_individual = :id");
            } else {
                $stmtDel = $conex->prepare("DELETE FROM palmares_grupal WHERE codigo_grupal = :id");
            }

            $stmtDel->bindValue(':id', $this->id, PDO::PARAM_INT);
            $stmtDel->execute();

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
            $stmt = $conex->prepare("
                SELECT 
                    pi.codigo_individual, 
                    pi.codigo_premio AS id_premio, 
                    dp.codigo_atleta AS id_atleta, 
                    part.codigo_torneo AS id_torneo
                FROM palmares_individual pi
                INNER JOIN detalles_participacion dp ON pi.codigo_dtll_prtc = dp.codigo_dtll_prtc
                INNER JOIN participaciones part ON dp.codigo_participacion = part.codigo_participacion
                WHERE pi.codigo_individual = :id
            ");
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
            $stmt = $conex->prepare("
                SELECT 
                    pg.codigo_grupal, 
                    pg.codigo_premio AS id_premio, 
                    part.codigo_equipo AS id_equipo, 
                    part.codigo_torneo AS id_torneo
                FROM palmares_grupal pg
                INNER JOIN participaciones part ON pg.codigo_participacion = part.codigo_participacion
                WHERE pg.codigo_grupal = :id
            ");
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
}