<?php

namespace App\modelo;

use App\modelo\ModeloBase;
use Exception;
use SensitiveParameter;

class ModeloCalidad extends ModeloBase
{
    private $id;
    private $nombre;
    private $nivel;

    public function __construct()
    {
        parent::__construct();
        $this->campoWhitelist = [
            'id' => 'id_categorias',
            'nombre' => 'nombre'
        ];
        $this->llavePrimaria = 'id_categorias';
    }
}