<?php
require_once("../../conn/conn.php");

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "Invalid input"]);
    exit();
}

$id = intval($data["id"] ?? 0);

if ($id <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid resident ID"]);
    exit();
}

try {
    // Check if resident exists
    $check = $conn->prepare("SELECT id FROM user WHERE id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Resident not found"]);
        exit();
    }

    // Delete resident
    $stmt = $conn->prepare("DELETE FROM user WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    echo json_encode(["success" => true]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
