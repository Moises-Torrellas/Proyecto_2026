<?php

namespace App\servicios;

use Dompdf\Dompdf;
use Dompdf\Options;

class ReporteUsuario
{
    public static function crearPdfUsuarios($datos)
{
    try {
        $dompdf = new \Dompdf\Dompdf();
        
        ob_start();
        include __DIR__ . '/../vista/reportes/PlantillaUsuarios.php';
        $html = ob_get_clean();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // 1. Obtenemos el contenido del PDF
        $output = $dompdf->output();
        
        // 2. Definimos la ruta (Asegúrate de que la carpeta 'docs' tenga permisos de escritura)
        $nombreArchivo = 'Reporte_Usuarios.pdf';
        $rutaRelativa = 'docs/' . $nombreArchivo;
        $rutaAbsoluta = __DIR__ . '/../../public/docs/' . $nombreArchivo;

        // 3. Guardamos el archivo en el servidor
        file_put_contents($rutaAbsoluta, $output);

        // 4. Devolvemos la ruta para que el controlador la use
        return ['accion' => 'reporte', 'archivo' => $rutaRelativa];

    } catch (\Exception $e) {
        return ['accion' => 'error', 'mensaje' => $e->getMessage()];
    }
}
}
