<?php
session_start();
require_once("../../conn/conn.php");

header('Content-Type: application/json');

if (!isset($_SESSION["admin_id"])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $stmt = $conn->prepare("
        SELECT 
            schedule_id,
            waste_type,
            collection_days,
            description,
            is_active,
            icon,
            color,
            created_at
        FROM waste_schedules
        ORDER BY schedule_id
    ");
    $stmt->execute();
    $result = $stmt->get_result();

    $schedules = [];
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }

    echo json_encode([
        'success' => true,
        'schedules' => $schedules,
        'total' => count($schedules)
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching schedules: ' . $e->getMessage()
    ]);
}
?>