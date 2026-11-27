<?php

declare(strict_types=1);

namespace Tests\Acceptance\Modulos;

use Codeception\Attribute\Group;
use PHPUnit\Framework\Assert;
use Tests\Support\AcceptanceTester;

if (!defined('_DB_NAME_')) {
    require_once __DIR__ . '/../../../config/config.php';
}

#[Group('modulos')]
#[Group('interfaz')]
final class ModulosCest
{
    private const RUTA_MODULO          = '/Modulos';
    private const TITULO_MODULO        = 'Modulos';

    private const REGISTRO             = '#resultadoconsulta .listado_contenedor_grupal';
    private const PRIMER_REGISTRO      = '#resultadoconsulta .listado_contenedor_grupal:first-child';

    private const MODULOS_OCULTOS      = ['Inicio de Sesion', 'Cerrar Sesion', 'Recuperacion De Contraseña'];

    private const MODULO_PRUEBA_ID     = 21;
    private const MODULO_PRUEBA        = 'Premios';
    private const MODULO_EXISTENTE     = 'Torneos';

    private string $nombreMod          = 'Premios Prueba Interfaz';
    private string $descripcionMod     = 'Modulo Modificado Por Las Pruebas';

    private ?array $original = null;

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

        $I->irAlModulo(self::RUTA_MODULO, self::TITULO_MODULO);
        $I->waitForElementNotVisible('#loader', 10);
    }

    public function _after(AcceptanceTester $I): void
    {
        if ($this->original !== null) {
            $this->restaurarModulo(self::MODULO_PRUEBA_ID, $this->original['nombre_modulo'], $this->original['descripcion']);
        }
    }

    #[Group('carga', 'interfaz')]
    public function probarCargaInicialDeInterfaz(AcceptanceTester $I): void
    {
        $I->see(self::TITULO_MODULO, AcceptanceTester::TITULO_PAGINA);
        $I->seeElement(AcceptanceTester::INPUT_BUSQUEDA);
        $I->seeElement(AcceptanceTester::CONT_RESULTADOS);
        $I->seeElement(AcceptanceTester::ITEM_LISTADO);

        $I->see('Usuarios', self::PRIMER_REGISTRO . ' .listado_titulo');
        $I->see('Descripción', self::PRIMER_REGISTRO);

        $I->seeElementInDOM(AcceptanceTester::MODAL);
        $I->dontSeeElement(AcceptanceTester::MODAL);

        $I->verificarSinErrores();
    }

    #[Group('seguridad')]
    public function probarModulosInternosNoSeListan(AcceptanceTester $I): void
    {
        $titulos = $this->titulosListados($I);
        $total = $this->contarModulos();
        if ($total !== null) {
            Assert::assertCount($total - count(self::MODULOS_OCULTOS), $titulos);
        }

        foreach (self::MODULOS_OCULTOS as $oculto) {
            Assert::assertNotContains($oculto, $titulos);
        }
        $I->buscarEnListado('Cerrar Sesion');
        $I->waitForText('No se encontraron registros', 5, AcceptanceTester::CONT_RESULTADOS);
        $I->limpiarBuscador();

        $I->verificarSinErrores();
    }

    #[Group('seguridad')]
    public function probarSoloSePermiteModificar(AcceptanceTester $I): void
    {
        $I->dontSeeElementInDOM(AcceptanceTester::BTN_NUEVO);
        $I->dontSeeElementInDOM(AcceptanceTester::BTN_REPORTE);
        $I->dontSeeElementInDOM(AcceptanceTester::BTN_ELIMINAR);

        $registros = (int)$I->executeJS("return document.querySelectorAll('" . self::REGISTRO . "').length;");
        $botones   = (int)$I->executeJS("return document.querySelectorAll('" . AcceptanceTester::BTN_MODIFICAR . "').length;");
        Assert::assertGreaterThan(0, $registros);
        Assert::assertSame($registros, $botones);

        $I->verificarSinErrores();
    }

    #[Group('buscador')]
    public function probarBuscadorModulos(AcceptanceTester $I): void
    {
        $I->buscarEnListado(self::MODULO_PRUEBA);
        $I->waitForText(self::MODULO_PRUEBA, 5, self::PRIMER_REGISTRO . ' .listado_titulo');
        Assert::assertSame([self::MODULO_PRUEBA], $this->titulosListados($I));

        $I->buscarEnListado('Pago');
        $I->waitForText('Pagos', 5, AcceptanceTester::CONT_RESULTADOS);
        $titulos = $this->titulosListados($I);
        Assert::assertContains('Pagos', $titulos);
        Assert::assertContains('Metodos de Pago', $titulos);
        foreach ($titulos as $t) {
            Assert::assertStringContainsStringIgnoringCase('Pago', $t);
        }

        $I->buscarEnListado('ModuloInexistenteXYZ');
        $I->waitForElementVisible(AcceptanceTester::LISTADO_VACIO, 5);
        $I->see('No se encontraron registros', AcceptanceTester::LISTADO_VACIO);

        $I->limpiarBuscador();
        $I->dontSee('No se encontraron registros', AcceptanceTester::CONT_RESULTADOS);

        $I->verificarSinErrores();
    }

    #[Group('validacion')]
    public function probarValidacionesDeFormulario(AcceptanceTester $I): void
    {
        $this->abrirModalModificar($I, self::MODULO_PRUEBA);

        $I->fillField(AcceptanceTester::INPUT_NOMBRE, '');
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('Tiene que ingresar un nombre de modulo valido', 5);
        $this->cerrarAlertaError($I);

        $I->fillField(AcceptanceTester::INPUT_NOMBRE, 'Pr');
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('Tiene que ingresar un nombre de modulo valido', 5);
        $this->cerrarAlertaError($I);

        $I->fillField(AcceptanceTester::INPUT_NOMBRE, self::MODULO_PRUEBA);
        $I->fillField(AcceptanceTester::INPUT_DESCRIPCION, '');
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('Tiene que ingresar una descripcion valida', 5);
        $this->cerrarAlertaError($I);

        $I->fillField(AcceptanceTester::INPUT_NOMBRE, '');
        $I->pressKey(AcceptanceTester::INPUT_NOMBRE, 'P', 'r', 'e', '1', '$');
        $I->seeInField(AcceptanceTester::INPUT_NOMBRE, 'Pre');

        $I->click(AcceptanceTester::MODAL_CERRAR);
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);
        if ($this->original !== null) {
            Assert::assertSame($this->original, $this->leerModulo(self::MODULO_PRUEBA_ID));
        }

        $I->verificarSinErrores();
    }

    #[Group('modificar', 'crud')]
    public function probarModificarModulo(AcceptanceTester $I): void
    {
        $this->abrirModalModificar($I, self::MODULO_PRUEBA);
        $I->fillField(AcceptanceTester::INPUT_NOMBRE, $this->nombreMod);
        $I->fillField(AcceptanceTester::INPUT_DESCRIPCION, $this->descripcionMod);

        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere modificar este modulo?', 5);
        $I->confirmarSweetAlert();

        $I->waitForText('Modificacion Exitosa', 10);
        $I->see('Modulo modificado exitosamente.');
        $I->aceptarSweetAlert();
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        $I->buscarEnListado($this->nombreMod);
        $I->waitForText($this->nombreMod, 5, self::PRIMER_REGISTRO . ' .listado_titulo');
        $I->see($this->descripcionMod, self::PRIMER_REGISTRO);

        $modulo = $this->leerModulo(self::MODULO_PRUEBA_ID);
        if ($modulo !== null) {
            Assert::assertSame($this->nombreMod, $modulo['nombre_modulo']);
            Assert::assertSame($this->descripcionMod, $modulo['descripcion']);
        }

        $I->verificarSinErrores();
    }

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

        $I->seeElement(AcceptanceTester::MODAL);
        $I->click(AcceptanceTester::MODAL_CERRAR);
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);
        $modulo = $this->leerModulo(self::MODULO_PRUEBA_ID);
        if ($modulo !== null) {
            Assert::assertSame(self::MODULO_PRUEBA, $modulo['nombre_modulo']);
        }

        $I->verificarSinErrores();
    }

    #[Group('seguridad', 'acceso')]
    public function probarAccesoDenegadoRolSinPermisos(AcceptanceTester $I): void
    {
        $this->asegurarUsuarioConRol($this->usuarioSinAcceso, self::ROL_ENTRENADOR_ID);

        $this->cerrarSesion($I);

        $I->amOnPage('/');
        $I->waitForElement('input[name="cedula"]', 5);
        $I->fillField('cedula', $this->usuarioSinAcceso['cedula']);
        $I->fillField('contraseña', $this->usuarioSinAcceso['contrasena']);
        $I->enviarLoginRespetandoLimite();

        $I->amOnPage(self::RUTA_MODULO);

        $I->waitForElement('#alerta-backend', 15);
        $I->seeInCurrentUrl('Principal');
        $I->seeElementInDOM('#alerta-backend', ['data-titulo' => 'Acceso denegado']);

        $this->cerrarSesion($I);
    }

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
        }
    }

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
        }
    }
}
