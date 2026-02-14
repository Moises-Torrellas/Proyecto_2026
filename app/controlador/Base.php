<?php

namespace App\controlador;

use App\interface\InterBitacora;
use Exception;

class Base
{

    private InterBitacora $bitacora;
    private int $id_modulo;
    public bool $incluir;
    public bool $modificar;
    public bool $eliminar;
    public bool $reporte;

    public function __construct(InterBitacora $bitacora)
    {
        $this->bitacora = $bitacora;
    }

    public function ProcesarPermisos(int $id_modulo): void
    {
        $this->incluir = false;
        $this->modificar = false;
        $this->eliminar = false;
        $this->reporte = false;

        if (isset($_SESSION['permisos'][$id_modulo])) {
            $permisos = $_SESSION['permisos'][$id_modulo];
            $this->incluir = $permisos['incluir'] == 1 ? true : false;
            $this->modificar = $permisos['modificar'] == 1 ? true : false;
            $this->eliminar = $permisos['eliminar'] == 1 ? true : false;
            $this->reporte = $permisos['reporte'] == 1 ? true : false;
            $this->id_modulo = $id_modulo;

            $this->Bitacora("Accedio al modulo");

        }else {
            $_SESSION['alerta'] = [
                'icono' => 'error',
                'titulo' => 'Acceso denegado',
                'mensaje' => 'No tienes permisos para acceder a este módulo.'
            ];
            header("Location:"._URL_."Principal");
            exit();
        }
    }

    public function Bitacora($mensaje): void
    {
        // Registrar la acción en la bitácora utilizando el método RegistrarAccion del modelo de bitácora
        $this->bitacora->RegistrarAccion($this->id_modulo, $mensaje, $_SESSION['id']);
    }
}
