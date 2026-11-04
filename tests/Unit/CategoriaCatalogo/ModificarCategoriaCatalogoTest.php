<?php

namespace tests\Unit\CategoriaCatalogo;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloCategoriaCatalogo;
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
#[Group('unitCategoriaCatalogo')]
#[Group('RF-03')]
#[Group('ESC-03')]
class ModificarCategoriaCatalogoTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    private function crearModeloConPDOMock(PDO $pdoMock): ModeloCategoriaCatalogo
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $pdoMock);

        return new ModeloCategoriaCatalogo();
    }

    private function obtenerDatosModificar(array $sobrescribir = []): array
    {
        $datos = [
            'accion'       => 'modificar',
            'id_categoria' => '2',
            'nombre'       => 'Coderas Pro',
            'descripcion'  => 'Protección para codos',
            'tipo_talla'   => 'Letras'
        ];

        return array_merge($datos, $sobrescribir);
    }

    // Caso 11: Modificación exitosa con un nuevo nombre disponible
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-11')]
    public function testModificarCategoriaCatalogoExitosoConNombreNuevo(): void
    {
        // Arrange
        $stmtPropia = $this->createMock(PDOStatement::class);
        $stmtPropia->method('execute')->willReturn(true);
        $stmtPropia->method('fetchColumn')->willReturn(0); // El nuevo nombre no es el propio

        $stmtGlobal = $this->createMock(PDOStatement::class);
        $stmtGlobal->method('execute')->willReturn(true);
        $stmtGlobal->method('fetchColumn')->willReturn(0); // Tampoco existe en otra categoría

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtPropia, $stmtGlobal, $stmtUpdate) {
            if (str_contains($sql, 'COUNT(*)') && str_contains($sql, ':id')) {
                return $stmtPropia;
            }
            if (str_contains($sql, 'COUNT(*)')) {
                return $stmtGlobal;
            }
            return $stmtUpdate;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosModificar());

        // Assert
        $this->assertEquals('modificar', $resultado['accion']);
        $this->assertEquals('Categoría modificada exitosamente.', $resultado['mensaje']);
        $this->assertArrayHasKey('datos_nuevos', $resultado);

        $datosDecoded = json_decode($resultado['datos_nuevos'], true);
        $this->assertEquals('Coderas Pro', $datosDecoded['nombre']);
        $this->assertEquals('Letras', $datosDecoded['tipo_talla']);
    }

    // Caso 12: Modificación exitosa conservando el mismo nombre de la propia categoría
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-12')]
    public function testModificarConservandoNombrePropio(): void
    {
        // Arrange
        $stmtPropia = $this->createMock(PDOStatement::class);
        $stmtPropia->method('execute')->willReturn(true);
        $stmtPropia->method('fetchColumn')->willReturn(1); // El nombre pertenece a este mismo registro

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtPropia, $stmtUpdate) {
            if (str_contains($sql, 'COUNT(*)') && str_contains($sql, ':id')) {
                return $stmtPropia;
            }
            return $stmtUpdate;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosModificar([
            'nombre'      => 'Coderas',
            'descripcion' => 'Actualizando descripción'
        ]));

        // Assert
        $this->assertEquals('modificar', $resultado['accion']);
        $this->assertEquals('Categoría modificada exitosamente.', $resultado['mensaje']);
    }

    // Caso 13: Rechazo de modificación por nombre duplicado en otra categoría
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-13')]
    public function testModificarNombreDuplicadoEnOtraCategoria(): void
    {
        // Arrange
        $stmtPropia = $this->createMock(PDOStatement::class);
        $stmtPropia->method('execute')->willReturn(true);
        $stmtPropia->method('fetchColumn')->willReturn(0); // No es su nombre actual

        $stmtGlobal = $this->createMock(PDOStatement::class);
        $stmtGlobal->method('execute')->willReturn(true);
        $stmtGlobal->method('fetchColumn')->willReturn(1); // Ya existe en otra categoría

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtPropia, $stmtGlobal) {
            if (str_contains($sql, 'COUNT(*)') && str_contains($sql, ':id')) {
                return $stmtPropia;
            }
            return $stmtGlobal;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosModificar([
            'nombre' => 'Cascos' // Nombre ya existente en otra categoría
        ]));

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Ya existe otra categoría registrada con este nombre.', $resultado['mensaje']);
    }

    // Caso 14: Error de base de datos durante la actualización
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-14')]
    public function testModificarErrorBaseDeDatos(): void
    {
        // Arrange
        $stmtPropia = $this->createMock(PDOStatement::class);
        $stmtPropia->method('execute')->willReturn(true);
        $stmtPropia->method('fetchColumn')->willReturn(1);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('execute')->willThrowException(new PDOException('Error de comunicación con la base de datos'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtPropia, $stmtUpdate) {
            if (str_contains($sql, 'COUNT(*)') && str_contains($sql, ':id')) {
                return $stmtPropia;
            }
            return $stmtUpdate;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosModificar());

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('Error al modificar:', $resultado['mensaje']);
    }
}
