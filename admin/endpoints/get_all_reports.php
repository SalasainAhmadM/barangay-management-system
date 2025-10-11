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
            mc.report_id,
            mc.waste_type,
            mc.location,
            mc.collection_date,
            mc.status,
            mc.reason,
            mc.created_at,
            u.first_name,
            u.middle_name,
            u.last_name,
            u.email,
            u.contact_number
        FROM missed_collections mc
        LEFT JOIN user u ON mc.user_id = u.id
        ORDER BY mc.created_at DESC
    ");
    $stmt->execute();
    $result = $stmt->get_result();

    $reports = [];
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }

    echo json_encode([
        'success' => true,
        'reports' => $reports,
        'total' => count($reports)
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching reports: ' . $e->getMessage()
    ]);
}
?>