<?php
session_start();
require_once("../conn.php");

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data["email"]) || !isset($data["otp"]) || !isset($data["password"])) {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
    exit();
}

$email = trim($data["email"]);
$otp = trim($data["otp"]);
$newPassword = password_hash($data["password"], PASSWORD_BCRYPT);

// Verify OTP
$stmt = $conn->prepare("SELECT id, first_name, middle_name, last_name, reset_otp, reset_otp_expiry FROM user WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "No account found"]);
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();

// Check if OTP exists
if (empty($user['reset_otp']) || empty($user['reset_otp_expiry'])) {
    echo json_encode(["success" => false, "message" => "No OTP found. Please request a new one."]);
    exit();
}

// Check if OTP has expired
if (strtotime($user['reset_otp_expiry']) < time()) {
    echo json_encode(["success" => false, "message" => "OTP has expired. Please request a new one."]);
    exit();
}

// Verify OTP
if ($user['reset_otp'] !== $otp) {
    echo json_encode(["success" => false, "message" => "Invalid OTP. Please try again."]);
    exit();
}

// Update password and clear OTP
$stmt = $conn->prepare("UPDATE user SET password = ?, reset_otp = NULL, reset_otp_expiry = NULL WHERE email = ?");
$stmt->bind_param("ss", $newPassword, $email);

if ($stmt->execute()) {
    // Prepare user name
    $userName = $user["first_name"];
    if (!empty($user["middle_name"])) {
        $userName .= " " . strtoupper(substr($user["middle_name"], 0, 1)) . ".";
    }
    $userName .= " " . $user["last_name"];

    // Log activity
    $activity = "Password Reset Successful";
    $description = "User {$userName} ({$email}) has successfully reset their password.";
    $log_stmt = $conn->prepare("INSERT INTO activity_logs (activity, description) VALUES (?, ?)");
    $log_stmt->bind_param("ss", $activity, $description);
    $log_stmt->execute();
    $log_stmt->close();

    echo json_encode(["success" => true, "message" => "Password reset successfully!"]);
} else {
    echo json_encode(["success" => false, "message" => "Error resetting password. Please try again."]);
}

$stmt->close();
$conn->close();
?>