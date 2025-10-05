<?php
session_start();
require_once("../conn.php");

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data["email"]) || !isset($data["password"])) {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
    exit();
}

$email = trim($data["email"]);
$newPassword = password_hash($data["password"], PASSWORD_BCRYPT);

// Check user
$stmt = $conn->prepare("SELECT id, first_name, middle_name, last_name FROM user WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "No account found"]);
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();

// Update password
$stmt = $conn->prepare("UPDATE user SET password = ? WHERE email = ?");
$stmt->bind_param("ss", $newPassword, $email);

if ($stmt->execute()) {

    $userName = $user["first_name"];
    if (!empty($user["middle_name"])) {
        $userName .= " " . strtoupper(substr($user["middle_name"], 0, 1)) . ".";
    }
    $userName .= " " . $user["last_name"];

    $activity = "Password Reset";
    $description = "User {$userName} ({$email}) has successfully reset their password.";
    $log_stmt = $conn->prepare("INSERT INTO activity_logs (activity, description) VALUES (?, ?)");
    $log_stmt->bind_param("ss", $activity, $description);
    $log_stmt->execute();
    $log_stmt->close();

    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Error resetting password"]);
}

$stmt->close();
$conn->close();
?>