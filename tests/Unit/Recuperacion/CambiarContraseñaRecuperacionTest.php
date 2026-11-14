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
#[Group('cambiarContraseñaRecuperacion')]
class CambiarContraseñaRecuperacionTest extends TestCase
{
    private ModeloRecuperacion $modelo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->modelo = new ModeloRecuperacion();
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

    /**
     * Caso 1: Rechazar cambio de contraseña si el usuario no ha superado la verificación del código.
     */
    public function testCambiarContraseñaSinVerificarRechazaOperacion(): void
    {
        $_SESSION['verificacion'] = false;
        $_SESSION['cedula_r'] = '12345678';

        $resultado = $this->modelo->ProcesarDatos([
            'accion' => 'cambiar',
            'contraseña' => 'NuevaClaveSegura2026$'
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('No esta verificado.', $resultado['mensaje']);
    }

    /**
     * Caso 2: Rechazar cambio de contraseña si la clave 'verificacion' ni siquiera existe en sesión.
     */
    public function testCambiarContraseñaSinClaveVerificacionEnSesionRechazaOperacion(): void
    {
        // Sin definir $_SESSION['verificacion']
        $resultado = $this->modelo->ProcesarDatos([
            'accion' => 'cambiar',
            'contraseña' => 'NuevaClaveSegura2026$'
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('No esta verificado.', $resultado['mensaje']);
    }

    /**
     * Caso 3: Actualización exitosa de contraseña con usuario verificado, hash bcrypt y limpieza de sesión.
     */
    public function testCambiarContraseñaExitosoActualizaBaseDatosYLimpiarSesion(): void
    {
        $_SESSION['verificacion'] = true;
        $_SESSION['cedula_r'] = '12345678';

        $hashCapturado = '';
        $cedulaCapturada = null;

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('bindParam')->willReturnCallback(function ($param, &$var) use (&$hashCapturado, &$cedulaCapturada) {
            if ($param === ':contra') {
                $hashCapturado = $var;
            } elseif ($param === ':cedula') {
                $cedulaCapturada = $var;
            }
            return true;
        });
        $stmtMock->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtMock) {
            $this->assertStringContainsString('UPDATE `usuarios`', $sql);
            $this->assertStringContainsString('`pass_hash` = :contra', $sql);
            $this->assertStringContainsString('WHERE cedulaUsuario = :cedula', $sql);
            return $stmtMock;
        });

        $this->inyectarPdoMock($pdoMock);

        $resultado = $this->modelo->ProcesarDatos([
            'accion' => 'cambiar',
            'contraseña' => 'MiClaveNueva99$'
        ]);

        $this->assertEquals('cambiar', $resultado['accion']);
        $this->assertEquals('Contraseña modificada correctamente.', $resultado['mensaje']);
        $this->assertEquals('Inicio', $resultado['url']);
        $this->assertEquals('12345678', $cedulaCapturada);
        $this->assertNotEmpty($hashCapturado);
        $this->assertTrue(password_verify('MiClaveNueva99$', $hashCapturado), 'El hash guardado debe ser verificable contra la nueva clave.');

        $this->assertArrayNotHasKey('verificacion', $_SESSION, 'La bandera de verificación debe eliminarse tras el cambio.');
        $this->assertArrayNotHasKey('cedula_r', $_SESSION, 'La cédula temporal debe eliminarse de la sesión.');
    }

    /**
     * Caso 4: Fallo en el execute de la sentencia SQL ejecuta rollback y retorna mensaje de error.
     */
    public function testCambiarContraseñaFalloExecuteSQLRealizaRollback(): void
    {
        $_SESSION['verificacion'] = true;
        $_SESSION['cedula_r'] = '12345678';

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('bindParam')->willReturn(true);
        $stmtMock->method('execute')->willReturn(false);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('rollBack')->willReturn(true);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $this->inyectarPdoMock($pdoMock);

        $resultado = $this->modelo->ProcesarDatos([
            'accion' => 'cambiar',
            'contraseña' => 'MiClaveNueva99$'
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error al modificar la contraseña.', $resultado['mensaje']);
    }

    /**
     * Caso 5: Excepción durante la actualización ejecuta rollback y captura el mensaje.
     */
    public function testCambiarContraseñaExcepcionBaseDatosManejaRollback(): void
    {
        $_SESSION['verificacion'] = true;
        $_SESSION['cedula_r'] = '12345678';

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollBack')->willReturn(true);
        $pdoMock->method('prepare')->willThrowException(new Exception('Error de bloqueo de tabla en base de datos'));

        $this->inyectarPdoMock($pdoMock);

        $resultado = $this->modelo->ProcesarDatos([
            'accion' => 'cambiar',
            'contraseña' => 'MiClaveNueva99$'
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('Error de bloqueo de tabla en base de datos', $resultado['mensaje']);
    }
}

