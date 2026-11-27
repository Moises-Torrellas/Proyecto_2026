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
use PHPUnit\Framework\Attributes\DataProvider;

if (!defined('INVALID_ID')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitModulos')]
#[Group('validarDatosModulos')]
class ValidarDatosModulosTest extends TestCase
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

    private function procesarSinEjecutar(ModeloModulos $modelo, array $datos): void
    {
        try {
            $modelo->ProcesarDatos($datos + ['accion' => 'accion_inexistente']);
        } catch (Exception $e) {
            if ($e->getMessage() !== 'La accion no es valida') {
                throw $e;
            }
        }
    }

    private function propiedad(ModeloModulos $modelo, string $nombre): mixed
    {
        return (new ReflectionClass($modelo))->getProperty($nombre)->getValue($modelo);
    }

    public function testDatosVacios(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se proporcionaron datos para procesar.');

        (new ModeloModulos())->ProcesarDatos([]);
    }

    public function testAccionNoPermitida(): void
    {
        foreach (['incluir', 'eliminar', 'otra'] as $accion) {
            try {
                (new ModeloModulos())->ProcesarDatos(['accion' => $accion]);
                $this->fail("La acción '$accion' no debería estar permitida.");
            } catch (Exception $e) {
                $this->assertSame('La accion no es valida', $e->getMessage());
            }
        }
    }

    public static function datosInvalidos(): array
    {
        return [
            'id con letras'              => [['id' => '3a'], 'Id inválido.'],
            'id con inyección SQL'       => [['id' => '1 OR 1=1'], 'Id inválido.'],
            'nombre muy corto'           => [['nombre' => 'Ab'], 'Nombre inválido.'],
            'nombre con números'         => [['nombre' => 'Atletas 2'], 'Nombre inválido.'],
            'nombre de más de 50'        => [['nombre' => str_repeat('a', 51)], 'Nombre inválido.'],
            'descripción con símbolos'   => [['descripcion' => 'Gestiona <script>'], 'Descripción inválida.'],
            'descripción de más de 100'  => [['descripcion' => str_repeat('a', 101)], 'Descripción inválida.'],
        ];
    }

    #[DataProvider('datosInvalidos')]
    public function testDatoInvalido(array $datos, string $mensaje): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage($mensaje);

        $this->inyectarPdoMock($this->crearPdoMock());
        (new ModeloModulos())->ProcesarDatos($datos + ['accion' => 'buscar']);
    }

    public function testNombreLimiteConTildes(): void
    {
        $nombre = str_repeat('Gestión Año ', 4) . 'Ñu';
        $this->assertSame(50, mb_strlen($nombre));

        $modelo = new ModeloModulos();
        $this->procesarSinEjecutar($modelo, ['nombre' => $nombre]);

        $this->assertSame(mb_convert_case($nombre, MB_CASE_TITLE, 'UTF-8'), $this->propiedad($modelo, 'nombre'));
    }

    public function testDescripcionLimiteConTildes(): void
    {
        $descripcion = str_repeat('Gestión de niños ', 5) . str_repeat('ñ', 15);
        $this->assertSame(100, mb_strlen($descripcion));

        $modelo = new ModeloModulos();
        $this->procesarSinEjecutar($modelo, ['descripcion' => $descripcion]);

        $this->assertNotSame('', $this->propiedad($modelo, 'descripcion'));
    }

    public function testNormalizaDatos(): void
    {
        $modelo = new ModeloModulos();
        $this->procesarSinEjecutar($modelo, [
            'id'          => '6',
            'nombre'      => '  gestión de ATLETAS ',
            'descripcion' => ' registro y control de atletas ',
        ]);

        $this->assertSame('6', $this->propiedad($modelo, 'id'));
        $this->assertSame('Gestión De Atletas', $this->propiedad($modelo, 'nombre'));
        $this->assertSame('Registro Y Control De Atletas', $this->propiedad($modelo, 'descripcion'));
    }

    public function testValoresPorDefecto(): void
    {
        $modelo = new ModeloModulos();
        $this->procesarSinEjecutar($modelo, ['otro' => 'x']);

        $this->assertNull($this->propiedad($modelo, 'id'));
        $this->assertSame('', $this->propiedad($modelo, 'nombre'));
        $this->assertSame('', $this->propiedad($modelo, 'descripcion'));
    }
}
