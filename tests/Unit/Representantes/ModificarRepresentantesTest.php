<?php

namespace tests\Unit\Representantes;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloRepresentantes;
use App\modelo\Conexion;
use Exception;
use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('DUPLICATE_CEDULA')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitRepresentantes')]
#[Group('modificarRepresentantes')]
class ModificarRepresentantesTest extends TestCase
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
            'accion'       => 'modificar',
            'id'           => '1',
            'cedula'       => '12345678',
            'nacionalidad' => 'V',
            'nombre'       => 'Carlos Alberto',
            'apellido'     => 'Perez Gonzalez',
            'telefono'     => '0414-1234567',
            'direccion'    => 'Av. Lara con Calle 10',
            'correo'       => 'carlos@correo.com',
            'instagram'    => '@carlosperez'
        ];

        return array_merge($base, $sobrescribir);
    }

    // Caso 1: Modificación exitosa conservando datos propios
    public function testModificarExitoso(): void
    {
        $stmtPropio = $this->createMock(PDOStatement::class);
        $stmtPropio->method('execute')->willReturn(true);
        $stmtPropio->method('fetchColumn')->willReturn(1);

        $stmtAtleta = $this->createMock(PDOStatement::class);
        $stmtAtleta->method('execute')->willReturn(true);
        $stmtAtleta->method('fetch')->willReturn(false);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtPropio, $stmtAtleta, $stmtUpdate) {
            if (str_contains($sql, 'codigo_representante = :id AND')) {
                return $stmtPropio;
            }
            if (str_contains($sql, 'vista_atletas')) {
                return $stmtAtleta;
            }
            return $stmtUpdate;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloRepresentantes();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('exito', $resultado['accion']);
        $this->assertArrayHasKey('datos_nuevos', $resultado);

        $datosNuevos = json_decode($resultado['datos_nuevos'], true);
        $this->assertEquals('12345678', $datosNuevos['cedula']);
        $this->assertEquals('Carlos Alberto', $datosNuevos['nombre']);
    }

    // Caso 2: Modificación exitosa cambiando cédula y teléfono a valores disponibles
    public function testModificarCambiandoDatos(): void
    {
        $stmtDisponible = $this->createMock(PDOStatement::class);
        $stmtDisponible->method('execute')->willReturn(true);
        $stmtDisponible->method('fetchColumn')->willReturn(0);

        $stmtAtleta = $this->createMock(PDOStatement::class);
        $stmtAtleta->method('execute')->willReturn(true);
        $stmtAtleta->method('fetch')->willReturn(false);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtDisponible, $stmtAtleta, $stmtUpdate) {
            if (str_contains($sql, 'vista_atletas')) {
                return $stmtAtleta;
            }
            if (str_contains($sql, 'SELECT COUNT(*)')) {
                return $stmtDisponible;
            }
            return $stmtUpdate;
        });

        $this->inyectarPdoMock($pdoMock);

        $datos = $this->obtenerDatosValidos([
            'cedula'   => '87654321',
            'telefono' => '0424-7654321'
        ]);

        $modelo = new ModeloRepresentantes();
        $resultado = $modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 3: Cédula duplicada por pertenecer a otro representante
    public function testCedulaDuplicadaOtro(): void
    {
        $stmtPropia = $this->createMock(PDOStatement::class);
        $stmtPropia->method('execute')->willReturn(true);
        $stmtPropia->method('fetchColumn')->willReturn(0);

        $stmtOtra = $this->createMock(PDOStatement::class);
        $stmtOtra->method('execute')->willReturn(true);
        $stmtOtra->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('rollBack')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtPropia, $stmtOtra) {
            if (str_contains($sql, 'codigo_representante = :id') && str_contains($sql, 'cedula = :valor')) {
                return $stmtPropia;
            }
            return $stmtOtra;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloRepresentantes();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_CEDULA, $resultado['codigo']);
    }

    // Caso 4: Teléfono duplicado por pertenecer a otro representante
    public function testTelefonoDuplicadoOtro(): void
    {
        $stmtCedulaPropia = $this->createMock(PDOStatement::class);
        $stmtCedulaPropia->method('execute')->willReturn(true);
        $stmtCedulaPropia->method('fetchColumn')->willReturn(1);

        $stmtTelefonoPropio = $this->createMock(PDOStatement::class);
        $stmtTelefonoPropio->method('execute')->willReturn(true);
        $stmtTelefonoPropio->method('fetchColumn')->willReturn(0);

        $stmtTelefonoOtro = $this->createMock(PDOStatement::class);
        $stmtTelefonoOtro->method('execute')->willReturn(true);
        $stmtTelefonoOtro->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('rollBack')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtCedulaPropia, $stmtTelefonoPropio, $stmtTelefonoOtro) {
            if (str_contains($sql, 'codigo_representante = :id') && str_contains($sql, 'cedula = :valor')) {
                return $stmtCedulaPropia;
            }
            if (str_contains($sql, 'codigo_representante = :id') && str_contains($sql, 'telefono = :valor')) {
                return $stmtTelefonoPropio;
            }
            return $stmtTelefonoOtro;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloRepresentantes();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_PHONE, $resultado['codigo']);
    }

    // Caso 5: Correo duplicado por pertenecer a otro representante
    public function testCorreoDuplicadoOtro(): void
    {
        $stmtPropio = $this->createMock(PDOStatement::class);
        $stmtPropio->method('execute')->willReturn(true);
        $stmtPropio->method('fetchColumn')->willReturn(1);

        $stmtCorreoPropio = $this->createMock(PDOStatement::class);
        $stmtCorreoPropio->method('execute')->willReturn(true);
        $stmtCorreoPropio->method('fetchColumn')->willReturn(0);

        $stmtCorreoOtro = $this->createMock(PDOStatement::class);
        $stmtCorreoOtro->method('execute')->willReturn(true);
        $stmtCorreoOtro->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('rollBack')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtPropio, $stmtCorreoPropio, $stmtCorreoOtro) {
            if (str_contains($sql, 'codigo_representante = :id') && str_contains($sql, 'correo = :valor')) {
                return $stmtCorreoPropio;
            }
            if (str_contains($sql, 'codigo_representante = :id')) {
                return $stmtPropio;
            }
            return $stmtCorreoOtro;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloRepresentantes();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_EMAIL, $resultado['codigo']);
    }

    // Caso 6: Instagram duplicado por pertenecer a otro representante
    public function testInstagramDuplicadoOtro(): void
    {
        $stmtPropio = $this->createMock(PDOStatement::class);
        $stmtPropio->method('execute')->willReturn(true);
        $stmtPropio->method('fetchColumn')->willReturn(1);

        $stmtInstagramPropio = $this->createMock(PDOStatement::class);
        $stmtInstagramPropio->method('execute')->willReturn(true);
        $stmtInstagramPropio->method('fetchColumn')->willReturn(0);

        $stmtInstagramOtro = $this->createMock(PDOStatement::class);
        $stmtInstagramOtro->method('execute')->willReturn(true);
        $stmtInstagramOtro->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('rollBack')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtPropio, $stmtInstagramPropio, $stmtInstagramOtro) {
            if (str_contains($sql, 'codigo_representante = :id') && str_contains($sql, 'instagram = :valor')) {
                return $stmtInstagramPropio;
            }
            if (str_contains($sql, 'codigo_representante = :id')) {
                return $stmtPropio;
            }
            return $stmtInstagramOtro;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloRepresentantes();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_INSTAGRAM, $resultado['codigo']);
    }

    // Caso 7: Cédula modificada coincide con atleta menor de edad
    public function testAtletaMenorDeEdad(): void
    {
        $anioActual = (int)date('Y');
        $fechaMenor = ($anioActual - 16) . '-06-15';

        $stmtPropio = $this->createMock(PDOStatement::class);
        $stmtPropio->method('execute')->willReturn(true);
        $stmtPropio->method('fetchColumn')->willReturn(1);

        $stmtAtleta = $this->createMock(PDOStatement::class);
        $stmtAtleta->method('execute')->willReturn(true);
        $stmtAtleta->method('fetch')->willReturn(['fecha_nac' => $fechaMenor]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('rollBack')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtPropio, $stmtAtleta) {
            if (str_contains($sql, 'vista_atletas')) {
                return $stmtAtleta;
            }
            return $stmtPropio;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloRepresentantes();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('UNDER_AGE_ATHLETE', $resultado['codigo']);
    }

    // Caso 8: Error de base de datos durante la actualización
    public function testErrorBaseDatos(): void
    {
        $stmtPropio = $this->createMock(PDOStatement::class);
        $stmtPropio->method('execute')->willReturn(true);
        $stmtPropio->method('fetchColumn')->willReturn(1);

        $stmtAtleta = $this->createMock(PDOStatement::class);
        $stmtAtleta->method('execute')->willReturn(true);
        $stmtAtleta->method('fetch')->willReturn(false);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('execute')->willThrowException(new Exception('Error al actualizar registro'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('rollBack')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtPropio, $stmtAtleta, $stmtUpdate) {
            if (str_contains($sql, 'codigo_representante = :id AND')) {
                return $stmtPropio;
            }
            if (str_contains($sql, 'vista_atletas')) {
                return $stmtAtleta;
            }
            return $stmtUpdate;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloRepresentantes();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error al actualizar registro', $resultado['codigo']);
    }
}
