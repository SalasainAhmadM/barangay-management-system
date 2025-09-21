<?php
require_once("../../conn/conn.php");

header("Content-Type: application/json");

$id = $_GET["id"] ?? null;

if (!$id || !is_numeric($id)) {
    echo json_encode(["success" => false, "message" => "Invalid resident ID"]);
    exit();
}

try {
    $stmt = $conn->prepare("SELECT id, first_name, middle_name, last_name, email, contact_number, image, created_at
                           FROM user
                           WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $resident = $result->fetch_assoc();

    if ($resident) {
        // Format the created_at date for better display
        $resident['formatted_date'] = date("F j, Y", strtotime($resident['created_at']));

        // Ensure image field is properly handled (null if empty)
        $resident['image'] = !empty($resident['image']) ? $resident['image'] : null;

        echo json_encode(["success" => true, "resident" => $resident]);
    } else {
        echo json_encode(["success" => false, "message" => "Resident not found"]);
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>