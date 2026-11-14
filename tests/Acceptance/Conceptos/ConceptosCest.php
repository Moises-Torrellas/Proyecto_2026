<?php

declare(strict_types=1);

namespace Tests\Acceptance\Conceptos;

use Codeception\Attribute\Group;
use Tests\Support\AcceptanceTester;

if (!defined('_DB_NAME_')) {
    require_once __DIR__ . '/../../../config/config.php';
}

#[Group('conceptos')]
#[Group('interfaz')]
final class ConceptosCest
{
    private const RUTA_MODULO           = '/Conceptos';
    private const TITULO_MODULO         = 'Conceptos de Cargos';

    // Selectores específicos de Conceptos de Cargos
    private const INPUT_MONTO           = '#monto';
    private const SELECT_FRECUENCIA     = '#frecuencia';
    private const INPUT_DIAS            = '#dias';
    private const BTN_ESTATUS           = '#resultadoconsulta #cbt_t';

    // Datos base de prueba
    private string $nombreBase          = 'Matricula Especial';
    private string $montoBase           = '50.00';
    private string $montoInputBase      = '5000';
    private string $frecuenciaBase      = 'U'; // Unico
    private string $diasBase            = '15';

    private string $nombreMod           = 'Matricula Actualizada';
    private string $montoMod            = '75.00';
    private string $montoInputMod       = '7500';
    private string $frecuenciaMod       = 'M'; // Mensual
    private string $diasMod             = '20';

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
     * Analiza las respuestas de validarEnvio() en Concepto.js y main.js.
     */
    #[Group('validacion')]
    public function probarValidacionesDeFormulario(AcceptanceTester $I): void
    {
        // 1. Abrir modal
        $I->click(AcceptanceTester::BTN_NUEVO);
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->see('Registrar Concepto de cargo', AcceptanceTester::MODAL_TITULO);

        // 2. Intentar registrar con campos vacíos (falla nombre)
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('Tiene que ingresar un nombre valido', 5);
        $I->aceptarSweetAlert();

        // 3. Nombre demasiado corto (mínimo 3 caracteres alfabéticos)
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, 'AB');
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('Tiene que ingresar un nombre valido', 5);
        $I->aceptarSweetAlert();

        // 4. Cerrar modal limpiamente
        $I->click(AcceptanceTester::MODAL_CERRAR);
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        $I->verificarSinErrores();
    }

    /**
     * Prueba 3: Registrar un nuevo concepto de cargo con confirmación de SweetAlert2.
     */
    #[Group('registrar', 'crud')]
    public function probarRegistrarConcepto(AcceptanceTester $I): void
    {
        $this->limpiarRegistroSiExiste($this->nombreBase);

        // 1. Abrir modal de inclusión
        $I->click(AcceptanceTester::BTN_NUEVO);
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->see('Registrar Concepto de cargo', AcceptanceTester::MODAL_TITULO);

        // 2. Rellenar formulario
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, $this->nombreBase);
        $I->fillField(self::INPUT_MONTO, $this->montoInputBase);
        $I->selectOption(self::SELECT_FRECUENCIA, $this->frecuenciaBase);
        $I->fillField(self::INPUT_DIAS, $this->diasBase);

        // 3. Enviar y confirmar en SweetAlert
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Esta seguro que quiere registrar el Concepto?', 5);
        $I->confirmarSweetAlert();

        // 4. Esperar alerta de éxito ("Registro Exitoso") y cierre automático de modal
        $I->waitForText('Registro Exitoso', 5);
        $I->aceptarSweetAlert();
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        // 5. Verificar en la lista mediante el buscador
        $I->buscarEnListado($this->nombreBase);
        $I->waitForText($this->nombreBase, 5, AcceptanceTester::CONT_RESULTADOS);
        $I->see('50', AcceptanceTester::CONT_RESULTADOS);
        $I->see('Unico', AcceptanceTester::CONT_RESULTADOS);
        $I->see($this->diasBase, AcceptanceTester::CONT_RESULTADOS);

        $I->verificarSinErrores();
    }

    /**
     * Prueba 4: Uso del buscador en tiempo real (coincidencia, sin resultados y restauración).
     */
    #[Group('buscador')]
    public function probarBuscadorConceptos(AcceptanceTester $I): void
    {
        $this->asegurarRegistroExiste(
            $this->nombreBase,
            $this->montoBase,
            $this->frecuenciaBase,
            $this->diasBase
        );

        // 1. Búsqueda por término existente
        $I->buscarEnListado($this->nombreBase);
        $I->waitForText($this->nombreBase, 5, AcceptanceTester::CONT_RESULTADOS);

        // 2. Búsqueda por término inexistente
        $I->buscarEnListado('ConceptoInexistenteXYZ999');
        $I->waitForText('No se encontraron registros', 5, AcceptanceTester::CONT_RESULTADOS);

        // 3. Limpiar buscador para restaurar el listado completo
        $I->limpiarBuscador();
        $I->dontSee('No se encontraron registros', AcceptanceTester::CONT_RESULTADOS);

        $I->verificarSinErrores();
    }

    /**
     * Prueba 5: Modificar un concepto de cargo existente.
     */
    #[Group('modificar', 'crud')]
    public function probarModificarConcepto(AcceptanceTester $I): void
    {
        $this->limpiarRegistroSiExiste($this->nombreMod);
        $this->asegurarRegistroExiste(
            $this->nombreBase,
            $this->montoBase,
            $this->frecuenciaBase,
            $this->diasBase
        );

        // 1. Ubicar el concepto con el buscador
        $I->buscarEnListado($this->nombreBase);
        $I->waitForText($this->nombreBase, 5, AcceptanceTester::CONT_RESULTADOS);

        // 2. Clic seguro en modificar
        $I->clickModificar();

        // 3. Esperar que el modal abra con datos precargados
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->waitForText('Modificar concepto de cargo', 5, AcceptanceTester::MODAL_TITULO);
        $I->seeInField(AcceptanceTester::INPUT_NOMBRE, $this->nombreBase);

        // 4. Modificar valores
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, $this->nombreMod);
        $I->fillField(self::INPUT_MONTO, $this->montoInputMod);
        $I->selectOption(self::SELECT_FRECUENCIA, $this->frecuenciaMod);
        $I->fillField(self::INPUT_DIAS, $this->diasMod);

        // 5. Guardar y confirmar
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Esta seguro que quiere modificar el Concepto?', 5);
        $I->confirmarSweetAlert();

        // 6. Esperar alerta de éxito ("Modificacion Exitosa") y cierre automático de modal
        $I->waitForText('Modificacion Exitosa', 5);
        $I->aceptarSweetAlert();
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        // 7. Validar el cambio en el listado
        $I->buscarEnListado($this->nombreMod);
        $I->waitForText($this->nombreMod, 5, AcceptanceTester::CONT_RESULTADOS);
        $I->see('75', AcceptanceTester::CONT_RESULTADOS);
        $I->see('Mensual', AcceptanceTester::CONT_RESULTADOS);
        $I->see($this->diasMod, AcceptanceTester::CONT_RESULTADOS);

        $I->verificarSinErrores();
    }

    /**
     * Prueba 6: Desactivar y activar (bloquear) un concepto de cargo.
     */
    #[Group('estatus')]
    public function probarCambiarEstatusConcepto(AcceptanceTester $I): void
    {
        $nombreEstatus = 'Torneo Relampago';
        $this->limpiarRegistroSiExiste($nombreEstatus);
        $this->asegurarRegistroExiste($nombreEstatus, '10.00', 'L', '5');

        // 1. Ubicar el concepto con el buscador
        $I->buscarEnListado($nombreEstatus);
        $I->waitForText($nombreEstatus, 5, AcceptanceTester::CONT_RESULTADOS);

        // 2. Clic en el botón de cambiar estatus
        $I->waitForElementVisible(self::BTN_ESTATUS, 5);
        $I->despejarOverlays();
        $I->executeJS("document.querySelector('" . self::BTN_ESTATUS . "').click();");

        // 3. Confirmar desactivación en SweetAlert
        $I->waitForText('¿Está seguro que desea desactivar este concepto de cargo?', 5);
        $I->confirmarSweetAlert();

        // 4. Esperar alerta de éxito ("Actualización Exitosa")
        $I->waitForText('Actualización Exitosa', 5);
        $I->aceptarSweetAlert();

        // 5. Re-ubicar y volver a activar
        $I->buscarEnListado($nombreEstatus);
        $I->waitForText($nombreEstatus, 5, AcceptanceTester::CONT_RESULTADOS);
        $I->waitForElementVisible(self::BTN_ESTATUS, 5);
        $I->despejarOverlays();
        $I->executeJS("document.querySelector('" . self::BTN_ESTATUS . "').click();");

        // 6. Confirmar activación
        $I->waitForText('¿Está seguro que desea activar este concepto de cargo?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Actualización Exitosa', 5);
        $I->aceptarSweetAlert();

        // Limpieza final
        $this->limpiarRegistroSiExiste($nombreEstatus);
        $I->verificarSinErrores();
    }

    /**
     * Prueba 7: Eliminar un concepto de cargo existente de manera autónoma.
     */
    #[Group('eliminar', 'crud')]
    public function probarEliminarConcepto(AcceptanceTester $I): void
    {
        $nombreAEliminar = 'Uniforme Extra';
        $this->limpiarRegistroSiExiste($nombreAEliminar);
        $this->asegurarRegistroExiste($nombreAEliminar, '20.00', 'U', '0');

        // 1. Localizar el concepto con el buscador
        $I->buscarEnListado($nombreAEliminar);
        $I->waitForText($nombreAEliminar, 5, AcceptanceTester::CONT_RESULTADOS);

        // 2. Clic seguro en eliminar
        $I->clickEliminar();

        // 3. Confirmar en SweetAlert
        $I->waitForText('¿Está seguro que quiere eliminar este proceso de cargo?', 5);
        $I->confirmarSweetAlert();

        // 4. Esperar éxito ("Eliminacion Exitosa")
        $I->waitForText('Eliminacion Exitosa', 5);
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
    public function probarCicloCompletoConcepto(AcceptanceTester $I): void
    {
        $nombreCiclo    = 'Mantenimiento Sede';
        $nombreCicloMod = 'Mantenimiento General';

        $this->limpiarRegistroSiExiste($nombreCiclo);
        $this->limpiarRegistroSiExiste($nombreCicloMod);

        // --- PASO 1: REGISTRAR ---
        $I->click(AcceptanceTester::BTN_NUEVO);
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, $nombreCiclo);
        $I->fillField(self::INPUT_MONTO, '1200');
        $I->selectOption(self::SELECT_FRECUENCIA, 'A'); // Anual
        $I->fillField(self::INPUT_DIAS, '10');
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Esta seguro que quiere registrar el Concepto?', 5);
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
        $I->waitForText('Modificar concepto de cargo', 5, AcceptanceTester::MODAL_TITULO);
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, $nombreCicloMod);
        $I->fillField(self::INPUT_MONTO, '1800');
        $I->selectOption(self::SELECT_FRECUENCIA, 'M'); // Mensual
        $I->fillField(self::INPUT_DIAS, '15');
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Esta seguro que quiere modificar el Concepto?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Modificacion Exitosa', 5);
        $I->aceptarSweetAlert();
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        // --- PASO 4: BUSCAR MODIFICADO ---
        $I->buscarEnListado($nombreCicloMod);
        $I->waitForText($nombreCicloMod, 5, AcceptanceTester::CONT_RESULTADOS);
        $I->see('Mensual', AcceptanceTester::CONT_RESULTADOS);

        // --- PASO 5: ELIMINAR ---
        $I->clickEliminar();
        $I->waitForText('¿Está seguro que quiere eliminar este proceso de cargo?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Eliminacion Exitosa', 5);
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
            $modelo = new \App\modelo\ModeloConceptos();
            $res = $modelo->Consultar(['filtro' => $nombre]);
            if (!empty($res['datos'])) {
                foreach ($res['datos'] as $d) {
                    if (strcasecmp($d['nombre'], $nombre) === 0) {
                        $modelo->ProcesarDatos([
                            'accion' => 'eliminar',
                            'id'     => (string)$d['codigo_concepto']
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Silencioso para no detener la prueba si no hay conexión
        }
    }

    private function asegurarRegistroExiste(
        string $nombre,
        string $monto = '50.00',
        string $frecuencia = 'U',
        string $dias = '15'
    ): void {
        try {
            $modelo = new \App\modelo\ModeloConceptos();
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
                    'accion'     => 'incluir',
                    'nombre'     => $nombre,
                    'monto'      => $monto,
                    'frecuencia' => $frecuencia,
                    'dias'       => $dias
                ]);
            }
        } catch (\Throwable $e) {
            // Silencioso para no interferir con la ejecución UI
        }
    }
}

