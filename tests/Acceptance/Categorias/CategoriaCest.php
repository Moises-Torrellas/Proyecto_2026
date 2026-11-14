<?php

declare(strict_types=1);

namespace Tests\Acceptance\Categorias;

use Codeception\Attribute\Group;
use Tests\Support\AcceptanceTester;

if (!defined('_DB_NAME_')) {
    require_once __DIR__ . '/../../../config/config.php';
}

#[Group('categorias')]
#[Group('interfaz')]
final class CategoriaCest
{
    private const RUTA_MODULO       = '/Categorias';
    private const TITULO_MODULO     = 'Categorías';

    // Inputs específicos de Categorías (adicionales a los comunes en AcceptanceTester)
    private const INPUT_EDAD_MIN    = '#edad_min';
    private const INPUT_EDAD_MAX    = '#edad_max';

    // Datos de prueba (edades que no colisionan con las categorías preexistentes 3-50 años)
    private string $nombreBase      = 'U-65';
    private string $edadMinBase     = '61';
    private string $edadMaxBase     = '65';

    private string $nombreMod       = 'U-70';
    private string $edadMinMod      = '61';
    private string $edadMaxMod      = '70';

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
     * Analiza las respuestas de validarEnvio() en Categorias.js y main.js.
     */
    #[Group('validacion')]
    public function probarValidacionesDeFormulario(AcceptanceTester $I): void
    {
        // 1. Abrir modal
        $I->click(AcceptanceTester::BTN_NUEVO);
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->see('Registrar Nueva Categoría', AcceptanceTester::MODAL_TITULO);

        // 2. Intentar registrar con campos vacíos (falla nombre)
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('Debe ingresar un nombre de categoría válido', 5);
        $I->aceptarSweetAlert();

        // 3. Llenar solo nombre y dejar edad mínima vacía
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, 'U-65');
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('Debe ingresar una edad mínima válida', 5);
        $I->aceptarSweetAlert();

        // 4. Llenar edad mínima y dejar edad máxima vacía
        $I->fillField(self::INPUT_EDAD_MIN, '61');
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('Debe ingresar una edad máxima válida', 5);
        $I->aceptarSweetAlert();

        // 5. Validación lógica: Edad mínima mayor a la máxima
        $I->fillField(self::INPUT_EDAD_MIN, '70');
        $I->fillField(self::INPUT_EDAD_MAX, '60');
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('La edad mínima no puede ser mayor a la edad máxima', 5);
        $I->aceptarSweetAlert();

        // 6. Cerrar modal limpiamente
        $I->click(AcceptanceTester::MODAL_CERRAR);
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        $I->verificarSinErrores();
    }

    /**
     * Prueba 3: Registrar una nueva categoría con confirmación de SweetAlert2.
     */
    #[Group('registrar', 'crud')]
    public function probarRegistrarCategoria(AcceptanceTester $I): void
    {
        $this->limpiarRegistroSiExiste($this->nombreBase);

        // 1. Abrir modal de inclusión
        $I->click(AcceptanceTester::BTN_NUEVO);
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->see('Registrar Nueva Categoría', AcceptanceTester::MODAL_TITULO);

        // 2. Rellenar formulario
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, $this->nombreBase);
        $I->fillField(self::INPUT_EDAD_MIN, $this->edadMinBase);
        $I->fillField(self::INPUT_EDAD_MAX, $this->edadMaxBase);

        // 3. Enviar y confirmar en SweetAlert
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere registrar esta categoría?', 5);
        $I->confirmarSweetAlert();

        // 4. Esperar alerta de éxito ("Registro Exitoso")
        $I->waitForText('Registro Exitoso', 5);
        $I->aceptarSweetAlert();

        // 5. Cerrar modal y verificar en la lista con el buscador
        $I->click(AcceptanceTester::MODAL_CERRAR);
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        $I->buscarEnListado($this->nombreBase);
        $I->waitForText($this->nombreBase, 5, AcceptanceTester::CONT_RESULTADOS);
        $I->see($this->edadMinBase, AcceptanceTester::CONT_RESULTADOS);
        $I->see($this->edadMaxBase, AcceptanceTester::CONT_RESULTADOS);

        $I->verificarSinErrores();
    }

    /**
     * Prueba 4: Uso del buscador en tiempo real (coincidencia, sin resultados y restauración).
     */
    #[Group('buscador')]
    public function probarBuscadorCategorias(AcceptanceTester $I): void
    {
        $this->asegurarRegistroExiste($this->nombreBase, $this->edadMinBase, $this->edadMaxBase);

        // 1. Búsqueda por término existente
        $I->buscarEnListado($this->nombreBase);
        $I->waitForText($this->nombreBase, 5, AcceptanceTester::CONT_RESULTADOS);

        // 2. Búsqueda por término inexistente
        $I->buscarEnListado('CategoriaInexistenteXYZ999');
        $I->waitForText('No se encontraron registros', 5, AcceptanceTester::CONT_RESULTADOS);

        // 3. Limpiar buscador para restaurar el listado completo
        $I->limpiarBuscador();
        $I->dontSee('No se encontraron registros', AcceptanceTester::CONT_RESULTADOS);

        $I->verificarSinErrores();
    }

    /**
     * Prueba 5: Modificar una categoría existente.
     */
    #[Group('modificar', 'crud')]
    public function probarModificarCategoria(AcceptanceTester $I): void
    {
        $this->limpiarRegistroSiExiste($this->nombreMod);
        $this->asegurarRegistroExiste($this->nombreBase, $this->edadMinBase, $this->edadMaxBase);

        // 1. Ubicar la categoría con el buscador
        $I->buscarEnListado($this->nombreBase);
        $I->waitForText($this->nombreBase, 5, AcceptanceTester::CONT_RESULTADOS);

        // 2. Clic seguro en modificar
        $I->clickModificar();

        // 3. Esperar que el modal abra con datos precargados
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->waitForText('Modificar Categoría', 5, AcceptanceTester::MODAL_TITULO);
        $I->seeInField(AcceptanceTester::INPUT_NOMBRE, $this->nombreBase);

        // 4. Modificar valores
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, $this->nombreMod);
        $I->fillField(self::INPUT_EDAD_MIN, $this->edadMinMod);
        $I->fillField(self::INPUT_EDAD_MAX, $this->edadMaxMod);

        // 5. Guardar y confirmar
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere modificar esta categoría?', 5);
        $I->confirmarSweetAlert();

        // 6. Esperar alerta de éxito (modificar() cierra el modal automáticamente)
        $I->waitForText('Modificación Exitosa', 5);
        $I->aceptarSweetAlert();
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        // 7. Validar el cambio en el listado
        $I->buscarEnListado($this->nombreMod);
        $I->waitForText($this->nombreMod, 5, AcceptanceTester::CONT_RESULTADOS);
        $I->see($this->edadMaxMod, AcceptanceTester::CONT_RESULTADOS);

        $I->verificarSinErrores();
    }

    /**
     * Prueba 6: Eliminar una categoría existente de manera autónoma.
     */
    #[Group('eliminar', 'crud')]
    public function probarEliminarCategoria(AcceptanceTester $I): void
    {
        $nombreAEliminar  = 'U-75';
        $edadMinEliminar  = '71';
        $edadMaxEliminar  = '75';

        $this->asegurarRegistroExiste($nombreAEliminar, $edadMinEliminar, $edadMaxEliminar);

        // 1. Localizar la categoría con el buscador
        $I->buscarEnListado($nombreAEliminar);
        $I->waitForText($nombreAEliminar, 5, AcceptanceTester::CONT_RESULTADOS);

        // 2. Clic seguro en eliminar
        $I->clickEliminar();

        // 3. Confirmar en SweetAlert
        $I->waitForText('¿Está seguro que quiere eliminar esta categoría?', 5);
        $I->confirmarSweetAlert();

        // 4. Esperar éxito
        $I->waitForText('Eliminación Exitosa', 5);
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
    public function probarCicloCompletoCategoria(AcceptanceTester $I): void
    {
        $nombreCiclo     = 'U-80';
        $edadMinCiclo    = '76';
        $edadMaxCiclo    = '80';

        $nombreCicloMod  = 'U-85';
        $edadMinCicloMod = '76';
        $edadMaxCicloMod = '85';

        $this->limpiarRegistroSiExiste($nombreCiclo);
        $this->limpiarRegistroSiExiste($nombreCicloMod);

        // --- PASO 1: REGISTRAR ---
        $I->click(AcceptanceTester::BTN_NUEVO);
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, $nombreCiclo);
        $I->fillField(self::INPUT_EDAD_MIN, $edadMinCiclo);
        $I->fillField(self::INPUT_EDAD_MAX, $edadMaxCiclo);
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere registrar esta categoría?', 5);
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
        $I->waitForText('Modificar Categoría', 5, AcceptanceTester::MODAL_TITULO);
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, $nombreCicloMod);
        $I->fillField(self::INPUT_EDAD_MIN, $edadMinCicloMod);
        $I->fillField(self::INPUT_EDAD_MAX, $edadMaxCicloMod);
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere modificar esta categoría?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Modificación Exitosa', 5);
        $I->aceptarSweetAlert();
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        // --- PASO 4: BUSCAR MODIFICADO ---
        $I->buscarEnListado($nombreCicloMod);
        $I->waitForText($nombreCicloMod, 5, AcceptanceTester::CONT_RESULTADOS);

        // --- PASO 5: ELIMINAR ---
        $I->clickEliminar();
        $I->waitForText('¿Está seguro que quiere eliminar esta categoría?', 5);
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
            $modelo = new \App\modelo\ModeloCategorias();
            $res = $modelo->Consultar(['filtro' => $nombre]);
            if (!empty($res['datos'])) {
                foreach ($res['datos'] as $d) {
                    if (strcasecmp($d['nombre'], $nombre) === 0) {
                        $modelo->ProcesarDatos([
                            'accion' => 'eliminar',
                            'id' => (string)$d['codigo_categoria']
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Silencioso para no detener la prueba si no hay conexión
        }
    }

    private function asegurarRegistroExiste(string $nombre, string $edadMin, string $edadMax): void
    {
        try {
            $modelo = new \App\modelo\ModeloCategorias();
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
                    'accion'   => 'incluir',
                    'nombre'   => $nombre,
                    'edad_min' => $edadMin,
                    'edad_max' => $edadMax
                ]);
            }
        } catch (\Throwable $e) {
            // Silencioso para no interferir con la ejecución UI
        }
    }
}
