<?php

namespace App\Modelo;

use App\Modelo\Conexion;
use PHPMailer\PHPMailer\PHPMailer;
use Exception;

use function Safe\error_log;

class ModeloRecuperacion extends Conexion
{

    private $codigo;
    private $cedula;
    private $contraseña;

    private $conexion = null;

    public function __construct() {}

    public function ProcesarDatos(array $datos) : array
    {
        if (empty($datos)) {
            throw new Exception('No se proporcionaron datos para procesar.');
        }

        $this->codigo = $datos['codigo'] ?? '';
        $this->cedula = trim($datos['cedula'] ?? '');
        if(!empty($datos['contraseña'])) {
            $this->contraseña = password_hash($datos['contraseña'], PASSWORD_BCRYPT);
        }

        $accion = $datos['accion'] ?? null;

        return match ($accion) {
            'comprobar' => $this->ComprobarCedula(),
            'reenviar' => $this->Reenviar(),
            'comprobarCodigo' => $this->ComprobarCodigo(),
            'cambiar' => $this->CambiarContraseña(),
            default => throw new Exception('La accion no es valida.')
        };
    }

    private function ComprobarCedula(): array
    {
        try {
            $this->conexion = self::conexSG();
            $this->conexion->beginTransaction();
            $sql = "SELECT usuarios.correo, usuarios.nombreUsuario, usuarios.apellidoUsuario 
            FROM `usuarios` WHERE usuarios.cedulaUsuario=:cedula AND usuarios.estatus=1";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindParam(':cedula', $this->cedula, \PDO::PARAM_STR);
            $stmt->execute();
            $respuesta = $stmt->fetch();
            if ($respuesta) {
                $this->conexion->commit();
                $_SESSION['correo'] = $respuesta['correo'];
                $_SESSION['destinatario'] = $respuesta['nombreUsuario'] . ' ' . $respuesta['apellidoUsuario'];
                $_SESSION['cedula_r'] = $this->cedula;
                $_SESSION['verificacion'] = false;
                return $this->enviarCorreo('Se envio un código a su correo.');
            } else {
                $this->conexion->rollBack();
                return ['accion' => 'error', 'mensaje' => 'Cedula no encontrada.'];
            }
        } catch (Exception $e) {
            if ($this->conexion && $this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            error_log('Error en ComprobarCedula: ' . $e->getMessage());
            return ['accion' => 'error', 'mensaje' => $e->getMessage()];
        } finally {
            $this->conexion = null;
        }
    }

    public function Reenviar() : array
    {
        try {
            return $this->enviarCorreo('Se le envio otro código al correo.');
        } catch (Exception $e) {
            error_log($e->getMessage());
            return ['accion' => 'error', 'mensaje' => $e->getMessage()];
        }
    }

    public function ComprobarCodigo() : array
    {
        try {
            if ($this->codigo == $_SESSION['codigo_verificacion']) {
                unset($_SESSION['codigo']);
                unset($_SESSION['destinatario']);
                unset($_SESSION['correo']);
                $_SESSION['verificacion'] = true;
                return ['accion' => 'comprobarCodigo', 'mensaje' => 'Código Validado.'];
            } else {
                return ['accion' => 'error', 'mensaje' => 'el código no es correcto.'];
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            return ['accion' => 'error', 'mensaje' => $e->getMessage()];
        }
    }

    public function CambiarContraseña() : array
    {
        try {

            if (!$_SESSION['verificacion']) {
                return ['accion' => 'error', 'mensaje' => 'No esta verificado.'];
            }
            $cedula_r = $_SESSION['cedula_r'];
            $sentencia = "UPDATE `usuarios` SET 
                            `contraseña` = :contra
                            WHERE cedulaUsuario = :cedula";

            $this->conexion = self::conexSG();
            $this->conexion->beginTransaction();
            $stmt = $this->conexion->prepare($sentencia);
            $stmt->bindParam(':cedula', $cedula_r);
            $stmt->bindParam(':contra', $this->contraseña);
            $respuesta = $stmt->execute();

            if ($respuesta) {
                $this->conexion->commit();
                unset($_SESSION['verificacion']);
                unset($_SESSION['cedula_r']);
                return ['accion' => 'cambiar', 'mensaje' => 'Contraseña modificada correctamente.', 'url' => _URL_];
            } else {
                $this->conexion->rollBack();
                return ['accion' => 'error', 'mensaje' => 'Error al modificar la contraseña.'];
            }
        } catch (Exception $e) {
            if ($this->conexion && $this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            error_log($e->getMessage());
            $resultado = ['accion' => 'error', 'mensaje' => $e->getMessage()];
        }
        return $resultado;
    }

    private function enviarCorreo($mensaje) : array
    {
        $mail = new PHPMailer(true);
        try {
            $codigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT); // Asegura que tenga 6 dígitos
            $correo = $_SESSION['correo'];
            $destinatario = $_SESSION['destinatario'];
            // 2. Configurar el servidor SMTP
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'moitcj@gmail.com';            // Cambia por tu correo real
            $mail->Password   = 'dcotyzvsafgxnfjt';      // Usa una clave de aplicación si usas Gmail
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;

            // 3. Configurar remitente y destinatario
            $mail->setFrom('moitcj@gmail.com', 'Moises Torrellas');
            $mail->addAddress($correo, $destinatario);

            // 4. Contenido del mensaje
            $mail->isHTML(true);
            $mail->Subject = 'Tu codigo de verificacion';
            $mail->Body    = "<p>Tu código de verificación es:</p><h2>$codigo</h2>";
            $mail->AltBody = "Tu código de verificación es: $codigo";

            // 5. Enviar correo
            $mail->send();
            $_SESSION['codigo_verificacion'] = $codigo;
            return ['accion' => 'comprobar', 'mensaje' => $mensaje];
        } catch (Exception $e) {
            error_log($e->getMessage());
            return ['accion' => 'error', 'mensaje' => $e->getMessage()];
        }
    }
}
