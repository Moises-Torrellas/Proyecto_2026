<?php

namespace App\modelo;

use Exception;
use PDO;

class ModeloAsignaciones extends Conexion
{
    private $id_asignacion;
    private $codigo_atleta;
    private $codigo_articulo;
    private $fecha_asignacion;
    private $estatus;
    
    private $filtro;
    private $fecha_inicio;
    private $fecha_fin;
    private $mostrar_inactivos;
    private $accion_actual; 

    private $objArticulos; 

    public function __construct()
    {
        parent::__construct();

        $this->campoWhitelist = [
            'id_asignacion'    => 'id_asignacion',
            'codigo_atleta'    => 'codigo_atleta',
            'codigo_articulo'  => 'codigo_articulo',
            'fecha_asignacion' => 'fecha_asignacion',
            'estatus'          => 'estatus',
            'filtro'           => 'filtro',
            'fecha_f'          => 'fecha_f',
            'anulados'         => 'anulados'
        ];

        $this->llavePrimaria = 'id_asignacion';
    }

    public function setArticulos(ModeloArticulosInventario $articulos)
    {
        $this->objArticulos = $articulos;
    }

    public function ProcesarDatos(array $datos): array
    {
        if (empty($datos)) {
            return ['accion' => 'error', 'codigo' => 'ERR_VACIO'];
        }

        $this->ValidarExpresiones($datos);

        $accion = $datos['accion'] ?? null;
        $this->accion_actual = $accion;

        $this->id_asignacion    = $datos['id_asignacion'] ?? null;
        $this->codigo_atleta    = $datos['codigo_atleta'] ?? null;
        $this->codigo_articulo  = $datos['codigo_articulo'] ?? null;
        $this->fecha_asignacion = $datos['fecha_asignacion'] ?? null;
        $this->estatus          = $datos['estatus'] ?? null;

        $this->filtro            = $datos['filtro'] ?? '';
        $this->fecha_inicio      = $datos['fecha_asignacion'] ?? ''; 
        $this->fecha_fin         = $datos['fecha_f'] ?? '';
        $this->mostrar_inactivos = $datos['anulados'] ?? 0;

        return match ($accion) {
            'consultar' => $this->ConsultarAsignaciones(),
            'generar'   => $this->ConsultarAsignaciones(), 
            'buscar'    => $this->Buscar(), // <-- NUEVO METODO AÑADIDO
            'incluir'   => $this->IncluirAsignacion(),
            'modificar' => $this->ModificarAsignacion(),
            'anular'    => $this->AnularAsignacion(),
            default     => ['accion' => 'error', 'codigo' => 'ERR_ACCION']
        };
    }

    // NUEVO METODO PARA LA BITÁCORA: Trae los datos antes de tocarlos
    public function Buscar($id = null): array
    {
        $conex = null;
        try {
            $codigo = ($id === null) ? $this->id_asignacion : $id;
            $conex = $this->conex();
            $sentencia = "SELECT * FROM asignaciones WHERE id_asignacion = :id";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id', $codigo);
            $stmt->execute();
            $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return array('accion' => 'buscar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Asignaciones', $e->getMessage(), 'Modelo_Buscar');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = null;
        }
    }

   public function ConsultarAsignaciones(): array
    {
        $conex = null;
        try {
            $conex = $this->conex();

            // 1. Llamamos directamente a la vista. El código queda súper limpio.
            $sql = "SELECT * FROM vista_asignaciones_general WHERE 1=1";

            // 2. Aplicamos los filtros usando los nombres de las columnas de la vista
            if (!empty($this->filtro)) {
                $sql .= " AND (atleta LIKE :filtro1 OR doc_identidad LIKE :filtro2 OR articulo LIKE :filtro3 OR fecha_vista LIKE :filtro4 OR fecha_real LIKE :filtro5)";
            }

            if (!empty($this->codigo_atleta)) {
                $sql .= " AND codigo_atleta = :codigo_atleta";
            }
            if (!empty($this->codigo_articulo)) {
                $sql .= " AND codigo_articulo = :codigo_articulo";
            }
            if (!empty($this->fecha_inicio) && !empty($this->fecha_fin)) {
                $sql .= " AND DATE(fecha_real) BETWEEN :fecha_inicio AND :fecha_fin";
            } else if (!empty($this->fecha_inicio)) {
                $sql .= " AND DATE(fecha_real) >= :fecha_inicio";
            } else if (!empty($this->fecha_fin)) {
                $sql .= " AND DATE(fecha_real) <= :fecha_fin";
            }

            if ($this->accion_actual === 'generar' && empty($this->mostrar_inactivos)) {
                $sql .= " AND estatus_asignacion = 1";
            }

            $sql .= " ORDER BY atleta ASC, fecha_real DESC";

            $stmt = $conex->prepare($sql);

            if (!empty($this->filtro)) {
                $p = '%' . $this->filtro . '%';
                $stmt->bindValue(':filtro1', $p);
                $stmt->bindValue(':filtro2', $p);
                $stmt->bindValue(':filtro3', $p);
                $stmt->bindValue(':filtro4', $p);
                $stmt->bindValue(':filtro5', $p);
            }
            if (!empty($this->codigo_atleta)) $stmt->bindValue(':codigo_atleta', $this->codigo_atleta);
            if (!empty($this->codigo_articulo)) $stmt->bindValue(':codigo_articulo', $this->codigo_articulo);
            if (!empty($this->fecha_inicio)) $stmt->bindValue(':fecha_inicio', $this->fecha_inicio);
            if (!empty($this->fecha_fin)) $stmt->bindValue(':fecha_fin', $this->fecha_fin);

            $stmt->execute();
            $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $agrupado = [];
            foreach ($datos as $fila) {
                $id = $fila['codigo_atleta'];
                if (!isset($agrupado[$id])) {
                    $agrupado[$id] = [
                        'codigo_atleta' => $id,
                        'nombre_completo' => $fila['atleta'],
                        'doc_identidad' => $fila['doc_identidad'] ?? 'Sin CI',
                        'asignaciones' => []
                    ];
                }
                $agrupado[$id]['asignaciones'][] = [
                    'id_asignacion' => $fila['id_asignacion'],
                    'codigo_articulo' => $fila['codigo_articulo'],
                    'articulo' => $fila['articulo'],
                    'codigo_club' => $fila['codigo_club'],
                    'fecha_vista' => $fila['fecha_vista'],
                    'fecha_real' => $fila['fecha_real'],
                    'estatus' => $fila['estatus_asignacion']
                ];
            }

            return ['accion' => 'consultar', 'datos' => array_values($agrupado)];
        } catch (Exception $e) {
            logs('Asignaciones', $e->getMessage(), 'Modelo_Consultar');
            return ['accion' => 'error', 'codigo' => 'ERR_BD', 'mensaje' => $e->getMessage()];
        } finally {
            $conex = null;
        }
    }

    private function IncluirAsignacion(): array
    {
        $conex = null;
        $datos_nuevos = [
            'codigo_atleta' => $this->codigo_atleta,
            'codigo_articulo' => $this->codigo_articulo,
            'fecha_asignacion' => $this->fecha_asignacion,
            'estatus' => 1
        ];

        try {
            $conex = $this->conex();
            $conex->beginTransaction();

            if (!$this->verificarExistencia('codigo_atleta', $this->codigo_atleta, 'atletas', null)) {
                return ['accion' => 'error', 'codigo' => 'ERR_ATLETA_NO_EXISTE', 'datos_nuevos' => json_encode($datos_nuevos)];
            }

            $stmtCheck = $conex->prepare("SELECT estatus FROM articulos_inventario WHERE codigo_articulo = ? FOR UPDATE");
            $stmtCheck->execute([$this->codigo_articulo]);
            $estadoEquipo = $stmtCheck->fetchColumn();

            if ($estadoEquipo === false) return ['accion' => 'error', 'codigo' => 'ERR_EQUIPO_NO_EXISTE', 'datos_nuevos' => json_encode($datos_nuevos)];
            if ($estadoEquipo != 1) return ['accion' => 'error', 'codigo' => 'ERR_EQUIPO_OCUPADO', 'datos_nuevos' => json_encode($datos_nuevos)];

            $sqlInsert = "INSERT INTO asignaciones (codigo_atleta, codigo_articulo, fecha_asignacion, estatus) VALUES (?, ?, ?, 1)";
            $stmtInsert = $conex->prepare($sqlInsert);
            $stmtInsert->execute([$this->codigo_atleta, $this->codigo_articulo, $this->fecha_asignacion]);

            $this->objArticulos->CambiarEstatus($this->codigo_articulo, 2, $conex);

            $conex->commit();
            // NUEVO: Devolvemos los datos nuevos para la bitácora
            return ['accion' => 'exito', 'mensaje' => 'Asignación procesada.', 'datos_nuevos' => json_encode($datos_nuevos)];
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) $conex->rollBack();
            logs('Asignaciones', $e->getMessage(), 'Modelo_Incluir');
            return ['accion' => 'error', 'codigo' => 'ERR_BD', 'datos_nuevos' => json_encode($datos_nuevos)];
        } finally {
            $conex = null;
        }
    }

    private function ModificarAsignacion(): array
    {
        $conex = null;
        $datos_nuevos = [
            'id_asignacion' => $this->id_asignacion,
            'codigo_atleta' => $this->codigo_atleta,
            'codigo_articulo' => $this->codigo_articulo,
            'fecha_asignacion' => $this->fecha_asignacion
        ];

        try {
            $conex = $this->conex();
            $conex->beginTransaction();

            if (!$this->verificarExistencia('id_asignacion', $this->id_asignacion, 'asignaciones', null)) {
                return ['accion' => 'error', 'codigo' => 'ERR_NO_EXISTE', 'datos_nuevos' => json_encode($datos_nuevos)];
            }

            $stmtOld = $conex->prepare("SELECT codigo_articulo, estatus FROM asignaciones WHERE id_asignacion = ? FOR UPDATE");
            $stmtOld->execute([$this->id_asignacion]);
            $datosViejos = $stmtOld->fetch(PDO::FETCH_ASSOC);

            // Evitar editar equipos ya devueltos o anulados
            if ($datosViejos['estatus'] != 1) {
                return ['accion' => 'error', 'codigo' => 'ERR_ESTATUS', 'datos_nuevos' => json_encode($datos_nuevos)];
            }

            $viejoEquipo = $datosViejos['codigo_articulo'];

            if ($viejoEquipo != $this->codigo_articulo) {
                $stmtCheck = $conex->prepare("SELECT estatus FROM articulos_inventario WHERE codigo_articulo = ? FOR UPDATE");
                $stmtCheck->execute([$this->codigo_articulo]);
                if ($stmtCheck->fetchColumn() != 1) return ['accion' => 'error', 'codigo' => 'ERR_EQUIPO_NO_DISPONIBLE', 'datos_nuevos' => json_encode($datos_nuevos)];

                $this->objArticulos->CambiarEstatus($viejoEquipo, 1, $conex); // Libera el viejo
                $this->objArticulos->CambiarEstatus($this->codigo_articulo, 2, $conex); // Ocupa el nuevo
            }

            $sqlUpdate = "UPDATE asignaciones SET codigo_atleta = ?, codigo_articulo = ?, fecha_asignacion = ? WHERE id_asignacion = ?";
            $conex->prepare($sqlUpdate)->execute([$this->codigo_atleta, $this->codigo_articulo, $this->fecha_asignacion, $this->id_asignacion]);

            $conex->commit();
            // NUEVO: Devolvemos los datos nuevos
            return ['accion' => 'exito', 'mensaje' => 'Modificación exitosa.', 'datos_nuevos' => json_encode($datos_nuevos)];
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) $conex->rollBack();
            logs('Asignaciones', $e->getMessage(), 'Modelo_Modificar');
            return ['accion' => 'error', 'codigo' => 'ERR_BD', 'datos_nuevos' => json_encode($datos_nuevos)];
        } finally {
            $conex = null;
        }
    }

    private function AnularAsignacion(): array
    {
        $conex = null;
        try {
            $conex = $this->conex();
            $conex->beginTransaction();

            if (!$this->verificarExistencia('id_asignacion', $this->id_asignacion, 'asignaciones', null)) {
                return ['accion' => 'error', 'codigo' => 'ERR_NO_EXISTE'];
            }

            $stmtCheck = $conex->prepare("SELECT codigo_articulo FROM asignaciones WHERE id_asignacion = ? FOR UPDATE");
            $stmtCheck->execute([$this->id_asignacion]);
            $codigo_articulo_actual = $stmtCheck->fetchColumn();

            $stmtAnular = $conex->prepare("UPDATE asignaciones SET estatus = 3 WHERE id_asignacion = ?");
            $stmtAnular->execute([$this->id_asignacion]);

            if ($codigo_articulo_actual !== false) {
                $this->objArticulos->CambiarEstatus($codigo_articulo_actual, 1, $conex);
            }

            $conex->commit();
            return ['accion' => 'exito', 'mensaje' => 'Asignación anulada.'];
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) $conex->rollBack();
            logs('Asignaciones', $e->getMessage(), 'Modelo_Anular');
            return ['accion' => 'error', 'codigo' => 'ERR_BD'];
        } finally {
            $conex = null;
        }
    }

    public function CambiarEstatusAsignacion($id_asignacion, $nuevo_estatus, $conex = null): bool
    {
        $c = $conex ?? $this->conex();
        try {
            $sql = "UPDATE asignaciones SET estatus = :estatus WHERE id_asignacion = :id";
            $stmt = $c->prepare($sql);
            $stmt->execute([
                ':estatus' => $nuevo_estatus,
                ':id'      => $id_asignacion
            ]);
            return true;
        } catch (Exception $e) {
            logs('Asignaciones', $e->getMessage(), 'Modelo_CambiarEstatusAsignacion');
            throw new Exception("Error al actualizar el estatus de la asignación.");
        }
    }

   private function ValidarExpresiones(array $datos): void
    {
        // Se mantiene la regex original de IDs
        $regexId = '/^[0-9]+$/';
        $regexFecha = '/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/';

        if (!empty($datos['id_asignacion']) && !preg_match($regexId, $datos['id_asignacion'])) {
            throw new Exception('ID de asignación inválido.');
        }
        if (!empty($datos['codigo_atleta']) && !preg_match($regexId, $datos['codigo_atleta'])) {
            throw new Exception('Atleta inválido.');
        }
        if (!empty($datos['codigo_articulo']) && !preg_match($regexId, $datos['codigo_articulo'])) {
            throw new Exception('Artículo inválido.');
        }
        
        if (!empty($datos['fecha_asignacion']) && !preg_match($regexFecha, $datos['fecha_asignacion'])) {
            throw new Exception('Formato de fecha de asignación inválido. Use AAAA-MM-DD.');
        }
        if (!empty($datos['fecha_f']) && !preg_match($regexFecha, $datos['fecha_f'])) {
            throw new Exception('Formato de fecha de fin inválido. Use AAAA-MM-DD.');
        }

        // Validación global de fechas
        if (!empty($datos['fecha_asignacion']) && $datos['fecha_asignacion'] < '2024-01-01') {
            throw new Exception('Fecha inválida. El sistema no admite registros anteriores al 2024.');
        }

        if (!empty($datos['fecha_asignacion']) && isset($datos['accion']) && $datos['accion'] === 'incluir') {
            $hoy = date('Y-m-d');
            if ($datos['fecha_asignacion'] < $hoy) {
                throw new Exception('La fecha de la nueva asignación no puede ser menor al día actual.');
            }
        }
    }
}