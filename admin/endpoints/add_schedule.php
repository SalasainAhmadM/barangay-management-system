<?php
// ========== add_schedule.php ==========
session_start();
require_once("../../conn/conn.php");
header('Content-Type: application/json');

if (!isset($_SESSION["admin_id"])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$wasteType = $data['wasteType'] ?? '';
$collectionDays = $data['collectionDays'] ?? '';
$icon = $data['iconClass'] ?? 'fa-trash';
$color = $data['colorTheme'] ?? 'biodegradable';
$description = $data['description'] ?? '';
$isActive = $data['status'] ?? 1;

if (empty($wasteType) || empty($collectionDays)) {
    echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
    exit();
}

try {
    $stmt = $conn->prepare("INSERT INTO waste_schedules (waste_type, collection_days, icon, color, description, is_active) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssi", $wasteType, $collectionDays, $icon, $color, $description, $isActive);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Schedule added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add schedule']);
    }
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
$conn->close();
?>