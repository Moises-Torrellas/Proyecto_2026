<?php

declare(strict_types=1);

namespace Tests\Support\Extension;

use Codeception\Event\SuiteEvent;
use Codeception\Events;
use Codeception\Extension;
use Codeception\Test\Descriptor;
use ReflectionProperty;

/**
 * Ordena las pruebas de la suite Acceptance respetando las dependencias
 * funcionales entre módulos (Administración, Cobranzas, Inventario, etc.)
 * para evitar errores cuando la base de datos de pruebas parte vacía.
 */
final class OrdenEjecucionExtension extends Extension
{
    public static array $events = [
        Events::SUITE_BEFORE => 'ordenarPruebas',
    ];

    /**
     * Prioridad de ejecución por clase/prefijo (menor número = se ejecuta primero).
     */
    private const PRIORIDAD = [
        // 1. Acceso y navegación base
        'Tests\\Acceptance\\LoginCest'                            => 10,
        'Tests\\Acceptance\\Inicio\\LoginCest'                    => 10,
        'Tests\\Acceptance\\Recuperacion\\RecuperacionCest'       => 11,
        'Tests\\Acceptance\\Interfaz\\AdministracionInterfazCest' => 12,

        // 2. Sección Administración: Categorías -> Posiciones -> Representantes -> Atletas
        'Tests\\Acceptance\\Categorias\\CategoriaCest'            => 20,
        'Tests\\Acceptance\\Posiciones\\PosicionesCest'           => 21,
        'Tests\\Acceptance\\Representantes\\RepresentantesCest'   => 22,
        'Tests\\Acceptance\\Atletas\\AtletasCest'                 => 23,

        // 3. Sección Cobranzas: Monedas -> Métodos de Pago -> Conceptos -> Cargos -> Pagos
        'Tests\\Acceptance\\Monedas\\MonedasCest'                 => 30,
        'Tests\\Acceptance\\MetodosPago\\MetodosPagoCest'         => 31,
        'Tests\\Acceptance\\Conceptos\\ConceptosCest'             => 32,
        'Tests\\Acceptance\\Cargos\\CargosCest'                   => 33,
        'Tests\\Acceptance\\Pagos\\PagosCest'                     => 34,

        // 4. Sección Inventario
        'Tests\\Acceptance\\Inventario\\CategoriaCatalogoCest'    => 40,
        'Tests\\Acceptance\\Inventario\\EstadoFisicoCest'         => 41,
        'Tests\\Acceptance\\Inventario\\CatalogoCest'             => 42,
        'Tests\\Acceptance\\Inventario\\ArticulosInventarioCest'  => 43,
        'Tests\\Acceptance\\Inventario\\AsignacionesCest'         => 44,
        'Tests\\Acceptance\\Inventario\\DevolucionesCest'         => 45,

        // 5. Sección Competencias
        'Tests\\Acceptance\\Competencias\\TorneosCest'            => 50,
        'Tests\\Acceptance\\Competencias\\EquiposCest'            => 51,
        'Tests\\Acceptance\\Competencias\\ParticipacionesCest'    => 52,

        // 6. Sección Historial Deportivo
        'Tests\\Acceptance\\HistorialDeportivo\\PremiosCest'      => 60,
        'Tests\\Acceptance\\HistorialDeportivo\\PalmaresCest'     => 61,
        'Tests\\Acceptance\\HistorialDeportivo\\EstadisticasCest' => 62,

        // 7. Sección Seguridad y Auditoría
        'Tests\\Acceptance\\Modulos\\ModulosCest'                 => 70,
        'Tests\\Acceptance\\Permisos\\PermisosCest'               => 71,
        'Tests\\Acceptance\\Roles\\RolesCest'                     => 72,
        'Tests\\Acceptance\\Usuarios\\UsuariosCest'               => 73,
        'Tests\\Acceptance\\Bitacora\\BitacoraCest'               => 74,

        // 8. Smoke de interfaz
        'Tests\\Acceptance\\Smoke\\'                              => 80,
    ];

    public function ordenarPruebas(SuiteEvent $event): void
    {
        $suite = $event->getSuite();
        if ($suite === null || !str_ends_with((string) $suite->getBaseName(), 'Acceptance')) {
            return;
        }

        $ref = new ReflectionProperty($suite, 'tests');
        $tests = $ref->getValue($suite);
        if (!is_array($tests) || count($tests) <= 1) {
            return;
        }

        // Ordenamiento estable: respeta el orden interno de los métodos dentro de cada Cest
        $indexados = [];
        foreach ($tests as $indice => $test) {
            $firma = Descriptor::getTestSignature($test);
            $indexados[] = [
                'prioridad' => $this->obtenerPrioridad($firma),
                'indice'    => $indice,
                'test'      => $test,
            ];
        }

        usort($indexados, static function (array $a, array $b): int {
            if ($a['prioridad'] !== $b['prioridad']) {
                return $a['prioridad'] <=> $b['prioridad'];
            }
            return $a['indice'] <=> $b['indice'];
        });

        $ref->setValue($suite, array_column($indexados, 'test'));
    }

    private function obtenerPrioridad(string $firma): int
    {
        foreach (self::PRIORIDAD as $prefijo => $prioridad) {
            if (str_starts_with($firma, $prefijo)) {
                return $prioridad;
            }
        }
        return 999;
    }
}
