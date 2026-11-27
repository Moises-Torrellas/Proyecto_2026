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

#[Group('unit')]
#[Group('unitPermisos')]
#[Group('registrarPermisos')]
class RegistrarPermisosTest extends TestCase
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
            'accion'      => 'incluir',
            'modulo'      => '6',
            'nombre'      => 'reinscribir atleta',
            'clave'       => 'reinscribir_atleta',
            'descripcion' => 'permite reinscribir atletas retirados',
        ], $sobrescribir);
    }

    private function ejecutar(array $conteos, array $respuestas = [], array $datos = []): array
    {
        $this->inyectarPdoMock($this->crearPdoMock($conteos, $respuestas));
        return (new ModeloPermisos())->ProcesarDatos($this->obtenerDatosValidos($datos));
    }

    public function testRegistroExitoso(): void
    {
        $resultado = $this->ejecutar(['modulos.id_modulo.1' => 1]);

        $this->assertSame(['accion' => 'exito'], $resultado);

        $insert = $this->sentenciaEjecutada('INSERT INTO `permisos`');
        $this->assertStringContainsString('(id_modulo, nombre, clave, descripcion)', $insert['sql']);
        $this->assertSame([
            ':id_modulo'   => '6',
            ':nombre'      => 'Reinscribir Atleta',
            ':clave'       => 'reinscribir_atleta',
            ':descripcion' => 'Permite Reinscribir Atletas Retirados',
        ], $insert['params']);
    }

    public function testRegistroSinDescripcion(): void
    {
        $resultado = $this->ejecutar(['modulos.id_modulo.1' => 1], [], ['descripcion' => '']);

        $this->assertSame('exito', $resultado['accion']);

        $insert = $this->sentenciaEjecutada('INSERT INTO `permisos`');
        $this->assertStringNotContainsString('descripcion', $insert['sql']);
        $this->assertArrayNotHasKey(':descripcion', $insert['params']);
    }

    public function testModuloNoExiste(): void
    {
        $resultado = $this->ejecutar(['modulos.id_modulo.1' => 0]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertNull($this->sentenciaEjecutada('INSERT INTO `permisos`'));
    }

    public function testModuloNoExisteCodigo(): void
    {
        $resultado = $this->ejecutar(['modulos.id_modulo.1' => 0]);

        $this->assertSame(
            ASSOCIATES,
            $resultado['codigo'],
            'El controlador espera ASSOCIATES; con otro código el usuario ve "Ocurrió un error inesperado".'
        );
    }

    public function testClaveDuplicada(): void
    {
        $resultado = $this->ejecutar([
            'modulos.id_modulo.1' => 1,
            'permisos.clave.1'    => 1,
            'permisos.clave.null' => 1,
        ]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('La clave técnica del permiso ya está registrada.', $resultado['codigo']);
        $this->assertNull($this->sentenciaEjecutada('INSERT INTO `permisos`'));
    }

    public function testClaveDePermisoBloqueado(): void
    {
        $resultado = $this->ejecutar([
            'modulos.id_modulo.1' => 1,
            'permisos.clave.null' => 1,
            'permisos.clave.2'    => 1,
        ]);

        $this->assertSame('error', $resultado['accion'], 'Se registró una clave que ya usa un permiso bloqueado.');
        $this->assertNull($this->sentenciaEjecutada('INSERT INTO `permisos`'));
    }

    public function testNombreDuplicado(): void
    {
        $resultado = $this->ejecutar(['modulos.id_modulo.1' => 1, 'permisos.nombre.1' => 1]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertNull($this->sentenciaEjecutada('INSERT INTO `permisos`'));
    }

    public function testNombreDuplicadoCodigo(): void
    {
        $resultado = $this->ejecutar(['modulos.id_modulo.1' => 1, 'permisos.nombre.1' => 1]);

        $this->assertSame(
            DUPLICATE_NAME,
            $resultado['codigo'],
            'El controlador espera DUPLICATE_NAME; con otro código el usuario ve "Ocurrió un error inesperado".'
        );
    }

    public function testErrorBaseDatosAlRegistrar(): void
    {
        $resultado = $this->ejecutar(['modulos.id_modulo.1' => 1], [
            'INSERT INTO `permisos`' => ['error' => new Exception('Error al insertar permiso')],
        ]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error al insertar permiso', $resultado['codigo']);
    }
}
