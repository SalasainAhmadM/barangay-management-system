<?php
// ========== get_report.php ==========
session_start();
require_once("../../conn/conn.php");

header('Content-Type: application/json');

if (!isset($_SESSION["admin_id"])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$report_id = $_GET['id'] ?? 0;

if (empty($report_id)) {
    echo json_encode(['success' => false, 'message' => 'Report ID is required']);
    exit();
}

try {
    $stmt = $conn->prepare("
        SELECT mc.*, u.first_name, u.middle_name, u.last_name, u.email, u.contact_number, u.image 
        FROM missed_collections mc
        LEFT JOIN user u ON mc.user_id = u.id
        WHERE mc.report_id = ?
    ");
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Prepend path for photo if it exists
        if ($row['photo_path']) {
            $row['photo_path'] = 'assets/waste_reports/' . $row['photo_path'];
        }
        echo json_encode(['success' => true, 'report' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Report not found']);
    }

    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>