<?php
require_once("../../conn/conn.php");

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "Invalid input"]);
    exit();
}

$firstName = trim($data["firstName"] ?? "");
$middleName = trim($data["middleName"] ?? "");
$lastName = trim($data["lastName"] ?? "");
$email = trim($data["email"] ?? "");
$contactNumber = trim($data["contactNumber"] ?? "");

if (empty($firstName) || empty($lastName) || empty($email) || empty($contactNumber)) {
    echo json_encode(["success" => false, "message" => "Required fields are missing"]);
    exit();
}

try {
    $stmt = $conn->prepare("INSERT INTO user (first_name, middle_name, last_name, email, contact_number, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssss", $firstName, $middleName, $lastName, $email, $contactNumber);
    $stmt->execute();

    echo json_encode(["success" => true]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
