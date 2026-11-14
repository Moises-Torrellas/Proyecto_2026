<?php

namespace tests\Unit\Modulos;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloModulos;
use Exception;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;

require_once __DIR__ . '/ModulosMockTrait.php';

/**
 * RF-MOD-01: El sistema debe validar los datos de un módulo antes de procesarlo:
 *  - id numérico.
 *  - nombre: solo letras (incluidas tildes y ñ) y espacios, de 3 a 50 caracteres.
 *  - descripción: solo letras y espacios, de 3 a 100 caracteres.
 * Ante un dato inválido se informa al usuario qué campo está mal. Nombre y descripción
 * se normalizan a formato título.
 *
 * Escenarios:
 *  E1. Petición sin datos o con acción no reconocida.
 *  E2. Datos con formato inválido.
 *  E3. Datos válidos en el límite de longitud.
 *  E4. Datos válidos que deben normalizarse.
 */
#[Group('unit')]
#[Group('unitModulos')]
#[Group('validarDatosModulos')]
class ValidarDatosModulosTest extends TestCase
{
    use ModulosMockTrait;

    private function procesarSinEjecutar(ModeloModulos $modelo, array $datos): void
    {
        try {
            $modelo->ProcesarDatos($datos + ['accion' => 'accion_inexistente']);
        } catch (Exception $e) {
            if ($e->getMessage() !== 'La accion no es valida') {
                throw $e;
            }
        }
    }

    private function propiedad(ModeloModulos $modelo, string $nombre): mixed
    {
        return (new ReflectionClass($modelo))->getProperty($nombre)->getValue($modelo);
    }

    // Caso 1 (E1): Arreglo de datos vacío lanza excepción
    public function testDatosVacios(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se proporcionaron datos para procesar.');

        (new ModeloModulos())->ProcesarDatos([]);
    }

    // Caso 2 (E1): Acciones no permitidas en este módulo (no se registran ni eliminan módulos)
    public function testAccionNoPermitida(): void
    {
        foreach (['incluir', 'eliminar', 'otra'] as $accion) {
            try {
                (new ModeloModulos())->ProcesarDatos(['accion' => $accion]);
                $this->fail("La acción '$accion' no debería estar permitida.");
            } catch (Exception $e) {
                $this->assertSame('La accion no es valida', $e->getMessage());
            }
        }
    }

    public static function datosInvalidos(): array
    {
        return [
            'id con letras'              => [['id' => '3a'], 'Id inválido.'],
            'id con inyección SQL'       => [['id' => '1 OR 1=1'], 'Id inválido.'],
            'nombre muy corto'           => [['nombre' => 'Ab'], 'Nombre inválido.'],
            'nombre con números'         => [['nombre' => 'Atletas 2'], 'Nombre inválido.'],
            'nombre de más de 50'        => [['nombre' => str_repeat('a', 51)], 'Nombre inválido.'],
            'descripción con símbolos'   => [['descripcion' => 'Gestiona <script>'], 'Descripción inválida.'],
            'descripción de más de 100'  => [['descripcion' => str_repeat('a', 101)], 'Descripción inválida.'],
        ];
    }

    // Casos 3 a 9 (E2): Datos con formato inválido son rechazados con el mensaje del campo
    #[DataProvider('datosInvalidos')]
    public function testDatoInvalido(array $datos, string $mensaje): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage($mensaje);

        // Si la validación no detiene la petición, la búsqueda no debe llegar a la BD real
        $this->inyectarPdoMock($this->crearPdoMock());
        (new ModeloModulos())->ProcesarDatos($datos + ['accion' => 'buscar']);
    }

    // Caso 10 (E3): Nombre de 50 letras con tildes y ñ es aceptado
    public function testNombreLimiteConTildes(): void
    {
        $nombre = str_repeat('Gestión Año ', 4) . 'Ñu'; // 50 caracteres
        $this->assertSame(50, mb_strlen($nombre));

        $modelo = new ModeloModulos();
        $this->procesarSinEjecutar($modelo, ['nombre' => $nombre]);

        $this->assertSame(mb_convert_case($nombre, MB_CASE_TITLE, 'UTF-8'), $this->propiedad($modelo, 'nombre'));
    }

    // Caso 11 (E3): Descripción de 100 letras con tildes y ñ es aceptada
    public function testDescripcionLimiteConTildes(): void
    {
        $descripcion = str_repeat('Gestión de niños ', 5) . str_repeat('ñ', 15); // 100 caracteres
        $this->assertSame(100, mb_strlen($descripcion));

        $modelo = new ModeloModulos();
        $this->procesarSinEjecutar($modelo, ['descripcion' => $descripcion]);

        $this->assertNotSame('', $this->propiedad($modelo, 'descripcion'));
    }

    // Caso 12 (E4): Nombre y descripción se normalizan a formato título sin espacios sobrantes
    public function testNormalizaDatos(): void
    {
        $modelo = new ModeloModulos();
        $this->procesarSinEjecutar($modelo, [
            'id'          => '6',
            'nombre'      => '  gestión de ATLETAS ',
            'descripcion' => ' registro y control de atletas ',
        ]);

        $this->assertSame('6', $this->propiedad($modelo, 'id'));
        $this->assertSame('Gestión De Atletas', $this->propiedad($modelo, 'nombre'));
        $this->assertSame('Registro Y Control De Atletas', $this->propiedad($modelo, 'descripcion'));
    }

    // Caso 13 (E4): Valores por defecto cuando no se envían campos
    public function testValoresPorDefecto(): void
    {
        $modelo = new ModeloModulos();
        $this->procesarSinEjecutar($modelo, ['otro' => 'x']);

        $this->assertNull($this->propiedad($modelo, 'id'));
        $this->assertSame('', $this->propiedad($modelo, 'nombre'));
        $this->assertSame('', $this->propiedad($modelo, 'descripcion'));
    }
}
