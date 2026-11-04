<?php

require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

ini_set('memory_limit', '512M');
set_time_limit(300);

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'Helvetica');

$dompdf = new Dompdf($options);

// Cargar logo si existe
$rutaLogo = __DIR__ . '/public/img/logo.png';
$logoBase64 = file_exists($rutaLogo) ? 'data:image/png;base64,' . base64_encode(file_get_contents($rutaLogo)) : '';

$fecha = date('d/m/Y - h:i A');

$html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reporte de Pruebas Unitarias - Asignaciones, Devoluciones, Estado Físico, Categoría Catálogo, Catálogo, Inventario, Torneos, Equipos y Participaciones</title>
<style>
    @page {
        margin: 85px 35px 65px 35px;
        size: A4 portrait;
    }
    body {
        font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        color: #1E293B;
        font-size: 8pt;
        line-height: 1.35;
        margin: 0;
        padding: 0;
    }

    /* HEADER */
    .header {
        position: fixed;
        top: -75px;
        left: 0;
        right: 0;
        height: 55px;
        border-bottom: 2px solid #059669;
        padding-bottom: 4px;
    }
    .header table {
        width: 100%;
        border-collapse: collapse;
    }
    .header-title {
        font-size: 11pt;
        font-weight: bold;
        color: #0F172A;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .header-sub {
        font-size: 7.5pt;
        color: #64748B;
        margin-top: 2px;
    }
    .header-logo {
        text-align: right;
        vertical-align: middle;
    }
    .header-logo img {
        height: 42px;
    }

    /* FOOTER */
    .footer {
        position: fixed;
        bottom: -50px;
        left: 0;
        right: 0;
        height: 35px;
        border-top: 1px solid #CBD5E1;
        font-size: 7pt;
        color: #64748B;
        padding-top: 5px;
    }
    .footer table {
        width: 100%;
        border-collapse: collapse;
    }
    .page-number:before {
        content: "Página " counter(page);
    }

    /* TITULOS */
    h1.doc-title {
        font-size: 15pt;
        color: #0F172A;
        text-align: center;
        margin: 8px 0 4px 0;
        text-transform: uppercase;
        font-weight: 800;
        letter-spacing: 0.5px;
    }
    p.doc-subtitle {
        font-size: 9pt;
        color: #059669;
        text-align: center;
        font-weight: bold;
        margin: 0 0 12px 0;
    }
    h2.section-header {
        font-size: 11pt;
        color: #0F172A;
        border-bottom: 2px solid #059669;
        padding-bottom: 3px;
        margin: 16px 0 8px 0;
        text-transform: uppercase;
    }
    h3.scenario-header {
        font-size: 8.8pt;
        color: #0F766E;
        background-color: #F0FDFA;
        border-left: 4px solid #059669;
        padding: 5px 8px;
        margin: 12px 0 6px 0;
        font-weight: bold;
    }

    /* TARJETAS RESUMEN (KPIS) */
    .kpi-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 4px;
        margin-bottom: 10px;
    }
    .kpi-card {
        background-color: #F8FAFC;
        border: 1px solid #E2E8F0;
        border-radius: 4px;
        padding: 6px 8px;
        text-align: center;
        vertical-align: top;
        width: 20%;
    }
    .kpi-value {
        font-size: 12.5pt;
        font-weight: 800;
        color: #059669;
        margin-bottom: 2px;
    }
    .kpi-label {
        font-size: 6.5pt;
        color: #475569;
        font-weight: 600;
        text-transform: uppercase;
    }
    .kpi-sub {
        font-size: 6pt;
        color: #94A3B8;
    }

    /* TABLAS */
    table.data-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        margin-bottom: 10px;
        page-break-inside: avoid;
    }
    table.data-table th {
        background-color: #0F172A;
        color: #FFFFFF;
        font-weight: bold;
        font-size: 7pt;
        text-transform: uppercase;
        padding: 5px 6px;
        border: 1px solid #0F172A;
        text-align: left;
    }
    table.data-table td {
        padding: 4px 6px;
        font-size: 7pt;
        border: 1px solid #E2E8F0;
        vertical-align: top;
        word-wrap: break-word;
    }
    table.data-table tr:nth-child(even) {
        background-color: #F8FAFC;
    }

    /* BADGES */
    .badge {
        display: inline-block;
        padding: 1px 4px;
        border-radius: 3px;
        font-weight: bold;
        font-size: 6.5pt;
        margin-bottom: 2px;
    }
    .badge-rf { background-color: #DBEAFE; color: #1E40AF; border: 1px solid #BFDBFE; }
    .badge-esc { background-color: #EDE9FE; color: #5B21B6; border: 1px solid #DDD6FE; }
    .badge-cp { background-color: #D1FAE5; color: #065F46; border: 1px solid #A7F3D0; }
    .badge-success { background-color: #DCFCE7; color: #166534; }
    .badge-code { font-family: monospace; background-color: #F1F5F9; color: #0F172A; border: 1px solid #CBD5E1; }

    .callout {
        background-color: #F0FDF4;
        border-left: 3px solid #22C55E;
        padding: 6px 10px;
        margin-bottom: 10px;
        font-size: 7.5pt;
    }
    .command-box {
        background-color: #0F172A;
        color: #F8FAFC;
        font-family: monospace;
        font-size: 6.8pt;
        padding: 4px 8px;
        border-radius: 3px;
        margin: 2px 0;
    }
    .page-break {
        page-break-before: always;
    }
</style>
</head>
<body>

<!-- ENCABEZADO FIJO -->
<div class="header">
    <table>
        <tr>
            <td style="width: 75%;">
                <div class="header-title">Club Deportivo Cannibals Lara - Sistema de Gestión</div>
                <div class="header-sub">Documento Técnico: Especificación de Pruebas Unitarias y Cobertura de Código</div>
            </td>
            <td class="header-logo" style="width: 25%;">
HTML;

if (!empty($logoBase64)) {
    $html .= '<img src="' . $logoBase64 . '" alt="Logo Cannibals">';
} else {
    $html .= '<span style="font-weight: bold; color: #059669;">CANNIBALS</span>';
}

$html .= <<<HTML
            </td>
        </tr>
    </table>
</div>

<!-- PIE DE PÁGINA FIJO -->
<div class="footer">
    <table>
        <tr>
            <td style="width: 33%; text-align: left;">Cannibals Lara - Proyecto 2026</td>
            <td style="width: 34%; text-align: center;">Asignaciones, Devoluciones, Estado Físico, Catálogo, Inventario, Torneos, Equipos y Participaciones</td>
            <td style="width: 33%; text-align: right;"><span class="page-number"></span></td>
        </tr>
    </table>
</div>

<!-- PORTADA / TÍTULO -->
<h1 class="doc-title">Plan y Resultados de Pruebas Unitarias</h1>
<p class="doc-subtitle">Módulos: Asignaciones, Devoluciones, Estado Físico, Categoría Catálogo, Catálogo, Inventario de Artículos, Torneos, Equipos y Participaciones</p>

<div class="callout">
    <strong>Resumen del Entorno de Evaluación:</strong>
    Pruebas unitarias de aislamiento estricto ejecutadas bajo <strong>PHPUnit 11.5.57</strong> (PHP 8.2.12 con Xdebug 3.5.3).
    Se implementaron mocks de PDO sobre el singleton <code>Conexion::class</code> mediante <code>ReflectionClass</code>, garantizando independencia total de la base de datos física, transacciones atómicas simuladas y verificación de integridad lógica y referencial.
</div>

<!-- CUADROS RESUMEN (KPIS) -->
<table class="kpi-table">
    <tr>
        <td class="kpi-card" style="width: 25%;">
            <div class="kpi-value">269 / 269</div>
            <div class="kpi-label">Pruebas Unitarias</div>
            <div class="kpi-sub">100% de Aprobación</div>
        </td>
        <td class="kpi-card" style="width: 25%;">
            <div class="kpi-value">766</div>
            <div class="kpi-label">Aserciones Totales</div>
            <div class="kpi-sub">97 As / 65 Dev / 45 Est / 70 CC / 96 Cat / 123 Inv / 83 Tor / 99 Eq / 88 Par</div>
        </td>
        <td class="kpi-card" style="width: 25%;">
            <div class="kpi-value">100%</div>
            <div class="kpi-label">Aprobación Global</div>
            <div class="kpi-sub">0 Errores / 0 Fallos</div>
        </td>
        <td class="kpi-card" style="width: 25%;">
            <div class="kpi-value">9 Módulos</div>
            <div class="kpi-label">Alcance Evaluado</div>
            <div class="kpi-sub">Aislamiento con Mocks PDO</div>
        </td>
    </tr>
</table>
<table class="kpi-table" style="margin-top: -6px;">
    <tr>
        <td class="kpi-card" style="width: 33.33%;">
            <div class="kpi-value">86.64%</div>
            <div class="kpi-label">Asignaciones</div>
            <div class="kpi-sub">ModeloAsignaciones.php</div>
        </td>
        <td class="kpi-card" style="width: 33.33%;">
            <div class="kpi-value">96.58%</div>
            <div class="kpi-label">Devoluciones</div>
            <div class="kpi-sub">ModeloDevoluciones.php</div>
        </td>
        <td class="kpi-card" style="width: 33.33%;">
            <div class="kpi-value">97.17%</div>
            <div class="kpi-label">Estado Físico</div>
            <div class="kpi-sub">ModeloEstadoFisico.php</div>
        </td>
    </tr>
    <tr>
        <td class="kpi-card" style="width: 33.33%;">
            <div class="kpi-value">100.00%</div>
            <div class="kpi-label">Cat. Catálogo</div>
            <div class="kpi-sub">ModeloCategoriaCat</div>
        </td>
        <td class="kpi-card" style="width: 33.33%;">
            <div class="kpi-value">100.00%</div>
            <div class="kpi-label">Catálogo</div>
            <div class="kpi-sub">ModeloCatalogo</div>
        </td>
        <td class="kpi-card" style="width: 33.33%;">
            <div class="kpi-value">100.00%</div>
            <div class="kpi-label">Artículos Inv.</div>
            <div class="kpi-sub">ModeloArticulosInv</div>
        </td>
    </tr>
    <tr>
        <td class="kpi-card" style="width: 33.33%;">
            <div class="kpi-value">100.00%</div>
            <div class="kpi-label">Torneos</div>
            <div class="kpi-sub">ModeloTorneos (110/110)</div>
        </td>
        <td class="kpi-card" style="width: 33.33%;">
            <div class="kpi-value">100.00%</div>
            <div class="kpi-label">Equipos</div>
            <div class="kpi-sub">ModeloEquipos (142/142)</div>
        </td>
        <td class="kpi-card" style="width: 33.33%;">
            <div class="kpi-value">100.00%</div>
            <div class="kpi-label">Participaciones</div>
            <div class="kpi-sub">ModeloParticipaciones (144/144)</div>
        </td>
    </tr>
</table>

<!-- TABLA RESUMEN GENERAL -->
<h2 class="section-header">1. Resumen General de Escenarios Implementados</h2>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 14%;">Módulo</th>
            <th style="width: 10%;">Requisito</th>
            <th style="width: 10%;">Escenario</th>
            <th style="width: 38%;">Descripción Funcional del Escenario</th>
            <th style="width: 20%;">Archivo de Prueba</th>
            <th style="width: 8%; text-align: center;">Casos</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Asignaciones</strong></td>
            <td><span class="badge badge-rf">RF-01</span></td>
            <td><span class="badge badge-esc">ESC-01</span></td>
            <td>Validación de entradas, formatos de IDs, fechas y coherencia cronológica</td>
            <td>ValidarDatosAsignacionesTest.php</td>
            <td style="text-align: center; font-weight: bold;">10</td>
        </tr>
        <tr>
            <td><strong>Asignaciones</strong></td>
            <td><span class="badge badge-rf">RF-02</span></td>
            <td><span class="badge badge-esc">ESC-02</span></td>
            <td>Registro individual y en lote, disponibilidad de stock y compatibilidad de posición</td>
            <td>RegistrarAsignacionesTest.php</td>
            <td style="text-align: center; font-weight: bold;">8</td>
        </tr>
        <tr>
            <td><strong>Asignaciones</strong></td>
            <td><span class="badge badge-rf">RF-03</span></td>
            <td><span class="badge badge-esc">ESC-03</span></td>
            <td>Modificación de asignaciones activas, reasignación y liberación de equipos previos</td>
            <td>ModificarAsignacionesTest.php</td>
            <td style="text-align: center; font-weight: bold;">6</td>
        </tr>
        <tr>
            <td><strong>Asignaciones</strong></td>
            <td><span class="badge badge-rf">RF-04</span></td>
            <td><span class="badge badge-esc">ESC-04</span></td>
            <td>Anulación de asignaciones activas y reintegración automática del artículo al inventario</td>
            <td>AnularAsignacionesTest.php</td>
            <td style="text-align: center; font-weight: bold;">3</td>
        </tr>
        <tr>
            <td><strong>Asignaciones</strong></td>
            <td><span class="badge badge-rf">RF-05</span></td>
            <td><span class="badge badge-esc">ESC-05</span></td>
            <td>Consultas generales agrupadas por atleta, filtrado avanzado por texto/fechas y reportes</td>
            <td>ConsultarAsignacionesTest.php</td>
            <td style="text-align: center; font-weight: bold;">5</td>
        </tr>
        <tr>
            <td><strong>Devoluciones</strong></td>
            <td><span class="badge badge-rf">RF-01</span></td>
            <td><span class="badge badge-esc">ESC-01</span></td>
            <td>Validación de acciones, arreglo de entrada y sanitización de observaciones</td>
            <td>ValidarDatosDevolucionesTest.php</td>
            <td style="text-align: center; font-weight: bold;">3</td>
        </tr>
        <tr>
            <td><strong>Devoluciones</strong></td>
            <td><span class="badge badge-rf">RF-02</span></td>
            <td><span class="badge badge-esc">ESC-02</span></td>
            <td>Registro de devoluciones, ejecución de SP y liberación del equipo prestado</td>
            <td>RegistrarDevolucionesTest.php</td>
            <td style="text-align: center; font-weight: bold;">3</td>
        </tr>
        <tr>
            <td><strong>Devoluciones</strong></td>
            <td><span class="badge badge-rf">RF-03</span></td>
            <td><span class="badge badge-esc">ESC-03</span></td>
            <td>Modificación de devoluciones y actualización de estado físico en inventario</td>
            <td>ModificarDevolucionesTest.php</td>
            <td style="text-align: center; font-weight: bold;">4</td>
        </tr>
        <tr>
            <td><strong>Devoluciones</strong></td>
            <td><span class="badge badge-rf">RF-04</span></td>
            <td><span class="badge badge-esc">ESC-04</span></td>
            <td>Anulación de devoluciones y control de bloqueo si el equipo fue reasignado a otro atleta</td>
            <td>AnularDevolucionesTest.php</td>
            <td style="text-align: center; font-weight: bold;">5</td>
        </tr>
        <tr>
            <td><strong>Devoluciones</strong></td>
            <td><span class="badge badge-rf">RF-05</span></td>
            <td><span class="badge badge-esc">ESC-05</span></td>
            <td>Consulta de devoluciones agrupadas por atleta, filtros por estado/fecha y búsqueda individual</td>
            <td>ConsultarDevolucionesTest.php</td>
            <td style="text-align: center; font-weight: bold;">6</td>
        </tr>
        <tr>
            <td><strong>Estado Físico</strong></td>
            <td><span class="badge badge-rf">RF-01</span></td>
            <td><span class="badge badge-esc">ESC-01</span></td>
            <td>Validación de acciones válidas, datos vacíos y formateo de nombres en Title Case</td>
            <td>ValidarDatosEstadoFisicoTest.php</td>
            <td style="text-align: center; font-weight: bold;">3</td>
        </tr>
        <tr>
            <td><strong>Estado Físico</strong></td>
            <td><span class="badge badge-rf">RF-02</span></td>
            <td><span class="badge badge-esc">ESC-02</span></td>
            <td>Registro de estados físicos, control de nombres duplicados e inserción segura</td>
            <td>RegistrarEstadoFisicoTest.php</td>
            <td style="text-align: center; font-weight: bold;">3</td>
        </tr>
        <tr>
            <td><strong>Estado Físico</strong></td>
            <td><span class="badge badge-rf">RF-03</span></td>
            <td><span class="badge badge-esc">ESC-03</span></td>
            <td>Modificación conservando nombre propio o validando duplicados en otros registros</td>
            <td>ModificarEstadoFisicoTest.php</td>
            <td style="text-align: center; font-weight: bold;">4</td>
        </tr>
        <tr>
            <td><strong>Estado Físico</strong></td>
            <td><span class="badge badge-rf">RF-04</span></td>
            <td><span class="badge badge-esc">ESC-04</span></td>
            <td>Eliminación e integridad referencial contra inventario y devoluciones</td>
            <td>EliminarEstadoFisicoTest.php</td>
            <td style="text-align: center; font-weight: bold;">5</td>
        </tr>
        <tr>
            <td><strong>Estado Físico</strong></td>
            <td><span class="badge badge-rf">RF-05</span></td>
            <td><span class="badge badge-esc">ESC-05</span></td>
            <td>Consulta con filtros por texto y nivel de estado, y búsqueda individual por ID</td>
            <td>ConsultarEstadoFisicoTest.php</td>
            <td style="text-align: center; font-weight: bold;">6</td>
        </tr>
        <tr>
            <td><strong>Cat. Catálogo</strong></td>
            <td><span class="badge badge-rf">RF-01</span></td>
            <td><span class="badge badge-esc">ESC-01</span></td>
            <td>Validación de estructura, expresiones regulares (ID, nombre, descripción) y Title Case</td>
            <td>ValidarDatosCategoriaCatalogoTest.php</td>
            <td style="text-align: center; font-weight: bold;">6</td>
        </tr>
        <tr>
            <td><strong>Cat. Catálogo</strong></td>
            <td><span class="badge badge-rf">RF-02</span></td>
            <td><span class="badge badge-esc">ESC-02</span></td>
            <td>Registro de categorías, control de nombres duplicados y asignación por defecto</td>
            <td>RegistrarCategoriaCatalogoTest.php</td>
            <td style="text-align: center; font-weight: bold;">4</td>
        </tr>
        <tr>
            <td><strong>Cat. Catálogo</strong></td>
            <td><span class="badge badge-rf">RF-03</span></td>
            <td><span class="badge badge-esc">ESC-03</span></td>
            <td>Modificación de categorías, preservación de nombre propio y detección de colisiones</td>
            <td>ModificarCategoriaCatalogoTest.php</td>
            <td style="text-align: center; font-weight: bold;">4</td>
        </tr>
        <tr>
            <td><strong>Cat. Catálogo</strong></td>
            <td><span class="badge badge-rf">RF-04</span></td>
            <td><span class="badge badge-esc">ESC-04</span></td>
            <td>Eliminación física con verificación de existencia e integridad referencial con catálogo</td>
            <td>EliminarCategoriaCatalogoTest.php</td>
            <td style="text-align: center; font-weight: bold;">4</td>
        </tr>
        <tr>
            <td><strong>Cat. Catálogo</strong></td>
            <td><span class="badge badge-rf">RF-05</span></td>
            <td><span class="badge badge-esc">ESC-05</span></td>
            <td>Consulta general con filtros dinámicos y búsqueda individual por ID</td>
            <td>ConsultarCategoriaCatalogoTest.php</td>
            <td style="text-align: center; font-weight: bold;">7</td>
        </tr>
        <tr>
            <td><strong>Catálogo</strong></td>
            <td><span class="badge badge-rf">RF-01</span></td>
            <td><span class="badge badge-esc">ESC-01</span></td>
            <td>Validación de estructura, expresiones regulares (ID, nombre, stock, talla, pos) y normalización</td>
            <td>ValidarDatosCatalogoTest.php</td>
            <td style="text-align: center; font-weight: bold;">9</td>
        </tr>
        <tr>
            <td><strong>Catálogo</strong></td>
            <td><span class="badge badge-rf">RF-02</span></td>
            <td><span class="badge badge-esc">ESC-02</span></td>
            <td>Registro de artículos de catálogo, verificación de categoría foránea y control transaccional</td>
            <td>RegistrarCatalogoTest.php</td>
            <td style="text-align: center; font-weight: bold;">4</td>
        </tr>
        <tr>
            <td><strong>Catálogo</strong></td>
            <td><span class="badge badge-rf">RF-03</span></td>
            <td><span class="badge badge-esc">ESC-03</span></td>
            <td>Modificación de artículos existentes, verificación de existencia y actualización segura</td>
            <td>ModificarCatalogoTest.php</td>
            <td style="text-align: center; font-weight: bold;">4</td>
        </tr>
        <tr>
            <td><strong>Catálogo</strong></td>
            <td><span class="badge badge-rf">RF-04</span></td>
            <td><span class="badge badge-esc">ESC-04</span></td>
            <td>Eliminación segura vía SP `EliminarCatalogoSeguro` e integridad con inventario físico</td>
            <td>EliminarCatalogoTest.php</td>
            <td style="text-align: center; font-weight: bold;">4</td>
        </tr>
        <tr>
            <td><strong>Catálogo</strong></td>
            <td><span class="badge badge-rf">RF-05</span></td>
            <td><span class="badge badge-esc">ESC-05</span></td>
            <td>Consulta con subconsultas, filtro dinámico en cascada, búsqueda por ID y reporte</td>
            <td>ConsultarCatalogoTest.php</td>
            <td style="text-align: center; font-weight: bold;">8</td>
        </tr>
        <tr>
            <td><strong>Artículos Inv.</strong></td>
            <td><span class="badge badge-rf">RF-01</span></td>
            <td><span class="badge badge-esc">ESC-01</span></td>
            <td>Validación de estructura, acciones del modelo, carga de combos y correlativo club</td>
            <td>ValidarDatosArticulosInventarioTest.php</td>
            <td style="text-align: center; font-weight: bold;">5</td>
        </tr>
        <tr>
            <td><strong>Artículos Inv.</strong></td>
            <td><span class="badge badge-rf">RF-02</span></td>
            <td><span class="badge badge-esc">ESC-02</span></td>
            <td>Registro individual y en lote, correlativo club atómico y control transaccional</td>
            <td>RegistrarArticulosInventarioTest.php</td>
            <td style="text-align: center; font-weight: bold;">4</td>
        </tr>
        <tr>
            <td><strong>Artículos Inv.</strong></td>
            <td><span class="badge badge-rf">RF-03</span></td>
            <td><span class="badge badge-esc">ESC-03</span></td>
            <td>Modificación de artículos, consistencia de datos nuevos y cambio de estatus</td>
            <td>ModificarArticulosInventarioTest.php</td>
            <td style="text-align: center; font-weight: bold;">4</td>
        </tr>
        <tr>
            <td><strong>Artículos Inv.</strong></td>
            <td><span class="badge badge-rf">RF-04</span></td>
            <td><span class="badge badge-esc">ESC-04</span></td>
            <td>Retiro mediante SP `RetirarArticuloSeguro` y reincorporación de artículos</td>
            <td>EliminarArticulosInventarioTest.php</td>
            <td style="text-align: center; font-weight: bold;">8</td>
        </tr>
        <tr>
            <td><strong>Artículos Inv.</strong></td>
            <td><span class="badge badge-rf">RF-05</span></td>
            <td><span class="badge badge-esc">ESC-05</span></td>
            <td>Consulta agrupada por catálogo, filtros dinámicos, búsqueda por ID y artículos libres</td>
            <td>ConsultarArticulosInventarioTest.php</td>
            <td style="text-align: center; font-weight: bold;">9</td>
        </tr>
        <tr>
            <td><strong>Torneos</strong></td>
            <td><span class="badge badge-rf">RF-01</span></td>
            <td><span class="badge badge-esc">ESC-01</span></td>
            <td>Validación de estructura, expresiones regulares y consistencia cronológica de fechas de torneos</td>
            <td>ValidarDatosTorneosTest.php</td>
            <td style="text-align: center; font-weight: bold;">13</td>
        </tr>
        <tr>
            <td><strong>Torneos</strong></td>
            <td><span class="badge badge-rf">RF-02</span></td>
            <td><span class="badge badge-esc">ESC-02</span></td>
            <td>Registro e inclusión de torneos y validación de unicidad de nombre de torneo</td>
            <td>RegistrarTorneosTest.php</td>
            <td style="text-align: center; font-weight: bold;">3</td>
        </tr>
        <tr>
            <td><strong>Torneos</strong></td>
            <td><span class="badge badge-rf">RF-03</span></td>
            <td><span class="badge badge-esc">ESC-03</span></td>
            <td>Modificación de torneos, preservación de nombre propio y detección de duplicados</td>
            <td>ModificarTorneosTest.php</td>
            <td style="text-align: center; font-weight: bold;">4</td>
        </tr>
        <tr>
            <td><strong>Torneos</strong></td>
            <td><span class="badge badge-rf">RF-04</span></td>
            <td><span class="badge badge-esc">ESC-04</span></td>
            <td>Eliminación segura con control de integridad referencial contra equipos asociados</td>
            <td>EliminarTorneosTest.php</td>
            <td style="text-align: center; font-weight: bold;">4</td>
        </tr>
        <tr>
            <td><strong>Torneos</strong></td>
            <td><span class="badge badge-rf">RF-05</span></td>
            <td><span class="badge badge-esc">ESC-05</span></td>
            <td>Consulta general, filtros por texto/fecha/estatus, búsqueda individual, reportes y torneos próximos</td>
            <td>ConsultarTorneosTest.php</td>
            <td style="text-align: center; font-weight: bold;">11</td>
        </tr>
        <tr>
            <td><strong>Equipos</strong></td>
            <td><span class="badge badge-rf">RF-01</span></td>
            <td><span class="badge badge-esc">ESC-01</span></td>
            <td>Validación de estructura, expresiones regulares (ID, nombre) y restricciones de entrada</td>
            <td>ValidarDatosEquiposTest.php</td>
            <td style="text-align: center; font-weight: bold;">8</td>
        </tr>
        <tr>
            <td><strong>Equipos</strong></td>
            <td><span class="badge badge-rf">RF-02</span></td>
            <td><span class="badge badge-esc">ESC-02</span></td>
            <td>Registro de equipos, unicidad de nombre y validación de reglas de categoría de atletas</td>
            <td>RegistrarEquiposTest.php</td>
            <td style="text-align: center; font-weight: bold;">9</td>
        </tr>
        <tr>
            <td><strong>Equipos</strong></td>
            <td><span class="badge badge-rf">RF-03</span></td>
            <td><span class="badge badge-esc">ESC-03</span></td>
            <td>Modificación de equipos, preservación de nombre propio y reasignación segura de atletas</td>
            <td>ModificarEquiposTest.php</td>
            <td style="text-align: center; font-weight: bold;">5</td>
        </tr>
        <tr>
            <td><strong>Equipos</strong></td>
            <td><span class="badge badge-rf">RF-04</span></td>
            <td><span class="badge badge-esc">ESC-04</span></td>
            <td>Eliminación física con borrado en cascada de detalles e integridad con participaciones</td>
            <td>EliminarEquiposTest.php</td>
            <td style="text-align: center; font-weight: bold;">4</td>
        </tr>
        <tr>
            <td><strong>Equipos</strong></td>
            <td><span class="badge badge-rf">RF-05</span></td>
            <td><span class="badge badge-esc">ESC-05</span></td>
            <td>Consulta de equipos, búsqueda por ID, atletas para asignación modal y atletas asignados</td>
            <td>ConsultarEquiposTest.php</td>
            <td style="text-align: center; font-weight: bold;">13</td>
        </tr>
        <tr>
            <td><strong>Participaciones</strong></td>
            <td><span class="badge badge-rf">RF-01</span></td>
            <td><span class="badge badge-esc">ESC-01</span></td>
            <td>Validación de estructura, acciones del modelo y helpers de verificación de participación</td>
            <td>ValidarDatosParticipacionesTest.php</td>
            <td style="text-align: center; font-weight: bold;">11</td>
        </tr>
        <tr>
            <td><strong>Participaciones</strong></td>
            <td><span class="badge badge-rf">RF-02</span></td>
            <td><span class="badge badge-esc">ESC-02</span></td>
            <td>Registro de participaciones, validación foránea y generación de snapshot de atletas</td>
            <td>RegistrarParticipacionesTest.php</td>
            <td style="text-align: center; font-weight: bold;">5</td>
        </tr>
        <tr>
            <td><strong>Participaciones</strong></td>
            <td><span class="badge badge-rf">RF-03</span></td>
            <td><span class="badge badge-esc">ESC-03</span></td>
            <td>Modificación de participaciones, validación foránea, duplicidad y actualización de snapshot</td>
            <td>ModificarParticipacionesTest.php</td>
            <td style="text-align: center; font-weight: bold;">6</td>
        </tr>
        <tr>
            <td><strong>Participaciones</strong></td>
            <td><span class="badge badge-rf">RF-04</span></td>
            <td><span class="badge badge-esc">ESC-04</span></td>
            <td>Eliminación segura de participaciones y cascada atómica sobre snapshot de atletas</td>
            <td>EliminarParticipacionesTest.php</td>
            <td style="text-align: center; font-weight: bold;">3</td>
        </tr>
        <tr>
            <td><strong>Participaciones</strong></td>
            <td><span class="badge badge-rf">RF-05</span></td>
            <td><span class="badge badge-esc">ESC-05</span></td>
            <td>Consulta con filtros dinámicos por torneo/equipo/estatus, búsqueda por ID y reportes</td>
            <td>ConsultarParticipacionesTest.php</td>
            <td style="text-align: center; font-weight: bold;">12</td>
        </tr>
    </tbody>
</table>

<!-- SALTO DE PAGINA -->
<div class="page-break"></div>

<!-- SECCION ASIGNACIONES -->
<h2 class="section-header">2. Módulo de Asignaciones (ModeloAsignaciones)</h2>
<p style="font-size: 7.5pt; color: #475569; margin-top: -4px;">
    <strong>Archivo evaluado:</strong> <code>app/modelo/ModeloAsignaciones.php</code> &bull; 
    <strong>Directorio de pruebas:</strong> <code>tests/Unit/Asignaciones/</code> &bull; 
    <strong>Total pruebas:</strong> 32 &bull; <strong>Aserciones:</strong> 97 &bull; <strong>Cobertura:</strong> 86.64%
</p>

<!-- ESC-01 -->
<h3 class="scenario-header">ESC-01: Validación de Datos de Entrada y Reglas de Formato (RF-01)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 24%;">Caso de Prueba / Método</th>
            <th style="width: 18%;">Grupos PHPUnit</th>
            <th style="width: 25%;">Condición / Entrada</th>
            <th style="width: 25%;">Resultado Esperado / Aserción</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-01</span></td>
            <td><strong>testDatosVacios</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-01</code></td>
            <td>Arreglo de datos vacío: <code>[]</code></td>
            <td>Retorna error: <code>['accion' => 'error', 'codigo' => 'ERR_VACIO']</code></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-02</span></td>
            <td><strong>testAccionInvalida</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-02</code></td>
            <td><code>accion => 'accion_invalida'</code></td>
            <td>Retorna error: <code>['accion' => 'error', 'codigo' => 'ERR_ACCION']</code></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-03</span></td>
            <td><strong>testIdAsignacionInvalido</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-03</code></td>
            <td><code>id_asignacion => 'ABC'</code></td>
            <td>Lanza <code>Exception</code>: <em>"ID de asignación inválido."</em></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-04</span></td>
            <td><strong>testCodigoAtletaInvalido</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-04</code></td>
            <td><code>codigo_atleta => 'XYZ'</code></td>
            <td>Lanza <code>Exception</code>: <em>"Atleta inválido."</em></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-05</span></td>
            <td><strong>testCodigoArticuloInvalido</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-05</code></td>
            <td><code>codigo_articulo => 'ART-1'</code></td>
            <td>Lanza <code>Exception</code>: <em>"Artículo inválido."</em></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-06</span></td>
            <td><strong>testListaArticulosInvalidaNoArray</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-06</code></td>
            <td><code>articulos => 'no_array'</code></td>
            <td>Lanza <code>Exception</code>: <em>"La lista de artículos es inválida."</em></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-07</span></td>
            <td><strong>testItemArticuloInvalidoEnLista</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-07</code></td>
            <td><code>articulos => ['10', 'invalido']</code></td>
            <td>Lanza <code>Exception</code>: <em>"Uno de los artículos seleccionados es inválido."</em></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-08</span></td>
            <td><strong>testFechaAsignacionFormatoInvalido</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-08</code></td>
            <td><code>fecha_asignacion => '15/10/2026'</code></td>
            <td>Lanza <code>Exception</code>: <em>"Formato de fecha de asignación inválido. Use AAAA-MM-DD."</em></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-09</span></td>
            <td><strong>testFechaAsignacionAnterior2024</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-09</code></td>
            <td><code>fecha_asignacion => '2023-12-31'</code></td>
            <td>Lanza <code>Exception</code>: <em>"Fecha inválida. El sistema no admite registros anteriores al 2024."</em></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-10</span></td>
            <td><strong>testFechaAsignacionPasadaEnIncluir</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-10</code></td>
            <td><code>accion => 'incluir'</code>, fecha en el pasado</td>
            <td>Lanza <code>Exception</code>: <em>"La fecha de la nueva asignación no puede ser menor al día actual."</em></td>
        </tr>
    </tbody>
</table>

<!-- ESC-02 -->
<h3 class="scenario-header">ESC-02: Registro Individual y en Lote de Asignaciones (RF-02)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 24%;">Caso de Prueba / Método</th>
            <th style="width: 18%;">Grupos PHPUnit</th>
            <th style="width: 25%;">Condición / Simulación (Mocks)</th>
            <th style="width: 25%;">Resultado Esperado / Aserción</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-11</span></td>
            <td><strong>testRegistroIndividualExitoso</strong></td>
            <td><code>RF-02</code>, <code>ESC-02</code>, <code>CP-11</code></td>
            <td>Atleta existe, artículo libre (estatus 1), posición compatible</td>
            <td>Retorna <code>['accion' => 'exito', 'total' => 1]</code>. Estatus de artículo pasa a ocupado (2).</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-12</span></td>
            <td><strong>testRegistroEnLoteExitoso</strong></td>
            <td><code>RF-02</code>, <code>ESC-02</code>, <code>CP-12</code></td>
            <td>Múltiples artículos válidos para el mismo atleta</td>
            <td>Retorna <code>['accion' => 'exito', 'total' => N]</code>. Todos los artículos pasan a estatus ocupado (2).</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-13</span></td>
            <td><strong>testAtletaNoExiste</strong></td>
            <td><code>RF-02</code>, <code>ESC-02</code>, <code>CP-13</code></td>
            <td><code>verificarExistencia</code> de atleta retorna 0</td>
            <td>Rollback transaccional. Retorna <code>['accion' => 'error', 'codigo' => 'ERR_ATLETA_NO_EXISTE']</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-14</span></td>
            <td><strong>testArticuloNoExiste</strong></td>
            <td><code>RF-02</code>, <code>ESC-02</code>, <code>CP-14</code></td>
            <td>Consulta de artículo retorna <code>false</code></td>
            <td>Rollback. Retorna <code>ERR_EQUIPO_NO_EXISTE</code> con <code>articulo_error = '999'</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-15</span></td>
            <td><strong>testArticuloOcupado</strong></td>
            <td><code>RF-02</code>, <code>ESC-02</code>, <code>CP-15</code></td>
            <td>Artículo tiene <code>estatus = 2</code> (en uso)</td>
            <td>Rollback. Retorna <code>ERR_EQUIPO_OCUPADO</code> con <code>articulo_error = 'CL-0010'</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-16</span></td>
            <td><strong>testPosicionNoCoincide</strong></td>
            <td><code>RF-02</code>, <code>ESC-02</code>, <code>CP-16</code></td>
            <td>Artículo requiere posición 3 y el atleta es posición 1</td>
            <td>Rollback. Retorna <code>ERR_POSICION_NO_COINCIDE</code>. Transacción abortada.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-17</span></td>
            <td><strong>testRollbackEnLoteSiUnoFalla</strong></td>
            <td><code>RF-02</code>, <code>ESC-02</code>, <code>CP-17</code></td>
            <td>Lote de 2: 1° artículo libre, pero el 2° está ocupado</td>
            <td>Rollback total. Ningún artículo es asignado ni cambia de estatus en inventario.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-18</span></td>
            <td><strong>testErrorBaseDeDatosEnInsercion</strong></td>
            <td><code>RF-02</code>, <code>ESC-02</code>, <code>CP-18</code></td>
            <td>Sentencia de inserción arroja <code>PDOException</code></td>
            <td>Rollback ejecutado. Retorna <code>['accion' => 'error', 'codigo' => 'ERR_BD']</code>.</td>
        </tr>
    </tbody>
</table>

<!-- ESC-03 -->
<h3 class="scenario-header">ESC-03: Modificación y Reasignación de Artículos (RF-03)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 24%;">Caso de Prueba / Método</th>
            <th style="width: 18%;">Grupos PHPUnit</th>
            <th style="width: 25%;">Condición / Simulación (Mocks)</th>
            <th style="width: 25%;">Resultado Esperado / Aserción</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-19</span></td>
            <td><strong>testModificarCambiandoArticuloExitoso</strong></td>
            <td><code>RF-03</code>, <code>ESC-03</code>, <code>CP-19</code></td>
            <td>Asignación activa, nuevo artículo libre y compatible</td>
            <td>Libera artículo anterior a 1, ocupa nuevo a 2. Retorna <code>['accion' => 'exito']</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-20</span></td>
            <td><strong>testModificarMismoArticuloExitoso</strong></td>
            <td><code>RF-03</code>, <code>ESC-03</code>, <code>CP-20</code></td>
            <td>Mismo artículo, actualización de fecha/atleta</td>
            <td>Actualiza tabla <code>asignaciones</code> sin alterar inventario. Retorna éxito.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-21</span></td>
            <td><strong>testModificarAsignacionNoExiste</strong></td>
            <td><code>RF-03</code>, <code>ESC-03</code>, <code>CP-21</code></td>
            <td><code>verificarExistencia</code> de <code>id_asignacion</code> retorna 0</td>
            <td>Retorna <code>['accion' => 'error', 'codigo' => 'ERR_NO_EXISTE']</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-22</span></td>
            <td><strong>testModificarAsignacionEstatusInvalido</strong></td>
            <td><code>RF-03</code>, <code>ESC-03</code>, <code>CP-22</code></td>
            <td>Asignación con <code>estatus = 2</code> (ya devuelta)</td>
            <td>Retorna <code>['accion' => 'error', 'codigo' => 'ERR_ESTATUS']</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-23</span></td>
            <td><strong>testModificarNuevoArticuloOcupado</strong></td>
            <td><code>RF-03</code>, <code>ESC-03</code>, <code>CP-23</code></td>
            <td>Nuevo artículo seleccionado tiene <code>estatus = 2</code></td>
            <td>Retorna <code>['accion' => 'error', 'codigo' => 'ERR_EQUIPO_NO_DISPONIBLE']</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-24</span></td>
            <td><strong>testModificarPosicionNoCoincide</strong></td>
            <td><code>RF-03</code>, <code>ESC-03</code>, <code>CP-24</code></td>
            <td>Posición requerida por nuevo artículo difiere de atleta</td>
            <td>Retorna <code>['accion' => 'error', 'codigo' => 'ERR_POSICION_NO_COINCIDE']</code>.</td>
        </tr>
    </tbody>
</table>

<!-- ESC-04 -->
<h3 class="scenario-header">ESC-04: Anulación y Reintegro de Artículos al Inventario (RF-04)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 24%;">Caso de Prueba / Método</th>
            <th style="width: 18%;">Grupos PHPUnit</th>
            <th style="width: 25%;">Condición / Simulación (Mocks)</th>
            <th style="width: 25%;">Resultado Esperado / Aserción</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-25</span></td>
            <td><strong>testAnulacionExitosa</strong></td>
            <td><code>RF-04</code>, <code>ESC-04</code>, <code>CP-25</code></td>
            <td>Asignación existe con artículo asignado '10'</td>
            <td>Asignación pasa a estatus 3 y artículo vuelve a estatus 1 (disponible). Retorna éxito.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-26</span></td>
            <td><strong>testAnulacionAsignacionNoExiste</strong></td>
            <td><code>RF-04</code>, <code>ESC-04</code>, <code>CP-26</code></td>
            <td>Asignación no existe en la base de datos</td>
            <td>Retorna <code>['accion' => 'error', 'codigo' => 'ERR_NO_EXISTE']</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-27</span></td>
            <td><strong>testErrorBaseDeDatosEnAnulacion</strong></td>
            <td><code>RF-04</code>, <code>ESC-04</code>, <code>CP-27</code></td>
            <td>Fallo de bloqueo o actualización arroja excepción</td>
            <td>Rollback ejecutado. Retorna <code>['accion' => 'error', 'codigo' => 'ERR_BD']</code>.</td>
        </tr>
    </tbody>
</table>

<!-- ESC-05 -->
<h3 class="scenario-header">ESC-05: Consulta General Agrupada, Filtros y Reportes (RF-05)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 24%;">Caso de Prueba / Método</th>
            <th style="width: 18%;">Grupos PHPUnit</th>
            <th style="width: 25%;">Condición / Simulación (Mocks)</th>
            <th style="width: 25%;">Resultado Esperado / Aserción</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-28</span></td>
            <td><strong>testConsultaGeneralAgrupadaPorAtleta</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-28</code></td>
            <td>Vista devuelve 2 artículos para un mismo atleta</td>
            <td>Retorna 1 atleta agrupado con arreglo de 2 asignaciones anidadas.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-29</span></td>
            <td><strong>testConsultaConFiltroTexto</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-29</code></td>
            <td><code>filtro => 'Casco'</code></td>
            <td>Aplica cláusula <code>LIKE</code> en nombre de atleta, cédula, artículo y fecha.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-30</span></td>
            <td><strong>testConsultaConRangoFechas</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-30</code></td>
            <td><code>fecha_asignacion</code> y <code>fecha_f</code> provistas</td>
            <td>Aplica cláusula <code>BETWEEN :fecha_inicio AND :fecha_fin</code> en la consulta SQL.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-31</span></td>
            <td><strong>testBuscarPorIdIndividual</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-31</code></td>
            <td>Invocación de <code>Buscar(5)</code></td>
            <td>Retorna <code>['accion' => 'buscar', 'datos' => [...]]</code> con el registro individual.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-32</span></td>
            <td><strong>testConsultarAsignacionesDevolucion</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-32</code></td>
            <td>Invocación de <code>ConsultarAsignacionesDevolucion()</code></td>
            <td>Retorna listado plano de asignaciones activas listo para el módulo de devoluciones.</td>
        </tr>
    </tbody>
</table>

<!-- SALTO DE PAGINA -->
<div class="page-break"></div>

<!-- SECCION DEVOLUCIONES -->
<h2 class="section-header">3. Módulo de Devoluciones (ModeloDevoluciones)</h2>
<p style="font-size: 7.5pt; color: #475569; margin-top: -4px;">
    <strong>Archivo evaluado:</strong> <code>app/modelo/ModeloDevoluciones.php</code> &bull; 
    <strong>Directorio de pruebas:</strong> <code>tests/Unit/Devoluciones/</code> &bull; 
    <strong>Total pruebas:</strong> 21 &bull; <strong>Aserciones:</strong> 65 &bull; <strong>Cobertura:</strong> 96.58%
</p>

<!-- ESC-01 DEVOLUCIONES -->
<h3 class="scenario-header">ESC-01: Validación de Acciones y Parámetros del Modelo (RF-01)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 24%;">Caso de Prueba / Método</th>
            <th style="width: 18%;">Grupos PHPUnit</th>
            <th style="width: 25%;">Condición / Entrada</th>
            <th style="width: 25%;">Resultado Esperado / Aserción</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-01</span></td>
            <td><strong>testAccionInvalida</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-01</code></td>
            <td><code>accion => 'accion_invalida'</code></td>
            <td>Retorna error: <code>['accion' => 'error', 'codigo' => 'Acción no válida']</code></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-02</span></td>
            <td><strong>testDatosVacios</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-02</code></td>
            <td>Arreglo de entrada vacío: <code>[]</code></td>
            <td>Retorna error: <code>['accion' => 'error', 'codigo' => 'Acción no válida']</code></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-03</span></td>
            <td><strong>testObservacionSanitizada</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-03</code></td>
            <td><code>observacion => '   Devuelto con desgaste normal   '</code></td>
            <td>La propiedad privada <code>\$observacion</code> es limpiada con <code>trim()</code> sin espacios laterales.</td>
        </tr>
    </tbody>
</table>

<!-- ESC-02 DEVOLUCIONES -->
<h3 class="scenario-header">ESC-02: Registro de Devolución de Equipos y Liberación de Inventario (RF-02)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 24%;">Caso de Prueba / Método</th>
            <th style="width: 18%;">Grupos PHPUnit</th>
            <th style="width: 25%;">Condición / Simulación (Mocks)</th>
            <th style="width: 25%;">Resultado Esperado / Aserción</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-04</span></td>
            <td><strong>testRegistroDevolucionExitoso</strong></td>
            <td><code>RF-02</code>, <code>ESC-02</code>, <code>CP-04</code></td>
            <td>Asignación activa existe, ejecución de SP exitosa</td>
            <td>Retorna <code>['accion' => 'exito', 'mensaje' => 'Devolución procesada y equipo liberado.', 'id_devolucion' => 42]</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-05</span></td>
            <td><strong>testAsignacionInvalidaOYaDevuelta</strong></td>
            <td><code>RF-02</code>, <code>ESC-02</code>, <code>CP-05</code></td>
            <td>Asignación no existe o tiene estatus distinto de 1</td>
            <td>Retorna <code>['accion' => 'error', 'mensaje' => 'La asignación no existe, no es válida o ya fue devuelta.']</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-06</span></td>
            <td><strong>testErrorBaseDeDatosEnProcedimientoAlmacenado</strong></td>
            <td><code>RF-02</code>, <code>ESC-02</code>, <code>CP-06</code></td>
            <td>Procedimiento <code>ProcesarDevolucionSegura</code> arroja <code>PDOException</code></td>
            <td>Captura fallo y retorna <code>['accion' => 'error', 'mensaje' => ...]</code>.</td>
        </tr>
    </tbody>
</table>

<!-- ESC-03 DEVOLUCIONES -->
<h3 class="scenario-header">ESC-03: Modificación de Registros de Devolución y Estado del Equipo (RF-03)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 24%;">Caso de Prueba / Método</th>
            <th style="width: 18%;">Grupos PHPUnit</th>
            <th style="width: 25%;">Condición / Simulación (Mocks)</th>
            <th style="width: 25%;">Resultado Esperado / Aserción</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-07</span></td>
            <td><strong>testModificarDevolucionSinCambioDeEstado</strong></td>
            <td><code>RF-03</code>, <code>ESC-03</code>, <code>CP-07</code></td>
            <td>Devolución existe, conserva mismo estado físico</td>
            <td>Actualiza tabla <code>devoluciones</code> sin modificar <code>articulos_inventario</code>. Retorna éxito.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-08</span></td>
            <td><strong>testModificarDevolucionConCambioDeEstadoFisico</strong></td>
            <td><code>RF-03</code>, <code>ESC-03</code>, <code>CP-08</code></td>
            <td>Estado físico anterior (1) cambia a nuevo estado (3)</td>
            <td>Actualiza <code>devoluciones</code> y actualiza <code>articulos_inventario.id_estado</code>. Retorna éxito.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-09</span></td>
            <td><strong>testModificarDevolucionNoExiste</strong></td>
            <td><code>RF-03</code>, <code>ESC-03</code>, <code>CP-09</code></td>
            <td><code>VerificarExistenciaDevolucion</code> retorna <code>false</code></td>
            <td>Retorna <code>['accion' => 'error', 'mensaje' => 'El registro de devolución que intenta modificar no existe.']</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-10</span></td>
            <td><strong>testErrorBaseDeDatosEnModificacion</strong></td>
            <td><code>RF-03</code>, <code>ESC-03</code>, <code>CP-10</code></td>
            <td>Fallo durante actualización arroja <code>PDOException</code></td>
            <td>Rollback ejecutado. Retorna mensaje de error capturado.</td>
        </tr>
    </tbody>
</table>

<!-- ESC-04 DEVOLUCIONES -->
<h3 class="scenario-header">ESC-04: Anulación de Devoluciones y Reversión de Estatus (RF-04)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 24%;">Caso de Prueba / Método</th>
            <th style="width: 18%;">Grupos PHPUnit</th>
            <th style="width: 25%;">Condición / Simulación (Mocks)</th>
            <th style="width: 25%;">Resultado Esperado / Aserción</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-11</span></td>
            <td><strong>testAnulacionExitosaSinReasignacion</strong></td>
            <td><code>RF-04</code>, <code>ESC-04</code>, <code>CP-11</code></td>
            <td>Equipo libre (enUso = 0). Se anula devolución</td>
            <td>Elimina devolución, asignación vuelve a activa (1) y artículo a ocupado (2). Retorna éxito.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-12</span></td>
            <td><strong>testRechazoPorArticuloYaReasignado</strong></td>
            <td><code>RF-04</code>, <code>ESC-04</code>, <code>CP-12</code></td>
            <td>El equipo ya fue asignado a otro atleta (enUso > 0)</td>
            <td>Rollback. Error: <em>"No se puede anular la devolución. El artículo ya ha sido reasignado..."</em></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-13</span></td>
            <td><strong>testAnulacionDevolucionNoExiste</strong></td>
            <td><code>RF-04</code>, <code>ESC-04</code>, <code>CP-13</code></td>
            <td>Registro de devolución no existe en la BD</td>
            <td>Retorna <code>['accion' => 'error', 'mensaje' => 'El registro que intenta anular ya no existe...']</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-14</span></td>
            <td><strong>testAnulacionConObjetoAsignacionesInyectado</strong></td>
            <td><code>RF-04</code>, <code>ESC-04</code>, <code>CP-14</code></td>
            <td>Inyección del modelo <code>ModeloAsignaciones</code></td>
            <td>Delega cambio en <code>CambiarEstatusAsignacion(id, 1, conex)</code>. Retorna éxito.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-15</span></td>
            <td><strong>testErrorBaseDeDatosEnAnulacion</strong></td>
            <td><code>RF-04</code>, <code>ESC-04</code>, <code>CP-15</code></td>
            <td>Fallo durante la eliminación o bloqueo</td>
            <td>Rollback ejecutado. Retorna mensaje de error de BD.</td>
        </tr>
    </tbody>
</table>

<!-- ESC-05 DEVOLUCIONES -->
<h3 class="scenario-header">ESC-05: Consulta Agrupada, Filtros Avanzados y Búsqueda (RF-05)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 24%;">Caso de Prueba / Método</th>
            <th style="width: 18%;">Grupos PHPUnit</th>
            <th style="width: 25%;">Condición / Simulación (Mocks)</th>
            <th style="width: 25%;">Resultado Esperado / Aserción</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-16</span></td>
            <td><strong>testConsultaGeneralAgrupadaPorAtleta</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-16</code></td>
            <td>Vista devuelve 2 devoluciones del mismo atleta</td>
            <td>Retorna datos agrupados por atleta con 2 devoluciones y <code>total_devoluciones_atleta = 2</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-17</span></td>
            <td><strong>testConsultaConFiltroTexto</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-17</code></td>
            <td><code>filtro => 'Pads'</code></td>
            <td>Aplica cláusula <code>LIKE</code> en nombre, apellido, cédula, artículo y fecha.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-18</span></td>
            <td><strong>testConsultaConFiltrosAtletaAsignacionEstado</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-18</code></td>
            <td>Filtros específicos: atleta, asignación y estado</td>
            <td>Se agregan cláusulas de igualdad correspondientes en la consulta SQL.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-19</span></td>
            <td><strong>testConsultaConRangoFechas</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-19</code></td>
            <td>Rango: <code>fecha_desde</code> y <code>fecha_hasta</code></td>
            <td>Aplica filtros <code>>= ? 00:00:00</code> y <code><= ? 23:59:59</code> en la consulta SQL.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-20</span></td>
            <td><strong>testBuscarPorIdIndividual</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-20</code></td>
            <td>Invocación de <code>Buscar(1)</code></td>
            <td>Retorna <code>['accion' => 'buscar', 'datos' => [...]]</code> con el registro individual.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-21</span></td>
            <td><strong>testConsultaManejoDeExcepcion</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-21</code></td>
            <td>Fallo de consulta en la vista</td>
            <td>Retorna <code>['accion' => 'error', 'codigo' => ...]</code>.</td>
        </tr>
    </tbody>
</table>

<!-- SALTO DE PAGINA -->
<div class="page-break"></div>

<!-- SECCION ESTADO FISICO -->
<h2 class="section-header">4. Módulo de Estado Físico (ModeloEstadoFisico)</h2>
<p style="font-size: 7.5pt; color: #475569; margin-top: -4px;">
    <strong>Archivo evaluado:</strong> <code>app/modelo/ModeloEstadoFisico.php</code> &bull; 
    <strong>Directorio de pruebas:</strong> <code>tests/Unit/EstadoFisico/</code> &bull; 
    <strong>Total pruebas:</strong> 21 &bull; <strong>Aserciones:</strong> 45 &bull; <strong>Cobertura:</strong> 97.17%
</p>

<!-- ESC-01 ESTADO FISICO -->
<h3 class="scenario-header">ESC-01: Validación de Acciones y Parámetros del Modelo (RF-01)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 24%;">Caso de Prueba / Método</th>
            <th style="width: 18%;">Grupos PHPUnit</th>
            <th style="width: 25%;">Condición / Entrada</th>
            <th style="width: 25%;">Resultado Esperado / Aserción</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-01</span></td>
            <td><strong>testDatosVaciosLanzaExcepcion</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-01</code></td>
            <td>Arreglo de entrada vacío: <code>[]</code></td>
            <td>Lanza <code>Exception</code>: <em>"No se proporcionaron datos para procesar."</em></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-02</span></td>
            <td><strong>testAccionInvalidaLanzaExcepcion</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-02</code></td>
            <td><code>accion => 'accion_no_valida'</code></td>
            <td>Lanza <code>Exception</code>: <em>"La accion no es valida"</em></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-03</span></td>
            <td><strong>testConversionNombreTitleCase</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-03</code></td>
            <td><code>nombre => '   buen estado físico   '</code></td>
            <td>Convierte automáticamente el nombre a formato Title Case: <code>"Buen Estado Físico"</code>.</td>
        </tr>
    </tbody>
</table>

<!-- ESC-02 ESTADO FISICO -->
<h3 class="scenario-header">ESC-02: Registro de Nuevos Estados Físicos (RF-02)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 24%;">Caso de Prueba / Método</th>
            <th style="width: 18%;">Grupos PHPUnit</th>
            <th style="width: 25%;">Condición / Simulación (Mocks)</th>
            <th style="width: 25%;">Resultado Esperado / Aserción</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-04</span></td>
            <td><strong>testIncluirEstadoFisicoExitoso</strong></td>
            <td><code>RF-02</code>, <code>ESC-02</code>, <code>CP-04</code></td>
            <td>Nombre no registrado previamente (<code>verificarExistencia = 0</code>)</td>
            <td>Retorna <code>['accion' => 'incluir', 'mensaje' => 'Estado físico registrado exitosamente.']</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-05</span></td>
            <td><strong>testIncluirNombreDuplicado</strong></td>
            <td><code>RF-02</code>, <code>ESC-02</code>, <code>CP-05</code></td>
            <td>Nombre ya existe en BD (<code>verificarExistencia = 1</code>)</td>
            <td>Retorna error: <em>"Ya existe un estado físico registrado con este nombre."</em></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-06</span></td>
            <td><strong>testErrorBaseDeDatosEnIncluir</strong></td>
            <td><code>RF-02</code>, <code>ESC-02</code>, <code>CP-06</code></td>
            <td>Sentencia de inserción arroja <code>PDOException</code></td>
            <td>Captura fallo y retorna <code>['accion' => 'error', 'mensaje' => 'Error al incluir: ...']</code>.</td>
        </tr>
    </tbody>
</table>

<!-- ESC-03 ESTADO FISICO -->
<h3 class="scenario-header">ESC-03: Modificación y Actualización de Estados Físicos (RF-03)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 24%;">Caso de Prueba / Método</th>
            <th style="width: 18%;">Grupos PHPUnit</th>
            <th style="width: 25%;">Condición / Simulación (Mocks)</th>
            <th style="width: 25%;">Resultado Esperado / Aserción</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-07</span></td>
            <td><strong>testModificarEstadoFisicoExitosoMismoNombre</strong></td>
            <td><code>RF-03</code>, <code>ESC-03</code>, <code>CP-07</code></td>
            <td>Conserva su nombre propio (<code>verificarExistenciaPropia = 1</code>)</td>
            <td>Ejecuta <code>UPDATE</code> sin colisión. Retorna mensaje de modificación exitosa.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-08</span></td>
            <td><strong>testModificarEstadoFisicoExitosoNuevoNombre</strong></td>
            <td><code>RF-03</code>, <code>ESC-03</code>, <code>CP-08</code></td>
            <td>Cambia de nombre a uno nuevo no ocupado por otros</td>
            <td>Verificaciones propias y globales superadas. Actualiza registro y retorna éxito.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-09</span></td>
            <td><strong>testModificarNombreDuplicadoEnOtroRegistro</strong></td>
            <td><code>RF-03</code>, <code>ESC-03</code>, <code>CP-09</code></td>
            <td>Nuevo nombre ya pertenece a otro registro existente</td>
            <td>Retorna error: <em>"Ya existe otro estado físico registrado con este nombre."</em></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-10</span></td>
            <td><strong>testErrorBaseDeDatosEnModificar</strong></td>
            <td><code>RF-03</code>, <code>ESC-03</code>, <code>CP-10</code></td>
            <td>Fallo durante actualización arroja <code>PDOException</code></td>
            <td>Captura fallo y retorna <code>['accion' => 'error', 'mensaje' => 'Error al modificar: ...']</code>.</td>
        </tr>
    </tbody>
</table>

<!-- ESC-04 ESTADO FISICO -->
<h3 class="scenario-header">ESC-04: Eliminación de Estados Físicos y Control de Integridad (RF-04)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 24%;">Caso de Prueba / Método</th>
            <th style="width: 18%;">Grupos PHPUnit</th>
            <th style="width: 25%;">Condición / Simulación (Mocks)</th>
            <th style="width: 25%;">Resultado Esperado / Aserción</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-11</span></td>
            <td><strong>testEliminarEstadoFisicoExitoso</strong></td>
            <td><code>RF-04</code>, <code>ESC-04</code>, <code>CP-11</code></td>
            <td>Estado existe, sin artículos ni devoluciones asociadas</td>
            <td>Ejecuta <code>DELETE</code>. Retorna <code>['accion' => 'eliminar', 'mensaje' => 'Estado físico eliminado exitosamente.']</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-12</span></td>
            <td><strong>testEliminarEstadoFisicoNoExiste</strong></td>
            <td><code>RF-04</code>, <code>ESC-04</code>, <code>CP-12</code></td>
            <td>El ID a eliminar no existe en <code>estado_fisico</code></td>
            <td>Retorna error: <em>"El estado físico no existe."</em></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-13</span></td>
            <td><strong>testEliminarRechazoPorArticulosAsociados</strong></td>
            <td><code>RF-04</code>, <code>ESC-04</code>, <code>CP-13</code></td>
            <td>Existen artículos de inventario vinculados al estado</td>
            <td>Integridad referencial protegida. Retorna: <em>"No se puede eliminar: existen artículos de inventario..."</em></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-14</span></td>
            <td><strong>testEliminarRechazoPorDevolucionesAsociadas</strong></td>
            <td><code>RF-04</code>, <code>ESC-04</code>, <code>CP-14</code></td>
            <td>Existen registros de devoluciones con este estado</td>
            <td>Integridad referencial protegida. Retorna: <em>"No se puede eliminar: existen devoluciones asociadas..."</em></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-15</span></td>
            <td><strong>testErrorBaseDeDatosEnEliminar</strong></td>
            <td><code>RF-04</code>, <code>ESC-04</code>, <code>CP-15</code></td>
            <td>Sentencia de eliminación arroja <code>PDOException</code></td>
            <td>Captura error y retorna: <em>"Hubo un error al eliminar el estado físico."</em></td>
        </tr>
    </tbody>
</table>

<!-- ESC-05 ESTADO FISICO -->
<h3 class="scenario-header">ESC-05: Consulta General, Filtros y Búsqueda por ID (RF-05)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 24%;">Caso de Prueba / Método</th>
            <th style="width: 18%;">Grupos PHPUnit</th>
            <th style="width: 25%;">Condición / Simulación (Mocks)</th>
            <th style="width: 25%;">Resultado Esperado / Aserción</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-16</span></td>
            <td><strong>testConsultarSinFiltrosExitoso</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-16</code></td>
            <td>Consulta general <code>WHERE 1=1 ORDER BY id_estado ASC</code></td>
            <td>Retorna arreglo completo de estados físicos disponibles.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-17</span></td>
            <td><strong>testConsultarConFiltroTexto</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-17</code></td>
            <td><code>filtro => 'Bueno'</code></td>
            <td>Aplica cláusula <code>AND (nombre LIKE :f1 OR nivel_estado LIKE :f2)</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-18</span></td>
            <td><strong>testConsultarConFiltroNivelEstado</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-18</code></td>
            <td><code>nivel_estado => '2'</code></td>
            <td>Aplica cláusula <code>AND nivel_estado = :filtro_nivel</code> en la sentencia SQL.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-19</span></td>
            <td><strong>testBuscarPorIdIndividualExitoso</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-19</code></td>
            <td>Invocación directa de <code>Buscar(1)</code> por parámetro</td>
            <td>Retorna <code>['accion' => 'buscar', 'datos' => [...]]</code> con el registro individual.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-20</span></td>
            <td><strong>testBuscarPorAtributoModelo</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-20</code></td>
            <td>Invocación a través de <code>ProcesarDatos(['accion' => 'buscar', 'id_estado' => 2])</code></td>
            <td>Toma el atributo interno del modelo y retorna el registro correspondiente.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-21</span></td>
            <td><strong>testConsultarManejoDeExcepcion</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-21</code></td>
            <td>Fallo en la preparación o ejecución de la consulta</td>
            <td>Captura excepción y retorna <code>['accion' => 'error', 'mensaje' => 'Error al listar: ...']</code>.</td>
        </tr>
    </tbody>
</table>

<!-- SALTO DE PAGINA -->
<div class="page-break"></div>

<!-- SECCION CATEGORIA CATALOGO -->
<h2 class="section-header">5. Módulo de Categoría Catálogo (ModeloCategoriaCatalogo)</h2>
<p style="font-size: 7.5pt; color: #475569; margin-top: -4px;">
    <strong>Archivo evaluado:</strong> <code>app/modelo/ModeloCategoriaCatalogo.php</code> &bull; 
    <strong>Directorio de pruebas:</strong> <code>tests/Unit/CategoriaCatalogo/</code> &bull; 
    <strong>Total pruebas:</strong> 25 &bull; <strong>Aserciones:</strong> 70 &bull; <strong>Cobertura:</strong> 100.00% (115/115 líneas)
</p>

<!-- ESC-01 CATEGORIA CATALOGO -->
<h3 class="scenario-header">ESC-01: Validación de Acciones, Datos de Entrada y Expresiones Regulares (RF-01)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 24%;">Caso de Prueba / Método</th>
            <th style="width: 18%;">Grupos PHPUnit</th>
            <th style="width: 25%;">Condición / Entrada</th>
            <th style="width: 25%;">Resultado Esperado / Aserción</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-01</span></td>
            <td><strong>testDatosVaciosLanzaExcepcion</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-01</code></td>
            <td>Arreglo de entrada vacío: <code>[]</code></td>
            <td>Lanza <code>Exception</code>: <em>"No se proporcionaron datos para procesar."</em></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-02</span></td>
            <td><strong>testAccionInvalidaLanzaExcepcion</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-02</code></td>
            <td><code>accion => 'accion_no_valida'</code></td>
            <td>Lanza <code>Exception</code>: <em>"La accion no es valida"</em></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-03</span></td>
            <td><strong>testIdCategoriaInvalidoLanzaExcepcion</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-03</code></td>
            <td><code>id_categoria => '12abc'</code> (no numérico)</td>
            <td>Lanza <code>Exception</code>: <em>"Id inválido."</em></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-04</span></td>
            <td><strong>testNombreInvalidoLanzaExcepcion</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-04</code></td>
            <td><code>nombre => 'C'</code> (menor a 2 caracteres)</td>
            <td>Lanza <code>Exception</code>: <em>"Nombre de categoría inválido."</em></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-05</span></td>
            <td><strong>testDescripcionInvalidaLanzaExcepcion</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-05</code></td>
            <td>Descripción con caracteres prohibidos (<code>@</code>, <code>#</code>)</td>
            <td>Lanza <code>Exception</code>: <em>"Descripción inválida."</em></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-06</span></td>
            <td><strong>testConversionNombreTitleCaseYTrim</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-06</code></td>
            <td><code>nombre => '   cascos de portero   '</code></td>
            <td>Normaliza a Title Case y trim: <code>"Cascos De Portero"</code>.</td>
        </tr>
    </tbody>
</table>

<!-- ESC-02 CATEGORIA CATALOGO -->
<h3 class="scenario-header">ESC-02: Registro de Categorías de Catálogo (RF-02)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 24%;">Caso de Prueba / Método</th>
            <th style="width: 18%;">Grupos PHPUnit</th>
            <th style="width: 25%;">Condición / Simulación (Mocks)</th>
            <th style="width: 25%;">Resultado Esperado / Aserción</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-07</span></td>
            <td><strong>testIncluirCategoriaCatalogoExitoso</strong></td>
            <td><code>RF-02</code>, <code>ESC-02</code>, <code>CP-07</code></td>
            <td>Categoría nueva (<code>verificarExistencia = 0</code>)</td>
            <td>Retorna <code>['accion' => 'incluir', 'mensaje' => 'Categoría registrada exitosamente.']</code> con <code>datos_nuevos</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-08</span></td>
            <td><strong>testIncluirNombreDuplicado</strong></td>
            <td><code>RF-02</code>, <code>ESC-02</code>, <code>CP-08</code></td>
            <td>Nombre ya existente (<code>verificarExistencia = 1</code>)</td>
            <td>Retorna error: <em>"Ya existe una categoría registrada con este nombre."</em></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-09</span></td>
            <td><strong>testIncluirErrorBaseDeDatos</strong></td>
            <td><code>RF-02</code>, <code>ESC-02</code>, <code>CP-09</code></td>
            <td>Sentencia INSERT arroja <code>PDOException</code></td>
            <td>Captura fallo y retorna <code>['accion' => 'error', 'mensaje' => 'Error al incluir: ...']</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-10</span></td>
            <td><strong>testIncluirTipoTallaPorDefectoNumerico</strong></td>
            <td><code>RF-02</code>, <code>ESC-02</code>, <code>CP-10</code></td>
            <td>Omisión del parámetro <code>tipo_talla</code></td>
            <td>Asigna automáticamente el valor por defecto: <code>'Numerico'</code>.</td>
        </tr>
    </tbody>
</table>

<!-- ESC-03 CATEGORIA CATALOGO -->
<h3 class="scenario-header">ESC-03: Modificación y Actualización de Categorías de Catálogo (RF-03)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 24%;">Caso de Prueba / Método</th>
            <th style="width: 18%;">Grupos PHPUnit</th>
            <th style="width: 25%;">Condición / Simulación (Mocks)</th>
            <th style="width: 25%;">Resultado Esperado / Aserción</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-11</span></td>
            <td><strong>testModificarCategoriaCatalogoExitosoConNombreNuevo</strong></td>
            <td><code>RF-03</code>, <code>ESC-03</code>, <code>CP-11</code></td>
            <td>Nombre actualizado no colisiona con otros registros</td>
            <td>Actualiza en BD y retorna: <em>"Categoría modificada exitosamente."</em></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-12</span></td>
            <td><strong>testModificarConservandoNombrePropio</strong></td>
            <td><code>RF-03</code>, <code>ESC-03</code>, <code>CP-12</code></td>
            <td>Conserva su nombre propio (<code>verificarExistenciaPropia = 1</code>)</td>
            <td>Omite validación de duplicidad global. Modifica exitosamente.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-13</span></td>
            <td><strong>testModificarNombreDuplicadoEnOtraCategoria</strong></td>
            <td><code>RF-03</code>, <code>ESC-03</code>, <code>CP-13</code></td>
            <td>Nombre ya pertenece a otra categoría diferente</td>
            <td>Retorna error: <em>"Ya existe otra categoría registrada con este nombre."</em></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-14</span></td>
            <td><strong>testModificarErrorBaseDeDatos</strong></td>
            <td><code>RF-03</code>, <code>ESC-03</code>, <code>CP-14</code></td>
            <td>Sentencia UPDATE arroja <code>PDOException</code></td>
            <td>Captura fallo y retorna: <em>"Error al modificar: ..."</em></td>
        </tr>
    </tbody>
</table>

<!-- ESC-04 CATEGORIA CATALOGO -->
<h3 class="scenario-header">ESC-04: Eliminación Física e Integridad Referencial (RF-04)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 24%;">Caso de Prueba / Método</th>
            <th style="width: 18%;">Grupos PHPUnit</th>
            <th style="width: 25%;">Condición / Simulación (Mocks)</th>
            <th style="width: 25%;">Resultado Esperado / Aserción</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-15</span></td>
            <td><strong>testEliminarCategoriaCatalogoExitoso</strong></td>
            <td><code>RF-04</code>, <code>ESC-04</code>, <code>CP-15</code></td>
            <td>Categoría existente y sin artículos en <code>catalogo</code></td>
            <td>Ejecuta DELETE y retorna: <em>"Categoría eliminada exitosamente."</em></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-16</span></td>
            <td><strong>testEliminarCategoriaNoExiste</strong></td>
            <td><code>RF-04</code>, <code>ESC-04</code>, <code>CP-16</code></td>
            <td>ID no existe en <code>categoria_catalogo</code></td>
            <td>Retorna error: <em>"La categoría no existe."</em></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-17</span></td>
            <td><strong>testEliminarCategoriaConArticulosAsociados</strong></td>
            <td><code>RF-04</code>, <code>ESC-04</code>, <code>CP-17</code></td>
            <td>Categoría vinculada con registros en tabla <code>catalogo</code></td>
            <td>Integridad protegida. Retorna: <em>"No se puede eliminar: la categoría tiene artículos..."</em></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-18</span></td>
            <td><strong>testEliminarErrorBaseDeDatos</strong></td>
            <td><code>RF-04</code>, <code>ESC-04</code>, <code>CP-18</code></td>
            <td>Sentencia DELETE arroja <code>PDOException</code></td>
            <td>Captura fallo y retorna: <em>"Hubo un error al eliminar la categoría."</em></td>
        </tr>
    </tbody>
</table>

<!-- ESC-05 CATEGORIA CATALOGO -->
<h3 class="scenario-header">ESC-05: Consulta General, Filtro Dinámico y Búsqueda por ID (RF-05)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 24%;">Caso de Prueba / Método</th>
            <th style="width: 18%;">Grupos PHPUnit</th>
            <th style="width: 25%;">Condición / Simulación (Mocks)</th>
            <th style="width: 25%;">Resultado Esperado / Aserción</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-19</span></td>
            <td><strong>testConsultarGeneralExitoso</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-19</code></td>
            <td>Consulta general <code>WHERE 1=1 ORDER BY id_categoria ASC</code></td>
            <td>Retorna listado completo de categorías ordenadas ascendentemente.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-20</span></td>
            <td><strong>testConsultarConFiltroTexto</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-20</code></td>
            <td><code>filtro => 'Cascos'</code></td>
            <td>Aplica cláusula <code>AND nombre LIKE :f1</code> con parámetro <code>%Cascos%</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-21</span></td>
            <td><strong>testConsultarConPropiedadNombreDefinida</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-21</code></td>
            <td>Propiedad interna <code>\$this->nombre</code> definida</td>
            <td>Aplica cláusula <code>AND nombre LIKE :nombre</code> en la sentencia SQL.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-22</span></td>
            <td><strong>testConsultarErrorBaseDeDatos</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-22</code></td>
            <td>Fallo de conexión o consulta general arroja <code>PDOException</code></td>
            <td>Captura error y retorna: <em>"Error al listar: ..."</em></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-23</span></td>
            <td><strong>testBuscarPorIdParametroDirecto</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-23</code></td>
            <td>Invocación directa de <code>Buscar(2)</code> por parámetro</td>
            <td>Retorna <code>['accion' => 'buscar', 'datos' => [...]]</code> en formato asociativo.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-24</span></td>
            <td><strong>testBuscarViaProcesarDatos</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-24</code></td>
            <td>Invocación a través de <code>ProcesarDatos(['accion' => 'buscar', 'id_categoria' => 3])</code></td>
            <td>Enruta internamente a <code>Buscar()</code> usando el atributo interno del modelo.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-25</span></td>
            <td><strong>testBuscarErrorBaseDeDatos</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-25</code></td>
            <td>Sentencia de búsqueda arroja <code>PDOException</code></td>
            <td>Captura fallo y retorna <code>['accion' => 'error', 'mensaje' => ...]</code>.</td>
        </tr>
    </tbody>
</table>

<!-- SALTO DE PAGINA -->
<div class="page-break"></div>

<!-- SECCION CATALOGO -->
<h2 class="section-header">6. Módulo de Catálogo (ModeloCatalogo)</h2>
<p style="font-size: 7.5pt; color: #475569; margin-top: -4px;">
    <strong>Archivo evaluado:</strong> <code>app/modelo/ModeloCatalogo.php</code> &bull; 
    <strong>Directorio de pruebas:</strong> <code>tests/Unit/Catalogo/</code> &bull; 
    <strong>Total pruebas:</strong> 29 &bull; <strong>Aserciones:</strong> 96 &bull; <strong>Cobertura:</strong> 100.00% (152/152 líneas)
</p>

<!-- ESC-01 CATALOGO -->
<h3 class="scenario-header">ESC-01: Validación de Acciones, Datos de Entrada y Expresiones Regulares (RF-01)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 24%;">Caso de Prueba / Método</th>
            <th style="width: 18%;">Grupos PHPUnit</th>
            <th style="width: 25%;">Condición / Entrada</th>
            <th style="width: 25%;">Resultado Esperado / Aserción</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-01</span></td>
            <td><strong>testDatosVaciosLanzaExcepcion</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-01</code></td>
            <td>Arreglo de entrada vacío: <code>[]</code></td>
            <td>Lanza <code>Exception</code>: <em>"No se proporcionaron datos para procesar."</em></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-02</span></td>
            <td><strong>testAccionInvalidaLanzaExcepcion</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-02</code></td>
            <td><code>accion => 'accion_no_valida'</code></td>
            <td>Lanza <code>Exception</code>: <em>"La accion no es valida"</em></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-03</span></td>
            <td><strong>testIdCatalogoInvalidoLanzaExcepcion</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-03</code></td>
            <td><code>id_catalogo => '10abc'</code></td>
            <td>Lanza <code>Exception</code>: <em>"Id inválido."</em></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-04</span></td>
            <td><strong>testNombreInvalidoLanzaExcepcion</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-04</code></td>
            <td><code>nombre => 'AB'</code> (menor a 3 caracteres)</td>
            <td>Lanza <code>Exception</code>: <em>"Nombre inválido."</em></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-05</span></td>
            <td><strong>testIdCategoriaInvalidoLanzaExcepcion</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-05</code></td>
            <td><code>id_categoria => 'cat-1'</code></td>
            <td>Lanza <code>Exception</code>: <em>"Categoría inválida."</em></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-06</span></td>
            <td><strong>testStockMinimoInvalidoLanzaExcepcion</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-06</code></td>
            <td><code>stock_minimo => 'cinco'</code></td>
            <td>Lanza <code>Exception</code>: <em>"Stock mínimo debe ser un número entero."</em></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-07</span></td>
            <td><strong>testTallaInvalidaLanzaExcepcion</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-07</code></td>
            <td><code>talla => 'Talla@Pro!'</code></td>
            <td>Lanza <code>Exception</code>: <em>"Talla inválida."</em></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-08</span></td>
            <td><strong>testCodigoPosicionInvalidoLanzaExcepcion</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-08</code></td>
            <td><code>codigo_posicion => 'portero_1'</code></td>
            <td>Lanza <code>Exception</code>: <em>"Posición inválida."</em></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-09</span></td>
            <td><strong>testNormalizacionNombreYTalla</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-09</code></td>
            <td>Espacios y minúsculas en nombre y talla</td>
            <td>Formatea nombre a Title Case y talla a Uppercase con trim.</td>
        </tr>
    </tbody>
</table>

<!-- ESC-02 CATALOGO -->
<h3 class="scenario-header">ESC-02: Registro de Artículos en Catálogo y Control Transaccional (RF-02)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 24%;">Caso de Prueba / Método</th>
            <th style="width: 18%;">Grupos PHPUnit</th>
            <th style="width: 25%;">Condición / Simulación (Mocks)</th>
            <th style="width: 25%;">Resultado Esperado / Aserción</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-10</span></td>
            <td><strong>testIncluirCatalogoExitoso</strong></td>
            <td><code>RF-02</code>, <code>ESC-02</code>, <code>CP-10</code></td>
            <td>Categoría existente en BD (<code>fetchColumn = 1</code>)</td>
            <td>Ejecuta INSERT, commit y retorna <code>['accion' => 'exito']</code> con <code>datos_nuevos</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-11</span></td>
            <td><strong>testIncluirCategoriaNoExiste</strong></td>
            <td><code>RF-02</code>, <code>ESC-02</code>, <code>CP-11</code></td>
            <td>Categoría no existe en BD (<code>fetchColumn = 0</code>)</td>
            <td>Rollback transaccional. Retorna: <em>"La categoría seleccionada no existe."</em></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-12</span></td>
            <td><strong>testIncluirErrorBaseDeDatosEnInsercion</strong></td>
            <td><code>RF-02</code>, <code>ESC-02</code>, <code>CP-12</code></td>
            <td>Sentencia INSERT arroja <code>PDOException</code></td>
            <td>Rollback transaccional. Captura fallo y retorna <code>['accion' => 'error']</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-13</span></td>
            <td><strong>testIncluirConCamposOpcionalesVacios</strong></td>
            <td><code>RF-02</code>, <code>ESC-02</code>, <code>CP-13</code></td>
            <td>Omisión de talla, posición y stock mínimo</td>
            <td>Asigna valores por defecto (stock = 0, talla = '', pos = null) y registra.</td>
        </tr>
    </tbody>
</table>

<!-- ESC-03 CATALOGO -->
<h3 class="scenario-header">ESC-03: Modificación y Actualización de Artículos de Catálogo (RF-03)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 24%;">Caso de Prueba / Método</th>
            <th style="width: 18%;">Grupos PHPUnit</th>
            <th style="width: 25%;">Condición / Simulación (Mocks)</th>
            <th style="width: 25%;">Resultado Esperado / Aserción</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-14</span></td>
            <td><strong>testModificarCatalogoExitoso</strong></td>
            <td><code>RF-03</code>, <code>ESC-03</code>, <code>CP-14</code></td>
            <td>ID de catálogo existe en BD (<code>fetchColumn = 1</code>)</td>
            <td>Ejecuta UPDATE, commit y retorna <code>['accion' => 'exito']</code> con datos nuevos.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-15</span></td>
            <td><strong>testModificarIdNoExiste</strong></td>
            <td><code>RF-03</code>, <code>ESC-03</code>, <code>CP-15</code></td>
            <td>ID de catálogo no existe en BD (<code>fetchColumn = 0</code>)</td>
            <td>Rollback transaccional. Retorna error con código <code>INVALID_ID</code> ("003").</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-16</span></td>
            <td><strong>testModificarErrorBaseDeDatosEnUpdate</strong></td>
            <td><code>RF-03</code>, <code>ESC-03</code>, <code>CP-16</code></td>
            <td>Sentencia UPDATE arroja <code>PDOException</code></td>
            <td>Rollback transaccional. Captura excepción y retorna código de error SQL.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-17</span></td>
            <td><strong>testModificarConCamposOpcionalesNull</strong></td>
            <td><code>RF-03</code>, <code>ESC-03</code>, <code>CP-17</code></td>
            <td>Actualización de posición o talla a valores vacíos</td>
            <td>Actualiza en BD asignando null y cadena vacía según corresponda.</td>
        </tr>
    </tbody>
</table>

<!-- ESC-04 CATALOGO -->
<h3 class="scenario-header">ESC-04: Eliminación Segura e Integridad Referencial (RF-04)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 24%;">Caso de Prueba / Método</th>
            <th style="width: 18%;">Grupos PHPUnit</th>
            <th style="width: 25%;">Condición / Simulación (Mocks)</th>
            <th style="width: 25%;">Resultado Esperado / Aserción</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-18</span></td>
            <td><strong>testEliminarCatalogoExitoso</strong></td>
            <td><code>RF-04</code>, <code>ESC-04</code>, <code>CP-18</code></td>
            <td>SP <code>EliminarCatalogoSeguro</code> retorna <code>@resultado = 1</code></td>
            <td>Eliminación exitosa confirmada. Retorna <code>['accion' => 'exito']</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-19</span></td>
            <td><strong>testEliminarRechazoPorArticulosInventarioAsociados</strong></td>
            <td><code>RF-04</code>, <code>ESC-04</code>, <code>CP-19</code></td>
            <td>SP retorna <code>@resultado = -2</code> (artículos en inventario)</td>
            <td>Integridad física protegida: <em>"No se puede eliminar... tiene artículos asociados."</em></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-20</span></td>
            <td><strong>testEliminarRechazoIdInvalidoOInexistente</strong></td>
            <td><code>RF-04</code>, <code>ESC-04</code>, <code>CP-20</code></td>
            <td>SP retorna <code>@resultado = 0</code></td>
            <td>Retorna error con código estándar <code>INVALID_ID</code> ("003").</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-21</span></td>
            <td><strong>testEliminarErrorBaseDeDatosEnSP</strong></td>
            <td><code>RF-04</code>, <code>ESC-04</code>, <code>CP-21</code></td>
            <td>Invocación del procedimiento arroja <code>PDOException</code></td>
            <td>Captura fallo y retorna <code>['accion' => 'error', 'codigo' => ...]</code>.</td>
        </tr>
    </tbody>
</table>

<!-- ESC-05 CATALOGO -->
<h3 class="scenario-header">ESC-05: Consulta con Subconsultas, Filtrado Dinámico y Reportes (RF-05)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 24%;">Caso de Prueba / Método</th>
            <th style="width: 18%;">Grupos PHPUnit</th>
            <th style="width: 25%;">Condición / Simulación (Mocks)</th>
            <th style="width: 25%;">Resultado Esperado / Aserción</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-22</span></td>
            <td><strong>testConsultarGeneralExitoso</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-22</code></td>
            <td>Consulta general con subconsultas de categoría, posición y stock</td>
            <td>Retorna listado con campos enriquecidos (<code>categoria_nombre</code>, <code>stock_actual</code>).</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-23</span></td>
            <td><strong>testConsultarConFiltroTexto</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-23</code></td>
            <td><code>filtro => 'Bauer'</code></td>
            <td>Aplica cláusula con parámetros <code>:f1</code>, <code>:f2</code> y <code>:f3</code> en cascada.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-24</span></td>
            <td><strong>testConsultarConFiltrosDeAtributos</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-24</code></td>
            <td>Atributos internos <code>\$this->id_categoria</code> y <code>\$this->talla</code></td>
            <td>Aplica filtros exactos <code>AND c.id_categoria = :id_categoria AND c.talla = :talla</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-25</span></td>
            <td><strong>testConsultarErrorBaseDeDatos</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-25</code></td>
            <td>Fallo de conexión en la consulta general</td>
            <td>Captura excepción y retorna <code>['accion' => 'error']</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-26</span></td>
            <td><strong>testBuscarPorIdParametroDirecto</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-26</code></td>
            <td>Invocación directa de <code>Buscar(5)</code></td>
            <td>Retorna registro asociativo: <code>['accion' => 'buscar', 'datos' => [...]]</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-27</span></td>
            <td><strong>testBuscarViaProcesarDatos</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-27</code></td>
            <td>Invocación mediante <code>ProcesarDatos(['accion' => 'buscar', 'id_catalogo' => 10])</code></td>
            <td>Enruta internamente a <code>Buscar()</code> utilizando el ID cargado en el modelo.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-28</span></td>
            <td><strong>testGenerarReporteViaProcesarDatos</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-28</code></td>
            <td>Invocación mediante <code>ProcesarDatos(['accion' => 'generar'])</code></td>
            <td>Enruta a <code>Consultar()</code> y retorna los datos para la generación del reporte.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-29</span></td>
            <td><strong>testBuscarErrorBaseDeDatos</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-29</code></td>
            <td>Fallo de consulta en <code>Buscar</code></td>
            <td>Captura fallo y retorna <code>['accion' => 'error', 'mensaje' => ...]</code>.</td>
        </tr>
    </tbody>
</table>

<!-- SALTO DE PAGINA -->
<div class="page-break"></div>

<!-- SECCION ARTICULOS INVENTARIO -->
<h2 class="section-header">7. Módulo de Artículos de Inventario (ModeloArticulosInventario)</h2>
<p style="font-size: 7.5pt; color: #475569; margin-top: -4px;">
    <strong>Archivo evaluado:</strong> <code>app/modelo/ModeloArticulosInventario.php</code> &bull; 
    <strong>Directorio de pruebas:</strong> <code>tests/Unit/ArticulosInventario/</code> &bull; 
    <strong>Total pruebas:</strong> 30 &bull; <strong>Aserciones:</strong> 123 &bull; <strong>Cobertura:</strong> 100.00% (181/181 líneas)
</p>

<!-- ESC-01 ARTICULOS INVENTARIO -->
<h3 class="scenario-header">ESC-01: Validación de Acciones, Carga de Combos y Correlativo Club (RF-01)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 24%;">Caso de Prueba / Método</th>
            <th style="width: 18%;">Grupos PHPUnit</th>
            <th style="width: 25%;">Condición / Entrada</th>
            <th style="width: 25%;">Resultado Esperado / Aserción</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-01</span></td>
            <td><strong>testDatosVaciosRetornaError</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-01</code></td>
            <td>Arreglo de entrada vacío: <code>[]</code></td>
            <td>Retorna error con código estándar <code>ERR_VACIO</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-02</span></td>
            <td><strong>testAccionInvalidaRetornaError</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-02</code></td>
            <td><code>accion => 'accion_no_permitida'</code></td>
            <td>Retorna error con código estándar <code>ERR_ACCION</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-03</span></td>
            <td><strong>testCargarCombosExitoso</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-03</code></td>
            <td>Invocación de acción <code>cargar_combos</code></td>
            <td>Retorna arreglos poblados de <code>catalogos</code> y <code>estados</code> físicos.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-04</span></td>
            <td><strong>testCargarCombosErrorBaseDeDatos</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-04</code></td>
            <td>Fallo de conexión o consulta en combos</td>
            <td>Captura excepción y retorna error con código <code>ERR_BD</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-05</span></td>
            <td><strong>testGenerarCodigoClubCorrelativo</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-05</code></td>
            <td>Cálculo de secuencia correlativa con prefijo <code>CL-</code></td>
            <td>Genera código formateado a 4 dígitos correlativos (ej. <code>CL-0025</code>).</td>
        </tr>
    </tbody>
</table>

<!-- ESC-02 ARTICULOS INVENTARIO -->
<h3 class="scenario-header">ESC-02: Registro Individual y en Lote de Artículos de Inventario (RF-02)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 24%;">Caso de Prueba / Método</th>
            <th style="width: 18%;">Grupos PHPUnit</th>
            <th style="width: 25%;">Condición / Simulación (Mocks)</th>
            <th style="width: 25%;">Resultado Esperado / Aserción</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-06</span></td>
            <td><strong>testIncluirArticuloIndividualExitoso</strong></td>
            <td><code>RF-02</code>, <code>ESC-02</code>, <code>CP-06</code></td>
            <td>Registro unitario con asignación de código club</td>
            <td>INSERT atómico con commit. Mensaje: <em>"Artículo registrado con el código CL-0001."</em></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-07</span></td>
            <td><strong>testIncluirArticulosLoteExitoso</strong></td>
            <td><code>RF-02</code>, <code>ESC-02</code>, <code>CP-07</code></td>
            <td>Registro en lote de 3 artículos</td>
            <td>Genera correlativos consecutivos (<code>CL-0005</code> al <code>CL-0007</code>) en una transacción.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-08</span></td>
            <td><strong>testIncluirArticulosArregloVacio</strong></td>
            <td><code>RF-02</code>, <code>ESC-02</code>, <code>CP-08</code></td>
            <td>Parámetro <code>articulos => []</code> vacío</td>
            <td>Rechazo inmediato sin tocar BD: retorna código <code>ERR_VACIO</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-09</span></td>
            <td><strong>testIncluirErrorBaseDeDatosEnInsercion</strong></td>
            <td><code>RF-02</code>, <code>ESC-02</code>, <code>CP-09</code></td>
            <td>Sentencia INSERT arroja <code>PDOException</code></td>
            <td>Rollback transaccional estricto y retorno de código <code>ERR_BD</code>.</td>
        </tr>
    </tbody>
</table>

<!-- ESC-03 ARTICULOS INVENTARIO -->
<h3 class="scenario-header">ESC-03: Modificación y Cambio de Estatus de Artículos (RF-03)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 24%;">Caso de Prueba / Método</th>
            <th style="width: 18%;">Grupos PHPUnit</th>
            <th style="width: 25%;">Condición / Simulación (Mocks)</th>
            <th style="width: 25%;">Resultado Esperado / Aserción</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-10</span></td>
            <td><strong>testModificarArticuloExitoso</strong></td>
            <td><code>RF-03</code>, <code>ESC-03</code>, <code>CP-10</code></td>
            <td>Actualización de estado físico y catálogo</td>
            <td>UPDATE con commit: <em>"Artículo actualizado correctamente."</em></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-11</span></td>
            <td><strong>testModificarArticuloVerificacionDatosNuevos</strong></td>
            <td><code>RF-03</code>, <code>ESC-03</code>, <code>CP-11</code></td>
            <td>Comprobación del payload JSON para bitácora</td>
            <td>Verifica consistencia exacta de <code>codigo_articulo</code>, <code>id_catalogo</code> e <code>id_estado</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-12</span></td>
            <td><strong>testModificarArticuloErrorBaseDeDatos</strong></td>
            <td><code>RF-03</code>, <code>ESC-03</code>, <code>CP-12</code></td>
            <td>Sentencia UPDATE arroja <code>PDOException</code></td>
            <td>Rollback transaccional y retorno de código de error <code>ERR_BD</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-13</span></td>
            <td><strong>testCambiarEstatusExitoso</strong></td>
            <td><code>RF-03</code>, <code>ESC-03</code>, <code>CP-13</code></td>
            <td>Invocación directa de <code>CambiarEstatus(15, 2)</code></td>
            <td>Ejecuta sentencia UPDATE de estatus y retorna <code>true</code>.</td>
        </tr>
    </tbody>
</table>

<!-- ESC-04 ARTICULOS INVENTARIO -->
<h3 class="scenario-header">ESC-04: Retiro Seguro e Reincorporación al Inventario (RF-04)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 24%;">Caso de Prueba / Método</th>
            <th style="width: 18%;">Grupos PHPUnit</th>
            <th style="width: 25%;">Condición / Simulación (Mocks)</th>
            <th style="width: 25%;">Resultado Esperado / Aserción</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-14</span></td>
            <td><strong>testEliminarArticuloExitoso</strong></td>
            <td><code>RF-04</code>, <code>ESC-04</code>, <code>CP-14</code></td>
            <td>SP <code>RetirarArticuloSeguro</code> retorna <code>@resultado = 1</code></td>
            <td>Retiro exitoso confirmado: <em>"El artículo ha sido retirado del inventario."</em></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-15</span></td>
            <td><strong>testEliminarArticuloEnUsoRechazado</strong></td>
            <td><code>RF-04</code>, <code>ESC-04</code>, <code>CP-15</code></td>
            <td>SP retorna <code>@resultado = -2</code> (artículo en uso)</td>
            <td>Rechazo protegido: <em>"El artículo no puede ser retirado porque está en uso..."</em></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-16</span></td>
            <td><strong>testEliminarArticuloCodigoFalloSP</strong></td>
            <td><code>RF-04</code>, <code>ESC-04</code>, <code>CP-16</code></td>
            <td>SP retorna <code>@resultado = 0</code></td>
            <td>Retorna error con código estándar <code>ERR_BD</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-17</span></td>
            <td><strong>testEliminarArticuloIdVacio</strong></td>
            <td><code>RF-04</code>, <code>ESC-04</code>, <code>CP-17</code></td>
            <td>Omisión del ID del artículo a retirar</td>
            <td>Rechazo inmediato sin llamada a BD: retorna <code>ERR_VACIO</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-18</span></td>
            <td><strong>testEliminarArticuloErrorPDOException</strong></td>
            <td><code>RF-04</code>, <code>ESC-04</code>, <code>CP-18</code></td>
            <td>Invocación del SP arroja <code>PDOException</code></td>
            <td>Captura fallo de conexión y retorna código <code>ERR_BD</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-19</span></td>
            <td><strong>testReincorporarArticuloExitoso</strong></td>
            <td><code>RF-04</code>, <code>ESC-04</code>, <code>CP-19</code></td>
            <td>Artículo retirado (<code>estatus = 3</code>)</td>
            <td>UPDATE a <code>estatus = 1</code> con commit: <em>"Artículo reincorporado como Disponible."</em></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-20</span></td>
            <td><strong>testReincorporarArticuloIdVacio</strong></td>
            <td><code>RF-04</code>, <code>ESC-04</code>, <code>CP-20</code></td>
            <td>ID vacío en reincorporación</td>
            <td>Rechazo inmediato retornando código <code>ERR_VACIO</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-21</span></td>
            <td><strong>testReincorporarArticuloErrorPDOException</strong></td>
            <td><code>RF-04</code>, <code>ESC-04</code>, <code>CP-21</code></td>
            <td>Fallo en UPDATE de reincorporación</td>
            <td>Rollback transaccional y retorno de código <code>ERR_BD</code>.</td>
        </tr>
    </tbody>
</table>

<!-- ESC-05 ARTICULOS INVENTARIO -->
<h3 class="scenario-header">ESC-05: Consulta Agrupada, Filtros Dinámicos y Artículos Libres (RF-05)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 24%;">Caso de Prueba / Método</th>
            <th style="width: 18%;">Grupos PHPUnit</th>
            <th style="width: 25%;">Condición / Simulación (Mocks)</th>
            <th style="width: 25%;">Resultado Esperado / Aserción</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-22</span></td>
            <td><strong>testConsultarAgrupadoSinFiltrosExitoso</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-22</code></td>
            <td>Consulta general agrupada por catálogo</td>
            <td>Agrupa las piezas de inventario bajo cada artículo de catálogo correspondiente.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-23</span></td>
            <td><strong>testConsultarConFiltroTexto</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-23</code></td>
            <td><code>filtro => 'Casco'</code></td>
            <td>Aplica cláusula dinámica <code>(c.nombre LIKE :filtro1 OR es.nombre LIKE :filtro2)</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-24</span></td>
            <td><strong>testConsultarConFiltroCatalogo</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-24</code></td>
            <td><code>id_catalogo => 5</code></td>
            <td>Aplica cláusula exacta <code>AND c.id_catalogo = :id_catalogo</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-25</span></td>
            <td><strong>testConsultarConFiltroEstado</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-25</code></td>
            <td><code>id_estado => 2</code></td>
            <td>Aplica cláusula exacta <code>AND e.id_estado = :id_estado</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-26</span></td>
            <td><strong>testConsultarAgrupadoErrorBaseDeDatos</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-26</code></td>
            <td>Fallo de conexión en consulta agrupada</td>
            <td>Captura excepción y retorna código <code>ERR_BD</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-27</span></td>
            <td><strong>testBuscarArticuloPorIdExitoso</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-27</code></td>
            <td>Búsqueda individual mediante <code>ProcesarDatos</code> y <code>Buscar()</code></td>
            <td>Retorna registro asociativo del artículo con su código club (<code>CL-0015</code>).</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-28</span></td>
            <td><strong>testBuscarArticuloErrorBaseDeDatos</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-28</code></td>
            <td>Fallo en la consulta de búsqueda individual</td>
            <td>Captura fallo y retorna <code>['accion' => 'error', 'mensaje' => ...]</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-29</span></td>
            <td><strong>testConsultarArticulosLibresExitoso</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-29</code></td>
            <td>Invocación directa de <code>ConsultarArticulosLibres()</code></td>
            <td>Retorna listado de piezas físicas con <code>estatus = 1</code> disponibles para préstamo.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-30</span></td>
            <td><strong>testConsultarArticulosLibresErrorBaseDeDatos</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-30</code></td>
            <td>Fallo en consulta de artículos libres</td>
            <td>Captura error y retorna <code>['accion' => 'error', 'datos' => []]</code>.</td>
        </tr>
    </tbody>
</table>

<!-- SALTO DE PAGINA -->
<div class="page-break"></div>

<!-- SECCION TORNEOS -->
<h2 class="section-header">8. Módulo de Torneos (ModeloTorneos)</h2>
<p style="font-size: 7.5pt; color: #475569; margin-top: -4px;">
    <strong>Archivo evaluado:</strong> <code>app/modelo/ModeloTorneos.php</code> &bull; 
    <strong>Directorio de pruebas:</strong> <code>tests/Unit/Torneos/</code> &bull; 
    <strong>Total pruebas:</strong> 35 &bull; <strong>Aserciones:</strong> 83 &bull; <strong>Cobertura:</strong> 100.00%
</p>

<!-- ESC-01 -->
<h3 class="scenario-header">ESC-01: Validación de Datos de Entrada, Reglas de Formato y Cronología Temporal (RF-01)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 24%;">Caso de Prueba / Método</th>
            <th style="width: 18%;">Grupos PHPUnit</th>
            <th style="width: 25%;">Condición / Entrada</th>
            <th style="width: 25%;">Resultado Esperado / Aserción</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-01</span></td>
            <td><strong>testDatosVaciosLanzaExcepcion</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-01</code></td>
            <td>Arreglo vacío <code>[]</code> enviado a <code>ProcesarDatos</code></td>
            <td>Lanza <code>Exception</code>: "No se proporcionaron datos para procesar."</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-02</span></td>
            <td><strong>testAccionInvalidaLanzaExcepcion</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-02</code></td>
            <td><code>accion => 'accion_inexistente'</code></td>
            <td>Lanza <code>Exception</code>: "La acción no es válida".</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-03</span></td>
            <td><strong>testCodigoTorneoInvalidoLanzaExcepcion</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-03</code></td>
            <td><code>codigo_torneo => 'abc#1'</code> (no numérico)</td>
            <td>Lanza <code>Exception</code>: "Código de torneo inválido."</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-04</span></td>
            <td><strong>testNombreInvalidoPorCaracteresLanzaExcepcion</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-04</code></td>
            <td><code>nombre => 'Torneo!@#'</code> (símbolos ilegales)</td>
            <td>Lanza <code>Exception</code>: "Nombre de torneo inválido."</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-05</span></td>
            <td><strong>testNombreInvalidoPorLongitudLanzaExcepcion</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-05</code></td>
            <td><code>nombre => 'T'</code> (longitud < 2 caracteres)</td>
            <td>Lanza <code>Exception</code>: "Nombre de torneo inválido."</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-06</span></td>
            <td><strong>testFechaInicioInvalidaLanzaExcepcion</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-06</code></td>
            <td><code>fecha_inicio => '01-05-2026'</code> (formato no YYYY-MM-DD)</td>
            <td>Lanza <code>Exception</code>: "Fecha de inicio inválida."</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-07</span></td>
            <td><strong>testFechaFinInvalidaLanzaExcepcion</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-07</code></td>
            <td><code>fecha_fin => '2026/12/31'</code> (separadores incorrectos)</td>
            <td>Lanza <code>Exception</code>: "Fecha de fin inválida."</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-08</span></td>
            <td><strong>testUbicacionInvalidaLanzaExcepcion</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-08</code></td>
            <td><code>ubicacion => 'Gym'</code> (longitud < 5 caracteres)</td>
            <td>Lanza <code>Exception</code>: "Ubicación inválida."</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-09</span></td>
            <td><strong>testEstatusInvalidoLanzaExcepcion</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-09</code></td>
            <td><code>estatus => '5'</code> (fuera del rango 1 a 3)</td>
            <td>Lanza <code>Exception</code>: "Estatus inválido."</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-10</span></td>
            <td><strong>testFechaInicioMayorQueFechaFinLanzaExcepcion</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-10</code></td>
            <td><code>fecha_inicio => 2026-06-15</code>, <code>fecha_fin => 2026-06-10</code></td>
            <td>Lanza <code>Exception</code>: "La fecha de inicio no puede ser mayor que la fecha de fin."</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-11</span></td>
            <td><strong>testEstatusPorDisputarseRequiereFechaFutura</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-11</code></td>
            <td><code>estatus => 1</code> ("Por Disputarse") con fecha inicio hoy o pasada</td>
            <td>Lanza <code>Exception</code>: "Un torneo 'Por Disputarse' debe tener un rango de fecha futura."</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-12</span></td>
            <td><strong>testEstatusEnCursoRequiereFechaTranscurriendo</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-12</code></td>
            <td><code>estatus => 2</code> ("En Curso") con fecha inicio en el futuro</td>
            <td>Lanza <code>Exception</code>: "Un torneo 'En Curso' debe tener un rango de fecha que esté transcurriendo."</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-13</span></td>
            <td><strong>testEstatusFinalizadoRequiereFechaPasada</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-13</code></td>
            <td><code>estatus => 3</code> ("Finalizado") con fecha fin hoy o en el futuro</td>
            <td>Lanza <code>Exception</code>: "Un torneo 'Finalizado' debe tener un rango de fecha que ya pasó."</td>
        </tr>
    </tbody>
</table>

<!-- ESC-02 -->
<h3 class="scenario-header">ESC-02: Registro e Inclusión de Nuevos Torneos (RF-02)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 24%;">Caso de Prueba / Método</th>
            <th style="width: 18%;">Grupos PHPUnit</th>
            <th style="width: 25%;">Condición / Simulación (Mocks)</th>
            <th style="width: 25%;">Resultado Esperado / Aserción</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-14</span></td>
            <td><strong>testIncluirTorneoExitoso</strong></td>
            <td><code>RF-02</code>, <code>ESC-02</code>, <code>CP-14</code></td>
            <td>Datos válidos, <code>verificarExistencia = 0</code> (nombre disponible)</td>
            <td>Ejecuta INSERT y retorna <code>['accion' => 'exito']</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-15</span></td>
            <td><strong>testIncluirNombreDuplicadoRechazado</strong></td>
            <td><code>RF-02</code>, <code>ESC-02</code>, <code>CP-15</code></td>
            <td><code>verificarExistencia = 1</code> (nombre ya registrado)</td>
            <td>Retorna <code>['accion' => 'error', 'codigo' => 'Ya existe un torneo registrado con este nombre.']</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-16</span></td>
            <td><strong>testIncluirTorneoFalloBaseDeDatos</strong></td>
            <td><code>RF-02</code>, <code>ESC-02</code>, <code>CP-16</code></td>
            <td>Simulación de <code>PDOException</code> durante la inserción</td>
            <td>Captura excepción y retorna <code>['accion' => 'error', 'codigo' => ...]</code>.</td>
        </tr>
    </tbody>
</table>

<!-- ESC-03 -->
<h3 class="scenario-header">ESC-03: Modificación y Actualización de Torneos (RF-03)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 24%;">Caso de Prueba / Método</th>
            <th style="width: 18%;">Grupos PHPUnit</th>
            <th style="width: 25%;">Condición / Simulación (Mocks)</th>
            <th style="width: 25%;">Resultado Esperado / Aserción</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-17</span></td>
            <td><strong>testModificarTorneoConNuevoNombreExitoso</strong></td>
            <td><code>RF-03</code>, <code>ESC-03</code>, <code>CP-17</code></td>
            <td>Nombre cambiado, <code>verificarExistenciaPropia = 0</code>, <code>verificarExistencia = 0</code></td>
            <td>Ejecuta UPDATE con éxito y retorna <code>['accion' => 'exito']</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-18</span></td>
            <td><strong>testModificarTorneoConservandoMismoNombreExitoso</strong></td>
            <td><code>RF-03</code>, <code>ESC-03</code>, <code>CP-18</code></td>
            <td>Mismo torneo conservando su nombre (<code>verificarExistenciaPropia = 1</code>)</td>
            <td>Omite verificación externa, ejecuta UPDATE y retorna <code>['accion' => 'exito']</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-19</span></td>
            <td><strong>testModificarTorneoNombreDuplicadoEnOtroTorneoRechazado</strong></td>
            <td><code>RF-03</code>, <code>ESC-03</code>, <code>CP-19</code></td>
            <td><code>verificarExistenciaPropia = 0</code>, <code>verificarExistencia = 1</code></td>
            <td>Rechaza colisión y retorna <code>['accion' => 'error', 'codigo' => 'Ya existe otro torneo registrado con este nombre.']</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-20</span></td>
            <td><strong>testModificarTorneoFalloBaseDeDatos</strong></td>
            <td><code>RF-03</code>, <code>ESC-03</code>, <code>CP-20</code></td>
            <td>Simulación de <code>PDOException</code> durante UPDATE</td>
            <td>Captura error y retorna <code>['accion' => 'error', 'codigo' => ...]</code>.</td>
        </tr>
    </tbody>
</table>

<!-- ESC-04 -->
<h3 class="scenario-header">ESC-04: Eliminación Segura y Control de Integridad Referencial (RF-04)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 24%;">Caso de Prueba / Método</th>
            <th style="width: 18%;">Grupos PHPUnit</th>
            <th style="width: 25%;">Condición / Simulación (Mocks)</th>
            <th style="width: 25%;">Resultado Esperado / Aserción</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-21</span></td>
            <td><strong>testEliminarTorneoExitoso</strong></td>
            <td><code>RF-04</code>, <code>ESC-04</code>, <code>CP-21</code></td>
            <td>Torneo existe en BD (<code>1</code>), sin equipos ni atletas asociados (<code>0</code>)</td>
            <td>Ejecuta DELETE y retorna <code>['accion' => 'exito']</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-22</span></td>
            <td><strong>testEliminarTorneoInexistenteRechazado</strong></td>
            <td><code>RF-04</code>, <code>ESC-04</code>, <code>CP-22</code></td>
            <td>Torneo no encontrado en tabla <code>torneos</code> (<code>0</code>)</td>
            <td>Retorna <code>['accion' => 'error', 'codigo' => 'El torneo no existe.']</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-23</span></td>
            <td><strong>testEliminarTorneoConEquiposAsociadosRechazado</strong></td>
            <td><code>RF-04</code>, <code>ESC-04</code>, <code>CP-23</code></td>
            <td>Torneo con registros vinculados en tabla <code>equipos</code> (<code>1</code>)</td>
            <td>Retorna <code>['accion' => 'error', 'codigo' => 'No se puede eliminar: el torneo tiene equipos o atletas asociados.']</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-24</span></td>
            <td><strong>testEliminarTorneoFalloBaseDeDatos</strong></td>
            <td><code>RF-04</code>, <code>ESC-04</code>, <code>CP-24</code></td>
            <td>Fallo simulado por <code>PDOException</code> durante DELETE</td>
            <td>Captura excepción y retorna <code>['accion' => 'error', 'codigo' => ...]</code>.</td>
        </tr>
    </tbody>
</table>

<!-- ESC-05 -->
<h3 class="scenario-header">ESC-05: Consulta, Búsqueda Individual, Reporte y Torneos Próximos (RF-05)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 24%;">Caso de Prueba / Método</th>
            <th style="width: 18%;">Grupos PHPUnit</th>
            <th style="width: 25%;">Condición / Simulación (Mocks)</th>
            <th style="width: 25%;">Resultado Esperado / Aserción</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-25</span></td>
            <td><strong>testConsultarTorneosSinFiltros</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-25</code></td>
            <td>Consulta general sin parámetros de filtrado</td>
            <td>Retorna <code>['accion' => 'consultar', 'datos' => [...]]</code> ordenado por <code>codigo_torneo ASC</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-26</span></td>
            <td><strong>testConsultarTorneosConFiltroTexto</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-26</code></td>
            <td><code>filtro => 'Nacional'</code></td>
            <td>Aplica condición dinámica <code>AND nombre LIKE :f1</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-27</span></td>
            <td><strong>testConsultarTorneosConFiltrosEstatusYFechas</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-27</code></td>
            <td>Filtros combinados de <code>estatus</code>, <code>fecha_inicio</code> y <code>fecha_fin</code></td>
            <td>Aplica cláusulas paramétricas para rango de fechas y estatus exacto.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-28</span></td>
            <td><strong>testConsultarTorneosConFiltroNombrePropio</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-28</code></td>
            <td>Invocación vía <code>ProcesarDatos</code> con clave <code>nombre</code></td>
            <td>Configura atributo interno y enlaza <code>AND nombre LIKE :nombre</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-29</span></td>
            <td><strong>testConsultarTorneosFalloBaseDeDatos</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-29</code></td>
            <td>Fallo de conexión o prepare en <code>Consultar()</code></td>
            <td>Captura error y retorna <code>['accion' => 'error']</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-30</span></td>
            <td><strong>testBuscarTorneoPorIdDirecto</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-30</code></td>
            <td>Llamada directa <code>Buscar(5)</code> con parámetro de ID</td>
            <td>Retorna <code>['accion' => 'buscar', 'datos' => [...]]</code> con el torneo solicitado.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-31</span></td>
            <td><strong>testBuscarTorneoEnrutadoPorProcesarDatos</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-31</code></td>
            <td>Llamada a <code>ProcesarDatos(['accion' => 'buscar', 'codigo_torneo' => 8])</code></td>
            <td>Enruta a <code>Buscar()</code> utilizando <code>codigo_torneo</code> interno.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-32</span></td>
            <td><strong>testGenerarReporteEnrutadoPorProcesarDatos</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-32</code></td>
            <td>Llamada a <code>ProcesarDatos(['accion' => 'generar'])</code></td>
            <td>Enruta a <code>Consultar()</code> y retorna conjunto de datos para reporte.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-33</span></td>
            <td><strong>testBuscarTorneoFalloBaseDeDatos</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-33</code></td>
            <td>Excepción en base de datos durante <code>Buscar()</code></td>
            <td>Retorna <code>['accion' => 'error', 'mensaje' => ...]</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-34</span></td>
            <td><strong>testConsultarProximosTorneosExitoso</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-34</code></td>
            <td>Llamada directa a <code>ConsultarProximos()</code></td>
            <td>Retorna torneos en estatus 1 con fecha en 1 o 2 días (<code>CURDATE() + INTERVAL</code>).</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-35</span></td>
            <td><strong>testConsultarProximosTorneosFalloBaseDeDatos</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-35</code></td>
            <td>Fallo simulado por <code>PDOException</code> en <code>ConsultarProximos()</code></td>
            <td>Captura la excepción y retorna un arreglo vacío <code>[]</code> de forma segura.</td>
        </tr>
    </tbody>
</table>

<!-- SALTO DE PAGINA -->
<div class="page-break"></div>

<!-- SECCION EQUIPOS -->
<h2 class="section-header">9. Módulo de Equipos (ModeloEquipos)</h2>
<p style="font-size: 7.5pt; color: #475569; margin-top: -4px;">
    <strong>Archivo evaluado:</strong> <code>app/modelo/ModeloEquipos.php</code> &bull; 
    <strong>Directorio de pruebas:</strong> <code>tests/Unit/Equipos/</code> &bull; 
    <strong>Total pruebas:</strong> 39 &bull; <strong>Aserciones:</strong> 99 &bull; <strong>Cobertura:</strong> 100.00%
</p>

<!-- ESC-01 -->
<h3 class="scenario-header">ESC-01: Validación de Datos de Entrada, Reglas de Formato y Estructura (RF-01)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 24%;">Caso de Prueba / Método</th>
            <th style="width: 18%;">Grupos PHPUnit</th>
            <th style="width: 25%;">Condición / Entrada</th>
            <th style="width: 25%;">Resultado Esperado / Aserción</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-01</span></td>
            <td><strong>testDatosVaciosLanzaExcepcion</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-01</code></td>
            <td>Arreglo vacío <code>[]</code> enviado a <code>ProcesarDatos</code></td>
            <td>Lanza <code>Exception</code>: "No se proporcionaron datos para procesar el registro del equipo."</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-02</span></td>
            <td><strong>testAccionInvalidaLanzaExcepcion</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-02</code></td>
            <td><code>accion => 'accion_no_existente'</code></td>
            <td>Lanza <code>Exception</code>: "La acción solicitada para el equipo no es válida."</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-03</span></td>
            <td><strong>testIdInvalidoNoEnteroLanzaExcepcion</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-03</code></td>
            <td><code>id => 'abc#12'</code> (no entero)</td>
            <td>Lanza <code>Exception</code>: "Id inválido."</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-04</span></td>
            <td><strong>testNombreInvalidoPorCaracteresEspeciales</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-04</code></td>
            <td><code>nombre => 'Cannibals@#\$Equipo'</code> (caracteres no permitidos)</td>
            <td>Lanza <code>Exception</code>: "Nombre inválido. Solo se permiten letras, números, espacios y guiones."</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-05</span></td>
            <td><strong>testNombreInvalidoPorLongitudCorta</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-05</code></td>
            <td><code>nombre => 'CA'</code> (longitud < 3)</td>
            <td>Lanza <code>Exception</code>: "Nombre inválido. Solo se permiten letras, números, espacios y guiones."</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-06</span></td>
            <td><strong>testNombreInvalidoPorLongitudExcesiva</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-06</code></td>
            <td><code>nombre</code> con más de 30 caracteres</td>
            <td>Lanza <code>Exception</code>: "Nombre inválido. Solo se permiten letras, números, espacios y guiones."</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-07</span></td>
            <td><strong>testNombreValidoConGuionesYNumerosAceptado</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-07</code></td>
            <td><code>nombre => 'cannibals sub-20'</code></td>
            <td>Normalización en Title Case y ejecución exitosa sin excepción.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-08</span></td>
            <td><strong>testGuardarDetallesEquipoIdInvalidoLanzaExcepcion</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-08</code></td>
            <td>Invocación directa con <code>idEquipo = 0</code></td>
            <td>Lanza <code>Exception</code>: "ID de equipo inválido."</td>
        </tr>
    </tbody>
</table>

<!-- ESC-02 -->
<h3 class="scenario-header">ESC-02: Registro de Equipos, Unicidad y Validación de Categorías de Atletas (RF-02)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 24%;">Caso de Prueba / Método</th>
            <th style="width: 18%;">Grupos PHPUnit</th>
            <th style="width: 25%;">Condición / Simulación (Mocks)</th>
            <th style="width: 25%;">Resultado Esperado / Aserción</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-09</span></td>
            <td><strong>testIncluirEquipoSinAtletasExitoso</strong></td>
            <td><code>RF-02</code>, <code>ESC-02</code>, <code>CP-09</code></td>
            <td>Equipo sin atletas (<code>atletas => []</code>), nombre disponible</td>
            <td>Inserta equipo y retorna <code>['accion' => 'incluir', 'mensaje' => 'Equipo registrado correctamente.']</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-10</span></td>
            <td><strong>testIncluirEquipoConAtletasMismaCategoriaExitoso</strong></td>
            <td><code>RF-02</code>, <code>ESC-02</code>, <code>CP-10</code></td>
            <td>Atletas pertenecientes a 1 sola categoría (<code>count = 1</code>)</td>
            <td>Inserta equipo y registros en pivote <code>detalles_equipos</code> con éxito.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-11</span></td>
            <td><strong>testIncluirEquipoConAtletasCategoriasContiguasExitoso</strong></td>
            <td><code>RF-02</code>, <code>ESC-02</code>, <code>CP-11</code></td>
            <td>Atletas de 2 categorías contiguas en el ranking (diferencia = 1)</td>
            <td>Valida regla de contigüidad, inserta detalles y hace commit de la transacción.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-12</span></td>
            <td><strong>testIncluirEquipoNombreDuplicadoRechazado</strong></td>
            <td><code>RF-02</code>, <code>ESC-02</code>, <code>CP-12</code></td>
            <td>Nombre ya registrado en tabla <code>equipos</code> (<code>COUNT = 1</code>)</td>
            <td>Retorna <code>['accion' => 'error', 'mensaje' => DUPLICATE_NAME]</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-13</span></td>
            <td><strong>testIncluirEquipoAtletasSinInscripcionesActivasRechazado</strong></td>
            <td><code>RF-02</code>, <code>ESC-02</code>, <code>CP-13</code></td>
            <td>Consulta de categorías de atletas retorna vacío (sin inscripciones)</td>
            <td>Retorna error: "No se pudo determinar la categoría de los atletas...".</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-14</span></td>
            <td><strong>testIncluirEquipoAtletasMasDeDosCategoriasRechazado</strong></td>
            <td><code>RF-02</code>, <code>ESC-02</code>, <code>CP-14</code></td>
            <td>Atletas asignados abarcan 3 categorías distintas (<code>count > 2</code>)</td>
            <td>Retorna error: "Un equipo no puede tener atletas de más de 2 categorías distintas.".</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-15</span></td>
            <td><strong>testIncluirEquipoAtletasCategoriaInexistenteEnRankingRechazado</strong></td>
            <td><code>RF-02</code>, <code>ESC-02</code>, <code>CP-15</code></td>
            <td>Una de las categorías de los atletas no figura en el ranking</td>
            <td>Retorna error: "Una de las categorías de los atletas no existe en el sistema.".</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-16</span></td>
            <td><strong>testIncluirEquipoAtletasCategoriasNoContiguasRechazado</strong></td>
            <td><code>RF-02</code>, <code>ESC-02</code>, <code>CP-16</code></td>
            <td>2 categorías con distancia en ranking > 1 (no inmediatas)</td>
            <td>Retorna error: "El equipo solo puede conformarse por atletas de la misma categoría o de una categoría inferior inmediata.".</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-17</span></td>
            <td><strong>testIncluirEquipoFalloBaseDeDatosRollback</strong></td>
            <td><code>RF-02</code>, <code>ESC-02</code>, <code>CP-17</code></td>
            <td>Excepción <code>PDOException</code> durante la inserción</td>
            <td>Ejecuta rollback de la transacción y retorna <code>['accion' => 'error']</code>.</td>
        </tr>
    </tbody>
</table>

<!-- ESC-03 -->
<h3 class="scenario-header">ESC-03: Modificación de Equipos, Preservación de Nombre y Reasignación de Atletas (RF-03)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 24%;">Caso de Prueba / Método</th>
            <th style="width: 18%;">Grupos PHPUnit</th>
            <th style="width: 25%;">Condición / Simulación (Mocks)</th>
            <th style="width: 25%;">Resultado Esperado / Aserción</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-18</span></td>
            <td><strong>testModificarEquipoConNuevoNombreYAtletasExitoso</strong></td>
            <td><code>RF-03</code>, <code>ESC-03</code>, <code>CP-18</code></td>
            <td>Nombre cambiado sin colisión y reasignación de atletas válidos</td>
            <td>Ejecuta UPDATE, actualiza pivote de atletas y retorna <code>['accion' => 'modificar']</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-19</span></td>
            <td><strong>testModificarEquipoConservandoMismoNombreExitoso</strong></td>
            <td><code>RF-03</code>, <code>ESC-03</code>, <code>CP-19</code></td>
            <td>Conserva su propio nombre (<code>verificarExistenciaPropia = 1</code>)</td>
            <td>Omite chequeo externo, actualiza datos y retorna <code>['accion' => 'modificar']</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-20</span></td>
            <td><strong>testModificarEquipoNombreDuplicadoEnOtroEquipoRechazado</strong></td>
            <td><code>RF-03</code>, <code>ESC-03</code>, <code>CP-20</code></td>
            <td>Nuevo nombre ya registrado en otro equipo (<code>COUNT = 1</code>)</td>
            <td>Rechaza modificación y retorna <code>['accion' => 'error', 'mensaje' => DUPLICATE_NAME]</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-21</span></td>
            <td><strong>testModificarEquipoFalloReglaCategoriasAtletasRechazado</strong></td>
            <td><code>RF-03</code>, <code>ESC-03</code>, <code>CP-21</code></td>
            <td>Nuevos atletas violan la regla de contigüidad de categorías</td>
            <td>Cancela actualización, ejecuta rollback y retorna mensaje de restricción.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-22</span></td>
            <td><strong>testModificarEquipoFalloBaseDeDatosRollback</strong></td>
            <td><code>RF-03</code>, <code>ESC-03</code>, <code>CP-22</code></td>
            <td>Fallo simulado por <code>PDOException</code> durante UPDATE</td>
            <td>Ejecuta rollback y retorna <code>['accion' => 'error', 'mensaje' => ...]</code>.</td>
        </tr>
    </tbody>
</table>

<!-- ESC-04 -->
<h3 class="scenario-header">ESC-04: Eliminación Segura, Cascada de Detalles e Integridad Referencial (RF-04)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 24%;">Caso de Prueba / Método</th>
            <th style="width: 18%;">Grupos PHPUnit</th>
            <th style="width: 25%;">Condición / Simulación (Mocks)</th>
            <th style="width: 25%;">Resultado Esperado / Aserción</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-23</span></td>
            <td><strong>testEliminarEquipoSinParticipacionesExitoso</strong></td>
            <td><code>RF-04</code>, <code>ESC-04</code>, <code>CP-23</code></td>
            <td>Equipo existe (<code>1</code>) y no tiene participaciones en torneos (<code>0</code>)</td>
            <td>Elimina detalles y equipo en cascada atómica y retorna <code>['accion' => 'eliminar']</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-24</span></td>
            <td><strong>testEliminarEquipoInexistenteRechazado</strong></td>
            <td><code>RF-04</code>, <code>ESC-04</code>, <code>CP-24</code></td>
            <td>Equipo no encontrado en base de datos (<code>0</code>)</td>
            <td>Rechaza con <code>INVALID_ID</code> y revierte la transacción.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-25</span></td>
            <td><strong>testEliminarEquipoConParticipacionesAsociadasRechazado</strong></td>
            <td><code>RF-04</code>, <code>ESC-04</code>, <code>CP-25</code></td>
            <td>Equipo vinculado con registros en tabla <code>participaciones</code> (<code>1</code>)</td>
            <td>Bloquea eliminación por integridad referencial y retorna <code>ASSOCIATES</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-26</span></td>
            <td><strong>testEliminarEquipoFalloBaseDeDatosRollback</strong></td>
            <td><code>RF-04</code>, <code>ESC-04</code>, <code>CP-26</code></td>
            <td>Excepción en base de datos durante la sentencia DELETE</td>
            <td>Revierte la transacción mediante rollback y retorna error estructurado.</td>
        </tr>
    </tbody>
</table>

<!-- ESC-05 -->
<h3 class="scenario-header">ESC-05: Consultas Generales, Búsquedas y Gestión de Atletas de Equipos (RF-05)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 24%;">Caso de Prueba / Método</th>
            <th style="width: 18%;">Grupos PHPUnit</th>
            <th style="width: 25%;">Condición / Simulación (Mocks)</th>
            <th style="width: 25%;">Resultado Esperado / Aserción</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-27</span></td>
            <td><strong>testConsultarEquiposSinFiltros</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-27</code></td>
            <td>Consulta general de equipos sin parámetros de filtrado</td>
            <td>Retorna <code>['accion' => 'consultar', 'datos' => [...]]</code> con conteo y premios.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-28</span></td>
            <td><strong>testConsultarEquiposConFiltroTexto</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-28</code></td>
            <td><code>filtro => 'Cannibals'</code></td>
            <td>Aplica condición dinámica <code>AND e.nombre LIKE :f1</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-29</span></td>
            <td><strong>testConsultarEquiposErrorBaseDeDatos</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-29</code></td>
            <td>Fallo de conexión o prepare en <code>Consultar()</code></td>
            <td>Captura error y retorna <code>['accion' => 'error', 'mensaje' => ...]</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-30</span></td>
            <td><strong>testConsultarEquiposDirectoExitoso</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-30</code></td>
            <td>Invocación directa de <code>ConsultarEquipos()</code></td>
            <td>Ejecuta query directa y retorna <code>['accion' => 'buscar', 'datos' => [...]]</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-31</span></td>
            <td><strong>testConsultarEquiposDirectoErrorBaseDeDatos</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-31</code></td>
            <td>Fallo simulado por <code>PDOException</code> en <code>ConsultarEquipos()</code></td>
            <td>Captura error y retorna respuesta estructurada de error.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-32</span></td>
            <td><strong>testConsultarAtletasParaAsignacionSinFiltros</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-32</code></td>
            <td>Consulta de atletas para modal de asignación</td>
            <td>Normaliza registros (<code>id</code>, <code>doc_i</code>, <code>nombre</code>, <code>categoria</code>, <code>posicion</code>) y retorna modal.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-33</span></td>
            <td><strong>testConsultarAtletasParaAsignacionConFiltroTexto</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-33</code></td>
            <td>Filtro de búsqueda por documento o nombre (<code>filtro => 'Perez'</code>)</td>
            <td>Enlaza parámetros <code>:f1</code>, <code>:f2</code>, <code>:f3</code> con patrón de búsqueda.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-34</span></td>
            <td><strong>testConsultarAtletasParaAsignacionErrorBaseDeDatos</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-34</code></td>
            <td>Fallo simulado en <code>ConsultarAtletasParaAsignacion()</code></td>
            <td>Retorna <code>['accion' => 'error', 'mensaje' => ...]</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-35</span></td>
            <td><strong>testConsultarAtletasAsignadosEquipoExitoso</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-35</code></td>
            <td>Consulta de atletas asignados a un equipo por ID</td>
            <td>Enlaza <code>:idEquipo</code> sobre <code>vista_atletas_equipo</code> y retorna lista normalizada.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-36</span></td>
            <td><strong>testConsultarAtletasAsignadosEquipoErrorBaseDeDatos</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-36</code></td>
            <td>Fallo de consulta en <code>ConsultarAtletasAsignadosEquipo()</code></td>
            <td>Retorna <code>['accion' => 'error', 'datos' => []]</code> de forma segura.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-37</span></td>
            <td><strong>testBuscarEquipoPorIdDirecto</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-37</code></td>
            <td>Invocación directa <code>Buscar(3)</code> con parámetro de ID</td>
            <td>Retorna registro asociativo del equipo solicitado.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-38</span></td>
            <td><strong>testBuscarEquipoEnrutadoPorProcesarDatos</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-38</code></td>
            <td>Llamada a <code>ProcesarDatos(['accion' => 'buscar', 'id' => 7])</code></td>
            <td>Enruta a <code>Buscar()</code> utilizando atributo interno de ID.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-39</span></td>
            <td><strong>testBuscarEquipoErrorBaseDeDatos</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-39</code></td>
            <td>Excepción en base de datos durante <code>Buscar()</code></td>
            <td>Retorna <code>['accion' => 'error', 'mensaje' => ...]</code>.</td>
        </tr>
    </tbody>
</table>

<!-- SALTO DE PAGINA -->
<div class="page-break"></div>

<!-- SECCION PARTICIPACIONES -->
<h2 class="section-header">10. Módulo de Participaciones (ModeloParticipaciones)</h2>
<p style="font-size: 7.5pt; color: #475569; margin-top: -4px;">
    <strong>Archivo evaluado:</strong> <code>app/modelo/ModeloParticipaciones.php</code> &bull; 
    <strong>Directorio de pruebas:</strong> <code>tests/Unit/Participaciones/</code> &bull; 
    <strong>Total pruebas:</strong> 37 &bull; <strong>Aserciones:</strong> 88 &bull; <strong>Cobertura:</strong> 100.00%
</p>

<!-- ESC-01 -->
<h3 class="scenario-header">ESC-01: Validación de Estructura, Acciones del Modelo y Helpers de Verificación (RF-01)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 24%;">Caso de Prueba / Método</th>
            <th style="width: 18%;">Grupos PHPUnit</th>
            <th style="width: 25%;">Condición / Entrada</th>
            <th style="width: 25%;">Resultado Esperado / Aserción</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-01</span></td>
            <td><strong>testDatosVaciosLanzaExcepcion</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-01</code></td>
            <td>Arreglo vacío <code>[]</code> enviado a <code>ProcesarDatos</code></td>
            <td>Lanza <code>Exception</code>: "No se proporcionaron datos para procesar."</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-02</span></td>
            <td><strong>testAccionInvalidaLanzaExcepcion</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-02</code></td>
            <td><code>accion => 'accion_desconocida'</code></td>
            <td>Lanza <code>Exception</code>: "La acción solicitada para participaciones no es válida."</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-03</span></td>
            <td><strong>testValidarParticipacionIndividualExisteRetornaTrue</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-03</code></td>
            <td>Atleta presente en detalles de participación (<code>COUNT = 1</code>)</td>
            <td>Retorna <code>true</code> confirmando participación individual.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-04</span></td>
            <td><strong>testValidarParticipacionIndividualNoExisteRetornaFalse</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-04</code></td>
            <td>Atleta no presente en detalles (<code>COUNT = 0</code>)</td>
            <td>Retorna <code>false</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-05</span></td>
            <td><strong>testValidarParticipacionIndividualErrorLanzaExcepcion</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-05</code></td>
            <td>Fallo de conexión simulado por <code>PDOException</code></td>
            <td>Propaga la excepción para manejo en capas superiores.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-06</span></td>
            <td><strong>testValidarParticipacionGrupalExisteRetornaTrue</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-06</code></td>
            <td>Equipo registrado previamente en el torneo (<code>COUNT = 1</code>)</td>
            <td>Retorna <code>true</code> detectando participación grupal existente.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-07</span></td>
            <td><strong>testValidarParticipacionGrupalNoExisteRetornaFalse</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-07</code></td>
            <td>Equipo no inscrito en el torneo (<code>COUNT = 0</code>)</td>
            <td>Retorna <code>false</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-08</span></td>
            <td><strong>testValidarParticipacionGrupalErrorLanzaExcepcion</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-08</code></td>
            <td>Excepción en base de datos durante validación grupal</td>
            <td>Propaga la excepción.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-09</span></td>
            <td><strong>testValidarParticipacionDuplicadaPropiaExisteRetornaTrue</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-09</code></td>
            <td>Par torneo/equipo ya registrado en otra participación</td>
            <td>Retorna <code>true</code> alertando colisión con otro registro.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-10</span></td>
            <td><strong>testValidarParticipacionDuplicadaPropiaNoExisteRetornaFalse</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-10</code></td>
            <td>Par torneo/equipo no colisiona con otros registros</td>
            <td>Retorna <code>false</code> permitiendo actualización.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-11</span></td>
            <td><strong>testValidarParticipacionDuplicadaPropiaErrorLanzaExcepcion</strong></td>
            <td><code>RF-01</code>, <code>ESC-01</code>, <code>CP-11</code></td>
            <td>Fallo simulado por <code>PDOException</code></td>
            <td>Propaga la excepción.</td>
        </tr>
    </tbody>
</table>

<!-- ESC-02 -->
<h3 class="scenario-header">ESC-02: Registro de Participaciones, Validación Foránea y Snapshot de Atletas (RF-02)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 24%;">Caso de Prueba / Método</th>
            <th style="width: 18%;">Grupos PHPUnit</th>
            <th style="width: 25%;">Condición / Simulación (Mocks)</th>
            <th style="width: 25%;">Resultado Esperado / Aserción</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-12</span></td>
            <td><strong>testIncluirParticipacionExitosa</strong></td>
            <td><code>RF-02</code>, <code>ESC-02</code>, <code>CP-12</code></td>
            <td>Torneo y equipo válidos, sin duplicados</td>
            <td>Inserta participación y copia atletas de <code>detalles_equipos</code> a <code>detalles_participacion</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-13</span></td>
            <td><strong>testIncluirTorneoInexistenteRechazado</strong></td>
            <td><code>RF-02</code>, <code>ESC-02</code>, <code>CP-13</code></td>
            <td>Torneo no registrado en base de datos (<code>0</code>)</td>
            <td>Retorna <code>['accion' => 'error', 'codigo' => INVALID_ID]</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-14</span></td>
            <td><strong>testIncluirEquipoInexistenteRechazado</strong></td>
            <td><code>RF-02</code>, <code>ESC-02</code>, <code>CP-14</code></td>
            <td>Torneo válido pero equipo inexistente (<code>0</code>)</td>
            <td>Retorna <code>['accion' => 'error', 'codigo' => INVALID_ID . '0']</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-15</span></td>
            <td><strong>testIncluirParticipacionDuplicadaRechazada</strong></td>
            <td><code>RF-02</code>, <code>ESC-02</code>, <code>CP-15</code></td>
            <td>Equipo ya inscrito en el torneo (<code>COUNT = 1</code>)</td>
            <td>Retorna <code>['accion' => 'error', 'codigo' => DUPLICATE]</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-16</span></td>
            <td><strong>testIncluirFalloBaseDeDatosRollback</strong></td>
            <td><code>RF-02</code>, <code>ESC-02</code>, <code>CP-16</code></td>
            <td>Excepción <code>PDOException</code> durante inserción de participación</td>
            <td>Ejecuta rollback de la transacción y retorna código de error.</td>
        </tr>
    </tbody>
</table>

<!-- ESC-03 -->
<h3 class="scenario-header">ESC-03: Modificación de Participaciones, Integridad y Actualización de Snapshot (RF-03)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 24%;">Caso de Prueba / Método</th>
            <th style="width: 18%;">Grupos PHPUnit</th>
            <th style="width: 25%;">Condición / Simulación (Mocks)</th>
            <th style="width: 25%;">Resultado Esperado / Aserción</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-17</span></td>
            <td><strong>testModificarParticipacionExitosa</strong></td>
            <td><code>RF-03</code>, <code>ESC-03</code>, <code>CP-17</code></td>
            <td>Participación existente, nuevos torneo/equipo válidos sin colisión</td>
            <td>Actualiza registro, regenera snapshot de atletas y retorna datos previos y nuevos.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-18</span></td>
            <td><strong>testModificarParticipacionInexistenteRechazada</strong></td>
            <td><code>RF-03</code>, <code>ESC-03</code>, <code>CP-18</code></td>
            <td>Participación no encontrada en tabla <code>participaciones</code></td>
            <td>Retorna <code>['accion' => 'error', 'codigo' => INVALID_ID . '2']</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-19</span></td>
            <td><strong>testModificarTorneoInexistenteRechazado</strong></td>
            <td><code>RF-03</code>, <code>ESC-03</code>, <code>CP-19</code></td>
            <td>Nuevo código de torneo no existe en base de datos</td>
            <td>Retorna <code>['accion' => 'error', 'codigo' => INVALID_ID]</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-20</span></td>
            <td><strong>testModificarEquipoInexistenteRechazado</strong></td>
            <td><code>RF-03</code>, <code>ESC-03</code>, <code>CP-20</code></td>
            <td>Nuevo código de equipo no existe en base de datos</td>
            <td>Retorna <code>['accion' => 'error', 'codigo' => INVALID_ID . '0']</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-21</span></td>
            <td><strong>testModificarParticipacionDuplicadaEnOtroRegistroRechazada</strong></td>
            <td><code>RF-03</code>, <code>ESC-03</code>, <code>CP-21</code></td>
            <td>Par torneo/equipo ya existe en otra participación ajena</td>
            <td>Rechaza con código <code>DUPLICATE</code> y revierte la transacción.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-22</span></td>
            <td><strong>testModificarFalloBaseDeDatosRollback</strong></td>
            <td><code>RF-03</code>, <code>ESC-03</code>, <code>CP-22</code></td>
            <td>Fallo simulado por <code>PDOException</code> durante UPDATE</td>
            <td>Ejecuta rollback y retorna error estructurado.</td>
        </tr>
    </tbody>
</table>

<!-- ESC-04 -->
<h3 class="scenario-header">ESC-04: Eliminación Segura y Cascada de Snapshot de Atletas (RF-04)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 24%;">Caso de Prueba / Método</th>
            <th style="width: 18%;">Grupos PHPUnit</th>
            <th style="width: 25%;">Condición / Simulación (Mocks)</th>
            <th style="width: 25%;">Resultado Esperado / Aserción</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-23</span></td>
            <td><strong>testEliminarParticipacionExitosa</strong></td>
            <td><code>RF-04</code>, <code>ESC-04</code>, <code>CP-23</code></td>
            <td>Participación existente en base de datos</td>
            <td>Elimina en cascada <code>detalles_participacion</code> y <code>participaciones</code>, retornando éxito y datos previos.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-24</span></td>
            <td><strong>testEliminarParticipacionInexistenteRechazada</strong></td>
            <td><code>RF-04</code>, <code>ESC-04</code>, <code>CP-24</code></td>
            <td>Participación no registrada en base de datos</td>
            <td>Rechaza con <code>INVALID_ID</code> y revierte la transacción.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-25</span></td>
            <td><strong>testEliminarFalloBaseDeDatosRollback</strong></td>
            <td><code>RF-04</code>, <code>ESC-04</code>, <code>CP-25</code></td>
            <td>Fallo en base de datos durante la eliminación</td>
            <td>Revierte la transacción mediante rollback y retorna código de error.</td>
        </tr>
    </tbody>
</table>

<!-- ESC-05 -->
<h3 class="scenario-header">ESC-05: Consulta con Filtros Dinámicos, Búsqueda por ID y Reportes (RF-05)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 24%;">Caso de Prueba / Método</th>
            <th style="width: 18%;">Grupos PHPUnit</th>
            <th style="width: 25%;">Condición / Simulación (Mocks)</th>
            <th style="width: 25%;">Resultado Esperado / Aserción</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-26</span></td>
            <td><strong>testConsultarParticipacionesSinFiltros</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-26</code></td>
            <td>Consulta general de participaciones sin filtros</td>
            <td>Retorna <code>['accion' => 'consultar', 'datos' => [...]]</code> con conteo de atletas.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-27</span></td>
            <td><strong>testConsultarParticipacionesConFiltroTextoTorneoOEquipo</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-27</code></td>
            <td><code>filtro => 'Elite'</code></td>
            <td>Aplica condición dinámica <code>AND (t.nombre LIKE :f1 OR e.nombre LIKE :f2)</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-28</span></td>
            <td><strong>testConsultarParticipacionesConFiltroTextoEstatusPorDisputarse</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-28</code></td>
            <td>Texto de búsqueda contiene "por disputarse"</td>
            <td>Añade condición <code>OR t.estatus = 1</code> a la consulta.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-29</span></td>
            <td><strong>testConsultarParticipacionesConFiltroTextoEstatusEnCurso</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-29</code></td>
            <td>Texto de búsqueda contiene "en curso"</td>
            <td>Añade condición <code>OR t.estatus = 2</code> a la consulta.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-30</span></td>
            <td><strong>testConsultarParticipacionesConFiltroTextoEstatusFinalizado</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-30</code></td>
            <td>Texto de búsqueda contiene "finalizado"</td>
            <td>Añade condición <code>OR t.estatus = 3</code> a la consulta.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-31</span></td>
            <td><strong>testConsultarParticipacionesConFiltroEstatusTorneoExacto</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-31</code></td>
            <td><code>estatus_torneo => 1</code></td>
            <td>Aplica condición paramétrica exacta <code>AND t.estatus = :estatus</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-32</span></td>
            <td><strong>testConsultarParticipacionesConFiltroTorneoYEquipoEspecificos</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-32</code></td>
            <td>Filtro simultáneo por <code>codigo_torneo</code> y <code>codigo_equipo</code></td>
            <td>Enlaza <code>:codigo_torneo_filtro</code> y <code>:codigo_equipo_filtro</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-33</span></td>
            <td><strong>testConsultarParticipacionesErrorBaseDeDatos</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-33</code></td>
            <td>Fallo simulado por <code>PDOException</code> en <code>Consultar()</code></td>
            <td>Captura error y retorna <code>['accion' => 'error']</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-34</span></td>
            <td><strong>testBuscarParticipacionExistenteExitosa</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-34</code></td>
            <td>Búsqueda individual de participación existente por ID</td>
            <td>Retorna <code>['accion' => 'buscar', 'datos' => [...]]</code> con el registro.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-35</span></td>
            <td><strong>testBuscarParticipacionNoEncontradaRetornaError</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-35</code></td>
            <td>Participación no encontrada en tabla</td>
            <td>Retorna <code>['accion' => 'error', 'mensaje' => 'Registro no encontrado']</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-36</span></td>
            <td><strong>testBuscarParticipacionErrorBaseDeDatos</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-36</code></td>
            <td>Excepción en base de datos durante <code>Buscar()</code></td>
            <td>Retorna <code>['accion' => 'error', 'mensaje' => 'Error al buscar el registro']</code>.</td>
        </tr>
        <tr>
            <td style="text-align: center;"><span class="badge badge-cp">CP-37</span></td>
            <td><strong>testGenerarReporteEnrutadoPorProcesarDatos</strong></td>
            <td><code>RF-05</code>, <code>ESC-05</code>, <code>CP-37</code></td>
            <td>Llamada a <code>ProcesarDatos(['accion' => 'generar'])</code></td>
            <td>Enruta a <code>Consultar()</code> y retorna conjunto de datos para el reporte.</td>
        </tr>
    </tbody>
</table>

<!-- GUIA DE COMANDOS -->
<h2 class="section-header">11. Guía de Ejecución y Comandos de Filtrado en Consola</h2>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 30%;">Objetivo de la Ejecución</th>
            <th style="width: 70%;">Comando de Consola (PHPUnit CLI)</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Ejecutar Todo Asignaciones</strong></td>
            <td><div class="command-box">vendor\bin\phpunit tests/Unit/Asignaciones</div></td>
        </tr>
        <tr>
            <td><strong>Ejecutar Todo Devoluciones</strong></td>
            <td><div class="command-box">vendor\bin\phpunit tests/Unit/Devoluciones</div></td>
        </tr>
        <tr>
            <td><strong>Ejecutar Todo Estado Físico</strong></td>
            <td><div class="command-box">vendor\bin\phpunit tests/Unit/EstadoFisico</div></td>
        </tr>
        <tr>
            <td><strong>Ejecutar Todo Categoría Catálogo</strong></td>
            <td><div class="command-box">vendor\bin\phpunit tests/Unit/CategoriaCatalogo</div></td>
        </tr>
        <tr>
            <td><strong>Ejecutar Todo Catálogo</strong></td>
            <td><div class="command-box">vendor\bin\phpunit tests/Unit/Catalogo</div></td>
        </tr>
        <tr>
            <td><strong>Ejecutar Todo Artículos Inventario</strong></td>
            <td><div class="command-box">vendor\bin\phpunit tests/Unit/ArticulosInventario</div></td>
        </tr>
        <tr>
            <td><strong>Ejecutar Todo Torneos</strong></td>
            <td><div class="command-box">vendor\bin\phpunit tests/Unit/Torneos</div></td>
        </tr>
        <tr>
            <td><strong>Ejecutar Todo Equipos</strong></td>
            <td><div class="command-box">vendor\bin\phpunit tests/Unit/Equipos</div></td>
        </tr>
        <tr>
            <td><strong>Ejecutar Todo Participaciones</strong></td>
            <td><div class="command-box">vendor\bin\phpunit tests/Unit/Participaciones</div></td>
        </tr>
        <tr>
            <td><strong>Filtrar por Módulo Completo</strong></td>
            <td><div class="command-box">vendor\bin\phpunit --group unitAsignaciones<br>vendor\bin\phpunit --group unitDevoluciones<br>vendor\bin\phpunit --group unitEstadoFisico<br>vendor\bin\phpunit --group unitCategoriaCatalogo<br>vendor\bin\phpunit --group unitCatalogo<br>vendor\bin\phpunit --group unitArticulosInventario<br>vendor\bin\phpunit --group unitTorneos<br>vendor\bin\phpunit --group unitEquipos<br>vendor\bin\phpunit --group unitParticipaciones</div></td>
        </tr>
        <tr>
            <td><strong>Filtrar por Escenario (ESC-XX)</strong></td>
            <td><div class="command-box">vendor\bin\phpunit tests/Unit/Participaciones --group ESC-01<br>vendor\bin\phpunit tests/Unit/Participaciones --group ESC-05</div></td>
        </tr>
        <tr>
            <td><strong>Filtrar por Requisito Funcional (RF-XX)</strong></td>
            <td><div class="command-box">vendor\bin\phpunit tests/Unit/Participaciones --group RF-02<br>vendor\bin\phpunit tests/Unit/Participaciones --group RF-04</div></td>
        </tr>
        <tr>
            <td><strong>Filtrar por Caso Específico (CP-XX)</strong></td>
            <td><div class="command-box">vendor\bin\phpunit tests/Unit/Participaciones --group CP-12<br>vendor\bin\phpunit tests/Unit/Participaciones --group CP-34</div></td>
        </tr>
        <tr>
            <td><strong>Generar Cobertura HTML</strong></td>
            <td><div class="command-box">vendor\bin\phpunit tests/Unit/Participaciones --coverage-html coverage/participaciones</div></td>
        </tr>
    </tbody>
</table>

</body>
</html>
HTML;

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$salidaPdf = $dompdf->output();

// Guardar en public/docs/reportes/
$dirPublic = __DIR__ . '/public/docs/reportes';
if (!is_dir($dirPublic)) {
    mkdir($dirPublic, 0755, true);
}
$rutaPublic = $dirPublic . '/Reporte_Pruebas_Unitarias_Asignaciones_y_Devoluciones.pdf';
file_put_contents($rutaPublic, $salidaPdf);

// Guardar también en la raíz del proyecto para acceso directo
$rutaRaiz = __DIR__ . '/Reporte_Pruebas_Unitarias_Asignaciones_y_Devoluciones.pdf';
file_put_contents($rutaRaiz, $salidaPdf);

echo "PDF actualizado exitosamente con 9 módulos:\n1. {$rutaPublic}\n2. {$rutaRaiz}\nTamaño: " . strlen($salidaPdf) . " bytes\n";
