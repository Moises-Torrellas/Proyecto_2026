<?php

namespace tests\Unit\Equipos;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloEquipos;
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
#[Group('unitEquipos')]
#[Group('RF-03')]
#[Group('ESC-03')]
class ModificarEquiposTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    private function crearModeloConPDOMock(PDO $pdoMock): ModeloEquipos
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $pdoMock);

        return new ModeloEquipos();
    }

    // Caso 18: Modificación exitosa actualizando nombre disponible y lista de atletas
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-18')]
    public function testModificarEquipoConNuevoNombreYAtletasExitoso(): void
    {
        $stmtCheckPropia = $this->createMock(PDOStatement::class);
        $stmtCheckPropia->method('execute')->willReturn(true);
        $stmtCheckPropia->method('fetchColumn')->willReturn(0); // El nombre es distinto al actual

        $stmtCheckGlobal = $this->createMock(PDOStatement::class);
        $stmtCheckGlobal->method('execute')->willReturn(true);
        $stmtCheckGlobal->method('fetchColumn')->willReturn(0); // Ningún otro equipo tiene este nombre

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('execute')->willReturn(true);

        $stmtRank = $this->createMock(PDOStatement::class);
        $stmtRank->method('fetchAll')->willReturn([1, 2, 3]);

        $stmtAtletasCat = $this->createMock(PDOStatement::class);
        $stmtAtletasCat->method('execute')->willReturn(true);
        $stmtAtletasCat->method('fetchAll')->willReturn([1]);

        $stmtDel = $this->createMock(PDOStatement::class);
        $stmtDel->method('execute')->willReturn(true);

        $stmtInsDet = $this->createMock(PDOStatement::class);
        $stmtInsDet->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('query')->willReturn($stmtRank);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use (
            $stmtCheckPropia,
            $stmtCheckGlobal,
            $stmtUpdate,
            $stmtAtletasCat,
            $stmtDel,
            $stmtInsDet
        ) {
            if (str_contains($sql, 'codigo_equipo = :id') && str_contains($sql, 'nombre = :valor')) {
                return $stmtCheckPropia;
            }
            if (str_contains($sql, 'COUNT(*)') && str_contains($sql, 'equipos')) {
                return $stmtCheckGlobal;
            }
            if (str_contains($sql, 'UPDATE equipos')) {
                return $stmtUpdate;
            }
            if (str_contains($sql, 'SELECT DISTINCT i.codigo_categoria')) {
                return $stmtAtletasCat;
            }
            if (str_contains($sql, 'DELETE FROM detalles_equipos')) {
                return $stmtDel;
            }
            return $stmtInsDet;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->ProcesarDatos([
            'accion'  => 'modificar',
            'id'      => 5,
            'nombre'  => 'Cannibals Juvenil Pro',
            'atletas' => [10, 11]
        ]);

        $this->assertEquals('modificar', $resultado['accion']);
        $this->assertEquals('Equipo modificado correctamente.', $resultado['mensaje']);
    }

    // Caso 19: Modificación exitosa conservando su propio nombre
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-19')]
    public function testModificarEquipoConservandoMismoNombreExitoso(): void
    {
        $stmtCheckPropia = $this->createMock(PDOStatement::class);
        $stmtCheckPropia->method('execute')->willReturn(true);
        $stmtCheckPropia->method('fetchColumn')->willReturn(1); // Es el mismo equipo conservando nombre

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('execute')->willReturn(true);

        $stmtRank = $this->createMock(PDOStatement::class);
        $stmtRank->method('fetchAll')->willReturn([1, 2, 3]);

        $stmtDel = $this->createMock(PDOStatement::class);
        $stmtDel->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('query')->willReturn($stmtRank);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtCheckPropia, $stmtUpdate, $stmtDel) {
            if (str_contains($sql, 'codigo_equipo = :id') && str_contains($sql, 'nombre = :valor')) {
                return $stmtCheckPropia;
            }
            if (str_contains($sql, 'UPDATE equipos')) {
                return $stmtUpdate;
            }
            return $stmtDel;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->ProcesarDatos([
            'accion'  => 'modificar',
            'id'      => 5,
            'nombre'  => 'Cannibals Juvenil',
            'atletas' => []
        ]);

        $this->assertEquals('modificar', $resultado['accion']);
        $this->assertEquals('Equipo modificado correctamente.', $resultado['mensaje']);
    }

    // Caso 20: Rechazo de modificación cuando el nombre ya pertenece a otro equipo
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-20')]
    public function testModificarEquipoNombreDuplicadoEnOtroEquipoRechazado(): void
    {
        $stmtCheckPropia = $this->createMock(PDOStatement::class);
        $stmtCheckPropia->method('execute')->willReturn(true);
        $stmtCheckPropia->method('fetchColumn')->willReturn(0);

        $stmtCheckGlobal = $this->createMock(PDOStatement::class);
        $stmtCheckGlobal->method('execute')->willReturn(true);
        $stmtCheckGlobal->method('fetchColumn')->willReturn(1); // En uso por otro equipo

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtCheckPropia, $stmtCheckGlobal) {
            if (str_contains($sql, 'codigo_equipo = :id') && str_contains($sql, 'nombre = :valor')) {
                return $stmtCheckPropia;
            }
            return $stmtCheckGlobal;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->ProcesarDatos([
            'accion'  => 'modificar',
            'id'      => 5,
            'nombre'  => 'Cannibals Oro',
            'atletas' => []
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_NAME, $resultado['mensaje']);
    }

    // Caso 21: Rechazo de modificación cuando la reasignación de atletas viola reglas de categoría
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-21')]
    public function testModificarEquipoFalloReglaCategoriasAtletasRechazado(): void
    {
        $stmtCheckPropia = $this->createMock(PDOStatement::class);
        $stmtCheckPropia->method('execute')->willReturn(true);
        $stmtCheckPropia->method('fetchColumn')->willReturn(1);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('execute')->willReturn(true);

        $stmtRank = $this->createMock(PDOStatement::class);
        $stmtRank->method('fetchAll')->willReturn([1, 2, 3]);

        $stmtAtletasCat = $this->createMock(PDOStatement::class);
        $stmtAtletasCat->method('execute')->willReturn(true);
        $stmtAtletasCat->method('fetchAll')->willReturn([1, 3]); // No contiguas

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('query')->willReturn($stmtRank);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtCheckPropia, $stmtUpdate, $stmtAtletasCat) {
            if (str_contains($sql, 'codigo_equipo = :id') && str_contains($sql, 'nombre = :valor')) {
                return $stmtCheckPropia;
            }
            if (str_contains($sql, 'UPDATE equipos')) {
                return $stmtUpdate;
            }
            return $stmtAtletasCat;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->ProcesarDatos([
            'accion'  => 'modificar',
            'id'      => 5,
            'nombre'  => 'Cannibals Juvenil',
            'atletas' => [2, 9]
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('El equipo solo puede conformarse por atletas de la misma categoría o de una categoría inferior inmediata.', $resultado['mensaje']);
    }

    // Caso 22: Captura de fallo en base de datos durante UPDATE con reversión de transacción
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-22')]
    public function testModificarEquipoFalloBaseDeDatosRollback(): void
    {
        $stmtCheckPropia = $this->createMock(PDOStatement::class);
        $stmtCheckPropia->method('execute')->willReturn(true);
        $stmtCheckPropia->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtCheckPropia) {
            if (str_contains($sql, 'codigo_equipo = :id') && str_contains($sql, 'nombre = :valor')) {
                return $stmtCheckPropia;
            }
            throw new PDOException('Error simulado al ejecutar UPDATE en equipos');
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->ProcesarDatos([
            'accion'  => 'modificar',
            'id'      => 5,
            'nombre'  => 'Cannibals Juvenil',
            'atletas' => []
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('Error simulado al ejecutar UPDATE en equipos', $resultado['mensaje']);
    }
}

