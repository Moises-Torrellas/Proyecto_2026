<?php

namespace App\modelo;

use App\modelo\ModeloBase;
use Exception;
use PDO;

class ModeloEquipamientos extends ModeloBase
{
    public function __construct()
    {
        parent::__construct();
        $this->campoWhitelist = ['id_catalogo', 'id_estados', 'estatus'];
        $this->llavePrimaria = 'id_equipamiento';
    }

    public function ProcesarDatos(array $datos): array
    {
        if (empty($datos)) {
            return ['accion' => 'error', 'codigo' => defined('_ERR_VACIO_') ? _ERR_VACIO_ : 'ERR_VACIO'];
        }

        $accion = $datos['accion'] ?? null;

        return match ($accion) {
            'consultar'     => $this->ConsultarAgrupado(), // Usamos la consulta agrupada
            'cargar_combos' => $this->CargarCombos(),
            'incluir'       => $this->IncluirEquipamiento($datos),
            'modificar'     => $this->ModificarEquipamiento($datos),
            'eliminar'      => $this->EliminarEquipamiento($datos['id_equipamiento'] ?? null),
            default         => ['accion' => 'error', 'codigo' => defined('_ERR_ACCION_') ? _ERR_ACCION_ : 'ERR_ACCION']
        };
    }

    private function ConsultarAgrupado(): array
    {
        try {
            $sql = "SELECT e.id_equipamiento, 
                           c.id_catalogo,
                           c.nombre as articulo, 
                           c.talla, 
                           ce.nombre as categoria, 
                           es.nombre as estado, 
                           es.nivel_estado,
                           e.id_estados as id_estado,
                           e.estatus
                    FROM equipamientos e
                    INNER JOIN catalogos c ON e.id_catalogo = c.id_catalogo
                    INNER JOIN categoria_catalogo ce ON c.id_categoria = ce.id_categoria
                    INNER JOIN estado_equipamiento es ON e.id_estados = es.id_estado
                    ORDER BY c.nombre ASC, e.id_equipamiento DESC";
            
            $stmt = $this->conex()->prepare($sql);
            $stmt->execute();
            $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Agrupamos por artículo del catálogo
            $agrupado = [];
            foreach ($datos as $fila) {
                $id_cat = $fila['id_catalogo'];
                if (!isset($agrupado[$id_cat])) {
                    $agrupado[$id_cat] = [
                        'id_catalogo' => $id_cat,
                        'articulo' => $fila['articulo'] . ($fila['talla'] ? ' (Talla: ' . $fila['talla'] . ')' : ''),
                        'categoria' => $fila['categoria'],
                        'piezas' => []
                    ];
                }
                $agrupado[$id_cat]['piezas'][] = [
                    'id_equipamiento' => $fila['id_equipamiento'],
                    'estado_fisico' => $fila['estado'],
                    'id_estado' => $fila['id_estado'],
                    'nivel_estado' => $fila['nivel_estado'],
                    'estatus' => $fila['estatus']
                ];
            }

            return ['accion' => 'consultar', 'datos' => array_values($agrupado)];
        } catch (Exception $e) {
            logs('Equipamientos', $e->getMessage(), 'Modelo_Consultar');
            return ['accion' => 'error', 'codigo' => defined('_ERR_BD_') ? _ERR_BD_ : 'ERR_BD'];
        }
    }

    private function CargarCombos(): array
    {
        try {
            $conex = $this->conex();
            $stmtCat = $conex->query("SELECT id_catalogo, nombre, talla FROM catalogos");
            $catalogos = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

            $stmtEst = $conex->query("SELECT id_estado, nombre FROM estado_equipamiento ORDER BY nivel_estado ASC");
            $estados = $stmtEst->fetchAll(PDO::FETCH_ASSOC);

            return ['accion' => 'cargar_combos', 'catalogos' => $catalogos, 'estados' => $estados];
        } catch (Exception $e) {
            return ['accion' => 'error', 'codigo' => defined('_ERR_BD_') ? _ERR_BD_ : 'ERR_BD'];
        }
    }

    private function IncluirEquipamiento(array $datos): array
    {
        $conex = $this->conex();
        try {
            $conex->beginTransaction();
            $sql = "INSERT INTO equipamientos (id_catalogo, id_estados, estatus) VALUES (?, ?, 1)";
            $stmt = $conex->prepare($sql);
            $stmt->execute([$datos['id_catalogo'], $datos['id_estado']]);
            $conex->commit();

            return ['accion' => 'exito', 'mensaje' => 'Equipamiento registrado en el inventario.'];
        } catch (Exception $e) {
            if ($conex->inTransaction()) $conex->rollBack();
            logs('Equipamientos', $e->getMessage(), 'Modelo_Incluir');
            return ['accion' => 'error', 'codigo' => defined('_ERR_BD_') ? _ERR_BD_ : 'ERR_BD'];
        }
    }

    private function ModificarEquipamiento(array $datos): array
    {
        $conex = $this->conex();
        try {
            $conex->beginTransaction();
            $sql = "UPDATE equipamientos SET id_catalogo = ?, id_estados = ? WHERE id_equipamiento = ?";
            $stmt = $conex->prepare($sql);
            $stmt->execute([$datos['id_catalogo'], $datos['id_estado'], $datos['id_equipamiento']]);
            $conex->commit();

            return ['accion' => 'exito', 'mensaje' => 'Equipamiento actualizado correctamente.'];
        } catch (Exception $e) {
            if ($conex->inTransaction()) $conex->rollBack();
            logs('Equipamientos', $e->getMessage(), 'Modelo_Modificar');
            return ['accion' => 'error', 'codigo' => defined('_ERR_BD_') ? _ERR_BD_ : 'ERR_BD'];
        }
    }

    private function EliminarEquipamiento($id): array
    {
        if (empty($id)) return ['accion' => 'error', 'codigo' => defined('_ERR_VACIO_') ? _ERR_VACIO_ : 'ERR_VACIO'];

        $conex = $this->conex();
        try {
            $conex->beginTransaction();
            $sql = "DELETE FROM equipamientos WHERE id_equipamiento = ?";
            $stmt = $conex->prepare($sql);
            $stmt->execute([$id]);
            $conex->commit();

            return ['accion' => 'exito', 'mensaje' => 'Equipamiento retirado del inventario.'];
        } catch (\PDOException $e) {
            if ($conex->inTransaction()) $conex->rollBack();
            if ($e->getCode() == 23000) { 
                return ['accion' => 'error', 'codigo' => defined('_ERR_USO_') ? _ERR_USO_ : 'ERR_USO'];
            }
            return ['accion' => 'error', 'codigo' => defined('_ERR_BD_') ? _ERR_BD_ : 'ERR_BD'];
        }
    }

    public function ConsultarEquiposLibres(): array { 
        $conex = null;
        try {
            $conex = $this->conex();
            $sql = "SELECT e.id_equipamiento, IFNULL(c.nombre, 'Artículo sin registrar') as articulo FROM equipamientos e LEFT JOIN catalogos c ON e.id_catalogo = c.id_catalogo WHERE e.estatus = 1";
            $equipos = $conex->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            return ['accion' => 'exito', 'datos' => $equipos];
        } catch (Exception $e) {
            return ['accion' => 'error', 'datos' => []];
        }
    }
    public function CambiarEstatus($id, $estatus, $conex = null): bool { /* ... Se mantiene igual ... */ 
        $c = $conex ?? $this->conex();
        $stmt = $c->prepare("UPDATE equipamientos SET estatus = ? WHERE id_equipamiento = ?");
        return $stmt->execute([$estatus, $id]);
    }
}