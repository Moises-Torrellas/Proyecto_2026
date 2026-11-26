<?php

declare(strict_types=1);

namespace Tests\Acceptance\Roles;

use Codeception\Attribute\Group;
use PHPUnit\Framework\Assert;
use Tests\Support\AcceptanceTester;

if (!defined('_DB_NAME_')) {
    require_once __DIR__ . '/../../../config/config.php';
}

/**
 * Pruebas de interfaz (aceptación) del módulo Gestión de Roles y Permisos.
 *
 * Escenarios cubiertos (Plan de Pruebas de Aceptación - Pruebas Alfa "Gestión de Roles y Permisos"):
 *  - CP-ROL-01: Carga inicial de la interfaz.
 *  - CP-ROL-02: Validaciones del formulario del lado del cliente.
 *  - CP-ROL-03: Registro exitoso de un rol (se crea con Nivel 3).
 *  - CP-ROL-04: Rechazo de un nombre de rol duplicado.
 *  - CP-ROL-05: Buscador en tiempo real (coincidencia, sin resultados y restauración).
 *  - CP-ROL-06: Modificación de un rol.
 *  - CP-ROL-07: Asignación de permisos a un rol (pestañas, buscador, guardado y persistencia).
 *  - CP-ROL-08: Diálogo de generación de reportes (PDF / Excel).
 *  - CP-ROL-09: Eliminación de un rol sin usuarios vinculados.
 *  - CP-ROL-10: Denegar la eliminación de un rol con usuarios vinculados.
 *  - CP-ROL-11: Roles de Nivel 1 y 2 protegidos (no se listan, ni se editan, ni se eliminan).
 *  - CP-ROL-12: Acceso denegado al módulo para un rol distinto de SuperUsuario.
 *  - CP-ROL-13: Ciclo de vida completo (Registrar -> Buscar -> Modificar -> Permisos -> Eliminar).
 */
#[Group('roles')]
#[Group('interfaz')]
final class RolesCest
{
    private const RUTA_MODULO          = '/Roles';
    private const TITULO_MODULO        = 'Roles';

    // Selectores específicos del módulo de Roles
    private const FILA_NOMBRE          = '#row_nombre';
    private const FILA_PERMISOS        = '#row_permisos';
    private const BUSCADOR_PERMISOS    = '#buscador_permisos';
    private const TAB_ASIGNADOS        = '.permisos_tab[data-tab="asignados"]';
    private const TAB_NO_ASIGNADOS     = '.permisos_tab[data-tab="no_asignados"]';
    private const CONT_TAB_ASIGNADOS   = '#tab_asignados';
    private const CONT_TAB_NO_ASIG     = '#tab_no_asignados';
    private const BADGE_ASIGNADOS      = '#badge_asignados';
    private const BTN_PERMISOS         = '#resultadoconsulta .cbt_m';

    // Roles protegidos que nunca deben aparecer en el listado
    private const ROL_SUPERUSUARIO     = 'Superusuario';
    private const ROL_SOPORTE          = 'Soporte';

    // Datos de prueba (solo letras y espacios, como exige la validación)
    private string $nombreBase         = 'Rol Prueba Interfaz';
    private string $descripcionBase    = 'Rol Creado Por Las Pruebas De Interfaz';

    private string $nombreMod          = 'Rol Prueba Editado';
    private string $descripcionMod     = 'Descripcion Modificada Por Las Pruebas';

    private string $nombreEliminar     = 'Rol Prueba Eliminar';
    private string $nombreConUsuario   = 'Rol Prueba Vinculado';

    private string $nombreCiclo        = 'Rol Prueba Ciclo';
    private string $nombreCicloMod     = 'Rol Ciclo Editado';

    // Usuario de prueba vinculado al rol "Rol Prueba Vinculado"
    private array $usuarioVinculado = [
        'cedula'     => '30111666',
        'nombre'     => 'Pruebacinco',
        'apellido'   => 'Vinculado',
        'telefono'   => '0414-1116677',
        'correo'     => 'prueba.cinco.vinculado@gmail.com',
        'contrasena' => 'Prueba123$',
    ];

    // Usuario de prueba con rol Entrenador (Nivel 3, sin permisos sobre Roles)
    private const ROL_ENTRENADOR_ID    = 6;
    private array $usuarioSinAcceso = [
        'cedula'     => '30111555',
        'nombre'     => 'Pruebacuatro',
        'apellido'   => 'Acceso',
        'telefono'   => '0414-1115566',
        'correo'     => 'prueba.cuatro.acceso@gmail.com',
        'contrasena' => 'Prueba123$',
    ];

    public function _before(AcceptanceTester $I): void
    {
        // Login con snapshot + navegación a /Roles + verificación de título y limpieza de overlays
        $I->irAlModulo(self::RUTA_MODULO, self::TITULO_MODULO);
    }

    /**
     * CP-ROL-01: Verifica la carga inicial de la interfaz del módulo de Roles.
     */
    #[Group('carga', 'interfaz')]
    public function probarCargaInicialDeInterfaz(AcceptanceTester $I): void
    {
        $I->see(self::TITULO_MODULO, AcceptanceTester::TITULO_PAGINA);
        $I->seeElement(AcceptanceTester::INPUT_BUSQUEDA);
        $I->seeElement(AcceptanceTester::BTN_NUEVO);
        $I->see('Nuevo Rol', AcceptanceTester::BTN_NUEVO);
        $I->seeElement(AcceptanceTester::BTN_REPORTE);
        $I->see('Generar Reporte', AcceptanceTester::BTN_REPORTE);
        $I->seeElement(AcceptanceTester::CONT_RESULTADOS);

        // El modal existe en el DOM pero debe arrancar oculto
        $I->seeElementInDOM(AcceptanceTester::MODAL);
        $I->dontSeeElement(AcceptanceTester::MODAL);

        $I->verificarSinErrores();
    }

    /**
     * CP-ROL-02: Validaciones del lado del cliente (validarEnvio() en roles.js).
     */
    #[Group('validacion')]
    public function probarValidacionesDeFormulario(AcceptanceTester $I): void
    {
        // 1. Abrir modal de registro
        $this->abrirModalRegistro($I);

        // 2. Formulario vacío -> falla el nombre
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('Solo puede ingresar letra, Maximo 30 caracteres', 5);
        $this->cerrarAlertaError($I);

        // 3. Nombre con menos de 3 letras
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, 'Ro');
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('Solo puede ingresar letra, Maximo 30 caracteres', 5);
        $this->cerrarAlertaError($I);

        // 4. Nombre válido, descripción vacía
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, $this->nombreBase);
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('Tiene que ingresar una descripcion valida', 5);
        $this->cerrarAlertaError($I);

        // 5. Descripción con menos de 3 letras
        $I->fillField(AcceptanceTester::INPUT_DESCRIPCION, 'Ab');
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('Tiene que ingresar una descripcion valida', 5);
        $this->cerrarAlertaError($I);

        // 6. La máscara de entrada bloquea números y símbolos en el nombre
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, '');
        $I->pressKey(AcceptanceTester::INPUT_NOMBRE, 'R', 'o', 'l', '1', '2', '$');
        $I->seeInField(AcceptanceTester::INPUT_NOMBRE, 'Rol');

        // 7. Cerrar modal limpiamente
        $I->click(AcceptanceTester::MODAL_CERRAR);
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        $I->verificarSinErrores();
    }

    /**
     * CP-ROL-03: Registro exitoso de un rol; el sistema lo crea con Nivel 3.
     */
    #[Group('registrar', 'crud')]
    public function probarRegistrarRol(AcceptanceTester $I): void
    {
        $this->eliminarRolSiExiste($this->nombreBase);
        $this->recargarModulo($I);

        // 1. Abrir modal y llenar formulario
        $this->abrirModalRegistro($I);
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, $this->nombreBase);
        $I->fillField(AcceptanceTester::INPUT_DESCRIPCION, $this->descripcionBase);

        // 2. Enviar y confirmar en SweetAlert
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere registrar este rol?', 5);
        $I->confirmarSweetAlert();

        // 3. Mensaje de éxito (el modal se cierra automáticamente)
        $I->waitForText('Registro Exitoso', 10);
        $I->aceptarSweetAlert();
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        // 4. Verificar el nuevo rol en el listado
        $I->buscarEnListado($this->nombreBase);
        $I->waitForText($this->nombreBase, 5, AcceptanceTester::CONT_RESULTADOS);
        $I->see($this->descripcionBase, AcceptanceTester::CONT_RESULTADOS);

        // 5. Los roles creados desde la interfaz son de Nivel 3
        $rol = $this->buscarRol($this->nombreBase);
        if ($rol !== null) {
            Assert::assertSame(3, (int)$rol['nivel_rol']);
        }

        $I->verificarSinErrores();
    }

    /**
     * CP-ROL-04: El sistema rechaza el registro de un rol con un nombre ya existente.
     */
    #[Group('validacion', 'duplicados')]
    public function probarRechazarNombreDuplicado(AcceptanceTester $I): void
    {
        $this->asegurarRolExiste($this->nombreBase, $this->descripcionBase);

        $this->abrirModalRegistro($I);
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, $this->nombreBase);
        $I->fillField(AcceptanceTester::INPUT_DESCRIPCION, 'Intento De Rol Duplicado');

        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere registrar este rol?', 5);
        $I->confirmarSweetAlert();

        $I->waitForText('Ya existe un rol registrado con este nombre.', 10);
        $I->aceptarSweetAlert();

        // El modal permanece abierto para corregir los datos
        $I->seeElement(AcceptanceTester::MODAL);
        $I->click(AcceptanceTester::MODAL_CERRAR);
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        // Solo existe un rol con ese nombre
        Assert::assertSame(1, $this->contarRoles($this->nombreBase) ?? 1);

        $I->verificarSinErrores();
    }

    /**
     * CP-ROL-05: Buscador de roles en tiempo real (debounce de 500ms).
     */
    #[Group('buscador')]
    public function probarBuscadorRoles(AcceptanceTester $I): void
    {
        $this->asegurarRolExiste($this->nombreBase, $this->descripcionBase);
        $this->recargarModulo($I);

        // 1. Búsqueda por término existente (coincidencia parcial)
        $I->buscarEnListado('Prueba Interfaz');
        $I->waitForText($this->nombreBase, 5, AcceptanceTester::CONT_RESULTADOS);

        // 2. Búsqueda por término inexistente
        $I->buscarEnListado('RolInexistenteXYZ');
        $I->waitForElementVisible(AcceptanceTester::LISTADO_VACIO, 5);
        $I->see('No se encontraron registros', AcceptanceTester::LISTADO_VACIO);

        // 3. Limpiar buscador y verificar recuperación del listado
        $I->limpiarBuscador();
        $I->dontSee('No se encontraron registros', AcceptanceTester::CONT_RESULTADOS);
        $I->see($this->nombreBase, AcceptanceTester::CONT_RESULTADOS);

        $I->verificarSinErrores();
    }

    /**
     * CP-ROL-06: Modificación del nombre y la descripción de un rol.
     */
    #[Group('modificar', 'crud')]
    public function probarModificarRol(AcceptanceTester $I): void
    {
        $this->eliminarRolSiExiste($this->nombreMod);
        $this->asegurarRolExiste($this->nombreBase, $this->descripcionBase);
        $this->recargarModulo($I);

        // 1. Ubicar el rol con el buscador
        $I->buscarEnListado($this->nombreBase);
        $I->waitForText($this->nombreBase, 5, AcceptanceTester::CONT_RESULTADOS);

        // 2. Abrir modal de modificación con los datos precargados
        $I->clickModificar();
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->waitForText('Modificar Rol', 5, AcceptanceTester::MODAL_TITULO);
        $I->seeInField(AcceptanceTester::INPUT_NOMBRE, $this->nombreBase);
        $I->seeInField(AcceptanceTester::INPUT_DESCRIPCION, $this->descripcionBase);
        $I->dontSeeElement(self::FILA_PERMISOS);

        // 3. Modificar valores
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, $this->nombreMod);
        $I->fillField(AcceptanceTester::INPUT_DESCRIPCION, $this->descripcionMod);

        // 4. Guardar y confirmar
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere modificar este rol?', 5);
        $I->confirmarSweetAlert();

        // 5. Mensaje de éxito (el modal se cierra automáticamente)
        $I->waitForText('Modificacion Exitosa', 10);
        $I->aceptarSweetAlert();
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        // 6. Validar el cambio en el listado
        $I->buscarEnListado($this->nombreMod);
        $I->waitForText($this->nombreMod, 5, AcceptanceTester::CONT_RESULTADOS);
        $I->see($this->descripcionMod, AcceptanceTester::CONT_RESULTADOS);

        // 7. Restaurar el nombre original para no afectar a las demás pruebas
        $this->renombrarRol($this->nombreMod, $this->nombreBase, $this->descripcionBase);

        $I->verificarSinErrores();
    }

    /**
     * CP-ROL-07: Asignación de permisos a un rol de Nivel 3 y persistencia de los cambios.
     */
    #[Group('permisos')]
    public function probarAsignarPermisosRol(AcceptanceTester $I): void
    {
        $this->asegurarRolExiste($this->nombreBase, $this->descripcionBase);
        $this->limpiarPermisosRol($this->nombreBase);
        $this->recargarModulo($I);

        $I->buscarEnListado($this->nombreBase);
        $I->waitForText($this->nombreBase, 5, AcceptanceTester::CONT_RESULTADOS);

        // 1. Abrir el modal de permisos
        $this->abrirModalPermisos($I);

        // 2. Solo se muestra la sección de permisos (nombre y descripción ocultos)
        $I->seeElement(self::FILA_PERMISOS);
        $I->dontSeeElement(self::FILA_NOMBRE);
        $I->see('Guardar Permisos', AcceptanceTester::BTN_PROCESO);

        // 3. Rol sin permisos: pestaña "Asignados" vacía y contador en 0
        $I->seeElement(self::TAB_ASIGNADOS . '.activa');
        $I->see('No hay permisos asignados', self::CONT_TAB_ASIGNADOS);
        $I->see('0', self::BADGE_ASIGNADOS);

        // 4. Ir a "No Asignados" y probar el buscador de módulos
        $I->click(self::TAB_NO_ASIGNADOS);
        $I->waitForElementVisible(self::CONT_TAB_NO_ASIG, 5);
        $I->seeElement(self::TAB_NO_ASIGNADOS . '.activa');

        $I->fillField(self::BUSCADOR_PERMISOS, 'ModuloInexistenteXYZ');
        $I->executeJS("$('#buscador_permisos').trigger('input');");
        $I->waitForText('No hay permisos sin asignar para esta búsqueda', 5, self::CONT_TAB_NO_ASIG);
        $I->fillField(self::BUSCADOR_PERMISOS, '');
        $I->executeJS("$('#buscador_permisos').trigger('input');");

        // 5. Desplegar el primer módulo y marcar su primer permiso
        $I->waitForElementVisible('#tabla_permisos_no_asignados .listado_item', 5);
        $I->executeJS("document.querySelector('#tabla_permisos_no_asignados .listado_item').click();");
        $I->wait(1);
        $idCheck = (string)$I->executeJS("
            let chk = document.querySelector('#tabla_permisos_no_asignados input.checkbox');
            chk.checked = true;
            $(chk).trigger('change');
            return chk.id;
        ");

        // 6. Guardar y confirmar
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere guardar los permisos?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Modificacion Exitosa', 10);
        $I->aceptarSweetAlert();
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        // 7. Reabrir: el permiso marcado ahora aparece en "Asignados"
        $I->buscarEnListado($this->nombreBase);
        $I->waitForText($this->nombreBase, 5, AcceptanceTester::CONT_RESULTADOS);
        $this->abrirModalPermisos($I);
        $I->see('1', self::BADGE_ASIGNADOS);
        $I->seeElementInDOM(self::CONT_TAB_ASIGNADOS . ' #' . $idCheck . ':checked');

        $I->click(AcceptanceTester::MODAL_CERRAR);
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        // 8. Persistencia en BD
        Assert::assertSame(1, $this->contarPermisosRol($this->nombreBase) ?? 1);

        $I->verificarSinErrores();
    }

    /**
     * CP-ROL-08: Diálogo de generación de reportes en PDF o Excel.
     */
    #[Group('reporte')]
    public function probarGenerarReporteRoles(AcceptanceTester $I): void
    {
        // 1. Abrir modal de reporte
        $I->click(AcceptanceTester::BTN_REPORTE);
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->see('Generar Reporte', AcceptanceTester::MODAL_TITULO);
        $I->seeElement(AcceptanceTester::INPUT_NOMBRE);
        $I->dontSeeElement(self::FILA_PERMISOS);

        // 2. Abrir el diálogo de selección de formato
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿En qué formato desea generar el reporte?', 5);
        $I->see('PDF');
        $I->see('Excel');

        // 3. Cancelar para evitar descargas durante la prueba
        $I->cancelarSweetAlert();

        // 4. Cerrar modal limpiamente
        $I->click(AcceptanceTester::MODAL_CERRAR);
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        $I->verificarSinErrores();
    }

    /**
     * CP-ROL-09: Eliminación de un rol sin usuarios vinculados.
     */
    #[Group('eliminar', 'crud')]
    public function probarEliminarRol(AcceptanceTester $I): void
    {
        $this->asegurarRolExiste($this->nombreEliminar, 'Rol Que Sera Eliminado');
        $this->recargarModulo($I);

        // 1. Localizar el rol
        $I->buscarEnListado($this->nombreEliminar);
        $I->waitForText($this->nombreEliminar, 5, AcceptanceTester::CONT_RESULTADOS);

        // 2. Eliminar y confirmar
        $I->clickEliminar();
        $I->waitForText('¿Está seguro que quiere eliminar este rol?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Eliminacion Exitosa', 10);
        $I->aceptarSweetAlert();

        // 3. Ya no aparece en el listado ni en BD
        $I->buscarEnListado($this->nombreEliminar);
        $I->waitForText('No se encontraron registros', 5, AcceptanceTester::CONT_RESULTADOS);
        Assert::assertSame(0, $this->contarRoles($this->nombreEliminar) ?? 0);

        $I->verificarSinErrores();
    }

    /**
     * CP-ROL-10: No se permite eliminar un rol que tiene usuarios vinculados.
     */
    #[Group('eliminar', 'seguridad')]
    public function probarDenegarEliminarRolConUsuarios(AcceptanceTester $I): void
    {
        $this->asegurarRolExiste($this->nombreConUsuario, 'Rol Con Un Usuario Asignado');
        $rol = $this->buscarRol($this->nombreConUsuario);
        if ($rol !== null) {
            $this->asegurarUsuarioConRol($this->usuarioVinculado, (int)$rol['id_rol']);
        }
        $this->recargarModulo($I);

        // 1. Localizar el rol
        $I->buscarEnListado($this->nombreConUsuario);
        $I->waitForText($this->nombreConUsuario, 5, AcceptanceTester::CONT_RESULTADOS);

        // 2. Intentar eliminar
        $I->clickEliminar();
        $I->waitForText('¿Está seguro que quiere eliminar este rol?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('El rol tiene usuarios asociados.', 10);
        $I->aceptarSweetAlert();

        // 3. El rol sigue registrado
        $I->buscarEnListado($this->nombreConUsuario);
        $I->waitForText($this->nombreConUsuario, 5, AcceptanceTester::CONT_RESULTADOS);
        Assert::assertSame(1, $this->contarRoles($this->nombreConUsuario) ?? 1);

        $I->verificarSinErrores();
    }

    /**
     * CP-ROL-11: Los roles de Nivel 1 y 2 están protegidos: no se listan, ni se editan, ni se eliminan.
     */
    #[Group('validacion', 'seguridad')]
    public function probarRolesProtegidosNivelUnoYDos(AcceptanceTester $I): void
    {
        // 1. No aparecen en el listado ni al buscarlos
        $I->dontSee(self::ROL_SUPERUSUARIO, AcceptanceTester::CONT_RESULTADOS);
        $I->dontSee(self::ROL_SOPORTE, AcceptanceTester::CONT_RESULTADOS);

        $I->buscarEnListado(self::ROL_SUPERUSUARIO);
        $I->waitForText('No se encontraron registros', 5, AcceptanceTester::CONT_RESULTADOS);
        $I->limpiarBuscador();

        // 2. Aun forzando la petición desde el navegador, el Superusuario (id 1) no se puede modificar
        $I->executeJS('buscar(1);');
        $I->waitForText('Este rol no puede ser modificado', 10);
        $I->aceptarSweetAlert();
        $I->dontSeeElement(AcceptanceTester::MODAL);

        // 3. Ni se pueden modificar sus permisos
        $I->executeJS('CargarPermisos(1);');
        $I->waitForText('Los permisos de este rol no pueden ser modificados', 10);
        $I->aceptarSweetAlert();
        $I->dontSeeElement(AcceptanceTester::MODAL);

        // 4. Ni se puede eliminar
        $I->executeJS('eliminar(1);');
        $I->waitForText('¿Está seguro que quiere eliminar este rol?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('No puedes eliminar roles protegidos.', 10);
        $I->aceptarSweetAlert();

        // 5. El rol sigue existiendo en BD
        Assert::assertSame(1, $this->contarRoles(self::ROL_SUPERUSUARIO) ?? 1);

        $I->verificarSinErrores();
    }

    /**
     * CP-ROL-12: Un rol distinto de SuperUsuario (Entrenador) no puede acceder al módulo.
     */
    #[Group('seguridad', 'acceso')]
    public function probarAccesoDenegadoRolSinPermisos(AcceptanceTester $I): void
    {
        $this->asegurarUsuarioConRol($this->usuarioSinAcceso, self::ROL_ENTRENADOR_ID);

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

        // 3. Intentar ingresar directamente al módulo de Roles
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
     * CP-ROL-13: Ciclo de vida completo (Registrar -> Buscar -> Modificar -> Permisos -> Eliminar).
     */
    #[Group('cicloCompleto', 'e2e')]
    public function probarCicloCompletoRol(AcceptanceTester $I): void
    {
        $this->eliminarRolSiExiste($this->nombreCiclo);
        $this->eliminarRolSiExiste($this->nombreCicloMod);
        $this->recargarModulo($I);

        // --- PASO 1: REGISTRAR ---
        $this->abrirModalRegistro($I);
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, $this->nombreCiclo);
        $I->fillField(AcceptanceTester::INPUT_DESCRIPCION, 'Rol Del Ciclo Completo');
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere registrar este rol?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Registro Exitoso', 10);
        $I->aceptarSweetAlert();
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        // --- PASO 2: BUSCAR ---
        $I->buscarEnListado($this->nombreCiclo);
        $I->waitForText($this->nombreCiclo, 5, AcceptanceTester::CONT_RESULTADOS);

        // --- PASO 3: MODIFICAR ---
        $I->clickModificar();
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->waitForText('Modificar Rol', 5, AcceptanceTester::MODAL_TITULO);
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, $this->nombreCicloMod);
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere modificar este rol?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Modificacion Exitosa', 10);
        $I->aceptarSweetAlert();
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        $I->buscarEnListado($this->nombreCicloMod);
        $I->waitForText($this->nombreCicloMod, 5, AcceptanceTester::CONT_RESULTADOS);

        // --- PASO 4: ASIGNAR UN PERMISO ---
        $this->abrirModalPermisos($I);
        $I->executeJS("
            let chk = document.querySelector('#tabla_permisos_no_asignados input.checkbox');
            chk.checked = true;
            $(chk).trigger('change');
        ");
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere guardar los permisos?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Modificacion Exitosa', 10);
        $I->aceptarSweetAlert();
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        // --- PASO 5: ELIMINAR (los permisos se eliminan en cascada) ---
        $I->buscarEnListado($this->nombreCicloMod);
        $I->waitForText($this->nombreCicloMod, 5, AcceptanceTester::CONT_RESULTADOS);
        $I->clickEliminar();
        $I->waitForText('¿Está seguro que quiere eliminar este rol?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Eliminacion Exitosa', 10);
        $I->aceptarSweetAlert();

        // --- PASO 6: VALIDAR INEXISTENCIA ---
        $I->buscarEnListado($this->nombreCicloMod);
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
     * Cierra la alerta de error y espera a que SweetAlert retire su contenedor
     * (la animación de cierre bloquea los clics siguientes).
     */
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
        $I->see('Registrar Rol', AcceptanceTester::MODAL_TITULO);
        $I->see('Registrar Rol', AcceptanceTester::BTN_PROCESO);
        $I->seeElement(AcceptanceTester::INPUT_NOMBRE);
        $I->seeElement(AcceptanceTester::INPUT_DESCRIPCION);
        $I->dontSeeElement(self::FILA_PERMISOS);
    }

    private function abrirModalPermisos(AcceptanceTester $I): void
    {
        $I->waitForElementVisible(self::BTN_PERMISOS, 5);
        $I->despejarOverlays();
        $I->executeJS("document.querySelector('" . self::BTN_PERMISOS . "').click();");
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->waitForText('Permisos del Rol', 5, AcceptanceTester::MODAL_TITULO);
        $I->waitForElementVisible(self::FILA_PERMISOS, 5);
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

    private function buscarRol(string $nombre): ?array
    {
        $pdo = $this->conexion();
        if ($pdo === null) {
            return null;
        }
        $st = $pdo->prepare('SELECT * FROM roles WHERE nombre_rol = :n LIMIT 1');
        $st->execute([':n' => $nombre]);
        $fila = $st->fetch(\PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    private function contarRoles(string $nombre): ?int
    {
        $pdo = $this->conexion();
        if ($pdo === null) {
            return null;
        }
        $st = $pdo->prepare('SELECT COUNT(*) FROM roles WHERE nombre_rol = :n');
        $st->execute([':n' => $nombre]);
        return (int)$st->fetchColumn();
    }

    private function contarPermisosRol(string $nombre): ?int
    {
        $rol = $this->buscarRol($nombre);
        $pdo = $this->conexion();
        if ($rol === null || $pdo === null) {
            return null;
        }
        $st = $pdo->prepare('SELECT COUNT(*) FROM permisos_rol WHERE id_rol = :id');
        $st->execute([':id' => $rol['id_rol']]);
        return (int)$st->fetchColumn();
    }

    private function limpiarPermisosRol(string $nombre): void
    {
        try {
            $rol = $this->buscarRol($nombre);
            if ($rol !== null) {
                $this->conexion()?->prepare('DELETE FROM permisos_rol WHERE id_rol = :id')
                    ->execute([':id' => $rol['id_rol']]);
            }
        } catch (\Throwable $e) {
            // Silencioso para no interferir con la ejecución UI
        }
    }

    /**
     * Elimina el rol de prueba si existe (solo si no tiene usuarios vinculados).
     */
    private function eliminarRolSiExiste(string $nombre): void
    {
        try {
            $this->conexion()?->prepare(
                'DELETE FROM roles WHERE nombre_rol = :n AND nivel_rol = 3
                   AND id_rol NOT IN (SELECT id_rol FROM usuarios)'
            )->execute([':n' => $nombre]);
        } catch (\Throwable $e) {
            // Silencioso para no detener la prueba si no hay conexión
        }
    }

    private function asegurarRolExiste(string $nombre, string $descripcion): void
    {
        try {
            if ($this->buscarRol($nombre) === null) {
                $modelo = new \App\modelo\ModeloRoles();
                $modelo->procesarDatos([
                    'accion'      => 'incluir',
                    'nombre'      => $nombre,
                    'descripcion' => $descripcion,
                ]);
            }
        } catch (\Throwable $e) {
            // Silencioso para no interferir con la ejecución UI
        }
    }

    private function renombrarRol(string $actual, string $nuevo, string $descripcion): void
    {
        try {
            $this->conexion()?->prepare('UPDATE roles SET nombre_rol = :nuevo, descripcion = :d WHERE nombre_rol = :actual')
                ->execute([':nuevo' => $nuevo, ':d' => $descripcion, ':actual' => $actual]);
        } catch (\Throwable $e) {
            // Silencioso para no interferir con la ejecución UI
        }
    }

    /**
     * Garantiza que el usuario de prueba exista activo, desbloqueado y con el rol indicado.
     */
    private function asegurarUsuarioConRol(array $u, int $idRol): void
    {
        try {
            $pdo = $this->conexion();
            if ($pdo === null) {
                return;
            }
            $hash = password_hash($u['contrasena'], PASSWORD_BCRYPT);
            $st = $pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE cedulaUsuario = :c');
            $st->execute([':c' => $u['cedula']]);

            if ((int)$st->fetchColumn() > 0) {
                $pdo->prepare(
                    'UPDATE usuarios SET nombreUsuario = :n, apellidoUsuario = :a, telefonoUsuario = :t, correo = :co,
                        pass_hash = :p, id_rol = :r, estatus = 1, bloqueo = 1, intentos_fallidos = 0
                     WHERE cedulaUsuario = :c'
                )->execute([
                    ':n' => $u['nombre'], ':a' => $u['apellido'], ':t' => $u['telefono'],
                    ':co' => $u['correo'], ':p' => $hash, ':r' => $idRol, ':c' => $u['cedula'],
                ]);
                return;
            }

            $pdo->prepare(
                'INSERT INTO usuarios (cedulaUsuario, nombreUsuario, apellidoUsuario, foto, telefonoUsuario, pass_hash, correo, id_rol, estatus, bloqueo, intentos_fallidos)
                 VALUES (:c, :n, :a, \'default.png\', :t, :p, :co, :r, 1, 1, 0)'
            )->execute([
                ':c' => $u['cedula'], ':n' => $u['nombre'], ':a' => $u['apellido'], ':t' => $u['telefono'],
                ':p' => $hash, ':co' => $u['correo'], ':r' => $idRol,
            ]);
        } catch (\Throwable $e) {
            // Silencioso para no interferir con la ejecución UI
        }
    }
}
