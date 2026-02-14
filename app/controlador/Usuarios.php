<?php

namespace App\controlador;
use App\modelo\ModeloUsuarios;
use App\controlador\Base;
use Exception;

class Usuarios extends Base{

    public function __construct($bitacora)
    {
        parent::__construct($bitacora);
        $this->ProcesarPermisos(_MD_USUARIOS_);
    }

    private function ValidarPermisos(){
        
    }

    public function ProcesarSolicitud(string $pagina) : void{
        
    }
}