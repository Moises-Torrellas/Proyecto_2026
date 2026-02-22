<?php

namespace App\controlador;
use App\controlador\Base;
use Exception;

class Principal extends Base{

    public function __construct($bitacora) // inyeccion de la bitacora
    {
        
    }

    public function ProcesarSolicitud(string $pagina) : void{
        $this->CargarVista($pagina);
    }
}