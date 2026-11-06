<?php

declare(strict_types=1);

namespace Tests\Acceptance\Monedas;

use Codeception\Attribute\Group;
use Tests\Support\AcceptanceTester;

if (!defined('_DB_NAME_')) {
    require_once __DIR__ . '/../../../config/config.php';
}

#[Group('monedas')]
#[Group('interfaz')]
final class MonedasCest
{
    private const RUTA_MODULO           = '/Monedas';
    private const TITULO_MODULO         = 'Monedas';

    // Selectores específicos de Monedas
    private const SELECT_ABREVIATURA    = '#abreviatura';
    private const INPUT_SIMBOLO         = '#simbolo';
    private const BTN_BLOQUEAR          = '#resultadoconsulta button[onclick*="bloquear"]';

    // Datos de prueba (Euro es la moneda oficial del sistema no registrada inicialmente)
    private string $isoTest             = 'EUR';
    private string $nombreTest          = 'Euro';
    private string $simboloTest         = '€';

    public function _before(AcceptanceTester $I): void
    {
        // Login con snapshot + navegación + comprobación de título y limpieza de overlays
        $I->irAlModulo(self::RUTA_MODULO, self::TITULO_MODULO);
    }

    /**
     * Prueba 1: Verifica que la interfaz cargue sus componentes base correctamente.
     */
    #[Group('carga', 'interfaz')]
    public function probarCargaInicialDeInterfaz(AcceptanceTester $I): void
    {
        $I->see(self::TITULO_MODULO, AcceptanceTester::TITULO_PAGINA);
        $I->seeElement(AcceptanceTester::INPUT_BUSQUEDA);
        $I->seeElement(AcceptanceTester::BTN_NUEVO);
        $I->seeElement(AcceptanceTester::CONT_RESULTADOS);

        // Monedas fijas del sistema visibles en el listado
        $I->see('Dólar', AcceptanceTester::CONT_RESULTADOS);
        $I->see('Bolívar', AcceptanceTester::CONT_RESULTADOS);

        // El modal existe en el DOM pero debe arrancar oculto
        $I->seeElementInDOM(AcceptanceTester::MODAL);
        $I->dontSeeElement(AcceptanceTester::MODAL);

        $I->verificarSinErrores();
    }

    /**
     * Prueba 2: Validaciones del lado del cliente antes de registrar.
     * Analiza las respuestas de validarEnvio() en monedas.js y main.js.
     */
    #[Group('validacion')]
    public function probarValidacionesDeFormulario(AcceptanceTester $I): void
    {
        // 1. Abrir modal
        $I->click(AcceptanceTester::BTN_NUEVO);
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->see('Registrar Moneda', AcceptanceTester::MODAL_TITULO);

        // 2. Intentar registrar con formulario vacío (sin seleccionar ISO)
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('Tiene que seleccionar una Moneda (ISO)', 5);
        $I->aceptarSweetAlert();

        // 3. Cerrar modal limpiamente
        $I->click(AcceptanceTester::MODAL_CERRAR);
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        $I->verificarSinErrores();
    }

    /**
     * Prueba 3: Registrar una nueva moneda con confirmación de SweetAlert2.
     */
    #[Group('registrar', 'crud')]
    public function probarRegistrarMoneda(AcceptanceTester $I): void
    {
        $this->limpiarRegistroSiExiste($this->isoTest);

        // 1. Abrir modal de inclusión
        $I->click(AcceptanceTester::BTN_NUEVO);
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->see('Registrar Moneda', AcceptanceTester::MODAL_TITULO);

        // 2. Seleccionar abreviatura y verificar auto-rellenado reactivo
        $I->executeJS("$('#abreviatura').val('" . $this->isoTest . "').trigger('change');");
        $I->waitForValue(AcceptanceTester::INPUT_NOMBRE, $this->nombreTest, 3);
        $I->waitForValue(self::INPUT_SIMBOLO, $this->simboloTest, 3);

        // 3. Enviar y confirmar en SweetAlert
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere registrar esta moneda?', 5);
        $I->confirmarSweetAlert();

        // 4. Esperar alerta de éxito ("Registro Exitoso")
        $I->waitForText('Registro Exitoso', 5);
        $I->aceptarSweetAlert();

        // 5. Cerrar modal (en incluir no se cierra automáticamente en monedas.js)
        $I->click(AcceptanceTester::MODAL_CERRAR);
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        // 6. Verificar en la lista mediante el buscador
        $I->buscarEnListado($this->nombreTest);
        $I->waitForText($this->nombreTest, 5, AcceptanceTester::CONT_RESULTADOS);
        $I->see($this->isoTest, AcceptanceTester::CONT_RESULTADOS);
        $I->see($this->simboloTest, AcceptanceTester::CONT_RESULTADOS);

        $I->verificarSinErrores();
    }

    /**
     * Prueba 4: Uso del buscador en tiempo real (coincidencia, sin resultados y restauración).
     */
    #[Group('buscador')]
    public function probarBuscadorMonedas(AcceptanceTester $I): void
    {
        // 1. Búsqueda por término existente (moneda base preexistente Dólar)
        $I->buscarEnListado('Dólar');
        $I->waitForText('Dólar', 5, AcceptanceTester::CONT_RESULTADOS);
        $I->see('USD', AcceptanceTester::CONT_RESULTADOS);

        // 2. Búsqueda por término inexistente
        $I->buscarEnListado('MonedaInexistenteXYZ999');
        $I->waitForText('No se encontraron registros', 5, AcceptanceTester::CONT_RESULTADOS);

        // 3. Limpiar buscador para restaurar el listado completo
        $I->limpiarBuscador();
        $I->dontSee('No se encontraron registros', AcceptanceTester::CONT_RESULTADOS);

        $I->verificarSinErrores();
    }

    /**
     * Prueba 5: Modificar una moneda existente.
     */
    #[Group('modificar', 'crud')]
    public function probarModificarMoneda(AcceptanceTester $I): void
    {
        $this->asegurarRegistroExiste($this->isoTest, $this->nombreTest, $this->simboloTest);

        // 1. Ubicar la moneda con el buscador
        $I->buscarEnListado($this->nombreTest);
        $I->waitForText($this->nombreTest, 5, AcceptanceTester::CONT_RESULTADOS);

        // 2. Clic seguro en modificar
        $I->clickModificar();

        // 3. Esperar que el modal abra con datos precargados
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->waitForText('Modificar Moneda', 5, AcceptanceTester::MODAL_TITULO);
        $I->seeInField(AcceptanceTester::INPUT_NOMBRE, $this->nombreTest);
        $I->seeInField(self::INPUT_SIMBOLO, $this->simboloTest);

        // 4. Guardar y confirmar
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere modificar esta moneda?', 5);
        $I->confirmarSweetAlert();

        // 5. Esperar alerta de éxito ("Modificacion Exitosa") y cierre automático de modal
        $I->waitForText('Modificacion Exitosa', 5);
        $I->aceptarSweetAlert();
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        // 6. Validar permanencia en el listado
        $I->buscarEnListado($this->nombreTest);
        $I->waitForText($this->nombreTest, 5, AcceptanceTester::CONT_RESULTADOS);

        $I->verificarSinErrores();
    }

    /**
     * Prueba 6: Bloquear y desbloquear una moneda no base (requiere >2 monedas activas).
     */
    #[Group('bloquear')]
    public function probarBloquearMoneda(AcceptanceTester $I): void
    {
        $this->asegurarRegistroExiste($this->isoTest, $this->nombreTest, $this->simboloTest);

        // 1. Ubicar la moneda con el buscador
        $I->buscarEnListado($this->nombreTest);
        $I->waitForText($this->nombreTest, 5, AcceptanceTester::CONT_RESULTADOS);

        // 2. Clic en bloquear
        $I->waitForElementVisible(self::BTN_BLOQUEAR, 5);
        $I->despejarOverlays();
        $I->executeJS("document.querySelector('" . self::BTN_BLOQUEAR . "').click();");

        // 3. Confirmar bloqueo en SweetAlert
        $I->waitForText('¿Está seguro que quiere bloquear esta Moneda?', 5);
        $I->confirmarSweetAlert();

        // 4. Esperar alerta de éxito ("Bloqueo Exitosa")
        $I->waitForText('Bloqueo Exitosa', 5);
        $I->aceptarSweetAlert();

        // 5. Re-ubicar y desbloquear
        $I->buscarEnListado($this->nombreTest);
        $I->waitForText($this->nombreTest, 5, AcceptanceTester::CONT_RESULTADOS);
        $I->waitForElementVisible(self::BTN_BLOQUEAR, 5);
        $I->despejarOverlays();
        $I->executeJS("document.querySelector('" . self::BTN_BLOQUEAR . "').click();");

        // 6. Confirmar desbloqueo
        $I->waitForText('¿Está seguro que quiere desbloquear esta Moneda?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Bloqueo Exitosa', 5);
        $I->aceptarSweetAlert();

        $I->verificarSinErrores();
    }

    /**
     * Prueba 7: Eliminar una moneda existente de manera autónoma (>2 monedas en el sistema).
     */
    #[Group('eliminar', 'crud')]
    public function probarEliminarMoneda(AcceptanceTester $I): void
    {
        $this->asegurarRegistroExiste($this->isoTest, $this->nombreTest, $this->simboloTest);

        // 1. Localizar la moneda con el buscador
        $I->buscarEnListado($this->nombreTest);
        $I->waitForText($this->nombreTest, 5, AcceptanceTester::CONT_RESULTADOS);

        // 2. Clic seguro en eliminar
        $I->clickEliminar();

        // 3. Confirmar en SweetAlert
        $I->waitForText('¿Está seguro que quiere eliminar esta moneda?', 5);
        $I->confirmarSweetAlert();

        // 4. Esperar éxito ("Eliminacion Exitosa")
        $I->waitForText('Eliminacion Exitosa', 5);
        $I->aceptarSweetAlert();

        // 5. Verificar que ya no existe
        $I->buscarEnListado($this->nombreTest);
        $I->waitForText('No se encontraron registros', 5, AcceptanceTester::CONT_RESULTADOS);
        $I->dontSee($this->nombreTest, AcceptanceTester::CONT_RESULTADOS);

        $I->verificarSinErrores();
    }

    /**
     * Prueba 8: Ciclo de vida completo continuo (Registrar -> Buscar -> Modificar -> Eliminar).
     */
    #[Group('cicloCompleto', 'e2e')]
    public function probarCicloCompletoMoneda(AcceptanceTester $I): void
    {
        $this->limpiarRegistroSiExiste($this->isoTest);

        // --- PASO 1: REGISTRAR ---
        $I->click(AcceptanceTester::BTN_NUEVO);
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->executeJS("$('#abreviatura').val('" . $this->isoTest . "').trigger('change');");
        $I->waitForValue(AcceptanceTester::INPUT_NOMBRE, $this->nombreTest, 3);
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere registrar esta moneda?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Registro Exitoso', 5);
        $I->aceptarSweetAlert();
        $I->click(AcceptanceTester::MODAL_CERRAR);
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        // --- PASO 2: BUSCAR ---
        $I->buscarEnListado($this->nombreTest);
        $I->waitForText($this->nombreTest, 5, AcceptanceTester::CONT_RESULTADOS);

        // --- PASO 3: MODIFICAR ---
        $I->clickModificar();
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->waitForText('Modificar Moneda', 5, AcceptanceTester::MODAL_TITULO);
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere modificar esta moneda?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Modificacion Exitosa', 5);
        $I->aceptarSweetAlert();
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        // --- PASO 4: BUSCAR MODIFICADO ---
        $I->buscarEnListado($this->nombreTest);
        $I->waitForText($this->nombreTest, 5, AcceptanceTester::CONT_RESULTADOS);

        // --- PASO 5: ELIMINAR ---
        $I->clickEliminar();
        $I->waitForText('¿Está seguro que quiere eliminar esta moneda?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Eliminacion Exitosa', 5);
        $I->aceptarSweetAlert();

        // --- PASO 6: VALIDAR INEXISTENCIA ---
        $I->buscarEnListado($this->nombreTest);
        $I->waitForText('No se encontraron registros', 5, AcceptanceTester::CONT_RESULTADOS);

        $I->verificarSinErrores();
    }

    // =========================================================================
    // HELPERS DE BASE DE DATOS
    // =========================================================================

    private function limpiarRegistroSiExiste(string $iso): void
    {
        try {
            $modelo = new \App\modelo\ModeloMonedas();
            $res = $modelo->Consultar(['filtro' => $iso]);
            if (!empty($res['datos'])) {
                foreach ($res['datos'] as $d) {
                    if (strcasecmp($d['abreviatura'], $iso) === 0 && (int)$d['base'] !== 1) {
                        $modelo->ProcesarDatos([
                            'accion' => 'eliminar',
                            'id'     => (string)$d['codigo_moneda']
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Silencioso para no detener la prueba si no hay conexión
        }
    }

    private function asegurarRegistroExiste(string $iso, string $nombre, string $simbolo): void
    {
        try {
            $modelo = new \App\modelo\ModeloMonedas();
            $res = $modelo->Consultar(['filtro' => $iso]);
            $encontrado = false;
            if (!empty($res['datos'])) {
                foreach ($res['datos'] as $d) {
                    if (strcasecmp($d['abreviatura'], $iso) === 0) {
                        $encontrado = true;
                        break;
                    }
                }
            }
            if (!$encontrado) {
                $modelo->ProcesarDatos([
                    'accion'      => 'incluir',
                    'abreviatura' => $iso,
                    'nombre'      => $nombre,
                    'simbolo'     => $simbolo
                ]);
            }
        } catch (\Throwable $e) {
            // Silencioso para no interferir con la ejecución UI
        }
    }
}

