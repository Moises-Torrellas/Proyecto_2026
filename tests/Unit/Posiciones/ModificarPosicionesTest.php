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
#[Group('modificarPosiciones')]
class ModificarPosicionesTest extends TestCase
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
            'accion'      => 'modificar',
            'id'          => '1',
            'nombre'      => 'Primera Base',
            'abreviatura' => 'PB',
            'descripcion' => 'Jugador defensivo de la primera base'
        ];

        return array_merge($base, $sobrescribir);
    }

    // Caso 1: Modificación exitosa conservando valores propios
    public function testModificarExitoso(): void
    {
        $stmtPropio = $this->createMock(PDOStatement::class);
        $stmtPropio->method('bindValue')->willReturn(true);
        $stmtPropio->method('execute')->willReturn(true);
        $stmtPropio->method('fetchColumn')->willReturn(1);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('bindParam')->willReturn(true);
        $stmtUpdate->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtPropio, $stmtUpdate) {
            if (str_contains($sql, 'UPDATE')) {
                return $stmtUpdate;
            }
            return $stmtPropio;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPosiciones();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('modificar', $resultado['accion']);
        $this->assertEquals('Posicion modificada exitosamente.', $resultado['mensaje']);
    }

    // Caso 2: Modificación exitosa cambiando a un nombre y abreviatura disponibles
    public function testModificarCambiandoDatos(): void
    {
        $stmtDisponible = $this->createMock(PDOStatement::class);
        $stmtDisponible->method('bindValue')->willReturn(true);
        $stmtDisponible->method('execute')->willReturn(true);
        $stmtDisponible->method('fetchColumn')->willReturn(0);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('bindParam')->willReturn(true);
        $stmtUpdate->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtDisponible, $stmtUpdate) {
            if (str_contains($sql, 'SELECT COUNT(*)')) {
                return $stmtDisponible;
            }
            return $stmtUpdate;
        });

        $this->inyectarPdoMock($pdoMock);

        $datos = $this->obtenerDatosValidos([
            'nombre'      => 'Tercera Base',
            'abreviatura' => 'TB'
        ]);

        $modelo = new ModeloPosiciones();
        $resultado = $modelo->ProcesarDatos($datos);

        $this->assertEquals('modificar', $resultado['accion']);
        $this->assertEquals('Posicion modificada exitosamente.', $resultado['mensaje']);
    }

    // Caso 3: Nombre duplicado por pertenecer a otra posición
    public function testNombreDuplicadoOtro(): void
    {
        $stmtPropio = $this->createMock(PDOStatement::class);
        $stmtPropio->method('bindValue')->willReturn(true);
        $stmtPropio->method('execute')->willReturn(true);
        $stmtPropio->method('fetchColumn')->willReturn(0);

        $stmtOtro = $this->createMock(PDOStatement::class);
        $stmtOtro->method('bindValue')->willReturn(true);
        $stmtOtro->method('execute')->willReturn(true);
        $stmtOtro->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtPropio, $stmtOtro) {
            if (str_contains($sql, 'codigo_posicion = :id') && str_contains($sql, 'nombre = :valor')) {
                return $stmtPropio;
            }
            return $stmtOtro;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPosiciones();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Ya existe una posición registrada con este nombre.', $resultado['mensaje']);
    }

    // Caso 4: Abreviatura duplicada por pertenecer a otra posición
    public function testAbreviaturaDuplicadaOtro(): void
    {
        $stmtNombrePropio = $this->createMock(PDOStatement::class);
        $stmtNombrePropio->method('bindValue')->willReturn(true);
        $stmtNombrePropio->method('execute')->willReturn(true);
        $stmtNombrePropio->method('fetchColumn')->willReturn(1);

        $stmtAbrevPropia = $this->createMock(PDOStatement::class);
        $stmtAbrevPropia->method('bindValue')->willReturn(true);
        $stmtAbrevPropia->method('execute')->willReturn(true);
        $stmtAbrevPropia->method('fetchColumn')->willReturn(0);

        $stmtAbrevOtra = $this->createMock(PDOStatement::class);
        $stmtAbrevOtra->method('bindValue')->willReturn(true);
        $stmtAbrevOtra->method('execute')->willReturn(true);
        $stmtAbrevOtra->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtNombrePropio, $stmtAbrevPropia, $stmtAbrevOtra) {
            if (str_contains($sql, 'codigo_posicion = :id') && str_contains($sql, 'nombre = :valor')) {
                return $stmtNombrePropio;
            }
            if (str_contains($sql, 'codigo_posicion = :id') && str_contains($sql, 'abreviatura = :valor')) {
                return $stmtAbrevPropia;
            }
            return $stmtAbrevOtra;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPosiciones();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Ya existe una posición registrada con esta abreviatura.', $resultado['mensaje']);
    }

    // Caso 5: Error en base de datos al actualizar
    public function testErrorBaseDatos(): void
    {
        $stmtPropio = $this->createMock(PDOStatement::class);
        $stmtPropio->method('bindValue')->willReturn(true);
        $stmtPropio->method('execute')->willReturn(true);
        $stmtPropio->method('fetchColumn')->willReturn(1);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('bindParam')->willReturn(true);
        $stmtUpdate->method('execute')->willThrowException(new Exception('Error de base de datos'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtPropio, $stmtUpdate) {
            if (str_contains($sql, 'UPDATE')) {
                return $stmtUpdate;
            }
            return $stmtPropio;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPosiciones();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Hubo un error al modificar la posición.', $resultado['mensaje']);
    }
}
