<?php
session_start();
require_once("../conn.php");
require_once("../email_helper.php");

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data["email"])) {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
    exit();
}

$email = trim($data["email"]);

// Check if user exists
$stmt = $conn->prepare("SELECT id, first_name, middle_name, last_name, is_approved FROM user WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "No account found with this email address"]);
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();

// Check if account is approved (fix: check is_approved, not status)
if ($user['is_approved'] !== 'approved') {
    if ($user['is_approved'] === 'pending') {
        echo json_encode(["success" => false, "message" => "Your account is pending approval. Please wait for admin approval before resetting your password."]);
    } else if ($user['is_approved'] === 'rejected') {
        echo json_encode(["success" => false, "message" => "Your account registration was rejected. Please contact the administrator."]);
    } else {
        echo json_encode(["success" => false, "message" => "Your account is not yet approved. Please wait for admin approval."]);
    }
    exit();
}

// Generate 6-digit OTP
$otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$otpExpiry = date('Y-m-d H:i:s', strtotime('+15 minutes')); // OTP expires in 15 minutes

// Store OTP in database
$stmt = $conn->prepare("UPDATE user SET reset_otp = ?, reset_otp_expiry = ? WHERE email = ?");
$stmt->bind_param("sss", $otp, $otpExpiry, $email);

if (!$stmt->execute()) {
    echo json_encode(["success" => false, "message" => "Error generating OTP. Please try again."]);
    exit();
}
$stmt->close();

// Prepare user name
$userName = $user["first_name"];
if (!empty($user["middle_name"])) {
    $userName .= " " . strtoupper(substr($user["middle_name"], 0, 1)) . ".";
}
$userName .= " " . $user["last_name"];

// Send OTP email
if (sendPasswordResetOTP($email, $userName, $otp)) {
    // Log activity
    $activity = "Password Reset OTP Sent";
    $description = "Password reset OTP sent to {$userName} ({$email})";
    $log_stmt = $conn->prepare("INSERT INTO activity_logs (activity, description) VALUES (?, ?)");
    $log_stmt->bind_param("ss", $activity, $description);
    $log_stmt->execute();
    $log_stmt->close();
    
    echo json_encode([
        "success" => true, 
        "message" => "OTP has been sent to your email address"
    ]);
} else {
    echo json_encode([
        "success" => false, 
        "message" => "Failed to send OTP email. Please try again."
    ]);
}

$conn->close();
?>