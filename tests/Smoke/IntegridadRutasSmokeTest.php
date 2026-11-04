<?php

namespace Tests\Smoke;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;
use App\modelo\Conexion;

#[Group('smoke')]
#[Group('smokeIntegridadRutas')]
class IntegridadRutasSmokeTest extends TestCase
{
    private string $rutaRaiz;

    protected function setUp(): void
    {
        $this->rutaRaiz = dirname(__DIR__, 2);
    }

    private function obtenerListaRutas(): array
    {
        return [
            'Inicio'              => 'Inicio',
            'Principal'           => 'Principal',
            'Usuarios'            => 'Usuarios',
            'IA'                  => 'IA',
            'Recuperacion'        => 'Recuperacion',
            'Roles'               => 'Roles',
            'Representantes'      => 'Representantes',
            'Posiciones'          => 'Posiciones',
            'Categorias'          => 'Categorias',
            'Torneos'             => 'Torneos',
            'EstadoFisico'        => 'EstadoFisico',
            'Bitacora'            => 'Bitacora',
            'Atletas'             => 'Atletas',
            'MetodosPago'         => 'MetodosPago',
            'Conceptos'           => 'Conceptos',
            'Monedas'             => 'Monedas',
            'Cargos'              => 'Cargos',
            'Notificaciones'      => 'Notificaciones',
            'Pagos'               => 'Pagos',
            'Respaldo'            => 'Respaldo',
            'CategoriaCatalogo'   => 'CategoriaCatalogo',
            'Premios'             => 'Premios',
            'Reportes'            => 'Reportes',
            'Catalogo'            => 'Catalogo',
            'Devoluciones'        => 'Devoluciones',
            'Equipos'             => 'Equipos',
            'Asignaciones'        => 'Asignaciones',
            'ArticulosInventario' => 'ArticulosInventario',
            'Palmares'            => 'Palmares',
            'Estadisticas'        => 'Estadisticas',
            'Participaciones'     => 'Participaciones',
            'TasaCambios'         => 'TasaCambios',
            'Modulos'             => 'Modulos',
            'Permisos'            => 'Permisos',
            'EditarPerfil'        => 'EditarPerfil',
            'PreguntasFrecuentes' => 'PreguntasFrecuentes',
        ];
    }

    // Caso 1: El archivo de rutas existe y la función despachadora está declarada
    public function testArchivoRutasExisteYDefineFuncion(): void
    {
        $rutaArchivo = $this->rutaRaiz . '/routes/rutas.php';
        $this->assertFileExists($rutaArchivo, 'El archivo de rutas routes/rutas.php debe existir.');

        require_once $rutaArchivo;
        $this->assertTrue(function_exists('manejarRuta'), 'La función despachadora manejarRuta() debe estar declarada.');
    }

    // Caso 2: Todos los controladores mapeados en rutas.php existen en app/controlador/
    public function testTodosLosControladoresDeRutasExisten(): void
    {
        $rutas = $this->obtenerListaRutas();

        foreach ($rutas as $pagina => $controlador) {
            $archivoControlador = $this->rutaRaiz . "/app/controlador/{$controlador}.php";
            $this->assertFileExists(
                $archivoControlador,
                "El controlador [{$controlador}.php] para la ruta [{$pagina}] debe existir físicamente en app/controlador/."
            );
        }
    }

    // Caso 3: Todas las vistas de interfaz asociadas a las rutas existen en app/vista/
    public function testTodasLasVistasDeRutasExisten(): void
    {
        $rutas = $this->obtenerListaRutas();
        // Rutas que operan como endpoints de servicio/JSON y no poseen plantilla HTML en app/vista/
        $rutasServicio = ['IA', 'Notificaciones'];

        foreach ($rutas as $pagina => $vista) {
            if (in_array($pagina, $rutasServicio, true)) {
                continue;
            }

            $archivoVista = $this->rutaRaiz . "/app/vista/{$vista}.php";
            $this->assertFileExists(
                $archivoVista,
                "La vista [{$vista}.php] para la ruta [{$pagina}] debe existir físicamente en app/vista/."
            );
        }
    }

    // Caso 4: Ningún controlador contiene errores de sintaxis (PHP Parse Check)
    public function testSintaxisDeControladoresSinErrores(): void
    {
        $archivosControladores = glob($this->rutaRaiz . '/app/controlador/*.php');
        $this->assertNotEmpty($archivosControladores);

        foreach ($archivosControladores as $archivo) {
            $salida = [];
            $codigoSalida = 0;
            exec('php -l ' . escapeshellarg($archivo) . ' 2>&1', $salida, $codigoSalida);

            $this->assertEquals(
                0,
                $codigoSalida,
                "Error de sintaxis detectado en el controlador [" . basename($archivo) . "]: " . implode("\n", $salida)
            );
        }
    }

    // Caso 5: Ninguna vista contiene errores de sintaxis (PHP Parse Check)
    public function testSintaxisDeVistasSinErrores(): void
    {
        $archivosVistas = glob($this->rutaRaiz . '/app/vista/*.php');
        $this->assertNotEmpty($archivosVistas);

        foreach ($archivosVistas as $archivo) {
            $salida = [];
            $codigoSalida = 0;
            exec('php -l ' . escapeshellarg($archivo) . ' 2>&1', $salida, $codigoSalida);

            $this->assertEquals(
                0,
                $codigoSalida,
                "Error de sintaxis detectado en la vista [" . basename($archivo) . "]: " . implode("\n", $salida)
            );
        }
    }

    // Caso 6: Los modelos principales del sistema se pueden cargar e instanciar
    public function testModelosPrincipalesSePuedenInstanciar(): void
    {
        $clasesModelos = [
            \App\modelo\ModeloAtletas::class,
            \App\modelo\ModeloRepresentantes::class,
            \App\modelo\ModeloCargos::class,
            \App\modelo\ModeloMonedas::class,
            \App\modelo\ModeloConceptos::class,
            \App\modelo\ModeloMetodosPago::class,
            \App\modelo\ModeloPosiciones::class,
            \App\modelo\ModeloCategorias::class,
            \App\modelo\ModeloUsuarios::class,
            \App\modelo\ModeloRoles::class,
            \App\modelo\ModeloBitacora::class,
            \App\modelo\ModeloInicio::class,
        ];

        foreach ($clasesModelos as $clase) {
            $this->assertTrue(class_exists($clase), "La clase del modelo [{$clase}] debe existir y ser autocargable.");

            $instancia = new $clase();
            $this->assertInstanceOf(
                Conexion::class,
                $instancia,
                "El modelo [{$clase}] debe heredar de la clase base Conexion."
            );
        }
    }

    // Caso 7: Los componentes y parciales globales de la vista existen
    public function testComplementosDeVistaExisten(): void
    {
        $complementos = [
            '404.php',
            'head.php',
            'nav_lateral.php',
            'nav_superior.php',
            'loader.php',
            'botonera.php',
            'MiniModal.php'
        ];

        foreach ($complementos as $archivo) {
            $ruta = $this->rutaRaiz . '/app/vista/complementos/' . $archivo;
            $this->assertFileExists($ruta, "El complemento de vista [{$archivo}] debe existir en app/vista/complementos/.");
        }
    }
}
