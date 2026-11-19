<?php

namespace tests\Unit\Premios;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloPremios;
use App\modelo\Conexion;
use PDO;
use PDOException;
use PDOStatement;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitPremios')]
#[Group('RF-02')]
#[Group('ESC-02')]
class RegistrarPremiosTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    private function crearModeloConPDOMock(PDO $pdoMock): ModeloPremios
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $pdoMock);

        return new ModeloPremios();
    }

    private function obtenerDatosRegistroValidos(array $sobrescribir = []): array
    {
        $datos = [
            'accion' => 'incluir',
            'nombre' => 'Mejor Anotador',
            'tipo'   => 'I'
        ];

        return array_merge($datos, $sobrescribir);
    }

    // Caso 04: Inclusión exitosa de un premio individual (Tipo 'I')
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-04')]
    public function testIncluirPremioIndividualExitoso(): void
    {
        // Arrange
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('bindValue')->willReturn(true);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(0); // No existe previo

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('bindParam')->willReturn(true);
        $stmtInsert->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('commit')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtExiste, $stmtInsert) {
            if (str_contains($sql, 'SELECT COUNT(*)') && str_contains($sql, 'premios')) {
                return $stmtExiste;
            }
            return $stmtInsert;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosRegistroValidos());

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 05: Inclusión exitosa de un premio grupal (Tipo 'G')
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-05')]
    public function testIncluirPremioGrupalExitoso(): void
    {
        // Arrange
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('bindValue')->willReturn(true);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(0);

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('bindParam')->willReturn(true);
        $stmtInsert->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('commit')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtExiste, $stmtInsert) {
            if (str_contains($sql, 'SELECT COUNT(*)') && str_contains($sql, 'premios')) {
                return $stmtExiste;
            }
            return $stmtInsert;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $datos = $this->obtenerDatosRegistroValidos([
            'nombre' => 'Subcampeón de Liga',
            'tipo'   => 'G'
        ]);

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 06: Rechazo de inclusión por nombre de premio ya existente (DUPLICATE_NAME)
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-06')]
    public function testIncluirPremioNombreDuplicado(): void
    {
        // Arrange
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('bindValue')->willReturn(true);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(1); // Ya existe en premios

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack')->willReturn(true);

        $pdoMock->method('prepare')->with($this->stringContains('SELECT COUNT(*)'))->willReturn($stmtExiste);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosRegistroValidos());

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_NAME, $resultado['codigo']);
    }

    // Caso 07: Error en ejecución de base de datos con rollback transaccional
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-07')]
    public function testIncluirPremioErrorBaseDeDatosConRollback(): void
    {
        // Arrange
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('bindValue')->willReturn(true);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(0);

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('bindParam')->willReturn(true);
        $stmtInsert->method('execute')->willThrowException(new PDOException('Error fatal de conexión al insertar'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtExiste, $stmtInsert) {
            if (str_contains($sql, 'SELECT COUNT(*)')) {
                return $stmtExiste;
            }
            return $stmtInsert;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosRegistroValidos());

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('Error fatal de conexión al insertar', $resultado['codigo']);
    }
}
