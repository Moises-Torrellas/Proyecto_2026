<?php 

namespace App\modelo;
use Exception;

class ModeloPalmares extends ModeloBase{

    private $id;
    private $id_atleta;
    private $id_torneo;
    private $id_equipo;
    private $id_premio;
    public function __construct(){
        parent::__construct();
    }

    Public function ProcesarDatos(array $datos){
        try{
            $this->id = $datos['id'];
            $this->id_atleta = $datos['id_atleta'];
            $this->id_torneo = $datos['id_torneo'];
            $this->id_equipo = $datos['id_equipo'];
            $this->id_premio = $datos['id_premio'];
        }catch(Exception $e){
            echo $e->getMessage();
        }
    }

}