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
$stmt = $conn->prepare("SELECT id FROM user WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "No account found"]);
    exit();
}
$stmt->close();

// Update password
$stmt = $conn->prepare("UPDATE user SET password = ? WHERE email = ?");
$stmt->bind_param("ss", $newPassword, $email);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Error resetting password"]);
}

$stmt->close();
$conn->close();
