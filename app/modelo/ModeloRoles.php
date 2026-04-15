<?php

namespace App\modelo;

use App\modelo\Conexion;
use Exception;

class ModeloRoles extends Conexion
{
    private $conexion = null;

    private int $id;
    private string $nombre;
    private array $id_modulo;
    private array $c_incluir;
    private array $c_modificar;
    private array $c_eliminar;
    private array $c_reporte;
    private array $c_otros;

    public function __construct() {}

    private array $campoWhitelist = [
        'id_rol' => 'id_rol',
        'nombre_rol' => 'nombre_rol'
    ];

    public function procesarDatos(array $datos)
    {
        if (empty($datos)) {
            throw new Exception('No se proporcionaron datos para procesar.');
        }

        $this->id = $datos['id'] ?? 0;
        $this->nombre = mb_convert_case(trim($datos['nombre'] ?? ''), MB_CASE_TITLE, "UTF-8");
        $accion = $datos['accion'] ?? null;

        $this->id_modulo   = $datos['id_modulo']   ?? [];
        $this->c_incluir   = $datos['c_incluir']   ?? [];
        $this->c_modificar = $datos['c_modificar'] ?? [];
        $this->c_eliminar  = $datos['c_eliminar']  ?? [];
        $this->c_reporte   = $datos['c_reporte']   ?? [];
        $this->c_otros     = $datos['c_otros']     ?? [];


        return match ($accion) {
            'incluir'   => $this->Incluir(),
            'modificar' => $this->Modificar(),
            'buscar'    => $this->Buscar(),
            default     => throw new Exception("Acción no válida."),
        };
    }
    public function consultar()
    {
        try {
            $this->conexion = parent::conexSG();
            $sentencia = 'SELECT * FROM roles';
            $stmt = $this->conexion->prepare($sentencia);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            return ['accion' => 'consultar', 'datos' => $datos];
        } catch (Exception $e) {
            error_log($e->getMessage());
            return ['accion' => 'error', 'mensaje' => $e->getMessage()];
        }
    }

    public function consultarModulo()
    {
        try {
            $this->conexion = parent::conexSG();
            $sentencia = 'SELECT modulo.id_modulo, modulo.nombre_modulo FROM `modulo` WHERE modulo.id_modulo NOT IN (4, 5, 8)';
            $stmt = $this->conexion->prepare($sentencia);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            $resultado = array('accion' => 'consultarModulo', 'datos' => $datos);
        } catch (Exception $e) {
            error_log($e->getMessage());
            $resultado = array('accion' => 'error', 'mensaje' => $e->getMessage());
        }
        return $resultado;
    }

    public function Buscar()
    {
        try {
            $this->conexion = self::conexSG();
            $sentencia = 'SELECT roles.id_rol,roles.nombre_rol,permiso.eliminar,permiso.modificar,permiso.incluir,permiso.reporte,permiso.otros,modulo.nombre_modulo,modulo.id_modulo FROM `roles` 
            INNER JOIN permiso ON roles.id_rol=permiso.id_rol 
            INNER JOIN modulo ON permiso.id_modulo=modulo.id_modulo 
            WHERE roles.id_rol=:id';
            $stmt = $this->conexion->prepare($sentencia);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            $resultado = array('accion' => 'buscar', 'datos' => $datos);
        } catch (Exception $e) {
            error_log($e->getMessage());
            $resultado = array('accion' => 'error', 'mensaje' => $e->getMessage());
        }
        return $resultado;
    }

    private function Incluir()
    {
        try {

            if ($this->verificarExistencia('nombre_rol', $this->nombre, 1)) {
                return ['accion' => 'error', 'mensaje' => 'El rol ya existe'];
            }

            if (!$this->verificarModulo($this->id_modulo)) {
                return ['accion' => 'error', 'mensaje' => 'Un modulo no existe o es restringido'];
            }

            $this->conexion = self::conexSG();
            $this->conexion->beginTransaction();

            $sql = 'INSERT INTO `roles`(`nombre_rol`, `estatus`) VALUES (:nombre, 1)';
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([':nombre' => $this->nombre]);

            $id_rol = $this->conexion->lastInsertId();

            $sentencia = "INSERT INTO `permiso`(`id_rol`, `id_modulo`, `eliminar`, `modificar`, `incluir`, `reporte`, `otros`) 
                        VALUES (:id_rol, :id_modulo, :eliminar, :modificar, :incluir, :reporte, :otros)";
            $stmtPermiso = $this->conexion->prepare($sentencia);

            foreach ($this->id_modulo as $modulo) {
                $stmtPermiso->execute([
                    ':id_rol'    => $id_rol,
                    ':id_modulo' => (int)$modulo,
                    ':eliminar'  => isset($this->c_eliminar[$modulo]) ? 1 : 0,
                    ':modificar' => isset($this->c_modificar[$modulo]) ? 1 : 0,
                    ':incluir'   => isset($this->c_incluir[$modulo]) ? 1 : 0,
                    ':reporte'   => isset($this->c_reporte[$modulo]) ? 1 : 0,
                    ':otros'     => isset($this->c_otros[$modulo]) ? 1 : 0
                ]);
            }

            // SI LLEGA AQUÍ, TODO SE GUARDA
            $this->conexion->commit();
            return ['accion' => 'incluir', 'mensaje' => 'Rol registrado correctamente'];
        } catch (Exception $e) {
            // SI ALGO FALLA, SE BORRA EL ROL TAMBIÉN
            if ($this->conexion && $this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            error_log("Error crítico en Incluir: " . $e->getMessage());
            return ['accion' => 'error', 'mensaje' => $e->getMessage()];
        } finally {
            $this->conexion = null;
        }
    }

    private function Modificar(){
        try {
            if(!$this->verificarExistenciaPropia('nombre_rol', $this->nombre, $this->id)){
                if($this->verificarExistencia('nombre_rol', $this->nombre, 1)){
                    return ['accion' => 'error', 'mensaje' => 'El rol ya existe'];
                }
            }
            if (!$this->verificarModulo($this->id_modulo)) {
                return ['accion' => 'error', 'mensaje' => 'Un modulo no existe o es restringido'];
            }
            $this->conexion = self::conexSG();
            $this->conexion->beginTransaction();
            $sql = 'UPDATE `roles` SET `nombre_rol`=:nombre WHERE id_rol=:id';
            $stmt =$this->conexion->prepare($sql);
            $parametros = [
                ':id' => $this->id,
                ':nombre' => $this->nombre
            ];
            $stmt->execute($parametros);

            $sql = 'DELETE FROM `permiso` WHERE `id_rol` = :id';
            $stmt= $this->conexion->prepare($sql);
            $stmt->execute([':id' => $this->id]);

            $sql = 'INSERT INTO `permiso`(`id_rol`, `id_modulo`, `eliminar`, `modificar`, `incluir`, `reporte`, `otros`) 
                                VALUES (:id_rol,:id_modulo,:eliminar,:modificar,:incluir,:reporte,:otros)';
            $stmt = $this->conexion->prepare($sql);

            foreach ($this->id_modulo as $modulo) {
                $stmt->execute([
                    ':id_rol'    => $this->id,
                    ':id_modulo' => (int)$modulo,
                    ':eliminar'  => isset($this->c_eliminar[$modulo]) ? 1 : 0,
                    ':modificar' => isset($this->c_modificar[$modulo]) ? 1 : 0,
                    ':incluir'   => isset($this->c_incluir[$modulo]) ? 1 : 0,
                    ':reporte'   => isset($this->c_reporte[$modulo]) ? 1 : 0,
                    ':otros'     => isset($this->c_otros[$modulo]) ? 1 : 0
                ]);
            }

            $this->conexion->commit();
            return ['accion' => 'modificar', 'mensaje' => 'Rol modificado correctamente'];
        }catch (Exception $e) {
            if ($this->conexion && $this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            error_log("Error crítico en Incluir: " . $e->getMessage());
            return ['accion' => 'error', 'mensaje' => $e->getMessage()];
        }finally {
            $this->conexion = null;
        }
    }

    private function verificarExistencia($campo, $valor, $estatus): bool
    {
        try {
            // Validar campo contra whitelist
            if (!array_key_exists($campo, $this->campoWhitelist)) {
                throw new Exception('Campo no permitido.');
            }

            $columna = $this->campoWhitelist[$campo];

            $this->conexion = self::conexSG();
            $sentencia = "SELECT COUNT(*) FROM `roles` WHERE $columna = :valor AND estatus = :estatus";
            $str = $this->conexion->prepare($sentencia);
            $str->bindValue(':valor', $valor);
            $str->bindValue(':estatus', $estatus, \PDO::PARAM_INT);
            $str->execute();

            $count = (int)$str->fetchColumn();
            return $count > 0;
        } catch (Exception $e) {
            error_log("roles " . $e->getMessage());
            return false;
        }
    }

    private function verificarExistenciaPropia($campo, $valor, $id): bool
    {

        try {
            if (!array_key_exists($campo, $this->campoWhitelist)) {
                throw new Exception('Campo no permitido.');
            }

            $columna = $this->campoWhitelist[$campo];

            $this->conexion = self::conexSG();
            $sentencia = "SELECT roles.id_rol FROM `roles` WHERE roles.id_rol=:id AND $campo = :valor AND roles.estatus=1;";
            $str = $this->conexion->prepare($sentencia);
            $str->bindParam(':id', $id,);
            $str->bindParam(':valor', $valor);
            $str->execute();

            $respuesta = $str->fetchAll();
            if ($respuesta) {
                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            error_log("Error en coincide Roles: " . $e->getMessage());
            return false;
        }
    }

    private function verificarModulo($modulo): bool
    {
        try {
            if (empty($modulo)) return false;

            $this->conexion = self::conexSG();

            // Limpiamos el array para asegurar que solo haya valores únicos y numéricos
            $idsUnicos = array_unique(array_map('intval', $modulo));
            $placeholders = implode(',', array_fill(0, count($idsUnicos), '?'));

            $sentencia = "SELECT COUNT(id_modulo) as total FROM `modulo` 
                        WHERE id_modulo IN ($placeholders) 
                        AND id_modulo NOT IN (4, 5, 8)";

            $str = $this->conexion->prepare($sentencia);
            $str->execute(array_values($idsUnicos));
            $resultado = $str->fetch();

            // Log para depurar si falla
            $totalDB = (int)($resultado['total'] ?? 0);
            $totalEnviado = count($idsUnicos);

            if ($totalDB !== $totalEnviado) {
                error_log("Fallo validación: DB tiene $totalDB y se enviaron $totalEnviado");
                return false;
            }

            return true;
        } catch (Exception $e) {
            error_log("Error en verificarModulos: " . $e->getMessage());
            return false;
        }
    }
}
