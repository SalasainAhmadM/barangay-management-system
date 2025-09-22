<?php
session_start();
require_once("../../conn/conn.php");

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

$date_of_birth = trim($data["date_of_birth"] ?? "");
$gender = trim($data["gender"] ?? "");
$civil_status = trim($data["civil_status"] ?? "");
$occupation = trim($data["occupation"] ?? "");
$house_number = trim($data["house_number"] ?? "");
$street_name = trim($data["street_name"] ?? "");
$barangay = trim($data["barangay"] ?? "Baliwasan");

if (empty($date_of_birth) || empty($gender)) {
    echo json_encode(["success" => false, "message" => "Date of Birth and Gender are required."]);
    exit();
}

$user_id = $_SESSION["user_id"];

$stmt = $conn->prepare("UPDATE user 
    SET date_of_birth = ?, gender = ?, civil_status = ?, occupation = ?, 
        house_number = ?, street_name = ?, barangay = ? 
    WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("sssssssi", $date_of_birth, $gender, $civil_status, $occupation, $house_number, $street_name, $barangay, $user_id);
    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "message" => "Database update failed."]);
    }
    $stmt->close();
} else {
    echo json_encode(["success" => false, "message" => "Query preparation failed."]);
}

$conn->close();
?>