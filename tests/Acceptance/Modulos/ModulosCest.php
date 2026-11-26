<?php

declare(strict_types=1);

namespace Tests\Acceptance\Modulos;

use Codeception\Attribute\Group;
use PHPUnit\Framework\Assert;
use Tests\Support\AcceptanceTester;

if (!defined('_DB_NAME_')) {
    require_once __DIR__ . '/../../../config/config.php';
}

/**
 * Pruebas de interfaz (aceptación) del módulo Módulos.
 *
 * Escenarios cubiertos:
 *  - CP-MOD-01: Carga inicial de la interfaz.
 *  - CP-MOD-02: Los módulos internos del sistema (Inicio de Sesión, Cerrar Sesión y
 *               Recuperación de Contraseña) no se listan.
 *  - CP-MOD-03: Solo se permite modificar (no hay opciones para registrar ni eliminar módulos).
 *  - CP-MOD-04: Buscador en tiempo real (coincidencia exacta, parcial, sin resultados y restauración).
 *  - CP-MOD-05: Validaciones del formulario de modificación.
 *  - CP-MOD-06: Modificación exitosa del nombre y la descripción de un módulo.
 *  - CP-MOD-07: Rechazo de un nombre de módulo duplicado.
 *  - CP-MOD-08: Acceso denegado al módulo para un rol distinto de SuperUsuario.
 */
#[Group('modulos')]
#[Group('interfaz')]
final class ModulosCest
{
    private const RUTA_MODULO          = '/Modulos';
    private const TITULO_MODULO        = 'Modulos';

    private const REGISTRO             = '#resultadoconsulta .listado_contenedor_grupal';
    private const PRIMER_REGISTRO      = '#resultadoconsulta .listado_contenedor_grupal:first-child';

    // Módulos internos que la consulta excluye (ModeloModulos::Consultar)
    private const MODULOS_OCULTOS      = ['Inicio de Sesion', 'Cerrar Sesion', 'Recuperacion De Contraseña'];

    // Módulo usado para las modificaciones (se restaura siempre en _after)
    private const MODULO_PRUEBA_ID     = 21;
    private const MODULO_PRUEBA        = 'Premios';
    private const MODULO_EXISTENTE     = 'Torneos';

    private string $nombreMod          = 'Premios Prueba Interfaz';
    private string $descripcionMod     = 'Modulo Modificado Por Las Pruebas';

    /** Nombre y descripción originales del módulo de prueba, leídos de la BD. */
    private ?array $original = null;

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
        $this->original = $this->leerModulo(self::MODULO_PRUEBA_ID);

        // Login con snapshot + navegación a /Modulos + verificación de título y limpieza de overlays
        $I->irAlModulo(self::RUTA_MODULO, self::TITULO_MODULO);
        $I->waitForElementNotVisible('#loader', 10);
    }

    public function _after(AcceptanceTester $I): void
    {
        // El módulo de prueba siempre vuelve a su nombre y descripción originales
        if ($this->original !== null) {
            $this->restaurarModulo(self::MODULO_PRUEBA_ID, $this->original['nombre_modulo'], $this->original['descripcion']);
        }
    }

    /**
     * CP-MOD-01: Verifica la carga inicial de la interfaz del módulo de Módulos.
     */
    #[Group('carga', 'interfaz')]
    public function probarCargaInicialDeInterfaz(AcceptanceTester $I): void
    {
        $I->see(self::TITULO_MODULO, AcceptanceTester::TITULO_PAGINA);
        $I->seeElement(AcceptanceTester::INPUT_BUSQUEDA);
        $I->seeElement(AcceptanceTester::CONT_RESULTADOS);
        $I->seeElement(AcceptanceTester::ITEM_LISTADO);

        // Cada registro muestra el nombre del módulo y su descripción
        $I->see('Usuarios', self::PRIMER_REGISTRO . ' .listado_titulo');
        $I->see('Descripción', self::PRIMER_REGISTRO);

        // El modal existe en el DOM pero debe arrancar oculto
        $I->seeElementInDOM(AcceptanceTester::MODAL);
        $I->dontSeeElement(AcceptanceTester::MODAL);

        $I->verificarSinErrores();
    }

    /**
     * CP-MOD-02: Los módulos internos del sistema no aparecen en el listado.
     */
    #[Group('seguridad')]
    public function probarModulosInternosNoSeListan(AcceptanceTester $I): void
    {
        // 1. El listado tiene todos los módulos menos los 3 internos
        $titulos = $this->titulosListados($I);
        $total = $this->contarModulos();
        if ($total !== null) {
            Assert::assertCount($total - count(self::MODULOS_OCULTOS), $titulos);
        }

        // 2. Ninguno de los módulos internos está en el listado ni aparece al buscarlo
        foreach (self::MODULOS_OCULTOS as $oculto) {
            Assert::assertNotContains($oculto, $titulos);
        }
        $I->buscarEnListado('Cerrar Sesion');
        $I->waitForText('No se encontraron registros', 5, AcceptanceTester::CONT_RESULTADOS);
        $I->limpiarBuscador();

        $I->verificarSinErrores();
    }

    /**
     * CP-MOD-03: Los módulos solo se pueden modificar; no se registran ni se eliminan desde la interfaz.
     */
    #[Group('seguridad')]
    public function probarSoloSePermiteModificar(AcceptanceTester $I): void
    {
        $I->dontSeeElementInDOM(AcceptanceTester::BTN_NUEVO);
        $I->dontSeeElementInDOM(AcceptanceTester::BTN_REPORTE);
        $I->dontSeeElementInDOM(AcceptanceTester::BTN_ELIMINAR);

        // Cada módulo listado tiene su botón de modificar
        $registros = (int)$I->executeJS("return document.querySelectorAll('" . self::REGISTRO . "').length;");
        $botones   = (int)$I->executeJS("return document.querySelectorAll('" . AcceptanceTester::BTN_MODIFICAR . "').length;");
        Assert::assertGreaterThan(0, $registros);
        Assert::assertSame($registros, $botones);

        $I->verificarSinErrores();
    }

    /**
     * CP-MOD-04: Buscador de módulos en tiempo real (debounce de 500ms).
     */
    #[Group('buscador')]
    public function probarBuscadorModulos(AcceptanceTester $I): void
    {
        // 1. Coincidencia exacta: un solo resultado
        $I->buscarEnListado(self::MODULO_PRUEBA);
        $I->waitForText(self::MODULO_PRUEBA, 5, self::PRIMER_REGISTRO . ' .listado_titulo');
        Assert::assertSame([self::MODULO_PRUEBA], $this->titulosListados($I));

        // 2. Coincidencia parcial: todos los resultados contienen el término
        $I->buscarEnListado('Pago');
        $I->waitForText('Pagos', 5, AcceptanceTester::CONT_RESULTADOS);
        $titulos = $this->titulosListados($I);
        Assert::assertContains('Pagos', $titulos);
        Assert::assertContains('Metodos de Pago', $titulos);
        foreach ($titulos as $t) {
            Assert::assertStringContainsStringIgnoringCase('Pago', $t);
        }

        // 3. Término inexistente
        $I->buscarEnListado('ModuloInexistenteXYZ');
        $I->waitForElementVisible(AcceptanceTester::LISTADO_VACIO, 5);
        $I->see('No se encontraron registros', AcceptanceTester::LISTADO_VACIO);

        // 4. Limpiar buscador y verificar recuperación del listado
        $I->limpiarBuscador();
        $I->dontSee('No se encontraron registros', AcceptanceTester::CONT_RESULTADOS);

        $I->verificarSinErrores();
    }

    /**
     * CP-MOD-05: Validaciones del formulario de modificación (validarEnvio() en modulos.js).
     */
    #[Group('validacion')]
    public function probarValidacionesDeFormulario(AcceptanceTester $I): void
    {
        // 1. Abrir el modal con los datos precargados
        $this->abrirModalModificar($I, self::MODULO_PRUEBA);

        // 2. Nombre vacío
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, '');
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('Tiene que ingresar un nombre de modulo valido', 5);
        $this->cerrarAlertaError($I);

        // 3. Nombre con menos de 3 letras
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, 'Pr');
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('Tiene que ingresar un nombre de modulo valido', 5);
        $this->cerrarAlertaError($I);

        // 4. Nombre válido, descripción vacía
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, self::MODULO_PRUEBA);
        $I->fillField(AcceptanceTester::INPUT_DESCRIPCION, '');
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('Tiene que ingresar una descripcion valida', 5);
        $this->cerrarAlertaError($I);

        // 5. La máscara de entrada bloquea números y símbolos en el nombre
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, '');
        $I->pressKey(AcceptanceTester::INPUT_NOMBRE, 'P', 'r', 'e', '1', '$');
        $I->seeInField(AcceptanceTester::INPUT_NOMBRE, 'Pre');

        // 6. Cerrar el modal sin guardar: el módulo no cambia
        $I->click(AcceptanceTester::MODAL_CERRAR);
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);
        if ($this->original !== null) {
            Assert::assertSame($this->original, $this->leerModulo(self::MODULO_PRUEBA_ID));
        }

        $I->verificarSinErrores();
    }

    /**
     * CP-MOD-06: Modificación exitosa del nombre y la descripción de un módulo.
     */
    #[Group('modificar', 'crud')]
    public function probarModificarModulo(AcceptanceTester $I): void
    {
        // 1. Abrir el modal y cambiar los datos
        $this->abrirModalModificar($I, self::MODULO_PRUEBA);
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, $this->nombreMod);
        $I->fillField(AcceptanceTester::INPUT_DESCRIPCION, $this->descripcionMod);

        // 2. Guardar y confirmar
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere modificar este modulo?', 5);
        $I->confirmarSweetAlert();

        // 3. Mensaje de éxito (el modal se cierra automáticamente)
        $I->waitForText('Modificacion Exitosa', 10);
        $I->see('Modulo modificado exitosamente.');
        $I->aceptarSweetAlert();
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        // 4. El listado refleja el cambio
        $I->buscarEnListado($this->nombreMod);
        $I->waitForText($this->nombreMod, 5, self::PRIMER_REGISTRO . ' .listado_titulo');
        $I->see($this->descripcionMod, self::PRIMER_REGISTRO);

        // 5. Persistencia en BD
        $modulo = $this->leerModulo(self::MODULO_PRUEBA_ID);
        if ($modulo !== null) {
            Assert::assertSame($this->nombreMod, $modulo['nombre_modulo']);
            Assert::assertSame($this->descripcionMod, $modulo['descripcion']);
        }

        $I->verificarSinErrores();
    }

    /**
     * CP-MOD-07: No se permite renombrar un módulo con el nombre de otro existente.
     */
    #[Group('validacion', 'duplicados')]
    public function probarRechazarNombreDuplicado(AcceptanceTester $I): void
    {
        $this->abrirModalModificar($I, self::MODULO_PRUEBA);
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, self::MODULO_EXISTENTE);

        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere modificar este modulo?', 5);
        $I->confirmarSweetAlert();

        $I->waitForText('Ya existe un modulo registrado con ese nombre.', 10);
        $this->cerrarAlertaError($I);

        // El modal permanece abierto y el módulo conserva su nombre
        $I->seeElement(AcceptanceTester::MODAL);
        $I->click(AcceptanceTester::MODAL_CERRAR);
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);
        $modulo = $this->leerModulo(self::MODULO_PRUEBA_ID);
        if ($modulo !== null) {
            Assert::assertSame(self::MODULO_PRUEBA, $modulo['nombre_modulo']);
        }

        $I->verificarSinErrores();
    }

    /**
     * CP-MOD-08: Un rol distinto de SuperUsuario (Entrenador) no puede acceder al módulo.
     */
    #[Group('seguridad', 'acceso')]
    public function probarAccesoDenegadoRolSinPermisos(AcceptanceTester $I): void
    {
        $this->asegurarUsuarioConRol($this->usuarioSinAcceso, self::ROL_ENTRENADOR_ID);

        // 1. Cerrar la sesión del administrador (sin tocar el snapshot)
        $this->cerrarSesion($I);

        // 2. Iniciar sesión con el usuario de rol Entrenador
        $I->amOnPage('/');
        $I->waitForElement('input[name="cedula"]', 5);
        $I->fillField('cedula', $this->usuarioSinAcceso['cedula']);
        $I->fillField('contraseña', $this->usuarioSinAcceso['contrasena']);
        $I->enviarLoginRespetandoLimite();

        // 3. Intentar ingresar directamente al módulo de Módulos
        $I->amOnPage(self::RUTA_MODULO);

        // 4. Debe redirigir a Principal con la alerta de acceso denegado
        //    (se valida el contenedor #alerta-backend, que sigue en el DOM aunque el SweetAlert ya se haya cerrado)
        $I->waitForElement('#alerta-backend', 15);
        $I->seeInCurrentUrl('Principal');
        $I->seeElementInDOM('#alerta-backend', ['data-titulo' => 'Acceso denegado']);

        // 5. Cerrar la sesión de prueba para que las demás pruebas usen el snapshot del admin
        $this->cerrarSesion($I);
    }

    // =========================================================================
    // HELPERS DE INTERFAZ
    // =========================================================================

    /**
     * Busca el módulo, abre su modal de modificación y verifica los datos precargados.
     */
    private function abrirModalModificar(AcceptanceTester $I, string $modulo): void
    {
        $I->buscarEnListado($modulo);
        $I->waitForText($modulo, 5, self::PRIMER_REGISTRO . ' .listado_titulo');
        $I->clickModificar();
        $I->waitForElementVisible(AcceptanceTester::MODAL, 10);
        $I->waitForText('Modificar Modulo', 5, AcceptanceTester::MODAL_TITULO);
        $I->see('Modificar Modulo', AcceptanceTester::BTN_PROCESO);
        $I->seeInField(AcceptanceTester::INPUT_NOMBRE, $modulo);
        if ($this->original !== null) {
            $I->seeInField(AcceptanceTester::INPUT_DESCRIPCION, $this->original['descripcion']);
        }
    }

    /**
     * Devuelve los títulos de todos los módulos del listado (incluidos los que oculta el paginador).
     */
    private function titulosListados(AcceptanceTester $I): array
    {
        return (array)$I->executeJS(
            "return Array.from(document.querySelectorAll('" . self::REGISTRO . " .listado_titulo')).map(e => e.textContent.trim());"
        );
    }

    private function cerrarAlertaError(AcceptanceTester $I): void
    {
        $I->aceptarSweetAlert();
        $I->waitForJS("return document.querySelector('.swal2-container') === null;", 5);
        $I->despejarOverlays();
    }

    private function cerrarSesion(AcceptanceTester $I): void
    {
        $I->executeInSelenium(function (\Facebook\WebDriver\Remote\RemoteWebDriver $wd) {
            $wd->manage()->deleteAllCookies();
        });
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

    private function leerModulo(int $id): ?array
    {
        $pdo = $this->conexion();
        if ($pdo === null) {
            return null;
        }
        $st = $pdo->prepare('SELECT nombre_modulo, descripcion FROM modulos WHERE id_modulo = :id');
        $st->execute([':id' => $id]);
        $fila = $st->fetch(\PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    private function contarModulos(): ?int
    {
        $pdo = $this->conexion();
        return $pdo === null ? null : (int)$pdo->query('SELECT COUNT(*) FROM modulos')->fetchColumn();
    }

    private function restaurarModulo(int $id, string $nombre, string $descripcion): void
    {
        try {
            $this->conexion()?->prepare('UPDATE modulos SET nombre_modulo = :n, descripcion = :d WHERE id_modulo = :id')
                ->execute([':n' => $nombre, ':d' => $descripcion, ':id' => $id]);
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
                    'UPDATE usuarios SET pass_hash = :p, id_rol = :r, estatus = 1, bloqueo = 1, intentos_fallidos = 0
                     WHERE cedulaUsuario = :c'
                )->execute([':p' => $hash, ':r' => $idRol, ':c' => $u['cedula']]);
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
