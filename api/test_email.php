<?php
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';
require 'config/email_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    // إعدادات السيرفر
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USERNAME;
    $mail->Password   = SMTP_PASSWORD;
    $mail->SMTPSecure = SMTP_SECURE;
    $mail->Port       = SMTP_PORT;

    // معلومات المرسل والمستقبل
    $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
    $mail->addAddress(SMTP_USERNAME, 'اختبار'); // أرسل لنفسك

    // المحتوى
    $mail->isHTML(true);
    $mail->Subject = 'اختبار إرسال البريد من العيادة';
    $mail->Body    = 'تم إرسال هذا البريد بنجاح من سكريبت الاختبار.';

    $mail->send();
    echo 'تم إرسال البريد بنجاح!';
} catch (Exception $e) {
    echo "فشل الإرسال. الخطأ: {$mail->ErrorInfo}";
}
?>
