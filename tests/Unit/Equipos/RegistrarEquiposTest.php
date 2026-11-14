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
#[Group('RF-02')]
#[Group('ESC-02')]
class RegistrarEquiposTest extends TestCase
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

    // Caso 09: Registro exitoso de un equipo sin atletas
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-09')]
    public function testIncluirEquipoSinAtletasExitoso(): void
    {
        $stmtCheck = $this->createMock(PDOStatement::class);
        $stmtCheck->method('execute')->willReturn(true);
        $stmtCheck->method('fetchColumn')->willReturn(0);

        $stmtInsertEquipo = $this->createMock(PDOStatement::class);
        $stmtInsertEquipo->method('execute')->willReturn(true);

        $stmtRank = $this->createMock(PDOStatement::class);
        $stmtRank->method('fetchAll')->willReturn([1, 2, 3]);

        $stmtDel = $this->createMock(PDOStatement::class);
        $stmtDel->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('lastInsertId')->willReturn('10');
        $pdoMock->method('query')->willReturn($stmtRank);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtCheck, $stmtInsertEquipo, $stmtDel) {
            if (str_contains($sql, 'COUNT(*)')) {
                return $stmtCheck;
            }
            if (str_contains($sql, 'INSERT INTO equipos')) {
                return $stmtInsertEquipo;
            }
            return $stmtDel;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->ProcesarDatos([
            'accion'  => 'incluir',
            'nombre'  => 'Cannibals Mayores',
            'atletas' => []
        ]);

        $this->assertEquals('incluir', $resultado['accion']);
        $this->assertEquals('Equipo registrado correctamente.', $resultado['mensaje']);
    }

    // Caso 10: Registro exitoso con atletas de la misma categoría
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-10')]
    public function testIncluirEquipoConAtletasMismaCategoriaExitoso(): void
    {
        $stmtCheck = $this->createMock(PDOStatement::class);
        $stmtCheck->method('execute')->willReturn(true);
        $stmtCheck->method('fetchColumn')->willReturn(0);

        $stmtInsertEquipo = $this->createMock(PDOStatement::class);
        $stmtInsertEquipo->method('execute')->willReturn(true);

        $stmtRank = $this->createMock(PDOStatement::class);
        $stmtRank->method('fetchAll')->willReturn([1, 2, 3]);

        $stmtAtletasCat = $this->createMock(PDOStatement::class);
        $stmtAtletasCat->method('execute')->willReturn(true);
        $stmtAtletasCat->method('fetchAll')->willReturn([1]); // Todos pertenecen a la categoría 1

        $stmtDel = $this->createMock(PDOStatement::class);
        $stmtDel->method('execute')->willReturn(true);

        $stmtInsDet = $this->createMock(PDOStatement::class);
        $stmtInsDet->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('lastInsertId')->willReturn('12');
        $pdoMock->method('query')->willReturn($stmtRank);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtCheck, $stmtInsertEquipo, $stmtAtletasCat, $stmtDel, $stmtInsDet) {
            if (str_contains($sql, 'COUNT(*)')) {
                return $stmtCheck;
            }
            if (str_contains($sql, 'INSERT INTO equipos')) {
                return $stmtInsertEquipo;
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
            'accion'  => 'incluir',
            'nombre'  => 'Cannibals Élite',
            'atletas' => [5, 8, 12]
        ]);

        $this->assertEquals('incluir', $resultado['accion']);
        $this->assertEquals('Equipo registrado correctamente.', $resultado['mensaje']);
    }

    // Caso 11: Registro exitoso con atletas de 2 categorías contiguas en el ranking
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-11')]
    public function testIncluirEquipoConAtletasCategoriasContiguasExitoso(): void
    {
        $stmtCheck = $this->createMock(PDOStatement::class);
        $stmtCheck->method('execute')->willReturn(true);
        $stmtCheck->method('fetchColumn')->willReturn(0);

        $stmtInsertEquipo = $this->createMock(PDOStatement::class);
        $stmtInsertEquipo->method('execute')->willReturn(true);

        $stmtRank = $this->createMock(PDOStatement::class);
        $stmtRank->method('fetchAll')->willReturn([1, 2, 3]); // Cat 1 y Cat 2 son índices 0 y 1

        $stmtAtletasCat = $this->createMock(PDOStatement::class);
        $stmtAtletasCat->method('execute')->willReturn(true);
        $stmtAtletasCat->method('fetchAll')->willReturn([1, 2]); // 2 categorías contiguas

        $stmtDel = $this->createMock(PDOStatement::class);
        $stmtDel->method('execute')->willReturn(true);

        $stmtInsDet = $this->createMock(PDOStatement::class);
        $stmtInsDet->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('lastInsertId')->willReturn('15');
        $pdoMock->method('query')->willReturn($stmtRank);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtCheck, $stmtInsertEquipo, $stmtAtletasCat, $stmtDel, $stmtInsDet) {
            if (str_contains($sql, 'COUNT(*)')) {
                return $stmtCheck;
            }
            if (str_contains($sql, 'INSERT INTO equipos')) {
                return $stmtInsertEquipo;
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
            'accion'  => 'incluir',
            'nombre'  => 'Cannibals Promesas',
            'atletas' => [3, 4]
        ]);

        $this->assertEquals('incluir', $resultado['accion']);
        $this->assertEquals('Equipo registrado correctamente.', $resultado['mensaje']);
    }

    // Caso 12: Rechazo de registro cuando ya existe un equipo con el mismo nombre
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-12')]
    public function testIncluirEquipoNombreDuplicadoRechazado(): void
    {
        $stmtCheck = $this->createMock(PDOStatement::class);
        $stmtCheck->method('execute')->willReturn(true);
        $stmtCheck->method('fetchColumn')->willReturn(1); // Nombre duplicado

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtCheck);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->ProcesarDatos([
            'accion'  => 'incluir',
            'nombre'  => 'Cannibals Oro',
            'atletas' => []
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_NAME, $resultado['mensaje']);
    }

    // Caso 13: Rechazo de registro cuando los atletas no tienen inscripciones activas
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-13')]
    public function testIncluirEquipoAtletasSinInscripcionesActivasRechazado(): void
    {
        $stmtCheck = $this->createMock(PDOStatement::class);
        $stmtCheck->method('execute')->willReturn(true);
        $stmtCheck->method('fetchColumn')->willReturn(0);

        $stmtInsertEquipo = $this->createMock(PDOStatement::class);
        $stmtInsertEquipo->method('execute')->willReturn(true);

        $stmtRank = $this->createMock(PDOStatement::class);
        $stmtRank->method('fetchAll')->willReturn([1, 2, 3]);

        $stmtAtletasCat = $this->createMock(PDOStatement::class);
        $stmtAtletasCat->method('execute')->willReturn(true);
        $stmtAtletasCat->method('fetchAll')->willReturn([]); // No se obtuvieron categorías

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('lastInsertId')->willReturn('20');
        $pdoMock->method('query')->willReturn($stmtRank);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtCheck, $stmtInsertEquipo, $stmtAtletasCat) {
            if (str_contains($sql, 'COUNT(*)')) {
                return $stmtCheck;
            }
            if (str_contains($sql, 'INSERT INTO equipos')) {
                return $stmtInsertEquipo;
            }
            return $stmtAtletasCat;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->ProcesarDatos([
            'accion'  => 'incluir',
            'nombre'  => 'Cannibals Inactivos',
            'atletas' => [99]
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('No se pudo determinar la categoría de los atletas', $resultado['mensaje']);
    }

    // Caso 14: Rechazo cuando se asignan atletas de más de 2 categorías distintas
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-14')]
    public function testIncluirEquipoAtletasMasDeDosCategoriasRechazado(): void
    {
        $stmtCheck = $this->createMock(PDOStatement::class);
        $stmtCheck->method('execute')->willReturn(true);
        $stmtCheck->method('fetchColumn')->willReturn(0);

        $stmtInsertEquipo = $this->createMock(PDOStatement::class);
        $stmtInsertEquipo->method('execute')->willReturn(true);

        $stmtRank = $this->createMock(PDOStatement::class);
        $stmtRank->method('fetchAll')->willReturn([1, 2, 3, 4]);

        $stmtAtletasCat = $this->createMock(PDOStatement::class);
        $stmtAtletasCat->method('execute')->willReturn(true);
        $stmtAtletasCat->method('fetchAll')->willReturn([1, 2, 3]); // 3 categorías distintas

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('lastInsertId')->willReturn('21');
        $pdoMock->method('query')->willReturn($stmtRank);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtCheck, $stmtInsertEquipo, $stmtAtletasCat) {
            if (str_contains($sql, 'COUNT(*)')) {
                return $stmtCheck;
            }
            if (str_contains($sql, 'INSERT INTO equipos')) {
                return $stmtInsertEquipo;
            }
            return $stmtAtletasCat;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->ProcesarDatos([
            'accion'  => 'incluir',
            'nombre'  => 'Cannibals Mixto Excesivo',
            'atletas' => [1, 2, 3]
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('Un equipo no puede tener atletas de más de 2 categorías distintas.', $resultado['mensaje']);
    }

    // Caso 15: Rechazo cuando una de las categorías retornadas no figura en el ranking
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-15')]
    public function testIncluirEquipoAtletasCategoriaInexistenteEnRankingRechazado(): void
    {
        $stmtCheck = $this->createMock(PDOStatement::class);
        $stmtCheck->method('execute')->willReturn(true);
        $stmtCheck->method('fetchColumn')->willReturn(0);

        $stmtInsertEquipo = $this->createMock(PDOStatement::class);
        $stmtInsertEquipo->method('execute')->willReturn(true);

        $stmtRank = $this->createMock(PDOStatement::class);
        $stmtRank->method('fetchAll')->willReturn([1, 2]); // El ranking solo tiene 1 y 2

        $stmtAtletasCat = $this->createMock(PDOStatement::class);
        $stmtAtletasCat->method('execute')->willReturn(true);
        $stmtAtletasCat->method('fetchAll')->willReturn([1, 999]); // 999 no existe en ranking

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('lastInsertId')->willReturn('22');
        $pdoMock->method('query')->willReturn($stmtRank);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtCheck, $stmtInsertEquipo, $stmtAtletasCat) {
            if (str_contains($sql, 'COUNT(*)')) {
                return $stmtCheck;
            }
            if (str_contains($sql, 'INSERT INTO equipos')) {
                return $stmtInsertEquipo;
            }
            return $stmtAtletasCat;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->ProcesarDatos([
            'accion'  => 'incluir',
            'nombre'  => 'Cannibals Categoria Rara',
            'atletas' => [10, 20]
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('Una de las categorías de los atletas no existe en el sistema.', $resultado['mensaje']);
    }

    // Caso 16: Rechazo cuando las 2 categorías seleccionadas no son contiguas
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-16')]
    public function testIncluirEquipoAtletasCategoriasNoContiguasRechazado(): void
    {
        $stmtCheck = $this->createMock(PDOStatement::class);
        $stmtCheck->method('execute')->willReturn(true);
        $stmtCheck->method('fetchColumn')->willReturn(0);

        $stmtInsertEquipo = $this->createMock(PDOStatement::class);
        $stmtInsertEquipo->method('execute')->willReturn(true);

        $stmtRank = $this->createMock(PDOStatement::class);
        $stmtRank->method('fetchAll')->willReturn([1, 2, 3]); // Cat 1 índice 0, Cat 3 índice 2 (abs(0-2) = 2 > 1)

        $stmtAtletasCat = $this->createMock(PDOStatement::class);
        $stmtAtletasCat->method('execute')->willReturn(true);
        $stmtAtletasCat->method('fetchAll')->willReturn([1, 3]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('lastInsertId')->willReturn('23');
        $pdoMock->method('query')->willReturn($stmtRank);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtCheck, $stmtInsertEquipo, $stmtAtletasCat) {
            if (str_contains($sql, 'COUNT(*)')) {
                return $stmtCheck;
            }
            if (str_contains($sql, 'INSERT INTO equipos')) {
                return $stmtInsertEquipo;
            }
            return $stmtAtletasCat;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->ProcesarDatos([
            'accion'  => 'incluir',
            'nombre'  => 'Cannibals Separados',
            'atletas' => [1, 3]
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('El equipo solo puede conformarse por atletas de la misma categoría o de una categoría inferior inmediata.', $resultado['mensaje']);
    }

    // Caso 17: Captura de fallo en base de datos con reversión de transacción (Rollback)
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-17')]
    public function testIncluirEquipoFalloBaseDeDatosRollback(): void
    {
        $stmtCheck = $this->createMock(PDOStatement::class);
        $stmtCheck->method('execute')->willReturn(true);
        $stmtCheck->method('fetchColumn')->willReturn(0);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtCheck) {
            if (str_contains($sql, 'COUNT(*)')) {
                return $stmtCheck;
            }
            throw new PDOException('Fallo simulado al insertar equipo');
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->ProcesarDatos([
            'accion'  => 'incluir',
            'nombre'  => 'Cannibals Fallo',
            'atletas' => []
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('Fallo simulado al insertar equipo', $resultado['mensaje']);
    }
}

