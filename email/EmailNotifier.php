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
    
    public function notifyStudentApproved($student_email, $data) {
        $subject = 'Class Card Drop Official Letter - PhilCST';
        $message = $this->buildStudentApprovedEmailBody($data);
        
        return $this->sendEmail($student_email, $subject, $message);
    }
    
    public function notifyTeacherApproved($teacher_email, $data) {
        $subject = 'Class Card Drop APPROVED - PhilCST';
        $message = $this->buildTeacherApprovedEmailBody($data);
        
        return $this->sendEmail($teacher_email, $subject, $message);
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
    
    private function buildStudentApprovedEmailBody($data) {
        $html = "<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #7f3fc6; color: white; padding: 20px; border-radius: 5px 5px 0 0; display: flex; align-items: center; gap: 20px; }
        .logo { width: 80px; height: 80px; }
        .header-content { flex: 1; }
        .header-content h1 { margin: 0; font-size: 24px; }
        .header-content p { margin: 5px 0 0 0; font-size: 14px; }
        .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
        .footer { background-color: #f0f0f0; padding: 10px; border-radius: 0 0 5px 5px; text-align: center; font-size: 12px; }
        .alert { background-color: #e7d4f5; border: 1px solid #7f3fc6; padding: 15px; border-radius: 5px; margin: 15px 0; color: #5a2d82; }
        .field { margin: 15px 0; }
        .label { font-weight: bold; color: #7f3fc6; }
        .value { margin-top: 5px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <img src='https://imgur.com/a/thIeOyO' alt='PhilCST Logo' class='logo'>
            <div class='header-content'>
                <h1>Class Card Drop Official Letter</h1>
                <p>PhilCST - Official Confirmation</p>
            </div>
        </div>
        <div class='content'>
            <p>Dear " . htmlspecialchars($data['student_name']) . ",</p>
            
            <div class='alert'>
                <p><strong>Your class card drop has been officially approved and processed by the admin.</strong></p>
            </div>
            
            <div class='field'>
                <div class='label'>Subject:</div>
                <div class='value'>" . htmlspecialchars($data['subject_no'] . ' - ' . $data['subject_name']) . "</div>
            </div>
            
            <div class='field'>
                <div class='label'>Submitted by:</div>
                <div class='value'>" . htmlspecialchars($data['teacher_name']) . "</div>
            </div>
            
            <div class='field'>
                <div class='label'>Request Date:</div>
                <div class='value'>" . htmlspecialchars($data['drop_date']) . "</div>
            </div>
            
            <p style='margin-top: 20px;'><strong>Status:</strong> Your class card drop is now officially recorded in the system. This subject will no longer appear on your enrollment.</p>
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
    
    private function buildTeacherApprovedEmailBody($data) {
        $html = "<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #28a745; color: white; padding: 20px; border-radius: 5px 5px 0 0; }
        .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
        .footer { background-color: #f0f0f0; padding: 10px; border-radius: 0 0 5px 5px; text-align: center; font-size: 12px; }
        .alert { background-color: #d4edda; border: 1px solid #28a745; padding: 15px; border-radius: 5px; margin: 15px 0; color: #155724; }
        .field { margin: 15px 0; }
        .label { font-weight: bold; color: #28a745; }
        .value { margin-top: 5px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>Class Card Drop APPROVED</h1>
            <p>PhilCST - Official Confirmation</p>
        </div>
        <div class='content'>
            <p>Dear Teacher,</p>
            
            <div class='alert'>
                <p><strong>The class card drop request has been APPROVED by the admin.</strong></p>
            </div>
            
            <div class='field'>
                <div class='label'>Student:</div>
                <div class='value'>" . htmlspecialchars($data['student_name']) . " (" . htmlspecialchars($data['student_id']) . ")</div>
            </div>
            
            <div class='field'>
                <div class='label'>Subject:</div>
                <div class='value'>" . htmlspecialchars($data['subject_no'] . ' - ' . $data['subject_name']) . "</div>
            </div>
            
            <div class='field'>
                <div class='label'>Request Date:</div>
                <div class='value'>" . htmlspecialchars($data['drop_date']) . "</div>
            </div>
            
            <div class='field'>
                <div class='label'>Approval Date:</div>
                <div class='value'>" . htmlspecialchars($data['approved_date']) . "</div>
            </div>
            
            <p style='margin-top: 20px;'><strong>Status:</strong> The class card drop has been officially processed and recorded in the system.</p>
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




