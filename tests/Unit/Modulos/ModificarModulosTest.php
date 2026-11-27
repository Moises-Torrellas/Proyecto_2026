<?php

namespace tests\Unit\Modulos;

use App\modelo\Conexion;
use PDO;
use PDOStatement;
use ReflectionClass;
use PHPUnit\Framework\TestCase;
use App\modelo\ModeloModulos;
use Exception;
use PHPUnit\Framework\Attributes\Group;

if (!defined('INVALID_ID')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitModulos')]
#[Group('modificarModulos')]
class ModificarModulosTest extends TestCase
{
    protected array $sentencias = [];

    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $reflector->getProperty('instanciaSG')->setValue(null, null);
        $reflector->getProperty('instancia')->setValue(null, null);

        $this->sentencias = [];
        parent::tearDown();
    }

    protected function inyectarPdoMock(PDO $pdoMock): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $reflector->getProperty('instanciaSG')->setValue(null, $pdoMock);
        $reflector->getProperty('instancia')->setValue(null, $pdoMock);
    }

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

    private function obtenerDatosValidos(array $sobrescribir = []): array
    {
        return array_merge([
            'accion'      => 'modificar',
            'id'          => '6',
            'nombre'      => 'gestión de atletas',
            'descripcion' => 'registro y control de atletas',
        ], $sobrescribir);
    }

    private function ejecutar(array $conteos, array $respuestas = [], array $datos = []): array
    {
        $this->inyectarPdoMock($this->crearPdoMock($conteos, $respuestas));
        return (new ModeloModulos())->ProcesarDatos($this->obtenerDatosValidos($datos));
    }

    public function testModificacionExitosa(): void
    {
        $resultado = $this->ejecutar(['modulos.id_modulo.1' => 1]);

        $this->assertSame(['accion' => 'exito'], $resultado);

        $update = $this->sentenciaEjecutada('UPDATE `modulos`');
        $this->assertStringNotContainsString('icono', $update['sql']);
        $this->assertStringNotContainsString('estatus', $update['sql']);
        $this->assertSame([
            ':nombre'      => 'Gestión De Atletas',
            ':descripcion' => 'Registro Y Control De Atletas',
            ':id'          => '6',
        ], $update['params']);
    }

    public function testConservaSuPropioNombre(): void
    {
        $resultado = $this->ejecutar([
            'modulos.id_modulo.1'     => 1,
            'propia.nombre_modulo'    => 1,
            'modulos.nombre_modulo.1' => 1,
        ]);

        $this->assertSame('exito', $resultado['accion']);
        $this->assertNotNull($this->sentenciaEjecutada('UPDATE `modulos`'));
    }

    public function testModuloNoExiste(): void
    {
        $resultado = $this->ejecutar(['modulos.id_modulo.1' => 0]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(INVALID_ID, $resultado['codigo']);
        $this->assertNull($this->sentenciaEjecutada('UPDATE `modulos`'));
    }

    public function testNombreDuplicado(): void
    {
        $resultado = $this->ejecutar([
            'modulos.id_modulo.1'     => 1,
            'propia.nombre_modulo'    => 0,
            'modulos.nombre_modulo.1' => 1,
        ]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_NAME, $resultado['codigo']);
        $this->assertNull($this->sentenciaEjecutada('UPDATE `modulos`'));
    }

    public function testCambioANombreLibre(): void
    {
        $resultado = $this->ejecutar(
            ['modulos.id_modulo.1' => 1, 'propia.nombre_modulo' => 0, 'modulos.nombre_modulo.1' => 0],
            [],
            ['nombre' => 'deportistas']
        );

        $this->assertSame('exito', $resultado['accion']);
        $this->assertSame('Deportistas', $this->sentenciaEjecutada('UPDATE `modulos`')['params'][':nombre']);
    }

    public function testErrorBaseDatosAlModificar(): void
    {
        $resultado = $this->ejecutar(['modulos.id_modulo.1' => 1], [
            'UPDATE `modulos`' => ['error' => new Exception('Error al actualizar módulo')],
        ]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error al actualizar módulo', $resultado['codigo']);
    }
}
