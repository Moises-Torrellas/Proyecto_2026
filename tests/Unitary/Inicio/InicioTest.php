<?php

namespace tests\Unitary\Inicio;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloInicio;
use PDO;
use PDOStatement;

class InicioTest extends TestCase{
    public function testProcesarDatosVacios(): void{
        $modelo = new ModeloInicio();
        $datosVacios = ['cedula'=>'', 'clave'=>''];

        $resultado = $modelo->ProcesarDatos($datosVacios);

        $this->assertEquals('inicio', $resultado['accion']);
        $this->assertEquals(0, $resultado['resultado']);
        $this->assertEquals('La cédula y la contraseña son obligatorias', $resultado['mensaje']);
    }

    public function testInicioUsuarioNoExiste(): void{
        // 1. Arrange: Simulamos las respuestas de la Base de Datos
        $mock = $this->createMock(PDOStatement::class);
        $mock->method('bindParam')->willReturn(true);
        $mock->method('execute')->willReturn(true);
        $mock->method('fetch')->willReturn(false);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($mock);

        // 2. Arrange: Creamos un "Fake" (Falsificación) según Concepto 2.4
        // Usamos una clase anónima para esquivar la restricción de métodos estáticos
        $modeloFake = new class extends ModeloInicio {
            // Propiedad estática para guardar nuestro Mock
            public static $pdoTemporal;

            // Sobrescribimos el método estático de la clase Conexion
            public static function conexSG() {
                return self::$pdoTemporal;
            }
        };

        // Inyectamos nuestro PDO simulado en la clase falsa
        $modeloFake::$pdoTemporal = $pdoMock;

        $datos = ['cedula'=>'78954125', 'clave'=>'MiClave123$'];

        // 3. Act: Ejecutamos la unidad
        $resultado = $modeloFake->ProcesarDatos($datos);

        // 4. Assert: Afirmamos los resultados (ya puedes borrar el var_dump)
        $this->assertEquals('inicio', $resultado['accion']);
        $this->assertEquals(0, $resultado['resultado']);
        $this->assertEquals('Las credenciales proporcionadas no son correctas.', $resultado['mensaje']);
    }
}