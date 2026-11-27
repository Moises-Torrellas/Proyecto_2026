<?php

namespace tests\Unit\Bitacora;

use App\modelo\Conexion;
use PDO;
use PDOStatement;
use ReflectionClass;
use PHPUnit\Framework\TestCase;
use App\modelo\ModeloBitacora;
use Exception;
use PHPUnit\Framework\Attributes\Group;

if (!defined('INVALID_ID')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitBitacora')]
#[Group('consultarCatalogosBitacora')]
class ConsultarCatalogosBitacoraTest extends TestCase
{
    protected array $sentencias = [];

    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $reflector->getProperty('instanciaSG')->setValue(null, null);
        $reflector->getProperty('instancia')->setValue(null, null);

        $_SESSION = [];
        unset($_SERVER['HTTP_USER_AGENT']);
        $this->sentencias = [];
        parent::tearDown();
    }

    protected function inyectarPdoMock(PDO $pdoMock): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $reflector->getProperty('instanciaSG')->setValue(null, $pdoMock);
        $reflector->getProperty('instancia')->setValue(null, $pdoMock);
    }

    protected function crearPdoMock(array $respuestas = [], bool $enTransaccion = false): PDO
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn($enTransaccion);
        $pdoMock->method('rollBack')->willReturnCallback(function () {
            $this->sentencias[] = ['sql' => 'ROLLBACK', 'params' => []];
            return true;
        });
        $pdoMock->method('prepare')->willReturnCallback(
            fn(string $sql) => $this->crearSentencia($sql, $respuestas)
        );

        return $pdoMock;
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

    public function testConsultarUsuarios(): void
    {
        $filas = [
            ['idUsuario' => 2, 'cedulaUsuario' => '87654321', 'nombreUsuario' => 'Ana', 'apellidoUsuario' => 'Gómez'],
            ['idUsuario' => 1, 'cedulaUsuario' => '12345678', 'nombreUsuario' => 'Juan', 'apellidoUsuario' => 'Pérez'],
        ];
        $this->inyectarPdoMock($this->crearPdoMock([
            'FROM usuarios' => ['fetchAll' => $filas],
        ]));

        $resultado = (new ModeloBitacora())->consultarUsuarios();

        $this->assertSame('consultar_usuarios', $resultado['accion']);
        $this->assertSame($filas, $resultado['datos']);

        $sql = $this->sentenciaEjecutada('FROM usuarios')['sql'];
        $this->assertStringContainsString('ORDER BY nombreUsuario ASC', $sql);
        $this->assertStringNotContainsString('pass_hash', $sql);
        $this->assertStringNotContainsString('*', $sql);
    }

    public function testConsultarUsuariosError(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([
            'FROM usuarios' => ['error' => new Exception('Tabla no encontrada')],
        ]));

        $resultado = (new ModeloBitacora())->consultarUsuarios();

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Tabla no encontrada', $resultado['mensaje']);
    }

    public function testConsultarModulos(): void
    {
        $filas = [
            ['id_modulo' => 6, 'nombre_modulo' => 'Atletas'],
            ['id_modulo' => 103, 'nombre_modulo' => 'Roles'],
        ];
        $this->inyectarPdoMock($this->crearPdoMock([
            'FROM modulos' => ['fetchAll' => $filas],
        ]));

        $resultado = (new ModeloBitacora())->consultarModulos();

        $this->assertSame('consultar_modulos', $resultado['accion']);
        $this->assertSame($filas, $resultado['datos']);
        $this->assertStringContainsString(
            'ORDER BY nombre_modulo ASC',
            $this->sentenciaEjecutada('FROM modulos')['sql']
        );
    }

    public function testConsultarModulosError(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([
            'FROM modulos' => ['error' => new Exception('Tabla no encontrada')],
        ]));

        $resultado = (new ModeloBitacora())->consultarModulos();

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Tabla no encontrada', $resultado['mensaje']);
    }
}
