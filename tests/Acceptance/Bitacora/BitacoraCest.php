<?php

declare(strict_types=1);

namespace Tests\Acceptance\Bitacora;

use Codeception\Attribute\Group;
use PHPUnit\Framework\Assert;
use Tests\Support\AcceptanceTester;

if (!defined('_DB_NAME_')) {
    require_once __DIR__ . '/../../../config/config.php';
}

#[Group('bitacora')]
#[Group('interfaz')]
final class BitacoraCest
{
    private const RUTA_MODULO          = '/Bitacora';
    private const TITULO_MODULO        = 'Bitacora';

    private const BTN_CARGAR_MAS       = '#btn_cargar_mas';
    private const SELECT_MODULO        = '#filtro_modulo';
    private const SELECT_USUARIO       = '#filtro_usuario';
    private const INPUT_FECHA_INICIO   = '#fecha_inicio';
    private const INPUT_FECHA_FIN      = '#fecha_fin';
    private const REGISTRO             = '#resultadoconsulta .listado_contenedor_grupal';
    private const PRIMER_REGISTRO      = '#resultadoconsulta .listado_contenedor_grupal:first-child';
    private const COL_FECHA_HORA       = '.listado_col_datos .listado_dato_grupo:nth-child(3) span';
    private const DETALLE_PRIMERO      = '#resultadoconsulta .listado_contenedor_grupal:first-child .listado_detalle_oculto';

    private const ADMIN_CEDULA         = '12345678';
    private const ADMIN_NOMBRE         = 'Admin Admin';

    private string $rolTraza           = 'Rol Prueba Interfaz';
    private string $descripcionTraza   = 'Rol Creado Por Las Pruebas De Interfaz';

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
        $I->irAlModulo(self::RUTA_MODULO, self::TITULO_MODULO);
        $I->waitForElementNotVisible('#loader', 10);
    }

    #[Group('carga', 'interfaz')]
    public function probarCargaInicialDeInterfaz(AcceptanceTester $I): void
    {
        $I->see(self::TITULO_MODULO, AcceptanceTester::TITULO_PAGINA);
        $I->seeElement(AcceptanceTester::INPUT_BUSQUEDA);
        $I->seeElement(AcceptanceTester::BTN_REPORTE);
        $I->see('Generar Reporte', AcceptanceTester::BTN_REPORTE);
        $I->seeElement(AcceptanceTester::CONT_RESULTADOS);
        $I->seeElement(AcceptanceTester::ITEM_LISTADO);

        $I->see('Usuario', self::PRIMER_REGISTRO);
        $I->see('Acción', self::PRIMER_REGISTRO);
        $I->see('Fecha y Hora', self::PRIMER_REGISTRO);

        $I->seeElementInDOM(AcceptanceTester::MODAL);
        $I->dontSeeElement(AcceptanceTester::MODAL);

        $I->verificarSinErrores();
    }

    #[Group('seguridad')]
    public function probarModuloSoloLectura(AcceptanceTester $I): void
    {
        $I->dontSeeElement(AcceptanceTester::BTN_NUEVO);
        $I->dontSeeElementInDOM(AcceptanceTester::BTN_MODIFICAR);
        $I->dontSeeElementInDOM(AcceptanceTester::BTN_ELIMINAR);

        $I->verificarSinErrores();
    }

    #[Group('registro')]
    public function probarRegistroAutomaticoDeIngreso(AcceptanceTester $I): void
    {
        $hoy = date('d/m/Y');

        $I->see(self::TITULO_MODULO, self::PRIMER_REGISTRO . ' .listado_titulo');
        $I->see(self::ADMIN_NOMBRE, self::PRIMER_REGISTRO);
        $I->see('Ingreso al Modulo', self::PRIMER_REGISTRO);
        $I->see($hoy, self::PRIMER_REGISTRO);

        $ultimo = $this->ultimoRegistro();
        if ($ultimo !== null) {
            Assert::assertSame(self::ADMIN_CEDULA, (string)$ultimo['cedulaUsuario']);
            Assert::assertSame(self::TITULO_MODULO, (string)$ultimo['nombre_modulo']);
            Assert::assertSame('Ingreso al Modulo', (string)$ultimo['acciones']);
            Assert::assertSame(date('Y-m-d'), (string)$ultimo['fecha']);
            Assert::assertNotEmpty($ultimo['hora']);
        }

        $totalAntes = $this->contarRegistros();
        $this->recargarModulo($I);
        $totalDespues = $this->contarRegistros();
        if ($totalAntes !== null && $totalDespues !== null) {
            Assert::assertSame($totalAntes + 1, $totalDespues);
        }

        $I->verificarSinErrores();
    }

    #[Group('orden')]
    public function probarOrdenCronologicoDescendente(AcceptanceTester $I): void
    {
        $fechas = (array)$I->executeJS(
            "return Array.from(document.querySelectorAll('" . self::REGISTRO . " " . self::COL_FECHA_HORA . "')).map(e => e.textContent.trim());"
        );
        Assert::assertNotEmpty($fechas);

        $anterior = null;
        foreach (array_slice($fechas, 0, 30) as $texto) {
            $actual = \DateTime::createFromFormat('d/m/Y h:i A', trim($texto));
            Assert::assertNotFalse($actual, "Formato de fecha inesperado: {$texto}");
            if ($anterior !== null) {
                Assert::assertLessThanOrEqual($anterior->getTimestamp(), $actual->getTimestamp());
            }
            $anterior = $actual;
        }

        $I->verificarSinErrores();
    }

    #[Group('trazabilidad')]
    public function probarTrazabilidadDeAccionEnOtroModulo(AcceptanceTester $I): void
    {
        $this->asegurarRolExiste($this->rolTraza, $this->descripcionTraza);
        $descripcionNueva = 'Traza Bitacora ' . $this->sufijoAleatorio();

        $I->amOnPage('/Roles');
        $I->waitForText('Roles', 10, AcceptanceTester::TITULO_PAGINA);
        $I->waitForElementNotVisible('#loader', 10);
        $I->despejarOverlays();
        $I->buscarEnListado($this->rolTraza);
        $I->waitForText($this->rolTraza, 5, AcceptanceTester::CONT_RESULTADOS);
        $I->clickModificar();
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->waitForText('Modificar Rol', 5, AcceptanceTester::MODAL_TITULO);
        $I->fillField(AcceptanceTester::INPUT_DESCRIPCION, $descripcionNueva);
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere modificar este rol?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Modificacion Exitosa', 10);
        $I->aceptarSweetAlert();

        $this->recargarModulo($I);
        $I->buscarEnListado('Modificó el Rol: ' . $this->rolTraza);
        $I->waitForText('Modificó el Rol: ' . $this->rolTraza, 5, self::PRIMER_REGISTRO);
        $I->see('Roles', self::PRIMER_REGISTRO . ' .listado_titulo');
        $I->see(self::ADMIN_NOMBRE, self::PRIMER_REGISTRO);
        $I->see(date('d/m/Y'), self::PRIMER_REGISTRO);

        $I->executeJS("document.querySelector('" . self::PRIMER_REGISTRO . " .listado_item').click();");
        $I->waitForElementVisible(self::DETALLE_PRIMERO, 5);
        $this->verEnDetalle($I, 'Datos Previos');
        $this->verEnDetalle($I, $this->descripcionTraza);
        $this->verEnDetalle($I, 'Datos Nuevos');
        $this->verEnDetalle($I, $descripcionNueva);
        $this->verEnDetalle($I, self::ADMIN_CEDULA);

        $this->restaurarDescripcionRol($this->rolTraza, $this->descripcionTraza);

        $I->verificarSinErrores();
    }

    #[Group('detalle')]
    public function probarVerDetalleRegistro(AcceptanceTester $I): void
    {
        $I->executeJS("document.querySelector('" . self::PRIMER_REGISTRO . " .listado_item').click();");
        $I->waitForElementVisible(self::DETALLE_PRIMERO, 5);

        $this->verEnDetalle($I, 'Detalles de la Acción:');
        $this->verEnDetalle($I, 'Información Adicional');
        $this->verEnDetalle($I, 'Cédula del Usuario: ' . self::ADMIN_CEDULA);
        $this->verEnDetalle($I, 'Entorno:');
        $this->verEnDetalle($I, 'Cambios Realizados:');
        $this->verEnDetalle($I, 'Datos Previos');
        $this->verEnDetalle($I, 'Datos Nuevos');
        $this->verEnDetalle($I, 'Ninguno');

        $I->executeJS("document.querySelector('" . self::PRIMER_REGISTRO . " .listado_item').click();");
        $I->waitForElementNotVisible(self::DETALLE_PRIMERO, 5);

        $I->verificarSinErrores();
    }

    #[Group('buscador')]
    public function probarBuscadorBitacora(AcceptanceTester $I): void
    {
        $I->buscarEnListado(self::ADMIN_CEDULA);
        $I->waitForElementVisible(AcceptanceTester::ITEM_LISTADO, 5);
        $I->see(self::ADMIN_NOMBRE, self::PRIMER_REGISTRO);

        $I->buscarEnListado('Roles');
        $I->waitForText('Roles', 5, self::PRIMER_REGISTRO . ' .listado_titulo');
        $I->dontSee(self::TITULO_MODULO, self::PRIMER_REGISTRO . ' .listado_titulo');

        $I->buscarEnListado('Ingreso al Modulo');
        $I->waitForText('Ingreso al Modulo', 5, self::PRIMER_REGISTRO);

        $I->buscarEnListado('AccionInexistenteXYZ999');
        $I->waitForElementVisible(AcceptanceTester::LISTADO_VACIO, 5);
        $I->see('No se encontraron registros', AcceptanceTester::LISTADO_VACIO);

        $I->limpiarBuscador();
        $I->dontSee('No se encontraron registros', AcceptanceTester::CONT_RESULTADOS);

        $I->verificarSinErrores();
    }

    #[Group('paginacion')]
    public function probarCargarMasRegistros(AcceptanceTester $I): void
    {
        $total = $this->contarRegistros() ?? 0;

        $cargados = count($I->grabMultiple(self::REGISTRO . ' .listado_titulo'));
        Assert::assertSame(min(100, $total), $cargados);

        if ($total <= 100) {
            $I->dontSeeElement(self::BTN_CARGAR_MAS);
            return;
        }

        $I->seeElement(self::BTN_CARGAR_MAS);
        $I->see('Cargar +100', self::BTN_CARGAR_MAS);
        $I->click(self::BTN_CARGAR_MAS);
        $esperados = min(200, $total);
        $I->waitForJS("return document.querySelectorAll('" . self::REGISTRO . "').length >= {$esperados};", 10);

        $cargados = count($I->grabMultiple(self::REGISTRO . ' .listado_titulo'));
        Assert::assertSame($esperados, $cargados);

        if ($total > 200) {
            $I->seeElement(self::BTN_CARGAR_MAS);
        } else {
            $I->dontSeeElement(self::BTN_CARGAR_MAS);
        }

        $I->verificarSinErrores();
    }

    #[Group('reporte')]
    public function probarModalFiltrosReporte(AcceptanceTester $I): void
    {
        $this->abrirModalReporte($I);

        $I->seeInField(self::SELECT_MODULO, '');
        $I->seeInField(self::SELECT_USUARIO, '');
        $I->seeElementInDOM(self::SELECT_MODULO . ' option[value="2"]');
        $I->see('Todos los Módulos', self::SELECT_MODULO);
        $I->see('Roles', self::SELECT_MODULO);
        $I->see('Todos los Usuarios', self::SELECT_USUARIO);
        $I->see(self::ADMIN_CEDULA . ' - ' . self::ADMIN_NOMBRE, self::SELECT_USUARIO);

        $I->seeElementInDOM(self::INPUT_FECHA_INICIO);
        $I->seeElementInDOM(self::INPUT_FECHA_FIN);
        $I->seeElement(self::INPUT_FECHA_INICIO . ' + input');
        $I->seeElement(self::INPUT_FECHA_FIN . ' + input');

        $this->fijarFiltros($I, '2', $this->idUsuario(self::ADMIN_CEDULA), date('Y-m-d'), date('Y-m-d'));
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿En qué formato desea generar el reporte?', 5);
        $I->see('PDF');
        $I->see('Excel');

        $I->cancelarSweetAlert();

        $I->click(AcceptanceTester::MODAL_CERRAR);
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        $I->verificarSinErrores();
    }

    #[Group('reporte', 'validacion')]
    public function probarValidacionesFiltrosReporte(AcceptanceTester $I): void
    {
        $this->abrirModalReporte($I);

        $this->fijarFiltros($I, '', '', date('Y-m-d'), '');
        $this->solicitarReportePdf($I);
        $I->waitForText('Debe seleccionar ambas fechas para filtrar por rango.', 10);
        $this->cerrarAlertaError($I);

        $this->fijarFiltros($I, '', '', date('Y-m-d'), date('Y-m-d', strtotime('-1 day')));
        $this->solicitarReportePdf($I);
        $I->waitForText('La fecha de inicio no puede ser mayor a la fecha de fin.', 10);
        $this->cerrarAlertaError($I);

        $this->fijarFiltros($I, '', $this->idUsuario(self::ADMIN_CEDULA), '2099-01-01', '2099-01-31');
        $this->solicitarReportePdf($I);
        $I->waitForText('No hay registros que coincidan con los filtros seleccionados.', 10);
        $this->cerrarAlertaError($I);

        $I->seeElement(AcceptanceTester::MODAL);
        $I->click(AcceptanceTester::MODAL_CERRAR);
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        $I->verificarSinErrores();
    }

    #[Group('seguridad', 'acceso')]
    public function probarAccesoDenegadoRolSinPermisos(AcceptanceTester $I): void
    {
        $this->asegurarUsuarioConRol($this->usuarioSinAcceso, self::ROL_ENTRENADOR_ID);

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
        $I->dontSeeElement(AcceptanceTester::BTN_REPORTE);

        $I->executeInSelenium(function (\Facebook\WebDriver\Remote\RemoteWebDriver $wd) {
            $wd->manage()->deleteAllCookies();
        });
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

    private function verEnDetalle(AcceptanceTester $I, string $texto): void
    {
        $contenido = (string)$I->executeJS(
            "return document.querySelector('" . self::DETALLE_PRIMERO . "').textContent.replace(/\s+/g, ' ');"
        );
        Assert::assertStringContainsString($texto, $contenido);
    }

    private function abrirModalReporte(AcceptanceTester $I): void
    {
        $I->click(AcceptanceTester::BTN_REPORTE);
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->see('Filtros del Reporte de Bitácora', AcceptanceTester::MODAL_TITULO);
        $I->see('Generar Reporte', AcceptanceTester::BTN_PROCESO);

        $I->waitForJS("return $('#filtro_modulo option').length > 1 && $('#filtro_usuario option').length > 1;", 5);
    }

    private function fijarFiltros(AcceptanceTester $I, string $modulo, string $usuario, string $inicio, string $fin): void
    {
        $I->executeJS("
            $('#filtro_modulo').val('{$modulo}').trigger('change');
            $('#filtro_usuario').val('{$usuario}').trigger('change');
            ['fecha_inicio', 'fecha_fin'].forEach(function (id, i) {
                let valor = i === 0 ? '{$inicio}' : '{$fin}';
                let el = document.getElementById(id);
                if (el._flatpickr) {
                    valor ? el._flatpickr.setDate(valor, true) : el._flatpickr.clear();
                }
                $(el).val(valor);
            });
        ");
    }

    private function solicitarReportePdf(AcceptanceTester $I): void
    {
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿En qué formato desea generar el reporte?', 5);
        $I->click('PDF', '.swal2-actions');
    }

    private function sufijoAleatorio(): string
    {
        $letras = 'abcdefghijklmnopqrstuvwxyz';
        $sufijo = '';
        for ($i = 0; $i < 6; $i++) {
            $sufijo .= $letras[random_int(0, 25)];
        }
        return ucfirst($sufijo);
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

    private function ultimoRegistro(): ?array
    {
        $pdo = $this->conexion();
        if ($pdo === null) {
            return null;
        }
        $fila = $pdo->query('SELECT * FROM vista_consulta_bitacora ORDER BY id_bitacora DESC LIMIT 1')->fetch(\PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    private function contarRegistros(): ?int
    {
        $pdo = $this->conexion();
        if ($pdo === null) {
            return null;
        }
        return (int)$pdo->query('SELECT COUNT(*) FROM vista_consulta_bitacora')->fetchColumn();
    }

    private function idUsuario(string $cedula): string
    {
        $pdo = $this->conexion();
        if ($pdo === null) {
            return '1';
        }
        $st = $pdo->prepare('SELECT idUsuario FROM usuarios WHERE cedulaUsuario = :c LIMIT 1');
        $st->execute([':c' => $cedula]);
        return (string)($st->fetchColumn() ?: '1');
    }

    private function asegurarRolExiste(string $nombre, string $descripcion): void
    {
        try {
            $pdo = $this->conexion();
            $st = $pdo?->prepare('SELECT COUNT(*) FROM roles WHERE nombre_rol = :n');
            $st?->execute([':n' => $nombre]);
            if ($st !== null && (int)$st->fetchColumn() === 0) {
                $pdo->prepare('INSERT INTO roles (nombre_rol, descripcion) VALUES (:n, :d)')
                    ->execute([':n' => $nombre, ':d' => $descripcion]);
            } else {
                $this->restaurarDescripcionRol($nombre, $descripcion);
            }
        } catch (\Throwable $e) {
        }
    }

    private function restaurarDescripcionRol(string $nombre, string $descripcion): void
    {
        try {
            $this->conexion()?->prepare('UPDATE roles SET descripcion = :d WHERE nombre_rol = :n')
                ->execute([':d' => $descripcion, ':n' => $nombre]);
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
