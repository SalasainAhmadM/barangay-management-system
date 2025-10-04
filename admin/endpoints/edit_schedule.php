<?php
// ========== edit_schedule.php ==========
session_start();
require_once("../../conn/conn.php");
header('Content-Type: application/json');

if (!isset($_SESSION["admin_id"])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? 0;
$wasteType = $data['wasteType'] ?? '';
$collectionDays = $data['collectionDays'] ?? '';
$icon = $data['iconClass'] ?? 'fa-trash';
$color = $data['colorTheme'] ?? 'biodegradable';
$description = $data['description'] ?? '';
$isActive = $data['status'] ?? 1;

if (empty($id) || empty($wasteType) || empty($collectionDays)) {
    echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
    exit();
}

try {
    $stmt = $conn->prepare("UPDATE waste_schedules SET waste_type = ?, collection_days = ?, icon = ?, color = ?, description = ?, is_active = ? WHERE schedule_id = ?");
    $stmt->bind_param("sssssii", $wasteType, $collectionDays, $icon, $color, $description, $isActive, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Schedule updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update schedule']);
    }
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>