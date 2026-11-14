<?php

namespace tests\Unit\Permisos;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloPermisos;
use Exception;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;

require_once __DIR__ . '/PermisosMockTrait.php';

/**
 * RF-PER-01: El sistema debe validar los datos de un permiso antes de procesarlo:
 *  - id e id de módulo numéricos.
 *  - estado de bloqueo: 1 (activo) o 2 (bloqueado).
 *  - nombre y descripción: solo letras y espacios, de 3 a 100 caracteres.
 *  - clave técnica: minúsculas y guion bajo, de 5 a 50 caracteres.
 * Nombre y descripción se normalizan a formato título y la clave a minúsculas.
 *
 * Escenarios:
 *  E1. Petición sin datos o con acción no reconocida.
 *  E2. Datos con formato inválido.
 *  E3. Datos válidos que deben normalizarse.
 */
#[Group('unit')]
#[Group('unitPermisos')]
#[Group('validarDatosPermisos')]
class ValidarDatosPermisosTest extends TestCase
{
    use PermisosMockTrait;

    private function procesarSinEjecutar(ModeloPermisos $modelo, array $datos): void
    {
        try {
            $modelo->ProcesarDatos($datos + ['accion' => 'accion_inexistente']);
        } catch (Exception $e) {
            if ($e->getMessage() !== 'La acción no es válida') {
                throw $e;
            }
        }
    }

    private function propiedad(ModeloPermisos $modelo, string $nombre): mixed
    {
        return (new ReflectionClass($modelo))->getProperty($nombre)->getValue($modelo);
    }

    // Caso 1 (E1): Arreglo de datos vacío lanza excepción
    public function testDatosVacios(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se proporcionaron datos para procesar.');

        (new ModeloPermisos())->ProcesarDatos([]);
    }

    // Caso 2 (E1): Acción no reconocida lanza excepción
    public function testAccionInvalida(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La acción no es válida');

        (new ModeloPermisos())->ProcesarDatos(['accion' => 'eliminar']);
    }

    public static function datosInvalidos(): array
    {
        return [
            'id con letras'                => [['id' => '5a'], 'Id inválido.'],
            'id con inyección SQL'         => [['id' => '1 OR 1=1'], 'Id inválido.'],
            'bloqueo fuera de rango'       => [['bloqueo' => '3'], 'bloqueo inválido.'],
            'bloqueo de varios dígitos'    => [['bloqueo' => '12'], 'bloqueo inválido.'],
            'módulo con letras'            => [['modulo' => 'abc'], 'Id de módulo inválido.'],
            'nombre muy corto'             => [['nombre' => 'Ve'], 'Nombre inválido.'],
            'nombre con números'           => [['nombre' => 'Registrar 2'], 'Nombre inválido.'],
            'descripción con símbolos'     => [['descripcion' => '<b>Permite</b>'], 'Descripción inválida.'],
            'clave con mayúsculas'         => [['clave' => 'Ver_Atletas'], 'La clave del permiso es inválida. Debe estar en minúsculas, usar guion bajo y tener entre 5 y 50 caracteres.'],
            'clave con espacios'           => [['clave' => 'ver atletas'], 'La clave del permiso es inválida. Debe estar en minúsculas, usar guion bajo y tener entre 5 y 50 caracteres.'],
            'clave muy corta'              => [['clave' => 'ver'], 'La clave del permiso es inválida. Debe estar en minúsculas, usar guion bajo y tener entre 5 y 50 caracteres.'],
            'clave de más de 50'           => [['clave' => str_repeat('a', 51)], 'La clave del permiso es inválida. Debe estar en minúsculas, usar guion bajo y tener entre 5 y 50 caracteres.'],
        ];
    }

    // Casos 3 a 14 (E2): Datos con formato inválido son rechazados
    #[DataProvider('datosInvalidos')]
    public function testDatoInvalido(array $datos, string $mensaje): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage($mensaje);

        // Si la validación no detiene la petición, la búsqueda no debe llegar a la BD real
        $this->inyectarPdoMock($this->crearPdoMock());
        (new ModeloPermisos())->ProcesarDatos($datos + ['accion' => 'buscar']);
    }

    // Caso 15 (E3): Nombre, descripción y clave se normalizan
    public function testNormalizaDatos(): void
    {
        $modelo = new ModeloPermisos();
        $this->procesarSinEjecutar($modelo, [
            'id'          => '9',
            'modulo'      => '6',
            'bloqueo'     => '1',
            'nombre'      => '  registrar atleta ',
            'descripcion' => ' permite REGISTRAR atletas ',
            'clave'       => 'registrar_atleta',
        ]);

        $this->assertSame('9', $this->propiedad($modelo, 'id'));
        $this->assertSame('6', $this->propiedad($modelo, 'modulo'));
        $this->assertSame('1', $this->propiedad($modelo, 'bloqueo'));
        $this->assertSame('Registrar Atleta', $this->propiedad($modelo, 'nombre'));
        $this->assertSame('Permite Registrar Atletas', $this->propiedad($modelo, 'descripcion'));
        $this->assertSame('registrar_atleta', $this->propiedad($modelo, 'clave'));
    }

    // Caso 16 (E3): Sin descripción queda nula; sin clave queda vacía
    public function testValoresPorDefecto(): void
    {
        $modelo = new ModeloPermisos();
        $this->procesarSinEjecutar($modelo, ['nombre' => 'Ver Reportes']);

        $this->assertNull($this->propiedad($modelo, 'id'));
        $this->assertNull($this->propiedad($modelo, 'descripcion'));
        $this->assertSame('', $this->propiedad($modelo, 'clave'));
    }
}
