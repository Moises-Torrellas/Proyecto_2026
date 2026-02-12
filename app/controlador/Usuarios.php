<?php

namespace App\controlador;
use App\modelo\ModeloUsuarios;
use App\interface\InterBitacora;
use Exception;

class Usuarios {
    private InterBitacora $bitacora;

    public function __construct(InterBitacora $bitacora)
    {
        $this->bitacora = $bitacora;
    }

    private function ValidarPermisos(){
        
    }

    public function ProcesarSolicitud(string $pagina) : void{
        
    }
}