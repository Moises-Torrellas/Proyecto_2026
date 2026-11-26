<?php

namespace tests\Unit\Permisos;

use App\modelo\Conexion;
use PDO;
use PDOStatement;
use ReflectionClass;
use PHPUnit\Framework\TestCase;
use App\modelo\ModeloPermisos;
use Exception;
use PHPUnit\Framework\Attributes\Group;

if (!defined('INVALID_ID')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

/**
 * RF-PER-04: El sistema debe permitir modificar el nombre y la descripción de un permiso
 * existente (la clave técnica y el módulo no se modifican). El nombre no puede pertenecer
 * a otro permiso. Si no se envía descripción no debe enviarse NULL a la base de datos,
 * porque la columna no lo admite. Los errores deben devolverse en la clave "codigo",
 * que es la que lee el controlador.
 *
 * Escenarios:
 *  E1. Modificación con nombre y descripción.
 *  E2. Modificación sin descripción.
 *  E3. El permiso conserva su propio nombre.
 *  E4. Permiso inexistente.
 *  E5. Nombre usado por otro permiso.
 *  E6. Falla de la base de datos.
 */
#[Group('unit')]
#[Group('unitPermisos')]
#[Group('modificarPermisos')]
class ModificarPermisosTest extends TestCase
{
    /** Registro de las sentencias ejecutadas: ['sql' => string, 'params' => array] */
    protected array $sentencias = [];

    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $reflector->getProperty('instanciaSG')->setValue(null, null);
        $reflector->getProperty('instancia')->setValue(null, null);

        $this->sentencias = [];
        parent::tearDown();
    }

    /**
     * Los permisos viven en la base de datos de seguridad (conexSG), por lo que el PDO
     * simulado se inyecta en la propiedad estática "instanciaSG" de Conexion.
     */
    protected function inyectarPdoMock(PDO $pdoMock): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $reflector->getProperty('instanciaSG')->setValue(null, $pdoMock);
        $reflector->getProperty('instancia')->setValue(null, $pdoMock);
    }

    /**
     * @param array $conteos   Resultados de las verificaciones de existencia (trait VerificarExistencia):
     *                         'tabla.columna.estatus' => int   (usar 'null' cuando no se filtra por estatus)
     *                         'propia.columna'        => int   (verificarExistenciaPropia)
     *                         Cualquier verificación no indicada devuelve 0 (no existe).
     * @param array $respuestas Fragmento de SQL => ['fetchAll' => , 'rowCount' => , 'error' => Exception]
     *                         Se usa el primer fragmento contenido en la sentencia (el orden importa).
     */
    protected function crearPdoMock(array $conteos = [], array $respuestas = []): PDO
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);
        $pdoMock->method('rollBack')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($conteos, $respuestas) {
            if (preg_match('/SELECT COUNT\(\*\) FROM `\w+`\s+WHERE\s+\w+\s*=\s*:id\s+AND\s+(\w+)\s*=\s*:valor/', $sql, $m)) {
                return $this->crearSentenciaConteo(fn() => $conteos['propia.' . $m[1]] ?? 0);
            }
            if (preg_match('/SELECT COUNT\(\*\) FROM `(\w+)` WHERE (\w+) = :valor/', $sql, $m)) {
                return $this->crearSentenciaConteo(
                    fn($estatus) => $conteos[$m[1] . '.' . $m[2] . '.' . ($estatus ?? 'null')] ?? 0
                );
            }
            return $this->crearSentencia($sql, $respuestas);
        });

        return $pdoMock;
    }

    private function crearSentenciaConteo(callable $resolver): PDOStatement
    {
        $estatus = null;
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('bindValue')->willReturnCallback(function ($param, $valor) use (&$estatus) {
            if ($param === ':estatus') {
                $estatus = $valor;
            }
            return true;
        });
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchColumn')->willReturnCallback(function () use (&$estatus, $resolver) {
            return $resolver($estatus);
        });
        return $stmt;
    }

    private function crearSentencia(string $sql, array $respuestas): PDOStatement
    {
        $config = [];
        foreach ($respuestas as $fragmento => $respuesta) {
            if (str_contains($sql, $fragmento)) {
                $config = $respuesta;
                break;
            }
        }

        $binds = new \ArrayObject();
        $stmt = $this->createMock(PDOStatement::class);
        $registrarBind = function ($param, $valor) use ($binds) {
            $binds[$param] = $valor;
            return true;
        };
        $stmt->method('bindValue')->willReturnCallback($registrarBind);
        $stmt->method('bindParam')->willReturnCallback($registrarBind);
        $stmt->method('execute')->willReturnCallback(function (?array $params = null) use ($sql, $binds, $config) {
            $this->sentencias[] = ['sql' => $sql, 'params' => $params ?? $binds->getArrayCopy()];
            if (isset($config['error'])) {
                throw $config['error'];
            }
            return true;
        });
        $stmt->method('fetchAll')->willReturn($config['fetchAll'] ?? []);
        $stmt->method('rowCount')->willReturn($config['rowCount'] ?? 1);

        return $stmt;
    }

    protected function sentenciaEjecutada(string $fragmento): ?array
    {
        foreach ($this->sentencias as $sentencia) {
            if (str_contains($sentencia['sql'], $fragmento)) {
                return $sentencia;
            }
        }
        return null;
    }

    /** Mensaje de error devuelto por el modelo, venga en 'codigo' o en 'mensaje'. */
    protected function textoError(array $resultado): ?string
    {
        return $resultado['codigo'] ?? $resultado['mensaje'] ?? null;
    }

    private function obtenerDatosValidos(array $sobrescribir = []): array
    {
        return array_merge([
            'accion'      => 'modificar',
            'id'          => '9',
            'nombre'      => 'reinscribir atleta',
            'descripcion' => 'permite reinscribir atletas',
        ], $sobrescribir);
    }

    private function ejecutar(array $conteos, array $respuestas = [], array $datos = []): array
    {
        $this->inyectarPdoMock($this->crearPdoMock($conteos, $respuestas));
        return (new ModeloPermisos())->ProcesarDatos($this->obtenerDatosValidos($datos));
    }

    // Caso 1 (E1): Modificación exitosa de nombre y descripción
    public function testModificacionExitosa(): void
    {
        $resultado = $this->ejecutar(['permisos.id_permiso.1' => 1]);

        $this->assertSame(['accion' => 'exito'], $resultado);

        $update = $this->sentenciaEjecutada('UPDATE `permisos`');
        $this->assertStringNotContainsString('clave', $update['sql']);
        $this->assertStringNotContainsString('id_modulo', $update['sql']);
        $this->assertSame([
            ':nombre'      => 'Reinscribir Atleta',
            ':descripcion' => 'Permite Reinscribir Atletas',
            ':id'          => '9',
        ], $update['params']);
    }

    // Caso 2 (E2): Sin descripción no se envía NULL a una columna NOT NULL
    public function testModificacionSinDescripcion(): void
    {
        $resultado = $this->ejecutar(['permisos.id_permiso.1' => 1], [], ['descripcion' => '']);

        $this->assertSame('exito', $resultado['accion']);

        $update = $this->sentenciaEjecutada('UPDATE `permisos`');
        $this->assertFalse(
            array_key_exists(':descripcion', $update['params']) && $update['params'][':descripcion'] === null,
            'Se envía descripcion = NULL y la columna `descripcion` es NOT NULL.'
        );
    }

    // Caso 3 (E3): El permiso puede guardar su mismo nombre
    public function testConservaSuPropioNombre(): void
    {
        $resultado = $this->ejecutar([
            'permisos.id_permiso.1' => 1,
            'propia.nombre'         => 1,
            'permisos.nombre.1'     => 1,
        ]);

        $this->assertSame('exito', $resultado['accion']);
    }

    // Caso 4 (E4): Permiso inexistente
    public function testPermisoNoExiste(): void
    {
        $resultado = $this->ejecutar(['permisos.id_permiso.1' => 0]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('El permiso seleccionado no existe.', $this->textoError($resultado));
        $this->assertNull($this->sentenciaEjecutada('UPDATE `permisos`'));
    }

    // Caso 5 (E5): Nombre usado por otro permiso
    public function testNombreDuplicado(): void
    {
        $resultado = $this->ejecutar([
            'permisos.id_permiso.1' => 1,
            'propia.nombre'         => 0,
            'permisos.nombre.1'     => 1,
        ]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Ya existe otro permiso con ese nombre.', $this->textoError($resultado));
        $this->assertNull($this->sentenciaEjecutada('UPDATE `permisos`'));
    }

    // Caso 6 (E4/E5): Los errores se devuelven en la clave "codigo" que lee el controlador
    public function testErrorEnClaveCodigo(): void
    {
        $resultado = $this->ejecutar(['permisos.id_permiso.1' => 0]);

        $this->assertArrayHasKey(
            'codigo',
            $resultado,
            'Modificar() devuelve el error en "mensaje", pero el controlador lee "codigo".'
        );
    }

    // Caso 7 (E6): Error de base de datos al modificar
    public function testErrorBaseDatosAlModificar(): void
    {
        $resultado = $this->ejecutar(['permisos.id_permiso.1' => 1], [
            'UPDATE `permisos`' => ['error' => new Exception('Error al actualizar permiso')],
        ]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error al actualizar permiso', $this->textoError($resultado));
    }
}
