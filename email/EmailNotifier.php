<?php
// email/EmailNotifier.php - Email Notification Handler

// Load PHPMailer via Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailNotifier {
    
    private $config;
    
    public function __construct() {
        // Load email configuration
        $this->config = require __DIR__ . '/../config/email.php';
    }
    
    public function notifyAdmin($data) {
        $subject = 'Class Card Dropped Notification - PhilCST';
        $message = $this->buildEmailBody($data);
        
        return $this->sendEmail($this->config['admin_email'], $subject, $message);
    }
    
    public function notifyStudent($student_email, $data) {
        $subject = 'Class Card Drop Notification - PhilCST';
        $message = $this->buildStudentEmailBody($data);
        
        return $this->sendEmail($student_email, $subject, $message);
    }
    
    private function sendEmail($recipient_email, $subject, $message) {
        try {
            $mail = new PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = $this->config['smtp_host'];
            $mail->SMTPAuth = $this->config['smtp_auth'];
            $mail->Username = $this->config['username'];
            $mail->Password = $this->config['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->config['smtp_port'];
            
            // Recipients
            $mail->setFrom($this->config['from_email'], $this->config['from_name']);
            $mail->addAddress($recipient_email);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;
            
            // Send
            $mail->send();
            error_log("EMAIL SENT: $subject to $recipient_email");
            return true;
        } catch (Exception $e) {
            error_log("EMAIL ERROR: {$mail->ErrorInfo}");
            
            // Fallback to basic mail function
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: " . $this->config['from_name'] . " <" . $this->config['from_email'] . ">\r\n";
            
            $sent = @mail($recipient_email, $subject, $message, $headers);
            if ($sent) {
                error_log("EMAIL SENT (FALLBACK): $subject to $recipient_email");
            } else {
                error_log("EMAIL FAILED (FALLBACK): $subject to $recipient_email");
            }
            return $sent;
        }
    }
    
    private function buildEmailBody($data) {
        $html = "<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #0066cc; color: white; padding: 20px; border-radius: 5px 5px 0 0; }
        .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
        .footer { background-color: #f0f0f0; padding: 10px; border-radius: 0 0 5px 5px; text-align: center; font-size: 12px; }
        .field { margin: 15px 0; }
        .label { font-weight: bold; color: #0066cc; }
        .value { margin-top: 5px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>Class Card Dropped Notification</h1>
            <p>PhilCST Guidance Office</p>
        </div>
        <div class='content'>
            <p>A class card has been dropped by a teacher. Here are the details:</p>
            
            <div class='field'>
                <div class='label'>Student ID:</div>
                <div class='value'>" . htmlspecialchars($data['student_id']) . "</div>
            </div>
            
            <div class='field'>
                <div class='label'>Student Name:</div>
                <div class='value'>" . htmlspecialchars($data['student_name']) . "</div>
            </div>
            
            <div class='field'>
                <div class='label'>Subject:</div>
                <div class='value'>" . htmlspecialchars($data['subject_no'] . ' - ' . $data['subject_name']) . "</div>
            </div>
            
            <div class='field'>
                <div class='label'>Teacher Name:</div>
                <div class='value'>" . htmlspecialchars($data['teacher_name']) . "</div>
            </div>
            
            <div class='field'>
                <div class='label'>Remarks:</div>
                <div class='value'>" . htmlspecialchars($data['remarks']) . "</div>
            </div>
            
            <div class='field'>
                <div class='label'>Date & Time:</div>
                <div class='value'>" . htmlspecialchars($data['drop_date']) . "</div>
            </div>
        </div>
        <div class='footer'>
            <p>This is an automated email from the PhilCST Class Card Dropping System</p>
        </div>
    </div>
</body>
</html>";
        
        return $html;
    }
    
    private function buildStudentEmailBody($data) {
        $html = "<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #dc3545; color: white; padding: 20px; border-radius: 5px 5px 0 0; }
        .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
        .footer { background-color: #f0f0f0; padding: 10px; border-radius: 0 0 5px 5px; text-align: center; font-size: 12px; }
        .alert { background-color: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .field { margin: 15px 0; }
        .label { font-weight: bold; color: #dc3545; }
        .value { margin-top: 5px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>Class Card Drop Notification</h1>
            <p>PhilCST - Important Notice</p>
        </div>
        <div class='content'>
            <p>Dear " . htmlspecialchars($data['student_name']) . ",</p>
            
            <div class='alert'>
                <p><strong>Your class card has been dropped.</strong> Please read the details below carefully.</p>
            </div>
            
            <div class='field'>
                <div class='label'>Subject:</div>
                <div class='value'>" . htmlspecialchars($data['subject_no'] . ' - ' . $data['subject_name']) . "</div>
            </div>
            
            <div class='field'>
                <div class='label'>Dropped by:</div>
                <div class='value'>" . htmlspecialchars($data['teacher_name']) . "</div>
            </div>
            
            <div class='field'>
                <div class='label'>Reason/Remarks:</div>
                <div class='value'>" . htmlspecialchars($data['remarks']) . "</div>
            </div>
            
            <div class='field'>
                <div class='label'>Date & Time:</div>
                <div class='value'>" . htmlspecialchars($data['drop_date']) . "</div>
            </div>
            
            <p style='margin-top: 20px;'><strong>Next Steps:</strong></p>
            <p>If you have any concerns regarding this class card drop, please contact the guidance office or reach out to the teacher mentioned above.</p>
        </div>
        <div class='footer'>
            <p>This is an automated email from the PhilCST Class Card Dropping System</p>
            <p>PhilCST Guidance Office - Do not reply to this email</p>
        </div>
    </div>
</body>
</html>";
        
        return $html;
    }
}
