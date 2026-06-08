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
        // Whitelist de campos permitidos para la tabla equipamientos
        $this->campoWhitelist = ['id_catalogo', 'id_estados', 'estatus'];
        $this->llavePrimaria = 'id_equipamiento';
    }

    public function ProcesarDatos(array $datos): array
    {
        $accion = $datos['accion'] ?? null;

        return match ($accion) {
            'consultar'     => $this->ConsultarEquipos(),
            'cargar_combos' => $this->CargarCombos(),
            'incluir'       => $this->IncluirEquipamiento($datos),
            'modificar'     => $this->ModificarEquipamiento($datos),
            'eliminar'      => $this->EliminarEquipamiento($datos['id_equipamiento'] ?? null),
            default         => throw new Exception('Acción no válida en Equipamientos.')
        };
    }

    private function ConsultarEquipos(): array
    {
        try {
            // INNER JOIN Estricto: Asegura la integridad referencial completa de la consulta
            $sql = "SELECT e.id_equipamiento, 
                           c.nombre as articulo, 
                           c.talla, 
                           ce.nombre as categoria, 
                           es.nombre as estado, 
                           es.nivel_estado,
                           e.id_catalogo,
                           e.id_estados as id_estado
                    FROM equipamientos e
                    INNER JOIN catalogos c ON e.id_catalogo = c.id_catalogo
                    INNER JOIN categoria_catalogo ce ON c.id_categoria = ce.id_categoria
                    INNER JOIN estado_equipamiento es ON e.id_estados = es.id_estado
                    WHERE e.estatus = 1
                    ORDER BY e.id_equipamiento DESC";
            
            $stmt = $this->conex()->prepare($sql);
            $stmt->execute();
            $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ['accion' => 'consultar', 'datos' => $datos];
        } catch (Exception $e) {
            logs('Equipamientos', $e->getMessage(), 'Modelo_Consultar');
            return ['accion' => 'error', 'mensaje' => 'Error BD: ' . $e->getMessage()];
        }
    }

    private function CargarCombos(): array
    {
        try {
            $conex = $this->conex();
            
            // Traemos el catálogo
            $stmtCat = $conex->query("SELECT id_catalogo, nombre, talla FROM catalogos");
            $catalogos = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

            // Traemos los estados ordenados por su nivel (Excelente, Regular, Malo...)
            $stmtEst = $conex->query("SELECT id_estado, nombre FROM estado_equipamiento ORDER BY nivel_estado ASC");
            $estados = $stmtEst->fetchAll(PDO::FETCH_ASSOC);

            return ['accion' => 'cargar_combos', 'catalogos' => $catalogos, 'estados' => $estados];
        } catch (Exception $e) {
            return ['accion' => 'error', 'mensaje' => 'Error al cargar listas desplegables.'];
        }
    }

    private function IncluirEquipamiento(array $datos): array
    {
        $conex = $this->conex();
        try {
            // INICIAMOS LA TRANSACCIÓN DE SEGURIDAD
            $conex->beginTransaction();

            $sql = "INSERT INTO equipamientos (id_catalogo, id_estados, estatus) VALUES (?, ?, 1)";
            $stmt = $conex->prepare($sql);
            
            // Usamos $datos['id_estado'] porque así lo envía el JS desde el formulario
            $stmt->execute([$datos['id_catalogo'], $datos['id_estado']]);

            // SI NO HUBO ERRORES, CONFIRMAMOS LOS CAMBIOS
            $conex->commit();

            return ['accion' => 'exito', 'mensaje' => 'Equipamiento registrado en el inventario.'];
        } catch (Exception $e) {
            // REVERTIMOS SI HAY FALLO
            if ($conex->inTransaction()) {
                $conex->rollBack();
            }
            logs('Equipamientos', $e->getMessage(), 'Modelo_Incluir');
            return ['accion' => 'error', 'codigo' => 'Error BD: ' . $e->getMessage()];
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
            if ($conex->inTransaction()) {
                $conex->rollBack();
            }
            logs('Equipamientos', $e->getMessage(), 'Modelo_Modificar');
            return ['accion' => 'error', 'codigo' => 'Error BD: ' . $e->getMessage()];
        }
    }

    private function EliminarEquipamiento($id): array
    {
        if (empty($id)) {
            return ['accion' => 'error', 'codigo' => 'ID de equipamiento no proporcionado.'];
        }

        $conex = $this->conex();
        try {
            $conex->beginTransaction();

            $sql = "DELETE FROM equipamientos WHERE id_equipamiento = ?";
            $stmt = $conex->prepare($sql);
            $stmt->execute([$id]);

            $conex->commit();

            return ['accion' => 'exito', 'mensaje' => 'Equipamiento retirado del inventario.'];
        } catch (\PDOException $e) {
            if ($conex->inTransaction()) {
                $conex->rollBack();
            }
            
            // Protección contra violación de integridad referencial (Código SQL 23000)
            if ($e->getCode() == 23000) {
                return ['accion' => 'error', 'codigo' => 'No se puede eliminar: El equipo tiene un historial de asignaciones activo.'];
            }
            return ['accion' => 'error', 'codigo' => 'Error al eliminar el equipamiento.'];
        }
    }

    public function ConsultarEquiposLibres(): array
    {
        $conex = null;
        try {
            $conex = $this->conex();
            
            $sql = "SELECT e.id_equipamiento, IFNULL(c.nombre, 'Artículo sin registrar') as articulo 
                    FROM equipamientos e 
                    LEFT JOIN catalogos c ON e.id_catalogo = c.id_catalogo 
                    WHERE e.estatus = 1";
                    
            $equipos = $conex->query($sql)->fetchAll(PDO::FETCH_ASSOC);

            return ['accion' => 'exito', 'datos' => $equipos];
        } catch (Exception $e) {
            logs('Equipamientos', $e->getMessage(), 'Modelo_EquiposLibres');
            return ['accion' => 'error', 'datos' => []];
        } finally {
            $conex = null;
        }
    }

    public function CambiarEstatus($id_equipamiento, $nuevo_estatus, $conexion_transaccional = null): bool
    {
        $conex = $conexion_transaccional ?? $this->conex();

        try {
            $sql = "UPDATE equipamientos SET estatus = :estatus WHERE id_equipamiento = :id";
            $stmt = $conex->prepare($sql);
            $stmt->execute([
                ':estatus' => $nuevo_estatus,
                ':id'     => $id_equipamiento
            ]);

            return true;
        } catch (Exception $e) {
            logs('Equipamientos', $e->getMessage(), 'Modelo_CambiarEstatus');
            throw new Exception("Error interno al actualizar el estado físico del equipo.");
        } finally {
            if ($conexion_transaccional === null) {
                $conex = null;
            }
        }
    }
}