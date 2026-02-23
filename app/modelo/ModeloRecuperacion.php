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

    public function ProcesarDatos(array $datos): array
    {
        if (empty($datos)) {
            throw new Exception('No se proporcionaron datos para procesar.');
        }

        $this->codigo = $datos['codigo'] ?? '';
        $this->cedula = trim($datos['cedula'] ?? '');
        if (!empty($datos['contraseña'])) {
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

    public function Reenviar(): array
    {
        try {
            return $this->enviarCorreo('Se le envio otro código al correo.');
        } catch (Exception $e) {
            error_log($e->getMessage());
            return ['accion' => 'error', 'mensaje' => $e->getMessage()];
        }
    }

    public function ComprobarCodigo(): array
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

    public function CambiarContraseña(): array
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

    private function enviarCorreo($mensaje): array
    {
        $mail = new PHPMailer(true);

        try {
            // 1. Generar código y obtener datos de sesión
            $codigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $correo = $_SESSION['correo'];
            $destinatario = $_SESSION['destinatario'];

            // 2. Configuración del Servidor SMTP (Gmail)
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'soporte.sigmasell@gmail.com'; // Tu cuenta de Gmail
            $mail->Password   = 'tfutvwetzdkevpwu';           // Tu Contraseña de Aplicación (16 letras)
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;   // SSL para puerto 465
            $mail->Port       = 465;
            $mail->CharSet    = 'UTF-8';                       // Asegura tildes y eñes

            // 3. Remitente y Destinatario
            $mail->setFrom('soporte.sigmasell@gmail.com', 'Soporte de SigmaSell');
            $mail->addAddress($correo, $destinatario);

            // 4. Diseño del Cuerpo del Correo (HTML)
            $mail->isHTML(true);
            $mail->Subject = 'Código de Verificación - SigmaSell';

            // Estilos usando tus variables: --color-primario: #0041f2, --fondo-principal: #f2f3f5, etc.
            $mail->Body = "
        <div style='background-color: #f2f3f5; padding: 40px; font-family: sans-serif;'>
            <div style='max-width: 500px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1);'>
                
                <div style='background-color: #0041f2; padding: 25px; text-align: center;'>
                    <h1 style='color: #ffffff; margin: 0; font-size: 26px; letter-spacing: 1px;'>SIGMASELL</h1>
                </div>

                <div style='padding: 35px; text-align: center; color: #3a4750;'>
                    <h2 style='color: #0041f2; margin-top: 0;'>Verificación de Seguridad</h2>
                    <p style='font-size: 16px; line-height: 1.6; color: #3a4750;'>Hola, <strong>$destinatario</strong>.</p>
                    <p style='font-size: 14px; color: #555;'>Has solicitado acceso o un cambio de contraseña en SigmaSell. Por favor, utiliza el siguiente código para continuar:</p>
                    
                    <div style='margin: 30px 0; padding: 20px; background-color: #e0e3e7; border-radius: 8px; border: 1px dashed #92c5ff;'>
                        <span style='font-size: 36px; font-weight: bold; letter-spacing: 8px; color: #0041f2;'>$codigo</span>
                    </div>
                    
                    <p style='font-size: 12px; color: #92c5ff; margin-top: 25px;'>
                        Este código es de un solo uso y expirará pronto por motivos de seguridad.
                    </p>
                </div>

                <div style='background-color: #f9f9f9; padding: 20px; text-align: center; border-top: 1px solid #e0e3e7;'>
                    <p style='font-size: 11px; color: #3a4750; margin: 0; line-height: 1.4;'>
                        ¿No solicitaste este código? Puedes ignorar este mensaje.<br>
                        <strong>SigmaSell System</strong> - Gestión de Ventas Inteligente
                    </p>
                </div>
            </div>
        </div>
        ";

            // Versión en texto plano para dispositivos que no soportan HTML
            $mail->AltBody = "SigmaSell: Tu código de verificación es $codigo. Si no solicitaste este cambio, ignora este mensaje.";

            // 5. Envío y Respuesta
            $mail->send();
            $_SESSION['codigo_verificacion'] = $codigo;

            return ['accion' => 'comprobar', 'mensaje' => $mensaje];
        } catch (Exception $e) {
            error_log("Error en PHPMailer: " . $e->getMessage());
            return ['accion' => 'error', 'mensaje' => "No se pudo enviar el correo. Error: {$mail->ErrorInfo}"];
        }
    }
}
