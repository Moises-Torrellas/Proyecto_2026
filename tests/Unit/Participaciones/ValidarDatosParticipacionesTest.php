<?php

namespace tests\Unit\Participaciones;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloParticipaciones;
use App\modelo\Conexion;
use PDO;
use PDOException;
use PDOStatement;
use Exception;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitParticipaciones')]
#[Group('RF-01')]
#[Group('ESC-01')]
class ValidarDatosParticipacionesTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    private function crearModeloConPDOMock(?PDO $pdoMock = null): ModeloParticipaciones
    {
        if ($pdoMock === null) {
            $pdoMock = $this->createMock(PDO::class);
        }
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $pdoMock);

        return new ModeloParticipaciones();
    }

    // Caso 01: Envío de arreglo vacío a ProcesarDatos
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-01')]
    public function testDatosVaciosLanzaExcepcion(): void
    {
        $modelo = $this->crearModeloConPDOMock();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se proporcionaron datos para procesar.');

        $modelo->ProcesarDatos([]);
    }

    // Caso 02: Envío de acción no válida a ProcesarDatos
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-02')]
    public function testAccionInvalidaLanzaExcepcion(): void
    {
        $modelo = $this->crearModeloConPDOMock();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La acción solicitada para participaciones no es válida.');

        $modelo->ProcesarDatos([
            'accion' => 'accion_desconocida'
        ]);
    }

    // Caso 03: Validación de participación individual exitosa (atleta existe en detalles)
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-03')]
    public function testValidarParticipacionIndividualExisteRetornaTrue(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmt) {
            $this->assertStringContainsString('FROM detalles_participacion', $sql);
            $this->assertStringContainsString('codigo_participacion = :codigo_participacion', $sql);
            return $stmt;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->validarParticipacionIndividual(1, 10);

        $this->assertTrue($resultado);
    }

    // Caso 04: Validación de participación individual inexistente (atleta no está en detalles)
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-04')]
    public function testValidarParticipacionIndividualNoExisteRetornaFalse(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchColumn')->willReturn(0);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmt);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->validarParticipacionIndividual(1, 99);

        $this->assertFalse($resultado);
    }

    // Caso 05: Captura y propagación de excepción en validarParticipacionIndividual
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-05')]
    public function testValidarParticipacionIndividualErrorLanzaExcepcion(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new PDOException('Error de conexión simulado'));

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $this->expectException(PDOException::class);
        $this->expectExceptionMessage('Error de conexión simulado');

        $modelo->validarParticipacionIndividual(1, 10);
    }

    // Caso 06: Validación de participación grupal existente (equipo ya registrado en torneo)
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-06')]
    public function testValidarParticipacionGrupalExisteRetornaTrue(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmt) {
            $this->assertStringContainsString('FROM participaciones', $sql);
            $this->assertStringContainsString('codigo_torneo = :codigo_torneo', $sql);
            return $stmt;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->validarParticipacionGrupal(1, 5);

        $this->assertTrue($resultado);
    }

    // Caso 07: Validación de participación grupal inexistente (equipo no registrado en torneo)
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-07')]
    public function testValidarParticipacionGrupalNoExisteRetornaFalse(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchColumn')->willReturn(0);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmt);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->validarParticipacionGrupal(1, 5);

        $this->assertFalse($resultado);
    }

    // Caso 08: Captura y propagación de excepción en validarParticipacionGrupal
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-08')]
    public function testValidarParticipacionGrupalErrorLanzaExcepcion(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new PDOException('Error al validar participación grupal'));

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $this->expectException(PDOException::class);
        $this->expectExceptionMessage('Error al validar participación grupal');

        $modelo->validarParticipacionGrupal(1, 5);
    }

    // Caso 09: Validación de participación duplicada propia (existe en otro registro distinto)
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-09')]
    public function testValidarParticipacionDuplicadaPropiaExisteRetornaTrue(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmt) {
            $this->assertStringContainsString('codigo_participacion != :codigo_participacion', $sql);
            return $stmt;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->validarParticipacionDuplicadaPropia(1, 5, 2);

        $this->assertTrue($resultado);
    }

    // Caso 10: Validación de participación duplicada propia inexistente (es el mismo registro)
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-10')]
    public function testValidarParticipacionDuplicadaPropiaNoExisteRetornaFalse(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchColumn')->willReturn(0);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmt);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->validarParticipacionDuplicadaPropia(1, 5, 2);

        $this->assertFalse($resultado);
    }

    // Caso 11: Captura y propagación de excepción en validarParticipacionDuplicadaPropia
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-11')]
    public function testValidarParticipacionDuplicadaPropiaErrorLanzaExcepcion(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new PDOException('Error al validar duplicado propio'));

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $this->expectException(PDOException::class);
        $this->expectExceptionMessage('Error al validar duplicado propio');

        $modelo->validarParticipacionDuplicadaPropia(1, 5, 2);
    }
}

