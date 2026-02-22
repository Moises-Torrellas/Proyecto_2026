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
    public bool $otros;

    public function __construct(InterBitacora $bitacora, int $id_modulo)
    {
        $this->bitacora = $bitacora;
        $this->id_modulo = $id_modulo;
    }

    public function ProcesarPermisos(): void
    {
        $this->incluir = false;
        $this->modificar = false;
        $this->eliminar = false;
        $this->reporte = false;

        if (isset($_SESSION['permisos'][$this->id_modulo])) {
            $permisos = $_SESSION['permisos'][$this->id_modulo];
            $this->incluir = $permisos['incluir'] == 1 ? true : false;
            $this->modificar = $permisos['modificar'] == 1 ? true : false;
            $this->eliminar = $permisos['eliminar'] == 1 ? true : false;
            $this->reporte = $permisos['reporte'] == 1 ? true : false;
            $this->otros = $permisos['otros'] == 1 ? true : false;
            if(!$this->ComprobarAjax()){
                $this->Bitacora("Accedio al modulo");
            }
        } else {
            $_SESSION['alerta'] = [
                'icono' => 'error',
                'titulo' => 'Acceso denegado',
                'mensaje' => 'No tienes permisos para acceder a este módulo.'
            ];
            header("Location:" . _URL_ . "Principal");
            exit();
        }
    }

    public function CargarVista(string $pagina): void
    {
        $archivoVista = sprintf(__DIR__ . '/../vista/%s.php', $pagina);
        if (is_file($archivoVista)) {
            // Si no es una solicitud AJAX, generar un token de seguridad para la sesión y cargar el archivo de vista
            $_SESSION['token'] = bin2hex(random_bytes(32));
            // Cargar el archivo de vista correspondiente a la página
            require_once($archivoVista);
        } else {
            // Si el archivo de vista no existe, mostrar una página de error 404
            require_once(__DIR__ . '/../vista/complementos/404.php');
            exit();
        }
    }

    public function ComprobarAjax(): bool
    {
        // Verificar si la solicitud es una solicitud AJAX comprobando el encabezado HTTP_X_REQUESTED_WITH
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    public function Bitacora($mensaje): void
    {
        // Registrar la acción en la bitácora utilizando el método RegistrarAccion del modelo de bitácora
        $this->bitacora->RegistrarAccion($this->id_modulo, $mensaje, $_SESSION['id']);
    }
}
