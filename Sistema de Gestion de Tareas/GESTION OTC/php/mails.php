<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';

function enviarNotificacionCorreo($destinatario, $descripcion, $fecha_solicitud, $plazoentrega) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'nicolasmaciel@gestordedatosotceprueba.es';
        $mail->Password = 'Xkukz2ur.'; // ⚠️ Recomendado mover esto a una variable de entorno
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('nicolasmaciel@gestordedatosotceprueba.es', 'Notificaciones OTCE');
        $mail->addAddress($destinatario);

        $mail->isHTML(false);
        $mail->Subject = '🔔 Nueva notificacion';

        $mail->Body = <<<EOT
Tienes una nueva notificación en el sitio: https://otcegestion.seguridadciudad.gob.ar

Descripción de la tarea: {$descripcion}
Fecha de solicitud: {$fecha_solicitud}
Plazo de entrega: {$plazoentrega}

Gracias.
EOT;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Error al enviar correo: ' . $mail->ErrorInfo);
        return false;
    }
}
