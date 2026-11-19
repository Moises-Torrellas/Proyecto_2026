<?php

declare(strict_types=1);

namespace tests\Integration\Premios;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloPremios;
use App\modelo\Conexion;
use Exception;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group("integ")]
#[Group("premios")]
class ModificarPremioIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloPremios $modelo;

    protected function setUp(): void
    {
        $dsn = 'mysql:host=' . (_DB_HOST_ ?: '127.0.0.1') . ';dbname=' . (_DB_NAME_ ?: 'cannibalsbd2') . ';charset=utf8';
        $this->pdo = new PDO($dsn, _DB_USER_ ?: 'root', _DB_PASS_ ?: '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $this->pdo);

        $this->modelo = new ModeloPremios();

        $this->limpiarTablas();
    }

    protected function tearDown(): void
    {
        if ($this->pdo) {
            $this->limpiarTablas();
            $reflector = new ReflectionClass(Conexion::class);
            $propiedad = $reflector->getProperty('instancia');
            $propiedad->setValue(null, null);
            $this->pdo = null;
        }
        parent::tearDown();
    }

    private function limpiarTablas(): void
    {
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $this->pdo->exec("TRUNCATE TABLE palmares_individual;");
        $this->pdo->exec("TRUNCATE TABLE palmares_grupal;");
        $this->pdo->exec("TRUNCATE TABLE premios;");
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    /**
     * ESC-INT-PREM-02 / CP-INT-PREM-06: Modificación exitosa del nombre de un premio existente.
     */
    public function testModificacionNombreExitosa(): void
    {
        $this->pdo->exec("INSERT INTO premios (codigo_premio, nombre, tipo) VALUES (1, 'Campeón', 'G')");

        $datos = [
            'accion'        => 'modificar',
            'codigo_premio' => 1,
            'nombre'        => 'campeón absoluto 2026',
            'tipo'          => 'G'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        // Verificamos en BD
        $stmt = $this->pdo->query("SELECT * FROM premios WHERE codigo_premio = 1");
        $premio = $stmt->fetch();

        $this->assertEquals('Campeón Absoluto 2026', $premio['nombre']);
        $this->assertEquals('G', $premio['tipo']);
    }

    /**
     * ESC-INT-PREM-02 / CP-INT-PREM-07: Modificación conservando su propio nombre (auto-exclusión de duplicado).
     */
    public function testModificacionConservandoMismoNombreExitoso(): void
    {
        $this->pdo->exec("INSERT INTO premios (codigo_premio, nombre, tipo) VALUES (1, 'Goleador', 'I')");

        $datos = [
            'accion'        => 'modificar',
            'codigo_premio' => 1,
            'nombre'        => 'Goleador',
            'tipo'          => 'I'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);
    }

    /**
     * ESC-INT-PREM-02 / CP-INT-PREM-08: Modificación exitosa del tipo de premio sin asociaciones.
     */
    public function testModificacionTipoSinAsociacionesExitoso(): void
    {
        $this->pdo->exec("INSERT INTO premios (codigo_premio, nombre, tipo) VALUES (1, 'Reconocimiento Especial', 'I')");

        // Cambiar de 'I' a 'G'
        $datos = [
            'accion'        => 'modificar',
            'codigo_premio' => 1,
            'nombre'        => 'Reconocimiento Especial',
            'tipo'          => 'G'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        $stmt = $this->pdo->query("SELECT tipo FROM premios WHERE codigo_premio = 1");
        $this->assertEquals('G', $stmt->fetchColumn(), 'El tipo debió actualizarse a grupal (G).');
    }

    /**
     * ESC-INT-PREM-02 / CP-INT-PREM-09: Rechazo al intentar modificar hacia el nombre de otro premio existente.
     */
    public function testErrorModificarNombreDuplicadoAjeno(): void
    {
        $this->pdo->exec("INSERT INTO premios (codigo_premio, nombre, tipo) VALUES 
                          (1, 'Premio Fair Play', 'G'),
                          (2, 'Mejor Defensor', 'I')");

        // Premio 2 intenta adoptar el nombre del Premio 1
        $datos = [
            'accion'        => 'modificar',
            'codigo_premio' => 2,
            'nombre'        => 'premio fair play',
            'tipo'          => 'I'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_NAME, $resultado['codigo']);

        // El premio 2 conserva su nombre intacto
        $stmt = $this->pdo->query("SELECT nombre FROM premios WHERE codigo_premio = 2");
        $this->assertEquals('Mejor Defensor', $stmt->fetchColumn());
    }

    /**
     * ESC-INT-PREM-02 / CP-INT-PREM-10: Bloqueo de cambio de tipo cuando el premio está vinculado a palmarés grupal.
     */
    public function testErrorModificarTipoAsociadoPalmaresGrupal(): void
    {
        $this->pdo->exec("INSERT INTO premios (codigo_premio, nombre, tipo) VALUES (1, 'Campeón de Grupo', 'G')");

        // Sembrar asociación en palmares_grupal
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $this->pdo->exec("INSERT INTO palmares_grupal (codigo_grupal, codigo_participacion, codigo_premio) VALUES (1, 100, 1)");
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

        // Intentar cambiar tipo de 'G' a 'I'
        $datos = [
            'accion'        => 'modificar',
            'codigo_premio' => 1,
            'nombre'        => 'Campeón de Grupo',
            'tipo'          => 'I'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('TYPE_ASSOCIATED', $resultado['codigo']);

        // El tipo no debe haber cambiado en la base de datos
        $stmt = $this->pdo->query("SELECT tipo FROM premios WHERE codigo_premio = 1");
        $this->assertEquals('G', $stmt->fetchColumn());
    }

    /**
     * ESC-INT-PREM-02 / CP-INT-PREM-11: Bloqueo de cambio de tipo cuando el premio está vinculado a palmarés individual.
     */
    public function testErrorModificarTipoAsociadoPalmaresIndividual(): void
    {
        $this->pdo->exec("INSERT INTO premios (codigo_premio, nombre, tipo) VALUES (1, 'Líder en Asistencias', 'I')");

        // Sembrar asociación en palmares_individual
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $this->pdo->exec("INSERT INTO palmares_individual (codigo_individual, codigo_premio, codigo_dtll_prtc) VALUES (1, 1, 200)");
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

        // Intentar cambiar tipo de 'I' a 'G'
        $datos = [
            'accion'        => 'modificar',
            'codigo_premio' => 1,
            'nombre'        => 'Líder en Asistencias',
            'tipo'          => 'G'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('TYPE_ASSOCIATED', $resultado['codigo']);

        // El tipo no debe haber cambiado en la base de datos
        $stmt = $this->pdo->query("SELECT tipo FROM premios WHERE codigo_premio = 1");
        $this->assertEquals('I', $stmt->fetchColumn());
    }
}
