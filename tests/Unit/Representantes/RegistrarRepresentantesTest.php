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
#[Group('registrarRepresentantes')]
class RegistrarRepresentantesTest extends TestCase
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
            'accion'       => 'incluir',
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

    // Caso 1: Registro exitoso con todos los campos
    public function testRegistroExitoso(): void
    {
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(0);

        $stmtAtleta = $this->createMock(PDOStatement::class);
        $stmtAtleta->method('execute')->willReturn(true);
        $stmtAtleta->method('fetch')->willReturn(false);

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtExiste, $stmtAtleta, $stmtInsert) {
            if (str_contains($sql, 'vista_atletas')) {
                return $stmtAtleta;
            }
            if (str_contains($sql, 'SELECT COUNT(*)')) {
                return $stmtExiste;
            }
            return $stmtInsert;
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

    // Caso 2: Registro exitoso sin correo ni instagram
    public function testRegistroSinCorreoNiInstagram(): void
    {
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(0);

        $stmtAtleta = $this->createMock(PDOStatement::class);
        $stmtAtleta->method('execute')->willReturn(true);
        $stmtAtleta->method('fetch')->willReturn(false);

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtExiste, $stmtAtleta, $stmtInsert) {
            if (str_contains($sql, 'vista_atletas')) {
                return $stmtAtleta;
            }
            if (str_contains($sql, 'SELECT COUNT(*)')) {
                return $stmtExiste;
            }
            return $stmtInsert;
        });

        $this->inyectarPdoMock($pdoMock);

        $datos = $this->obtenerDatosValidos([
            'correo' => '',
            'instagram' => ''
        ]);

        $modelo = new ModeloRepresentantes();
        $resultado = $modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 3: Cédula duplicada en el sistema
    public function testCedulaDuplicada(): void
    {
        $stmtCedula = $this->createMock(PDOStatement::class);
        $stmtCedula->method('execute')->willReturn(true);
        $stmtCedula->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('rollBack')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('prepare')->willReturn($stmtCedula);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloRepresentantes();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_CEDULA, $resultado['codigo']);
    }

    // Caso 4: Teléfono duplicado en el sistema
    public function testTelefonoDuplicado(): void
    {
        $stmtCedula = $this->createMock(PDOStatement::class);
        $stmtCedula->method('execute')->willReturn(true);
        $stmtCedula->method('fetchColumn')->willReturn(0);

        $stmtTelefono = $this->createMock(PDOStatement::class);
        $stmtTelefono->method('execute')->willReturn(true);
        $stmtTelefono->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('rollBack')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtCedula, $stmtTelefono) {
            if (str_contains($sql, 'WHERE cedula =')) {
                return $stmtCedula;
            }
            return $stmtTelefono;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloRepresentantes();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_PHONE, $resultado['codigo']);
    }

    // Caso 5: Correo duplicado en el sistema
    public function testCorreoDuplicado(): void
    {
        $stmtLibre = $this->createMock(PDOStatement::class);
        $stmtLibre->method('execute')->willReturn(true);
        $stmtLibre->method('fetchColumn')->willReturn(0);

        $stmtCorreo = $this->createMock(PDOStatement::class);
        $stmtCorreo->method('execute')->willReturn(true);
        $stmtCorreo->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('rollBack')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtLibre, $stmtCorreo) {
            if (str_contains($sql, 'WHERE correo =')) {
                return $stmtCorreo;
            }
            return $stmtLibre;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloRepresentantes();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_EMAIL, $resultado['codigo']);
    }

    // Caso 6: Instagram duplicado en el sistema
    public function testInstagramDuplicado(): void
    {
        $stmtLibre = $this->createMock(PDOStatement::class);
        $stmtLibre->method('execute')->willReturn(true);
        $stmtLibre->method('fetchColumn')->willReturn(0);

        $stmtInstagram = $this->createMock(PDOStatement::class);
        $stmtInstagram->method('execute')->willReturn(true);
        $stmtInstagram->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('rollBack')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtLibre, $stmtInstagram) {
            if (str_contains($sql, 'WHERE instagram =')) {
                return $stmtInstagram;
            }
            return $stmtLibre;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloRepresentantes();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_INSTAGRAM, $resultado['codigo']);
    }

    // Caso 7: Cédula coincide con atleta menor de 18 años
    public function testAtletaMenorDeEdad(): void
    {
        $anioActual = (int)date('Y');
        $fechaMenor = ($anioActual - 15) . '-01-01';

        $stmtLibre = $this->createMock(PDOStatement::class);
        $stmtLibre->method('execute')->willReturn(true);
        $stmtLibre->method('fetchColumn')->willReturn(0);

        $stmtAtleta = $this->createMock(PDOStatement::class);
        $stmtAtleta->method('execute')->willReturn(true);
        $stmtAtleta->method('fetch')->willReturn(['fecha_nac' => $fechaMenor]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('rollBack')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtLibre, $stmtAtleta) {
            if (str_contains($sql, 'vista_atletas')) {
                return $stmtAtleta;
            }
            return $stmtLibre;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloRepresentantes();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('UNDER_AGE_ATHLETE', $resultado['codigo']);
    }

    // Caso 8: Cédula coincide con atleta mayor de 18 años (válido)
    public function testAtletaMayorDeEdad(): void
    {
        $anioActual = (int)date('Y');
        $fechaMayor = ($anioActual - 20) . '-01-01';

        $stmtLibre = $this->createMock(PDOStatement::class);
        $stmtLibre->method('execute')->willReturn(true);
        $stmtLibre->method('fetchColumn')->willReturn(0);

        $stmtAtleta = $this->createMock(PDOStatement::class);
        $stmtAtleta->method('execute')->willReturn(true);
        $stmtAtleta->method('fetch')->willReturn(['fecha_nac' => $fechaMayor]);

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtLibre, $stmtAtleta, $stmtInsert) {
            if (str_contains($sql, 'vista_atletas')) {
                return $stmtAtleta;
            }
            if (str_contains($sql, 'SELECT COUNT(*)')) {
                return $stmtLibre;
            }
            return $stmtInsert;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloRepresentantes();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 9: Error de base de datos durante la inserción
    public function testErrorBaseDatos(): void
    {
        $stmtLibre = $this->createMock(PDOStatement::class);
        $stmtLibre->method('execute')->willReturn(true);
        $stmtLibre->method('fetchColumn')->willReturn(0);

        $stmtAtleta = $this->createMock(PDOStatement::class);
        $stmtAtleta->method('execute')->willReturn(true);
        $stmtAtleta->method('fetch')->willReturn(false);

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('execute')->willThrowException(new Exception('Error al insertar registro'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('rollBack')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtLibre, $stmtAtleta, $stmtInsert) {
            if (str_contains($sql, 'vista_atletas')) {
                return $stmtAtleta;
            }
            if (str_contains($sql, 'SELECT COUNT(*)')) {
                return $stmtLibre;
            }
            return $stmtInsert;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloRepresentantes();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error al insertar registro', $resultado['codigo']);
    }
}
