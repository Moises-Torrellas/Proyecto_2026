<?php

namespace tests\Unit\Inicio;

use App\modelo\Conexion;
use PHPUnit\Framework\TestCase;
use App\modelo\ModeloInicio;
use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

#[Group("unit")]
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

        $reflecion = new ReflectionClass(Conexion::class);
        $propiedad = $reflecion->getProperty('instanciaSG');
        $propiedad->setValue(null,$pdoMock);

        $datos = ['cedula'=>'78954125', 'clave'=>'MiClave123$'];

        // 3. Act: Ejecutamos la unidad
        $modelo = new ModeloInicio();
        $resultado = $modelo->ProcesarDatos($datos);

        // 4. Assert: Afirmamos los resultados (ya puedes borrar el var_dump)
        $this->assertEquals('inicio', $resultado['accion']);
        $this->assertEquals(0, $resultado['resultado']);
        $this->assertEquals('Las credenciales proporcionadas no son correctas.', $resultado['mensaje']);
    }
}