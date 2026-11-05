<?php

declare(strict_types=1);

namespace Tests\Acceptance\MetodosPago;

use Codeception\Attribute\Group;
use Tests\Support\AcceptanceTester;

if (!defined('_DB_NAME_')) {
    require_once __DIR__ . '/../../../config/config.php';
}

#[Group('metodospago')]
#[Group('interfaz')]
final class MetodosPagoCest
{
    private const RUTA_MODULO           = '/MetodosPago';
    private const TITULO_MODULO         = 'Metodos de Pago';

    // Selectores específicos de Métodos de Pago
    private const SELECT_NEC_REFERENCIA = '#nec_referencia';
    private const BTN_BLOQUEAR          = '#resultadoconsulta #cbt_t';

    // Datos base de prueba
    private string $nombreBase          = 'Zelle Test';
    private string $refBase             = '1'; // 1: Sí requiere referencia

    private string $nombreMod           = 'Zelle Modificado';
    private string $refMod              = '2'; // 2: No requiere referencia

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

        // El modal existe en el DOM pero debe arrancar oculto
        $I->seeElementInDOM(AcceptanceTester::MODAL);
        $I->dontSeeElement(AcceptanceTester::MODAL);

        $I->verificarSinErrores();
    }

    /**
     * Prueba 2: Validaciones del lado del cliente antes de registrar.
     * Analiza las respuestas de validarEnvio() en metodospago.js y main.js.
     */
    #[Group('validacion')]
    public function probarValidacionesDeFormulario(AcceptanceTester $I): void
    {
        // 1. Abrir modal
        $I->click(AcceptanceTester::BTN_NUEVO);
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->see('Registrar Nuevo Método de Pago', AcceptanceTester::MODAL_TITULO);

        // 2. Intentar registrar con campos vacíos (falla nombre)
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('Debe ingresar un nombre de método válido', 5);
        $I->aceptarSweetAlert();

        // 3. Nombre demasiado corto (mínimo 2 caracteres)
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, 'A');
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('Debe ingresar un nombre de método válido', 5);
        $I->aceptarSweetAlert();

        // 4. Cerrar modal limpiamente
        $I->click(AcceptanceTester::MODAL_CERRAR);
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        $I->verificarSinErrores();
    }

    /**
     * Prueba 3: Registrar un nuevo método de pago con confirmación de SweetAlert2.
     */
    #[Group('registrar', 'crud')]
    public function probarRegistrarMetodoPago(AcceptanceTester $I): void
    {
        $this->limpiarRegistroSiExiste($this->nombreBase);

        // 1. Abrir modal de inclusión
        $I->click(AcceptanceTester::BTN_NUEVO);
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->see('Registrar Nuevo Método de Pago', AcceptanceTester::MODAL_TITULO);

        // 2. Rellenar formulario
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, $this->nombreBase);
        $I->selectOption(self::SELECT_NEC_REFERENCIA, $this->refBase);

        // 3. Enviar y confirmar en SweetAlert
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere registrar este método de pago?', 5);
        $I->confirmarSweetAlert();

        // 4. Esperar alerta de éxito ("Registro Exitoso") y cierre automático de modal
        $I->waitForText('Registro Exitoso', 5);
        $I->aceptarSweetAlert();
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        // 5. Verificar en la lista mediante el buscador
        $I->buscarEnListado($this->nombreBase);
        $I->waitForText($this->nombreBase, 5, AcceptanceTester::CONT_RESULTADOS);
        $I->see('Sí', AcceptanceTester::CONT_RESULTADOS);

        $I->verificarSinErrores();
    }

    /**
     * Prueba 4: Uso del buscador en tiempo real (coincidencia, sin resultados y restauración).
     */
    #[Group('buscador')]
    public function probarBuscadorMetodosPago(AcceptanceTester $I): void
    {
        $this->asegurarRegistroExiste($this->nombreBase, $this->refBase);

        // 1. Búsqueda por término existente
        $I->buscarEnListado($this->nombreBase);
        $I->waitForText($this->nombreBase, 5, AcceptanceTester::CONT_RESULTADOS);

        // 2. Búsqueda por término inexistente
        $I->buscarEnListado('MetodoInexistenteXYZ999');
        $I->waitForText('No se encontraron registros', 5, AcceptanceTester::CONT_RESULTADOS);

        // 3. Limpiar buscador para restaurar el listado completo
        $I->limpiarBuscador();
        $I->dontSee('No se encontraron registros', AcceptanceTester::CONT_RESULTADOS);

        $I->verificarSinErrores();
    }

    /**
     * Prueba 5: Modificar un método de pago existente.
     */
    #[Group('modificar', 'crud')]
    public function probarModificarMetodoPago(AcceptanceTester $I): void
    {
        $this->limpiarRegistroSiExiste($this->nombreMod);
        $this->asegurarRegistroExiste($this->nombreBase, $this->refBase);

        // 1. Ubicar el método con el buscador
        $I->buscarEnListado($this->nombreBase);
        $I->waitForText($this->nombreBase, 5, AcceptanceTester::CONT_RESULTADOS);

        // 2. Clic seguro en modificar
        $I->clickModificar();

        // 3. Esperar que el modal abra con datos precargados
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->waitForText('Modificar Método de Pago', 5, AcceptanceTester::MODAL_TITULO);
        $I->seeInField(AcceptanceTester::INPUT_NOMBRE, $this->nombreBase);

        // 4. Modificar valores
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, $this->nombreMod);
        $I->selectOption(self::SELECT_NEC_REFERENCIA, $this->refMod);

        // 5. Guardar y confirmar
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere modificar este método de pago?', 5);
        $I->confirmarSweetAlert();

        // 6. Esperar alerta de éxito ("Modificación Exitosa") y cierre automático de modal
        $I->waitForText('Modificación Exitosa', 5);
        $I->aceptarSweetAlert();
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        // 7. Validar el cambio en el listado
        $I->buscarEnListado($this->nombreMod);
        $I->waitForText($this->nombreMod, 5, AcceptanceTester::CONT_RESULTADOS);
        $I->see('No', AcceptanceTester::CONT_RESULTADOS);

        $I->verificarSinErrores();
    }

    /**
     * Prueba 6: Bloquear y desbloquear un método de pago.
     */
    #[Group('bloquear')]
    public function probarBloquearMetodoPago(AcceptanceTester $I): void
    {
        $nombreBloqueo = 'Crypto Bloqueo';
        $this->limpiarRegistroSiExiste($nombreBloqueo);
        $this->asegurarRegistroExiste($nombreBloqueo, '1');

        // 1. Ubicar el método con el buscador
        $I->buscarEnListado($nombreBloqueo);
        $I->waitForText($nombreBloqueo, 5, AcceptanceTester::CONT_RESULTADOS);

        // 2. Clic en bloquear
        $I->waitForElementVisible(self::BTN_BLOQUEAR, 5);
        $I->despejarOverlays();
        $I->executeJS("document.querySelector('" . self::BTN_BLOQUEAR . "').click();");

        // 3. Confirmar bloqueo en SweetAlert
        $I->waitForText('¿Está seguro que quiere bloquear este Metodo?', 5);
        $I->confirmarSweetAlert();

        // 4. Esperar alerta de éxito ("Bloqueo Existoso")
        $I->waitForText('Bloqueo Existoso', 5);
        $I->aceptarSweetAlert();

        // 5. Re-ubicar y desbloquear
        $I->buscarEnListado($nombreBloqueo);
        $I->waitForText($nombreBloqueo, 5, AcceptanceTester::CONT_RESULTADOS);
        $I->waitForElementVisible(self::BTN_BLOQUEAR, 5);
        $I->despejarOverlays();
        $I->executeJS("document.querySelector('" . self::BTN_BLOQUEAR . "').click();");

        // 6. Confirmar desbloqueo
        $I->waitForText('¿Está seguro que quiere desbloquear este Metodo?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Bloqueo Existoso', 5);
        $I->aceptarSweetAlert();

        // Limpieza final
        $this->limpiarRegistroSiExiste($nombreBloqueo);
        $I->verificarSinErrores();
    }

    /**
     * Prueba 7: Eliminar un método de pago existente de manera autónoma.
     */
    #[Group('eliminar', 'crud')]
    public function probarEliminarMetodoPago(AcceptanceTester $I): void
    {
        $nombreAEliminar = 'Cheque Temporal';
        $this->limpiarRegistroSiExiste($nombreAEliminar);
        $this->asegurarRegistroExiste($nombreAEliminar, '1');

        // 1. Localizar el método con el buscador
        $I->buscarEnListado($nombreAEliminar);
        $I->waitForText($nombreAEliminar, 5, AcceptanceTester::CONT_RESULTADOS);

        // 2. Clic seguro en eliminar
        $I->clickEliminar();

        // 3. Confirmar en SweetAlert
        $I->waitForText('¿Está seguro que quiere eliminar este método de pago?', 5);
        $I->confirmarSweetAlert();

        // 4. Esperar éxito ("Eliminación Exitosa")
        $I->waitForText('Eliminación Exitosa', 5);
        $I->aceptarSweetAlert();

        // 5. Verificar que ya no existe
        $I->buscarEnListado($nombreAEliminar);
        $I->waitForText('No se encontraron registros', 5, AcceptanceTester::CONT_RESULTADOS);
        $I->dontSee($nombreAEliminar, AcceptanceTester::CONT_RESULTADOS);

        $I->verificarSinErrores();
    }

    /**
     * Prueba 8: Ciclo de vida completo continuo (Registrar -> Buscar -> Modificar -> Eliminar).
     */
    #[Group('cicloCompleto', 'e2e')]
    public function probarCicloCompletoMetodoPago(AcceptanceTester $I): void
    {
        $nombreCiclo    = 'Binance Pay';
        $nombreCicloMod = 'Binance Pay Mod';

        $this->limpiarRegistroSiExiste($nombreCiclo);
        $this->limpiarRegistroSiExiste($nombreCicloMod);

        // --- PASO 1: REGISTRAR ---
        $I->click(AcceptanceTester::BTN_NUEVO);
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, $nombreCiclo);
        $I->selectOption(self::SELECT_NEC_REFERENCIA, '1');
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere registrar este método de pago?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Registro Exitoso', 5);
        $I->aceptarSweetAlert();
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        // --- PASO 2: BUSCAR ---
        $I->buscarEnListado($nombreCiclo);
        $I->waitForText($nombreCiclo, 5, AcceptanceTester::CONT_RESULTADOS);

        // --- PASO 3: MODIFICAR ---
        $I->clickModificar();
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->waitForText('Modificar Método de Pago', 5, AcceptanceTester::MODAL_TITULO);
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, $nombreCicloMod);
        $I->selectOption(self::SELECT_NEC_REFERENCIA, '2');
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere modificar este método de pago?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Modificación Exitosa', 5);
        $I->aceptarSweetAlert();
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        // --- PASO 4: BUSCAR MODIFICADO ---
        $I->buscarEnListado($nombreCicloMod);
        $I->waitForText($nombreCicloMod, 5, AcceptanceTester::CONT_RESULTADOS);
        $I->see('No', AcceptanceTester::CONT_RESULTADOS);

        // --- PASO 5: ELIMINAR ---
        $I->clickEliminar();
        $I->waitForText('¿Está seguro que quiere eliminar este método de pago?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Eliminación Exitosa', 5);
        $I->aceptarSweetAlert();

        // --- PASO 6: VALIDAR INEXISTENCIA ---
        $I->buscarEnListado($nombreCicloMod);
        $I->waitForText('No se encontraron registros', 5, AcceptanceTester::CONT_RESULTADOS);

        $I->verificarSinErrores();
    }

    // =========================================================================
    // HELPERS DE BASE DE DATOS
    // =========================================================================

    private function limpiarRegistroSiExiste(string $nombre): void
    {
        try {
            $modelo = new \App\modelo\ModeloMetodosPago();
            $res = $modelo->Consultar(['filtro' => $nombre]);
            if (!empty($res['datos'])) {
                foreach ($res['datos'] as $d) {
                    if (strcasecmp($d['nombre'], $nombre) === 0) {
                        $modelo->ProcesarDatos([
                            'accion' => 'eliminar',
                            'id'     => (string)$d['codigo_metodo']
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Silencioso para no detener la prueba si no hay conexión
        }
    }

    private function asegurarRegistroExiste(string $nombre, string $necReferencia = '1'): void
    {
        try {
            $modelo = new \App\modelo\ModeloMetodosPago();
            $res = $modelo->Consultar(['filtro' => $nombre]);
            $encontrado = false;
            if (!empty($res['datos'])) {
                foreach ($res['datos'] as $d) {
                    if (strcasecmp($d['nombre'], $nombre) === 0) {
                        $encontrado = true;
                        break;
                    }
                }
            }
            if (!$encontrado) {
                $modelo->ProcesarDatos([
                    'accion'         => 'incluir',
                    'nombre'         => $nombre,
                    'nec_referencia' => $necReferencia
                ]);
            }
        } catch (\Throwable $e) {
            // Silencioso para no interferir con la ejecución UI
        }
    }
}

