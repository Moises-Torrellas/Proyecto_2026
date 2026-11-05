<?php

declare(strict_types=1);

namespace Tests\Acceptance\Posiciones;

use Codeception\Attribute\Group;
use Tests\Support\AcceptanceTester;

if (!defined('_DB_NAME_')) {
    require_once __DIR__ . '/../../../config/config.php';
}

#[Group('posiciones')]
#[Group('interfaz')]
final class PosicionesCest
{
    private const RUTA_MODULO       = '/Posiciones';
    private const TITULO_MODULO     = 'Posiciones';

    // Campo específico de este módulo (no existe en todos los CRUDs)
    private const INPUT_NOMBRE = '#nombre';
    private const INPUT_DESCRIPCION = '#descripcion';
    private const INPUT_ABREVIATURA = '#abreviatura';

    // Datos base para las pruebas
    private string $nombreBase = 'Base Defensiva';
    private string $abrevBase  = 'BD';
    private string $descBase   = 'Posicion defensiva en el terreno';

    private string $nombreMod  = 'Base Ofensiva';
    private string $abrevMod   = 'BO';
    private string $descMod    = 'Posicion actualizada al ataque';

    public function _before(AcceptanceTester $I): void
    {
        // Login con snapshot + navegación + limpieza de alertas flotantes (1 sola línea)
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
     */
    #[Group('validacion')]
    public function probarValidacionesDeFormulario(AcceptanceTester $I): void
    {
        // 1. Abrir modal
        $I->click(AcceptanceTester::BTN_NUEVO);
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->see('Registrar Posición', AcceptanceTester::MODAL_TITULO);

        // 2. Intentar registrar con campos vacíos
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('Tiene que ingresar un nombre valido', 5);
        $I->aceptarSweetAlert();

        // 3. Llenar solo el nombre y dejar la abreviatura vacía
        $I->fillField(self::INPUT_NOMBRE, 'Escolta Lateral');
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('Tiene que ingresar una abreviatura valido', 5);
        $I->aceptarSweetAlert();

        // 4. Cerrar el modal limpiamente
        $I->click(AcceptanceTester::MODAL_CERRAR);
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        $I->verificarSinErrores();
    }

    /**
     * Prueba 3: Registrar una nueva posición con confirmación de SweetAlert2.
     */
    #[Group('registrar', 'crud')]
    public function probarRegistrarPosicion(AcceptanceTester $I): void
    {
        $this->limpiarRegistroSiExiste($this->nombreBase);

        // 1. Abrir modal de inclusión
        $I->click(AcceptanceTester::BTN_NUEVO);
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->see('Registrar Posición', AcceptanceTester::MODAL_TITULO);

        // 2. Rellenar formulario
        $I->fillField(self::INPUT_NOMBRE, $this->nombreBase);
        $I->fillField(self::INPUT_ABREVIATURA, $this->abrevBase);
        $I->fillField(self::INPUT_DESCRIPCION, $this->descBase);

        // 3. Enviar y confirmar en SweetAlert
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere registrar esta posición?', 5);
        $I->confirmarSweetAlert();

        // 4. Esperar confirmación de éxito ("Registro Exitoso")
        $I->waitForText('Registro Exitoso', 5);
        $I->aceptarSweetAlert();

        // 5. Cerrar modal y verificar en la lista con el buscador
        $I->click(AcceptanceTester::MODAL_CERRAR);
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        $I->buscarEnListado($this->nombreBase);
        $I->waitForText($this->nombreBase, 5, AcceptanceTester::CONT_RESULTADOS);
        $I->see($this->abrevBase, AcceptanceTester::CONT_RESULTADOS);

        $I->verificarSinErrores();
    }

    /**
     * Prueba 4: Uso del buscador en tiempo real (coincidencia, sin resultados y restauración).
     */
    #[Group('buscador')]
    public function probarBuscadorPosiciones(AcceptanceTester $I): void
    {
        $this->asegurarRegistroExiste($this->nombreBase, $this->abrevBase, $this->descBase);

        // 1. Búsqueda por término existente
        $I->buscarEnListado($this->nombreBase);
        $I->waitForText($this->nombreBase, 5, AcceptanceTester::CONT_RESULTADOS);

        // 2. Búsqueda por término inexistente
        $I->buscarEnListado('PosicionInexistenteXYZ999');
        $I->waitForText('No se encontraron registros', 5, AcceptanceTester::CONT_RESULTADOS);

        // 3. Limpiar buscador para restaurar el listado
        $I->limpiarBuscador();
        $I->dontSee('No se encontraron registros', AcceptanceTester::CONT_RESULTADOS);

        $I->verificarSinErrores();
    }

    /**
     * Prueba 5: Modificar una posición existente.
     */
    #[Group('modificar', 'crud')]
    public function probarModificarPosicion(AcceptanceTester $I): void
    {
        $this->asegurarRegistroExiste($this->nombreBase, $this->abrevBase, $this->descBase);
        $this->limpiarRegistroSiExiste($this->nombreMod);

        // 1. Ubicar la posición con el buscador
        $I->buscarEnListado($this->nombreBase);
        $I->waitForText($this->nombreBase, 5, AcceptanceTester::CONT_RESULTADOS);

        // 2. Clic seguro en modificar
        $I->clickModificar();

        // 3. Esperar que el modal abra con datos precargados
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->waitForText('Modificar Posición', 5, AcceptanceTester::MODAL_TITULO);
        $I->seeInField(self::INPUT_NOMBRE, $this->nombreBase);

        // 4. Modificar valores
        $I->fillField(self::INPUT_NOMBRE, $this->nombreMod);
        $I->fillField(self::INPUT_ABREVIATURA, $this->abrevMod);
        $I->fillField(self::INPUT_DESCRIPCION, $this->descMod);

        // 5. Guardar y confirmar
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere modificar esta posición?', 5);
        $I->confirmarSweetAlert();

        // 6. Esperar alerta de éxito (modificar() cierra el modal automáticamente)
        $I->waitForText('Modificacion Exitosa', 5);
        $I->aceptarSweetAlert();
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        // 7. Validar el cambio en el listado
        $I->buscarEnListado($this->nombreMod);
        $I->waitForText($this->nombreMod, 5, AcceptanceTester::CONT_RESULTADOS);
        $I->see($this->abrevMod, AcceptanceTester::CONT_RESULTADOS);

        $I->verificarSinErrores();
    }

    /**
     * Prueba 6: Eliminar una posición existente de manera autónoma.
     */
    #[Group('eliminar', 'crud')]
    public function probarEliminarPosicion(AcceptanceTester $I): void
    {
        $nombreAEliminar = 'Base Para Eliminar';
        $abrevAEliminar  = 'BPEL';
        $descAEliminar   = 'Posicion descartable para prueba de eliminacion';

        $this->asegurarRegistroExiste($nombreAEliminar, $abrevAEliminar, $descAEliminar);

        // 1. Localizar la posición con el buscador
        $I->buscarEnListado($nombreAEliminar);
        $I->waitForText($nombreAEliminar, 5, AcceptanceTester::CONT_RESULTADOS);

        // 2. Clic seguro en eliminar
        $I->clickEliminar();

        // 3. Confirmar en SweetAlert
        $I->waitForText('¿Está seguro que quiere eliminar esta posición?', 5);
        $I->confirmarSweetAlert();

        // 4. Esperar éxito
        $I->waitForText('Eliminacion Exitosa', 5);
        $I->aceptarSweetAlert();

        // 5. Verificar que ya no existe
        $I->buscarEnListado($nombreAEliminar);
        $I->waitForText('No se encontraron registros', 5, AcceptanceTester::CONT_RESULTADOS);
        $I->dontSee($nombreAEliminar, AcceptanceTester::CONT_RESULTADOS);

        $I->verificarSinErrores();
    }

    /**
     * Prueba 7: Ciclo de vida completo continuo (Registrar -> Buscar -> Modificar -> Eliminar).
     */
    #[Group('cicloCompleto', 'e2e')]
    public function probarCicloCompletoPosicion(AcceptanceTester $I): void
    {
        $nombreCiclo = 'Alero Transicion';
        $abrevCiclo  = 'AT';
        $descCiclo   = 'Posicion de transicion rapida';

        $nombreCicloMod = 'Alero Finalizador';
        $abrevCicloMod  = 'AF';
        $descCicloMod   = 'Posicion especializada en definicion';

        $this->limpiarRegistroSiExiste($nombreCiclo);
        $this->limpiarRegistroSiExiste($nombreCicloMod);

        // --- PASO 1: REGISTRAR ---
        $I->click(AcceptanceTester::BTN_NUEVO);
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->fillField(self::INPUT_NOMBRE, $nombreCiclo);
        $I->fillField(self::INPUT_ABREVIATURA, $abrevCiclo);
        $I->fillField(self::INPUT_DESCRIPCION, $descCiclo);
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere registrar esta posición?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Registro Exitoso', 5);
        $I->aceptarSweetAlert();
        $I->click(AcceptanceTester::MODAL_CERRAR);
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        // --- PASO 2: BUSCAR ---
        $I->buscarEnListado($nombreCiclo);
        $I->waitForText($nombreCiclo, 5, AcceptanceTester::CONT_RESULTADOS);

        // --- PASO 3: MODIFICAR ---
        $I->clickModificar();
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->waitForText('Modificar Posición', 5, AcceptanceTester::MODAL_TITULO);
        $I->fillField(self::INPUT_NOMBRE, $nombreCicloMod);
        $I->fillField(self::INPUT_ABREVIATURA, $abrevCicloMod);
        $I->fillField(self::INPUT_DESCRIPCION, $descCicloMod);
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere modificar esta posición?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Modificacion Exitosa', 5);
        $I->aceptarSweetAlert();
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        // --- PASO 4: BUSCAR MODIFICADO ---
        $I->buscarEnListado($nombreCicloMod);
        $I->waitForText($nombreCicloMod, 5, AcceptanceTester::CONT_RESULTADOS);

        // --- PASO 5: ELIMINAR ---
        $I->clickEliminar();
        $I->waitForText('¿Está seguro que quiere eliminar esta posición?', 5);
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
            $modelo = new \App\modelo\ModeloPosiciones();
            $res = $modelo->Consultar(['filtro' => $nombre]);
            if (!empty($res['datos'])) {
                foreach ($res['datos'] as $d) {
                    if (strcasecmp($d['nombre'], $nombre) === 0) {
                        $modelo->ProcesarDatos([
                            'accion' => 'eliminar',
                            'id' => (string)$d['codigo_posicion']
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Silencioso para no detener la prueba si no hay conexión
        }
    }

    private function asegurarRegistroExiste(string $nombre, string $abreviatura, string $descripcion): void
    {
        try {
            $modelo = new \App\modelo\ModeloPosiciones();
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
                    'accion'      => 'incluir',
                    'nombre'      => $nombre,
                    'abreviatura' => $abreviatura,
                    'descripcion' => $descripcion
                ]);
            }
        } catch (\Throwable $e) {
            // Silencioso para no interferir con la ejecución UI
        }
    }
}
