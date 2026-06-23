<?php

namespace App\modelo;

use Exception;
use PDO;

class ModeloArticulosInventario extends Conexion
{
    public function __construct()
    {
        parent::__construct();
        $this->campoWhitelist = ['id_catalogo', 'id_estado', 'estatus'];
        $this->llavePrimaria = 'codigo_articulo';
    }

    public function ProcesarDatos(array $datos): array
    {
        if (empty($datos)) {
            return ['accion' => 'error', 'codigo' => defined('_ERR_VACIO_') ? _ERR_VACIO_ : 'ERR_VACIO'];
        }

        $accion = $datos['accion'] ?? null;

        return match ($accion) {
            'consultar'     => $this->ConsultarAgrupado(), 
            'cargar_combos' => $this->CargarCombos(),
            'incluir'       => $this->IncluirArticulo($datos),
            'modificar'     => $this->ModificarArticulo($datos),
            'eliminar'      => $this->EliminarArticulo($datos['codigo_articulo'] ?? null),
            default         => ['accion' => 'error', 'codigo' => defined('_ERR_ACCION_') ? _ERR_ACCION_ : 'ERR_ACCION']
        };
    }

    private function GenerarCodigoClub(): string
    {
        $conex = $this->conex();
        // Buscamos el número más alto extrayendo los dígitos después de "CL-"
        $sql = "SELECT MAX(CAST(SUBSTRING(codigo_club, 4) AS UNSIGNED)) as max_num 
                FROM articulos_inventario 
                WHERE codigo_club LIKE 'CL-%'";
        
        $stmt = $conex->query($sql);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        $siguiente = 1;
        if ($resultado && $resultado['max_num']) {
            $siguiente = (int)$resultado['max_num'] + 1;
        }

        // Formateamos para que tenga ceros a la izquierda (ej. CL-001)
        return 'CL-' . str_pad((string)$siguiente, 3, '0', STR_PAD_LEFT);
    }

    private function ConsultarAgrupado(): array
    {
        try {
            $sql = "SELECT e.codigo_articulo, 
                           c.id_catalogo,
                           c.nombre as articulo, 
                           c.talla, 
                           ce.nombre as categoria, 
                           es.nombre as estado, 
                           es.nivel_estado,
                           e.id_estado,
                           e.codigo_club,
                           e.estatus
                    FROM articulos_inventario e
                    INNER JOIN catalogo c ON e.id_catalogo = c.id_catalogo
                    INNER JOIN categoria_catalogo ce ON c.id_categoria = ce.id_categoria
                    INNER JOIN estado_fisico es ON e.id_estado = es.id_estado
                    ORDER BY c.nombre ASC, e.codigo_articulo DESC";
            
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
                    'codigo_articulo' => $fila['codigo_articulo'],
                    'estado_fisico' => $fila['estado'],
                    'id_estado' => $fila['id_estado'],
                    'nivel_estado' => $fila['nivel_estado'],
                    'codigo_club' => $fila['codigo_club'],
                    'estatus' => $fila['estatus']
                ];
            }

            return ['accion' => 'consultar', 'datos' => array_values($agrupado)];
        } catch (Exception $e) {
            logs('Articulos Inventario', $e->getMessage(), 'Modelo_Consultar');
            return ['accion' => 'error', 'codigo' => defined('_ERR_BD_') ? _ERR_BD_ : 'ERR_BD'];
        }
    }

    private function CargarCombos(): array
    {
        try {
            $conex = $this->conex();
            $stmtCat = $conex->query("SELECT id_catalogo, nombre, talla FROM catalogo");
            $catalogos = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

            $stmtEst = $conex->query("SELECT id_estado, nombre FROM estado_fisico ORDER BY nivel_estado ASC");
            $estados = $stmtEst->fetchAll(PDO::FETCH_ASSOC);

            return ['accion' => 'cargar_combos', 'catalogos' => $catalogos, 'estados' => $estados];
        } catch (Exception $e) {
            return ['accion' => 'error', 'codigo' => defined('_ERR_BD_') ? _ERR_BD_ : 'ERR_BD'];
        }
    }

    private function IncluirArticulo(array $datos): array
    {
        $conex = $this->conex();
        try {
            $conex->beginTransaction();
            
            $codigo_club = $this->GenerarCodigoClub();

            $sql = "INSERT INTO articulos_inventario (id_catalogo, id_estado, codigo_club, estatus) VALUES (?, ?, ?, 1)";
            $stmt = $conex->prepare($sql);
            $stmt->execute([$datos['id_catalogo'], $datos['id_estado'], $codigo_club]);
            $conex->commit();

            return ['accion' => 'exito', 'mensaje' => "Artículo registrado con el código $codigo_club."];
        } catch (Exception $e) {
            if ($conex->inTransaction()) $conex->rollBack();
            logs('Articulos Inventario', $e->getMessage(), 'Modelo_Incluir');
            return ['accion' => 'error', 'codigo' => defined('_ERR_BD_') ? _ERR_BD_ : 'ERR_BD'];
        }
    }

    private function ModificarArticulo(array $datos): array
    {
        $conex = $this->conex();
        try {
            $conex->beginTransaction();
            $sql = "UPDATE articulos_inventario SET id_catalogo = ?, id_estado = ? WHERE codigo_articulo = ?";
            $stmt = $conex->prepare($sql);
            $stmt->execute([$datos['id_catalogo'], $datos['id_estado'], $datos['codigo_articulo']]);
            $conex->commit();

            return ['accion' => 'exito', 'mensaje' => 'Artículo actualizado correctamente.'];
        } catch (Exception $e) {
            if ($conex->inTransaction()) $conex->rollBack();
            logs('Articulos Inventario', $e->getMessage(), 'Modelo_Modificar');
            return ['accion' => 'error', 'codigo' => defined('_ERR_BD_') ? _ERR_BD_ : 'ERR_BD'];
        }
    }

    private function EliminarArticulo($id): array
    {
        if (empty($id)) return ['accion' => 'error', 'codigo' => defined('_ERR_VACIO_') ? _ERR_VACIO_ : 'ERR_VACIO'];

        $conex = $this->conex();
        try {
            $conex->beginTransaction();
            $sql = "DELETE FROM articulos_inventario WHERE codigo_articulo = ?";
            $stmt = $conex->prepare($sql);
            $stmt->execute([$id]);
            $conex->commit();

            return ['accion' => 'exito', 'mensaje' => 'Artículo retirado del inventario.'];
        } catch (\PDOException $e) {
            if ($conex->inTransaction()) $conex->rollBack();
            if ($e->getCode() == 23000) { 
                return ['accion' => 'error', 'codigo' => defined('_ERR_USO_') ? _ERR_USO_ : 'ERR_USO'];
            }
            return ['accion' => 'error', 'codigo' => defined('_ERR_BD_') ? _ERR_BD_ : 'ERR_BD'];
        }
    }

    public function ConsultarArticulosLibres(): array { 
        $conex = null;
        try {
            $conex = $this->conex();
            $sql = "SELECT e.codigo_articulo, IFNULL(c.nombre, 'Artículo sin registrar') as articulo FROM articulos_inventario e LEFT JOIN catalogo c ON e.id_catalogo = c.id_catalogo WHERE e.estatus = 1";
            $articulos = $conex->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            return ['accion' => 'exito', 'datos' => $articulos];
        } catch (Exception $e) {
            return ['accion' => 'error', 'datos' => []];
        }
    }

    public function CambiarEstatus($id, $estatus, $conex = null): bool {
        $c = $conex ?? $this->conex();
        $stmt = $c->prepare("UPDATE articulos_inventario SET estatus = ? WHERE codigo_articulo = ?");
        return $stmt->execute([$estatus, $id]);
    }
}