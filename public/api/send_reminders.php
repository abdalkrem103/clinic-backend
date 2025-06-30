<?php
// Set the timezone to your clinic's timezone to ensure date calculations are correct
date_default_timezone_set('Asia/Riyadh');

require_once 'config.php';
require_once 'email_sender.php';

// A simple logging function for this script
function log_reminder_status($message) {
    $logFile = __DIR__ . '/reminder_log.txt'; // Log file in the same directory
    $timestamp = date('Y-m-d H:i:s');
    // Prepend the timestamp to the message and append a newline
    file_put_contents($logFile, "[$timestamp] " . $message . "\n", FILE_APPEND);
}

log_reminder_status("--- Reminder Script Started ---");

try {
    // Get tomorrow's date in YYYY-MM-DD format
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    log_reminder_status("Searching for confirmed appointments for date: " . $tomorrow);

    // Prepare SQL to find all confirmed appointments for tomorrow
    $stmt = $pdo->prepare("
        SELECT 
            a.id AS appointment_id,
            a.appointment_date,
            a.appointment_time,
            CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
            p.email AS patient_email,
            d.name AS doctor_name
        FROM 
            appointments a
        JOIN 
            patients p ON a.patient_id = p.id
        JOIN 
            doctors d ON a.doctor_id = d.id
        WHERE 
            a.appointment_date = :tomorrow_date
            AND a.status = 'confirmed'
    ");

    $stmt->execute(['tomorrow_date' => $tomorrow]);
    $appointments_to_remind = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($appointments_to_remind)) {
        log_reminder_status("No appointments found for tomorrow. Script finished.");
        echo "No confirmed appointments for tomorrow.\n";
        exit;
    }

    log_reminder_status("Found " . count($appointments_to_remind) . " appointments. Starting to send emails...");

    foreach ($appointments_to_remind as $appointment) {
        $patient_name = $appointment['patient_name'];
        $patient_email = $appointment['patient_email'];
        $doctor_name = $appointment['doctor_name'];
        $appointment_time = date('h:i A', strtotime($appointment['appointment_time'])); // Format time to 12-hour AM/PM
        $appointment_date = date('d-m-Y', strtotime($appointment['appointment_date']));

        log_reminder_status("Preparing reminder for appointment ID: {$appointment['appointment_id']} for patient: {$patient_name}");

        $email_subject = 'تذكير بموعدك غداً في عيادتنا';
        $email_body = "
            <html dir='rtl'>
            <body style='font-family: Arial, sans-serif; text-align: right;'>
                <h2>تذكير بموعدك الهام</h2>
                <p>مرحباً <strong>{$patient_name}</strong>،</p>
                <p>نود تذكيرك بلطف بموعدك القادم غداً في عيادتنا.</p>
                <p>تفاصيل الموعد:</p>
                <ul>
                    <li><strong>الطبيب:</strong> {$doctor_name}</li>
                    <li><strong>التاريخ:</strong> {$appointment_date}</li>
                    <li><strong>الوقت:</strong> {$appointment_time}</li>
                </ul>
                <p>نرجو منك الحضور قبل الموعد بـ 10 دقائق لإتمام إجراءات الدخول. إذا كنت بحاجة لإعادة جدولة أو إلغاء الموعد، يرجى الاتصال بنا في أقرب وقت ممكن.</p>
                <p>نتطلع لرؤيتك غداً!</p>
                <p>مع أطيب التحيات،<br>فريق عيادة الأسنان المتخصصة</p>
            </body>
            </html>
        ";

        // Send the email
        $email_result = sendEmail($patient_email, $email_subject, $email_body);

        if ($email_result['status'] === 'success') {
            log_reminder_status("Successfully sent reminder to {$patient_email} for appointment ID: {$appointment['appointment_id']}");
        } else {
            log_reminder_status("FAILED to send reminder to {$patient_email}. Reason: {$email_result['message']}");
        }
    }

    log_reminder_status("--- Reminder Script Finished ---");
    echo "Reminder process completed.\n";

} catch (PDOException $e) {
    $error_message = "Database Error: " . $e->getMessage();
    log_reminder_status($error_message);
    // In a cron job context, printing the error is useful for debugging
    die($error_message);
} catch (Exception $e) {
    $error_message = "A general error occurred: " . $e->getMessage();
    log_reminder_status($error_message);
    die($error_message);
}
?> 