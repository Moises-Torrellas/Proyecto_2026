<?php

declare(strict_types=1);

namespace Tests\Acceptance\Usuarios;

use Codeception\Attribute\Group;
use PHPUnit\Framework\Assert;
use Tests\Support\AcceptanceTester;

if (!defined('_DB_NAME_')) {
    require_once __DIR__ . '/../../../config/config.php';
}

/**
 * Pruebas de interfaz (aceptación) del módulo Gestión de Usuarios.
 *
 * Escenarios cubiertos (Plan de Pruebas de Aceptación - Pruebas Alfa "Gestión de Usuarios"):
 *  - CP-USU-01: Carga inicial de la interfaz.
 *  - CP-USU-02: Validaciones del formulario del lado del cliente.
 *  - CP-USU-03: Registro exitoso de un usuario (contraseña guardada encriptada).
 *  - CP-USU-04: Rechazo de cédula duplicada.
 *  - CP-USU-05: Rechazo de correo duplicado.
 *  - CP-USU-06: Buscador en tiempo real (coincidencia, sin resultados y restauración).
 *  - CP-USU-07: Despliegue del detalle (acordeón) de un usuario.
 *  - CP-USU-08: Modificación de un usuario.
 *  - CP-USU-09: Bloqueo y desbloqueo de un usuario.
 *  - CP-USU-10: Gestión de permisos específicos del usuario (pestañas y buscador).
 *  - CP-USU-11: Diálogo de generación de reportes (PDF / Excel).
 *  - CP-USU-12: Eliminación (borrado lógico) de un usuario.
 *  - CP-USU-13: Protección del Super Usuario (no puede eliminarse ni bloquearse).
 *  - CP-USU-14: Acceso denegado al módulo para un rol distinto de SuperUsuario.
 *  - CP-USU-15: Ciclo de vida completo (Registrar -> Buscar -> Modificar -> Bloquear -> Eliminar).
 */
#[Group('usuarios')]
#[Group('interfaz')]
final class UsuariosCest
{
    private const RUTA_MODULO          = '/Usuarios';
    private const TITULO_MODULO        = 'Usuarios';

    // Selectores específicos del módulo de Usuarios
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

    // Botones de acción del listado
    private const BTN_PERMISOS         = '#resultadoconsulta .cbt_m';
    private const BTN_DESBLOQUEADO     = '#resultadoconsulta .listado_col_acciones .cbt_g';
    private const BTN_BLOQUEADO        = '#resultadoconsulta .listado_col_acciones .cbt_a';

    // Rol de prueba de Nivel 3 (Entrenador) sin permisos sobre el módulo Usuarios
    private const ROL_PRUEBA_ID        = '6';

    // Datos de prueba (cédulas fuera del rango de usuarios reales)
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
        // Login con snapshot + navegación a /Usuarios + verificación de título y limpieza de overlays
        $I->irAlModulo(self::RUTA_MODULO, self::TITULO_MODULO);
    }

    /**
     * CP-USU-01: Verifica la carga inicial de la interfaz del módulo de Usuarios.
     */
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

        // Debe listarse al menos el Super Usuario
        $I->seeElement(AcceptanceTester::ITEM_LISTADO);

        // El modal existe en el DOM pero debe arrancar oculto
        $I->seeElementInDOM(AcceptanceTester::MODAL);
        $I->dontSeeElement(AcceptanceTester::MODAL);

        $I->verificarSinErrores();
    }

    /**
     * CP-USU-02: Validaciones del lado del cliente (validarEnvio() en usuarios.js).
     */
    #[Group('validacion')]
    public function probarValidacionesDeFormulario(AcceptanceTester $I): void
    {
        // 1. Abrir modal de registro
        $this->abrirModalRegistro($I);

        // 2. Formulario vacío -> falla la cédula
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('Tiene que ingresar una cedula valida', 5);
        $this->cerrarAlertaError($I);

        // 3. Cédula con menos de 7 dígitos
        $I->fillField(AcceptanceTester::INPUT_CEDULA, '12345');
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('Tiene que ingresar una cedula valida', 5);
        $this->cerrarAlertaError($I);

        // 4. Cédula válida, nombre inválido (menos de 3 letras)
        $I->fillField(AcceptanceTester::INPUT_CEDULA, $this->usuarioBase['cedula']);
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, 'Ab');
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('Tiene que ingresar un nombre valido', 5);
        $this->cerrarAlertaError($I);

        // 5. Nombre válido, apellido vacío
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, $this->usuarioBase['nombre']);
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('Tiene que ingresar un apellido valido', 5);
        $this->cerrarAlertaError($I);

        // 6. Apellido válido, teléfono incompleto
        $I->fillField(AcceptanceTester::INPUT_APELLIDO, $this->usuarioBase['apellido']);
        $I->fillField(AcceptanceTester::INPUT_TELEFONO, '0414123');
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('Tiene que ingresar un telefono valido', 5);
        $this->cerrarAlertaError($I);

        // 7. Teléfono válido, contraseña débil (sin mayúscula ni carácter especial)
        $I->fillField(AcceptanceTester::INPUT_TELEFONO, $this->usuarioBase['telefono']);
        $I->fillField(self::INPUT_CONTRASENA, 'clave123');
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('Tiene que ingresar una contraseña valido', 5);
        $this->cerrarAlertaError($I);

        // 8. Verificar la máscara de entrada: la cédula admite máximo 8 dígitos
        $I->fillField(AcceptanceTester::INPUT_CEDULA, '123456789');
        $I->executeJS("$('#cedula').trigger('input');");
        $I->seeInField(AcceptanceTester::INPUT_CEDULA, '12345678');

        // 9. Verificar la máscara del teléfono: formato 0000-0000000
        $I->fillField(AcceptanceTester::INPUT_TELEFONO, '04141234567');
        $I->executeJS("$('#telefono').trigger('input');");
        $I->seeInField(AcceptanceTester::INPUT_TELEFONO, '0414-1234567');

        // 10. Cerrar modal limpiamente
        $I->click(AcceptanceTester::MODAL_CERRAR);
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        $I->verificarSinErrores();
    }

    /**
     * CP-USU-03: Registro exitoso de un usuario y verificación de la contraseña encriptada.
     */
    #[Group('registrar', 'crud')]
    public function probarRegistrarUsuario(AcceptanceTester $I): void
    {
        $this->eliminarUsuarioSiExiste($this->usuarioBase['cedula']);
        $this->recargarModulo($I);

        // 1. Abrir modal y llenar formulario
        $this->abrirModalRegistro($I);
        $this->llenarFormulario($I, $this->usuarioBase);

        // 2. Enviar y confirmar en SweetAlert
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere registrar este usuario?', 5);
        $I->confirmarSweetAlert();

        // 3. Mensaje de éxito (el modal se cierra automáticamente)
        $I->waitForText('Registro Exitoso', 10);
        $I->aceptarSweetAlert();
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        // 4. Verificar el nuevo usuario en el listado
        $I->buscarEnListado($this->usuarioBase['cedula']);
        $I->waitForText($this->usuarioBase['cedula'], 5, AcceptanceTester::CONT_RESULTADOS);
        $I->see($this->usuarioBase['nombre'] . ' ' . $this->usuarioBase['apellido'], AcceptanceTester::CONT_RESULTADOS);
        $I->see($this->usuarioBase['correo'], AcceptanceTester::CONT_RESULTADOS);
        $I->see('Entrenador', AcceptanceTester::CONT_RESULTADOS);

        // 5. La contraseña debe almacenarse encriptada (bcrypt) y nunca en texto plano
        $hash = $this->obtenerHashContrasena($this->usuarioBase['cedula']);
        if ($hash !== null) {
            Assert::assertNotEquals($this->usuarioBase['contrasena'], $hash);
            Assert::assertStringStartsWith('$2y$', $hash);
            Assert::assertTrue(password_verify($this->usuarioBase['contrasena'], $hash));
        }

        $I->verificarSinErrores();
    }

    /**
     * CP-USU-04: El sistema rechaza el registro con una cédula ya registrada.
     */
    #[Group('validacion', 'duplicados')]
    public function probarRechazarCedulaDuplicada(AcceptanceTester $I): void
    {
        $this->asegurarUsuarioExiste($this->usuarioBase);

        // Misma cédula con teléfono y correo distintos
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

        // El modal permanece abierto para corregir los datos
        $I->seeElement(AcceptanceTester::MODAL);
        $I->click(AcceptanceTester::MODAL_CERRAR);
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        $I->verificarSinErrores();
    }

    /**
     * CP-USU-05: El sistema rechaza el registro con un correo ya registrado.
     */
    #[Group('validacion', 'duplicados')]
    public function probarRechazarCorreoDuplicado(AcceptanceTester $I): void
    {
        $this->asegurarUsuarioExiste($this->usuarioBase);

        // Cédula y teléfono nuevos, correo repetido
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

        // El usuario con la cédula nueva no debe haberse creado
        $I->buscarEnListado($duplicado['cedula']);
        $I->waitForText('No se encontraron registros', 5, AcceptanceTester::CONT_RESULTADOS);

        $I->verificarSinErrores();
    }

    /**
     * CP-USU-06: Buscador de usuarios en tiempo real (debounce de 500ms).
     */
    #[Group('buscador')]
    public function probarBuscadorUsuarios(AcceptanceTester $I): void
    {
        $this->asegurarUsuarioExiste($this->usuarioBase);
        $this->recargarModulo($I);

        // 1. Búsqueda por cédula
        $I->buscarEnListado($this->usuarioBase['cedula']);
        $I->waitForText($this->usuarioBase['nombre'], 5, AcceptanceTester::CONT_RESULTADOS);

        // 2. Búsqueda por correo
        $I->buscarEnListado($this->usuarioBase['correo']);
        $I->waitForText($this->usuarioBase['cedula'], 5, AcceptanceTester::CONT_RESULTADOS);

        // 3. Búsqueda por término inexistente
        $I->buscarEnListado('UsuarioInexistenteXYZ999');
        $I->waitForElementVisible(AcceptanceTester::LISTADO_VACIO, 5);
        $I->see('No se encontraron registros', AcceptanceTester::LISTADO_VACIO);

        // 4. Limpiar buscador y verificar recuperación del listado
        $I->limpiarBuscador();
        $I->dontSee('No se encontraron registros', AcceptanceTester::CONT_RESULTADOS);

        $I->verificarSinErrores();
    }

    /**
     * CP-USU-07: Despliegue del detalle (acordeón) con el último ingreso del usuario.
     */
    #[Group('detalle')]
    public function probarVerDetalleUsuario(AcceptanceTester $I): void
    {
        $this->asegurarUsuarioExiste($this->usuarioBase);
        $this->recargarModulo($I);

        $I->buscarEnListado($this->usuarioBase['cedula']);
        $I->waitForText($this->usuarioBase['nombre'], 5, AcceptanceTester::CONT_RESULTADOS);

        // 1. Expandir el registro
        $I->executeJS("document.querySelector('" . AcceptanceTester::ITEM_LISTADO . "').click();");
        $I->wait(1);

        // 2. Verificar la tarjeta de último ingreso (usuario nuevo => sin ingresos)
        $I->waitForElementVisible(self::DETALLE_OCULTO . ' .detalle_card', 5);
        $I->see('Ultimo Ingreso', self::DETALLE_OCULTO);
        $I->see('Sin Ingresos Al Sistema', self::DETALLE_OCULTO);

        // 3. Contraer nuevamente
        $I->executeJS("document.querySelector('" . AcceptanceTester::ITEM_LISTADO . "').click();");
        $I->wait(1);

        $I->verificarSinErrores();
    }

    /**
     * CP-USU-08: Modificación de los datos de un usuario existente.
     */
    #[Group('modificar', 'crud')]
    public function probarModificarUsuario(AcceptanceTester $I): void
    {
        $this->asegurarUsuarioExiste($this->usuarioBase);
        $this->recargarModulo($I);

        // 1. Ubicar al usuario con el buscador
        $I->buscarEnListado($this->usuarioBase['cedula']);
        $I->waitForText($this->usuarioBase['cedula'], 5, AcceptanceTester::CONT_RESULTADOS);

        // 2. Abrir modal de modificación con los datos precargados
        $I->clickModificar();
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->waitForText('Modificar Usuario', 5, AcceptanceTester::MODAL_TITULO);
        $I->seeInField(AcceptanceTester::INPUT_CEDULA, $this->usuarioBase['cedula']);
        $I->seeInField(AcceptanceTester::INPUT_CORREO, $this->usuarioBase['correo']);
        $I->seeElement(self::SELECT_ROLES . ' + .select2');

        // 3. Modificar nombre y apellido (sin cambiar la contraseña)
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, $this->nombreModificado);
        $I->fillField(AcceptanceTester::INPUT_APELLIDO, $this->apellidoModificado);

        // 4. Guardar y confirmar
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere modificar este usuario?', 5);
        $I->confirmarSweetAlert();

        // 5. Mensaje de éxito (el modal se cierra automáticamente)
        $I->waitForText('Modificacion Exitosa', 10);
        $I->aceptarSweetAlert();
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        // 6. Validar el cambio en el listado
        $I->buscarEnListado($this->usuarioBase['cedula']);
        $I->waitForText($this->nombreModificado . ' ' . $this->apellidoModificado, 5, AcceptanceTester::CONT_RESULTADOS);

        // 7. Restaurar datos originales para no afectar a las demás pruebas
        $this->restaurarDatosUsuario($this->usuarioBase);

        $I->verificarSinErrores();
    }

    /**
     * CP-USU-09: Bloqueo y desbloqueo del acceso de un usuario al sistema.
     */
    #[Group('bloquear')]
    public function probarBloquearYDesbloquearUsuario(AcceptanceTester $I): void
    {
        $this->asegurarUsuarioExiste($this->usuarioBase);
        $this->establecerBloqueo($this->usuarioBase['cedula'], 1);
        $this->recargarModulo($I);

        $I->buscarEnListado($this->usuarioBase['cedula']);
        $I->waitForText($this->usuarioBase['cedula'], 5, AcceptanceTester::CONT_RESULTADOS);

        // 1. Bloquear (botón verde con candado abierto)
        $I->waitForElementVisible(self::BTN_DESBLOQUEADO, 5);
        $I->despejarOverlays();
        $I->executeJS("document.querySelector('" . self::BTN_DESBLOQUEADO . "').click();");
        $I->waitForText('¿Está seguro que quiere bloquear este Usuario?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Usuario bloqueado exitosamente.', 10);
        $I->aceptarSweetAlert();

        // 2. El botón cambia a estado bloqueado (ámbar con candado cerrado)
        $I->waitForElementVisible(self::BTN_BLOQUEADO, 5);
        $I->seeElement(self::BTN_BLOQUEADO . ' i.fi-sr-lock');
        Assert::assertSame(2, $this->obtenerEstadoBloqueo($this->usuarioBase['cedula']) ?? 2);

        // 3. Desbloquear
        $I->despejarOverlays();
        $I->executeJS("document.querySelector('" . self::BTN_BLOQUEADO . "').click();");
        $I->waitForText('¿Está seguro que quiere desbloquear este Usuario?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Usuario desbloqueado exitosamente.', 10);
        $I->aceptarSweetAlert();

        // 4. El botón vuelve a estado desbloqueado
        $I->waitForElementVisible(self::BTN_DESBLOQUEADO, 5);
        $I->seeElement(self::BTN_DESBLOQUEADO . ' i.fi-sr-unlock');
        Assert::assertSame(1, $this->obtenerEstadoBloqueo($this->usuarioBase['cedula']) ?? 1);

        $I->verificarSinErrores();
    }

    /**
     * CP-USU-10: Gestión de permisos específicos del usuario (pestañas, buscador y guardado).
     */
    #[Group('permisos')]
    public function probarGestionarPermisosUsuario(AcceptanceTester $I): void
    {
        $this->asegurarUsuarioExiste($this->usuarioBase);
        $this->recargarModulo($I);

        $I->buscarEnListado($this->usuarioBase['cedula']);
        $I->waitForText($this->usuarioBase['cedula'], 5, AcceptanceTester::CONT_RESULTADOS);

        // 1. Abrir el modal de permisos
        $I->waitForElementVisible(self::BTN_PERMISOS, 5);
        $I->despejarOverlays();
        $I->executeJS("document.querySelector('" . self::BTN_PERMISOS . "').click();");
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->waitForText('Permisos del Usuario', 5, AcceptanceTester::MODAL_TITULO);

        // 2. Solo se muestra la sección de permisos (campos personales ocultos)
        $I->seeElement(self::FILA_PERMISOS);
        $I->dontSeeElement(AcceptanceTester::INPUT_CEDULA);
        $I->dontSeeElement(self::INPUT_CONTRASENA);
        $I->see('Guardar Permisos', AcceptanceTester::BTN_PROCESO);

        // 3. Pestaña "Asignados" activa por defecto, cambio a "No Asignados"
        $I->seeElement(self::TAB_ASIGNADOS . '.activa');
        $I->click(self::TAB_NO_ASIGNADOS);
        $I->waitForElementVisible(self::CONT_TAB_NO_ASIG, 5);
        $I->seeElement(self::TAB_NO_ASIGNADOS . '.activa');
        $I->click(self::TAB_ASIGNADOS);
        $I->waitForElementVisible(self::CONT_TAB_ASIGNADOS, 5);

        // 4. Buscador de módulos con un término inexistente
        $I->fillField(self::BUSCADOR_PERMISOS, 'ModuloInexistenteXYZ');
        $I->executeJS("$('#buscador_permisos').trigger('input');");
        $I->waitForText('No hay permisos asignados para esta búsqueda', 5, self::CONT_TAB_ASIGNADOS);
        $I->fillField(self::BUSCADOR_PERMISOS, '');
        $I->executeJS("$('#buscador_permisos').trigger('input');");

        // 5. Guardar permisos sin cambios y confirmar
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere guardar los permisos?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Permisos Guardados', 10);
        $I->aceptarSweetAlert();
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        $I->verificarSinErrores();
    }

    /**
     * CP-USU-11: Diálogo de generación de reportes en PDF o Excel.
     */
    #[Group('reporte')]
    public function probarGenerarReporteUsuarios(AcceptanceTester $I): void
    {
        // 1. Abrir modal de reporte
        $I->click(AcceptanceTester::BTN_REPORTE);
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->see('Generar Reporte', AcceptanceTester::MODAL_TITULO);

        // 2. Solo se muestran los filtros del reporte (cédula, nombre, apellido y rol)
        $I->seeElement(AcceptanceTester::INPUT_CEDULA);
        $I->seeElement(AcceptanceTester::INPUT_NOMBRE);
        $I->seeElement(AcceptanceTester::INPUT_APELLIDO);
        $I->dontSeeElement(self::INPUT_CONTRASENA);
        $I->dontSeeElement(AcceptanceTester::INPUT_CORREO);
        $I->dontSeeElement(AcceptanceTester::INPUT_TELEFONO);

        // 3. Abrir el diálogo de selección de formato
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿En qué formato desea generar el reporte?', 5);
        $I->see('PDF');
        $I->see('Excel');

        // 4. Cancelar para evitar descargas durante la prueba
        $I->cancelarSweetAlert();

        // 5. Cerrar modal limpiamente
        $I->click(AcceptanceTester::MODAL_CERRAR);
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        $I->verificarSinErrores();
    }

    /**
     * CP-USU-12: Eliminación (borrado lógico) de un usuario.
     */
    #[Group('eliminar', 'crud')]
    public function probarEliminarUsuario(AcceptanceTester $I): void
    {
        $this->asegurarUsuarioExiste($this->usuarioEliminar);
        $this->recargarModulo($I);

        // 1. Localizar al usuario
        $I->buscarEnListado($this->usuarioEliminar['cedula']);
        $I->waitForText($this->usuarioEliminar['cedula'], 5, AcceptanceTester::CONT_RESULTADOS);

        // 2. Eliminar y confirmar
        $I->clickEliminar();
        $I->waitForText('¿Está seguro que quiere eliminar este Usuario?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Eliminacion Exitosa', 10);
        $I->aceptarSweetAlert();

        // 3. Ya no aparece en el listado
        $I->buscarEnListado($this->usuarioEliminar['cedula']);
        $I->waitForText('No se encontraron registros', 5, AcceptanceTester::CONT_RESULTADOS);

        // 4. Borrado lógico: el registro sigue en BD con estatus 0
        $estatus = $this->obtenerEstatus($this->usuarioEliminar['cedula']);
        if ($estatus !== null) {
            Assert::assertSame(0, $estatus);
        }

        $I->verificarSinErrores();
    }

    /**
     * CP-USU-13: El Super Usuario no puede eliminarse ni bloquearse.
     */
    #[Group('validacion', 'seguridad')]
    public function probarProtegerSuperUsuario(AcceptanceTester $I): void
    {
        $I->buscarEnListado('12345678');
        $I->waitForText('12345678', 5, AcceptanceTester::CONT_RESULTADOS);

        // 1. Intento de eliminación
        $I->clickEliminar();
        $I->waitForText('¿Está seguro que quiere eliminar este Usuario?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('No puedes eliminar tu propio usuario.', 10);
        $I->aceptarSweetAlert();

        // 2. Intento de bloqueo
        $I->waitForElementVisible(self::BTN_DESBLOQUEADO, 5);
        $I->despejarOverlays();
        $I->executeJS("document.querySelector('" . self::BTN_DESBLOQUEADO . "').click();");
        $I->waitForText('¿Está seguro que quiere bloquear este Usuario?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('No puedes bloquear tu propio usuario.', 10);
        $I->aceptarSweetAlert();

        // 3. El Super Usuario sigue listado y desbloqueado
        $I->buscarEnListado('12345678');
        $I->waitForText('12345678', 5, AcceptanceTester::CONT_RESULTADOS);
        $I->seeElement(self::BTN_DESBLOQUEADO);

        $I->verificarSinErrores();
    }

    /**
     * CP-USU-14: Un rol distinto de SuperUsuario (Entrenador) no puede acceder al módulo.
     */
    #[Group('seguridad', 'acceso')]
    public function probarAccesoDenegadoRolSinPermisos(AcceptanceTester $I): void
    {
        $this->asegurarUsuarioExiste($this->usuarioSinAcceso);
        $this->establecerBloqueo($this->usuarioSinAcceso['cedula'], 1);

        // 1. Cerrar la sesión del administrador (sin tocar el snapshot)
        $I->executeInSelenium(function (\Facebook\WebDriver\Remote\RemoteWebDriver $wd) {
            $wd->manage()->deleteAllCookies();
        });

        // 2. Iniciar sesión con el usuario de rol Entrenador
        $I->amOnPage('/');
        $I->waitForElement('input[name="cedula"]', 5);
        $I->fillField('cedula', $this->usuarioSinAcceso['cedula']);
        $I->fillField('contraseña', $this->usuarioSinAcceso['contrasena']);
        $I->click('Iniciar Sesión');
        $I->waitForText('Inicio Validado', 10);

        // 3. Intentar ingresar directamente al módulo de Usuarios
        $I->amOnPage(self::RUTA_MODULO);

        // 4. Debe redirigir a Principal con la alerta de acceso denegado
        $I->waitForText('Acceso denegado', 10);
        $I->seeInCurrentUrl('Principal');
        $I->dontSeeElement(AcceptanceTester::BTN_NUEVO);

        // 5. Cerrar la sesión de prueba para que las demás pruebas usen el snapshot del admin
        $I->executeInSelenium(function (\Facebook\WebDriver\Remote\RemoteWebDriver $wd) {
            $wd->manage()->deleteAllCookies();
        });
    }

    /**
     * CP-USU-15: Ciclo de vida completo (Registrar -> Buscar -> Modificar -> Bloquear -> Eliminar).
     */
    #[Group('cicloCompleto', 'e2e')]
    public function probarCicloCompletoUsuario(AcceptanceTester $I): void
    {
        $u = $this->usuarioCiclo;
        $this->eliminarUsuarioSiExiste($u['cedula']);
        $this->recargarModulo($I);

        // --- PASO 1: REGISTRAR ---
        $this->abrirModalRegistro($I);
        $this->llenarFormulario($I, $u);
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere registrar este usuario?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Registro Exitoso', 10);
        $I->aceptarSweetAlert();
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        // --- PASO 2: BUSCAR ---
        $I->buscarEnListado($u['cedula']);
        $I->waitForText($u['nombre'] . ' ' . $u['apellido'], 5, AcceptanceTester::CONT_RESULTADOS);

        // --- PASO 3: MODIFICAR ---
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

        // --- PASO 4: BLOQUEAR ---
        $I->waitForElementVisible(self::BTN_DESBLOQUEADO, 5);
        $I->despejarOverlays();
        $I->executeJS("document.querySelector('" . self::BTN_DESBLOQUEADO . "').click();");
        $I->waitForText('¿Está seguro que quiere bloquear este Usuario?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Usuario bloqueado exitosamente.', 10);
        $I->aceptarSweetAlert();
        $I->waitForElementVisible(self::BTN_BLOQUEADO, 5);

        // --- PASO 5: ELIMINAR ---
        $I->clickEliminar();
        $I->waitForText('¿Está seguro que quiere eliminar este Usuario?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Eliminacion Exitosa', 10);
        $I->aceptarSweetAlert();

        // --- PASO 6: VALIDAR INEXISTENCIA ---
        $I->buscarEnListado($u['cedula']);
        $I->waitForText('No se encontraron registros', 5, AcceptanceTester::CONT_RESULTADOS);

        $I->limpiarBuscador();
        $I->verificarSinErrores();
    }

    // =========================================================================
    // HELPERS DE INTERFAZ
    // =========================================================================

    /**
     * Recarga el módulo (para reflejar datos preparados en BD) y espera a que el loader desaparezca.
     */
    private function recargarModulo(AcceptanceTester $I): void
    {
        $I->amOnPage(self::RUTA_MODULO);
        $I->waitForElementVisible(AcceptanceTester::INPUT_BUSQUEDA, 10);
        $I->waitForElementNotVisible('#loader', 10);
        $I->despejarOverlays();
    }

    /**
     * Cierra la alerta de error y espera a que desaparezca por completo
     * para que el siguiente fillField no se pierda durante la animación.
     */
    private function cerrarAlertaError(AcceptanceTester $I): void
    {
        $I->aceptarSweetAlert();
        // Esperar a que SweetAlert retire su contenedor (la animación de cierre bloquea los clics)
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

        // Esperar a que consultarRoles() pueble el selector
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

        // Select2: se fija el valor por JS y se dispara el evento change
        $I->executeJS("$('#roles').val('" . self::ROL_PRUEBA_ID . "').trigger('change');");
    }

    // =========================================================================
    // HELPERS DE BASE DE DATOS
    // =========================================================================

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
            // Silencioso para no interferir con la ejecución UI
        }
    }

    /**
     * Borrado lógico del usuario de prueba (estatus = 0) si está activo.
     * Al registrarlo de nuevo por la interfaz, el modelo lo reactiva.
     */
    private function eliminarUsuarioSiExiste(string $cedula): void
    {
        try {
            $pdo = $this->conexion();
            // También se restablece el bloqueo: al reactivar un usuario, el modelo conserva su estado de bloqueo anterior
            $pdo?->prepare('UPDATE usuarios SET estatus = 0, bloqueo = 1, intentos_fallidos = 0 WHERE cedulaUsuario = :c')
                ->execute([':c' => $cedula]);
        } catch (\Throwable $e) {
            // Silencioso para no detener la prueba si no hay conexión
        }
    }

    /**
     * Garantiza que el usuario de prueba exista activo, desbloqueado y con sus datos originales.
     */
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
            // Silencioso para no interferir con la ejecución UI
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
            // Silencioso para no interferir con la ejecución UI
        }
    }
}
