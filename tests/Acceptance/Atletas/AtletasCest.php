<?php

declare(strict_types=1);

namespace Tests\Acceptance\Atletas;

use Codeception\Attribute\Group;
use Tests\Support\AcceptanceTester;

if (!defined('_DB_NAME_')) {
    require_once __DIR__ . '/../../../config/config.php';
}

#[Group('atletas')]
#[Group('interfaz')]
final class AtletasCest
{
    private const RUTA_MODULO                   = '/Atletas';
    private const TITULO_MODULO                 = 'Atletas';

    // Selectores del Asistente y Modal Principal
    private const MODAL_HISTORIAL               = '#modal_historial';
    private const CONT_MODAL_HISTORIAL          = '#contenedor_modal_historial';
    private const BTN_HISTORIAL_CERRAR          = '#modal_historial .cerrar_modal';

    private const BTN_SIGUIENTE                 = '#btn_siguiente';
    private const BTN_ANTERIOR                  = '#btn_anterior';

    // Paso 1: Personales
    private const INPUT_FECHA_NAC               = '#fecha_nac';
    private const INPUT_LUGAR_NAC               = '#lugar_nacimiento';
    private const INPUT_DOC_I                   = '#doc_i';
    private const INPUT_EDAD                    = '#edad';
    private const SELECT_GENERO                 = '#genero';

    // Paso 2: Deportivos
    private const SELECT_CATEGORIA              = '#categoria';
    private const SELECT_POSICION               = '#posicion';
    private const INPUT_DORSAL                  = '#dorsal';

    // Paso 3: Contacto
    private const SELECT_REPRESENTANTE          = '#representante';
    private const INPUT_MUNICIPIO               = '#municipio';
    private const INPUT_INSTAGRAM               = '#instagram';

    // Paso 4: Salud y Medidas
    private const INPUT_PESO                    = '#peso';
    private const INPUT_ESTATURA                = '#estatura';
    private const SELECT_TALLA_PANTALON         = '#talla_pantalon';
    private const SELECT_TALLA_FRANELA          = '#talla_franela';
    private const INPUT_TALLA_CALZADO           = '#talla_calzado';
    private const SELECT_TIPO_SANGRE            = '#tipo_sangre';

    // Botones de acción en tarjetas de atleta
    private const BTN_MODIFICAR_ATLETA          = '#resultadoconsulta .cbt_v';
    private const BTN_RETIRAR_ATLETA            = '#resultadoconsulta .cbt_r';
    private const BTN_REINSCRIBIR_ATLETA        = '#resultadoconsulta .cbt_m';
    private const BTN_DOCUMENTOS_ATLETA         = '#resultadoconsulta .cbt_sec';
    private const BTN_HISTORIAL_ATLETA          = '#resultadoconsulta #cbt_historial';

    // Datos base de prueba
    private string $docBase;
    private string $primerNombre                = 'Gabriel';
    private string $segundoNombre               = 'Alejandro';
    private string $nombreBase                  = 'Gabriel Alejandro';
    private string $apellidoBase                = 'Mendoza Ramos';
    private string $lugarNacBase                = 'Barquisimeto';
    private string $telefonoBase                = '0414-9988776';
    private string $correoBase;
    private string $direccionBase               = 'Urbanización Las Trinitarias Manzana 12';
    private string $municipioBase               = 'Iribarren';
    private string $instagramBase               = '@gabriel_atleta';
    private string $dorsalBase                  = '23';
    private string $pesoBase                    = '68.5';
    private string $estaturaBase                = '178';
    private string $tallaCalzadoBase            = '42';

    public function __construct()
    {
        // Cédula y correo aislados para evitar colisiones únicas
        $timestamp = (string) time();
        $this->docBase    = '8' . substr($timestamp, -7);
        $this->correoBase = 'atleta_' . substr($timestamp, -6) . '@testmail.com';
    }

    public function _before(AcceptanceTester $I): void
    {
        // Login con snapshot + navegación + comprobación de título y limpieza de overlays
        $I->irAlModulo(self::RUTA_MODULO, self::TITULO_MODULO);
    }

    /**
     * Desactiva las transiciones CSS de deslizamiento del asistente para evitar
     * que WebDriver intente interactuar con campos mientras la animación sigue en curso.
     */
    private function desactivarTransicionesAsistente(AcceptanceTester $I): void
    {
        $I->executeJS("
            if (!$('#test-no-transitions').length) {
                $('head').append('<style id=\"test-no-transitions\">#asistente_pista, .asistente_paso { transition: none !important; animation: none !important; } .asistente_vista { scroll-behavior: auto !important; }</style>');
            }
            $('#asistente_pista, .asistente_paso').css({'transition': 'none', 'animation': 'none'});
        ");
    }

    /**
     * Cierra el modal de registro/asistente de forma robusta e interactiva.
     */
    private function cerrarModalAsistenteSeguro(AcceptanceTester $I): void
    {
        $I->executeJS("
            if (typeof cerrarModal === 'function') {
                cerrarModal();
            } else {
                $('#cerrar_modal').click();
            }
        ");
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);
    }

    /**
     * Avanza al siguiente paso del asistente esperando que la sección termine de renderizar.
     */
    private function avanzarPaso(AcceptanceTester $I, ?string $elementoEsperado = null): void
    {
        $I->click(self::BTN_SIGUIENTE);
        $I->wait(1);
        $I->despejarOverlays();
        if ($elementoEsperado !== null) {
            $I->waitForElementVisible($elementoEsperado, 5);
        }
    }

    /**
     * Prueba 1: Verifica la carga inicial de la interfaz del módulo de Atletas.
     */
    #[Group('carga', 'interfaz')]
    public function probarCargaInicialDeInterfaz(AcceptanceTester $I): void
    {
        $I->see(self::TITULO_MODULO, AcceptanceTester::TITULO_PAGINA);
        $I->seeElement(AcceptanceTester::INPUT_BUSQUEDA);
        $I->seeElement(AcceptanceTester::BTN_NUEVO);
        $I->seeElement(AcceptanceTester::CONT_RESULTADOS);

        // Modal de registro/asistente debe existir en el DOM pero oculto
        $I->seeElementInDOM(AcceptanceTester::MODAL);
        $I->dontSeeElement(AcceptanceTester::MODAL);

        // Modal de historial debe existir en el DOM pero oculto
        $I->seeElementInDOM(self::MODAL_HISTORIAL);
        $I->dontSeeElement(self::MODAL_HISTORIAL);

        $I->verificarSinErrores();
    }

    /**
     * Prueba 2: Registro completo de un atleta navegando por los 5 pasos del asistente,
     * y comprobación inmediata del buscador utilizando ÚNICAMENTE su primer nombre.
     */
    #[Group('registro')]
    public function probarRegistroAtleta(AcceptanceTester $I): void
    {
        // 1. Abrir asistente de registro
        $I->click(AcceptanceTester::BTN_NUEVO);
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->see('Registrar Atleta', AcceptanceTester::MODAL_TITULO);

        // Desactivar animaciones de deslizamiento
        $this->desactivarTransicionesAsistente($I);

        // Esperar que la petición AJAX MultiConsulta popule las opciones en el DOM
        $I->waitForJS("return $('#categoria option[data-min]').length > 0;", 5);

        // Seleccionar una categoría compatible (preferiblemente senior o con rango amplio) y fijar fecha en Flatpickr
        $I->executeJS("
            let options = $('#categoria option[data-min]').toArray();
            let cat = options.find(function(o) {
                return parseInt(o.getAttribute('data-min') || '0') >= 18;
            }) || options[0];
            let catVal = cat ? cat.value : '1';
            window.catSeleccionada = catVal;
            let min = cat ? parseInt(cat.getAttribute('data-min') || '18') : 18;
            let max = cat ? parseInt(cat.getAttribute('data-max') || '30') : 30;
            let edadCalc = Math.floor((min + max) / 2);
            let anio = new Date().getFullYear() - edadCalc;
            let fechaStr = anio + '-06-15';

            let fInput = document.getElementById('fecha_nac');
            if (fInput && fInput._flatpickr) {
                fInput._flatpickr.setDate(fechaStr, true);
            }
            $('#fecha_nac').val(fechaStr).trigger('change');
        ");

        // PASO 1: Datos Personales
        $I->fillField(self::INPUT_LUGAR_NAC, $this->lugarNacBase);
        $I->fillField(self::INPUT_DOC_I, $this->docBase);
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, $this->nombreBase);
        $I->fillField(AcceptanceTester::INPUT_APELLIDO, $this->apellidoBase);
        $I->executeJS("$('#genero').val('H').trigger('change');");

        // PASO 2: Datos Deportivos (usando la misma categoría calculada)
        $this->avanzarPaso($I, self::INPUT_DORSAL);
        $I->executeJS("
            let catVal = window.catSeleccionada || $('#categoria option[data-min]').first().val();
            let posVal = $('#posicion option:not([value=\"\"])').first().val();
            $('#categoria').val(catVal).trigger('change');
            $('#posicion').val(posVal).trigger('change');
        ");
        $I->fillField(self::INPUT_DORSAL, $this->dorsalBase);

        // PASO 3: Datos de Contacto
        $this->avanzarPaso($I, self::INPUT_INSTAGRAM);
        $I->executeJS("
            let repVal = $('#representante option:not([value=\"\"])').first().val();
            if (repVal) {
                $('#representante').val(repVal).trigger('change');
            }
        ");

        // Teléfono y dirección solo aplican a atletas mayores de edad (para menores se usa el representante)
        $telefonoInteractable = (bool) $I->executeJS("
            let t = document.getElementById('telefono');
            return t && !t.disabled && !t.readOnly;
        ");
        if ($telefonoInteractable) {
            $I->fillField(AcceptanceTester::INPUT_TELEFONO, $this->telefonoBase);
        }
        $direccionInteractable = (bool) $I->executeJS("
            let d = document.getElementById('direccion');
            return d && !d.disabled && !d.readOnly;
        ");
        if ($direccionInteractable) {
            $I->fillField(AcceptanceTester::INPUT_DIRECCION, $this->direccionBase);
        }
        $I->fillField(AcceptanceTester::INPUT_CORREO, $this->correoBase);
        $I->fillField(self::INPUT_INSTAGRAM, $this->instagramBase);
        $I->fillField(self::INPUT_MUNICIPIO, $this->municipioBase);

        // PASO 4: Salud y Medidas
        $this->avanzarPaso($I, self::INPUT_PESO);
        $I->fillField(self::INPUT_PESO, $this->pesoBase);
        $I->fillField(self::INPUT_ESTATURA, $this->estaturaBase);
        $I->executeJS("
            $('#talla_pantalon').val('M').trigger('change');
            $('#talla_franela').val('M').trigger('change');
            $('#tipo_sangre').val('O+').trigger('change');
        ");
        $I->fillField(self::INPUT_TALLA_CALZADO, $this->tallaCalzadoBase);

        // PASO 5: Foto (opcional) y Envío Final
        $this->avanzarPaso($I, AcceptanceTester::BTN_PROCESO);
        $I->click(AcceptanceTester::BTN_PROCESO);

        // Confirmación en SweetAlert
        $I->waitForText('¿Está seguro que quiere registrar este atleta?', 5);
        $I->confirmarSweetAlert();

        // Verificación de éxito
        $I->waitForText('Registro Exitoso', 10);
        $I->aceptarSweetAlert();

        // Cerrar el modal del asistente para acceder al listado de atletas
        $this->cerrarModalAsistenteSeguro($I);

        // 2. Probar el buscador con ÚNICAMENTE el primer nombre del atleta recién registrado
        $I->buscarEnListado($this->primerNombre);
        $I->waitForText($this->primerNombre, 5, AcceptanceTester::CONT_RESULTADOS);
        $I->see($this->apellidoBase, AcceptanceTester::CONT_RESULTADOS);
        $I->see($this->docBase, AcceptanceTester::CONT_RESULTADOS);

        $I->limpiarBuscador();
        $I->verificarSinErrores();
    }

    /**
     * Prueba 3: Buscador en tiempo real de atletas con debounce utilizando
     * el atleta registrado y búsquedas inexistentes.
     */
    #[Group('buscador')]
    public function probarBuscadorAtletas(AcceptanceTester $I): void
    {
        // 1. Búsqueda con un término inexistente
        $I->buscarEnListado('NONEXISTENT_ATLETA_XYZ_999');
        $I->waitForElementVisible(AcceptanceTester::LISTADO_VACIO, 5);
        $I->see('No se encontraron registros', AcceptanceTester::LISTADO_VACIO);

        // 2. Restaurar buscador
        $I->limpiarBuscador();

        // 3. Buscar al atleta registrado utilizando únicamente su primer nombre
        $I->buscarEnListado($this->primerNombre);
        $I->waitForElementVisible(AcceptanceTester::ITEM_LISTADO, 5);
        $I->see($this->primerNombre, AcceptanceTester::CONT_RESULTADOS);
        $I->see($this->apellidoBase, AcceptanceTester::CONT_RESULTADOS);
        $I->see($this->docBase, AcceptanceTester::CONT_RESULTADOS);
        $I->dontSee('No se encontraron registros');

        // 4. Limpieza final del buscador
        $I->limpiarBuscador();
        $I->verificarSinErrores();
    }

    /**
     * Prueba 4: Validaciones por pasos del asistente antes de permitir el registro.
     */
    #[Group('validacion')]
    public function probarValidacionesWizardRegistro(AcceptanceTester $I): void
    {
        // 1. Abrir modal de nuevo atleta
        $I->click(AcceptanceTester::BTN_NUEVO);
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->see('Registrar Atleta', AcceptanceTester::MODAL_TITULO);

        $this->desactivarTransicionesAsistente($I);

        // 2. Intentar avanzar el paso 1 con campos vacíos (falla fecha de nacimiento)
        $I->click(self::BTN_SIGUIENTE);
        $I->waitForText('La fecha de nacimiento es obligatoria', 5);
        $I->aceptarSweetAlert();

        // 3. Asignar fecha válida en Flatpickr pero dejar lugar de nacimiento vacío
        $I->executeJS("
            let anio = new Date().getFullYear() - 15;
            let fVal = anio + '-04-20';
            let fInput = document.getElementById('fecha_nac');
            if (fInput && fInput._flatpickr) {
                fInput._flatpickr.setDate(fVal, true);
            }
            $('#fecha_nac').val(fVal).trigger('change');
        ");
        $I->click(self::BTN_SIGUIENTE);
        $I->waitForText('El lugar de nacimiento es obligatorio', 5);
        $I->aceptarSweetAlert();

        // 4. Llenar lugar de nacimiento pero dejar cédula vacía
        $I->fillField(self::INPUT_LUGAR_NAC, $this->lugarNacBase);
        $I->click(self::BTN_SIGUIENTE);
        $I->waitForText('Debe ingresar una cédula válida', 5);
        $I->aceptarSweetAlert();

        // 5. Llenar cédula pero dejar nombre vacío
        $I->fillField(self::INPUT_DOC_I, $this->docBase);
        $I->click(self::BTN_SIGUIENTE);
        $I->waitForText('Debe ingresar un nombre válido', 5);
        $I->aceptarSweetAlert();

        // 6. Llenar nombre pero dejar apellido vacío
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, $this->nombreBase);
        $I->click(self::BTN_SIGUIENTE);
        $I->waitForText('Debe ingresar un apellido válido', 5);
        $I->aceptarSweetAlert();

        // 7. Cerrar modal de registro
        $I->click(AcceptanceTester::MODAL_CERRAR);
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);
        $I->verificarSinErrores();
    }

    /**
     * Prueba 5: Modificación de los datos de un atleta existente.
     */
    #[Group('modificar')]
    public function probarModificarAtleta(AcceptanceTester $I): void
    {
        // 1. Filtrar por el atleta registrado para modificarlo
        $I->buscarEnListado($this->primerNombre);
        $I->waitForElementVisible(self::BTN_MODIFICAR_ATLETA, 5);
        $I->despejarOverlays();
        $I->executeJS("document.querySelector('" . self::BTN_MODIFICAR_ATLETA . "').click();");

        // 2. Esperar que cargue el modal en modo modificar
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->see('Modificar Atleta', AcceptanceTester::MODAL_TITULO);

        $this->desactivarTransicionesAsistente($I);

        // 3. Modificar lugar de nacimiento en Paso 0 (Personales)
        $I->fillField(self::INPUT_LUGAR_NAC, 'Cabudare');

        // 4. Saltar al último paso para activar el botón de procesar
        $I->executeJS("saltarAPaso(4);");
        $I->waitForElementVisible(AcceptanceTester::BTN_PROCESO, 5);
        $I->click(AcceptanceTester::BTN_PROCESO);

        // 5. Confirmar modificación en SweetAlert
        $I->waitForText('¿Está seguro que quiere modificar este atleta?', 5);
        $I->confirmarSweetAlert();

        // 6. Verificar mensaje de éxito
        $I->waitForText('Operación Exitosa', 10);
        $I->aceptarSweetAlert();
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        $I->limpiarBuscador();
        $I->verificarSinErrores();
    }

    /**
     * Prueba 6: Consulta del historial de inscripciones y retiros del atleta.
     */
    #[Group('historial')]
    public function probarVerHistorialInscripcion(AcceptanceTester $I): void
    {
        // 1. Filtrar por el atleta registrado y expandir su tarjeta
        $I->buscarEnListado($this->primerNombre);
        $I->waitForElementVisible(AcceptanceTester::ITEM_LISTADO, 5);
        $I->executeJS("document.querySelector('" . AcceptanceTester::ITEM_LISTADO . "').click();");
        $I->wait(1);

        // 2. Clic en el botón Historial de Inscripción
        $I->waitForElementVisible(self::BTN_HISTORIAL_ATLETA, 5);
        $I->despejarOverlays();
        $I->executeJS("document.querySelector('" . self::BTN_HISTORIAL_ATLETA . "').click();");

        // 3. Esperar que aparezca el modal de historial
        $I->waitForElementVisible(self::MODAL_HISTORIAL, 5);
        $I->see('Historial de Inscripciones y Retiros', self::MODAL_HISTORIAL . ' .titulo_modal');
        $I->seeElement('#tbody_historial');

        // 4. Cerrar modal de historial
        $I->executeJS("cerrarModalHistorial();");
        $I->waitForElementNotVisible(self::MODAL_HISTORIAL, 5);

        $I->limpiarBuscador();
        $I->verificarSinErrores();
    }

    /**
     * Prueba 7: Diálogo interactivo para generación de documentos del atleta (Curriculum / Fichas).
     */
    #[Group('documentos')]
    public function probarGenerarDocumentos(AcceptanceTester $I): void
    {
        // 1. Clic en el botón de generar documentos
        $I->buscarEnListado($this->primerNombre);
        $I->waitForElementVisible(self::BTN_DOCUMENTOS_ATLETA, 5);
        $I->despejarOverlays();
        $I->executeJS("document.querySelector('" . self::BTN_DOCUMENTOS_ATLETA . "').click();");

        // 2. Verificar diálogo de selección de documentos
        $I->waitForText('Seleccione el Documento a Generar', 5);
        $I->see('Generar Currículum (PDF)');
        $I->see('Generar Ficha Deportiva de Alto Rendimiento (Word)');
        $I->see('Generar Ficha Técnica (Word)');

        // 3. Seleccionar la primera opción (Currículum PDF) y dar Siguiente
        $I->executeJS("document.querySelectorAll('.btn-doc-option')[0].click();");
        $I->wait(1);
        $I->executeJS("document.querySelector('.swal2-confirm').click();");

        // 4. Diálogo secundario: Selección de año opcional
        $I->waitForText('¿A partir de qué año desea generar el historial?', 5);
        $I->seeElement('#anio_curriculum');

        // 5. Cancelar la generación para evitar descargas innecesarias durante las pruebas
        $I->cancelarSweetAlert();

        $I->limpiarBuscador();
        $I->despejarOverlays();
        $I->verificarSinErrores();
    }

    /**
     * Prueba 8: Flujo de Retiro voluntario con motivo y posterior Re-inscripción.
     */
    #[Group('retiro_reinscripcion')]
    public function probarRetiroYReinscripcionAtleta(AcceptanceTester $I): void
    {
        // A. RETIRO DEL ATLETA REGISTRADO
        $I->buscarEnListado($this->primerNombre);
        $I->waitForElementVisible(self::BTN_RETIRAR_ATLETA, 5);
        $I->despejarOverlays();
        $I->executeJS("document.querySelector('" . self::BTN_RETIRAR_ATLETA . "').click();");

        // SweetAlert prompt solicitando motivo
        $I->waitForText('¿Está seguro que quiere retirar este atleta del club?', 5);
        $I->responderSweetAlertPrompt('Cambio de residencia familiar a otra region');

        // Confirmación de retiro
        $I->waitForText('Retiro Exitoso', 10);
        $I->aceptarSweetAlert();

        // B. RE-INSCRIPCIÓN DEL ATLETA RETIRADO
        $I->buscarEnListado($this->primerNombre);
        $I->waitForElementVisible(self::BTN_REINSCRIBIR_ATLETA, 5);
        $I->despejarOverlays();
        $I->executeJS("document.querySelector('" . self::BTN_REINSCRIBIR_ATLETA . "').click();");

        // Verificar apertura del modal de re-inscripción
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->see('Re-inscribir Atleta', AcceptanceTester::MODAL_TITULO);

        $this->desactivarTransicionesAsistente($I);

        // Avanzar al último paso y procesar
        $I->executeJS("saltarAPaso(4);");
        $I->waitForElementVisible(AcceptanceTester::BTN_PROCESO, 5);
        $I->click(AcceptanceTester::BTN_PROCESO);

        // Confirmar re-inscripción
        $I->waitForText('¿Está seguro que quiere re-inscribir a este atleta?', 5);
        $I->confirmarSweetAlert();

        // Confirmación de éxito
        $I->waitForText('Operación Exitosa', 10);
        $I->aceptarSweetAlert();
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        $I->limpiarBuscador();
        $I->verificarSinErrores();
    }

    /**
     * Prueba 9: Ciclo de vida completo del atleta (Registro -> Historial -> Documentos -> Modificar -> Retirar -> Re-inscribir).
     */
    #[Group('ciclo_completo')]
    public function probarCicloCompletoAtleta(AcceptanceTester $I): void
    {
        $docCiclo = '8' . substr((string) (time() + 10), -7);
        $primerNombreCiclo = 'Atleta';
        $segundoNombreCiclo = 'Ciclo';
        $nombreCiclo = $primerNombreCiclo . ' ' . $segundoNombreCiclo;
        $apellidoCiclo = 'Automatizado';
        $correoCiclo = 'ciclo_' . substr((string) time(), -6) . '@testmail.com';

        // 1. Registro
        $I->click(AcceptanceTester::BTN_NUEVO);
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);

        $this->desactivarTransicionesAsistente($I);

        // Esperar que MultiConsulta llene las opciones
        $I->waitForJS("return $('#categoria option[data-min]').length > 0;", 5);

        $I->executeJS("
            let options = $('#categoria option[data-min]').toArray();
            let opt = options.find(function(o) {
                return parseInt(o.getAttribute('data-min') || '0') >= 18;
            }) || options[0];
            let catVal = opt ? opt.value : '1';
            window.catCiclo = catVal;
            let min = opt ? parseInt(opt.getAttribute('data-min') || '18') : 18;
            let max = opt ? parseInt(opt.getAttribute('data-max') || '30') : 30;
            let edadCalc = Math.floor((min + max) / 2);
            let anio = new Date().getFullYear() - edadCalc;
            let fechaStr = anio + '-06-15';

            let fInput = document.getElementById('fecha_nac');
            if (fInput && fInput._flatpickr) {
                fInput._flatpickr.setDate(fechaStr, true);
            }
            $('#fecha_nac').val(fechaStr).trigger('change');
        ");

        $I->fillField(self::INPUT_LUGAR_NAC, $this->lugarNacBase);
        $I->fillField(self::INPUT_DOC_I, $docCiclo);
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, $nombreCiclo);
        $I->fillField(AcceptanceTester::INPUT_APELLIDO, $apellidoCiclo);
        $I->executeJS("$('#genero').val('H').trigger('change');");

        $this->avanzarPaso($I, self::INPUT_DORSAL);
        $I->executeJS("
            let catVal = window.catCiclo || $('#categoria option[data-min]').first().val();
            let posVal = $('#posicion option:not([value=\"\"])').first().val();
            $('#categoria').val(catVal).trigger('change');
            $('#posicion').val(posVal).trigger('change');
        ");
        $I->fillField(self::INPUT_DORSAL, '77');

        $this->avanzarPaso($I, self::INPUT_MUNICIPIO);
        $I->executeJS("
            let repVal = $('#representante option:not([value=\"\"])').first().val();
            if (repVal) {
                $('#representante').val(repVal).trigger('change');
            }
        ");

        $telefonoInteractable = (bool) $I->executeJS("
            let t = document.getElementById('telefono');
            return t && !t.disabled && !t.readOnly;
        ");
        if ($telefonoInteractable) {
            $I->fillField(AcceptanceTester::INPUT_TELEFONO, '0424-5551234');
        }
        $direccionInteractable = (bool) $I->executeJS("
            let d = document.getElementById('direccion');
            return d && !d.disabled && !d.readOnly;
        ");
        if ($direccionInteractable) {
            $I->fillField(AcceptanceTester::INPUT_DIRECCION, $this->direccionBase);
        }
        $I->fillField(AcceptanceTester::INPUT_CORREO, $correoCiclo);
        $I->fillField(self::INPUT_MUNICIPIO, $this->municipioBase);

        $this->avanzarPaso($I, self::INPUT_PESO);
        $I->fillField(self::INPUT_PESO, '60.0');
        $I->fillField(self::INPUT_ESTATURA, '168');
        $I->executeJS("
            $('#talla_pantalon').val('S').trigger('change');
            $('#talla_franela').val('S').trigger('change');
            $('#tipo_sangre').val('A+').trigger('change');
        ");
        $I->fillField(self::INPUT_TALLA_CALZADO, '40');

        $this->avanzarPaso($I, AcceptanceTester::BTN_PROCESO);
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere registrar este atleta?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Registro Exitoso', 10);
        $I->aceptarSweetAlert();

        // Cerrar modal del asistente
        $this->cerrarModalAsistenteSeguro($I);

        // 2. Búsqueda y verificación con primer nombre nada más
        $I->buscarEnListado($primerNombreCiclo);
        $I->waitForText($nombreCiclo, 5, AcceptanceTester::CONT_RESULTADOS);

        // 3. Ver historial de inscripción
        $I->executeJS("document.querySelector('" . AcceptanceTester::ITEM_LISTADO . "').click();");
        $I->wait(1);
        $I->executeJS("document.querySelector('" . self::BTN_HISTORIAL_ATLETA . "').click();");
        $I->waitForElementVisible(self::MODAL_HISTORIAL, 5);
        $I->executeJS("cerrarModalHistorial();");
        $I->waitForElementNotVisible(self::MODAL_HISTORIAL, 5);

        // 4. Probar diálogo de documentos
        $I->executeJS("document.querySelector('" . self::BTN_DOCUMENTOS_ATLETA . "').click();");
        $I->waitForText('Seleccione el Documento a Generar', 5);
        $I->cancelarSweetAlert();

        // 5. Modificar
        $I->executeJS("document.querySelector('" . self::BTN_MODIFICAR_ATLETA . "').click();");
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->fillField(self::INPUT_LUGAR_NAC, 'Cabudare');
        $I->executeJS("saltarAPaso(4);");
        $I->waitForElementVisible(AcceptanceTester::BTN_PROCESO, 5);
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere modificar este atleta?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Operación Exitosa', 10);
        $I->aceptarSweetAlert();
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        // 6. Retirar
        $I->buscarEnListado($primerNombreCiclo);
        $I->waitForElementVisible(self::BTN_RETIRAR_ATLETA, 5);
        $I->executeJS("document.querySelector('" . self::BTN_RETIRAR_ATLETA . "').click();");
        $I->waitForText('¿Está seguro que quiere retirar este atleta del club?', 5);
        $I->responderSweetAlertPrompt('Cierre de ciclo deportivo anual');
        $I->waitForText('Retiro Exitoso', 10);
        $I->aceptarSweetAlert();

        // 7. Re-inscribir
        $I->buscarEnListado($primerNombreCiclo);
        $I->waitForElementVisible(self::BTN_REINSCRIBIR_ATLETA, 5);
        $I->executeJS("document.querySelector('" . self::BTN_REINSCRIBIR_ATLETA . "').click();");
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->executeJS("saltarAPaso(4);");
        $I->waitForElementVisible(AcceptanceTester::BTN_PROCESO, 5);
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere re-inscribir a este atleta?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Operación Exitosa', 10);
        $I->aceptarSweetAlert();
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        // 8. Restaurar buscador y confirmar sin errores
        $I->limpiarBuscador();
        $I->verificarSinErrores();
    }
}
