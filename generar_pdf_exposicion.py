# -*- coding: utf-8 -*-
"""
Generador Maestro del Documento PDF para la Exposición de Técnicas de Pruebas
Ingeniería del Software 2026
Estructurado en 4 Puntos de Exposición (1 por Expositor)
"""

import os
import subprocess
import sys

def generar_html():
    html_content = """<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Técnicas de Pruebas de Software - Guía de Exposición</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap');

  @page {
    size: A4;
    margin: 14mm 16mm 14mm 16mm;
    @bottom-center {
      content: "Página " counter(page) " de " counter(pages);
      font-family: 'Inter', sans-serif;
      font-size: 8pt;
      color: #64748b;
    }
  }

  * {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
  }

  body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    color: #1e293b;
    background-color: #ffffff;
    line-height: 1.5;
    font-size: 9.8pt;
  }

  .page-break {
    page-break-before: always;
  }

  .avoid-break {
    page-break-inside: avoid;
  }

  /* Portada */
  .cover-container {
    padding: 25px 30px;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 55%, #1e1b4b 100%);
    color: #ffffff;
    border-radius: 12px;
    margin-bottom: 25px;
    box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.3);
  }

  .cover-badge {
    display: inline-block;
    background: rgba(59, 130, 246, 0.25);
    border: 1px solid rgba(147, 197, 253, 0.4);
    color: #93c5fd;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 8pt;
    font-weight: 600;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    margin-bottom: 12px;
  }

  .cover-title {
    font-size: 24pt;
    font-weight: 800;
    line-height: 1.15;
    color: #ffffff;
    margin-bottom: 8px;
    letter-spacing: -0.5px;
  }

  .cover-subtitle {
    font-size: 11pt;
    color: #cbd5e1;
    font-weight: 400;
    margin-bottom: 20px;
    max-width: 90%;
  }

  .meta-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    padding: 12px 16px;
    border-radius: 8px;
    font-size: 8.5pt;
  }

  .meta-item strong {
    color: #93c5fd;
    display: block;
    font-size: 7.5pt;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 2px;
  }

  /* Plan de Exposición (4 Expositores) */
  .speakers-plan {
    margin-bottom: 25px;
  }

  .section-headline {
    font-size: 13pt;
    font-weight: 700;
    color: #0f172a;
    border-left: 4px solid #2563eb;
    padding-left: 10px;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .speakers-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
  }

  .speaker-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 12px 14px;
    border-top: 3px solid #2563eb;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  }

  .speaker-card.sp-2 { border-top-color: #0d9488; }
  .speaker-card.sp-3 { border-top-color: #4f46e5; }
  .speaker-card.sp-4 { border-top-color: #d97706; }

  .speaker-card .role-badge {
    font-size: 7pt;
    font-weight: 700;
    text-transform: uppercase;
    display: inline-block;
    padding: 2px 8px;
    border-radius: 4px;
    background: #dbeafe;
    color: #1e40af;
    margin-bottom: 6px;
  }

  .speaker-card.sp-2 .role-badge { background: #ccfbf1; color: #115e59; }
  .speaker-card.sp-3 .role-badge { background: #e0e7ff; color: #3730a3; }
  .speaker-card.sp-4 .role-badge { background: #fef3c7; color: #92400e; }

  .speaker-card h4 {
    font-size: 9.5pt;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 4px;
  }

  .speaker-card p {
    font-size: 8.2pt;
    color: #475569;
    line-height: 1.35;
  }

  /* Encabezados de Puntos */
  .point-header {
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    border-left: 6px solid #2563eb;
    padding: 10px 14px;
    border-radius: 6px;
    margin-top: 18px;
    margin-bottom: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .point-header.p-2 { border-left-color: #0d9488; background: #f0fdfa; border-color: #99f6e4; }
  .point-header.p-3 { border-left-color: #4f46e5; background: #eef2ff; border-color: #c7d2fe; }
  .point-header.p-4 { border-left-color: #d97706; background: #fffbeb; border-color: #fde68a; }

  .point-title {
    font-size: 12pt;
    font-weight: 800;
    color: #0f172a;
  }

  .point-speaker-tag {
    font-size: 7.5pt;
    font-weight: 700;
    background: #0f172a;
    color: #ffffff;
    padding: 3px 9px;
    border-radius: 12px;
    text-transform: uppercase;
  }

  h2 {
    font-size: 11pt;
    font-weight: 700;
    color: #1e293b;
    margin-top: 12px;
    margin-bottom: 6px;
  }

  h3 {
    font-size: 9.5pt;
    font-weight: 600;
    color: #334155;
    margin-top: 8px;
    margin-bottom: 4px;
  }

  p {
    margin-bottom: 8px;
    color: #334155;
    text-align: justify;
  }

  ul, ol {
    margin-left: 18px;
    margin-bottom: 8px;
    color: #334155;
  }

  li {
    margin-bottom: 3px;
  }

  /* Citas de Autores */
  .quote-box {
    background: #f8fafc;
    border-left: 3.5px solid #64748b;
    padding: 8px 12px;
    margin: 8px 0;
    border-radius: 0 6px 6px 0;
    font-size: 8.8pt;
    color: #1e293b;
  }

  .quote-box strong {
    color: #0f172a;
  }

  .quote-box .author-cite {
    font-size: 7.5pt;
    color: #64748b;
    display: block;
    margin-top: 3px;
    font-style: italic;
  }

  /* Tips para el Expositor */
  .tip-box {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-left: 4px solid #16a34a;
    padding: 8px 12px;
    border-radius: 6px;
    margin: 10px 0;
    font-size: 8.5pt;
  }

  .tip-box .tip-title {
    font-weight: 700;
    color: #15803d;
    font-size: 8pt;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 3px;
    display: flex;
    align-items: center;
    gap: 4px;
  }

  /* Warning / Notice */
  .notice-box {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-left: 4px solid #2563eb;
    padding: 8px 12px;
    border-radius: 6px;
    margin: 8px 0;
    font-size: 8.5pt;
  }

  .notice-box .notice-title {
    font-weight: 700;
    color: #1d4ed8;
    font-size: 8pt;
    text-transform: uppercase;
    margin-bottom: 2px;
  }

  /* Bloques de Código */
  .code-container {
    background: #0f172a;
    border-radius: 6px;
    overflow: hidden;
    margin: 10px 0;
    border: 1px solid #334155;
    page-break-inside: avoid;
  }

  .code-header {
    background: #1e293b;
    padding: 5px 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #334155;
  }

  .code-lang-badge {
    font-family: 'Inter', sans-serif;
    font-size: 7pt;
    font-weight: 700;
    text-transform: uppercase;
    padding: 2px 7px;
    border-radius: 4px;
  }

  .badge-php {
    background: #4f46e5;
    color: #ffffff;
  }

  .badge-python {
    background: #0284c7;
    color: #ffffff;
  }

  .code-filename {
    font-family: 'JetBrains Mono', monospace;
    font-size: 7.5pt;
    color: #94a3b8;
  }

  pre {
    padding: 10px 12px;
    font-family: 'JetBrains Mono', monospace;
    font-size: 7.8pt;
    line-height: 1.45;
    color: #e2e8f0;
    overflow-x: auto;
    white-space: pre-wrap;
  }

  .c-kw { color: #f43f5e; font-weight: 600; } /* keyword */
  .c-fn { color: #38bdf8; } /* function */
  .c-cls { color: #fbbf24; font-weight: 600; } /* class */
  .c-str { color: #34d399; } /* string */
  .c-com { color: #64748b; font-style: italic; } /* comment */
  .c-var { color: #e2e8f0; } /* variable */
  .c-num { color: #f97316; } /* number */

  /* Tablas Comparativas */
  table {
    width: 100%;
    border-collapse: collapse;
    margin: 10px 0;
    font-size: 8.2pt;
    page-break-inside: avoid;
  }

  th, td {
    padding: 6px 9px;
    text-align: left;
    border: 1px solid #e2e8f0;
  }

  th {
    background: #f1f5f9;
    color: #0f172a;
    font-weight: 700;
    font-size: 7.8pt;
    text-transform: uppercase;
    letter-spacing: 0.3px;
  }

  tr:nth-child(even) td {
    background-color: #f8fafc;
  }

  /* Diagramas SVG */
  .diagram-wrapper {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 12px;
    margin: 12px 0;
    text-align: center;
    page-break-inside: avoid;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
  }

  .diagram-title {
    font-size: 8.5pt;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  /* Grid 2 Columnas */
  .two-col-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin: 8px 0;
  }

  .col-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 8px 10px;
  }

  .col-card h4 {
    font-size: 8.5pt;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 4px;
    border-bottom: 1px solid #cbd5e1;
    padding-bottom: 2px;
  }

  /* Footer */
  .doc-footer {
    border-top: 1px solid #cbd5e1;
    padding-top: 8px;
    margin-top: 15px;
    font-size: 7.5pt;
    color: #64748b;
    display: flex;
    justify-content: space-between;
  }
</style>
</head>
<body>

  <!-- ==================== PORTADA Y PLAN ==================== -->
  <div class="cover-container">
    <div class="cover-badge">Ingeniería del Software • Cátedra de Calidad de Software</div>
    <h1 class="cover-title">Técnicas y Niveles de Pruebas de Software</h1>
    <p class="cover-subtitle">Guía de Exposición Grupal y Manual Técnico: Fundamentos, Pirámide de Pruebas, Backend, Integración, Sistema, Interfaz de Usuario (GUI) y Pruebas de Humo.</p>
    
    <div class="meta-grid">
      <div class="meta-item">
        <strong>Estructura</strong>
        4 Puntos de Exposición (1 por Integrante)
      </div>
      <div class="meta-item">
        <strong>Tecnologías Prácticas</strong>
        PHP (PHPUnit) &amp; Python (pytest)
      </div>
      <div class="meta-item">
        <strong>Referencias Principales</strong>
        Pressman, Fowler, Jorgensen, McConnell, Kaner
      </div>
    </div>
  </div>

  <div class="speakers-plan avoid-break">
    <div class="section-headline">
      <span>Distribución del Equipo: 4 Puntos de Exposición</span>
      <span style="font-size: 8pt; font-weight: normal; color: #64748b;">Asignación equitativa para 4 expositores</span>
    </div>
    
    <div class="speakers-grid">
      <div class="speaker-card sp-1">
        <span class="role-badge">Punto 1 • Expositor 1</span>
        <h4>Fundamentos, Pirámide y Pruebas Unitarias</h4>
        <p>Introducción metodológica, pirámide clásica y moderna, patrón AAA, dobles de prueba (Mocks vs Stubs), cobertura de código y ejemplos prácticos en PHPUnit y pytest.</p>
      </div>

      <div class="speaker-card sp-2">
        <span class="role-badge">Punto 2 • Expositor 2</span>
        <h4>Pruebas de Backend (Puente Niveles 1 y 2)</h4>
        <p>Definición según Martin Fowler, ámbito del servidor sin GUI, endpoints REST, validación transaccional de BD, diferencia clave con Sistema y automatización con Postman/PHPUnit/pytest.</p>
      </div>

      <div class="speaker-card sp-3">
        <span class="role-badge">Punto 3 • Expositor 3</span>
        <h4>Pruebas de Integración y Estrategias</h4>
        <p>Definición de Pressman, diferenciación conceptual Stubs vs Drivers, las 4 estrategias (Big-Bang, Top-Down, Bottom-Up, Híbrida) y pruebas de persistencia e interfaces.</p>
      </div>

      <div class="speaker-card sp-4">
        <span class="role-badge">Punto 4 • Expositor 4</span>
        <h4>Sistema, Interfaz (GUI) y Pruebas de Humo</h4>
        <p>Pruebas de Sistema (SRS), tabla Carga vs Estrés, automatización GUI con Page Object Model (Playwright/Cypress), y Pruebas de Humo transversales (McConnell / BVT vs Regresión).</p>
      </div>
    </div>
  </div>

  <!-- DIAGRAMA DE LA PIRÁMIDE -->
  <div class="diagram-wrapper avoid-break">
    <div class="diagram-title">Diagrama Oficial: Pirámide de Niveles de Pruebas y Eje Transversal</div>
    <svg viewBox="0 0 760 330" width="100%" height="240" xmlns="http://www.w3.org/2000/svg" style="font-family: 'Inter', sans-serif;">
      <defs>
        <linearGradient id="gradTop" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" stop-color="#ef4444" />
          <stop offset="100%" stop-color="#b91c1c" />
        </linearGradient>
        <linearGradient id="gradSys" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" stop-color="#f59e0b" />
          <stop offset="100%" stop-color="#d97706" />
        </linearGradient>
        <linearGradient id="gradInt" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" stop-color="#0d9488" />
          <stop offset="100%" stop-color="#0f766e" />
        </linearGradient>
        <linearGradient id="gradUnit" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" stop-color="#2563eb" />
          <stop offset="100%" stop-color="#1d4ed8" />
        </linearGradient>
        <linearGradient id="gradSmoke" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" stop-color="#7c3aed" />
          <stop offset="100%" stop-color="#5b21b6" />
        </linearGradient>
      </defs>

      <!-- Eje Izquierdo: Costo y Tiempo -->
      <line x1="90" y1="285" x2="90" y2="40" stroke="#94a3b8" stroke-width="2" stroke-dasharray="4" marker-end="url(#arrowUp)" />
      <path d="M85 45 L90 35 L95 45 Z" fill="#64748b" />
      <text x="80" y="165" font-size="9" fill="#475569" font-weight="600" text-anchor="middle" transform="rotate(-90, 80, 165)">▲ MAYOR COSTO Y TIEMPO DE EJECUCIÓN</text>

      <!-- Eje Derecho: Volumen y Velocidad -->
      <line x1="590" y1="40" x2="590" y2="285" stroke="#94a3b8" stroke-width="2" stroke-dasharray="4" />
      <path d="M585 280 L590 290 L595 280 Z" fill="#64748b" />
      <text x="605" y="165" font-size="9" fill="#475569" font-weight="600" text-anchor="middle" transform="rotate(90, 605, 165)">▼ MAYOR VOLUMEN, RAPIDEZ Y AISLAMIENTO</text>

      <!-- Pirámide: Capa 4 (Aceptación / UAT) -->
      <polygon points="340,30 290,90 390,90" fill="url(#gradTop)" />
      <text x="340" y="65" font-size="9.5" fill="#ffffff" font-weight="bold" text-anchor="middle">NIVEL 4: ACEPTACIÓN</text>
      <text x="340" y="78" font-size="7.5" fill="#fee2e2" text-anchor="middle">(UAT / Negocio - Siguiente Equipo)</text>

      <!-- Pirámide: Capa 3 (Sistema & GUI) -->
      <polygon points="290,95 240,155 440,155 390,95" fill="url(#gradSys)" />
      <text x="340" y="120" font-size="10" fill="#ffffff" font-weight="bold" text-anchor="middle">NIVEL 3: SISTEMA Y GUI (E2E)</text>
      <text x="340" y="134" font-size="8" fill="#fef3c7" text-anchor="middle">Flujos completos, Rendimiento, Carga/Estrés, Usabilidad</text>

      <!-- Pirámide: Capa 2 (Integración & Backend) -->
      <polygon points="240,160 190,220 490,220 440,160" fill="url(#gradInt)" />
      <text x="340" y="185" font-size="10" fill="#ffffff" font-weight="bold" text-anchor="middle">NIVEL 2: INTEGRACIÓN Y BACKEND</text>
      <text x="340" y="199" font-size="8" fill="#ccfbf1" text-anchor="middle">APIs REST, Bases de Datos, Stubs/Drivers, Servicios y Microservicios</text>

      <!-- Pirámide: Capa 1 (Unitarias) -->
      <polygon points="190,225 140,290 540,290 490,225" fill="url(#gradUnit)" />
      <text x="340" y="250" font-size="11" fill="#ffffff" font-weight="bold" text-anchor="middle">NIVEL 1: PRUEBAS UNITARIAS (BASE)</text>
      <text x="340" y="265" font-size="8.5" fill="#dbeafe" text-anchor="middle">Funciones, Métodos, Clases aisladas, Patrón AAA, Mocks y Stubs</text>
      <text x="340" y="278" font-size="7.5" fill="#bfdbfe" text-anchor="middle">Ejecución en milisegundos • Base sólida de la arquitectura</text>

      <!-- Barra Transversal: Pruebas de Humo -->
      <rect x="635" y="45" width="105" height="235" rx="6" fill="url(#gradSmoke)" />
      <text x="687" y="70" font-size="8.5" fill="#ffffff" font-weight="bold" text-anchor="middle">CATEGORÍA</text>
      <text x="687" y="83" font-size="8.5" fill="#ffffff" font-weight="bold" text-anchor="middle">TRANSVERSAL</text>
      <line x1="645" y1="95" x2="730" y2="95" stroke="rgba(255,255,255,0.3)" stroke-width="1" />
      <text x="687" y="125" font-size="9" fill="#f5d0fe" font-weight="bold" text-anchor="middle">PRUEBAS</text>
      <text x="687" y="140" font-size="9" fill="#f5d0fe" font-weight="bold" text-anchor="middle">DE HUMO</text>
      <text x="687" y="155" font-size="7.5" fill="#ffffff" text-anchor="middle">(Smoke / BVT)</text>
      <text x="687" y="185" font-size="7.5" fill="#f5d0fe" text-anchor="middle">Filtro rápido</text>
      <text x="687" y="198" font-size="7.5" fill="#f5d0fe" text-anchor="middle">de compilación</text>
      <text x="687" y="211" font-size="7.5" fill="#f5d0fe" text-anchor="middle">para cada nueva</text>
      <text x="687" y="224" font-size="7.5" fill="#f5d0fe" text-anchor="middle">versión / build</text>
      <text x="687" y="250" font-size="7" fill="#fae8ff" text-anchor="middle">¿Pasa? ➔ Continúa</text>
      <text x="687" y="262" font-size="7" fill="#fecdd3" text-anchor="middle">¿Falla? ➔ Se rechaza</text>
    </svg>
  </div>

  <!-- ==================== PUNTO 1 ==================== -->
  <div class="page-break"></div>

  <div class="point-header">
    <div class="point-title">PUNTO 1: Fundamentos, Pirámide y Pruebas Unitarias</div>
    <div class="point-speaker-tag">Expositor 1</div>
  </div>

  <h2>1.1 Introducción y la Estrategia de Niveles de Pruebas</h2>
  <p>
    En la ingeniería del software, las pruebas no son un evento final y aislado, sino un proceso riguroso de verificación y validación que acompaña todo el ciclo de vida del desarrollo. Ninguna técnica individual puede descubrir todos los defectos; por ello, se adopta un enfoque estratificado en niveles.
  </p>

  <div class="quote-box">
    <strong>Roger S. Pressman:</strong> "La estrategia clásica de pruebas de software se organiza en una progresión sistemática: pruebas de unidad, de integración, de sistema y de aceptación. El proceso avanza desde los componentes individuales más pequeños hacia el ensamblaje del sistema global."
    <span class="author-cite">— Ingeniería del Software: Un enfoque práctico, 7ma Edición.</span>
  </div>

  <p>
    La <strong>Pirámide de Pruebas</strong> (popularizada por Mike Cohn y formalizada por Martin Fowler) postula que la base de la inversión en aseguramiento de calidad debe sustentarse en un volumen masivo de pruebas unitarias automáticas, rápidas y de bajo costo. A medida que se asciende hacia integración y pruebas de sistema/GUI, el número de pruebas disminuye debido a su mayor lentitud, fragilidad y alto costo de mantenimiento.
  </p>

  <h2>1.2 Pruebas Unitarias o de Unidad (Nivel 1 - Base)</h2>
  <div class="quote-box">
    <strong>Definición Canónica (Pressman, Pág. 389):</strong> "Las pruebas unitarias enfocan la verificación en la unidad más pequeña del diseño del software: un componente o módulo individual. Su propósito primordial es detectar errores dentro de ese componente examinando su lógica algorítmica interna y sus estructuras de datos locales de forma totalmente aislada."
  </div>

  <p><strong>Características esenciales de una prueba unitaria de alta calidad:</strong></p>
  <ul>
    <li><strong>Rápida:</strong> Se ejecuta en milisegundos; una suite de miles de pruebas unitarias debe culminar en pocos segundos.</li>
    <li><strong>Aislada e Independiente:</strong> No interactúa con recursos externos volátiles (bases de datos de producción, red, disco ni servicios web).</li>
    <li><strong>Repetible y Determinista:</strong> Produce idéntico resultado en cualquier máquina y momento, sin importar el orden de ejecución.</li>
    <li><strong>Autovalidada:</strong> Determina automáticamente si el caso pasó o falló mediante aserciones formales, sin inspección manual humana.</li>
  </ul>

  <h2>1.3 El Patrón AAA (Arrange - Act - Assert)</h2>
  <p>Es el estándar de la industria para estructurar casos de prueba legibles, modulares y mantenibles:</p>
  <div class="two-col-grid">
    <div class="col-card">
      <h4>1. Arrange (Organizar / Preparar)</h4>
      <p style="font-size: 8pt; margin: 0;">Se inicializan los objetos bajo prueba, se definen los parámetros de entrada y se configuran los dobles de prueba (Mocks o Stubs) necesarios.</p>
    </div>
    <div class="col-card">
      <h4>2. Act (Actuar / Ejecutar)</h4>
      <p style="font-size: 8pt; margin: 0;">Se invoca la función o método específico que se desea validar con las entradas preparadas.</p>
    </div>
  </div>
  <div class="col-card" style="margin-top: 6px;">
    <h4>3. Assert (Afirmar / Verificar)</h4>
    <p style="font-size: 8pt; margin: 0;">Se comprueba mediante aserciones que la salida real obtenida coincida exactamente con el valor o estado esperado, o que se lance la excepción correcta.</p>
  </div>

  <h2>1.4 Conceptos Críticos: Mocks vs. Stubs y Cobertura de Código</h2>
  <div class="two-col-grid">
    <div class="col-card">
      <h4>Stubs (Resguardos Pasivos)</h4>
      <p style="font-size: 8pt; margin: 0;">Objetos que devuelven respuestas predefinidas enlatadas para alimentar la prueba. No verifican comportamiento; solo suministran datos para que el método avance sin fallar.</p>
    </div>
    <div class="col-card">
      <h4>Mocks (Simuladores con Verificación)</h4>
      <p style="font-size: 8pt; margin: 0;">Objetos con expectativas activas. Registran llamadas y permiten afirmar si un método dependiente fue ejecutado con argumentos exactos y una cantidad específica de veces.</p>
    </div>
  </div>

  <p style="margin-top: 8px;">
    <strong>Cobertura de Código (Code Coverage):</strong> Métrica cuantitativa que indica el porcentaje de líneas, ramas (branch coverage) o rutas ejecutadas por la suite de pruebas. Un umbral saludable oscila entre el <strong>75% y 85%</strong> en reglas de negocio críticas. <em>Importante para la exposición:</em> 100% de cobertura no certifica ausencia de defectos (se puede ejecutar código sin evaluar aserciones significativas); sin embargo, una cobertura nula garantiza vulnerabilidad e incertidumbre técnica.
  </p>

  <h2>1.5 Ejemplos Prácticos de Pruebas Unitarias</h2>

  <div class="code-container">
    <div class="code-header">
      <span class="code-filename">tests/Unit/CalculadoraDescuentoTest.php</span>
      <span class="code-lang-badge badge-php">PHP • PHPUnit</span>
    </div>
    <pre><span class="c-kw">&lt;?php</span>
<span class="c-kw">use</span> PHPUnit\Framework\TestCase;

<span class="c-kw">class</span> <span class="c-cls">CalculadoraDescuentoTest</span> <span class="c-kw">extends</span> <span class="c-cls">TestCase</span> {
    <span class="c-kw">public function</span> <span class="c-fn">testCalculaTotalConDescuentoEImpuesto</span>(): <span class="c-kw">void</span> {
        <span class="c-com">// 1. ARRANGE (Organizar): Crear Mock de la tasa de impuesto y la instancia</span>
        <span class="c-var">$mockImpuesto</span> = <span class="c-var">$this</span>-><span class="c-fn">createMock</span>(ServicioImpuestoInterface::<span class="c-kw">class</span>);
        <span class="c-var">$mockImpuesto</span>-><span class="c-fn">method</span>(<span class="c-str">'obtenerTasa'</span>)-><span class="c-fn">willReturn</span>(<span class="c-num">0.16</span>); <span class="c-com">// 16% IVA</span>
        
        <span class="c-var">$calculadora</span> = <span class="c-kw">new</span> <span class="c-cls">CalculadoraDescuento</span>(<span class="c-var">$mockImpuesto</span>);

        <span class="c-com">// 2. ACT (Actuar): Subtotal $100 con cupón PROMO10 (10% descuento = $90 base)</span>
        <span class="c-var">$total</span> = <span class="c-var">$calculadora</span>-><span class="c-fn">calcular</span>(<span class="c-num">100.0</span>, <span class="c-str">'PROMO10'</span>);

        <span class="c-com">// 3. ASSERT (Afirmar): $90 + 16% IVA ($14.4) = $104.4</span>
        <span class="c-var">$this</span>-><span class="c-fn">assertEquals</span>(<span class="c-num">104.4</span>, <span class="c-var">$total</span>, <span class="c-str">"El total con descuento e impuesto debe ser 104.4"</span>);
    }

    <span class="c-kw">public function</span> <span class="c-fn">testLanzaExcepcionSiMontoEsNegativo</span>(): <span class="c-kw">void</span> {
        <span class="c-var">$mockImpuesto</span> = <span class="c-var">$this</span>-><span class="c-fn">createStub</span>(ServicioImpuestoInterface::<span class="c-kw">class</span>);
        <span class="c-var">$calculadora</span> = <span class="c-kw">new</span> <span class="c-cls">CalculadoraDescuento</span>(<span class="c-var">$mockImpuesto</span>);

        <span class="c-var">$this</span>-><span class="c-fn">expectException</span>(\InvalidArgumentException::<span class="c-kw">class</span>);
        <span class="c-var">$calculadora</span>-><span class="c-fn">calcular</span>(-<span class="c-num">50.0</span>, <span class="c-str">'PROMO10'</span>);
    }
}</pre>
  </div>

  <div class="code-container">
    <div class="code-header">
      <span class="code-filename">tests/test_calculadora.py</span>
      <span class="code-lang-badge badge-python">Python • pytest</span>
    </div>
    <pre><span class="c-kw">import</span> pytest
<span class="c-kw">from</span> unittest.mock <span class="c-kw">import</span> Mock
<span class="c-kw">from</span> app.servicios <span class="c-kw">import</span> calcular_total_pedido

<span class="c-com"># Fixture reutilizable para preparar el servicio simulado</span>
<span class="c-kw">@pytest.fixture</span>
<span class="c-kw">def</span> <span class="c-fn">mock_impuesto</span>():
    servicio = Mock()
    servicio.obtener_tasa.return_value = <span class="c-num">0.16</span>  <span class="c-com"># Stub de 16%</span>
    <span class="c-kw">return</span> servicio

<span class="c-kw">def</span> <span class="c-fn">test_calculo_total_con_descuento_valido</span>(mock_impuesto):
    <span class="c-com"># Arrange</span>
    subtotal = <span class="c-num">100.0</span>
    cupon = <span class="c-str">"PROMO10"</span>
    
    <span class="c-com"># Act</span>
    resultado = calcular_total_pedido(subtotal, cupon, mock_impuesto)
    
    <span class="c-com"># Assert</span>
    <span class="c-kw">assert</span> resultado == <span class="c-num">104.4</span>
    mock_impuesto.obtener_tasa.assert_called_once()  <span class="c-com"># Verificación de Mock</span>

<span class="c-kw">def</span> <span class="c-fn">test_monto_negativo_lanza_error</span>(mock_impuesto):
    <span class="c-kw">with</span> pytest.raises(ValueError, match=<span class="c-str">"El monto no puede ser negativo"</span>):
        calcular_total_pedido(-<span class="c-num">25.0</span>, <span class="c-str">"PROMO10"</span>, mock_impuesto)</pre>
  </div>

  <div class="tip-box">
    <div class="tip-title">Tip de Exposición para el Integrante 1:</div>
    Destaca ante el profesor que las pruebas unitarias <strong>nunca deben conectarse a la base de datos real ni a internet</strong>; para eso existen los dobles de prueba (Mocks y Stubs). Si se conectan al exterior, dejan de ser unitarias y se transforman en pruebas de integración.
  </div>

  <!-- ==================== PUNTO 2 ==================== -->
  <div class="page-break"></div>

  <div class="point-header p-2">
    <div class="point-title">PUNTO 2: Pruebas de Backend (El Puente entre Niveles 1 y 2)</div>
    <div class="point-speaker-tag" style="background: #0d9488;">Expositor 2</div>
  </div>

  <h2>2.1 Definición y Alcance según la Ingeniería del Software</h2>
  <div class="quote-box">
    <strong>Martin Fowler:</strong> "Las evaluaciones focalizadas en servicios o API examinan la lógica operativa, la persistencia en bases de datos y los esquemas de seguridad sin interactuar con la capa visual. Representan una verificación rigurosa de caja negra en la infraestructura del servidor."
    <span class="author-cite">— Testing Strategies in a Microservice Architecture.</span>
  </div>

  <div class="notice-box">
    <div class="notice-title">Aclaración Estructural Clave: Backend como Puente de Niveles 1 y 2</div>
    Las pruebas de Backend unen conceptualmente el <strong>Nivel 1 (Unitario)</strong> y el <strong>Nivel 2 (Integración)</strong>. Dentro del backend se prueban tanto componentes aislados (controladores, servicios internos y entidades sin HTTP ni GUI) como la integración directa entre endpoints HTTP, serialización JSON, reglas transaccionales ACID y bases de datos relacionales o NoSQL.
  </div>

  <h2>2.2 Diferencia Crucial: Pruebas de Backend vs. Pruebas de Sistema</h2>
  <p>Es un error habitual confundir las pruebas de backend con las de sistema. La distinción es nítida:</p>
  <table>
    <thead>
      <tr>
        <th style="width: 25%;">Criterio</th>
        <th style="width: 37%;">Pruebas de Backend (Servicios / APIs)</th>
        <th style="width: 38%;">Pruebas de Sistema (System Testing)</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><strong>Ámbito</strong></td>
        <td>Capa del servidor: controladores, microservicios, APIs REST y BD.</td>
        <td>Todo el sistema integrado: Frontend + Backend + Hardware + Red.</td>
      </tr>
      <tr>
        <td><strong>Capa Visual</strong></td>
        <td>Completamente excluida (se prueba vía HTTP directo o llamadas RPC).</td>
        <td>Incluida en los flujos completos de usuario (E2E).</td>
      </tr>
      <tr>
        <td><strong>Objetivo</strong></td>
        <td>Validar lógica de negocio, esquemas de datos, contratos API y BD.</td>
        <td>Validar cumplimiento global de la especificación SRS del cliente.</td>
      </tr>
      <tr>
        <td><strong>Velocidad</strong></td>
        <td>Rápida (sin renderizado visual en navegador web).</td>
        <td>Lenta (espera cargas de assets, renderizado y red completa).</td>
      </tr>
    </tbody>
  </table>

  <h2>2.3 Validación de Base de Datos en el Servidor</h2>
  <p>Una faceta central de las pruebas de backend es asegurar la integridad del modelo de persistencia:</p>
  <ul>
    <li><strong>Transacciones ACID:</strong> Verificar que operaciones con fallos intermedios apliquen <em>Rollback</em> estricto y no corrompan los saldos o inventarios.</li>
    <li><strong>Restricciones de Integridad:</strong> Comprobar que se respeten claves foráneas (FK), índices únicos y restricciones NOT NULL.</li>
    <li><strong>Procedimientos Almacenados y Triggers:</strong> Validar que los eventos automáticos de base de datos disparen los cálculos esperados ante inserciones o bajas.</li>
  </ul>

  <h2>2.4 Flujo de Trabajo Metódico en 5 Pasos</h2>
  <ol>
    <li><strong>1. Aprovisionamiento:</strong> Configurar el entorno con base de datos de pruebas aislada y fixtures de datos conocidas.</li>
    <li><strong>2. Diseño de Casos:</strong> Estructurar escenarios de contratos API (códigos 200/201 para éxito, 400 para validación, 401/403 para autenticación).</li>
    <li><strong>3. Ejecución Automatizada:</strong> Lanzar peticiones HTTP o llamadas de servicio mediante herramientas CLI o de integración.</li>
    <li><strong>4. Verificación de Estado:</strong> Comprobar códigos HTTP, cuerpo JSON devuelto y la persistencia física en la base de datos.</li>
    <li><strong>5. Registro y CI/CD:</strong> Consolidar reportes de fallos de endpoints e incorporar la suite a la canalización de integración continua.</li>
  </ol>

  <h2>2.5 Ejemplos Prácticos de Pruebas de Backend (APIs y Servicios)</h2>

  <div class="code-container">
    <div class="code-header">
      <span class="code-filename">tests/Backend/ApiArticulosTest.php</span>
      <span class="code-lang-badge badge-php">PHP • PHPUnit / Controller Test</span>
    </div>
    <pre><span class="c-kw">&lt;?php</span>
<span class="c-kw">use</span> PHPUnit\Framework\TestCase;

<span class="c-kw">class</span> <span class="c-cls">ApiArticulosTest</span> <span class="c-kw">extends</span> <span class="c-cls">TestCase</span> {
    <span class="c-kw">public function</span> <span class="c-fn">testCrearArticuloRetornaHttp201YJsonValido</span>(): <span class="c-kw">void</span> {
        <span class="c-var">$controlador</span> = <span class="c-kw">new</span> <span class="c-cls">ControladorArticulos</span>();
        <span class="c-var">$payload</span> = [
            <span class="c-str">'codigo'</span> => <span class="c-str">'ART-2026'</span>,
            <span class="c-str">'nombre'</span> => <span class="c-str">'Monitor 27 Pulgadas 144Hz'</span>,
            <span class="c-str">'precio'</span> => <span class="c-num">249.99</span>,
            <span class="c-str">'stock'</span>  => <span class="c-num">15</span>
        ];

        <span class="c-com">// Ejecución en backend: Procesa lógica y guarda en BD</span>
        <span class="c-var">$respuesta</span> = <span class="c-var">$controlador</span>-><span class="c-fn">guardarArticulo</span>(<span class="c-var">$payload</span>);

        <span class="c-com">// Validaciones de API Backend</span>
        <span class="c-var">$this</span>-><span class="c-fn">assertEquals</span>(<span class="c-num">201</span>, <span class="c-var">$respuesta</span>-><span class="c-fn">getStatusCode</span>());
        <span class="c-var">$this</span>-><span class="c-fn">assertTrue</span>(<span class="c-var">$respuesta</span>-><span class="c-fn">hasHeader</span>(<span class="c-str">'Content-Type'</span>, <span class="c-str">'application/json'</span>));
        
        <span class="c-var">$cuerpo</span> = <span class="c-fn">json_decode</span>(<span class="c-var">$respuesta</span>-><span class="c-fn">getBody</span>(), <span class="c-kw">true</span>);
        <span class="c-var">$this</span>-><span class="c-fn">assertEquals</span>(<span class="c-str">'success'</span>, <span class="c-var">$cuerpo</span>[<span class="c-str">'estado'</span>]);
        <span class="c-var">$this</span>-><span class="c-fn">assertArrayHasKey</span>(<span class="c-str">'id_generado'</span>, <span class="c-var">$cuerpo</span>);
    }
}</pre>
  </div>

  <div class="code-container">
    <div class="code-header">
      <span class="code-filename">tests/backend/test_api_backend.py</span>
      <span class="code-lang-badge badge-python">Python • pytest + TestClient</span>
    </div>
    <pre><span class="c-kw">import</span> pytest
<span class="c-kw">from</span> fastapi.testclient <span class="c-kw">import</span> TestClient
<span class="c-kw">from</span> app.main <span class="c-kw">import</span> app

client = TestClient(app)

<span class="c-kw">def</span> <span class="c-fn">test_registro_articulo_backend_exitoso</span>():
    payload = {
        <span class="c-str">"sku"</span>: <span class="c-str">"LAP-001"</span>,
        <span class="c-str">"nombre"</span>: <span class="c-str">"Laptop ThinkPad"</span>,
        <span class="c-str">"precio"</span>: <span class="c-num">850.00</span>,
        <span class="c-str">"categoria_id"</span>: <span class="c-num">3</span>
    }
    response = client.post(<span class="c-str">"/api/v1/articulos"</span>, json=payload)
    
    <span class="c-com"># Aserciones de API Backend</span>
    <span class="c-kw">assert</span> response.status_code == <span class="c-num">201</span>
    data = response.json()
    <span class="c-kw">assert</span> data[<span class="c-str">"sku"</span>] == <span class="c-str">"LAP-001"</span>
    <span class="c-kw">assert</span> data[<span class="c-str">"activo"</span>] <span class="c-kw">is True</span>

<span class="c-kw">def</span> <span class="c-fn">test_validacion_error_422_por_precio_invalido</span>():
    payload = {<span class="c-str">"sku"</span>: <span class="c-str">"LAP-001"</span>, <span class="c-str">"precio"</span>: -<span class="c-num">10.0</span>}
    response = client.post(<span class="c-str">"/api/v1/articulos"</span>, json=payload)
    <span class="c-kw">assert</span> response.status_code == <span class="c-num">422</span>  <span class="c-com"># Unprocessable Entity</span></pre>
  </div>

  <p><strong>Herramientas destacadas:</strong> <strong>Postman</strong> (diseño y colecciones automatizadas con Newman), <strong>Apache JMeter</strong> (rendimiento y estrés de endpoints), <strong>sqlmap</strong> (auditoría de inyección SQL) y frameworks de código como <strong>PHPUnit</strong> y <strong>pytest</strong>.</p>

  <!-- ==================== PUNTO 3 ==================== -->
  <div class="page-break"></div>

  <div class="point-header p-3">
    <div class="point-title">PUNTO 3: Pruebas de Integración (Nivel 2: Interfaces y Estrategias)</div>
    <div class="point-speaker-tag" style="background: #4f46e5;">Expositor 3</div>
  </div>

  <h2>3.1 Definición y Enfoque Metodológico</h2>
  <div class="notice-box">
    <div class="notice-title">Frase Puente para la Exposición</div>
    "Como acabamos de ver con las pruebas de backend, los componentes del servidor necesitan comunicarse continuamente entre sí. Ahora profundizamos formalmente en el <strong>Nivel 2 de la Pirámide: Pruebas de Integración</strong>, analizando cómo validar la interacción entre múltiples módulos ensamblados."
  </div>

  <div class="quote-box">
    <strong>Roger S. Pressman:</strong> "Las pruebas de integración son una técnica sistemática para construir la arquitectura del software y, al mismo tiempo, realizar pruebas que descubran errores asociados con las interfaces entre los componentes. Se unen módulos previamente probados de forma individual y se comprueba que colaboren correctamente."
    <span class="author-cite">— Software Engineering: A Practitioner's Approach.</span>
  </div>

  <h2>3.2 Diferenciación Fundamental: Stubs vs. Drivers</h2>
  <p>
    En integración, casi nunca todos los componentes están listos al mismo tiempo. Para probar módulos intermedios se utilizan dos tipos de sustitutos con funciones opuestas:
  </p>

  <div class="two-col-grid">
    <div class="col-card" style="border-left: 3px solid #2563eb;">
      <h4>Drivers (Controladores / Módulos Conductores)</h4>
      <p style="font-size: 8.2pt; margin-bottom: 4px;"><strong>Ubicación:</strong> Capa Superior (Llamador).</p>
      <p style="font-size: 8pt; margin: 0;">Un Driver es un programa auxiliar que <em>invoca al módulo bajo prueba</em>, pasándole parámetros y recibiendo resultados. Se necesita cuando los módulos superiores de coordinación aún no han sido desarrollados.</p>
    </div>
    <div class="col-card" style="border-left: 3px solid #0d9488;">
      <h4>Stubs (Resguardos / Servidores Falsos)</h4>
      <p style="font-size: 8.2pt; margin-bottom: 4px;"><strong>Ubicación:</strong> Capa Inferior (Llamado).</p>
      <p style="font-size: 8pt; margin: 0;">Un Stub es un módulo simulado que <em>es invocado por el módulo bajo prueba</em>. Responde con datos sintéticos para sustituir servicios o bases de datos de bajo nivel que aún no están disponibles o son inestables.</p>
    </div>
  </div>

  <!-- DIAGRAMA DRIVERS VS STUBS -->
  <div class="diagram-wrapper avoid-break" style="padding: 8px;">
    <div class="diagram-title">Esquema Arquitectónico: Uso de Drivers y Stubs en Integración</div>
    <svg viewBox="0 0 680 145" width="100%" height="115" xmlns="http://www.w3.org/2000/svg" style="font-family: 'Inter', sans-serif;">
      <!-- Driver -->
      <rect x="30" y="45" width="170" height="55" rx="6" fill="#dbeafe" stroke="#2563eb" stroke-width="2" />
      <text x="115" y="70" font-size="9.5" fill="#1e40af" font-weight="bold" text-anchor="middle">DRIVER (Controlador)</text>
      <text x="115" y="85" font-size="7.5" fill="#1e3a8a" text-anchor="middle">Emula la capa superior no creada</text>

      <!-- Flecha Driver -> Componente -->
      <line x1="200" y1="72" x2="250" y2="72" stroke="#2563eb" stroke-width="2" />
      <polygon points="250,72 242,67 242,77" fill="#2563eb" />
      <text x="225" y="64" font-size="7" fill="#64748b" text-anchor="middle">Invoca</text>

      <!-- Módulo Bajo Prueba -->
      <rect x="255" y="35" width="170" height="75" rx="6" fill="#1e293b" stroke="#0f172a" stroke-width="2" />
      <text x="340" y="65" font-size="10" fill="#ffffff" font-weight="bold" text-anchor="middle">MÓDULO EVALUADO</text>
      <text x="340" y="80" font-size="8" fill="#38bdf8" text-anchor="middle">(Componente bajo prueba)</text>
      <text x="340" y="95" font-size="7" fill="#94a3b8" text-anchor="middle">Ejecuta lógica e interactúa</text>

      <!-- Flecha Componente -> Stub -->
      <line x1="425" y1="72" x2="475" y2="72" stroke="#0d9488" stroke-width="2" />
      <polygon points="475,72 467,67 467,77" fill="#0d9488" />
      <text x="450" y="64" font-size="7" fill="#64748b" text-anchor="middle">Consume</text>

      <!-- Stub -->
      <rect x="480" y="45" width="170" height="55" rx="6" fill="#ccfbf1" stroke="#0d9488" stroke-width="2" />
      <text x="565" y="70" font-size="9.5" fill="#115e59" font-weight="bold" text-anchor="middle">STUB (Resguardo)</text>
      <text x="565" y="85" font-size="7.5" fill="#134e4a" text-anchor="middle">Emula módulo inferior o servicio</text>
    </svg>
  </div>

  <h2>3.3 Las Cuatro Estrategias de Integración</h2>
  <table>
    <thead>
      <tr>
        <th style="width: 18%;">Estrategia</th>
        <th style="width: 32%;">Mecanismo de Ensamblaje</th>
        <th style="width: 25%;">Elementos Necesarios</th>
        <th style="width: 25%;">Dificultad de Depuración</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><strong>1. Big-Bang</strong></td>
        <td>Todos los módulos se unen y se prueban en un solo paso masivo.</td>
        <td>Ninguno (solo el sistema listo).</td>
        <td><strong>Extrema:</strong> Imposible aislar qué módulo originó la falla.</td>
      </tr>
      <tr>
        <td><strong>2. Ascendente (Bottom-Up)</strong></td>
        <td>Inicia en los módulos más bajos (acceso a BD, utilidades) y sube progresivamente.</td>
        <td>Requiere <strong>Drivers</strong> para emular los niveles superiores.</td>
        <td><strong>Baja:</strong> Los cimientos se estabilizan tempranamente.</td>
      </tr>
      <tr>
        <td><strong>3. Descendente (Top-Down)</strong></td>
        <td>Inicia en los módulos arquitectónicos y de control superior y desciende hacia las hojas.</td>
        <td>Requiere <strong>Stubs</strong> para simular las funciones inferiores faltantes.</td>
        <td><strong>Moderada:</strong> Valida reglas de negocio principales desde el inicio.</td>
      </tr>
      <tr>
        <td><strong>4. Híbrida / Sandwich</strong></td>
        <td>Combina Bottom-Up en capas de infraestructura y Top-Down en capas de negocio.</td>
        <td>Utiliza simultáneamente <strong>Drivers y Stubs</strong>.</td>
        <td><strong>Controlada:</strong> La más balanceada en proyectos industriales.</td>
      </tr>
    </tbody>
  </table>

  <h2>3.4 Ejemplos Prácticos de Pruebas de Integración</h2>

  <div class="code-container">
    <div class="code-header">
      <span class="code-filename">tests/Integration/ServicioInventarioDbTest.php</span>
      <span class="code-lang-badge badge-php">PHP • PHPUnit con Base de Datos SQLite</span>
    </div>
    <pre><span class="c-kw">&lt;?php</span>
<span class="c-kw">use</span> PHPUnit\Framework\TestCase;

<span class="c-kw">class</span> <span class="c-cls">ServicioInventarioDbTest</span> <span class="c-kw">extends</span> <span class="c-cls">TestCase</span> {
    <span class="c-kw">private</span> \PDO <span class="c-var">$pdo</span>;

    <span class="c-kw">protected function</span> <span class="c-fn">setUp</span>(): <span class="c-kw">void</span> {
        <span class="c-com">// Integración real contra SQLite en memoria (aislado pero con motor SQL real)</span>
        <span class="c-var">$this</span>->pdo = <span class="c-kw">new</span> \PDO(<span class="c-str">'sqlite::memory:'</span>);
        <span class="c-var">$this</span>->pdo-><span class="c-fn">exec</span>(<span class="c-str">"CREATE TABLE articulos (id INTEGER PRIMARY KEY, sku TEXT, stock INT)"</span>);
    }

    <span class="c-kw">public function</span> <span class="c-fn">testIntegracionServicioYRepositorioPersistenEnBaseDatos</span>(): <span class="c-kw">void</span> {
        <span class="c-var">$repositorio</span> = <span class="c-kw">new</span> <span class="c-cls">RepositorioArticulosPdo</span>(<span class="c-var">$this</span>->pdo);
        <span class="c-var">$servicio</span> = <span class="c-kw">new</span> <span class="c-cls">ServicioInventario</span>(<span class="c-var">$repositorio</span>);

        <span class="c-com">// Se ejecuta el flujo integrado de múltiples clases</span>
        <span class="c-var">$servicio</span>-><span class="c-fn">ingresarStock</span>(<span class="c-str">'SKU-88'</span>, <span class="c-num">50</span>);

        <span class="c-com">// Se comprueba la persistencia real consultando el registro</span>
        <span class="c-var">$stmt</span> = <span class="c-var">$this</span>->pdo-><span class="c-fn">query</span>(<span class="c-str">"SELECT stock FROM articulos WHERE sku = 'SKU-88'"</span>);
        <span class="c-var">$fila</span> = <span class="c-var">$stmt</span>-><span class="c-fn">fetch</span>(\PDO::FETCH_ASSOC);

        <span class="c-var">$this</span>-><span class="c-fn">assertEquals</span>(<span class="c-num">50</span>, (int)<span class="c-var">$fila</span>[<span class="c-str">'stock'</span>]);
    }
}</pre>
  </div>

  <div class="code-container">
    <div class="code-header">
      <span class="code-filename">tests/integration/test_integracion_servicio_db.py</span>
      <span class="code-lang-badge badge-python">Python • pytest + SQLite Fixture</span>
    </div>
    <pre><span class="c-kw">import</span> pytest
<span class="c-kw">import</span> sqlite3
<span class="c-kw">from</span> app.repositorios <span class="c-kw">import</span> RepositorioUsuario
<span class="c-kw">from</span> app.servicios <span class="c-kw">import</span> ServicioAutenticacion

<span class="c-kw">@pytest.fixture</span>
<span class="c-kw">def</span> <span class="c-fn">db_conexion</span>():
    conn = sqlite3.connect(<span class="c-str">":memory:"</span>)  <span class="c-com"># Base de datos en memoria para integración</span>
    conn.execute(<span class="c-str">"CREATE TABLE usuarios (id INT, email TEXT, clave_hash TEXT)"</span>)
    <span class="c-kw">yield</span> conn
    conn.close()

<span class="c-kw">def</span> <span class="c-fn">test_integracion_servicio_autenticacion_con_bd_real</span>(db_conexion):
    repo = RepositorioUsuario(db_conexion)
    servicio = ServicioAutenticacion(repo)
    
    <span class="c-com"># Act: Registrar usuario e intentar login</span>
    servicio.registrar_usuario(<span class="c-str">"admin@empresa.com"</span>, <span class="c-str">"ClaveSegura123!"</span>)
    es_valido = servicio.validar_credenciales(<span class="c-str">"admin@empresa.com"</span>, <span class="c-str">"ClaveSegura123!"</span>)
    
    <span class="c-com"># Assert: Verifica la comunicación real entre servicio de hashing y tabla SQL</span>
    <span class="c-kw">assert</span> es_valido <span class="c-kw">is True</span></pre>
  </div>

  <!-- ==================== PUNTO 4 ==================== -->
  <div class="page-break"></div>

  <div class="point-header p-4">
    <div class="point-title">PUNTO 4: Pruebas de Sistema, Interfaz (GUI) y Pruebas de Humo</div>
    <div class="point-speaker-tag" style="background: #d97706;">Expositor 4</div>
  </div>

  <h2>4.1 Pruebas de Sistema (Nivel 3)</h2>
  <div class="quote-box">
    <strong>Roger S. Pressman:</strong> "Las pruebas de sistema evalúan el software completamente integrado con los demás elementos del entorno —hardware, personas y bases de datos operativas— para comprobar que el conjunto completo responde a los requisitos globales."
    <span class="author-cite">— Software Engineering: A Practitioner's Approach.</span>
  </div>

  <div class="quote-box">
    <strong>Paul C. Jorgensen:</strong> "La prueba de sistema examina el comportamiento del sistema completo y totalmente integrado contra la Especificación de Requisitos de Software (SRS), validando flujos de trabajo de extremo a extremo bajo un enfoque estricto de caja negra."
    <span class="author-cite">— Software Testing: A Craftsman's Approach, 4ta Edición.</span>
  </div>

  <h3>Tabla Comparativa Fundamental: Pruebas de Carga vs. Pruebas de Estrés</h3>
  <table>
    <thead>
      <tr>
        <th style="width: 20%;">Parámetro</th>
        <th style="width: 40%;">Pruebas de Carga (Load Testing)</th>
        <th style="width: 40%;">Pruebas de Estrés (Stress Testing)</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><strong>Objetivo</strong></td>
        <td>Validar el rendimiento y los tiempos de respuesta bajo el tráfico previsto normal o pico planificado.</td>
        <td>Llevar el sistema <em>más allá</em> del límite máximo para provocar la falla y evaluar su punto de quiebre.</td>
      </tr>
      <tr>
        <td><strong>Volumen</strong></td>
        <td>Dentro de los límites esperados según la especificación del negocio.</td>
        <td>Cargas extremas, saturación de memoria, desconexión abrupta de servidores.</td>
      </tr>
      <tr>
        <td><strong>Resultado Esperado</strong></td>
        <td>El sistema responde de manera fluida y dentro del umbral SLA acordado.</td>
        <td>Degradación controlada, sin corrupción de datos y con capacidad de auto-recuperación.</td>
      </tr>
    </tbody>
  </table>

  <p><strong>Herramientas de Ejecución vs Gestión:</strong> Es fundamental diferenciar las herramientas de ejecución real (<strong>Apache JMeter, k6, Locust, OWASP ZAP</strong>) de aquellas que solo sirven para la gestión de casos y reporte de defectos (<strong>TestRail, Jira, Bugzilla</strong>).</p>

  <h2>4.2 Pruebas de Interfaz Gráfica de Usuario (UI/GUI Testing)</h2>
  <div class="quote-box">
    <strong>Cem Kaner:</strong> "Estas evaluaciones comprueban la correcta renderización visual y operatividad de los componentes con los que interactúa el usuario final, abarcando elementos visuales, botones, formularios y navegación."
    <span class="author-cite">— Testing Computer Software.</span>
  </div>

  <p>
    <strong>El Patrón Page Object Model (POM):</strong> Es el patrón arquitectónico estándar de automatización de GUI. Separa la lógica de prueba de los selectores HTML específicos encapsulándolos en clases representativas de página. Si un botón o campo cambia de selector en el HTML, solo se actualiza una clase Page Object sin alterar cientos de pruebas existentes.
  </p>

  <p><strong>Herramientas Modernas de GUI:</strong> <strong>Playwright</strong> (de Microsoft, ultra rápido y multi-lenguaje), <strong>Cypress</strong> (excelente para Single Page Apps en JavaScript) y <strong>Selenium WebDriver</strong> (el estándar histórico multiplataforma).</p>

  <div class="code-container">
    <div class="code-header">
      <span class="code-filename">tests/gui/test_login_page_object.py</span>
      <span class="code-lang-badge badge-python">Python • Playwright + Page Object Model</span>
    </div>
    <pre><span class="c-kw">from</span> playwright.sync_api <span class="c-kw">import</span> Page, expect

<span class="c-kw">class</span> <span class="c-cls">LoginPage</span>:
    <span class="c-kw">def</span> <span class="c-fn">__init__</span>(self, page: Page):
        self.page = page
        self.input_usuario = page.locator(<span class="c-str">"#txt_usuario"</span>)
        self.input_clave = page.locator(<span class="c-str">"#txt_password"</span>)
        self.btn_ingresar = page.locator(<span class="c-str">"button[type='submit']"</span>)

    <span class="c-kw">def</span> <span class="c-fn">iniciar_sesion</span>(self, usuario: str, clave: str):
        self.input_usuario.fill(usuario)
        self.input_clave.fill(clave)
        self.btn_ingresar.click()

<span class="c-kw">def</span> <span class="c-fn">test_login_exitoso</span>(page: Page):
    page.goto(<span class="c-str">"https://sistema2026.local/login"</span>)
    login_page = LoginPage(page)
    
    <span class="c-com"># Ejecución de la acción de interfaz de usuario</span>
    login_page.iniciar_sesion(<span class="c-str">"operador1"</span>, <span class="c-str">"Secret2026*"</span>)
    
    <span class="c-com"># Verificación de GUI: Debe redirigir y mostrar el Dashboard</span>
    expect(page).to_have_url(<span class="c-str">"https://sistema2026.local/dashboard"</span>)
    expect(page.locator(<span class="c-str">"#bienvenida"</span>)).to_contain_text(<span class="c-str">"Bienvenido, operador1"</span>)</pre>
  </div>

  <div class="notice-box">
    <div class="notice-title">Frase Puente al Nivel 4 (Cúspide de la Pirámide)</div>
    "El <strong>Nivel 4 corresponde a las Pruebas de Aceptación del Usuario (UAT)</strong>, donde el cliente y los usuarios finales verifican el sistema en base a los criterios de aceptación del negocio para autorizar el paso a producción. Este nivel corresponde desarrollarlo al siguiente equipo expositor."
  </div>

  <h2>4.3 Pruebas de Humo (Smoke Testing - Categoría Transversal)</h2>
  <div class="quote-box">
    <strong>Steve McConnell:</strong> "Adaptado de la ingeniería de hardware, este concepto consiste en una Verificación Preliminar de Construcción (Build Verification Testing - BVT). Su meta primordial radica en validar que las funciones críticas y de mayor prioridad operen adecuadamente tras una compilación reciente; si esta prueba falla, la versión es rechazada de inmediato."
    <span class="author-cite">— Rapid Development: Taming Wild Software Schedules.</span>
  </div>

  <!-- DIAGRAMA FLUJO SMOKE TESTING -->
  <div class="diagram-wrapper avoid-break" style="padding: 6px;">
    <div class="diagram-title">Flujo Operativo de Pruebas de Humo (BVT)</div>
    <svg viewBox="0 0 680 120" width="100%" height="95" xmlns="http://www.w3.org/2000/svg" style="font-family: 'Inter', sans-serif;">
      <!-- Build -->
      <rect x="20" y="35" width="130" height="50" rx="5" fill="#f1f5f9" stroke="#64748b" stroke-width="1.5" />
      <text x="85" y="56" font-size="8.5" fill="#0f172a" font-weight="bold" text-anchor="middle">NUEVA VERSIÓN</text>
      <text x="85" y="70" font-size="7.5" fill="#475569" text-anchor="middle">(Nueva Compilación / Build)</text>

      <line x1="150" y1="60" x2="190" y2="60" stroke="#64748b" stroke-width="1.5" />
      <polygon points="190,60 182,56 182,64" fill="#64748b" />

      <!-- Smoke Suite -->
      <rect x="195" y="30" width="160" height="60" rx="5" fill="#7c3aed" stroke="#5b21b6" stroke-width="1.5" />
      <text x="275" y="52" font-size="9" fill="#ffffff" font-weight="bold" text-anchor="middle">EJECUTAR SMOKE TEST</text>
      <text x="275" y="66" font-size="7.5" fill="#f5d0fe" text-anchor="middle">Suite rápida (5 a 15 min)</text>
      <text x="275" y="78" font-size="7" fill="#f5d0fe" text-anchor="middle">Prueba login, BD y rutas clave</text>

      <line x1="355" y1="60" x2="395" y2="60" stroke="#64748b" stroke-width="1.5" />
      <polygon points="395,60 387,56 387,64" fill="#64748b" />

      <!-- Decisión -->
      <polygon points="440,30 485,60 440,90 395,60" fill="#fef3c7" stroke="#d97706" stroke-width="1.5" />
      <text x="440" y="58" font-size="7.5" fill="#92400e" font-weight="bold" text-anchor="middle">¿Pasan</text>
      <text x="440" y="68" font-size="7.5" fill="#92400e" font-weight="bold" text-anchor="middle">críticas?</text>

      <!-- Si: Aprobado -->
      <line x1="485" y1="48" x2="530" y2="35" stroke="#16a34a" stroke-width="1.5" />
      <polygon points="530,35 520,34 524,42" fill="#16a34a" />
      <rect x="535" y="15" width="130" height="40" rx="4" fill="#dcfce7" stroke="#16a34a" stroke-width="1.5" />
      <text x="600" y="32" font-size="8" fill="#15803d" font-weight="bold" text-anchor="middle">BUILD APROBADA (PASS)</text>
      <text x="600" y="44" font-size="7" fill="#166534" text-anchor="middle">Pasa a Sistema y Regresión</text>

      <!-- No: Rechazado -->
      <line x1="485" y1="72" x2="530" y2="85" stroke="#dc2626" stroke-width="1.5" />
      <polygon points="530,85 524,78 520,86" fill="#dc2626" />
      <rect x="535" y="68" width="130" height="40" rx="4" fill="#fee2e2" stroke="#dc2626" stroke-width="1.5" />
      <text x="600" y="85" font-size="8" fill="#b91c1c" font-weight="bold" text-anchor="middle">BUILD RECHAZADA (FAIL)</text>
      <text x="600" y="97" font-size="7" fill="#991b1b" text-anchor="middle">Retorna a Devs de inmediato</text>
    </svg>
  </div>

  <h3>Tabla de Diferenciación: Smoke vs. Sanity vs. Regression Testing</h3>
  <table>
    <thead>
      <tr>
        <th style="width: 18%;">Técnica</th>
        <th style="width: 28%;">Momento de Ejecución</th>
        <th style="width: 27%;">Alcance y Profundidad</th>
        <th style="width: 27%;">Objetivo Primordial</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><strong>Smoke Testing</strong></td>
        <td>Inmediatamente tras compilar una nueva versión.</td>
        <td><strong>Muy amplio y superficial:</strong> Toca todos los módulos principales sin entrar en detalle.</td>
        <td>Comprobar si la versión es suficientemente estable para ser evaluada.</td>
      </tr>
      <tr>
        <td><strong>Sanity Testing</strong></td>
        <td>Tras aplicar una corrección de error (bugfix) puntual.</td>
        <td><strong>Muy estrecho y profundo:</strong> Se centra estrictamente en la funcionalidad reparada.</td>
        <td>Validar que la falla reportada fue solucionada antes de continuar.</td>
      </tr>
      <tr>
        <td><strong>Regression Testing</strong></td>
        <td>De forma periódica antes de pases a producción.</td>
        <td><strong>Exhaustivo:</strong> Vuelve a ejecutar la suite completa de pruebas funcionales.</td>
        <td>Garantizar que nuevos cambios no hayan deteriorado funcionalidades previas.</td>
      </tr>
    </tbody>
  </table>

  <div class="tip-box">
    <div class="tip-title">¿Qué entra en una Prueba de Humo práctica?:</div>
    Solo flujos esenciales ("Happy Paths"): <strong>1.</strong> Inicio de sesión con usuario válido. <strong>2.</strong> Carga correcta de la vista principal. <strong>3.</strong> Conexión activa a la base de datos. <strong>4.</strong> Creación de una transacción mínima. Tiempo límite: menor a 10 minutos.
  </div>

  <div class="doc-footer">
    <span>Ingeniería del Software 2026 • Documento Maestro de Exposición</span>
    <span>Consolidado para 4 Expositores • Ejemplos en PHPUnit y Pytest</span>
  </div>

</body>
</html>
"""
    return html_content

def compilar_pdf():
    html_path = r"c:\xampp\htdocs\Proyecto_2026\Tecnicas_de_Pruebas_Exposicion_Guia_Completa.html"
    pdf_path = r"c:\xampp\htdocs\Proyecto_2026\Tecnicas_de_Pruebas_Exposicion_Guia_Completa.pdf"

    html_content = generar_html()
    with open(html_path, "w", encoding="utf-8") as f:
        f.write(html_content)
    print(f"Archivo HTML generado exitosamente en: {html_path}")

    # Ejecutar Google Chrome en modo Headless para imprimir PDF impecable
    chrome_cmd = [
        r"C:\Program Files\Google\Chrome\Application\chrome.exe",
        "--headless=new",
        "--disable-gpu",
        "--no-pdf-header-footer",
        f"--print-to-pdf={pdf_path}",
        html_path
    ]

    print("Ejecutando renderizador Chrome Headless...")
    result = subprocess.run(chrome_cmd, capture_output=True, text=True)
    if result.returncode == 0 and os.path.exists(pdf_path):
        size_kb = os.path.getsize(pdf_path) / 1024
        print(f"PDF generado con EXCELENCIA: {pdf_path} ({size_kb:.1f} KB)")
        return True
    else:
        print(f"Error generando PDF: {result.stderr}")
        return False

if __name__ == "__main__":
    exito = compilar_pdf()
    if not exito:
        sys.exit(1)
