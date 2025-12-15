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
        <p>Best regards,<br><strong>{$systemName}</strong></p>
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

function sendApprovalNotification($recipientEmail, $recipientName) {
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
        $mail->Subject = 'Account Approved - Welcome to ' . SYSTEM_NAME;
        $mail->Body = generateApprovalEmailTemplate($recipientName);
        $mail->AltBody = generateApprovalEmailPlainText($recipientName);
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Approval email sending failed: " . $e->getMessage());
        return false;
    }
}

function sendRejectionNotification($recipientEmail, $recipientName, $rejectionReason = '') {
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
        $mail->Subject = 'Registration Update - ' . SYSTEM_NAME;
        $mail->Body = generateRejectionEmailTemplate($recipientName, $rejectionReason);
        $mail->AltBody = generateRejectionEmailPlainText($recipientName, $rejectionReason);
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Rejection email sending failed: " . $e->getMessage());
        return false;
    }
}

function generateApprovalEmailTemplate($name) {
    $systemName = SYSTEM_NAME;
    
    return "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
        .success-icon { font-size: 48px; margin-bottom: 10px; }
        .info-box { background: white; padding: 20px; margin: 20px 0; border-radius: 5px; border-left: 4px solid #10b981; }
        .action-button { display: inline-block; padding: 12px 30px; background: #10b981; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; font-weight: bold; }
        .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee; color: #666; font-size: 14px; }
    </style>
</head>
<body>
    <div class='header'>
        <div class='success-icon'>✓</div>
        <h1>Account Approved!</h1>
    </div>
    <div class='content'>
        <p>Dear <strong>{$name}</strong>,</p>
        
        <p>Great news! Your account registration has been <strong>approved</strong> by our administrators.</p>
        
        <div class='info-box'>
            <h3 style='margin-top: 0; color: #10b981;'>What's Next?</h3>
            <p style='margin: 10px 0;'>You can now log in to your account and access all available services:</p>
            <ul style='margin: 10px 0; padding-left: 20px;'>
                <li>Submit service requests</li>
                <li>View announcements and updates</li>
                <li>Access your profile information</li>
                <li>Manage your account settings</li>
            </ul>
        </div>
        
        <p>Welcome to {$systemName}! We're excited to have you as part of our community.</p>
        
        <p>If you have any questions or need assistance, please don't hesitate to contact us.</p>
        
        <p>Best regards,<br><strong>{$systemName} Team</strong></p>
    </div>
    <div class='footer'>
        <p>This is an automated message. Please do not reply to this email.</p>
        <p>&copy; " . date('Y') . " {$systemName}. All rights reserved.</p>
    </div>
</body>
</html>";
}

function generateApprovalEmailPlainText($name) {
    $systemName = SYSTEM_NAME;
    return "Account Approved!

Dear {$name},

Great news! Your account registration has been approved by our administrators.

What's Next?
You can now log in to your account and access all available services:
- Submit service requests
- View announcements and updates
- Access your profile information
- Manage your account settings

Welcome to {$systemName}! We're excited to have you as part of our community.

If you have any questions or need assistance, please don't hesitate to contact us.

Best regards,
{$systemName} Team

---
This is an automated message. Please do not reply to this email.
© " . date('Y') . " {$systemName}. All rights reserved.";
}

function generateRejectionEmailTemplate($name, $reason) {
    $systemName = SYSTEM_NAME;
    $reasonText = !empty($reason) ? "<p><strong>Reason:</strong> {$reason}</p>" : "";
    
    return "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #ef4444; color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
        .info-box { background: #fee2e2; padding: 20px; margin: 20px 0; border-radius: 5px; border-left: 4px solid #ef4444; }
        .action-box { background: white; padding: 20px; margin: 20px 0; border-radius: 5px; border-left: 4px solid #3b82f6; }
        .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee; color: #666; font-size: 14px; }
    </style>
</head>
<body>
    <div class='header'>
        <h1>Registration Update</h1>
    </div>
    <div class='content'>
        <p>Dear <strong>{$name}</strong>,</p>
        
        <p>Thank you for your interest in registering with {$systemName}.</p>
        
        <div class='info-box'>
            <p style='margin: 0;'>Unfortunately, we are unable to approve your registration at this time.</p>
            {$reasonText}
        </div>
        
        <div class='action-box'>
            <h3 style='margin-top: 0; color: #3b82f6;'>You Can Appeal This Decision</h3>
            <p style='margin: 10px 0;'>If you believe this decision was made in error or if you have additional information to provide, you can submit an appeal by:</p>
            <ol style='margin: 10px 0; padding-left: 20px;'>
                <li>Visiting the login page</li>
                <li>Attempting to log in with your email</li>
                <li>Clicking the \"Appeal\" button when prompted</li>
                <li>Updating your information and resubmitting</li>
            </ol>
        </div>
        
        <p>If you have any questions or need further clarification, please don't hesitate to contact us.</p>
        
        <p>Best regards,<br><strong>{$systemName} Team</strong></p>
    </div>
    <div class='footer'>
        <p>This is an automated message. Please do not reply to this email.</p>
        <p>&copy; " . date('Y') . " {$systemName}. All rights reserved.</p>
    </div>
</body>
</html>";
}

function generateRejectionEmailPlainText($name, $reason) {
    $systemName = SYSTEM_NAME;
    $reasonText = !empty($reason) ? "\nReason: {$reason}\n" : "";
    
    return "Registration Update

Dear {$name},

Thank you for your interest in registering with {$systemName}.

Unfortunately, we are unable to approve your registration at this time.
{$reasonText}

You Can Appeal This Decision:
If you believe this decision was made in error or if you have additional information to provide, you can submit an appeal by:
1. Visiting the login page
2. Attempting to log in with your email
3. Clicking the \"Appeal\" button when prompted
4. Updating your information and resubmitting

If you have any questions or need further clarification, please don't hesitate to contact us.

Best regards,
{$systemName} Team

---
This is an automated message. Please do not reply to this email.
© " . date('Y') . " {$systemName}. All rights reserved.";
}

function sendPasswordResetOTP($recipientEmail, $recipientName, $otp) {
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
        $mail->Subject = 'Password Reset OTP - ' . SYSTEM_NAME;
        $mail->Body = generatePasswordResetOTPTemplate($recipientName, $otp);
        $mail->AltBody = generatePasswordResetOTPPlainText($recipientName, $otp);
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("OTP email sending failed: " . $e->getMessage());
        return false;
    }
}

function generatePasswordResetOTPTemplate($name, $otp) {
    $systemName = SYSTEM_NAME;
    $resetLink = "http://localhost:8080/barangay-management-system/reset_password.php";
    
    return "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
        .otp-box { background: white; padding: 30px; margin: 20px 0; border-radius: 10px; border: 2px solid #667eea; text-align: center; }
        .otp-code { font-size: 36px; font-weight: bold; color: #667eea; letter-spacing: 8px; margin: 15px 0; font-family: 'Courier New', monospace; }
        .warning-box { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .action-button { display: inline-block; padding: 15px 40px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; font-weight: bold; font-size: 16px; }
        .action-button:hover { background: #5568d3; }
        .info-text { color: #666; font-size: 14px; margin: 10px 0; }
        .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee; color: #666; font-size: 14px; }
        .security-note { background: #f8f9fa; padding: 15px; margin: 20px 0; border-radius: 5px; border-left: 4px solid #28a745; }
    </style>
</head>
<body>
    <div class='header'>
        <h1>🔐 Password Reset Request</h1>
    </div>
    <div class='content'>
        <p>Dear <strong>{$name}</strong>,</p>
        
        <p>We received a request to reset your password for your {$systemName} account. Use the OTP code below to proceed with your password reset.</p>
        
        <div class='otp-box'>
            <p style='margin: 0; color: #666; font-size: 14px;'>Your One-Time Password (OTP)</p>
            <div class='otp-code'>{$otp}</div>
            <p class='info-text'>This OTP is valid for <strong>15 minutes</strong></p>
        </div>
        
        <div style='text-align: center;'>
            <a href='{$resetLink}' class='action-button'>Reset Password Now</a>
        </div>
        
        <div class='warning-box'>
            <strong>⏱️ Important:</strong>
            <p style='margin: 10px 0 0 0;'>This OTP will expire in 15 minutes. If you did not request this password reset, please ignore this email or contact support if you have concerns.</p>
        </div>
        
        <div class='security-note'>
            <strong>🛡️ Security Tips:</strong>
            <ul style='margin: 10px 0; padding-left: 20px;'>
                <li>Never share your OTP with anyone</li>
                <li>Our team will never ask for your OTP</li>
                <li>Make sure you're on the official website before entering your OTP</li>
            </ul>
        </div>
        
        <p>If you're having trouble with the button above, copy and paste this link into your browser:</p>
        <p style='word-break: break-all; color: #667eea;'>{$resetLink}</p>
        
        <p>Best regards,<br><strong>{$systemName} Team</strong></p>
    </div>
    <div class='footer'>
        <p>This is an automated message. Please do not reply to this email.</p>
        <p>&copy; " . date('Y') . " {$systemName}. All rights reserved.</p>
    </div>
</body>
</html>";
}

function generatePasswordResetOTPPlainText($name, $otp) {
    $systemName = SYSTEM_NAME;
    $resetLink = "http://localhost:8080/barangay-management-system/reset_password.php";
    
    return "Password Reset Request - {$systemName}

Dear {$name},

We received a request to reset your password for your {$systemName} account. Use the OTP code below to proceed with your password reset.

Your One-Time Password (OTP): {$otp}

This OTP is valid for 15 minutes.

Reset your password here: {$resetLink}

Important:
This OTP will expire in 15 minutes. If you did not request this password reset, please ignore this email or contact support if you have concerns.

Security Tips:
- Never share your OTP with anyone
- Our team will never ask for your OTP
- Make sure you're on the official website before entering your OTP

Best regards,
{$systemName} Team

---
This is an automated message. Please do not reply to this email.
© " . date('Y') . " {$systemName}. All rights reserved.";
}

?>

