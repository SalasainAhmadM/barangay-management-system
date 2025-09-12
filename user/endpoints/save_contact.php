<?php
session_start();
require_once("../../conn/conn.php");

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);
$contact = trim($data["contact"] ?? "");

if (empty($contact)) {
    echo json_encode(["success" => false, "message" => "Contact number is required."]);
    exit();
}

$user_id = $_SESSION["user_id"];

$stmt = $conn->prepare("UPDATE user SET contact_number = ? WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("si", $contact, $user_id);
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