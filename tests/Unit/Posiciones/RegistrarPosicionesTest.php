<?php

namespace tests\Unit\Posiciones;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloPosiciones;
use App\modelo\Conexion;
use Exception;
use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

#[Group('unit')]
#[Group('unitPosiciones')]
#[Group('registrarPosiciones')]
class RegistrarPosicionesTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    private function inyectarPdoMock(PDO $pdoMock): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $pdoMock);
    }

    private function obtenerDatosValidos(array $sobrescribir = []): array
    {
        $base = [
            'accion'      => 'incluir',
            'nombre'      => 'Primera Base',
            'abreviatura' => 'PB',
            'descripcion' => 'Jugador defensivo de la primera base'
        ];

        return array_merge($base, $sobrescribir);
    }

    // Caso 1: Registro exitoso de una nueva posición
    public function testRegistroExitoso(): void
    {
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('bindValue')->willReturn(true);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(0);

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('bindParam')->willReturn(true);
        $stmtInsert->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtExiste, $stmtInsert) {
            if (str_contains($sql, 'SELECT COUNT(*)')) {
                return $stmtExiste;
            }
            return $stmtInsert;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPosiciones();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('incluir', $resultado['accion']);
        $this->assertEquals('Posicion registrada exitosamente.', $resultado['mensaje']);
    }

    // Caso 2: Registro exitoso sin descripción opcional
    public function testRegistroSinDescripcion(): void
    {
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('bindValue')->willReturn(true);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(0);

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('bindParam')->willReturn(true);
        $stmtInsert->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtExiste, $stmtInsert) {
            if (str_contains($sql, 'SELECT COUNT(*)')) {
                return $stmtExiste;
            }
            return $stmtInsert;
        });

        $this->inyectarPdoMock($pdoMock);

        $datos = $this->obtenerDatosValidos(['descripcion' => '']);

        $modelo = new ModeloPosiciones();
        $resultado = $modelo->ProcesarDatos($datos);

        $this->assertEquals('incluir', $resultado['accion']);
        $this->assertEquals('Posicion registrada exitosamente.', $resultado['mensaje']);
    }

    // Caso 3: Nombre de posición duplicado
    public function testNombreDuplicado(): void
    {
        $stmtNombre = $this->createMock(PDOStatement::class);
        $stmtNombre->method('bindValue')->willReturn(true);
        $stmtNombre->method('execute')->willReturn(true);
        $stmtNombre->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtNombre);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPosiciones();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Ya existe una posición registrada con este nombre.', $resultado['mensaje']);
    }

    // Caso 4: Abreviatura de posición duplicada
    public function testAbreviaturaDuplicada(): void
    {
        $stmtNombre = $this->createMock(PDOStatement::class);
        $stmtNombre->method('bindValue')->willReturn(true);
        $stmtNombre->method('execute')->willReturn(true);
        $stmtNombre->method('fetchColumn')->willReturn(0);

        $stmtAbrev = $this->createMock(PDOStatement::class);
        $stmtAbrev->method('bindValue')->willReturn(true);
        $stmtAbrev->method('execute')->willReturn(true);
        $stmtAbrev->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtNombre, $stmtAbrev) {
            if (str_contains($sql, 'WHERE nombre = :valor')) {
                return $stmtNombre;
            }
            return $stmtAbrev;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPosiciones();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Ya existe una posición registrada con esta abreviatura.', $resultado['mensaje']);
    }

    // Caso 5: Error de base de datos al insertar
    public function testErrorBaseDatos(): void
    {
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('bindValue')->willReturn(true);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(0);

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('bindParam')->willReturn(true);
        $stmtInsert->method('execute')->willThrowException(new Exception('Error al insertar registro'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtExiste, $stmtInsert) {
            if (str_contains($sql, 'SELECT COUNT(*)')) {
                return $stmtExiste;
            }
            return $stmtInsert;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPosiciones();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error al insertar registro', $resultado['mensaje']);
    }
}

