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
    private $objArticulos; // Instancia del modelo ArticulosInventario

    public function __construct()
    {
        parent::__construct();

        $this->campoWhitelist = [
            'id_asignacion'    => 'id_asignacion',
            'codigo_atleta'    => 'codigo_atleta',
            'codigo_articulo'  => 'codigo_articulo',
            'fecha_asignacion' => 'fecha_asignacion',
            'estatus'          => 'estatus'
        ];

        $this->llavePrimaria = 'id_asignacion';
    }

    // Recibe la instancia del modelo de inventario físico
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

        $this->id_asignacion    = $datos['id_asignacion'] ?? null;
        $this->codigo_atleta    = $datos['codigo_atleta'] ?? null;
        $this->codigo_articulo  = $datos['codigo_articulo'] ?? null;
        $this->fecha_asignacion = $datos['fecha_asignacion'] ?? null;
        $this->estatus          = $datos['estatus'] ?? null;

        $accion = $datos['accion'] ?? null;

        return match ($accion) {
            'consultar' => $this->ConsultarAsignaciones(),
            'incluir'   => $this->IncluirAsignacion(),
            'modificar' => $this->ModificarAsignacion(),
            'anular'    => $this->AnularAsignacion(),
            default     => ['accion' => 'error', 'codigo' => 'ERR_ACCION']
        };
    }

    public function ConsultarAsignaciones(): array
    {
        $conex = null;
        try {
            $conex = $this->conex();

            // Consulta corregida usando p_nombre, p_apellidos y codigo_atleta
            $sql = "SELECT a.id_asignacion, 
                           DATE_FORMAT(a.fecha_asignacion, '%d/%m/%Y %H:%i') as fecha_vista,
                           a.fecha_asignacion as fecha_real,
                           a.estatus as estatus_asignacion,
                           a.codigo_atleta,
                           CONCAT(at.p_nombre, ' ', at.p_apellidos) as atleta,
                           c.nombre as articulo,
                           a.codigo_articulo
                    FROM asignaciones a
                    INNER JOIN atletas at ON a.codigo_atleta = at.codigo_atleta
                    INNER JOIN articulos_inventario e ON a.codigo_articulo = e.codigo_articulo
                    INNER JOIN catalogo c ON e.id_catalogo = c.id_catalogo
                    ORDER BY at.p_nombre ASC, a.fecha_asignacion DESC";

            $stmt = $conex->prepare($sql);
            $stmt->execute();
            $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $agrupado = [];
            foreach ($datos as $fila) {
                $id = $fila['codigo_atleta'];
                if (!isset($agrupado[$id])) {
                    $agrupado[$id] = [
                        'codigo_atleta' => $id,
                        'nombre_completo' => $fila['atleta'],
                        'asignaciones' => [] // Se removió doc_identidad por no existir en la tabla atletas
                    ];
                }
                $agrupado[$id]['asignaciones'][] = [
                    'id_asignacion' => $fila['id_asignacion'],
                    'codigo_articulo' => $fila['codigo_articulo'],
                    'articulo' => $fila['articulo'],
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
        try {
            $conex = $this->conex();
            $conex->beginTransaction();

            // Corrección: Validar usando la columna 'codigo_atleta'
            if (!$this->verificarExistencia('codigo_atleta', $this->codigo_atleta, 'atletas', null)) {
                return ['accion' => 'error', 'codigo' => 'ERR_ATLETA_NO_EXISTE'];
            }

            // Validar existencia y disponibilidad en artículos_inventario
            $stmtCheck = $conex->prepare("SELECT estatus FROM articulos_inventario WHERE codigo_articulo = ? FOR UPDATE");
            $stmtCheck->execute([$this->codigo_articulo]);
            $estadoEquipo = $stmtCheck->fetchColumn();

            if ($estadoEquipo === false) return ['accion' => 'error', 'codigo' => 'ERR_EQUIPO_NO_EXISTE'];
            if ($estadoEquipo != 1) return ['accion' => 'error', 'codigo' => 'ERR_EQUIPO_OCUPADO'];

            // Insertar asignación activa (estatus = 1)
            $sqlInsert = "INSERT INTO asignaciones (codigo_atleta, codigo_articulo, fecha_asignacion, estatus) VALUES (?, ?, ?, 1)";
            $stmtInsert = $conex->prepare($sqlInsert);
            $stmtInsert->execute([$this->codigo_atleta, $this->codigo_articulo, $this->fecha_asignacion]);

            // Cambiar estatus del artículo a "En Uso" (2)
            $this->objArticulos->CambiarEstatus($this->codigo_articulo, 2, $conex);

            $conex->commit();
            return ['accion' => 'exito', 'mensaje' => 'Asignación procesada.'];
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) $conex->rollBack();
            logs('Asignaciones', $e->getMessage(), 'Modelo_Incluir');
            return ['accion' => 'error', 'codigo' => 'ERR_BD'];
        } finally {
            $conex = null;
        }
    }

    private function ModificarAsignacion(): array
    {
        $conex = null;
        try {
            $conex = $this->conex();
            $conex->beginTransaction();

            if (!$this->verificarExistencia('id_asignacion', $this->id_asignacion, 'asignaciones', null)) {
                return ['accion' => 'error', 'codigo' => 'ERR_NO_EXISTE'];
            }

            $stmtOld = $conex->prepare("SELECT codigo_articulo FROM asignaciones WHERE id_asignacion = ? FOR UPDATE");
            $stmtOld->execute([$this->id_asignacion]);
            $viejoEquipo = $stmtOld->fetchColumn();

            // Si cambiaron el artículo, liberamos el viejo y ocupamos el nuevo
            if ($viejoEquipo != $this->codigo_articulo) {
                $stmtCheck = $conex->prepare("SELECT estatus FROM articulos_inventario WHERE codigo_articulo = ? FOR UPDATE");
                $stmtCheck->execute([$this->codigo_articulo]);
                if ($stmtCheck->fetchColumn() != 1) return ['accion' => 'error', 'codigo' => 'ERR_EQUIPO_NO_DISPONIBLE'];

                $this->objArticulos->CambiarEstatus($viejoEquipo, 1, $conex); // Libera el viejo
                $this->objArticulos->CambiarEstatus($this->codigo_articulo, 2, $conex); // Ocupa el nuevo
            }

            $sqlUpdate = "UPDATE asignaciones SET codigo_atleta = ?, codigo_articulo = ?, fecha_asignacion = ? WHERE id_asignacion = ?";
            $conex->prepare($sqlUpdate)->execute([$this->codigo_atleta, $this->codigo_articulo, $this->fecha_asignacion, $this->id_asignacion]);

            $conex->commit();
            return ['accion' => 'exito', 'mensaje' => 'Modificación exitosa.'];
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) $conex->rollBack();
            logs('Asignaciones', $e->getMessage(), 'Modelo_Modificar');
            return ['accion' => 'error', 'codigo' => 'ERR_BD'];
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

            // Cambiar el estatus de la asignación a inactivo (0)
            $stmtAnular = $conex->prepare("UPDATE asignaciones SET estatus = 0 WHERE id_asignacion = ?");
            $stmtAnular->execute([$this->id_asignacion]);

            // Liberamos el artículo (estatus = 1)
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
        if (!empty($datos['id_asignacion']) && !preg_match('/^[0-9]+$/', $datos['id_asignacion'])) {
            throw new Exception('ID de asignación inválido.');
        }
        if (!empty($datos['codigo_atleta']) && !preg_match('/^[0-9]+$/', $datos['codigo_atleta'])) {
            throw new Exception('Atleta inválido.');
        }
        if (!empty($datos['codigo_articulo']) && !preg_match('/^[0-9]+$/', $datos['codigo_articulo'])) {
            throw new Exception('Artículo inválido.');
        }
        if (!empty($datos['fecha_asignacion']) && !preg_match('/^\d{4}-\d{2}-\d{2}([ T]\d{2}:\d{2}(:\d{2})?)?$/', $datos['fecha_asignacion'])) {
            throw new Exception('Formato de fecha inválido.');
        }
    }
}