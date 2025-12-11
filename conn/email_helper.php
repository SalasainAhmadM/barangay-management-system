<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Manual PHPMailer includes
require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

function sendRegistrationNotification($recipientEmail, $recipientName, $userDetails) {
    try {
        require_once __DIR__ . '/email_config.php';
        
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        $mail->Timeout    = 10;
        
        // Disable SSL certificate verification
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        // Recipients
        $mail->setFrom(FROM_EMAIL, FROM_NAME);
        $mail->addAddress($recipientEmail, $recipientName);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Registration Successful - Pending Approval';
        $mail->Body = generateRegistrationEmailTemplate($recipientName, $userDetails);
        $mail->AltBody = generateRegistrationEmailPlainText($recipientName, $userDetails);
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return false;
    }
}

function sendAdminNewRegistrationNotification($userDetails) {
    try {
        require_once __DIR__ . '/email_config.php';
        
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        $mail->Timeout    = 10;
        
        // Disable SSL certificate verification
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        // Recipients
        $mail->setFrom(FROM_EMAIL, FROM_NAME);
        $mail->addAddress(ADMIN_EMAIL, 'Administrator');
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'New User Registration - Pending Approval';
        $mail->Body = generateAdminNotificationTemplate($userDetails);
        $mail->AltBody = generateAdminNotificationPlainText($userDetails);
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Admin email sending failed: " . $e->getMessage());
        return false;
    }
}

function generateRegistrationEmailTemplate($name, $details) {
    $systemName = SYSTEM_NAME;
    
    return "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
        .info-box { background: white; padding: 20px; margin: 20px 0; border-radius: 5px; border-left: 4px solid #667eea; }
        .info-row { margin: 10px 0; padding: 8px 0; border-bottom: 1px solid #eee; }
        .info-label { font-weight: bold; color: #667eea; display: inline-block; width: 140px; }
        .status-badge { display: inline-block; padding: 5px 15px; background: #ffc107; color: #000; border-radius: 20px; font-weight: bold; font-size: 14px; }
        .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee; color: #666; font-size: 14px; }
        .warning-box { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 5px; }
    </style>
</head>
<body>
    <div class='header'><h1>Welcome to {$systemName}!</h1></div>
    <div class='content'>
        <p>Dear <strong>{$name}</strong>,</p>
        <p>Thank you for registering with us! Your account has been successfully created and is now pending approval.</p>
        <div class='info-box'>
            <h3 style='margin-top: 0; color: #667eea;'>Registration Details</h3>
            <div class='info-row'><span class='info-label'>Name:</span><span>{$details['full_name']}</span></div>
            <div class='info-row'><span class='info-label'>Email:</span><span>{$details['email']}</span></div>
            <div class='info-row'><span class='info-label'>Contact:</span><span>{$details['contact']}</span></div>
            <div class='info-row'><span class='info-label'>Address:</span><span>{$details['address']}</span></div>
            <div class='info-row'><span class='info-label'>Status:</span><span class='status-badge'>Pending Approval</span></div>
        </div>
        <div class='warning-box'>
            <strong>⏳ What's Next?</strong>
            <p style='margin: 10px 0 0 0;'>Your account is currently under review by our administrators. You will receive another email notification once your account has been approved. This process typically takes 24-48 hours.</p>
        </div>
        <p><strong>Important:</strong> Please do not attempt to log in until you receive approval confirmation.</p>
        <p>If you have any questions or concerns, please don't hesitate to contact us.</p>
        <p>Best regards,<br><strong>{$systemName} Team</strong></p>
    </div>
    <div class='footer'>
        <p>This is an automated message. Please do not reply to this email.</p>
        <p>&copy; " . date('Y') . " {$systemName}. All rights reserved.</p>
    </div>
</body>
</html>";
}

function generateRegistrationEmailPlainText($name, $details) {
    $systemName = SYSTEM_NAME;
    return "Welcome to {$systemName}!

Dear {$name},

Thank you for registering with us! Your account has been successfully created and is now pending approval.

Registration Details:
- Name: {$details['full_name']}
- Email: {$details['email']}
- Contact: {$details['contact']}
- Address: {$details['address']}
- Status: Pending Approval

What's Next?
Your account is currently under review by our administrators. You will receive another email notification once your account has been approved. This process typically takes 24-48 hours.

Important: Please do not attempt to log in until you receive approval confirmation.

If you have any questions or concerns, please don't hesitate to contact us.

Best regards,
{$systemName} Team

---
This is an automated message. Please do not reply to this email.
© " . date('Y') . " {$systemName}. All rights reserved.";
}

function generateAdminNotificationTemplate($details) {
    $systemName = SYSTEM_NAME;
    return "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #dc3545; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
        .info-box { background: white; padding: 20px; margin: 20px 0; border-radius: 5px; border-left: 4px solid #dc3545; }
        .info-row { margin: 10px 0; padding: 8px 0; border-bottom: 1px solid #eee; }
        .info-label { font-weight: bold; color: #dc3545; display: inline-block; width: 140px; }
    </style>
</head>
<body>
    <div class='header'><h2>🔔 New User Registration</h2></div>
    <div class='content'>
        <p><strong>A new user has registered and is awaiting approval.</strong></p>
        <div class='info-box'>
            <h3 style='margin-top: 0; color: #dc3545;'>User Details</h3>
            <div class='info-row'><span class='info-label'>Name:</span><span>{$details['full_name']}</span></div>
            <div class='info-row'><span class='info-label'>Email:</span><span>{$details['email']}</span></div>
            <div class='info-row'><span class='info-label'>Contact:</span><span>{$details['contact']}</span></div>
            <div class='info-row'><span class='info-label'>Address:</span><span>{$details['address']}</span></div>
            <div class='info-row'><span class='info-label'>Gov ID Type:</span><span>{$details['gov_id_type']}</span></div>
            <div class='info-row'><span class='info-label'>Registration Date:</span><span>{$details['registration_date']}</span></div>
        </div>
        <p>Please log in to the admin panel to review and approve this registration.</p>
        <p>Best regards,<br><strong>{$systemName} System</strong></p>
    </div>
</body>
</html>";
}

function generateAdminNotificationPlainText($details) {
    $systemName = SYSTEM_NAME;
    return "New User Registration - {$systemName}

A new user has registered and is awaiting approval.

User Details:
- Name: {$details['full_name']}
- Email: {$details['email']}
- Contact: {$details['contact']}
- Address: {$details['address']}
- Gov ID Type: {$details['gov_id_type']}
- Registration Date: {$details['registration_date']}

Please log in to the admin panel to review and approve this registration.

Best regards,
{$systemName} System";
}
?>