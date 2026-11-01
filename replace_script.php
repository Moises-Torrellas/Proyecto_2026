<?php
$filePath = __DIR__ . '/app/controlador/Devoluciones.php';
$content = file_get_contents($filePath);
$crlf = (strpos($content, "\r\n") !== false);

$pattern = '/function MultiConsulta\(\): void\s*\{.*?\n\}\n/s';

$replacement = "function MultiConsulta(): void\n{\n    try {\n        \$modeloAsignaciones = new ModeloAsignaciones();\n        \$modeloAtletas = new ModeloAtletas();\n        \$modeloEstado = new ModeloEstadoFisico();\n\n        \$respAsignaciones = \$modeloAsignaciones->ConsultarAsignacionesDevolucion();\n        \$respAtletas = \$modeloAtletas->ConsultarAtletas();\n        \$respEstado = \$modeloEstado->Consultar();\n\n        echo json_encode([\n            'accion'       => 'MultiConsulta',\n            'asignaciones' => \$respAsignaciones['datos'] ?? [],\n            'atletas'      => \$respAtletas['datos'] ?? [],\n            'estados'      => \$respEstado['datos'] ?? []\n        ]);\n    } catch (Exception \$e) {\n        logs('Devoluciones', \$e->getMessage(), 'Controlador_MultiConsulta');\n        echo json_encode(['accion' => 'error', 'codigo' => DB_CONNECTION, 'mensaje' => \$e->getMessage()]);\n    }\n}\n";

$normalized = str_replace("\r\n", "\n", $content);
$newNormalized = preg_replace($pattern, $replacement, $normalized, 1, $count);

if ($count === 1) {
    if ($crlf) {
        $newNormalized = str_replace("\n", "\r\n", $newNormalized);
    }
    file_put_contents($filePath, $newNormalized);
    echo "SUCCESS\n";
} else {
    echo "FAILED: Count is $count\n";
}
