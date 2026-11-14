<?php

namespace tests\Unit\Catalogo;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloCatalogo;
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
#[Group('unitCatalogo')]
#[Group('RF-03')]
#[Group('ESC-03')]
class ModificarCatalogoTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    private function crearModeloConPDOMock(PDO $pdoMock): ModeloCatalogo
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $pdoMock);

        return new ModeloCatalogo();
    }

    private function obtenerDatosModificar(array $sobrescribir = []): array
    {
        $datos = [
            'accion'          => 'modificar',
            'id_catalogo'     => '5',
            'nombre'          => 'Stick CCM Ribcor Actualizado',
            'stock_minimo'    => '4',
            'id_categoria'    => '3',
            'talla'           => 'Senior 85 Flex',
            'codigo_posicion' => '2'
        ];

        return array_merge($datos, $sobrescribir);
    }

    // Caso 14: Modificación exitosa de un artículo de catálogo
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-14')]
    public function testModificarCatalogoExitoso(): void
    {
        // Arrange
        $stmtCheck = $this->createMock(PDOStatement::class);
        $stmtCheck->method('execute')->willReturn(true);
        $stmtCheck->method('fetchColumn')->willReturn(1); // El catálogo sí existe

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('commit')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtCheck, $stmtUpdate) {
            if (str_contains($sql, 'catalogo') && str_contains($sql, 'COUNT(*)')) {
                return $stmtCheck;
            }
            return $stmtUpdate;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosModificar());

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
        $this->assertArrayHasKey('datos_nuevos', $resultado);

        $datosDecoded = json_decode($resultado['datos_nuevos'], true);
        $this->assertEquals('5', $datosDecoded['id_catalogo']);
        $this->assertEquals('Stick Ccm Ribcor Actualizado', $datosDecoded['nombre']);
        $this->assertEquals('4', $datosDecoded['stock_minimo']);
        $this->assertEquals('SENIOR 85 FLEX', $datosDecoded['talla']);
    }

    // Caso 15: Rechazo de modificación cuando el ID de catálogo no existe
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-15')]
    public function testModificarIdNoExiste(): void
    {
        // Arrange
        $stmtCheck = $this->createMock(PDOStatement::class);
        $stmtCheck->method('execute')->willReturn(true);
        $stmtCheck->method('fetchColumn')->willReturn(0); // No existe el registro en la BD

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack')->willReturn(true);

        $pdoMock->method('prepare')->willReturn($stmtCheck);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosModificar([
            'id_catalogo' => '9999'
        ]));

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }

    // Caso 16: Error de base de datos durante el UPDATE
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-16')]
    public function testModificarErrorBaseDeDatosEnUpdate(): void
    {
        // Arrange
        $stmtCheck = $this->createMock(PDOStatement::class);
        $stmtCheck->method('execute')->willReturn(true);
        $stmtCheck->method('fetchColumn')->willReturn(1);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('execute')->willThrowException(new PDOException('Error al actualizar registro SQL'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtCheck, $stmtUpdate) {
            if (str_contains($sql, 'catalogo') && str_contains($sql, 'COUNT(*)')) {
                return $stmtCheck;
            }
            return $stmtUpdate;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosModificar());

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error al actualizar registro SQL', $resultado['codigo']);
    }

    // Caso 17: Modificación estableciendo campos opcionales nulos o vacíos
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-17')]
    public function testModificarConCamposOpcionalesNull(): void
    {
        // Arrange
        $stmtCheck = $this->createMock(PDOStatement::class);
        $stmtCheck->method('execute')->willReturn(true);
        $stmtCheck->method('fetchColumn')->willReturn(1);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('commit')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtCheck, $stmtUpdate) {
            if (str_contains($sql, 'catalogo') && str_contains($sql, 'COUNT(*)')) {
                return $stmtCheck;
            }
            return $stmtUpdate;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $datos = [
            'accion'          => 'modificar',
            'id_catalogo'     => '5',
            'nombre'          => 'Puck Oficial',
            'id_categoria'    => '1',
            'stock_minimo'    => '10',
            'talla'           => '',
            'codigo_posicion' => ''
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
        $datosDecoded = json_decode($resultado['datos_nuevos'], true);
        $this->assertNull($datosDecoded['codigo_posicion']);
        $this->assertEquals('', $datosDecoded['talla']);
    }
}

