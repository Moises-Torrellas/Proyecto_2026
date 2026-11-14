<?php

declare(strict_types=1);

namespace Tests\Acceptance\Representantes;

use Codeception\Attribute\Group;
use Tests\Support\AcceptanceTester;

if (!defined('_DB_NAME_')) {
    require_once __DIR__ . '/../../../config/config.php';
}

#[Group('representantes')]
#[Group('interfaz')]
final class RepresentantesCest
{
    private const RUTA_MODULO           = '/Representantes';
    private const TITULO_MODULO         = 'Representantes';

    // Selectores específicos de Representantes
    private const SELECT_NACIONALIDAD   = '#nacionalidad';
    private const INPUT_INSTAGRAM       = '#instagram';

    // Datos base de prueba (cédulas y teléfonos aislados que no chocan con registros reales)
    private string $cedulaBase          = '91000001';
    private string $nombreBase          = 'Carlos';
    private string $apellidoBase        = 'Gomez';
    private string $telefonoBase        = '0412-9900001';
    private string $direccionBase       = 'Avenida Principal Casa 123';
    private string $correoBase          = 'carlos.gomez@testmail.com';
    private string $instagramBase       = '@carlos_g';

    private string $nombreMod           = 'Carlos Modificado';
    private string $apellidoMod         = 'Gomez Editado';
    private string $telefonoMod         = '0412-9900002';
    private string $direccionMod        = 'Calle Nueva Modificada 456';
    private string $correoMod           = 'carlos.modificado@testmail.com';
    private string $instagramMod        = '@carlos_mod';

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
     * Analiza las respuestas de validarEnvio() en representantes.js y main.js.
     */
    #[Group('validacion')]
    public function probarValidacionesDeFormulario(AcceptanceTester $I): void
    {
        // 1. Abrir modal
        $I->click(AcceptanceTester::BTN_NUEVO);
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->see('Registrar Representante', AcceptanceTester::MODAL_TITULO);

        // 2. Intentar registrar con formulario vacío (falla cédula)
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('Tiene que ingresar una cedula valida', 5);
        $I->aceptarSweetAlert();

        // 3. Cédula inválida (muy corta)
        $I->fillField(AcceptanceTester::INPUT_CEDULA, '123');
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('Tiene que ingresar una cedula valida', 5);
        $I->aceptarSweetAlert();

        // 4. Llenar cédula válida y dejar nombre vacío
        $I->fillField(AcceptanceTester::INPUT_CEDULA, $this->cedulaBase);
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('Tiene que ingresar un nombre valido', 5);
        $I->aceptarSweetAlert();

        // 5. Llenar nombre y dejar apellido vacío
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, $this->nombreBase);
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('Tiene que ingresar un apellido valido', 5);
        $I->aceptarSweetAlert();

        // 6. Llenar apellido y dejar teléfono vacío
        $I->fillField(AcceptanceTester::INPUT_APELLIDO, $this->apellidoBase);
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('Tiene que ingresar un telefono valido', 5);
        $I->aceptarSweetAlert();

        // 7. Llenar teléfono y dejar dirección vacía
        $I->fillField(AcceptanceTester::INPUT_TELEFONO, $this->telefonoBase);
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('Tiene que ingresar una direccion valida', 5);
        $I->aceptarSweetAlert();

        // 8. Llenar dirección válida pero correo inválido
        $I->fillField(AcceptanceTester::INPUT_DIRECCION, $this->direccionBase);
        $I->fillField(AcceptanceTester::INPUT_CORREO, 'correo-invalido');
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('Debe ingresar un correo válido', 5);
        $I->aceptarSweetAlert();

        // 9. Cerrar modal limpiamente
        $I->click(AcceptanceTester::MODAL_CERRAR);
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        $I->verificarSinErrores();
    }

    /**
     * Prueba 3: Registrar un nuevo representante con confirmación de SweetAlert2.
     */
    #[Group('registrar', 'crud')]
    public function probarRegistrarRepresentante(AcceptanceTester $I): void
    {
        $this->limpiarRegistroSiExiste($this->cedulaBase, $this->telefonoBase);

        // 1. Abrir modal de inclusión
        $I->click(AcceptanceTester::BTN_NUEVO);
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->see('Registrar Representante', AcceptanceTester::MODAL_TITULO);

        // 2. Rellenar formulario
        $I->selectOption(self::SELECT_NACIONALIDAD, 'V');
        $I->fillField(AcceptanceTester::INPUT_CEDULA, $this->cedulaBase);
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, $this->nombreBase);
        $I->fillField(AcceptanceTester::INPUT_APELLIDO, $this->apellidoBase);
        $I->fillField(AcceptanceTester::INPUT_TELEFONO, $this->telefonoBase);
        $I->fillField(AcceptanceTester::INPUT_DIRECCION, $this->direccionBase);
        $I->fillField(AcceptanceTester::INPUT_CORREO, $this->correoBase);
        $I->fillField(self::INPUT_INSTAGRAM, $this->instagramBase);

        // 3. Enviar y confirmar en SweetAlert
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere registrar este representante?', 5);
        $I->confirmarSweetAlert();

        // 4. Esperar alerta de éxito ("Registro Exitoso")
        $I->waitForText('Registro Exitoso', 5);
        $I->aceptarSweetAlert();

        // 5. Cerrar modal (en incluir no se cierra automáticamente)
        $I->click(AcceptanceTester::MODAL_CERRAR);
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        // 6. Verificar en la lista mediante el buscador
        $I->buscarEnListado($this->cedulaBase);
        $I->waitForText($this->nombreBase . ' ' . $this->apellidoBase, 5, AcceptanceTester::CONT_RESULTADOS);
        $I->see($this->cedulaBase, AcceptanceTester::CONT_RESULTADOS);
        $I->see($this->telefonoBase, AcceptanceTester::CONT_RESULTADOS);

        $I->verificarSinErrores();
    }

    /**
     * Prueba 4: Uso del buscador en tiempo real (coincidencia, sin resultados y restauración).
     */
    #[Group('buscador')]
    public function probarBuscadorRepresentantes(AcceptanceTester $I): void
    {
        $this->asegurarRegistroExiste(
            $this->cedulaBase,
            $this->nombreBase,
            $this->apellidoBase,
            $this->telefonoBase,
            $this->direccionBase,
            'V',
            $this->correoBase,
            $this->instagramBase
        );

        // 1. Búsqueda por cédula existente
        $I->buscarEnListado($this->cedulaBase);
        $I->waitForText($this->nombreBase . ' ' . $this->apellidoBase, 5, AcceptanceTester::CONT_RESULTADOS);

        // 2. Búsqueda por término inexistente
        $I->buscarEnListado('RepresentanteInexistenteXYZ999');
        $I->waitForText('No se encontraron registros', 5, AcceptanceTester::CONT_RESULTADOS);

        // 3. Limpiar buscador para restaurar el listado completo
        $I->limpiarBuscador();
        $I->dontSee('No se encontraron registros', AcceptanceTester::CONT_RESULTADOS);

        $I->verificarSinErrores();
    }

    /**
     * Prueba 5: Modificar un representante existente.
     */
    #[Group('modificar', 'crud')]
    public function probarModificarRepresentante(AcceptanceTester $I): void
    {
        $this->limpiarRegistroSiExiste($this->cedulaBase, $this->telefonoMod);
        $this->asegurarRegistroExiste(
            $this->cedulaBase,
            $this->nombreBase,
            $this->apellidoBase,
            $this->telefonoBase,
            $this->direccionBase,
            'V',
            $this->correoBase,
            $this->instagramBase
        );

        // 1. Ubicar el representante con el buscador
        $I->buscarEnListado($this->cedulaBase);
        $I->waitForText($this->nombreBase . ' ' . $this->apellidoBase, 5, AcceptanceTester::CONT_RESULTADOS);

        // 2. Clic seguro en modificar
        $I->clickModificar();

        // 3. Esperar que el modal abra con datos precargados
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->waitForText('Modificar Representante', 5, AcceptanceTester::MODAL_TITULO);
        $I->seeInField(AcceptanceTester::INPUT_CEDULA, $this->cedulaBase);
        $I->seeInField(AcceptanceTester::INPUT_NOMBRE, $this->nombreBase);

        // 4. Modificar valores
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, $this->nombreMod);
        $I->fillField(AcceptanceTester::INPUT_APELLIDO, $this->apellidoMod);
        $I->fillField(AcceptanceTester::INPUT_TELEFONO, $this->telefonoMod);
        $I->fillField(AcceptanceTester::INPUT_DIRECCION, $this->direccionMod);

        // 5. Guardar y confirmar
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere modificar este representante?', 5);
        $I->confirmarSweetAlert();

        // 6. Esperar alerta de éxito (modificar() cierra el modal automáticamente)
        $I->waitForText('Modificacion Exitosa', 5);
        $I->aceptarSweetAlert();
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        // 7. Validar el cambio en el listado
        $I->buscarEnListado($this->nombreMod);
        $I->waitForText($this->nombreMod . ' ' . $this->apellidoMod, 5, AcceptanceTester::CONT_RESULTADOS);
        $I->see($this->telefonoMod, AcceptanceTester::CONT_RESULTADOS);

        $I->verificarSinErrores();
    }

    /**
     * Prueba 6: Eliminar un representante existente de manera autónoma.
     */
    #[Group('eliminar', 'crud')]
    public function probarEliminarRepresentante(AcceptanceTester $I): void
    {
        $cedulaEliminar    = '91000003';
        $nombreEliminar    = 'Eliminar';
        $apellidoEliminar  = 'Temporal';
        $telefonoEliminar  = '0412-9900003';
        $direccionEliminar = 'Calle Temporal Para Borrar 789';

        $this->asegurarRegistroExiste(
            $cedulaEliminar,
            $nombreEliminar,
            $apellidoEliminar,
            $telefonoEliminar,
            $direccionEliminar
        );

        // 1. Localizar el representante con el buscador
        $I->buscarEnListado($cedulaEliminar);
        $I->waitForText($nombreEliminar . ' ' . $apellidoEliminar, 5, AcceptanceTester::CONT_RESULTADOS);

        // 2. Clic seguro en eliminar
        $I->clickEliminar();

        // 3. Confirmar en SweetAlert
        $I->waitForText('¿Está seguro que quiere eliminar este representante?', 5);
        $I->confirmarSweetAlert();

        // 4. Esperar éxito ("Eliminacion Exitosa")
        $I->waitForText('Eliminacion Exitosa', 5);
        $I->aceptarSweetAlert();

        // 5. Verificar que ya no existe
        $I->buscarEnListado($cedulaEliminar);
        $I->waitForText('No se encontraron registros', 5, AcceptanceTester::CONT_RESULTADOS);
        $I->dontSee($nombreEliminar . ' ' . $apellidoEliminar, AcceptanceTester::CONT_RESULTADOS);

        $I->verificarSinErrores();
    }

    /**
     * Prueba 7: Ciclo de vida completo continuo (Registrar -> Buscar -> Modificar -> Eliminar).
     */
    #[Group('cicloCompleto', 'e2e')]
    public function probarCicloCompletoRepresentante(AcceptanceTester $I): void
    {
        $cedulaCiclo       = '91000004';
        $nombreCiclo       = 'Andres';
        $apellidoCiclo     = 'Ciclo';
        $telefonoCiclo     = '0412-9900004';
        $direccionCiclo    = 'Sector El Ciclo Casa 10';

        $nombreCicloMod    = 'Andres Modificado';
        $apellidoCicloMod  = 'Ciclo Actualizado';
        $telefonoCicloMod  = '0412-9900005';
        $direccionCicloMod = 'Sector El Ciclo Modificado Casa 20';

        $this->limpiarRegistroSiExiste($cedulaCiclo, $telefonoCiclo);
        $this->limpiarRegistroSiExiste($cedulaCiclo, $telefonoCicloMod);

        // --- PASO 1: REGISTRAR ---
        $I->click(AcceptanceTester::BTN_NUEVO);
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->selectOption(self::SELECT_NACIONALIDAD, 'V');
        $I->fillField(AcceptanceTester::INPUT_CEDULA, $cedulaCiclo);
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, $nombreCiclo);
        $I->fillField(AcceptanceTester::INPUT_APELLIDO, $apellidoCiclo);
        $I->fillField(AcceptanceTester::INPUT_TELEFONO, $telefonoCiclo);
        $I->fillField(AcceptanceTester::INPUT_DIRECCION, $direccionCiclo);
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere registrar este representante?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Registro Exitoso', 5);
        $I->aceptarSweetAlert();
        $I->click(AcceptanceTester::MODAL_CERRAR);
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        // --- PASO 2: BUSCAR ---
        $I->buscarEnListado($cedulaCiclo);
        $I->waitForText($nombreCiclo . ' ' . $apellidoCiclo, 5, AcceptanceTester::CONT_RESULTADOS);

        // --- PASO 3: MODIFICAR ---
        $I->clickModificar();
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->waitForText('Modificar Representante', 5, AcceptanceTester::MODAL_TITULO);
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, $nombreCicloMod);
        $I->fillField(AcceptanceTester::INPUT_APELLIDO, $apellidoCicloMod);
        $I->fillField(AcceptanceTester::INPUT_TELEFONO, $telefonoCicloMod);
        $I->fillField(AcceptanceTester::INPUT_DIRECCION, $direccionCicloMod);
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere modificar este representante?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Modificacion Exitosa', 5);
        $I->aceptarSweetAlert();
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        // --- PASO 4: BUSCAR MODIFICADO ---
        $I->buscarEnListado($cedulaCiclo);
        $I->waitForText($nombreCicloMod . ' ' . $apellidoCicloMod, 5, AcceptanceTester::CONT_RESULTADOS);
        $I->see($telefonoCicloMod, AcceptanceTester::CONT_RESULTADOS);

        // --- PASO 5: ELIMINAR ---
        $I->clickEliminar();
        $I->waitForText('¿Está seguro que quiere eliminar este representante?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Eliminacion Exitosa', 5);
        $I->aceptarSweetAlert();

        // --- PASO 6: VALIDAR INEXISTENCIA ---
        $I->buscarEnListado($cedulaCiclo);
        $I->waitForText('No se encontraron registros', 5, AcceptanceTester::CONT_RESULTADOS);

        $I->verificarSinErrores();
    }

    // =========================================================================
    // HELPERS DE BASE DE DATOS
    // =========================================================================

    private function limpiarRegistroSiExiste(string $cedula, string $telefono = ''): void
    {
        try {
            $modelo = new \App\modelo\ModeloRepresentantes();
            $res = $modelo->Consultar(['filtro' => $cedula]);
            if (!empty($res['datos'])) {
                foreach ($res['datos'] as $d) {
                    if ($d['cedula'] === $cedula) {
                        $modelo->ProcesarDatos([
                            'accion' => 'eliminar',
                            'id'     => (string)$d['codigo_representante']
                        ]);
                    }
                }
            }
            if ($telefono !== '') {
                $resTel = $modelo->Consultar(['filtro' => $telefono]);
                if (!empty($resTel['datos'])) {
                    foreach ($resTel['datos'] as $d) {
                        if ($d['telefono'] === $telefono) {
                            $modelo->ProcesarDatos([
                                'accion' => 'eliminar',
                                'id'     => (string)$d['codigo_representante']
                            ]);
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // Silencioso para no detener la prueba si no hay conexión
        }
    }

    private function asegurarRegistroExiste(
        string $cedula,
        string $nombre,
        string $apellido,
        string $telefono,
        string $direccion,
        string $nacionalidad = 'V',
        string $correo = '',
        string $instagram = ''
    ): void {
        try {
            $modelo = new \App\modelo\ModeloRepresentantes();
            $res = $modelo->Consultar(['filtro' => $cedula]);
            $encontrado = false;
            if (!empty($res['datos'])) {
                foreach ($res['datos'] as $d) {
                    if ($d['cedula'] === $cedula) {
                        $encontrado = true;
                        break;
                    }
                }
            }
            if (!$encontrado) {
                $modelo->ProcesarDatos([
                    'accion'       => 'incluir',
                    'nacionalidad' => $nacionalidad,
                    'cedula'       => $cedula,
                    'nombre'       => $nombre,
                    'apellido'     => $apellido,
                    'telefono'     => $telefono,
                    'direccion'    => $direccion,
                    'correo'       => $correo,
                    'instagram'    => $instagram
                ]);
            }
        } catch (\Throwable $e) {
            // Silencioso para no interferir con la ejecución UI
        }
    }
}

