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
    $check = $conn->prepare("SELECT id, first_name, middle_name, last_name FROM user WHERE id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Resident not found"]);
        exit();
    }

    $resident = $result->fetch_assoc();
    $residentName = $resident["first_name"] . " " . $resident["middle_name"] . " " . $resident["last_name"];

    // Delete resident
    $stmt = $conn->prepare("DELETE FROM user WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        // Log the activity
        $activity = "Deleted a Resident";
        $log_description = "Removed resident '{$residentName}' (ID: {$id}) from the system.";

        $log_stmt = $conn->prepare("INSERT INTO activity_logs ( activity, description, created_at) VALUES (?, ?, NOW())");
        $log_stmt->bind_param("ss", $activity, $log_description);
        $log_stmt->execute();
        $log_stmt->close();

        echo json_encode(["success" => true, "message" => "Resident deleted successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to delete resident"]);
    }

    $stmt->close();
    $check->close();
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}

$conn->close();
?>