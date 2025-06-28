<?php
require_once 'config/email_config.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Sends an email using PHPMailer
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $body Email body (HTML supported)
 * @param string $altBody Plain text version of the email body
 * @return array Response with status and message
 */
function sendEmail($to, $subject, $body, $altBody = '') {
    try {
        $mail = new PHPMailer(true);

        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';

        // Recipients
        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = $altBody ?: strip_tags($body);

        $mail->send();
        
        return [
            'status' => 'success',
            'message' => 'تم إرسال البريد الإلكتروني بنجاح'
        ];
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => 'فشل في إرسال البريد الإلكتروني: ' . $mail->ErrorInfo
        ];
    }
}

// Example usage:
/*
$result = sendEmail(
    'recipient@example.com',
    'عنوان البريد الإلكتروني',
    '<h1>مرحباً</h1><p>هذا محتوى البريد الإلكتروني</p>',
    'مرحباً، هذا محتوى البريد الإلكتروني'
);

if ($result['status'] === 'success') {
    echo $result['message'];
} else {
    echo $result['message'];
}
*/ 