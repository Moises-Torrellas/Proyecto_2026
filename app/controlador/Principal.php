<?php

namespace App\controlador;
use App\interface\InterBitacora;
use Exception;

class Principal{

    private InterBitacora $bitacora;

    public function __construct(InterBitacora $bitacora) // inyeccion de la bitacora
    {
        $this->bitacora = $bitacora; // Asignar la instancia de la bitácora al controlador
    }

    public function ProcesarSolicitud(string $pagina) : void{
            $archivoVista = sprintf(__DIR__ . '/../vista/%s.php', $pagina);
            if (is_file($archivoVista)) {
                    // Cargar el archivo de vista correspondiente a la página
                    require_once($archivoVista);
            } else {
                // Si el archivo de vista no existe, mostrar una página de error 404
                require_once(__DIR__ . '/../vista/complementos/404.php');
                exit();
            }
        
    }
}