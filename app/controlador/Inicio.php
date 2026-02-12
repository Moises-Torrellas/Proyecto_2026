<?php

namespace App\controlador;

use App\interface\InterBitacora;
use App\modelo\ModeloInicio;
use Exception;

class Inicio
{
    private InterBitacora $bitacora;

    public function __construct(InterBitacora $bitacora) // inyeccion de la bitacora
    {
        $this->bitacora = $bitacora; // Asignar la instancia de la bitácora al controlador
    }

    public function ProcesarSolicitud(string $pagina): void //funcion principal del controlador, recibe el nombre de la pagina a cargar
    {
        $nombreClase = 'App\modelo\ModeloInicio'; // Construir el nombre completo de la clase del modelo correspondiente a la página
        // Verificar si la clase del modelo existe
        if (!class_exists($nombreClase)) {
            // Si la clase del modelo no existe, mostrar una página de error 404
            require_once(__DIR__ . '/../vista/complementos/404.php');
            exit();
        } else {
            // Si la clase del modelo existe, crear una instancia de la clase del modelo
            $obj = new ModeloInicio();
            // Construir la ruta del archivo de vista correspondiente a la página
            $archivoVista = sprintf(__DIR__ . '/../vista/%s.php', $pagina);
            if (is_file($archivoVista)) {
                // Si el archivo de vista existe, verificar si la solicitud es una solicitud AJAX y si se han enviado datos por POST
                if ($this->ComprobarAjax() && !empty($_POST)) {
                    $this->ManejarSolicitud($obj); // Manejar la solicitud AJAX utilizando el objeto del modelo
                } else {
                    // Si no es una solicitud AJAX, generar un token de seguridad para la sesión y cargar el archivo de vista
                    $_SESSION['token'] = bin2hex(random_bytes(32));
                    // Cargar el archivo de vista correspondiente a la página
                    require_once($archivoVista);
                }
            } else {
                // Si el archivo de vista no existe, mostrar una página de error 404
                require_once(__DIR__ . '/../vista/complementos/404.php');
                exit();
            }
        }
    }

    private function ComprobarAjax(): bool
    {
        // Verificar si la solicitud es una solicitud AJAX comprobando el encabezado HTTP_X_REQUESTED_WITH
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    private function ManejarSolicitud($obj): void
    {
        try {
            // Validar el token de seguridad para proteger contra ataques CSRF
            $tokenRecibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            
            if (!hash_equals($_SESSION['token'], $tokenRecibido)) {
                throw new Exception('Error de seguridad: Token inválido o expirado.');
            }
            // Manejar la solicitud AJAX utilizando el objeto del modelo
            $accion = isset($_POST['accion']) ? filter_var($_POST['accion'], FILTER_SANITIZE_SPECIAL_CHARS) : '';
            switch ($accion) {
                case 'inicio':
                    $this->InicioSesion($obj); // Manejar la acción de inicio de sesión utilizando el objeto del modelo
                    break;
            }
            exit();
        } catch (Exception $e) {
            echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
        }
    }

    private function InicioSesion($obj): void
    {
        try {
            // Validar los datos de entrada utilizando el método ValidarDatos
            $this->ValidarDatos($_POST['cedula'], $_POST['contraseña']);
            $datos = [
                'cedula' => $_POST['cedula'],
                'clave' => $_POST['contraseña']
            ]; // Procesar los datos utilizando el método ProcesarDatos del modelo
            $respuesta = $obj->ProcesarDatos($datos);

            // Si el inicio de sesión es exitoso, almacenar la información del usuario en la sesión y registrar la acción en la bitácora
            if ($respuesta['resultado'] == 1) {
                $_SESSION['id'] = $respuesta['datos']['idUsuario'];
                $_SESSION['rol'] = $respuesta['datos']['nombre_rol'];
                $_SESSION['nombre'] = $respuesta['datos']['nombreUsuario'];
                $_SESSION['apellido'] = $respuesta['datos']['apellidoUsuario'];
                $_SESSION['permisos'] = $respuesta['permisos'];
                $this->Bitacora('Inicio de sesión exitoso');
            }
            echo json_encode($respuesta);
        } catch (Exception $e) {
            echo json_encode(['accion' => 'error', 'mensaje' => $e->getMessage()]);
        }
    }

    private function ValidarDatos($cedula, $clave): void
    {
        // Validar los datos de entrada utilizando expresiones regulares y reglas de validación
        if (empty($cedula) || empty($clave)) {
            throw new Exception('Todos los campos son obligatorios.');
        } else if (!preg_match('/^[0-9]{7,8}$/', $cedula)) {
            throw new Exception('La cédula debe tener entre 7 y 8 dígitos.');
        } else if (!preg_match('/^(?=.*[0-9])(?=.*[A-Z])(?=.*[a-z])(?=.*[!@#\$%\^\&*\)\(+=._-])[0-9A-Za-z!@#\$%\^\&*\)\(+=._-]{8,20}$/', $clave)) {
            throw new Exception('Contraseña inválida. Debe tener entre 8 y 20 caracteres y contener al menos una mayúscula, una minúscula, un número y un símbolo especial.');
        }
    }

    private function Bitacora($mensaje): void
    {
        // Registrar la acción en la bitácora utilizando el método RegistrarAccion del modelo de bitácora
        $this->bitacora->RegistrarAccion(_MD_INICIO_, $mensaje, $_SESSION['id']);
    }
}
