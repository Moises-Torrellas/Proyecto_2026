<?php

namespace tests\Unit\Bitacora;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloBitacora;
use App\interface\InterBitacora;
use Exception;
use PHPUnit\Framework\Attributes\Group;

require_once __DIR__ . '/BitacoraMockTrait.php';

/**
 * RF-BIT-01: El sistema debe registrar en la bitácora cada acción relevante que realizan
 * los usuarios: módulo, descripción de la acción, usuario, datos previos, datos nuevos y
 * entorno (navegador). Una falla al registrar la bitácora no debe interrumpir la
 * operación que el usuario estaba realizando.
 *
 * Escenarios:
 *  E1. Registro de una acción con todos sus datos.
 *  E2. Registro de una acción sin datos previos ni nuevos.
 *  E3. Falla de la base de datos al registrar.
 */
#[Group('unit')]
#[Group('unitBitacora')]
#[Group('registrarAccionBitacora')]
class RegistrarAccionBitacoraTest extends TestCase
{
    use BitacoraMockTrait;

    // Caso 1 (E1): El modelo implementa el contrato de bitácora usado por los demás módulos
    public function testImplementaInterfaz(): void
    {
        $this->assertInstanceOf(InterBitacora::class, new ModeloBitacora());
    }

    // Caso 2 (E1): Registro exitoso envía todos los datos al procedimiento almacenado
    public function testRegistroExitoso(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock());

        $datosPrevios = json_encode(['nombre' => 'Entrenador']);
        $datosNuevos  = json_encode(['nombre' => 'Entrenador Principal']);

        (new ModeloBitacora())->RegistrarAccion(
            103,
            'Modificó el Rol: Entrenador Principal',
            4,
            $datosPrevios,
            $datosNuevos,
            'Mozilla/5.0 (Windows NT 10.0)'
        );

        $llamada = $this->sentenciaEjecutada('CALL pa_incluir_bitacora');
        $this->assertNotNull($llamada);
        $this->assertSame([
            ':modulo'        => 103,
            ':accion'        => 'Modificó el Rol: Entrenador Principal',
            ':datos_previos' => $datosPrevios,
            ':datos_nuevos'  => $datosNuevos,
            ':entorno'       => 'Mozilla/5.0 (Windows NT 10.0)',
            ':usuario'       => 4,
        ], $llamada['params']);
    }

    // Caso 3 (E2): Registro sin datos previos, nuevos ni entorno usa los valores por defecto
    public function testRegistroSoloAccion(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock());

        (new ModeloBitacora())->RegistrarAccion(103, 'Ingreso al Modulo', 4);

        $params = $this->sentenciaEjecutada('CALL pa_incluir_bitacora')['params'];
        $this->assertNull($params[':datos_previos']);
        $this->assertNull($params[':datos_nuevos']);
        $this->assertSame('', $params[':entorno']);
        $this->assertSame('Ingreso al Modulo', $params[':accion']);
    }

    // Caso 4 (E3): Una falla de la BD no se propaga a la operación del usuario
    public function testErrorBaseDatosNoInterrumpe(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([
            'CALL pa_incluir_bitacora' => ['error' => new Exception('Procedimiento no encontrado')],
        ]));

        $resultado = (new ModeloBitacora())->RegistrarAccion(103, 'Ingreso al Modulo', 4);

        $this->assertNull($resultado);
        $this->assertNotNull($this->sentenciaEjecutada('CALL pa_incluir_bitacora'));
    }

    // Caso 5 (E3): Si la falla ocurre dentro de una transacción abierta, se revierte
    public function testErrorConTransaccionHaceRollback(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([
            'CALL pa_incluir_bitacora' => ['error' => new Exception('Error al registrar')],
        ], enTransaccion: true));

        (new ModeloBitacora())->RegistrarAccion(103, 'Ingreso al Modulo', 4);

        $this->assertNotNull($this->sentenciaEjecutada('ROLLBACK'));
    }

    // Caso 6 (E3): Si no hay transacción abierta, no se intenta revertir
    public function testErrorSinTransaccionNoHaceRollback(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([
            'CALL pa_incluir_bitacora' => ['error' => new Exception('Error al registrar')],
        ], enTransaccion: false));

        (new ModeloBitacora())->RegistrarAccion(103, 'Ingreso al Modulo', 4);

        $this->assertNull($this->sentenciaEjecutada('ROLLBACK'));
    }
}
