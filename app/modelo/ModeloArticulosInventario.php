<?php

namespace App\modelo;

use Exception;
use PDO;

class ModeloArticulosInventario extends Conexion
{
    public function __construct()
    {
        parent::__construct();
        $this->campoWhitelist = ['id_catalogo', 'id_estado', 'codigo_club', 'estatus'];
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
            'buscar'        => $this->Buscar($datos['codigo_articulo'] ?? null), // <-- Añadimos el buscar
            'incluir'       => $this->IncluirArticulo($datos),
            'modificar'     => $this->ModificarArticulo($datos),
            'eliminar'      => $this->EliminarArticulo($datos['codigo_articulo'] ?? null),
            'reincorporar'  => $this->ReincorporarArticulo($datos['codigo_articulo'] ?? null),
            default         => ['accion' => 'error', 'codigo' => defined('_ERR_ACCION_') ? _ERR_ACCION_ : 'ERR_ACCION']
        };
    }

    // NUEVO MÉTODO PARA LA BITÁCORA: Trae los datos antes de tocarlos
    public function Buscar($id = null): array
    {
        try {
            $conex = $this->conex();
            $sentencia = "SELECT * FROM articulos_inventario WHERE codigo_articulo = :id";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return array('accion' => 'buscar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Articulos Inventario', $e->getMessage(), 'Modelo_Buscar');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        }
    }

    private function GenerarCodigoClub(): string
    {
        $conex = $this->conex();
        $sql = "SELECT MAX(CAST(SUBSTRING(codigo_club, 4) AS UNSIGNED)) as max_num 
                FROM articulos_inventario 
                WHERE codigo_club LIKE 'CL-%'";
        
        $stmt = $conex->query($sql);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        $siguiente = 1;
        if ($resultado && $resultado['max_num']) {
            $siguiente = (int)$resultado['max_num'] + 1;
        }

        return 'CL-' . str_pad((string)$siguiente, 4, '0', STR_PAD_LEFT);
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
                           EsAptoParaUso(e.id_estado) AS apto_para_uso,
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
                    'apto_para_uso' => $fila['apto_para_uso'],
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
        
        // Generamos el código club antes para poder adjuntarlo al JSON
        $codigo_club = $this->GenerarCodigoClub();
        $datos_nuevos = [
            'id_catalogo' => $datos['id_catalogo'],
            'id_estado'   => $datos['id_estado'],
            'codigo_club' => $codigo_club,
            'estatus'     => 1
        ];

        try {
            $conex->beginTransaction();
            $sql = "INSERT INTO articulos_inventario (id_catalogo, id_estado, codigo_club, estatus) VALUES (?, ?, ?, 1)";
            $stmt = $conex->prepare($sql);
            $stmt->execute([$datos['id_catalogo'], $datos['id_estado'], $codigo_club]);
            $conex->commit();

            return ['accion' => 'exito', 'mensaje' => "Artículo registrado con el código $codigo_club.", 'datos_nuevos' => json_encode($datos_nuevos)];
        } catch (Exception $e) {
            if ($conex->inTransaction()) $conex->rollBack();
            logs('Articulos Inventario', $e->getMessage(), 'Modelo_Incluir');
            return ['accion' => 'error', 'codigo' => defined('_ERR_BD_') ? _ERR_BD_ : 'ERR_BD', 'datos_nuevos' => json_encode($datos_nuevos)];
        }
    }

    private function ModificarArticulo(array $datos): array
    {
        $conex = $this->conex();
        
        $datos_nuevos = [
            'codigo_articulo' => $datos['codigo_articulo'],
            'id_catalogo'     => $datos['id_catalogo'],
            'id_estado'       => $datos['id_estado']
        ];

        try {
            $conex->beginTransaction();
            $sql = "UPDATE articulos_inventario SET id_catalogo = ?, id_estado = ? WHERE codigo_articulo = ?";
            $stmt = $conex->prepare($sql);
            $stmt->execute([$datos['id_catalogo'], $datos['id_estado'], $datos['codigo_articulo']]);
            $conex->commit();

            return ['accion' => 'exito', 'mensaje' => 'Artículo actualizado correctamente.', 'datos_nuevos' => json_encode($datos_nuevos)];
        } catch (Exception $e) {
            if ($conex->inTransaction()) $conex->rollBack();
            logs('Articulos Inventario', $e->getMessage(), 'Modelo_Modificar');
            return ['accion' => 'error', 'codigo' => defined('_ERR_BD_') ? _ERR_BD_ : 'ERR_BD', 'datos_nuevos' => json_encode($datos_nuevos)];
        }
    }

   private function EliminarArticulo($id): array
    {
        if (empty($id)) return ['accion' => 'error', 'codigo' => defined('_ERR_VACIO_') ? _ERR_VACIO_ : 'ERR_VACIO'];

        $conex = null;
        try {
            $conex = $this->conex();
            
            // 1. Llamamos al proceso almacenado
            $sql = "CALL RetirarArticuloSeguro(?, @resultado)";
            $stmt = $conex->prepare($sql);
            $stmt->execute([$id]);
            
            // 2. Leemos la respuesta de MariaDB
            $resQuery = $conex->query("SELECT @resultado AS res");
            $resultado_sp = $resQuery->fetchColumn();

            // 3. Devolvemos el mensaje según el código de la BD
            if ($resultado_sp == 1) {
                return ['accion' => 'exito', 'mensaje' => 'El artículo ha sido retirado del inventario.'];
            } else if ($resultado_sp == -2) {
                return ['accion' => 'error', 'mensaje' => 'El artículo no puede ser retirado porque está en uso o ya fue dado de baja.'];
            } else {
                return ['accion' => 'error', 'codigo' => defined('_ERR_BD_') ? _ERR_BD_ : 'ERR_BD'];
            }
        } catch (\PDOException $e) {
            logs('Articulos Inventario', $e->getMessage(), 'Modelo_Eliminar');
            return ['accion' => 'error', 'codigo' => defined('_ERR_BD_') ? _ERR_BD_ : 'ERR_BD'];
        } finally {
            $conex = null;
        }
    }
    private function ReincorporarArticulo($id): array
    {
        if (empty($id)) return ['accion' => 'error', 'codigo' => defined('_ERR_VACIO_') ? _ERR_VACIO_ : 'ERR_VACIO'];

        $conex = $this->conex();
        try {
            $conex->beginTransaction();
            $sql = "UPDATE articulos_inventario SET estatus = 1 WHERE codigo_articulo = ? AND estatus = 3";
            $stmt = $conex->prepare($sql);
            $stmt->execute([$id]);
            $conex->commit();

            return ['accion' => 'exito', 'mensaje' => 'Artículo reincorporado como Disponible.'];
        } catch (\PDOException $e) {
            if ($conex->inTransaction()) $conex->rollBack();
            return ['accion' => 'error', 'codigo' => defined('_ERR_BD_') ? _ERR_BD_ : 'ERR_BD'];
        }
    }

public function ConsultarArticulosLibres(): array { 
        $conex = null;
        try {
            $conex = $this->conex();
            
            // APLICACIÓN DE SUBCONSULTA: Extraemos el nombre del catálogo consultando la tabla anidada
            $sql = "SELECT e.codigo_articulo, 
                           e.codigo_club, 
                           IFNULL((SELECT nombre FROM catalogo c WHERE c.id_catalogo = e.id_catalogo), 'Artículo sin registrar') as articulo 
                    FROM articulos_inventario e 
                    WHERE e.estatus = 1";
                    
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