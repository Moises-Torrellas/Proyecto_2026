<?php

namespace tests\Integration\Cargos;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloCargos;
use App\modelo\Conexion;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group("integ")]
class RegistrarCargoTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloCargos $modelo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('mysql:host=127.0.0.1;dbname=cannibalsbd2;charset=utf8', 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $this->pdo);

        $this->modelo = new ModeloCargos();

        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $this->pdo->exec("TRUNCATE TABLE vueltos;");
        $this->pdo->exec("TRUNCATE TABLE detalles_pagos;");
        $this->pdo->exec("TRUNCATE TABLE pagos;");
        $this->pdo->exec("TRUNCATE TABLE cargos;");
        $this->pdo->exec("TRUNCATE TABLE conceptos;");
        $this->pdo->exec("TRUNCATE TABLE monedas;");
        $this->pdo->exec("TRUNCATE TABLE atletas;");
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    protected function tearDown(): void
    {
        $this->pdo = null;
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    private function cargarDatosBaseParaPrueba(): void
    {
        $this->pdo->exec("INSERT INTO monedas (codigo_moneda, nombre, abreviatura, simbolo, base, estatus) VALUES (1, 'Bolívar', 'VES', 'Bs', 2, 1), (2, 'Dólar', 'USD', '$', 1, 1)");
        $this->pdo->exec("INSERT INTO conceptos (codigo_concepto, nombre, monto, frecuencia, dias_gracia, estatus) VALUES (1, 'Mensualidad', 30.00, 'M', 0, 1), (2, 'Inscripción', 25.00, 'A', 0, 1)");
        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, p_apellidos, genero, fecha_nac, foto) VALUES (1, 'Diego', 'Reinoso', 'H', '2011-12-07', 'foto.jpg')");
    }

    public function testIncluirCargoExitosoParaAtletaEnBaseDeDatosReal(): void
    {
        $this->cargarDatosBaseParaPrueba();

        $datos = [
            'accion'        => 'incluir',
            'id_concepto'   => 1,
            'id_atleta'     => [1],
            'monto_total'   => 30.00,
            'fecha_emision' => date('Y-m-d')
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);
        $this->assertNotEmpty($resultado['desc_bitacora']);

        // Verificamos que el cargo se insertó en la base de datos con estatus 1 (pendiente)
        $stmt = $this->pdo->query("SELECT * FROM cargos WHERE codigo_atleta = 1 AND codigo_concepto = 1");
        $cargoDB = $stmt->fetch();

        $this->assertNotEmpty($cargoDB, 'El cargo no se insertó en la base de datos.');
        $this->assertEquals(1, $cargoDB['estatus'], 'El estatus inicial del cargo debe ser 1 (pendiente).');
        $this->assertEquals(30.00, (float)$cargoDB['monto_total'], 'El monto del cargo no coincide.');
        $this->assertEquals(2, $cargoDB['codigo_moneda'], 'La moneda base esperada es 2 (USD).');
    }

    public function testIncluirCargoFallaSiAtletaYaTieneConceptoEnElMismoPeriodo(): void
    {
        $this->cargarDatosBaseParaPrueba();

        $datos = [
            'accion'        => 'incluir',
            'id_concepto'   => 1,
            'id_atleta'     => [1],
            'monto_total'   => 30.00,
            'fecha_emision' => date('Y-m-d')
        ];

        // Primer registro exitoso
        $res1 = $this->modelo->ProcesarDatos($datos);
        $this->assertEquals('exito', $res1['accion']);

        // Intento de emitir el mismo cargo mensual en el mismo periodo
        $res2 = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $res2['accion']);
        $this->assertStringContainsString('ya tiene asignado este concepto para el periodo correspondiente', $res2['codigo']);
    }

    public function testIncluirCargoFallaSiConceptoNoExiste(): void
    {
        $this->cargarDatosBaseParaPrueba();

        $datosInvalido = [
            'accion'        => 'incluir',
            'id_concepto'   => 999, // Concepto inexistente
            'id_atleta'     => [1],
            'monto_total'   => 30.00,
            'fecha_emision' => date('Y-m-d')
        ];

        $resultado = $this->modelo->ProcesarDatos($datosInvalido);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('concepto de cobro seleccionado no existe', $resultado['codigo']);
    }
}
