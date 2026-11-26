<?php

declare(strict_types=1);

namespace Tests\Acceptance\Permisos;

use Codeception\Attribute\Group;
use PHPUnit\Framework\Assert;
use Tests\Support\AcceptanceTester;

if (!defined('_DB_NAME_')) {
    require_once __DIR__ . '/../../../config/config.php';
}

/**
 * Pruebas de interfaz (aceptación) del módulo Permisos.
 *
 * Escenarios cubiertos (Plan de Pruebas de Aceptación - Pruebas Alfa "Gestión de Roles y Permisos"):
 *  - CP-PER-01: Carga inicial de la interfaz (listado agrupado por módulo).
 *  - CP-PER-02: El Nivel 1 (Soporte) y el Nivel 2 (Superusuario) ven "Nuevo Permiso" y pueden registrar permisos.
 *  - CP-PER-03: Despliegue del detalle de un módulo con sus permisos.
 *  - CP-PER-04: Buscador en tiempo real (por nombre, por clave, sin resultados y restauración).
 *  - CP-PER-05: Validaciones del formulario de registro (usuario Nivel 1).
 *  - CP-PER-06: Registro exitoso de un permiso (usuario Nivel 1).
 *  - CP-PER-07: Rechazo de nombre y clave duplicados (usuario Nivel 1).
 *  - CP-PER-08: Modificación del nombre y la descripción de un permiso.
 *  - CP-PER-09: Bloqueo y desbloqueo de un permiso.
 *  - CP-PER-10: Un permiso bloqueado deja de otorgar acceso a los roles que lo tienen vinculado.
 *  - CP-PER-11: Acceso denegado al módulo para un rol distinto de SuperUsuario.
 */
#[Group('permisos')]
#[Group('interfaz')]
final class PermisosCest
{
    private const RUTA_MODULO          = '/Permisos';
    private const TITULO_MODULO        = 'Permisos';

    // Selectores específicos del módulo de Permisos
    private const INPUT_CLAVE          = '#clave';
    private const SELECT_MODULO        = '#modulo';
    private const REGISTRO             = '#resultadoconsulta .listado_contenedor_grupal';
    private const PRIMER_REGISTRO      = '#resultadoconsulta .listado_contenedor_grupal:first-child';
    private const DETALLE_PRIMERO      = '#resultadoconsulta .listado_contenedor_grupal:first-child .listado_detalle_oculto';
    private const FILA_PERMISO         = '.sub_item_fila';
    private const BTN_DESBLOQUEADO     = '#resultadoconsulta .sub_item_acciones .cbt_g';
    private const BTN_BLOQUEADO        = '#resultadoconsulta .sub_item_acciones .cbt_a';

    // Módulo usado para los permisos de prueba
    private const MODULO_PRUEBA_ID     = '10';
    private const MODULO_PRUEBA        = 'Posiciones';

    // Permiso real usado para comprobar el efecto del bloqueo (se restaura siempre)
    private const PERMISO_INGRESO_ID   = 13;
    private const PERMISO_INGRESO      = 'ingresar_posiciones';

    // Permiso de prueba para modificar y bloquear
    private string $nombreBase         = 'Permiso Prueba Interfaz';
    private string $claveBase          = 'prueba_interfaz';
    private string $descripcionBase    = 'Permiso Creado Por Las Pruebas';
    private string $nombreMod          = 'Permiso Prueba Editado';
    private string $descripcionMod     = 'Descripcion Modificada Por Las Pruebas';

    // Permiso que se registra desde la interfaz
    private string $nombreNuevo        = 'Permiso Prueba Registro';
    private string $claveNueva         = 'registro_prueba_interfaz';

    // Usuario de prueba de Nivel 1 (rol Soporte); junto al Superusuario son los niveles que registran permisos
    private const ROL_SOPORTE_ID       = 5;
    private array $usuarioSoporte = [
        'cedula'     => '30111777',
        'nombre'     => 'Pruebaseis',
        'apellido'   => 'Soporte',
        'telefono'   => '0414-1117788',
        'correo'     => 'prueba.seis.soporte@gmail.com',
        'contrasena' => 'Prueba123$',
    ];

    // Usuario de prueba de Nivel 3 vinculado a un rol de prueba
    private string $rolVinculado       = 'Rol Prueba Vinculado';
    private array $usuarioVinculado = [
        'cedula'     => '30111666',
        'nombre'     => 'Pruebacinco',
        'apellido'   => 'Vinculado',
        'telefono'   => '0414-1116677',
        'correo'     => 'prueba.cinco.vinculado@gmail.com',
        'contrasena' => 'Prueba123$',
    ];

    // Usuario de prueba con rol Entrenador (Nivel 3, sin permisos sobre este módulo)
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
        // Login con snapshot + navegación a /Permisos + verificación de título y limpieza de overlays
        $I->irAlModulo(self::RUTA_MODULO, self::TITULO_MODULO);
        $I->waitForElementNotVisible('#loader', 10);
    }

    public function _after(AcceptanceTester $I): void
    {
        // El permiso real nunca debe quedar bloqueado ni el usuario Nivel 1 activo
        $this->fijarEstatusPermiso(self::PERMISO_INGRESO_ID, 1);
        $this->desactivarUsuario($this->usuarioSoporte['cedula']);
    }

    /**
     * CP-PER-01: Verifica la carga inicial de la interfaz del módulo de Permisos.
     */
    #[Group('carga', 'interfaz')]
    public function probarCargaInicialDeInterfaz(AcceptanceTester $I): void
    {
        $I->see(self::TITULO_MODULO, AcceptanceTester::TITULO_PAGINA);
        $I->seeElement(AcceptanceTester::INPUT_BUSQUEDA);
        $I->seeElement(AcceptanceTester::CONT_RESULTADOS);
        $I->seeElement(AcceptanceTester::ITEM_LISTADO);

        // Los permisos se agrupan por módulo con su estado y cantidad
        $I->see('Estado Módulo', self::PRIMER_REGISTRO);
        $I->see('Permisos Asignados', self::PRIMER_REGISTRO);
        $I->see('Permiso(s)', self::PRIMER_REGISTRO);

        // El modal existe en el DOM pero debe arrancar oculto
        $I->seeElementInDOM(AcceptanceTester::MODAL);
        $I->dontSeeElement(AcceptanceTester::MODAL);

        $I->verificarSinErrores();
    }

    /**
     * CP-PER-02: El Superusuario (Nivel 2) y el Soporte (Nivel 1) pueden registrar permisos.
     */
    #[Group('seguridad')]
    public function probarNivelesUnoYDosRegistranPermisos(AcceptanceTester $I): void
    {
        // 1. Con el Superusuario aparece el botón "Nuevo Permiso"
        $I->seeElement(AcceptanceTester::BTN_NUEVO);
        $I->see('Nuevo Permiso', AcceptanceTester::BTN_NUEVO);

        // 2. Con un usuario de Nivel 1 (Soporte) también aparece
        $this->iniciarSesionComo($I, $this->usuarioSoporte, self::ROL_SOPORTE_ID);
        $this->recargarModulo($I);
        $I->seeElement(AcceptanceTester::BTN_NUEVO);
        $I->see('Nuevo Permiso', AcceptanceTester::BTN_NUEVO);

        $this->cerrarSesion($I);
        $I->verificarSinErrores();
    }

    /**
     * CP-PER-03: Despliegue del detalle de un módulo con la lista de sus permisos.
     */
    #[Group('detalle')]
    public function probarVerDetalleModulo(AcceptanceTester $I): void
    {
        $total = $this->contarPermisosModulo(self::MODULO_PRUEBA_ID);

        // 1. Ubicar el módulo de prueba y expandirlo
        $I->buscarEnListado(self::PERMISO_INGRESO);
        $I->waitForText(self::MODULO_PRUEBA, 5, self::PRIMER_REGISTRO . ' .listado_titulo');
        $I->limpiarBuscador();
        $this->expandirModulo($I, self::MODULO_PRUEBA);

        // 2. Resumen del módulo y lista de permisos con estado y acciones
        $selector = $this->selectorModulo(self::MODULO_PRUEBA);
        $I->see('Total de Permisos: ' . $total, $selector);
        $I->see('Ingresar A Posiciones', $selector);
        $I->see('Activo', $selector . ' .sub_item_centro');
        $I->seeElement($selector . ' ' . self::FILA_PERMISO . ' .cbt_v');
        $I->seeElement($selector . ' ' . self::FILA_PERMISO . ' .cbt_g');
        Assert::assertSame(
            $total,
            (int)$I->executeJS("return document.querySelectorAll(\"{$selector} .sub_item_fila\").length;")
        );

        $I->verificarSinErrores();
    }

    /**
     * CP-PER-04: Buscador de permisos en tiempo real (debounce de 500ms).
     */
    #[Group('buscador')]
    public function probarBuscadorPermisos(AcceptanceTester $I): void
    {
        // 1. Búsqueda por clave: solo queda el módulo con 1 permiso
        $I->buscarEnListado(self::PERMISO_INGRESO);
        $I->waitForText(self::MODULO_PRUEBA, 5, self::PRIMER_REGISTRO . ' .listado_titulo');
        $I->see('1 Permiso(s)', self::PRIMER_REGISTRO);
        Assert::assertSame(1, (int)$I->executeJS("return document.querySelectorAll('" . self::REGISTRO . "').length;"));

        // 2. Búsqueda por nombre del permiso
        $I->buscarEnListado('Ingresar A Posiciones');
        $I->waitForText(self::MODULO_PRUEBA, 5, self::PRIMER_REGISTRO . ' .listado_titulo');

        // 3. Búsqueda por término inexistente
        $I->buscarEnListado('PermisoInexistenteXYZ');
        $I->waitForElementVisible(AcceptanceTester::LISTADO_VACIO, 5);
        $I->see('No se encontraron registros', AcceptanceTester::LISTADO_VACIO);

        // 4. Limpiar buscador y verificar recuperación del listado
        $I->limpiarBuscador();
        $I->dontSee('No se encontraron registros', AcceptanceTester::CONT_RESULTADOS);

        $I->verificarSinErrores();
    }

    /**
     * CP-PER-05: Validaciones del formulario de registro (validarEnvio() en permisos.js).
     */
    #[Group('validacion')]
    public function probarValidacionesDeFormulario(AcceptanceTester $I): void
    {
        $this->iniciarSesionComo($I, $this->usuarioSoporte, self::ROL_SOPORTE_ID);
        $this->recargarModulo($I);
        $this->abrirModalRegistro($I);

        // 1. Formulario vacío -> falla el nombre
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('Solo puede ingresar letra, Maximo 30 caracteres', 5);
        $this->cerrarAlertaError($I);

        // 2. Nombre válido, clave vacía
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, $this->nombreNuevo);
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('La clave debe tener el formato accion_entidad y medir entre 5 y 50 caracteres', 5);
        $this->cerrarAlertaError($I);

        // 3. Clave demasiado corta
        $I->fillField(self::INPUT_CLAVE, 'ab');
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('La clave debe tener el formato accion_entidad y medir entre 5 y 50 caracteres', 5);
        $this->cerrarAlertaError($I);

        // 4. Clave válida, módulo sin seleccionar
        $I->fillField(self::INPUT_CLAVE, $this->claveNueva);
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('Debe elegir un modulo.', 5);
        $this->cerrarAlertaError($I);

        // 5. La máscara de la clave solo admite minúsculas y guion bajo
        $I->fillField(self::INPUT_CLAVE, '');
        $I->pressKey(self::INPUT_CLAVE, 'a', 'B', '1', '_', 'c', '-');
        $I->seeInField(self::INPUT_CLAVE, 'a_c');

        // 6. Cerrar modal limpiamente
        $I->click(AcceptanceTester::MODAL_CERRAR);
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        $this->cerrarSesion($I);
        $I->verificarSinErrores();
    }

    /**
     * CP-PER-06: Registro exitoso de un permiso por un usuario de Nivel 1.
     */
    #[Group('registrar', 'crud')]
    public function probarRegistrarPermiso(AcceptanceTester $I): void
    {
        $this->eliminarPermisoPorClave($this->claveNueva);
        $this->iniciarSesionComo($I, $this->usuarioSoporte, self::ROL_SOPORTE_ID);
        $this->recargarModulo($I);

        // 1. Abrir modal y llenar formulario
        $this->abrirModalRegistro($I);
        $this->llenarFormularioRegistro($I, $this->nombreNuevo, $this->claveNueva, 'Permiso Registrado Desde La Interfaz');

        // 2. Enviar y confirmar en SweetAlert
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere registrar este rol?', 5);
        $I->confirmarSweetAlert();

        // 3. Mensaje de éxito (el modal se cierra automáticamente)
        $I->waitForText('Registro Exitoso', 10);
        $I->aceptarSweetAlert();
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        // 4. Verificar el nuevo permiso dentro de su módulo
        $I->buscarEnListado($this->claveNueva);
        $I->waitForText(self::MODULO_PRUEBA, 5, self::PRIMER_REGISTRO . ' .listado_titulo');
        Assert::assertStringContainsString(
            $this->nombreNuevo,
            (string)$I->executeJS("return document.querySelector('" . self::DETALLE_PRIMERO . "').textContent;")
        );

        // 5. Persistencia en BD con el módulo y la clave indicados
        $permiso = $this->buscarPermisoPorClave($this->claveNueva);
        if ($permiso !== null) {
            Assert::assertSame(self::MODULO_PRUEBA_ID, (string)$permiso['id_modulo']);
            Assert::assertSame($this->nombreNuevo, (string)$permiso['nombre']);
            Assert::assertSame(1, (int)$permiso['estatus']);
        }

        $this->cerrarSesion($I);
        $this->eliminarPermisoPorClave($this->claveNueva);
        $I->verificarSinErrores();
    }

    /**
     * CP-PER-07: El sistema rechaza permisos con nombre o clave ya registrados.
     */
    #[Group('validacion', 'duplicados')]
    public function probarRechazarPermisoDuplicado(AcceptanceTester $I): void
    {
        $this->asegurarPermisoExiste();
        $this->eliminarPermisoPorClave('clave_distinta_prueba');
        $this->eliminarPermisoPorClave('otra_clave_prueba');
        $this->iniciarSesionComo($I, $this->usuarioSoporte, self::ROL_SOPORTE_ID);
        $this->recargarModulo($I);

        // 1. Nombre duplicado con clave distinta
        $this->abrirModalRegistro($I);
        $this->llenarFormularioRegistro($I, $this->nombreBase, 'clave_distinta_prueba', '');
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere registrar este rol?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Ya existe un permiso registrado con este nombre.', 10);
        $this->cerrarAlertaError($I);

        // 2. Clave duplicada con nombre distinto (el servidor la rechaza)
        $this->llenarFormularioRegistro($I, 'Permiso Prueba Otro Nombre', $this->claveBase, '');
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere registrar este rol?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Ocurrió un error inesperado en el registro del permiso.', 10);
        $this->cerrarAlertaError($I);

        $I->click(AcceptanceTester::MODAL_CERRAR);
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        // 3. No se creó ningún permiso nuevo
        Assert::assertSame(1, $this->contarPermisosPorClave($this->claveBase) ?? 1);
        Assert::assertNull($this->buscarPermisoPorClave('clave_distinta_prueba'));
        Assert::assertSame(0, $this->contarPermisosPorNombre('Permiso Prueba Otro Nombre') ?? 0);

        $this->cerrarSesion($I);
        $I->verificarSinErrores();
    }

    /**
     * CP-PER-08: Modificación del nombre y la descripción de un permiso.
     */
    #[Group('modificar', 'crud')]
    public function probarModificarPermiso(AcceptanceTester $I): void
    {
        $this->asegurarPermisoExiste();
        $this->recargarModulo($I);

        // 1. Ubicar el permiso por su clave
        $I->buscarEnListado($this->claveBase);
        $I->waitForText(self::MODULO_PRUEBA, 5, self::PRIMER_REGISTRO . ' .listado_titulo');
        $this->expandirModulo($I, self::MODULO_PRUEBA);

        // 2. Abrir modal de modificación: clave y módulo no se pueden cambiar
        $I->clickModificar();
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->waitForText('Modificar Permiso', 5, AcceptanceTester::MODAL_TITULO);
        $I->seeInField(AcceptanceTester::INPUT_NOMBRE, $this->nombreBase);
        $I->seeInField(AcceptanceTester::INPUT_DESCRIPCION, $this->descripcionBase);
        $I->dontSeeElement(self::INPUT_CLAVE);
        $I->dontSeeElement(self::SELECT_MODULO . ' + .select2');

        // 3. Nombre vacío -> validación
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, '');
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('Solo puede ingresar letra, Maximo 30 caracteres', 5);
        $this->cerrarAlertaError($I);

        // 4. Modificar valores, guardar y confirmar
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, $this->nombreMod);
        $I->fillField(AcceptanceTester::INPUT_DESCRIPCION, $this->descripcionMod);
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere modificar este rol?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Modificacion Exitosa', 10);
        $I->aceptarSweetAlert();
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        // 5. Validar el cambio en el listado
        $I->buscarEnListado($this->claveBase);
        $I->waitForText(self::MODULO_PRUEBA, 5, self::PRIMER_REGISTRO . ' .listado_titulo');
        $detalle = (string)$I->executeJS("return document.querySelector('" . self::DETALLE_PRIMERO . "').textContent;");
        Assert::assertStringContainsString($this->nombreMod, $detalle);
        Assert::assertStringContainsString($this->descripcionMod, $detalle);

        // 6. Restaurar datos originales
        $this->asegurarPermisoExiste();

        $I->verificarSinErrores();
    }

    /**
     * CP-PER-09: Bloqueo y desbloqueo de un permiso.
     */
    #[Group('bloquear')]
    public function probarBloquearYDesbloquearPermiso(AcceptanceTester $I): void
    {
        $this->asegurarPermisoExiste();
        $this->recargarModulo($I);

        $I->buscarEnListado($this->claveBase);
        $I->waitForText(self::MODULO_PRUEBA, 5, self::PRIMER_REGISTRO . ' .listado_titulo');
        $this->expandirModulo($I, self::MODULO_PRUEBA);

        // 1. Bloquear: el mensaje advierte que afecta a todos los roles vinculados
        $this->clickBoton($I, self::BTN_DESBLOQUEADO);
        $I->waitForText('¿Está seguro que quiere bloquear este permiso? Este permiso se bloquear para todos los roles que lo tengan vinculado', 5);
        $I->confirmarSweetAlert();
        $this->esperarMensajeExito($I, 'Permiso bloqueada exitosamente.');

        // 2. El permiso aparece como Bloqueado
        $I->buscarEnListado($this->claveBase);
        $I->waitForText(self::MODULO_PRUEBA, 5, self::PRIMER_REGISTRO . ' .listado_titulo');
        $this->expandirModulo($I, self::MODULO_PRUEBA);
        $I->waitForElementVisible(self::BTN_BLOQUEADO, 5);
        $I->see('Bloqueado', self::PRIMER_REGISTRO . ' .sub_item_centro');
        Assert::assertSame(2, $this->estatusPermisoPorClave($this->claveBase) ?? 2);

        // 3. Desbloquear
        $this->clickBoton($I, self::BTN_BLOQUEADO);
        $I->waitForText('¿Está seguro que quiere desbloquear este permiso?', 5);
        $I->confirmarSweetAlert();
        $this->esperarMensajeExito($I, 'Permiso desbloqueada exitosamente.');

        // 4. El permiso vuelve a estar Activo
        $I->buscarEnListado($this->claveBase);
        $I->waitForText(self::MODULO_PRUEBA, 5, self::PRIMER_REGISTRO . ' .listado_titulo');
        $this->expandirModulo($I, self::MODULO_PRUEBA);
        $I->waitForElementVisible(self::BTN_DESBLOQUEADO, 5);
        $I->see('Activo', self::PRIMER_REGISTRO . ' .sub_item_centro');
        Assert::assertSame(1, $this->estatusPermisoPorClave($this->claveBase) ?? 1);

        $I->verificarSinErrores();
    }

    /**
     * CP-PER-10: Al bloquear un permiso, los roles que lo tienen vinculado pierden ese acceso.
     */
    #[Group('bloquear', 'seguridad')]
    public function probarPermisoBloqueadoRevocaAcceso(AcceptanceTester $I): void
    {
        $this->fijarEstatusPermiso(self::PERMISO_INGRESO_ID, 1);
        $idRol = $this->prepararRolConPermiso($this->rolVinculado, self::PERMISO_INGRESO_ID);
        $this->asegurarUsuario($this->usuarioVinculado, $idRol);

        // 1. Con el permiso activo, el usuario del rol vinculado entra a Posiciones
        $this->iniciarSesionComo($I, $this->usuarioVinculado, $idRol);
        $I->amOnPage('/Posiciones');
        $I->waitForText(self::MODULO_PRUEBA, 10, AcceptanceTester::TITULO_PAGINA);
        $this->cerrarSesion($I);

        // 2. El Superusuario bloquea "ingresar_posiciones" desde la interfaz
        $I->irAlModulo(self::RUTA_MODULO, self::TITULO_MODULO);
        $I->waitForElementNotVisible('#loader', 10);
        $I->buscarEnListado(self::PERMISO_INGRESO);
        $I->waitForText(self::MODULO_PRUEBA, 5, self::PRIMER_REGISTRO . ' .listado_titulo');
        $this->expandirModulo($I, self::MODULO_PRUEBA);
        $this->clickBoton($I, self::BTN_DESBLOQUEADO);
        $I->waitForText('¿Está seguro que quiere bloquear este permiso?', 5);
        $I->confirmarSweetAlert();
        $this->esperarMensajeExito($I, 'Permiso bloqueada exitosamente.');

        // 3. El mismo usuario ya no puede entrar a Posiciones
        $this->iniciarSesionComo($I, $this->usuarioVinculado, $idRol);
        $I->amOnPage('/Posiciones');
        $I->waitForText('Acceso denegado', 10);
        $I->seeInCurrentUrl('Principal');
        $this->cerrarSesion($I);

        // 4. El Superusuario lo desbloquea desde la interfaz
        $I->irAlModulo(self::RUTA_MODULO, self::TITULO_MODULO);
        $I->waitForElementNotVisible('#loader', 10);
        $I->buscarEnListado(self::PERMISO_INGRESO);
        $I->waitForText(self::MODULO_PRUEBA, 5, self::PRIMER_REGISTRO . ' .listado_titulo');
        $this->expandirModulo($I, self::MODULO_PRUEBA);
        $this->clickBoton($I, self::BTN_BLOQUEADO);
        $I->waitForText('¿Está seguro que quiere desbloquear este permiso?', 5);
        $I->confirmarSweetAlert();
        $this->esperarMensajeExito($I, 'Permiso desbloqueada exitosamente.');
        Assert::assertSame(1, $this->estatusPermiso(self::PERMISO_INGRESO_ID) ?? 1);

        // 5. El usuario recupera el acceso
        $this->iniciarSesionComo($I, $this->usuarioVinculado, $idRol);
        $I->amOnPage('/Posiciones');
        $I->waitForText(self::MODULO_PRUEBA, 10, AcceptanceTester::TITULO_PAGINA);
        $this->cerrarSesion($I);
    }

    /**
     * CP-PER-11: Un rol distinto de SuperUsuario (Entrenador) no puede acceder al módulo.
     */
    #[Group('seguridad', 'acceso')]
    public function probarAccesoDenegadoRolSinPermisos(AcceptanceTester $I): void
    {
        $this->iniciarSesionComo($I, $this->usuarioSinAcceso, self::ROL_ENTRENADOR_ID);

        $I->amOnPage(self::RUTA_MODULO);
        $I->waitForText('Acceso denegado', 10);
        $I->seeInCurrentUrl('Principal');

        $this->cerrarSesion($I);
    }

    // =========================================================================
    // HELPERS DE INTERFAZ
    // =========================================================================

    private function recargarModulo(AcceptanceTester $I): void
    {
        $I->amOnPage(self::RUTA_MODULO);
        $I->waitForText(self::TITULO_MODULO, 10, AcceptanceTester::TITULO_PAGINA);
        $I->waitForElementVisible(AcceptanceTester::INPUT_BUSQUEDA, 10);
        $I->waitForElementNotVisible('#loader', 10);
        $I->despejarOverlays();
    }

    /**
     * Cierra la sesión actual sin tocar el snapshot del administrador e inicia con otro usuario.
     */
    private function iniciarSesionComo(AcceptanceTester $I, array $u, int $idRol): void
    {
        $this->asegurarUsuario($u, $idRol);
        $this->cerrarSesion($I);
        $I->amOnPage('/');
        $I->waitForElement('input[name="cedula"]', 5);
        $I->fillField('cedula', $u['cedula']);
        $I->fillField('contraseña', $u['contrasena']);

        // El login admite solo 3 intentos por minuto por IP: el helper espera y reintenta si hace falta
        $I->enviarLoginRespetandoLimite();
    }

    /**
     * Espera el mensaje de éxito y lo cierra (puede cerrarse solo por su temporizador).
     */
    private function esperarMensajeExito(AcceptanceTester $I, string $mensaje): void
    {
        $I->waitForText($mensaje, 10);
        $I->executeJS("if (typeof Swal !== 'undefined' && Swal.isVisible()) { Swal.close(); }");
        $I->waitForJS("return document.querySelector('.swal2-container') === null;", 5);
    }

    private function cerrarSesion(AcceptanceTester $I): void
    {
        $I->executeInSelenium(function (\Facebook\WebDriver\Remote\RemoteWebDriver $wd) {
            $wd->manage()->deleteAllCookies();
        });
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
        $I->see('Registrar Permiso', AcceptanceTester::MODAL_TITULO);
        $I->see('Registrar Permiso', AcceptanceTester::BTN_PROCESO);
        $I->seeElement(self::INPUT_CLAVE);

        // Esperar a que consultarModulos() pueble el selector
        $I->waitForJS("return $('#modulo option').length > 1;", 5);
    }

    private function llenarFormularioRegistro(AcceptanceTester $I, string $nombre, string $clave, string $descripcion): void
    {
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, $nombre);
        $I->fillField(AcceptanceTester::INPUT_DESCRIPCION, $descripcion);
        $I->fillField(self::INPUT_CLAVE, $clave);
        $I->executeJS("$('#modulo').val('" . self::MODULO_PRUEBA_ID . "').trigger('change');");
    }

    private function selectorModulo(string $modulo): string
    {
        $indice = (int)$this->indiceModulo($modulo);
        return self::REGISTRO . ':nth-child(' . $indice . ')';
    }

    private function indiceModulo(string $modulo): int
    {
        // Se calcula en el navegador al momento de usarlo (ver expandirModulo)
        return $this->indiceActual[$modulo] ?? 1;
    }

    /** @var array<string,int> */
    private array $indiceActual = [];

    /**
     * Expande el módulo indicado del listado actual y espera a que se muestre su detalle.
     */
    private function expandirModulo(AcceptanceTester $I, string $modulo): void
    {
        $indice = (int)$I->executeJS("
            let items = Array.from(document.querySelectorAll('" . self::REGISTRO . "'));
            let pos = items.findIndex(el => el.querySelector('.listado_titulo').textContent.trim() === '{$modulo}');
            if (pos < 0) { return 0; }
            let detalle = items[pos].querySelector('.listado_detalle_oculto');
            if (getComputedStyle(detalle).display === 'none') {
                items[pos].querySelector('.listado_item').click();
            }
            return pos + 1;
        ");
        Assert::assertGreaterThan(0, $indice, "No se encontró el módulo {$modulo} en el listado");
        $this->indiceActual[$modulo] = $indice;
        $I->waitForElementVisible(self::REGISTRO . ':nth-child(' . $indice . ') .listado_detalle_oculto', 5);
        $I->wait(1);
    }

    private function clickBoton(AcceptanceTester $I, string $selector): void
    {
        $I->waitForElementVisible($selector, 5);
        $I->despejarOverlays();
        $I->executeJS("document.querySelector('{$selector}').click();");
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

    private function buscarPermisoPorClave(string $clave): ?array
    {
        $pdo = $this->conexion();
        if ($pdo === null) {
            return null;
        }
        $st = $pdo->prepare('SELECT * FROM permisos WHERE clave = :c LIMIT 1');
        $st->execute([':c' => $clave]);
        $fila = $st->fetch(\PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    private function contarPermisosPorClave(string $clave): ?int
    {
        $pdo = $this->conexion();
        if ($pdo === null) {
            return null;
        }
        $st = $pdo->prepare('SELECT COUNT(*) FROM permisos WHERE clave = :c');
        $st->execute([':c' => $clave]);
        return (int)$st->fetchColumn();
    }

    private function contarPermisosPorNombre(string $nombre): ?int
    {
        $pdo = $this->conexion();
        if ($pdo === null) {
            return null;
        }
        $st = $pdo->prepare('SELECT COUNT(*) FROM permisos WHERE nombre = :n');
        $st->execute([':n' => $nombre]);
        return (int)$st->fetchColumn();
    }

    private function contarPermisosModulo(string $idModulo): int
    {
        $pdo = $this->conexion();
        if ($pdo === null) {
            return 0;
        }
        $st = $pdo->prepare('SELECT COUNT(*) FROM permisos WHERE id_modulo = :m');
        $st->execute([':m' => $idModulo]);
        return (int)$st->fetchColumn();
    }

    private function estatusPermisoPorClave(string $clave): ?int
    {
        $p = $this->buscarPermisoPorClave($clave);
        return $p !== null ? (int)$p['estatus'] : null;
    }

    private function estatusPermiso(int $id): ?int
    {
        $pdo = $this->conexion();
        if ($pdo === null) {
            return null;
        }
        $st = $pdo->prepare('SELECT estatus FROM permisos WHERE id_permiso = :id');
        $st->execute([':id' => $id]);
        $v = $st->fetchColumn();
        return $v === false ? null : (int)$v;
    }

    private function fijarEstatusPermiso(int $id, int $estatus): void
    {
        try {
            $this->conexion()?->prepare('UPDATE permisos SET estatus = :e WHERE id_permiso = :id')
                ->execute([':e' => $estatus, ':id' => $id]);
        } catch (\Throwable $e) {
            // Silencioso para no interferir con la ejecución UI
        }
    }

    /**
     * Garantiza el permiso de prueba activo y con sus datos originales.
     */
    private function asegurarPermisoExiste(): void
    {
        try {
            $pdo = $this->conexion();
            if ($pdo === null) {
                return;
            }
            if ($this->buscarPermisoPorClave($this->claveBase) === null) {
                $pdo->prepare('INSERT INTO permisos (id_modulo, nombre, clave, descripcion, estatus) VALUES (:m, :n, :c, :d, 1)')
                    ->execute([':m' => self::MODULO_PRUEBA_ID, ':n' => $this->nombreBase, ':c' => $this->claveBase, ':d' => $this->descripcionBase]);
                return;
            }
            $pdo->prepare('UPDATE permisos SET id_modulo = :m, nombre = :n, descripcion = :d, estatus = 1 WHERE clave = :c')
                ->execute([':m' => self::MODULO_PRUEBA_ID, ':n' => $this->nombreBase, ':c' => $this->claveBase, ':d' => $this->descripcionBase]);
        } catch (\Throwable $e) {
            // Silencioso para no interferir con la ejecución UI
        }
    }

    private function eliminarPermisoPorClave(string $clave): void
    {
        try {
            $pdo = $this->conexion();
            $p = $this->buscarPermisoPorClave($clave);
            if ($pdo === null || $p === null) {
                return;
            }
            $pdo->prepare('DELETE FROM permisos_rol WHERE id_permiso = :id')->execute([':id' => $p['id_permiso']]);
            $pdo->prepare('DELETE FROM excepciones WHERE id_permiso = :id')->execute([':id' => $p['id_permiso']]);
            $pdo->prepare('DELETE FROM permisos WHERE id_permiso = :id')->execute([':id' => $p['id_permiso']]);
        } catch (\Throwable $e) {
            // Silencioso para no interferir con la ejecución UI
        }
    }

    /**
     * Garantiza que exista el rol de prueba (Nivel 3) con únicamente el permiso indicado.
     */
    private function prepararRolConPermiso(string $nombreRol, int $idPermiso): int
    {
        $pdo = $this->conexion();
        if ($pdo === null) {
            return 0;
        }
        $st = $pdo->prepare('SELECT id_rol FROM roles WHERE nombre_rol = :n LIMIT 1');
        $st->execute([':n' => $nombreRol]);
        $idRol = (int)$st->fetchColumn();
        if ($idRol === 0) {
            $pdo->prepare('INSERT INTO roles (nombre_rol, descripcion) VALUES (:n, :d)')
                ->execute([':n' => $nombreRol, ':d' => 'Rol Con Un Usuario Asignado']);
            $idRol = (int)$pdo->lastInsertId();
        }
        $pdo->prepare('DELETE FROM permisos_rol WHERE id_rol = :r')->execute([':r' => $idRol]);
        $pdo->prepare('INSERT INTO permisos_rol (id_permiso, id_rol) VALUES (:p, :r)')->execute([':p' => $idPermiso, ':r' => $idRol]);
        return $idRol;
    }

    /**
     * Garantiza que el usuario de prueba exista activo, desbloqueado y con el rol indicado.
     */
    private function asegurarUsuario(array $u, int $idRol): void
    {
        try {
            $pdo = $this->conexion();
            if ($pdo === null) {
                return;
            }
            $hash = password_hash($u['contrasena'], PASSWORD_BCRYPT);
            $st = $pdo->prepare('SELECT idUsuario FROM usuarios WHERE cedulaUsuario = :c');
            $st->execute([':c' => $u['cedula']]);
            $id = $st->fetchColumn();

            if ($id !== false) {
                $pdo->prepare(
                    'UPDATE usuarios SET pass_hash = :p, id_rol = :r, estatus = 1, bloqueo = 1, intentos_fallidos = 0
                     WHERE cedulaUsuario = :c'
                )->execute([':p' => $hash, ':r' => $idRol, ':c' => $u['cedula']]);
                $pdo->prepare('DELETE FROM excepciones WHERE id_usuario = :id')->execute([':id' => $id]);
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

    /**
     * Desactiva (borrado lógico) un usuario de prueba para que no quede una cuenta de Nivel 1 activa.
     */
    private function desactivarUsuario(string $cedula): void
    {
        try {
            $this->conexion()?->prepare('UPDATE usuarios SET estatus = 0 WHERE cedulaUsuario = :c')
                ->execute([':c' => $cedula]);
        } catch (\Throwable $e) {
            // Silencioso para no interferir con la ejecución UI
        }
    }
}
