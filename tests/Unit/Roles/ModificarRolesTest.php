<?php

namespace tests\Unit\Roles;

use App\modelo\Conexion;
use Exception;
use PDO;
use PDOStatement;
use ReflectionClass;
use PHPUnit\Framework\TestCase;
use App\modelo\ModeloRoles;
use PHPUnit\Framework\Attributes\Group;

if (!defined('INVALID_ID')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

/**
 * RF-ROL-04: El sistema debe permitir modificar el nombre y la descripción de un rol
 * existente. El nombre no puede pertenecer a otro rol; conservar el propio nombre es válido.
 *
 * Escenarios:
 *  E1. Modificación válida con nombre y descripción.
 *  E2. Modificación solo del nombre.
 *  E3. El rol conserva su propio nombre.
 *  E4. Rol inexistente.
 *  E5. Nombre ya usado por otro rol.
 *  E6. Falla de la base de datos.
 */
#[Group('unit')]
#[Group('unitRoles')]
#[Group('modificarRoles')]
class ModificarRolesTest extends TestCase
{
    /** Registro de las sentencias ejecutadas: ['sql' => string, 'params' => array] */
    protected array $sentencias = [];

    /** Sentencias que el modelo intentó preparar sobre la base de datos general. */
    protected array $sentenciasBdGeneral = [];

    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $reflector->getProperty('instanciaSG')->setValue(null, null);
        $reflector->getProperty('instancia')->setValue(null, null);

        $this->sentencias = [];
        $this->sentenciasBdGeneral = [];
        parent::tearDown();
    }

    /**
     * Los roles viven en la base de datos de seguridad (conexSG). El PDO simulado se inyecta
     * en "instanciaSG"; la conexión general ("instancia") se reemplaza por un PDO que falla,
     * de modo que si el modelo consulta por error la base de datos general la prueba lo detecta.
     */
    protected function inyectarPdoMock(PDO $pdoMock): void
    {
        $pdoGeneral = $this->createMock(PDO::class);
        $pdoGeneral->method('prepare')->willReturnCallback(function (string $sql) {
            $this->sentenciasBdGeneral[] = $sql;
            throw new Exception('El módulo de roles no debe consultar la base de datos general.');
        });

        $reflector = new ReflectionClass(Conexion::class);
        $reflector->getProperty('instanciaSG')->setValue(null, $pdoMock);
        $reflector->getProperty('instancia')->setValue(null, $pdoGeneral);
    }

    /**
     * Crea el PDO simulado de la base de datos de seguridad.
     *
     * @param array $conteos   Resultados de las verificaciones de existencia (trait VerificarExistencia):
     *                         'tabla.columna.estatus' => int   (usar 'null' cuando no se filtra por estatus)
     *                         'propia.columna'        => int   (verificarExistenciaPropia)
     *                         Cualquier verificación no indicada devuelve 0 (no existe).
     * @param array $respuestas Fragmento de SQL => ['fetchAll' => , 'error' => Exception]
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

    private function obtenerDatosValidos(array $sobrescribir = []): array
    {
        return array_merge([
            'accion'      => 'modificar',
            'id'          => '5',
            'nombre'      => 'preparador fisico',
            'descripcion' => 'planifica la preparación física',
        ], $sobrescribir);
    }

    private function ejecutar(array $conteos, array $respuestas = [], array $datos = []): array
    {
        $this->inyectarPdoMock($this->crearPdoMock($conteos, $respuestas));
        return (new ModeloRoles())->procesarDatos($this->obtenerDatosValidos($datos));
    }

    // Caso 1 (E1): Modificación exitosa de nombre y descripción
    public function testModificacionExitosa(): void
    {
        $resultado = $this->ejecutar(['roles.id_rol.1' => 1]);

        $this->assertSame(['accion' => 'exito'], $resultado);

        $update = $this->sentenciaEjecutada('UPDATE `roles`');
        $this->assertStringContainsString('`descripcion` = :descripcion', $update['sql']);
        $this->assertSame([
            ':id'          => 5,
            ':nombre'      => 'Preparador Fisico',
            ':descripcion' => 'Planifica La Preparación Física',
        ], $update['params']);
    }

    // Caso 2 (E2): Modificación sin descripción no la sobrescribe
    public function testModificacionSinDescripcion(): void
    {
        $resultado = $this->ejecutar(['roles.id_rol.1' => 1], [], ['descripcion' => '']);

        $this->assertSame('exito', $resultado['accion']);

        $update = $this->sentenciaEjecutada('UPDATE `roles`');
        $this->assertStringNotContainsString('descripcion', $update['sql']);
        $this->assertSame([':id' => 5, ':nombre' => 'Preparador Fisico'], $update['params']);
    }

    // Caso 3 (E3): El rol puede guardar su mismo nombre sin considerarse duplicado
    public function testConservaSuPropioNombre(): void
    {
        $resultado = $this->ejecutar([
            'roles.id_rol.1'      => 1,
            'propia.nombre_rol'   => 1,
            'roles.nombre_rol.1'  => 1,
        ]);

        $this->assertSame('exito', $resultado['accion']);
        $this->assertNotNull($this->sentenciaEjecutada('UPDATE `roles`'));
    }

    // Caso 4 (E4): Rol inexistente
    public function testRolNoExiste(): void
    {
        $resultado = $this->ejecutar(['roles.id_rol.1' => 0]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(INVALID_ID, $resultado['codigo']);
        $this->assertNull($this->sentenciaEjecutada('UPDATE `roles`'));
    }

    // Caso 5 (E5): Nombre ya usado por otro rol
    public function testNombreDuplicado(): void
    {
        $resultado = $this->ejecutar([
            'roles.id_rol.1'     => 1,
            'propia.nombre_rol'  => 0,
            'roles.nombre_rol.1' => 1,
        ]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_NAME, $resultado['codigo']);
        $this->assertNull($this->sentenciaEjecutada('UPDATE `roles`'));
    }

    // Caso 6 (E6): Error de base de datos al modificar
    public function testErrorBaseDatosAlModificar(): void
    {
        $resultado = $this->ejecutar(['roles.id_rol.1' => 1], [
            'UPDATE `roles`' => ['error' => new Exception('Error al actualizar rol')],
        ]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error al actualizar rol', $resultado['codigo']);
    }
}
