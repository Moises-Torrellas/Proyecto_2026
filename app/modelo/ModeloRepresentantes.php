<?php

namespace App\modelo;

use App\modelo\ModeloBase;
use Exception;

class ModeloRepresentantes extends ModeloBase
{
    private $id;
    private $cedula;
    private $nombre;
    private $apellido;
    private $telefono;
    private $direccion;
    private $nacionalidad;
    public function __construct()
    {
        parent::__construct();
        //Definimos los campos permitidos para usar en las validaciones
        $this->campoWhitelist = [
            'cedula' => 'cedula',
            'telefono' => 'telefono',
            'id' => 'id_representante'
        ];
        //Definimos la llave primaria de la tabla en la base de datos
        $this->llavePrimaria = 'id_representante';
    }


    public function ProcesarDatos(array $datos): array
    {
        //si datos esta vacio ejecutamos la excepcion
        if (empty($datos)) {
            throw new Exception('No se proporcionaron datos para procesar.');
        }
        //Procesamos los datos
        $this->cedula = $datos['cedula'] ?? '';
        $this->telefono = $datos['telefono'] ?? '';
        $this->id = $datos['id'] ?? null;
        $this->direccion = mb_convert_case(trim($datos['direccion'] ?? ''), MB_CASE_TITLE, "UTF-8");
        $this->nacionalidad = $datos['nacionalidad'] ?? null;
        $this->nombre = mb_convert_case(trim($datos['nombre'] ?? ''), MB_CASE_TITLE, "UTF-8");
        $this->apellido = mb_convert_case(trim($datos['apellido'] ?? ''), MB_CASE_TITLE, "UTF-8");
        //ejecutamos la accion enviada por el controlador
        $accion = $datos['accion'] ?? null;
        return match ($accion) {
            'incluir'   => $this->Incluir(),
            'eliminar'  => $this->Eliminar(),
            'buscar' => $this->Buscar(),
            'modificar' => $this->Modificar(),
            default => throw new Exception('La accion no es valida')
        };
    }

    public function Consultar(array $filtro = []): array
    {
        try {
            $conex = $this->conex();
            $params = []; // Unificamos el nombre de la variable

            // 1. Iniciamos la sentencia con WHERE 1=1 para concatenar AND tranquilamente
            $sentencia = "SELECT * FROM representantes WHERE 1=1";

            // 2. BUSCADOR GENERAL (El que viene del keyup)
            if (!empty($filtro['filtro'])) {
                $p = "%" . $filtro['filtro'] . "%";
                $sentencia .= " AND (
                cedula LIKE :f1 OR 
                nombre LIKE :f2 OR 
                apellido LIKE :f3 OR 
                telefono LIKE :f4
            )";
                $params[':f1'] = $p;
                $params[':f2'] = $p;
                $params[':f3'] = $p;
                $params[':f4'] = $p;
            }

            // 3. FILTROS ESPECÍFICOS (Si vienen del Modal o propiedades del objeto)
            if (!empty($this->cedula)) {
                $sentencia .= " AND cedula LIKE :cedula";
                $params[':cedula'] = trim($this->cedula) . "%";
            }

            if (!empty($this->nombre)) {
                $sentencia .= " AND nombre LIKE :nombre";
                $params[':nombre'] = "%" . trim($this->nombre) . "%";
            }

            if (!empty($this->apellido)) {
                $sentencia .= " AND apellido LIKE :apellido";
                $params[':apellido'] = "%" . trim($this->apellido) . "%";
            }

            // 4. Orden (Asegúrate de usar una columna que exista, como id_representante)
            $sentencia .= " ORDER BY id_representante ASC";

            $stmt = $conex->prepare($sentencia);

            // IMPORTANTE: Pasar los parámetros al execute
            $stmt->execute($params);

            $datos = $stmt->fetchAll();

            return array('accion' => 'consultar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Representantes', $e->getMessage(), 'Modelo_Consultar');
            return array('accion' => 'error');
        } finally {
            $conex = NULL;
        }
    }

    private function Incluir(): array
    {
        try {
            //para verificar existencia los parametros que recibe son string $campo, $valor, string $tabla, ?int $estatus = 1, string $db = 'general', bool $bloquear = false

            /* string $campo     Nombre lógico del campo (ej: 'cedula', 'correo'). Se valida contra la whitelist definida en el __construct.
            mixed  $valor     El valor específico que se desea buscar en la tabla.
            string $tabla     Nombre de la tabla donde se realizará la búsqueda.
            int|null $estatus Filtro por estado del registro. (1 = Activo por defecto, NULL = Buscar en todo) sirve si la tabla tiene estatus para borrado logico.
            string $db        Identificador de la conexión a usar (por defecto 'general' para la base de datos del club) para la base de datos de seguridad se le pasa como parametro 'sg' .
            bool   $bloquear  Si es true, aplica 'FOR UPDATE' para bloquear la fila (Manejo de concurrencia).
            
            return bool Devuelve true si el registro existe, false en caso contrario.
            throws Exception Si el campo proporcionado no está en la whitelist de seguridad. */
            $conex = $this->conex();
            $conex->beginTransaction();
            if ($this->verificarExistencia('cedula', $this->cedula, 'representantes', NULL, bloquear: true)) {
                //estas constantes como DUPLICATE_CEDULA o DUPLICATE_PHONE estan definidas en el config.php
                throw new Exception(DUPLICATE_CEDULA);
            }
            if ($this->verificarExistencia('telefono', $this->telefono, 'representantes', NULL, bloquear: true)) {
                throw new Exception(DUPLICATE_PHONE);
            }

            $sentencia = "INSERT INTO representantes (`cedula`, `nacionalidad`, `nombre`, `apellido`, `telefono`, `direccion`) VALUES (:cedula, :nacionalidad,:nombre, :apellido,:telefono, :direccion)";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':cedula', $this->cedula);
            $stmt->bindParam(':nacionalidad', $this->nacionalidad);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':apellido', $this->apellido);
            $stmt->bindParam(':telefono', $this->telefono);
            $stmt->bindParam(':direccion', $this->direccion);
            $stmt->execute();

            $conex->commit();
            return array('accion' => 'exito');
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollback();
            }
            logs('Representantes', $e->getMessage(), 'Modelo');
            return array('accion' => 'error', 'codigo' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    private function Modificar(): array
    {
        try {
            $conex = $this->conex();
            $conex->beginTransaction();

            /*
            * EXPLICACIÓN DE PARÁMETROS - verificarExistenciaPropia:
            * 
            * 1. $campo: El nombre del dato a validar (ej: 'cedula'). Se verifica contra una lista de campos permitidos por seguridad.
            * 
            * 2. $valor: El contenido que quieres buscar (ej: '27123456'). Es el valor que el usuario ingresó en el formulario.
            * 
            * 3. $id: La llave primaria del registro que estás editando actualmente. Sirve para comparar si el valor ya es tuyo.
            * 
            * 4. $tabla: Nombre de la tabla en la base de datos donde se hará la consulta (ej: 'representantes').
            * 
            * 5. $estatus: Filtro de estado. Por defecto busca activos (1), pero si envías NULL busca en todos los registros.
            * 
            * 6. $db: Indica cuál conexión de base de datos usar (por defecto usa la conexión 'general').
            * 
            * 7. $bloquear: Si es true, activa el "FOR UPDATE". Esto pausa otros procesos que intenten leer o cambiar esta fila 
            *    específica hasta que tú termines tu transacción, evitando choques de datos en el sistema de la academia.
 */
            if (!$this->verificarExistenciaPropia('cedula', $this->cedula, $this->id, 'representantes', NULL, bloquear: true)) {
                if ($this->verificarExistencia('cedula', $this->cedula, 'representantes', NULL, bloquear: true)) {
                    throw new Exception(DUPLICATE_CEDULA);
                }
            }
            if (!$this->verificarExistenciaPropia('telefono', $this->telefono, $this->id, 'representantes', NULL, bloquear: true)) {
                if ($this->verificarExistencia('telefono', $this->telefono, 'representantes', NULL, bloquear: true)) {
                    throw new Exception(DUPLICATE_PHONE);
                }
            }
            $sentencia = "UPDATE representantes SET 
            cedula = :cedula, 
            nacionalidad = :nacionalidad, 
            nombre = :nombre, 
            apellido = :apellido, 
            telefono = :telefono, 
            direccion = :direccion 
            WHERE id_representante = :id_representante";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':cedula', $this->cedula);
            $stmt->bindParam(':nacionalidad', $this->nacionalidad);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':apellido', $this->apellido);
            $stmt->bindParam(':telefono', $this->telefono);
            $stmt->bindParam(':direccion', $this->direccion);
            $stmt->bindParam(':id_representante', $this->id);
            $stmt->execute();

            $conex->commit();
            return array('accion' => 'exito');
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollback();
            }
            logs('Representantes', $e->getMessage(), 'Modelo_Modificar');
            return array('accion' => 'error', 'codigo' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    function Buscar(): array
    {
        try {
            $conex = $this->conex();
            $sentencia = "SELECT * FROM representantes WHERE id_representante = :id";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            return array('accion' => 'buscar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Representantes', $e->getMessage(), 'Modelo');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    private function Eliminar(): array
    {
        try {
            $conex = $this->conex();
            $conex->beginTransaction();
            if (!$this->verificarExistencia('id', $this->id, 'representantes', NULL, bloquear:true)) {
                throw new Exception(INVALID_ID);
            }
            if ($this->verificarExistencia('id', $this->id, 'atletas', NULL, bloquear:true)) {
                throw new Exception(ASSOCIATES);
            }
            $sentencia = "DELETE FROM representantes WHERE id_representante = :id";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
            $conex->commit();
            return array('accion' => 'exito');
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollback();
            }
            logs('Representantes', $e->getMessage(), 'Modelo_Eliminar');
            return array('accion' => 'error', 'codigo' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }
}
