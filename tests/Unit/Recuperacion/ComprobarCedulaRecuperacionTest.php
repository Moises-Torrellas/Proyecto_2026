<?php

namespace tests\Unit\Recuperacion;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloRecuperacion;
use App\modelo\Conexion;
use Exception;
use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('recuperacion')]
#[Group('comprobarCedulaRecuperacion')]
class ComprobarCedulaRecuperacionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedadSG = $reflector->getProperty('instanciaSG');
        $propiedadSG->setValue(null, null);

        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);

        $_SESSION = [];
        parent::tearDown();
    }

    private function inyectarPdoMock(PDO $pdoMock): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedadSG = $reflector->getProperty('instanciaSG');
        $propiedadSG->setValue(null, $pdoMock);

        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $pdoMock);
    }

    private function crearModeloConSimuladorCorreo(bool $simularFalloCorreo = false): ModeloRecuperacion
    {
        return new class($simularFalloCorreo) extends ModeloRecuperacion {
            private bool $fallarCorreo;

            public function __construct(bool $fallarCorreo)
            {
                parent::__construct();
                $this->fallarCorreo = $fallarCorreo;
            }

            protected function enviarCorreo($mensaje): array
            {
                if ($this->fallarCorreo) {
                    return ['accion' => 'error', 'mensaje' => 'No se pudo enviar el correo. Error: SMTP Connection timed out'];
                }

                $codigo = '654321';
                $_SESSION['codigo_verificacion'] = $codigo;
                $_SESSION['codigo_timestamp'] = time();
                $_SESSION['codigo_intentos'] = 0;

                return ['accion' => 'comprobar', 'mensaje' => $mensaje];
            }
        };
    }

    /**
     * Caso 1: Cédula registrada y activa inicia proceso de recuperación y genera código en sesión.
     */
    public function testComprobarCedulaExistenteIniciaRecuperacionExitosamente(): void
    {
        $datosUsuario = [
            'correo' => 'juan.perez@test.com',
            'nombreUsuario' => 'Juan',
            'apellidoUsuario' => 'Perez'
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('bindParam')->willReturn(true);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetch')->willReturn($datosUsuario);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtMock) {
            $this->assertStringContainsString('FROM `usuarios`', $sql);
            $this->assertStringContainsString('cedulaUsuario=:cedula', $sql);
            $this->assertStringContainsString('estatus=1', $sql);
            return $stmtMock;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = $this->crearModeloConSimuladorCorreo(false);
        $resultado = $modelo->ProcesarDatos([
            'accion' => 'comprobar',
            'cedula' => '12345678'
        ]);

        $this->assertEquals('comprobar', $resultado['accion']);
        $this->assertStringContainsString('Se envio un código a su correo', $resultado['mensaje']);
        $this->assertEquals('juan.perez@test.com', $_SESSION['correo']);
        $this->assertEquals('Juan Perez', $_SESSION['destinatario']);
        $this->assertEquals('12345678', $_SESSION['cedula_r']);
        $this->assertFalse($_SESSION['verificacion']);
        $this->assertEquals('654321', $_SESSION['codigo_verificacion']);
        $this->assertEquals(0, $_SESSION['codigo_intentos']);
    }

    /**
     * Caso 2: Cédula no registrada en base de datos retorna mensaje genérico seguro.
     */
    public function testComprobarCedulaNoRegistradaRetornaMensajeSeguro(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('bindParam')->willReturn(true);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetch')->willReturn(false);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('rollBack')->willReturn(true);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $this->inyectarPdoMock($pdoMock);

        $modelo = $this->crearModeloConSimuladorCorreo(false);
        $resultado = $modelo->ProcesarDatos([
            'accion' => 'comprobar',
            'cedula' => '99999999'
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Si la cédula está registrada, recibirás un correo.', $resultado['mensaje']);
        $this->assertArrayNotHasKey('correo', $_SESSION);
        $this->assertArrayNotHasKey('codigo_verificacion', $_SESSION);
    }

    /**
     * Caso 3: Fallo en el envío de correo retorna respuesta con acción error.
     */
    public function testComprobarCedulaFalloEnvioCorreoRetornaError(): void
    {
        $datosUsuario = [
            'correo' => 'juan.perez@test.com',
            'nombreUsuario' => 'Juan',
            'apellidoUsuario' => 'Perez'
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('bindParam')->willReturn(true);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetch')->willReturn($datosUsuario);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $this->inyectarPdoMock($pdoMock);

        $modelo = $this->crearModeloConSimuladorCorreo(true);
        $resultado = $modelo->ProcesarDatos([
            'accion' => 'comprobar',
            'cedula' => '12345678'
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('No se pudo enviar el correo', $resultado['mensaje']);
    }

    /**
     * Caso 4: Excepción de base de datos realiza rollback y retorna mensaje de error capturado.
     */
    public function testComprobarCedulaExcepcionBaseDatosManejaRollback(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollBack')->willReturn(true);
        $pdoMock->method('prepare')->willThrowException(new Exception('Error de conexión con el servidor MySQL'));

        $this->inyectarPdoMock($pdoMock);

        $modelo = $this->crearModeloConSimuladorCorreo(false);
        $resultado = $modelo->ProcesarDatos([
            'accion' => 'comprobar',
            'cedula' => '12345678'
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('Error de conexión con el servidor MySQL', $resultado['mensaje']);
    }
}

