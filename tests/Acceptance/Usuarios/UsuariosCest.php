<?php

declare(strict_types=1);

namespace Tests\Acceptance\Usuarios;

use Codeception\Attribute\Group;
use PHPUnit\Framework\Assert;
use Tests\Support\AcceptanceTester;

if (!defined('_DB_NAME_')) {
    require_once __DIR__ . '/../../../config/config.php';
}

#[Group('usuarios')]
#[Group('interfaz')]
final class UsuariosCest
{
    private const RUTA_MODULO          = '/Usuarios';
    private const TITULO_MODULO        = 'Usuarios';

    private const INPUT_CONTRASENA     = '#contraseña';
    private const SELECT_ROLES         = '#roles';
    private const INPUT_FOTO           = '#foto';
    private const FILA_PERMISOS        = '#row_permisos';
    private const BUSCADOR_PERMISOS    = '#buscador_permisos';
    private const TAB_ASIGNADOS        = '.permisos_tab[data-tab="asignados"]';
    private const TAB_NO_ASIGNADOS     = '.permisos_tab[data-tab="no_asignados"]';
    private const CONT_TAB_ASIGNADOS   = '#tab_asignados';
    private const CONT_TAB_NO_ASIG     = '#tab_no_asignados';
    private const DETALLE_OCULTO       = '.listado_detalle_oculto';

    private const BTN_PERMISOS         = '#resultadoconsulta .cbt_m';
    private const BTN_DESBLOQUEADO     = '#resultadoconsulta .listado_col_acciones .cbt_g';
    private const BTN_BLOQUEADO        = '#resultadoconsulta .listado_col_acciones .cbt_a';

    private const ROL_PRUEBA_ID        = '6';

    private array $usuarioBase = [
        'cedula'     => '30111222',
        'nombre'     => 'Pruebauno',
        'apellido'   => 'Interfaz',
        'telefono'   => '0414-1112233',
        'correo'     => 'prueba.uno.interfaz@gmail.com',
        'contrasena' => 'Prueba123$',
    ];

    private array $usuarioEliminar = [
        'cedula'     => '30111333',
        'nombre'     => 'Pruebados',
        'apellido'   => 'Eliminar',
        'telefono'   => '0414-1113344',
        'correo'     => 'prueba.dos.eliminar@gmail.com',
        'contrasena' => 'Prueba123$',
    ];

    private array $usuarioCiclo = [
        'cedula'     => '30111444',
        'nombre'     => 'Pruebatres',
        'apellido'   => 'Ciclo',
        'telefono'   => '0414-1114455',
        'correo'     => 'prueba.tres.ciclo@gmail.com',
        'contrasena' => 'Prueba123$',
    ];

    private array $usuarioSinAcceso = [
        'cedula'     => '30111555',
        'nombre'     => 'Pruebacuatro',
        'apellido'   => 'Acceso',
        'telefono'   => '0414-1115566',
        'correo'     => 'prueba.cuatro.acceso@gmail.com',
        'contrasena' => 'Prueba123$',
    ];

    private string $nombreModificado   = 'Pruebamodificado';
    private string $apellidoModificado = 'Editado';

    public function _before(AcceptanceTester $I): void
    {
        $I->irAlModulo(self::RUTA_MODULO, self::TITULO_MODULO);
    }

    #[Group('carga', 'interfaz')]
    public function probarCargaInicialDeInterfaz(AcceptanceTester $I): void
    {
        $I->see(self::TITULO_MODULO, AcceptanceTester::TITULO_PAGINA);
        $I->seeElement(AcceptanceTester::INPUT_BUSQUEDA);
        $I->seeElement(AcceptanceTester::BTN_NUEVO);
        $I->see('Nuevo Usuario', AcceptanceTester::BTN_NUEVO);
        $I->seeElement(AcceptanceTester::BTN_REPORTE);
        $I->see('Generar Reporte', AcceptanceTester::BTN_REPORTE);
        $I->seeElement(AcceptanceTester::CONT_RESULTADOS);

        $I->seeElement(AcceptanceTester::ITEM_LISTADO);

        $I->seeElementInDOM(AcceptanceTester::MODAL);
        $I->dontSeeElement(AcceptanceTester::MODAL);

        $I->verificarSinErrores();
    }

    #[Group('validacion')]
    public function probarValidacionesDeFormulario(AcceptanceTester $I): void
    {
        $this->abrirModalRegistro($I);

        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('Tiene que ingresar una cedula valida', 5);
        $this->cerrarAlertaError($I);

        $I->fillField(AcceptanceTester::INPUT_CEDULA, '12345');
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('Tiene que ingresar una cedula valida', 5);
        $this->cerrarAlertaError($I);

        $I->fillField(AcceptanceTester::INPUT_CEDULA, $this->usuarioBase['cedula']);
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, 'Ab');
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('Tiene que ingresar un nombre valido', 5);
        $this->cerrarAlertaError($I);

        $I->fillField(AcceptanceTester::INPUT_NOMBRE, $this->usuarioBase['nombre']);
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('Tiene que ingresar un apellido valido', 5);
        $this->cerrarAlertaError($I);

        $I->fillField(AcceptanceTester::INPUT_APELLIDO, $this->usuarioBase['apellido']);
        $I->fillField(AcceptanceTester::INPUT_TELEFONO, '0414123');
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('Tiene que ingresar un telefono valido', 5);
        $this->cerrarAlertaError($I);

        $I->fillField(AcceptanceTester::INPUT_TELEFONO, $this->usuarioBase['telefono']);
        $I->fillField(self::INPUT_CONTRASENA, 'clave123');
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('Tiene que ingresar una contraseña valido', 5);
        $this->cerrarAlertaError($I);

        $I->fillField(AcceptanceTester::INPUT_CEDULA, '123456789');
        $I->executeJS("$('#cedula').trigger('input');");
        $I->seeInField(AcceptanceTester::INPUT_CEDULA, '12345678');

        $I->fillField(AcceptanceTester::INPUT_TELEFONO, '04141234567');
        $I->executeJS("$('#telefono').trigger('input');");
        $I->seeInField(AcceptanceTester::INPUT_TELEFONO, '0414-1234567');

        $I->click(AcceptanceTester::MODAL_CERRAR);
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        $I->verificarSinErrores();
    }

    #[Group('registrar', 'crud')]
    public function probarRegistrarUsuario(AcceptanceTester $I): void
    {
        $this->eliminarUsuarioSiExiste($this->usuarioBase['cedula']);
        $this->recargarModulo($I);

        $this->abrirModalRegistro($I);
        $this->llenarFormulario($I, $this->usuarioBase);

        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere registrar este usuario?', 5);
        $I->confirmarSweetAlert();

        $I->waitForText('Registro Exitoso', 10);
        $I->aceptarSweetAlert();
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        $I->buscarEnListado($this->usuarioBase['cedula']);
        $I->waitForText($this->usuarioBase['cedula'], 5, AcceptanceTester::CONT_RESULTADOS);
        $I->see($this->usuarioBase['nombre'] . ' ' . $this->usuarioBase['apellido'], AcceptanceTester::CONT_RESULTADOS);
        $I->see($this->usuarioBase['correo'], AcceptanceTester::CONT_RESULTADOS);
        $I->see('Entrenador', AcceptanceTester::CONT_RESULTADOS);

        $hash = $this->obtenerHashContrasena($this->usuarioBase['cedula']);
        if ($hash !== null) {
            Assert::assertNotEquals($this->usuarioBase['contrasena'], $hash);
            Assert::assertStringStartsWith('$2y$', $hash);
            Assert::assertTrue(password_verify($this->usuarioBase['contrasena'], $hash));
        }

        $I->verificarSinErrores();
    }

    #[Group('validacion', 'duplicados')]
    public function probarRechazarCedulaDuplicada(AcceptanceTester $I): void
    {
        $this->asegurarUsuarioExiste($this->usuarioBase);

        $duplicado = $this->usuarioBase;
        $duplicado['telefono'] = '0414-9990001';
        $duplicado['correo']   = 'cedula.duplicada@gmail.com';

        $this->abrirModalRegistro($I);
        $this->llenarFormulario($I, $duplicado);

        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere registrar este usuario?', 5);
        $I->confirmarSweetAlert();

        $I->waitForText('La cedula ingresada ya pertenece a un usuario registrado.', 10);
        $I->aceptarSweetAlert();

        $I->seeElement(AcceptanceTester::MODAL);
        $I->click(AcceptanceTester::MODAL_CERRAR);
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        $I->verificarSinErrores();
    }

    #[Group('validacion', 'duplicados')]
    public function probarRechazarCorreoDuplicado(AcceptanceTester $I): void
    {
        $this->asegurarUsuarioExiste($this->usuarioBase);

        $duplicado = $this->usuarioBase;
        $duplicado['cedula']   = '30999001';
        $duplicado['telefono'] = '0414-9990002';

        $this->eliminarUsuarioSiExiste($duplicado['cedula']);

        $this->abrirModalRegistro($I);
        $this->llenarFormulario($I, $duplicado);

        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere registrar este usuario?', 5);
        $I->confirmarSweetAlert();

        $I->waitForText('El correo ingresado ya pertenece a un usuario registrado.', 10);
        $I->aceptarSweetAlert();

        $I->click(AcceptanceTester::MODAL_CERRAR);
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        $I->buscarEnListado($duplicado['cedula']);
        $I->waitForText('No se encontraron registros', 5, AcceptanceTester::CONT_RESULTADOS);

        $I->verificarSinErrores();
    }

    #[Group('buscador')]
    public function probarBuscadorUsuarios(AcceptanceTester $I): void
    {
        $this->asegurarUsuarioExiste($this->usuarioBase);
        $this->recargarModulo($I);

        $I->buscarEnListado($this->usuarioBase['cedula']);
        $I->waitForText($this->usuarioBase['nombre'], 5, AcceptanceTester::CONT_RESULTADOS);

        $I->buscarEnListado($this->usuarioBase['correo']);
        $I->waitForText($this->usuarioBase['cedula'], 5, AcceptanceTester::CONT_RESULTADOS);

        $I->buscarEnListado('UsuarioInexistenteXYZ999');
        $I->waitForElementVisible(AcceptanceTester::LISTADO_VACIO, 5);
        $I->see('No se encontraron registros', AcceptanceTester::LISTADO_VACIO);

        $I->limpiarBuscador();
        $I->dontSee('No se encontraron registros', AcceptanceTester::CONT_RESULTADOS);

        $I->verificarSinErrores();
    }

    #[Group('detalle')]
    public function probarVerDetalleUsuario(AcceptanceTester $I): void
    {
        $this->asegurarUsuarioExiste($this->usuarioBase);
        $this->recargarModulo($I);

        $I->buscarEnListado($this->usuarioBase['cedula']);
        $I->waitForText($this->usuarioBase['nombre'], 5, AcceptanceTester::CONT_RESULTADOS);

        $I->executeJS("document.querySelector('" . AcceptanceTester::ITEM_LISTADO . "').click();");
        $I->wait(1);

        $I->waitForElementVisible(self::DETALLE_OCULTO . ' .detalle_card', 5);
        $I->see('Ultimo Ingreso', self::DETALLE_OCULTO);
        $I->see('Sin Ingresos Al Sistema', self::DETALLE_OCULTO);

        $I->executeJS("document.querySelector('" . AcceptanceTester::ITEM_LISTADO . "').click();");
        $I->wait(1);

        $I->verificarSinErrores();
    }

    #[Group('modificar', 'crud')]
    public function probarModificarUsuario(AcceptanceTester $I): void
    {
        $this->asegurarUsuarioExiste($this->usuarioBase);
        $this->recargarModulo($I);

        $I->buscarEnListado($this->usuarioBase['cedula']);
        $I->waitForText($this->usuarioBase['cedula'], 5, AcceptanceTester::CONT_RESULTADOS);

        $I->clickModificar();
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->waitForText('Modificar Usuario', 5, AcceptanceTester::MODAL_TITULO);
        $I->seeInField(AcceptanceTester::INPUT_CEDULA, $this->usuarioBase['cedula']);
        $I->seeInField(AcceptanceTester::INPUT_CORREO, $this->usuarioBase['correo']);
        $I->seeElement(self::SELECT_ROLES . ' + .select2');

        $I->fillField(AcceptanceTester::INPUT_NOMBRE, $this->nombreModificado);
        $I->fillField(AcceptanceTester::INPUT_APELLIDO, $this->apellidoModificado);

        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere modificar este usuario?', 5);
        $I->confirmarSweetAlert();

        $I->waitForText('Modificacion Exitosa', 10);
        $I->aceptarSweetAlert();
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        $I->buscarEnListado($this->usuarioBase['cedula']);
        $I->waitForText($this->nombreModificado . ' ' . $this->apellidoModificado, 5, AcceptanceTester::CONT_RESULTADOS);

        $this->restaurarDatosUsuario($this->usuarioBase);

        $I->verificarSinErrores();
    }

    #[Group('bloquear')]
    public function probarBloquearYDesbloquearUsuario(AcceptanceTester $I): void
    {
        $this->asegurarUsuarioExiste($this->usuarioBase);
        $this->establecerBloqueo($this->usuarioBase['cedula'], 1);
        $this->recargarModulo($I);

        $I->buscarEnListado($this->usuarioBase['cedula']);
        $I->waitForText($this->usuarioBase['cedula'], 5, AcceptanceTester::CONT_RESULTADOS);

        $I->waitForElementVisible(self::BTN_DESBLOQUEADO, 5);
        $I->despejarOverlays();
        $I->executeJS("document.querySelector('" . self::BTN_DESBLOQUEADO . "').click();");
        $I->waitForText('¿Está seguro que quiere bloquear este Usuario?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Usuario bloqueado exitosamente.', 10);
        $I->aceptarSweetAlert();

        $I->waitForElementVisible(self::BTN_BLOQUEADO, 5);
        $I->seeElement(self::BTN_BLOQUEADO . ' i.fi-sr-lock');
        Assert::assertSame(2, $this->obtenerEstadoBloqueo($this->usuarioBase['cedula']) ?? 2);

        $I->despejarOverlays();
        $I->executeJS("document.querySelector('" . self::BTN_BLOQUEADO . "').click();");
        $I->waitForText('¿Está seguro que quiere desbloquear este Usuario?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Usuario desbloqueado exitosamente.', 10);
        $I->aceptarSweetAlert();

        $I->waitForElementVisible(self::BTN_DESBLOQUEADO, 5);
        $I->seeElement(self::BTN_DESBLOQUEADO . ' i.fi-sr-unlock');
        Assert::assertSame(1, $this->obtenerEstadoBloqueo($this->usuarioBase['cedula']) ?? 1);

        $I->verificarSinErrores();
    }

    #[Group('permisos')]
    public function probarGestionarPermisosUsuario(AcceptanceTester $I): void
    {
        $this->asegurarUsuarioExiste($this->usuarioBase);
        $this->recargarModulo($I);

        $I->buscarEnListado($this->usuarioBase['cedula']);
        $I->waitForText($this->usuarioBase['cedula'], 5, AcceptanceTester::CONT_RESULTADOS);

        $I->waitForElementVisible(self::BTN_PERMISOS, 5);
        $I->despejarOverlays();
        $I->executeJS("document.querySelector('" . self::BTN_PERMISOS . "').click();");
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->waitForText('Permisos del Usuario', 5, AcceptanceTester::MODAL_TITULO);

        $I->seeElement(self::FILA_PERMISOS);
        $I->dontSeeElement(AcceptanceTester::INPUT_CEDULA);
        $I->dontSeeElement(self::INPUT_CONTRASENA);
        $I->see('Guardar Permisos', AcceptanceTester::BTN_PROCESO);

        $I->seeElement(self::TAB_ASIGNADOS . '.activa');
        $I->click(self::TAB_NO_ASIGNADOS);
        $I->waitForElementVisible(self::CONT_TAB_NO_ASIG, 5);
        $I->seeElement(self::TAB_NO_ASIGNADOS . '.activa');
        $I->click(self::TAB_ASIGNADOS);
        $I->waitForElementVisible(self::CONT_TAB_ASIGNADOS, 5);

        $I->fillField(self::BUSCADOR_PERMISOS, 'ModuloInexistenteXYZ');
        $I->executeJS("$('#buscador_permisos').trigger('input');");
        $I->waitForText('No hay permisos asignados para esta búsqueda', 5, self::CONT_TAB_ASIGNADOS);
        $I->fillField(self::BUSCADOR_PERMISOS, '');
        $I->executeJS("$('#buscador_permisos').trigger('input');");

        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere guardar los permisos?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Permisos Guardados', 10);
        $I->aceptarSweetAlert();
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        $I->verificarSinErrores();
    }

    #[Group('reporte')]
    public function probarGenerarReporteUsuarios(AcceptanceTester $I): void
    {
        $I->click(AcceptanceTester::BTN_REPORTE);
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->see('Generar Reporte', AcceptanceTester::MODAL_TITULO);

        $I->seeElement(AcceptanceTester::INPUT_CEDULA);
        $I->seeElement(AcceptanceTester::INPUT_NOMBRE);
        $I->seeElement(AcceptanceTester::INPUT_APELLIDO);
        $I->dontSeeElement(self::INPUT_CONTRASENA);
        $I->dontSeeElement(AcceptanceTester::INPUT_CORREO);
        $I->dontSeeElement(AcceptanceTester::INPUT_TELEFONO);

        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿En qué formato desea generar el reporte?', 5);
        $I->see('PDF');
        $I->see('Excel');

        $I->cancelarSweetAlert();

        $I->click(AcceptanceTester::MODAL_CERRAR);
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        $I->verificarSinErrores();
    }

    #[Group('eliminar', 'crud')]
    public function probarEliminarUsuario(AcceptanceTester $I): void
    {
        $this->asegurarUsuarioExiste($this->usuarioEliminar);
        $this->recargarModulo($I);

        $I->buscarEnListado($this->usuarioEliminar['cedula']);
        $I->waitForText($this->usuarioEliminar['cedula'], 5, AcceptanceTester::CONT_RESULTADOS);

        $I->clickEliminar();
        $I->waitForText('¿Está seguro que quiere eliminar este Usuario?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Eliminacion Exitosa', 10);
        $I->aceptarSweetAlert();

        $I->buscarEnListado($this->usuarioEliminar['cedula']);
        $I->waitForText('No se encontraron registros', 5, AcceptanceTester::CONT_RESULTADOS);

        $estatus = $this->obtenerEstatus($this->usuarioEliminar['cedula']);
        if ($estatus !== null) {
            Assert::assertSame(0, $estatus);
        }

        $I->verificarSinErrores();
    }

    #[Group('validacion', 'seguridad')]
    public function probarProtegerSuperUsuario(AcceptanceTester $I): void
    {
        $I->buscarEnListado('12345678');
        $I->waitForText('12345678', 5, AcceptanceTester::CONT_RESULTADOS);

        $I->clickEliminar();
        $I->waitForText('¿Está seguro que quiere eliminar este Usuario?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('No puedes eliminar tu propio usuario.', 10);
        $I->aceptarSweetAlert();

        $I->waitForElementVisible(self::BTN_DESBLOQUEADO, 5);
        $I->despejarOverlays();
        $I->executeJS("document.querySelector('" . self::BTN_DESBLOQUEADO . "').click();");
        $I->waitForText('¿Está seguro que quiere bloquear este Usuario?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('No puedes bloquear tu propio usuario.', 10);
        $I->aceptarSweetAlert();

        $I->buscarEnListado('12345678');
        $I->waitForText('12345678', 5, AcceptanceTester::CONT_RESULTADOS);
        $I->seeElement(self::BTN_DESBLOQUEADO);

        $I->verificarSinErrores();
    }

    #[Group('seguridad', 'acceso')]
    public function probarAccesoDenegadoRolSinPermisos(AcceptanceTester $I): void
    {
        $this->asegurarUsuarioExiste($this->usuarioSinAcceso);
        $this->establecerBloqueo($this->usuarioSinAcceso['cedula'], 1);

        $I->executeInSelenium(function (\Facebook\WebDriver\Remote\RemoteWebDriver $wd) {
            $wd->manage()->deleteAllCookies();
        });

        $I->amOnPage('/');
        $I->waitForElement('input[name="cedula"]', 5);
        $I->fillField('cedula', $this->usuarioSinAcceso['cedula']);
        $I->fillField('contraseña', $this->usuarioSinAcceso['contrasena']);
        $I->click('Iniciar Sesión');
        $I->waitForText('Inicio Validado', 10);

        $I->amOnPage(self::RUTA_MODULO);

        $I->waitForText('Acceso denegado', 10);
        $I->seeInCurrentUrl('Principal');
        $I->dontSeeElement(AcceptanceTester::BTN_NUEVO);

        $I->executeInSelenium(function (\Facebook\WebDriver\Remote\RemoteWebDriver $wd) {
            $wd->manage()->deleteAllCookies();
        });
    }

    #[Group('cicloCompleto', 'e2e')]
    public function probarCicloCompletoUsuario(AcceptanceTester $I): void
    {
        $u = $this->usuarioCiclo;
        $this->eliminarUsuarioSiExiste($u['cedula']);
        $this->recargarModulo($I);

        $this->abrirModalRegistro($I);
        $this->llenarFormulario($I, $u);
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere registrar este usuario?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Registro Exitoso', 10);
        $I->aceptarSweetAlert();
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        $I->buscarEnListado($u['cedula']);
        $I->waitForText($u['nombre'] . ' ' . $u['apellido'], 5, AcceptanceTester::CONT_RESULTADOS);

        $I->clickModificar();
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->waitForText('Modificar Usuario', 5, AcceptanceTester::MODAL_TITULO);
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, $this->nombreModificado);
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere modificar este usuario?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Modificacion Exitosa', 10);
        $I->aceptarSweetAlert();
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        $I->buscarEnListado($u['cedula']);
        $I->waitForText($this->nombreModificado . ' ' . $u['apellido'], 5, AcceptanceTester::CONT_RESULTADOS);

        $I->waitForElementVisible(self::BTN_DESBLOQUEADO, 5);
        $I->despejarOverlays();
        $I->executeJS("document.querySelector('" . self::BTN_DESBLOQUEADO . "').click();");
        $I->waitForText('¿Está seguro que quiere bloquear este Usuario?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Usuario bloqueado exitosamente.', 10);
        $I->aceptarSweetAlert();
        $I->waitForElementVisible(self::BTN_BLOQUEADO, 5);

        $I->clickEliminar();
        $I->waitForText('¿Está seguro que quiere eliminar este Usuario?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Eliminacion Exitosa', 10);
        $I->aceptarSweetAlert();

        $I->buscarEnListado($u['cedula']);
        $I->waitForText('No se encontraron registros', 5, AcceptanceTester::CONT_RESULTADOS);

        $I->limpiarBuscador();
        $I->verificarSinErrores();
    }

    private function recargarModulo(AcceptanceTester $I): void
    {
        $I->amOnPage(self::RUTA_MODULO);
        $I->waitForElementVisible(AcceptanceTester::INPUT_BUSQUEDA, 10);
        $I->waitForElementNotVisible('#loader', 10);
        $I->despejarOverlays();
    }

    private function cerrarAlertaError(AcceptanceTester $I): void
    {
        $I->aceptarSweetAlert();
        $I->waitForJS("return document.querySelector('.swal2-container') === null;", 5);
        $I->despejarOverlays();
    }

    private function abrirModalRegistro(AcceptanceTester $I): void
    {
        $I->click(AcceptanceTester::BTN_NUEVO);
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->see('Registrar Usuario', AcceptanceTester::MODAL_TITULO);
        $I->see('Registrar Usuario', AcceptanceTester::BTN_PROCESO);
        $I->seeElement(self::INPUT_CONTRASENA);
        $I->seeElementInDOM(self::INPUT_FOTO);
        $I->dontSeeElement(self::FILA_PERMISOS);

        $I->waitForJS("return $('#roles option').length > 0;", 5);
    }

    private function llenarFormulario(AcceptanceTester $I, array $u): void
    {
        $I->fillField(AcceptanceTester::INPUT_CEDULA, $u['cedula']);
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, $u['nombre']);
        $I->fillField(AcceptanceTester::INPUT_APELLIDO, $u['apellido']);
        $I->fillField(AcceptanceTester::INPUT_TELEFONO, $u['telefono']);
        $I->fillField(AcceptanceTester::INPUT_CORREO, $u['correo']);
        $I->fillField(self::INPUT_CONTRASENA, $u['contrasena']);

        $I->executeJS("$('#roles').val('" . self::ROL_PRUEBA_ID . "').trigger('change');");
    }

    private function conexion(): ?\PDO
    {
        try {
            $pdo = new \PDO(
                'mysql:host=' . _DB_HOST_SG_ . ';dbname=' . _DB_NAME_SG_ . ';charset=utf8',
                _DB_USER_SG_,
                _DB_PASS_SG_
            );
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function buscarUsuario(string $cedula): ?array
    {
        $pdo = $this->conexion();
        if ($pdo === null) {
            return null;
        }
        $st = $pdo->prepare('SELECT * FROM usuarios WHERE cedulaUsuario = :c LIMIT 1');
        $st->execute([':c' => $cedula]);
        $fila = $st->fetch(\PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    private function obtenerHashContrasena(string $cedula): ?string
    {
        $u = $this->buscarUsuario($cedula);
        return $u['pass_hash'] ?? null;
    }

    private function obtenerEstatus(string $cedula): ?int
    {
        $u = $this->buscarUsuario($cedula);
        return $u !== null ? (int)$u['estatus'] : null;
    }

    private function obtenerEstadoBloqueo(string $cedula): ?int
    {
        $u = $this->buscarUsuario($cedula);
        return $u !== null ? (int)$u['bloqueo'] : null;
    }

    private function establecerBloqueo(string $cedula, int $estado): void
    {
        try {
            $pdo = $this->conexion();
            $pdo?->prepare('UPDATE usuarios SET bloqueo = :b, intentos_fallidos = 0 WHERE cedulaUsuario = :c')
                ->execute([':b' => $estado, ':c' => $cedula]);
        } catch (\Throwable $e) {
        }
    }

    private function eliminarUsuarioSiExiste(string $cedula): void
    {
        try {
            $pdo = $this->conexion();
            $pdo?->prepare('UPDATE usuarios SET estatus = 0, bloqueo = 1, intentos_fallidos = 0 WHERE cedulaUsuario = :c')
                ->execute([':c' => $cedula]);
        } catch (\Throwable $e) {
        }
    }

    private function asegurarUsuarioExiste(array $u): void
    {
        try {
            $existente = $this->buscarUsuario($u['cedula']);
            if ($existente !== null) {
                $this->restaurarDatosUsuario($u);
                return;
            }
            $modelo = new \App\modelo\ModeloUsuarios();
            $modelo->procesarDatos([
                'accion'     => 'incluir',
                'cedula'     => $u['cedula'],
                'nombre'     => $u['nombre'],
                'apellido'   => $u['apellido'],
                'telefono'   => $u['telefono'],
                'correo'     => $u['correo'],
                'contraseña' => $u['contrasena'],
                'roles_id'   => self::ROL_PRUEBA_ID,
                'foto'       => 'default.png',
            ]);
        } catch (\Throwable $e) {
        }
    }

    private function restaurarDatosUsuario(array $u): void
    {
        try {
            $pdo = $this->conexion();
            $pdo?->prepare(
                'UPDATE usuarios SET nombreUsuario = :n, apellidoUsuario = :a, telefonoUsuario = :t,
                    correo = :co, pass_hash = :p, id_rol = :r, estatus = 1, bloqueo = 1, intentos_fallidos = 0
                 WHERE cedulaUsuario = :c'
            )->execute([
                ':n'  => $u['nombre'],
                ':a'  => $u['apellido'],
                ':t'  => $u['telefono'],
                ':co' => $u['correo'],
                ':p'  => password_hash($u['contrasena'], PASSWORD_BCRYPT),
                ':r'  => self::ROL_PRUEBA_ID,
                ':c'  => $u['cedula'],
            ]);
        } catch (\Throwable $e) {
        }
    }
}
